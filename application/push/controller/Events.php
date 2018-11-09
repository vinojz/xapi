<?php
namespace app\push\controller;

use \GatewayWorker\Lib\Gateway;
use \think\Db;
use \think\worker\Events;
/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $data)
    {
        $message = json_decode($data, true);

        $message_type = $message['type'];
        switch ($message_type) {
            case 'init':
                // uid
                $uid = $message['id'];
                // 设置session
                $_SESSION = array(
                    'username' => $message['username'],
                    'avatar' => $message['avatar'],
                    'id' => $uid,
                    'sign' => $message['sign']
                );

                // 将当前链接与uid绑定
                Gateway::bindUid($client_id, $uid);


                //查询最近1周有无需要推送的离线信息
                $time = time() - 7 * 3600 * 24;
                $resMsg = db('chatlog')
                        ->where('to_id','=',$uid)
                        ->where('timeline','>',$time)
                        ->where('type','=','friend')
                        ->where('need_send','=',1)
                        ->field('id,from_id,from_name,from_avatar,timeline,content')
                        ->select();
                if (!empty($resMsg)) {

                    foreach ($resMsg as $key => $vo) {

                        $log_message = [
                            'message_type' => 'logMessage',
                            'data' => [
                                'username' => $vo['from_name'],
                                'avatar' => $vo['from_avatar'],
                                'id' => $vo['from_id'],
                                'type' => 'friend',
                                'content' => htmlspecialchars($vo['content']),
                                'timestamp' => $vo['timeline'] * 1000,
                            ]
                        ];

                        Gateway::sendToUid($uid, json_encode($log_message));

                        //设置推送状态为已经推送
                        db('chatlog')->where('id','=',$uid)->setField('need_send',0);
                    }
                }

                // 通知所有该用户好友，此用户上线，将此用户头像变亮
                $friends = db('friends')->where('friend_id','=',$uid)->field('user_id')->select();
                if (!empty($friends)) {
                    foreach ($friends as $key => $vo) {
                        $user_client_id = Gateway::getClientIdByUid($vo['user_id']);
                        if (!empty($user_client_id)) {
                            $online_message = [
                                'message_type' => 'online',
                                'id' => $uid,
                            ];
                            Gateway::sendToClient($user_client_id['0'], json_encode($online_message));
                        }
                    }
                }

                //查询当前的用户是在哪个分组中,将当前的链接加入该分组
                $ret = db('groupdetail')->where('user_id','=',$uid)->field('group_id')->group('group_id')->select();
                if (!empty($ret)) {
                    foreach ($ret as $key => $vo) {
                        Gateway::joinGroup($client_id, $vo['group_id']);  //将登录用户加入群组
                    }
                }
                unset($ret);
                //设置用户为登录状态
                db('chatuser')->where('id','=',$uid)->setField('status',1);
                return;
            case 'online':
                // 在线切换状态
                $friends = db('friends')->where('friend_id','=',$message['uid'])->field('user_id')->select();
                if (!empty($friends)) {
                    foreach ($friends as $key => $vo) {
                        $user_client_id = Gateway::getClientIdByUid($vo['user_id']);
                        if (!empty($user_client_id)) {
                            $online_message = [
                                'message_type' => ('online' == $message['status']) ? 'online' : 'offline','id' => $message['uid'],
                            ];
                            Gateway::sendToClient($user_client_id['0'], json_encode($online_message));
                        }
                    }
                }
                return;
                break;
            case 'addFriend':
                $client_id = Gateway::getClientIdByUid($message['toid']);
                //用户在线才通知
                if(!empty($client_id)){
                    $add_message = [
                        'message_type' => 'addFriend',
                        'data' => [
                            'username' => $message['username'],
                            'avatar' => $message['avatar'],
                            'id' => $message['id'],
                            'type' => 'friend',
                            'sign' => $message['sign'],
                            'groupid' => $message['groupid'],
                        ]
                    ];
                    Gateway::sendToClient($client_id['0'], json_encode($add_message));
                }
                return;
                break;
            case 'chatMessage':
                // 聊天消息
                $type = $message['data']['to']['type'];
                $to_id = $message['data']['to']['id'];
                $uid = $_SESSION['id'];

                $chat_message = [
                    'message_type' => 'chatMessage',
                    'data' => [
                        'username' => $_SESSION['username'],
                        'avatar' => $_SESSION['avatar'],
                        'id' => $type === 'friend' ? $uid : $to_id,
                        'type' => $type,
                        'content' => htmlspecialchars($message['data']['mine']['content']),
                        'timestamp' => time() * 1000,
                    ]
                ];
                print_r($chat_message);
                // 加入聊天log表
                $param = [
                    'from_id' => $uid,
                    'to_id' => $to_id,
                    'from_name' => $_SESSION['username'],
                    'from_avatar' => $_SESSION['avatar'],
                    'content' => htmlspecialchars($message['data']['mine']['content']),
                    'timeline' => time(),
                    'need_send' => 0
                ];

                switch ($type) {
                    // 私聊
                    case 'friend':
                        // 插入
                        $param['type'] = 'friend';
                        if (empty(Gateway::getClientIdByUid($to_id))) {
                            $param['need_send'] = 1;  //用户不在线,标记此消息推送
                        }
                        db('chatlog')->data($param)->insert();
                        return Gateway::sendToUid($to_id, json_encode($chat_message));
                    // 群聊
                    case 'group':
                        $param['type'] = 'group';
                        db('chatlog')->data($param)->insert();
                        return Gateway::sendToGroup($to_id, json_encode($chat_message), $client_id);
                }
                return;
                break;
            case 'addGroup':
                 // 创建群组
                $client_id = Gateway::getClientIdByUid($message['join_id']);
                Gateway::joinGroup($client_id['0'], $message['id']); // 将该用户加入群组
                $add_message = [
                    'message_type' => 'addGroup',
                    'data' => [
                          'type' => 'group',
                          'avatar'   => $message['avatar'],
                          'id'       => $message['id'],
                           'groupname'     => $message['groupname']
                       ]
                   ];
                Gateway::sendToClient($client_id['0'], json_encode($add_message));
                return;
                break;
            case 'applyGroup':
                // 申请加入群组
                // 通知群组管理员，进行入群申请
                $client_id = Gateway::getClientIdByUid($message['to_id']);
                if(!empty($client_id)){
                    $apply_message = [
                        'message_type' => 'applyGroup',
                        'data' => [
                            'uid' => $message['to_id'],  //群组的管理员
                            'groupname' => $message['groupname'],
                            'groupid' => $message['groupid'],
                            'groupavatar' => $message['groupavatar'],
                            'joinid' => $message['join_id'],  //申请加入群组的id
                            'joinname' => $message['join_name'], //申请加入群组的用户名
                            'joinsign' => $message['join_sign'], //申请加组的的用户签名
                            'joinavatar' => $message['join_avatar'], // 申请人头像
                            'remark' => $message['remark'] //附加信息
                        ]
                    ];
                    Gateway::sendToClient($client_id['0'], json_encode($apply_message));
                }
                return;
                break;
            case 'joinGroup':
                //将申请人加入讨论组
                $client_id = Gateway::getClientIdByUid($message['join_id']); //若在线实时推送
                if( !empty($client_id) ){
                    Gateway::joinGroup($client_id['0'], $message['group_id']);  //将该用户加入群组

                    $add_message = [
                        'message_type' => 'addGroup',
                        'data' => [
                            'type' => 'group',
                            'avatar'   => $message['group_avatar'],
                            'id'       => $message['group_id'],
                            'groupname'     => $message['group_name']
                        ]
                    ];
                    Gateway::sendToClient($client_id['0'], json_encode($add_message));
                }
                return;
                break;
            case 'removeMember':
                //将移除群组的成员的群信息移除，并从讨论组移除
                $client_id = Gateway::getClientIdByUid( $message['remove_id'] );
                if( !empty($client_id) ){
                    Gateway::leaveGroup($client_id['0'], $message['group_id']);
                    $del_message = [
                        'message_type' => 'delGroup',
                        'data' => [
                            'type' => 'group',
                            'id'   => $message['group_id']
                        ]
                    ];
                    Gateway::sendToClient($client_id['0'], json_encode($del_message));
                }
                return;
                break;
            case "breakUp":
                // 解散群组
                $uids = explode(',', $message['uids']);
                foreach($uids as $key=>$vo){
                    $client_id = Gateway::getClientIdByUid($vo);
                    if(!empty($client_id)){
                        Gateway::leaveGroup($client_id['0'], $message['group_id']);

                        $del_message = [
                            'message_type' => 'delGroup',
                            'data' => [
                                'type' => 'group',
                                'id'       => $message['group_id']
                            ]
                        ];
                        Gateway::sendToClient($client_id['0'], json_encode($del_message));
                    }
                }
                return;
                break;
            case 'leaveGroup':
                // 退出群组
                $client_id = Gateway::getClientIdByUid($message['leave_id']);
                if( !empty($client_id) ){
                    Gateway::leaveGroup($client_id['0'], $message['group_id']);
                }
                return;
                break;
            case 'black':
                $client_id = Gateway::getClientIdByUid($message['to_id']);
                //用户在线才通知
                if(!empty($client_id)){
                    $add_message = [
                        'message_type' => 'black',
                        'data' => [
                            'id' => $message['del_id']
                        ]
                    ];
                    Gateway::sendToClient($client_id['0'], json_encode($add_message));
                }
                return;
                break;
            case 'delFriend':

                $client_id = Gateway::getClientIdByUid($message['to_id']);
                //用户在线才通知
                if(!empty($client_id)){
                    $add_message = [
                        'message_type' => 'delFriend',
                        'data' => [
                            'id' => $message['del_id']
                        ]
                    ];
                    Gateway::sendToClient($client_id['0'], json_encode($add_message));
                }
                return;
                break;
        }
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        //维持连接的worker退出才触发
        if(!empty($_SESSION['id'])){
            //通知该用户的好友，该用户下线
            $friends = db('friends')->where('friend_id','=',$_SESSION['id'])->field('user_id')->select();
            if (!empty($friends)) {
                foreach ($friends as $key => $vo) {
                    $user_client_id = Gateway::getClientIdByUid($vo['user_id']);
                    if (!empty($user_client_id)) {
                        $online_message = [
                            'message_type' => 'logout',
                            'id' => $_SESSION['id'],
                        ];
                        Gateway::sendToClient($user_client_id['0'], json_encode($online_message));
                    }
                }
            }
            //设置用户为退出状态
            db('chatuser')->where('id','=',$_SESSION['id'])->setField('status',0);
        }
    }
}