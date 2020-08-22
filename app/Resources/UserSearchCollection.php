<?php

/**
 * @Author: Dell
 * @Date:   2019-08-19 16:47:05
 * @Last Modified by:   Gxc
 * @Last Modified time: 2019-10-28 14:15:40
 */
namespace App\Resources;

use App\Traits\CachableUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\PostCommentRepository;

class UserSearchCollection extends Resource
{
    use CachableUser;
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $country_code = config('countries');
        $country      = ($this->resource['user_country_id']-1);
        if($country==-1) {
            $userCountry = 'world';
        }
        if(array_key_exists($country , $country_code)) {
            $userCountry = strtolower($country_code[$country]);
        }

        return [
            'user_id'           => $this->resource['user_id'],
            'user_name'         => $this->resource['user_name'],
            'user_nick_name'    => $this->resource['user_nick_name'],
            'user_avatar'       => config('common.qnUploadDomain.avatar_domain').$this->resource['user_avatar'].'?imageView2/0/w/50/h/50/interlace/1|imageslim',
            'user_country'      => $userCountry,
            'user_continent'    => getContinentByCountry($userCountry),
            'user_gender'       => $this->resource['user_gender'],
            'user_about'        => $this->resource['user_about'],
            'user_level'        => $this->resource['user_level'],
            'user_birthday'     => $this->resource['user_birthday']
        ];
    }
}