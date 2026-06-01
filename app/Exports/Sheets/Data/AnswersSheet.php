<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractDetailSheet;
use LogicException;

/**
 * Foglio dati "answers" (detail).
 *
 * NOTA: per ora la classe dichiara solo lo schema (validazione).
 * Il rendering (`rows()`) viene aggiunto nello step dedicato ai detail sheets.
 */
class AnswersSheet extends AbstractDetailSheet
{
    protected function allowedColumns(): array
    {
        return ['answer_id', 'player_id', 'question', 'answer', 'occurred_at'];
    }

    public function title(): string
    {
        return 'Answers';
    }

    public function rows(): iterable
    {
        throw new LogicException('Rendering del foglio "answers" non ancora implementato.');
    }
}
