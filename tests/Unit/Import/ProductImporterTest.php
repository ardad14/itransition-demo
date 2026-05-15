<?php

namespace Tests\Unit\Import;

use App\Import\Contracts\CsvReaderInterface;
use App\Import\Contracts\ImportRuleInterface;
use App\Import\Contracts\ProductRepositoryInterface;
use App\Import\Data\ImportReport;
use App\Import\Data\ProductData;
use App\Import\ProductImporter;
use App\Import\ProductParser;
use App\Import\Rules\HighPriceRule;
use App\Import\Rules\LowValueLowStockRule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductImporterTest extends TestCase
{
    /** @var CsvReaderInterface&MockObject */
    private CsvReaderInterface $reader;

    /** @var ProductRepositoryInterface&MockObject */
    private ProductRepositoryInterface $repository;

    private ProductParser $parser;

    protected function setUp(): void
    {
        $this->reader = $this->createMock(CsvReaderInterface::class);
        $this->repository = $this->createMock(ProductRepositoryInterface::class);
        $this->parser = new ProductParser();
    }

    private function makeImporter(array $rows, bool $testMode = false, array $rules = []): ProductImporter
    {
        $this->reader->method('getRecords')->willReturn($rows);

        return new ProductImporter(
            reader: $this->reader,
            parser: $this->parser,
            repository: $this->repository,
            report: new ImportReport(),
            rules: $rules ?: [new LowValueLowStockRule(), new HighPriceRule()],
            testMode: $testMode,
        );
    }

    // -------------------------------------------------------------------------
    // Basic pipeline
    // -------------------------------------------------------------------------

    public function test_imports_valid_product_and_counts_success(): void
    {
        $this->repository->expects($this->once())->method('save');

        $report = $this->makeImporter([
            ['P0001', 'TV', '32" TV', '10', '399.99', ''],
        ])->import('file.csv');

        $this->assertSame(1, $report->getProcessedCount());
        $this->assertSame(1, $report->getSuccessCount());
        $this->assertSame(0, $report->getSkippedCount());
        $this->assertSame(0, $report->getFailedCount());
    }

    // -------------------------------------------------------------------------
    // Test mode
    // -------------------------------------------------------------------------

    public function test_mode_does_not_call_repository_save(): void
    {
        $this->repository->expects($this->never())->method('save');

        $report = $this->makeImporter(
            [['P0001', 'TV', '32" TV', '10', '399.99', '']],
            testMode: true,
        )->import('file.csv');

        $this->assertSame(1, $report->getSuccessCount());
    }

    // -------------------------------------------------------------------------
    // Rule: low value + low stock
    // -------------------------------------------------------------------------

    public function test_skips_product_with_low_price_and_low_stock(): void
    {
        $this->repository->expects($this->never())->method('save');

        // price=4.22, stock=4 — both below threshold
        $report = $this->makeImporter([
            ['P0017', 'CPU', 'Fast CPU', '4', '4.22', ''],
        ])->import('file.csv');

        $this->assertSame(1, $report->getSkippedCount());
        $this->assertSame(0, $report->getSuccessCount());
    }

    public function test_does_not_skip_when_only_price_is_low(): void
    {
        $this->repository->expects($this->once())->method('save');

        // price=3.44, stock=23 — price<5 but stock>=10 → import
        $report = $this->makeImporter([
            ['P0019', 'CD Bundle', 'Data storage', '23', '3.44', ''],
        ])->import('file.csv');

        $this->assertSame(0, $report->getSkippedCount());
        $this->assertSame(1, $report->getSuccessCount());
    }

    // -------------------------------------------------------------------------
    // Rule: high price
    // -------------------------------------------------------------------------

    public function test_skips_product_with_price_above_1000(): void
    {
        $this->repository->expects($this->never())->method('save');

        // P0027: price=1200.03
        $report = $this->makeImporter([
            ['P0027', 'VCR', 'Plays videos', '34', '1200.03', 'yes'],
        ])->import('file.csv');

        $this->assertSame(1, $report->getSkippedCount());
    }

    // -------------------------------------------------------------------------
    // Discontinued products
    // -------------------------------------------------------------------------

    public function test_imports_discontinued_product(): void
    {
        $savedData = null;
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (ProductData $d) use (&$savedData) {
                $savedData = $d;
            });

        $this->makeImporter([
            ['P0002', 'Cd Player', 'Nice CD player', '11', '50.12', 'yes'],
        ])->import('file.csv');

        $this->assertTrue($savedData->discontinued);
    }

    // -------------------------------------------------------------------------
    // Invalid rows
    // -------------------------------------------------------------------------

    public function test_records_failure_for_malformed_row(): void
    {
        // P0011 — only 3 fields
        $report = $this->makeImporter([
            ['P0011', 'Misc Cables', 'error in export'],
        ])->import('file.csv');

        $this->assertSame(1, $report->getProcessedCount());
        $this->assertSame(0, $report->getSuccessCount());
        $this->assertSame(1, $report->getFailedCount());
        $this->assertSame('P0011', $report->getFailedRows()[0]['code']);
    }

    public function test_records_failure_when_repository_throws(): void
    {
        $this->repository
            ->method('save')
            ->willThrowException(new \RuntimeException('Duplicate entry'));

        $report = $this->makeImporter([
            ['P0001', 'TV', '32" TV', '10', '399.99', ''],
        ])->import('file.csv');

        $this->assertSame(1, $report->getFailedCount());
        $this->assertStringContainsString('Duplicate', $report->getFailedRows()[0]['reason']);
    }

    // -------------------------------------------------------------------------
    // Multiple rows
    // -------------------------------------------------------------------------

    public function test_processes_multiple_rows_independently(): void
    {
        $this->repository->expects($this->exactly(2))->method('save');

        $report = $this->makeImporter([
            ['P0001', 'TV', 'TV desc', '10', '399.99', ''],         // imported
            ['P0027', 'VCR', 'VCR desc', '34', '1200.03', 'yes'],   // skipped: price > 1000
            ['P0002', 'CD Player', 'CD desc', '11', '50.12', 'yes'], // imported (discontinued)
            ['P0011', 'Cables', 'bad'],                               // failed: too few fields
        ])->import('file.csv');

        $this->assertSame(4, $report->getProcessedCount());
        $this->assertSame(2, $report->getSuccessCount());
        $this->assertSame(1, $report->getSkippedCount());
        $this->assertSame(1, $report->getFailedCount());
    }

    public function test_propagates_runtime_exception_from_reader(): void
    {
        $this->reader
            ->method('getRecords')
            ->willThrowException(new \RuntimeException('File not found'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        (new ProductImporter(
            reader: $this->reader,
            parser: $this->parser,
            repository: $this->repository,
            report: new ImportReport(),
            rules: [],
        ))->import('missing.csv');
    }
}
