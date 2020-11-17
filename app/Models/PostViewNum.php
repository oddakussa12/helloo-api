<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostViewNum extends Model
{

	protected $table = "posts_views_nums";

	const CREATED_AT = 'post_view_num_created_at';

	const UPDATED_AT = 'post_view_num_updated_at';

	protected $primaryKey = 'post_view_num_id';

	protected $fillable = ['post_view_num_id' , 'post_id' , 'post_view_num'];

	public $paginateParamName = 'post_view_num_page';
}
