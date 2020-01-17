<?php

namespace App\Console\Commands;

use App\Models\Like;
use App\Models\User;
use Illuminate\Console\Command;

class CalculatingCountry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculating:country';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculating post country';

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
     * @return mixed
     */
    public function handle()
    {
        try{
            $count = Like::whereNull('likable_country')->orWhere('likable_country' , 0)->count();
            while ($count)
            {
                $likes = Like::whereNull('likable_country')->orWhere('likable_country' , 0)->limit(20)->get();
                if(!empty($likes))
                {
                    $userIds = $likes->pluck('user_id')->unique()->values()->all();
                    $users = User::whereIn('user_id' , $userIds)->select('user_id' , 'user_country_id as user_countryId')->get();
                    foreach ($likes as $like)
                    {
                        $user = $users->where('user_id' , $like->user_id)->first();
                        $userCountryId = $user->user_countryId;
                        $like->likable_country = $userCountryId;
                        $like->save();
                    }
                }
                $count = Like::whereNull('likable_country')->orWhere('likable_country' , 0)->count();

            }
        }catch (\Exception $e)
        {
            \Log::error(\json_encode($e));
        }

    }
}
