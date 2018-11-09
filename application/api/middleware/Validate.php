<?php

namespace app\api\middleware;

use think\Controller;

class Validate extends Controller
{
    /**
     * 默认返回资源类型
     * @var \think\Request $request
     * @var mixed $next
     * @var string $name
     * @throws \Exception
     * @return mixed
     */
    public function handle($request, \Closure $next, $name)
    {
        //获取当前参数
        $params = $request->param();
        //获取访问模块
        $module = $request->module();
        //获取访问控制器
        $vcontroller = explode('.',lcfirst($request->controller()));
        $vcontroller[1] = ucfirst($vcontroller[1]);
        $controller = implode('\\',$vcontroller);
        //获取操作名,用于验证场景scene
        $scene    = $request->action();
        $validate = "\\app\\" . $module . "\\validate\\" . $controller;
        //仅当验证器存在时 进行校验
        if (class_exists($validate)) {
            $v = $this->app->validate($validate);
            if ($v->hasScene($scene)) {
                //仅当存在验证场景才校验
                $result = $this->validate($params, $validate . '.' . $scene);
                if (true !== $result) {
                    //校验不通过则直接返回错误信息
                    return json(['code' => 1007, 'msg' => $result]);
                }
            }
        }
        return $next($request);
    }
}
