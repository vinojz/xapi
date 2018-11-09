<?php
namespace app\api\controller\v1;
use think\Controller;
use think\Request;
use Env;
use app\api\controller\Api;

//后台登陆接口
class Tools extends Api
{
	private $ROOT_PATH;
    private $BASE_URL;

    public function __construct()
    {
        $this->ROOT_PATH = Env::get('root_path');
        $this->BASE_URL = 'http://www.xxhappy.cn/';
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {      
        return $this->error('you are lost');
    }

    public function UploadImg()
    {
        $name = isset($_POST['upimg'])? $_POST['upimg'] : 'sss';
        $img = request()->file('upimg');
        $info = $img->move($this->ROOT_PATH . 'public' . '/' . 'uploads');
        if ($info) {
            $result = [
                'code'     => 20000,
                'msg'      => '上传成功',
                'filename' => '/public/uploads/' . str_replace('\\', '/', $info->getSaveName()),
                'url'      => $this->BASE_URL.'/uploads/'.str_replace('\\', '/', $info->getSaveName())
            ];
        } else {
            $result = [
                'code' => -1,
                'msg'  => $img->getError()
            ];
        }
        return json($result);
    }
}