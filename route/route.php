<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
Route::allowCrossDomain();
Route::get(':version/auth/code2Session','api/:version.auth/code2Session');  //一般路由规则，
Route::get(':version/auth','api/:version.auth/index');  //一般路由规则，
Route::post(':version/auth/loginByThirdparty','api/:version.auth/loginByThirdparty');  //一般路由规则

Route::post(':version/login/login','api/:version.login/login');  //一般路由规则，
Route::get(':version/login/getUserInfo','api/:version.login/getUserInfo');  //一般路由规则，
Route::post(':version/login/logout','api/:version.login/logout');  //一般路由规则，

Route::post('v1/tools/uploadimg','api/v1.tools/uploadimg');  //一般路由规则，
Route::post('v1/admin/login','api/v1.admin/login');  //一般路由规则，
Route::post(':version/admin/login','api/:version.admin/login');  //一般路由规则，

Route::resource(':version/message','api/:version.message'); //资源路由
Route::resource(':version/blog','api/:version.blog');//资源路由
Route::post(':version/token','api/:version.token/token');  //生成access_token

//所有路由匹配不到情况下触发该路由
//Route::miss('\app\api\controller\Exception::miss');
return [

];
