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
        '__JS__' => '/admin/js',
    ],

    //redis
    'redis_flag' => false,

    'file_upload' => './uploads/',

    'excel_url'   =>'../extend/org/vendor/',

    //大点击连接182服务器数据库
    'db_182_config'=>[
        // 数据库类型
        'type'        => 'mysql',
        // 数据库连接DSN配置
        'dsn'         => '',

        // 本地地址
        'hostname'    => '127.0.0.1',
        'database'    => 'lezunsys',
        'username'    => 'root',
        'password'    => '123456',

        // 数据库连接端口
        'hostport'    => '3306',
        // 数据库连接参数
        'params'      => [],
        // 数据库编码默认采用utf8
        'charset'     => 'utf8',
        // 数据库表前缀
        'prefix'      => 'lz_',
    ],

];