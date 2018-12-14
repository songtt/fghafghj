<?php
/** 测试功能
 * date   2018-06-30
 */
namespace app\admin\controller;

use think\Db;
use think\Loader;
use think\Request;
use think\Session;
use think\Cache;
use think\Cookie;
use think\Hook;
use app\user\api\DelApi as DelApi;
use app\user\common\Encrypt as Encrypt;
class Newtest extends Admin
{
	public function _initialize()
    {
        parent::_initialize();
        header("Cache-control: private");
    }

    /**
     *  **********************************************************测试计划列表**********************************************
     */
    public function list()
    {
        $request = Request::instance();
        //权限
        Hook::listen('auth',$this->_uid);
        $pageParam = $request->param('');
        //搜索为空判断
        $pageParam['types'] = !isset($pageParam['types'])?'all':$pageParam['types'];
        $pageParam['mobile'] = !isset($pageParam['mobile'])?'':$pageParam['mobile'];
        $pageParam['plan'] = !isset($pageParam['plan'])?'':$pageParam['plan'];
        $pageParam['status'] = $_SESSION['think']['status'];
        //计划列表
        $list = Loader::model('Newtest')->getLst($pageParam);
        $total = count($list);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        $res = array_slice($list,$Page->firstRow,$Page->listRows);
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        $this->assign('mobile',$pageParam['mobile']);

        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        return $this->fetch('plan-list');
    }

    /**
     * 新建计划
     */
    public function add()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){

            $params = $request->post();
            //组装参数
            $data = $this->_dataForAdd($params);
            //新建计划是待匹配状态
            $data['status'] = 3;
            //验证数据
            $validate = Loader::validate('Plan');
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            //返回新添加的计划id
            $pid = Loader::model('Newtest')->add($data);

            //插入数据到提醒表中
            Loader::model('Newtest')->remindingAdd($pid,$data);

            if($pid>0){
                //写操作日志
                $this->logWrite('0013',$params['pname']);
                //保存成功
                $this->redirect('list',['cmd_flag' => 'add']);
            }else{
                $this->_error();
            }
        }else{
            $param = $request->param('uid');
            if($param){
                $ad_name = Loader::model('Newtest')->getkLst($param);
            }else{
                $ad_name ='';
            }
            //读取广告商
            $res = Loader::model('Newtest')->getOnekLst();
            //计费类型
            $array = Loader::model('Newtest')->getSetting();
            //获取网站与计划类型
            $classLst = $this->_classType();
            $planclass = Loader::model('Newtest')->planclassList();
            //获取手机高中低类型
            $modle = $this->_getModle();
            //获取用户名
            $name = $request->session('uname');
            $this->assign('modle',$modle);
            $this->assign('planclass',$planclass);
            $this->assign('class_list',$classLst);
            $this->assign('ad_list',$res);
            $this->assign('ad_name',$ad_name);
            $this->assign('one',$array);
            $this->assign('name', $name);
            return $this->fetch('plan-add');
        }
    }


    /**
     * 编辑
     */
    public function edit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            $data = $this->_dataForAdd($params);
            //验证数据
            $validate = Loader::validate('Plan');
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $res = Loader::model('Newtest')->editPlan($data,$params['pid']);
            if($res>=0){
                $pms = array(
                    'pid' => $params['pid'],
                    'cmd_flag' => 1,
                );
                //写操作日志
                $this->logWrite('0021',$params['pid']);
                $this->redirect('newtest/edit',$pms);
            }else{
                $this->error('error');
            }
        }else{
            $pid = $request->param('pid');
            //查出所有广告商  编辑可修改广告商
            $ad_data = Loader::model('Newtest')->ad_user();
            $this->assign("ad_data",$ad_data);
            $res = Loader::model('Newtest')->getOne($pid);
            if(!empty($res)){
                //数据处理
                $res = $this->_doEditData($res[0]);
                //获取网站与计划类型
                $classLst = $this->_classType();
                //计划分类
                $planclass = Loader::model('Newtest')->planclassList();
                //获取手机高中低类型
                $modle = $this->_getModle();
                //获取用户名
                $name = $request->session('uname');
                $this->assign('modle',$modle);
                $this->assign('planclass',$planclass);
                $this->assign('class_list',$classLst);
                $this->assign('one',$res);
                $this->assign('name', $name);
                return $this->fetch('plan-edit');
            }else{
                $this->redirect('newtest/add');
            }
        }
    }


    /**
     * ***********************************************************审核测试*************************************************
     */
    //审核测试  列表
    public function audit(){
        Hook::listen('auth',$this->_uid);
        $re = Loader::model('Newtest')->audit();
        $this->assign('re',$re);
        return $this->fetch('audit');
    }

    //ajax 删除待审核数据 单条
    public function del_audit(){
        $request = Request::instance();
        $id = $request->post('id');
        $res = Loader::model('Newtest')->del_audit($id);
        if($res>0){
            //写操作日志
            $this->_success();
        }else{
            $this->_error();
        }
    }

    //ajax 审核  激活 待审核数据 单条
    public function edit_audit(){
        $request = Request::instance();
        $id = $request->post('id');
        //查询审核列表
        $res = Loader::model('Newtest')->find_user($id);
        //测试类型
        $typename = $this->_typsName($res['type']);
        //查询可匹配的计划
        $like_plan = Loader::model('Newtest')->edit_audit($res,$typename);
        if(empty($like_plan)){
            $this->_error('没有匹配到该类型计划');
            return 0;//没有匹配到该计划
        }else{
            //随机匹配计划
            $match_rand = round(0,count($like_plan)-1);
        }
        $p_id = $like_plan[$match_rand]['pid'];
        //激活匹配到的计划  并将站长或是广告位id添加至计划限制中
        $where['pid'] = $p_id;
        $where['type'] = 2;
        //组装修改计划限制数据
        $data = $this->_doData($res,$like_plan[$match_rand]);
        //更新匹配到的计划数据
        $editRes = Loader::model('Newtest')->updatePlan($where,$data);
        $status = 1;
        Loader::model('Newtest')->updateAdsStatus($p_id,$status);
        //更新激活测试站长
        $upWeb = Loader::model('Newtest')->updateWeb($id,$p_id);
        if($upWeb>0){
            $this->_success(array('pid'=>$p_id),'审核成功');
            //查询该计划下的所有的广告，并且全部激活/锁定
        }else{
            $this->_error('没有匹配到该类型计划');
        }
    }

    //组装数据  将匹配到的计划激活并将对应的站长id或者广告位id添加到对应的限制中
    //并清除之前计划中站长限制或是广告位限制
    private function _doData($res,$plan){
        $checkplan = unserialize($plan['checkplan']);
        if($res['status'] == 1){ 
            //广告位
            $checkplan['adzlimit']['adzlimit'] = 1;
            $checkplan['adzlimit']['limitadzid'] = $res['adz_id'];
            $data['checkplan'] = serialize($checkplan); 
            //清除之前匹配到的测试站长
            $data['restrictions'] = 0;
            $data['resuid'] = ''; 
        }elseif($res['status'] == 2){
            //站长
            $data['resuid'] = $res['u_id'] ; 
            // $data['resuid'] = $plan_data['resuid'].','.$re['u_id'] ; 
            $data['restrictions'] = 1;
            //清除之前匹配到的广告位
            $checkplan['adzlimit']['adzlimit'] = 0;
            $checkplan['adzlimit']['limitadzid'] = '';
            $data['checkplan'] = serialize($checkplan); 
        }
        $data['status'] = 1;
        return $data;
    }

    //锁定审核数据
    public function edit_audit_s(){
        $request = Request::instance();
        $params = $request->param();
        $id = $params['id'];
        //更新审核测试的状态
        $res = Loader::model('Newtest')->edit_audit_s($id);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }
    //测试类型
    private function _typsName($res){
        switch ($res) {
            case 1:
                $type_name = "清理类";
                break;
            case 2:
                $type_name = "高要求";
                break;
            case 3:
                $type_name = "小清新";
                break;
            case 4:
                $type_name = "小说";
                break;
            case 5:
                $type_name = "文字";
                break;
            case 6:
                $type_name = "右漂";
                break;
        }
        return $type_name;
    }



/***** ********************************站长测试报表*************************************************************/

    //站长测试报表
    public function weblist(){
        $request = Request::instance();
        $pageParam = $request->param();
        $user_name = Session::get('user_login_uname');
        //下载数及成本，只能运营部及超级管理员填入和修改查看
        if($user_name == 'yunyingbu1' or $user_name == 'yunyingbu2' or $user_name =='yunyingbu3' or $user_name == 'yunyingbu4' or $user_name == 'yunyingbu5' or $user_name == 'yanfabu' or $user_name =='admin'){
                $name = 'outh';
        }else{
                $name = 'wu';
        }
        $this->assign('name',$name);
        //处理时间插件
        $day = date("Y-m-d");
        if(empty($pageParam['time'])){
            $pageParam['time'] = date("Y-m-d");
        }
        $pageParam = array(
            'time' => $pageParam['time'],
            'day' => (substr($pageParam['time'], 0,3) == 'all') ? '2017-01-01' : substr($pageParam['time'], 0,10),
            'day1' => empty(substr($pageParam['time'], 10,20)) ? date("Y-m-d"): substr($pageParam['time'], 10,20),
            'id' => !isset($pageParam['id'])?'':$pageParam['id'],
            'p' => !isset($pageParam['p'])?1:$pageParam['p'],
        );
        $timeValue = $this->_getTime($day,$pageParam);
        $timeValue['time'] = $pageParam['time'];
        $today = $day.$day;
        if($timeValue['time'] == $today){
            //查询当天所有的测试站长数据
            $web_todayRes =  Loader::model('Newtest')->getWebtoday($timeValue,$pageParam['id']);
            $web_res = $web_todayRes;
        }else{
            $web_lastRes = Loader::model('Newtest')->getWeb($timeValue,$pageParam['id']);
            if($timeValue['endday'] == $day){
                $web_todayRes =  Loader::model('Newtest')->getWebtoday($timeValue,$pageParam['id']);
                $web_res = array_merge($web_todayRes,$web_lastRes);
            }else{
                $web_res = $web_lastRes;
            }
        }
        //组装数据
        $web_res = $this->_getRes($web_res);
        //计算点击率组装数据
        $crtRes = $this->_getCrtRes($web_res);
        unset($web_res);
        //查询计算激活数成本和星级
        $activtRes = $this->_getactivtRes($crtRes);
        $total = count($activtRes);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        $activtRes = array_slice($activtRes,$Page->firstRow,$Page->listRows);
        $this->assign('res',$activtRes);
        $this->assign('timeValue',$timeValue);
        $this->assign('pageParam',$pageParam);
        $this->assign('data',$data);
        unset($activtRes);
        return $this->fetch('web-list');
    }



/**********************************广告位测试报表************************************************************/

    //广告位测试报表
    public function adzlist(){
        $request = Request::instance();
        $pageParam = $request->param();
        $user_name = Session::get('user_login_uname');
        //下载数及成本，只能运营部及超级管理员填入和修改查看
        if($user_name == 'yunyingbu1' or $user_name == 'yunyingbu2' or $user_name =='yunyingbu3' or $user_name == 'yunyingbu4' or $user_name == 'yunyingbu5' or $user_name == 'yanfabu' or $user_name =='admin'){
                $name = 'outh';
        }else{
                $name = 'wu';
        }
        $this->assign('name',$name);
        //处理时间插件
        $day = date("Y-m-d");
        if(empty($pageParam['time'])){
            $pageParam['time'] = date("Y-m-d");
        }
        $pageParam = array(
            'time' => $pageParam['time'],
            'day' => (substr($pageParam['time'], 0,3) == 'all') ? '2017-01-01' : substr($pageParam['time'], 0,10),
            'day1' => empty(substr($pageParam['time'], 10,20)) ? date("Y-m-d"): substr($pageParam['time'], 10,20),
            'id' => !isset($pageParam['id'])?'':$pageParam['id'],
            'p' => !isset($pageParam['p'])?1:$pageParam['p'],
        );
        $timeValue = $this->_getTime($day,$pageParam);
        $timeValue['time'] = $pageParam['time'];
        $today = $day.$day;

        if($timeValue['time'] == $today){
            //查询当天所有的测试站长数据
            $web_todayRes =  Loader::model('Newtest')->getAdztoday($timeValue,$pageParam['id']);

            $web_res = $web_todayRes;
        }else{
            $web_lastRes = Loader::model('Newtest')->getAdz($timeValue,$pageParam['id']);
            if($timeValue['endday'] == $day){
                $web_todayRes =  Loader::model('Newtest')->getAdztoday($timeValue,$pageParam['id']);
                $web_res = array_merge($web_todayRes,$web_lastRes);
            }else{
                $web_res = $web_lastRes;
            }
        }
        //组装数据
        $web_res = $this->_getRes($web_res);
        //计算点击率组装数据
        $crtRes = $this->_getAdzCrtRes($web_res);
        unset($web_res);
        //查询计算激活数成本和星级
        $activtRes = $this->_getAdzactivtRes($crtRes);
        $total = count($activtRes);
        $Page = new \org\PageUtil($total,$pageParam);
        $data['show'] = $Page->show($request->action(),$pageParam);
        $activtRes = array_slice($activtRes,$Page->firstRow,$Page->listRows);
        $this->assign('res',$activtRes);
        $this->assign('timeValue',$timeValue);
        $this->assign('pageParam',$pageParam);
        $this->assign('data',$data);
        unset($activtRes);

        return $this->fetch('adz-list');
    }

    //组装测试 数据
    private function _getRes($data){
        foreach ($data as $key => $value) {
            $type = $this->_typsName($value['type']);
            //去除掉数组中测试类型与计划不匹配数据
            if(!stristr($value['plan_name'], $type)){
                unset($data[$key]);
            }
        }
        return $data;
    }


    //测试广告位修改激活数
    public function adzdownload(){
        $params = Request::instance()->post();
        $data['activt_num'] =  $params['param'];
        //先查询表中是否有当天的数据
        $webData = Loader::model('Newtest')->getAdzStats($params);
        if(empty($webData)){
            $data['adz_id'] =  $params['adz_id'];
            $data['pid'] =  $params['pid'];
            $data['day'] =  $params['day'];
            $res = Loader::model('Newtest')->testInster($data);
        }else{
            $where['adz_id'] =  $params['adz_id'];
            $where['pid'] =  $params['pid'];
            $where['day'] =  $params['day'];
            $res = Loader::model('Newtest')->testUpdate($data,$where);
        }
        if($res>=0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    //修改激活数
    public function webdownload(){
        $params = Request::instance()->post();
        $data['activt_num'] =  $params['param'];
        //先查询表中是否有当天的数据
        $webData = Loader::model('Newtest')->getwebStats($params);
        if(empty($webData)){
            $data['uid'] =  $params['uid'];
            $data['pid'] =  $params['pid'];
            $data['day'] =  $params['day'];
            $res = Loader::model('Newtest')->testInster($data);
        }else{
            $where['uid'] =  $params['uid'];
            $where['pid'] =  $params['pid'];
            $where['day'] =  $params['day'];
            $res = Loader::model('Newtest')->testUpdate($data,$where);
        }
        if($res>=0){
            $this->_success();
        }else{
            $this->_error();
        }
    }


    //添加备注
    public function addInfo(){
        $params = Request::instance()->post();
        $data['info'] =  $params['param'];
        //先查询表中是否有当天的数据
        if(isset($params['adz_id'])){
            $webData = Loader::model('Newtest')->getAdzStats($params);
        }else{
            $webData = Loader::model('Newtest')->getwebStats($params);
        }
        if(empty($webData)){
            if(isset($params['adz_id'])){
                $data['adz_id'] =  $params['adz_id'];
            }else{
                $data['uid'] =  $params['uid'];
            }
            $data['pid'] =  $params['pid'];
            $data['day'] =  $params['day'];
            $res = Loader::model('Newtest')->testInster($data);
        }else{
            if(isset($params['adz_id'])){
                $where['adz_id'] =  $params['adz_id'];
            }else{
                $where['uid'] =  $params['uid'];
            }
            $where['pid'] =  $params['pid'];
            $where['day'] =  $params['day'];
            $res = Loader::model('Newtest')->testUpdate($data,$where);
        }
        if($res>=0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    //添加扣量备注
    public function deductionInfo(){
        $params = Request::instance()->post();
        $data['deduction_info'] =  $params['param'];
        //先查询表中是否有当天的数据
        if(isset($params['adz_id'])){
            $webData = Loader::model('Newtest')->getAdzStats($params);
        }else{
            $webData = Loader::model('Newtest')->getwebStats($params);
        }
        if(empty($webData)){
            if(isset($params['adz_id'])){
                $data['adz_id'] =  $params['adz_id'];
            }else{
                $data['uid'] =  $params['uid'];
            }
            $data['pid'] =  $params['pid'];
            $data['day'] =  $params['day'];
            $res = Loader::model('Newtest')->testInster($data);
        }else{
            if(isset($params['adz_id'])){
                $where['adz_id'] =  $params['adz_id'];
            }else{
                $where['uid'] =  $params['uid'];
            }
            $where['pid'] =  $params['pid'];
            $where['day'] =  $params['day'];
            $res = Loader::model('Newtest')->testUpdate($data,$where);
        }
        if($res>=0){
            $this->_success();
        }else{
            $this->_error();
        }
    }



/**************************************************星级管理***********************************************/
    public function star(){
        Hook::listen('auth',$this->_uid);
        $request = Request::instance();
        $params = $request->param();
        if($request->post()){
            $params = $request->post();
            $where['type'] = $params['type'];
            $edit = Loader::model('Newtest')->editStar($params,$where);
            if($edit>0){
                $this->_success('','操作成功');
            }else{
                $this->_error('操作失败');
            }
            $res = $params;
        }else{
            if(isset($params['type'])){
                $type['type'] = $params['type'];
            }else{
                $type['type'] = '0';
            }
            $res = Loader::model('Newtest')->starData($type);
            if(!$res){
                Db::query('truncate  table lz_stat_copy');
                return $this->fetch('star-add');
            }
            $statData = Loader::model('Newtest')->statInfo();
        }
        $data = $this->_getstarRes($res);
        $this->assign('res',$data);
        $this->assign('statData',$statData);
        return $this->fetch("star");
    }

    //p
    private function _getstarRes($res){
        $one_start = explode('-',$res['yistar']);
        $two_start = explode('-',$res['erstar']);
        $three_start = explode('-',$res['sanstar']);
        $four_start = explode('-',$res['sistar']);
        $fives_start = explode('-',$res['wustar']);
        //一星
        $res['one'] = isset($one_start['1']) ? $one_start['1'] : 0;
        //二星
//        $res['two_up'] = $two_start['1'];//上
        $res['two_up'] = isset($two_start['1']) ? $two_start['1'] : 0;

//        $res['two_dwon'] = $two_start['0'];//下
        $res['two_dwon'] = isset($two_start['0']) ? $two_start['0'] : 0;

        //三星
//        $res['three_up'] = $three_start['1'];//上
        $res['three_up'] = isset($three_start['1']) ? $three_start['1'] : 0;

//        $res['three_dwon'] = $three_start['0'];//下
        $res['three_dwon'] = isset($three_start['0']) ? $three_start['0'] : 0;


        //四星
//        $res['four_up'] = $four_start['1'];//上
        $res['four_up'] = isset($four_start['1']) ? $four_start['1'] : 0;

//        $res['four_dwon'] = $four_start['0'];//下
        $res['four_dwon'] = isset($four_start['0']) ? $four_start['0'] : 0;


        //五星
//        $res['fives'] = $fives_start['0'];
        $res['fives'] = isset($fives_start['0']) ? $fives_start['0'] : 0;


        return $res;
    }

    //提交 需要审核站长测试
    public function user_test(){
        $request = Request::instance();
        $param = $request->param();
        $data['type'] = $param['type'] ;
        $data['time'] = time();
        $data['user'] = Session::get('user_login_uname');
        $data['shenhe'] = 1;
        if(isset($param['adz_id'])){
            $data['adz_id'] = $param['adz_id'];
            $data['status'] = 1;
            $get = Loader::model('Newtest')->getadzTest($data);
        }else{
            $data['u_id'] = $param['u_id'] ;
            $data['status'] = 2;
            $get = Loader::model('Newtest')->getTest($data);
        }
        if ($get) {
            $re = Loader::model('Newtest')->updateTest($data);
        }else{
            $re = Loader::model('Newtest')->user_test($data);
        }
        if($re){
            $this->_success('','申请成功，等待审核');
        }else{
            $this->_success('','申请失败，请重试');
        }
    }


    public function addpage()
    {
        $token = md5(microtime(true));
        if(empty(session('token'))){
            session('token',$token);
        }
        $this->assign('session_token',$token);
        return $this->fetch('testpage');
    }


    /**
     * 复制当前的计划
     */
    public function planCopy()
    {
        $request = Request::instance();
        $params = $request->param();
        $pid = $params['pid'];

        //获取当前计划的内容
        $plan_res = Loader::model('Newtest')->planCopy($pid);
        //插入新计划，返回新插入的id
        $new_pid = Loader::model('Newtest')->planCopyAdd($plan_res);

        //插入数据到提醒表中
        Loader::model('Newtest')->remindingAdd($new_pid,$plan_res);

        //获取复制计划下面的单价模板
        $price_res = Loader::model('Newtest')->PriceCopy($pid);
        foreach($price_res as $key => $value){
            //把单价模板插入到新计划下面
            $price_copy = Loader::model('Newtest')->PriceCopyAdd($value,$new_pid);
        }
        //防止计划单价模板为空报错
        if(empty($price_copy)){
            $price_copy = true;
        }

        //查询复制计划下面的广告
        $ads_res = Loader::model('Newtest')->adsCopy($pid);
        foreach($ads_res as $key => $value){
            //把查询到的广告插入到新计划下面
            $ads_copy = Loader::model('Newtest')->adsCopyAdd($value,$new_pid);
        }

        //获取新复制的计划下面的单价id
        $new_price_res = Loader::model('Newtest')->priceSelect($new_pid);
        //查询新复制计划下面的广告
        $new_ads_res = Loader::model('Newtest')->adsSelect($new_pid);

        foreach($new_price_res as $key => $value){
            foreach($new_ads_res as $k => $v){

                if($v['pid'] == $value['pid'] && $v['width'].'*'.$v['height'] == $value['size'] && $v['tpl_id'] == $value['tpl_id']){

                    //在修改到复制广告关联的单价字段里
                    Loader::model('Newtest')->adsCopyUpdate($value['id'],$v['ad_id']);
                }
            }

        }

        //防止广告为空报错
        if(empty($ads_copy)){
            $ads_copy = true;
        }

        if($new_pid == true && $price_copy == true && $ads_copy == true){
            $status = 1;
        }else{
            $status = 0;
        }
        return $status;
    }

 

    /**
     * 获取中高低端手机型号
     */
    private function _getModle()
    {
        $res = Loader::model('Newtest')->getModle();
        $modle = array(
            '0'=>'',
            '1'=>'',
            '2'=>'',
            '3'=>'',
        );
        //将查询出来的手机机型分类
        foreach($res as $key=>$value){
            switch ($value['type']){
                case 1:
                    $modle[0] = $modle[0].$value['name'].',';
                    break;
                case 2:
                    $modle[1] = $modle[1].$value['name'].',';
                    break;
                case 3:
                    $modle[2] = $modle[2].$value['name'].',';
                    break;
            }
            if($value['type_pay'] == 1){
                $modle[3] = $modle[3].$value['name'].',';
            }
        }
        //去除掉最后一个逗号
        foreach($modle as $key=>$value){
            $modle[$key] = substr($modle[$key], 0, -1);
        }
        return $modle;
    }

    /**
     * 批量删除操作
     */
    public function batchDel()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid,'plan-dodel'); //权限
        $params = $request->post();
        if(!isset($params['id'])){
            $this->redirect('newtest/list');
        }
        $UserApi = new DelApi();
        $UserApi->del($params['id'],'plans');
        $ids = implode(',', $params['id']);
        $res = Loader::model('Newtest')->delLst($ids,$params['id']);
        if($res>0){
            //写操作日志
            $this->logWrite('0014',$ids);
            $this->redirect('newtest/list');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 激活/锁定  
     */
    public function activate()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $status = $request->post('status');
        $pid = $request->post('pid');
        $activate = $request->post('activate');
        if($activate == 2){
            $this->_reviewed($pid);
        }else{
            $res = Loader::model('Newtest')->updateStatus($pid,$status);
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
                Loader::model('Newtest')->updateAdsStatus($pid,$status);
                // $this->_updateAdzone($pid,$status);
                $this->_success();
            }else{
                $this->_error('修改失败');
            }
        }
    }

    /**
     * 删除计划并删除计划下所有广告
     */
    public function dodel()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pid = $request->post('pid');
        $UserApi = new DelApi();
        $UserApi->del($pid,'plan');
        $res = Loader::model('Newtest')->delOne($pid);
        if($res>0){
            //写操作日志
            $this->logWrite('0020',$pid);
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 更改计划权重
     */
    public function changePriority()
    {
        Hook::listen('auth',$this->_uid,'plan-changePrice'); //权限
        $params = Request::instance()->post();
        $data['priority'] =  $params['money'];
        $res = Loader::model('Newtest')->updatePriority($params['pid'],$data);
        if($res>=0){
            //写操作日志
            $this->logWrite('0022',$params['pid'],$data['priority']);
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 更改计划列表扣量
     */
    public function deduction()
    {
//        Hook::listen('auth',$this->_uid,'plan-changePrice'); //权限
        $params = Request::instance()->post();
        $data['pid'] =  $params['pid'];
        if($params['type'] == 'adv'){
            $data['deduction'] =  $params['money'];
        }else{
            $data['web_deduction'] =  $params['money'];
        }
        $res = Loader::model('Newtest')->deduction($data);
        if($res>=0){
            $this->_success();
        }else{
            $this->_error();
        }
    }


    /**
     * 编辑定向处理
     */
    private function _doEditData($res)
    {
        $res['checkplan'] = unserialize($res['checkplan']);
        $res['editmodle'] = unserialize($res['editmodle']);
        //投放地域
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

        //投放机型
        //处理原有的计划
        $res['editmodle']['run_model_edit']['isacl'] = isset($res['editmodle']['run_model_edit']['comparison_mobile']) ? $res['editmodle']['run_model_edit']['isacl']:0;
        $res['editmodle']['run_model_edit']['comparison_mobile'] = isset($res['editmodle']['run_model_edit']['comparison_mobile']) ? $res['editmodle']['run_model_edit']['comparison_mobile']:1;
        $res['modle_type'] = '';
        $res['modle_data'] = '';
        if(!empty($res['editmodle']['run_model_edit']['modle_type'])){
            $res['modle_type'] = implode(',', $res['editmodle']['run_model_edit']['modle_type']);
            unset($res['editmodle']['run_model_edit']['modle_type']);
        }
        if(!empty($res['editmodle']['run_model_edit']['modle_data'])){
            $res['modle_data'] = implode(',', $res['editmodle']['run_model_edit']['modle_data']);
            unset($res['editmodle']['run_model_edit']['modle_data']);
        }
        $res['modle_type_pay'] = '';
        $res['modle_data_pay'] = '';
        if(!empty($res['editmodle']['run_model_edit']['modle_type_pay'])){
            $res['modle_type_pay'] = implode(',', $res['editmodle']['run_model_edit']['modle_type_pay']);
            unset($res['editmodle']['run_model_edit']['modle_type_pay']);
        }
        if(!empty($res['editmodle']['run_model_edit']['modle_data_pay'])){
            $res['modle_data_pay'] = implode(',', $res['editmodle']['run_model_edit']['modle_data_pay']);
            unset($res['editmodle']['run_model_edit']['modle_data_pay']);
        }

        $res['expire_date'] = $res['checkplan']['expire_date'];
        $res['mobile_data'] = $this->_doStype($res['checkplan']['mobile']['data']);
        $res['model_data'] = $this->_dotype(empty($res['checkplan']['run_model']['data']) ? '' : $res['checkplan']['run_model']['data']);
        unset($res['checkplan']['expire_date']);
        unset($res['checkplan']['mobile']['data']);

        $res['sitedata'] = '';
        if(!empty($res['checkplan']['siteclass']['data'])){
            $res['sitedata'] = json_encode($res['checkplan']['siteclass']['data']);
            unset($res['checkplan']['siteclass']['data']);
        }

        $res['adzdata'] = '';
        if(!empty($res['checkplan']['adzclass']['data'])){
            $res['adzdata'] = json_encode($res['checkplan']['adzclass']['data']);
            unset($res['checkplan']['adzclass']['data']);
        }else{
            $res['checkplan']['adzclass'] = array(
                'isacl' => 0,
                'choose' => 1,
                'data' => '',
            );
        }

        $res['weekdata'] = '';
        if(!empty($res['checkplan']['week']['data'])){
            $res['weekdata'] = array();
            foreach ($res['checkplan']['week']['data'] as $key => $value) {
                $res['weekdata'][$key] = $value;
            }
            $res['weekdata'] = json_encode($res['weekdata']);
        }
        //广告位限制
        $res['adzlimit'] = isset($res['checkplan']['adzlimit']['adzlimit'])?$res['checkplan']['adzlimit']['adzlimit']:0;
        $res['limitadzid'] = isset($res['checkplan']['adzlimit']['limitadzid'])?$res['checkplan']['adzlimit']['limitadzid']:'';
        unset($res['checkplan']['adzlimit']);
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

    /**
     * 手机 型号  定义处理
     */
    private function _dotype($data)
    {
        $res['3'] = '';
        $res['2'] = '';
        $res['1'] = '';
        if(empty($data)) return $res;

        foreach ($data as $value) {
            $res[$value] = $value;
        }
        return $res;
    }

    /**
     * 得到网站与计划类型
     */
    private function _classType(){
        $classRes = Loader::model('Classes')->getLstByType();
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
        if($params['priority'] <= 0){
            $params['priority'] = 1;
        }
        //投放设备    1.桌面 2.IOS 3.Android  4.微软WP
        if($params['mobile_isacl'] == 1){
            $run_terminal = isset($params['mobile_data']) ? $params['mobile_data']:'';
            if($run_terminal[0] == 'pc'){
                $run_terminal_date = 1;
            }elseif($run_terminal[0] == 'ios'){
                $run_terminal_date = 2;
            }elseif($run_terminal[0] == 'android'){
                $run_terminal_date = 3;
            }elseif($run_terminal[0] == 'wp'){
                $run_terminal_date = 4;
            }else{
                $run_terminal_date = 0;
            }
        }else{
            $run_terminal_date = 0;
        }
        $data = array(
            'uid' => $params['uid'],  //会员id
            'plan_name' => $params['pname'],
            'plan_type' => isset($params['ptype']) ? $params['ptype']:'',
            // 'run_terminal' => $params['run_terminal'], //投放终端
            'clearing' => empty($params['clearing']) ? '0' : $params['clearing'],//结算周期
            // 'class_id' => $params['class_id'] == '选择分类' ? '':$params['class_id'],//分类id
            'mobile_price' => empty($params['mobile_price']) ? '0' : $params['mobile_price'],
            'budget' => $params['budget'],//每日限额
            'price_info' => empty($params['price_info']) ? '0' : $params['price_info'],
            'restrictions' => empty($params['restrictions']) ? '0' : $params['restrictions'],//站长限制
            'resuid' => str_replace('，',',',$params['resuid']),//站长限制ID
            'sitelimit' => empty($params['sitelimit']) ? '0' : $params['sitelimit'],//网站限制
            'limitsiteid' => str_replace('，',',',$params['limitsiteid']),//限制网站ID
            'priority'  =>$params['priority'],//计划权重
//            'ads_sel_status' => $params['ads_sel_status'],
//            'ads_sel_views' => $params['ads_sel_views'],
            'class_id' =>empty($params['class_id'])? '0':$params['class_id'],
            'run_terminal' => $run_terminal_date,
            'type'  => empty($params['type']) ? '0' : $params['type'],
            'delay_show_status'  => empty($params['delay_show_status']) ? '0' : $params['delay_show_status'],
            'click_status'  => empty($params['click_status']) ? '0' : $params['click_status'],
            'percent'  => empty($params['percent']) ? '0' : $params['percent'],
        );
        if(!empty($params['web_deduction'])){
            $data['web_deduction'] = $params['web_deduction'];
            $data['deduction'] = $params['deduction'];
        }
        //将高中低机型和高付费机型合并，去重，减少news.php查询的数据量
        $params['modle_data'] = isset($params['modle_data']) ? $params['modle_data']:array();
        $params['modle_data_pay'] = isset($params['modle_data_pay']) ? $params['modle_data_pay']:array();
        $checkplan = array(
            'mobile' => array(
                'isacl' => $params['mobile_isacl'],
                'data' => isset($params['mobile_data']) ? $params['mobile_data']:'',
            ),
            'run_model' => array(
                'isacl' => $params['modle_isacl'],
                'comparison_mobile' => $params['comparison_mobile'],
                'modle_data' => array_unique(array_merge($params['modle_data'],$params['modle_data_pay'])),
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
            'adzclass' => array(
                'isacl' => $params['adz_isacl'],
                'choose' => $params['adzchoose'],
                'data' => isset($params['adz_data']) ? $params['adz_data']:'',
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
            ),
            'adzlimit' => array(
                'adzlimit' => empty($params['adzlimit'])? 0:$params['adzlimit'],//广告位限制
                'limitadzid' => str_replace('，',',',$params['limitadzid']),//限制广告位ID
            ),
        );
        $editModle = array(
            'run_model_edit' => array(
                'isacl' => $params['modle_isacl'],
                'comparison_mobile' => $params['comparison_mobile'],
                'modle_type' => isset($params['modle_type']) ? $params['modle_type']:'',
                'modle_data' => isset($params['modle_data']) ? $params['modle_data']:'',
                'modle_type_pay' => isset($params['modle_type_pay']) ? $params['modle_type_pay']:'',
                'modle_data_pay' => isset($params['modle_data_pay']) ? $params['modle_data_pay']:'',
            ),
        );
        $data['checkplan'] = serialize($checkplan);
        $data['editmodle'] = serialize($editModle);
        return $data;
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


    //计算点击率组装数据
    private function _getCrtRes($data){
        //计算点击率拼接数据
        $res = array();
        foreach ($data as $key => $value) {
            $name = 'key'.$value['uid'].'-'.$value['pid'].$value['day'];
            $res[$name]['views'] = !isset($res[$name]['views'])?0:$res[$name]['views'];
            $res[$name]['click_num'] = !isset($res[$name]['click_num'])?0:$res[$name]['click_num'];
            $res[$name]['sumadvpay'] = !isset($res[$name]['sumadvpay'])?0:$res[$name]['sumadvpay'];
            $res[$name]['day'] = $value['day'];
            $res[$name]['pid'] = $value['pid'];
            $res[$name]['uid'] = $value['uid'];
            $res[$name]['plan_name'] = $value['plan_name'];
            $res[$name]['type'] = $value['type'];
            $res[$name]['username'] = $value['username'];
            //浏览数取广告位最大浏览数
            if($res[$name]['views']<$value['views']){
                $res[$name]['views'] = $value['views'];
            }
            $res[$name]['click_num'] += $value['click_num'];
            $res[$name]['sumadvpay'] += $value['sumadvpay'];

            if($res[$name]['views'] < $value)
            if(!empty($value['views'])){    
                $res[$name]['ctr'] = round($value['click_num']/$value['views']*100,2);
            }else{
                $res[$name]['ctr'] = 0;
            }
        }
        return $res;
    }

    //广告位计算点击率组装数据
    private function _getAdzCrtRes($data){
        //计算点击率拼接数据
        $res = array();
        foreach ($data as $key => $value) {
            $name = 'key'.$value['adz_id'].'-'.$value['pid'].$value['day'];
            $res[$name] = $value;
            if($res[$name]['views'] < $value)
            if(!empty($value['views'])){    
                $res[$name]['ctr'] = round($value['click_num']/$value['views']*100,2);
            }else{
                $res[$name]['ctr'] = 0;
            }
        }
        return $res;
    }

    //广告位查询计算激活数成本和星级
    private function _getAdzactivtRes($data){
        //星级数据
        $startRes = $this->_getStar();
        //计算点击率拼接数据
        foreach ($data as $key => $value) {
            $activateNum = Loader::model('Newtest')->getAdzActivte($value);
            //激活数
            $data[$key]['activt_num'] = !isset($activateNum['activt_num'])?0:$activateNum['activt_num'];
            //成本
            if($value['sumadvpay'] > 0 && $data[$key]['activt_num'] > 0){
                $data[$key]['cost'] =  round($value['sumadvpay']/$data[$key]['activt_num'],4);
            }else{
                $data[$key]['cost'] = 0;
            }
            $data[$key]['deduction_info'] = !isset($activateNum['deduction_info'])?'':$activateNum['deduction_info'];
            $data[$key]['info'] = !isset($activateNum['info'])?'':$activateNum['info'];
            //星级
            $data[$key]['star'] = $this->_starData($data[$key],$startRes);

        }
        return $data;
    }

    //站长查询计算激活数成本和星级
    private function _getactivtRes($data){
        //星级数据
        $startRes = $this->_getStar();
        //计算点击率拼接数据
        foreach ($data as $key => $value) {
            $activateNum = Loader::model('Newtest')->getActivte($value);
            //激活数
            $data[$key]['activt_num'] = !isset($activateNum['activt_num'])?0:$activateNum['activt_num'];
            //成本
            if($value['sumadvpay'] > 0 && $data[$key]['activt_num'] > 0){
                $data[$key]['cost'] =  round($value['sumadvpay']/$data[$key]['activt_num'],4);
            }else{
                $data[$key]['cost'] = 0;
            }
            $data[$key]['deduction_info'] = !isset($activateNum['deduction_info'])?'':$activateNum['deduction_info'];
            $data[$key]['info'] = !isset($activateNum['info'])?'':$activateNum['info'];
            //星级
            $data[$key]['star'] = $this->_starData($data[$key],$startRes);

        }
        return $data;
    }
    //星级
    private function _starData($params,$star){
        if($params['type'] == 2){
            //高要求素材有两个所以用计划名称来确定  用那个星级
            if(strpos($params['plan_name'],'梦幻') !== false){  //包含梦幻关键词
                $params['type'] = 7;
            }
        }
        $starNum = $star[$params['type']];
        //判定星级等级   成本越小，星级越小  星级的排序是1最大  5最小
        if($params['activt_num']>0){
            if($params['cost'] >= $starNum['one']){ 
                $params['star'] = "一星";
            }elseif($params['cost'] == $starNum['two']){
                $params['star'] = "二星";
            }elseif($params['cost'] == $starNum['three']){
                $params['star'] = "三星";
            }elseif($params['cost'] == $starNum['four']){
                $params['star'] = "四星";
            }elseif($params['cost'] <= $starNum['fives']){
                $params['star'] = "五星";
            }elseif($params['cost'] >= $starNum['two_up']&&$params['cost'] < $starNum['two']){
                $params['star'] = "二星上";
            }elseif($params['cost'] <= $starNum['two_dwon']&&$params['cost'] > $starNum['two']){
                $params['star'] = "二星下";
            }elseif($params['cost'] >= $starNum['three_up']&&$params['cost'] < $starNum['three']){
                $params['star'] = "三星上";
            }elseif($params['cost'] <= $starNum['three_dwon']&&$params['cost'] > $starNum['three']){
                $params['star'] = "三星下";
            }elseif($params['cost'] >= $starNum['four_up']&&$params['cost'] < $starNum['four']){
                $params['star'] = "四星上";
            }elseif($params['cost'] <= $starNum['four_dwon']&&$params['cost'] > $starNum['four']){
                $params['star'] = "四星下";
            }
        }else{
            $params['star'] = "无激活";
        }
        return $params['star'];
    }


    //星级划分
    private function _getStar(){
        //查询出星级
        $getStar = Loader::model('Newtest')->getStar();
        $res = array();
        foreach ($getStar as $key => $value) {
            $one_start = explode('-',$value['yistar']);
            $two_start = explode('-',$value['erstar']);
            $three_start = explode('-',$value['sanstar']);
            $four_start = explode('-',$value['sistar']);
            $fives_start = explode('-',$value['wustar']);
            //一星
            $res[$value['type']]['one'] = $one_start['1'];
            //二星
            $two = ($two_start['0']+$two_start['1'])/2;
            $res[$value['type']]['two'] = $two;
            $res[$value['type']]['two_up'] = $two_start['1'];//上
            $res[$value['type']]['two_dwon'] = $two_start['0'];//下
            //三星
            $three = ($three_start['0']+$three_start['1'])/2;
            $res[$value['type']]['three'] = $three;
            $res[$value['type']]['three_up'] = $three_start['1'];//上
            $res[$value['type']]['three_dwon'] = $three_start['0'];//下

            //四星
            $four = ($four_start['0']+$four_start['1'])/2;
            $res[$value['type']]['four'] = $four;
            $res[$value['type']]['four_up'] = $four_start['1'];//上
            $res[$value['type']]['four_dwon'] = $four_start['0'];//下

            //五星
            $res[$value['type']]['fives'] = $fives_start['0'];
            $res[$value['type']]['type'] = $value['type'];

        }
        return $res;
    }

    public function starAdd()
    {
        $request = Request::instance();
        if($request->post()){
            $params = $request->post();
            $add = Loader::model('Newtest')->AddStar($params);
            if($add>0){
                $this->_success('/admin/newtest/star','添加成功');
            }else{
                $this->_error('添加失败');
            }
        }else{
            return $this->fetch('star-add');
        }
    }

    public function starDelete()
    {
        $request = Request::instance();
        $params = $request->post();
            Db::name('stat_copy')->where('id',$params['type'])->delete();
            $res = Db::name('star')->where('type',$params['type'])->delete();
            if($res){
                echo 1;die;
            }else{
                echo 2;die;
            }
    }

}
