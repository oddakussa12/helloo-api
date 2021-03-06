<?php

/*
 * This file is part of the overtrue/laravel-like.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

return [
    /*
     * User tables foreign key name.
     */
    'user_foreign_key' => 'user_id',

    /*
     * Table name for likes records.
     */
    'likes_table' => 'common_likes',

    'likes_likable_state' => 'likable_state',


    'likes_country_field' => 'likable_country',


    /*
     * Model name for like record.
     */
    'like_model' => 'App\Models\Like',
];
