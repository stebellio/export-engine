<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExportRequest;
use App\Jobs\GenerateExportJob;
use App\Models\Export;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function store(Version $version, StoreExportRequest $request): JsonResponse
    {
        $data = $request->validated();

        $export = $version->exports()->create([
            'status' => Export::STATUS_PENDING,
            'format' => $data['format'] ?? 'xlsx',
            'config' => $data,
        ]);

        GenerateExportJob::dispatch($export->id);

        return response()->json($this->present($export), Response::HTTP_ACCEPTED);
    }

    public function show(Export $export): JsonResponse
    {
        return response()->json($this->present($export));
    }

    /**
     * @return JsonResponse|StreamedResponse
     */
    public function download(Export $export)
    {
        if ($export->status !== Export::STATUS_COMPLETED || $export->file_path === null) {
            return response()->json([
                'message' => 'Export is not ready for download.',
                'status' => $export->status,
            ], Response::HTTP_CONFLICT);
        }

        $disk = Storage::disk(config('export.disk'));

        if (! $disk->exists($export->file_path)) {
            return response()->json([
                'message' => 'Export file not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return $disk->download($export->file_path, $export->downloadName());
    }

    private function present(Export $export): array
    {
        return [
            'id' => $export->id,
            'version_id' => $export->version_id,
            'status' => $export->status,
            'format' => $export->format,
            'error_message' => $export->error_message,
            'download_url' => $export->status === Export::STATUS_COMPLETED
                ? url('/api/v1/exports/' . $export->id . '/download')
                : null,
            'created_at' => $export->created_at->toIso8601String(),
            'updated_at' => $export->updated_at->toIso8601String(),
        ];
    }
}
