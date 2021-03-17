<?php

namespace App\Http\Controllers\V1;

use App\Models\Question;
use Illuminate\Http\Request;
use App\Resources\AnonymousCollection;


class QuestionController extends BaseController
{

    public function index(Request $request)
    {
        return AnonymousCollection::collection(Question::where('status' ,1)->paginate(10));
    }




}
