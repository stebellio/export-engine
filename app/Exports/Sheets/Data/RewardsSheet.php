<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractDetailSheet;
use LogicException;

/**
 * Foglio dati "rewards" (detail).
 *
 * NOTA: per ora la classe dichiara solo lo schema (validazione).
 * Il rendering (`rows()`) viene aggiunto nello step dedicato ai detail sheets.
 */
class RewardsSheet extends AbstractDetailSheet
{
    protected function allowedColumns(): array
    {
        return ['reward_id', 'player_id', 'name', 'value', 'occurred_at'];
    }

    public function title(): string
    {
        return 'Rewards';
    }

    public function rows(): iterable
    {
        throw new LogicException('Rendering del foglio "rewards" non ancora implementato.');
    }
}
