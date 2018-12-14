<?php
namespace app\home\validate;

use think\Validate;

class Users extends Validate
{

    protected $rule = [
        'mobile' => 'require',   //手机
        'qq'  =>  'require',  //QQ
        'email' => 'email', //Email
        'tel' => 'require', //固定电话
        'idcard' => 'require', //身份证
    ];

    protected $message = [
        'mobile' => '手机号必填',   //手机
        'qq'  =>  'QQ必填',  //QQ
        'email' => 'Email格式不正确', //Email
        'tel' => '移动电话必填', //固定电话
        'idcard' => '身份证号必填', //身份证
    ];

}