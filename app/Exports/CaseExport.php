<?php

namespace App\Exports;

use App\Helpers\ZCRMRecordHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use zcrmsdk\crm\crud\ZCRMRecord;

class CaseExport implements FromArray, WithCustomCsvSettings
{
    /** @var ZCRMRecord */
    protected $case;

    public function __construct(ZCRMRecord $case)
    {
        $this->case = $case;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];

        $data['case id'] = $this->case->getEntityId();
        $data['contact id'] = $this->case->getFieldValue('Related_To')
            ? $this->case->getFieldValue('Related_To')->getEntityId()
            : "";
        $data['Subject'] = ZCRMRecordHelper::getFieldValue($this->case, 'Subject', "");
        $data['Description'] = ZCRMRecordHelper::getFieldValue($this->case, 'details', "");
        $data['So_id'] = $this->case->getFieldValue('sale_order')
            ? $this->case->getFieldValue('sale_order')->getEntityId()
            : "";
        $data['Type'] = ZCRMRecordHelper::getFieldValue($this->case, 'type_case', "");
        $data['Responsibility'] = ZCRMRecordHelper::getFieldValue($this->case, 'responsibility', "");

        return [
            array_keys($data),
            array_values($data),
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => "\t",
            'enclosure' => "",
            'line_ending' => "\r\n",
        ];
    }
}