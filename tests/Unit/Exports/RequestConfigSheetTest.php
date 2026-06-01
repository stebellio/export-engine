<?php

namespace Tests\Unit\Exports;

use App\Exports\Sheets\Metadata\RequestConfigSheet;
use PHPUnit\Framework\TestCase;

class RequestConfigSheetTest extends TestCase
{
    public function test_flatten_produces_expected_param_value_pairs()
    {
        $config = [
            'format' => 'xlsx',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'sheets' => [
                [
                    'name' => 'players',
                    'columns' => ['player_id', 'email'],
                    'filters' => ['email' => 'a@b.test'],
                    'sort' => ['registered_at:desc'],
                ],
                [
                    'name' => 'events_summary',
                    'group_by' => ['type', 'payload.language'],
                    'metrics' => ['count', 'unique_players'],
                ],
            ],
        ];

        $flat = (new RequestConfigSheet($config))->flatten($config);

        $this->assertSame('xlsx', $flat['format']);
        $this->assertSame('2026-01-01', $flat['date_from']);
        $this->assertSame('2026-01-31', $flat['date_to']);
        $this->assertSame('players, events_summary', $flat['sheets']);
        $this->assertSame('player_id, email', $flat['players.columns']);
        $this->assertSame('email=a@b.test', $flat['players.filters']);
        $this->assertSame('registered_at:desc', $flat['players.sort']);
        $this->assertSame('type, payload.language', $flat['events_summary.group_by']);
        $this->assertSame('count, unique_players', $flat['events_summary.metrics']);
    }

    public function test_flatten_defaults_format_and_handles_missing_dates()
    {
        $flat = (new RequestConfigSheet([]))->flatten([]);

        $this->assertSame('xlsx', $flat['format']);
        $this->assertArrayNotHasKey('date_from', $flat);
        $this->assertArrayNotHasKey('date_to', $flat);
        $this->assertSame('', $flat['sheets']);
    }
}
