<?php


namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class PropsCollection extends Resource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource;
    }
}