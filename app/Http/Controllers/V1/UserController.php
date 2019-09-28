<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Resources\UserCollection;

class UserController extends Controller
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

    public function follow($uuid)
    {
//        $user = User::find('2');
//        $post = $this->post->findByUuid($uuid);
//        $user->follow($post);
        return $this->response->noContent();
    }

    public function unfollow($uuid)
    {
//        $user = User::find('2');
//        $post = $this->post->findByUuid($uuid);
//        $user->unfollow($post);
        return $this->response->noContent();
    }

    public function getQiniuUploadToken()
    {
        $policy = [
            'saveKey'=>"$(etag)$(ext)",
            'mimeLimit'=>'image/*',
            'fsizeLimit'=>'5242880'
        ];
        $disk = Storage::disk('idwebother');
        $token = $disk->getUploadToken(null , 3600 , $policy);
        return array('qntoken'=>$token);
    }
}
