<?php

namespace App\Http\Controllers\V1;

use App\Models\Bgm;
use App\Models\Props;
use Illuminate\Pagination\Paginator;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Resources\PropsCollection;
use App\Resources\AnonymousCollection;
use Illuminate\Database\Concerns\BuildsQueries;


class PropsController extends BaseController
{
    use BuildsQueries;

    /**
     * @note 道具
     * @datetime 2021-07-12 18:58
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $agent = new Agent();
        $version = $agent->getHttpHeader('HellooVersion');
        $props = new Props();
        if(version_compare($version , '1.1.2' , '<'))
        {
            $props = $props->where('default' , 0)->where('is_delete' , 0)->orderByDesc('id')->paginate(50 , ['*'] , $props->paginateParamName);
        }else{
            $props = $props->where('is_delete' , 0)->orderByDesc('id')->paginate(50 , ['*'] , $props->paginateParamName);
        }
        return PropsCollection::collection($props);
    }

    /**
     * @note 背景音乐
     * @datetime 2021-07-12 18:58
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function bgm()
    {
        return AnonymousCollection::collection(Bgm::where('is_delete' , 0)->where('status' , 1)->paginate(50 , ['*']));
    }

    /**
     * @note 道具推荐
     * @datetime 2021-07-12 18:58
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function recommendation()
    {
        $props = new Props();
        $frontProps = $props->where('is_delete' , 0)->where('recommendation' , 1)->where('camera' , 'front')->orderByDesc('sort')->orderBydesc('created_at')->limit(15)->get();
        $backProps = $props->where('is_delete' , 0)->where('recommendation' , 1)->where('camera' , 'back')->orderByDesc('sort')->orderBydesc('created_at')->limit(15)->get();
        $props = $frontProps->merge($backProps)->values();
        return PropsCollection::collection($props);
    }

    /**
     * @note 道具热门
     * @datetime 2021-07-12 18:59
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    private function hot()
    {
        $props = new Props();
        $props = $props->where('is_delete' , 0)->where('hot' , 1)->orderBydesc('created_at')->paginate(50 , ['*']);
        return PropsCollection::collection($props);
    }

    /**
     * @note 道具最新
     * @datetime 2021-07-12 18:59
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    private function new()
    {
        $props = new Props();
        $props = $props->where('is_delete' , 0)->orderBydesc('created_at')->limit(14)->get();
        $props = $this->paginator($props, collect($props)->count(), 14 , 1, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
        return PropsCollection::collection($props);
    }

    /**
     * @note 道具首页
     * @datetime 2021-07-12 18:59
     * @param $category
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function home($category)
    {
        if($category=='hot'||$category=='new')
        {
            return $this->$category();
        }
        $props = new Props();
        $props = $props->where('is_delete' , 0)->where('category' , $category)->paginate(50 , ['*'] , $props->paginateParamName);
        return PropsCollection::collection($props);
    }

    /**
     * @note 道具类别
     * @datetime 2021-07-12 18:59
     * @return mixed
     */
    public function category()
    {
        $local = locale();
        $categories = DB::table('props_categories')->where('is_delete' , 0)->orderByDesc('sort')->orderByDesc('created_at')->get();
        $categories = $categories->map(function ($category, $key) use ($local){
            $language = \json_decode($category->language , true);
            if(isset($language[$local]))
            {
                $tag = $language[$local];
            }elseif(isset($language['en'])){
                $tag = $language['en'];
            }else{
                $tag = $category->name;
            }
            return array(
                'tag'=>$category->name,
                'name'=>$tag,
            );
        })->toArray();
        return $this->response->array(array('data'=>$categories));
    }

}
