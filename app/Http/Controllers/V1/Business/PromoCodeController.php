<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
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

    public function update(Request $request , $code)
    {
        $promo = PromoCode::where('promo_code' , $code)->first();
        if($promo->limit>0)
        {
            PromoCode::where('promo_code' , $code)->decrement('limit');
        }
        return $this->response->accepted();
    }
}
