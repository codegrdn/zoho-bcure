<?php

namespace App\Exports;

use App\Helpers\ZCRMRecordHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\crud\ZCRMRecord;

/**
 * Class SalesOrderExport
 * @package App\Exports
 * @todo check that dir exists and writeable
 */
class SalesOrderExport implements FromArray, WithCustomCsvSettings
{
    /** @var ZCRMRecord */
    protected $order;
    /** @var ZCRMRecord */
    protected $products;
    /** @var ZCRMRecord */
    protected $invoice;
    /** @var ZCRMRecord */
    protected $contact;

    public function __construct($order, $product, $invoice, $contact)
    {
        $this->order = $order;
        $this->products = $product;
        $this->invoice = $invoice;
        $this->contact = $contact;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];
        $content = [];

        foreach ($this->products as $product) {
            $data['so_type'] = ZCRMRecordHelper::getFieldValue($this->order, 'Status');
            $data['so_number'] = $this->order ? $this->order->getEntityId() : null;
            $data['so_status'] = ZCRMRecordHelper::getFieldValue($this->order, 'Status');

            $data['payment_type'] = ZCRMRecordHelper::getFieldValue($this->order, 'payment_type');
            $data['contact_id'] = $this->contact ? $this->contact->getEntityId() : null;
            $data['document_id'] = null;
            $data['cardcom_id'] = ZCRMRecordHelper::getFieldValue($this->invoice, 'CardCom_Deal_ID');
            $data['last_digits'] = ZCRMRecordHelper::getFieldValue($this->invoice, 'Last_4_digits', null);
            $data['amount_paid'] = ZCRMRecordHelper::getFieldValue($this->invoice, 'Actual_Payment');

            $data['product_code'] = ZCRMRecordHelper::getFieldValue($product, 'Product_Code');
            /** @var ZCRMInventoryLineItem $lineItem */
            $lineItem = $this->order->getLineItems()[0];
            $data['quantity'] = $lineItem->getQuantity();
            $data['discount'] = ZCRMRecordHelper::getFieldValue($this->invoice, 'Discount', "0");
            $data['unit_price'] = ZCRMRecordHelper::getFieldValue($product, 'Unit_Price', "0");
            $data['total'] = ZCRMRecordHelper::getFieldValue($this->order, 'Grand_Total', "0");

            $instractions = ZCRMRecordHelper::getFieldValue($this->order, 'instructions', "");
	    $data['instractions'] = preg_replace('/"/', '', $instractions);
            $data['shipping notes'] = ZCRMRecordHelper::getFieldValue($this->order, 'shipping_notes', "");
            $data['govina details'] = ZCRMRecordHelper::getFieldValue($this->order, 'govina_details', "");

            $content[] = array_values($data);
        }

        return [
            array_keys($data),
            $content
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
