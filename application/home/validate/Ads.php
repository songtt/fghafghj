<?php
namespace app\home\validate;

use think\Validate;

class Ads extends Validate
{

    protected $rule = [
        'ads_pid' => 'require',   //计划id
        'ad_name'  =>  'require',  //广告名称
        'adtpl_id' => 'require', //广告模板id
        'url' => 'require', //url地址
    ];

    protected $message = [
        'ads_pid' => '计划必选',   //计划id
        'ad_name'  =>  '广告名称必填',  //广告名称
        'adtpl_id' => '广告类型必选', //广告模板id
        'url' => '广告地址必填', //url地址
    ];

}