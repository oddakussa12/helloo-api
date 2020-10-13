<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceMessage extends Model
{
    protected $table = "service_messages";

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $dateFormat = "U";

    protected $fillable = [
        'type',
        'value',
        'title',
        'content',
        'image'
    ];
}
