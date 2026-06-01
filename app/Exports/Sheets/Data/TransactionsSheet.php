<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractDetailSheet;

class TransactionsSheet extends AbstractDetailSheet
{
    protected function table(): string
    {
        return 'transactions';
    }

    protected function columnMap(): array
    {
        return [
            'transaction_id' => 'id',
            'player_id' => 'player_id',
            'amount' => 'amount',
            'currency' => 'currency',
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
        return 'Transactions';
    }
}
