<?php
/** 广告商客户端
 * date   2016-8-2
 */
namespace app\home\controller;
use app\user\common\Encrypt;
use think\Loader;
use think\Request;
use think\Session;
use think\config;
class Game extends Client
{

/***************************************************我的首页*****************************************************/
    /**
     * 首页
     */
    public function index()
    {
        $request = Request::instance();

        $pageParam = $request->param('');
        //游戏推广员工账号
        $pageParam['game_uid'] = Session::get('gameUid');
//        dump($game_uid);exit;
        $total = Loader::model('Game')->gameplanLstCount($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }
        $res = Loader::model('Game')->gamegetLst($Page->firstRow,$Page->listRows,$pageParam);
//        dump($res);exit;
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }

        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        $this->assign('mobile',$pageParam['mobile']);

        return $this->fetch('game-list');
    }

    /**
     * 游戏推广计划报表
     */
    public function gameReport()
    {
        $request = Request::instance();
        $pageParam = $request->param();
        $game_uid = session::get('gameUid');

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
        $timeValue = $this->_getTime(date("Y-m-d"),$params);
        // $startday = strtotime($timeValue['startday']);
        // $endday = strtotime($timeValue['endday']);
        //处理 adv 搜索 web
        if($params['sort'] =='web_deduction'){
            $params['sort'] = 'adv_deduction';
        }elseif($params['sort'] =='web_num'){
            $params['sort'] = 'adv_num';
        }
        // $resdate = ($endday-$startday)/86400;
        if(empty($params['numid'])&&empty($params['mobile'])&&empty($params['type'])){
            $this->gamestatsforplandate($params,$pageParam,$timeValue,$request,$game_uid);
        }else{

            //分页功能下查询当前页的数据
            $total = Loader::model('Game')->gameLstCount($params,$game_uid);
            $Page = new \org\PageUtil($total,$pageParam);
            $data['show'] = $Page->show($request->action(),$pageParam);
            $totalNum = Loader::model('Game')->gameplanTotal($params,$game_uid);

            //加上扣量后处理数据
            $totalNum = $this-> _getnumber($totalNum);
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
        return $this->fetch('game-report');
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

    public function gamestatsforplandate($params,$pageParam,$timeValue,$request,$game_uid)
    {

        //分页功能下查询当前页的数据
        $total = Loader::model('Game')->gameLstCount($params,$game_uid);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        $totalNum = Loader::model('Game')->gameplanTotal($params,$game_uid);

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
        }

        return $mergeRes;
    }

    /**
     * 数据报表排序
     */
    public function _dataSort($arrays,$sort_key,$sort_order=SORT_DESC,$sort_type=SORT_NUMERIC )
    {
        $sort_key = $sort_key == 'click_num' ? 'ctr' : $sort_key;
        $arrays = is_array($arrays) ? $arrays : $arrays = array();
        foreach($arrays as $key=>$value){
            $arrays[$key]['ctime'] = -strtotime($value['day']);
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
     * 数据报表页面准备检索后的条件数据
     */
    private function _getEnd($pageParam)
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
            'heavy_click_num' => empty($pageParam['heavy_click_num']) ? '': $pageParam['heavy_click_num'],
        );
        return $params;
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
        );
        $data = array_merge($timeValue,$data);
        return $data;
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
            $arr[$value['day']][$name]['ctime'] = $value['ctime'];
            $arr[$value['day']][$name]['views'] += $value['views'];
            $arr[$value['day']][$name]['download'] += $value['download'];
            $arr[$value['day']][$name]['click_num'] += $value['click_num'];
            $arr[$value['day']][$name]['sumprofit'] += $value['sumprofit'];
            $arr[$value['day']][$name]['fact_sumprofit'] += $value['fact_sumprofit'];
            $arr[$value['day']][$name]['sumpay'] += $value['sumpay'];
            $arr[$value['day']][$name]['cpd'] += $value['cpd'];
            $arr[$value['day']][$name]['sumadvpay'] += $value['sumadvpay'];
            $arr[$value['day']][$name]['web_num'] += $value['web_num'];
            $arr[$value['day']][$name]['adv_num'] += $value['adv_num'];
            $arr[$value['day']][$name]['web_deduction'] += $value['web_deduction'];
            $arr[$value['day']][$name]['adv_deduction'] += $value['adv_deduction'];
            $arr[$value['day']][$name]['uv_plan'] += $value['uv_plan'];
            $arr[$value['day']][$name]['uv_web'] += $value['uv_web'];
            $arr[$value['day']][$name]['uv_ads'] += $value['uv_ads'];
            $arr[$value['day']][$name]['uv_adzone'] += $value['uv_adzone'];
            $arr[$value['day']][$name]['ui_plan'] += $value['ui_plan'];
            $arr[$value['day']][$name]['ui_web'] += $value['ui_web'];
            $arr[$value['day']][$name]['ui_ads'] += $value['ui_ads'];
            $arr[$value['day']][$name]['ui_adzone'] = max($adzone_ip[$value['day']][$value['adz_id']]);
            $arr[$value['day']][$name]['heavy_click_num'] = max($num[$value['day']][$value['pid']]);
            $arr[$value['day']][$name]['web_click_num'] = max($web_click_num[$value['day']][$value['uid']]);
            $arr[$value['day']][$name]['adz_click_num'] = max($adz_click_num[$value['day']][$value['adz_id']]);
        }
        return $arr;
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
        }
        //执行数据累加
        foreach($number as $key=>$value){
            $arr['views'] += $value['views'];
            $arr['download'] += $value['download'];
            $arr['web_num'] += $value['web_num'];
            $arr['adv_num'] += $value['adv_num'];
            $arr['click_num'] += $value['click_num'];
            $arr['cpd'] += $value['cpd'];
            $arr['sumprofit'] += $value['sumprofit'];
            $arr['sumpay'] += $value['sumpay'];
            $arr['sumadvpay'] += $value['sumadvpay'];
            $arr['fact_sumprofit'] += $value['fact_sumprofit'];
            $arr['web_deduction'] += $value['web_deduction'];
            $arr['adv_deduction'] += $value['adv_deduction'];
            $arr['uv_plan'] += $value['uv_plan'];
            $arr['uv_web'] += $value['uv_web'];
            $arr['uv_ads'] += $value['uv_ads'];
            $arr['uv_adzone'] += $value['uv_adzone'];
            $arr['ui_plan'] += $value['ui_plan'];
            $arr['ui_web'] += $value['ui_web'];
            $arr['ui_ads'] += $value['ui_ads'];
            $arr['ui_adzone'] += $value['ui_adzone'];
            $arr['web_views_fact'] += empty($value['web_views_fact']) ? 0 : $value['web_views_fact'];

            $arr['heavy_click_num'] += $value['heavy_click_num'];

            $arr['web_click_num'] += $value['web_click_num'];
            $arr['adz_click_num'] += $value['adz_click_num'];
        }
        if($arr){
            $arr['web_views_fact'] = empty($arr['web_views_fact']) ? $arr['views'] : $arr['web_views_fact'];
            //如果汇总数据不为空的情况下计算CRT
            $arr['ctr'] = round($arr['click_num']/$arr['web_views_fact']*100,2);
            if($arr['views'] && ($params['stats'] == 'plan_list' || $params['stats'] == 'ads_list')){
                $arr['crt'] = round($arr['adv_num']/$arr['views']*100,2);
                $arr['cpa'] = round($arr['download']/$arr['views']*100,2);
            }elseif($arr['views'] && ($params['stats'] == 'user_list' || $params['stats'] == 'zone_list' || $params['stats'] == 'user_ads_list')){
                $arr['crt'] = round($arr['web_num']/$arr['views']*100,2);
            }else{
                $arr['crt'] = 0;
            }
        }
        return $arr;
    }

    /**
     * 计划报表导出excel  游戏推广
     */
    public function gameExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param();
        $game_uid = session::get('gameUid');

        //数据报表页面检索后的条件数据
        $params = $this->_getEnd($pageParam);

        //处理时间插件
        $timeValue = $this->_getTime(date("Y-m-d"),$params);
        //查询出数据报表的所有数据(判断是否为所有时间，提高sql的性能)

        $totalNum = Loader::model('Game')->gameplanTotal($params,$game_uid);

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

        //统计数据个数
        $num_count = count($res)+1;
        $objPHPExcel = new \ PHPExcel();


        $objPHPExcel = $this->planExcelPart($objPHPExcel,$res,$num_count);
        $filename='计划报表'.date('Y-m-d'); //设置表的名字+日期

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
    public function planExcelPart($objPHPExcel,$res,$num_count)
    {
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
            ->setCellValue('M1', '  跑量盈利');
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:M1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {

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
                ->setCellValue('M'.$i, $value['sumprofit']);
            $i++;
        }
        return $objPHPExcel;
    }



























































    /**
     * 游戏推广  锁定的计划
     */
    public function gameLock()
    {
        $request = Request::instance();
        $pageParam = $request->param('');

        $total = Loader::model('Game')->gameplanLstCount1($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }

        $res = Loader::model('Game')->gamegetLstOne($Page->firstRow,$Page->listRows,$pageParam);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        $this->assign('mobile',$pageParam['mobile']);

        return $this->fetch('game-plan-lock');
    }

    /**
     * 游戏推广   活动的计划
     */
    public function gameActivity()
    {
        $request = Request::instance();
        $pageParam = $request->param('');

        $total = Loader::model('Game')->gameplanLstCount2($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action());
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }

        $res = Loader::model('Game')->gamegetLstTwo($Page->firstRow,$Page->listRows,$pageParam);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        $this->assign('mobile',$pageParam['mobile']);

        return $this->fetch('game-plan-activity');

    }

    /**
     * 游戏推广   待审的计划
     */
    public function gamePending()
    {
        $request = Request::instance();
        $pageParam = $request->param('');

        $total = Loader::model('Game')->gameplanLstCount3($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action());
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }

        $res = Loader::model('Game')->gamegetLstThree($Page->firstRow,$Page->listRows,$pageParam);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        $this->assign('mobile',$pageParam['mobile']);
        return $this->fetch('game-plan-pending');
    }

    /**
     * 游戏推广   超出限额的计划
     */
    public function gameQuota()
    {
        $request = Request::instance();
        $pageParam = $request->param('');
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }

        $res = Loader::model('Game')->gameallList($pageParam);
        $this->assign('plan_list',$res);
        $this->assign('mobile',$pageParam['mobile']);
        return $this->fetch('game-plan-quota');
    }

    /**
     * 激活/锁定
     */
    public function activate()
    {
        $request = Request::instance();
        $status = $request->post('status');
        $pid = $request->post('pid');
        $activate = $request->post('activate');
        if($activate == 2){
            $this->_reviewed($pid);
        }else{
            $res = Loader::model('Game')->updateStatus($pid,$status);
            if($res>0){
                //判断操作日志的写入内容
                if($status == 1){
                    //写操作日志
                    $this->logWrite('0015',$pid);
                }else{
                    //写操作日志
                    $this->logWrite('0016',$pid);
                }
                //查询该计划下的所有的广告，并且全部激活/锁定
                Loader::model('Game')->updateAdsStatus($pid,$status);
                // $this->_updateAdzone($pid,$status);
                $this->_success();
            }else{
                $this->_error('修改失败');
            }
        }
    }

    /**
     * 更新游戏推广计划总限额表次数
     */
    public function gameUpdate()
    {
        $request = Request::instance();
        $params = $request->param();
        //获取当前计划的内容
        $plan_res = Loader::model('Game')->planCopy($params['pid']);

        if($plan_res['status'] != '1'){
            //当前次数 加 1
            $params['num'] = $params['num']+1;
            $num = Loader::model('Game')->gameUpdateNum($params);
        }else{
            $num = '';
        }


        return $num;
    }

    /**
     * ajax审核返回
     * param $data array  数据数组
     * param $info string 成功返回的字符串
     * return json
     */
    private function _reviewed($datas = array(),$info='success'){
        $data['status']  = 2;
        $data['data'] = $datas;
        $data['info'] = $info;
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }




































    /**
     *   账户设置
     */
    public function accountEdit()
    {
        $uid = Session::get('advertiserUid');
        $res = Loader::model('Advertiser')->getBasic($uid);

        $this->assign('one',$res);
        return $this->fetch('account');
    }

    /**
     *     修改账号信息
     */
    public function edit()
    {
        $params =Request::instance()->post();
        $uid = $params['uid'];
        //组装 修改的字段
        $data=array(
            'mobile'  =>$params['mobile'],
            'qq'      =>$params['qq'],
            'email'   =>$params['email'],
            'tel'     =>$params['tel'],
            'idcard'  =>$params['idcard'],
        );
        //验证数据
        $validate = Loader::validate('Users');
        if(!$validate->check($data)){
            $this->error($validate->getError());
        }

        $res = Loader::model('Advertiser')->accountEdit($uid,$data);
        if($res >=0){
            $this->redirect('accountEdit');
        }else{
            $this->error('error');
        }
    }

    /**
     *   修改账号密码
     */
    public function passEdit(){
        $params = Request::instance()->post();
        $uid = $params['uid'];
        if(!in_array('',$params)){
            // 查询原始密码
            $res = Loader::model('Advertiser')->getPwd($uid);
            //用户输入的密码加密
            $Encrypt = new Encrypt();
            $password = $Encrypt->fb_ucenter_encrypt($params['password']);
            //判断新密码和原始密码是否一致
            if($params['new_password'] != $params['password']){
                //判断 用户输入的2次新密码是否一致
                if($params['new_password'] == $params['new_password_1']){
                    //判断 用户输入的原始密码是否正确
                    if($password == $res['password']){
                        $new_password = $Encrypt->fb_ucenter_encrypt($params['new_password']);
                        $data = array(
                            'password'=>$new_password,
                        );
                        $update = Loader::model('Advertiser')->passEdit($uid,$data);
                        if($update >= 0){
                            $this->_success(array(),'修改成功');
                        }else{
                            $this->_error('修改失败');
                        }
                    }else{
                        $this->_error('原密码输入错误');
                    }
                }else{
                    $this->_error('新密码不一致');
                }
            }else{
                $this->_error('新密码和原始密码一致');
            }
        }else{
            $this->_error('密码不能为空');
        }
    }


}
