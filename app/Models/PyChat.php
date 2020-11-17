<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PyChat extends Model
{
    use SoftDeletes;

    protected $table = "pychats";

    const CREATED_AT = 'chat_created_at';

    const UPDATED_AT = 'chat_updated_at';

    const DELETED_AT = 'chat_deleted_at';

    protected $primaryKey = 'chat_id';

    public $translatedAttributes = ['chat_locale' , 'chat_message' ];

    protected $fillable = [
        'chat_id' ,
        'chat_uuid' ,
        'from_id' ,
        'chat_type',
        'chat_image',
        'chat_message_type',
        'to_id' ,
        'chat_default_locale' ,
        'chat_ip',
    ];

    public $translationModel = 'App\Models\PyChatTranslation';

    public $paginateParamName = 'pychat_page';

    protected $localeKey = 'chat_locale';

    public $perPage = 15;

    public function getChatDefaultMessageAttribute()
    {
        $chat_default_message = optional($this->translate($this->chat_default_locale))->chat_message;
        return htmlspecialchars_decode(htmlspecialchars_decode($chat_default_message , ENT_QUOTES) , ENT_QUOTES);
    }

    public function user()
    {
        return $this->belongsTo(User::class , 'from_id' , 'user_id');
    }

    public function getChatMessageFormatCreatedAtAttribute()
    {
        $locale = locale();
        if($locale=='zh-CN')
        {
            Carbon::setLocale('zh');
        }elseif ($locale=='zh-TW'||$locale=='zh-HK')
        {
            Carbon::setLocale('zh_TW');
        }elseif ($locale=='en'){
            $translator = \Carbon\Translator::get($locale);
            $translator->setMessages($locale , [
                'minute' => ':count min|:count min',
                'hour' => ':count hr|:count hr',
            ]);
        }
        return Carbon::parse($this->chat_created_at)->diffForHumans();
    }
    public function getChatImageAttribute($value)
        {
            if(empty($value))
            {
                return array();
            }

            $chat_reource['chat_image'] = config('common.qnUploadDomain.thumbnail_domain').$value;
            $chat_reource['chat_thumb_image'] = config('common.qnUploadDomain.thumbnail_domain').$value.'?imageView2/5/w/192/h/192/interlace/1';
            return $value=$chat_reource;
        }
}
