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
        $topictitle = \Storage::get('events/'.'topictitle'.'.json');
        $topictitle = \json_decode($topictitle);
        $topiccontent = \Storage::get('events/'.'topiccontent'.date('Ymd',time()).'.json');
        $topiccontent = \json_decode($topiccontent);
        $topic = $topictitle->$lang.$topiccontent->$lang;
        return response()->json($topic);
    }
    
}
