<?php

namespace App\Http\Controllers\V1;

use App\Models\Report;
use App\Jobs\Dispatcher;
use App\Custom\RedisList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Contracts\PostRepository;
use Dingo\Api\Exception\InternalHttpException;
use App\Repositories\Contracts\UserRepository;

class ReportController extends BaseController
{
    private $post;

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
        $userId   = strval($request->input('user_id' , ''));
        $auth     = auth()->user();
        $officialKey = config('common.official_user_id');
        if(!blank($postUuid)) {
            try{
                $post      = $this->post->findOrFailByUuid($postUuid);
                if(Redis::exists($officialKey)&&Redis::sismember($officialKey , $post->user_id))
                {
                    return $this->response->noContent();
                }
                $reportNum = $this->reportInfo($auth, $post);

                if($reportNum >= config('common.report_post_num')) {
                    $this->post->destroy($post);

                    $userPostsKey         = config('redis-key.user.posts');
                    $postKey              = config('redis-key.post.post_index_new');
                    $essencePostKey       = config('redis-key.post.post_index_essence');
                    $rateKeyOne           = config('redis-key.post.post_index_rate').'_1';
                    $rateKeyTwo           = config('redis-key.post.post_index_rate').'_2';
                    $essenceManualPostKey = config('redis-key.post.post_index_essence_customize');

                    $redis = new RedisList();
                    $redis->zIncrBy($userPostsKey , -1 , $post->user_id);
                    $redis->zRem($postKey , $post->getKey());
                    $redis->zRem($rateKeyOne , $post->getKey());
                    $redis->zRem($rateKeyTwo , $post->getKey());
                    $redis->zRem($essencePostKey , $post->getKey());
                    $redis->zRem($essenceManualPostKey , $post->getKey());
                }
            }catch (\Exception $e) {
                \Log::error('report post error:'.\json_encode($e->getMessage()));
            }
        } elseif (!blank($userId)) {
            try{
                if(Redis::exists($officialKey)&&Redis::sismember($officialKey , $userId))
                {
                    return $this->response->noContent();
                }
                $user      = app(UserRepository::class)->findOrFail($userId);
                $reportNum = $this->reportInfo($auth, $user);

                if($reportNum >= config('common.report_user_num')) {
                    $params = [
                        'user_id'    => $userId,
                        'operator'   => $auth->user_id,
                        'desc'       => 'automatic ban',
                        'user_name'  => $user->user_name,
                        'time_stamp' => time()
                    ];
                    $params['signature'] = common_signature($params);
                    $this->dispatch(new Dispatcher('/api/ry/set/block' , 'post' , $params));
                }
            }catch (InternalHttpException $e) {
                \Log::error('report user error response:'.$e->getResponse());
            }catch (\Exception $e) {
                \Log::error('report user error:'.\json_encode($e->getMessage()));
            }

        }
        return $this->response->noContent();
    }


    /**
     * @param $auth
     * @param $model
     * @return int
     * 举报信息入库
     */
    protected function reportInfo($auth, $model)
    {
        $relation = Report::where('reportable_id', $model->getKey())
            ->where('reportable_type', $model->getMorphClass())
            ->select(\DB::raw('DISTINCT user_id'))->orderBy('id' , 'DESC')->limit(intval(config('common.report_user_num')+config('common.report_limit_num')))->pluck('user_id')->all();

        $reportNum = count($relation);
        if(!in_array($auth->getKey() , $relation) && $reportNum < config('common.report_post_num')) {
            $report = new Report();
            $report->user_id = $auth->getKey();
            $report->reportable_id=$model->getKey();
            $report->reportable_type=$model->getMorphClass();
            $report->save();
            $reportNum = $reportNum+1;
        }
        return $reportNum;

    }
}
