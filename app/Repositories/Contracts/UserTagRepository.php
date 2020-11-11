<?php

namespace App\Repositories\Contracts;


interface UserTagRepository extends BaseRepository
{
    public function getByUserId($userId);
}
