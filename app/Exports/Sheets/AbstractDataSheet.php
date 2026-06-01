<?php

namespace App\Exports\Sheets;

use App\Models\Version;

/**
 * Base for requestable data sheets.
 *
 * Constructor args are optional on purpose: a no-arg instance is used for
 * validation (no data needed), a fully-built one for rendering. The default
 * empty rows() yields a present-but-empty tab for sheets not yet implemented.
 */
abstract class AbstractDataSheet extends AbstractSheet
{
    /** @var Version|null */
    protected $version;

    /** @var array */
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
