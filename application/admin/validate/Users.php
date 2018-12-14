<?php
namespace app\admin\validate;

use think\Validate;

class Users extends Validate
{

    protected $rule = [
        'username' => 'require',   //用户名
        'password'  =>  'require',            //密码
    ];

    protected $message = [
        'username.require' => '用户名不能为空',     //用户名
        'password' => '密码不能为空',       //密码
    ];

}