<?php

namespace Tests\Unit\Import;

use App\Import\Data\ImportReport;
use PHPUnit\Framework\TestCase;

class ImportReportTest extends TestCase
{
    public function test_initial_counts_are_zero(): void
    {
        $report = new ImportReport();

        $this->assertSame(0, $report->getProcessedCount());
        $this->assertSame(0, $report->getSuccessCount());
        $this->assertSame(0, $report->getSkippedCount());
        $this->assertSame(0, $report->getFailedCount());
        $this->assertEmpty($report->getFailedRows());
    }

    public function test_increment_processed(): void
    {
        $report = new ImportReport();
        $report->incrementProcessed();
        $report->incrementProcessed();

        $this->assertSame(2, $report->getProcessedCount());
    }

    public function test_increment_success(): void
    {
        $report = new ImportReport();
        $report->incrementSuccess();

        $this->assertSame(1, $report->getSuccessCount());
    }

    public function test_increment_skipped(): void
    {
        $report = new ImportReport();
        $report->incrementSkipped();

        $this->assertSame(1, $report->getSkippedCount());
    }

    public function test_add_failure_records_code_and_reason(): void
    {
        $report = new ImportReport();
        $report->addFailure('P0011', 'Invalid row format');

        $this->assertSame(1, $report->getFailedCount());
        $this->assertSame([
            ['code' => 'P0011', 'reason' => 'Invalid row format'],
        ], $report->getFailedRows());
    }

    public function test_get_summary_returns_exact_string(): void
    {
        $report = new ImportReport();
        $report->incrementProcessed();
        $report->incrementProcessed();
        $report->incrementProcessed();
        $report->incrementSuccess();
        $report->incrementSuccess();
        $report->incrementSkipped();
        $report->addFailure('P0001', 'Some error');

        $this->assertSame(
            'Processed: 3 | Imported: 2 | Skipped: 1 | Failed: 1',
            $report->getSummary()
        );
    }
}
