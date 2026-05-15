<?php

namespace App\Import;

use App\Import\Contracts\CsvReaderInterface;
use App\Import\Contracts\ImportRuleInterface;
use App\Import\Contracts\ProductRepositoryInterface;
use App\Import\Data\ImportReport;
use InvalidArgumentException;
use Throwable;

// Runs the full import: read CSV → parse → check rules → save (or skip in test mode).
readonly class ProductImporter
{
    /** @param ImportRuleInterface[] $rules */
    public function __construct(
        private CsvReaderInterface         $reader,
        private ProductParser              $parser,
        private ProductRepositoryInterface $repository,
        private ImportReport               $report,
        private array                      $rules,
        private bool                       $testMode = false,
    )
    {
    }

    /** @throws \RuntimeException if the file can't be opened */
    public function import(string $filePath): ImportReport
    {
        foreach ($this->reader->getRecords($filePath) as $row) {
            $row = (array)$row;

            $this->report->incrementProcessed();

            try {
                $data = $this->parser->parse($row);
            } catch (InvalidArgumentException $e) {
                $this->report->addFailure(trim($row[0] ?? 'UNKNOWN'), $e->getMessage());
                continue;
            }

            foreach ($this->rules as $rule) {
                if (!$rule->isSatisfiedBy($data)) {
                    $this->report->incrementSkipped();
                    continue 2;
                }
            }

            if ($this->testMode) {
                $this->report->incrementSuccess();
                continue;
            }

            try {
                $this->repository->save($data);
                $this->report->incrementSuccess();
            } catch (Throwable $e) {
                $this->report->addFailure($data->productCode, $e->getMessage());
            }
        }

        return $this->report;
    }
}
