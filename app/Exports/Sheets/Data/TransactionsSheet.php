<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractDetailSheet;
use LogicException;

/**
 * Foglio dati "transactions" (detail). Ammette colonne/filtri/sort su `payload.*`.
 *
 * NOTA: per ora la classe dichiara solo lo schema (validazione).
 * Il rendering (`rows()`) viene aggiunto nello step dedicato ai detail sheets.
 */
class TransactionsSheet extends AbstractDetailSheet
{
    protected function allowedColumns(): array
    {
        return ['transaction_id', 'player_id', 'amount', 'currency', 'occurred_at'];
    }

    protected function supportsPayload(): bool
    {
        return true;
    }

    public function title(): string
    {
        return 'Transactions';
    }

    public function rows(): iterable
    {
        throw new LogicException('Rendering del foglio "transactions" non ancora implementato.');
    }
}
