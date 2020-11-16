<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Services\TranslateService;

class PrivateMessageController extends BaseController
{

    private $translate;


    public function __construct(TranslateService $translate)
    {
        $this->translate = $translate;
    }

    public function translate(Request $request)
    {

    }



}
