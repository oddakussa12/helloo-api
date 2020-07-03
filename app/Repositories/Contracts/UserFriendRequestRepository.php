<?php

namespace App\Repositories\Contracts;


interface UserFriendRequestRepository extends BaseRepository
{
    public function paginateByUser($toId , $perPage = 15);

    public function batchCreate($data);
}
