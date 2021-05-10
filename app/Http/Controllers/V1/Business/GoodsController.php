<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\GoodsRepository;

class GoodsController extends BaseController
{
    public function index()
    {

    }

    public function show($id)
    {

    }

    public function store(Request $request)
    {

    }

    public function update(Request $request , $id)
    {

    }

    public function like(Request $request , $id)
    {
        $user = auth()->user();
        app(GoodsRepository::class)->like($user , $id);
        return $this->response->accepted();
    }
}
