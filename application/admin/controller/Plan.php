<?php
/* 计划管理
 * @date   2016-6-2
 */
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Hook;
use app\user\api\DelApi as DelApi;

class Plan extends Admin
{
    public function _initialize()
    {
        parent::_initialize();
        header("Cache-control: private");
    }

    public function addtest()
    {
        $params = Request::instance()->post();
        dump(session('token'));
        dump($params);
        session('token',null);
        dump(session('token'));
        exit;
        $validate = Loader::validate('Plan');
        if(!$validate->check($params)){
            $this->error($validate->getError());
        }
        $data = ['plan_name' => 'foo'];
        Db::name('plan')->insert($data);
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
     * plan list    #234
     */
    public function list()
    {
        $request = Request::instance();
        //权限
        Hook::listen('auth',$this->_uid);
        $pageParam = $request->param('');
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }
        $pageParam['status'] = $_SESSION['think']['status'];
        $list = Loader::model('Plan')->getLst($pageParam);
        //判断是否为广告部账号
        $list = $this->_getRole($pageParam,$list);
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $total = count($list);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        $res = array_slice($list,$Page->firstRow,$Page->listRows);
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        $this->assign('mobile',$pageParam['mobile']);

        return $this->fetch('plan-list');
    }

    /**
     * 更新游戏推广计划总限额表次数
     */
    public function gameUpdate()
    {
        $request = Request::instance();
        $params = $request->param();
        //获取当前计划的内容
        $plan_res = Loader::model('Plan')->planCopy($params['pid']);

        if($plan_res['status'] != '1'){
            //当前次数 加 1
            $params['num'] = $params['num']+1;
            $num = Loader::model('Plan')->gameUpdateNum($params);
        }else{
            $num = '';
        }


        return $num;
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
        $plan_res = Loader::model('Plan')->planCopy($pid);
        //插入新计划，返回新插入的id
        $new_pid = Loader::model('Plan')->planCopyAdd($plan_res);

        //插入数据到提醒表中
        Loader::model('Plan')->remindingAdd($new_pid,$plan_res);

        //获取复制游戏推广计划的总限额
        $game_res = Loader::model('Plan')->gameCopyTotal($pid);
        if(!empty($game_res)){
            //把复制游戏推广计划的总限额插入到新的计划下
            Loader::model('Plan')->gameCopyAdd($game_res,$new_pid);
        }


        //获取复制计划下面的单价模板
        $price_res = Loader::model('Plan')->PriceCopy($pid);
        foreach($price_res as $key => $value){
            //把单价模板插入到新计划下面
            $price_copy = Loader::model('Plan')->PriceCopyAdd($value,$new_pid);
        }
        //防止计划单价模板为空报错
        if(empty($price_copy)){
            $price_copy = true;
        }

        //查询复制计划下面的广告
        $ads_res = Loader::model('Plan')->adsCopy($pid);
        foreach($ads_res as $key => $value){
            //把查询到的广告插入到新计划下面
            $ads_copy = Loader::model('Plan')->adsCopyAdd($value,$new_pid);
        }

        //获取新复制的计划下面的单价id
        $new_price_res = Loader::model('Plan')->priceSelect($new_pid);
        //查询新复制计划下面的广告
        $new_ads_res = Loader::model('Plan')->adsSelect($new_pid);

        foreach($new_price_res as $key => $value){
            foreach($new_ads_res as $k => $v){

                if($v['pid'] == $value['pid'] && $v['width'].'*'.$v['height'] == $value['size'] && $v['tpl_id'] == $value['tpl_id']){

                    //在修改到复制广告关联的单价字段里
                    Loader::model('Plan')->adsCopyUpdate($value['id'],$v['ad_id']);
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
     * 根据计划id获得计划内容
     */
    public function one()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pid = $request->param('pid');
        $res = Loader::model('Plan')->getOne($pid);

        //文字类的计划和普通计划区分
        foreach($res as $key => $val){
            if(strstr($val['plan_name'],'文字')){
                $res[$key]['urlType'] = '1';
            }else{
                $res[$key]['urlType'] = '0';
            }
        }

        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page','');

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

            //验证数据
            $validate = Loader::validate('Plan');
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            //返回新添加的计划id
            $pid = Loader::model('Plan')->add($data);

            //插入游戏推广计划总限额表
            if(!empty($params['totalbudget'])){
                $game_data = array(
                    'uid' => !empty($params['game_uid'])?$params['game_uid']:'',
                    'pid' => $pid,
                );

                Loader::model('Plan')->gametotalbudget($pid,$params['totalbudget']);

                //添加该计划分配的客服
                Loader::model('Plan')->gameAdd($game_data);

            }
            //插入数据到提醒表中
            Loader::model('Plan')->remindingAdd($pid,$data);

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
                $ad_name = Loader::model('Plan')->getkLst($param);
            }else{
                $ad_name ='';
            }
            //var_dump($ad_name);exit;
            //读取广告商
            $res = Loader::model('Plan')->getOnekLst();
            $array = Loader::model('Plan')->getSetting();
            //获取网站与计划类型
            $classLst = $this->_classType();
            $planclass = Loader::model('plan')->planclassList();
            //查询游戏部员工账号 5 他的type 所属身份
            $game_list = Loader::model('plan')->gameList('5');
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
            $this->assign('game_list',$game_list);

            return $this->fetch('plan-add');
        }
    }

    /**
     * 获取中高低端手机型号
     */
    private function _getModle()
    {
        $res = Loader::model('Plan')->getModle();
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
            $this->redirect('Plan/list');
        }
        $UserApi = new DelApi();
        $UserApi->del($params['id'],'plans');
        $ids = implode(',', $params['id']);
        $res = Loader::model('Plan')->delLst($ids,$params['id']);
        if($res>0){
            //写操作日志
            $this->logWrite('0014',$ids);
            $this->redirect('Plan/list');
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
            $res = Loader::model('Plan')->updateStatus($pid,$status);
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
                Loader::model('Plan')->updateAdsStatus($pid,$status);
                // $this->_updateAdzone($pid,$status);
                $this->_success();
            }else{
                $this->_error('修改失败');
            }
        }
    }

    /**
     * 新建单价
     */
    public function addprice()
    {
        Hook::listen('auth',$this->_uid); //权限
        $request = Request::instance();
        $pid = $request->param('pid');
        if($request->isPost()){
            $param = $request->param();
            $arr = explode(',',$param['price_name']);
            $param['tpl_id'] = $arr[2];
            $param['size'] = $arr[1];
            $param['price_name'] = $arr[0].','.$arr[1];
            $res = Loader::model('plan')->validatePrice($param);
            if($res){
                $this->_error('该类型尺寸单价已创建');
            }
            $data= $this->_priceAdd($param);
            $res = Loader::model('plan')->addPrice($data);
            if($res>=0){
                //写操作日志
                $this->logWrite('0017',$pid,$param['price_name']);
                $this->_success($pid);
            }else{
                $this->_error('新建失败');
            }
        }else{
            $res = $this->_priceTc();
//            $type_one = Loader::model('plan')->typePidprice($pid);
            $typename = Loader::model('plan')->typeprice();
            if(empty($typename)){
                $this->redirect('admin/plan/pricemodel');
            }
            $this->assign('typename',$typename);
            $this->assign('one',$res);
            $this->assign('pid',$pid);
            return $this->fetch('plan-price');
        }
    }

    /**
     * 批量新建单价
     */
    public function batchaddprice()
    {
        Hook::listen('auth',$this->_uid); //权限
        $request = Request::instance();
        $pid = $request->param('pid');
        if($request->isPost()){
            $param = $request->param();
            //获取所选取的单价模板
            $res = Loader::model('plan')->batchtypeprice($param);
            //获取该计划下所建立的单价
            $planallprice = Loader::model('plan')->planallprice($param);
            //判断是不是倍数模板
            if(($param['template_name'] == 'Android倍数模版') || ($param['template_name'] == 'IOS倍数模版')){
                //模板倍数不填写，默认为1倍
                $param['template_times'] = empty($param['template_times']) ? 1 : $param['template_times'];
                //利用所填写倍数匹配广告商单价
                foreach($res as $key=>$value){
                    $res[$key]['pricedv'] = $value['price_5'] * $param['template_times'];
                }
            }
            //将已经建立的单价从单价模板中去除,并且存入到另一个数组中，在后面编辑单价模板时使用
            //即将模板单价分为需要新增使用和编辑使用两个数组
            $arr = array();
            foreach($res as $key=>$value){
                foreach($planallprice as $K=>$v){
                    if($value['price_name'] == $v['price_name']){
                        $arr[$key] = $res[$key];
                        unset($res[$key]);
                    }
                }
            }

            //已存在的单价编辑为所选取的模板单价
            foreach($arr as $key=>$value){
                $one = Loader::model('plan')->editprice($value,$param);
            }
            //不存在的单价按照模板单价新建
            foreach($res as $key=>$value){
                $one = Loader::model('plan')->batchaddprice($value,$param);
                //写操作日志
                $this->logWrite('0059',$value['price_name']);
            }
            if(isset($one)){
                $this->_success($pid);
            }else{
                $this->_error('模板应用失败');
            }
        }else{
            $typename = Loader::model('plan')->typeprice();
            if(empty($typename)){
                $this->redirect('admin/plan/pricemodel');
            }
            $this->assign('typename',$typename);
            $this->assign('pid',$pid);
            return $this->fetch('plan-batch-price');
        }
    }

    /**
     * 查看计划单价
     */
    public function planprice()
    {
        Hook::listen('auth',$this->_uid); //权限
        $request = Request::instance();
        $pid = $request->param('pid');
        $res = Loader::model('plan')->getPlanPrice($pid);
        if(empty($res)){
            $pid= $pid;
        }
        $this->assign('pid',$pid);
        $this->assign('one',$res);
        return $this->fetch('price-list');
    }

    /**
     * 计划分类列表
     */
    public function planclass()
    {
        Hook::listen('auth',$this->_uid); //权限
        $res = Loader::model('plan')->planclassList();
        $this->assign('res',$res);
        return $this->fetch('plan-class-list');
    }

    /**
     * 计划分类添加
     */
    public function planclassadd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->Post();
        if(!empty($params) && !empty($params['class_name'])){
            $data = array(
                'class_name'=>$params['class_name'],
                'type'      =>$params['type'],
            );
            $int = Loader::model('plan')->planclassadd($data);
            if($int >0){
                //写操作日志
                $this->logWrite('0056',$params['class_name']);
                $this->redirect('planclass');
            }else{
                $this->error();
            }
        }else{
            $this->success('分类名称不能为空','planclass');
        }
    }

    /**
     *   计划分类编辑
     */
    public function planclassedit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $id = $request->get('classid');
        //修改信息
        $params =$request->post();
        if(!empty($params)) {
            $data = array(
                'class_name' =>$params['class_name'],
            );
            $update = Loader::model('plan')->planclassedit($data,$params);
            if ($update >=0) {
                //写操作日志
                $this->logWrite('0057',$params['classid'],$params['class_name']);
                $this->redirect('planclass');
            } else {
                $this->error();
            }
        }else{
            $plan_res = Loader::model('plan')->planclassone($id);
        }
        $this->assign('plan_res', $plan_res);
        $this->assign('classid', $id);
        return $this->fetch('plan-class-edit');
    }

    /**
     *   计划分类删除
     */
    public function planclassdel()
    {
        Hook::listen('auth',$this->_uid); //权限
        $classid = $_GET['classid'];
        if(!empty($classid)){
            $del = Loader::model('plan')->planclassdel($classid);
            if($del >0){
                //写操作日志
                $this->logWrite('0058',$classid);
                $this->_success();
            }else{
                $this->_error();
            }
        }
    }

    /**
     * 更新广告位的数据
     */
    // private function _updateAdzone($pid,$status)
    // {
    //     if($status == 1){
    //         $res = Loader::model('Plan')->getAds($pid);
    //         dump($res);exit;
    //         foreach($res as $key_plan=>$value_plan){
    //             $res = Loader::model('Plan')->getAdzone($value_plan);
    //             foreach($res as $key=>$value){
    //                 $show_adid = explode(',',$value['show_adid']);
    //                 $show_adid = empty($show_adid['0']) ? array() : $show_adid;
    //                 $ads = array('ad_id' => $value_plan['ad_id']);
    //                 $show_adid = array_merge($show_adid,$ads);
    //                 $show_adid = array_unique($show_adid);
    //                 $show_adid = implode(',',$show_adid);
    //                 Loader::model('Ads')->updateAdzone($show_adid,$value);
    //             }
    //         }
    //     }
    // }

    /**
     * 获取计划单价所需的类型和尺寸
     */
    private function _priceTc()
    {
        //获取计划单价所需的类型和尺寸
        $res = Loader::model('Plan')->getPriceTc();
        $num = 0;
        $arr = array();$data=array();
        foreach($res as $key => $value){
            $specs = unserialize($value['specs']);
            foreach($specs as $k => $v){
                $arr[$num]['name'] = $value['tplname'].$v;
                $arr[$num]['tplname'] = $value['tplname'];
                $arr[$num]['tpl_id'] = $value['tpl_id'];
                $arr[$num]['size'] = $v;
                $data[$num] = implode(',',$arr[$num++]);

            }
        }
        //去除重复的类型+尺寸
        $data = array_unique($data);
        //将去除重复后的一维数组恢复成二维数组
        $res = array();$arr = array();
        foreach($data as $key => $value){
            $res[$key] = explode(',',$value);
            $arr[$key]['name'] = $res[$key]['0'];
            $arr[$key]['tplname'] = $res[$key]['1'];
            $arr[$key]['tpl_id'] = $res[$key]['2'];
            $arr[$key]['size'] = $res[$key]['3'];
        }
        return $arr;
    }

    /**
     * 组装数据
     */
    private function _priceAdd($param)
    {
        $data =array(
            'pid' => $param['pid'],
            'gradation' => $param['gradation'],
            'price_name' => $param['price_name'],
            'tpl_id' => $param['tpl_id'],
            'size' => $param['size'],
            'price' => $param['price'],
            'price_1' => $param['price_1'],
            'price_2' => $param['price_2'],
            'price_3' => $param['price_3'],
            'price_4' => $param['price_4'],
            'price_5' => $param['price_5'],
            'pricedv' => $param['pricedv'],
            'template_name' => $param['template_name'],
            'ctime' => time(),
        );
        return $data;
    }

    /**
     * 批量删除新建单价
     */
    public function delPrice()
    {
        $request = Request::instance();
        $params = $request->param();
        if(!empty($params['id'])){
            foreach ($params['id'] as $key => $value) {
                $res = Loader::model('plan')->batDelPrice($value);
                //写操作日志
                $this->logWrite('0018',$params['pid'],$value);
            }
        }
        if($params['pid'] == 0){
            $this->redirect("plan/pricemodel",['pid'=>$params['pid']]);
        }else{
            $this->redirect("plan/planprice",['pid'=>$params['pid']]);
        }
    }

    /**
     * 批量删除单价模板
     */
    public function delTemplatePrice()
    {
        $request = Request::instance();
        $params = $request->param();

        if(!empty($params['template_name'])){
            foreach ($params['template_name'] as $key => $value) {
                $res = Loader::model('plan')->templateDelPrice($value);
                //写操作日志
                $this->logWrite('0051',$value);
            }
        }
        if($params['pid'] == 0){
            $this->redirect("plan/pricemodel",['pid'=>$params['pid']]);
        }else{
            $this->redirect("plan/planprice",['pid'=>$params['pid']]);
        }
    }

    /**
     *  Ajax提交  判断计划单价下是否有广告
     */
    public function ads()
    {
        $request = Request::instance();
        $params = $request->post();
        if(!empty($params)){
            $text_id = implode(',',$params['id']);
            $res = Loader::model('Plan')->priceAds($text_id);
            if(!empty($res)){
                $this->_error('请先删除该计划下广告！');
            }else{
                echo 0;
            }
        }
    }

    /**
     * 编辑新建单价
     */
    public function editPrice()
    {
        $request = Request::instance();
        $param =  $request->param();
        if($request->post()){
            $total = Loader::model('plan')->updateOne($param);
            //写操作日志
            $this->logWrite('0019',$param['pid']);
            $this->redirect("Plan/planprice",['pid' => $param['pid']]);
        }else{
            $res = Loader::model('plan')->getTd($param['id']);
            $this->assign('one',$res[0]);
        }
        return $this->fetch('plan-priceedit');
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
        $res = Loader::model('Plan')->delOne($pid);
        if($res>0){
            //写操作日志
            $this->logWrite('0020',$pid);
            $this->_success();
        }else{
            $this->_error();
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
            $res = Loader::model('Plan')->editPlan($data,$params['pid']);
            if($res>=0){
                $pms = array(
                    'pid' => $params['pid'],
                    'cmd_flag' => 1,
                );
                //写操作日志
                $this->logWrite('0021',$params['pid']);
                //游戏推广计划时   在更新游戏推广总限额表的限额
                if($params['type']==1){
                    $game_data = array(
                        'uid' => !empty($params['game_uid'])?$params['game_uid']:'',
                        'pid' => $params['pid'],
                    );
                    Loader::model('Plan')->editgamebudget($params);
                    //查询分配表是否有该计划的数据
                    $game_res = Loader::model('Plan')->gameone($params);
                    if(!empty($game_res)){
                        //修改该计划分配的客服
                        Loader::model('Plan')->gameEdit($game_data);
                    }else{
                        //添加该计划分配的客服
                        Loader::model('Plan')->gameAdd($game_data);
                    }
                }
                $this->redirect('Plan/edit',$pms);
            }else{
                $this->error('error');
            }
        }else{
            $pid = $request->param('pid');
            //查出所有广告商  编辑可修改广告商
            $ad_data = Loader::model('plan')->ad_user();
            $this->assign("ad_data",$ad_data);
            $res = Loader::model('Plan')->getOne($pid);
            if(!empty($res)){
                //数据处理
                $res = $this->_doEditData($res[0]);

                //获取网站与计划类型
                $classLst = $this->_classType();
                //计划分类
                $planclass = Loader::model('plan')->planclassList();
                //查询游戏部员工账号 5 他的type 所属身份
                $game_list = Loader::model('plan')->gameList('5');

                //查询分配表该计划的默认客服

                $data = array(
                    'pid' => $pid,
                );
                $gameone = Loader::model('Plan')->gameone($data);
                if(empty($gameone)){
                    $gameone[0]['uid'] = '';
                }
                //获取手机高中低类型
                $modle = $this->_getModle();
                //获取用户名
                $name = $request->session('uname');
                $this->assign('modle',$modle);
                $this->assign('planclass',$planclass);
                $this->assign('class_list',$classLst);
                $this->assign('one',$res);
                $this->assign('game_list',$game_list);
                $this->assign('gameone',$gameone);
                $this->assign('name', $name);
                return $this->fetch('plan-edit');
            }else{
                $this->redirect('Plan/add');
            }
        }
    }

    /**
     * 在计划页面跳转到会员管理页面，并且根据计划id获得计划内容
     */
    public function planToUser()
    {
        $uid = Request::instance();
        $pageParam = $uid->param();
        $uid = $pageParam['uid'];
        $res = Loader::model('Users')->getAdvOne($uid);
        if(!empty($res)) {
            //获取广告商今日消耗(今日支出)
            $params['adv_id'] = $res[0]['uid'];
            $params['day'] = date("Y-m-d");
            $resTotalNow = Loader::model('Plan')->advMoney($params);
            $res[0]['Today'] = empty($resTotalNow[0]['sumadvpay']) ? 0 : round($resTotalNow[0]['sumadvpay'],2);
            //获取广告商昨日消耗(昨日支出)
            $params['day'] = date("Y-m-d",strtotime("-1 day"));
            $resTotalYes = Loader::model('Plan')->advMoney($params);
            $res[0]['Yesterday'] = empty($resTotalYes[0]['sumadvpay']) ? 0 : round($resTotalYes[0]['sumadvpay'],2);
        }else{
            echo '该广告商不存在';exit;
        }
        $this->assign('params',$pageParam);
        $this->assign('advertiser_list',$res);
        $this->assign('page','');

        return $this->fetch('users/advertiser-list');
    }

    /**
     * 更改价格
     */
    public function changePrice()
    {
        Hook::listen('auth',$this->_uid,'plan-changePrice'); //权限
        $params = Request::instance()->post();
        $data['price'] =  $params['money'];
        $res = Loader::model('Plan')->updatePrice($params['pid'],$data);
        if($res>=0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 新建单价模板
     */
    public function price()
    {
        Hook::listen('auth',$this->_uid); //权限
        $request = Request::instance();
        if($request->isPost()){
            $param = $request->param();
            $param['pid'] = 0;
            $arr = explode(',',$param['price_name']);
            $param['tpl_id'] = $arr[2];
            $param['size'] = $arr[1];
            $param['price_name'] = $arr[0].','.$arr[1];
            if(!empty($param['template_name'])){
                $res = Loader::model('plan')->templatePrice($param);
                if($res){
                    $this->_error('该模板类型尺寸单价已创建');
                }
                $data= $this->_priceAdd($param);
                $res = Loader::model('plan')->addPrice($data);
                if($res>=0){
                    //写操作日志
                    $this->logWrite('0050',$param['template_name']);
                    $this->_success();
                }else{
                    $this->_error('新建失败');
                }
            }else{
                $this->_error('模板名称不能为空');
            }

        }else{
            $res = $this->_priceTc();
            $this->assign('one',$res);
            return $this->fetch('plan-price-model');
        }
    }

    /**
     * 游戏推广列表
     */
    public function gamePromote()
    {
        Hook::listen('auth',$this->_uid); //权限
        $request = Request::instance();

        $pageParam = $request->param('');
        $total = Loader::model('Plan')->gameplanLstCount($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }
        $res = Loader::model('Plan')->gamegetLst($Page->firstRow,$Page->listRows,$pageParam);
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }

        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        $this->assign('mobile',$pageParam['mobile']);

        return $this->fetch('plan-gamePromote-model');

    }

    /**
     * 获取默认价格
     */
    public function getPrice()
    {
        $params = Request::instance()->post();
        $params = explode(',',$params['price_id']);
        $params['template_name'] = $_GET['template_name'];
        $res = Loader::model('Plan')->getPriceModel($params);
        $data = array(
            'price' => 0,
            'price_1' => 0,
            'price_2' => 0,
            'price_3' => 0,
            'price_4' => 0,
            'price_5' => 0,
            'pricedv' => 0,
        );
        $res[0] = empty($res) ? $data : $res[0];
        if($res>=0){
            $this->_success($res[0]);
        }else{
            $this->_error();
        }
    }

    /**
     * 查看计划单价模板
     */
    public function pricemodel()
    {
        Hook::listen('auth',$this->_uid); //权限

        $pid = 0;
        $template_list = Loader::model('plan')->templateList();






        $this->assign('pid',$pid);
        $this->assign('template_list',$template_list);
//        $this->assign('template_price',$template_price);
        return $this->fetch('price-model');
    }

    /**
     * 查看模板名称下面的单价模板
     */
    public function templatePrice()
    {
        $request = Request::instance();
        $param =  $request->param();
//        var_dump($param);exit;

        $template_price = Loader::model('plan')->batchtypeprice($param);
        $this->assign('template_price',$template_price);
        return  $this->fetch('price-template');
    }


    /**
     * 编辑单价模板
     */
    public function editModlePrice()
    {
        $request = Request::instance();
        $param =  $request->param();
        if($request->post()){
            $param['price'] = empty($param['price']) ? 0 : $param['price'];
            $total = Loader::model('plan')->updateOne($param);
            //写操作日志
            $this->logWrite('0019',$param['pid']);
            $this->redirect("Plan/pricemodel",['pid' => $param['pid']]);
        }else{
            $res = Loader::model('plan')->getTd($param['id']);
            $this->assign('one',$res[0]);
        }
        return $this->fetch('plan-modleedit');
    }

    /**
     * 更改价格
     */
    public function changePricedv()
    {
        Hook::listen('auth',$this->_uid,'plan-changePrice'); //权限
        $params = Request::instance()->post();
        $data['pricedv'] =  $params['money'];
        $res = Loader::model('Plan')->updatePrice($params['pid'],$data);
        if($res>=0){
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
        $res = Loader::model('Plan')->updatePriority($params['pid'],$data);
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
        $res = Loader::model('Plan')->deduction($data);
        if($res>=0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 更改价格
     */
    public function changeBudget()
    {
        Hook::listen('auth',$this->_uid,'plan-changePrice'); //权限
        $params = Request::instance()->post();
        $data['budget'] =  $params['money'];
        $res = Loader::model('Plan')->updatePrice($params['pid'],$data);
        if($res>=0){
            //写操作日志
            $this->logWrite('0023',$params['pid'],$data['budget']);
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 广告筛选器
     */
    public function adssel()
    {
        $request = Request::instance();
        $pageParam = $request->param('');
//		$pageParam['status'] = 1;
//		$pageParam['pid'] = 68;

        if($pageParam['status'] == 1){
            //开启广告筛选器
            $this->_selOpen($pageParam);
        }else{
            //关闭广告筛选器
            $this->_selClose($pageParam);
        }
        //更新该计划的广告筛选器的状态
        $res = Loader::model('Plan')->adsSelStatus($pageParam);
        if($res > 0 && $pageParam['status'] == 1){
            $this->_success('',1);
        }elseif($res > 0 && $pageParam['status'] == 0){
            $this->_success('',0);
        }
    }

    /**
     * 开启广告筛选器
     */
    private function _selOpen($pageParam)
    {
        //只有浏览量大于100000的计划才可以开启广告筛选器
        $views = Loader::model('Plan')->getPlanViews($pageParam['pid']);
        if($views['views']<100000){
            $this->_error();
            exit;
        }
        //查出该计划下的所有广告尺寸
        $res = Loader::model('Plan')->getSize($pageParam['pid']);
        //拼接尺寸查出对应的广告
        foreach($res as $key=>$value){
            $res[$key]['size'] = $value['width'] .'*'.$value['height'];
        }

        foreach ($res as $k => $v) {
            //查出尺寸相同的广告
            $ad_id = Loader::model('Plan')->getAdid($v);
            //计算尺寸相同的广告总点击数
            $click_num = 0;
            foreach ($ad_id as $key => $value) {
                $adclick = Loader::model('Plan')->getClickNum($value);
                $click_num += $adclick['click_num'];
            }
            foreach ($ad_id as $num => $ber) {
                //计算尺寸相同的各个广告点击数
                $ad = Loader::model('Plan')->getAdNum($ber);
                if(empty($ad['click_num'])){
                    $priority = 1;
                }else{
                    //计算权重
                    $priority = floor($ad['click_num'] /$click_num * 100);
                    if($priority <= 0){
                        $priority = 1;
                    }
                }
                //更新权重
                $res = Loader::model('Plan')->updateAdsPriority($ber['ad_id'],$priority);
            }
        }
        return $res;
    }

    /**
     * 关闭广告筛选器
     */
    private function _selClose($pageParam)
    {

        //根据点击数调整该计划下所有广告的权重
        $res = Loader::model('Plan')->getClick($pageParam['pid']);
        //根据点击数确定不同广告的权重
        foreach($res as $key=>$value){
            $priority = 1;
            //更新权重
            $res = Loader::model('Plan')->updateAdsPriority($value['ad_id'],$priority);
        }
        return $res;
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
     * 锁定的计划
     */
    public function lock()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }
        $res = Loader::model('Plan')->getLstOne($pageParam);
        //判断是否为广告部账号
        $pageParam['status'] = $_SESSION['think']['status'];
        $res = $this->_getRole($pageParam,$res);
        //分页
        $total = count($res);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        $res = array_slice($res,$Page->firstRow,$Page->listRows);

        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        $this->assign('mobile',$pageParam['mobile']);
        return $this->fetch('plan-lock');
    }

    /**
     * 活动的计划
     */
    public function activity()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }
        $res = Loader::model('Plan')->getLstTwo($pageParam);
        //判断是否为广告部账号
        $pageParam['status'] = $_SESSION['think']['status'];
        $res = $this->_getRole($pageParam,$res);
        //分页
        $total = count($res);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        $this->assign('mobile',$pageParam['mobile']);
        return $this->fetch('plan-activity');
    }

    /**
     * 待审的计划
     */
    public function pending()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }
        $res = Loader::model('Plan')->getLstThree($pageParam);
        //判断是否为广告部账号
        $pageParam['status'] = $_SESSION['think']['status'];
        $res = $this->_getRole($pageParam,$res);
        //分页
        $total = count($res);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        $this->assign('mobile',$pageParam['mobile']);
        return $this->fetch('plan-pending');
    }

    /**
     * 超出限额的计划
     */
    public function quota()
    {
        $request = Request::instance();
        $pageParam = $request->param('');
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }
        $res = Loader::model('Plan')->allList($pageParam);
        //判断是否为广告部账号
        $pageParam['status'] = $_SESSION['think']['status'];
        $res = $this->_getRole($pageParam,$res);
        $this->assign('plan_list',$res);
        $this->assign('mobile',$pageParam['mobile']);
        return $this->fetch('plan-quota');
    }

    /**
     * 游戏推广  锁定的计划
     */
    public function gameLock()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');

        $total = Loader::model('Plan')->gameplanLstCount1($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }

        $res = Loader::model('Plan')->gamegetLstOne($Page->firstRow,$Page->listRows,$pageParam);
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
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');

        $total = Loader::model('Plan')->gameplanLstCount2($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action());
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }

        $res = Loader::model('Plan')->gamegetLstTwo($Page->firstRow,$Page->listRows,$pageParam);
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
        Hook::listen('auth',$this->_uid); //权限
        $pageParam = $request->param('');

        $total = Loader::model('Plan')->gameplanLstCount3($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action());
        if(empty($pageParam['mobile'])){
            $pageParam['mobile'] = '';
        }

        $res = Loader::model('Plan')->gamegetLstThree($Page->firstRow,$Page->listRows,$pageParam);
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

        $res = Loader::model('Plan')->gameallList($pageParam);
        $this->assign('plan_list',$res);
        $this->assign('mobile',$pageParam['mobile']);
        return $this->fetch('game-plan-quota');
    }

    /**
     * 判断是否为广告部账号
     */
    private function _getRole($pageParam,$res)
    {
        //判断是否为广告部账号
        if($pageParam['status']==7){
            foreach($res as $key => $value){
                if($value['uid'] != 1021 && $value['uid'] != 6358 && $value['uid'] != 6379){
                    unset($res[$key]);
                }
            }
        }

        return $res;
    }

    /**
     *  计划补消耗功能
     */
    public function changeSumadvpay()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->post();
        // 凌晨的00:00-01：00是整理前一天数据的时间不可以操作补消耗功能
        $js_hour = date('H', time());
        if ((int)$js_hour <= 1 && (int)$js_hour >= 0){
            $this->_error('0点至1点为数据整理时间，不能操作补消耗功能！');
        }
        if (empty($params['money']) || !is_numeric($params['money'])) {
            $this->_error('请填写正确的金额！');
            exit;
        }
        //只可以补昨天的数据
        $params['day'] = date("Y-m-d",strtotime("-1 day"));
        //获取广告商id
        $res = Loader::model('Plan')->getAdvid($params);
        $params['adv_id'] = empty($res) ? 0 : $res[0]['uid'];
        //数据报表添加数据
        $resStats = Loader::model('Plan')->insertToStats($params);
        //减去广告商消耗
        $resUser = Loader::model('Plan')->updateUserMoney($params);
        if ($resStats >= 0 && $resUser >= 0) {
            //写操作日志
            $this->logWrite('0068', $params['pid'], $params['money'], $params['day']);
            $this->_success('', '操作成功');
        } else {
            $this->_error('操作失败');
        }

    }


}
