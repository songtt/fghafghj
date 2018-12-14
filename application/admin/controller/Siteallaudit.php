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

class Siteallaudit extends Controller
{
    /**
     * 审核全部网站
     */
    public function siteAllAudit()
    {
        $request = Request::instance();
        $pageParam = $request->param('');
        if(empty($pageParam)){
            $pageParam = array('status' => 'site_all' );
        }
        $total = Loader::model('site')->siteCount($pageParam);
        $arr = $total['count'];
        $Page = new \org\PageUtil($arr,$pageParam);
        $show = $Page->show($request->action());
        $siteList = Loader::model('site')->siteList($Page->firstRow,$Page->listRows,$pageParam);
        $todayViews = Loader::model('site')->siteViews(date("Y-m-d"));
        $yesterdayViews = Loader::model('site')->siteViews(date("Y-m-d",strtotime("-1 day")));
        $siteList = $this->_getViews($siteList,$todayViews,$yesterdayViews);
        $res['title'] = '';
        $this->assign('Administrators',$res);
        $this->assign('one',$pageParam);
        $this->assign('site_list',$siteList);
        $this->assign('page',$show);
        return $this->fetch('site-all-audit');
    }

    /**
     * 修改 网站状态 0 锁定 1 激活
     */
    public function siteEditStatus()
    {
        $params = input('');
        if(!empty($params)){
            $update = Loader::model('site')->siteEditStatus($params['id'],$params['status']);
            if($update>0){
                $this->_success();
            }else{
                $this->_error();
            }
        }
    }

    /**
     * 获取今日访问和昨日访问
     */
    private function _getViews($siteList,$todayViews,$yesterdayViews)
    {
        foreach($siteList as $key=>$value){
            //将今日访问组装到数组中
            foreach($todayViews as $key1=>$value1){
                if($value['site_id'] == $value1['site_id']){
                    $siteList[$key]['todayViews'] = $value1['views'];
                }
            }
            empty($siteList[$key]['todayViews']) ? $siteList[$key]['todayViews']=0 : $siteList[$key]['todayViews'];
            //将昨日访问组装到数组中
            foreach($yesterdayViews as $key1=>$value1){
                if($value['site_id'] == $value1['site_id']){
                    $siteList[$key]['yesterdayViews'] = $value1['views'];
                }
            }
            empty($siteList[$key]['yesterdayViews']) ? $siteList[$key]['yesterdayViews']=0 : $siteList[$key]['yesterdayViews'];
        }
        return $siteList;
    }

    /**
     * ajax成功返回
     * param $data array  数据数组
     * param $info string 成功返回的字符串
     * return json
     */
    protected function _success($datas = array(),$info='success'){
        $data['status']  = 1;
        $data['data'] = $datas;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    /**
     * ajax成功返回
     * param $info string 错误返回的字符串
     * return json
     */
    protected function _error($info='error'){
        $data['status']  = 0;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }
}