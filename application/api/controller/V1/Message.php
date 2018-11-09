<?php
namespace app\api\controller\v1;
use think\Controller;
use think\Request;
use Db;
use app\api\controller\Api;
use app\api\controller\Send;

class Message extends Api
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {   
        $params = input('get.');
        $where = [];
        $order = 'blogmessage.id desc';
        if(isset($params['key']) && trim($params['key'])) 
            $where[] = ['content','like','%'.$params['key'].'%'];
        if(isset($params['status']) && trim($params['status']) != '') 
            //$where[] = ['status','in',explode(',', trim($params['status']))];
        	$where[] = ['status','in',[0,1]];
         if(isset($params['sort']) && trim($params['sort']))
            $order = 'blogmessage.id '.$params['sort'];
        $page = isset($params['page'])?$params['page']:1;
        $limit = isset($params['limit'])?$params['limit']:100;
        try{
            $query =  Db::view('blogmessage','*')->view('user','name,avatar,username','user.id = blogmessage.uid')->where($where);
            $message = $query->page($page)->limit($limit)->order($order)->select();
            $total = $query->count();
            return json(['data'=>$message,'code'=>20000,'msg'=>'','total'=>$total]); 
        }catch(\Exception $e){
            return $this->returnmsg(401,$e->getMessage());
        }
    }

 
    /**
     * 保存新建的资源
     * @method POST
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = input('');
        if(!isset($params['uid']) || !trim($params['uid'])) 
            return $this->returnmsg(1002,'请先登录或授权');
        if(!isset($params['content']) || !trim($params['content'])) 
            return $this->returnmsg(1003,'请说点什么吧');
        try{
            db('blogmessage')->strict(false)->insert($params);
            return $this->returnmsg(20000,'留言成功');
        }catch(\Exception $e){
            return $this->returnmsg(1000,'留言失败');
            //return json($e->getMessage());
        }
    }
 
    /**
     * 保存更新的资源,留言状态变更
     * @method PUT
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $params = input('put.');
        if(!isset($params['status']) || !in_array($params['status'], ['0','1','2']))
            return $this->returnmsg(1001,'参数错误');
        try{
            db('blogmessage')->strict(false)->update($params);
            return $this->returnmsg(20000,'修改成功');
        }catch(\Exception $e){
            return $this->returnmsg(1004,$e->getMessage());
        }
        
    }
 
    /**
     * 删除指定资源
     * @method DELETE
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        try{
            db('blogmessage')->delete($id);
            return $this->returnmsg(20000,'删除成功');
        }catch(\Exception $e){
            return $this->returnmsg(1004,$e->getMessage());
        }
    }
}
