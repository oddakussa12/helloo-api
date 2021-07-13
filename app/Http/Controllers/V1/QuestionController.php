<?php

namespace App\Http\Controllers\V1;

use App\Models\Question;
use Illuminate\Http\Request;
use App\Resources\AnonymousCollection;


class QuestionController extends BaseController
{
    /**
     * @deprecated
     * @note 问题
     * @datetime 2021-07-12 18:59
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        return AnonymousCollection::collection(Question::where('status' ,1)->paginate(10));
    }

    /**
     * @deprecated
     * @note 问题
     * @datetime 2021-07-12 19:00
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function hot(Request $request)
    {
        $locale = locale();
        $questions = Question::where('status' ,1)->select('id' , 'title', 'url')->paginate(15);
        $questions->each(function($question)use($locale){
            $title = json_decode($question->title, true);
            $url   = json_decode($question->url, true);
            $question->title = !empty($title[$locale]) ? $title[$locale] : $title['en'];
            $question->url   = !empty($url[$locale])   ? $url[$locale]   : $url['en'];
        });
        return AnonymousCollection::collection($questions);
    }




}
