<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\EnsuresPlayersInVersion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAnswersRequest;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnswerController extends Controller
{
    use EnsuresPlayersInVersion;

    public function store(Version $version, StoreAnswersRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];

        $this->ensurePlayersBelongToVersion($version, $items);

        $now = Carbon::now()->format('Y-m-d H:i:s');
        $rows = array_map(function (array $item) use ($version, $now) {
            return [
                'version_id' => $version->id,
                'player_id' => $item['player_id'],
                'question' => $item['question'],
                'answer' => $item['answer'],
                'occurred_at' => Carbon::parse($item['occurred_at'])->format('Y-m-d H:i:s'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $items);

        DB::transaction(function () use ($rows) {
            DB::table('answers')->insert($rows);
        });

        return response()->json(['count' => count($rows)], Response::HTTP_CREATED);
    }
}
