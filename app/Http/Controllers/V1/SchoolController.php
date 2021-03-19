<?php

namespace App\Http\Controllers\V1;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SchoolController extends BaseController
{

    public function index(Request $request)
    {
        $userSchools = array();
        $key = strval($request->input('key' , ''));
        if(!blank($key)&&strlen($key)==mb_strlen($key))
        {
            $key = escape_like($key);
            $userSchools = DB::table('schools')->where('name' , 'like' , '%'.$key.'%')->orderByRaw("length(REPLACE(name,'{$key}',''))/length(name) desc")->select('name as school')->distinct()->limit(5)->pluck('school')->toArray();
        }
        return $this->response->array(array('data'=>$userSchools));
    }

}
