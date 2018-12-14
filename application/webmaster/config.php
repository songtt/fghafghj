<?php


return [
    
    // 默认控制器名
    'default_controller'    => 'Index',
    // 默认操作名
    'default_action'        => 'index',

    'url_route_on' => true,

    // 'app_trace'  => true,

    'view_replace_str'=>[
        '__STATIC__' => '/static',
        '__CSS__' => '/admin/css',
        '__JS__' => '/webmaster/js',
    ],

    //redis
    'redis_flag' => false,

    'file_upload' => './uploads/',
    
];