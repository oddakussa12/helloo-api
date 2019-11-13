<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PyChat extends Model
{
    use Translatable,SoftDeletes;

    protected $table = "pychats";

    const CREATED_AT = 'chat_created_at';

    const UPDATED_AT = 'chat_updated_at';

    const DELETED_AT = 'chat_deleted_at';

    protected $primaryKey = 'chat_id';

    public $translatedAttributes = ['chat_locale' , 'chat_massage' ];

    protected $fillable = [
        'chat_id' ,
        'from_id' ,
        'chat_type',
        'to_id' ,
        'chat_default_locale' ,
        'chat_ip',
    ];

    public $translationModel = 'App\Models\PyChatTranslation';

    public $paginateParamName = 'pychat_page';

    protected $localeKey = 'chat_locale';

    public $perPage = 15;

    public function getChatDefaultMassageAttribute()
    {
        $chat_default_massage = optional($this->translate($this->chat_default_locale))->chat_massage;
        return htmlspecialchars_decode(htmlspecialchars_decode($chat_default_massage , ENT_QUOTES) , ENT_QUOTES);
    }

    public function user()
    {
        return $this->belongsTo(User::class , 'from_id' , 'user_id');
    }

    public function getChatMassageFormatCreatedAtAttribute()
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
}
