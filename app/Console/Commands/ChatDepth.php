<?php

namespace App\Console\Commands;


use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ChatDepth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:depth {type} {value} {date?} {num?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chat Depth';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $num = intval($this->argument('num'));
        $num = $num<=0?5:$num;
        $type = $this->argument('type');
        if($type=='school')
        {
            $this->runCommandSchool($this->argument('value') ,$this->argument('date') , $num);
        }else{
            $this->runCommandCountry($this->argument('value') ,$this->argument('date') , $num);
        }
        dump('end');
    }

    public function runCommandCountry($country , $date=null , $num=5)
    {
        $country = strtolower($country);
        if($country=='tl')
        {
            $tz = "Asia/Dili";
        }elseif ($country=='gd')
        {
            $tz = "America/Grenada";
        }elseif ($country=='mu')
        {
            $tz = "Indian/Mauritius";
        }elseif ($country=='id')
        {
            $tz = "Asia/Jakarta";
        }elseif ($country=='et')
        {
            $tz = "Africa/Addis_Ababa";
        }else{
            return;
        }
        if(blank($date))
        {
            $start = (Carbon::now($tz)->startOfDay()->timestamp)*1000;
            $end = (Carbon::now($tz)->endOfDay()->timestamp)*1000+999;
            $index = Carbon::now($tz)->format("Ym");
            $time = Carbon::now($tz)->toDateString();
            $pIndex = Carbon::createFromTimestamp(Carbon::now($tz)->startOfDay()->timestamp , "UTC")->format("Ym");
            $aIndex = Carbon::createFromTimestamp(Carbon::now($tz)->endOfDay()->timestamp , "UTC")->format("Ym");
        }else{
            $start = (Carbon::createFromFormat('Y-m-d' , $date , $tz)->startOfDay()->timestamp)*1000;
            $end = (Carbon::createFromFormat('Y-m-d' , $date , $tz)->endOfDay()->timestamp)*1000+999;
            $index = Carbon::createFromFormat('Y-m-d' , $date , $tz)->format("Ym");
            $time = Carbon::createFromFormat('Y-m-d' , $date , $tz)->toDateString();
            $pIndex = Carbon::createFromTimestamp(Carbon::createFromFormat('Y-m-d' , $date , $tz)->startOfDay()->timestamp , "UTC")->format("Ym");
            $aIndex = Carbon::createFromTimestamp(Carbon::createFromFormat('Y-m-d' , $date , $tz)->endOfDay()->timestamp , "UTC")->format("Ym");
        }
        if($pIndex==$aIndex)
        {
            $indexes = array($index);
        }else{
            $indexes = array($pIndex , $aIndex);
        }
        $counted = $completed = $videoCompleted = array();
        $turn = 0;
        $chatData = array();
        foreach ($indexes as $ii)
        {
            $table = 'ry_chats_'.$ii;
            DB::table($table)
                ->whereIn('chat_msg_type' , array('RC:TxtMsg' , 'Helloo:VideoMsg'))
                ->where('chat_time' , '>=' , $start)
                ->where('chat_time' , '<=' , $end)
                ->select('chat_from_id' , 'chat_to_id' , DB::raw("CONCAT(`chat_from_id`, ' ', `chat_to_id`) as `ft`"))
                ->groupBy('ft')->orderByDesc('chat_from_id')
                ->chunk(500 , function($chats) use ($table , $country , $start , $end , &$counted , &$turn , &$completed , $num , &$videoCompleted , &$chatData){
                    $fromIds = $chats->pluck('chat_from_id')->all();
                    $toIds = $chats->pluck('chat_to_id')->all();
                    $userIds = array_unique(array_merge($fromIds , $toIds));
                    $userIds = DB::table('users_countries')->whereIn('user_id' , $userIds)->where('country' , $country)->pluck('user_id')->toArray();
                    foreach ($chats as $chat)
                    {
                        if($chat->chat_from_id>$chat->chat_to_id)
                        {
                            $tag = $chat->chat_to_id.'-'.$chat->chat_from_id;
                        }else{
                            $tag = $chat->chat_from_id.'-'.$chat->chat_to_id;
                        }
                        if(in_array($tag , $counted))
                        {
                            continue;
                        }
                        array_push($counted , $tag);
                        $abCount = $baCount = 0;
                        if(in_array($chat->chat_from_id , $userIds)&&in_array($chat->chat_to_id , $userIds))
                        {
                            $ab = strval($chat->chat_from_id).'-'.strval($chat->chat_to_id);
                            $ba = strval($chat->chat_to_id).'-'.strval($chat->chat_from_id);
                            $preTurn = '';
                            $video = false;
                            DB::table($table)
                                ->whereIn('chat_msg_type' , array('RC:TxtMsg' , 'Helloo:VideoMsg'))
                                ->whereIn('chat_from_id' , array($chat->chat_from_id , $chat->chat_to_id))
                                ->whereIn('chat_to_id' , array($chat->chat_from_id , $chat->chat_to_id))
                                ->where('chat_time' , '>=' , $start)
                                ->where('chat_time' , '<=' , $end)
                                ->orderBy('chat_time')
                                ->chunk(1000 , function ($chatData) use (&$abCount , &$baCount , &$preTurn , $chat , $ab , $ba , &$video){
                                    foreach ($chatData as $c)
                                    {
                                        if($c->chat_msg_type=='Helloo:VideoMsg')
                                        {
                                            $video = true;
                                        }
                                        $flag = strval($c->chat_from_id).'-'.strval($c->chat_to_id);
                                        if(!blank($preTurn)&&$preTurn!=$flag)
                                        {
                                            if($ab==$flag)
                                            {
                                                $baCount++;
                                            }else if($ba==$flag){
                                                $abCount++;
                                            }
                                        }
                                        $preTurn = $flag;
                                    }
                                });
                            if($baCount>=$num||$abCount>=$num)
                            {
                                $turn++;
                                if($video)
                                {
                                    array_push($videoCompleted , $chat->chat_from_id , $chat->chat_to_id);
                                }
                                if(isset($chatData[$chat->chat_from_id]))
                                {
                                    array_push($chatData[$chat->chat_from_id] , $chat->chat_to_id);
                                }else{
                                    $chatData[$chat->chat_from_id] = array($chat->chat_to_id);
                                }
                                if(isset($chatData[$chat->chat_to_id]))
                                {
                                    array_push($chatData[$chat->chat_to_id] , $chat->chat_from_id);
                                }else{
                                    $chatData[$chat->chat_to_id] = array($chat->chat_from_id);
                                }
                                array_push($completed , $chat->chat_from_id , $chat->chat_to_id);
                            }
//                        dump('$chat->chat_from_id and $chat->chat_to_id '. $chat->chat_from_id .'-'. $chat->chat_to_id .' $baCount'.$baCount.' $abCount'.$abCount);
                        }
                    }
                });
        }

        $completed = array_unique($completed);
        $videoCompleted = array_unique($videoCompleted);
        asort($completed);
        asort($videoCompleted);
        $data = array();
        foreach ($completed as $c)
        {
            $amount = array_unique($chatData[$c]);
            dump('country');
            dump('$c:'.$c);
            dump($amount);
            dump('-------------');
            $item = DB::table('chat_layers')->where('user_id' , $c)->where('type' , 'country')->where('country' , $country)->where('time' , $time)->first();
            if(blank($item))
            {
                array_push($data , array(
                    'user_id'=>$c,
                    'video'=>intval(in_array($c , $videoCompleted)),
                    'num'=>$num,
                    'type'=>'country',
                    'country'=>$country,
                    'amount'=>count($amount),
                    'time'=>$time,
                    'created_at'=>Carbon::now()->toDateTimeString()
                ));
            }else{
                DB::table('chat_layers')
                    ->where('user_id' , $c)
                    ->where('type' , 'country')
                    ->where('country' , $country)
                    ->where('time' , $time)
                    ->where('time' , $time)->update(array(
                        'video'=>intval(in_array($c , $videoCompleted)),
                        'amount'=>count($amount),
                        'num'=>$num
                    ));
            }

        }
        Log::info('country_end' , array(
            '$time'=>$time,
            '$data'=>count($data),
            '$completed'=>count($completed),
            '$country'=>$country,
        ));
        dump('$time:'.$time.' $data:'.count($data).' $completed:'.count($completed).' $country:'.$country);
        !blank($data)&&DB::table('chat_layers')->insert($data);
    }

    public function runCommandSchool($school , $date=null , $num=5)
    {
        $tz = 'Asia/Shanghai';
        if(blank($date))
        {
            $start = (Carbon::now($tz)->startOfDay()->timestamp)*1000;
            $end = (Carbon::now($tz)->endOfDay()->timestamp)*1000+999;
            $index = Carbon::now($tz)->format("Ym");
            $time = Carbon::now($tz)->toDateString();
            $pIndex = Carbon::createFromTimestamp(Carbon::now($tz)->startOfDay()->timestamp , "UTC")->format("Ym");
            $aIndex = Carbon::createFromTimestamp(Carbon::now($tz)->endOfDay()->timestamp , "UTC")->format("Ym");
        }else{
            $start = (Carbon::createFromFormat('Y-m-d' , $date , $tz)->startOfDay()->timestamp)*1000;
            $end = (Carbon::createFromFormat('Y-m-d' , $date , $tz)->endOfDay()->timestamp)*1000+999;
            $index = Carbon::createFromFormat('Y-m-d' , $date , $tz)->format("Ym");
            $time = Carbon::createFromFormat('Y-m-d' , $date , $tz)->toDateString();
            $pIndex = Carbon::createFromTimestamp(Carbon::createFromFormat('Y-m-d' , $date , $tz)->startOfDay()->timestamp , "UTC")->format("Ym");
            $aIndex = Carbon::createFromTimestamp(Carbon::createFromFormat('Y-m-d' , $date , $tz)->endOfDay()->timestamp , "UTC")->format("Ym");
        }
        if($pIndex==$aIndex)
        {
            $indexes = array($index);
        }else{
            $indexes = array($pIndex , $aIndex);
        }
        $counted = $completed = $videoCompleted = array();
        $turn = 0;
        $chatData = array();
        foreach ($indexes as $ii)
        {
            $table = 'ry_chats_'.$ii;
            DB::table($table)
                ->whereIn('chat_msg_type' , array('RC:TxtMsg' , 'Helloo:VideoMsg'))
                ->where('chat_time' , '>=' , $start)
                ->where('chat_time' , '<=' , $end)
                ->select('chat_from_id' , 'chat_to_id' , DB::raw("CONCAT(`chat_from_id`, ' ', `chat_to_id`) as `ft`"))
                ->groupBy('ft')->orderByDesc('chat_from_id')
                ->chunk(500 , function($chats) use ($table , $school , $start , $end , &$counted , &$turn , &$completed , $num , &$videoCompleted , &$chatData){
                    $fromIds = $chats->pluck('chat_from_id')->all();
                    $toIds = $chats->pluck('chat_to_id')->all();
                    $userIds = array_unique(array_merge($fromIds , $toIds));
                    $userIds = DB::table('users')->whereIn('user_id' , $userIds)->where('user_sl' , $school)->pluck('user_id')->toArray();
                    foreach ($chats as $chat)
                    {
                        if($chat->chat_from_id>$chat->chat_to_id)
                        {
                            $tag = $chat->chat_to_id.'-'.$chat->chat_from_id;
                        }else{
                            $tag = $chat->chat_from_id.'-'.$chat->chat_to_id;
                        }
                        if(in_array($tag , $counted))
                        {
                            continue;
                        }
                        array_push($counted , $tag);
                        $abCount = $baCount = 0;
                        if(in_array($chat->chat_from_id , $userIds)||in_array($chat->chat_to_id , $userIds))
                        {
                            $ab = strval($chat->chat_from_id).'-'.strval($chat->chat_to_id);
                            $ba = strval($chat->chat_to_id).'-'.strval($chat->chat_from_id);
                            $preTurn = '';
                            $video = false;
                            DB::table($table)
                                ->whereIn('chat_msg_type' , array('RC:TxtMsg' , 'Helloo:VideoMsg'))
                                ->whereIn('chat_from_id' , array($chat->chat_from_id , $chat->chat_to_id))
                                ->whereIn('chat_to_id' , array($chat->chat_from_id , $chat->chat_to_id))
                                ->where('chat_time' , '>=' , $start)
                                ->where('chat_time' , '<=' , $end)
                                ->orderBy('chat_time')
                                ->chunk(1000 , function ($chatData) use (&$abCount , &$baCount , &$preTurn , $chat , $ab , $ba , &$video){
                                    foreach ($chatData as $c)
                                    {
                                        if($c->chat_msg_type=='Helloo:VideoMsg')
                                        {
                                            $video = true;
                                        }
                                        $flag = strval($c->chat_from_id).'-'.strval($c->chat_to_id);
                                        if(!blank($preTurn)&&$preTurn!=$flag)
                                        {
                                            if($ab==$flag)
                                            {
                                                $baCount++;
                                            }else if($ba==$flag){
                                                $abCount++;
                                            }
                                        }
                                        $preTurn = $flag;
                                    }
                                });
                            if($baCount>=$num||$abCount>=$num)
                            {
                                $turn++;
                                if($video)
                                {
                                    array_push($videoCompleted , $chat->chat_from_id , $chat->chat_to_id);
                                }
                                if(in_array($chat->chat_from_id , $userIds))
                                {
                                    array_push($completed , $chat->chat_from_id);
                                }
                                if(in_array($chat->chat_to_id , $userIds))
                                {
                                    array_push($completed , $chat->chat_to_id);
                                }

                                if(isset($chatData[$chat->chat_from_id]))
                                {
                                    array_push($chatData[$chat->chat_from_id] , $chat->chat_to_id);
                                }else{
                                    $chatData[$chat->chat_from_id] = array($chat->chat_to_id);
                                }
                                if(isset($chatData[$chat->chat_to_id]))
                                {
                                    array_push($chatData[$chat->chat_to_id] , $chat->chat_from_id);
                                }else{
                                    $chatData[$chat->chat_to_id] = array($chat->chat_from_id);
                                }

                            }
//                        dump('$chat->chat_from_id and $chat->chat_to_id '. $chat->chat_from_id .'-'. $chat->chat_to_id .' $baCount'.$baCount.' $abCount'.$abCount);
                        }
                    }
                });
        }

        $completed = array_unique($completed);
        $videoCompleted = array_unique($videoCompleted);
        asort($completed);
        asort($videoCompleted);
        $data = array();
        foreach ($completed as $c)
        {
            $amount = array_unique($chatData[$c]);
            dump('school');
            dump('$c:'.$c);
            dump($amount);
            dump('-------------');
            $item = DB::table('chat_layers')->where('user_id' , $c)->where('type' , 'school')->where('school' , $school)->where('time' , $time)->first();
            if(blank($item))
            {
                array_push($data , array(
                    'user_id'=>$c,
                    'video'=>intval(in_array($c , $videoCompleted)),
                    'num'=>$num,
                    'type'=>'school',
                    'school'=>$school,
                    'amount'=>count($amount),
                    'time'=>$time,
                    'created_at'=>Carbon::now()->toDateTimeString()
                ));
            }else{
                DB::table('chat_layers')
                    ->where('user_id' , $c)
                    ->where('type' , 'school')
                    ->where('school' , $school)
                    ->where('time' , $time)->update(array(
                        'video'=>intval(in_array($c , $videoCompleted)),
                        'amount'=>count($amount),
                        'num'=>$num
                ));
            }
        }
        Log::info('school_end' , array(
            '$time'=>$time,
            '$data'=>count($data),
            '$completed'=>count($completed),
            '$school'=>$school,
        ));
        dump('$time:'.$time.' $data:'.count($data).' $completed:'.count($completed).' $school:'.$school);
        !blank($data)&&DB::table('chat_layers')->insert($data);
    }

}
