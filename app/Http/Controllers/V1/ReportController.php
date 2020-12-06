<?php

namespace App\Http\Controllers\V1;

use App\Models\Report;
use Illuminate\Http\Request;
use App\Jobs\Report as ReportJob;
use App\Repositories\Contracts\UserRepository;

class ReportController extends BaseController
{


    public function index()
    {

    }

    public function store(Request $request)
    {
        $userId   = strval($request->input('user_id' , ''));
        $reportType   = strval($request->input('report_type' , 'default'));
        $auth     = auth()->user();
        if(!blank($userId)&&$userId!=$auth->getKey()&&in_array($reportType , config('report_type'))) {
            $user = app(UserRepository::class)->findOrFail($userId);
            $report = new Report();
            $report->user_id = $auth->getKey();
            $report->reported_id=$user->getKey();
            $report->reported_type=$reportType;
            $result = $report->save();
            if($result)
            {
//                $job = new ReportJob($auth , $userId , $reportType);
//                $this->dispatch($job->onQueue('helloo_{user_report}'));
            }
        }
        return $this->response->noContent();
    }

}
