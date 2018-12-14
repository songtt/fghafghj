<?php
/** 数据报表
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


class Report extends Admin
{
    public function _initialize()
    {
        parent::_initialize();
        header("Cache-control: private");
    }



    /**
     * 获取大点击的数据，并且拼接到计划报表中
     */
    private function _getBigClick($params,$totalRes)
    {
        $params['day'] = (substr($params['time'], 0,3) == 'all') ? '1900-01-01' : substr($params['time'], 0,10);
        $params['day1'] = empty(substr($params['time'], 10,20)) ? date('Y-m-d'): substr($params['time'], 10,20);

        //如果查询结果包含今天，则查询出log表今天的数据
        $todaynum = array();
        if($params['day1'] == date('Y-m-d')){
            $todaynum = Loader::model('Stats')->getTodayBigClick($params);
        }
        //查询出今天之前所需的数据,不需要的情况下则不查询
        $num = array();
        if($params['day'] != date("Y-m-d")){
            $num = Loader::model('Stats')->getBigClick($params);
        }
        //合并大点击的数据
        $num = array_merge($todaynum,$num);

        //将大点击的数据合并到计划报表中
        foreach($totalRes as $key => $value){
            foreach($num as $k => $v){
                if(($value['pid'] == $v['pid']) && ($value['day'] == $v['day'])){
                    $totalRes[$key]['bigclick'] = $v['bigclick'];
                }

            }
        }
        return $totalRes;
    }

    /**
     * 计划报表
     */
    public function planReport()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param();
        //判断
        $pageParam['status'] = $_SESSION['think']['status'];
        if(($request->isPost()) || (!empty($pageParam['time'])) || (!empty($pageParam['type'])) || (!empty($pageParam['sort']))){
            //数据报表页面检索后的条件数据
            $params = $this->_getEnd($pageParam);
        }else{
            //数据报表页面准备初期查询的条件数据
            $params = $this->_getPlanFront($pageParam);
        }
        //处理广告位报表二次点击排序后跳转防报错
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' || $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = 'ctime';
        }
        //处理时间插件
        $day = date("Y-m-d");
        $timeValue = $this->_getTime($day,$params);
        $today = $day.$day;
        // $startday = strtotime($timeValue['startday']);
        // $endday = strtotime($timeValue['endday']);
        //处理 adv 搜索 web
        if($params['sort'] =='web_deduction'){
            $params['sort'] = 'adv_deduction';
        }elseif($params['sort'] =='web_num'){
            $params['sort'] = 'adv_num';
        }
        //当查询日期为今天数据时  查询lz_stats_log表
        if($params['time'] == $today){
            $this->getPlanStatsLog($params,$pageParam,$timeValue,$request);
        }else{
            // $resdate = ($endday-$startday)/86400;
            if(empty($params['mobile'])&&empty($params['type'])){
                $this->statsforplandate($params,$pageParam,$timeValue,$request);
            }else{
                //处理时间插件
                //$params['sort'] = $params['sort'] == 'ctr' ? 'ctime' : $params['sort'];
                //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
                if($params['day'] == "all"){
                    //分页功能下查询当前页的数据
                    $total = Loader::model('Stats')->planAllTimeCount($params);
                    $Page = new \org\PageUtil($total,$pageParam);
                    $data['show'] = $Page->show($request->action(),$pageParam);
                    $totalNum = Loader::model('Stats')->planAllTimeTotal($params);
                }else{
                    //分页功能下查询当前页的数据
                    // $total = Loader::model('Stats')->planLstCount($params);
                    // $Page = new \org\PageUtil($total,$pageParam);
                    // $data['show'] = $Page->show($request->action(),$pageParam);
                    $totalNum = Loader::model('Stats')->planTotal($params);
                }
                //加上扣量后处理数据
                $totalNum = $this->_getnumber($totalNum);
                //计算CRT并且加上独立IP数 
                $totalNum = $this->_getCRT($totalNum,$params);
                //当查询条件包含今天数据时   拼接今天的数据
                if($params['day1'] == date("Y-m-d")){
                    $params['day'] = $params['day1'];
                    $todayNum = Loader::model('Stats')->planToStatsLog($params);
                    $todayNum = $this->_getCrtForPlan($todayNum);
                }else{
                    $todayNum = array();
                }
                $totalRes = array_merge($todayNum,$totalNum);
                //处理广告用户下的数据
                $totalRes = $this->_getUnsetPlan($pageParam,$totalRes);
                //排序
                if($params['sort'] == 'ctime'){
                    $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
                }else{
                    $totalRes = $this->_dataSort($totalRes,$params['sort']);
                }
                unset($totalNum);unset($todayNum);
                //合并后分页
                $total = count($totalRes);
                $Page = new \org\PageUtil($total,$pageParam);
                $data['show'] = $Page->show($request->action(),$pageParam);

                //获取大点击统计并且拼接到data数组中
                $totalRes = $this->_getBigClick($params,$totalRes);
                $data['res'] = array_slice($totalRes,$Page->firstRow,$Page->listRows);
                //汇总
                $data['data'] = $this->_dataTotal($totalRes,$params,$timeValue);
                //将查询到的数据传到前台
                $this->assign('data',$data);
                unset($totalRes);unset($timeValue);unset($data);
            }
        }

        $judge_name = $request->session('uname');

        //排重点击数
        $ratio = !empty($pageParam['ratio']) ? $pageParam['ratio']: '';
        //搜索值的类型
        $type = !empty($pageParam['id']) ? $pageParam['id'] : '';
        $this->assign('ratio',$ratio);
        $this->assign('type',$type);
        $this->assign('judge_name',$judge_name);
        //判断当前登录的用户
        $session = Session::get();
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '专用714'){
                return $this->fetch('plan-medreport');
            }else{
                return $this->fetch('plan-report');
            }
        }
    }

    public function statsforplandate($params,$pageParam,$timeValue,$request)
    {
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->planAllTimeCount($params);
            // echo Loader::model('Stats')->getlastsql().'<br>';
            // echo microtime(true).'---a3<br>';
            // echo memory_get_usage(), '<br />';
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->planAllTimeTotal($params);
            // echo Loader::model('Stats')->getlastsql().'<br>';
            // echo microtime(true).'---4<br>';
            // echo memory_get_usage(), '<br />';
        }else{
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->planLstCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->planTotal($params);

        }
        //当查询条件包含今天数据时   拼接今天的数据
        if($params['day1'] == date("Y-m-d")){
            $params['day'] = $params['day1'];
            $todayNum = Loader::model('Stats')->planToStatsLog($params);
            //合并今天数据和之前的数据
            $totalNum = array_merge($totalNum,$todayNum);
        }
        //处理广告用户下的数据
        $totalNum = $this->_getUnsetPlan($pageParam,$totalNum);
        //分页
        $total = count($totalNum);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //每条的
        $totalRes = $this->_getCrtForPlan($totalNum);

        //获取大点击统计并且拼接到data数组中
        $totalRes = $this->_getBigClick($params,$totalRes);
        //汇总的计算CRT并且加上独立IP数
        $totalResCrt = $this->_getLstCRTForPlan($totalRes);
        //数据报表排序优化
        $totalRes = $this -> _viewDaySort($totalRes);
        // dump($totalResCrt);
        // dump($params);
        // exit;

        //排序
        if($params['sort'] == 'ctime'){
            $totalLst = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalLst = $this->_dataSort($totalRes,$params['sort']);
        }
        // dump($totalNum);
        // exit;

        //单条
        $data['res'] = array_slice($totalLst,$Page->firstRow,$Page->listRows);
        //汇总
        $data['data'] = $this->_dataTotalForPlan($totalResCrt,$params,$timeValue);

        unset($totalResCrt);
        unset($totalRes);
        // dump($data);exit;
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($timeValue);unset($totalLst);unset($data);
        // echo memory_get_usage(), '<br />';
    }

    public function getPlanStatsLog($params,$pageParam,$timeValue,$request)
    {
        //分页功能下查询当前页的数据
        $totalNum = Loader::model('Stats')->planToStatsLog($params);
        //处理广告用户下的数据
        $totalNum = $this->_getUnsetPlan($pageParam,$totalNum);
        $total = count($totalNum);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //每条的
        $totalRes = $this->_getCrtForPlanLst($totalNum,$params);
        //获取大点击统计并且拼接到data数组中
        $totalRes = $this->_getBigClick($params,$totalRes);
        //汇总的计算CRT并且加上独立IP数
        $totalResCrt = $this->_getLstCRTForPlan($totalRes);
        //数据报表排序优化
        $totalRes = $this -> _viewDaySort($totalRes);
        //排序
        if($params['sort'] == 'ctime'){
            $totalLst = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalLst = $this->_dataSort($totalRes,$params['sort']);
        }
        //单条
        $data['res'] = array_slice($totalLst,$Page->firstRow,$Page->listRows);
        //汇总
        $data['data'] = $this->_dataTotalForPlan($totalResCrt,$params,$timeValue);
        unset($totalResCrt);unset($totalRes);

        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($timeValue);unset($totalLst);unset($data);
    }

    /**
     * 计划报表导出excel
     */
    public function planExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();

        //数据报表页面检索后的条件数据
        $params = $this->_getEnd($pageParam);

        //处理时间插件
        $timeValue = $this->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            $totalNum = Loader::model('Stats')->planAllTimeTotal($params);
        }else{
            $totalNum = Loader::model('Stats')->planTotal($params);
        }
        //当查询条件包含今天数据时   拼接今天的数据
        if($params['day1'] == date("Y-m-d")){
            $params['day'] = $params['day1'];
            $todayNum = Loader::model('Stats')->planToStatsLog($params);
            //合并今天数据和之前的数据
            $totalNum = array_merge($totalNum,$todayNum);
        }
        //每条的
        $totalRes = $this->_getCrtForPlan($totalNum);
        //汇总的计算CRT并且加上独立IP数
        $totalResCrt = $this->_getLstCRTForPlan($totalNum);
        //排序
        if($params['sort'] == 'ctime'){
            $totalLst = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalLst = $this->_dataSort($totalRes,$params['sort']);
        }

        foreach ($totalLst as $key => $value) {
            switch ($value['run_terminal']) {
                case 0:
                    $totalLst[$key]['run_terminal']='不限';
                    break;
                case 1:
                    $totalLst[$key]['run_terminal']='桌面';
                    break;
                case 2:
                    $totalLst[$key]['run_terminal']='IOS';
                    break;
                case 3:
                    $totalLst[$key]['run_terminal']='Android';
                    break;
                case 4:
                    $totalLst[$key]['run_terminal']='微软WP';
                    break;
                default:
                    $totalLst[$key]['run_terminal']='不限';
                    break;
            }
        }
        $this->excel($totalLst,$params);
    }
    /******************************************站长报表 *******************************************************/
    /**
     * 站长报表
     */
    public function webReport()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param();

        if(($request->isPost()) || (!empty($pageParam['time'])) || (!empty($pageParam['type'])) || (!empty($pageParam['sort']))){
            //数据报表页面检索后的条件数据
            $params = $this->_getEnd($pageParam);
        }else{
            //数据报表页面准备初期查询的条件数据
            $params = $this->_getWebFront($pageParam);
        }
        //处理广告位报表二次点击排序后跳转防报错
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' || $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = 'ctime';
        }
        if($params['sort'] =='adv_deduction'){
            $params['sort'] = 'web_deduction';
        }elseif($params['sort'] =='adv_num'){
            $params['sort'] = 'web_num';
        }
        //处理时间插件
        $day = date("Y-m-d");
        $timeValue = $this->_getTime($day,$params);
        $today = $day.$day;
        if($params['time'] == $today){
            $this->_getWebStatsLog($params,$pageParam,$timeValue,$request);
        }else{
            if(empty($params['numid']) && empty($params['type'])){
                $this->webStats($params,$pageParam,$timeValue,$request);
            }
            else
            {
                if($params['day'] == "all"){
                    //分页功能下查询当前页的数据
                    $totalNum = Loader::model('Stats')->webAllTimeTotal($params);
                    $total = count($totalNum);
                    $Page = new \org\PageUtil($total,$pageParam);
                    $data['show'] = $Page->show($request->action(),$pageParam);
                }else{
                    //分页功能下查询当前页的数据
                    $totalNum = Loader::model('Stats')->webTotal($params);
                    $this->webDisplay($totalNum,$params,$request,$timeValue,$pageParam);
                }
            }
        }
        $ratio = !empty($pageParam['ratio']) ? $pageParam['ratio']: '';
        $type = !empty($pageParam['id']) ? $pageParam['id'] : '';
        $this->assign('ratio',$ratio);
        $this->assign('type',$type);

        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        //判断当前登录的用户
        $session = Session::get();
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '媒介主管' || $res['title'] == '专用714'){
                return $this->fetch('web-medreport');
            }else{
                return $this->fetch('web-report');
            }
        }
    }

    //站长今日的数据 查询stats_log
    public function _getWebStatsLog($params,$pageParam,$timeValue,$request)
    {
        //分页功能下查询当前页的数据
        $totalNum = Loader::model('Stats')->webToStatsLog($params);
        //查询到的数据为站长下的广告位  合并同站长  并计算crt
        $totalRes = $this->_webToMarge($totalNum,$params);
        $total = count($totalRes);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //计算点击成本
        $totalRes = $this -> _clickCost($totalRes);
        //数据报表排序优化
        $totalRes = $this -> _viewDaySort($totalRes);
        //排序
        if($params['sort'] == 'ctime'){
            $totalLst = $this->_dataSortForweb($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalLst = $this->_dataSortForweb($totalRes,$params['sort']);
        }
        //站长跳转数
        $totalLst = $this->_getWebJump($totalLst,$params);
        //单条
        $data['res'] = array_slice($totalLst,$Page->firstRow,$Page->listRows);
        //计算汇总crt
        $data['data'] = $this->_newDateForWeb($totalLst,$params,$timeValue);
        unset($totalRes);
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($timeValue);unset($totalLst);unset($data);
    }

    //今日数据站长报表合并同广告位的站长
    private function _webToMarge($totalNum,$params)
    {
        //合并
        $totalRes = array();
        foreach ($totalNum as $key => $value) {
            $name = $value['uid'].$value['day'];
            $totalRes[$name]['views'] = !isset($totalRes[$name]['views'])?0:$totalRes[$name]['views'];
            $totalRes[$name]['click_num'] = !isset($totalRes[$name]['click_num'])?0:$totalRes[$name]['click_num'];
            $totalRes[$name]['web_deduction'] = !isset($totalRes[$name]['web_deduction'])?0:$totalRes[$name]['web_deduction'];
            $totalRes[$name]['web_num'] = !isset($totalRes[$name]['web_num'])?0:$totalRes[$name]['web_num'];
            $totalRes[$name]['sumadvpay'] = !isset($totalRes[$name]['sumadvpay'])?0:$totalRes[$name]['sumadvpay'];
            $totalRes[$name]['sumprofit'] = !isset($totalRes[$name]['sumprofit'])?0:$totalRes[$name]['sumprofit'];
            $totalRes[$name]['sumpay'] = !isset($totalRes[$name]['sumpay'])?0:$totalRes[$name]['sumpay'];
            $totalRes[$name]['uv_web'] = !isset($totalRes[$name]['uv_web'])?0:$totalRes[$name]['uv_web'];
            $totalRes[$name]['ui_web'] = !isset($totalRes[$name]['ui_web'])?0:$totalRes[$name]['ui_web'];
            $totalRes[$name]['web_click_num'] = !isset($totalRes[$name]['web_click_num'])?0:$totalRes[$name]['web_click_num'];
            $totalRes[$name]['cpd'] = !isset($totalRes[$name]['cpd'])?0:$totalRes[$name]['cpd'];
            $totalRes[$name]['uid'] = $value['uid'];
            $totalRes[$name]['day'] = $value['day'];
            if($totalRes[$name]['views'] < $value['views']){
                $totalRes[$name]['views'] = $value['views'];
            }
            $totalRes[$name]['click_num'] += $value['click_num'];
            $totalRes[$name]['web_deduction'] += $value['web_deduction'];
            $totalRes[$name]['web_num'] += $value['web_num'];
            $totalRes[$name]['sumadvpay'] += $value['sumadvpay'];
            $totalRes[$name]['sumprofit'] += $value['sumprofit'];
            $totalRes[$name]['sumpay'] += $value['sumpay'];
            if($totalRes[$name]['web_click_num'] < $value['web_click_num']){
                $totalRes[$name]['web_click_num'] = $value['web_click_num'];
            }
            if($totalRes[$name]['uv_web'] < $value['uv_web']){
                $totalRes[$name]['uv_web'] = $value['uv_web'];
            }
            if($totalRes[$name]['ui_web'] < $value['ui_web']){
                $totalRes[$name]['ui_web'] = $value['ui_web'];
            }
            $totalRes[$name]['username'] = $value['username'];
            $totalRes[$name]['cpd'] += $value['cpd'];
        }
        unset($totalNum);
        //计算crt
        foreach($totalRes as $key=>$value){
            if(!empty($params['numid']) && $value['web_click_num']>$value['click_num']){
                if(empty($value['click_num'])){
                    $totalRes[$key]['web_click_num'] = 0;
                }else{
                    $totalRes[$key]['web_click_num'] = (floor($value['click_num']/$value['web_click_num']*100)/100)*$value['web_click_num'];
                }
            }
            if(!empty($value['views'])){
                $totalRes[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                $totalRes[$key]['crt'] = round($value['web_num']/$value['views']*100,2);
            }
        }
        return $totalRes;
    }
    //站长报表今日数据汇总
    private function _newDateForWeb($totalRes,$params,$timeValue)
    {
        $mergeWeb = array(
            'views' => 0,
            'click_num' => 0,
            'jump' => 0,
            'uv_web' => 0,
            'ui_web' => 0,
            'web_num' => 0,
            'web_click_num' => 0,
            'sumpay' => 0,
            'sumprofit' => 0,
            'web_deduction' => 0,
            'cpd' => 0,
            'stats' => $params['stats'],
            'numid' => $params['numid'],
            'time' => $params['time'],
            'type' => $params['type'],
            'sort' => $params['sort'],
            'id' => $params['id']);
        foreach ($totalRes as $key => $value) {
            $mergeWeb['views'] += $value['views'];
            $mergeWeb['click_num'] += $value['click_num'];
            $mergeWeb['jump'] += $value['jump'];
            $mergeWeb['uv_web'] += $value['uv_web'];
            $mergeWeb['ui_web'] += $value['ui_web'];
            $mergeWeb['web_num'] += $value['web_num'];
            $mergeWeb['web_click_num'] += $value['web_click_num'];
            $mergeWeb['sumpay'] += $value['sumpay'];
            $mergeWeb['sumprofit'] += $value['sumprofit'];
            $mergeWeb['web_deduction'] += $value['web_deduction'];
            $mergeWeb['cpd'] += $value['cpd'];
        }
        if(!empty($mergeWeb['views'])){
            $mergeWeb['ctr'] = round($mergeWeb['click_num']/$mergeWeb['views']*100,2);
            $mergeWeb['crt'] = round($mergeWeb['web_num']/$mergeWeb['views']*100,2);
            if(empty($mergeWeb['web_click_num'])){
                $mergeWeb['click_cost'] = 0;
            }else{
                $mergeWeb['click_cost'] = round($mergeWeb['sumpay']/$mergeWeb['web_click_num'],4);
            }
        }
        $res = array_merge($timeValue,$mergeWeb);
        return $res;
    }

    //站长报表数据预处理
    public function webStats($params,$pageParam,$timeValue,$request)
    {
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $totalNum = Loader::model('Stats')->webAllTimeTotal($params);
            $total = count($totalNum);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
        }else{
            //分页功能下查询当前页的数据
            $totalNum = Loader::model('Stats')->webTotal($params);
            $this->webDisplay($totalNum,$params,$request,$timeValue,$pageParam);
        }
    }

    //站长报表数据汇总-排序-展示
    public function webDisplay($totalNum,$params,$request,$timeValue,$pageParam)
    {
        $totalRes = $this->_webSum($totalNum,$params);
        $total = count($totalRes);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //计算点击成本
        //注：bug，如果只有今天有数据，那么查询前七天的数据，new表中总数据$totalNum为空，直接返回，错误，故将此代码注释
//        if(empty($totalNum) || count($totalNum)==0){
//            $data['res'] = [];
//            $totalResCrt = $this->_getLstCRTForWeb($totalRes);
//            $data['data'] = $this->_dataTotalForWeb($totalResCrt,$params,$timeValue);
//            $this->assign('data',$data);
//        }else{

            //当查询条件中携带当天数据  组装拼接
            if($params['day1'] == date("Y-m-d")){
                $params['day'] = date("Y-m-d");
                //分页功能下查询当前页的数据
                $todayNum = Loader::model('Stats')->webToStatsLog($params);
                //查询到的数据为站长下的广告位  合并同站长  并计算crt
                $todayRes = $this->_webToMarge($todayNum,$params);
            }else{
                $todayRes =array();
            }
            //拼接后分页
            $totalRes = array_merge($totalRes,$todayRes);
            //数据报表页面排序优化
            $totalRes = $this->_viewDaySort($totalRes);
            $total = count($totalRes);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalRes = $this -> _clickCost($totalRes);

            //排序
            if($params['sort'] == 'ctime'){
                $totalLst = $this->_dataSortForweb($totalRes,$params['sort'],$sort_order=SORT_ASC );
            }else{
                $totalLst = $this->_dataSortForweb($totalRes,$params['sort']);
            }
            //站长跳转数
            $totalLst = $this->_getWebJump($totalLst,$params);
            //单条
            $data['res'] = array_slice($totalLst,$Page->firstRow,$Page->listRows);
            //汇总的计算CRT并且加上独立IP数
            $totalResCrt = $this->_getLstCRTForWeb($totalLst);
            //汇总
            $data['data'] = $this->_dataTotalForWeb($totalResCrt,$params,$timeValue);
            $this->assign('data',$data);
//        }

        unset($totalResCrt);
        unset($totalRes);
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($timeValue);unset($totalLst);unset($data);
    }

    /**
     *  站长报表计算点击成本
     */
    private function _clickCost($res)
    {
        if(!empty($res)){
            foreach ($res as $key => $value) {
                if(empty($value['web_click_num'])){
                    $res[$key]['click_cost'] = 0;
                }else{
                    $res[$key]['click_cost'] = round($value['sumpay']/$value['web_click_num'],4);
                }
            }
        }
        return $res;
    }

    /**
     *  站长报表跳转数计算
     * @param $data
     * @param $params
     * @return
     */
    private function _getWebJump($data, $params)
    {
        //查询跳转数
        $dayStar = (substr($params['time'], 0, 3) == 'all') ? '2017-01-01' : substr($params['time'], 0, 10);
        //当查询条件中携带当天数据  组装拼接
        $todayJump = array();
        if ($params['day1'] == date("Y-m-d")) {
            //分页功能下查询当前页的数据
            $todayJump = Loader::model('Stats')->webJump(date("Y-m-d"), $params, 'lz_stats_log');
        }
        $webJump = Loader::model('Stats')->webJump($dayStar, $params, 'lz_stats_new');
        $webJump = array_merge($todayJump, $webJump);
        //跳转数计算
        $webJumpNum = array();
        foreach ($webJump as $key => $value) {
            $name = $value['uid'] . $value['day'];
            $webJumpNum[$name]['jump'] = !isset($webJumpNum[$name]['jump']) ? 0 : $webJumpNum[$name]['jump'];
            $webJumpNum[$name]['uid'] = $value['uid'];
            $webJumpNum[$name]['day'] = $value['day'];
            $webJumpNum[$name]['jump'] += $value['jump'];
        }
        foreach ($data as $key => $value) {
            $data[$key]['jump'] = isset($webJumpNum[$key]['jump']) ? $webJumpNum[$key]['jump'] : 0;
        }
        return $data;
    }

    /**
     * 站长报表导出excel
     */
    public function webExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();

        //数据报表页面检索后的条件数据
        $params = $this->_getEnd($pageParam);

        //处理时间插件
        $timeValue = $this->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            $totalNum = Loader::model('Stats')->webAllTimeTotal($params);
        }else{
            $totalNum = Loader::model('Stats')->webTotal($params);

        }
        $totalRes = $this->_webSum($totalNum,$params);
        //当查询条件中携带当天数据  组装拼接
        if($params['day1'] == date("Y-m-d")){
            $params['day'] = date("Y-m-d");
            //分页功能下查询当前页的数据
            $todayNum = Loader::model('Stats')->webToStatsLog($params);
            //查询到的数据为站长下的广告位  合并同站长  并计算crt
            $todayRes = $this->_webToMarge($todayNum,$params);
        }else{
            $todayRes =array();
        }
        $totalRes = array_merge($totalRes,$todayRes);
        //站长跳转数
        $totalRes = $this->_getWebJump($totalRes,$params);
        //汇总的计算CRT并且加上独立IP数
        $totalResCrt = $this->_getLstCRTForWeb($totalRes);
        //排序
        if($params['sort'] == 'ctime'){
            $totalLst = $this->_dataSortForweb($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalLst = $this->_dataSortForweb($totalRes,$params['sort']);
        }
        unset($totalRes);
        $this->excel($totalLst,$params);
    }

    /**
     * 广告报表
     */
    public function adsReport()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param();
        $pageParam['status'] = $_SESSION['think']['status'];
        if(($request->isPost()) || (!empty($pageParam['time'])) || (!empty($pageParam['type'])) || (!empty($pageParam['sort']))){
            //数据报表页面检索后的条件数据
            $params = $this->_getEnd($pageParam);
        }else{
            //数据报表页面准备初期查询的条件数据
            $params = $this->_getAdsFront($pageParam);
        }
        //处理广告位报表二次点击排序后跳转防报错
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' || $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = 'ctime';
        }
        //处理 adv 搜索 web
        if($params['sort'] =='web_deduction'){
            $params['sort'] = 'adv_deduction';
        }elseif($params['sort'] =='web_num'){
            $params['sort'] = 'adv_num';
        }
        $day = date("Y-m-d");
        //处理时间插件
        $timeValue = $this->_getTime($day,$params);
        $today = $day.$day;
        if($params['time'] == $today){
            $this->_getAdsStatsLog($params,$pageParam,$timeValue,$request);
        }else{
            if(empty($params['numid'])&&empty($params['type'])){
                $this->statsforAdsdate($params,$pageParam,$timeValue,$request);
            }else{
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
                $totalNum = $this->_getnumber($totalNum);
                //计算CRT并且加上独立IP数
                $totalNum = $this->_getCRT($totalNum,$params);
                //当查询条件包含今天数据时   拼接今天的数据
                if($params['day1'] == date("Y-m-d")){
                    $params['day'] = $params['day1'];
                    $todayNum = Loader::model('Stats')->adsToStatsLog($params);
                    $todayNum = $this->_getCrtForAds($todayNum);
                }else{
                    $todayNum = array();
                }
                //合并
                $totalRes = array_merge($todayNum,$totalNum);
                //数据报表排序优化
                $totalRes = $this -> _viewDaySort($totalRes);
                //处理广告用户下的数据
                $totalRes = $this->_getUnsetPlan($pageParam,$totalRes);
                //排序
                if($params['sort'] == 'ctime'){
                    $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
                }else{
                    $totalRes = $this->_dataSort($totalRes,$params['sort']);
                }
                unset($totalNum);unset($todayNum);
                //合并后分页
                $total = count($totalRes);
                $Page = new \org\PageUtil($total,$pageParam);
                $data['show'] = $Page->show($request->action(),$pageParam);
                $data['res'] = array_slice($totalRes,$Page->firstRow,$Page->listRows);
                $data['data'] = $this->_dataTotal($totalRes,$params,$timeValue);
                //将查询到的数据传到前台
                $this->assign('data',$data);
                unset($timeValue);unset($totalNum);unset($data);
            }
        }
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        //判断当前登录的用户
        $session = Session::get();
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '媒介主管' || $res['title'] == '专用714'){
                return $this->fetch('ads-medreport');
            }else{
                return $this->fetch('ads-report');
            }
        }
    }

    //广告报表今日的数据 查询stats_log
    public function _getAdsStatsLog($params,$pageParam,$timeValue,$request)
    {
        //分页功能下查询当前页的数据
        $totalNum = Loader::model('Stats')->adsToStatsLog($params);
        //处理广告用户下的数据
        $totalNum = $this->_getUnsetPlan($pageParam,$totalNum);
        $total = count($totalNum);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //计算单条crt
        $totalRes = $this->_getCrtForAds($totalNum);
        //数据汇总
        $getSumRes = $this->_getCrtSumForAds($totalRes);
        unset($totalNum);
        //数据报表排序优化
        $totalRes = $this -> _viewDaySort($totalRes);
        //排序
        if($params['sort'] == 'ctime'){
            $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalRes = $this->_dataSort($totalRes,$params['sort']);
        }
        $data['res'] = array_slice($totalRes,$Page->firstRow,$Page->listRows);
        $data['data'] = $this->_dataTotalForAds($getSumRes,$params,$timeValue);
        unset($totalRes);unset($getSumRes);
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($data);
    }

    /**
     *  广告报表搜索条件为空 查询
     */
    public function statsforAdsdate($params,$pageParam,$timeValue,$request)
    {
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->adsAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->adsAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
            $totalNum = Loader::model('Stats')->adsTotal($params);
            $total = count($totalNum);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
        }
        //当查询条件包含今天数据时   拼接今天的数据
        if($params['day1'] == date("Y-m-d")){
            $params['day'] = $params['day1'];
            $todayNum = Loader::model('Stats')->adsToStatsLog($params);
            //合并今天数据和之前的数据
            $totalNum = array_merge($totalNum,$todayNum);
        }
        //处理广告用户下的数据
        $totalNum = $this->_getUnsetPlan($pageParam,$totalNum);
        //分页
        $total = count($totalNum);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //计算单条crt
        $totalRes = $this->_getCrtForAds($totalNum);
        //数据汇总
        $getSumRes = $this->_getCrtSumForAds($totalRes);
        unset($totalNum);
        //数据报表排序优化
        $totalRes = $this -> _viewDaySort($totalRes);
        //排序
        if($params['sort'] == 'ctime'){
            $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalRes = $this->_dataSort($totalRes,$params['sort']);
        }
        $data['res'] = array_slice($totalRes,$Page->firstRow,$Page->listRows);
        $data['data'] = $this->_dataTotalForAds($getSumRes,$params,$timeValue);
        unset($totalRes);unset($getSumRes);
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($data);
    }

    /**
     *  计算单条广告的crt
     */
    public function _getCrtForAds($arr)
    {
        foreach ($arr as $key => $value) {
            if(!empty($value['views'])){
                $arr[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                $arr[$key]['crt'] = round($value['adv_num']/$value['views']*100,2);
                $arr[$key]['cpa'] = round($value['download']/$value['views']*100,2); //cpa下载率
            }else{
                $arr[$key]['ctr'] = 0;
                $arr[$key]['crt'] = 0;
                $arr[$key]['cpa'] = 0;
            }
        }
        return $arr;
    }

    /**
     *  计算广告报表汇总
     */
    public function _getCrtSumForAds($res)
    {
        $mergeAds = array(
            'views' => 0,
            'click_num' =>  0,
            'adv_deduction' => 0,
            'download' => 0,
            'adv_num' =>  0,
            'sumadvpay' => 0,
            'sumprofit' => 0,);
        foreach ($res as $key => $value) {
            $mergeAds['views'] += $value['views'];
            $mergeAds['click_num'] += $value['click_num'];
            $mergeAds['adv_deduction'] += $value['adv_deduction'];
            $mergeAds['download'] += $value['download'];
            $mergeAds['adv_num'] += $value['adv_num'];
            $mergeAds['sumadvpay'] += $value['sumadvpay'];
            $mergeAds['sumprofit'] += $value['sumprofit'];
        }
        return $mergeAds;
    }

    /**
     * 合并数组  并计算汇总后数据的crt
     */
    public function _dataTotalForAds($number,$params,$timeValue)
    {
        $number['stats'] = $params['stats'];
        $number['numid'] = $params['numid'];
        $number['time'] = $params['time'];
        $number['type'] = $params['type'];
        $number['sort'] = $params['sort'];
        $number['id'] = $params['id'];
        if(!empty($number['views'])){
            $number['ctr'] = round($number['click_num']/$number['views']*100,2);
            $number['crt'] = round($number['adv_num']/$number['views']*100,2);
            $number['cpa'] = round($number['download']/$number['views']*100,2); //cpa下载率
        }else{
            $number['ctr'] = 0;
            $number['crt'] = 0;
            $number['cpa'] = 0;
        }
        $res = array_merge($timeValue,$number);
        return $res;
    }

    /**
     * 广告导出 excel 导出
     */
    public function adsExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();
        //数据报表页面检索后的条件数据
        $params = $this->_getEnd($pageParam);

        //处理时间插件
        $timeValue = $this->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            $totalNum = Loader::model('Stats')->adsAllTimeTotal($params);
        }else{
            $totalNum = Loader::model('Stats')->adsTotal($params);
        }
        //当查询条件包含今天数据时   拼接今天的数据
        if($params['day1'] == date("Y-m-d")){
            $params['day'] = $params['day1'];
            $todayNum = Loader::model('Stats')->adsToStatsLog($params);
            //合并今天数据和之前的数据
            $totalNum = array_merge($totalNum,$todayNum);
        }
        //计算单条crt
        $totalRes = $this->_getCrtForAds($totalNum);
        if($params['sort'] == 'ctime'){
            $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalRes = $this->_dataSort($totalRes,$params['sort']);
        }
        $this->excel($totalRes,$params);
    }

    /**
     * 广告位报表
     */
    public function zoneReport()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param();
        if(($request->isPost()) || (!empty($pageParam['time'])) || (!empty($pageParam['type'])) || (!empty($pageParam['sort']))){
            //数据报表页面检索后的条件数据
            $params = $this->_getEnd($pageParam);
        }else{
            //数据报表页面准备初期查询的条件数据
            $params = $this->_getAdzFront($pageParam);
        }
        $day = date("Y-m-d");
        //处理时间插件
        $timeValue = $this->_getTime($day,$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->zoneAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->zoneAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
            $totalNum = Loader::model('Stats')->zoneNTotal($params);
        }
        if($params['day1'] == $day && $params['time'] != $day.$day){
            $params['time'] = $day.$day;
            $todayNum = Loader::model('Stats')->zoneNTotal($params);
            $params['time'] = $params['day'].$params['day1'];
        }else{
            $todayNum = array();
        }
        $totalNum = array_merge($totalNum,$todayNum);
        $total = count($totalNum);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //广告位包天情况下钱数
        $totalNum = $this->_getadzCpd($totalNum);
        //重新组装广告位数据 汇总+ CRT +二次点击
        $toAdzCRT = $this->_getAdzCRT($totalNum,$params);
        //排序 web and adv排序按照扣量和结算数跳
        if($params['sort'] =='adv_deduction'){
            $params['sort'] = 'web_deduction';
        }elseif($params['sort'] =='adv_num'){
            $params['sort'] = 'web_num';
        }
        //数据报表页面排序优化
        $toAdzCRT = $this->_viewDaySort($toAdzCRT);
        //排序
        if($params['sort'] == 'ctime'){
            $totalres = $this->_dataSort($toAdzCRT,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalres = $this->_dataSort($toAdzCRT,$params['sort']);
        }
        unset($totalNum);unset($toAdzCRT);
        //广告位跳转数
        $totalres = $this->_getAdzJump($totalres,$params);
        //分页
        $data['res'] = array_slice($totalres,$Page->firstRow,$Page->listRows);
        //汇总
        $data['data'] = $this->_adzSummary($totalres,$params,$timeValue);
        //将查询到的数据传到前台
        $this->assign('params',$params);
        $this->assign('data',$data);

        $ratio = !empty($pageParam['ratio']) ? $pageParam['ratio']: '';
        $type = !empty($pageParam['id']) ? $pageParam['id'] : '';
        $this->assign('ratio',$ratio);
        $this->assign('type',$type);

        unset($timeValue);unset($totalres);unset($data);
        //判断当前登录的用户
        $session = Session::get();
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '专用714'){
                return $this->fetch('adzone-medreport');
            }else{
                return $this->fetch('adzone-report');
            }
        }
    }

    /***
     *  广告位报表跳转数
     */
    private function _getAdzJump($data, $params)
    {
        //当查询条件中携带当天数据  组装拼接
        $todayJump = array();
        if ($params['day1'] == date("Y-m-d")) {
            //分页功能下查询当前页的数据
            $todayJump = Loader::model('Stats')->adzJump(date("Y-m-d"), $params, 'lz_stats_log');
        }
        $adzJump = Loader::model('Stats')->adzJump($params['day'], $params, 'lz_stats_new');
        $adzJump = array_merge($todayJump, $adzJump);
        //跳转数计算
        $adzJumpNum = array();
        foreach ($adzJump as $key => $value) {
            $name = $value['adz_id'] . $value['day'];
            $adzJumpNum[$name]['jump'] = !isset($adzJumpNum[$name]['jump']) ? 0 : $adzJumpNum[$name]['jump'];
            $adzJumpNum[$name]['adz_id'] = $value['adz_id'];
            $adzJumpNum[$name]['day'] = $value['day'];
            $adzJumpNum[$name]['jump'] += $value['jump'];
        }
        foreach ($data as $key => $value) {
            $data[$key]['jump'] = isset($adzJumpNum[$key]['jump']) ? $adzJumpNum[$key]['jump'] : 0;
        }
        return $data;
    }

    /**
     *  广告位报表汇总
     */
    private function _adzSummary($totalNum,$params,$timeValue)
    {
        //初始化
        $adzSum = array(
            'stats' => $params['stats'],
            'numid' => $params['numid'],
            'time' => $params['time'],
            'type' => $params['type'],
            'sort' => $params['sort'],
            'id' => $params['id'],
            'views' => 0,
            'num' => 0,
            'web_num' => 0,
            'click_num' => 0,
            'jump' => 0,
            'web_deduction' => 0,
            'sumprofit' => 0,
            'sumpay' => 0,
            'sumadvpay' => 0,
            'ui_adzone' => 0,
            'ui_web' => 0,
            'uv_adzone' => 0,
            'adz_click_num' => 0,
            'cpd' => 0,
            'cpd_day' => 0,
            'fact_sumprofit' => 0,
            'click_numA' => 0,
            'click_numB' => 0,
            'click_numC' => 0,
            'click_numD' => 0,
            'click_numE' => 0,
            'click_numF' => 0,
            'ui_numA' => 0,
            'ui_numB' => 0,
            'ui_numC' => 0,
            'ui_numD' => 0,
            'ui_numE' => 0,
            'ui_numF' => 0,
        );
        //执行数据累加
        foreach ($totalNum as $key => $value) {
            $adzSum['views'] += $value['views'];
            $adzSum['num'] += $value['num'];
            $adzSum['web_num'] += $value['web_num'];
            $adzSum['click_num'] += $value['click_num'];
            $adzSum['web_deduction'] += $value['web_deduction'];
            $adzSum['sumprofit'] += $value['sumprofit'];
            $adzSum['sumpay'] += $value['sumpay'];
            $adzSum['sumadvpay'] += $value['sumadvpay'];
            $adzSum['ui_adzone'] += $value['ui_adzone'];
            $adzSum['ui_web'] += $value['ui_web'];
            $adzSum['uv_adzone'] += $value['uv_adzone'];
            $adzSum['adz_click_num'] += $value['adz_click_num'];
            $adzSum['cpd'] += $value['cpd'];
            $adzSum['cpd_day'] = $value['cpd_day'];
            $adzSum['fact_sumprofit'] += $value['fact_sumprofit'];
            $adzSum['jump'] += $value['jump'];
            //二次点击汇总
            $adzSum['click_numA'] += empty($value['click_numA'])? 0 : $value['click_numA'];
            $adzSum['click_numB'] += empty($value['click_numB'])? 0 : $value['click_numB'];
            $adzSum['click_numC'] += empty($value['click_numC'])? 0 : $value['click_numC'];
            $adzSum['click_numD'] += empty($value['click_numD'])? 0 : $value['click_numD'];
            $adzSum['click_numE'] += empty($value['click_numE'])? 0 : $value['click_numE'];
            $adzSum['click_numF'] += empty($value['click_numF'])? 0 : $value['click_numF'];
            $adzSum['ui_numA'] += empty($value['ui_numA'])? 0 : $value['ui_numA'];
            $adzSum['ui_numB'] += empty($value['ui_numB'])? 0 : $value['ui_numB'];
            $adzSum['ui_numC'] += empty($value['ui_numC'])? 0 : $value['ui_numC'];
            $adzSum['ui_numD'] += empty($value['ui_numD'])? 0 : $value['ui_numD'];
            $adzSum['ui_numE'] += empty($value['ui_numE'])? 0 : $value['ui_numE'];
            $adzSum['ui_numF'] += empty($value['ui_numF'])? 0 : $value['ui_numF'];
        }
        //如果汇总数据不为空的情况下计算CRT
        if(!empty($adzSum['views'])){
            $adzSum['ctr'] = round($adzSum['click_num']/$adzSum['views']*100,2);
            $adzSum['crt'] = round($adzSum['web_num']/$adzSum['views']*100,2);
        }else{
            $adzSum['crt'] = 0;
            $adzSum['ctr'] = 0;
        }
        $adzSum = array_merge($adzSum,$timeValue);
        return $adzSum;
    }

    /**
     * 重新组装广告位报表数据 + CRT + 二次点击
     */
    public function _getAdzCRT($res,$params)
    {
        //重新组装数据
        $arr = array();
        foreach ($res as $key => $value) {
            $name = $value['adz_id'].$value['day'];
            $arr[$name]['views'] = $value['views'];
            $arr[$name]['num'] = $value['num'];
            $arr[$name]['web_num'] = $value['web_num'];
            $arr[$name]['click_num'] = $value['click_num'];
            $arr[$name]['web_deduction'] = $value['web_deduction'];
            $arr[$name]['sumprofit'] = $value['sumprofit'];
            $arr[$name]['sumpay'] = $value['sumpay'];
            $arr[$name]['sumadvpay'] = $value['sumadvpay'];
            $arr[$name]['pid'] = $value['pid'];
            $arr[$name]['uid'] = $value['uid'];
            $arr[$name]['ad_id'] = $value['ad_id'];
            $arr[$name]['adz_id'] = $value['adz_id'];
            $arr[$name]['adv_id'] = $value['adv_id'];
            $arr[$name]['site_id'] = $value['site_id'];
            $arr[$name]['day'] = $value['day'];
            $arr[$name]['ui_adzone'] = $value['ui_adzone'];
            $arr[$name]['ui_web'] = $value['ui_web'];
            $arr[$name]['plan_type'] = $value['plan_type'];
            $arr[$name]['uv_adzone'] = $value['uv_adzone'];
            $arr[$name]['adz_click_num'] = $value['adz_click_num'];
            $arr[$name]['width'] = $value['width'];
            $arr[$name]['height'] = $value['height'];
            $arr[$name]['cpd'] = $value['cpd'];
            $arr[$name]['cpd_day'] = $value['cpd_day'];
            $arr[$name]['fact_sumprofit'] = $value['fact_sumprofit'];
            $arr[$name]['sortA'] = empty($value['sortA'])? '' : $value['sortA'];
            $arr[$name]['sortB'] = empty($value['sortB'])? '' : $value['sortB'];
            $arr[$name]['sortC'] = empty($value['sortC'])? '' : $value['sortC'];
            $arr[$name]['sortD'] = empty($value['sortD'])? '' : $value['sortD'];
            $arr[$name]['sortE'] = empty($value['sortE'])? '' : $value['sortE'];
            $arr[$name]['sortF'] = empty($value['sortF'])? '' : $value['sortF'];
            if(empty($value['width']) || empty($value['height'])){
                $arr[$name]['size'] = '';
            }else{
                $arr[$name]['size'] = $value['width'].'*'.$value['height'];
            }
        }

        if(!empty($arr)){
            $arr = $this->_getTwoclick($arr,$params);
        }
        //计算CRT
        foreach($arr as $key=>$value){
            if(!empty($params['numid']) && $value['adz_click_num']>$value['click_num']){
                if(empty($value['click_num'])){
                    $arr[$key]['adz_click_num'] = 0;
                }else{
                    $arr[$key]['adz_click_num'] = (floor($value['click_num']/$value['adz_click_num']*100)/100)*$value['adz_click_num'];
                }
            }
            if(!empty($value['views'])){
                $arr[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                $arr[$key]['crt'] = round($value['web_num']/$value['views']*100,2);
            }else{
                $arr[$key]['crt'] = '0';
                $arr[$key]['ctr'] = '0';
            }
        }
        return $arr;
    }

    /**
     * 广告位包天情况下实际盈利和跑量盈利
     */
    private function _getadzCpd($res)
    {
        foreach($res as $key=>$value){
            //包天情况下
            if(!empty($value['cpd'])){
                $res[$key]['cpd'] = sprintf("%.4f",$value['cpd']);
                //实际盈利
                $res[$key]['fact_sumprofit'] = sprintf("%.4f", $res[$key]['sumadvpay'] - $res[$key]['cpd']);
                //跑量盈利
                $res[$key]['sumprofit'] = sprintf("%.4f", $res[$key]['sumadvpay'] - $res[$key]['sumpay']);
            }else{
                //实际盈利
                $res[$key]['fact_sumprofit'] = sprintf("%.4f", $res[$key]['sumadvpay'] - $res[$key]['sumpay']);
                //跑量盈利
                $res[$key]['sumprofit'] = sprintf("%.4f", $res[$key]['sumadvpay'] - $res[$key]['sumpay']);
            }
        }
        return $res;
    }

    /**
     * 拼装二次点击
     */
    private function _getTwoclick($arr,$params)
    {
        //拼装二次点击
        $two_click = Loader::model('stats')->twoClick($params);
        if(!empty($two_click) ){
            foreach ($two_click as $key => $click_num) {
                $name = $click_num['adz_id'].$click_num['day'];
                if($click_num['event_type'] == 'A'  && !empty($arr[$name])){
                    $arr[$name]['click_numA'] = $click_num['click_num'];
                    $arr[$name]['ui_numA'] = $click_num['ui_num'];
                    $arr[$name]['sortA'] = $arr[$name]['sumpay']/$click_num['ui_num']*1;
                }elseif($click_num['event_type'] == 'B' && !empty($arr[$name])){
                    $arr[$name]['click_numB'] = $click_num['click_num'];
                    $arr[$name]['ui_numB'] = $click_num['ui_num'];
                    $arr[$name]['sortB'] = $arr[$name]['sumpay']/$click_num['ui_num']*1;
                }elseif($click_num['event_type'] == 'C' && !empty($arr[$name])){
                    $arr[$name]['click_numC'] = $click_num['click_num'];
                    $arr[$name]['ui_numC'] = $click_num['ui_num'];
                    $arr[$name]['sortC'] = $arr[$name]['sumpay']/$click_num['ui_num']*1;
                }elseif($click_num['event_type'] == 'D' && !empty($arr[$name])){
                    $arr[$name]['click_numD'] = $click_num['click_num'];
                    $arr[$name]['ui_numD'] = $click_num['ui_num'];
                    $arr[$name]['sortD'] = $arr[$name]['sumpay']/$click_num['ui_num']*1;
                }elseif($click_num['event_type'] == 'E' && !empty($arr[$name])){
                    $arr[$name]['click_numE'] = $click_num['click_num'];
                    $arr[$name]['ui_numE'] = $click_num['ui_num'];
                    $arr[$name]['sortE'] = $arr[$name]['sumpay']/$click_num['ui_num']*1;
                }elseif($click_num['event_type'] == 'F' && !empty($arr[$name])){
                    $arr[$name]['click_numF'] = $click_num['click_num'];
                    $arr[$name]['ui_numF'] = $click_num['ui_num'];
                    $arr[$name]['sortF'] = $arr[$name]['sumpay']/$click_num['ui_num']*1;
                }
            }
        }
        return $arr;
    }

    /**
     * 广告位导出 excel 导出
     */
    public function zoneExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();
        $params = $this->_getEnd($pageParam);

        $day = date("Y-m-d");
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->zoneAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->zoneAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
            $totalNum = Loader::model('Stats')->zoneNTotal($params);
        }
        if($params['day1'] == $day && $params['time'] != $day.$day){
            $params['time'] = $day.$day;
            $todayNum = Loader::model('Stats')->zoneNTotal($params);
            $params['time'] = $params['day'].$params['day1'];
        }else{
            $todayNum = array();
        }
        $totalNum = array_merge($totalNum,$todayNum);
        //广告位包天情况下钱数
        $totalNum = $this->_getadzCpd($totalNum);
        //重新组装广告位数据 汇总+ CRT +二次点击
        $toAdzCRT = $this->_getAdzCRT($totalNum,$params);
        //广告位跳转数
        $toAdzCRT = $this->_getAdzJump($toAdzCRT,$params);
        //排序
        if($params['sort'] == 'ctime'){
            $totalres = $this->_dataSort($toAdzCRT,$params['sort'],$sort_order=SORT_DESC );
        }else{
            $totalres = $this->_dataSort($toAdzCRT,$params['sort']);
        }
        $this->excel($totalres,$params);
    }

    /**
     * 广告位 授访域名 top 10 导出 excel 导出
     */
    public function zoneSiteExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();
        $params = $this->_siteGetEnd($pageParam);

        //处理时间插件
        $timeValue = $this->_siteGetTime(date("Y-m-d"),$params);

        if($params['day'] == "all"){
            $totalNum = Loader::model('Stats')->zoneAllTimeTotal($params);
        }else{
            $totalNum = Loader::model('Stats')->zoneNTotal($params);
        }

        //广告位包天情况下钱数
        $totalNum = $this-> _getadzCpd($totalNum);
        //重新组装广告位数据 汇总+ CRT +二次点击
        $toAdzCRT = $this->_getAdzCRT($totalNum,$params);

        //排序
        if($params['sort'] == 'ctime'){
            $totalres = $this->_dataSort($toAdzCRT,$params['sort'],$sort_order=SORT_DESC );
        }else{
            $totalres = $this->_dataSort($toAdzCRT,$params['sort']);
        }

        $siteStats = array();
        foreach($totalres as $key => $value){

            $siteStats[] = Loader::model('Stats')->zoneDomain($value);

        }

        $this->domainexcel($siteStats,$params);
    }

    /**
     * 广告商报表
     */
    public function advReport()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param();
        $pageParam['status'] = $_SESSION['think']['status'];
        if(($request->isPost()) || (!empty($pageParam['time'])) || (!empty($pageParam['type'])) || (!empty($pageParam['sort']))){
            //数据报表页面检索后的条件数据
            $params = $this->_getEnd($pageParam);
        }else{
            //数据报表页面准备初期查询的条件数据
            $params = $this->_getAdvFront($pageParam);
        }
        //处理 adv 搜索 web
        if($params['sort'] =='web_deduction'){
            $params['sort'] = 'adv_deduction';
        }elseif($params['sort'] =='web_num'){
            $params['sort'] = 'adv_num';
        }
        //处理广告位报表二次点击排序后跳转防报错
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' || $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = 'ctime';
        }
        $day = date("Y-m-d");
        $today = $day.$day;
        //处理时间插件
        $timeValue = $this->_getTime($day,$params);
        if($params['time'] == $today){
            $this->_getAdvStatsLog($params,$pageParam,$timeValue,$request);
        }else{
            if(empty($params['mobile'])&&empty($params['type'])){

                $this->statsforadvdate($params,$pageParam,$timeValue,$request);

            }else{
                //处理时间插件
                //$params['sort'] = $params['sort'] == 'ctr' ? 'ctime' : $params['sort'];
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
                //处理广告用户下的数据
                $totalNum = $this->_getUnsetPlan($pageParam,$totalNum);
                //加上扣量后处理数据
                $totalNum = $this->_getnumber($totalNum);
                //计算CRT并且加上独立IP数
                $totalNum = $this->_getCRT($totalNum,$params);
                //排序
                if($params['sort'] == 'ctime'){
                    $totalNum = $this->_dataSort($totalNum,$params['sort'],$sort_order=SORT_ASC );
                }else{
                    $totalNum = $this->_dataSort($totalNum,$params['sort']);
                }
                $data['res'] = array_slice($totalNum,$Page->firstRow,$Page->listRows);
                $data['data'] = $this->_dataTotal($totalNum,$params,$timeValue);
                //将查询到的数据传到前台
                $this->assign('data',$data);
                unset($timeValue);unset($totalNum);unset($data);
            }
        }
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        //判断当前登录的用户
        $session = Session::get();
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '专用714'){
                return $this->fetch('adv-medreport');
            }else{
                return $this->fetch('adv-report');
            }
        }
    }

    //广告商报表今日的数据 查询stats_log
    public function _getAdvStatsLog($params,$pageParam,$timeValue,$request)
    {
        //分页功能下查询当前页的数据
        $totalNum = Loader::model('Stats')->advToStatsLog($params);
        //处理广告用户下的数据
        $totalNum = $this->_getUnsetPlan($pageParam,$totalNum);
        $total = count($totalNum);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //计算单条crt
        $totalRes = $this->_getCrtForAdv($totalNum);  //广告和广告商合用
        //数据汇总
        $getSumRes = $this->_getCrtSumForAds($totalRes);//广告和广告商合用
        unset($totalNum);
        //排序
        if($params['sort'] == 'ctime'){
            $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalRes = $this->_dataSort($totalRes,$params['sort']);
        }
        $data['res'] = array_slice($totalRes,$Page->firstRow,$Page->listRows);
        $data['data'] = $this->_dataTotalForAds($getSumRes,$params,$timeValue);//广告和广告商合用
        unset($totalRes);unset($getSumRes);
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($data);
    }

    /**
     * 广告商报表默认数据
     **/
    public function statsforadvdate($params,$pageParam,$timeValue,$request)
    {
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $total = Loader::model('Stats')->advAllTimeCount($params);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Stats')->advAllTimeTotal($params);
        }else{
            //分页功能下查询当前页的数据
//            $total = Loader::model('Stats')->advLstCount($params);
            $totalNum = Loader::model('Stats')->advTotal($params);
            $total = count($totalNum);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
        }
        //当查询条件包含今天数据时   拼接今天的数据
        if($params['day1'] == date("Y-m-d")){
            $params['day'] = $params['day1'];
            $todayNum = Loader::model('Stats')->advToStatsLog($params);
            //合并今天数据和之前的数据
            $totalNum = array_merge($totalNum,$todayNum);

        }
        //处理广告用户下的数据
        $totalNum = $this->_getUnsetPlan($pageParam,$totalNum);
        //分页
        $total = count($totalNum);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //每条的
        $totalRes = $this->_getCrtForAdv($totalNum);

        //汇总的计算CRT并且加上独立IP数
        $totalResCrt = $this->_getLstCRTForAdv($totalNum);
        //数据报表排序优化
        $totalRes = $this -> _viewDaySort($totalRes);
        //排序
        if($params['sort'] == 'ctime'){
            $totalLst = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalLst = $this->_dataSort($totalRes,$params['sort']);
        }

        //单条
        $data['res'] = array_slice($totalLst,$Page->firstRow,$Page->listRows);
        //汇总
        $data['data'] = $this->_dataTotalForAdv($totalResCrt,$params,$timeValue);

        unset($totalResCrt);
        unset($totalRes);
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($timeValue);unset($totalLst);unset($data);
    }
    /**
     * 处理广告账号下数据1438
     */
    public function _getUnsetPlan($pageParam,$totalRes)
    {
        if($pageParam['status']==7){
            foreach($totalRes as $key => $value){
                if($value['adv_id'] != 1021 && $value['adv_id'] != 6358 && $value['adv_id'] != 6379){
                    unset($totalRes[$key]);
                }
            }
        }
        return $totalRes;
    }

    /**
     * 广告商导出 excel 导出
     */
    public function advExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();
        $params = $this->_getEnd($pageParam);

        //处理时间插件
        $timeValue = $this->_getTime(date("Y-m-d"),$params);

        if($params['day'] == "all"){
            $totalNum = Loader::model('Stats')->advAllTimeTotal($params);
        }else{
            $totalNum = Loader::model('Stats')->advTotal($params);
        }
        //当查询条件包含今天数据时   拼接今天的数据
        if($params['day1'] == date("Y-m-d")){
            $params['day'] = $params['day1'];
            $todayNum = Loader::model('Stats')->advToStatsLog($params);
            //合并今天数据和之前的数据
            $totalNum = array_merge($totalNum,$todayNum);

        }
        //每条的
        $totalRes = $this->_getCrtForAdv($totalNum);

        //汇总的计算CRT并且加上独立IP数
        // $totalResCrt = $this->_getLstCRTForAdv($totalNum);
        //排序
        if($params['sort'] == 'ctime'){
            $totalNum = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalNum = $this->_dataSort($totalRes,$params['sort']);
        }
        $res = $totalNum;
        $this->excel($res,$params);
    }

    /**
     * 网站报表
     */
    public function siteReport()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param();
        if(($request->isPost()) || (!empty($pageParam['time'])) || (!empty($pageParam['type'])) || (!empty($pageParam['sort']))){
            //数据报表页面检索后的条件数据
            $params = $this->_getEnd($pageParam);
        }else{
            //数据报表页面准备初期查询的条件数据
            $params = $this->_getSiteFront($pageParam);
        }
        //处理广告位报表二次点击排序后跳转防报错
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' || $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = 'ctime';
        }
        //处理 adv 搜索 web
        if($params['sort'] =='adv_deduction'){
            $params['sort'] = 'web_deduction';
        }elseif($params['sort'] =='adv_num'){
            $params['sort'] = 'web_num';
        }
        $day = date("Y-m-d");
        $today = $day.$day;
        //处理时间插件
        $timeValue = $this->_getTime($day,$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['time'] == $today){
            $this->_getSiteStatsLog($params,$pageParam,$timeValue,$request);
        }else{
            if($params['day'] == "all"){
                //分页功能下查询当前页的数据
                $total = Loader::model('Stats')->siteAllTimeCount($params);
                $Page = new \org\PageUtil($total,$pageParam);
                $data['show'] = $Page->show($request->action(),$pageParam);
                $totalNum = Loader::model('Stats')->siteAllTimeTotal($params);
            }else{
                //分页功能下查询当前页的数据
                // $total = Loader::model('Stats')->siteLstCount($params);
                // $Page = new \org\PageUtil($total,$pageParam);
                // $data['show'] = $Page->show($request->action(),$pageParam);
                $totalNum = Loader::model('Stats')->siteTotal($params);
            }
            //加上扣量后处理数据
            $totalNum = $this->_getnumber($totalNum);
            //计算CRT并且加上独立IP数
            $totalNum = $this->_getCRT($totalNum,$params);
            //页面排序优化
            $totalNum = $this->_viewDaySort($totalNum);
            //当查询条件包含今天数据时   拼接今天的数据
            if($params['day1'] == date("Y-m-d")){
                $params['day'] = $params['day1'];
                $todayNum = Loader::model('Stats')->siteToStatsLog($params);
                $todayNum = $this->_getCrtForSite($todayNum);
            }else{
                $todayNum = array();
            }
            $totalRes = array_merge($todayNum,$totalNum);
            unset($totalNum);unset($todayNum);
            //排序
            if($params['sort'] == 'ctime'){
                $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
            }else{
                $totalRes = $this->_dataSort($totalRes,$params['sort']);
            }
            //分页
            $total = count($totalRes);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $data['res'] = array_slice($totalRes,$Page->firstRow,$Page->listRows);
            $data['data'] = $this->_dataTotal($totalRes,$params,$timeValue);
            //将查询到的数据传到前台
            $this->assign('data',$data);
            unset($timeValue);unset($totalNum);unset($data);
        }
        //判断当前登录的用户
        $session = Session::get();
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '媒介主管' || $res['title'] == '专用714'){
                return $this->fetch('site-medreport');
            }else{
                return $this->fetch('site-report');
            }
        }
    }

    //网站报表今日的数据 查询stats_log
    public function _getSiteStatsLog($params,$pageParam,$timeValue,$request)
    {
        //分页功能下查询当前页的数据
        $totalNum = Loader::model('Stats')->siteToStatsLog($params);
        $total = count($totalNum);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //计算单条crt
        $totalRes = $this->_getCrtForSite($totalNum);
        unset($totalNum);
        //排序
        if($params['sort'] == 'ctime'){
            $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalRes = $this->_dataSort($totalRes,$params['sort']);
        }
        $data['res'] = array_slice($totalRes,$Page->firstRow,$Page->listRows);
        $data['data'] = $this->_dataForSite($totalRes,$params,$timeValue);  //与站长合用计算汇总  和汇总crt
        unset($totalRes);unset($getSumRes);
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($data);
    }

    /**
     * 网站导出 excel 导出
     */
    public function siteExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();
        $params = $this->_getEnd($pageParam);

        //处理时间插件
        $timeValue = $this->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            $totalNum = Loader::model('Stats')->siteAllTimeTotal($params);
        }else{
            $totalNum = Loader::model('Stats')->siteTotal($params);
        }
        //当查询条件包含今天数据时   拼接今天的数据
        if($params['day1'] == date("Y-m-d")){
            $params['day'] = $params['day1'];
            $todayNum = Loader::model('Stats')->siteToStatsLog($params);
            $todayNum = $this->_getCrtForSite($todayNum);
        }else{
            $todayNum = array();
        }
        $totalNum = array_merge($todayNum,$totalNum);
        //计算单条crt
        $totalNum = $this->_getCrtForSite($totalNum);
        //排序
        if($params['sort'] == 'ctime'){
            $totalNum = $this->_dataSort($totalNum,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalNum = $this->_dataSort($totalNum,$params['sort']);
        }
        $res = $totalNum;
        $this->excel($res,$params);
    }


    /**
     * 网站类型报表
     */
    public function classReport()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param();
        if(($request->isPost()) || (!empty($pageParam['time'])) || (!empty($pageParam['type'])) || (!empty($pageParam['sort']))){
            //数据报表页面检索后的条件数据
            $params = $this->_getEnd($pageParam);
        }else{
            //数据报表页面准备初期查询的条件数据
            $params = $this->_getClaFront($pageParam);
        }
        //处理广告位报表二次点击排序后跳转防报错
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' || $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = 'ctime';
        }
        $day = date("Y-m-d");
        $today = $day.$day;
        //处理时间插件
        $timeValue = $this->_getTime($day,$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['time'] == $today){
            $this->_getClasStatsLog($params,$pageParam,$timeValue,$request);
        }else{
            if($params['day'] == "all"){
                //分页功能下查询当前页的数据
                $total = Loader::model('Stats')->classAllTimeCount($params);
                $Page = new \org\PageUtil($total,$pageParam);
                $data['show'] = $Page->show($request->action(),$pageParam);
                $totalNum = Loader::model('Stats')->classAllTimeTotal($params);
            }else{
                //分页功能下查询当前页的数据
                // $total = Loader::model('Stats')->classLstCount($params);
                // $Page = new \org\PageUtil($total,$pageParam);
                // $data['show'] = $Page->show($request->action(),$pageParam);
                $totalNum = Loader::model('Stats')->classTotal($params);
            }
            //加上扣量后处理数据
            $totalNum = $this->_getnumber($totalNum);
            //计算CRT并且加上独立IP数
            $totalNum = $this->_getCRT($totalNum,$params);
            //当查询条件包含今天数据时   拼接今天的数据
            if($params['day1'] == date("Y-m-d")){
                $params['day'] = $params['day1'];
                $todayNum = Loader::model('Stats')->classToStatsLog($params);
                $todayNum = $this->_getCrtForSiteClass($todayNum);
            }else{
                $todayNum = array();
            }
            //数据报表页面排序优化
            $totalNum = $this->_viewDaySort($totalNum);
            $totalRes = array_merge($todayNum,$totalNum);
            unset($totalNum);unset($todayNum);
            //排序
            if($params['sort'] == 'ctime'){
                $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
            }else{
                $totalRes = $this->_dataSort($totalRes,$params['sort']);
            }
            //分页
            $total = count($totalRes);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $data['res'] = array_slice($totalRes,$Page->firstRow,$Page->listRows);
            $data['data'] = $this->_dataTotal($totalRes,$params,$timeValue);
            //将查询到的数据传到前台
            $this->assign('data',$data);
            unset($timeValue);unset($totalNum);unset($data);
        }
        //判断当前登录的用户
        $session = Session::get();
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '媒介主管' || $res['title'] == '专用714'){
                return $this->fetch('classes-medreport');
            }else{
                return $this->fetch('classes-report');
            }
        }
    }

    //网站分类报表今日的数据 查询stats_log
    public function _getClasStatsLog($params,$pageParam,$timeValue,$request)
    {
        //分页功能下查询当前页的数据
        $totalNum = Loader::model('Stats')->classToStatsLog($params);

        //计算单条crt
        $totalRes = $this->_getCrtForSiteClass($totalNum);
        $total = count($totalRes);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        unset($totalNum);
        //排序
        if($params['sort'] == 'ctime'){
            $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalRes = $this->_dataSort($totalRes,$params['sort']);
        }
        $data['res'] = array_slice($totalRes,$Page->firstRow,$Page->listRows);
        $data['data'] = $this->_dataForSite($totalRes,$params,$timeValue);  //与站长合用计算汇总  和汇总crt
        unset($totalRes);unset($getSumRes);
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($data);
    }

    /**
     * 网站导出 excel 导出
     */
    public function classExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();
        $params = $this->_getEnd($pageParam);

        //处理时间插件
        $timeValue = $this->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            $totalNum = Loader::model('Stats')->classAllTimeTotal($params);
        }else{
            $totalNum = Loader::model('Stats')->classTotal($params);
        }
        //当查询条件包含今天数据时   拼接今天的数据
        if($params['day1'] == date("Y-m-d")){
            $params['day'] = $params['day1'];
            $todayNum = Loader::model('Stats')->classToStatsLog($params);
            $todayNum = $this->_getCrtForSiteClass($todayNum);
        }else{
            $todayNum = array();
        }
        $totalNum = array_merge($todayNum,$totalNum);
        //计算单条crt
        $totalNum = $this->_getCrtForSiteClass($totalNum);
        //排序
        if($params['sort'] == 'ctime'){
            $totalNum = $this->_dataSort($totalNum,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalNum = $this->_dataSort($totalNum,$params['sort']);
        }
        $res = $totalNum;
        $this->excel($res,$params);
    }

    /**
     * 实时IP页面显示
     */
    public function iplist()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param();
        if(($request->isPost()) || (!empty($pageParam['ipname']))){
            //实时IP页面检索后的条件数据
            $params = $this->_getIpEnd($pageParam);
        }else{
            //IP报表页面准备初期查询的条件数据
            $params = $this->_getIpFront($pageParam);
        }
        //处理时间插件
        $timeValue = $this->_getTime(date("Y-m-d"),$params);

        //实时IP检索条件数据的拼接
        $data['timeValue'] = $this->_ipNum($timeValue,$params);

        $data['page'] = Loader::model('Realtimeip')->ipCount();
        if($params['day'] == "all"){
            //分页功能下查询当前页的数据
            $Page = new \org\PageUtil(9999999999,$pageParam);
            $data['show'] = $Page->showMax($request->action(),$pageParam);
            $data['res'] = Loader::model('Realtimeip')->ipAllLst($Page->firstRow,$Page->listRows,$params);
        }else{
            //分页功能下查询当前页的数据
            $Page = new \org\PageUtil(9999999999,$pageParam);
            $data['show'] = $Page->showMax($request->action(),$pageParam);
            $data['res'] = Loader::model('Realtimeip')->ipLst($Page->firstRow,$Page->listRows,$params);
        }
        if(empty($data['res'])){
            $data['show'] = '';
        }
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($data);
        return $this->fetch('realtime-ip');
    }

    /**
     * 批量删除数据报表
     */
    public function delReport()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        if(!isset($params['id'])){
            $this->redirect($_SERVER['HTTP_REFERER']);
        }

        foreach($params['id'] as $id){
            $id = explode(".",$id);
            $res = Loader::model('Stats')->delOne($id);
            //写操作日志
            $this->logWrite('0041');
        }
        $url = explode('?',$_SERVER['HTTP_REFERER']);;
        $url = $url[0].'?stats='.$params['stats'].'&numid='.$params['numid'].'&id='.$params['number']
            .'&sort='.$params['sort'].'&time='.$params['time'];
        if($res>0){
            $this->redirect($url);
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 批量删除实时IP报表
     */
    public function delIp()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        if(!isset($params['id'])){
            $this->redirect('Report/iplist');
        }
        $ids = implode(',', $params['id']);
        $res = Loader::model('Realtimeip')->delIp($ids);
        if($res>0){
            //写操作日志
            $this->logWrite('0042');
            $this->redirect('Report/iplist');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 更改广告下载数
     */
    public function changeDownload()
    {
//        Hook::listen('auth',$this->_uid,'plan-changePrice'); //权限
        $params = Request::instance()->post();
        $res = Loader::model('Stats')->updateDownload($params);
        if($res>=0){
            //写操作日志
            $this->logWrite('0043',$params['day'],$params['ad_id'],$params['money']);
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 数据报表页面准备初期查询的条件数据(直接点击和从站长管理跳转两种情况)
     */
    private function _getPlanFront($pageParam)
    {
        $params = array(
            'stats' => empty($pageParam['stats'])?'plan_list':$pageParam['stats'],
            'time' => date("Y-m-d").date("Y-m-d"),
            'day' => date("Y-m-d"),
            'day1' => date("Y-m-d"),
            'type' => '',
            'id' => empty($pageParam['id'])?'pid':$pageParam['id'],
            'numid' => empty($pageParam['numid'])?'':$pageParam['numid'],
            'mobile' => empty($pageParam['mobile'])?'':$pageParam['mobile'],
            'sort' => 'ctime',
        );
        return $params;
    }

    /**
     * 数据报表页面准备初期查询的条件数据(直接点击和从站长管理跳转两种情况)
     */
    private function _getWebFront($pageParam)
    {
        $params = array(
            'stats' => empty($pageParam['stats'])?'user_list':$pageParam['stats'],
            'time' => date("Y-m-d").date("Y-m-d"),
            'day' => date("Y-m-d"),
            'day1' => date("Y-m-d"),
            'type' => '',
            'id' => empty($pageParam['id'])?'uid':$pageParam['id'],
            'numid' => empty($pageParam['numid'])?'':$pageParam['numid'],
            'sort' => 'ctime',
        );
        return $params;
    }

    /**
     * 数据报表页面准备初期查询的条件数据(直接点击和从站长管理跳转两种情况)
     */
    private function _getAdsFront($pageParam)
    {
        $params = array(
            'stats' => empty($pageParam['stats'])?'ads_list':$pageParam['stats'],
            'time' => date("Y-m-d").date("Y-m-d"),
            'day' => date("Y-m-d"),
            'day1' => date("Y-m-d"),
            'type' => '',
            'id' => empty($pageParam['id'])?'ad_id':$pageParam['id'],
            'numid' => empty($pageParam['numid'])?'':$pageParam['numid'],
            'sort' => 'ctime',
        );
        return $params;
    }

    /**
     * 数据报表页面准备初期查询的条件数据(直接点击和从站长管理跳转两种情况)
     */
    private function _getAdzFront($pageParam)
    {
        $params = array(
            'stats' => empty($pageParam['stats'])?'zone_list':$pageParam['stats'],
            'time' => date("Y-m-d").date("Y-m-d"),
            'day' => date("Y-m-d"),
            'day1' => date("Y-m-d"),
            'type' => '',
            'id' => empty($pageParam['id'])?'adz_id':$pageParam['id'],
            'numid' => empty($pageParam['numid'])?'':$pageParam['numid'],
            'sort' => 'ctime',
        );
        return $params;
    }

    /**
     * 数据报表页面准备初期查询的条件数据(直接点击和从站长管理跳转两种情况)
     */
    private function _getAdvFront($pageParam)
    {
        $params = array(
            'stats' => empty($pageParam['stats'])?'adv_list':$pageParam['stats'],
            'time' => date("Y-m-d").date("Y-m-d"),
            'day' => date("Y-m-d"),
            'day1' => date("Y-m-d"),
            'type' => '',
            'id' => empty($pageParam['id'])?'adv_id':$pageParam['id'],
            'numid' => empty($pageParam['numid'])?'':$pageParam['numid'],
            'sort' => 'ctime',
        );
        return $params;
    }

    /**
     * 数据报表页面准备初期查询的条件数据(直接点击和从站长管理跳转两种情况)
     */
    private function _getSiteFront($pageParam)
    {
        $params = array(
            'stats' => empty($pageParam['stats'])?'site_list':$pageParam['stats'],
            'time' => date("Y-m-d").date("Y-m-d"),
            'day' => date("Y-m-d"),
            'day1' => date("Y-m-d"),
            'type' => '',
            'id' => empty($pageParam['id'])?'site_id':$pageParam['id'],
            'numid' => empty($pageParam['numid'])?'':$pageParam['numid'],
            'sort' => 'ctime',
        );
        return $params;
    }

    /**
     * 数据报表页面准备初期查询的条件数据(直接点击和从站长管理跳转两种情况)
     */
    private function _getClaFront($pageParam)
    {
        $params = array(
            'stats' => empty($pageParam['stats'])?'classes_list':$pageParam['stats'],
            'time' => date("Y-m-d").date("Y-m-d"),
            'day' => date("Y-m-d"),
            'day1' => date("Y-m-d"),
            'type' => '',
            'id' => empty($pageParam['id'])?'class_name':$pageParam['id'],
            'numid' => empty($pageParam['numid'])?'':$pageParam['numid'],
            'sort' => 'ctime',
        );
        return $params;
    }

    /**
     * 数据报表页面准备检索后的条件数据
     */
    private function _getEnd($pageParam)
    {
        $params = array(
            'stats' => $pageParam['stats'],
            'time' => $pageParam['time'],
            'day' => (substr($pageParam['time'], 0,3) == 'all') ? '2017-01-01' : substr($pageParam['time'], 0,10),
            'day1' => empty(substr($pageParam['time'], 10,20)) ? date("Y-m-d"): substr($pageParam['time'], 10,20),
            'type' => empty($pageParam['type']) ? '' : $pageParam['type'],
            'id' => $pageParam['id'],
            'numid' => empty($pageParam['numid']) ? '': $pageParam['numid'],
            'sort' => $pageParam['sort'],
            'mobile' => empty($pageParam['mobile']) ? '' : $pageParam['mobile'],
            'heavy_click_num' => empty($pageParam['heavy_click_num']) ? '': $pageParam['heavy_click_num'],
        );
        return $params;
    }

    /**
     * 广告位授访域名  top 10 数据报表页面准备检索后的条件数据
     */
    private function _siteGetEnd($pageParam)
    {
        $params = array(
            'stats' => $pageParam['stats'],
            'time' => $pageParam['time'],
            'day' => (substr($pageParam['time'], 0,3) == 'all') ? 'all' : substr($pageParam['time'], 0,10),
            'day1' => empty(substr($pageParam['time'], 10,20)) ? 'all': substr($pageParam['time'], 10,20),
            'type' => empty($pageParam['type']) ? '' : $pageParam['type'],
            'id' => $pageParam['id'],
            'numid' => empty($pageParam['numid']) ? '': $pageParam['numid'],
            'sort' => $pageParam['sort'],
            'mobile' => empty($pageParam['mobile']) ? '' : $pageParam['mobile'],
            'heavy_click' => empty($pageParam['heavy_click']) ? '': $pageParam['heavy_click'],
            'adzid' => empty($pageParam['adzid']) ? '' : $pageParam['adzid'],
        );
        return $params;
    }

    /**
     * 实时IP时数据初期准备(直接点击和从站长管理跳转两种情况)
     */
    private function _getIpFront($pageParam)
    {
        $params = array(
            'time' => date("Y-m-d").date("Y-m-d"),
            'day' => date("Y-m-d"),
            'day1' => date("Y-m-d"),
            'ipname' => empty($pageParam['uid'])?'ip':'uid',
            'ipnum' => empty($pageParam['uid'])?'':$pageParam['uid'],
            'plan' => '',
        );
        return $params;
    }

    /**
     * 数据报表页面检索后的条件数据
     */
    private function _getIpEnd($params)
    {
        $params = array(
            'time' => $params['time'],
            'day' => (substr($params['time'], 0,3) == 'all') ? 'all' : substr($params['time'], 0,10),
            'day1' => empty(substr($params['time'], 10,20)) ? 'all': substr($params['time'], 10,20),
            'ipname' => $params['ipname'],
            'ipnum' => empty($params['ipnum']) ? '':$params['ipnum'],
            'plan' => '',
        );
        return $params;
    }


    /**
     *  处理CRT 计算。每条
     */
    public function _getCrtForPlan($res)
    {
        //计算CRT
        foreach($res as $key=>$value){

            if(!empty($value['views'])){
                $res[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                $res[$key]['crt'] = round($value['adv_num']/$value['views']*100,2);
                $res[$key]['cpa'] = round($value['download']/$value['views']*100,2);
            }
        }
        return $res;
    }

    /**
     *  处理CRT 计算。每条
     */
    public function _getCrtForPlanLst($res,$params)
    {
        //计算CRT
        foreach($res as $key=>$value){
            if(!empty($params['numid']) && $value['heavy_click_num']>$value['click_num']){
                if(empty($value['click_num'])){
                    $res[$key]['heavy_click_num'] = 0;
                }else{
                    $res[$key]['heavy_click_num'] = (floor($value['click_num']/$value['heavy_click_num']*100)/100)*$value['heavy_click_num'];
                }
            }

            if(!empty($value['views'])){
                $res[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                $res[$key]['crt'] = round($value['adv_num']/$value['views']*100,2);
                $res[$key]['cpa'] = round($value['download']/$value['views']*100,2);
            }
        }
        return $res;
    }

    /**
     *  广告商 处理CRT 计算。每条
     */
    public function _getCrtForAdv($res)
    {
        //计算CRT
        foreach($res as $key=>$value){
            if(!empty($value['username'])){
                if(!empty($value['views'])){
                    $res[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                    $res[$key]['crt'] = round($value['adv_num']/$value['views']*100,2);
                    $res[$key]['cpa'] = round($value['download']/$value['views']*100,2);
                }
            }else{
                unset($res[$key]);
            }
        }
        return $res;
    }

    /**
     *  站长报表处理CRT计算。每条
     */
    public function _getCrtForWeb($res)
    {
        //计算CRT
        $_res = array();
        foreach($res as $key=>$value){
            if(!empty($value['views']))
            {
                $res[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                $res[$key]['crt'] = round($value['web_num']/$value['views']*100,2);
                $res[$key]['cpa'] = round($value['download']/$value['views']*100,2);
            }
        }
        return $res;
    }


    /**
     *  处理CRT 计算。合并汇总
     */
    public function _getLstCRTForPlan($res)
    {
        //把计划排重点击 从新组装新数组
        $num = array();
        $mergeRes = array();
        foreach ($res as $key => $value) {
            if(empty($mergeRes)){
                $mergeRes['views'] = 0;
                $mergeRes['download'] = 0;
                $mergeRes['click_num'] = 0;
                $mergeRes['sumprofit'] = 0;
                $mergeRes['sumpay'] = 0;
                $mergeRes['sumadvpay'] = 0;
                $mergeRes['adv_num'] = 0;
                $mergeRes['adv_deduction'] = 0;
                $mergeRes['heavy_click_num'] = 0;
                $mergeRes['bigclick'] = 0;
                // $mergeRes['ctime'] = 0;
            }

            //排重点击用
            $num[$value['day']][$value['pid']][] = $value['heavy_click_num'];

            $mergeRes['views'] +=$value['views'];
            $mergeRes['download'] += $value['download'];
            $mergeRes['click_num'] += $value['click_num'];
            $mergeRes['sumprofit'] += $value['sumprofit'];
            $mergeRes['sumpay'] += $value['sumpay'];
            $mergeRes['sumadvpay'] += $value['sumadvpay'];
            $mergeRes['adv_num'] += $value['adv_num'];
            $mergeRes['adv_deduction'] += $value['adv_deduction'];
            $mergeRes['heavy_click_num'] += max($num[$value['day']][$value['pid']]);
            $mergeRes['bigclick'] += isset($value['bigclick']) ? $value['bigclick'] : 0;
        }

        return $mergeRes;
    }



    /**
     *  站长报表处理CRT计算,合并汇总
     */
    public function _getLstCRTForWeb($res)
    {
        $_res = array();
        if(!empty($res)){
            foreach($res as $key => $value){
                if(empty($_res)){
                    $_res['views'] = 0;
                    $_res['click_num'] = 0;
                    $_res['uv_web'] = 0;
                    $_res['ui_web'] = 0;
                    $_res['web_deduction'] = 0;
                    $_res['web_num'] = 0;
                    //$_res['adv_num'] = 0;
                    $_res['sumpay'] = 0;
                    $_res['sumprofit'] = 0;
                    $_res['cpd'] = 0;
                    $_res['cpd_day'] = '';
                    $_res['web_click_num'] = 0;
                    $_res['jump'] = 0;
                }
                $_res['views'] +=$value['views'];
                $_res['click_num'] += $value['click_num'];
                $_res['uv_web'] += $value['uv_web'];
                $_res['ui_web'] += $value['ui_web'];
                $_res['web_deduction'] += $value['web_deduction'];
                $_res['web_num'] += $value['web_num'];
                //$_res['adv_num'] += $value['adv_num'];
                $_res['sumpay'] += $value['sumpay'];
                $_res['sumprofit'] += $value['sumprofit'];
                $_res['web_click_num'] += $value['web_click_num'];
                $_res['cpd'] += $value['cpd'];
                $_res['jump'] += $value['jump'];
            }
        }
        return $_res;
    }

    /**
     *  处理CRT 计算。合并汇总
     */
    public function _getLstCRTForAdv($res)
    {
        //把计划排重点击 从新组装新数组
        $num = array();
        $mergeRes = array();
        foreach ($res as $key => $value) {
            if(empty($mergeRes)){
                $mergeRes['views'] = 0;
                $mergeRes['download'] = 0;
                $mergeRes['click_num'] = 0;
                $mergeRes['sumprofit'] = 0;
                $mergeRes['sumadvpay'] = 0;
                $mergeRes['adv_num'] = 0;
                $mergeRes['adv_deduction'] = 0;

            }

            $mergeRes['views'] += $value['views'];
            $mergeRes['download'] += $value['download'];
            $mergeRes['click_num'] += $value['click_num'];
            $mergeRes['sumprofit'] += $value['sumprofit'];
            $mergeRes['sumadvpay'] += $value['sumadvpay'];
            $mergeRes['adv_num'] += $value['adv_num'];
            $mergeRes['adv_deduction'] += $value['adv_deduction'];
        }

        return $mergeRes;
    }

    /**
     * 将查询出来的数据进行处理，计算出CRT
     */
    public function _getCRT($res,$params)
    {
        $res = $this->_getTodayNumber($res,$params);
        $num = array();
        $number = 0;
        //将三维数组转换为二维数组
        foreach($res as $key=>$value){
            foreach($value as $arr){
                $num[$number]['views'] = $arr['views'];
                $num[$number]['ctime'] = $arr['ctime'];
                $num[$number]['download'] = $arr['download'];
                $num[$number]['click_num'] = $arr['click_num'];
                $num[$number]['sumprofit'] = sprintf("%.4f", $arr['sumprofit']);
                $num[$number]['fact_sumprofit'] = sprintf("%.4f", $arr['fact_sumprofit']);
                $num[$number]['sumpay'] = sprintf("%.4f", $arr['sumpay']);
                $num[$number]['sumadvpay'] = sprintf("%.4f", $arr['sumadvpay']);
                $num[$number]['web_num'] = floor($arr['web_num']);
                $num[$number]['cpd'] = sprintf("%.2f", $arr['cpd']);
                $num[$number]['adv_num'] = floor($arr['adv_num']);
                $num[$number]['web_deduction'] = floor($arr['web_deduction']);
                $num[$number]['adv_deduction'] = floor($arr['adv_deduction']);
                $num[$number]['ui_plan'] = $arr['ui_plan'];
                $num[$number]['ui_web'] = $arr['ui_web'];
                $num[$number]['ui_ads'] = $arr['ui_ads'];
                $num[$number]['ui_adzone'] = $arr['ui_adzone'];
                $num[$number]['uv_plan'] = $arr['uv_plan'];
                $num[$number]['uv_web'] = $arr['uv_web'];
                $num[$number]['uv_ads'] = $arr['uv_ads'];
                $num[$number]['uv_adzone'] = $arr['uv_adzone'];
                $num[$number]['plan_name'] = empty($arr['plan_name']) ? '' : $arr['plan_name'];
                $num[$number]['checkplan'] = empty($arr['checkplan']) ? '' : $arr['checkplan'];
                $num[$number]['username'] = empty($arr['username']) ? '' : $arr['username'];
                $num[$number]['adname'] = empty($arr['adname']) ? '' : $arr['adname'];
                $num[$number]['zonename'] = empty($arr['zonename']) ? '' : $arr['zonename'];
                $num[$number]['size'] = empty($arr['size']) ? '' : $arr['size'];
                $num[$number]['sitename'] = empty($arr['sitename']) ? '' : $arr['sitename'];
                $num[$number]['siteurl'] = empty($arr['siteurl']) ? '' : $arr['siteurl'];
                $num[$number]['class_name'] = empty($arr['class_name']) ? '' : $arr['class_name'];
                $num[$number]['plan_type'] = empty($arr['plan_type']) ? '' : $arr['plan_type'];
                $num[$number]['pid'] = $arr['pid'];
                $num[$number]['uid'] = $arr['uid'];
                $num[$number]['ad_id'] = $arr['ad_id'];
                $num[$number]['adz_id'] = $arr['adz_id'];
                $num[$number]['adv_id'] = $arr['adv_id'];
                $num[$number]['site_id'] = $arr['site_id'];
                $num[$number]['heavy_click_num'] = $arr['heavy_click_num'];
                $num[$number]['run_terminal'] = empty($arr['run_terminal']) ? '' : $arr['run_terminal'];
                $num[$number]['web_click_num'] = $arr['web_click_num'];
                $num[$number]['adz_click_num'] = $arr['adz_click_num'];
                $num[$number++]['day'] = $arr['day'];

            }
        }
        //计算CRT
        foreach($num as $key=>$value){
            if($params['stats']=='plan_list' && !empty($params['numid']) && $value['heavy_click_num']>$value['click_num']){
                if(empty($value['click_num'])){
                    $num[$key]['heavy_click_num'] = 0;
                }else{
                    $num[$key]['heavy_click_num'] = (floor($value['click_num']/$value['heavy_click_num']*100)/100)*$value['heavy_click_num'];
                }
            }
            if(!empty($value['views'])){
                $num[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                if($value['views'] && ($params['stats'] == 'plan_list' || $params['stats'] == 'ads_list')){
                    $num[$key]['crt'] = round($value['adv_num']/$value['views']*100,2);
                    $num[$key]['cpa'] = round($value['download']/$value['views']*100,2);
                }elseif($value['views'] && ($params['stats'] == 'user_list' || $params['stats'] == 'zone_list' || $params['stats'] == 'user_ads_list' || $params['stats'] == 'site_list'|| $params['stats'] == 'classes_list')){
                    $num[$key]['crt'] = round($value['web_num']/$value['views']*100,2);
                }else{
                    $num[$key]['crt'] = '0';
                }
            }
        }
        return $num;
    }




    public function _getWebCRT($res,$params)
    {
        $res = $this->_getWebTodayNumber($res,$params);
        $num = array();
        $number = 0;
        //将三维数组转换为二维数组
        foreach($res as $key=>$value){
            foreach($value as $arr){
                $num[$number]['download'] = $arr['download'];
                $num[$number]['web_click_num'] = $arr['web_click_num'];
                $num[$number]['views'] = $arr['views'];
                $num[$number]['click_num'] = $arr['click_num'];
                $num[$number]['adv_num'] = $arr['adv_num'];
                $num[$number]['uv_web'] = $arr['uv_web'];
                $num[$number]['ui_web'] = $arr['ui_web'];
                $num[$number]['web_deduction'] = floor($arr['web_deduction']);
                $num[$number]['web_num'] = floor($arr['web_num']);
                $num[$number]['sumpay'] = sprintf("%.4f", $arr['sumpay']);
                $num[$number]['sumprofit'] = sprintf("%.4f", $arr['sumprofit']);
                $num[$number]['cpd'] = sprintf("%.2f", $arr['cpd']);
                $num[$number++]['day'] = $arr['day'];
            }
        }
        //计算CRT
        foreach($num as $key=>$value){
            if(!empty($value['views'])){
                $num[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                if($value['views'] && ($params['stats'] == 'plan_list' || $params['stats'] == 'ads_list')){
                    $num[$key]['crt'] = round($value['adv_num']/$value['views']*100,2);
                    $num[$key]['cpa'] = round($value['download']/$value['views']*100,2);
                }elseif($value['views'] && ($params['stats'] == 'user_list' || $params['stats'] == 'zone_list' || $params['stats'] == 'user_ads_list')){
                    $num[$key]['crt'] = round($value['web_num']/$value['views']*100,2);
                }else{
                    $num[$key]['crt'] = '0';
                }
            }
        }
        return $num;
    }




    /**
     * 计算所有数据的汇总  for plan
     */
    public function _dataTotalForPlan($number,$params,$timeValue)
    {
        if(!empty($number)){
            $number['web_views_fact'] = empty($number['web_views_fact']) ? $number['views'] :
                $number['web_views_fact'];
            $number['ctr'] = round($number['click_num']/$number['web_views_fact']*100,2);
            $number['crt'] = round($number['adv_num']/$number['views']*100,2);
            $number['cpa'] = round($number['download']/$number['views']*100,2);
        }else{
            $number['views'] = 0;
        }

        $number['stats'] = $params['stats'];
        $number['numid'] = $params['numid'];
        $number['time'] = $params['time'];
        $number['type'] = $params['type'];
        $number['sort'] = $params['sort'];
        $number['id'] = $params['id'];
        $number['mobile'] = $params['mobile'];

        $res = array_merge($timeValue,$number);
        return $res;
    }

    /**
     * 计算所有数据的汇总  for plan
     */
    public function _dataTotalForAdv($number,$params,$timeValue)
    {
        if(!empty($number)){

            $number['ctr'] = round($number['click_num']/$number['views']*100,2);
            $number['crt'] = round($number['adv_num']/$number['views']*100,2);
            $number['cpa'] = round($number['download']/$number['views']*100,2);
        }else{
            $number['views'] = 0;
        }

        $number['stats'] = $params['stats'];
        $number['numid'] = $params['numid'];
        $number['time'] = $params['time'];
        $number['type'] = $params['type'];
        $number['sort'] = $params['sort'];
        $number['id'] = $params['id'];


        $res = array_merge($timeValue,$number);
        return $res;
    }

    /**
     * 站长报表所有数据的汇总
     */
    public function _dataTotalForWeb($number,$params,$timeValue)
    {
        $number['stats'] = $params['stats'];
        $number['numid'] = $params['numid'];
        $number['time'] = $params['time'];
        $number['type'] = $params['type'];
        $number['sort'] = $params['sort'];
        $number['id'] = $params['id'];
        if(!empty($number['views'])){
            $number['ctr'] = round($number['click_num']/$number['views']*100,2);
            $number['crt'] = round($number['web_num']/$number['views']*100,2);
        }else{
            $number['views'] = 0;
            $number['ctr'] = 0;
            $number['crt'] = 0;
        }
        if(empty($number['web_click_num'])){
            $number['click_cost'] = 0;
        }else{
            $number['click_cost'] = round($number['sumpay']/$number['web_click_num'],4);
        }
        $res = array_merge($timeValue,$number);
        return $res;
    }


    /**
     * 计算所有数据的汇总
     */
    public function _dataTotal($number,$params,$timeValue)
    {
        $arr = $this->_total($number,$params);
        //将累加后的数据跟程序的一些参数拼接（每次搜索之后可以使搜索条件保留到页面上）
        $data = array(
            'stats' => $params['stats'],
            'numid' => $params['numid'],
            'time' => $params['time'],
            'type' => $params['type'],
            'sort' => $params['sort'],
            'id' => $params['id'],
            'mobile' => empty($params['mobile']) ? '' :$params['mobile'],
            'views' => empty($arr['views']) ? '' : $arr['views'],
            'download' => empty($arr['download']) ? '' : $arr['download'],
            'web_num' => empty($arr['web_num']) ? '' : $arr['web_num'],
            'adv_num' => empty($arr['adv_num']) ? '' : $arr['adv_num'],
            'ui_plan' => empty($arr['ui_plan']) ? '' : $arr['ui_plan'],
            'ui_web' => empty($arr['ui_web']) ? '' : $arr['ui_web'],
            'ui_ads' => empty($arr['ui_ads']) ? '' : $arr['ui_ads'],
            'ui_adzone' => empty($arr['ui_adzone']) ? '' : $arr['ui_adzone'],
            'uv_plan' => empty($arr['uv_plan']) ? '' : $arr['uv_plan'],
            'uv_web' => empty($arr['uv_web']) ? '' : $arr['uv_web'],
            'uv_ads' => empty($arr['uv_ads']) ? '' : $arr['uv_ads'],
            'uv_adzone' => empty($arr['uv_adzone']) ? '' : $arr['uv_adzone'],
            'unique_ip' => empty($arr['unique_ip']) ? '' : $arr['unique_ip'],
            'click_num' => empty($arr['click_num']) ? '' : $arr['click_num'],
            'web_deduction' => empty($arr['web_deduction']) ? '' : $arr['web_deduction'],
            'adv_deduction' => empty($arr['adv_deduction']) ? '' : $arr['adv_deduction'],
            'crt' => empty($arr['crt']) ? '' : $arr['crt'],
            'cpa' => empty($arr['cpa']) ? '' : $arr['cpa'],
            'ctr' => empty($arr['ctr']) ? '' : $arr['ctr'],
            'cpd' => empty($arr['cpd']) ? '' : sprintf("%.2f", $arr['cpd']),
            'sumprofit' => empty($arr['sumprofit']) ? '' : sprintf("%.4f", $arr['sumprofit']),
            'sumpay' => empty($arr['sumpay']) ? '' : sprintf("%.4f", $arr['sumpay']),
            'sumadvpay' => empty($arr['sumadvpay']) ? '' : sprintf("%.4f", $arr['sumadvpay']),
            'fact_sumprofit' => empty($arr['fact_sumprofit']) ? '' : sprintf("%.4f", $arr['fact_sumprofit']),
            'unique_visitor' => empty($arr['unique_visitor']) ? '' : $arr['unique_visitor'],
            'heavy_click_num' => empty($arr['heavy_click_num']) ? '' : $arr['heavy_click_num'],
            'web_click_num' => empty($arr['web_click_num']) ? '' : $arr['web_click_num'],
            'adz_click_num' => empty($arr['adz_click_num']) ? '' : $arr['adz_click_num'],
            'bigclick' => empty($arr['bigclick']) ? '' : $arr['bigclick'],
        );
        $data = array_merge($timeValue,$data);
        return $data;
    }

    /**
     * 实时IP检索条件数据的拼接
     */
    private function _ipNum($timeValue,$params)
    {
        $data = array(
            'time' => $params['time'],
            'ipname' => $params['ipname'],
            'ipnum' => $params['ipnum'],
            'plan' => $params['plan'],
        );
        $data = array_merge($timeValue,$data);
        return $data;
    }

    /**
     * 处理时间函数
     */
    public function _getTime($day,$parama)
    {
        //获取所有时间段
        $allday = 'all';
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

    /**
     * 广告位授访域名 top 10  处理时间函数
     */
    public function _siteGetTime($day,$parama)
    {
        //获取所有时间段
        $allday = 'all';
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

            'allday' => $allday,
            'today' => $today,
            'yesterday' => $yesterday,
            'lastlastTwo' => $lastTwo,
            'lastseven' => $lastSeven,
            'lastthirty' => $lastThirty,
            'lastmonth' => $lastMonth,
        );
        return $data;
    }

    /**
     * 根据扣量计算不同的数据
     */
    public function _getnumber($res)
    {
        $data[] = array();
        foreach($res as $key=>$value){
            if($value['gradation'] == 1){
                switch($value['star']){
                    case 1 : $value['price'] = $value['price_1']; break;
                    case 2 : $value['price'] = $value['price_2']; break;
                    case 3 : $value['price'] = $value['price_3']; break;
                    case 4 : $value['price'] = $value['price_4']; break;
                    case 5 : $value['price'] = $value['price_5']; break;
                }
            }
            //计算扣量后的站长结算数
            $res[$key]['cpd'] = 0;
            //包天情况下
            if(!empty($value['cpd'])){
                $arr = array(
                    'adz_id' => $value['adz_id'],
                    'day' => $value['day'],
                );
                if(!in_array($arr,$data)){
                    $res[$key]['cpd'] = sprintf("%.4f",$value['cpd']);
                }
                //实际盈利
                $res[$key]['fact_sumprofit'] = sprintf("%.4f", $res[$key]['sumadvpay'] - $res[$key]['cpd']);
                //跑量盈利
                $res[$key]['sumprofit'] = sprintf("%.4f", $res[$key]['sumadvpay'] - $res[$key]['sumpay']);
            }else{
                //实际盈利
                $res[$key]['fact_sumprofit'] = sprintf("%.4f", $res[$key]['sumadvpay'] - $res[$key]['sumpay']);
                //跑量盈利
                $res[$key]['sumprofit'] = sprintf("%.4f", $res[$key]['sumadvpay'] - $res[$key]['sumpay']);
            }
            $data[] = array(
                'adz_id' => $value['adz_id'],
                'day' => $value['day'],
            );
        }
        return $res;
    }

    /**
     * 数据汇总
     */
    private function _total($number,$params)
    {
        //初始化数组
        $arr = array();
        foreach($number as $key=>$value){
            $arr['views'] = 0;
            $arr['download'] = 0;
            $arr['web_num'] = 0;
            $arr['adv_num'] = 0;
            $arr['click_num'] = 0;
            $arr['cpd'] = 0;
            $arr['sumprofit'] = 0;
            $arr['sumpay'] = 0;
            $arr['sumadvpay'] = 0;
            $arr['fact_sumprofit'] = 0;
            $arr['web_deduction'] = 0;
            $arr['adv_deduction'] = 0;
            $arr['uv_plan'] = 0;
            $arr['uv_web'] = 0;
            $arr['uv_ads'] = 0;
            $arr['uv_adzone'] = 0;
            $arr['ui_plan'] = 0;
            $arr['ui_web'] = 0;
            $arr['ui_ads'] = 0;
            $arr['ui_adzone'] = 0;
            $arr['web_views_fact'] = 0;
            $arr['heavy_click_num'] = 0;
            $arr['web_click_num'] = 0;
            $arr['adz_click_num'] = 0;
            $arr['bigclick'] = 0;
        }
        //执行数据累加
        foreach($number as $key=>$value){
            $arr['views'] += $value['views'];
            $arr['download'] += !isset($value['download']) ? 0 : $value['download'];
            $arr['web_num'] += !isset($value['web_num']) ? 0 : $value['web_num'];
            $arr['adv_num'] += !isset($value['adv_num']) ? 0 : $value['adv_num'];
            $arr['click_num'] += $value['click_num'];
            $arr['cpd'] += !isset($value['cpd']) ? 0 : $value['cpd'];
            $arr['sumprofit'] += $value['sumprofit'];
            $arr['sumpay'] += $value['sumpay'];
            $arr['sumadvpay'] += $value['sumadvpay'];
            $arr['fact_sumprofit'] += !isset($value['fact_sumprofit']) ? 0 : $value['fact_sumprofit'];
            $arr['web_deduction'] += !isset($value['web_deduction']) ? 0 : $value['web_deduction'];
            $arr['adv_deduction'] += !isset($value['adv_deduction']) ? 0 : $value['adv_deduction'];
            $arr['uv_plan'] += !isset($value['uv_plan']) ? 0 : $value['uv_plan'];
            $arr['uv_web'] += !isset($value['uv_web']) ? 0 : $value['uv_web'];
            $arr['uv_ads'] += !isset($value['uv_ads']) ? 0 : $value['uv_ads'];
            $arr['uv_adzone'] += !isset($value['uv_adzone']) ? 0 : $value['uv_adzone'];
            $arr['ui_plan'] += !isset($value['ui_plan']) ? 0 : $value['ui_plan'];
            $arr['ui_web'] += !isset($value['ui_web']) ? 0 : $value['ui_web'];
            $arr['ui_ads'] += !isset($value['ui_ads']) ? 0 : $value['ui_ads'];
            $arr['ui_adzone'] += !isset($value['ui_adzone']) ? 0 : $value['ui_adzone'];
            $arr['web_views_fact'] += !isset($value['web_views_fact']) ? 0 : $value['web_views_fact'];

            $arr['heavy_click_num'] += !isset($value['heavy_click_num']) ? 0 : $value['heavy_click_num'];

            $arr['web_click_num'] += !isset($value['web_click_num']) ? 0 : $value['web_click_num'];
            $arr['adz_click_num'] += !isset($value['adz_click_num']) ? 0 : $value['adz_click_num'];
            $arr['bigclick'] += !isset($value['bigclick']) ? 0 : $value['bigclick'];
        }
        if($arr){
            $arr['web_views_fact'] = empty($arr['web_views_fact']) ? $arr['views'] : $arr['web_views_fact'];
            //如果汇总数据不为空的情况下计算CRT
            $arr['ctr'] = round($arr['click_num']/$arr['web_views_fact']*100,2);
            if($arr['views'] && ($params['stats'] == 'plan_list' || $params['stats'] == 'ads_list')){
                $arr['crt'] = round($arr['adv_num']/$arr['views']*100,2);
                $arr['cpa'] = round($arr['download']/$arr['views']*100,2);
            }elseif($arr['views'] && ($params['stats'] == 'user_list' || $params['stats'] == 'zone_list' || $params['stats'] == 'user_ads_list' ||$params['stats'] == 'site_list'||$params['stats'] == 'classes_list')){
                $arr['crt'] = round($arr['web_num']/$arr['views']*100,2);
            }else{
                $arr['crt'] = 0;
            }
        }
        return $arr;
    }

    /**
     * 将同一天下同一计划，或者同一站长，或者同一广告，同一广告位下，同一广告商下的数据汇总
     */
    private function _getTodayNumber($res,$params)
    {
        $arr = array();
        //初始化报告的数据
        foreach ($res as $key => $value) {
            if($params['stats'] == 'plan_list'){
                $name = $value['pid'].'pid';
            }elseif($params['stats'] == 'user_list'){
                $name = $value['adz_id'].'adz_id';
            }elseif($params['stats'] == 'ads_list'){
                $name = $value['ad_id'].'ad_id';
            }elseif($params['stats'] == 'zone_list'){
                $name = $value['adz_id'].'adz_id';
            }elseif($params['stats'] == 'adv_list'){
                $name = $value['adv_id'].'adv_id';
            }elseif($params['stats'] == 'site_list'){
                $name = $value['site_id'].'site_id';
            }else{
                $name = $value['class_name'].'class_name';
            }
            $arr[$value['day']][$name]['views'] = 0;
            $arr[$value['day']][$name]['ctime'] = 0;
            $arr[$value['day']][$name]['download'] = 0;
            $arr[$value['day']][$name]['click_num'] = 0;
            $arr[$value['day']][$name]['sumprofit'] = 0;
            $arr[$value['day']][$name]['fact_sumprofit'] = 0;
            $arr[$value['day']][$name]['sumpay'] = 0;
            $arr[$value['day']][$name]['cpd'] = 0;
            $arr[$value['day']][$name]['sumadvpay'] = 0;
            $arr[$value['day']][$name]['web_num'] = 0;
            $arr[$value['day']][$name]['adv_num'] = 0;
            $arr[$value['day']][$name]['web_deduction'] = 0;
            $arr[$value['day']][$name]['adv_deduction'] = 0;
            $arr[$value['day']][$name]['uv_plan'] = 0;
            $arr[$value['day']][$name]['uv_web'] = 0;
            $arr[$value['day']][$name]['uv_ads'] = 0;
            $arr[$value['day']][$name]['uv_adzone'] = 0;
            $arr[$value['day']][$name]['ui_plan'] = 0;
            $arr[$value['day']][$name]['ui_web'] = 0;
            $arr[$value['day']][$name]['ui_ads'] = 0;
            $arr[$value['day']][$name]['heavy_click_num'] = 0;
            $arr[$value['day']][$name]['web_click_num'] = 0;
            $arr[$value['day']][$name]['adz_click_num'] = 0;
            $arr[$value['day']][$name]['ui_adzone'] = 0;
        }
        //把计划排重点击 从新组装新数组
        $num = array();
        //把广告位独立 ip 从新组装新数组
        $adzone_ip = array();

        //把站长排重点击 从新组装新数组
        $web_click_num = array();

        //把广告位排重点击 从新组装新数组
        $adz_click_num = array();
        foreach($res as $k => $v){
            $num[$v['day']][$v['pid']][] = $v['heavy_click_num'];
            $adzone_ip[$v['day']][$v['adz_id']][] = $v['ui_adzone'];
            $web_click_num[$v['day']][$v['uid']][] = $v['web_click_num'];
            $adz_click_num[$v['day']][$v['adz_id']][] = $v['adz_click_num'];
        }
        foreach ($res as $key => $value) {
            if($params['stats'] == 'plan_list'){
                $name = $value['pid'].'pid';
                $arr[$value['day']][$name]['plan_name'] = $value['plan_name'];
                $arr[$value['day']][$name]['plan_type'] = $value['plan_type'];
                $arr[$value['day']][$name]['checkplan'] = $value['checkplan'];
                $arr[$value['day']][$name]['run_terminal'] = $value['run_terminal'];
            }elseif($params['stats'] == 'user_list'){
                $name = $value['adz_id'].'adz_id';
                $arr[$value['day']][$name]['username'] = $value['username'];
            }elseif($params['stats'] == 'ads_list'){
                $name = $value['ad_id'].'ad_id';
                $arr[$value['day']][$name]['adname'] = $value['adname'];
            }elseif($params['stats'] == 'zone_list'){
                $name = $value['adz_id'].'adz_id';
                $arr[$value['day']][$name]['zonename'] = $value['zonename'];
                $arr[$value['day']][$name]['size'] = $value['width'] .'*'. $value['height'];
            }elseif($params['stats'] == 'adv_list'){
                $name = $value['adv_id'].'adv_id';
                $arr[$value['day']][$name]['username'] = $value['username'];
            }elseif($params['stats'] == 'site_list'){
                $name = $value['site_id'].'site_id';
                $arr[$value['day']][$name]['sitename'] = $value['sitename'];
            }else{
                $name = $value['class_name'].'class_name';
                $arr[$value['day']][$name]['class_name'] = $value['class_name'];
            }
            $arr[$value['day']][$name]['pid'] = $value['pid'];
            $arr[$value['day']][$name]['uid'] = $value['uid'];
            $arr[$value['day']][$name]['ad_id'] = $value['ad_id'];
            $arr[$value['day']][$name]['adz_id'] = $value['adz_id'];
            $arr[$value['day']][$name]['adv_id'] = $value['adv_id'];
            $arr[$value['day']][$name]['site_id'] = $value['site_id'];
            $arr[$value['day']][$name]['day'] = $value['day'];
            $arr[$value['day']][$name]['siteurl'] = empty($value['siteurl']) ? '' : $value['siteurl'];
            $arr[$value['day']][$name]['ctime'] = $value['ctime'];
            $arr[$value['day']][$name]['views'] += $value['views'];
            $arr[$value['day']][$name]['download'] += $value['download'];
            $arr[$value['day']][$name]['click_num'] += $value['click_num'];
            $arr[$value['day']][$name]['sumprofit'] += $value['sumprofit'];
            $arr[$value['day']][$name]['fact_sumprofit'] += $value['fact_sumprofit'];
            $arr[$value['day']][$name]['sumpay'] += $value['sumpay'];
            $arr[$value['day']][$name]['cpd'] += $value['cpd'];
            $arr[$value['day']][$name]['sumadvpay'] += $value['sumadvpay'];
            $arr[$value['day']][$name]['web_num'] += empty($value['web_num']) ? 0 : $value['web_num'];
            $arr[$value['day']][$name]['adv_num'] += empty($value['adv_num']) ? 0 : $value['adv_num'];
            $arr[$value['day']][$name]['web_deduction'] += empty($value['web_deduction']) ? 0 : $value['web_deduction'];
            $arr[$value['day']][$name]['adv_deduction'] += empty($value['adv_deduction']) ? 0 : $value['adv_deduction'];
            $arr[$value['day']][$name]['uv_plan'] += empty($value['uv_plan']) ? 0 : $value['uv_plan'];
            $arr[$value['day']][$name]['uv_web'] += empty($value['uv_web']) ? 0 : $value['uv_web'];
            $arr[$value['day']][$name]['uv_ads'] += empty($value['uv_ads']) ? 0 : $value['uv_ads'];
            $arr[$value['day']][$name]['uv_adzone'] += empty($value['uv_adzone']) ? 0 : $value['uv_adzone'];
            $arr[$value['day']][$name]['ui_plan'] += empty($value['ui_plan']) ? 0 : $value['ui_plan'];
            $arr[$value['day']][$name]['ui_web'] += empty($value['ui_web']) ? 0 : $value['ui_web'];
            $arr[$value['day']][$name]['ui_ads'] += empty($value['ui_ads']) ? 0 : $value['ui_ads'];
            $arr[$value['day']][$name]['ui_adzone'] = max($adzone_ip[$value['day']][$value['adz_id']]);
            $arr[$value['day']][$name]['heavy_click_num'] = max($num[$value['day']][$value['pid']]);
            $arr[$value['day']][$name]['web_click_num'] = max($web_click_num[$value['day']][$value['uid']]);
            $arr[$value['day']][$name]['adz_click_num'] = max($adz_click_num[$value['day']][$value['adz_id']]);
        }
        return $arr;
    }




    private function _getWebTodayNumber($res,$params)
    {
        $arr = array();
        //初始化报告的数据
        foreach ($res as $key => $value) {
            /*if($params['stats'] == 'plan_list'){
                $name = $value['pid'].'pid';
            }elseif($params['stats'] == 'user_list'){
                $name = $value['adz_id'].'adz_id';
            }elseif($params['stats'] == 'ads_list'){
                $name = $value['ad_id'].'ad_id';
            }elseif($params['stats'] == 'zone_list'){
                $name = $value['adz_id'].'adz_id';
            }elseif($params['stats'] == 'adv_list'){
                $name = $value['adv_id'].'adv_id';
            }elseif($params['stats'] == 'site_list'){
                $name = $value['site_id'].'site_id';
            }else{
                $name = $value['class_name'].'class_name';
            }*/
            $name = $value['adz_id'].'adz_id';
            $arr[$value['day']][$name]['download'] = 0;
            $arr[$value['day']][$name]['views'] = 0;
            $arr[$value['day']][$name]['click_num'] = 0;
            $arr[$value['day']][$name]['adv_num'] = 0;
            $arr[$value['day']][$name]['ui_web'] = 0;
            $arr[$value['day']][$name]['uv_web'] = 0;
            $arr[$value['day']][$name]['sumpay'] = 0;
            $arr[$value['day']][$name]['sumprofit'] = 0;
            $arr[$value['day']][$name]['web_deduction'] = 0;
            $arr[$value['day']][$name]['web_num'] = 0;
            $arr[$value['day']][$name]['web_click_num'] = 0;
            $arr[$value['day']][$name]['cpd'] = 0;
            $arr[$value['day']][$name]['day'] = $value['day'];
        }
        //把计划排重点击 从新组装新数组
        $num = array();
        //把广告位独立 ip 从新组装新数组
        $adzone_ip = array();

        //把站长排重点击 从新组装新数组
        $web_click_num = array();

        //把广告位排重点击 从新组装新数组
        $adz_click_num = array();
        /*foreach($res as $k => $v){
            $num[$v['day']][$v['pid']][] = $v['heavy_click_num'];
            $adzone_ip[$v['day']][$v['adz_id']][] = $v['ui_adzone'];
            $web_click_num[$v['day']][$v['uid']][] = $v['web_click_num'];
            $adz_click_num[$v['day']][$v['adz_id']][] = $v['adz_click_num'];
        }*/

        foreach ($res as $key => $value) {
            /*if($params['stats'] == 'user_list'){
                $name = $value['adz_id'].'adz_id';
                $arr[$value['day']][$name]['username'] = $value['username'];
            }*/
            $name = $value['adz_id'].'adz_id';
            $arr[$value['day']][$name]['username'] = $value['username'];
            $arr[$value['day']][$name]['views'] += $value['views'];
            $arr[$value['day']][$name]['download'] += $value['download'];
            $arr[$value['day']][$name]['click_num'] += $value['click_num'];
            $arr[$value['day']][$name]['adv_num'] += $value['adv_num'];
            $arr[$value['day']][$name]['sumprofit'] += $value['sumprofit'];
            $arr[$value['day']][$name]['sumpay'] += $value['sumpay'];
            $arr[$value['day']][$name]['cpd'] += $value['cpd'];
            $arr[$value['day']][$name]['web_num'] += $value['web_num'];
            $arr[$value['day']][$name]['web_deduction'] += $value['web_deduction'];
            $arr[$value['day']][$name]['uv_web'] += $value['uv_web'];
            $arr[$value['day']][$name]['ui_web'] += $value['ui_web'];
            $arr[$value['day']][$name]['web_click_num'] += $value['web_click_num'];
            //$arr[$value['day']][$name]['web_click_num'] = max($web_click_num[$value['day']][$value['uid']]);
        }
        return $arr;
    }



    /**
     * 数据报表排序 for web
     */
    public function _dataSortForweb($arrays,$sort_key,$sort_order=SORT_DESC,$sort_type=SORT_NUMERIC )
    {
        $sort_key = $sort_key == 'click_num' ? 'ctr' : $sort_key;
        $sort_key = $sort_key == 'ui_adzone' ? 'ui_web' : $sort_key;
        $sort_key = $sort_key == 'sumadvpay' ? 'sumpay' : $sort_key;
        $arrays = is_array($arrays) ? $arrays : $arrays = array();
        $i = 1;
        foreach($arrays as $key=>$value){
            $arrays[$key]['ctime'] = -strtotime($value['day'])+$i;
            $i++;
            
        }
        $key_arrays = array();
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }

    /**
     * 数据报表排序
     */
    public function _dataSort($arrays,$sort_key,$sort_order=SORT_DESC,$sort_type=SORT_NUMERIC )
    {
        $request = Request::instance();
        $pageParam = $request->param();
        $sort_key = $sort_key == 'click_num' ? 'ctr' : $sort_key;
        $sort_key = $sort_key == 'click_cost' ? 'ctr' : $sort_key;
        if(isset($pageParam['stats'])&&$pageParam['stats'] != 'zone_list'){
            $sort_key = $sort_key == 'ui_adzone' ? 'ctr' : $sort_key;
            $sort_key = $sort_key == 'ui_web' ? 'ctr' : $sort_key;
        }else{
            $sort_key = $sort_key == 'ui_web' ? 'ui_adzone' : $sort_key;
        }
        $arrays = is_array($arrays) ? $arrays : $arrays = array();
        $i=1;
        foreach($arrays as $key=>$value){
            $arrays[$key]['ctime'] = -strtotime($value['day'])+$i;
            $i++;
        }
        $key_arrays = array();
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }

    /**
     * 将站长报表按照时间，uid排序
     */
    public function _webSort($totalNum)
    {
        $day = '';$i = 0;
        $webCtime = array();
        $webUid = array();
        foreach($totalNum as $key=>$value){
            if($value['day'] != $day){
                $i = $i + 1;
            }
            $webCtime[$i][$key] = $value;
            $day = $value['day'];
        }
        foreach($webCtime as $key=>$value){
            $webUid[$key] = $this->_dataSort($value,'uid');
        }
        $data = array();
        foreach($webUid as $key=>$value){
            $data = array_merge($value,$data);
        }
        return $data;
    }


    /**
     * 处理站长报表数据
     */
    public function _webData($totalNum)
    {
        $uid = '';
        $ui_web = array();
        $arr = array();

        //初始化数组
        foreach($totalNum as $key=>$value){
            //$arr[$value['uid'].$value['day']]['views'] = 0;
            $arr[$value['uid'].$value['day']]['click_num'] = 0;
            $arr[$value['uid'].$value['day']]['uv_web'] = 0;
            $arr[$value['uid'].$value['day']]['ui_web'] = 0;
            $arr[$value['uid'].$value['day']]['web_num'] = 0;
            $arr[$value['uid'].$value['day']]['sumpay'] = 0;
            $arr[$value['uid'].$value['day']]['sumprofit'] = 0;
            $arr[$value['uid'].$value['day']]['web_deduction'] = 0;
            $arr[$value['uid'].$value['day']]['web_views_fact'] = 0;
        }
        //如果一个站长同时有多个广告位，则该站长的浏览数则取其广告位下最大的浏览数（下面数组用uid和day来拼接，是为了
        //保证不同的站长在不同的日期下才会取其广告位下最大的浏览数）
        foreach($totalNum as $key=>$value){
            if($value['uid'].$value['day'] != $uid)
            {
                $arr[$value['uid'].$value['day']] = $value;
                $arr[$value['uid'].$value['day']]['web_views_fact'] = $value['views']; //同站长下广告位浏览数总和
                $arr[$value['uid'].$value['day']]['views'] = $value['views'];
            }
            elseif($value['ui_web'] > $ui_web[$value['uid'].$value['day']])
            {
                $arr[$value['uid'].$value['day']]['uv_web'] = $value['uv_web'] + $arr[$value['uid'].$value['day']]['uv_web'];
                $value['uv_web'] = $arr[$value['uid'].$value['day']]['uv_web'];

                $arr[$value['uid'].$value['day']]['ui_web'] = $value['ui_web'] + $arr[$value['uid'].$value['day']]['ui_web'];
                $value['ui_web'] = $arr[$value['uid'].$value['day']]['ui_web'];

                $arr[$value['uid'].$value['day']]['web_num'] = $value['web_num'] + $arr[$value['uid'].$value['day']]['web_num'];
                $value['web_num'] = $arr[$value['uid'].$value['day']]['web_num'];

                $arr[$value['uid'].$value['day']]['click_num'] = $value['click_num'] + $arr[$value['uid'].$value['day']]['click_num'];
                $value['click_num'] = $arr[$value['uid'].$value['day']]['click_num'];

                $arr[$value['uid'].$value['day']]['sumpay'] = $value['sumpay'] + $arr[$value['uid'].$value['day']]['sumpay'];
                $value['sumpay'] = $arr[$value['uid'].$value['day']]['sumpay'];

                $arr[$value['uid'].$value['day']]['sumprofit'] = $value['sumprofit'] + $arr[$value['uid'].$value['day']]['sumprofit'];
                $value['sumprofit'] = $arr[$value['uid'].$value['day']]['sumprofit'];

                $arr[$value['uid'].$value['day']]['web_deduction'] = $value['web_deduction'] + $arr[$value['uid'].$value['day']]['web_deduction'];
                $value['web_deduction'] = $arr[$value['uid'].$value['day']]['web_deduction'];

                $arr[$value['uid'].$value['day']]['web_views_fact'] = $value['views'] + $arr[$value['uid'].$value['day']]['web_views_fact'];
                $value['web_views_fact'] = $arr[$value['uid'].$value['day']]['web_views_fact'];

                $arr[$value['uid'].$value['day']] = $value;
            }
            else
            {
                $arr[$value['uid'].$value['day']]['uv_web'] = $value['uv_web'] + $arr[$value['uid'].$value['day']]['uv_web'];
                $arr[$value['uid'].$value['day']]['ui_web'] = $value['ui_web'] + $arr[$value['uid'].$value['day']]['ui_web'];
                $arr[$value['uid'].$value['day']]['web_num'] = $value['web_num'] + $arr[$value['uid'].$value['day']]['web_num'];
                $arr[$value['uid'].$value['day']]['click_num'] = $value['click_num'] + $arr[$value['uid'].$value['day']]['click_num'];
                $arr[$value['uid'].$value['day']]['sumpay'] = $value['sumpay'] + $arr[$value['uid'].$value['day']]['sumpay'];
                $arr[$value['uid'].$value['day']]['sumprofit'] = $value['sumprofit'] + $arr[$value['uid'].$value['day']]['sumprofit'];
                $arr[$value['uid'].$value['day']]['web_deduction'] = $value['web_deduction'] + $arr[$value['uid'].$value['day']]['web_deduction'];
                $arr[$value['uid'].$value['day']]['web_views_fact'] = $value['views'] + $arr[$value['uid'].$value['day']]['web_views_fact'];
                $value['web_views_fact'] = $arr[$value['uid'].$value['day']]['web_views_fact'];

                //判断每次循环的浏览数如果大于之前的浏览数就选最大
                if($arr[$value['uid'].$value['day']]['views'] < $value['views']){
                    $arr[$value['uid'].$value['day']]['views'] = $value['views'];
                }
            }
            $uid = $value['uid'].$value['day'];
            $ui_web = array(
                $value['uid'].$value['day'] => $value['ui_web'],
            );
        }
        //计算点击率和crt
        foreach($arr as $key=>$value){
            if(empty($value['views'])){
                $arr[$key]['crt'] = 0;
                $arr[$key]['ctr'] = 0;
            }else{
                $arr[$key]['crt'] = round($value['web_num']/$value['views']*100,2);
                $arr[$key]['ctr'] = round($value['click_num']/$value['web_views_fact']*100,2);
                $arr[$key]['cpa'] = round($value['download']/$value['views']*100,2);
            }
        }
        $arr = empty($arr) ? '' : $arr;
        return $arr;
    }



    public function _webSum($totalNum,$params)
    {
        $arr = array();
        //初始化数组
        foreach($totalNum as $key=>$value){
            $arr[$value['uid'].$value['day']]['views'] = 0;
            $arr[$value['uid'].$value['day']]['click_num'] = 0;
            $arr[$value['uid'].$value['day']]['uv_web'] = 0;
            $arr[$value['uid'].$value['day']]['ui_web'] = 0;
            $arr[$value['uid'].$value['day']]['web_num'] = 0;
            $arr[$value['uid'].$value['day']]['sumpay'] = 0;
            $arr[$value['uid'].$value['day']]['sumprofit'] = 0;
            $arr[$value['uid'].$value['day']]['web_deduction'] = 0;
            $arr[$value['uid'].$value['day']]['web_views_fact'] = 0;
            $arr[$value['uid'].$value['day']]['web_click_num'] = 0;
            $arr[$value['uid'].$value['day']]['cpd'] = 0;
        }
        //如果一个站长同时有多个广告位,则该站长的浏览数则取其广告位下最大的浏览数（下面数组用uid和day来拼接，是为了
        //保证不同的站长在不同的日期下才会取其广告位下最大的浏览数）
        $web_click_num = array();
        foreach($totalNum as $key=>$value){
            //站长排重点击
            $web_click_num[$value['uid']][$value['day']][] = $value['web_click_num'];
            $arr[$value['uid'].$value['day']]['web_num'] = $value['web_num'] + $arr[$value['uid'].$value['day']]['web_num'];
            $arr[$value['uid'].$value['day']]['click_num'] = $value['click_num'] + $arr[$value['uid'].$value['day']]['click_num'];
            $arr[$value['uid'].$value['day']]['sumpay'] = $value['sumpay'] + $arr[$value['uid'].$value['day']]['sumpay'];
            $arr[$value['uid'].$value['day']]['sumprofit'] = $value['sumprofit'] + $arr[$value['uid'].$value['day']]['sumprofit'];
            $arr[$value['uid'].$value['day']]['web_deduction'] = $value['web_deduction'] + $arr[$value['uid'].$value['day']]['web_deduction'];
            $arr[$value['uid'].$value['day']]['web_views_fact'] = $value['views'] + $arr[$value['uid'].$value['day']]['web_views_fact'];
            $value['web_views_fact'] = $arr[$value['uid'].$value['day']]['web_views_fact'];
            $arr[$value['uid'].$value['day']]['web_click_num'] = max($web_click_num[$value['uid']][$value['day']]);

            //同一站长多个广告位,浏览数取最大值
            if($arr[$value['uid'].$value['day']]['views'] < $value['views']){
                $arr[$value['uid'].$value['day']]['views'] = $value['views'];
            }

            //同一站长多个广告位,独立访客取最大值
            if($arr[$value['uid'].$value['day']]['uv_web'] < $value['uv_web']){
                $arr[$value['uid'].$value['day']]['uv_web'] = $value['uv_web'];
            }

            //同一站长多个广告位,独立ip最大值
            if($arr[$value['uid'].$value['day']]['ui_web'] < $value['ui_web']){
                $arr[$value['uid'].$value['day']]['ui_web'] = $value['ui_web'];
            }

            $arr[$value['uid'].$value['day']]['day'] = $value['day'];
            $arr[$value['uid'].$value['day']]['uid'] = $value['uid'];
            $arr[$value['uid'].$value['day']]['username'] = $value['username'];
            $arr[$value['uid'].$value['day']]['cpd'] = empty($value['cpd'])? 0: $value['cpd'] + $arr[$value['uid'].$value['day']]['cpd'];
            $arr[$value['uid'].$value['day']]['cpd_day'] = empty($value['cpd_day'])? '': $value['cpd_day'];
        }
        //计算点击率和crt
        foreach($arr as $key=>$value){
            if(!empty($params['numid']) && $value['web_click_num']>$value['click_num']){
                if(empty($value['click_num'])){
                    $arr[$key]['web_click_num'] = 0;
                }else{
                    $arr[$key]['web_click_num'] = (floor($value['click_num']/$value['web_click_num']*100)/100)*$value['web_click_num'];
                }
            }
            if(empty($value['views'])){
                $arr[$key]['crt'] = 0;
                $arr[$key]['ctr'] = 0;
            }else{
                $arr[$key]['crt'] = round($value['web_num']/$value['views']*100,2);
                $arr[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                //$arr[$key]['cpa'] = round($value['download']/$value['views']*100,2);
            }

        }
        // dump($arr);exit;
        //echo '<pre>';
        //exit;
        $arr = empty($arr) ? array() : $arr;
        return $arr;
    }



    /**
     * 导出excel
     */
    public function excel($res,$params)
    {
        $excel = Config::get('excel_url');
        require_once  "".$excel."autoload.php";
        //修改内存
        ini_set('memory_limit','500M');
        //修改时间
        ini_set("max_execution_time", "0");
        $login_user =$_SESSION['think']['user_login_uname'];

        //统计数据个数
        $num_count = count($res)+1;
        $objPHPExcel = new \ PHPExcel();

        if($params['stats'] == 'plan_list'){
            $objPHPExcel = $this->planExcelPart($objPHPExcel,$res,$num_count,$login_user);
            $filename='计划报表'.date('Y-m-d'); //设置表的名字+日期

        }elseif($params['stats'] == 'user_list'){
            $objPHPExcel = $this->webExcelPart($objPHPExcel,$res,$num_count,$login_user);
            $filename='站长报表'.date('Y-m-d'); //设置表的名字+日期

        }elseif($params['stats'] == 'ads_list'){
            $objPHPExcel = $this->adsExcelPart($objPHPExcel,$res,$num_count,$login_user);
            $filename='广告报表'.date('Y-m-d'); //设置表的名字+日期

        }elseif($params['stats'] == 'zone_list'){
            $objPHPExcel = $this->zoneExcelPart($objPHPExcel,$res,$num_count,$login_user);
            $filename='广告位报表'.date('Y-m-d'); //设置表的名字+日期

        }elseif($params['stats'] == 'adv_list'){
            $objPHPExcel = $this->advExcelPart($objPHPExcel,$res,$num_count,$login_user);
            $filename='广告商报表'.date('Y-m-d'); //设置表的名字+日期

        }elseif($params['stats'] == 'site_list'){
            $objPHPExcel = $this->siteExcelPart($objPHPExcel,$res,$num_count,$login_user);
            $filename='网站报表'.date('Y-m-d'); //设置表的名字+日期

        }elseif($params['stats'] == 'classes_list'){
            $objPHPExcel = $this->classExcelPart($objPHPExcel,$res,$num_count,$login_user);
            $filename='网站分类报表'.date('Y-m-d'); //设置表的名字+日期
        }else{
            $objPHPExcel = $this->adzClasslassExcelPart($objPHPExcel,$res,$num_count,$login_user);
            $filename='广告位分类报表'.date('Y-m-d'); //设置表的名字+日期
        }


        // 重命名工作表
        $objPHPExcel->getActiveSheet()->setTitle('sheet1');
        // 将活动表索引设置为第一个表，所以将此作为第一张表打开
        $objPHPExcel->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
        // IE 9浏览器设置
        header('Cache-Control: max-age=1');
        // header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // 过去的日期
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        //导出Excel
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 计划导出excel（计划导出的一部分提出）  part
     */
    public function planExcelPart($objPHPExcel,$res,$num_count,$login_user)
    {
        //权限不足的情况下盈利不显示
        if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
            $auth_yl = '跑量盈利';
        }else{
            $auth_yl = ' ';
        }
        $objPHPExcel->getProperties();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getActiveSheet()->getStyle('G2')->getNumberFormat()->setFormatCode("0");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(15);

        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '日期')
            ->setCellValue('B1', '计划名称')
            ->setCellValue('C1', '类型')
            ->setCellValue('D1', '浏览数')
            ->setCellValue('E1', '点击数')
            ->setCellValue('F1', '下载数')
            ->setCellValue('G1', '扣量数')
            ->setCellValue('H1', '结算数')
            ->setCellValue('I1', 'CRT')
            ->setCellValue('J1', 'CPA')
            ->setCellValue('K1', '点击率')
            ->setCellValue('L1', '应付')
            ->setCellValue('M1', $auth_yl)
            ->setCellValue('N1', '站长支出');
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:M1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {
            if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
                $value['sumprofit'] = $value['sumprofit'];
            }else{
                $value['sumprofit'] = '';
            }
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['day'])
                ->setCellValue('B'.$i, !empty($value['plan_name'])?$value['plan_name']:'已删除')
                ->setCellValue('C'.$i, $value['plan_type'])
                ->setCellValue('D'.$i, $value['views'])
                ->setCellValue('E'.$i, $value['click_num'])
                ->setCellValue('F'.$i, $value['download'])
                ->setCellValue('G'.$i, $value['adv_deduction'])
                ->setCellValue('H'.$i, $value['adv_num'])
                ->setCellValue('I'.$i, $value['crt'].'%')
                ->setCellValue('J'.$i, $value['cpa'].'%')
                ->setCellValue('K'.$i, $value['ctr'].'%')
                ->setCellValue('L'.$i, $value['sumadvpay'])
                ->setCellValue('M'.$i, $value['sumprofit'])
                  ->setCellValue('N'.$i, $value['sumadvpay']-$value['sumprofit']);
            $i++;
        }
        return $objPHPExcel;
    }

    /**
     * 站长导出excel（站长导出的一部分提出）  part
     */
    public function webExcelPart($objPHPExcel,$res,$num_count,$login_user)
    {
        //权限不足的情况下盈利不显示
        if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
            $auth_yl = '跑量盈利';
        }else{
            $auth_yl = ' ';
        }
        $objPHPExcel->getProperties();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getActiveSheet()->getStyle('G2')->getNumberFormat()->setFormatCode("0");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(15);

        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '日期')
            ->setCellValue('B1', '站长id')
            ->setCellValue('C1', '站长名称')
            ->setCellValue('D1', '浏览数')
            ->setCellValue('E1', '点击数')
            ->setCellValue('F1', '独立访客')
            ->setCellValue('G1', '独立IP')
            ->setCellValue('H1', '扣量数')
            ->setCellValue('I1', '结算数')
            ->setCellValue('J1', 'CRT')
            ->setCellValue('K1', '点击率')
            ->setCellValue('L1', '跑量佣金')
            ->setCellValue('M1', '包天价钱')
            ->setCellValue('N1', $auth_yl);
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:N1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {
            if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
                $value['sumprofit'] = $value['sumprofit'];
            }else{
                $value['sumprofit'] = '';
            }
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['day'])
                ->setCellValue('B'.$i, $value['uid'])
                ->setCellValue('C'.$i, !empty($value['username'])?$value['username']:'已删除')
                ->setCellValue('D'.$i, $value['views'])
                ->setCellValue('E'.$i, $value['click_num'])
                ->setCellValue('F'.$i, $value['uv_web'])
                ->setCellValue('G'.$i, $value['ui_web'])
                ->setCellValue('H'.$i, $value['web_deduction'])
                ->setCellValue('I'.$i, $value['web_num'])
                ->setCellValue('J'.$i, $value['crt'].'%')
                ->setCellValue('K'.$i, $value['ctr'].'%')
                ->setCellValue('L'.$i, $value['sumpay'])
                ->setCellValue('M'.$i, $value['cpd'])
                ->setCellValue('N'.$i, $value['sumprofit']);
            $i++;
        }
        return $objPHPExcel;
    }

    /**
     * 广告导出excel（广告导出的一部分提出）  part
     */
    public function adsExcelPart($objPHPExcel,$res,$num_count,$login_user)
    {
        //权限不足的情况下盈利不显示
        if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
            $auth_yl = '跑量盈利';
        }else{
            $auth_yl = ' ';
        }
        $objPHPExcel->getProperties();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getActiveSheet()->getStyle('G2')->getNumberFormat()->setFormatCode("0");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(15);

        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '日期')
            ->setCellValue('B1', '广告名称')
            ->setCellValue('C1', '浏览数')
            ->setCellValue('D1', '点击数')
            ->setCellValue('E1', '下载数')
            ->setCellValue('F1', '扣量数')
            ->setCellValue('G1', '结算数')
            ->setCellValue('H1', 'CRT')
            ->setCellValue('I1', 'CPA')
            ->setCellValue('J1', '点击率')
            ->setCellValue('K1', '应付')
            ->setCellValue('L1', $auth_yl);
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:L1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {
            if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
                $value['sumprofit'] = $value['sumprofit'];
            }else{
                $value['sumprofit'] = '';
            }
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['day'])
                ->setCellValue('B'.$i, !empty($value['adname'])?$value['adname']:'已删除')
                ->setCellValue('C'.$i, $value['views'])
                ->setCellValue('D'.$i, $value['click_num'])
                ->setCellValue('E'.$i, $value['download'])
                ->setCellValue('F'.$i, $value['adv_deduction'])
                ->setCellValue('G'.$i, $value['adv_num'])
                ->setCellValue('H'.$i, $value['crt'].'%')
                ->setCellValue('I'.$i, $value['cpa'].'%')
                ->setCellValue('J'.$i, $value['ctr'].'%')
                ->setCellValue('K'.$i, $value['sumadvpay'])
                ->setCellValue('L'.$i, $value['sumprofit']);
            $i++;
        }
        return $objPHPExcel;
    }

    /**
     * 广告位导出excel（广告位导出的一部分提出）  part
     */
    public function zoneExcelPart($objPHPExcel,$res,$num_count,$login_user)
    {
        //权限不足的情况下盈利不显示
        if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
            $auth_yl = '跑量盈利';
            $num = 15;
        }else{
            $num = 0;
            $auth_yl = ' ';
        }
        $objPHPExcel->getProperties();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getActiveSheet()->getStyle('G2')->getNumberFormat()->setFormatCode("0");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth($num);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('V')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('W')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('X')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Y')->setWidth(15);


        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '日期')
            ->setCellValue('B1', '广告位ID')
            ->setCellValue('C1', '尺寸')
            ->setCellValue('D1', '浏览数')
            ->setCellValue('E1', '点击数')
            ->setCellValue('F1', '独立ip')
            ->setCellValue('G1', '扣量数')
            ->setCellValue('H1', '结算数')
            ->setCellValue('I1', 'CRT')
            ->setCellValue('J1', '点击率')
            ->setCellValue('K1', '实际跑量佣金')
            ->setCellValue('L1', '包天价钱')
            ->setCellValue('M1', $auth_yl)
            ->setCellValue('N1', 'A次数')
            ->setCellValue('O1', 'A独立')
            ->setCellValue('P1', 'B次数')
            ->setCellValue('Q1', 'B独立')
            ->setCellValue('R1', 'C次数')
            ->setCellValue('S1', 'C独立')
            ->setCellValue('T1', 'D次数')
            ->setCellValue('U1', 'D独立')
            ->setCellValue('V1', 'E次数')
            ->setCellValue('W1', 'E独立')
            ->setCellValue('X1', 'F次数')
            ->setCellValue('Y1', 'F独立');
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:L1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {
            if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
                $value['sumprofit'] = $value['sumprofit'];
            }else{
                $value['sumprofit'] = '';
            }
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['day'])
                ->setCellValue('B'.$i, $value['adz_id'])
                ->setCellValue('C'.$i, $value['size'])
                ->setCellValue('D'.$i, $value['views'])
                ->setCellValue('E'.$i, $value['click_num'])
                ->setCellValue('F'.$i, $value['ui_web'])
                ->setCellValue('G'.$i, $value['web_deduction'])
                ->setCellValue('H'.$i, $value['web_num'])
                ->setCellValue('I'.$i, $value['crt'].'%')
                ->setCellValue('J'.$i, $value['ctr'].'%')
                ->setCellValue('K'.$i, $value['sumpay'])
                ->setCellValue('L'.$i, $value['cpd'])
                ->setCellValue('M'.$i, $value['sumprofit'])
                ->setCellValue('N'.$i,empty($value['click_numA'])? 0 :$value['click_numA'])
                ->setCellValue('O'.$i,empty($value['ui_numA'])? 0 :$value['ui_numA'])
                ->setCellValue('P'.$i,empty($value['click_numB'])? 0 :$value['click_numB'])
                ->setCellValue('Q'.$i,empty($value['ui_numB'])? 0 :$value['ui_numB'])
                ->setCellValue('R'.$i,empty($value['click_numC'])? 0 :$value['click_numC'])
                ->setCellValue('S'.$i,empty($value['ui_numC'])? 0 :$value['ui_numC'])
                ->setCellValue('T'.$i,empty($value['click_numD'])? 0 :$value['click_numD'])
                ->setCellValue('U'.$i,empty($value['ui_numD'])? 0 :$value['ui_numD'])
                ->setCellValue('V'.$i,empty($value['click_numE'])? 0 :$value['click_numE'])
                ->setCellValue('W'.$i,empty($value['ui_numE'])? 0 :$value['ui_numE'])
                ->setCellValue('X'.$i,empty($value['click_numF'])? 0 :$value['click_numF'])
                ->setCellValue('Y'.$i,empty($value['ui_numF'])? 0 :$value['ui_numF']);
            $i++;
        }
        return $objPHPExcel;
    }

    /**
     * 广告位导出excel（广告位导出的一部分提出）  part
     */
    public function zoneDomainExcel($objPHPExcel,$res,$num_count)
    {
        $objPHPExcel->getProperties();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");

        $objPHPExcel->getActiveSheet()->getStyle('A')->getNumberFormat()->setFormatCode("0");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);

        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '广告位id')
            ->setCellValue('B1', '受访域名前十')
            ->setCellValue('C1', '次数')
            ->setCellValue('D1', '受访时间');

        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:D1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {

            foreach($value as $k => $v){
                //合并单元格
//                $objPHPExcel->getActiveSheet()->mergeCells('A2'.':'.'A'.$i);
                //水平居中
                $objPHPExcel->getActiveSheet()->getStyle('A'.$i)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('C'.$i)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $objPHPExcel->getActiveSheet()->getStyle('A'.$i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$i, $v['adz_id'])
                    ->setCellValue('B'.$i, $v['siteurl'])
                    ->setCellValue('C'.$i, $v['num'])
                    ->setCellValue('D'.$i, $v['day']);
                $i++;
            }
        }

        return $objPHPExcel;
    }

    /**
     * 广告商导出excel（广告商导出的一部分提出）  part
     */
    public function advExcelPart($objPHPExcel,$res,$num_count,$login_user)
    {
        //权限不足的情况下盈利不显示
        if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
            $auth_yl = '跑量盈利';
        }else{
            $auth_yl = ' ';
        }
        $objPHPExcel->getProperties();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getActiveSheet()->getStyle('G2')->getNumberFormat()->setFormatCode("0");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
//        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(15);

        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '日期')
            ->setCellValue('B1', '广告商名称')
            ->setCellValue('C1', '浏览数')
            ->setCellValue('D1', '点击数')
            ->setCellValue('E1', '下载数')
            ->setCellValue('F1', '扣量数')
            ->setCellValue('G1', '结算数')
            ->setCellValue('H1', 'CRT')
//            ->setCellValue('I1', 'CPA')
            ->setCellValue('I1', '点击率')
            ->setCellValue('J1', '应付')
            ->setCellValue('K1', $auth_yl);
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:K1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {
            if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
                $value['sumprofit'] = $value['sumprofit'];
            }else{
                $value['sumprofit'] = '';
            }
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['day'])
                ->setCellValue('B'.$i, !empty($value['username'])?$value['username']:'已删除')
                ->setCellValue('C'.$i, $value['views'])
                ->setCellValue('D'.$i, $value['click_num'])
                ->setCellValue('E'.$i, $value['download'])
                ->setCellValue('F'.$i, $value['adv_deduction'])
                ->setCellValue('G'.$i, $value['adv_num'])
                ->setCellValue('H'.$i, $value['crt'].'%')
//                ->setCellValue('I'.$i, $value['cpa'])
                ->setCellValue('I'.$i, $value['ctr'].'%')
                ->setCellValue('J'.$i, $value['sumadvpay'])
                ->setCellValue('K'.$i, $value['sumprofit']);
            $i++;
        }
        return $objPHPExcel;
    }

    /**
     * 网站导出excel（网站导出的一部分提出）  part
     */
    public function siteExcelPart($objPHPExcel,$res,$num_count,$login_user)
    {
        //权限不足的情况下盈利不显示
        if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
            $auth_yl = '跑量盈利';
        }else{
            $auth_yl = ' ';
        }
        $objPHPExcel->getProperties();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getActiveSheet()->getStyle('G2')->getNumberFormat()->setFormatCode("0");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);


        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '日期')
            ->setCellValue('B1', '网站名称')
            ->setCellValue('C1', '浏览数')
            ->setCellValue('D1', '点击数')
            ->setCellValue('E1', '扣量数')
            ->setCellValue('F1', '结算数')
            ->setCellValue('G1', 'CRT')
            ->setCellValue('H1', '点击率')
            ->setCellValue('I1', '跑量佣金')
            ->setCellValue('J1', $auth_yl);
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:J1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {
            if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
                $value['sumprofit'] = $value['sumprofit'];
            }else{
                $value['sumprofit'] = '';
            }
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['day'])
                ->setCellValue('B'.$i, !empty($value['sitename'])?$value['sitename']:'已删除')
                ->setCellValue('C'.$i, $value['views'])
                ->setCellValue('D'.$i, $value['click_num'])
                ->setCellValue('E'.$i, $value['web_deduction'])
                ->setCellValue('F'.$i, $value['web_num'])
                ->setCellValue('G'.$i, $value['crt'].'%')
                ->setCellValue('H'.$i, $value['ctr'].'%')
                ->setCellValue('I'.$i, $value['sumpay'])
                ->setCellValue('J'.$i, $value['sumprofit']);
            $i++;
        }
        return $objPHPExcel;
    }

    /**
     * 网站分类导出excel（网站分类导出的一部分提出）  part
     */
    public function classExcelPart($objPHPExcel,$res,$num_count,$login_user)
    {
        //权限不足的情况下盈利不显示
        if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
            $auth_yl = '跑量盈利';
        }else{
            $auth_yl = ' ';
        }
        $objPHPExcel->getProperties();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getActiveSheet()->getStyle('G2')->getNumberFormat()->setFormatCode("0");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);


        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '日期')
            ->setCellValue('B1', '网站分类名称')
            ->setCellValue('C1', '浏览数')
            ->setCellValue('D1', '点击数')
            ->setCellValue('E1', '扣量数')
            ->setCellValue('F1', '结算数')
            ->setCellValue('G1', 'CRT')
            ->setCellValue('H1', '点击率')
            ->setCellValue('I1', '跑量佣金')
            ->setCellValue('J1', $auth_yl);
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:J1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {
            if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
                $value['sumprofit'] = $value['sumprofit'];
            }else{
                $value['sumprofit'] = '';
            }
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['day'])
                ->setCellValue('B'.$i, !empty($value['class_name'])?$value['class_name']:'已删除')
                ->setCellValue('C'.$i, $value['views'])
                ->setCellValue('D'.$i, $value['click_num'])
                ->setCellValue('E'.$i, $value['web_deduction'])
                ->setCellValue('F'.$i, $value['web_num'])
                ->setCellValue('G'.$i, $value['crt'].'%')
                ->setCellValue('H'.$i, $value['ctr'].'%')
                ->setCellValue('I'.$i, $value['sumpay'])
                ->setCellValue('J'.$i, $value['sumprofit']);
            $i++;
        }
        return $objPHPExcel;
    }

    /**
     * 游戏推广计划报表
     */
    public function gameReport()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param();

        if(($request->isPost()) || (!empty($pageParam['time'])) || (!empty($pageParam['type'])) || (!empty($pageParam['sort']))){
            //数据报表页面检索后的条件数据
            $params = $this->_getEnd($pageParam);
        }else{
            //数据报表页面准备初期查询的条件数据
            $params = $this->_getPlanFront($pageParam);
        }
        //处理广告位报表二次点击排序后跳转防报错
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' || $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = 'ctime';
        }
        $day = date("Y-m-d");
        $today = $day.$day;
        //处理时间插件
        $timeValue = $this->_getTime($day,$params);
        // $startday = strtotime($timeValue['startday']);
        // $endday = strtotime($timeValue['endday']);

        // $resdate = ($endday-$startday)/86400;
        if($params['time'] == $today){
            $this->getgamePlanStatsLog($params,$pageParam,$timeValue,$request);
        }else{
            if(empty($params['numid'])&&empty($params['mobile'])&&empty($params['type'])){
                $this->gamestatsforplandate($params,$pageParam,$timeValue,$request);
            }else{

                //分页功能下查询当前页的数据
                $total = Loader::model('Stats')->gameplanLstCount($params);
                $Page = new \org\PageUtil($total,$pageParam);
                $data['show'] = $Page->show($request->action(),$pageParam);
                $totalNum = Loader::model('Stats')->gameplanTotal($params);

                //加上扣量后处理数据
                $totalNum = $this->_getnumber($totalNum);
                //计算CRT并且加上独立IP数
                $totalNum = $this->_getCRT($totalNum,$params);
                //当查询条件包含今天数据时   拼接今天的数据
                if($params['day1'] == date("Y-m-d")){
                    $params['day'] = $params['day1'];
                    $todayNum = Loader::model('Stats')->gameplanToStatsLog($params);
                    $todayNum = $this->_getCrtForPlan($todayNum);
                }else{
                    $todayNum = array();
                }
                $totalRes = array_merge($todayNum,$totalNum);
                //排序
                if($params['sort'] == 'ctime'){
                    $totalRes = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
                }else{
                    $totalRes = $this->_dataSort($totalRes,$params['sort']);
                }
                unset($totalNum);unset($todayNum);
                //合并后分页
                $total = count($totalRes);
                $Page = new \org\PageUtil($total,$pageParam);
                $data['show'] = $Page->show($request->action(),$pageParam);

                $data['res'] = array_slice($totalRes,$Page->firstRow,$Page->listRows);
                //汇总
                $data['data'] = $this->_dataTotal($totalRes,$params,$timeValue);

                //将查询到的数据传到前台
                $this->assign('data',$data);
                unset($timeValue);unset($totalNum);unset($data);
            }
        }
        return $this->fetch('game-report');
    }

    //游戏推广报表今日数据
    public function getgamePlanStatsLog($params,$pageParam,$timeValue,$request)
    {
        //分页功能下查询当前页的数据
        $totalNum = Loader::model('Stats')->gameplanToStatsLog($params);
        $total = count($totalNum);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        //每条的
        $totalRes = $this->_getCrtForPlan($totalNum);
        //汇总的计算CRT并且加上独立IP数
        $totalResCrt = $this->_getLstCRTForPlan($totalNum);
        //排序
        if($params['sort'] == 'ctime'){
            $totalLst = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalLst = $this->_dataSort($totalRes,$params['sort']);
        }
        //单条
        $data['res'] = array_slice($totalLst,$Page->firstRow,$Page->listRows);
        //汇总
        $data['data'] = $this->_dataTotalForPlan($totalResCrt,$params,$timeValue);
        unset($totalResCrt);unset($totalRes);
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($timeValue);unset($totalLst);unset($data);
    }


    public function gamestatsforplandate($params,$pageParam,$timeValue,$request)
    {

        //分页功能下查询当前页的数据
        $total = Loader::model('Stats')->gameplanLstCount($params);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        $totalNum = Loader::model('Stats')->gameplanTotal($params);
        //当查询条件包含今天数据时   拼接今天的数据
        if($params['day1'] == date("Y-m-d")){
            $params['day'] = $params['day1'];
            $todayNum = Loader::model('Stats')->gameplanToStatsLog($params);
            //合并今天数据和之前的数据
            $totalNum = array_merge($totalNum,$todayNum);
            //分页
            $total = count($totalNum);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
        }
        //每条的
        $totalRes = $this->_getCrtForPlan($totalNum);

        //汇总的计算CRT并且加上独立IP数
        $totalResCrt = $this->_getLstCRTForPlan($totalNum);

        // dump($totalResCrt);
        // dump($params);
        // exit;

        //排序
        if($params['sort'] == 'ctime'){
            $totalLst = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalLst = $this->_dataSort($totalRes,$params['sort']);
        }
        // dump($totalNum);
        // exit;

        //单条
        $data['res'] = array_slice($totalLst,$Page->firstRow,$Page->listRows);
        //汇总
        $data['data'] = $this->_dataTotalForPlan($totalResCrt,$params,$timeValue);

        unset($totalResCrt);
        unset($totalRes);
        // dump($data);exit;
        //将查询到的数据传到前台
        $this->assign('data',$data);
        unset($timeValue);unset($totalLst);unset($data);
        // echo memory_get_usage(), '<br />';
    }

    /**
     * 计划报表导出excel  游戏推广
     */
    public function gameExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();

        //数据报表页面检索后的条件数据
        $params = $this->_getEnd($pageParam);

        //处理时间插件
        $timeValue = $this->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)

        $totalNum = Loader::model('Stats')->gameplanTotal($params);

        //每条的
        $totalRes = $this->_getCrtForPlan($totalNum);
        //汇总的计算CRT并且加上独立IP数
        $totalResCrt = $this->_getLstCRTForPlan($totalNum);
        //排序
        if($params['sort'] == 'ctime'){
            $totalLst = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalLst = $this->_dataSort($totalRes,$params['sort']);
        }

        foreach ($totalLst as $key => $value) {
            switch ($value['run_terminal']) {
                case 0:
                    $totalLst[$key]['run_terminal']='不限';
                    break;
                case 1:
                    $totalLst[$key]['run_terminal']='桌面';
                    break;
                case 2:
                    $totalLst[$key]['run_terminal']='IOS';
                    break;
                case 3:
                    $totalLst[$key]['run_terminal']='Android';
                    break;
                case 4:
                    $totalLst[$key]['run_terminal']='微软WP';
                    break;
                default:
                    $totalLst[$key]['run_terminal']='不限';
                    break;
            }
        }
        $this->excel($totalLst,$params);
    }

    /**
     * 广告位分类报表
     */
    public function adzClassReport()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param();
        if(($request->isPost()) || (!empty($pageParam['time'])) || (!empty($pageParam['type'])) || (!empty($pageParam['sort']))){
            //数据报表页面检索后的条件数据
            $params = $this->_getEnd($pageParam);
        }else{
            //数据报表页面准备初期查询的条件数据
            $params = $this->_getAdzFront($pageParam);
        }
        $day= date("Y-m-d");
        //处理时间插件
        $timeValue = $this->_getTime(date("Y-m-d"),$params);

        //处理广告位报表二次点击排序后跳转防报错
        if($params['sort'] == 'sortA' || $params['sort'] == 'sortB' || $params['sort'] == 'sortC' || $params['sort'] == 'sortD' || $params['sort'] == 'sortE' ||$params['sort'] == 'sortF'){
            $params['sort'] = 'ctime';
        }

        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //所有时间段数据
            $totalNum = Loader::model('Stats')->adzClasAllTimeTotal($params);
        }else{
            //查询广告位数据
            $totalNum = Loader::model('Stats')->adzClassList($params);
        }
        if($params['day1'] == $day && $params['time'] != $day.$day){
            $params['time'] = $day.$day;
            $todayNum = Loader::model('Stats')->adzClassList($params);
            $params['time'] = $params['day'].$params['day1'];
        }else{
            $todayNum = array();
        }
        $totalRes = array_merge($totalNum,$todayNum);
        //得到广告位分类数据  并计算crt ctr
        $totalRes = $this->_getCrtForSiteClass($totalRes);
        unset($totalNum);
        //得到页码
        $total = count($totalRes);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);

        //得到广告为分类汇总数据 并计算汇总crt
        $adzClassForMerge = $this->_getAdzClassMerge($totalRes);
        //防止adv排序跳web报错
        if($params['sort'] =='adv_deduction'){
            $params['sort'] = 'web_deduction';
        }elseif($params['sort'] =='adv_num'){
            $params['sort'] = 'web_num';
        }
        //数据报表页面排序优化
        $totalRes = $this->_viewDaySort($totalRes);
        //排序
        if($params['sort'] == 'ctime'){
            $totalres = $this->_dataSort($totalRes,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalres = $this->_dataSort($totalRes,$params['sort']);
        }

        //分页
        $data['res'] = array_slice($totalres,$Page->firstRow,$Page->listRows);
        //汇总 合并数据
        $data['data'] = array_merge($adzClassForMerge,$params,$timeValue);
        unset($adzClassForMerge);unset($totalres);
        //将查询到的数据传到前台
        $this->assign('params',$params);
        $this->assign('data',$data);
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        unset($data);
        //判断当前登录的用户
        $session = Session::get();
        if (!empty($session['status'])) {
            $res = Loader::model('Index')->getUname($session);
            if($res['title'] == '媒介主管' || $res['title'] == '专用714'){
                return $this->fetch('adzclass-medreport');
            }else{
                return $this->fetch('adzclass-report');
            }
        }
    }

    /**
     * 得到广告位分类数据
     */
    private function _classList($totalNum)
    {
        $array =array();
        foreach ($totalNum as $key => $value) {
            $name = $value['class_id'].$value['day'];
            if(empty($array[$name])){
                $array[$name]['views'] = 0;
                $array[$name]['web_num'] = 0;
                $array[$name]['click_num'] = 0;
                $array[$name]['web_deduction'] = 0;
                $array[$name]['sumprofit'] = 0;
                $array[$name]['sumpay'] = 0;
                $array[$name]['sumadvpay'] = 0;
                $array[$name]['day'] = 0;
                $array[$name]['class_id'] = 0;
                $array[$name]['class_name'] = 0;
            }
            $array[$name]['views'] = $array[$name]['views'] + $value['views'];
            $array[$name]['web_num'] = $array[$name]['web_num'] + $value['web_num'];
            $array[$name]['click_num'] = $array[$name]['click_num'] + $value['click_num'];
            $array[$name]['web_deduction'] = $array[$name]['web_deduction'] + $value['web_deduction'];
            $array[$name]['sumprofit'] = $array[$name]['sumprofit'] + $value['sumprofit'];
            $array[$name]['sumpay'] = $array[$name]['sumpay'] + $value['sumpay'];
            $array[$name]['sumadvpay'] = $array[$name]['sumadvpay'] + $value['sumadvpay'];
            $array[$name]['day'] = $value['day'];
            $array[$name]['class_id'] = $value['class_id'];
            $array[$name]['class_name'] = $value['class_name'];
        }
        //计算广告位分类crt
        foreach ($array as $key => $value) {
            if(!empty($value['views'])){
                $array[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                $array[$key]['crt'] = round($value['web_num']/$value['views']*100,2);
            }else{
                $array[$key]['crt'] = '0';
                $array[$key]['ctr'] = '0';
            }
        }
        return $array;
    }

    /**
     * 计算广告位分类汇总数据
     */
    private function _getAdzClassMerge($totalNum)
    {
        //初始化
        $res = array(
            'views' => 0,
            'web_num' => 0,
            'click_num' => 0,
            'web_deduction' => 0,
            'sumprofit' => 0,
            'sumpay' => 0,
            'sumadvpay' => 0,
            'crt' => 0,
            'ctr' => 0,
        );
        //得到汇总数据
        foreach ($totalNum as $key => $value) {
            $res['views'] += $value['views'];
            $res['web_num'] += $value['web_num'];
            $res['click_num'] += $value['click_num'];
            $res['web_deduction'] += $value['web_deduction'];
            $res['sumprofit'] += $value['sumprofit'];
            $res['sumpay'] += $value['sumpay'];
            $res['sumadvpay'] += $value['sumadvpay'];
        }
        //计算汇总后的crt
        if(!empty($res['views'])){
            $res['ctr'] = round($res['click_num']/$res['views']*100,2);
            $res['crt'] = round($res['web_num']/$res['views']*100,2);
        }else{
            $res['crt'] = 0;
            $res['ctr'] = 0;
        }
        return $res;
    }

    /**
     * 广告位分类报表导出 excel 导出
     */
    public function adzClassExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();
        $params = $this->_getEnd($pageParam);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)
        if($params['day'] == "all"){
            //所有时间段数据
            $totalNum = Loader::model('Stats')->adzClasAllTimeTotal($params);
        }else{
            //查询广告位数据
            $totalNum = Loader::model('Stats')->adzClassList($params);
        }
        $day= date("Y-m-d");
        if($params['day1'] == $day && $params['time'] != $day.$day){
            $params['time'] = $day.$day;
            $todayNum = Loader::model('Stats')->adzClassList($params);
            $params['time'] = $params['day'].$params['day1'];
        }else{
            $todayNum = array();
        }
        $totalNum = array_merge($totalNum,$todayNum);
        //得到广告位分类数据  并计算crt ctr
        $totalNum = $this->_getCrtForSiteClass($totalNum);
        //排序
        if($params['sort'] == 'ctime'){
            $totalres = $this->_dataSort($totalNum,$params['sort'],$sort_order=SORT_ASC );
        }else{
            $totalres = $this->_dataSort($totalNum,$params['sort']);
        }
        $this->excel($totalres,$params);
    }

    /**
     * 广告位分类导出excel（网站分类导出的一部分提出）  part
     */
    public function adzClasslassExcelPart($objPHPExcel,$res,$num_count,$login_user)
    {
        //权限不足的情况下盈利不显示
        if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
            $auth_yl = '跑量盈利';
        }else{
            $auth_yl = ' ';
        }
        $objPHPExcel->getProperties();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getActiveSheet()->getStyle('G2')->getNumberFormat()->setFormatCode("0");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);


        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '日期')
            ->setCellValue('B1', '网站分类名称')
            ->setCellValue('C1', '浏览数')
            ->setCellValue('D1', '点击数')
            ->setCellValue('E1', '扣量数')
            ->setCellValue('F1', '结算数')
            ->setCellValue('G1', 'CRT')
            ->setCellValue('H1', '点击率')
            ->setCellValue('I1', '跑量佣金')
            ->setCellValue('J1',  $auth_yl);
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:J1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {
            if($login_user=='admin'||$login_user=='yyblizz'||$login_user=='yanfabu'||$login_user=='yfb001'||$login_user=='yunyingbu1'||$login_user=='yunyingbu2'){
                $value['sumprofit'] = $value['sumprofit'];
            }else{
                $value['sumprofit'] = '';
            }
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['day'])
                ->setCellValue('B'.$i, !empty($value['class_name'])?$value['class_name']:'已删除')
                ->setCellValue('C'.$i, $value['views'])
                ->setCellValue('D'.$i, $value['click_num'])
                ->setCellValue('E'.$i, $value['web_deduction'])
                ->setCellValue('F'.$i, $value['web_num'])
                ->setCellValue('G'.$i, $value['crt'].'%')
                ->setCellValue('H'.$i, $value['ctr'].'%')
                ->setCellValue('I'.$i, $value['sumpay'])
                ->setCellValue('J'.$i, $value['sumprofit']);
            $i++;
        }
        return $objPHPExcel;
    }

    /**
     * 导出excel
     */
    public function domainexcel($res,$params)
    {
        $excel = Config::get('excel_url');
        require_once  "".$excel."autoload.php";
        //修改内存
        ini_set('memory_limit','500M');
        //修改时间
        ini_set("max_execution_time", "0");

        //统计数据个数
        $num_count = count($res)+1;
        $objPHPExcel = new \ PHPExcel();

        $objPHPExcel = $this->zoneDomainExcel($objPHPExcel,$res,$num_count);

        $filename='批量导出广告位受访域名报表'.date('Y-m-d'); //设置表的名字+日期

        // 重命名工作表
        $objPHPExcel->getActiveSheet()->setTitle('sheet1');
        // 将活动表索引设置为第一个表，所以将此作为第一张表打开
        $objPHPExcel->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
        // IE 9浏览器设置
        header('Cache-Control: max-age=1');
        // header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // 过去的日期
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        //导出Excel
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    /**
     *  网站报表计算单条的crt
     */
    public function _getCrtForSite($arr)
    {
        foreach ($arr as $key => $value) {
            if(!empty($value['views'])){
                $arr[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                $arr[$key]['crt'] = round($value['web_num']/$value['views']*100,2);
            }else{
                $arr[$key]['ctr'] = 0;
                $arr[$key]['crt'] = 0;
            }
        }
        return $arr;
    }

    /**
     *  网站分类报表计算单条的crt
     */
    public function _getCrtForSiteClass($arr)
    {
        $res = array();
        foreach ($arr as $key => $value) {
            $name = $value['class_name'].'-'.$value['day'];
            $res[$name]['views'] = !isset($res[$name]['views'])?0:$res[$name]['views'];
            $res[$name]['click_num'] = !isset($res[$name]['click_num'])?0:$res[$name]['click_num'];
            $res[$name]['web_deduction'] = !isset($res[$name]['web_deduction'])?0:$res[$name]['web_deduction'];
            $res[$name]['web_num'] = !isset($res[$name]['web_num'])?0:$res[$name]['web_num'];
            $res[$name]['sumadvpay'] = !isset($res[$name]['sumadvpay'])?0:$res[$name]['sumadvpay'];
            $res[$name]['sumprofit'] = !isset($res[$name]['sumprofit'])?0:$res[$name]['sumprofit'];
            $res[$name]['sumpay'] = !isset($res[$name]['sumpay'])?0:$res[$name]['sumpay'];
            $res[$name]['class_name'] = $value['class_name'];
            $res[$name]['class_id'] = $value['class_id'];
            $res[$name]['uid'] = $value['uid'];
            $res[$name]['pid'] = $value['pid'];
            $res[$name]['ad_id'] = $value['ad_id'];
            $res[$name]['adv_id'] = $value['adv_id'];
            $res[$name]['adz_id'] = $value['adz_id'];
            $res[$name]['site_id'] = $value['site_id'];
            $res[$name]['day'] = $value['day'];
            $res[$name]['views'] += $value['views'];
            $res[$name]['click_num'] += $value['click_num'];
            $res[$name]['web_deduction'] += $value['web_deduction'];
            $res[$name]['web_num'] += $value['web_num'];
            $res[$name]['sumadvpay'] += $value['sumadvpay'];
            $res[$name]['sumprofit'] += $value['sumprofit'];
            $res[$name]['sumpay'] += $value['sumpay'];
        }

        foreach ($res as $key => $value) {
            if(!empty($value['views'])){
                $res[$key]['ctr'] = round($value['click_num']/$value['views']*100,2);
                $res[$key]['crt'] = round($value['web_num']/$value['views']*100,2);
            }else{
                $res[$key]['ctr'] = 0;
                $res[$key]['crt'] = 0;
            }
        }
        return $res;
    }

    //网站报表今日数据汇总
    private function _dataForSite($totalRes,$params,$timeValue)
    {
        $mergeWeb = array(
            'views' => 0,
            'click_num' => 0,
            'web_num' => 0,
            'sumpay' => 0,
            'sumprofit' => 0,
            'web_deduction' => 0,
            'cpd' => 0,
            'stats' => $params['stats'],
            'numid' => $params['numid'],
            'time' => $params['time'],
            'type' => $params['type'],
            'sort' => $params['sort'],
            'id' => $params['id']);
        foreach ($totalRes as $key => $value) {
            $mergeWeb['views'] += $value['views'];
            $mergeWeb['click_num'] += $value['click_num'];
            $mergeWeb['web_num'] += $value['web_num'];
            $mergeWeb['sumpay'] += $value['sumpay'];
            $mergeWeb['sumprofit'] += $value['sumprofit'];
            $mergeWeb['web_deduction'] += $value['web_deduction'];
        }
        if(!empty($mergeWeb['views'])){
            $mergeWeb['ctr'] = round($mergeWeb['click_num']/$mergeWeb['views']*100,2);
            $mergeWeb['crt'] = round($mergeWeb['web_num']/$mergeWeb['views']*100,2);
        }
        $res = array_merge($timeValue,$mergeWeb);
        return $res;
    }

    /*
     *  数据报表页面优化排序
     */
    private function _viewDaySort($res)
    {
        $array = array();
        $array_res = array();
        foreach ($res as $key => $value) {
            $array[$value['day']][$key] =  $value;
        }
        foreach($array as $key =>$val){
            foreach ($val as $value) {
                $val_key = $val;
                $array_key[]=$value['views'];
            }
            array_multisort($array_key,SORT_DESC,SORT_NUMERIC,$val_key);
            if(!empty($array_res)){
                $array_res = array_merge($array_res,$val_key);
            }else{
                $array_res = $val_key;
            }
            unset($array_key);
        }
        return $array_res;
    }

}