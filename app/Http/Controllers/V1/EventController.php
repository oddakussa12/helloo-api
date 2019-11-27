<?php

namespace App\Http\Controllers\V1;
use Illuminate\Http\Request;

class EventController extends BaseController
{
    //
    public function index()
    {
        $events = config('events');
        $event = isset($events[0])?$events[0]:'default';
        $contents = \Storage::get('events/'.$event.'.json');
        $contents = \json_decode($contents);
        return response()->json($contents);
    }

    public function roomTopic(Request $request)
    {
        $lang = locale();
        $topicTitleFile = 'events/topicTitle.json';
        $topicContentFile = 'events/topicContent'.date('Ymd',time()).'.json';
        $topicContentDefault= 'events/topicContentDefault.json';
        $topic = '......';
        if(\Storage::exists($topicContentFile)&&\Storage::exists($topicTitleFile))
        {
            $topicTitle = \Storage::get($topicTitleFile);
            $topicTitle = \json_decode($topicTitle);
            $topicContent = \Storage::get($topicContentFile);
            $topicContent = \json_decode($topicContent);
            $topic = $topicTitle->$lang.$topicContent->$lang;
            return response()->json($topic);
        }else if(\Storage::exists($topicContentDefault)&&\Storage::exists($topicTitleFile)){
            $topicTitle = \Storage::get($topicTitleFile);
            $topicTitle = \json_decode($topicTitle);
            $topicContentDefault = \Storage::get($topicContentDefault);
            $topicContentDefault = \json_decode($topicContentDefault);
            $topic = $topicTitle->$lang.$topicContentDefault->$lang;
            return response()->json($topic);
        }
        return response()->json($topic);
    }
    
}
