<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\V1\BaseController;
use App\Models\PyChatTranslation;
use App\Services\TranslateService;
use App\Repositories\Contracts\PyChatTranslationRepository;
use App\Models\PyChat;

class PyChatTranslationController extends BaseController
{
    /**
     * @var PyChatTranslationRepository
     */
    private $pychattranslation;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct(PyChatTranslationRepository $pychattranslation)
    {
        $this->pychattranslation = $pychattranslation;
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
        if(!PyChat::find($request->chat_id)->hasTranslation($request->chat_locale)){
            $pychattranslation_array = array(
                'chat_id'=> $request->chat_id,
                'chat_locale'=>$request->chat_locale,
                'chat_message'=>$request->chat_message,
            );
            return $this->pychattranslation->store($pychattranslation_array);
        }
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
}
