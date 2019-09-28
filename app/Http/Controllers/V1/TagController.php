<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Resources\TagCollection;
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return TagCollection::collection($this->tag->all());
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
        //


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

    public function test()
    {
        $this->tag->create(array(
            array(
                'tag_slug'=>'popular_science',
                'tag_sort'=>'1',
                'zh-CN'=>array(
                    'tag_name'=>'科普'
                ),
                'en'=>array(
                    'tag_name'=>'Knowledge'
                ),
                'id'=>array(
                    'tag_name'=>'Pengetahuan'
                ),
                'ko'=>array(
                    'tag_name'=>'지식'
                ),
                'hi'=>array(
                    'tag_name'=>'ज्ञान'
                ),
                'ja'=>array(
                    'tag_name'=>'知識'
                ),
                'ar'=>array(
                    'tag_name'=>'المعرفه'
                ),
                'ru'=>array(
                    'tag_name'=>'Знания'
                ),
                'th'=>array(
                    'tag_name'=>'ความรู้'
                ),
                'vi'=>array(
                    'tag_name'=>'Hiểu biết'
                ),
                'es'=>array(
                    'tag_name'=>'Conocimiento'
                ),
                'fr'=>array(
                    'tag_name'=>'Connaissance'
                ),
                'de'=>array(
                    'tag_name'=>'Wissen'
                ),
                'zh-TW'=>array(
                    'tag_name'=>'科普'
                ),
                'zh-HK'=>array(
                    'tag_name'=>'科普'
                ),
            )
        ));
    }
}
