<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;


class SetController extends BaseController
{

    public function commonSwitch(Request $request)
    {
        $fieldStr = (string)$request->input('include' , '');
        $fields   = explode(',' , $fieldStr);
        $switches = array('matchLimit'=>true);
        return $this->response->array($switches);
    }

}
