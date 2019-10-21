<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\V1\BaseController;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Resources\UserCollection;
use App\Events\Follow;
use App\Events\UnFollow;
use App\Resources\FollowCollection;
class UserController extends BaseController
{

    /**
     * @var UserRepository
     */
    private $user;

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return UserCollection
     */
    public function show($id)
    {
         return new UserCollection($this->user->findOrFail($id));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,User $user)
    {
        $result = $this->user->update($user,$request->all());
        return $result;
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

    public function showPrivateUser($id)
    {
        return new UserCollection($this->user->find($id));
    }

    public function follow($user_id)
    {
        $user = $this->user->findByUuid($user_id);
        auth()->user()->follow($user);
        event(new Follow($user));
        return $this->response->noContent();
    }

    public function unfollow($user_id)
    {
        $user = $this->user->findByUuid($user_id);
        auth()->user()->unfollow($user);
        event(new UnFollow($user));
        return $this->response->noContent();
    }

    public function myFollow()
    {
        return FollowCollection::collection(auth()->user()->followings()->get());
    }

    public function followMe()
    {
        return FollowCollection::collection(auth()->user()->followers()->get());
    }

    public function getQiniuUploadToken(Request $request)
    {
        $policy = [
            'saveKey'=>"$(etag)$(ext)",
            'mimeLimit'=>'image/*',
            'fsizeLimit'=>5242880
        ];
        $driver = $request->input('driver' , 'qn_avatar');
        if(!in_array($driver , array('qn_avatar' , 'qn_image')))
        {
            $driver = 'qn_avatar';
        }
        $disk = Storage::disk($driver);
        $token = $disk->getUploadToken(null , 3600 , $policy);
        return array('qntoken'=>$token);
    }
}
