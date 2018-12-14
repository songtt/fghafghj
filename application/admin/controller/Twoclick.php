<?php
/** 二次点击报表
 * date   2017-5-2
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Loader;
use think\Request;
use think\Hook;
use think\config;

class Twoclick extends Admin
{
    /**
     *  二次点击报表列表
     */
    public function twoClick()
    {
        $request = Request::instance();
        // Hook::listen('auth',$this->_uid); //权限
    	$params = $request->param();
        if(!isset($params['twoclick'])){
            $params['twoclick'] = '';
        }
        $total = Loader::model('Stats')->clickCount($params);
        $Page = new \org\PageUtil($total,$params);
        $show = $Page->show(Request::instance()->action(),$params);
        $totalNum = Loader::model('Stats')->clickTotal($params,$Page->firstRow,$Page->listRows);
        $this->assign('data',$totalNum);
        $this->assign('page',$show);
        $this->assign('params',$params);
        return $this->fetch('report/twoclick-report');
    }

}
