<?php

namespace App\Jobs;

use App\Exports\CaseExport;
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

class MakeCaseDoc implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $caseId;

    /**
     * Create a new job instance.
     * @param $id
     */
    public function __construct($id)
    {
        $this->caseId = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Run job to create c_{$this->caseId}.txt");

        ZohoCrmApi::initialize(DB::table('users')->first());

        /** @var ZCRMModule $moduleCases */
        $moduleCases = ZCRMRestClient::getInstance()->getModule('Cases')->getData();
        /** @var ZCRMRecord $case */
        $case = $moduleCases->getRecord($this->caseId)->getData();

        Excel::store(
            new CaseExport($case),
            "priority/cases_out/c_{$this->caseId}.txt.tmp",
            "local",
            \Maatwebsite\Excel\Excel::TSV
        );

        // put file on sftp server
        $contents = Storage::disk('local')->get("priority/cases_out/c_{$this->caseId}.txt.tmp");
        $contentsCP1255 = iconv('UTF-8', 'CP1255', $contents);

        Storage::disk('sftp')->getDriver()->getAdapter()->disconnect();
        Storage::disk('sftp')->getDriver()->getAdapter()->connect();
        Storage::disk('sftp')->delete("cases_out/c_{$this->caseId}.txt");
        Storage::disk('sftp')->put("cases_out/c_{$this->caseId}.txt", $contentsCP1255);

        // remove local tmp file
        Storage::disk('local')->delete("priority/cases_out/c_{$this->caseId}.txt.tmp");

        Log::info("File c_{$this->caseId}.txt was created in /cases_out.");
    }
}
