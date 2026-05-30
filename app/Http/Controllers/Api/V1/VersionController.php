<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreVersionRequest;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class VersionController extends Controller
{
    public function store(StoreVersionRequest $request): JsonResponse
    {
        $version = Version::create($request->validated());

        return response()->json([
            'id' => $version->id,
            'name' => $version->name,
            'created_at' => $version->created_at->toIso8601String(),
        ], Response::HTTP_CREATED);
    }
}
