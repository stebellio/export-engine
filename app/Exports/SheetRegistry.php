<?php

namespace App\Exports;

use App\Exports\Sheets\Data\AnswersSheet;
use App\Exports\Sheets\Data\EventsSheet;
use App\Exports\Sheets\Data\EventsSummarySheet;
use App\Exports\Sheets\Data\PlayersSheet;
use App\Exports\Sheets\Data\RewardsSheet;
use App\Exports\Sheets\Data\TransactionsSheet;
use App\Exports\Sheets\SheetInterface;
use App\Models\Version;

/**
 * Maps a requestable sheet name to its class. Single source for both validation
 * (no context) and rendering (with version/config/date range).
 */
class SheetRegistry
{
    private const MAP = [
        'players' => PlayersSheet::class,
        'events' => EventsSheet::class,
        'transactions' => TransactionsSheet::class,
        'answers' => AnswersSheet::class,
        'rewards' => RewardsSheet::class,
        'events_summary' => EventsSummarySheet::class,
    ];

    /**
     * @return string[]
     */
    public function names(): array
    {
        return array_keys(self::MAP);
    }

    public function has(string $name): bool
    {
        return isset(self::MAP[$name]);
    }

    public function get(
        string $name,
        ?Version $version = null,
        array $config = [],
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): ?SheetInterface {
        $class = self::MAP[$name] ?? null;

        return $class ? new $class($version, $config, $dateFrom, $dateTo) : null;
    }
}
