<?php

namespace Tests\Unit\Exports;

use App\Exports\Sheets\Data\EventsSummarySheet;
use App\Exports\Sheets\Data\PlayersSheet;
use App\Exports\Sheets\Data\TransactionsSheet;
use PHPUnit\Framework\TestCase;

class SheetValidationTest extends TestCase
{
    public function test_valid_detail_config_passes()
    {
        $errors = (new PlayersSheet())->validate([
            'name' => 'players',
            'columns' => ['player_id', 'email'],
            'filters' => ['email' => 'a@b.test'],
            'sort' => ['registered_at:desc', 'player_id'],
        ]);

        $this->assertSame([], $errors);
    }

    public function test_detail_rejects_unknown_column()
    {
        $errors = (new PlayersSheet())->validate(['columns' => ['player_id', 'nope']]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString("colonna non ammessa: 'nope'", $errors[0]);
    }

    public function test_detail_requires_columns()
    {
        $errors = (new PlayersSheet())->validate(['name' => 'players']);

        $this->assertContains("manca 'columns' (obbligatorio per un foglio detail)", $errors);
    }

    public function test_payload_path_allowed_only_when_supported()
    {
        // TransactionsSheet supporta payload.*
        $this->assertSame([], (new TransactionsSheet())->validate([
            'columns' => ['amount', 'payload.gateway'],
        ]));

        // PlayersSheet non supporta payload.*
        $this->assertNotEmpty((new PlayersSheet())->validate([
            'columns' => ['payload.language'],
        ]));
    }

    public function test_payload_path_with_invalid_characters_rejected()
    {
        $errors = (new TransactionsSheet())->validate(['columns' => ['payload.foo-bar']]);

        $this->assertNotEmpty($errors);
    }

    public function test_detail_rejects_invalid_filter_key()
    {
        $errors = (new PlayersSheet())->validate([
            'columns' => ['email'],
            'filters' => ['nope' => 'x'],
        ]);

        $this->assertContains("filtro non ammesso: 'nope'", $errors);
    }

    public function test_detail_rejects_bad_sort_direction()
    {
        $errors = (new PlayersSheet())->validate([
            'columns' => ['registered_at'],
            'sort' => ['registered_at:sideways'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('direzione di sort non valida', $errors[0]);
    }

    public function test_detail_rejects_summary_keys()
    {
        $errors = (new PlayersSheet())->validate([
            'columns' => ['player_id'],
            'group_by' => ['type'],
        ]);

        $this->assertContains("chiave non ammessa: 'group_by'", $errors);
    }

    public function test_valid_summary_config_passes()
    {
        $errors = (new EventsSummarySheet())->validate([
            'name' => 'events_summary',
            'group_by' => ['type', 'payload.language'],
            'metrics' => ['count', 'unique_players'],
        ]);

        $this->assertSame([], $errors);
    }

    public function test_summary_rejects_unknown_metric()
    {
        $errors = (new EventsSummarySheet())->validate([
            'group_by' => ['type'],
            'metrics' => ['count', 'sum_of_everything'],
        ]);

        $this->assertContains("metrica non ammessa: 'sum_of_everything'", $errors);
    }

    public function test_summary_requires_group_by_and_metrics()
    {
        $errors = (new EventsSummarySheet())->validate(['name' => 'events_summary']);

        $this->assertContains("manca 'group_by' (obbligatorio per un foglio summary)", $errors);
        $this->assertContains("manca 'metrics' (obbligatorio per un foglio summary)", $errors);
    }
}
