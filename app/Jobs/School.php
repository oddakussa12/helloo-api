<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class School implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $school;

    public function __construct($school)
    {
        $this->school = $school;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $school = DB::table('schools')->where('name' , $this->school)->first();
        if(blank($school))
        {
            DB::table('schools')->insert(array(
                'name'=>$this->school,
                'created_at'=>Carbon::now()->toDateTimeString()
            ));
        }
    }

}
