<?php
/** 客服客户端后台
 * date   2016-8-15
 */
namespace app\home\controller;
use app\user\common\Encrypt;
use think\Loader;
use think\Request;
use think\Session;
use think\Cache;
use think\config;

class Customer extends Client
{
    /**
     * 我的首页
     */
    public function index()
    {
        $date['uid'] = Session::get('customerUid');

        //统计客服今日业绩
        $date['money'] = $this->_gettodayMoney($date['uid'],date("Y-m-d"));

        //将当前时间转化为时间戳
        $time = strtotime(date("Y-m-d"));

        //查询当天下属新增会员个数
        $date['num'] = Loader::model('Customer')->getTodaynum($date['uid'],$time);

        //查询待审会员ＴＯＰ５０
        $users = Loader::model('Customer')->getUsers($date['uid']);
        //数据组装(时间转换)
        $date['users'] = $this->_getusers($users);
        //查询待审会员个数
        $date['usersCount'] = Loader::model('Customer')->getUsersCount($date['uid']);

        //查询待审网站ＴＯＰ５０
        $date['site'] = Loader::model('Customer')->getSite($date['uid']);
        //查询待审网站个数
        $date['siteCount'] = Loader::model('Customer')->getSiteCount($date['uid']);
        //全局服务器配置地址
        $globalRes =Loader::model('Customer')->globalList();
        $date['url'] = $globalRes[0]['jump_server'];

        //查询待审广告位
        $res = Loader::model('Customer')->getToAdzone($date['uid']);

        $month['monthBegin'] =  date('Y-m-d',mktime(0,0,0,date('m'),1,date('Y')));
        $month['monthEnd'] = date("Y-m-d",strtotime("-1 day"));

        //计算所有站长的当月消耗
        $date['sumpay'] = $this->_getWebMoney($date['uid'],$month,$date['money']);
        //客服下级站长的合作明细
        $date['detail'] = '';
//        $date['detail'] = $this->_getWebDetail($web,$month);

        //将统计后的值传到前台
        $this->assign('res',$res);
        $this->assign('date',$date);
        return $this->fetch('customer-index');
    }

    /**
     * 会员管理
     */
    public function user()
    {
        $Request = Request::instance();
        //获取初期数据
        $date['params'] = $this->_getUsersParam($Request);
        //获取uid
        $date['uid'] = Session::get('customerUid');
        $date['now'] = date("Y-m-d");
        $date['twoMonth'] =  date("Y-m-d H:i:s", strtotime("-2 month"));
        //判读查询为全部会员或者有收益会员
        if($date['params']['sortMoney'] == 'sumpay'){
            $Profit =  Loader::model('Customer')->getreportProfit($date,$date['uid']);
            //分页功能
            $total = count($Profit);           
        }else{
            //分页功能
            $total = Loader::model('Customer')->getUsersListCount($date['uid'],$date['params']);
        }
        //判断是升序还是降序排列
        if($date['params']['sortMethod'] == 'descend'){
            //查询线下会员（倒序）
            $userProfit = Loader::model('Customer')->getUsersDes($date);
        }else{
            //查询线下会员（升序）
            $userProfit = Loader::model('Customer')->getUsersAs($date);
        } 
        //判断是否用户名查询 
        if((($date['params']['selectName'] == 'uid')||($date['params']['selectName'] == 'uname')) && !empty($date['params']['num'])){
             $total = count($userProfit);
        }
        $pageParam = $Request->param('');
        $Page = new \org\PageUtil($total,$pageParam);
        $date['show'] = $Page->show($Request->action(),$date['params']);
        //只获取有业绩会员
        if(isset($Profit)){ 
            $userId = array_column($Profit,'uid');
            $date['res'] = array();
            foreach($userProfit as $key=> $value){
                if(in_array($userProfit[$key]['uid'],$userId)){
                    $totalNum[$key] = $userProfit[$key];
                }
            }
        }
        if(!isset($totalNum)){
            $totalNum = $userProfit;
        }
        //查询当日收益
        $res = Loader::model('Customer')->getreportNow($date,$date['uid']);
        $sumpay = array_column($res,'sumpay','uid');
        $volume = array();
        foreach($totalNum as $key=> $value){
            if(array_key_exists($totalNum[$key]['uid'],$sumpay)){
                $totalNum[$key]['sumpay'] = $sumpay[$totalNum[$key]['uid']];
                $volume[$key]  = $sumpay[$totalNum[$key]['uid']];
            }else{ 
                $totalNum[$key]['sumpay'] = '0.00';
                $volume[$key]  = '0.00';
            }
        }
        if($date['params']['sortName'] == 'sumpay' && $date['params']['sortMethod'] == 'descend'){
            array_multisort($volume, SORT_DESC,  $totalNum);
        }elseif ($date['params']['sortName'] == 'sumpay' && $date['params']['sortMethod'] == 'ascend') {
            array_multisort($volume, SORT_ASC,  $totalNum);
        }

        $date['res'] = array_slice($totalNum,$Page->firstRow,$Page->listRows);
        $this->assign('one',$date);
        return $this->fetch('customer-user');
    }

    /**
     * 网站管理
     */
    public function site()
    {
        $Request = Request::instance();
        //获取初期数据
        $date['params'] = $this->_getSiteParam($Request);
        //获取uid
        $date['uid'] = Session::get('customerUid');

        //分页功能
        $total = Loader::model('Customer')->getSitelistCount($date['uid'],$date['params']);
        $pageParam = $Request->param('');
        $Page = new \org\PageUtil($total,$pageParam);
        $date['show'] = $Page->show($Request->action(),$date['params']);

        $date['site'] = Loader::model('Customer')->getSitelist($Page->firstRow,$Page->listRows,$date);

        $this->assign('one',$date);
        return $this->fetch('customer-site');
    }

    /**
     * 账户设置
     */
    public function account()
    {
        $Request = Request::instance();
        if($Request->isPost()){
            $params = $Request->post();
            $uid = $params['uid'];
            //组装 修改的字段
            $data = $this->_getAccount($params);
            //验证数据
            $validate = Loader::validate('Users');
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }

            $res = Loader::model('Customer')->accountEdit($uid,$data);
            if($res >=0){
                $this->redirect('account');
            }else{
                $this->error('error');
            }
        }else{
            $uid = Session::get('customerUid');
            $res = Loader::model('Customer')->getBasic($uid);

            $this->assign('one',$res[0]);
            return $this->fetch('customer-account');
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
            $res = Loader::model('Customer')->getPwd($uid);
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
                        $update = Loader::model('Customer')->passEdit($uid,$data);
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
    * 新增下属会员
    */
    public function getnewusers()
    {
        $serviceid = Request::instance()->param('uid');
        Session::set('serviceid',$serviceid);

        $this->redirect('index/Index/register');
    }

    /**
     * 网站管理审核/锁定
     */
    public function siteActivate()
    {
        $params = input('');
        $res = Loader::model('Customer')->updateSiteStatus($params['siteid'],$params['status']);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 会员管理审核/锁定
     */
    public function usersActivate()
    {
        $params = input('');
        $res = Loader::model('Customer')->updateUsersStatus($params['uid'],$params['status']);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 客服名下站长密码重置
     */
    public function usersReset()
    {
        $param = Request::instance()->get('uid');
        $password = '123456';
        $Encrypt = new Encrypt();
        $password = $Encrypt->fb_ucenter_encrypt($password);
        $res = Loader::model('Customer')->passwordReset($param,$password);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 客服修改名下站长的合作模式
     */
    public function editCpdType()
    {
        $pageParam = Request::instance()->param();
        $res = Loader::model('Customer')->updateCpd($pageParam);
        $this->redirect('user');
    }

    /**
     * 会员管理跳转到客户端
     */
    public function toWebmaster()
    {
        $pageParam = Request::instance()->param();
        Session::set('webmasterUid',$pageParam['uid']);
        $this->redirect('/home/webmaster/myCenter');
    }

    /**
     * 统计客服今日业绩
     */
    private function _gettodayMoney($uid,$day)
    {
        //今日业绩缓存30分钟
        $cache_model = new Cache();
        $cus_money_now = $cache_model->get('cus_money_now'.$uid);
        if(empty($cus_money_now['sumpay'])){
            $res = Loader::model('Customer')->getMoney($uid,$day);
            $res[0]['sumpay'] = empty($res[0]) ? '' : $res[0]['sumpay'];
            $cache_model->set('cus_money_now'.$uid,$res[0],1800);//设置缓存
        }else{
            $res[0]['sumpay'] = $cus_money_now['sumpay'];
        }
        return $res[0]['sumpay'];
    }

    /**
     * 统计客服当月业绩
     */
    private function _getWebMoney($uid,$month,$todaymoney)
    {
        //缓存当月业绩
        $cache_model = new Cache();
        $cus_money_month = $cache_model->get('cus_money_month'.$uid);
        //查看后台缓存是否存在
        $kf_money = $cache_model->get('kf_money');

        if($cus_money_month == false  && empty($kf_money[$uid])){
            $money = Loader::model('Customer')->monthCusMoney($uid,$month);
            $money[0]['sumpay'] = !isset($money[0]['sumpay']) ? 0 : $money[0]['sumpay'];
            $cache_model->set('cus_money_month'.$uid,$money[0],mktime(23,59,59,date('m'),date('d'),date('Y'))-time());//设置缓存
        }else{
            //使用存在的缓存
            if(empty($kf_money[$uid])){
                $money[0]['sumpay'] = $cus_money_month['sumpay'];
            }else{
                $money[0]['sumpay'] = $kf_money[$uid];
            }
        }

        //当月业绩是当日业绩加上本月业绩
        $money = $money[0]['sumpay']+$todaymoney;
        return $money;
    }

    /**
     * 客服下级站长的合作明细
     */
    private function _getWebDetail($web,$month)
    {
        $num = 0;
        $array = array();
        //查询各个站长下的所有的广告位
        foreach($web as $key=>$value){
            $res = Loader::model('Customer')->getAdzone($value['uid']);
            foreach($res as $k=>$v){
                $array[$num++] = $v['adz_id'];
            }
        }

        //今天
        $today = date('Y-m-d',time());
        //昨天
        $yesterday = date('Y-m-d',mktime(0,0,0,date('m'),date('d')-1,date('Y')));
        //前天
        $before_yesterday = date('Y-m-d',mktime(0,0,0,date('m'),date('d')-2,date('Y')));

        //查询各个广告位下的当月消耗
        $money = array();
        foreach($array as $key=>$value){
            $res = Loader::model('Customer')->monthCusMoney($value,$month);
            foreach($res as $k=>$v){
                if(is_array($money)){
                    continue;
                }
                if(empty($v['cpd'])){
                    $money[$res[$k]['uid']]=isset($money[$res[$k]['uid']])?$money[$res[$k]['uid']]:'';
                    if(is_array($money[$res[$k]['uid']])){
                        $money[$res[$k]['uid']]['username']= $v['username'];
                    }else{
                        if(!empty($money[$res[$k]['uid']])){
                            $money[$res[$k]['uid']]['username'] = '';
                        }
                    }
                    if($today == $res[$k]['day']){
                        $money[$res[$k]['uid']]['today']= $v['money'];//今天
                    }

                    if($yesterday == $res[$k]['day']){
                        $money[$res[$k]['uid']]['yesterday']= $v['money'];//昨天
                    }

                    if($before_yesterday == $res[$k]['day']){
                        if(is_array($money[$res[$k]['uid']])){
                             $money[$res[$k]['uid']]['before_yesterday'] = $v['money'];//前天
                        }
                    }

                }else{
                    $money[$res[$k]['uid']]=isset($money[$res[$k]['uid']])?$money[$res[$k]['uid']]:'';
                    $money[$res[$k]['uid']]['username']= $v['username'];
                    if($today == $res[$k]['day']){
                        $money[$res[$k]['uid']]['today']= $v['cpd'];//今天
                    }

                    if($yesterday == $res[$k]['day']){
                        $money[$res[$k]['uid']]['yesterday']= $v['cpd'];//昨天
                    }

                    if($before_yesterday == $res[$k]['day']){
                        $money[$res[$k]['uid']]['before_yesterday']= $v['cpd'];//前天
                    }

                }
            }
        }

        return $money;
    }

    /**
     * 转化时间格式并且统计待审会员的个数
     */
    private function _getusers($res)
    {
        $date = array();
        foreach($res as $key =>$value){
            $date[$key]['username'] = $value['username'];
            $date[$key]['uid'] = $value['uid'];
            $date[$key]['regip'] = $value['regip'];
            $date[$key]['ctime'] = date("y-m-d H:i:s",$value['ctime']);
        }
        return $date;
    }
    /**
     *获得会员管理的初期数据
     */
    private function _getUsersParam($Request)
    {
        $pageParam = $Request->param();
        if($Request->isPost() || (!empty($pageParam['p']))){
            $date = array(
                'num' => empty($pageParam['num']) ? 0:ltrim($pageParam['num']),
                'selectName' => !isset($pageParam['selectName']) ? 0:$pageParam['selectName'],
                'sortMethod' => !isset($pageParam['sortMethod']) ? 0:$pageParam['sortMethod'],
                'sortName' => !isset($pageParam['sortName']) ? 0:$pageParam['sortName'],
                'sortMoney'=> !isset($pageParam['sortMoney']) ? 0:$pageParam['sortMoney'],
            );
        }else{
            $date = array(
                'num' => '0',
                'selectName' => 'uname',
                'sortMethod' => 'descend',
                'sortName' => 'ctime',
                'sortMoney' =>'all',
            );
        }
        return $date;
    }

    /**
     *获得会员管理的初期数据
     */
    private function _getSiteParam($Request)
    {
        $pageParam = $Request->param();
        if($Request->isPost() || (!empty($pageParam['p']))){
            $date = array(
                'num' => empty($pageParam['num']) ? 0:$pageParam['num'],
                'selectName' => $pageParam['selectName'],
            );
        }else{
            $date = array(
                'num' => '0',
                'selectName' => 'sitename',
            );
        }
        return $date;
    }

    /**
     * 组装 修改的字段的数据
     */
    private function _getAccount($params)
    {
        $data = array(
            'mobile'  =>$params['mobile'],
            'qq'      =>$params['qq'],
            'email'   =>$params['email'],
            'tel'     =>$params['tel'],
            'idcard'  =>$params['idcard'],
        );
        return $data;
    }

    /**
     * 客服后台导出每周应结算站长
     */
    public function custExcel()
    {
        //获取客服uid
        $custid = Session::get('customerUid');
        $cpd_type = Request::instance()->param('cpd_type');
        //得到客服名下的站长
        $getuid = Loader::model('Customer')->getUid($custid,$cpd_type);

        //判断今天是星期几,得到日期数据
        $week = date('w');
        if($week == '0'){
            $week = '7';
        }
        $params['sun'] = date('Y-m-d',strtotime( '-'. $week .' days' ));
        $uidArray = array();
        foreach ($getuid as $key => $value) {
            //得到该站长最近结算时间
            $getPayday = Loader::model('Customer')->getPayday($value['uid']);
            $getPayday['uid'] = empty($getPayday['uid'])?$value['uid']:$getPayday['uid'];
            if(!empty($getPayday['payday'])){
                $getPayday['payday'] = date('Y-m-d', strtotime('-1 sunday', strtotime($getPayday['payday'])));
            }
            $uidArray[$value['uid']] = $getPayday['payday'];
        }
        $array = array();
        //查询站长的结算信息   没办法只能循环查
        foreach ($uidArray as $key => $value) {
            if(empty($value)){
                $array[$key] = Loader::model('Customer')->getPayInfo($key,$params);
                //站长排重点击
                $get_web = Loader::model('Customer')->webClickPay($key,$params);
                $web_click_num[$key]['web_click_num'] = $this->_get_webclick($get_web);
                $array[$key]['payday'] = '';
            }else{
                $array[$key] = Loader::model('Customer')->getPaidInfo($key,$value,$params);
                $array[$key]['payday'] = $value;
                //站长排重点击
                $get_web = Loader::model('Customer')->webClickPaid($key,$value,$params);
                $web_click_num[$key]['web_click_num'] = $this->_get_webclick($get_web);
            }
        }
        $web_click_num = !isset($web_click_num)?'':$web_click_num;
        //得到各广告位的价钱  考虑包天情况
        $res = $this->_getRes($array);
        //得到各站长不同位置的价钱
        $data = $this->_getUidArray($res,$params,$web_click_num);
        unset($res);
        //导出表格制作
        $this->excel($data,$custid,$cpd_type);
    }

    /**
     *  处理数据   按照广告位把相同样式的广告位价钱计算出来
     */
    private function _getRes($array)
    {
        $data = array();
        //得带各个广告位下的钱   以广告位id和样式id为key是为了考虑在同样式下不同尺寸广告的价钱总和
        foreach ($array as $key => $value) {

            $count = count($value);
            if($count > 1){
                foreach ($value as $get => $arr) {
                    if(is_array($arr)){
                        $name = $arr['adz_id'].'-'.$arr['adtpl_id'];
                        if(!isset($data[$name]['sumpay'])){
                            $data[$name]['sumpay'] = 0;
                        }
                        if(!empty($arr['cpd'])){
                            $arr['sumpay'] = $arr['cpd'];
                        }
                        $data[$name]['ui_adzone'] = !isset($data[$name]['ui_adzone'])?0:$data[$name]['ui_adzone'];
                        $data[$name]['uid'] = $arr['uid'];
                        $data[$name]['adz_id'] = $arr['adz_id'];
                        $data[$name]['adtpl_id'] = $arr['adtpl_id'];
                        $data[$name]['sumpay'] += $arr['sumpay'];  //同样式同广告位跑量佣金总和包含包天情况
                        $data[$name]['payday'] = $value['payday'];
                        $data[$name]['username'] = $arr['username'];
                        $data[$name]['account_name'] = $arr['account_name'];
                        $data[$name]['bank_card'] = $arr['bank_card'];
                        $data[$name]['bank'] = $arr['bank_name'].$arr['bank_branch'];
                        $data[$name]['username'] = $arr['username'];
                        $data[$name]['siteurl'] = $arr['siteurl'];
                        $data[$name]['ui_adzone'] = $arr['ui_adzone'] + (int)$data[$name]['ui_adzone'];
                    }
                }
            }else{
                unset($value);
            }
        }
        return $data;
    }

    /**
    *  站长排重点击计算
    */
    private function _get_webclick($data)
    {
        $res = 0;
        foreach ($data as $key => $value) {
            if(empty($value['click_num'])){
                $value['web_click_num'] = 0;
            }elseif($value['click_num'] < $value['web_click_num']){
                $value['web_click_num'] = floor((floor($value['click_num']/$value['web_click_num']*100)/100)*$value['web_click_num']);
            }
            $res = $value['web_click_num'] + $res;
        }
        return $res;
    }

    /**
     * 计算站长的钱    
     */
    private function _getUidArray($res,$params,$web_click)
    {
        $data = array();
        foreach ($res as $key => $value) {
            $i = $value['uid'];            
            $data[$i]['bottom'] = isset($data[$i]['bottom'])?$data[$i]['bottom']:0;//底部
            $data[$i]['tablepla'] = isset($data[$i]['tablepla'])?$data[$i]['tablepla']:0;//插屏
            $data[$i]['left'] = isset($data[$i]['left'])?$data[$i]['left']:0;//右漂   
            $data[$i]['top'] = isset($data[$i]['top'])?$data[$i]['top']:0;//顶部
            $data[$i]['fixed'] = isset($data[$i]['fixed'])?$data[$i]['fixed']:0;//固定
            $data[$i]['txt'] = isset($data[$i]['txt'])?$data[$i]['txt']:0; //文字
            $data[$i]['sumpay'] = isset($data[$i]['sumpay'])?$data[$i]['sumpay']:0; //总和
            //各个位置的独立ip
            $data[$i]['ui_bottom'] = isset($data[$i]['ui_bottom'])?$data[$i]['ui_bottom']:0; 
            $data[$i]['ui_tablepla'] = isset($data[$i]['ui_tablepla'])?$data[$i]['ui_tablepla']:0; 
            $data[$i]['ui_left'] = isset($data[$i]['ui_left'])?$data[$i]['ui_left']:0; 
            $data[$i]['ui_top'] = isset($data[$i]['ui_top'])?$data[$i]['ui_top']:0; 
            $data[$i]['ui_fixed'] = isset($data[$i]['ui_fixed'])?$data[$i]['ui_fixed']:0; 
            $data[$i]['ui_txt'] = isset($data[$i]['ui_txt'])?$data[$i]['ui_txt']:0; 
            $data[$i]['ui_num'] = isset($data[$i]['ui_num'])?$data[$i]['ui_num']:0; 
            //$data[$i]['txt'] = 0;//文字
            $data[$i]['uid'] = $value['uid'];
            $data[$i]['username'] = $value['username'];
            $data[$i]['adtpl_id'] = $value['adtpl_id'];
            $data[$i]['account_name'] = $value['account_name'];
            $data[$i]['bank_card'] = $value['bank_card'];
            $data[$i]['bank'] = $value['bank'];
            $data[$i]['siteurl'] = $value['siteurl'];
            $data[$i]['sumpay'] += $value['sumpay'];
            $data[$i]['ui_num'] += $value['ui_adzone'];
            //各个位置的独立ip   若一个位置有两个广告位 就是个广告位的独立ip之和
            if($value['adtpl_id'] == 5017){//底部
                $data[$i]['bottom'] += $value['sumpay'];
                $data[$i]['ui_bottom'] += $value['ui_adzone'];
            }elseif($value['adtpl_id'] == 5015){//插屏
                $data[$i]['tablepla'] += $value['sumpay'];
                $data[$i]['ui_tablepla'] += $value['ui_adzone'];
            }elseif($value['adtpl_id'] == 5029){//右漂 
                $data[$i]['left'] += $value['sumpay'];
                $data[$i]['ui_left'] += $value['ui_adzone'];
            }elseif($value['adtpl_id'] == 5032){//顶部
                $data[$i]['top'] += $value['sumpay'];
                $data[$i]['ui_top']  += $value['ui_adzone'];
            }elseif($value['adtpl_id'] == 5030){//固定
                $data[$i]['fixed'] += $value['sumpay'];
                $data[$i]['ui_fixed']  += $value['ui_adzone'];
            }elseif($value['adtpl_id'] == 5033){//文字
                $data[$i]['txt'] += $value['sumpay'];
                $data[$i]['ui_txt'] += $value['ui_adzone'];
            }
            if(empty($value['payday'])){
                $data[$i]['payday'] = '此站长为第一次结算,期间为最近三个月';
            }else{
                $value['payday'] = date('Y-m-d', strtotime('+1 day', strtotime($value['payday'])));
                $data[$i]['payday'] = $value['payday'].'至'.$params['sun'];
            }
        }
        //计算点击成本
        foreach ($data as $key => $value) {
            if(empty($web_click[$key]['web_click_num'])){
                $data[$key]['click_cost'] = 0;
            }else{
                $data[$key]['click_cost'] = round($value['sumpay']/$web_click[$key]['web_click_num'],4);
            }
        }
        return $data;
    }

    /**
     * 导出处理
     */
    private function excel($data,$custid,$cpd_type)
    {
        $excel = '.././extend/org/vendor/';
        require_once "".$excel."autoload.php";

        //修改内存
        ini_set('memory_limit','500M');  
        //修改时间 
        ini_set("max_execution_time", "0");
        $custName = Loader::model('Customer')->getCusname($custid);
        switch($cpd_type){
            case 0:
            $type_name = '跑量';
            break;
            case 1:
            $type_name = '包月';
            break;
            case 2:
            $type_name = '公户跑量';
            break;
        }
        $filename='客服'.$custName['username'].$type_name.'周结算'.date('Y-m-d');

        //统计数据个数
        $num_count = count($data)+1;
        $objPHPExcel = new \ PHPExcel();
//      $objPHPExcel->getActiveSheet()->getStyle ('G')->getNumberFormat()->setFormatCode ("@");

        $objPHPExcel->getProperties();

        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getActiveSheet()->getStyle('H2')->getNumberFormat()->setFormatCode("0");
//        $objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('G2'), 'G2:G16' );
        //设置单元格为文本格式
        // $objPHPExcel->getActiveSheet()->getStyle('H')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        // $objPHPExcel->getActiveSheet()->setCellValueExplicit(chr($asc2++).$i, $field, PHPExcel_Cell_DataType::TYPE_STRING);
        $objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('H2'), 'H2:H'.$num_count.'' );
        //合并单元格
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
        //设置Excel的单元格的宽
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $ta = 1;
        $tone = 2;
        $ts = 3;
        $tz = 4;
        $tk = 5;
        $tg = 6;
        $tstyle = 7;
        $tm = 8;
        $tt = 9;
        $tw = 10;
        $i = 2;
        $d = 3;
        $z = 4;
        $t = 5;
        $j = 6;
        $s = 8;
        $m = 9;
        $o = 10;
        foreach ($data as $key => $value) {
            if($value['sumpay'] >= '100'){

                // 添加表头数据
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$ta, '
                                                    '.$type_name.'付款信息统计表')
                    ->setCellValue('A'.$tone, '网站：')
                    ->setCellValue('D'.$tone, 'ID：')
                    ->setCellValue('A'.$ts, '收款单位：')
                    ->setCellValue('A'.$tz, '账号：')
                    ->setCellValue('A'.$tk, '开户银行：')
                    ->setCellValue('A'.$tg, '款项期间：')
                    ->setCellValue('A'.$tstyle, '款项位置:')
                    ->setCellValue('B'.$tstyle, '底部')
                    ->setCellValue('C'.$tstyle, '插屏')
                    ->setCellValue('D'.$tstyle, '右漂')
                    ->setCellValue('E'.$tstyle, '顶部')
                    ->setCellValue('F'.$tstyle, '固定')
                    ->setCellValue('G'.$tstyle, '文字')
                    ->setCellValue('H'.$tstyle, '合计')
                    ->setCellValue('A'.$tm, '款项金额:')
                    ->setCellValue('A'.$tt, '独立IP:')
                    ->setCellValue('A'.$tw, '审核:')
                    ->setCellValue('D'.$tw, '结算:')
                    ->setCellValue('F'.$tw, '点击成本:');


                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('B'.$i,$value['siteurl'])
                        ->setCellValue('E'.$i,$value['username'])
                        ->setCellValue('B'.$d,$value['account_name'])
                        ->setCellValue('B'.$z,' '.$value['bank_card'])
                        ->setCellValue('B'.$t,$value['bank'])
                        ->setCellValue('B'.$j,$value['payday'])
                        ->setCellValue('B'.$s,decimal($value['bottom']))
                        ->setCellValue('C'.$s,decimal($value['tablepla']))
                        ->setCellValue('D'.$s,decimal($value['left']))
                        ->setCellValue('E'.$s,decimal($value['top']))
                        ->setCellValue('F'.$s,decimal($value['fixed']))
                        ->setCellValue('G'.$s,decimal($value['txt']))
                        ->setCellValue('H'.$s,' '.decimal($value['sumpay']))
                        // ->setCellValue('B'.$m,$value['ui_bottom'])
                        // ->setCellValue('C'.$m,$value['ui_tablepla'])
                        // ->setCellValue('D'.$m,$value['ui_left'])
                        // ->setCellValue('E'.$m,$value['ui_top'])
                        // ->setCellValue('F'.$m,$value['ui_fixed'])
                        // ->setCellValue('G'.$m,$value['ui_txt'])
                        // ->setCellValue('H'.$m,' '.$value['ui_num'])
                        // ->setCellValue('G'.$o,$value['click_cost']);
                        ->setCellValue('B'.$m,0)
                        ->setCellValue('C'.$m,0)
                        ->setCellValue('D'.$m,0)
                        ->setCellValue('E'.$m,0)
                        ->setCellValue('F'.$m,0)
                        ->setCellValue('G'.$m,0)
                        ->setCellValue('H'.$m,0)
                        ->setCellValue('G'.$o,0);

                $ta = $ta+13;
                $tone =$tone+13;
                $ts =$ts+13;
                $tz =$tz+13;
                $tk =$tk+13;
                $tg =$tg+13;
                $tstyle =$tstyle+13;
                $tm =$tm+13;
                $tt =$tt+13;
                $tw =$tw+14;
                $i =$i+13;
                $d =$d+13;
                $z =$z+13;
                $t =$t+13;
                $j =$j+13;
                $s =$s+13;
                $m =$m+13;
                $o =$o+13;
                $ta++;
                $tone++;
                $ts++;
                $tz++;
                $tk++;
                $tg++;
                $tstyle++;
                $tm++;
                $tt++;
                $i++;
                $d++;
                $z++;
                $t++;
                $j++;
                $s++;
                $m++;
                $o++;
            }
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

        // 正则
        // header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // 过去的日期
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }


    //提交 需要审核测试
    public function user_test(){

        $request = Request::instance();
        $param = $request->param();
        $data['type'] = $param['test'] ;
        $data['time'] = time();
        $data['user'] = Session::get('user_login_uname');
        $data['shenhe'] = 1;
        if($param['testid'] == 1){
            $data['adz_id'] = $param['id'];
            $data['status'] = 1;
            $get = Loader::model('Customer')->getadzTest($data);
        }else{
            $data['u_id'] = $param['id'] ;
            $data['status'] = 2;
            $get = Loader::model('Customer')->getTest($data);
        }
        if ($get) {
            $re = Loader::model('Customer')->updateTest($data);
        }else{
            $re = Loader::model('Customer')->user_test($data);
        }
        if($re){
            $this->redirect('user');
        }else{
            $this->redirect('user');
        }
    }
}
