<?php
namespace app\api\model;

use think\Model;

class BlogModel extends Model
{
	protected $pk = 'id';
	protected $table = 'bloglist';

	public function searchTitleAttr($query, $value, $data)
    {
        $query->where('title','like', '%' . $value . '%')
        	  ->where('content','like', '%' . $value . '%')
        	  ->where('content_short','like', '%' . $value . '%');
    }

    public function searchTagAttr($query, $value, $data)
    {
        $query->where('tag','like', '%' . $value . '%');
    } 
}