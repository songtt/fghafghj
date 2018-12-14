<?php
/** 自定义查询
 * date   2016-7-11
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Loader;
use think\Request;
use think\Hook;
use think\config;
use think\Session;


class Diyreport extends Admin
{
    /**
     * 自定义查询
     */    
    public function diyQuery()
    {
        $request = Request::instance();
        //Hook::listen('auth',$this->_uid);
        $pageParam = $request->param();
        $report = controller('Report');
        if($request->isAjax())
        {
            if((count($pageParam) > 0) || (!empty($pageParam)))
            {
                if((!empty($pageParam['time'])) && ($pageParam['time']=='all'))
                {
                    $params = $this->_getAll($pageParam);      
                }else{
                    $params = $this->_getItem($pageParam); 
                }  
            }
            switch ($params['stats']) 
            {
                case 'plan_list':
                    $data = $this->planReport($request,$params,$pageParam,$report);
                    break;
                case 'user_list':
                    $data = $this->webReport($request,$params,$pageParam,$report);
                    break;
                case 'ads_list':
                    $data = $this->adsReport($request,$params,$pageParam,$report);
                    break;
                case 'zone_list':
                    $data = $this->zoneReport($request,$params,$pageParam,$report);
                    break;
                case 'adv_list':
                    $data = $this->advReport($request,$params,$pageParam,$report);
                    break;
                case 'site_list':
                    $data = $this->siteReport($request,$params,$pageParam,$report);
                    break;
                default:
                    $data = $this->classReport($request,$params,$pageParam,$report);
                    break;
            }
            return $data;
        }else{
            return $this->fetch('diy-query');
        }   
    }


    /**
     * 计划报表
     */
    public function planReport($request,$params,$pageParam,$report)
    {
        //处理时间插件
        $timeValue = $report->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能) 
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->planAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->planAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->planLstCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->planTotal($params);
        }
        //加上扣量后处理数据
        $totalNum = $report->_getnumber($totalNum);
        //计算CRT并且加上独立IP数
        $totalNum = $report->_getCRT($totalNum,$params);
        //排序
        if(strlen($params['sort'])>0 || $params['sort']!=""){
            if($params['sort'] == 'ctime'){
                $totalNum = $report->_dataSort($totalNum,$params['sort'],$sort_order=SORT_ASC );
            }else{
                $totalNum = $report->_dataSort($totalNum,$params['sort']);
            }
        }
        $data['res'] = array_slice($totalNum,$Page->firstRow,$Page->listRows);
        $data['data'] = $report->_dataTotal($totalNum,$params,$timeValue);

        return $data;
        unset($timeValue);unset($totalNum);unset($data);    
    }



    /**
     * 站长报表
     */
    public function webReport($request,$params,$pageParam,$report)
    {
        //处理时间插件
        $timeValue = $report->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->webAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->webAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->webLstCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->webTotal($params);
        }
        //加上扣量后处理数据
        $totalNum = $report-> _getnumber($totalNum);
        //计算CRT并且加上独立IP数
        $totalNum = $report->_getCRT($totalNum,$params);
        //将数据按照同一天下，根据uid的大小来排序
        $totalNum = $report->_dataSort($totalNum,'ctime');
        $webdata = $report->_webSort($totalNum);
        //处理数据，一个站长多广告位，取最大的广告位ip数据
        $arr = $report->_webData($webdata);
        //排序
        if(strlen($params['sort'])>0 || $params['sort']!=""){
            if($params['sort'] == 'ctime'){
                $arr = $report->_dataSort($arr,$params['sort'],$sort_order=SORT_ASC );
            }else{
                $arr = $report->_dataSort($arr,$params['sort']);
            }
        }    
        $arr = empty($arr) ? array() : $arr;
        $data['res'] = array_slice($arr,$Page->firstRow,$Page->listRows);
        $data['data'] = $report->_dataTotal($arr,$params,$timeValue);
        

        //判断当前登录的用户
        $session = Session::get();
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '媒介主管'){
                $data['title'] = '媒介主管';
                return $data;
                unset($timeValue);unset($arr);unset($data);
            }else{
                return $data;
                unset($timeValue);unset($arr);unset($data);
            }
        }
    }


    /**
     * 广告报表
     */
    public function adsReport($request,$params,$pageParam,$report)
    {
        //处理时间插件
        $timeValue = $report->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->adsAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->adsAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->adsLstCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->adsTotal($params);
        }
        //加上扣量后处理数据
        $totalNum = $report-> _getnumber($totalNum);
        //计算CRT并且加上独立IP数
        $totalNum = $report->_getCRT($totalNum,$params);
        //排序
        if(strlen($params['sort'])>0 || $params['sort']!="")
        {
            if($params['sort'] == 'ctime'){
                $totalNum = $report->_dataSort($totalNum,$params['sort'],$sort_order=SORT_ASC );
            }else{
                $totalNum = $report->_dataSort($totalNum,$params['sort']);
            }
        }
        $data['res'] = array_slice($totalNum,$Page->firstRow,$Page->listRows);
        $data['data'] = $report->_dataTotal($totalNum,$params,$timeValue);
        
        return $data;
        unset($timeValue);unset($totalNum);unset($data);
    }



    /**
     * 广告位报表
     */
    public function zoneReport($request,$params,$pageParam,$report)
    {
        //处理时间插件
        $timeValue = $report->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->zoneAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->zoneAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->zoneLstCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->zoneTotal($params);
        }
        //加上扣量后处理数据
        
        $totalNum = $report-> _getnumber($totalNum);
        //计算CRT并且加上独立IP数
        $totalNum = $report->_getCRT($totalNum,$params);
        //排序
        if(strlen($params['sort'])>0 || $params['sort']!="")
        {  
            if($params['sort'] == 'ctime'){
                $totalNum = $report->_dataSort($totalNum,$params['sort'],$sort_order=SORT_ASC );
            }else{
                $totalNum = $report->_dataSort($totalNum,$params['sort']);
            }
        }
        $data['res'] = array_slice($totalNum,$Page->firstRow,$Page->listRows);
        $data['data'] = $report->_dataTotal($totalNum,$params,$timeValue);

        //判断当前登录的用户
        $session = Session::get();
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '媒介主管'){
                $data['title'] = '媒介主管';
                return $data;
                unset($timeValue);unset($arr);unset($data);
            }else{
                return $data;
                unset($timeValue);unset($arr);unset($data);
            }
        }
    }



    /**
     * 广告商报表
     */
    public function advReport($request,$params,$pageParam,$report)
    {
        //处理时间插件
        $timeValue = $report->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->advAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->advAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->advLstCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->advTotal($params);
        }
        //加上扣量后处理数据
        $totalNum = $report-> _getnumber($totalNum);
        //计算CRT并且加上独立IP数
        $totalNum = $report->_getCRT($totalNum,$params);
        //排序
        if(strlen($params['sort'])>0 || $params['sort']!="")
        {
            if($params['sort'] == 'ctime'){
                $totalNum = $report->_dataSort($totalNum,$params['sort'],$sort_order=SORT_ASC );
            }else{
                $totalNum = $report->_dataSort($totalNum,$params['sort']);
            }
        }
        $data['res'] = array_slice($totalNum,$Page->firstRow,$Page->listRows);
        $data['data'] = $report->_dataTotal($totalNum,$params,$timeValue);
       
        return $data;
        unset($timeValue);unset($totalNum);unset($data);
        
    }



    /**
     * 网站报表
     */
    public function siteReport($request,$params,$pageParam,$report)
    {
        //处理时间插件
        $timeValue = $report->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->siteAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->siteAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->siteLstCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->siteTotal($params);
        }
        //加上扣量后处理数据
        $totalNum = $report-> _getnumber($totalNum);
        //计算CRT并且加上独立IP数
        $totalNum = $report->_getCRT($totalNum,$params);
        //排序
        if(strlen($params['sort'])>0 || $params['sort']!="")
        {
            if($params['sort'] == 'ctime'){
                $totalNum = $report->_dataSort($totalNum,$params['sort'],$sort_order=SORT_ASC );
            }else{
                $totalNum = $report->_dataSort($totalNum,$params['sort']);
            }
        }
        $data['res'] = array_slice($totalNum,$Page->firstRow,$Page->listRows);
        $data['data'] = $report->_dataTotal($totalNum,$params,$timeValue);

        //判断当前登录的用户
        $session = Session::get();
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '媒介主管'){
                $data['title'] = '媒介主管';
                return $data;
                unset($timeValue);unset($arr);unset($data);
            }else{
                return $data;
                unset($timeValue);unset($arr);unset($data);
            }
        }   
    }



    /**
     * 网站类型报表
     */
    public function classReport($request,$params,$pageParam,$report)
    {
        //处理时间插件
        $timeValue = $report->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->classAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->classAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->classLstCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->classTotal($params);
        }
        //加上扣量后处理数据
        $totalNum = $report-> _getnumber($totalNum);
        //计算CRT并且加上独立IP数
        $totalNum = $report->_getCRT($totalNum,$params);
        //排序
        if(strlen($params['sort'])>0 || $params['sort']!="")
        {
                if($params['sort'] == 'ctime'){
                $totalNum = $report->_dataSort($totalNum,$params['sort'],$sort_order=SORT_ASC );
            }else{
                $totalNum = $report->_dataSort($totalNum,$params['sort']);
            }
        }
        $data['res'] = array_slice($totalNum,$Page->firstRow,$Page->listRows);
        $data['data'] = $report->_dataTotal($totalNum,$params,$timeValue);

        //判断当前登录的用户
        $session = Session::get();
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '媒介主管'){
                $data['title'] = '媒介主管';
                return $data;
                unset($timeValue);unset($arr);unset($data);
            }else{
                return $data;
                unset($timeValue);unset($arr);unset($data);
            }
        }
    }


    private function _getAll($pageParam)
    {
        $params = array(
                        'stats' => $pageParam['stats'],
                        'time' => 'all',
                        'day' => 'all',
                        'day1' => 'all',
                        'type' => empty($pageParam['type']) ? '' : $pageParam['type'],
                        'id' => empty($pageParam['id']) ? '' : $pageParam['id'],
                        'numid' => empty($pageParam['numid']) ? '': $pageParam['numid'],
                        'sort' => empty($pageParam['sort']) ? '' : $pageParam['sort'],
                    ); 
        return $params;
    }

    private function _getItem($pageParam)
    {
        $params = array(
                        'stats' => $pageParam['stats'],
                        'time' => empty($pageParam['time']) ? '' : $pageParam['time'],
                        'day' => empty($pageParam['begin']) ? '' : $pageParam['begin'],
                        'day1' => empty($pageParam['end']) ? '' : $pageParam['end'],
                        'type' => empty($pageParam['type']) ? '' : $pageParam['type'],
                        'id' => empty($pageParam['id']) ? '' : $pageParam['id'],
                        'numid' => empty($pageParam['numid']) ? '': $pageParam['numid'],
                        'sort' => empty($pageParam['sort']) ? '' : $pageParam['sort'],
                    );  
        return $params;
    }
}    

   