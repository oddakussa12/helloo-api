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
            $key = addcslashes($key);
            $userSchools = DB::table('users_schools_logs')->where('school' , 'like' , '%'.$key.'%')->orderByRaw("length(REPLACE(school,'{$key}',''))/length(school) desc")->select('school')->distinct()->limit(5)->pluck('school')->toArray();
        }
        return $this->response->array(array('data'=>$userSchools));
    }


}
