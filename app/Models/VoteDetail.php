<?php

namespace App\Models;

use App\Traits\CachablePost;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;


class VoteDetail extends Model
{
    use CachablePost;

    protected $table = "vote_details";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $translatedAttributes = ['locale' , 'content'];

    protected $fillable = [
      'post_id','user_id','tab_name','vote_media','default_locale','country','vote_type','vote_num', 'content'
    ];

   // public $translationModel = 'App\Models\VoteDetailTranslation';

    public function voteDetailTranslate()
    {
        return $this->hasOne(VoteDetailTranslation::class, 'vote_detail_id' , 'id')->where('locale', locale());
    }

    public function getVoteFormatMediaAttribute()
    {
        $data = !empty($this->vote_media) ? json_decode($this->vote_media, true) : [];
        if (!empty($data['image'])) {
            $imgDomain = config('common.qnUploadDomain.thumbnail_domain');
            $suffix    = '?imageMogr2/auto-orient/interlace/1|imageslim';
            $data['image']['image_url'] = $imgDomain . $data['image']['image_url'] . $suffix;
        }
        return $data;
    }

    public function getContentAttribute()
    {
        $languages = array_unique([locale(), 'en', $this->default_locale]);
        return $this->getTranslation($this->translations, $languages, 'locale', 'content');
    }

    public function getDefaultContentAttribute()
    {
        $languages = array_unique([$this->default_locale, 'en', locale()]);
        return $this->getTranslation($this->translations, $languages, 'locale', 'content');
    }




    /**
     * @param $translation
     * @param array $languages 语言排序
     * @param string $field_locale 字段语言 如 post_default_locale default_locale等
     * @param string $field_content 字段内容 如 post_content content 等
     * @return Model|null
     */
    public function getTranslation($translation, array $languages, $field_locale='locale', $field_content='content')
    {
        $content = null;
        foreach ($languages as $Key=> $language) {
            foreach ($translation as $item) {
                if ($item->$field_locale==$language) {
                    $content = $item->$field_content;
                    break;
                }
            }
            if ($content) break;
        }
        return $content ?? null;
    }
}
