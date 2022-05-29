<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FileImport implements ToArray, WithCustomCsvSettings, WithHeadingRow
{
    public function array(array $array): array
    {
        // do nothing
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => "\t"
        ];
    }
}