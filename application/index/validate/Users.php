<?php
namespace app\Index\validate;

use think\Validate;

class Users extends Validate
{

    protected $rule = [
        //'username' => 'unique:users|require',   //用户名
        'reg_password'=>'require|confirm:reg_password_2'//密码
    ];

    protected $message = [
        'reg_password.require' => '密码不能为空',    //密码
        'reg_password.confirm' => '两次输入的密码不一致',    //密码
        'reg_name.require' => '用户名不能为空',  //用户名
        'reg_name.unique'  => '用户名已被注册',  //用户名
    ];

}