<?php


namespace App\Exports;


use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class MockDocExport implements FromArray, WithCustomCsvSettings
{
    /**
     * @return array
     */
    public function array(): array
    {
        // CASE
        return [
            ['Zoho case id', 'Priority task id ', 'Status', 'Note'],
            ['4624378000000783004', '1', 'test-status', 'test-note']
        ];

        // INVOICE
        // return array(
        //     ['so_number', 'so_priority', 'status', 'remark'],
        //     ['123',       '1',           'status', 'some comments'],
        // );

        // SALES ORDER
        // return [
        //     ['sale order number', 'document type', 'file name'],
        //     ['4624378000000807962', 'invoice', 'invoice_4624378000000807962.txt']
        // ];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => "\t",
            'enclosure' => "",
        ];
    }
}