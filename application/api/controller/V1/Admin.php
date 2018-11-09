<?php
namespace app\api\controller\v1;
use think\Controller;
use think\Request;
use app\api\controller\Api;

//后台登陆接口
class Admin extends Api
{
    
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {  
        return $this->error('you are lost');
    }

    public function login()
    {   
    	$username = input('post.username');
    	$password = input('post.password');
    	$admin = db('admin')->where('username',$username)->find();
    	if(!$admin) return json(['code'=>500,'msg'=>'用户不存在']);
    	if($admin['password'] != $password) return json(['code'=>500,'msg'=>'密码错误']);
        return json(['user'=>$admin,'code'=>200,'msg'=>'登录成功']);
    }
}