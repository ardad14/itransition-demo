<?php

namespace App\Import;

use App\Import\Contracts\CsvReaderInterface;
use League\Csv\Reader;
use League\Csv\Statement;
use RuntimeException;

class CsvReader implements CsvReaderInterface
{
    public function getRecords(string $filePath): iterable
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("CSV file not found: {$filePath}");
        }

        $reader = Reader::createFromPath($filePath, 'r');

        // Skip the header row and work with numeric indices.
        // This way a renamed or reordered header won't break anything.
        // league/csv handles BOM and \r\n vs \n differences automatically.
        $statement = Statement::create()->offset(1);

        return $statement->process($reader);
    }
}
