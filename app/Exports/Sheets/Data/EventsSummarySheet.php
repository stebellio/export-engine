<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractSummarySheet;

/**
 * Foglio "events_summary" (summary): aggregazione degli eventi per dimensioni
 * (`type` + `payload.*`) con metriche count / unique_players.
 */
class EventsSummarySheet extends AbstractSummarySheet
{
    protected function table(): string
    {
        return 'events';
    }

    protected function timeColumn(): ?string
    {
        return 'occurred_at';
    }

    protected function groupByColumnMap(): array
    {
        return ['type' => 'type'];
    }

    protected function metricMap(): array
    {
        return [
            'count' => 'COUNT(*)',
            'unique_players' => 'COUNT(DISTINCT player_id)',
        ];
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
