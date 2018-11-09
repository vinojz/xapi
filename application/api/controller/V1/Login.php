<?php
namespace app\api\controller\v1;
use think\Controller;
use think\Request;
use app\api\controller\Api;
use app\api\controller\Send;

//登陆接口
class Login extends Api
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
        $data = [
            'username' => input('post.username'),
            'password' => input('post.password')
        ];
        
        $user = db('user')->where('username',$data['username'])->find();
        if(!$user) 
            return $this->returnmsg(1005,'用户不存在');
        if($user['password'] != $data['password']) 
            return $this->returnmsg(1006,'密码错误');
        return json(['token'=>$user['id'],'code'=>20000,'msg'=>'登录成功']);
    }

    public function getUserInfo(){
        $id = input('get.token');
        $userinfo = db('user')->find($id);
        return json(['code'=>20000,'userinfo'=>$userinfo,'roles'=>['admin']]);
    }

    public function logout()
    {   
        return $this->returnmsg(20000,'退出成功');
    }
}