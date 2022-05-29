<?php

namespace App\Jobs;

use App\Imports\FileImport;
use App\Repositories\ZohoCrmApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use zcrmsdk\crm\api\response\EntityResponse;
use zcrmsdk\crm\crud\ZCRMModule;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;

class UpdateCase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pathname;
    protected $archivePathname;
    protected $localTmpPathname;

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

        Storage::disk('sftp')->getDriver()->getAdapter()->disconnect();
        Storage::disk('sftp')->getDriver()->getAdapter()->connect();
        $contents = Storage::disk('sftp')->get($this->pathname);
        $contentsUTF8 = iconv('CP1255', 'UTF-8', $contents);
        Storage::disk('local')->delete($this->localTmpPathname);
        Storage::disk('local')->put($this->localTmpPathname, $contentsUTF8);

        $data = Excel::toArray(
            new FileImport(),
            $this->localTmpPathname,
            'local',
            \Maatwebsite\Excel\Excel::TSV
        )[0][0];

	ZohoCrmApi::initialize(DB::table('users')->first());
        /** @var ZCRMModule $moduleCases */
        $moduleCases = ZCRMRestClient::getInstance()->getModule('Cases')->getData();
        /** @var ZCRMRecord $case */
        $case = $moduleCases->getRecord($data['zoho_case_id'])->getData();

        $case->setFieldValue('priority_case_id', "{$data['priority_task_id']}");
        $case->setFieldValue('Status', strval($data['status']));
        $case->setFieldValue('priority_notes', $data['note']);

        /** @var EntityResponse $entityResponse */
        $entityResponse = $moduleCases->updateRecords([$case])->getEntityResponses()[0];

        if ($entityResponse->getCode() === "SUCCESS") {
            Storage::disk('sftp')->getDriver()->getAdapter()->disconnect();
            Storage::disk('sftp')->getDriver()->getAdapter()->connect();
            Storage::disk('sftp')->delete($this->archivePathname);
            Storage::disk('sftp')->move($this->pathname, $this->archivePathname);
            Storage::disk('local')->delete($this->localTmpPathname);
            Log::info("Processing {$this->pathname} successfully completed and the file archived.");
        } else {
            $this->fail(json_encode($entityResponse->getResponseJSON()));
        }
    }

    protected function init()
    {
        $this->localTmpPathname = "priority/{$this->pathname}.tmp";
        $pathInfo = pathinfo($this->pathname);
        $this->archivePathname = "{$pathInfo['dirname']}/archive/{$pathInfo['basename']}";
    }
}
