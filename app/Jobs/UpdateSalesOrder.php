<?php

namespace App\Jobs;

use App\Imports\FileImport;
use App\Repositories\ZohoCrmApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\api\response\EntityResponse;
use zcrmsdk\crm\crud\ZCRMModule;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use Maatwebsite\Excel\Facades\Excel;

class UpdateSalesOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pathname;
    protected $archivePathname;
    protected $tmpPathname;


    /**
     * Create a new job instance.
     *
     * @param $pathname
     */
    public function __construct($pathname)
    {
        $this->pathname = $pathname;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Run {$this->pathname} processing...");

        $this->init();
        ZohoCrmApi::initialize(DB::table('users')->first());

        Storage::disk('sftp')->getDriver()->getAdapter()->disconnect();
        Storage::disk('sftp')->getDriver()->getAdapter()->connect();
        $contents = Storage::disk('sftp')->get($this->pathname);
        $contentsUTF8 = iconv('CP1255', 'UTF-8', $contents);
        Storage::disk('local')->delete($this->tmpPathname);
        Storage::disk('local')->put($this->tmpPathname, $contentsUTF8);

        $data = Excel::toArray(
            new FileImport(),
            $this->tmpPathname,
            'local',
            \Maatwebsite\Excel\Excel::TSV
        )[0][0];

        /** @var ZCRMModule $moduleSalesOrders */
        $moduleSalesOrders = ZCRMRestClient::getInstance()->getModule('Sales_Orders')->getData();
        /** @var ZCRMRecord $salesOrder */
        $salesOrder = $moduleSalesOrders->getRecord($data['so_number'])->getData();

        $salesOrder->setFieldValue('priority_number', strval($data['so_priority']));
        $salesOrder->setFieldValue('Status', $data['status']);
        $salesOrder->setFieldValue('comments', $data['remark']);

        /** @var EntityResponse $entityResponse */
        $entityResponse = $moduleSalesOrders->updateRecords([$salesOrder])->getEntityResponses()[0];

        if ($entityResponse->getCode() === "SUCCESS") {
            Storage::disk('sftp')->getDriver()->getAdapter()->disconnect();
            Storage::disk('sftp')->getDriver()->getAdapter()->connect();
	    Storage::disk('sftp')->delete($this->archivePathname);
            Storage::disk('sftp')->move($this->pathname, $this->archivePathname);
            Storage::disk('local')->delete($this->tmpPathname);
            Log::info("Processing {$this->pathname} successfully completed.");
        } else {
	        $this->fail(json_encode($entityResponse->getResponseJSON()));
	    }
    }

    protected function init()
    {
        $this->tmpPathname = "priority/{$this->pathname}.tmp";

        $pathInfo = pathinfo($this->pathname);
        $this->archivePathname = "{$pathInfo['dirname']}/archive/{$pathInfo['basename']}";
    }
}
