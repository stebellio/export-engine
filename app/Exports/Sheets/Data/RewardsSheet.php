<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractDetailSheet;

class RewardsSheet extends AbstractDetailSheet
{
    protected function table(): string
    {
        return 'rewards';
    }

    protected function columnMap(): array
    {
        return [
            'reward_id' => 'id',
            'player_id' => 'player_id',
            'name' => 'name',
            'value' => 'value',
            'occurred_at' => 'occurred_at',
        ];
    }

    protected function timeColumn(): ?string
    {
        return 'occurred_at';
    }

    public function title(): string
    {
        return 'Rewards';
    }
}
