<?php

namespace App\Http\Controllers\V1;

use App\Models\Sticker;
use App\Resources\AnonymousCollection;

class StickerController extends BaseController
{
    /**
     * @note 贴纸
     * @datetime 2021-07-12 19:07
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $stickers = Sticker::where('status' , 1)->paginate(50);
        return AnonymousCollection::collection($stickers);
    }

}
