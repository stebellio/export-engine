<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePlayersRequest;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    public function store(Version $version, StorePlayersRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];
        $now = Carbon::now()->format('Y-m-d H:i:s');

        $rows = array_map(function (array $item) use ($version, $now) {
            return [
                'version_id' => $version->id,
                'email' => $item['email'] ?? null,
                'registered_at' => isset($item['registered_at'])
                    ? Carbon::parse($item['registered_at'])->format('Y-m-d H:i:s')
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $items);

        $firstId = DB::transaction(function () use ($rows) {
            DB::table('players')->insert($rows);
            return (int) DB::getPdo()->lastInsertId();
        });

        $response = [];
        foreach ($rows as $i => $row) {
            $response[] = [
                'id' => $firstId + $i,
                'email' => $row['email'],
                'registered_at' => $row['registered_at'],
            ];
        }

        return response()->json([
            'count' => count($response),
            'items' => $response,
        ], Response::HTTP_CREATED);
    }
}
