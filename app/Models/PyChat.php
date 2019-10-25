<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PyChat extends Model
{
    use Translatable;

    protected $table = "pychats";

    const CREATED_AT = 'chat_created_at';

    const UPDATED_AT = 'chat_updated_at';

    const DELETED_AT = 'chat_deleted_at';

    protected $primaryKey = 'chat_id';

    public $translatedAttributes = ['chat_locale' , 'chat_massage' ];

    protected $fillable = [
        'chat_id' ,
        'from_id' ,
        'to_id' ,
        'chat_default_local' ,
        'chat_ip',
    ];

    public $translationModel = 'App\Models\PyChatTranslation';

    public $paginateParamName = 'pychat_page';

    protected $localeKey = 'chat_locale';

    public $perPage = 15;

    public function setChatDefaultMassageAttribute()
    {
        $chat_default_massage = optional($this->translate($this->chat_default_locale))->chat_default_massage;
        return htmlspecialchars_decode(htmlspecialchars_decode($chat_default_massage , ENT_QUOTES) , ENT_QUOTES);
    }
}
