<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractSummarySheet;
use LogicException;

/**
 * Foglio "events_summary" (summary): aggregazione degli eventi per dimensioni
 * (`type` + `payload.*`) con metriche count / unique_players.
 *
 * NOTA: per ora la classe dichiara solo lo schema (validazione).
 * Il rendering (`rows()`) viene aggiunto nello step dedicato ai summary sheets.
 */
class EventsSummarySheet extends AbstractSummarySheet
{
    protected function allowedGroupBy(): array
    {
        return ['type'];
    }

    protected function allowedMetrics(): array
    {
        return ['count', 'unique_players'];
    }

    protected function supportsPayload(): bool
    {
        return true;
    }

    public function title(): string
    {
        return 'Events_Summary';
    }

    public function rows(): iterable
    {
        throw new LogicException('Rendering del foglio "events_summary" non ancora implementato.');
    }
}
