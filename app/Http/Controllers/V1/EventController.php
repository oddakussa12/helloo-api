<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Resources\AnonymousCollection;
use App\Repositories\Contracts\EventRepository;
use Illuminate\Support\Facades\Validator;

class EventController extends BaseController
{
    //
    public function index()
    {

    }

    public function event($event)
    {
        $flag = false;
        Log::info($event);
        $gameEvent = app(EventRepository::class)->findByAttributes(array('game'=>$event));
        if(!blank($gameEvent))
        {
            $now = Carbon::now()->timestamp;
            Log::info($now);
            if($now<=$gameEvent->ended_at)
            {
                $flag = true;
            }
        }
        return $this->response->array(array("data"=>array(
            "$event"=>$flag
        )));
    }

    public function current()
    {
        $now = Carbon::now()->timestamp;
        $events = Event::where('status' , 1)->where('started_at' , '<=' , $now)->where('ended_at' , '<=' , $now)->get();
        $cache = app(EventRepository::class)->getActiveEvent();
        return $this->response->array(
            array('current'=>AnonymousCollection::collection($events) , 'cache'=>AnonymousCollection::collection(collect($cache)))
        );
    }

    public function store(Request $request)
    {
        $now = Carbon::now()->timestamp;
        $name = $request->input('name' , '');
        $game = $request->input('game' , 'coronation');
        $value = $request->input('value' , '');
        $type = $request->input('type' , '');
        $image = $request->input('image' , '');
        $status = $request->input('status' , 1);
        $started_at = $request->input('started_at' , '');
        $ended_at = $request->input('ended_at' , '');
        $rules = array(
            'game' => [
                'bail',
                'required',
                'string',
                Rule::in(array('coronation' , 'superZero' , 'trumpAdventures')),
                Rule::exists('game_events')->where(function ($query) use ($game) {
                    $query->where('game', $game);
                }),
            ],
            'name' => [
                'bail',
                'required',
                'string',
                'between:2,32',
            ],
            'value' => [
                'bail',
                'required',
                'string',
                'between:2,512',
            ],
            'type' => [
                'bail',
                'required',
                'string',
                'between:2,64',
            ],
            'status' => [
                'bail',
                'required',
                'string',
                Rule::in(array(0 , 1 , '0' , '1')),
            ],
            'image' => [
                'bail',
                'required',
                'string',
                'between:2,512',
            ],
            'started_at' => [
                'bail',
                'required',
                'numeric',
                'min:'.$now,
            ],
            'ended_at' => [
                'bail',
                'required',
                'numeric',
                'min:'.$now,
            ]
        );
        $fields = array(
            'game'=>$game,
            'name'=>$name,
            'value'=>$value,
            'type'=>$type,
            'image'=>$image,
            'started_at'=>$started_at,
            'ended_at'=>$ended_at,
        );
        Validator::make($fields, $rules)->validate();
        $event = app(EventRepository::class)->findByAttributes(array('name'=>$name));
        if(!blank($event))
        {
            abort(403);
        }
        app(EventRepository::class)->store(array(
            'game'=>$game,
            'name'=>$name,
            'value'=>$value,
            'type'=>$type,
            'image'=>$image,
            'status'=>$status,
            'started_at'=>$started_at,
            'ended_at'=>$ended_at,
        ));
        return $this->response->created();
    }

    public function update(Request $request , $game)
    {
        $event = app(EventRepository::class)->findByAttributes(array('name'=>$game));
        if(!blank($event))
        {
            $now = Carbon::now()->timestamp;
            $name = $request->input('name' , '');
            $value = $request->input('value' , '');
            $type = $request->input('type' , '');
            $image = $request->input('image' , '');
            $status = $request->input('status' , 1);
            $started_at = $request->input('started_at' , '');
            $ended_at = $request->input('ended_at' , '');
            $rules = array(
                'name' => [
                    'bail',
                    'required',
                    'string',
                    'between:2,32',
                ],
                'value' => [
                    'bail',
                    'required',
                    'string',
                    'between:2,512',
                ],
                'type' => [
                    'bail',
                    'required',
                    'string',
                    'between:2,64',
                ],
                'status' => [
                    'bail',
                    'required',
                    'string',
                    Rule::in(array(0 , 1 , '0' , '1')),
                ],
                'image' => [
                    'bail',
                    'required',
                    'string',
                    'between:2,512',
                ],
                'started_at' => [
                    'bail',
                    'required',
                    'numeric',
                    'min:'.$now,
                ],
                'ended_at' => [
                    'bail',
                    'required',
                    'numeric',
                    'min:'.$now,
                ]
            );
            $fields = array(
                'name'=>$name,
                'value'=>$value,
                'type'=>$type,
                'image'=>$image,
                'status'=>$status,
                'started_at'=>$started_at,
                'ended_at'=>$ended_at,
            );
            Validator::make($fields, $rules)->validate();
            DB::beginTransaction();
            try {
                $count = DB::table('game_events')->where('id' , $event->id)->update($fields);
                if($count<=0)
                {
                    throw new \Exception('game event update fail');
                }
                $fields['createdAt'] = Carbon::now()->toDateTimeString();
                $fields['game'] = $game;
                $result = DB::table('game_events_logs')->insert($fields);
                if(!$result)
                {
                    throw new \Exception('game event log store fail');
                }
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('event_update_fail' , array(
                    'code'=>$e->getCode(),
                    'message'=>$e->getMessage(),
                ));
            }
        }
        return $this->response->accepted();
    }
}
