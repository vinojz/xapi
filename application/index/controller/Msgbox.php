<?php
// +----------------------------------------------------------------------
// | laychat-v3.0
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;

class Msgbox extends Base
{
    //信息框展示页面
    public function index()
    {
        $this->assign([
            'uid' => session('f_user_id'),
            'username' => session('f_user_name'),
            'avatar' => session('f_user_avatar'),
            'sign' => session('f_user_sign')
        ]);
        return $this->fetch();
    }
    
    //获取当前用户有多少个未读通知
    public function getNoRead()
    {
        if(request()->isAjax()){

            $tips = db('message')->where('`uid`=' . session('f_user_id') . ' and `read`=1')->count();
            return json(['code' => 1, 'data' => $tips, 'msg' => 'success']);
        }
        $this->error('非法访问');
    }

    //申请好友
    public function applyFriend()
    {
        if(request()->isAjax()){

            $param = input('post.');
            //检测是否被要添加的用户加入了黑名单，若加入，则无法申请添加该好友
            $inBlack = db('blacktab')->where('put_uid=' . session('f_user_id') . ' and user_id=' . $param['uid'])
                ->find();
            if(!empty($inBlack)){
                return json(['code' => -2, 'data' => '', 'msg' => '对方已将你加入黑名单']);
            }
            //入库系统消息
            $msg = [
                'content' => '申请添加你为好友',
                'uid' => $param['uid'],
                'from' => session('f_user_id'), //发起好友申请的uid
                'remark' => $param['remark'],
                'from_group' => $param['from_group'],
                'type' => 1,
                'read' => 1,
                'time' => time()
            ];

            $flag = db('message')->insert($msg);
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => '系统错误']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => 'success']);
        }
        $this->error('非法访问');
    }

    //获取通知消息
    public function getMsg()
    {
        if(request()->isAjax()){

            $data = db('message')->where('uid', session('f_user_id'))->order('time desc')->paginate(5);
            //拼装发送人信息
            if(empty($data)){
               return json(['code' => 0, 'pages' => 0, 'data' => '', 'msg' => '']);
            }
            $pages = $data->lastPage();

            $msg = $data->all();
            //$msg = objToArr($msg);
            foreach($msg as $key=>$vo){
                $msg[$key]['time'] = date('Y-m-d H:i');
                if(1 == $vo['type']){
                    $user = db('chatuser')->field('avatar,user_name,sign')->where('id', $vo['from'])->find();
                    $msg[$key]['user'] = [
                        'id' => $vo['from'],
                        'avatar' => $user['avatar'],
                        'username' => $user['user_name'],
                        'sign' => $user['sign']
                    ];
                }else{
                    $msg[$key]['user']['id'] = null;
                }
            }

            return json(['code' => 0, 'pages' => $pages, 'data' => $msg, 'msg' => '']);

        }
        $this->error('非法访问');
    }

    //标记当前推送的消息为已读状态
    public function read()
    {
        if(request()->isAjax()){
            $read = input('post.read');
            db('message')->where('uid=' . session('f_user_id'))->setField('read', $read);
            return true;
        }
        $this->error('非法访问');
    }

    //同意好友申请
    public function agreeFriend()
    {
        if(request()->isAjax()){
            $param = input('post.');

            //建立好友关系
            //1、将我与请求人建立关系
            $myFriend = [
                'user_id' => session('f_user_id'),
                'friend_id' => $param['uid'],
                'group_id' => $param['group']
            ];

            $flag = db('friends')->insert($myFriend);
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => '系统错误']);
            }
            unset($myFriend);

            //2、将请求人与我建立关系
            $yourFriend = [
                'user_id' => $param['uid'],
                'friend_id' => session('f_user_id'),
                'group_id' => $param['from_group']
            ];

            $flag = db('friends')->insert($yourFriend);
            if(empty($flag)){
                return json(['code' => -2, 'data' => '', 'msg' => '系统错误']);
            }
            unset($yourFriend);

            //入库系统消息
            $msg = [
                'content' => session('f_user_name') . ' 已经同意你的好友申请',
                'uid' => $param['uid'],
                'from'=>session('f_user_id'),
                'from_group' => $param['from_group'],
                //'remark' => '',
                'type' => 2,
                'read' => 1,
                'time' => time()
            ];


            $flag = db('message')->insert($msg);
            if(empty($flag)){
                return json(['code' => -3, 'data' => '', 'msg' => '系统错误']);
            }

            //将此消息标记为已经同意
            $flag = db('message')->where('id', $param['id'])->setField('agree', 1);
            if(empty($flag)){
                return json(['code' => -4, 'data' => '', 'msg' => '系统错误']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => 'success']);
        }
        $this->error('非法访问');
    }

    //拒绝好友申请
    public function refuseFriend()
    {
        if(request()->isAjax()){

            $param = input('post.');

            //将此消息标记为拒绝
            $flag = db('message')->where('id', $param['id'])->setField('agree', 2);
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => '系统错误']);
            }

            //入库系统消息
            $msg = [
                'content' => session('f_user_name') . ' 拒绝了你的好友申请',
                'uid' => $param['uid'],
                'type' => 2,
                'read' => 1,
                'time' => time()
            ];

            $flag = db('message')->insert($msg);
            if(empty($flag)){
                return json(['code' => -2, 'data' => '', 'msg' => '系统错误']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => 'success']);
        }
        $this->error('非法访问');
    }
}