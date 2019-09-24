<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTranslation extends Model
{

    protected $primaryKey = 'notification_translation_id';

    protected $fillable = ['notification_info'];

    protected $table = 'notifications_translations';
}
