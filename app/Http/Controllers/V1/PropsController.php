<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use App\Models\Props;
use Illuminate\Http\Request;
use App\Resources\PropsCollection;
use Illuminate\Support\Facades\DB;
use Dingo\Api\Exception\StoreResourceFailedException;
use Jenssegers\Agent\Agent;

class PropsController extends BaseController
{

    public function index(Request $request)
    {
        $agent = new Agent();
        $version = $agent->getHttpHeader('HellooVersion');
        $props = new Props();
        if(version_compare($version , '1.1.2' , '<'))
        {
            $props = $props->where('default' , 0)->where('is_delete' , 0)->paginate(50 , ['*'] , $props->paginateParamName);
        }else{
            $props = $props->where('is_delete' , 0)->paginate(50 , ['*'] , $props->paginateParamName);
        }
        return PropsCollection::collection($props);
    }

}
