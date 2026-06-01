<?php

namespace Tests\Feature;

use App\Models\Export;
use App\Models\Player;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
use Tests\TestCase;

class ExportGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_contains_metadata_and_requested_sheets_in_order()
    {
        Storage::fake('local');
        $version = Version::factory()->create(['name' => 'Campagna Demo']);

        Player::factory()->for($version)->create(['email' => 'alice@example.test', 'registered_at' => '2026-01-10 09:00:00']);
        Player::factory()->for($version)->create(['email' => 'bob@example.test', 'registered_at' => '2026-01-20 09:00:00']);

        $payload = [
            'format' => 'xlsx',
            'sheets' => [
                ['name' => 'players', 'columns' => ['player_id', 'email', 'registered_at'], 'sort' => ['registered_at:desc']],
                ['name' => 'events_summary', 'group_by' => ['type'], 'metrics' => ['count']],
            ],
        ];

        $this->postJson("/api/v1/versions/{$version->id}/exports", $payload)->assertStatus(202);

        $export = Export::firstOrFail();
        $this->assertSame(Export::STATUS_COMPLETED, $export->status);

        $disk = Storage::disk('local');
        [$sheetNames, $sheetsData] = $this->readWorkbook($disk->path($export->file_path));

        $this->assertSame(['README', 'Configurazione_Richiesta', 'Players', 'Events_Summary'], $sheetNames);

        // Players: header + righe ordinate per registered_at desc (bob prima di alice).
        $players = $sheetsData['Players'];
        $this->assertSame(['player_id', 'email', 'registered_at'], $players[0]);
        $this->assertSame('bob@example.test', $players[1][1]);
        $this->assertSame('alice@example.test', $players[2][1]);
        $this->assertCount(3, $players);

        // Events_Summary: summary non ancora implementato → tab vuoto.
        $this->assertSame([], $sheetsData['Events_Summary']);
    }

    public function test_players_sheet_applies_filter_and_date_range()
    {
        Storage::fake('local');
        $version = Version::factory()->create();

        Player::factory()->for($version)->create(['email' => 'keep@example.test', 'registered_at' => '2026-01-15 09:00:00']);
        Player::factory()->for($version)->create(['email' => 'out@example.test', 'registered_at' => '2025-12-01 09:00:00']);
        Player::factory()->for($version)->create(['email' => 'filtered@example.test', 'registered_at' => '2026-01-16 09:00:00']);

        $payload = [
            'format' => 'xlsx',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'sheets' => [
                ['name' => 'players', 'columns' => ['email'], 'filters' => ['email' => 'keep@example.test']],
            ],
        ];

        $this->postJson("/api/v1/versions/{$version->id}/exports", $payload)->assertStatus(202);

        [, $sheetsData] = $this->readWorkbook(Storage::disk('local')->path(Export::firstOrFail()->file_path));

        $players = $sheetsData['Players'];
        $this->assertSame(['email'], $players[0]);
        $this->assertCount(2, $players); // header + solo keep@
        $this->assertSame('keep@example.test', $players[1][0]);
    }

    public function test_events_sheet_renders_and_filters_payload_fields()
    {
        Storage::fake('local');
        $version = Version::factory()->create();
        $player = Player::factory()->for($version)->create();

        $this->insertEvent($version->id, $player->id, 'open', ['language' => 'it', 'score' => 10], '2026-01-05 10:00:00');
        $this->insertEvent($version->id, $player->id, 'share', ['language' => 'en', 'score' => 20], '2026-01-06 10:00:00');
        $this->insertEvent($version->id, $player->id, 'complete', ['language' => 'it', 'score' => 30], '2026-01-07 10:00:00');

        $payload = [
            'format' => 'xlsx',
            'sheets' => [
                [
                    'name' => 'events',
                    'columns' => ['type', 'payload.language', 'payload.score'],
                    'filters' => ['payload.language' => 'it'],
                    'sort' => ['payload.score:desc'],
                ],
            ],
        ];

        $this->postJson("/api/v1/versions/{$version->id}/exports", $payload)->assertStatus(202);

        [, $sheetsData] = $this->readWorkbook(Storage::disk('local')->path(Export::firstOrFail()->file_path));
        $events = $sheetsData['Events'];

        $this->assertSame(['type', 'payload.language', 'payload.score'], $events[0]);
        $this->assertCount(3, $events); // header + 2 eventi 'it'
        // Ordinati per payload.score desc: complete(30) poi open(10).
        $this->assertSame(['complete', 'it', '30'], $events[1]);
        $this->assertSame(['open', 'it', '10'], $events[2]);
    }

    private function insertEvent(int $versionId, int $playerId, string $type, array $payload, string $occurredAt): void
    {
        DB::table('events')->insert([
            'version_id' => $versionId,
            'player_id' => $playerId,
            'type' => $type,
            'occurred_at' => $occurredAt,
            'payload' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0: string[], 1: array<string, array<int, array<int, string>>>}
     */
    private function readWorkbook(string $path): array
    {
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($path);

        $names = [];
        $data = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $name = $sheet->getName();
            $names[] = $name;
            $data[$name] = [];
            foreach ($sheet->getRowIterator() as $row) {
                $data[$name][] = $row->toArray();
            }
        }
        $reader->close();

        return [$names, $data];
    }
}
