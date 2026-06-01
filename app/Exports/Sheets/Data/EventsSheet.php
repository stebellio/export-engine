<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractDetailSheet;
use LogicException;

/**
 * Foglio dati "events" (detail). Ammette colonne/filtri/sort su `payload.*`.
 *
 * NOTA: per ora la classe dichiara solo lo schema (validazione).
 * Il rendering (`rows()`) viene aggiunto nello step dedicato ai detail sheets.
 */
class EventsSheet extends AbstractDetailSheet
{
    protected function allowedColumns(): array
    {
        return ['event_id', 'player_id', 'type', 'occurred_at'];
    }

    protected function supportsPayload(): bool
    {
        return true;
    }

    public function title(): string
    {
        return 'Events';
    }

    public function rows(): iterable
    {
        throw new LogicException('Rendering del foglio "events" non ancora implementato.');
    }
}
