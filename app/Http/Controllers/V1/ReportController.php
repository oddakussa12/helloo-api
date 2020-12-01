<?php

namespace App\Http\Controllers\V1;

use App\Models\Report;
use Illuminate\Http\Request;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Support\Facades\Redis;

class ReportController extends BaseController
{


    public function index()
    {

    }

    public function store(Request $request)
    {
        $userId   = strval($request->input('user_id' , ''));
        $auth     = auth()->user();
        if(!blank($userId)) {
            $user = app(UserRepository::class)->findOrFail($userId);
            $report = new Report();
            $report->user_id = $auth->getKey();
            $report->reported_id=$user->getKey();
            $result = $report->save();
            if($result)
            {
                $key = 'helloo:account:service:account-reported-sort-set';
                Redis::zincrby($key , 1 , $userId);
            }
        }
        return $this->response->noContent();
    }

}
