<?php

use App\Jobs\UpdateSalesOrder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sales-order:update', function () {
    $job = new UpdateSalesOrder();
    UpdateSalesOrder::dispatch($job);
    $this->info("A job to update Sales Order was queued.");
});

Artisan::command('mock:tsv', function () {
    Excel::store(
        new \App\Exports\MockDocExport(),
        "priority/cases_in/c_4624378000000783004.txt",
        "local",
        \Maatwebsite\Excel\Excel::TSV
    );
    $this->info("File priority/cases_in/so_4624378000000807962.txt was created");
});