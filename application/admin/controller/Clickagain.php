<?php
/* 
 * 网站与广告位管理
 * date   2016-6-2
 */
namespace app\admin\controller;
use think\Controller;
use think\Loader;
use think\Request;
use think\Hook;

class Clickagain extends Controller
{
    /**
     * 接收二次点击数据
     */
    public function clickagain()
    {
        $request = Request::instance();
        $params = $request->param();
        $res = Loader::model('Clickagain')->insertdata($params);
    }
}