<?php

namespace App\Jobs;

use App\Exports\SalesOrderExport;
use App\Repositories\ZohoCrmApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\crud\ZCRMModule;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use Maatwebsite\Excel\Facades\Excel;

class MakeSalesOrderDoc implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $salesOrderId;

    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->salesOrderId = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Run job to create so_{$this->salesOrderId}.txt");

        ZohoCrmApi::initialize(DB::table('users')->first());

        $salesOrder = $this->getSalesOrder();
        $products = $this->getProducts($salesOrder);
        $invoice = $this->getInvoice($salesOrder);
        $contact = $this->getContact($salesOrder);

        Excel::store(
            new SalesOrderExport($salesOrder, $products, $invoice, $contact),
            "priority/so_out/so_{$this->salesOrderId}.txt.tmp",
            "local",
            \Maatwebsite\Excel\Excel::TSV
        );

        $contents = Storage::disk('local')->get("priority/so_out/so_{$this->salesOrderId}.txt.tmp");
        $contentsCP1255 = iconv('UTF-8', 'CP1255', $contents);

        Storage::disk('sftp')->getDriver()->getAdapter()->disconnect();
        Storage::disk('sftp')->getDriver()->getAdapter()->connect();
        Storage::disk('sftp')->delete("so_out/so_{$this->salesOrderId}.txt");
        Storage::disk('sftp')->put("so_out/so_{$this->salesOrderId}.txt", $contentsCP1255);

        Storage::disk('local')->delete("priority/so_out/so_{$this->salesOrderId}.txt.tmp");

        Log::info("File so_{$this->salesOrderId}.txt was created in /so_out.");
    }

    /**
     * @return ZCRMRecord|null
     */
    protected function getSalesOrder(): ?ZCRMRecord
    {
        /** @var ZCRMModule $moduleSalesOrders */
        $moduleSalesOrders = ZCRMRestClient::getInstance()->getModule('Sales_Orders')->getData();
        /** @var ZCRMRecord $salesOrder */
        $salesOrder = $moduleSalesOrders->getRecord($this->salesOrderId)->getData();

        return $salesOrder;
    }

    /**
     * @param  ZCRMRecord  $salesOrder
     * @return ZCRMRecord|null
     */
    protected function getProducts(ZCRMRecord $salesOrder)
    {
        $products = [];

        /** @var ZCRMModule $moduleProducts */
        $moduleProducts = ZCRMRestClient::getInstance()->getModule('Products')->getData();
        /** @var ZCRMInventoryLineItem $productLineItem */
        $productLineItems = $salesOrder->getLineItems();

        foreach ($productLineItems as $productLineItem) {
            $productId = $productLineItem->getProduct()->getEntityId();
            $products[] = $moduleProducts->getRecord($productId)->getData();
        }

        return $products;
    }

    /**
     * @param  ZCRMRecord  $salesOrder
     * @return ZCRMRecord|null
     */
    protected function getInvoice(ZCRMRecord $salesOrder): ?ZCRMRecord
    {
        /** @var ZCRMModule $moduleInvoices */
        $moduleInvoices = ZCRMRestClient::getInstance()->getModule('Invoices')->getData();
        try {
            /** @var ZCRMRecord $invoice */
            $invoice = $salesOrder->getRelatedListRecords('Invoices')->getData()[0];
        } catch (ZCRMException $exception) {
            $invoice = null;
        }

        return $invoice;
    }

    /**
     * @param  ZCRMRecord  $salesOrder
     * @return ZCRMRecord|null
     */
    protected function getContact(ZCRMRecord $salesOrder): ?ZCRMRecord
    {
        if ($salesOrder->getFieldValue('Contact_Name')) {
            $contactId = $salesOrder->getFieldValue('Contact_Name')->getEntityId();
            /** @var ZCRMModule $moduleContacts */
            $moduleContacts = ZCRMRestClient::getInstance()->getModule('Contacts')->getData();
            /** @var ZCRMRecord $invoice */
            $contact = $moduleContacts->getRecord($contactId)->getData();
        } else {
            $contact = null;
        }

        return $contact;
    }
}
