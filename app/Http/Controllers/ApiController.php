<?php

namespace App\Http\Controllers;

use App\Exports\OrderImport;
use App\Jobs\MakeCaseDoc;
use App\Jobs\MakeContactDoc;
use App\Jobs\MakeSalesOrderDoc;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class ApiController extends BaseController
{
    /**
     * @param  Request  $request
     * @return Application|ResponseFactory|Response
     * @todo (?) if a new order already created (error or update)
     */
    public function createSalesOrderDoc(Request $request)
    {
        MakeSalesOrderDoc::dispatch($request->id);
        MakeContactDoc::dispatch($request->id);

        return response(json_encode([
            "code" => "SUCCESS",
            "message" => "Jobs to create contact_{$request->id}.txt and so_{$request->id}.txt were queued."
        ]));
    }

    /**
     * @param  Request  $request
     * @return Application|ResponseFactory|Response
     * @todo (?) if a job for the case already created created (error or update)
     */
    public function createCaseDoc(Request $request)
    {
        MakeCaseDoc::dispatch($request->id);

        return response(json_encode([
            "code" => "SUCCESS",
            "message" => "Job to create c_{$request->id}.txt was queued."
        ]));
    }
}
