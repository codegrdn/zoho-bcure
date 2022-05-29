<?php

namespace App\Exports;

use App\Helpers\ZCRMRecordHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use zcrmsdk\crm\crud\ZCRMRecord;

class ContactExport implements FromArray, WithCustomCsvSettings
{
    /** @var ZCRMRecord|null */
    protected $salesOrder;
    /** @var ZCRMRecord|null */
    protected $contact;

    public function __construct($order, $contact)
    {
        $this->salesOrder = $order;
        $this->contact = $contact;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];

        $data['ContactID'] = $this->contact ? $this->contact->getEntityId() : null;
        $data['First Name'] = ZCRMRecordHelper::getFieldValue($this->contact, 'First_Name');
        $data['Last Name'] = ZCRMRecordHelper::getFieldValue($this->contact, 'Last_Name');
        $data['Email'] = ZCRMRecordHelper::getFieldValue($this->contact, 'Email');
        $data['Mobile'] = ZCRMRecordHelper::getFieldValue($this->contact, 'Mobile');
        $data['language'] = ZCRMRecordHelper::getFieldValue($this->contact, 'language');

        $data['Address'] = ZCRMRecordHelper::getFieldValue($this->salesOrder, 'address');
        $data['City'] = ZCRMRecordHelper::getFieldValue($this->salesOrder, 'city');
        $data['Zipcode'] = ZCRMRecordHelper::getFieldValue($this->salesOrder, 'zip_code');
        $data['Agent'] = "";

        $data['utm_medium'] = ZCRMRecordHelper::getFieldValue($this->contact, 'UTM_Medium');
        $data['utm_source'] = ZCRMRecordHelper::getFieldValue($this->contact, 'UTM_Source');
        $data['utm_term'] = ZCRMRecordHelper::getFieldValue($this->contact, 'UTM_Term');
        $data['utm_campaign'] = ZCRMRecordHelper::getFieldValue($this->contact, 'UTM_Campaign');
        $data['utm_content'] = ZCRMRecordHelper::getFieldValue($this->contact, 'UTM_Content');

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
