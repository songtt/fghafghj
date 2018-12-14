<?php
use think\Route;

//api路由地址
//Route::get('getUrl/:id','dsp/api.getUrl/read');
//Route::get('getUrl/:adz_id','api/getUrl/read');
Route::post('getAdsStatus/','api/GetAdsStatus/charge');
Route::resource('getUrl','api/getUrl',['var'=>['getUrl'=>'adz_id'],'only'=>['read'],'except'=>['index','create','delete','edit','update','save']]);

return [
    //根目录
    '/' => 'index/hpage/index',

    //路由未定义情况下
    '__miss__' => 'index/error/index',

    //模块通用
    'admin/:c/:f' => 'admin/:c/:f',
    'home/:c/:f' => 'home/:c/:f',
    'index/:c/:f' => 'index/:c/:f',
    //'api/:c/:f' => 'index/:c/:f',
    'webmaster/:c/:f' => 'webmaster/:c/:f',
    'webgeneral/:c/:f' => 'webgeneral/:c/:f',
    'bigclick/:c/:f' => 'bigclick/:c/:f',
    'tackjs/:c/:f' => 'tackjs/:c/:f',

    // admin
    'manage' => 'admin/index/login',
    'reg' => 'admin/index/reg',

    'siteaudit'=>'admin/siteallaudit/siteAllAudit',
    'serverldpageup'=>'admin/setting/serviceeasy',
    /**
     *  home 板块 
     * */
    'users' => '/',

    //会员发展链接
    'cusnewusers/:uid/:type'=>'index/hpage/register',
    //厂商发展链接
    'busnewusers/:uid/:type'=>'index/hpage/register',
    //web
    'mj' => 'webmaster/index/login',

    'lz' => 'webmaster/webmaster/overduelist',

    /**
     *  index 板块
     * */
    'login' => 'index/hpage/login',
    'register'=>'index/hpage/register',
	'gameregister'=>'index/hpage/gameRegister',
    //网站主
    'sitemaster'=>'index/hpage/sitemaster',
    //广告商
    'advertisers'=>'index/hpage/advertisers',
    //公告
    'notice'=>'index/hpage/notice',
    //公告里面的文章 （暂时这么写）
    'article'=>'index/hpage/article',
    //关于
    'about'=>'index/hpage/about',
    'mailstatus'=>'index/hpage/mailStatus',

    'clickType/type' => 'api/getClickType/checkType',
   /*'getUrl/:id'=>'dsp/api.getUrl/read',
    '__rest__'=>[
        // 指向api模块的getUrl控制器
        'getUrl'=>'api/getUrl',
    ],*/
];