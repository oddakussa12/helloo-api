<?php


namespace App\Resources;

use App\Models\PostComment;
use Illuminate\Http\Resources\Json\Resource;

class BannerCollection extends Resource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'sort' => $this->resource['sort'],
            'type' => $this->resource['type'],
            'image' => config('common.qnUploadDomain.thumbnail_domain').$this->resource['image'],
            'value' => $this->resource['value'],
        ];
    }
}