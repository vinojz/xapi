<?php

namespace app\common\handle;

use app\common\handle\HttpHandle;

class WechatAppletHandle extends HttpHandle
{
    private $AppID = 'wx9fe93ebe6d3c3aad';
    private $AppSecret = 'f195355f4715fa4707fafd491e1192dd';
    private $code;

    // 实例化并传入参数
    public function __construct($code)
    {
        $this->code =$code;
    }
    public function code2Session(){
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$this->AppID.'&secret='.$this->AppSecret.'&js_code='.$this->code.'&grant_type=authorization_code';
        return json_decode($this->curl_request($url),true);
    }
}