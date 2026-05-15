<?php

namespace App\Console\Commands;

use App\Import\Contracts\CsvReaderInterface;
use App\Import\Contracts\ProductRepositoryInterface;
use App\Import\Data\ImportReport;
use App\Import\ProductImporter;
use App\Import\ProductParser;
use App\Import\Rules\HighPriceRule;
use App\Import\Rules\LowValueLowStockRule;
use Illuminate\Console\Command;
use RuntimeException;


// Usage:
//   php artisan products:import /path/to/stock.csv
//   php artisan products:import /path/to/stock.csv --test   dry run, no DB writes
class ImportProductsCommand extends Command
{
    protected $signature = 'products:import
        {file : Absolute or relative path to the supplier CSV file}
        {--test : Dry-run mode — parse and validate without writing to the database}';

    protected $description = 'Import products from a supplier CSV file into tblProductData';

    public function __construct(
        private readonly CsvReaderInterface         $reader,
        private readonly ProductParser              $parser,
        private readonly ProductRepositoryInterface $repository,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $testMode = (bool)$this->option('test');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        if ($testMode) {
            $this->warn('Running in TEST MODE — no data will be written to the database.');
        }

        $rules = [
            new LowValueLowStockRule(),
            new HighPriceRule(),
        ];

        $importer = new ProductImporter(
            reader: $this->reader,
            parser: $this->parser,
            repository: $this->repository,
            report: new ImportReport(),
            rules: $rules,
            testMode: $testMode,
        );

        try {
            $report = $importer->import($filePath);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $this->info($report->getSummary());

        if ($report->getFailedCount() > 0) {
            $this->newLine();
            $this->warn('Failed rows:');
            foreach ($report->getFailedRows() as $failure) {
                $this->line("  - {$failure['code']}: {$failure['reason']}");
            }
        }

        return Command::SUCCESS;
    }
}
