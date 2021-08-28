<?php
namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class Recipient extends Model
{
    protected $table = "recipients";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'longitude' => 'double',
        'is_default' => 'bool',
        'latitude' => 'double',
    ];
}