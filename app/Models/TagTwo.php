<?php

namespace App\Models;

use App\Traits\tag\HasSlug;
use Spatie\EloquentSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Spatie\EloquentSortable\SortableTrait;
use Illuminate\Database\Eloquent\Collection as DbCollection;

class TagTwo extends Model implements Sortable
{
    use SortableTrait, HasTranslations, HasSlug;

    public $translatable = ['name', 'slug'];

    public $sortable = [
        'order_column_name' => 'tag_sort',
        'sort_when_creating' => true,
    ];

    public $guarded = [];

    public $table = 'tags';

    public function scopeWithType(Builder $query, string $type = null): Builder
    {
        if (is_null($type)) {
            return $query;
        }

        return $query->where('type', $type)->orderBy('order_column');
    }

    /**
     * @param array|\ArrayAccess $values
     * @param string|null $type
     * @param string|null $locale
     *
     * @return \Spatie\Tags\Tag|static
     */
    public static function findOrCreate($values, string $type = null, string $locale = null)
    {
        $tags = collect($values)->map(function ($value) use ($type, $locale) {
            if ($value instanceof TagTwo) {
                return $value;
            }
            return static::findOrCreateFromString($value, $type, $locale);
        });

        return is_string($values) ? $tags->first() : $tags;
    }

    public static function getWithType(string $type): DbCollection
    {
        return static::withType($type)->orderBy('order_column')->get();
    }

    public static function findFromString(string $name, string $type = null, string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return static::query()
//            ->where("name->{$locale}", $name)
            ->where('tag_slug', $name)
            ->first();
    }

    protected static function findOrCreateFromString(string $name, string $type = null, string $locale = null): Tag
    {
        $locale = $locale ?? app()->getLocale();

        $tag = static::findFromString($name, $type, $locale);

        if (! $tag) {
            $tag = static::create([
                'name' => [$locale => $name],
                'type' => $type,
            ]);
        }

        return $tag;
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'name' && ! is_array($value)) {
            return $this->setTranslation($key, app()->getLocale(), $value);
        }

        return parent::setAttribute($key, $value);
    }
}
