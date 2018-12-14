<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/13 0013
 * Time: 下午 5:03
 */

namespace app\common;
class WriteLog
{
    /**
     * @param $file
     * @param $str
     * @return void
     */
    public static function writeFile($file, $str)
    {
        @file_put_contents($file,$str,'a+');
    }
}