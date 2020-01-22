<?php

namespace App\Repositories\Contracts;


interface UserRepository extends BaseRepository
{
    public function isDeletedUser($name);
}
