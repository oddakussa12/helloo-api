<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YesterdayScore extends Model
{
    //
    public $timestamps = false;

    protected $fillable = [
        'user_id' ,
        'user_score' ,
        'rank_date' ,
    ];

    public function user()
    {
        return $this->belongsTo(User::class , 'user_id' , 'user_id');
    }
}
