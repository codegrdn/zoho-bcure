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
use zcrmsdk\crm\crud\ZCRMModule;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use Maatwebsite\Excel\Facades\Excel;

class AttachFileToSalesOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mapFilename;
    protected $tmpMapFilename;

    /**
     * Create a new job instance.
     *
     * @param $mapFilename
     */
    public function __construct($mapFilename)
    {
        $this->mapFilename = $mapFilename;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Run {$this->mapFilename} processing");

        // download mapFile
        Storage::disk('sftp')->getDriver()->getAdapter()->disconnect();
        Storage::disk('sftp')->getDriver()->getAdapter()->connect();
        $contents = Storage::disk('sftp')->get($this->mapFilename);
        $this->tmpMapFilename = "priority/{$this->mapFilename}.tmp";
        Storage::disk('local')->delete($this->tmpMapFilename);
        Storage::disk('local')->put($this->tmpMapFilename, $contents);

        ZohoCrmApi::initialize(DB::table('users')->first());

        // read map file data
        $tsv = $this->readTsvDoc();

        /** @var ZCRMModule $moduleSalesOrders */
        $moduleSalesOrders = ZCRMRestClient::getInstance()->getModule('Sales_Orders')->getData();

        foreach ($tsv as $row) {
            /** @var ZCRMRecord $saleOrder */
            $saleOrder = $moduleSalesOrders->getRecord($row['sale_order_number'])->getData();

                // DOWNLOAD TMP DOC
            Log::info("Downloading documents/{$row['file_name']} file from sftp.");
            $tmpDocFilepath = "priority/documents/{$row['file_name']}";
            Storage::disk('sftp')->getDriver()->getAdapter()->disconnect();
            Storage::disk('sftp')->getDriver()->getAdapter()->connect();

            if (!Storage::disk('sftp')->exists("documents/{$row['file_name']}")) {
                continue;
            }

            Storage::disk('local')->put($tmpDocFilepath, Storage::disk('sftp')->get("documents/{$row['file_name']}"));
            $APIResponse = $saleOrder->uploadAttachment(storage_path("app/{$tmpDocFilepath}"));

            if ($APIResponse->getCode() === "SUCCESS") {
                // archive files on smtp
                Storage::disk('local')->delete($tmpDocFilepath);
                Storage::disk('sftp')->getDriver()->getAdapter()->disconnect();
                Storage::disk('sftp')->getDriver()->getAdapter()->connect();
                Storage::disk('sftp')->delete("documents/archive/{$row['file_name']}");
                Storage::disk('sftp')->move("documents/{$row['file_name']}", "documents/archive/{$row['file_name']}");
                Log::info("Processing {$row['file_name']} sucessfully cmpleted  and the file was archived.");
            } else {
                $this->fail(json_encode($APIResponse->getResponseJSON()));
            }
        }

        // remove tmp map file
	    Log::info("File {$this->tmpMapFilename} was deleted.");
        Storage::disk('local')->delete($this->tmpMapFilename);

        // archive map file
        Log::info("Archiving file {$this->mapFilename}.");
        $pathInfo = pathinfo($this->mapFilename);
        Storage::disk('sftp')->delete("{$pathInfo['dirname']}/archive/{$pathInfo['basename']}");
        Storage::disk('sftp')->move($this->mapFilename, "{$pathInfo['dirname']}/archive/{$pathInfo['basename']}");

        Log::info("Processing {$this->mapFilename} successfully completed and the file was archived.");
    }

    protected function readTsvDoc()
    {
        return Excel::toArray(
            new FileImport(),
            $this->tmpMapFilename,
            'local',
            \Maatwebsite\Excel\Excel::TSV
        )[0];
    }
}
