<?php
// +----------------------------------------------------------------------
// | laychat-v3.0
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;

class Findgroup extends Base
{
    //显示查询 / 添加 分组的页面
    public function index()
    {
        //查询最新4名非好友，建议用户添加
        $uid = session('f_user_id');
        $friends = db('friends')->field('friend_id')->where('user_id', $uid)->select();

         $fArr[] = $uid;
        if(!empty($friends)){
            foreach($friends as $vo){
                $fArr[] = $vo['friend_id'];
            }
            unset($friends);
        }
        $where = ' id not in(' . implode(',', $fArr) . ')';
        $userList = db('chatuser')->field('id,user_name,area,avatar,sex,age')->where($where)
            ->order('id desc')->limit(4)->select();
        //初始化省份
        $province = db('area')->field('id,area_name')->where('parent_id', 0)->select();
        //推荐新建的群组，并且是审核通过的，供用户添加,排除已经加入的群
        $inGroupIds = db('groupdetail')->where('user_id = '.session('f_user_id'))->column('group_id');
        $map = 'status = 1 ';
        $map.=!empty($inGroupIds)?'AND id not in ('.implode(',', $inGroupIds).')':'';
        $groupArr = db('chatgroup')->where($map)->order('id desc')->limit(4)->select();

        $this->assign([
            'group' => $groupArr,
            'age' => config('index.age'),
            'province' => $province,
            'user' => $userList
        ]);
        return $this->fetch();
    }

    //检测群组创建权限
    public function checkGroupAuth()
    {
        //用户点击创建群组的时候，检测用户是否还可以创建群粗
        $config = readConfig();
        //Array ( [make] => 1 [pass] => -1 [maxgroup] => 10 [maxjoin] => 15 )
        if(-1 == $config['make']){
            return json(['code' => -1, 'data' => '', 'msg' => '系统不允许创建群组']);
        }

        //检测用户当前创建的群组数是否到达了最大要求
        $cNum = db('chatgroup')->where('owner_id', session('f_user_id'))->count();
        if($cNum >= $config['maxgroup']){
            return json(['code' => -2, 'data' => '', 'msg' => '您已经创建了' . $cNum . '个群组,不可再创建!']);
        }

        return json(['code' => 1, 'data' => '', 'msg' => 'ok']);
    }

    //添加群组
    public function addGroup()
    {
        if (request()->isAjax()) {
            //检测创建群组权限
            $auth = $this->checkGroupAuth()->getData();
            if(1 != $auth['code']){
                return json(['code' => -5, 'data' => '', 'msg' => $auth['msg']]);
            }

            $param = input('post.');

            if (empty($param['group_name'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '群组名不能为空']);
            }

            $param['owner_name'] = session('f_user_name');
            $param['owner_id'] = session('f_user_id');
            $param['owner_avatar'] = session('f_user_avatar');
            $param['owner_sign'] = session('f_user_sign');
            $param['addtime'] = time();

            //如果开启了群组审核功能，那么新创建的群组必须通过审核才可以使用
            //默认不需审核
            $status = 1;
            $config = readConfig();
            if(1 == $config['pass']){
                $status = -1;  //等待审核
            }
            $param['status'] = $status;

            $groupId = db('chatgroup')->insertGetId($param);
            if (empty($groupId)) {
                return json(['code' => -2, 'data' => '', 'msg' => '添加群组失败']);
            }
            unset($param);

            //将自己加入群组
            $groupDetail = [
                'user_id' => session('f_user_id'),
                'user_name' => session('f_user_name'),
                'user_avatar' => session('f_user_avatar'),
                'user_sign' => session('f_user_sign'),
                'group_id' => $groupId,
            ];
            $flag = db('groupdetail')->insert($groupDetail);

            //return json(['code' => 1, 'data' => $groupId, 'msg' => '创建群组 成功']);
            if (empty($flag)) {
                return json(['code' => -3, 'data' => '', 'msg' => '添加群组失败']);
            }

            $return = [
                'join_id' => session('f_user_id'),
                'group_id' => $groupId
            ];
            return json(['code' => 1, 'data' => $return, 'msg' => '创建群组 成功']);
        }

        return $this->fetch();
    }

    /**
     * 上传图片方法
     * @param $param
     */
    public function upGroupAvatar()
    {
        $auth = $this->checkGroupAuth()->getData();
        if(1 != $auth['code']){
            return json(['code' => -5, 'data' => '', 'msg' => $auth['msg']]);
        }
        // 获取表单上传文件
        $file = request()->file('avatar');
    
        // 移动到框架应用根目录/public/uploads/ 目录下
        if(!is_null($file)){

            $ROOT_PATH = Env('root_path');
            $fileInfo = $file->getInfo();

            $imgExt = config('index.common_img');
            $ext = explode('.', $fileInfo['name']);
            $ext = array_pop($ext);

            if(!in_array($ext, $imgExt)){
                return json(['code' => -2, 'data' => '', 'msg' => '请上传' . implode(',' , $imgExt) . '的图片']);
            }
            unset($ext);

            $size = config('index.common_size');
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

        return json(['code' => -1, 'data' => '', 'msg' => '上传群头像失败']);
    }

    //搜索查询群组
    public function search()
    {
       $groupName = input('param.search_txt');
       $find = db('chatgroup')->where("group_name like '%" . $groupName . "%'")->where('status')->select();

       if( empty($find) ){
            return json( ['code' => -1, 'data' => '', 'msg' => '您搜的群不存在' ] );
       }

      return json( ['code' => 1, 'data' => $find, 'msg' => 'success' ] );
    }    

    //申请加组
    public function applyGroup()
    {
        if(request()->isAjax()){

            $param = input('post.');
            //检测该用户是否已经加入了该群组
            $isJoin = db('groupdetail')->where('user_id=' . session('f_user_id') . ' and group_id=' . $param['group'])->find();
            if(!empty($isJoin)){
                return json(['code' => -1, 'data' => '', 'msg' => '您已经加入了该群组']);
            }
            unset($isJoin);

            $return = [
                'uid' => session('f_user_id'),
                'uname' => session('f_user_name'),
                'avatar' => session('f_user_avatar'),
                'sign' => session('f_user_sign')
            ];
            return json(['code' => 1, 'data' => $return, 'msg' => 'success']);
        }
        $this->error('非法访问');
    }

    //同意加入群组
    public function joinDetail()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $flag = db('groupdetail')->insert($param);
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => "系统错误"]);
            }

            return json(['code' => 1, 'data' => '', 'msg' => 'success']);
        }
        $this->error('非法访问');
    }

    //管理我的群组
    public function myGroup()
    {
        if (request()->isAjax()) {
            $groupId = input('param.id');
            $users = db('groupdetail')->field('user_name,user_id,user_avatar,group_id')->where('group_id', $groupId)
                ->paginate(4);

            return json(['code' => 1, 'data' => $users, 'msg' => 'success']);
        }

        $group = db('chatgroup')->field('id,group_name name')->where('owner_id', session('f_user_id'))->select();
        $this->assign([
            'group' => json_encode($group)
        ]);

        return $this->fetch();
    }

    //移出成员出组
    public function removeMember()
    {
        if(request()->isAjax()){
            $uid = input('param.uid');
            $groupId = input('param.gid');

            $canNot = db('chatgroup')->field('owner_id')->where('id=' . $groupId)->find();
            if(empty($canNot)){
                return json(['code' => -1, 'data' => '', 'msg' => '异常操作']);
            }

            if($uid == $canNot['owner_id']){
                return json(['code' => -2, 'data' => '', 'msg' => '不可移除群主']);
            }
            //该群的管理员不是当前的操作人，则为非法操作
            if($canNot['owner_id'] != session('f_user_id')){
                return json(['code' => -3, 'data' => '', 'msg' => '非法操作']);
            }

            $flag = db('groupdetail')->where('user_id = ' . $uid . ' and group_id = ' .$groupId)->delete();
            if(empty($flag)){
                return json(['code' => -4, 'data' => '', 'msg' => '操作失败']);
            }

            return json( ['code' => 1, 'data' => '', 'msg' => '移除成功'] );
        }
        $this->error('非法访问');
    }

    //解散群组
    public function removeGroup()
    {
        if(request()->isAjax()){
            $groupId = input('param.gid');

            //检测解散群组的人是否是该群组的管理员
            $can = db('chatgroup')->field('id')->where('id = ' . $groupId . ' and owner_id = ' . session('f_user_id'))
                ->find();
            if(empty($can)){
                return json(['code' => -1, 'data' => '', 'msg' => '非法操作']);
            }

            //删除群组
            $flag = db('chatgroup')->where('id', $groupId)->delete();
            if(empty($flag)){
                return json(['code' => -2, 'data' => '', 'msg' => '解散群组失败']);
            }

            // 查出该群组的所有的用户id
            $userIds = db('groupdetail')->field('user_id')->where('group_id', $groupId)->select();
            $uids = '';
            foreach($userIds as $vo){
                $uids .= $vo['user_id'] . ',';
            }
            $uids = rtrim($uids, ',');
            unset($userIds);

            //删除群成员
            $flag = db('groupdetail')->where('group_id', $groupId)->delete();
            if(empty($flag)){
                return json(['code' => -3, 'data' => '', 'msg' => '解散群成员失败']);
            }

            return json(['code' => 1, 'data' => $uids, 'msg' => '成功解散该群']);
        }
        $this->error('非法访问');
    }

    //省市区,三级联动
    public function getArea()
    {
        if(request()->isAjax()){
            $code = input('post.code');

            $result = db('area')->field('id,area_name')->where('parent_id = ' . $code)->select();
            return json(['code' => 1, 'data' => $result, 'msg' => 'success']);
        }
        $this->error('非法访问');
    }
 }   