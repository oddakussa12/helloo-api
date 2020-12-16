<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use App\Models\Props;
use Illuminate\Http\Request;
use App\Resources\PropsCollection;
use Illuminate\Support\Facades\DB;
use Dingo\Api\Exception\StoreResourceFailedException;

class PropsController extends BaseController
{

    public function index(Request $request)
    {
        $props = new Props();
        $props = $props->paginate(50 , ['*'] , $props->paginateParamName);
        return PropsCollection::collection($props);
    }

}
