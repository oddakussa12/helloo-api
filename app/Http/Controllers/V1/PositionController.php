<?php

namespace App\Http\Controllers\V1;

use App\Models\UserPosition;
use Illuminate\Http\Request;


class PositionController extends BaseController
{

    public function store(Request $request)
    {
        $rule = [
            "longitude" => [
                'required',
                'string',
                'regex:/^1[1-2][0-9]\.\d{6,12}$/u',
                'min:1',
                'max:32',
            ],
            "latitude" => [
                'required',
                'string',
                'regex:/^((3[0-6])|(2[8-9]))\.\d{6,12}$/u',
                'min:1',
                'max:32',
            ],
        ];
        $position = $request->only(array('longitude' , 'latitude'));
        \Validator::make($position, $rule)->validate();
        $position['user_id'] = auth()->id();
        UserPosition::create($position);
    }

}
