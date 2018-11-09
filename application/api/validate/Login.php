<?php

namespace app\api\validate;

use think\Validate;

class Login extends Validate
{
    protected $rule = [
        'username'      =>  'require',
        'password'      =>  'require|length:6,16',
    ];

    protected $message  =   [
        'username.require'    => '用户名不能为空',
        'password.length'    => '密码长度在6-16之间',
        'password.require'    => '密码不能为空'  
    ];
    
    protected $scene = [
        'login'  =>  ['username','password'],
    ];
}
