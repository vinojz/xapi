<?php
// +----------------------------------------------------------------------
// | laychat-v3.0
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;

class Finduser extends Base
{
    //显示查询 / 添加 分组的页面
    public function index()
    {
        $param = input('param.');
        $find = db('chatuser')->select();

        if( empty($find) ){
            return json( ['code' => -1, 'data' => '', 'msg' => '迷路了' ] );
        }
        return json( ['code' => 1, 'data' => $find, 'msg' => 'success' ] );
    }
 }   