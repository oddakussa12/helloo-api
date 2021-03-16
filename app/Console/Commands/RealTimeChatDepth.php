<?php

namespace App\Console\Commands;


use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class RealTimeChatDepth extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'real:time_chat_depth {type?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Real Time Chat Depth';

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
        $dates = array();
        $startDate = Carbon::yesterday('Asia/Shanghai')->toDateString();
        $endTime = Carbon::now('Asia/Shanghai')->toDateString();
        $type = $this->argument('type');
        if($type=='today')
        {
            $dates = array($endTime);
        }elseif ($type=='yesterday')
        {
            $dates = array($startDate);
        }else{
            do{
                array_push($dates , $startDate);
                $startDate = Carbon::createFromFormat('Y-m-d' , $startDate)->addDays(1)->toDateString();
            }while($startDate <= $endTime);
        }
        $countries = config('country');
        foreach ($countries as $country)
        {
            foreach ($dates as $date)
            {
                $command = "chat:depth";
//                $this->call($command , array('type'=>'country' , 'value'=>$country , 'date'=>$date));
            }
        }
        $schools = config('school');
        foreach ($schools as $school)
        {
            foreach ($dates as $date)
            {
                $command = "chat:depth";
//                $this->call($command , array('type'=>'school' , 'value'=>$school , 'date'=>$date));
            }
        }
        dump($countries);
        dump($schools);
    }

    public function fixChatDepth()
    {
        $schools = array(
            "Sekolah Menengah Atas Negri 10",
            "Addis Ababa University",
            "Colégio São Pedro",
            "ESTV - GTI/STM Becora",
            "Ensino Secundário 5 de Maio",
            "Ensino Secundário 28 de Novembro",
            "Nobel da Paz ",
            "Ensino Secundário 12 de Novembro",
            "Escola Técnica Informática ETI ",
            "Escola An-Nur",
            "Ensino Secundário Nicolao Lobato",
            "São José Operário",
            "Universidade Nacionál de Timor Lorosa'e",
        );
        $dates = array();
        $startDate = Carbon::yesterday('Asia/Shanghai')->toDateString();
        $endTime = Carbon::now('Asia/Shanghai')->toDateString();
        do{
            array_push($dates , $startDate);
            $startDate = Carbon::createFromFormat('Y-m-d' , $startDate)->addDays(1)->toDateString();
        }while($startDate <= $endTime);
        foreach ($schools as $school)
        {
            foreach ($dates as $date)
            {
                $command = "chat:depth";
                $this->call($command , array('type'=>'school' , 'value'=>$school , 'date'=>$date));
            }
        }
    }

}
