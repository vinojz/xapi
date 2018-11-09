<?php

namespace app\common\handle;

use app\common\handle\HttpHandle;

class WorkWechatHandle extends HttpHandle
{
    public function send_workweixin_textcard($data)
    {
        $corpid       = 'wwb4f4bb86ab60d5ce';
        $agentid      = '1000008';
        $access_token = $this->get_access_token();
        $url          = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $access_token;
        $description = "<div class=\"normal\">" . $data['keyword1'] . "</div><br>";
        if (!empty($data['keyword2'])) {
            $description .= "<div class=\"highlight\">" . $data['keyword2'] . "</div><br>";
        }

        $post_data    = [
            'touser'   => 'JingZhuan',//成员ID列表（消息接收者，多个接收者用‘|’分隔，最多支持1000个）。特殊情况：指定为@all，则向关注该企业应用的全部成员发送
            'msgtype'  => 'textcard',
            'agentid'  => $agentid,
            'textcard' => [
                'title'       => $data['title'],
                'description' => $description,
                'url'         => 'url'
            ]
        ];
        $this->curl_request($url,json_encode($post_data));  //发送消息 curl post请求请自行百度实现
    }
    
    protected function get_access_token()
    {
        $corpid     = 'wwb4f4bb86ab60d5ce';
        $corpsecret = 'xRaUnGvUQct_JZnZiuBZgk1Efdo_Eq87fZoq1fdlIaA';
        $url        = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=' . $corpid . '&corpsecret=' . $corpsecret;
        $json       = $this->curl_request($url); //curl 方法请自行百度
        $result     = json_decode($json, true);
        if (isset($result['errcode']) || $result['errcode'] === 0) {
            return $result['access_token']; //todo实际场景中请做好缓存逻辑
        } else {
            throw new \Exception('access_token获取异常' . $result['errmsg'], $result['errcode']);
        }
    }
}