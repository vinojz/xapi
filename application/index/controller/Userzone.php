<?php
// +----------------------------------------------------------------------
// | laychat-v3.0
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;

class Userzone extends Base
{
    //指定用户的空间首页
    //进入我的空间，则显示所有的好友的更新的状态,以及相关的评论信息
    //进入某人的空间，则集中显示这个人的最近更新的内容以及相关评论
    public function index()
    {
        //查询自己，以及好友发布的说说
        $uid = session('f_user_id');
        $friends = db('friends')->field('friend_id')->where('user_id', $uid)->select();

        $uids = '';
        if(!empty($friends)){
            foreach($friends as $key=>$vo){
                $uids .= $vo['friend_id'] . ',';
            }
        }

        $uids .= $uid;
        $blogs = db('mblog')->where('post_uid in(' . $uids . ')')->order('post_time desc')
            ->paginate(config('blog_page'));

        $this->assign([
            'user' => session('f_user_name'),
            'blogs' => $blogs
        ]);

        return $this->fetch();
    }

    //发表说说
    public function postTips()
    {
        if(request()->isAjax()){

            $content = input('post.content');
            if(empty($content)){
                return json(['code' => -1, 'data' => '', 'msg' => '发表说说不能为空']);
            }

            $param = [
                'post_user' => session('f_user_name'),
                'post_uid' => session('f_user_id'),
                'post_avatar' => session('f_user_avatar'),
                'content' => $content,
                'post_time' => time()
            ];

            $flag = db('mblog')->insert($param);
            if(empty($flag)){
                return json(['code' => -3, 'data' => '', 'msg' => '系统错误']);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '发表成功']);
        }

        $this->error('非法访问');
    }

    //上传说说图片
    public function upImg()
    {
        // 获取表单上传文件
        $file = request()->file('file');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if(!is_null($file)){

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

            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                $avatar = '/uploads' . '/' . date('Ymd') . '/' . $info->getFilename();
                return json(['code' => 0, 'data' => ['src' => $avatar], 'msg' => '']);
            }else{
                // 上传失败获取错误信息
                return json(['code' => -4, 'url' => '', 'msg' => $file->getError()]);
            }
        }

        return json(['code' => -1, 'data' => '', 'msg' => '上传图片失败']);
    }

    //发表评论
    public function postComment()
    {
        if(request()->isAjax()){

            $blogId = input('post.blog_id');
            $content = input('post.content');
            if(empty($content)){
                return json(['code' => -1, 'data' => '', 'msg' => '发表评论不能为空']);
            }

            $param = [
                'blog_id' => $blogId,
                'com_user' => session('f_user_name'),
                'com_uid' => session('f_user_id'),
                'com_avatar' => session('f_user_avatar'),
                'content' => $content,
                'com_time' => time()
            ];

            $flag = db('comment')->insert($param);
            if(empty($flag)){
                return json(['code' => -3, 'data' => '', 'msg' => '系统错误']);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '评论成功']);
        }

        $this->error('非法访问');
    }

    //进入指定好友的空间
    public function uZone()
    {
        $delStr = 'layim-friend';
        $friendId = input('fid');
        $friendId = substr($friendId, strlen($delStr), strlen($friendId));
        //查询属于该用户的说说信息
        $blogs = db('mblog')->where('post_uid', $friendId)->order('post_time desc')->paginate(config('blog_page'));

        $this->assign([
            'user' => db('chatuser')->field('user_name')->where('id', $friendId)->find(),
            'blogs' => $blogs
        ]);

        return $this->fetch();
    }
}