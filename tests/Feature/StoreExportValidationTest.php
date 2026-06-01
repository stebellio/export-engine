<?php

namespace Tests\Feature;

use App\Jobs\GenerateExportJob;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StoreExportValidationTest extends TestCase
{
    use RefreshDatabase;

    private function postExport(Version $version, array $payload)
    {
        return $this->postJson("/api/v1/versions/{$version->id}/exports", $payload);
    }

    public function test_valid_request_is_accepted()
    {
        Queue::fake();
        $version = Version::factory()->create();

        $response = $this->postExport($version, [
            'format' => 'xlsx',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'sheets' => [
                ['name' => 'players', 'columns' => ['player_id', 'email'], 'sort' => ['registered_at:desc']],
                ['name' => 'events_summary', 'group_by' => ['type', 'payload.language'], 'metrics' => ['count', 'unique_players']],
            ],
        ]);

        $response->assertStatus(202);
        Queue::assertPushed(GenerateExportJob::class);
    }

    public function test_unknown_sheet_name_is_rejected()
    {
        $version = Version::factory()->create();

        $response = $this->postExport($version, [
            'sheets' => [['name' => 'nope', 'columns' => ['x']]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['sheets.0.name']);
    }

    public function test_unknown_column_is_rejected()
    {
        $version = Version::factory()->create();

        $response = $this->postExport($version, [
            'sheets' => [['name' => 'players', 'columns' => ['player_id', 'nope']]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['sheets.0']);
    }

    public function test_unknown_metric_is_rejected()
    {
        $version = Version::factory()->create();

        $response = $this->postExport($version, [
            'sheets' => [[
                'name' => 'events_summary',
                'group_by' => ['type'],
                'metrics' => ['count', 'bogus'],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['sheets.0']);
    }

    public function test_sheets_are_required()
    {
        $version = Version::factory()->create();

        $response = $this->postExport($version, ['format' => 'xlsx']);

        $response->assertStatus(422)->assertJsonValidationErrors(['sheets']);
    }
}
