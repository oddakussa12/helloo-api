<?php

/**
 * @Author: Dell
 * @Date:   2019-08-09 21:22:21
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-08-09 21:22:42
 */
namespace App\Repositories\Contracts;

use Illuminate\Http\Request;

interface PostRepository extends BaseRepository
{

    public function showByUuid($uuid);

    public function paginateAll(Request $request);

    public function paginateTopic(Request $request);
}
