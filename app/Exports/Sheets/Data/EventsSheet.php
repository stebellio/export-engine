<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractDetailSheet;

class EventsSheet extends AbstractDetailSheet
{
    protected function table(): string
    {
        return 'events';
    }

    protected function columnMap(): array
    {
        return [
            'event_id' => 'id',
            'player_id' => 'player_id',
            'type' => 'type',
            'occurred_at' => 'occurred_at',
        ];
    }

    protected function timeColumn(): ?string
    {
        return 'occurred_at';
    }

    protected function supportsPayload(): bool
    {
        return true;
    }

    public function title(): string
    {
        return 'Events';
    }
}
