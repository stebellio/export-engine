<?php

namespace App\Exports;

use App\Exports\Sheets\Data\AnswersSheet;
use App\Exports\Sheets\Data\EventsSheet;
use App\Exports\Sheets\Data\EventsSummarySheet;
use App\Exports\Sheets\Data\PlayersSheet;
use App\Exports\Sheets\Data\RewardsSheet;
use App\Exports\Sheets\Data\TransactionsSheet;
use App\Exports\Sheets\SheetInterface;

/**
 * Anagrafica di tutti i fogli richiedibili dal client: mappa `name` → classe-foglio.
 * Sorgente unica per validazione e (dai prossimi step) rendering.
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

    /**
     * Istanza del foglio per il nome dato, o null se sconosciuto.
     */
    public function get(string $name): ?SheetInterface
    {
        $class = self::MAP[$name] ?? null;

        return $class ? new $class() : null;
    }
}
