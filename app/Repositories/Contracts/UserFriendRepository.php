<?php

namespace App\Repositories\Contracts;


interface UserFriendRepository extends BaseRepository
{
    public function paginateByUser($userId , $perPage = 15);

    public function getAllByUser($userId , $perPage = 15);
}
