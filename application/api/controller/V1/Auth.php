<?php
namespace app\api\controller\v1;
use think\Controller;
use think\Request;

use app\common\handle\WorkWechatHandle;
use app\common\handle\WechatAppletHandle;
use app\common\handle\TencentHandle;

//第三方登录登陆接口
class Auth extends Controller
{
    
    /**
     * 第三方登录回调地址：QQ
     *
     * @return \think\Response
     */
    public function index()
    {
       if(request()->isGet()){
            $url = input('get.redirect');
            $path = input('get.path');
            $code = input('get.code');
            if(isset($url) && isset($code)){
                return $this->redirect($url.'?code='.$code.'#/'.$path,302);
            }else{
                echo "failed";
            }
        }
    }

    public function code2Session(){
        $code = input('get.code');
        if(!isset($code)) 
            return  json(['code'=> 1101,'msg' =>'参数错误']);
        $wechatAppletHandle = new WechatAppletHandle($code);
        $result = $wechatAppletHandle->code2Session();
        if(!isset($result['openid'])) 
            return  json(['code'=> 1102,'msg' =>'获取失败']);
        return json(['code'=> 20000,'openid' =>$result['openid']]);
    }

    public function loginByThirdparty(){
        if(request()->isPost()){
        	$param = array_merge(input('get.'),input('post.'));
            $auth_type = $param['auth_type'];
            $_method = 'thirdparty_'.$auth_type;
            if(method_exists($this, $_method)) {
                return $this->$_method($param);
            }else{
                return json(['code'=> 1100,'msg'=>'参数错误']);
            }
        }
    }

    protected function thirdparty_wechat_applet($param){
    	if(!isset($param['openid'])) return  json(['code'=> 0,'msg' =>'参数错误']);
    	$user_id = $this->check_user($param['openid']);
    	if(!$user_id){
    		if(isset($param['nickName'])&&isset($param['avatarUrl'])){
    			$user_info['name'] = $param['nickName'];
        		$user_info['avatar'] = $param['avatarUrl'];
        		$user_info['username'] = $param['openid'];
            	$user_id = $this->regiest($user_info,'wechat_applet');
    		}else{
    			return json(['code'=> 20000,'msg' =>'not existed']);
    		}
    	}else{
    		try{
                $user_info = db('user')->field('password',true)->find($user_id);
            }catch(\Exception $e){
                return json(['code'=> 1103,'msg' =>"获取用户信息失败"]);
            }
    	}
    	return json(['code'=> 20000,'token' =>$user_id,'info' => $user_info]);
    }

    protected function thirdparty_tencent($param){
        $code = $param['code'];
        $tencentHandle = new TencentHandle($code);
        $tencentHandle->get_access_token();
        $user_id = $this->check_user($tencentHandle->get_openid());
        if(!$user_id){
            $user_info = $tencentHandle->get_user_info();
            $user_id = $this->regiest($user_info,'tencent');
        }else{
            try{
                $user_info = db('user')->field('password',true)->find($user_id);
            }catch(\Exception $e){
                return json(['code'=> 1103,'msg' =>"获取用户信息失败"]);
            }
        }
        return json(['code'=> 20000,'token' =>$user_id,'info' => $user_info]);
    }

    //检查是否存在用户
    protected function check_user($openid){
        $uid = db('user_auths')->where('third_key',$openid)->value('uid');
        return $uid;
    }

    //注册第三方帐号
    protected function regiest($userinfo,$third_type){
        try{
            $userId = db('user')->insertGetId($userinfo);
            $third = ['uid' => $userId, 'third_type' => $third_type, 'third_key' => $userinfo['username']];
            db('user_auths')->insert($third);
            return $userId;
        }catch(\Exception $e){
            return json(['code'=> 1104,'msg' =>"注册失败"]);
        }
    }
}