<?php

namespace App\Exports\Sheets;

interface SheetInterface
{
    /**
     * @return string[] validation error messages (empty when valid)
     */
    public function validate(array $config): array;

    public function title(): string;

    /**
     * @return iterable each item is an array of scalar cells
     */
    public function rows(): iterable;
}
