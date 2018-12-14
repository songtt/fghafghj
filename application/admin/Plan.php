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
        $total = Loader::model('Plan')->planLstCount($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        $res = Loader::model('Plan')->getLst($Page->firstRow,$Page->listRows,$pageParam);
        $judge_name = $request->session('uname');
        $this->assign('judge_name',$judge_name);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);

        return $this->fetch('plan-list');
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

            $res = Loader::model('Plan')->add($data);

            if($res>0){
                //写操作日志
                $this->logWrite('0013',$params['pname']);
                //保存成功
                $this->redirect('list',['cmd_flag' => 'add']);
            }else{
                $this->_error();
            }
        }else{
            //读取广告商
            $res = Loader::model('Plan')->getOnekLst();
            $array = Loader::model('Plan')->getSetting();
            //获取网站与计划类型
            $classLst = $this->_classType();
            $this->assign('class_list',$classLst);
            $this->assign('ad_list',$res);
            $this->assign('one',$array);
            return $this->fetch('plan-add');
        }
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
                $this->_updateAdzone($pid,$status);
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
                $this->_error('该单价已创建');
            }
            $data= $this->_priceAdd($param);
            $res = Loader::model('plan')->addPrice($data);
            if($res>=0){
                //写操作日志
                $this->logWrite('0017',$pid,$param['price_name']);
                $this->_success();
            }else{
                $this->_error('新建失败');
            }
        }else{
            $res = $this->_priceTc();
            $this->assign('one',$res);
            $this->assign('pid',$pid);
            return $this->fetch('plan-price');
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
        $res = Loader::model('plan')->getPrice($pid);
        if(empty($res)){
            $pid= $pid;
        }
        $this->assign('pid',$pid);
        $this->assign('one',$res);
        return $this->fetch('price-list');
    }

    /**
     * 更新广告位的数据
     */
    private function _updateAdzone($pid,$status)
    {
        if($status == 1){
            $res = Loader::model('Plan')->getAds($pid);
            foreach($res as $key_plan=>$value_plan){
                $res = Loader::model('Plan')->getAdzone($value_plan);
                foreach($res as $key=>$value){
                    $show_adid = explode(',',$value['show_adid']);
                    $show_adid = empty($show_adid['0']) ? array() : $show_adid;
                    $ads = array('ad_id' => $value_plan['ad_id']);
                    $show_adid = array_merge($show_adid,$ads);
                    $show_adid = array_unique($show_adid);
                    $show_adid = implode(',',$show_adid);
                    Loader::model('Ads')->updateAdzone($show_adid,$value);
                }
            }
        }
    }

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
        $this->redirect("Plan/planprice",['pid' => $params['pid']]);

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
                $this->redirect('Plan/edit',$pms);
            }else{
                $this->error('error');
            }
        }else{
            $pid = $request->param('pid');

            $res = Loader::model('Plan')->getOne($pid);
            if(!empty($res)){
                //数据处理
                $res = $this->_doEditData($res[0]);

                //获取网站与计划类型
                $classLst = $this->_classType();

                $this->assign('class_list',$classLst);
                $this->assign('one',$res);
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
            for($i=0;$i<count($res);$i++){
                $params['adv_id'] = $res[$i]['uid'];
                $params['day'] = date("Y-m-d");
                $resTotal[] = Loader::model('Users')->advReportNow($params);
                $res[$i]['Today'] = round($resTotal[$i]['SUM(a.sumadvpay)']);
            }
            //获取广告商昨日消耗(昨日支出)
            for($i=0;$i<count($res);$i++){
                $params['adv_id'] = $res[$i]['uid'];
                $params['day'] = date("Y-m-d",strtotime("-1 day"));
                $resTotalZuo[] = Loader::model('Users')->advReportNow($params);
                $res[$i]['Yesterday'] = round($resTotalZuo[$i]['SUM(a.sumadvpay)']);
            }
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
        $res['model_data'] = $this->_dotype(empty($res['checkplan']['run_model']['data']) ? '' : $res['checkplan']['run_model']['data']);
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
        $data = array(
            'uid' => $params['uid'],  //会员id
            'plan_name' => $params['pname'],
            'plan_type' => isset($params['ptype']) ? $params['ptype']:'',
            // 'run_terminal' => $params['run_terminal'], //投放终端
            'clearing' => empty($params['clearing']) ? '' : $params['clearing'],//结算周期
            // 'class_id' => $params['class_id'] == '选择分类' ? '':$params['class_id'],//分类id
            'mobile_price' => empty($params['mobile_price']) ? '' : $params['mobile_price'],
            'budget' => $params['budget'],//每日限额
            'price_info' => empty($params['price_info']) ? '' : $params['price_info'],
            'restrictions' => empty($params['restrictions']) ? '' : $params['restrictions'],//站长限制
            'resuid' => $params['resuid'],//站长限制ID
            'sitelimit' => empty($params['sitelimit']) ? '' : $params['sitelimit'],//网站限制
            'limitsiteid' => $params['limitsiteid'],//限制网站ID
            'adzlimit' => empty($params['adzlimit']) ? '' : $params['adzlimit'],//广告位限制limitadzid
            'limitadzid' => empty($params['limitadzid'])?'':$params['limitadzid'],//限制网站ID
            'priority'  =>$params['priority'],//计划权重
//            'ads_sel_status' => $params['ads_sel_status'],
//            'ads_sel_views' => $params['ads_sel_views'],
        );
        if(!empty($params['web_deduction'])){
            $data['web_deduction'] = $params['web_deduction'];
            $data['deduction'] = $params['deduction'];
        }
        $checkplan = array(
            'mobile' => array(
                'isacl' => $params['mobile_isacl'],
                'data' => isset($params['mobile_data']) ? $params['mobile_data']:'',
            ),
            'run_model' => array(
                'isacl' => $params['run_type'],
                'data' => isset($params['run_model']) ? $params['run_model']:'',
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
        $total = Loader::model('Plan')->planLstCount1($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        $res = Loader::model('Plan')->getLstOne($Page->firstRow,$Page->listRows,$pageParam);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
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
        $total = Loader::model('Plan')->planLstCount2($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action());
        $res = Loader::model('Plan')->getLstTwo($Page->firstRow,$Page->listRows,$pageParam);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
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
        $total = Loader::model('Plan')->planLstCount3($pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action());
        $res = Loader::model('Plan')->getLstThree($Page->firstRow,$Page->listRows,$pageParam);
        if(!isset($pageParam['plan'])){
            $pageParam['plan'] = '';
        }
        $this->assign('plan',$pageParam);
        $this->assign('plan_list',$res);
        $this->assign('page',$show);
        return $this->fetch('plan-pending');
    }

    /**
     * 超出限额的计划
     */
    public function quota()
    {  $res = Loader::model('Plan')->allList();
        $this->assign('plan_list',$res);
        return $this->fetch('plan-quota');
    }
}
