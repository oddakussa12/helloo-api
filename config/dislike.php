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
    'dislikes_table' => 'post_dislikes',


    'dislikes_country_field' => 'dislikable_country',


    /*
     * Model name for like record.
     */
    'dislike_model' => 'App\Models\Dislike',
];
