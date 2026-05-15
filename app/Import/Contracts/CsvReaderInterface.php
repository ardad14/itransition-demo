<?php

namespace App\Import\Contracts;

// Returns rows from a CSV file one by one (lazy, so large files don't eat memory).
// Header row is excluded — callers get only data rows.
interface CsvReaderInterface
{
    /**
     * @return iterable<array<int, string>>
     * @throws \RuntimeException if the file can't be opened
     */
    public function getRecords(string $filePath): iterable;
}
