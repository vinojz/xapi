<?php
namespace app\api\model;

use think\Model;

class MessageModel extends Model
{
	protected $pk = 'id';
	protected $table = 'blogmessage';

	public function searchTitleAttr($query, $value, $data)
    {
        $query->where('title','like', '%' . $value . '%')
    }

    public function searchContentAttr($query, $value, $data)
    {
        $query->where('content','like', '%' . $value . '%')
    }

    public function searchContentShortAttr($query, $value, $data)
    {
        $query->where('content_short','like', '%' . $value . '%');
    }

    public function searchTagAttr($query, $value, $data)
    {
        $query->where('tag','like', '%' . $value . '%');
    } 
}