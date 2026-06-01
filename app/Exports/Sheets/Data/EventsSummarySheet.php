<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractSummarySheet;

/**
 * Foglio "events_summary" (summary): aggregazione degli eventi per dimensioni
 * (`type` + `payload.*`) con metriche count / unique_players.
 *
 * NOTA: schema dichiarato; `rows()` eredita il default vuoto finché non si
 * implementa il rendering nello step dedicato ai summary sheets.
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
}
