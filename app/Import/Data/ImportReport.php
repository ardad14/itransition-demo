<?php

namespace App\Import\Data;

// Tracks what happened during an import: how many rows were processed, imported, skipped, or failed.
class ImportReport
{
    private int $processedCount = 0;
    private int $successCount = 0;
    private int $skippedCount = 0;

    /** @var array<array{code: string, reason: string}> */
    private array $failedRows = [];

    public function incrementProcessed(): void
    {
        $this->processedCount++;
    }

    public function incrementSuccess(): void
    {
        $this->successCount++;
    }

    public function incrementSkipped(): void
    {
        $this->skippedCount++;
    }

    public function addFailure(string $productCode, string $reason): void
    {
        $this->failedRows[] = ['code' => $productCode, 'reason' => $reason];
    }

    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /** @return array<array{code: string, reason: string}> */
    public function getFailedRows(): array
    {
        return $this->failedRows;
    }

    public function getFailedCount(): int
    {
        return count($this->failedRows);
    }

    public function getSummary(): string
    {
        return sprintf(
            'Processed: %d | Imported: %d | Skipped: %d | Failed: %d',
            $this->processedCount,
            $this->successCount,
            $this->skippedCount,
            $this->getFailedCount(),
        );
    }
}
