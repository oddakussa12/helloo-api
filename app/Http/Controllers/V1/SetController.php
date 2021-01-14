<?php

namespace App\Http\Controllers\V1;

use App\Models\Grade;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SetController extends BaseController
{

    public function commonSwitch(Request $request)
    {
        $fieldStr = (string)$request->input('include' , '');
        $fields   = explode(',' , $fieldStr);
        $switches = array('matchLimit'=>true);
        return $this->response->array($switches);
    }

    public function school()
    {
        $phone = DB::table('users_phones')->where('user_id' , auth()->id())->first();
        switch ($phone->user_phone_country)
        {
            case 1:
                $country = 'gd';//格林纳达
                break;
//            case 7:
//                $country = 'ru';
                break;
            case 62:
                $country = 'id';//印尼
                break;
            case 670:
                $country = 'tl';//东帝汶
                break;
            case 880:
                $country = 'bd';//孟加拉
                break;
            default:
                $country = '';
                break;
        }
        $schools = School::orderBy('name');
        if(!blank($country))
        {
            $schools = $schools->whereIn('country' , array($country , 'other'));
        }else{
            $schools = $schools->where('country' , 'other');
        }
        $schools = $schools->get();
        $grades = Grade::orderBy('sort')->get();
        $grades->each(function ($grade , $index) use ($country){
            if($grade->key=='other')
            {
                if($country=='id')
                {
                    $grade->name = 'Lainnya';
                }
            }else{
                if($country=='gd')
                {
                    $grade->name = str_replace('Grade' , 'Form' , $grade->name);
                }elseif($country=='tl'){
                    $grade->name = str_replace('Grade' , 'Class' , $grade->name);
                }elseif($country=='id'){
                    $grade->name = str_replace('Grade' , 'Kelas' , $grade->name);
                }
            }

        });
        $schools->each(function($school , $index) use ($grades){
            $school->grade = $grades;
        });
        return $this->response->array(array('school'=>$schools));
    }


}
