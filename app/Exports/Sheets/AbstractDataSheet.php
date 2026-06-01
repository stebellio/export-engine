<?php

namespace App\Exports\Sheets;

use App\Models\Version;

/**
 * Base dei fogli dati (richiedibili dal client). Porta il contesto di rendering
 * — versione, config del foglio, range temporale globale — iniettato in
 * costruzione. I parametri sono opzionali così l'istanza resta costruibile "a
 * vuoto" per la sola validazione (`new XxxSheet()` → `validate($config)`).
 *
 * `rows()` ha default vuoto: un foglio non ancora implementato produce un tab
 * presente ma senza righe. I fogli concreti lo sovrascrivono quando pronti.
 */
abstract class AbstractDataSheet extends AbstractSheet
{
    /** @var Version|null */
    protected $version;

    /** @var array config del singolo foglio (columns/filters/sort/group_by/metrics) */
    protected $config;

    /** @var string|null */
    protected $dateFrom;

    /** @var string|null */
    protected $dateTo;

    public function __construct(?Version $version = null, array $config = [], ?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->version = $version;
        $this->config = $config;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function rows(): iterable
    {
        return [];
    }
}
