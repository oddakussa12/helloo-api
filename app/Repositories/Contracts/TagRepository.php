<?php

namespace App\Repositories\Contracts;


interface TagRepository extends BaseRepository
{
    public function getByTags(array $tags);
}
