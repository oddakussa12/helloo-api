<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\V1\BaseController;
use App\Models\PyChat;
use App\Services\TranslateService;
use App\Repositories\Contracts\PyChatRepository;

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
        $pychat_array = array(
            'from_id' => auth()->id(),
            'to_id' => $request->to_id,
            'chat_default_local' => $request->chat_default_local,
            'chat_ip' => $request->chat_ip,
            'zh-CN'=>['chat_massage'=>'你好'],
            'en'=>['chat_massage'=>'hello'],
        );
        // dd($pychat_array);
        return $this->pychat->store($pychat_array);
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
        //
    }

    public function showMassageByUserId(Request $request)
    {
        return $this->pychat->showMassage('28464');
    }
}
