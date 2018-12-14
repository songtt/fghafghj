<?php
/* 
 * redis
 *----------------------------------------------------------------
 * @author: wangxz<3528319@qq.com>
 * @date : 2016-8-29 10:25:49
 *----------------------------------------------------------------
 */
namespace org;

class Redis
{

    public function __construct($type='redis') {
        $options['type'] = $type;
        \think\Cache::connect($options);
    }

    public static function set($name='',$val='',$time=null)
    {
        $version = PHP_VERSION;
        \think\Cache::set($name,$val,$time);
        
    }

    public static function get($name='')
    {
        $res = \think\Cache::get($name);
        return $res;
    }

    public static function rm($name='')
    {
        $res = \think\Cache::rm($name);
        return $res;
    }

    public static function has($name)
    {
        $res = \think\Cache::has($name);
        return $res;
    }
}