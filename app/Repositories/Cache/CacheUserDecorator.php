<?php

namespace App\Repositories\Cache;

use App\Repositories\BaseCacheDecorator;
use App\Repositories\Contracts\UserRepository;

class CacheUserDecorator extends BaseCacheDecorator implements UserRepository
{
    public function __construct(UserRepository $user)
    {
        parent::__construct();

        $this->entityName = 'user';
        $this->repository = $user;
    }

    /**
     * @inheritdoc
     */
    public function all()
    {
        return $this->remember(function (){
            return $this->repository->all();
        });
    }

    public function likePost($userId)
    {
        return $this->remember(function () use ($userId) {
            return $this->repository->likePost($userId);
        });
    }


}
