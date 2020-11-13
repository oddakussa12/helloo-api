<?php

namespace App\Repositories\Contracts;


interface UserFriendRepository extends BaseRepository
{
    public function paginateByUser($userId);

    public function getAllByUser($userId , $perPage = 15);
}
