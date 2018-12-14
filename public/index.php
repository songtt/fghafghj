<?php
//echo 'aaa';exit;

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 开启调试模式
define('APP_DEBUG', false);

// 加载框架引导文件
require __DIR__ . '/../core/start.php';
