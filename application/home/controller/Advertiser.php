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
class Advertiser extends Client
{

/***************************************************我的首页*****************************************************/
    /**
     * 首页
     */
    public function homePage()
    {
        //判断登录的入口
        $pageParam = Request::instance()->param();
        //获取当天报告
        $params['uid'] = Session::get('advertiserUid');
        $params['day'] = date("Y-m-d");
        //处理快捷报告所需的数据
        $params['startday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 0,10);
        $params['endday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 10,20);
        $date['time']  = $this->_getTime(date("Y-m-d"),$params);

        $res = Loader::model('Advertiser')->reportNow($params);

        //获取昨日支付数据
        $params['yesterday'] = date("Y-m-d",strtotime("-1 day"));
        $yesterday = Loader::model('Advertiser')->getYesMoney($params);
        //获取当月支付数据
        $params['firstday'] = date("Y-m-01");
        $month = Loader::model('Advertiser')->getMonthMoney($params);
        //获取账户余额
        $money = Loader::model('Advertiser')->advMoney($params['uid']);
        //获取广告商余额提醒
        $balance = Loader::model('Advertiser')->getSetAdv();

        //统计广告商当日支付，昨日支付，当月支付，余额
        $date['total'] = $this->_getTotal($res,$yesterday,$month,$money);
        //将当日报告下的同一类型的数据合并
        $date['report'] = $this->_getReport($res);

        //将统计后的值传到前台
        $this->assign('homepage',$date);
        //将广告商的余额提醒传值到前台
        $this->assign('adv_money',$balance);

        return $this->fetch('homepage');
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

    /**
     * 我的首页审批申请
     */
    public function apply()
    {
        return $this->fetch('apply');
    }

    /**
     * 充值记录
     */
    public function recharge()
    {
        $request = Request::instance();
        $params['uid'] = Session::get('advertiserUid');
        $params['day'] = date("Y-m-d");
        $res = Loader::model('Advertiser')->rechargeLog($params);
        //获取账户余额
        $advStats = Loader::model('Advertiser')->advStats($params['uid'],$params['day']);
        foreach ($res as $key => $value) {
            $res[$key]['money'] =  floor(($value['money']-$advStats['sumadvpay'])*100)/100;
        }
        $this->assign('recharge',$res);
        return $this->fetch('recharge');
    }
    

    /**
     * 消息中心
     */
    public function message()
    {
        return $this->fetch('message');
    }


/***************************************************计划管理*****************************************************/
    /**
     * 计划管理中的计划列表
     */
    public function planlist()
    {
        $Request = Request::instance();
        $param['type'] = $Request->param('type');
        //获取该广告商的id，查询属于该广告商的计划列表
        $param['uid'] = session::get('advertiserUid');

        //分页功能
        $total = Loader::model('Advertiser')->planLstCount($param);
        $pageParam = $Request->param('');
        $Page = new \org\PageUtil($total,$pageParam);
        $date['show'] = $Page->show($Request->action());

        //查询当页的所有数据
        $date['res'] = Loader::model('Advertiser')->getPlanLst($Page->firstRow,$Page->listRows,$param);

        //查询所有的数据
        $res = Loader::model('Advertiser')->getPlanAll($param);
        //处理计划类型，合并相同的计划类型
        $date['type'] = $this->_getType($res);

        $this->assign('plan_list',$date);

        return $this->fetch('plan-list');
    }

    /**
     * 新建计划
     */
    public function planAdd()
    {
        $Request = Request::instance();
        if($Request->isPost()){
            $params = $Request->post();
            //组装参数
            $data = $this->_dataForAdd($params);

            //验证数据
            $validate = Loader::validate('Plan');
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }

            $res = Loader::model('Advertiser')->add($data);

            if($res>0){  //保存成功
                $this->redirect('planlist',['cmd_flag' => 'add']);
            }else{
                $this->_error();
            }
        }else{
            //读取广告商
            $array = Loader::model('Advertiser')->getSetting();
            //获取网站与计划类型
            $classLst = $this->_classType();
            $this->assign('class_list',$classLst);
            $this->assign('one',$array);
            return $this->fetch('plan-add');
        }
    }

    /**
     * 计划编辑
     */
    public function planEdit()
    {
        $Request = Request::instance();
        if($Request->isPost()){
            $params = $Request->post();
            //组装参数
            $data = $this->_dataForAdd($params);
            //验证数据
            $validate = Loader::validate('Plan');
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $res = Loader::model('Advertiser')->editOne($data,$params['pid']);
            if($res>=0){
                $pms = array(
                    'pid' => $params['pid'],
                    'cmd_flag' => 1,
                );
                $this->redirect('Advertiser/planEdit',$pms);
            }else{
                $this->error('error');
            }
        }else{
            $param = $Request->param('');

            $res = Loader::model('Advertiser')->getOne($param['pid']);
            if(!empty($res)){
                //编辑定向处理
                $res = $this->_doEditData($res[0]);
                //获取网站与计划类型
                $classLst = $this->_classType();
                //判断是初始编辑页面，还是提交编辑成功后的编辑页面
                $res['cmd_flag'] = empty($param['cmd_flag']) ? '0':'1';

                $this->assign('class_list',$classLst);
                $this->assign('one',$res);
                return $this->fetch('plan-edit');
            }else{
                $this->redirect('Advertiser/planAdd');
            }
        }
    }

/***************************************************广告管理*****************************************************/
    /**
     * 广告管理中的广告列表
     */
    public function adslist()
    {
        $Request = Request::instance();
        $param['pname'] = $Request->param('pname');
        $param['uid'] = session::get('advertiserUid');
        //广告分类列表
        $pageParam = $Request->param('');
        $total = Loader::model('Advertiser')->adLstCount($param);
        $Page = new \org\PageUtil($total,$pageParam);
        $date['show'] = $Page->show($Request->action());

        //查询当月所有数据
        $date['res'] = Loader::model('Advertiser')->adLst($Page->firstRow,$Page->listRows,$param);
        $date['img'] = Loader::model('Advertiser')->getImgService();
        $date['img'] = empty($date['img']['img_server']) ? array('img_server' => '/') : $date['img'];

        //查询所有的计划名称
        $res = Loader::model('Advertiser')->getAdsAll($param);
        //处理计划名称，合并相同的计划名称
        $date['pname'] = $this->_getPname($res);

        $this->assign('ads_list',$date);

        return $this->fetch('ads-list');
    }

    /**
     * 计划下查看广告
     */
    public function planToAds()
    {
        $param['pid'] = Request::instance()->get('pid');
        $param['uid'] = session::get('advertiserUid');
        $date['res'] = Loader::model('Advertiser')->adPlanLst($param);

        //查询所有的计划名称
        $res = Loader::model('Advertiser')->getAdsAll($param);
        //处理计划名称，合并相同的计划名称
        $date['pname'] = $this->_getPname($res);

        $this->assign('ads_list',$date);
        return $this->fetch('ads-list');
    }

    /**
     * 新建广告展示
     */
    public function adsAdd()
    {
        // 获取计划
        $uid = session::get('advertiserUid');
        $res = Loader::model('Advertiser')->getAll($uid);

        $adsRes = Loader::model('Advertiser')->adsTypeLst();
        $arr = array();
        foreach ($adsRes as $key => $value) {
            $arr[$value['type_name']][] = $value;
        }
        //广告类型
        $this->assign('adtype_list',$arr);

        //处理
        $newRes = $this->_doPlanType($res);
        $this->assign('ptype_list',$newRes);

        return $this->fetch('ads-add');
    }

    /**
     * 新建广告
     */
    public function doAdd()
    {
        $Request = Request::instance();
        $params = $Request->post();

        if(!empty($params['specs'])){
            $specs = explode('*', $params['specs']);
            $params['width'] = $specs[0];
            $params['height'] = $specs[1];
        }
        //上传文件
        $file = $Request->file('files');
        $params['imageurl'] = $this->_upfile($file);
        //验证数据
        $validate = Loader::validate('Ads');
        if(!$validate->check($params)){
            $this->error($validate->getError());
        }


        $res = Loader::model('Advertiser')->addOne($params);
        if($res>0){  //保存成功
            $this->redirect('adslist',['cmd_flag' => 'add']);
        }else{
            $this->_error();
        }
    }

    /**
     * 广告编辑
     */
    public function adsEdit()
    {
        $Request = Request::instance();
        if($Request->isPost()){
            $params = $Request->post();
            $data = array(
                'adname' => $params['ad_name'],
                'url'  => $params['url'],
                'status' => '2',
            );
            //上传文件
            $file = $Request->file('files');
            if(!empty($file)){
                $data['imageurl'] = $this->_upfile($file);
            }
            $res = Loader::model('Advertiser')->edit($data,$params['hid_aid']);

            if($res>=0){
                $pms = array(
                    'aid' => $params['hid_aid'],
                    'cmd_flag' => 1,
                );
                $this->redirect('Advertiser/adsEdit',$pms);
            }else{
                $this->error('error');
            }
        }else{
            $param = $Request->param('');
            $res = Loader::model('Advertiser')->getAdsOne($param['aid']);
            if(!empty($res)){
                $res['adtype'] = Loader::model('Advertiser')->adsTypeOne($res['tpl_id']);
                //判断是初始编辑页面，还是提交编辑成功后的编辑页面
                $res['cmd_flag'] = empty($param['cmd_flag']) ? '0':'1';
                $this->assign('one',$res);
                return $this->fetch('ads-edit');
            }else{
                $this->redirect('Advertiser/adsAdd');
            }
        }
    }

    /**
     * select改变广告类型
     */
    public function changeAdtpl()
    {
        $tpl_id = Request::instance()->post('tpl_id');
        $tplRes = Loader::model('Advertiser')->getAdHtml($tpl_id);
        if(!empty($tplRes)){
            $tplRes['htmlcontrol'] = unserialize($tplRes['htmlcontrol']);
            $tplRes['specs'] = unserialize($tplRes['specs']);
            $this->_success($tplRes);
        }else{
            $this->_error();
        }
    }

    /**
     * select改变计划
     */
    public function changePlan()
    {
        $ptype = Request::instance()->post('ptype');
        $planRes = Loader::model('Advertiser')->adsTypeLst();
        $new = array();
        $i = 0;
        foreach ($planRes as $key => $value) {
            if(false !== stripos($value['stats_type'], $ptype)){
                $new[$i]['tpl_id'] = $value['tpl_id'];
                $new[$i]['tplname'] = $value['tplname'];
                $new[$i]['type_name'] = $value['type_name'];
                $i++;
            }
        }
        $arr = array();
        foreach ($new as $key => $value) {
            $arr[$value['type_name']][] = $value;
        }
        if(!empty($arr)){
            $this->_success($arr);
        }else{
            $this->_error();
        }
    }
/***************************************************效果报告*****************************************************/
    /**
     * 综合报告
     */
    public function summaryReport()
    {
        $pageParam = Request::instance()->param('');
        $params['uid'] = session::get('advertiserUid');
        $params['startday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 0,10);
        $params['endday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 10,20);

        //当搜索开始时间不是今天，结束日期是今天的，需要同时查询log和new表，拼接
        if(($params['startday'] != date("Y-m-d")) && ($params['endday'] == date("Y-m-d"))){
            //今日数据查询log表，以前数据查询new表
            $todaydata = Loader::model('Advertiser')->getTodayPerformance($params);
            //new表无今天数据
            $params['endday'] = date("Y-m-d",strtotime("-1 day"));
            $befordata = Loader::model('Advertiser')->getPerformance($params);
            $res = array_merge($todaydata,$befordata);
        }else{
            $res = Loader::model('Advertiser')->getPerformance($params);
        }
        //执行完sql后将endday还原，不影响页面显示
        $params['endday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 10,20);

        //统计综合报告的数据
        $date['report'] = $this->_getReportlist($res);

        //拼接折线图所需要的数据
        $date['chart'] = $this->_getchart($date['report']);

        //处理时间插件
        $date['time']  = $this->_getTime(date("Y-m-d"),$params);

        //综合报告页面执行数据汇总和
        $date['total'] = $this->_getReportTotal($date['report']);

        $this->assign('date',$date);
        return $this->fetch('effect-day');
    }
    

    /**
     * 项目报告和广告报告
     */
    public function normalReport()
    {
        $pageParam = Request::instance()->param('');
        //组装初期数据
        $params = $this->_getParams($pageParam);
        //当搜索开始时间不是今天，结束日期是今天的，需要同时查询log和new表，拼接
        if(($params['startday'] != date("Y-m-d")) && ($params['endday'] == date("Y-m-d"))){
            //今日数据查询log表，以前数据查询new表
            //new表无今天数据
            $params['endday'] = date("Y-m-d",strtotime("-1 day"));
            if(empty($params['plan_name']) && empty($params['ad_id'])){
                $date['todayplan'] = Loader::model('Advertiser')->getTodayPerformanceFortype($params);
                $date['beforplan'] = Loader::model('Advertiser')->getPerformanceFortype($params);
            }elseif(!empty($params['plan_name'])){
                $date['todayplan'] = Loader::model('Advertiser')->getPlanTodayPerformance($params);
                $date['beforplan'] = Loader::model('Advertiser')->getPlanPerformance($params);
            }else{
                $date['todayplan'] = Loader::model('Advertiser')->getAdsTodayPerformance($params);
                $date['beforplan'] = Loader::model('Advertiser')->getAdsPerformance($params);
            }
            $date['plan'] = array_merge($date['todayplan'],$date['beforplan']);
        }else{
            //根据不同的检索条件执行不同的sql文
            if(empty($params['plan_name']) && empty($params['ad_id'])){
                $date['plan'] = Loader::model('Advertiser')->getPerformanceFortype($params);
            }elseif(!empty($params['plan_name'])){
                $date['plan'] = Loader::model('Advertiser')->getPlanPerformance($params);
            }else{
                $date['plan'] = Loader::model('Advertiser')->getAdsPerformance($params);
            }
        }
        //执行完sql后将endday还原，不影响页面显示
        $params['endday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 10,20);
        //将检索条件传回页面，将其显示在页面上
        $date['plan_name'] = empty($params['plan_name']) ? '' : $params['plan_name'];
        $date['ad_id'] = empty($params['ad_id']) ? '' : $params['ad_id'];

        //统计每天的浏览量和结算数等，为折线图准备数据
        $plan = $this->_getReportlist($date['plan']);

        //拼接折线图所需要的数据
        $date['chart'] = $this->_getchart($plan);

        //处理得到项目报告下的数据
        if($pageParam['type'] == 'plan'){
            $date['plan'] = $this->_getPlanlist($date['plan']);
        }else{
            $date['plan'] = $this->_getAdslist($date['plan']);
        }

        //处理时间插件
        $date['time'] = $this->_getTime(date("Y-m-d"),$params);

        //将效果报告的类型传到前台，判断要渲染的页面内容
        $date['type'] = $pageParam['type'];

        $this->assign('date',$date);
        return $this->fetch('effect-list');
    }

/***************************************************我的首页*****************************************************/
    /**
     * 统计广告商当日支付
     */
    private function _getReport($res)
    {
        $arr = array();
//        $res = $this->_getnumber($res);
        //初始化当天报告的数据
        foreach ($res as $key => $value) {
            $arr[$value['plan_type']]['adv_views'] = '0';
            $arr[$value['plan_type']]['adv_num'] = '0';
            $arr[$value['plan_type']]['sumadvpay'] = '0';
        }

        //将当天报告相同类型的数据汇总
        foreach ($res as $key => $value) {
            $arr[$value['plan_type']]['plan_type'] = $value['plan_type'];
//            $arr[$value['plan_type']]['adv_views'] += $value['adv_views'];
//            $arr[$value['plan_type']]['adv_num'] += $value['adv_num'];
            $arr[$value['plan_type']]['sumadvpay'] += $value['sumadvpay'];
        }

        return $arr;
    }

    /**
     * //统计广告商当日支付，昨日支付，当月支付，余额
     */
    private function _getTotal($res,$yesterday,$month,$money)
    {   
        $nowTotal = empty($res[0]['sumadvpay']) ? '0.00':$res[0]['sumadvpay'];
        $yesTotal = empty($yesterday[0]['sumadvpay']) ? '0.00':$yesterday[0]['sumadvpay'];
        $month = empty($month[0]['sumadvpay']) ? '0.00':$month[0]['sumadvpay'];
        //将执行累加后的数据保留两位小数
        $arr['nowTotal'] = $nowTotal ;
        $arr['yesTotal'] = $yesTotal;
        $arr['month'] = $month  + $nowTotal;
        //得到广告商余额
        $arr['money'] = $money['money'] - $nowTotal;
        return $arr;
    }
    /*private function _getTotal($res,$yesterday,$month,$money,$advStats)
    {
        //在不进行误差修正的情况下计算当月收入
        $monthNow = '0.00';
        foreach($month as $key=>$value){
            $monthNow += $value['sumadvpay'];
        }
//        $res = $this->_getnumber($res);
//        $yesterday = $this->_getnumber($yesterday);
//        $month = $this->_getnumber($month);
        //初始化当日支付，昨日支付数据
        $nowTotal = '0.00';
        $yesTotal = '0.00';
        $monthMoney = '0.00';
        //计算当日支付总额
        foreach($res as $key=>$value){
            $nowTotal += $value['sumadvpay'];
        }
        //计算昨日支付总额
        foreach($yesterday as $key=>$value){
            $yesTotal += $value['sumadvpay'];
        }
        $month = array_merge($res,$month);
        //计算当月支付总额
        foreach($month as $key=>$value){
            $monthMoney += $value['sumadvpay'];
        }
        //将执行累加后的数据保留两位小数
        $arr['nowTotal'] = floor($nowTotal*100)/100;
        $arr['yesTotal'] = floor($yesTotal*100)/100;
        $arr['month'] = floor($monthMoney*100)/100;
        //得到广告商余额
        $arr['money'] = floor(($money['money'] - $advStats['SUM(a.sumadvpay)'])*100)/100;
        return $arr;
    }*/
/***************************************************计划管理数据处理*****************************************************/
    /**
     * 合并相同的数据类型(显示在左侧导航栏内)
     */
    private function _getType($res)
    {
        $type = '';
        $arr = '';
        foreach($res as $key=>$value){
            if($type != $value['plan_type']){
                $type = $value['plan_type'];
                $arr[] = $value['plan_type'];
            }
        }
        return $arr;
    }

    /**
     * 得到网站与计划类型
     */
    private function _classType(){
        $classRes = Loader::model('Advertiser')->getLstByType();
        $classLst = array();
        foreach ($classRes as $key => $value) {
            if($value['type'] == 1){
                $classLst['site'][$key] = $value;
            }
            if($value['type'] == 2){
                $classLst['plan'][$key] = $value;
            }
        }
        return $classLst;
    }

    /**
     * 新增计划参数
     * param data 参数数组
     */
    private function _dataForAdd($params)
    {
        $data = array(
            'uid' => session::get('advertiserUid'),  //会员id
            'plan_name' => $params['pname'],//计划名称
            'plan_type' => isset($params['ptype']) ? $params['ptype']:'',//计划类型
            'clearing' => $params['clearing'],//结算周期
            'class_id' => $params['class_id'] == '选择分类' ? '':$params['class_id'],//分类id
            'pricedv' => $params['pricedv'],//广告商单价
            'mobile_price' => $params['mobile_price'],//移动设备出价
            'budget' => $params['budget'],//每日限额
            'price_info' => $params['price_info'],//价格说明
            'status' => '2',//编辑后状态变为待审

        );
        $checkplan = array(
            'mobile' => array(
                'isacl' => $params['mobile_isacl'],
                'data' => isset($params['mobile_data']) ? $params['mobile_data']:'',
            ),
            'city' => array(
                'isacl' => $params['city_isacl'],
                'comparison' => $params['comparison'],
                'province' => isset($params['city_province']) ? $params['city_province']:'',
                'data' => isset($params['city_data']) ? $params['city_data']:'',
            ),
            'siteclass' => array(
                'isacl' => $params['site_isacl'],
                'choose' => $params['choose'],
                'data' => isset($params['site_data']) ? $params['site_data']:'',
            ),
            'week' => array(
                'isacl' => $params['week_isacl'],
                'data' => isset($params['week_data']) ? $params['week_data']:'',
            ),
            'expire_date' => array(
                'isdate' => $params['expire_date'],
                'year' => isset($params['expire_year']) ? $params['expire_year']:'',
                'month' => isset($params['expire_month']) ? $params['expire_month']:'',
                'day' => isset($params['expire_day']) ? $params['expire_day']:'',
            )
        );
        $data['checkplan'] = serialize($checkplan);
        return $data;
    }

    /**
     * 编辑定向处理
     */
    private function _doEditData($res)
    {
        $res['checkplan'] = unserialize($res['checkplan']);
        $res['province'] = '';
        $res['city'] = '';

        if(!empty($res['checkplan']['city']['province'])){
            $res['province'] = implode(',', $res['checkplan']['city']['province']);
            unset($res['checkplan']['city']['province']);
        }
        if(!empty($res['checkplan']['city']['data'])){
            $res['city'] = implode(',', $res['checkplan']['city']['data']);
            unset($res['checkplan']['city']['data']);
        }

        $res['expire_date'] = $res['checkplan']['expire_date'];
        $res['mobile_data'] = $this->_doStype($res['checkplan']['mobile']['data']);
        unset($res['checkplan']['expire_date']);
        unset($res['checkplan']['mobile']['data']);

        $res['sitedata'] = '';
        if(!empty($res['checkplan']['siteclass']['data'])){
            $res['sitedata'] = json_encode($res['checkplan']['siteclass']['data']);
            unset($res['checkplan']['siteclass']['data']);
        }
        $res['weekdata'] = '';
        if(!empty($res['checkplan']['week']['data'])){
            foreach ($res['checkplan']['week']['data'] as $key => $value) {
                $res['weekdata'][$key] = $value;
            }
            $res['weekdata'] = json_encode($res['weekdata']);
        }
        $res['price'] = rtrim(rtrim($res['price'],'0'),'.');  //去右侧0 然后去右侧点
        $res['pricedv'] = rtrim(rtrim($res['pricedv'],'0'),'.');

        return $res;
    }

    /**
     * mobile_data  定义处理
     */
    private function _doStype($data)
    {
        $res['pc'] = '';
        $res['ios'] = '';
        $res['android'] = '';
        $res['wp'] = '';
        if(empty($data)) return $res;

        foreach ($data as $value) {
            $res[$value] = $value;
        }
        return $res;
    }

/***************************************************广告管理数据处理*****************************************************/
    /**
     * 上传文件
     */
    private function _upfile($file)
    {
        $str = '';
        if(isset($file)){
            //上传
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(Config::get('file_upload'));
            $path = $info->getPathname();
            $stropos = strpos($path,'\uploads');

            if(!$info){
                // 上传失败获取错误信息
                $this->_error($file->getError());
            }
            $str = substr($path, $stropos);
        }
        return $str;
    }

    /**
     * 合并相同的数据类型(显示在左侧导航栏内)
     */
    private function _getPname($res)
    {
        $pname = '';
        $arr = '';
        foreach($res as $key=>$value){
            if($pname != $value['plan_name']){
                $pname = $value['plan_name'];
                $arr[] = $value['plan_name'];
            }
        }
        return $arr;
    }

    /**
     * 处理计划类型数组
     * param  数组
     */
    private function _doPlanType($arrs)
    {
        $res = array();
        foreach ($arrs as $key => $value) {
            $res[$value['plan_type']][$key]['uid']=$value['uid'];
            $res[$value['plan_type']][$key]['plan_name']=$value['plan_name'];
            $res[$value['plan_type']][$key]['pid']=$value['pid'];
        }
        return $res;
    }

/***************************************************效果报告*****************************************************/
    /**
     * 统计效果报告的数据
     */
    private function _getReportlist($res)
    {
//        $res = $this->_getnumber($res);
        $arr = array();
        //初始化报告的数据
        foreach ($res as $key => $value) {
            $arr[$value['day']]['adv_views'] = 0;
            $arr[$value['day']]['adv_num'] = 0;
            $arr[$value['day']]['sumadvpay'] = 0;
            $arr[$value['day']]['cpc'] = 0;
            $arr[$value['day']]['cpm'] = 0;
            $arr[$value['day']]['cpv'] = 0;
            $arr[$value['day']]['cps'] = 0;
            $arr[$value['day']]['cpa'] = 0;
        }

        //将报告同一天的数据汇总,并且计算出CPC，CPM，CPV，CPS，CPA的有效数
        foreach ($res as $key => $value) {
            switch($value['plan_type'] ){
                case 'CPC':
                    $arr[$value['day']]['cpc'] += 1;
                    break;
                case 'CPM':
                    $arr[$value['day']]['cpm'] += 1;
                    break;
                case 'CPV':
                    $arr[$value['day']]['cpv'] += 1;
                    break;
                case 'CPS':
                    $arr[$value['day']]['cps'] += 1;
                    break;
                case 'CPA':
                    $arr[$value['day']]['cpa'] += 1;
                    break;
            }
            $arr[$value['day']]['day'] = $value['day'];
            $arr[$value['day']]['plan_name'] = $value['plan_name'];
            $arr[$value['day']]['bigpname'] = $value['bigpname'];
            $arr[$value['day']]['ad_id'] = $value['ad_id'];
//            $arr[$value['day']]['adv_views'] += empty($value['adv_views']) ? 0 : $value['adv_views'];
//            $arr[$value['day']]['adv_num'] += empty($value['adv_num']) ? 0 : $value['adv_num'];
            $arr[$value['day']]['sumadvpay'] += sprintf("%.4f",$value['sumadvpay']);
        }
        return $arr;
    }

    /**
     * 统计项目报告的数据
     */
    private function _getPlanlist($res)
    {
//        $res = $this->_getnumber($res);
        $arr = array();
        //初始化报告的数据
        foreach ($res as $key => $value) {
            $arr[$value['day']][$value['plan_name']]['adv_views'] = 0;
            $arr[$value['day']][$value['plan_name']]['adv_num'] = 0;
            $arr[$value['day']][$value['plan_name']]['sumadvpay'] = 0;
        }

        //将报告同一天的数据汇总,并且计算出CPC，CPM，CPV，CPS，CPA的有效数
        foreach ($res as $key => $value) {
            $arr[$value['day']][$value['plan_name']]['day'] = $value['day'];
            $arr[$value['day']][$value['plan_name']]['plan_name'] = $value['plan_name'];
            $arr[$value['day']][$value['plan_name']]['bigpname'] = $value['bigpname'];
            $arr[$value['day']][$value['plan_name']]['ad_id'] = $value['ad_id'];
//            $arr[$value['day']][$value['plan_name']]['adv_views'] += $value['adv_views'];
//            $arr[$value['day']][$value['plan_name']]['adv_num'] += $value['adv_num'];
            $arr[$value['day']][$value['plan_name']]['sumadvpay'] += $value['sumadvpay'];
        }

        $num = array();
        $number = 0;
        //将三维数组转换为二维数组
        foreach($arr as $key=>$value){
            foreach($value as $res){
                $num[$number]['sumadvpay'] = empty($res['sumadvpay']) ? '' : sprintf("%.4f",$res['sumadvpay']);
                $num[$number]['adv_num'] = $res['adv_num'];
                $num[$number]['adv_views'] = $res['adv_views'];
                $num[$number]['ad_id'] = $res['ad_id'];
                $num[$number]['plan_name'] = $res['plan_name'];
                $num[$number++]['day'] = $res['day'];
            }
        }
        return $num;
    }

    /**
     * 统计项目报告的数据
     */
    private function _getAdslist($res)
    {
//        $res = $this->_getnumber($res);
        $arr = array();
        //初始化报告的数据
        foreach ($res as $key => $value) {
            $arr[$value['day']][$value['ad_id']]['adv_views'] = 0;
            $arr[$value['day']][$value['ad_id']]['adv_num'] = 0;
            $arr[$value['day']][$value['ad_id']]['sumadvpay'] = 0;
        }

        //将报告同一天的数据汇总,并且计算出CPC，CPM，CPV，CPS，CPA的有效数
        foreach ($res as $key => $value) {
            $arr[$value['day']][$value['ad_id']]['day'] = $value['day'];
            $arr[$value['day']][$value['ad_id']]['plan_name'] = $value['plan_name'];
            $arr[$value['day']][$value['ad_id']]['bigpname'] = $value['bigpname'];
            $arr[$value['day']][$value['ad_id']]['ad_id'] = $value['ad_id'];
//            $arr[$value['day']][$value['ad_id']]['adv_views'] += $value['adv_views'];
//            $arr[$value['day']][$value['ad_id']]['adv_num'] += $value['adv_num'];
            $arr[$value['day']][$value['ad_id']]['sumadvpay'] += $value['sumadvpay'];
        }
        $num = array();
        $number = 0;
        //将三维数组转换为二维数组
        foreach($arr as $key=>$value){
            foreach($value as $res){
                $num[$number]['sumadvpay'] = empty($res['sumadvpay']) ? '' : sprintf("%.4f",$res['sumadvpay']);
                $num[$number]['adv_num'] = $res['adv_num'];
                $num[$number]['adv_views'] = $res['adv_views'];
                $num[$number]['ad_id'] = $res['ad_id'];
                $num[$number]['plan_name'] = $res['bigpname'];
                $num[$number++]['day'] = $res['day'];
            }
        }
        return $num;
    }

    /**
     * 效果报告页面执行数据汇总
     */
    private function _getReportTotal($date)
    {
        $arr = array();
        foreach ($date as $key => $value) {
            $arr['cpc'] = empty($arr['cpc']) ? (0+$value['cpc']):($arr['cpc']+$value['cpc']);
            $arr['cpm'] = empty($arr['cpm']) ? (0+$value['cpm']):($arr['cpm']+$value['cpm']);
            $arr['cpv'] = empty($arr['cpv']) ? (0+$value['cpv']):($arr['cpv']+$value['cpv']);
            $arr['cps'] = empty($arr['cps']) ? (0+$value['cps']):($arr['cps']+$value['cps']);
            $arr['cpa'] = empty($arr['cpa']) ? (0+$value['cpa']):($arr['cpa']+$value['cpa']);
            $arr['adv_views'] = empty($arr['adv_views']) ? (0+$value['adv_views']):($arr['adv_views']+$value['adv_views']);
            $arr['adv_num'] = empty($arr['adv_num']) ? (0+$value['adv_num']):($arr['adv_num']+$value['adv_num']);
            $arr['sumadvpay'] = empty($arr['sumadvpay']) ? sprintf("%.4f",(0+$value['sumadvpay'])):sprintf("%.4f",($arr['sumadvpay']+$value['sumadvpay']));
        }
        return $arr;
    }

    /**
     * 拼接折线图所需要的数据
     */
    private function _getchart($date)
    {
        $strday = '';
        foreach ($date as $key => $value) {
            //拼接折线图的日期(拼接成 "'2016-08-03','2016-08-04','2016-08-05'"类型)
            $day = $value['day'];
            $valueday = str_Replace("$day","'$day'",$day);
            $strday .= $valueday;

            //拼接折线图的结算数(拼接成"1,2,3")
            $num[] = $value['adv_num'];

            //拼接折线图的浏览数(拼接成"1,2,3")
            $views[] = $value['adv_views'];
            $adv_sumadvpay[] = $value['sumadvpay'];
        }
        $str['day'] = str_Replace("''","','",$strday);
        $str['adv_num'] = empty($num) ? '':implode(',',$num);
        $str['adv_views'] = empty($views) ? '':implode(',',$views);
        $str['adv_sumadvpay'] = empty($adv_sumadvpay) ? '':implode(',',$adv_sumadvpay);
        return $str;
    }

    /**
     * 处理时间函数
     */
    private function _getTime($day,$parama)
    {
        //获取所有时间段
        $allday = '2000-01-01'.'2199-12-31';
        //获取今天日期
        $today = $day.$day;
        //获取昨天日期
        $yesterday = date("Y-m-d",strtotime("-1 day")).date("Y-m-d",strtotime("-1 day"));
        //最近7天
        $lastSeven = date('Y-m-d',strtotime("-6 days")).$day;
        //最近30天
        $lastThirty = date('Y-m-d',strtotime("-29 days")).$day;
        //本月
        $nowmonth = date("Y-m-01").$day;
        //获取上个月日期
        $timestamp = strtotime($day);
        $firstday = date('Y-m-01',strtotime(date('Y',$timestamp).'-'.(date('m',$timestamp)-1).'-01'));
        $lastday = date('Y-m-d',strtotime("$firstday +1 month -1 day"));
        $lastMonth = $firstday.$lastday;
        $data = array(
            'nowval' => $parama['startday'].$parama['endday'],
            'now' => $parama['startday']."至".$parama['endday'],
            'allday' => $allday,
            'today' => $today,
            'yesterday' => $yesterday,
            'lastseven' => $lastSeven,
            'lastthirty' => $lastThirty,
            'lastmonth' => $lastMonth,
            'nowmonth' => $nowmonth,
            'time' => $parama['startday'].$parama['endday'],
        );
        return $data;
    }

    /**
     * 准备初期数据
     */
    private function _getParams($pageParam)
    {
        $params['uid'] = session::get('advertiserUid');
        $params['startday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 0,10);
        $params['endday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 10,20);
        $params['plan_name'] = empty($pageParam['plan_name']) ? '' : $pageParam['plan_name'];
        $params['ad_id'] = empty($pageParam['ad_id']) ? '' : $pageParam['ad_id'];
        $params['type'] = empty($pageParam['type']) ? '' : $pageParam['type'];
        return $params;
    }

    /**
     * 根据扣量计算不同的数据
     */
    private function _getnumber($res)
    {
        foreach($res as $key=>$value){
            //计算扣量后的广告商结算数
            $res[$key]['adv_num'] = empty($value['pricedv']) ? '0' : round($value['sumadvpay'] / $value['pricedv']);
            $res[$key]['sumadvpay'] = floor( ($res[$key]['adv_num'] * $value['pricedv'])*100)/100;

            $res[$key]['adv_views'] = empty($value['num']) ? '0' : round($value['views'] / $value['num'] * $res[$key]['adv_num']);
        }
        return $res;
    }
}
