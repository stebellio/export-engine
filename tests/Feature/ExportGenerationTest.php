<?php

namespace Tests\Feature;

use App\Models\Export;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
use Tests\TestCase;

class ExportGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_generates_file_with_only_metadata_sheets()
    {
        Storage::fake('local');
        $version = Version::factory()->create(['name' => 'Campagna Demo']);

        $payload = [
            'format' => 'xlsx',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'sheets' => [
                ['name' => 'players', 'columns' => ['player_id', 'email'], 'sort' => ['registered_at:desc']],
                ['name' => 'events_summary', 'group_by' => ['type'], 'metrics' => ['count', 'unique_players']],
            ],
        ];

        // Queue sync in testing: il job gira inline durante la richiesta.
        $response = $this->postJson("/api/v1/versions/{$version->id}/exports", $payload);
        $response->assertStatus(202);

        $export = Export::firstOrFail();
        $this->assertSame(Export::STATUS_COMPLETED, $export->status);
        $this->assertNotNull($export->file_path);

        $disk = Storage::disk('local');
        $this->assertTrue($disk->exists($export->file_path));

        [$sheetNames, $sheetsData] = $this->readWorkbook($disk->path($export->file_path));

        // Solo i due fogli metadata, niente fogli dati in questa fase.
        $this->assertSame(['README', 'Configurazione_Richiesta'], $sheetNames);

        $readme = $this->pairs($sheetsData['README']);
        $this->assertSame((string) $version->id, $readme['Version ID']);
        $this->assertSame('Campagna Demo', $readme['Versione']);
        $this->assertSame('xlsx', $readme['Formato']);
        $this->assertSame('2026-01-01 - 2026-01-31', $readme['Periodo']);

        $config = $this->pairs($sheetsData['Configurazione_Richiesta']);
        $this->assertSame('xlsx', $config['format']);
        $this->assertSame('players, events_summary', $config['sheets']);
        $this->assertSame('player_id, email', $config['players.columns']);
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

    /**
     * Converte le righe [chiave, valore] di un foglio key/value in mappa.
     *
     * @param array<int, array<int, string>> $rows
     * @return array<string, string>
     */
    private function pairs(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            if (isset($row[0])) {
                $map[(string) $row[0]] = (string) ($row[1] ?? '');
            }
        }

        return $map;
    }
}
