<?php
// +----------------------------------------------------------------------
// | laychat-v3.0
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;

use think\Controller;
use session\Session;

class Base extends Controller
{
    public function _initialize()
    {
        if(empty(session('f_user_name'))){

            $this->redirect(url('login/index'));
        }
    }
}