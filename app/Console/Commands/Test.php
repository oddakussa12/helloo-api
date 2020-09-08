<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\TencentTranslateService;
use App\Traits\CachableUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;


class Test extends Command
{
    use CachableUser;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto test';

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

        $userTags = \Storage::get('userTags/tags.json');
        $userTags = collect(\json_decode($userTags , true));
        dd($userTags);
//        $tags_id = array_filter($tag_slug ,function($v) use ($userTags){
//            return is_int($v);
//        });
        die;
        $tags = \DB::table("users_tags")->select('tag_id' , "tag_slug")->get();

        $userTags = array();
        $tags->each(function($tag , $index) use (&$userTags){
            $userTags[$tag->tag_id] = $tag;
        });
        $userTags = collect($userTags)->map(function ($value) {return (array)$value;})->toArray();
        \Storage::put('userTags/tags.json' , \json_encode($userTags , JSON_PRETTY_PRINT));
//        $file = storage_path('app/tmp/count.csv');
//        Post::where('post_created_at' , '>' , "2020-07-01 00:00:00")->where('post_type' , 'image')->chunk(1000 , function($posts) use ($file){
//            $str = '';
//            foreach ($posts as $post)
//            {
//                $image = $post->post_media;
//                $str .= strval($post->post_id).','.$image['image']['image_count'].PHP_EOL;
//            }
//            file_put_contents($file , $str , FILE_APPEND);
//        });
    }
}
