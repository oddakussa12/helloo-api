<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;

class CommentCollection extends Collection
{
    /**
     * Thread the comment tree.
     *
     * @return $this
     */
    public function threaded()
    {
        $comments = parent::groupBy('comment_comment_p_id');

        if (count($comments)) {
            $comments['root'] = $comments[''];
            unset($comments['']);
        }

        return $comments;
    }
}
