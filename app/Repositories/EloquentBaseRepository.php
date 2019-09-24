<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Repositories\Contracts\BaseRepository;

/**
 * Class EloquentCoreRepository
 *
 * @package Modules\Core\Repositories\Eloquent
 */
abstract class EloquentBaseRepository implements BaseRepository
{

    const MAX_HASH_NUMBER = 16;

    /**
     * @var Model An instance of the Eloquent Model
     */
    protected $model;

    protected $pageName;

    protected $perPage;

    /**
     * @param Model $model
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->pageName = isset($this->model->paginateParamName)?$this->model->paginateParamName:'page';
        $this->perPage = isset($this->model->perPage)?$this->model->perPage:10;
    }

    /**
     * @inheritdoc
     */
    public function find($id)
    {
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->find($id);
        }

        return $this->model->find($id);
    }

    /**
     * @inheritdoc
     */
    public function all()
    {
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->orderBy($this->model->getCreatedAtColumn(), 'DESC')->get();
        }

        return $this->model->orderBy($this->model->getCreatedAtColumn(), 'DESC')->get();
    }

    /**
     * @inheritdoc
     */
    public function allWithBuilder() : Builder
    {
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations');
        }

        return $this->model->query();
    }

    /**
     * @inheritdoc
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $pageName = isset($this->model->paginateParamName)?$this->model->paginateParamName:$pageName;
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->orderBy($this->model->getCreatedAtColumn(), 'DESC')->paginate($perPage , $columns , $pageName , $page);
        }
        return $this->model->orderBy($this->model->getCreatedAtColumn(), 'DESC')->paginate($perPage , $columns , $pageName , $page);
    }

    /**
     * @inheritdoc
     */
    public function store($data)
    {
        return $this->model->create($data);
    }

    /**
     * @inheritdoc
     */
    public function update($model, $data)
    {
        $model->update($data);

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function destroy($model)
    {
        return $model->delete();
    }

    /**
     * @inheritdoc
     */
    public function allTranslatedIn($lang)
    {
        return $this->model->whereHas('translations', function (Builder $q) use ($lang) {
            $q->where('locale', "$lang");
        })->with('translations')->orderBy($this->model->getCreatedAtColumn(), 'DESC')->get();
    }

    /**
     * @inheritdoc
     */
    public function findBySlug($slug)
    {
        if (method_exists($this->model, 'translations')) {
            return $this->model->whereHas('translations', function (Builder $q) use ($slug) {
                $q->where('slug', $slug);
            })->with('translations')->first();
        }

        return $this->model->where('slug', $slug)->first();
    }

    /**
     * @inheritdoc
     */
    public function findByAttributes(array $attributes)
    {
        $query = $this->buildQueryByAttributes($attributes);

        return $query->first();
    }

    /**
     * @inheritdoc
     */
    public function getByAttributes(array $attributes, $orderBy = null, $sortOrder = 'asc')
    {
        $query = $this->buildQueryByAttributes($attributes, $orderBy, $sortOrder);

        return $query->get();
    }

    /**
     * Build Query to catch resources by an array of attributes and params
     * @param  array $attributes
     * @param  null|string $orderBy
     * @param  string $sortOrder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildQueryByAttributes(array $attributes, $orderBy = null, $sortOrder = 'asc')
    {
        $query = $this->model->query();

        if (method_exists($this->model, 'translations')) {
            $query = $query->with('translations');
        }

        foreach ($attributes as $field => $value) {
            $query = $query->where($field, $value);
        }

        if (null !== $orderBy) {
            $query->orderBy($orderBy, $sortOrder);
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function findByMany(array $ids)
    {
        $query = $this->model->query();

        if (method_exists($this->model, 'translations')) {
            $query = $query->with('translations');
        }

        return $query->whereIn("id", $ids)->get();
    }

    /**
     * @inheritdoc
     */
    public function clearCache()
    {
        return true;
    }

    public function hashDbIndex($string)
    {
        //return abs(hash('crc32b', md5(strtolower($string))) % parent::MAX_HASH_NUMBER);
        $checksum  = crc32(md5(strtolower($string)));
        //64位机,进行移位从处理成和32机一样
        if(8==PHP_INT_SIZE){
            if($checksum >2147483647){
                //对64位机的先进截取后32位
                $checksum  = $checksum&(2147483647);
                //取补码
                $checksum = ~($checksum-1);
                //由于补码操作的修改，但是这时的checksum是正值而不是负值
                $checksum = $checksum&2147483647;
            }
        }
        $hash = (abs($checksum) % self::MAX_HASH_NUMBER);
        return $hash;
    }
}
