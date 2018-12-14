<?php
namespace app\admin\validate;

use think\Validate;

class Plan extends Validate
{

    protected $rule = [
        'uid' => 'require',   //广告商id
        'plan_name'  =>  'require',  //计划名称
        // 'class_id' => 'require',//请选择活动分类
        'budget' => 'require|integer', //每日限额
    ];

    protected $message = [
        'uid' => '广告商单价必填',   //广告商id
        'plan_name'  =>  '计划名称必填',  //计划名称
        // 'class_id' => '请选择活动分类',//请选择活动分类

        'budget.require' => '每日限额必填', //每日限额

        'budget.integer' => '每日限额必须为整型数字', //每日限额
    ];

}