<?php
namespace app\api\controller\v1;
use think\Controller;
use think\Request;
use Db;
use app\api\controller\Api;
use app\api\controller\Send;
use app\api\model\BlogModel;

class Blog extends Api
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {      
        $where = [];$map = [];$order = 'id desc';
        $params = input('get.');
        if(isset($params['title']) && trim($params['title'])){
            $where[] = ['content','like','%'.$params['title'].'%'];
            $where[] = ['title','like','%'.$params['title'].'%'];
            $where[] = ['content_short','like','%'.$params['title'].'%'];
        }
        if(isset($params['tag']) && trim($params['tag'])){
            $map[] = ['tag','like','%'.$params['tag'].'%'];
        }
        if(isset($params['sort']) && trim($params['sort'])){
            $order = 'id '.$params['sort'];
        }
        $page = isset($params['page'])?$params['page']:1;
        try{
            $bloglist = db('bloglist')->whereOr($where)->where($map)->field('content',true)->order($order)->page($page)->limit(10)->select();
            $total = db('bloglist')->whereOr($where)->where($map)->count();
            return json(['items'=>$bloglist,'code'=>20000,'total'=>$total]);
        }catch(\Exception $e){
            return $this->returnmsg(1200,$e->getMessage());
        } 
    }

    public function read($id)
    {
        try{
            $blog = db('bloglist')->where('id',$id)->find(); 
            return json(['blog'=>$blog,'code'=>20000,'msg'=>'']); 
        }catch(\Exception $e){
           return $this->returnmsg(1200,$e->getMessage());
        }         
    }

    /*
    新增 博客post
     */
    public function save()
    {
        $params = input('post.');
        unset($params['id']);
        try{
            db('bloglist')->strict(false)->insert($params);
             return $this->returnmsg(20000,'保存成功');
        }catch(\Exception $e){
            return $this->returnmsg(1200,$e->getMessage());
        }
    }

    public function edit($id)
    {
        $params = input('params');
        try{
            db('bloglist')->update($params);
            return $this->returnmsg(20000,'保存成功');
        }catch(\Exception $e){
            return $this->returnmsg(1200,$e->getMessage());
        }
    }

    /*
    更新 博客put
     */
    public function update($id){
        $params = input('put.');
        try{
            db('bloglist')->strict(false)->update($params);
            return $this->returnmsg(20000,'保存成功');
        }catch(\Exception $e){
            return $this->returnmsg(1200,$e->getMessage());
        } 
    }
}
