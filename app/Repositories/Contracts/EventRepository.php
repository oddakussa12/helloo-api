<?php

/**
 * @Author: Dell
 * @Date:   2019-08-09 21:22:21
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-08-09 21:22:42
 */
namespace App\Repositories\Contracts;


interface EventRepository extends BaseRepository
{
    public function getActiveEvent();
    
    public function updateActiveEvent();
}
