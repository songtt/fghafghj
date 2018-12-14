<?php

return [

    'url_route_on' => true,

    'url_convert' => true,

    // 默认模块名
    'default_module'        => 'index',
    // 默认控制器名
    'default_controller'    => 'Index',
    // 默认操作名
    'default_action'        => 'index',

    'http_exception_template'    =>  [
        // 定义404错误的重定向页面地址
        404 =>  APP_PATH.'404.html',
        // 还可以定义其它的HTTP status
        401 =>  APP_PATH.'401.html',
    ],


    // 'app_debug' => 'true',

    'default_filter'=> ['htmlspecialchars'],   //全局过滤

    //默认错误跳转对应的模板文件
    'dispatch_error_tmpl' => 'public/error',

    //变量报错页面
   // 'exception_tmpl'         => '404.html',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------
    'log'                    => [
        // 日志记录方式，支持 file socket
        'type' => 'File',
        // 日志保存目录
        'path' => LOG_PATH,
    ],

    // +----------------------------------------------------------------------
    // | Trace设置
    // +----------------------------------------------------------------------

    'trace'                  => [
        //支持Html Console
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------
    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    'db_query_config'=>[

        // 数据库类型
        'type'        => 'mysql',
        // 数据库连接DSN配置
        'dsn'         => '',

        // 本地地址
        'hostname'    => '127.0.0.1',
        'database'    => 'lezunsys',
        'username'    => 'root',
        'password'    => '123456',

        //测试环境
        // 'hostname'    => '127.0.0.1',
        // 'database'    => 'lezun',
        // 'username'    => 'root',
        // 'password'    => 'xya197a3321',
     
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
