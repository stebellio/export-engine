<?php

namespace Tests\Unit\Exports;

use App\Exports\Sheets\Metadata\ReadmeSheet;
use App\Models\Export;
use App\Models\Version;
use Tests\TestCase;

class ReadmeSheetTest extends TestCase
{
    private function pairs(ReadmeSheet $sheet): array
    {
        $map = [];
        foreach ($sheet->rows() as [$key, $value]) {
            $map[$key] = $value;
        }

        return $map;
    }

    public function test_readme_reports_version_format_and_period()
    {
        $export = new Export([
            'version_id' => 42,
            'format' => 'xlsx',
            'config' => ['date_from' => '2026-01-01', 'date_to' => '2026-01-31'],
        ]);
        $export->setRelation('version', new Version(['name' => 'My Version']));

        $pairs = $this->pairs(new ReadmeSheet($export));

        $this->assertSame(42, $pairs['Version ID']);
        $this->assertSame('My Version', $pairs['Versione']);
        $this->assertSame('xlsx', $pairs['Formato']);
        $this->assertSame('2026-01-01 - 2026-01-31', $pairs['Periodo']);
        $this->assertArrayHasKey('Generato il', $pairs);
    }

    public function test_period_is_tutto_when_no_dates()
    {
        $export = new Export(['version_id' => 1, 'format' => 'xlsx', 'config' => []]);
        $export->setRelation('version', new Version(['name' => 'V']));

        $pairs = $this->pairs(new ReadmeSheet($export));

        $this->assertSame('tutto', $pairs['Periodo']);
    }
}
