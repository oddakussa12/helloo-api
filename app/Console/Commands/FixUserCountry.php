<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;

class FixUserCountry extends Command
{
    use CachableUser,Update;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:user_country';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix User Country';

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
        $this->fixIso();
    }

    public function fixIso()
    {
        DB::table('signin_infos')->where('isocode' , 'US_DEFAULT')->orderByDesc('id')->chunk(1000 , function ($users){
            foreach ($users as $user)
            {
                $sign_in_info = array();
                $geo = geoip($user->ip);
                $iso_code = strval($geo->iso_code);
                if($iso_code!='US_DEFAULT')
                {
                    $sign_in_info['isocode'] = $iso_code;
                    $sign_in_info['country'] = strval($geo->country);
                    $sign_in_info['state'] = strval($geo->state_name);
                    $sign_in_info['city'] = strval($geo->city);
                    $sign_in_info['lat'] = strval($geo->lat);
                    $sign_in_info['lon'] = strval($geo->lon);
                    $sign_in_info['timezone'] = strval($geo->timezone);
                    $sign_in_info['continent'] = strval($geo->continent);
                    DB::table('signin_infos')->where('id' , $user->id)->update($sign_in_info);
                }
            }
        });
        DB::table('signup_infos')->where('signup_isocode' , 'US_DEFAULT')->orderByDesc('signup_id')->chunk(1000 , function ($users){
            foreach ($users as $user)
            {
                $signup_info = array();
                $geo = geoip($user->signup_ip);
                $iso_code = strval($geo->iso_code);
                if($iso_code!='US_DEFAULT')
                {
                    $signup_info['signup_isocode'] = strval($geo->iso_code);
                    $signup_info['signup_country'] = strval($geo->country);
                    $signup_info['signup_state'] = strval($geo->state_name);
                    $signup_info['signup_city'] = strval($geo->city);
                    $signup_info['signup_lat'] = strval($geo->lat);
                    $signup_info['signup_lon'] = strval($geo->lon);
                    $signup_info['signup_timezone'] = strval($geo->timezone);
                    $signup_info['signup_continent'] = strval($geo->continent);
                    DB::table('signup_infos')->where('signup_id' , $user->signup_id)->update($signup_info);
                }
            }
        });
    }
}
