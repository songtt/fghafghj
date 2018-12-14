<?php
/* 站长客户端
 * @date   2016-6-23
 */
namespace app\home\controller;;
use think\Controller;
use think\Loader;
use think\Request;
use app\user\common\Encrypt;
use think\Db;
use think\response\Redirect;
use think\Session;
use app\user\api\DelApi as DelApi;

class Webmaster extends Client
{
    /**
     *  我的概况
     */
    public function myCenter()
    {
        //判断登录的入口
        $pageParam = Request::instance()->param();
        //获取当天报告
        $params['uid'] = Session::get('webmasterUid');
        $params['day'] = date("Y-m-d");
        //处理快捷报告所需的数据
        $params['startday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 0,10);
        $params['endday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 10,20);
        $date['time']  = $this->_getTime(date("Y-m-d"),$params);

        $res = Loader::model('Webmaster')->reportNow($params);
        //当日收入
        $nowDay = Loader::model('Webmaster')->reportNownew($params);
        //获取昨日收入数据
        $params['yesterday'] = date("Y-m-d",strtotime("-1 day"));
        $yesterday = Loader::model('Webmaster')->getYesMoney($params);

        //获取当月收入数据
        $params['firstday'] = date("Y-m-01");
        $month = Loader::model('Webmaster')->getMonthMoney($params);

        //获取账户余额(暂时用)
        $money = Loader::model('Webmaster')->webMoney($params['uid']);
        $moneyPay = Loader::model('Webmaster')->webMoneyPay($params['uid']);
        //统计站长当日收入，昨日收入，当月收入，余额
        $date['total'] = $this->_getTotal($nowDay,$yesterday,$month,$money,$moneyPay);

        //将当日报告下的同一类型的数据合并
        $date['report'] = $this->_getReport($res);

        //将统计后的值传到前台
        $this->assign('homepage',$date);

        return $this->fetch('my-center');
    }

    /**
     *   站长获取代码
     */
    public function obtain()
    {
        $params =Request::instance()->param();
        $param_adz = explode(',',$params['adz_id']);
        $params = array(
            'adz_id' => $param_adz[0],
            'type'=>$param_adz[1]);
        //获取该广告位是否有专属域名，没有则使用通用域名
        $service = Loader::model('Users')->getAdzService($params['adz_id']);
        if(empty($service)){
            $service = Loader::model('Users')->getJsService();
        }
        $this->assign('data',$params);
        $this->assign('servicename',$service);
        return $this->fetch('Obtaincode');
    }


    /**
     *   账户设置
     */
    public function account()
    {
        $uid = Session::get('webmasterUid');
        $list = Loader::model('Webmaster')->account($uid);
        $this->assign('list',$list);

        $judge_name = Session::get('user_login_uname');
        $this->assign('judge_name',$judge_name);

        return $this->fetch('account');
    }


    /**
     *     修改账号信息
     */
    public function edit(){
        $params =Request::instance()->post();
        $uid = $params['uid'];
        //组装 修改的字段
        $data=array(
            'mobile'  =>$params['mobiles'],
            'qq' => empty($params['qqhao']) ? '空' : $params['qqhao'],
            'email' => empty($params['emails']) ? '' : $params['emails'],
            'tel'     =>$params['tels'],
            'idcard'  =>$params['idcars'],
            'bank_name'  =>$params['bank_name'],
            'bank_branch'  =>$params['bank_branch'],
            'account_name'  =>$params['account_name'],
            'bank_card'  =>$params['bank_card'],

        );
        //验证数据
        $validate = Loader::validate('users');
        if(!$validate->check($data)){
            $this->error($validate->getError());
        }
        $update = Loader::model('Webmaster')->edit($uid,$data);
        if($update >=0){
            $this->redirect('account');
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
            $sele = Loader::model('Webmaster')->getOne($uid);
            //用户输入的密码加密
            $Encrypt = new Encrypt();
            $password = $Encrypt->fb_ucenter_encrypt($params['password']);
            //判断 用户输入的原始密码是否正确
            if($password == $sele['password']){
                if($params['new_password'] != $params['password']){
                    //判断 用户输入的2次新密码是否一致
                    if($params['new_password'] ==$params['new_password_1']){
                        $new_password = $Encrypt->fb_ucenter_encrypt($params['new_password']);
                        $data = array(
                            'password'=>$new_password,
                        );
                        $update = Loader::model('Webmaster')->passEdit($uid,$data);
                        if($update >=0){
                            $this->_error('修改成功');
                        }else{
                            $this->_error('修改失败');
                        }
                    }else{
                        $this->_error('2次新密码不一致');
                    }
                }else{
                    $this->_error('新密码不能和旧密码相同');
                }
            }else{
                $this->_error('原始密码不一致');
            }
        }else{
            $this->_error('密码不能为空');
        }
    }

    /**
     *  网站管理   列表
     */
    public function siteList()
    {
        $uid = session::get('webmasterUid');
        $list = Loader::model('Webmaster')->siteList($uid);
        $this->assign('list',$list);
        //查询分类
        return $this->fetch('site-list');
    }

    /**
     *  新建网站
     */
    public function siteAdd()
    {
        // 获取账号的 ID
        $uid = session::get('webmasterUid');
        $request = Request::instance();
        $params = $request->post();
        if($request->isPost()){
            //查询网址是否重复
            $siteUrl = Loader::model('Webmaster')->siteRepeatUrl($params['url']);
            if($siteUrl == true){
                $this->redirect('siteAdd');
            }else{
                $add = $this->_siteData($uid,$params);
                if($add >0){
                    $this->redirect('siteList');
                }else{
                    $this->_error();
                }
            }
        }else{
            //查询网站分类
            $class = Loader::model('Webmaster')->siteClass();
            $this->assign('class_list',$class);
        }
        return $this->fetch('site-add');
    }

    /**
     * 查询网址是否重复
     */
    public function siteRepeatUrl()
    {
        $request = Request::instance();
        $params = $request->post();
        $url = explode('/',$params['siteUrl']);
        $siteUrl = Loader::model('Webmaster')->siteRepeatUrl($url[0]);
        if (!empty($siteUrl)){
            echo 1;
        }else{
            echo 0;
        }
    }

    /**
     *     修改网站
     */
    public function siteEdit()
    {
        $uid = $_GET['id'];
        $request = Request::instance();
        if($request->isPost()){
            $params = $request->post();
            $update = $this->_siteEdit($uid,$params);
            if($update>=0){
                $this->redirect('siteList');
            }else{
                $this->_error();
            }
        }
        $site_one = Loader::model('Webmaster')->siteOne($uid);
        if(!empty($site_one)){
            //查询网站分类
            $class = Loader::model('Webmaster')->siteClass();
            $this->assign('class_list',$class);
            $this->assign('site_one',$site_one);
        }else{
            $this->redirect('siteAdd');
        }

        return $this->fetch('site_edit');
    }

    /**
     *   删除网站
     */
    public function siteDele()
    {
        $siteid = Request::instance()->get('id');
        if(!empty($siteid)){
            $UserApi = new DelApi();
            $UserApi->del($siteid,'home_site');
            $dele = Loader::model('Webmaster')->siteDele($siteid);
            if($dele >0){
                $this->redirect('siteList');
            }else{
                $this->_error();
            }
        }
    }

    /**
     *   付款记录
     */
    public function record()
    {
        $request = Request::instance();
        $uid = $request->session('webmasterUid');
        $week = date('w');
        $params['mon'] = date('Y-m-d',strtotime( '-'. 6-$week .' days' ));
        $params['sun'] = date('Y-m-d',strtotime( '-'. $week .' days' ));
        $params['today'] = date('Y-m-d');
        //得到财务报表所有结算记录
        $res = Loader::model('Webmaster')->recordLogall($uid,$params);
        //得到未结算金额
        $data = Loader::model('Webmaster')->recordLog($uid,$params);
        foreach ($res as $k => $v) {
            $res[$k]['log'] = '已支付';
        }
        if($data[0]['money'] != ''){
            $data[0]['day'] = $params['sun'];
            $data[0]['log'] = '未支付';
            $data[0]['payinfo'] = '空';
            $res = array_merge($data,$res);
        }
        $this->assign('record',$res);
        return $this->fetch('record');
    }

    /**
     *   消息中心
     */
    public function message()
    {
        return $this->fetch('message');
    }

    /**
     *   我的广告
     */
    public function advertising()
    {
        $request = Request::instance();
        $uid = $request->session('webmasterUid');
        //查询是否有网站
        $list = Loader::model('Webmaster')->siteList($uid);

        $type = $request->get('type');
        if(!empty($request->param('zid'))){
            $zid = $request->param('zid');
        }else{
            $zid = '0';
        }

		$pageParam = $request->param();
        $pageParam['mobile'] = empty($pageParam['mobile']) ? '0' : $pageParam['mobile'];

        if(!empty($uid)){
            $pname = Loader::model('Webmaster')->actList($uid,$pageParam);
            if(empty($type)) {
                $actList = Loader::model('Webmaster')->actList($uid,$pageParam);
            }else{
                $actList = Loader::model('Webmaster')->actListType($uid,$type,$pageParam);
            }
        }else{
            $this->redirect('login');
        }
        if(!empty($pname)){
            for ($i = 0; $i < count($pname); $i++) {
                $actpname[] = $pname[$i]['plan_type'];
            }
            $actpnameChong = array_unique($actpname);
            $this->assign('actpname',$actpnameChong);
        }
        foreach($actList as $key=>$value){
            if($value['adtpl_id'] == 5030){
                $actList[$key]['plan_type'] = 'CPC';
            }
        }
        $this->assign('list',$list);
        $this->assign('act_list',$actList);
//        $this->assign('siteUrl',$_SERVER['HTTP_HOST']);
        $this->assign('zid',$zid);
		$this->assign('mobile',$pageParam['mobile']);
        return $this->fetch('activities');
    }


    /**
     * 新建广告位
     */
    public function activitAdd()
    {
        $request = Request::instance();
        $uid = $request->session('webmasterUid');
        if($request->isPost()){
            $params = $request->post();
            $params['type'] = !empty($params['adz_type'])?2:'0';
            $add = $this->_activitAdd($params,$uid);
            if($add >0){
				//获取插入数据的id
                $adz_id = $add;
                //站长id
                $params['uid'] = $uid;
                $params['type'] = $request->session('type');
                $params['adz_id'] = $adz_id;

                $this->redirect('advertising',['zid'=>$adz_id]);
            }else{
                $this->_error();
            }
        }else{
            $adsSql = $this->_actAds();
            $img = Loader::model('Webmaster')->getImgService();
            $img = empty($img['img_server']) ? array('img_server' => '/') : $img;
            $ad_class = Loader::model('Webmaster')->getClass();
            $this->assign('ad_class',$ad_class);
            $this->assign('ads_res',$adsSql);
            $this->assign('img',$img);
            if(!empty($adsSql)){
                $res = $this->_actRes($adsSql);
                if (empty($res['getType'])){
                    $data = $this->_actDataMo($res['getptname']);
                }else{
                    $data = $this->_actData($res['getptname'],$res);
                }
                if(count($res['ptype']) == 1 && $res['ptype'][0]['plan_type']  == 'CPM'){
                    $res['ptype'][1]['plan_type'] = 'CPC';
                }
                $data['name'] = $request->get('name');
                //只有站长id为5963的站长可以看到640*80的顶部banner(罗总测试需求)
                $data['zSize'] = empty($data['zSize']) ? [] : $data['zSize'];
                foreach($data['zSize'] as $key=>$value){
                    if($value['tpl_id'] == 5032 && $value['width'] == 640 && $value['height'] == 80){
                        if(Session::get('webmasterUid') != 5963 && Session::get('webmasterUid') != 6053){
                            unset($data['zSize'][$key]);
                        }
                    }
                }

                $this->assign('res', $res);
                $this->assign('data', $data);
            }
        }
        return $this->fetch('activitAdd');
    }

    /**
     *  修改广告
     */
    public function activitEdit()
    {
        $request = Request::instance();
        if($request->isPost()){
            $gid = $request->get('zid');
            $params = $request->post();
            $zList = Loader::model('Webmaster')->activitEdit($gid);
            $Edit = $this->_actEditData($gid,$params);
            if($Edit >=0){
                $this->redirect('advertising',['zid'=>$gid]);
            }else{
                $this->_error();
            }
        }else {
            $zoneid = $request->get('zid');
            $zList = Loader::model('Webmaster')->activitEdit($zoneid);
            $ad_class = Loader::model('Webmaster')->getClass();
            if(!empty($zList)){
                $data = $this->_editData($zList,$zoneid);

            }else{
                $this->redirect('advertising');
            }
            // 删除当前广告位缓存
            //$Redis = new \org\Redis;
            //$Redis::rm('adzList'.$zoneid.'');
        }
        if($zList[0]['adtpl_id'] == 5030){
            $zList[0]['plan_type'] = 'CPC';

        }
        $img = Loader::model('Webmaster')->getImgService();
        $img = empty($img['img_server']) ? array('img_server' => '/') : $img;
        $data['adstyle_id'] = $zList[0]['adstyle_id'];
        $this->assign('img',$img);
        $this->assign('data',$data);
        $this->assign('ad_class',$ad_class);
        $this->assign('zlist',$zList[0]);
        return $this->fetch('activitEdit');
    }

    /**
     *   广告位删除
     */
    public function activitDel()
    {
        $params = input('');
        $UserApi = new DelApi();
        $UserApi->del($params['zid'],'home_adz');
        $zdel = Loader::model('Webmaster')->zdel($params['zid']);
        if($zdel>0){
            // 删除当前广告位缓存
            //$Redis = new \org\Redis;
            //$Redis::rm('adzList'.$params['zid'].'');
            $this->_success();
        }else{
            $this->error();
        }
    }

    /**
     *   活动广告
     */
    public function activity()
    {
        $gettype = Request::instance()->get('type');
        $plans = Loader::model('Webmaster')->planList();
        //活动分类
        if(empty($gettype)){
            $planList = Loader::model('Webmaster')->planList();
        }else{
            $planList = Loader::model('Webmaster')->ptype($gettype);
        }
        for($i=0;$i<count($plans);$i++){
            $type[] = $plans[$i]['plan_type'];
        }
        if(!empty($type)){
            $pname = array_unique($type);
            $this->assign('plan_list',$planList);
            $this->assign('pname',$pname);
            $this->assign('type',$type);

        }else{
            $type = '';
            $this->assign('type',$type);
        }
        return $this->fetch('advertisement');
    }

    /**
     *   活动广告详情
     */
    public function advertisementinfo()
    {
        $pid = Request::instance()->get('pid');
        $planList = Loader::model('Webmaster')->planinfo($pid);
        $checkplan = unserialize($planList[0]['checkplan']);
        if(!empty($checkplan['mobile']['data'])) {
            $checkplan['zhi'] = implode(',', $checkplan['mobile']['data']);
        }
        $this->assign('plan_list',$planList);
        $this->assign('exdate',$checkplan);
        return $this->fetch('advertisementinfo');
    }

    /**
     *   活动模块  查看广告
     */
    public function advertisementshow()
    {
        $request = Request::instance();
        $pid = $request->get('pid');
        $tplid = $request->get('admtpid');

        $plans = Loader::model('Webmaster')->plans($pid);
        for($i=0;$i<count($plans);$i++){
            $admtpid[$plans[$i]['ad_id']] = $plans[$i]['tpl_id'];
        }
        $tid = implode(',',array_keys(array_unique($admtpid)));
        $plantype = Loader::model('Webmaster')->plantype($pid,$tid);
        if(empty($tplid)){
            $tplid =isset($plantype[0]['tpl_id'])?$plantype[0]['tpl_id']:' 0 ';
        }
        $this->assign('tplid',$tplid); //给jq传值
        $planshow = Loader::model('Webmaster')->planshow($pid,$tplid);
        $img = Loader::model('Webmaster')->getImgService();
        $img = empty($img['img_server']) ? array('img_server' => '/') : $img;
        $this->assign('img',$img);
        $this->assign('plantype',$plantype);
        $this->assign('pid',$pid);

        $this->assign('planshow',$planshow);

        return $this->fetch('advertisementshow');
    }

    /**
     *   数据报告  汇总
     */
    public function effectReport()
    {
        $request = Request::instance();
        $pageParam = $request->param('');
        $getTime = $request->post('time');
        $params['uid'] = session::get('webmasterUid');
        $params['startday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 0,10);
        $params['endday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 10,20);
 
        //当搜索开始时间不是今天，结束日期是今天的，需要同时查询log和new表，拼接
        if(($params['startday'] != date("Y-m-d")) && ($params['endday'] == date("Y-m-d"))){
            //今日数据查询log表
            $statlistToday = Loader::model('Webmaster')->statlistToday($params);
            //除今日数据 查new表
            $params['endday'] = date("Y-m-d",strtotime("-1 day"));
            $statlist = Loader::model('Webmaster')->statlist($params);
            $res = array_merge($statlistToday,$statlist);
        }else{
            //今日数据查询log表 其他数据查new
            $res = Loader::model('Webmaster')->statlist($params);
        }
        //执行完sql后将endday还原，不影响页面显示
        $params['endday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 10,20);
        //汇总报告
        $reportList['report'] = $this->_getReportlist($res);
        //汇总报告 汇总
        $reportList['total'] = $this->_getReportTotal($reportList['report']);
        //拼接折线图所需要的数据
        $reportList['chart'] = $this->_getchart($reportList['report']);
        //处理时间插件
        $reportList['time'] = $this->_getTime(date("Y-m-d"),$params);

        $this->assign('report_list',$reportList);
        return $this->fetch('effect-day');
    }

    /**
     * 项目报告和广告位报告
     */
    public function normalReport()
    {

        $request = Request::instance();
        $adz_id = $request->get('adzid');
        $pageParam = $request->param('');
        //组装初期数据
        $params = $this->_getParams($pageParam);

        //当搜索开始时间不是今天，结束日期是今天的，需要同时查询log和new表，拼接
        if(($params['startday'] != date("Y-m-d")) && ($params['endday'] == date("Y-m-d"))){
            //根据不同的检索条件执行不同的sql文
            if(empty($params['site_id']) && empty($params['adz_id']) && empty($adz_id)){
                //今日数据 查log表
                $statlistToday = Loader::model('Webmaster')->statlistToday($params);
                //除今日数据 查new表
                $params['endday'] = date("Y-m-d",strtotime("-1 day"));
                $beforstat = Loader::model('Webmaster')->statlist($params);
            }elseif(!empty($params['site_id']) && empty($adz_id)){
                //今日数据 查log表
                $statlistToday = Loader::model('Webmaster')->statSitelistToday($params);
                //除今日数据 查new表
                $params['endday'] = date("Y-m-d",strtotime("-1 day"));
                $beforstat = Loader::model('Webmaster')->getsitePerformance($params);
            }else{
                if(!empty($adz_id)){
                    $params['adz_id'] = $adz_id;
                }
                //今日数据 查log表
                $statlistToday = Loader::model('Webmaster')->statAdzlistToday($params);
                //除今日数据 查new表
                $params['endday'] = date("Y-m-d",strtotime("-1 day"));
                $beforstat = Loader::model('Webmaster')->getadzPerformance($params);
            }
            $date['plan'] = array_merge($statlistToday,$beforstat);
        }else{
            //根据不同的检索条件执行不同的sql文
            if(empty($params['site_id']) && empty($params['adz_id']) && empty($adz_id)){
                $date['plan'] = Loader::model('Webmaster')->statlist($params);
            }elseif(!empty($params['site_id']) && empty($adz_id)){
                $date['plan'] = Loader::model('Webmaster')->getsitePerformance($params);
            }else{
                if(!empty($adz_id)){
                    $params['adz_id'] = $adz_id;
                }
                $date['plan'] = Loader::model('Webmaster')->getadzPerformance($params);
            }

        }
        //执行完sql后将endday还原，不影响页面显示
        $params['endday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 10,20);

        //将检索条件传回页面，将其显示在页面上
        $date['pid'] = empty($params['pid']) ? '' : $params['pid'];
        $date['site_id'] = empty($params['site_id']) ? '' : $params['site_id'];
        $date['adz_id'] = empty($params['adz_id']) ? '' : $params['adz_id'];
        //统计每天的浏览量和结算数等，为折线图准备数据
        $plan = $this->_getReportlist($date['plan']);
        //拼接折线图所需要的数据
        $date['chart'] = $this->_getchart($plan);

        if($pageParam['type'] == 'plan'){
            //处理得到项目报告下的数据
            $date['plan'] = $this->_getPlanlist($date['plan']);
        }elseif($pageParam['type'] == 'site'){
            //处理得到项目报告下的数据
            $date['plan'] = $this->_getSitelist($date['plan']);
        }else{
            //处理得到项目报告下的数据
            $date['plan'] = $this->_getAdzlist($date['plan']);

        }
        //汇总数据
        $date['total'] = $this->_getsum($plan);

        //处理时间插件
        $date['time'] = $this->_getTime(date("Y-m-d"),$params);

        //将效果报告的类型传到前台，判断要渲染的页面内容
        $date['type'] = $pageParam['type'];
        $this->assign('date',$date);
        return $this->fetch('effect-list');
    }

    /**
     * 统计站长当日收入
     */
    private function _getReport($res)
    {
        //$res = $this->_getnumber($res);
        $arr = array();
        //初始化当天报告的数据
        foreach ($res as $key => $value) {
            $arr[$value['plan_type']]['web_views'] = '0';
            $arr[$value['plan_type']]['web_num'] = '0';
            $arr[$value['plan_type']]['sumpay'] = '0';
        }

        //将当天报告相同类型的数据汇总
        foreach ($res as $key => $value) {
            $arr[$value['plan_type']]['plan_type'] = $value['plan_type'];
            //$arr[$value['plan_type']]['web_views'] += $value['web_views'];
            //$arr[$value['plan_type']]['web_num'] += $value['web_num'];
            $arr[$value['plan_type']]['sumpay'] += $value['sumpay'];
        }
        return $arr;
    }

    /**
     * 统计站长当日收入，昨日收入，当月收入，余额
     */
    private function _getTotal($res,$yesterday,$month,$money,$moneyPay)
    {
        //$res = $this->_getnumber($res);
        //$yesterday = $this->_getnumber($yesterday);
        //$month = $this->_getnumber($month);
        //初始化当日收入，昨日收入数据
        $nowTotal = '0.00';$nowcpd = '0.00';
        $yesTotal = '0.00';$yescpd = '0.00';
        $monthMoney = '0.00';$monthcpd = '0.00';
        $cpd = '0.00';
        //当天总收入
        $nowDay = array();
        $yesmonth = array();
        //计算当日收入总额
        foreach($res as $key=>$value){
            $name = $value['adz_id'];
            if($value['cpd_status'] != 1){
                $yesmonth[$name]['sumpay'] = $value['sumpay'];
            }else{
                if(empty($value['cpd'])){
                    $yesmonth[$name]['sumpay'] = $value['sumpay'];
                }else{
                    $yesmonth[$name]['sumpay'] = $value['cpd'];
                }
            }
        }
        foreach ($yesmonth as $key => $value) {
            $nowTotal += $value['sumpay'];
        }

        //昨天总收入
        $yesmonth = array();
        //计算昨日收入总额
        foreach($yesterday as $key=>$value){
            $name = $value['adz_id'];
            if($value['cpd_status'] != 1){
                $yesmonth[$name]['sumpay'] = $value['sumpay'];
            }else{
                if(empty($value['cpd'])){
                    $yesmonth[$name]['sumpay'] = $value['sumpay'];
                }else{
                    $yesmonth[$name]['sumpay'] = $value['cpd'];
                }
            }
        }
        foreach ($yesmonth as $key => $value) {
            $yesTotal += $value['sumpay'];
        }
        $month = array_merge($res,$month);
        //当月总收入
        $cpd_month = array();
        //计算当月收入总额
        foreach($month as $key=>$value){
            $name = $value['adz_id'].'-'.$value['day'];
            if($value['cpd_status'] != 1){
                //不包天请况
                $cpd_month[$name]['sumpay'] = $value['sumpay'];
                $cpd_month[$name]['cpd'] =  0;
            }else{
                //包天情况下
                if(!empty($value['cpd'])){
                    $cpd_month[$name]['sumpay'] = $value['cpd'];
                    $cpd_month[$name]['cpd'] =  $value['cpd'];
                }else{
                    $cpd_month[$name]['sumpay'] = $value['sumpay'];
                    $cpd_month[$name]['cpd'] =  0;
                }
            }

        }
        foreach ($cpd_month as $key => $value) {
            $monthcpd += $value['sumpay'];//当月总收入
            $cpd += $value['cpd'];//当月包天收入
        }

        //将执行累加后的数据保留两位小数
        $arr['nowTotal'] = $nowTotal;
        $arr['yesTotal'] = $yesTotal;
        $arr['month'] = $monthcpd;
        $arr['nowcpd'] = $nowcpd;
        $arr['yescpd'] = $yescpd;
        $arr['monthcpd'] = $cpd;
        //计算账户余额
        if(empty($moneyPay['cpd'])){
            $arr['money'] = $money['money']+$moneyPay['money'];
        }else{
            $arr['money'] = $money['money'] ;
        }

        return $arr;
    }

    /**
     *  新建网站 $data
     */
    private function _siteData($uid,$params)
    {
        //组建字段数组
        $data = array(
            'uid'     =>$uid,
            'siteurl' => trim($params['url']),
            'sitename' => $params['name'],
            'https' => $params['https'],
            'beian' => $params['bei'],
            'class_id' => $params['class_id'],
            'day_ip' => $params['dayip'],
            'siteinfo' => $params['info'],
            'status'    =>2,
            'add_time' =>date('Y-m-d H:i:s',time()),
            'ctime'    =>time(),
        );
        $add = Loader::model('Webmaster')->siteAdd($data);
        return $add;
    }

    /**
     *  修改网站 $data
     */
    private function _siteEdit($uid,$params)
    {
        //组建字段数组
        $data = array(
            'siteurl' => trim($params['url']),
            'sitename' => $params['name'],
            'https' => $params['https'],
            'beian' => $params['bei'],
            'class_id' => $params['class_id'],
            'day_ip' => $params['dayip'],
            'siteinfo' => $params['info'],
            'add_time' =>date('Y-m-d H:i:s',time()),
            'ctime'    =>time(),
        );
        $update = Loader::model('Webmaster')->siteEdit($uid,$data);
        return $update;
    }

    /**
     *  模块 广告位 $ads
     */
    private function _actAds()
    {
        $sett = Loader::model('Webmaster')->sett();
        foreach($sett[0] as $key=>$val){
            $type = '';
            if($val ==1){
                $type = '"'.$key.'"';
            }
            $seType[] = $type;
        }
        $textType = implode(',',array_filter($seType));
        //小写转大写  strtoupper
        $textType = strtoupper($textType);

        $plan = Loader::model('Webmaster')->plan($textType);
        if(!empty($plan)){
            for($i=0;$i<count($plan);$i++){
                $arrpid[$plan[$i]['pid']] = $plan[$i]['plan_type'];
                // 获取当前广告的计划设置的投放限制和定向设置
                $checkplan = unserialize($plan[$i]['checkplan']);
                $userid = Session::get('webmasterUid');

                if($plan[$i]['restrictions'] ==1 && in_array($userid,explode(',',$plan[$i]['resuid']))){

                    //获取当前广告的计划是否到达结束日期 0 没有结束时间 1 有结束时间
                    if($checkplan['expire_date']['isdate'] == 1){
                        $time = strtotime(date('Y-m-d',time()));
                        //结束时间 转时间戳
                        $expireTime = strtotime($checkplan['expire_date']['year'].'-'.$checkplan['expire_date']['month'].'-'.$checkplan['expire_date']['day']);
                        if(($expireTime - $time) >= 0){
                            $pid = $plan[$i]['pid'];//允许站长
                        }else{
                            $pid = 0;
                        }
                    }else{
                        $pid = $plan[$i]['pid'];//允许站长
                    }

                }elseif($plan[$i]['restrictions'] ==2 && in_array($userid,explode(',',$plan[$i]['resuid']))){
                    $pid = 0;//不允许站长

                }elseif($plan[$i]['restrictions'] ==1 && !in_array($userid,explode(',',$plan[$i]['resuid']))){
                    // $pid = implode(',',array_keys(array_unique($arrpid)));
                    $pid = 0;

                }else{

                    //获取当前广告的计划是否到达结束日期 0 没有结束时间 1 有结束时间
                    if($checkplan['expire_date']['isdate'] == 1){
                        $time = strtotime(date('Y-m-d',time()));
                        //结束时间 转时间戳
                        $expireTime = strtotime($checkplan['expire_date']['year'].'-'.$checkplan['expire_date']['month'].'-'.$checkplan['expire_date']['day']);
                        if(($expireTime - $time) >= 0){
                            $pid = $plan[$i]['pid'];
                        }else{
                            $pid = 0;
                        }
                    }else{
                        $pid = $plan[$i]['pid'];
                    }

                }
                $arrpidChu[] = $pid;
            }
            $pid = implode(',',array_unique($arrpidChu));
            // 全部 广告数据
            $adsSql = Loader::model('Webmaster')->adsSql($pid);
        }else{
            $adsSql = '';
        }
        return $adsSql;
    }

    /**
     *  模块 广告位 $res
     */
    private function _actRes($adsSql)
    {
        $request = Request::instance();
        $res['ptype'] = $this->_typeSql($adsSql);
        $res['getType'] = $request->get('pltype');// 查询广告类型  这里 $getType 指的是 计划id
        $res['getzonelei'] = $request->get('zonelei');
        $res['getptname'] = $request->get('ptname');
        if(empty($res['getptname']) && !empty($res['ptype'])){
            $res['getptname'] = $res['ptype'][0]['plan_type'];
        }
        return $res;
    }

    /**
     *  模块 广告位添加默认数据
     */
    private function _actDataMo($getptname)
    {
        $request = Request::instance();

        if($getptname == 'CPC'){
            //如果是CPC，则先查出CPC下所有数据，再查出CPM下广告样式为固定banner的数据
            $CPC_data = Loader::model('Webmaster')->chongType($getptname);
            $CPC_adtype = $this->_adtype($CPC_data); // 广告类型 处理
            //CPC下查看固定位置的广告
            $CPM_data = Loader::model('Webmaster')->chongType_CPC('CPM');
            $CPM_adtype = $this->_adtype($CPM_data); // 广告类型 处理
            $data['chong'] = array_merge($CPC_data,$CPM_data);
            $data['adtype'] = array_merge($CPC_adtype,$CPM_adtype);
        }else{
            //cpm下查看固定位置以外的广告
            $data['chong'] = Loader::model('Webmaster')->chongType_CPM($getptname);
            $data['adtype'] = $this->_adtype($data['chong']); // 广告类型 处理
        }
        if(isset($res['getzonelei'])){
            if($res['getzonelei'] == 5030){
                $getptname = 'CPM';
            }
        }

        $data['zonelei'] = $request->get('zonelei'); // 获取用户选择的广告类型
        if(empty($data['zonelei'])){
            $data['zonelei'] = $data['adtype'][0]['tpl_id'];
        }
        $data['gsize'] = $request->get('gsize');// 点击广告尺寸后，显示当前的广告尺寸

        $zoneSize = Loader::model('Webmaster')->zoneSize($data['chong'][0]['tpl_id'], $data['zonelei'],$getptname);// 广告尺寸是否有重复

        $data['zSize'] = $this->_zSize($data['chong'],$zoneSize,$data['zonelei']);// 广告尺寸 处理
        if(empty($data['gsize'])){
            $data['gsize'] = $data['zSize'][0]['width'].'*'.$data['zSize'][0]['height'];
        }
        $data['styleShow'] = Loader::model('Webmaster')->styleShow($data['chong'][0]['tpl_id'], $data['zonelei']);// 显示效果

        if(!empty($data['styleShow'])){

            $data['effect'] = $this->_effect($data['styleShow'],$data['gsize'],$data['zSize']);// 显示 处理

            $data['html'] = unserialize($data['styleShow'][0]['htmlcontrol']);
            $data['zoneFilter'] = $this->_zoneFilter($zoneSize,$data['gsize'],$getptname);// 广告显示 处理
        }else{
            $data['effect'] =0;
            $data['html'] = 0;
            $data['zoneFilter'] = 0;
            $data =$data;
        }
        return $data;
    }

    /**
     *  模块 广告位添加默认数据
     */
    private function _actData($getptname,$res)
    {
        $request = Request::instance();
        if($getptname == 'CPC'){
            //如果是CPC，则先查出CPC下所有数据，再查出CPM下广告样式为固定banner的数据
            $CPC_data = Loader::model('Webmaster')->chongType($getptname);
            $CPC_adtype = $this->_adtype($CPC_data); // 广告类型 处理
            //CPC下查看固定位置的广告
            $CPM_data = Loader::model('Webmaster')->chongType_CPC('CPM');
            $CPM_adtype = $this->_adtype($CPM_data); // 广告类型 处理
            $data['chong'] = array_merge($CPC_data,$CPM_data);
            $data['adtype'] = array_merge($CPC_adtype,$CPM_adtype);
        }else{
            //cpm下查看固定位置以外的广告
            $data['chong'] = Loader::model('Webmaster')->chongType_CPM($getptname);
            $data['adtype'] = $this->_adtype($data['chong']); // 广告类型 处理
        }
        if(isset($res['getzonelei'])){
            if($res['getzonelei'] == 5030){
                $getptname = 'CPM';
            }
        }
        if(count($data['adtype']) == 1 && $data['adtype'][0]['tpl_id'] == 5030){
            $getptname = 'CPM';
        }

        $data['zonelei'] = $request->get('zonelei'); // 获取用户选择的广告类型

        if(!empty($data['adtype'])){
            if(empty($data['zonelei'])){
                $data['zonelei'] = $data['adtype'][0]['tpl_id'];
            }
            $zoneSize = Loader::model('Webmaster')->zoneSize($data['adtype'][0]['tpl_id'], $data['zonelei'],$getptname);//广告尺寸
            $data['zSize'] = $this->_zSize($data['chong'],$zoneSize,$data['zonelei']);// 广告尺寸 处理

            $data['gsize'] = $request->get('gsize');// 默认广告尺寸
            if(empty($data['gsize'])){
                $data['gsize'] = $data['zSize'][0]['width'].'*'.$data['zSize'][0]['height'];
            }
            $data['styleShow'] = Loader::model('Webmaster')->styleShow($data['chong'][0]['tpl_id'], $data['zonelei']); // 显示效果
        }else{
            $data['zSize'] = 0;
            $data['gsize'] = 0;
            $data['styleShow'] = 0;
        }
        if(!empty($data['styleShow'])){

            $data['effect'] = $this->_effect($data['styleShow'],$data['gsize'],$data['zSize']); // 显示 处理
            //$this->assign('style_res', $data['effect']);
            $data['html'] = unserialize($data['styleShow'][0]['htmlcontrol']);
            $data['zoneFilter'] = $this->_zoneFilter($zoneSize,$data['gsize'],$getptname);// 广告显示 处理
        }else{
            $data['effect'] =0;
            $data['html'] = 0;
            $data['zoneFilter'] = 0;
            $data =$data;
        }
        return $data;
    }

    /**
     *  模块 广告位添加
     */
    private function _activitAdd($params,$uid)
    {
        // 处理宽高
        $chuSize = explode('*',$params['gsize']);
        $htmlArr = array(
            'claid'=>$params['claid'],
        );

        if(!empty($params['position'])){
            $htmlArr['position']=$params['position'];
        }else{
            $htmlArr['position']=0;
        }
        if(!empty($params['show_adid'])){
            $show_adid = implode(',',$params['show_adid']);
        }else{
            $show_adid = '';
        }
        if($params['zonelei'] == 5030){
            $params['pltype'] = 'CPM';
        }
        $data = array(
            'uid' =>$uid,
            'zonename'=>$params['name'],
            'star'=>empty($params['star']) ? 1 : $params['star'],
            'plantype'=>$params['pltype'],
            'adtpl_id'=>$params['zonelei'],
            'adstyle_id'=>$params['claid'],
            'class_id'=>110,
            'viewtype'=>$params['viewtype'],
            'htmlcontrol'=>serialize($htmlArr),
            'show_adid' =>$show_adid,
            'minutes'=>isset($params['minutes'])?serialize($params['minutes']):'',
            'add_time'=>date('Y-m-d H:i:s',time()),
            'width' =>$chuSize[0],
            'height'=>$chuSize[1],
            'status'=>2,
            'type' => $params['type'],
            'adz_type' => isset($params['adz_type'])?$params['adz_type']:'',
			'system_type' => isset($params['system_type'])?$params['system_type']:'0',
        );
        //验证数据
        $validate = Loader::validate('ActivitAdd');
        if(!$validate->check($data)){
            $this->error($validate->getError());
        }
        $add = Loader::model('Webmaster')->zoneAdd($data);
        return $add;
    }

    /**
     *  模块 计费方式
     */
    private function _typeSql($adsSql)
    {
        //计划方式 个数
        $adTypeCunt = count($adsSql);
        //计划相同时,计划方式显示一条
        for ($i = 0; $i < $adTypeCunt; $i++) {
            $adpid[$adsSql[$i]['pid']] = $adsSql[$i]['plan_type'];
        }
        //去掉重复  pid
        $arraypid = array_keys(array_unique($adpid));
        // 数组转化字符串
        $impid = implode(',', $arraypid);

        // 查询计费方式
        $typeSql = Loader::model('Webmaster')->typeSql($impid);

        return $typeSql;
    }

    /**
     *  模块 广告类型
     */
    private function _adtype($chong)
    {

        foreach ($chong as $k => $v) {
            // 吧 Id 值赋给key
            $ctplid[0][$v['ad_id']] = $v['tpl_id'];
        }
        if(!empty($chong)){
            // 去掉重复广告模式id
            $keytplid = implode(',', array_keys(array_unique($ctplid[0])));
            $zitplid = implode(',', array_unique($ctplid[0]));
            //广告类型
            $adType = Loader::model('Webmaster')->adtype($zitplid, $keytplid);
        }else{
            $adType =array();
        }
        return $adType;
    }

    /**
     *  模块 广告尺寸
     */
    private function _zSize($chong,$zoneSize,$zonelei)
    {
        // 处理广告尺寸数据
        foreach($zoneSize as $a=>$b){
            // 吧 Id 值赋给key
            $sizeid[$b['ad_id']] = $b['width'].'*'.$b['height'];

        }
        // 得到 key值，就是尺寸对应的广告id
        $keysize = implode(',', array_keys(array_unique($sizeid)));
        $zSize= Loader::model('Webmaster')->guangSize($chong[0]['tpl_id'], $zonelei,$keysize);

        return $zSize;
    }

    /**
     *  模块 显示
     */
    private function _effect($styleShow,$gsize,$zSize)
    {
        if($gsize == null){
            if(!empty($zSize)){
                $gsize =$zSize[0]['width'].'*'.$zSize[0]['height'];
            }else{
                $gsize =0;
            }

        }
        $effect = array();
        // 处理查询数据
        $a = 0;
        for($i=0;$i<count($styleShow);$i++){
            $bb[$styleShow[$i]['style_id']] = unserialize($styleShow[$i]['specs']);
            if(!empty($styleShow[$i]['specs'])){
                if(in_array($gsize,$bb[$styleShow[$i]['style_id']])){
                    $effect[$a]['style_id'] = $styleShow[$i]['style_id'];
                    $effect[$a]['stylename'] = $styleShow[$i]['stylename'];
                    $a++;
                }else{
                    continue;
                }
            }

        }
        return $effect;
    }

    /**
     *  模块 显示广告
     */
    private function _zoneFilter($zoneSize,$gsize,$getptname)
    {
        //根据广告尺寸判断显示的广告
        if($gsize ==null){
            $zwidth = $zoneSize[0]['width'];
            $zheight = $zoneSize[0]['height'];
        }else{
            $arraySize = explode('*',$gsize);
            $zwidth = $arraySize[0];
            $zheight = $arraySize[1];
        }
        $pname = Loader::model('Webmaster')->pname($getptname);
        for($i=0;$i<count($pname);$i++){
            $pid[] = $pname[$i]['pid'];
        }
        $pidChu = implode(',',$pid);
        //广告过滤
        $zoneFilter = Loader::model('Webmaster')->zoneFilter($zoneSize[0]['tpl_id'],$zwidth,$zheight,$pidChu);
        return $zoneFilter;
    }

    /**
     *  重构 $data
     */
    private function _editData($zList,$zoneid)
    {
        $data['html'] = unserialize($zList[0]['adhtml']);
        // 查询同类型下面的同尺寸显示
        $actShow = Loader::model('Webmaster')->actShow($zList[0]['adtpl_id']);

        $data['effect'] = $this->_actEditEct($actShow,$zList[0]['width'],$zList[0]['height']);

        $data['zone'] = unserialize($zList[0]['htmlcontrol']);
        if(empty($zList[0]['minutes'])){
            $data['minutes'] = '';
        }else{
            $data['minutes'] = unserialize($zList[0]['minutes']);
        }

        //广告类型默认选中
        if(!empty($data['zone']['position'])){
            $data['zonelei']=   json_encode($data['zone']['position']);
        }else{
            $data['zonelei']=   json_encode(0);
        }
        //广告默认选中
        if(!empty($zList[0]['show_adid'])){
            $data['zoneMo'] = json_encode(explode(',',$zList[0]['show_adid']));
            $data['zid'] = $zList[0]['show_adid'];
        }else{
            $data['zoneMo'] = json_encode(0);
        }

        $data['zShow'] = $this->_actEditzShow($zList,$zoneid);
        return $data;
    }

    /**
     *  修改广告  $data
     */
    private function _actEditData($gid,$params)
    {
        // 处理宽高
        $htmlArr = array(
            'claid'=>$params['claid'],
        );

        if(!empty($params['position'])){
            $htmlArr['position']=$params['position'];
        }else{
            $htmlArr['position']=0;
        }
        if(!empty($params['show_adid'])){
            $show_adid = implode(',',$params['show_adid']);
        }else{
            $show_adid = '';
        }

        $data = array(
            'zonename'=>$params['name'],
            'star'     =>empty($params['star']) ? 1 : $params['star'],
            'adstyle_id'=>$params['claid'],
            'viewtype'=>$params['viewtype'],
            'htmlcontrol'=>serialize($htmlArr),
            'minutes'=>isset($params['minutes'])?serialize($params['minutes']):'',
            'add_time'=>date('Y-m-d H:i:s',time()),
            'adz_type'=>empty($params['adz_type'])?0:$params['adz_type'],
        );
        //验证数据
        $validate = Loader::validate('ActivitAdd');
        if(!$validate->check($data)){
            $this->error($validate->getError());
        }
        $Edit= Loader::model('Webmaster')->zoneEdit($data,$gid);
        return $Edit;
    }

    /**
     *  修改广告  $effect
     */
    private function _actEditEct($actShow,$width,$height)
    {

        // 处理查询数据
        for ($i = 0; $i < count($actShow); $i++) {
            $bb[$actShow[$i]['style_id']] = unserialize($actShow[$i]['specs']);

            if (in_array(''.$width.'*'.$height.'', $bb[$actShow[$i]['style_id']])) {
                $xianShow[] = $actShow[$i]['style_id'];
            }
        }
        // 得到 id值，转字符串
        $zigid = implode(',', $xianShow);
        //最终显示效果
        $effect = Loader::model('Webmaster')->effect($zigid);
        return $effect;
    }

    /**
     *  修改广告  $zShow
     */
    private function _actEditzShow($zList,$zoneid)
    {
        $chong = Loader::model('Webmaster')->chongType($zList[0]['plan_type']);
        foreach ($chong as $k => $v) {
            // 吧 Id 值赋给key
            $ctplid[0][$v['pid']] = $v['pid'];
        }
        $ctplid[0] = isset($ctplid[0])?$ctplid[0]:0;
        if(!empty($ctplid[0])){
            // 去掉重复广告模式id
            $keytplid = implode(',', array_keys($ctplid[0]));
        }else{
            $keytplid = 0;
        }

        $zShow = Loader::model('Webmaster')->zShow($keytplid,$zoneid);
        return $zShow;
    }

    /**
     *  汇总报告
     */
    private function _getReportlist($statlist)
    {
//        $statlist = $this->_getnumber($statlist);
        $array = array();
        //初始化报告的数据
        foreach ($statlist as $key => $value) {

            $array[$value['day']]['sumpay'] = 0;
            $array[$value['day']]['cpd'] = 0;
            $array[$value['day']]['webpay'] = 0;

        }
        foreach($statlist as $k =>$v){
            $array[$v['day']]['zonename'] = $v['zonename'];
            $array[$v['day']]['day'] = $v['day'];
            //如果包天状态开启  且 包天价钱不为空的情况下汇总的金额为包天的钱
            if($v['cpd_status'] ==1){
                if(empty($v['cpd'])){
                    $array[$v['day']]['webpay'] += $v['sumpay'];
                }else{
                    $array[$v['day']]['webpay'] += $v['cpd'];
                }
            }else{
                $array[$v['day']]['webpay'] += $v['sumpay'];
            }
            $array[$v['day']]['sumpay'] += $v['sumpay'];
            $array[$v['day']]['cpd'] += $v['cpd'];
        }
        foreach($array as $key=>$value){
            $array[$key]['cpd'] = sprintf("%.4f",$value['cpd']);
            $array[$key]['sumpay'] = sprintf("%.4f",$value['sumpay']);
        }
        return $array;
    }

    /**
     * 统计效果报告的数据
     */
    private function _getPlanlist($res)
    {
//        $res = $this->_getnumber($res);
        $arr = array();
        //初始化报告的数据
        foreach ($res as $key => $value) {
            // $arr[$value['day']][$value['bigpname']]['web_views'] = 0;
            // $arr[$value['day']][$value['bigpname']]['web_num'] = 0;
            $arr[$value['day']][$value['bigpname']]['sumpay'] = 0;
        }

        //将报告同一天的数据汇总,并且计算出CPC，CPM，CPV，CPS，CPA的有效数
        foreach ($res as $key => $value) {
            $arr[$value['day']][$value['bigpname']]['day'] = $value['day'];
            // $arr[$value['day']][$value['bigpname']]['plan_name'] = $value['plan_name'];
            // $arr[$value['day']][$value['bigpname']]['bigpname'] = $value['bigpname'];
            $arr[$value['day']][$value['bigpname']]['ad_id'] = $value['ad_id'];
            // $arr[$value['day']][$value['bigpname']]['web_views'] += $value['web_views'];
            // $arr[$value['day']][$value['bigpname']]['web_num'] += $value['web_num'];
            $arr[$value['day']][$value['bigpname']]['sumpay'] += $value['sumpay'];
        }
        $num = array();
        $number = 0;
        //将三维数组转换为二维数组
        foreach($arr as $key=>$value){
            foreach($value as $res){
                $num[$number]['sumpay'] = empty($res['sumpay']) ? '' : $res['sumpay'];
                $num[$number]['web_num'] = $res['web_num'];
                $num[$number]['web_views'] = $res['web_views'];
                $num[$number]['ad_id'] = $res['ad_id'];
                $num[$number]['plan_name'] = $res['bigpname'];
                $num[$number++]['day'] = $res['day'];
            }
        }
        return $num;
    }

    /**
     * 统计效果报告的数据
     */
    private function _getAdzlist($res)
    {
//        $res = $this->_getnumber($res);
        $arr = array();
        //初始化报告的数据
        foreach ($res as $key => $value) {
            // $arr[$value['day']][$value['zonename']]['web_views'] = 0;
            // $arr[$value['day']][$value['zonename']]['web_num'] = 0;
            $arr[$value['day']][$value['adz_id']]['webpay'] = 0;
            $arr[$value['day']][$value['adz_id']]['cpd'] = 0;
            $arr[$value['day']][$value['adz_id']]['sumpay'] = 0;
        }

        //将报告同一天的数据汇总,并且计算出CPC，CPM，CPV，CPS，CPA的有效数
        foreach ($res as $key => $value) {
            $arr[$value['day']][$value['adz_id']]['day'] = $value['day'];
            // $arr[$value['day']][$value['adz_id']]['plan_name'] = $value['plan_name'];
            // $arr[$value['day']][$value['adz_id']]['bigpname'] = $value['bigpname'];
            // $arr[$value['day']][$value['adz_id']]['ad_id'] = $value['ad_id'];
            $arr[$value['day']][$value['adz_id']]['zonename'] = $value['zonename'];
//            $arr[$value['day']][$value['zonename']]['web_views'] += $value['web_views'];
//            $arr[$value['day']][$value['zonename']]['web_num'] += $value['web_num'];
            if($value['cpd_status'] == 1 && !empty($value['cpd'])){
                $arr[$value['day']][$value['adz_id']]['webpay'] += $value['cpd'];
            }else{
                $arr[$value['day']][$value['adz_id']]['webpay'] += $value['sumpay'];
            }
            $arr[$value['day']][$value['adz_id']]['sumpay'] += $value['sumpay'];
            $arr[$value['day']][$value['adz_id']]['cpd'] += $value['cpd'];
        }
        $num = array();
        $number = 0;
        //将三维数组转换为二维数组
        foreach($arr as $key=>$value){
            foreach($value as $res){
                $num[$number]['sumpay'] = empty($res['sumpay']) ? '' : sprintf("%.4f",$res['sumpay']);
                $num[$number]['webpay'] = empty($res['webpay']) ? '' : sprintf("%.4f",$res['webpay']);
                $num[$number]['cpd'] = empty($res['cpd']) ? '' : sprintf("%.4f",$res['cpd']);
                // $num[$number]['web_num'] = $res['web_num'];
                // $num[$number]['web_views'] = $res['web_views'];
                // $num[$number]['ad_id'] = $res['ad_id'];
                // $num[$number]['plan_name'] = $res['bigpname'];
                $num[$number]['zonename'] = $res['zonename'];
                $num[$number++]['day'] = $res['day'];
            }
        }
        return $num;
    }

    /**
     * 统计效果报告的数据
     */
    private function _getSitelist($res)
    {
//        $res = $this->_getnumber($res);
        $arr = array();
        //初始化报告的数据

        foreach ($res as $key => $value) {
            $arr[$value['day']][$value['site_id']]['webpay'] = 0;
            $arr[$value['day']][$value['site_id']]['cpd'] = 0;
            $arr[$value['day']][$value['site_id']]['sumpay'] = 0;
        }

        //将报告同一天的数据汇总,并且计算出CPC，CPM，CPV，CPS，CPA的有效数
        foreach ($res as $key => $value) {
            $arr[$value['day']][$value['site_id']]['day'] = $value['day'];
            $arr[$value['day']][$value['site_id']]['sitename'] = $value['sitename'];
//            $arr[$value['day']][$value['sitename']]['web_views'] += $value['web_views'];
//            $arr[$value['day']][$value['sitename']]['web_num'] += $value['web_num'];
            if($value['cpd_status'] == 1 && !empty($value['cpd'])){
                $arr[$value['day']][$value['site_id']]['webpay'] += $value['cpd'];
            }else{
                $arr[$value['day']][$value['site_id']]['webpay'] += $value['sumpay'];
            }
                $arr[$value['day']][$value['site_id']]['cpd'] += $value['cpd'];
                $arr[$value['day']][$value['site_id']]['sumpay'] += $value['sumpay'];
        }
        $num = array();
        $number = 0;
        //将三维数组转换为二维数组
        foreach($arr as $key=>$value){
            foreach($value as $res){
                $num[$number]['sumpay'] = empty($res['sumpay']) ? '' : sprintf("%.4f",$res['sumpay']);
                $num[$number]['webpay'] = empty($res['webpay']) ? '' : sprintf("%.4f",$res['webpay']);
                $num[$number]['cpd'] = empty($res['cpd']) ? '' : sprintf("%.4f",$res['cpd']);
                $num[$number]['sitename'] = $res['sitename'];
                $num[$number++]['day'] = $res['day'];
            }
        }
        return $num;
    }

    /**
     *  汇总报告 汇总
     */
    private function _getReportTotal($date)
    {
        $arr = array();
        foreach ($date as $key => $value) {
            $arr['cpd'] = empty($arr['cpd']) ? sprintf("%.4f",(0+$value['cpd'])):sprintf("%.4f",($arr['cpd']+$value['cpd']));
            $arr['webpay'] = empty($arr['webpay']) ? sprintf("%.4f",(0+$value['webpay'])):sprintf("%.4f",($arr['webpay']+$value['webpay']));
            $arr['sumpay'] = empty($arr['sumpay']) ? sprintf("%.4f",(0+$value['sumpay'])):sprintf("%.4f",($arr['sumpay']+$value['sumpay']));
        }
        return $arr;
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
            // $num[] = $value['web_num'];

            //拼接折线图的浏览数(拼接成"1,2,3")
            // $views[] = $value['web_views'];
            $sumpay[] = $value['sumpay'];
        }
        $str['day'] = str_Replace("''","','",$strday);
        // $str['web_num'] = empty($num) ? '':implode(',',$num);
        // $str['web_views'] = empty($views) ? '':implode(',',$views);
        $str['sumpay'] = empty($sumpay) ? '':implode(',',$sumpay);
        return $str;
    }

    /**
     * 准备初期数据
     */
    private function _getParams($pageParam)
    {
        $params['uid'] = session::get('webmasterUid');
        $params['startday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 0,10);
        $params['endday'] = empty($pageParam['time']) ? date("Y-m-d") : substr($pageParam['time'], 10,20);
        $params['pid'] = empty($pageParam['pid']) ? '' : $pageParam['pid'];
        $params['site_id'] = empty($pageParam['site_id']) ? '' : $pageParam['site_id'];
        $params['adz_id'] = empty($pageParam['adz_id']) ? '' : $pageParam['adz_id'];
		$params['type'] = empty($pageParam['type']) ? '' : $pageParam['type'];
        return $params;
    }

    /**
     * 根据扣量计算不同的数据
     */
    private function _getnumber($res)
    {
        foreach($res as $key=>$value){
            //不同星级的站长有不同的单价
            if($value['gradation'] == 1){
                switch($value['star']){
                    case 1 : $value['price'] = $value['price_1']; break;
                    case 2 : $value['price'] = $value['price_2']; break;
                    case 3 : $value['price'] = $value['price_3']; break;
                    case 4 : $value['price'] = $value['price_4']; break;
                    case 5 : $value['price'] = $value['price_5']; break;
                }
            }
            if(!empty($value['cpd'])){
                $count = Loader::model('Webmaster')->getAdzCount($value['adz_id'],$value['day']);
                //计算扣量后的广告商结算数
                $res[$key]['cpd'] = sprintf("%.4f",$value['cpd']/$count['count']);
                $res[$key]['web_num'] = ($value['price'] == '0.0000'||empty($value['price'])) ? '0' : round($value['sumpay'] / $value['price']);
                $res[$key]['sumpay'] = sprintf("%.4f", $res[$key]['web_num'] * $value['price']);
                $res[$key]['web_views'] = $value['views'];
                $res[$key]['sumpay'] = 0;
            }else{
                //计算扣量后的广告商结算数
                $res[$key]['cpd'] = 0;
                $res[$key]['web_num'] = ($value['price'] == '0.0000'||empty($value['price'])) ? '0' : round($value['sumpay'] / $value['price']);
                $res[$key]['sumpay'] = sprintf("%.4f", $res[$key]['web_num'] * $value['price']);
                $res[$key]['web_views'] = $value['views'];
            }
        }
        return $res;
    }

    /**
     * 广告位报告 汇总
     */
    private function _getsum($date)
    {
        $arr = array();
        foreach ($date as $key => $value) {
            $arr['sumpay'] = empty($arr['sumpay']) ? (0+$value['sumpay']):($arr['sumpay']+$value['sumpay']);
            $arr['webpay'] = empty($arr['webpay']) ? (0+$value['webpay']):($arr['webpay']+$value['webpay']);
            $arr['cpd'] = empty($arr['cpd']) ? (0+$value['cpd']):($arr['cpd']+$value['cpd']);
        }
        return $arr;
    }

    /**
     * 图文教程
     */
    public function Course()
    {
        return $this->fetch('course');
    }

}