<?php

namespace App\Jobs;

use App\Exports\ContactExport;
use App\Repositories\ZohoCrmApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\crud\ZCRMModule;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use Maatwebsite\Excel\Facades\Excel;

class MakeContactDoc implements ShouldQueue
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
        Log::info("Run job to create contact_{$this->salesOrderId}.txt");

        ZohoCrmApi::initialize(DB::table('users')->first());

        $salesOrder = $this->getSalesOrder();
        $contact = $this->getContact($salesOrder);


	if (!$contact) {
            return;
	}

        Excel::store(
            new ContactExport($salesOrder, $contact),
            "priority/contacts/contact_{$contact->getEntityId()}.txt.tmp",
            "local",
            \Maatwebsite\Excel\Excel::TSV
        );

        // put file on sftp server
        $contents = Storage::disk('local')->get("priority/contacts/contact_{$contact->getEntityId()}.txt.tmp");
        $contentsCP1255 = iconv('UTF-8', 'CP1255', $contents);

        Storage::disk('sftp')->getDriver()->getAdapter()->disconnect();
        Storage::disk('sftp')->getDriver()->getAdapter()->connect();
        Storage::disk('sftp')->delete("contacts/contact_{$this->salesOrderId}.txt");
        Storage::disk('sftp')->put("contacts/contact_{$this->salesOrderId}.txt", $contentsCP1255);

        // remove local tmp file
        Storage::disk('local')->delete("priority/contacts/contact_{$contact->getEntityId()}.txt.tmp");

        Log::info("File contact_{$this->salesOrderId}.txt was created in /contacts.");
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
