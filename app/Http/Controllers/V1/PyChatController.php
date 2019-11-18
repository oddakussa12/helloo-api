<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\V1\BaseController;
use App\Models\PyChat;
use App\Services\TranslateService;
use App\Repositories\Contracts\PyChatRepository;
use App\Resources\PyChatCollection;
use Illuminate\Support\Facades\DB;

class PyChatController extends BaseController
{
    /**
     * @var PyChatRepository
     */
    private $pychat;
    /**
     * @var TranslateService
     */
    private $translate;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct(PyChatRepository $pychat,TranslateService $translate)
    {
        $this->pychat = $pychat;
        $this->translate = $translate;
    }
    public function index()
    {
        //
        dd('index');
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
        DB::beginTransaction();
        $isInTran = DB::table('pychats')->where('chat_uuid',$request->input('chat_uuid' , ''))->lockForUpdate()->first();
        if(empty($isInTran)){
            $pychat_array = array(
                'from_id' => auth()->id(),
                'to_id' => $request->input('to_id' , ''),
                'chat_type' => $request->input('chat_type' , ''),
                'chat_uuid' => $request->input('chat_uuid' , ''),
                'chat_image' => $request->input('chat_image' , ''),
                'chat_message_type' => $request->input('chat_message_type' , ''),
                'chat_default_locale' => $request->input('chat_default_locale' , 'en'),
                'chat_ip' => getRequestIpAddress(),
            );
            // dd($pychat_array);
            $pychatdata = $this->pychat->store($pychat_array);
        }
        //执行事务
        DB::commit();
        return $this->response->noContent();

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
    public function update(Request $request, $id)
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
        $this->pychat->find($id)->delete();
    }

    public function showMessageByUserId(Request $request)
    {
        return $this->pychat->showMessageByUserId('28464');
    }

    public function showMessageByRoomUuid(Request $request)
    {
        return PyChatCollection::Collection($this->pychat->limitMessage($request->input('chat_id' , ''),$request->input('room_uuid' , '')));
    }
}
