<?php
/** 会员管理
 * date   2016-6-15
 */
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Session;
use think\Cache;
use think\Cookie;
use think\Hook;
use app\user\api\DelApi as DelApi;
use app\user\common\Encrypt as Encrypt;

class Users extends Admin
{
    public function _initialize()
    {
        parent::_initialize();
        header("Cache-control: private");
    }

    /**
     * 站长管理
     */
    public function webmaster()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');

		if(empty($pageParam)){
            $pageParam = array('status' => 'users_all',
                'index' => '',
                'search'=> '' ,
                'cpd_type'=>'');
        }else{
            $pageParam['search'] = empty($pageParam['search']) ? '' : $pageParam['search'];
            $pageParam['index'] = empty($pageParam['index']) ? '' : $pageParam['index'];
            $pageParam['status'] = !isset($pageParam['status']) ? 'users_all' : $pageParam['status'];
            $pageParam['cpd_type'] = !isset($pageParam['cpd_type']) ? '' : $pageParam['cpd_type'];
        }

        $total = Loader::model('Users')->getListCount1('1',$pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$pageParam);
        $res = Loader::model('Users')->getWebList($Page->firstRow,$Page->listRows,'1',$pageParam);
        $res = $this->_getWebMoney($res); //站长余额
        if(!empty($res)) {
            $params['day'] = date("Y-m-d");
            $params['yesterday'] = date("Y-m-d",strtotime("-1 day"));
            $cache_model = new Cache();
            //$uid_money_now = $cache_model->get('uid_money_now');
            //$cpd_money_now = $cache_model->get('cpd_money_now');
            $uid_money_yes = $cache_model->get('uid_money_yes');
            //今日收入缓存10分钟
            //if(empty($uid_money_now)){
                //查今日收入
                $now = Loader::model('Users')->webreportNow($params);
                $today = array_column($now,'sumpay','uid');
                $cpd = array_column($now,'cpd','uid');
                //$cache_model->set('uid_money_now',$today,600);//设置缓存
                //$cache_model->set('cpd_money_now',$cpd,600);//设置缓存 
            //}else{
             //   $today = $uid_money_now;
              //  $cpd = $cpd_money_now;
            //}
            //昨日收入缓存到12.00
            if(empty($uid_money_yes)){
                //查昨日收入
                $yesday = Loader::model('Users')->webReportYes($params);
                $Yesterday = array_column($yesday,'sumpay','uid');
                $time = mktime(23,59,59,date('m'),date('d'),date('Y'))-time();
                $cache_model->set('uid_money_yes',$Yesterday,$time);//设置缓存
            }else{
                $Yesterday = $uid_money_yes;
            }
            
            foreach($res as $key=> $value){
                //今日消耗 拼入数组
                if(array_key_exists($res[$key]['uid'],$today)){
                    $res[$key]['today'] = $today[$res[$key]['uid']];
                }else{ 
                    $res[$key]['today'] = '0.00';
                }
                //今日包天 拼入数组
                if(array_key_exists($res[$key]['uid'],$cpd)){

                    $res[$key]['tocpd'] = $cpd[$res[$key]['uid']];
                }else{ 
                    $res[$key]['tocpd'] = '0.00';
                }
                //昨日消耗 拼入数组
                if(array_key_exists($res[$key]['uid'],$Yesterday)){
                    $res[$key]['Yesterday'] = $Yesterday[$res[$key]['uid']];
                }else{ 
                    $res[$key]['Yesterday'] = '0.00';
                }
            }
        }
        if(!isset($pageParam['type'])){
            $pageParam['type'] = '';
        }
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        $this->assign('cpd_type',$pageParam['cpd_type']);
        $this->assign('params',$pageParam);
        $this->assign('page',$show);
        $this->assign('master_list',$res);
		$this->assign('one',$pageParam);
        return $this->fetch('webmaster-list');
    }

    /**
     * 站长业绩排序
     */
    public function websort()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $param = $request->param('');
        //初始化数据，防止为空报错
        $param['time'] = empty($param['time']) ? date("Y-m-d") : $param['time'];
        $param['sort_day'] = empty($param['sort_day']) ? 1 : $param['sort_day'];
        $param['sort_type'] = empty($param['sort_type']) ? 1 : $param['sort_type'];
        //今日查询log表，其他日期查询new表
        if($param['time'] == date("Y-m-d")){
            //查询今日的站长消耗，并判断是否按今日排序
            $nowsumpay = Loader::model('Users')->gettodaysumpay($param);
        }else{
            //查询当前日的站长消耗，并判断是否按当前日排序(传入1是判断是否按照当前日排序)
            $nowsumpay = Loader::model('Users')->getsumpay($param,$param['time'],1);
        }
        //查询上一日的站长消耗，并判断是否按上一日排序(传入2是判断是否按照上一日排序)
        $lastday = date("Y-m-d",(strtotime($param['time']) - 3600*24));
        $lastsumpay = Loader::model('Users')->getsumpay($param,$lastday,2);
        //拼接数据并且排序
        $data = $this->_getdata($param,$nowsumpay,$lastsumpay);

        $this->assign('one',$data);
        $this->assign('param',$param);
        return $this->fetch('web-sort');
    }

    public function iframe()
    {
        return $this->fetch('search');
    }

    /**
     * 广告商管理
     */
    public function advertiser()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        $total = Loader::model('Users')->getListCount1('2',$pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$pageParam);
        $res = Loader::model('Users')->getList1($Page->firstRow,$Page->listRows,'2',$pageParam);
        if(!empty($res)) { 
            $cache_model = new Cache();
            //$adv_money_now = $cache_model->get('adv_money_now');
            $adv_money_yes = $cache_model->get('adv_money_yes');
            $params['day'] = date("Y-m-d");
            $params['yesday'] = date("Y-m-d",strtotime("-1 day"));
            //今日消耗缓存10分钟
            //if(empty($adv_money_now)){
                //今日消耗
                $now = Loader::model('Users')->advReportNow($params);
                $today = array_column($now,'sumadvpay','uid');
               // $cache_model->set('adv_money_now',$today,600);//设置缓存
            //}else{
              //  $today = $adv_money_now;
            //}
            //昨日消耗缓存到12.00
            if(empty($adv_money_yes)){
                //昨日消耗
                $yesday = Loader::model('Users')->advReportYes($params);
                $Yesterday = array_column($yesday,'sumadvpay','uid');
                $time = mktime(23,59,59,date('m'),date('d'),date('Y'))-time();
                $cache_model->set('adv_money_yes',$Yesterday,$time);//设置缓存
            }else{
                $Yesterday = $adv_money_yes;
            }
            foreach($res as $key=> $value){
                //今日消耗 拼入数组
                if(array_key_exists($res[$key]['uid'],$today)){
                    $res[$key]['Today'] = $today[$res[$key]['uid']];
                    $res[$key]['money'] -= $res[$key]['Today'];
                }else{ 
                    $res[$key]['Today'] = '0.00';
                }
                 //昨日消耗 拼入数组
                if(array_key_exists($res[$key]['uid'],$Yesterday)){
                    $res[$key]['Yesterday'] = $Yesterday[$res[$key]['uid']];
                }else{ 
                    $res[$key]['Yesterday'] = '0.00';
                }
            }
           
        }
        if(!isset($pageParam['type'])){
            $pageParam['type'] = '';
        }
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        $this->assign('params',$pageParam);
        $this->assign('page',$show);
        $this->assign('advertiser_list',$res);
        return $this->fetch('advertiser-list');
    }

    /**
     * 广告商充值
     */
    public function advertiserPay()
    {
        $request = Request::instance();
        $params = $request->post();

        if($request->isPost()){
            //查询广告商原始金额
            $adv_res = Loader::model('Users')->advUserOne($params['username']);

            $advertiserPay = Loader::model('Users')->advertiserPay($params,$adv_res);
            if($advertiserPay>0){
                //组建字段数组
                $data =array(
                    'uid'       => $adv_res['uid'],
                    'clearingadmin' => $_SESSION['think']['uname'],
                    'money'    => $params['money'],
                    'payinfo'    => $params['payinfo'],
                    'type'      => $adv_res['type'],
                    'ctime'     => time(),
                    'day'       => date('Y-m-d',time()),

                );
                // clearingadmin 充值人  $adv_res 充值人信息    $params  充值金额
                $paylog = Loader::model('Users')->paylog($data);

                //写操作日志
                $this->logWrite('0001',$params['username'],$data['money']);
                $this->redirect('/admin/users/advertiser');
            }
        }
        return $this->fetch('advertiser-pay');
    }

    /**
     * 广告商充值查询（查询是否有此广告商）
     */
    public function advUserOne()
    {
        $request = Request::instance();
        $params = $request->post();
        //查询是否有次广告商
        $advUser_find = Loader::model('Users')->advUserOne($params['username']);
        //查询充值最低限额 least_money
        $least = Loader::model('Users')->least();
        if(empty($least)){
            $this->redirect('/admin/users/advertiser');
        }
        //广告商返回值
        if(empty($params['money'])){
            if($advUser_find == true){
                echo $advUser_find['uid'];
            }else{
                echo 0;
            }
        }
        //小于最低充值金额返回值
        if(!empty($params['money'])) {
            if ($params['money'] >= $least['least_money']) {
                echo 2;
            } else {
                echo 1;
            }
        }
    }

    /**
     * 客服管理
     */
    public function customer()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        if(!isset($pageParam['p'])){
            $pageParam['p'] = '1';
        }
        $params = array('type' => '3');
        $res = Loader::model('Users')->getList('3',$pageParam);
        //后分页
        $total = count($res);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$pageParam);
        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        //查询名下人员和名下厂商
        foreach ($res as $key => $value) {
            //查询名下人员和名下厂商
            $num = Loader::model('Users')->getNumber($value);
            $number[] = $num[0];
            //数组合并
            $res[$key]['num']=$number[$key]['number'];
        }
        if(!isset($pageParam['type'])){
            $pageParam['type'] = '';
        }
        $this->assign('users_list',$res);
        $this->assign('pageParam',$pageParam);
        $this->assign('page',$show);
        $this->assign('one',$params);

        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        
        return $this->fetch('users-list');
    }
     /**
     * 客服当月业绩
     */
    public function ajaxcustomer()
    {
        $request = Request::instance();
        $pageParam = $request->param('');
        $params = array('type' => '3'); 
        $res = Loader::model('Users')->getList('3',$pageParam);
        //后分页
        $total = count($res);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$pageParam);
        $res = array_slice($res,$Page->firstRow,$Page->listRows);

        //今日业绩
        $month['monthBegin'] = date('Y-m-d');
        $month['monthEnd'] = date('Y-m-d');
        $cache_model = new Cache();
        //处理今日业绩
        //$kf_nowRes = $cache_model->get('kf_nowRes');
        //if(empty($kf_nowRes)){
            $kf_nowRes = Loader::model('Users')->getKFList('3',$month,$pageParam);
            //$cache_model->set('kf_nowRes',$kf_nowRes,600);//设置缓存
        //}
        //处理昨日之前的业绩
        $kf_money = $cache_model->get('kf_money');
        if(empty($kf_money)){
            
            //昨日之前的业绩
            $month['monthBegin'] =  date('Y-m-d',mktime(0,0,0,date('m'),1,date('Y')));
            $month['monthEnd'] = date("Y-m-d",strtotime("-1 day"));
            $BeginRes = Loader::model('Users')->getKFList('3',$month,$pageParam);
            //设置缓存
            $Beginmoney= array_column($BeginRes,'money','uid');
            $cache_model->set('kf_money',$Beginmoney,mktime(23,59,59,date('m'),date('d'),date('Y'))-time());//设置缓存
            //将今日业绩和昨日业绩相加
            foreach($kf_nowRes as $key=>$value){
                foreach($BeginRes as $k=>$v){
                    if($value['uid'] == $v['uid']){
                        $BeginRes[$k]['money'] += $kf_nowRes[$key]['money'];
                        unset($kf_nowRes[$key]);
                    }
                }
            }
            $BeginRes = array_merge($kf_nowRes,$BeginRes);
            $Beginmoney= array_column($BeginRes,'money','uid');
            $sumpay = array();
            foreach($res as $key => $value) {
                //查询名名下厂商
                $num = Loader::model('Users')->getNumber($value);
                $number[] = $num[0];
                //数组合并
                $res[$key]['num']=$number[$key]['number'];
                $uid = $value["uid"];
                if($res[$key]['num']){
                    if(isset($Beginmoney[$uid])){
                    $sumpay[$key] = floor($Beginmoney[$uid]*100)/100;
                    }else{

                       $sumpay[$key] = "0.00";
                    };
                   
                }else{
                    $sumpay[$key] = "0.00";
                }
            }

            echo json_encode($sumpay); 
        }else{
            $nowMoney = array_column($kf_nowRes,'money','uid');
            $totalmoney= array();
            foreach($nowMoney as $ke=>$val){
                if(isset($kf_money[$ke])){
                    $totalmoney[$ke] = $nowMoney[$ke] + $kf_money[$ke];
                }else{
                    $totalmoney[$ke] = $nowMoney[$ke];
                }
            }
            foreach($kf_money as $ke=>$val){
                if(!isset($nowMoney[$ke])){
                     $totalmoney[$ke] = $kf_money[$ke];
                }
            }
            $sumpay = array();
            foreach($res as $key => $value) {
                //查询名名下厂商
                $num = Loader::model('Users')->getNumber($value);
                $number[] = $num[0];
                //数组合并
                $res[$key]['num']=$number[$key]['number'];
                $uid = $value["uid"];
                if($res[$key]['num']){
                    if(isset($totalmoney[$uid])){
                        $sumpay[$key] = floor($totalmoney[$uid]*100)/100;
                    }else{
                       $sumpay[$key] = "0.00";
                    };
                   
                }else{
                    $sumpay[$key] = "0.00";
                }
            }
            echo json_encode($sumpay);  
        }      
    }

    /**
     * 转化时间格式并且统计待审会员的个数
     */
    /*private function _getMoney($web,$month)
    {
        $array = array();
        //查询该客服下各个站长的所有佣金
        foreach ($web as $key => $value) {
            $res[$value['uid']] = Loader::model('Users')->getWebPay($value['uid'],$month);
        }
        $lastMoney = 0;
        if(!empty($res)){
            foreach ($res as $key => $value) {
                if(!empty($value)){
                //计算客服的当月业绩   包天请况下以包天计算
                foreach ($value as $get => $sumpay) {
                    $lastMoney = $sumpay['money'] + $lastMoney;
                }

                }
            }
        }else{
            $lastMoney = 0;
        }
        return $lastMoney;
    }*/

    /**
     * 商务管理
     */
    public function business()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        if(empty($pageParam)){
            $pageParam['p'] = '1';
        }
        $res = Loader::model('Users')->getList('4',$pageParam);
        //后分页
        $total = count($res);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$pageParam);
        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        foreach($res as $key => $value) {
            //查询名名下厂商
            $num = Loader::model('Users')->getNumber($value);
            $number[] = $num[0];
            //数组合并
            $res[$key]['num']=$number[$key]['number'];
        }
        $this->assign('users_list',$res);
        $this->assign('pageParam',$pageParam);
        $this->assign('page',$show);
        return $this->fetch('users-sw-list');
    }

    /**
     * 游戏部管理
     */
    public function gamelist()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        if(empty($pageParam)){
            $pageParam['p'] = '1';
        }
        $params = array('type' => '5');
        $total = Loader::model('Users')->getListCount('5');
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action());
        $res = Loader::model('Users')->getList($Page->firstRow,$Page->listRows,'5',$pageParam);

        $this->assign('users_list',$res);
        $this->assign('pageParam',$pageParam);
        $this->assign('page',$show);
        $this->assign('one',$params);
        return $this->fetch('game-list');
    }

     /**
     * 商务当月业绩
     */
    public function ajaxbusiness()
    {   
        $request = Request::instance();
        $pageParam = $request->param('');
         $res = Loader::model('Users')->getList('4',$pageParam);
        //后分页
        $total = count($res);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$pageParam);
        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        //今日业绩
        $cache_model = new Cache();
        $month['monthBegin'] = date('Y-m-d');
        $month['monthEnd'] = date('Y-m-d');
        //处理今日业绩
        //$sw_nowRes = $cache_model->get('sw_nowRes');
        //if(empty($sw_nowRes)){
            $sw_nowRes = Loader::model('Users')->getSWList('4',$month);
         //   $cache_model->set('sw_nowRes',$sw_nowRes,600);//设置缓存
        //}
        //处理昨日之前的业绩
        $sw_money = $cache_model->get('sw_money');

        if(empty($sw_money)){
            
            //今日之前的业绩
            $month['monthBegin'] =  date('Y-m-d',mktime(0,0,0,date('m'),1,date('Y')));
            $month['monthEnd'] = date("Y-m-d",strtotime("-1 day"));
            $BeginRes = Loader::model('Users')->getSWList('4',$month);
            //设置缓存
            $Beginmoney= array_column($BeginRes,'money','uid');
            $cache_model->set('sw_money',$Beginmoney,mktime(23,59,59,date('m'),date('d'),date('Y'))-time());//设置缓存
            //将今日业绩和昨日业绩相加
            foreach($sw_nowRes as $key=>$value){
                foreach($BeginRes as $k=>$v){
                    if($value['uid'] == $v['uid']){
                        $BeginRes[$k]['money'] += $sw_nowRes[$key]['money'];
                        unset($sw_nowRes[$key]);
                    }
                }
            }
            $BeginRes = array_merge($sw_nowRes,$BeginRes);
            $Beginmoney= array_column($BeginRes,'money','uid');
            $sumpay = array();
            foreach($res as $key => $value) {
                //查询名名下厂商
                $num = Loader::model('Users')->getNumber($value);
                $number[] = $num[0];
                //数组合并
                $res[$key]['num']=$number[$key]['number'];
                $uid = $value["uid"];
                if($res[$key]['num']){
                    if(isset($Beginmoney[$uid])){
                    $sumpay[$key] = floor($Beginmoney[$uid]*100)/100;
                    }else{
                       $sumpay[$key] = "0.00";
                    };
                   
                }else{
                    $sumpay[$key] = "0.00";
                }
            }
            echo json_encode($sumpay);
        }else{
            $nowMoney = array_column($sw_nowRes,'money','uid');
            $totalmoney= array();
            foreach($nowMoney as $ke=>$val){
                if(isset($sw_money[$ke])){
                    $totalmoney[$ke] = $nowMoney[$ke] + $sw_money[$ke];
                }else{
                    $totalmoney[$ke] = $nowMoney[$ke];
                }
            }
            foreach($sw_money as $ke=>$val){
                if(!isset($nowMoney[$ke])){
                     $totalmoney[$ke] = $sw_money[$ke];
                }
            }
            $sumpay = array();
            foreach($res as $key => $value) {
                //查询名名下厂商
                $num = Loader::model('Users')->getNumber($value);
                $number[] = $num[0];
                //数组合并
                $res[$key]['num']=$number[$key]['number'];
                $uid = $value["uid"];
                if($res[$key]['num']){
                    if(isset($totalmoney[$uid])){
                        $sumpay[$key] = floor($totalmoney[$uid]*100)/100;
                    }else{
                       $sumpay[$key] = "0.00";
                    };
                   
                }else{
                    $sumpay[$key] = "0.00";
                }
            }
           echo json_encode($sumpay);  
        }       
    }

    /**
     * users add
     */
    public function webAdd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        //验证数据
        $validate = Loader::validate('Users');
        if(!$validate->check($params)){
            $this->error($validate->getError());
        }
        $res = Loader::model('Users')->validation($params,1);
        if(!empty($res)){
            $this->redirect('echoError');
        }
        //组装数据
        $data = $this->_dataAdd($params);

        //将新建的用户信息插入到数据库中
        $res = Loader::model('Users')->add($data);
        if($res>0){
            //写操作日志
            $this->logWrite('0002',$data['username']);
            $this->redirect('webmaster');
        }else{
            $this->_error();
        }
    }

    /**
     * users add
     */
    public function advAdd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        //验证数据
        $validate = Loader::validate('Users');
        if(!$validate->check($params)){
            $this->error($validate->getError());
        }
        $res = Loader::model('Users')->validation($params,2);
        if(!empty($res)){
            $this->redirect('echoError');
        }
        //组装数据
        $data = $this->_dataAdd($params);
        //将新建的用户信息插入到数据库中
        $res = Loader::model('Users')->add($data);
        if($res>0){
            //写操作日志
            $this->logWrite('0003',$data['username']);
            $this->redirect('advertiser');
        }else{
            $this->_error();
        }
    }

    /**
     * users add
     */
    public function cusAdd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        //验证数据
        $validate = Loader::validate('Users');
        if(!$validate->check($params)){
            $this->error($validate->getError());
        }
        $res = Loader::model('Users')->validation($params,3);
        if(!empty($res)){
            $this->redirect('echoError');
        }
        //组装数据
        $data = $this->_dataAdd($params);

        //将新建的用户信息插入到数据库中
        $res = Loader::model('Users')->add($data);

        if($res>0){
            //写操作日志
            $this->logWrite('0004',$data['username']);
            $this->redirect('customer');
        }else{
            $this->_error();
        }
    }

    /**
     * users add
     */
    public function busAdd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        //验证数据
        $validate = Loader::validate('Users');
        if(!$validate->check($params)){
            $this->error($validate->getError());
        }
        $res = Loader::model('Users')->validation($params,4);
        if(!empty($res)){
            $this->redirect('echoError');
        }
        //组装数据
        $data = $this->_dataAdd($params);

        //将新建的用户信息插入到数据库中
        $res = Loader::model('Users')->add($data);

        if($res>0){
            //写操作日志
            $this->logWrite('0005',$data['username']);
            $this->redirect('business');
        }else{
            $this->_error();
        }
    }

    /**
     * users game add
     */
    public function gameadd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        //验证数据
        $validate = Loader::validate('Users');
        if(!$validate->check($params)){
            $this->error($validate->getError());
        }
        $res = Loader::model('Users')->validation($params,5);
        if(!empty($res)){
            $this->redirect('echoError');
        }
        //组装数据
        $data = $this->_dataAdd($params);

        //将新建的用户信息插入到数据库中
        $res = Loader::model('Users')->add($data);

        if($res>0){
            //写操作日志
            $this->logWrite('0004',$data['username']);
            $this->redirect('gamelist');
        }else{
            $this->_error();
        }
    }

    /**
     * 激活/锁定
     */
    public function activate()
    {
        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        $res = Loader::model('Users')->updateStatus($params['uid'],$params['status']);
        if($res>0){
            //判断操作日志的写入内容
            if($params['status'] == 1){
                //写操作日志
                $this->logWrite('0006',$params['uid']);
            }else{
                //若锁定站长 则将其名下的广告位和网站都锁定
                Loader::model('Users')->updateAdzStatus($params['uid'],$params['status']);
                //写操作日志
                $this->logWrite('0007',$params['uid']);
            }
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 直接在页面上修改总金额等
     */
    public function editor()
    {
        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        if(Request::instance()->isPost()) {
            $res = Loader::model('Users')->editor($params);
            if ($res >= 0) {
                $this->_success();
            }else{
                $this->error('修改失败');
            }
        }else{
            //读取修改金额页面
            $params = input('');
            $this->assign('one',$params);
            return $this->fetch('users-editor');
        }
    }

    /**
     * 站长列表直接在页面上修改扣量
     */
    public function webDeduction()
    {
//        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        if(Request::instance()->isPost()) {
            $data['uid'] = $params['uid'];
            $data['web_deduction'] = $params['money'];
            $res = Loader::model('Users')->webDeduction($data);
            if ($res >= 0) {
                $this->_success();
            }else{
                $this->error('修改失败');
            }
        }
    }

    /**
     * 广告商列表直接在页面上修改扣量
     */
    public function advDeduction()
    {
//        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        if(Request::instance()->isPost()) {
            $data['uid'] = $params['uid'];
            $data['adv_deduction'] = $params['money'];
            $res = Loader::model('Users')->webDeduction($data);
            if ($res >= 0) {
                $this->_success();
            }else{
                $this->error('修改失败');
            }
        }
    }

    /**
     * 删除
     */
    public function del()
    {
        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        //查看站长用户旗下是否有网站,有则提醒先删除网站
        $site_res = Loader::model('Users')->siteOne($params['uid']);
        if(!empty($site_res)){

            $this->_success(array(),$info='先删除站长旗下的网站');
        }else{
            $UserApi = new DelApi();
            $UserApi->del($params['uid'],'users');
            $res = Loader::model('Users')->delOne($params['uid']);
            if($res>0){
                //写操作日志
                $this->logWrite('0008',$params['uid']);
                $this->_success(array(),$info='删除成功');
            }else{
                $this->_error('删除失败');
            }
        }

    }

    /**
    * 站长编辑
    */
    public function webmasteredit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            $uid = $params['uid'];
            //组装数据
            $data = $this->_dataMasterEdit($params);
            $res = Loader::model('Users')->editOne($uid,$data);
            if($res>=0){
                //写操作日志
                $this->logWrite('0009',$uid);
                $this->redirect('users/webmaster');
            }else{
                $this->_error('error');
            }
        }else{
            $uid = $request->get('uid');
            $type = $request->get('type');
            //获取用户数据
            $res = Loader::model('Users')->getOne($uid);
            if(!empty($res)){
                //获取客服的名称和id
                $num = Loader::model('Users')->getNumberList($type);
                $this->assign('edit',$num);
                $this->assign('one',$res);
                $judge_name = $request->session('uname');
                $this->assign('judge_name',$judge_name);
                return $this->fetch('webmaster-editor');
            }else{
                $this->redirect('users/webmaster');
            }
        }
    }

    /**
     * 普通编辑
     */
    public function edit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            $uid = $params['uid'];
            $type = $params['type'];
            //组装数据,type=2为广告商
            if($type == '2'){
                $data = $this->_dataEditAdv($params);
            } else{
                $data = $this->_dataEdit($params);
            }
            $res = Loader::model('Users')->editOne($uid,$data);
            if($res>=0){
                if($type == '2'){
                    //写操作日志
                    $this->logWrite('0010',$uid);
                    $this->redirect('advertiser');
                }elseif($type == '3'){
                    //写操作日志
                    $this->logWrite('0011',$uid);
                    $this->redirect('customer');
                }elseif($type == '5'){
                    $this->redirect('gamelist');
                }else{
                    //写操作日志
                    $this->logWrite('0012',$uid);
                    $this->redirect('business');
                }
            }else{
                $this->_error('error');
            }
        }else{
            $uid = $request->get('uid');
            $type = $request->get('type');
            $res = Loader::model('Users')->getOne($uid);
            if(!empty($res)){
                if($type == '2'){
                    $num = Loader::model('Users')->getNumberList($type);
                    $this->assign('edit',$num);
                }
                $this->assign('one',$res);
                if($type == '3'){
                    $judge_name = $request->session('uname');
                    $this->assign('judge_name',$judge_name);
                    return $this->fetch('users-customer-editor');
                }else{
                    return $this->fetch('users-editor');
                }

            }else{
                if($type == '2'){
                    $this->redirect('advertiser');
                }elseif($type == '3'){
                    $this->redirect('customer');
                }else{
                    $this->redirect('business');
                }
            }
        }
    }

    /**
     * 名下人员和名下广告商
     */
    public function belong()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        $pageParam = $request->param('');
        $total = Loader::model('Users')->getBelongListCount($params['uid'],$params['type']);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$params);
        $res = Loader::model('Users')->getBelongList($Page->firstRow,$Page->listRows,$params['uid'],$params['type']);
        if($params['type']-2 == 1){
            if(!empty($res)) {
                $params['day'] = date("Y-m-d");
                $params['yesterday'] = date("Y-m-d",strtotime("-1 day"));
                $cache_model = new Cache();
                $uid_money_yes = $cache_model->get('uid_money_yes');
                //查今日收入
                $now = Loader::model('Users')->webreportNow($params);
                $today = array_column($now,'sumpay','uid');
                $cpd = array_column($now,'cpd','uid');
                //昨日收入缓存到12.00
                if(empty($uid_money_yes)){
                    //查昨日收入
                    $yesday = Loader::model('Users')->webReportYes($params);
                    $Yesterday = array_column($yesday,'sumpay','uid');
                    $time = mktime(23,59,59,date('m'),date('d'),date('Y'))-time();
                    $cache_model->set('uid_money_yes',$Yesterday,$time);//设置缓存
                }else{
                    $Yesterday = $uid_money_yes;
                }

                foreach($res as $key=> $value){
                    //今日消耗 拼入数组
                    if(array_key_exists($res[$key]['uid'],$today)){
                        $res[$key]['today'] = $today[$res[$key]['uid']];
                    }else{
                        $res[$key]['today'] = '0.00';
                    }
                    //今日包天 拼入数组
                    if(array_key_exists($res[$key]['uid'],$cpd)){

                        $res[$key]['tocpd'] = $cpd[$res[$key]['uid']];
                    }else{
                        $res[$key]['tocpd'] = '0.00';
                    }
                    //昨日消耗 拼入数组
                    if(array_key_exists($res[$key]['uid'],$Yesterday)){
                        $res[$key]['Yesterday'] = $Yesterday[$res[$key]['uid']];
                    }else{
                        $res[$key]['Yesterday'] = '0.00';
                    }
                }
            }
            if(!isset($pageParam['type'])){
                $pageParam['type'] = '';
            }
            $judge_name = $request->session('uname');
            $this->assign('judge_name',$judge_name);
            $this->assign('params',$pageParam);
            $this->assign('page',$show);
            $this->assign('master_list',$res);
            return $this->fetch('webmaster-list');
        }else{
            if(!empty($res)) {
                $cache_model = new Cache();
                $adv_money_yes = $cache_model->get('adv_money_yes');
                $params['day'] = date("Y-m-d");
                $params['yesday'] = date("Y-m-d",strtotime("-1 day"));
                //今日消耗
                $now = Loader::model('Users')->advReportNow($params);
                $today = array_column($now,'sumadvpay','uid');
                //昨日消耗缓存到12.00
                if(empty($adv_money_yes)){
                    //昨日消耗
                    $yesday = Loader::model('Users')->advReportYes($params);
                    $Yesterday = array_column($yesday,'sumadvpay','uid');
                    $time = mktime(23,59,59,date('m'),date('d'),date('Y'))-time();
                    $cache_model->set('adv_money_yes',$Yesterday,$time);//设置缓存
                }else{
                    $Yesterday = $adv_money_yes;
                }
                foreach($res as $key=> $value){
                    //今日消耗 拼入数组
                    if(array_key_exists($res[$key]['uid'],$today)){
                        $res[$key]['Today'] = $today[$res[$key]['uid']];
                        $res[$key]['money'] -= $res[$key]['Today'];
                    }else{
                        $res[$key]['Today'] = '0.00';
                    }
                    //昨日消耗 拼入数组
                    if(array_key_exists($res[$key]['uid'],$Yesterday)){
                        $res[$key]['Yesterday'] = $Yesterday[$res[$key]['uid']];
                    }else{
                        $res[$key]['Yesterday'] = '0.00';
                    }
                }
            }
            if(!isset($pageParam['type'])){
                $pageParam['type'] = '';
            }
            $judge_name = $request->session('uname');
            $this->assign('judge_name',$judge_name);
            $this->assign('params',$pageParam);
            $this->assign('page',$show);
            $this->assign('advertiser_list',$res);
            return $this->fetch('advertiser-list');
        }
    }

    /**
     * 站长页面和广告商管理点击站长或者广告商名称
     */
    public function show()
    {
        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        $res = Loader::model('Users')->getOne($params['uid']);
        if($params['type'] == 1){
            $this->assign('one',$res);
            return $this->fetch('master-show');
        } else{
            $this->assign('one',$res);
            return $this->fetch('advertiser-show');
        }
    }


    /**
     * 我的业绩
     */
    public function performance()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->param('');
        $params['day'] = date('Y-m-d');
        $params['frontDay'] = empty($params['time']) ? date('Y-m-01') : substr($params['time'], 0,10);
        // $params['endDay'] = empty($params['time']) ? date('Y-m-d') : substr($params['time'], 10,20);
        $params['endDay'] = empty($params['time']) ? date("Y-m-d",strtotime("-1 day")) : substr($params['time'], 10,20);
        $params['list'] = Loader::model('Users')->getNumList($params);

        $todayRes = array();
        if($params['type'] == 3){
            //查询该客服下所有的站长
            $web = Loader::model('Users')->getAdvs($params['uid'],1);
            $num = 0;
            foreach($web as $k=>$v){
                $array[$num++] = $v['uid'];
            }
            if(!empty($array)){
                $text_advid = implode(',',$array);
                //客服当月业绩  除今日
                $res = Loader::model('Users')->getCusNumPay($text_advid,$params);
                //客服今日业绩
                if($params['endDay'] == $params['day']){
                    $todayRes = Loader::model('Users')->getCusNumPayForDay($text_advid,$params);
                }else{
                    $todayRes = array();
                }
            }else{
                $res = array();
            }
        }else{
            //查询该商务下所有的广告商
            $web = Loader::model('Users')->getAdvs($params['uid'],2);
            $num = 0;
            foreach($web as $k=>$v){
                $array[$num++] = $v['uid'];
            }
            if(!empty($array)){
                $text_advid = implode(',',$array);
                //商务除今日的当月业绩
                $res = Loader::model('Users')->getBusNumPay($text_advid,$params);
                //商务今日业绩
                if($params['endDay'] == $params['day']){
                    $todayRes = Loader::model('Users')->getBusNumPayForday($text_advid,$params);
                }else{
                    $todayRes = array();
                }
            }else{
                $res = array();
            }
        }

        //统计我的业绩的数据
        $date['num'] = $this->_getPerformance($params['type'],$res,$todayRes);

        //拼接折线图所需要的数据
        $date['chart'] = $this->_getchart($params['type'],$date['num']);
        //处理折线小数点
        $datenum = "";
        foreach (explode(',',$date['chart']['num']) as $value ) {
            $datamoney = process_decimal($value);
            $datenum .= $datamoney.',';
        }
        $date['chart']['num'] = $datenum ;
        //处理时间插件
        $date['time']  = $this->_getTime(date("Y-m-d"),$params);
        //业绩汇总
        $summoney =array_sum(array_column($date['num'],'money'));
        
        $this->assign('summoney',$summoney);
        $this->assign('params',$params);
        $this->assign('data',$date);
        return $this->fetch('users-performance');
    }

    /**
     * 广告商管理下查看计划
     */
    public function usersToplan()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        $total = Loader::model('Users')->planLstCount($params);
        $pageParam = $request->param('');
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = 'uid';
        }
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$params);
        $res = Loader::model('Users')->getPlanLst($Page->firstRow,$Page->listRows,$params);

        //文字类的计划和普通计划区分
        foreach($res as $key => $val){
            if(strstr($val['plan_name'],'文字')){
                $res[$key]['urlType'] = '1';
            }else{
                $res[$key]['urlType'] = '0';
            }
        }

        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);

        return $this->fetch('plan/plan-list');
    }

    /**
     * 广告商管理下查看广告
     */
    public function usersToAds()
    {   
        $request = Request::instance();
        $uid = Request::instance()->get('uid');
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        $res = Loader::model('Users')->adPlanLst($uid);
        $data['img'] = Loader::model('Ads')->getImgService();
        $data['img'] = empty($data['img']['img_server']) ? array('img_server' => '/') : $data['img'];
        $data['page'] = '';
        if(!isset($pageParam['ads'])){
            $pageParam['ads'] = 'uid';
        }
        $this->assign('ads',$pageParam);
        $this->assign('ads_list',$res);
        $this->assign('data',$data);

        return $this->fetch('ads/ads-list');
    }

    /**
     *  站长管理下查看网站管理列表
     */
    public function usersToSite()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        $pageParam = $request->param('');
        $total = Loader::model('Users')->siteCount($params);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$params);
        $siteList = Loader::model('Users')->siteList($Page->firstRow,$Page->listRows,$params);

        $todayViews = Loader::model('site')->siteViews(date("Y-m-d"));
        $yesterdayViews = Loader::model('site')->siteViews(date("Y-m-d",strtotime("-1 day")));
        $siteList = $this->_getViews($siteList,$todayViews,$yesterdayViews);
        if(!isset($pageParam['index'])){
            $pageParam['index'] = 'username';
        }
        $this->assign('one',$pageParam);
        $this->assign('site_list',$siteList);
        $this->assign('page',$show);

        return $this->fetch('site/site-list');
    }

    /**
     * 站长管理下查看广告位列表
     */
    public function usersToAdzone()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        $pageParam = $request->param('');
        $pageParam['search'] = empty($pageParam['search']) ? '' : $pageParam['search'];
        $pageParam['adzone'] = empty($pageParam['adzone']) ? '' : $pageParam['adzone'];
        //处理投放设备,投放尺寸,投放模式没有值得情况
        $pageParam['system_type'] = empty($pageParam['system_type']) ? '-1' : $pageParam['system_type'];
        $pageParam['adzsize'] = empty($pageParam['adzsize']) ? '-1' : $pageParam['adzsize'];
        $pageParam['adtpl_id'] = empty($pageParam['adtpl_id']) ? '-1' : $pageParam['adtpl_id'];
        //获取所有的广告模式
        $pageParam['admode'] = Loader::model('site')->getAdtype();
        $total = Loader::model('Users')->adzoneCount($params);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$params);
        $res = Loader::model('Users')->adzone($Page->firstRow,$Page->listRows,$params);
        if(!isset($pageParam['adzone'])){
            $pageParam['adzone'] = 'uid';
        }
        $this->assign('one',$pageParam);
        $this->assign('adzone_list',$res);
        $this->assign('page',$show);
        return $this->fetch('site/adzone-list');
    }

    /**
     * 会员管理跳转到客户端
     */
    public function usersToClient()
    {
        if(empty(Session::get('user_login_id'))){
            $this->redirect('/admin/Index/login');
        }else{
            $pageParam = Request::instance()->param();
            $type = Loader::model('Users')->getType($pageParam['uid']);
            if($type['type'] == 1){
                Session::set('type',1);
                Session::set('webmasterUid',$pageParam['uid']);
                $this->redirect('/home/webmaster/myCenter');
            }elseif($type['type'] == 2){
                Session::set('type',2);
                Session::set('advertiserUid',$pageParam['uid']);
                $this->redirect('/home/advertiser/homePage');
            }elseif($type['type'] == 3){
                Session::set('type',3);
                Session::set('customerUid',$pageParam['uid']);
                $this->redirect('/home/customer/index');
            }elseif($type['type'] == 4){
                Session::set('type',4);
                Session::set('businessUid',$pageParam['uid']);
                $this->redirect('/home/business/index');
            }elseif($type['type'] == 5){
                Session::set('type',5);
                Session::set('gameUid',$pageParam['uid']);
                $this->redirect('/home/game/index');
            }else{
                return $this->fetch('index@public/404');
            }
        }
    }

    /**
     * 输出错误
     */
    public function echoError()
    {
        echo '<h1>此网站已有合作人</h1>';
        exit;
    }

    /**
     * 统计我的业绩的数据
     */
    private function _getPerformance($type,$res,$todayRes)
    {
        $arr = array();
        $id = '';
        $day = '';
        $res = array_merge($res,$todayRes);
        //初始化报告的数据
        foreach ($res as $key => $value) {
            $arr[$value['day']]['money'] = 0;
            $arr[$value['day']]['num'] = 0;
        }

        //计算同一天下的数据综合
        foreach ($res as $key => $value) {
            $arr[$value['day']]['day'] = $value['day'];
            // if(empty($value['cpd'])){
            $arr[$value['day']]['money'] += $value['money'];
            // }else{
            //     $arr[$value['day']]['money'] += $value['cpd'];
            // }
            if((($type == 3) ? $value['uid'] : $value['uid'] != $id) || ($value['day'] != $day) ){
                $arr[$value['day']]['num'] += 1;
            }
            $id = ($type == 3) ? $value['uid'] : $value['uid'];
            $day = $value['day'];
        }

        return $arr;
    }

    /**
     * 拼接折线图所需要的数据
     */
    private function _getchart($type,$date)
    {

        $strday = '';
        foreach ($date as $key => $value) {
            //拼接折线图的日期(拼接成 "'2016-08-03','2016-08-04','2016-08-05'"类型)
            $day = $value['day'];
            $valueday = str_Replace("$day","'$day'",$day);
            $strday .= $valueday;
            //拼接折线图的结算数(拼接成"1,2,3")
            $num[] = ($type == 3) ? $value['money'] : $value['money'];
        }
        $str['day'] = str_Replace("''","','",$strday);
        $str['num'] = empty($num) ? '':implode(',',$num);
        return $str;
    }

    /**
     * 处理时间函数
     */
    private function _getTime($day,$parama)
    {
        //获取所有时间段
        $allday = '2000-01-01'.'2199-12-31';
        //获取昨天日期
        $yesterday = date("Y-m-d",strtotime("-1 day")).date("Y-m-d",strtotime("-1 day"));
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
            'nowval' => $parama['frontDay'].$parama['endDay'],
            'now' => $parama['frontDay']."至".$parama['endDay'],
            'yesterday' => $yesterday,
            'lastseven' => $lastSeven,
            'lastthirty' => $lastThirty,
            'lastmonth' => $lastMonth,
            'time' => $parama['frontDay'].$parama['endDay'],
        );
        return $data;
    }



    /**
     * 新增会员参数
     * param data 参数数组
     */
    private function _dataAdd($params)
    {
        //用户密码加密
        $password = $params['password'];
        $Encrypt = new Encrypt();
        $pwd = $Encrypt->fb_ucenter_encrypt($password);
        $data = array(
            'type' => $params['type'],
            'nickname' => $params['nickname'],
            'username' => $params['username'],
            'password' => $pwd,
            'tel' => $params['tel'],
            'qq' => $params['qq'],
            'status' => '1',
            'mail_status'=>'1',
        );

        if($params['type'] == '1'){
            $data['web_deduction'] = empty($params['deduction']) ? 0 : $params['deduction'];
        }elseif($params['type'] == '2'){
            $data['adv_deduction'] = empty($params['deduction']) ? 0 : $params['deduction'];
        }
        return $data;
    }

    /**
     * 站长管理编辑参数
     * param data 参数数组
     */
    private function _dataMasterEdit($params)
    {
        //组装数据
        $data = array(
            'serviceid'=>$params['serviceid'],
            'remark' => $params['remark'],
            'email' => empty($params['email']) ? '': $params['email'],
            'contact' => $params['contact'],
            'qq' => empty($params['qq']) ? '': $params['qq'],
            'tel' => $params['tel'],
            'bank_name' => $params['bank_name'],
            'bank_branch' => $params['bank_branch'],
            'account_name' => $params['account_name'],
            'bank_card' => $params['bank_card'],
            'domain_limit' =>$params['domain_limit'],
            'cpd_type' =>$params['cpd_type'],
        );
        if(!empty($params['deduction'])){
            $data['web_deduction'] = $params['deduction'];
        }
        return $data;
    }

    /**
     * 广告商管理编辑参数
     * param data 参数数组
     */
    private function _dataEditAdv($params)
    {
        //从页面获取编辑的值
        $data = array(
            'serviceid'=>$params['serviceid'],
            'nickname' => $params['nickname'],
            'remark' => $params['remark'],
            'email' => $params['email'],
            'contact' => $params['contact'],
            'qq' => $params['qq'],
            'tel' => $params['tel'],
        );
        if(!empty($params['deduction'])){
            $data['adv_deduction'] = $params['deduction'];
        }
        return $data;
    }

    /**
     * 客服管理和商务编辑参数
     * param data 参数数组
     */
    private function _dataEdit($params)
    {
        //从页面获取编辑的值
        $data = array(
        	'nickname' => $params['nickname'],
            'remark' => $params['remark'],
            'email' => $params['email'],
            'contact' => $params['contact'],
            'qq' => $params['qq'],
            'tel' => $params['tel'],
        );
        return $data;
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

    // /**
    //  * 获取站长未结算
    //  */
    // private function _getWebMoney($res)
    // {
    //     $params['today'] = date('Y-m-d');
    //     foreach($res as $key=>$value){
    //         $moneyPay = Loader::model('Users')->webMoneyPay($value['uid'],$params['today']);
    //         $money = 0;
    //         if(empty($moneyPay)){
    //             $money = 0;
    //             $res[$key]['money'] = $money;
    //         }else{
    //             foreach ($moneyPay as $get => $mon) {
    //                 if($mon['cpd_status'] != 1){
    //                     $res[$key]['money'] = $money + $mon['money'];
    //                 }else{
    //                     //包天情况下
    //                     if(empty($mon['cpd'])){
    //                         $res[$key]['money'] = $money + $mon['money'];
    //                     }else{
    //                         $res[$key]['money'] = $money + $mon['cpd'];
    //                     }
    //                 }
    //             }
    //         }
    //     }
    //     return $res;
    // }

    /**
     * 获取站长余额
     */
    private function _getWebMoney($res)
    {
        $params['today'] = date('Y-m-d');
        foreach($res as $key=>$value){
            $moneyPay = Loader::model('Paylog')->webMoneyPay($value['uid'],$params['today']);
            if(empty($moneyPay['cpd'])){
                $res[$key]['money'] = $value['money'] + $moneyPay['money'];
            }else{
                $res[$key]['money'] = $value['money'];
            }
        }
        return $res;
    }

    /**
     * 拼接站长排序的数据
     */
    private function _getdata($param,$nowsumpay,$lastsumpay)
    {
        //重置键值
        foreach($nowsumpay as $key => $value){
            $nowsumpay[$key]['nowsumpay'] = $nowsumpay[$key]['sumpay'];
            unset($nowsumpay[$key]['sumpay']);
        }
        foreach($lastsumpay as $key => $value){
            $lastsumpay[$key]['lastsumpay'] = $lastsumpay[$key]['sumpay'];
            unset($lastsumpay[$key]['sumpay']);
        }

        //如果按照当前日排序，则把上一日数据拼接到当前日；否则反之
        if($param['sort_day'] == 1){
            foreach($nowsumpay as $key => $value){
                foreach($lastsumpay as $k => $v){
                    if($value['uid'] == $v['uid']){
                        $nowsumpay[$key]['lastsumpay'] = $v['lastsumpay'];
                        unset($lastsumpay[$k]);
                    }
                }
            }
        }else{
            foreach($lastsumpay as $key => $value){
                foreach($nowsumpay as $k => $v){
                    if($value['uid'] == $v['uid']){
                        $lastsumpay[$key]['nowsumpay'] = $v['nowsumpay'];
                        unset($nowsumpay[$k]);
                    }
                }
            }
        }

        //数组合并，根据正序和倒序等条件确定两个数组的位置
        if($param['sort_type'] == $param['sort_day']){
            $data = array_merge($nowsumpay,$lastsumpay);
        }else{
            $data = array_merge($lastsumpay,$nowsumpay);
        }
        //处理数组空值
        foreach($data as $key => $value){
            if(!isset($value['nowsumpay'])){
                $data[$key]['nowsumpay'] = '0.00000';
            }
            if(!isset($value['lastsumpay'])){
                $data[$key]['lastsumpay'] = '0.00000';
            }
        }
        return $data;
    }

}