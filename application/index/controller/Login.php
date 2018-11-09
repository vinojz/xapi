<?php
// +----------------------------------------------------------------------
// | laychat-v3.0
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;

use think\Controller;

class Login extends Controller
{
    //展示登录页面
    public function index()
    {
        return $this->fetch();
    }

    //处理登录
    public function doLogin()
    {
        if(request()->isAjax()){

            $userName = input('post.user_name');
            if(empty($userName)){
                return json(['code' => -3, 'data' => '', 'msg' => '用户名不能为空']);
            }

            $pwd = input('post.pwd');
            if(empty($pwd)){
                return json(['code' => -4, 'data' => '', 'msg' => '密码不能为空']);
            }

            $user = db('chatuser')->field('id,user_name,pwd,sign,avatar')
                ->where('user_name = "' . $userName . '"')->find();
            if(empty($user)){
                return json(['code' => -1, 'data' => '', 'msg' => '用户不存在']);
            }

            if( md5($pwd) != $user['pwd'] ){
                return json(['code' => -2, 'data' => '', 'msg' => '密码错误']);
            }

            //设置用户登录
            db('chatuser')->where('id', $user['id'])->setField('status', 1);

            //设置session标识状态
            session('f_user_name', $user['user_name']);
            session('f_user_id', $user['id']);
            session('f_user_sign', $user['sign']);
            session('f_user_avatar', $user['avatar']);

            return json(['code' => 1, 'data' => url('index/index'), 'msg' => '登录成功']);
        }

        $this->error('非法访问');
    }

    //注册页面
    public function register()
    {
        $ip = request()->ip();
        $taobaoUrl = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $taobaoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ( $ch,  CURLOPT_NOSIGNAL,true);//支持毫秒级别超时设置
        curl_setopt($ch, CURLOPT_TIMEOUT, 1200);   //1.2秒未获取到信息，视为定位失败
        $myCity = curl_exec($ch);
        curl_close($ch);

        $myCity = json_decode($myCity, true);

        if('中国' != $myCity['data']['country']){
            $local = ['110000', '110100', '110101'];  //默认定位北京东城
        }else{
            $local = [$myCity['data']['region_id'], $myCity['data']['city_id'], 0];
        }
        unset($myCity);

        $this->assign([
            'local' => $local
        ]);
        return $this->fetch();
    }

    //处理注册
    public function doRegister()
    {
        if(request()->isAjax()){
            $param = input('post.');

            //TODO 理论上应该对所有的传入参数做正则校验,此处为了节省时间，暂时未做
            if($param['pwd'] != $param['repwd']){
                return json(['code' => -1, 'data' => '', 'msg' => '两次密码输入不一致']);
            }

            //查询获得区域描述
            $where = 'id =' . $param['province'];
            if(!empty($param['city'])){
                $where .= ' or id=' . $param['city'];
            }else{
                $param['city'] = 0;
            }

            if(!empty($param['area'])){
                $where .= ' or id=' . $param['area'];
            }else{
                $param['area'] = 0;
            }
            $area = db('area')->field('area_name')->where($where)->order('level asc')->select();

            $areaStr = '';
            if(!empty($area)){
                foreach($area as $key=>$vo){
                    $areaStr .= $vo['area_name'] . '-';
                }
                $areaStr = rtrim($areaStr, '-');
            }else{
                $areaStr = '北京-北京市-东城区';
            }
            unset($area);

            $insertData = [
                'user_name' => trim($param['user_name']),
                'pwd' => md5($param['pwd']),
                'sign' => '暂无',
                'avatar' => config('avatar'),
                'sex' => $param['sex'],
                'age' => $param['age'],
                'pid' => $param['province'],
                'cid' => $param['city'],
                'aid' => $param['area'],
                'area' => $areaStr,
                'status' => 0
            ];
            unset($param);

            db('chatuser')->insert($insertData);
            return json(['code' => '1', 'data' => url('login/index'), 'msg' => '注册成功']);
        }
        $this->error('非法访问');
    }

}