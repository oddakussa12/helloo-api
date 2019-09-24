<?php

namespace App\Jobs;

use App\Models\PostComment;
use Illuminate\Bus\Queueable;
use App\Services\TranslateService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostCommentTranslation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $translate;

    protected $datas;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
//        $this->translate = $translate;
        $this->datas = $data;
        file_put_contents('1.txt' , \json_encode($data));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->datas;

        $translate = app(TranslateService::class);
        foreach (supportedLocales() as $locale=>$properties)
        {
            $comment[$locale] = array('comment_content'=>$translate->translate('我爱你' , array('target'=>$locale)));
        }
        file_put_contents('./notice1.txt', 'ID 为：'.$data['id'].PHP_EOL.'数据为：'.json_encode($comment).PHP_EOL, FILE_APPEND);
    }
}
