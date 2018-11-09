<?php

namespace app\common\handle;

use Exception;
use think\exception\Handle as TpHandle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\console\Output as ConsoleOutput;
use app\common\handle\WorkWechatHandle;

class ExceptionHandle extends TpHandle
{
    protected function alarm(Exception $exception)
    {
        try {
            //将异常所在文件,以及行数 通知开发者 方便排查异常原因
            $errmsg = $exception->getMessage();
                $data = [
                    'title'    => '程序异常通知',
                    'keyword1' => "file: " . $exception->getFile() . ';line:' . $exception->getLine(),
                    'keyword2' => "message: " . $errmsg,
                ];
                //发送微信企业消息 通知开发者 下文会详细讲解
                $WorkWechatHandle = new WorkWechatHandle;
                $WorkWechatHandle->send_workweixin_textcard($data);
        } catch (Exception $e) {
            //需手动捕获异常,防止上文异常后死循环
            trace('消息发送失败:'. $e->getMessage() . $errmsg, 'error');
        }
    }

    public function report(Exception $e)
    {
        //异常通知
        $this->alarm($e);
        //交由Thinkphp框架继续处理
        parent::report($e);
    }

    //在CLI命令行模式有异常时将由此方法接管
    /**
     * @param Output $output
     * @param Exception $e
     */
    public function renderForConsole(ConsoleOutput $output, Exception $e)
    {
        //异常通知
        $this->alarm($e);
        //交由Thinkphp框架继续处理
        parent::renderForConsole($output, $e);
    }

    //在普通模式有异常时将由此方法接管
     /**
     * @param Exception $e
     */
    public function render(Exception $e)
    {
        try {
            $data = [
                'code' => intval($e->getCode()) ?: -1,
                'msg'  => $e->getMessage(),
            ];
            //因本项目是API接口开发 统一返回固定格式
             return json($data);
        } catch (Exception $e) {
            //需手动捕获异常,防止上文异常后造成死循环
            trace('消息发送失败'. $e->getMessage(), 'error');
        }
        //交由Thinkphp框架继续处理
        return parent::render($e);
    }
}