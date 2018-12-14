<?php
namespace app\home\validate;

use think\Validate;

class ActivitAdd extends Validate
{

    protected $rule = [
        'zonename' => 'require',   //广告位名称
    ];

    protected $message = [
        'zonename' => '广告位名称必填',   //广告位名称

    ];

}