<?php

namespace App\Console\Commands;

use App\Jobs\UpdateCase;
use App\Jobs\UpdateSalesOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CheckCasesIn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:cases_in';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check /cases_in. If there is a file then update Case on Zoho.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('Check /cases_in');

        $files = Storage::disk('sftp')->files('cases_in/');
        // filter files by filename
        foreach ($files as $k => $filepath) {
            preg_match('/c_.+\.txt$/', $filepath, $matches);
            if (empty($matches)) {
                unset($files[$k]);
            }
        }

        foreach ($files as $filepath) {
            UpdateCase::dispatch($filepath);
            $this->info("Job to process {$filepath} was queued");
            Log::info("Job to process {$filepath} was queued");
        }

        return 0;
    }
}
