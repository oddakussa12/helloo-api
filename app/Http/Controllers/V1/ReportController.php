<?php

namespace App\Http\Controllers\V1;

use App\Models\Report;
use Illuminate\Http\Request;
use App\Repositories\Contracts\PostRepository;

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
        $postUuid = $request->input('post_uuid' , '');
        if(!empty($postUuid))
        {
            $user = auth()->user();
            $post = $this->post->findOrFailByUuid($postUuid);
            $relation = $user->reports->where('reportable_id', $post->getKey())
                ->where('reportable_type', $post->getMorphClass())
                ->first();
            if(!$relation)
            {
                $report = new Report();
                $report->user_id = $user->getKey();
                $report->reportable_id=$post->getKey();
                $report->reportable_type=$post->getMorphClass();
                $post->reports()->save($report);
            }
        }
        return $this->response->noContent();
    }
}
