<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventStreamController extends Controller
{
    public function stream(Version $version): JsonResponse
    {
        @set_time_limit(0);

        $batchSize = (int) config('ingestion.max_items_per_batch');
        $validPlayerIds = Player::where('version_id', $version->id)->pluck('id')->flip();

        $input = fopen('php://input', 'r');
        if ($input === false) {
            return response()->json(['message' => 'Cannot read request body'], Response::HTTP_BAD_REQUEST);
        }

        $now = Carbon::now()->format('Y-m-d H:i:s');
        $buffer = [];
        $totalCount = 0;
        $batchCount = 0;
        $lineNum = 0;
        $error = null;

        try {
            while (($line = fgets($input)) !== false) {
                $lineNum++;
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $data = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                    $error = ['line' => $lineNum, 'message' => 'invalid JSON: ' . json_last_error_msg()];
                    break;
                }

                $validator = Validator::make($data, [
                    'player_id' => 'required|integer',
                    'type' => 'required|string|max:64',
                    'occurred_at' => 'required|date',
                    'payload' => 'nullable|array',
                ]);
                if ($validator->fails()) {
                    $error = ['line' => $lineNum, 'message' => $validator->errors()->first()];
                    break;
                }

                if (!isset($validPlayerIds[$data['player_id']])) {
                    $error = ['line' => $lineNum, 'message' => 'unknown player_id for this version: ' . $data['player_id']];
                    break;
                }

                $buffer[] = [
                    'version_id' => $version->id,
                    'player_id' => $data['player_id'],
                    'type' => $data['type'],
                    'occurred_at' => Carbon::parse($data['occurred_at'])->format('Y-m-d H:i:s'),
                    'payload' => isset($data['payload']) ? json_encode($data['payload']) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($buffer) >= $batchSize) {
                    DB::transaction(function () use ($buffer) {
                        DB::table('events')->insert($buffer);
                    });
                    $totalCount += count($buffer);
                    $batchCount++;
                    $buffer = [];
                }
            }

            if (!$error && $buffer) {
                DB::transaction(function () use ($buffer) {
                    DB::table('events')->insert($buffer);
                });
                $totalCount += count($buffer);
                $batchCount++;
            }
        } finally {
            fclose($input);
        }

        $payload = [
            'count' => $totalCount,
            'batches' => $batchCount,
        ];

        if ($error) {
            $payload['error'] = $error;
            return response()->json($payload, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($payload, Response::HTTP_CREATED);
    }
}
