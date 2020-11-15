<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Resources\TagCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\Contracts\TagRepository;
use App\Repositories\Contracts\UserTagRepository;

class TagController extends BaseController
{

    /**
     * @var TagRepository
     */
    private $tag;

    public function __construct(TagRepository $tag)
    {
        $this->tag = $tag;
    }

    public function index()
    {
        return TagCollection::collection($this->tag->all());
    }

//    public function user($userId)
//    {
//        $userTags = app(UserTagRepository::class)->getByUserIds($userId);
//        $tagIds = $userTags->pluck('tag_id')->all();
//        $tags = app(TagRepository::class)->findByMany($tagIds);
//        return TagCollection::collection($tags);
//    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $newTagIds = array();
        $dateTime = Carbon::now()->toDateTimeString();
        $userId = auth()->id();
        $tags = (array)$request->input('tags');

        $fields = array_map(function($v){
            return str_replace("#" , "" , $v);
        }, $tags);
        $fields = array_filter($fields , function($value){
            return !blank($value);
        });
        $paramsTags = array_slice($fields , 0 , 20);
        $tags = app(TagRepository::class)->getByTags($paramsTags);
        $originUserTags = app(UserTagRepository::class)->getByUserId($userId);
        $originUserTagIds = $originUserTags->pluck('tag_id')->all();
        $originIds = $tags->pluck('id')->all();
        $originTags = $tags->pluck('tag')->all();
        $paramsTags = array_diff($paramsTags , $originTags);
        DB::beginTransaction();
        try{
            !blank($paramsTags)&&array_walk($paramsTags , function($v , $k) use ($dateTime , &$newTagIds){
                $tagId = DB::table('tags')->insertGetId(array('tag'=>$v , 'created_at'=>$dateTime , 'updated_at'=>$dateTime));
                array_push($newTagIds , $tagId);
            });
            $tagIds = array_merge($originIds , $newTagIds);
            $crossTagIds = array_intersect($originUserTagIds , $tagIds);
            $storeTagIds = array_diff($tagIds , $crossTagIds);
            $destroyTagIds = array_diff($originUserTagIds , $crossTagIds);
            $userTagData = array_map(function($tagId) use ($userId , $dateTime){
                return array(
                    'user_id'=>$userId,
                    'tag_id'=>$tagId,
                    'created_at'=>$dateTime,
                );
            } , $storeTagIds);
            $userTagLogData = array_map(function($tagId) use ($userId , $dateTime , $originUserTags){
                $originUserTag = $originUserTags->where('tag_id' , $tagId)->first()->toArray();
                return array(
                    'user_id'=>$userId,
                    'tag_id'=>$tagId,
                    'log_created_at'=>$originUserTag['created_at'],
                    'created_at'=>$dateTime
                );
            } , $destroyTagIds);
            DB::table('users_tags')->insert($userTagData);
            DB::table('users_tags')->where('user_id' , $userId)->whereIn('tag_id' , $destroyTagIds)->delete();
            DB::table('users_tags_logs')->insert($userTagLogData);
            DB::commit();
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::error('tag_failed:'.\json_encode($e->getMessage() , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        }

        return $this->response->created();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


}
