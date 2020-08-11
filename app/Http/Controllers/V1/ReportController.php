<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Dispatcher;
use App\Models\Report;
use App\Custom\RedisList;
use Illuminate\Http\Request;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\UserRepository;

class ReportController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @param PostRepository $post
     */

    public function __construct(PostRepository $post)
    {
        $this->post = $post;
    }

    public function index()
    {

    }

    public function store(Request $request)
    {
        $postUuid = strval($request->input('post_uuid' , ''));
        $userId = strval($request->input('user_id' , ''));
        if(!blank($postUuid))
        {
            $auth = auth()->user();
            try{
                $post = $this->post->findOrFailByUuid($postUuid);
                $relation = Report::where('reportable_id', $post->getKey())
                    ->where('reportable_type', $post->getMorphClass())
                    ->select(\DB::raw('DISTINCT user_id'))->orderBy('id' , 'DESC')->limit(intval(config('common.report_post_num')+config('common.report_limit_num')))->pluck('user_id')->all();
                $reportNum = count($relation);
                if(!in_array($auth->getKey() , $relation)&&$reportNum<config('common.report_post_num'))
                {
                    $report = new Report();
                    $report->user_id = $auth->getKey();
                    $report->reportable_id=$post->getKey();
                    $report->reportable_type=$post->getMorphClass();
                    $report->save();
                    $reportNum = $reportNum+1;
                }
                if($reportNum>=config('common.report_post_num'))
                {
                    $redis = new RedisList();
                    $this->post->destroy($post);
                    $userPostsKey = config('redis-key.user.posts');
                    $redis->zIncrBy($userPostsKey , -1 , $post->user_id);
                    $postKey = config('redis-key.post.post_index_new');
                    $essencePostKey = config('redis-key.post.post_index_essence');
                    $essenceManualPostKey = config('redis-key.post.post_index_essence_customize');
                    $rateKeyOne = config('redis-key.post.post_index_rate').'_1';
                    $rateKeyTwo = config('redis-key.post.post_index_rate').'_2';
                    $redis->zRem($postKey , $post->getKey());
                    $redis->zRem($rateKeyOne , $post->getKey());
                    $redis->zRem($rateKeyTwo , $post->getKey());
                    $redis->zRem($essencePostKey , $post->getKey());
                    $redis->zRem($essenceManualPostKey , $post->getKey());
                }
            }catch (\Exception $e)
            {

            }
        }elseif (!blank($userId))
        {
            $auth = auth()->user();
            try{
                $user = app(UserRepository::class)->findOrFail($userId);
                $relation = Report::where('reportable_id', $user->getKey())
                    ->where('reportable_type', $user->getMorphClass())
                    ->select(\DB::raw('DISTINCT user_id'))->orderBy('id' , 'DESC')->limit(intval(config('common.report_user_num')+config('common.report_limit_num')))->pluck('user_id')->all();
                $reportNum = count($relation);
                if(!in_array($auth->getKey() , $relation)&&$reportNum<config('common.report_post_num'))
                {
                    $report = new Report();
                    $report->user_id = $auth->getKey();
                    $report->reportable_id=$user->getKey();
                    $report->reportable_type=$user->getMorphClass();
                    $report->save();
                    $reportNum = $reportNum+1;
                }
                if($reportNum>=config('common.report_user_num'))
                {
                    $params = array(
                        'user_id'=>$userId,
                        'user_name'=>$user->user_name,
                        'time_stamp'=>time()
                    );
                    $params['signature'] = common_signature($params);
                    $this->dispatch(new Dispatcher('/api/ry/set/block' , 'post' , $params));
                }
            }catch (\Dingo\Api\Exception\InternalHttpException $e){
                \Log::error('report error response:'.$e->getResponse());
            }catch (\Exception $e)
            {

            }

        }
        return $this->response->noContent();
    }
}
