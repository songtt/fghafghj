<?php
/* 财务报表
 * date   2016-10-08
 */
namespace app\admin\controller;
use think\Loader;
use think\Request;
use think\Hook;
use think\config;
use think\Cache;

class Paylog extends Admin
{
    /**
     *  财务结算(全部信息 )
     */
    public function clearing()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //搜索站长名称查询传值
        $params = $request->param('');
        $params['webmaster'] = !isset($params['webmaster']) ? '' : $params['webmaster'];
        //判断今天是星期几,得到日期数据
        $week = date('w');
        if($week == 0){
            $week = '7';
        }
        $params['mon'] = date('Y-m-d',strtotime( '-'. 6-$week .' days' ));
        $params['sun'] = date('Y-m-d',strtotime( '-'. $week .' days' ));
        $params['today'] = date('Y-m-d');

        //实例化缓存
        $cache_model = new Cache();
        $cache_keys = 'last_money'; //缓存key
        $catch_time = 43200; //设置缓存时间
        $lastMoney = $cache_model->get($cache_keys);
        if(!$lastMoney){
            //查询站长上周的钱    获取数据
            $lastMoney = Loader::model('Paylog')->getLastMoney($params);
            $cache_model->set($cache_keys,$lastMoney,$catch_time);//设置缓存
        }
        //得到站长上一周的钱,并插入paylog
        $data = $this->_getMoney($lastMoney,$params);
        unset($data);unset($lastMoney);
        //查询站长上周的钱    获取数据
        $res = Loader::model('Paylog')->getWebList($params);
        $total = count($res);
        $Page = new \org\PageUtil($total,$params);
        $show = $Page->show(Request::instance()->action(),$params);
        foreach ($res as $key => $value) {
            //处理结算的时间
            $res[$key]['day'] = date("Y-m-d",strtotime('-6 days',strtotime($value['day'])));
            //在以前的基础上修改  防止改动过大引起未知错误
            $res[$key]['sum'] = $value['xmoney'];//应付金额
            $res[$key]['zmoney'] = $value['money'];//余额
            $res[$key]['paid_money'] = $value['xmoney'];//实付金额   默认等于应付金额
        }
        $res = array_slice($res,$Page->firstRow,$Page->listRows);
        $this->assign('params',$params);
        $this->assign('paylog',$res);
        $this->assign('page',$show);
        unset($res);
        return $this->fetch('paylog-set');
    }

    /**
     * 手动结算
     */
    public function handSet()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $arr = $request->param();

        //判断今天是星期几,得到日期数据
        $week = date('w');
        if($week == 0){
            $week = '7';
        }
        $params['sun'] = date('Y-m-d',strtotime( '-'. $week .' days' ));
        $params['today'] = date('Y-m-d');
        //token验证
        if(!isset($_SESSION['__token__'])){
            $_SESSION['__token__'] = '';
        }
        if($_SESSION['__token__'] != $arr['__token__']){
            $_SESSION['__token__'] = $arr['__token__'];
            //组装数据  插入等待支付
            if(empty($arr['paid_money'])){
                $arr['paid_money'] = $arr['1'];
            }
            $data = $this->_addLog($arr);
            $res = Loader::model('Paylog')->paylog($data);//将等待支付插入表中
            $status = Loader::model('Paylog')->payStatus($arr);

            //写操作日志
            $this->logWrite('0044',$data['uid']);
            $this->redirect('paylog/clearing');
        }else{
            $this->redirect('paylog/clearing');
        }
    }

    /**
     * 批量结算
     */
    public function batchSet()
    {
        $request = Request::instance();
        $params = $request->post();
        //判断次次结算的站长中是否有已经结算的站长
        if(!empty($params)){
            //结算
            foreach ($params['id'] as $key => $value) {
                $arr = explode(',',$value);
                $arr['day'] = $arr['4'];
                $arr['paid_money'] = $arr['5'];
                $data = $this->_addLog($arr);
                $res = Loader::model('Paylog')->paylog($data);//将等待支付更新至表中
                $status = Loader::model('Paylog')->payStatus($arr,$params);
                //写操作日志
                $this->logWrite('0044',$data['uid']);
            }
            $this->_success(array(),$info='结算成功！');
        }else{
            $this->_error('未选择站长');
        }
    }

    /**
     *  已支付
     */
    public function paid()
    {
        header("Cache-control: private");
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //搜索站长名称查询传值
        $params = $request->param();
        if($request->get()){
            $params = $request->get();
            $params['today'] = date('Y-m-d');
            $params['search'] = $params['uid'];
            $params['paylog'] = 'uid';
        }
        //分页功能下查询数据
        $total = Loader::model('Paylog')->paidCount($params);
        $Page = new \org\PageUtil($total,$params);
        $show = $Page->show(Request::instance()->action(),$params);
        $res = Loader::model('Paylog')->paided($params,$Page->firstRow,$Page->listRows);
        if(@$params['type']){
            $excel = Config::get('excel_url');
            require_once  "".$excel."autoload.php";
            //修改内存
            ini_set('memory_limit','500M');
            $request = Request::instance();
            //导出站长付款明细表Excel
            //搜索站长名称查询传值
            $params = $request->param('');
            $params['webmaster'] = !isset($params['webmaster']) ? '' : $params['webmaster'];
            //判断今天是星期几,得到日期数据
            $week = date('w');
            if($week == 0){
                $week = '7';
            }
            $params['mon'] = date('Y-m-d',strtotime( '-'. 6-$week .' days' ));
            $params['sun'] = date('Y-m-d',strtotime( '-'. $week .' days' ));
            $params['today'] = date('Y-m-d');
            //实例化缓存
            $cache_model = new Cache();
            $cache_keys = 'last_money'; //缓存key
            $catch_time = 43200; //设置缓存时间
            $lastMoney = $cache_model->get($cache_keys);
            if(!$lastMoney){
                //查询站长上周的钱    获取数据
                $lastMoney = Loader::model('Paylog')->getLastMoney($params);
                $cache_model->set($cache_keys,$lastMoney,$catch_time);//设置缓存
            }
            //得到站长上一周的钱,并插入paylog
            $data = $this->_getMoney($lastMoney,$params);
            unset($data);unset($lastMoney);
            //查询站长上周的钱    获取数据
            $zhufu = Loader::model('Paylog')->zhanzhangList();
            foreach ($zhufu as $key => $value) {
                //处理结算的时间
                $zhufu[$key]['day'] = date("Y-m-d",strtotime('-6 days',strtotime($value['day'])));
                //在以前的基础上修改  防止改动过大引起未知错误
                $zhufu[$key]['sum'] = $value['xmoney'];//应付金额
                $zhufu[$key]['zmoney'] = $value['money'];//余额
                $zhufu[$key]['paid_money'] = $value['xmoney'];//实付金额   默认等于应付金额
            }
            $arr1 = array();
            $i = 0;
            foreach ($zhufu as $k => $v) {
                //多的数组
                foreach ($res as $ke => $va) {
                    //少的数组为主
                    if ($va['uid'] == $v['uid']) {
                        $i++;
                        $arr1[$i]['uid'] = $va['uid'];
                        $arr1[$i]['username']  = $va['username'];
                        $arr1[$i]['actualMoney']  = $va['actualMoney'];
                        $arr1[$i]['uname']  = $v['uname'];
                        $arr1[$i]['day'] =$v['day'];//开始时间
                        $arr1[$i]['sun'] = $params['sun'];
                        $arr1[$i]['payinfo'] = $va['payinfo'];
                    }
                }
            }
                    foreach ($arr1 as $k=>$v){
                        $regex="'\d{4}-\d{1,2}-\d{1,2}'is";
                        if($v['payinfo']){
                            preg_match_all($regex,$v['payinfo'],$matches);
                            if(!empty($matches)){
                                $arr1[$k]['day'] = @$matches[0][0];
                                $arr1[$k]['sun'] = @$matches[0][1];
                            }
                        }
                    }
            if($params['type'] == 1){
                //导出站长付款明细
                $objPHPExcel = new \PHPExcel();
                //4.激活当前的sheet表
                $objPHPExcel->setActiveSheetIndex(0);
                //5.设置表格头（即excel表格的第一行）
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', '站长id')
                    ->setCellValue('B1', '站长名称')
                    ->setCellValue('C1', '开始日期')
                    ->setCellValue('D1', '结算日期')
                    ->setCellValue('E1', '金额')
                    ->setCellValue('F1', '客服');
                //设置F列水平居中
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()
                    ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                //设置单元格宽度
                $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('J')->setWidth(15);
                $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('K')->setWidth(30);
                //6.循环刚取出来的数组，将数据逐一添加到excel表格。
                for($i=1;$i<=count($arr1);$i++){
                    $objPHPExcel->getActiveSheet()->setCellValue('A'.($i+2),$arr1[$i]['uid']);//站长id
                    $objPHPExcel->getActiveSheet()->setCellValue('B'.($i+2),$arr1[$i]['username']);//站长名称
                    $objPHPExcel->getActiveSheet()->setCellValue('C'.($i+2),$arr1[$i]['day']);//开始日期
                    $objPHPExcel->getActiveSheet()->setCellValue('D'.($i+2),$arr1[$i]['sun']);//结算日期actualMoney
                    $objPHPExcel->getActiveSheet()->setCellValue('E'.($i+2),$arr1[$i]['actualMoney']);//
                    $objPHPExcel->getActiveSheet()->setCellValue('F'.($i+2),$arr1[$i]['uname']);
                }

                //7.设置保存的Excel表格名称
                $filename = '站长付款明细.xls';
                //8.设置当前激活的sheet表格名称；
                $objPHPExcel->getActiveSheet()->setTitle('订单统计');
                //9.设置浏览器窗口下载表格
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");
                header('Content-Disposition:inline;filename="'.$filename.'"');
                //生成excel文件
                $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
                //下载文件在浏览器窗口
                $objWriter->save('php://output');
                exit;
            }else if($params['type'] == 2 ){

                $arr1 = array();
                $i = 0;
                    foreach ($zhufu as $k => $v) {
                        //多的数组
                        foreach ($res as $ke => $va) {
                            //少的数组为主
                            if ($va['uid'] == $v['uid']) {
                                $i++;
                                $arr1[$i]['uid'] = $va['uid'];
                                $arr1[$i]['username']  = $va['username'];
                                $arr1[$i]['actualMoney']  = $va['actualMoney'];
                                $arr1[$i]['uname']  = $v['uname'];
                                $arr1[$i]['day'] =$v['day'];//开始时间
                                $arr1[$i]['sun'] = $params['sun'];
                                $arr1[$i]['payinfo'] = $va['payinfo'];

                            }
                        }
                    }
                    foreach ($arr1 as $key=>$value){
                        $data = count($this->getDateFromRange($value['day'],$value['sun']));
                        $regex="'\d{4}-\d{1,2}-\d{1,2}'is";
                        preg_match_all($regex,$value['payinfo'],$matches);
                        if($data > 7 && !empty($matches[0][1])){
                            $info[] = $value;
                        }else if($data < 7){
                            $info[] = $value;
                        }
                    }
                    $c=array();
                    foreach ($info as $v=>$k)
                    {
                        $regex="'\d{4}-\d{1,2}-\d{1,2}'is";
                        preg_match_all($regex,$k['payinfo'],$matches);
                        if($k['payinfo']){
                            $k['day'] = $matches[0][0];
                            $k['sun'] = $matches[0][1];
                            $c[] = $k;
                        }else{
                            $c[] = $k;
                        }
                    }


                $objPHPExcel = new \ PHPExcel();
                $objPHPExcel->setActiveSheetIndex(0);
                //8.设置当前激活的sheet表格名称；
                $objPHPExcel->getActiveSheet()->setTitle('订单统计');
                // 重命名工作表
                $objPHPExcel->getActiveSheet()->setTitle('sheet1');
                //5.设置表格头（即excel表格的第一行）
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', '时间')
                    ->setCellValue('B1', '金额')
                    ->setCellValue('C1', 'uid')
                    ->setCellValue('D1', '站长名称');

                //设置F列水平居中
                $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()
                    ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                //设置单元格宽度
                $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('J')->setWidth(15);
                $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('K')->setWidth(30);

                $x = 0;
                foreach ($c as $ks =>$vs){

                        $namedd = $this->getDateFromRange($vs['day'],$vs['sun']);
                        $me = $vs['actualMoney']/count($namedd);
                             //6.循环刚取出来的数组，将数据逐一添加到excel表格。
                             for($i=0;$i<count($namedd);$i++){
                                 $objPHPExcel->getActiveSheet()->setCellValue('A'.($i+2+$x),$namedd[$i]);//时间
                                 $objPHPExcel->getActiveSheet()->setCellValue('B'.($i+2+$x),$me);//金额
                                 $objPHPExcel->getActiveSheet()->setCellValue('C'.($i+2+$x),$vs['uid']);//uid
                                 $objPHPExcel->getActiveSheet()->setCellValue('D'.($i+2+$x),$vs['username']);//站长名称
                             }
                    $x += count($namedd);
                 }
                //7.设置保存的Excel表格名称
                $filename = '站长每日分摊表.xls';

                //9.设置浏览器窗口下载表格
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
        }
        if(!isset($params['paylog'])){
            $params['paylog'] = '';
        }
        $this->assign('params',$params);
        $this->assign('paylog',$res);
        $this->assign('page',$show);
        return $this->fetch('paylog-paid');
    }

    /**
     *  等待支付
     */
    public function paypal()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //搜索站长名称查询传值
        $params = $request->post();
        $week = date('w');
        if($week == 0){
            $week = '7';
        }
        $params['sun'] = date('Y-m-d',strtotime( '-'. $week .' days' ));
        $params['today'] = date('Y-m-d');
        //分页功能下查询数据
        $total = Loader::model('Paylog')->waitCount($params);
        $pageParam = $request->param('');
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show(Request::instance()->action(),$params);
        $res = Loader::model('Paylog')->waitPay($params,$Page->firstRow,$Page->listRows);
        // $res = $this->_getWebMoney($res,$params['today']);
        if(!isset($params['payinfo'])){
            $params['payinfo'] = '';
        }
        $this->assign('page',$show);
        $this->assign('params',$params);
        $this->assign('paylog',$res);
        return $this->fetch('paylog-payPal');
    }

    /**
     *结算等待支付
     */
    public function handPay()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->param();
        if(!empty($params)){
            foreach ($params['id'] as $key => $value) {
                $arr = explode(',',$value);
                $res = Loader::model('Paylog')->updateStatus($arr['0']);
                //余额不足  置余额为0
                if($arr[3]>$arr[4]){
                    $arr[3] = 0.00;
                }else{
                    $arr[3] = $arr[4]-$arr[3];
                }
                //更新站长的钱
                $res = Loader::model('Paylog')->updateWebMoney($arr['1'],$arr[3]);
            }
        }
        //写操作日志
        $this->logWrite('0045');
        $this->redirect('paypal');
    }

    /**
     * 删除等待支付并更新站长余额
     */
    public function delepay()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $week = date('w');
        if($week == 0){
            $week = '7';
        }
        $params['sun'] = date('Y-m-d',strtotime( '-'. $week .' days' ));
        $uid = $request->get();
        //更新paylog
        $data = Loader::model('paylog')->updatePay($uid);
        $this->logWrite('0046');
        $this->redirect('paypal');
    }

    /**
     *  批量删除财务结算记录
     */
    public function doDele()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        if(!isset($params['id'])){
            $this->redirect('paylog/paid');
        }
        $id = implode(',', $params['id']);
        $res = Loader::model('Paylog')->del($id);
        if($res>0){
            //写操作日志
            $this->logWrite('0047',$id);
            $this->redirect('paylog/paid');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     *  财务结算跳站长管理
     */
    public function paytouser()
    {
        $request = Request::instance();
        $pageParam = $request->param('');
        $pageParam = array('search' => $pageParam['uid'],
            'type' => 'uid');
        $params['day'] = date("Y-m-d");
        $total = Loader::model('Users')->getListCount1('1',$pageParam);
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$pageParam);
        $res = Loader::model('Users')->getWebList($Page->firstRow,$Page->listRows,'1',$pageParam);
        $res = $this->_getWebMoney($res,$params['day']);
        if(!empty($res)) {
            $params['day'] = date("Y-m-d");
            $params['yesterday'] = date("Y-m-d",strtotime("-1 day"));
            foreach ($res as $key => $value) {
                $res[$key]['today'] = 0;
                $res[$key]['Yesterday'] = 0;
                $res[$key]['tocpd'] = 0;
                //循环查今日收入
                $today = Loader::model('Users')->webreportNow($params,$value['uid']);
                //循环查昨日收入
                $yesterday = Loader::model('Users')->webReportYes($params,$value['uid']);
                //获取站长今日余额(今日收入)
                foreach ($today as $now => $daymoney) {
                    $res[$key]['today'] += $daymoney['sumpay'];
                    if(empty($daymoney['cpd'])){
                        $daymoney['cpd'] = 0;
                    }
                    $res[$key]['tocpd'] += $daymoney['cpd'];
                }
                //获取站长昨日余额(昨日收入)
                foreach ($yesterday as $yes => $yesMoney) {
                    $res[$key]['Yesterday'] += $yesMoney['sumpay'];
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
        return $this->fetch('users/webmaster-list');

    }

    /**
     *  充值管理
     */
    public function rechaegelog()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //搜索会员名称查询传值
        $params = $request->param('');
        if(empty($params)){
            $params['recharges'] = '';
        }
        if(!isset($params)){
            $params['recharges'] = '';
        }
        $total = Loader::model('Paylog')->adsCount($params);
        $pageParam = $request->param('');
        $Page = new \org\PageUtil($total,$pageParam);
        $show = $Page->show($request->action(),$params);
        $res = Loader::model('Paylog')->adsRecharge($params,$Page->firstRow,$Page->listRows);
        $this->assign('params',$params);
        $this->assign('paylog',$res);
        $this->assign('page',$show);
        return $this->fetch('paylog-prepaid');
    }

    /**
     *  手动充值
     */
    public function addLog()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        if($request->isPost()){
            //查询广告商原始金额
            $adv_res = Loader::model('Paylog')->advUserOne($params['username']);
            $webPay = Loader::model('Paylog')->advertiserPay($params,$adv_res);
            if($webPay>0){
                //组建字段数组
                $data =array(
                    'uid' => $adv_res['uid'],
                    'money' => $params['money'],
                    'type' => $adv_res['type'],
                    'clearingadmin' => $_SESSION['think']['uname'],
                    'payinfo' => $params['payinfo'],
                    'day' =>  date('Y-m-d',time()),
                    'ctime' => time(),
                );
                // clearingadmin 充值人  $adv_res 充值人信息    $params  充值金额  payinfo备注
                $paylog = Loader::model('Paylog')->paylog($data);
                //写操作日志
                $this->logWrite('0048',$params['username'],$data['money']);
                $this->redirect('rechaegelog');
                // $this->success('充值成功','/admin/Paylog/rechaegelog');
            }
        }
        return $this->fetch('advertiser-pay');
    }

    /**
     *  广告商充值查询（查询是否有此广告商）
     */
    public function advUserOne()
    {
        $request = Request::instance();
        $params = $request->post();
        //查询是否有此广告商
        $advUser_find = Loader::model('Users')->advUserOne($params['username']);
        //查询充值最低限额 least_money
        $least = Loader::model('Users')-> least();
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
     *  删除充值记录
     */
    public function deleLog()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->post()){
            $params = $request->post();
            $id = implode(',', $params['id']);
            if(!isset($params['id'])){
                $this->redirect('paylog/rechaegelog');
            }
            $id = implode(',', $params['id']);
            $res = Loader::model('Paylog')->del($id);
            if($res>0){
                $this->logWrite('0049');
                $this->redirect('paylog/rechaegelog');
            }else{
                $this->error('删除失败');
            }
        }
        $this->redirect('paylog/rechaegelog');
    }

    /**
     * 组装数据
     */
    private function _addLog($arr)
    {
        //组建字段数组
        $day = date("Y-m-d",strtotime('+6 days',strtotime($arr['4'])));
        $data =array(
            'uid' => $arr['0'],
            'money' => $arr['paid_money'],
            'clearingtype' => $_SESSION['think']['uname'],
            'clearingadmin' =>  $arr['4'],   //paylog中结算时记录结算的周期时间
            'type' =>'1',
            'status' =>'0',
            'payinfo' => empty($arr['payinfo']) ? '': $arr['payinfo'],  //备注
            'day' => $day,
            'ctime' => time(),
        );
        //money实付费用  clearingtype操作人
        return $data;
    }


    /**
     * 导出excel
     */
    public function excel()
    {
        $excel = Config::get('excel_url');
        require_once  "".$excel."autoload.php";

        //修改内存
        ini_set('memory_limit','500M');
        //修改时间
        ini_set("max_execution_time", "0");

        $request = Request::instance();
        $params = $request->param('');

        //判断星期几   得到时间数据
        $week = date('w');
        if($week == 0){
            $week = '7';
        }
        $params['mon'] = date('Y-m-d',strtotime( '-'. 6-$week .' days' ));
        $params['sun'] = date('Y-m-d',strtotime( '-'. $week .' days' ));
        $params['today'] = date('Y-m-d');
        //处理已支付和未支付数据
        // type=1未支付  type=2已支付
        if($params['type'] == 1){
            //得到未支付应付费用
            $res = $this->_getpal($params);
            $filename='财务结算未支付表'.date('Y-m-d'); //设置表的名字+日期
        }else{
            $res = $this->_getpaid($params);
            if(empty($res)){
                $this->success('没有可导出数据');
            }
            $filename='财务结算已支付表'.date('Y-m-d'); //设置表的名字+日期
        }
        //统计数据个数
        $num_count = count($res)+1;
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

        //设置Excel的单元格的宽
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(35);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(30);

        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '站长名称')
            ->setCellValue('B1', '所属客服')
            ->setCellValue('C1', '结算期间')
            ->setCellValue('D1', '应付金额')
            ->setCellValue('E1', '实付金额')
            ->setCellValue('F1', '银行信息')
            ->setCellValue('G1', '收款姓名')
            ->setCellValue('H1', '收款帐号')
            ->setCellValue('I1', '底部')
            ->setCellValue('J1', '插屏')
            ->setCellValue('K1', '右漂')
            ->setCellValue('L1', '顶部')
            ->setCellValue('M1', '固定')
            ->setCellValue('N1', '文字')
            ->setCellValue('O1', '备注');

        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:M1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {
            if($params['type'] == 2){
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$i, $value['username'])
                    ->setCellValue('B'.$i, $value['uname'])
                    ->setCellValue('C'.$i, $value['payday'])
                    ->setCellValue('D'.$i, process_decimal($value['sumpay']))
                    ->setCellValue('E'.$i, process_decimal($value['money']))
                    ->setCellValue('F'.$i, $value['bank'])
                    ->setCellValue('G'.$i, $value['account_name'])
                    ->setCellValue('H'.$i, ' '.$value['bank_card'])
                    ->setCellValue('I'.$i, process_decimal($value['bottom']))
                    ->setCellValue('J'.$i, process_decimal($value['tablepla']))
                    ->setCellValue('K'.$i, process_decimal($value['left']))
                    ->setCellValue('L'.$i, process_decimal($value['top']))
                    ->setCellValue('M'.$i, process_decimal($value['fixed']))
                    ->setCellValue('N'.$i, process_decimal($value['txt']))
                    ->setCellValue('O'.$i, $value['payinfo']);
            }else{
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$i, $value['username'])
                    ->setCellValue('B'.$i, $value['uname'])
                    ->setCellValue('C'.$i, $value['payday'])
                    ->setCellValue('D'.$i, process_decimal($value['money']))
                    ->setCellValue('E'.$i, process_decimal($value['sumpay']))
                    ->setCellValue('F'.$i, $value['bank'])
                    ->setCellValue('G'.$i, $value['account_name'])
                    ->setCellValue('H'.$i, ' '.$value['bank_card'])
                    ->setCellValue('I'.$i, process_decimal($value['bottom']))
                    ->setCellValue('J'.$i, process_decimal($value['tablepla']))
                    ->setCellValue('K'.$i, process_decimal($value['left']))
                    ->setCellValue('L'.$i, process_decimal($value['top']))
                    ->setCellValue('M'.$i, process_decimal($value['fixed']))
                    ->setCellValue('N'.$i, process_decimal($value['txt']))
                    ->setCellValue('O'.$i, $value['payinfo']);
            }
            $i++;
            //设置居中
            // $a = $objPHPExcel->getDefaultStyle('A1:G'.($key+4))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::CENTER);
            // dump($a);exit;
            // $objPHPExcel->getActiveSheet()->getStyle('A8')->getAlignment()->setWrapText(true);
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

    /**
     *  导出充值记录
     */
    public function advexcel()
    {
        $excel = Config::get('excel_url');
        require_once  "".$excel."autoload.php";

        //修改内存
        ini_set('memory_limit','500M');
        //修改时间
        ini_set("max_execution_time", "0");

        $request = Request::instance();
        $params = $request->param('');
        $res = Loader::model('Paylog')->advList($params);

        $filename='充值记录'.date('Y-m-d'); //设置表的名字+日期

        //统计数据个数
        $num_count = count($res)+1;
        $objPHPExcel = new \ PHPExcel();

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
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(45);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '广告商ID')
            ->setCellValue('B1', '会员名称')
            ->setCellValue('C1', '日期')
            ->setCellValue('D1', '充值金额')
            ->setCellValue('E1', '充值人')
            ->setCellValue('F1', '备注')
            ->setCellValue('G1', '状态');
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:G1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['uid'])
                ->setCellValue('B'.$i, $value['username'])
                ->setCellValue('C'.$i, $value['day'])
                ->setCellValue('D'.$i, $value['money'])
                ->setCellValue('E'.$i, $value['clearingadmin'])
                ->setCellValue('F'.$i, !empty($value['payinfo'])?$value['payinfo']:'空')
                ->setCellValue('G'.$i, '充值成功');
            $i++;
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
     * 获取站长余额
     */
    private function _getWebMoney($res,$date)
    {
        foreach($res as $key=>$value){
            $moneyPay = Loader::model('Paylog')->webMoneyPay($value['uid'],$date);
            if(empty($moneyPay['cpd'])){
                $res[$key]['zmoney'] = floor(($value['money'] + $moneyPay['money'])*100)/100;
            }else{
                $res[$key]['zmoney'] = floor($value['money']*100)/100;
            }
        }
        return $res;
    }


    /**
     * 处理站长上一周的钱 以及包天情况下
     *
     */
    private function _getCpdMoney($lastMoney,$params)
    {
        //初始化
        $data = array();
        foreach ($lastMoney as $key => $value) {
            $name = $value['uid'];
            $data[$name]['xmoney'] = 0;
            $data[$name]['uid'] = $value['uid'];
            $data[$name]['day'] = $value['day'];
        }
        //合并
        foreach ($lastMoney as $get => $res) {
            $name = $res['uid'];
            if(empty($res['cpd'])){
                $data[$name]['xmoney'] = $data[$name]['xmoney'] + $res['sumpay'];
            }else{
                $data[$name]['xmoney'] = $data[$name]['xmoney'] + $res['cpd'];
            }
        }
        return $data;
    }

    /**
     * 获取站长上周的钱   并组装数据
     * status 3为未支付
     */
    private function _getMoney($lastMoney,$params)
    {
        if(empty($lastMoney)){
            $data = '';
        }else{
            foreach ($lastMoney as $key => $value) {
                //查看上周的钱是否已经更新入paylog
                $paySet = Loader::model('Paylog')->paySet($value['uid'],$params);
                if(empty($paySet['uid'])){
                    $data =array(
                        'uid' => $value['uid'],
                        'xmoney' => $value['sumpay'],
                        'type' =>'1',
                        'status' =>'3',
                        'payinfo'=> '4',
                        'day' =>  $params['sun'],
                        'ctime' => time());
                    $payInster = Loader::model('Paylog')->payInster($data);
                }else{
                    $data = '';
                }
            }
        }

        return $data;
    }

    /**
     *  处理已支付导出
     */
    private function _getpaid($params)
    {
        //得到本周已支付的站长
        $array = Loader::model('Paylog')->getDay($params);
        if(!empty($array)){
            $total = array();
            foreach ($array as $key => $value) {
                //得到已支付信息
                $total[$value['uid']] = Loader::model('Paylog')->getExcelpaid($value,$params);
                $total[$value['uid']]['payday'] = $value['clearingadmin'];
                $total[$value['uid']]['money'] = $value['money'];
                $total[$value['uid']]['payinfo'] = $value['payinfo'];
            }
            $data = array();
            //按照广告位 +样式   考虑包天   得到广告位的信息
            foreach ($total as $key => $value) {
                $count = count($value);
                if($count > 3){
                    foreach ($value as $get => $arr) {
                        if(is_array($arr)){
                            $name = $arr['adz_id'].'-'.$arr['adtpl_id'];
                            if(!isset($data[$name]['sumpay'])){
                                $data[$name]['sumpay'] = 0;
                            }
                            if(!empty($arr['cpd'])){
                                $arr['sumpay'] = $arr['cpd'];
                            }
                            $data[$name]['uid'] = $arr['uid'];
                            $data[$name]['adz_id'] = $arr['adz_id'];
                            $data[$name]['adtpl_id'] = $arr['adtpl_id'];
                            $data[$name]['sumpay'] += $arr['sumpay'];  //同样式同广告位跑量佣金总和包含包天情况
                            $data[$name]['payday'] = $value['payday'];
                            $data[$name]['money'] = $value['money'];
                            $data[$name]['payinfo'] = $value['payinfo'];
                            $data[$name]['username'] = $arr['username'];
                            $data[$name]['account_name'] = $arr['account_name'];
                            $data[$name]['bank_card'] = $arr['bank_card'];
                            $data[$name]['bank'] = $arr['bank_name'].$arr['bank_branch'];
                            $data[$name]['username'] = $arr['username'];
                            $data[$name]['uname'] = $arr['uname'];
                        }
                    }
                }else{
                    unset($value);
                }
            }
            //拼接数据
            $res = $this->_getUidArray($data,$params);
            return $res;
        }else{
            return '';
        }

    }


    /**
     * 计算站长的钱
     */
    private function _getUidArray($res,$params)
    {
        $data = array();
        foreach ($res as $key => $value) {
            $i = $value['uid'];
            $data[$i]['bottom'] = isset($data[$i]['bottom'])?$data[$i]['bottom']:0;//底部
            $data[$i]['tablepla'] = isset($data[$i]['tablepla'])?$data[$i]['tablepla']:0;//插屏
            $data[$i]['left'] = isset($data[$i]['left'])?$data[$i]['left']:0;//右漂
            $data[$i]['top'] = isset($data[$i]['top'])?$data[$i]['top']:0;//顶部
            $data[$i]['fixed'] = isset($data[$i]['fixed'])?$data[$i]['fixed']:0;//固定
            $data[$i]['txt'] = isset($data[$i]['txt'])?$data[$i]['txt']:0;//文字
            $data[$i]['sumpay'] = isset($data[$i]['sumpay'])?$data[$i]['sumpay']:0; //总和
            //$data[$i]['txt'] = 0;//文字
            $data[$i]['uid'] = $value['uid'];
            $data[$i]['username'] = $value['username'];
            $data[$i]['uname'] = $value['uname'];
            $data[$i]['adtpl_id'] = $value['adtpl_id'];
            $data[$i]['account_name'] = $value['account_name'];
            $data[$i]['bank_card'] = $value['bank_card'];
            $data[$i]['bank'] = $value['bank'];
            $data[$i]['sumpay'] += $value['sumpay'];
            if($value['adtpl_id'] == 5017){//底部
                $data[$i]['bottom'] += $value['sumpay'];
            }elseif($value['adtpl_id'] == 5015){//插屏
                $data[$i]['tablepla'] += $value['sumpay'];
            }elseif($value['adtpl_id'] == 5029){//右漂
                $data[$i]['left'] += $value['sumpay'];
            }elseif($value['adtpl_id'] == 5032){//顶部
                $data[$i]['top'] += $value['sumpay'];
            }elseif($value['adtpl_id'] == 5030){//固定
                $data[$i]['fixed'] += $value['sumpay'];
            }else{
                $data[$i]['txt'] = $value['sumpay'];//文字
            }
            if(empty($value['payday'])){
                $data[$i]['payday'] = '此站长为第一次结算,期间为最近三个月';
            }else{
                // $value['payday'] = date('Y-m-d', strtotime('+1 day', strtotime($value['payday'])));
                $data[$i]['payday'] = $value['payday'].'至'.$params['sun'];
            }
            $data[$i]['money'] = $value['money'];
            $data[$i]['payinfo'] = empty($value['payinfo'])?'':$value['payinfo'];
        }
        return $data;
    }

    /**
     *  处理导出未支付
     */
    private function _getpal($params)
    {
        //得出未支付列表
        $getCope = Loader::model('Paylog')->getCope();
        $total = array();
        foreach ($getCope as $key => $value) {
            //处理时间  查询出来的时间为周日
            $day = date("Y-m-d",strtotime('-6 days',strtotime($value['day'])));
            //查询未结算的站长下的广告位
            $total[$value['uid']] = Loader::model('Paylog')->getExcel($value['uid'],$day,$params);
            $total[$value['uid']]['payday'] = $day;
            $total[$value['uid']]['money'] = $value['xmoney'];  //未支付应付金额
        }
        $data = array();
        //按照广告位 +样式   考虑包天   得到广告位的信息
        foreach ($total as $key => $value) {
            $count = count($value);
            if($count > 2){
                foreach ($value as $get => $arr) {
                    if(is_array($arr)){
                        $name = $arr['adz_id'].'-'.$arr['adtpl_id'];
                        if(!isset($data[$name]['sumpay'])){
                            $data[$name]['sumpay'] = 0;
                        }
                        if(!empty($arr['cpd'])){
                            $arr['sumpay'] = $arr['cpd'];
                        }
                        $data[$name]['uid'] = $arr['uid'];
                        $data[$name]['adz_id'] = $arr['adz_id'];
                        $data[$name]['adtpl_id'] = $arr['adtpl_id'];
                        $data[$name]['sumpay'] += $arr['sumpay'];  //同样式同广告位跑量佣金总和包含包天情况
                        $data[$name]['payday'] = $value['payday'];
                        $data[$name]['money'] = $value['money'];
                        $data[$name]['username'] = $arr['username'];
                        $data[$name]['account_name'] = $arr['account_name'];
                        $data[$name]['bank_card'] = $arr['bank_card'];
                        $data[$name]['bank'] = $arr['bank_name'].$arr['bank_branch'];
                        $data[$name]['username'] = $arr['username'];
                        $data[$name]['uname'] = $arr['uname'];
                    }
                }
            }else{
                unset($value);
            }
        }
        $res = $this->_getUidArray($data,$params);
        return $res;
    }
    /**
     * 获取指定日期段内每一天的日期
     * @param Date $startdate 开始日期
     * @param Date $enddate  结束日期
     * @return Array
     */
    function getDateFromRange($startdate, $enddate){
        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);
        // 计算日期段内有多少天
        $days = ($etimestamp-$stimestamp)/86400+1;
        // 保存每天日期
        $date = array();
        for($i=0; $i<$days; $i++){
            $date[] = date('Y-m-d', $stimestamp+(86400*$i));
        }
        return $date;
    }


}
