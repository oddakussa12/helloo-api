<?php


namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class OrderCollection extends Resource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = collect($this->resource);
        if($resource->has('free_delivery'))
        {
            $resource->put('free_delivery' , boolval($resource->get('free_delivery')));
        }
        if($resource->has('detail'))
        {
            $detail = $resource->get('detail');
            $resource->put('detail' , collect($detail)->except('purchase_price' , 'package_purchase_price' , 'extension_id' , 'charge')->toArray());
        }
        return $resource->toArray();
    }
}