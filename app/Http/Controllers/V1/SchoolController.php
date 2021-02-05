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
            $userSchools = DB::table('users_schools_logs')->where('school' , 'like' , '%'.$key.'%')->orderByRaw("REPLACE(school,'{$key}','')")->select('school')->distinct()->limit(5)->pluck('school')->toArray();
        }
        return $this->response->array(array('data'=>$userSchools));
    }


}
