<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\Contracts\TagRepository;

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
        $tags = $request->input('tags');
        $fields = array_filter($tags , function($value){
            return !blank($value);
        });
        $tags = app(TagRepository::class)->getByTag($fields);
        $originIds = $tags->pluck('tag_id')->all();
        $userTags = $tags->pluck('tag')->all();
        $fields = array_diff($fields , $userTags);
        if(!blank($fields))
        {
            DB::beginTransaction();
            try{
                array_walk($fields , function($v , $k) use ($dateTime , $newTagIds){
                    $tagId = DB::table('tags')->insertGetId(array('tag'=>$v , 'created_at'=>$dateTime , 'updated_at'=>$dateTime));
                    array_push($newTagIds , $tagId);
                });
                $tagIds = array_merge($originIds , $newTagIds);
                $originUserTags = DB::table('users_tags')->where('user_id' , $userId)->whereIn('tag_id' , $tagIds)->get();
                $originUserTagIds = $originUserTags->pluck('tag_id')->all();
                $tagIds = array_diff($tagIds , $originUserTagIds);
                $userData = array_map(function($tagId) use ($userId , $dateTime){
                    return array(
                        'user_id'=>$userId,
                        'tag_id'=>$tagId,
                        'created_at'=>$dateTime,
                        'updated_at'=>$dateTime
                    );
                } , $tagIds);
                DB::table('users_tags')->insert($userData);
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::error('tag_failed:'.\json_encode($e->getMessage() , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            }
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
