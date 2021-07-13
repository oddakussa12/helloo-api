<?php

namespace App\Repositories\Contracts;


interface UserRepository extends BaseRepository
{

    public function findByUserIds($userIds);

    public function findByUserId($userId);

}
