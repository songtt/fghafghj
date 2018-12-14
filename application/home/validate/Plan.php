<?php
namespace app\home\validate;

use think\Validate;

class Plan extends Validate
{

    protected $rule = [
        'plan_name'  =>  'require',  //计划名称
        'class_id' => 'require',//请选择活动分类
        'pricedv' => 'require|float',  //广告商单价
        'budget' => 'require|integer', //每日限额
    ];

    protected $message = [
        'plan_name' => '计划名称必填',  //计划名称
        'class_id' => '请选择活动分类',//请选择活动分类

        'pricedv.require' => '单价必填',  //广告商单价
        'budget.require' => '每日限额必填', //每日限额

        'pricedv.float' => '单价必须为数字',  //广告商单价
        'budget.integer' => '每日限额必须为整型数字', //每日限额
    ];

}