<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\Business\PromoCode;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;

class PromoCodeController extends BaseController
{
    public function show($code)
    {
        $code = PromoCode::where('promo_code' , $code)->first();
        return new AnonymousCollection($code);
    }
}
