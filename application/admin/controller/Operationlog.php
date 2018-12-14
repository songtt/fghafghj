<?php
/* 计划管理
 * @date   2016-6-2
 */
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Hook;


class Operationlog extends Admin
{
    /**
     * 操作日志列表
     */
    public function list()
    {
        $request = Request::instance();
//        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        $day = date("Y-m-d");
        if(!isset($pageParam['time'])){
            $pageParam['time'] = '2017-01-01'.$day;
        }
        //处理时间插件
        $time = $this->_getTime($day,$pageParam);
        $time['starttime'] = strtotime($time['startday']);
        $time['endtime'] = strtotime($time['endday']) + 86399;
        if($time['endtime'] == 86399){
            $time['endtime'] = time() + 86399;
        }
        //分页查询操作日志
        $total = Loader::model('Operationlog')->getlistCount($pageParam,$time);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        $data['page'] = $Page->show(Request::instance()->action(),$pageParam);
        $res = Loader::model('Operationlog')->getlist($Page->firstRow,$Page->listRows,$pageParam,$time);
        foreach($res as $key=>$value){
            $res[$key]['time'] = date("Y-m-d H:i:s",$value['time']);
        }
        //将参数传回页面，在页面上保留上次搜索历史
        $pageParam['searchName'] = !isset($pageParam['searchName']) ? '' : $pageParam['searchName'];
        $pageParam['search'] = !isset($pageParam['search']) ? '' : $pageParam['search'];
        $pageParam['time'] = $time;

        $this->assign('one',$res);
        $this->assign('param',$pageParam);
        $this->assign('page',$show);
        return $this->fetch('operation-list');
    }

    /**
     * 处理时间函数
     */
    public function _getTime($day,$parama)
    {
        $parama['day'] = substr($parama['time'], 0,10);
        $parama['day1'] = substr($parama['time'], 10,20);
        //获取所有时间段
        $allday = '2017-01-01'.$day;
        //获取今天日期
        $today = $day.$day;
        //获取昨天日期
        $yesterday = date("Y-m-d",strtotime("-1 day")).date("Y-m-d",strtotime("-1 day"));
        //最近2天
        $lastTwo = date('Y-m-d',strtotime("-1 days")).$day;
        //最近7天
        $lastSeven = date('Y-m-d',strtotime("-6 days")).$day;
        //最近30天
        $lastThirty = date('Y-m-d',strtotime("-29 days")).$day;
        //获取上个月日期
        $timestamp = strtotime($day);
        $firstday = date('Y-m-01',strtotime(date('Y',$timestamp).'-'.(date('m',$timestamp)-1).'-01'));
        $lastday = date('Y-m-d',strtotime("$firstday +1 month -1 day"));
        $lastMonth = $firstday.$lastday;
        $data = array(
            'nowval' => $parama['day'].$parama['day1'],
            'now' => $parama['day']."至".$parama['day1'],
            'allday' => $allday,
            'today' => $today,
            'yesterday' => $yesterday,
            'lastlastTwo' => $lastTwo,
            'lastseven' => $lastSeven,
            'lastthirty' => $lastThirty,
            'lastmonth' => $lastMonth,
            'startday' => $parama['day'],
            'endday' => $parama['day1']
        );
        return $data;
    }
}
