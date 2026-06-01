<?php

namespace Tests\Feature;

use App\Models\Export;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeExport(string $status = Export::STATUS_PENDING, ?string $filePath = null): Export
    {
        $version = Version::factory()->create();

        return $version->exports()->create([
            'status' => $status,
            'format' => 'xlsx',
            'config' => ['sheets' => []],
            'file_path' => $filePath,
        ]);
    }

    public function test_show_returns_status_without_download_url_when_pending()
    {
        $export = $this->makeExport(Export::STATUS_PENDING);

        $this->getJson("/api/v1/exports/{$export->id}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('download_url', null);
    }

    public function test_show_returns_download_url_when_completed()
    {
        $export = $this->makeExport(Export::STATUS_COMPLETED, 'exports/export-1.xlsx');

        $this->getJson("/api/v1/exports/{$export->id}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('download_url', url("/api/v1/exports/{$export->id}/download"));
    }

    public function test_download_conflicts_when_not_completed()
    {
        $export = $this->makeExport(Export::STATUS_PROCESSING);

        $this->getJson("/api/v1/exports/{$export->id}/download")->assertStatus(409);
    }

    public function test_download_not_found_when_file_missing()
    {
        Storage::fake('local');
        $export = $this->makeExport(Export::STATUS_COMPLETED, 'exports/missing.xlsx');

        $this->getJson("/api/v1/exports/{$export->id}/download")->assertStatus(404);
    }

    public function test_download_streams_the_file_when_completed()
    {
        Storage::fake('local');
        Storage::disk('local')->put('exports/export-x.xlsx', 'binary-content');
        $export = $this->makeExport(Export::STATUS_COMPLETED, 'exports/export-x.xlsx');

        $response = $this->get("/api/v1/exports/{$export->id}/download");

        $response->assertStatus(200);
        $this->assertStringContainsString('export-'.$export->id.'.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_status_transition_methods_update_the_record()
    {
        $export = $this->makeExport(Export::STATUS_PENDING);

        $export->markProcessing();
        $this->assertSame(Export::STATUS_PROCESSING, $export->fresh()->status);

        $export->markCompleted('exports/done.xlsx');
        $fresh = $export->fresh();
        $this->assertSame(Export::STATUS_COMPLETED, $fresh->status);
        $this->assertSame('exports/done.xlsx', $fresh->file_path);

        $export->markFailed('boom');
        $this->assertSame(Export::STATUS_FAILED, $export->fresh()->status);
        $this->assertSame('boom', $export->fresh()->error_message);
    }
}
