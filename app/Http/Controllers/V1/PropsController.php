<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use App\Models\Bgm;
use App\Models\Props;
use Illuminate\Http\Request;
use App\Resources\PropsCollection;
use App\Resources\AnonymousCollection;
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

    public function bgm()
    {
        return AnonymousCollection::collection(Bgm::where('is_delete' , 0)->where('is_delete' , 0)->paginate(50 , ['*']));
    }

    public function recommendation()
    {
        $props = new Props();
        $props = $props->where('default' , 0)->where('is_delete' , 0)->where('recommendation' , 1)->limit(15)->get();
        return PropsCollection::collection($props);
    }

    public function hot()
    {

    }

    public function new()
    {

    }

    public function home($category)
    {
        $props = new Props();
        $props = $props->where('default' , 0)->where('is_delete' , 0)->where('category' , $category)->paginate(50 , ['*'] , $props->paginateParamName);
        return PropsCollection::collection($props);
    }

    public function category()
    {
        return $this->response->array(
            array('data'=>array(
                array(
                    'name'=>'test',
                    'tag'=>'test',
                ),
                array(
                    'name'=>'test 1',
                    'tag'=>'test1',
                ),
                array(
                    'name'=>'test 2',
                    'tag'=>'test2',
                ),
                array(
                    'name'=>'test 3',
                    'tag'=>'test3',
                ),
                array(
                    'name'=>'test 4',
                    'tag'=>'test4',
                ),
            ))
        );
    }

}
