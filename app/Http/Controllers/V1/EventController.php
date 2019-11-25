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
        $contents = \Storage::get('events/'.'default'.'.json');
        $contents = \json_decode($contents);
        return response()->json($contents);
    }
    
}
