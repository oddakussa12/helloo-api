<?php

namespace App\Http\Controllers\V1;

use App\Models\Sticker;
use App\Resources\AnonymousCollection;

class StickerController extends BaseController
{

    public function index()
    {
        $stickers = Sticker::where('status' , 1)->paginate(50);
        return AnonymousCollection::collection($stickers);
    }

}
