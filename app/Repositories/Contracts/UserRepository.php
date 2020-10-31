<?php

namespace App\Repositories\Contracts;


interface UserRepository extends BaseRepository
{
    public function isDeletedUser($name);

    public function isBlackUser($user_id);

    public function virtualViewCount($user, $switch=0);
}
