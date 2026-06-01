<?php

namespace App\Exports\Sheets\Data;

use App\Exports\Sheets\AbstractDetailSheet;

class AnswersSheet extends AbstractDetailSheet
{
    protected function table(): string
    {
        return 'answers';
    }

    protected function columnMap(): array
    {
        return [
            'answer_id' => 'id',
            'player_id' => 'player_id',
            'question' => 'question',
            'answer' => 'answer',
            'occurred_at' => 'occurred_at',
        ];
    }

    protected function timeColumn(): ?string
    {
        return 'occurred_at';
    }

    public function title(): string
    {
        return 'Answers';
    }
}
