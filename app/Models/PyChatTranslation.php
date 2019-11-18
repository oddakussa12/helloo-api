<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PyChatTranslation extends Model
{
	public $timestamps = false;

    protected $table = 'pychats_translations';

    protected $fillable = ['chat_uuid','chat_id','chat_translation_id','chat_locale' , 'chat_message'];

    protected $primaryKey = 'chat_translation_id';
}
