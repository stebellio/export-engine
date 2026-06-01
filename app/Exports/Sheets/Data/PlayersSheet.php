<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractDetailSheet;
use LogicException;

/**
 * Foglio dati "players" (detail).
 *
 * NOTA: per ora la classe dichiara solo le colonne ammesse (validazione).
 * Il rendering (`rows()`) viene aggiunto nello step dedicato ai detail sheets.
 */
class PlayersSheet extends AbstractDetailSheet
{
    protected function allowedColumns(): array
    {
        return ['player_id', 'email', 'registered_at'];
    }

    public function title(): string
    {
        return 'Players';
    }

    public function rows(): iterable
    {
        throw new LogicException('Rendering del foglio "players" non ancora implementato.');
    }
}
