<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
use App\Models\Business\PromoCode;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;

class PromoCodeController extends BaseController
{
    /**
     * @note 促销码详情
     * @datetime 2021-07-12 17:57
     * @param $code
     * @return AnonymousCollection
     */
    public function show($code)
    {
        $code = PromoCode::where('promo_code' , $code)->first();
        return new AnonymousCollection($code);
    }

    /**
     * @note 促销码更新
     * @datetime 2021-07-12 17:57
     * @param Request $request
     * @param $code
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request , $code)
    {
        $promo = PromoCode::where('promo_code' , $code)->first();
        if(!empty($promo)&&$promo->limit>0)
        {
            PromoCode::where('promo_code' , $code)->decrement('limit');
        }
        return $this->response->accepted();
    }
}
