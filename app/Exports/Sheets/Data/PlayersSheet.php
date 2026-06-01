<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractDetailSheet;

/**
 * Foglio dati "players" (detail). Colonne dirette; range temporale su registered_at.
 */
class PlayersSheet extends AbstractDetailSheet
{
    protected function table(): string
    {
        return 'players';
    }

    protected function columnMap(): array
    {
        return [
            'player_id' => 'id',
            'email' => 'email',
            'registered_at' => 'registered_at',
        ];
    }

    protected function timeColumn(): ?string
    {
        return 'registered_at';
    }

    public function title(): string
    {
        return 'Players';
    }
}
