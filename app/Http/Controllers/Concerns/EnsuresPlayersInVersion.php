<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Player;
use App\Models\Version;
use Illuminate\Validation\ValidationException;

trait EnsuresPlayersInVersion
{
    protected function ensurePlayersBelongToVersion(Version $version, array $items): void
    {
        $ids = collect($items)->pluck('player_id')->unique()->values()->all();
        $existing = Player::where('version_id', $version->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
        $missing = array_values(array_diff($ids, $existing));

        if ($missing) {
            throw ValidationException::withMessages([
                'items' => 'Unknown player_id for this version: ' . implode(',', $missing),
            ]);
        }
    }
}
