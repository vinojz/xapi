<?php

namespace app\common\handle;

use app\common\handle\HttpHandle;

/* 曦曦开心*/
class TencentHandle extends HttpHandle
{
    private $app_id = '101506809';

    private $app_key = '28924607a33678d2cd614dcc94e25527';

    private $grant_type = 'authorization_code';

    private $access_token;

    private $open_id;

    private $code;

    // 实例化并传入参数
    public function __construct($code)
    {
        $this->code =$code;
    }

    public function get_access_token(){
        $url="https://graph.qq.com/oauth2.0/token";
        $param['grant_type']= $this->grant_type;
        $param['client_id']= $this->app_id;
        $param['client_secret']=$this->app_key;
        $param['code']=$this->code;
        $param['redirect_uri']="http://www.xxhappy.cn/index.php/v1/auth";
        $param =http_build_query($param,"","&");
        $url=$url."?".$param;
        parse_str($this->curl_request($url),$data);
        if(!isset($data['access_token'])) 
            return json(['code'=> 0,'msg'=>'ACCESSTOKEN 获取失败']);
        $this->access_token = $data['access_token'];
    }

    public function get_openid(){
        $url  = "https://graph.qq.com/oauth2.0/me?access_token=".$this->access_token;
        $open_res = $this->curl_request($url);
        if(strpos($open_res,"callback") !== false){
            $lpos = strpos($open_res,"(");
            $rpos = strrpos($open_res,")");
            $open_res = substr($open_res,$lpos + 1 ,$rpos - $lpos - 1);
        }
        $user = json_decode($open_res);
        $this->open_id = $user->openid;
        return $user->openid;
    }

    public function get_user_info(){
        $url = "https://graph.qq.com/user/get_user_info?access_token=".$this->access_token."&oauth_consumer_key=101506809&openid=".$this->open_id;
        $userinfo = json_decode($this->curl_request($url));
        $data['name'] = $userinfo->nickname;
        $data['avatar'] = $userinfo->figureurl;
        $data['username'] = $this->open_id;
        return $data;
    }
}