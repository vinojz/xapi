<?php
// +----------------------------------------------------------------------
// | laychat-v3.0
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;

class Chatuser extends Base
{
    //展示当前用户的基本资料
    public function index()
    {
        $user = db('chatuser')->where('id', session('f_user_id'))->find();
        $this->assign([
            'user' => $user
        ]);

        return $this->fetch();
    }

    //修改个人资料
    public function doChange()
    {
        if (request()->isAjax()) {
            $param = input('post.');

            //所有验证应该在后台重新做，此处暂时不做
            if (!empty($param['oldpwd'])) {

                $pwd = db('chatuser')->field('pwd')->where('id', session('f_user_id'))->find();
                if (md5($param['oldpwd']) != $pwd['pwd']) {
                    return json(['code' => -1, 'data' => '', 'msg' => '旧密码不正确！']);
                }

                if($param['pwd'] != $param['repwd']){
                    return json(['code' => -2, 'data' => '', 'msg' => '两次密码输入不一致！']);
                }

                $upData['pwd'] = md5($param['pwd']);
            }

            //查询获得区域描述
            $where = 'id =' . $param['pid'];
            if(!empty($param['cid'])){
                $where .= ' or id=' . $param['cid'];
            }else{
                $param['city'] = 0;
            }

            if(!empty($param['aid'])){
                $where .= ' or id=' . $param['aid'];
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

            $upData['user_name'] = trim($param['user_name']);
            !empty($param['avatar']) && $upData['avatar'] = $param['avatar'];
            $upData['sex'] = $param['sex'];
            $upData['age'] = $param['age'];
            $upData['pid'] = $param['pid'];
            $upData['cid'] = $param['cid'];
            $upData['aid'] = $param['aid'];
            $upData['area'] = $areaStr;

            unset($param);

            $flag = db('chatuser')->where('id', session('f_user_id'))->update($upData);
            if(false === $flag){
                return json(['code' => -3, 'data' => '', 'msg' => '系统错误']);
            }

            //重置头像
            !empty($upData['avatar']) && session('f_user_avatar', $upData['avatar']);

            return json(['code' => 1, 'data' => '', 'msg' => '修改成功']);
        }
        $this->error('非法访问');
    }
    
    //上传个人头像
    public function upAvatar()
    {
        // 获取表单上传文件
        $file = request()->file('avatar');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if(!is_null($file)){
            
            $ROOT_PATH = Env('root_path');
            $fileInfo = $file->getInfo();

            $imgExt = config('common_img');
            $ext = explode('.', $fileInfo['name']);
            $ext = array_pop($ext);

            if(!in_array($ext, $imgExt)){
                return json(['code' => -2, 'data' => '', 'msg' => '请上传' . implode(',' , $imgExt) . '的图片']);
            }
            unset($ext);

            $size = config('common_size');
            if($fileInfo['size'] > $size){
                return json(['code' => -3, 'data' => '', 'msg' => '上传的图片超过' . $size/1024 . 'kb']);
            }
            unset($fileInfo);

            $info = $file->move($ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                $avatar = '/uploads' . '/' . date('Ymd') . '/' . $info->getFilename();
                return json(['code' => 1, 'url' => $avatar, 'msg' => 'success']);
            }else{
                // 上传失败获取错误信息
                return json(['code' => -4, 'url' => '', 'msg' => $file->getError()]);
            }
        }

        return json(['code' => -1, 'data' => '', 'msg' => '修改头像失败']);
    }
    
    //修改个性签名
    public function changeSign()
    {
        if(request()->isAjax()){

            $sign = input('post.sign');
            $flag = db('chatuser')->where('id', session('f_user_id'))->setField('sign', $sign);
            if(false === $flag){
                return json(['code' => -1, 'data' => '', 'msg' => '系统错误']);
            }

            //重置签名
            session('f_user_sign', $sign);

            return json(['code' => 1, 'data' => '', 'msg' => '修改成功']);
        }
        $this->error('非法访问');
    }
}    