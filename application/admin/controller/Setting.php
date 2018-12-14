<?php
/* 基本设置
 * date   2016-6-2
 */
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Loader;
use think\Hook;
use think\Db;
use think\Session;

class Setting extends Admin
{
    /**
     * 首页
     */
    public function index()
    {

        $id = Session::get('user_login_id');
        //查询管理员权限
        $res = Loader::model('Setting')->getAuth($id);
        $group_id = $res[0]['group_id'];
        $auth = explode(',',$res[0]['rules']);
        $name = array();
        foreach($auth as $key=>$value){
            $arr = Loader::model('Setting')->getAuthName($value);
            $arr['name'] = !isset($arr['name'])?'':$arr['name'];
            $name[$arr['name']] = $arr['name'];
        }

        Session::set('auth_name',$name);
        $uname = Session::get('uname');
        //计算广告商余额
        $get = Loader::model('Index')->getMoney();
        $set = Loader::model('Index')->getSetAdv();
        $this->assign('set',$set);
        $this->assign('remind',$get);
        $this->assign('uname',$uname);
        $this->assign('group_id',$group_id);
//        $this->assign('auth_name',$name);
        return $this->fetch('index');
    }

	/**
     * lizz 账号下，首页计划和广告位提醒
     */
    public function reminding()
    {
        //查询需要提醒的计划和广告位
        $res = loader::model('Setting')->getlizzlist();
        foreach($res as $key => $value){
            //处理提示类型
            $res[$key]['pid_adzid'] = empty($value['pid']) ? $value['adz_id'] : $value['pid'];
            $res[$key]['pname_adzname'] = empty($value['plan_name']) ? $value['adz_name'] : $value['plan_name'];
            $res[$key]['url'] = ($value['type'] == 1) ? '/admin/plan/list?plan=pid&search='.$value['pid'] : '/admin/site/adzone?adzone=adz_id&status=adzone_all&search='.$value['adz_id'];
            if($value['type'] == 1){
                $res[$key]['remind'] = '新增计划';
            }elseif($value['type'] == 2){
                $res[$key]['remind'] = '新增广告位';
            }else{
                $res[$key]['remind'] = '重新激活广告位';
            }
            unset($res[$key]['pid']);unset($res[$key]['adz_id']);unset($res[$key]['plan_name']);
            unset($res[$key]['adz_name']);unset($res[$key]['type']);
        }
        //数组合并
        $count = count($res);
        Session::set('reminding_num',$count);
        return $res;

    }

    /**
     * 点击关闭后直接删除
     */
    public function clickVanish()
    {
        $id = $_GET['id'];
        // 删除
        $delete = loader::model('Setting')->delLizzlist($id);
        return $delete;

    }

    /**
     * 基本设置
     */
    public function basic()
    {
        //instance
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            //组装参数
            $data = $this->_dataBasic($params);
            //更新数据库中的数据
            Loader::model('Setting')->UpdateBasic($data);
            //数据更新完毕，刷新当前页面
            $this->redirect('Setting/basic',['cmd_flag' => '1']);
        } else{
            $res = Loader::model('Setting')->getone();
            $res[0]['keep'] = $request->param('cmd_flag');
            $this->assign('setting_basic',$res[0]);
            return $this->fetch('setting-basic');
        }
    }


    /**
     *  权限页面
     */
    public function roles()
    {
        Hook::listen('auth',$this->_uid,'auth-roles'); //权限
        $res = Loader::model('Setting')->getRoles();
        $this->assign('one',$res);
        return $this->fetch('setting-auth');
    }

    /**
     * 服务器设置
     */
    public function service()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            //组装参数
            $data = $this->_dataService($params);
            //更新数据库中的数据
            Loader::model('Setting')->UpdateBasic($data);
            //数据更新完毕，刷新当前页面
            $this->redirect('Setting/service',['cmd_flag' => '1']);
        } else{
            $res = Loader::model('Setting')->getone();
            $res[0]['keep'] = $request->param('cmd_flag');
            $this->assign('setting_service',$res[0]);
            return $this->fetch('setting-service');
        }
    }

    /**
     * 方便的 图片  跳转服务器设置
     */
    public function serviceEasy()
    {
        $request = Request::instance();
        $params = $request->post();
        $status = $request->param('status');
        if($request->isPost() && !empty($params['type'])){
            $data = array(
                'type' => $params['type'],
                'find_url'=>'.'.$params['find_url'].'.',
                'update_url'=>'.'.$params['update_url'].'.',
            );

            //查询更新服务器地址的数据
            $res = Loader::model('Setting')->Batchfind($data);
            if(!empty($res)){
                foreach($res as $key => $val)
                {
                    $ad_id[] = $val['ad_id'];
                }
                $txt_adid = implode(',',$ad_id);
                //批量更新服务器地址
                $update = Loader::model('Setting')->Batchupdate($data,$txt_adid);
                if($update >=0){
                    //写操作日志
                    $this->logWrite('0060',$data,$txt_adid);
                    $this->redirect('serviceEasy',['status' => '1']);
                }else{
                    $this->redirect('serviceEasy',['status' => '0']);
                }
            }else{
                $this->redirect('serviceEasy',['status' => '0']);
            }
        }
        if($status === ''){
            $status = 2;
        }
        $this->assign('status',$status);
        return $this->fetch('setting-service_easy');
    }

    /**
     * 财务相关
     */
    public function finance()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            //组装参数
            $data = $this->_dataFinance($params);
            //更新数据库中的数据
            Loader::model('Setting')->UpdateBasic($data);
            //数据更新完毕，刷新当前页面
            $this->redirect('Setting/finance',['cmd_flag' => '1']);
        } else{
            $res = Loader::model('Setting')->getone();
            $res[0]['keep'] = $request->param('cmd_flag');
            $this->assign('setting_finance',$res[0]);
            return $this->fetch('setting-finance');
        }
    }

    /**
     *  编辑权限
     */
    public function authEdit()
    {
        Hook::listen('auth',$this->_uid,'auth-roles'); //权限
        $uid = Request::instance()->param('uid');
        $res = Loader::model('Setting')->getRolesName();
        $date = $this->_getRoles($res);
        $auth = Loader::model('Setting')->getAuth($uid);
        $this->assign('data',$date);
        $this->assign('auth',$auth);
        return $this->fetch('auth-edit');
    }

    /**
     * 更新管理员的权限
     */
    public function updateAuth()
    {
        Hook::listen('auth',$this->_uid,'auth-roles'); //权限
        $request = Request::instance();
        $params = $request->post();
        $date['rules'] = empty($params['roles']) ? '' : implode(',',$params['roles']);

        $res = Loader::model('Setting')->updateAuth($date,$params['uid']);
        if($res>=0){
            $this->redirect('roles');
        }else{
            $this->_error();
        }
    }

    /**
     * 网站管理列表导出
     */
    public function remindExcel()
    {
        //查询需要提醒的计划和广告位
        $res = loader::model('Setting')->getlizzlist();
        foreach($res as $key => $value){
            //处理提示类型
            $res[$key]['pid_adzid'] = empty($value['pid']) ? $value['adz_id'] : $value['pid'];
            $res[$key]['pname_adzname'] = empty($value['plan_name']) ? $value['adz_name'] : $value['plan_name'];
            $res[$key]['url'] = ($value['type'] == 1) ? '/admin/plan/list?plan=pid&search='.$value['pid'] : '/admin/site/adzone?adzone=adz_id&status=adzone_all&search='.$value['adz_id'];
            if($value['type'] == 1){
                $res[$key]['remind'] = '新增计划';
            }elseif($value['type'] == 2){
                $res[$key]['remind'] = '新增广告位';
            }else{
                $res[$key]['remind'] = '重新激活广告位';
            }
            unset($res[$key]['pid']);unset($res[$key]['adz_id']);unset($res[$key]['plan_name']);
            unset($res[$key]['adz_name']);unset($res[$key]['type']);
        }

        require_once  "../extend/org/vendor/autoload.php";
        //修改内存
        ini_set('memory_limit','500M');
        //修改时间
        ini_set("max_execution_time", "0");

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
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objPHPExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '提示类型')
            ->setCellValue('B1', '计划/广告位id')
            ->setCellValue('C1', '计划/广告位名称')
            ->setCellValue('D1', '广告商/站长id')
            ->setCellValue('E1', '广告商/站长名称')
            ->setCellValue('F1', '投放设备')
            ->setCellValue('G1', '所属客服/商务');
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:G1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($res as $key => $value) {

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['remind'])
                ->setCellValue('B'.$i, $value['pid_adzid'])
                ->setCellValue('C'.$i, $value['pname_adzname'])
                ->setCellValue('D'.$i, $value['uid'])
                ->setCellValue('E'.$i, $value['username'])
                ->setCellValue('F'.$i, $value['terminal'])
                ->setCellValue('G'.$i, $value['customer']);
            $i++;
        }
        // 重命名工作表
        $objPHPExcel->getActiveSheet()->setTitle('sheet1');
        // 将活动表索引设置为第一个表，所以将此作为第一张表打开
        $objPHPExcel->setActiveSheetIndex(0);

        //设置表的名字
        $filename='首页提醒('.date('Y-m-d H:i:s').')';

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
     * 扣量设置参数
     * param data 参数数组
     */
    private function _dataBasic($params)
    {
        //从页面获取基本设置的值
        $data = array(
            'sitename' => $params['sitename1'],//联盟名称
            'cpc' => empty($params['cpc'])? '0':$params['cpc'],//计费模式开关CPC
            'cpm' => empty($params['cpm'])? '0':$params['cpm'],//计费模式开关CPM
//            'cpv' => empty($params['cpv'])? '0':$params['cpv'],//计费模式开关CPV
//            'cps' => empty($params['cps'])? '0':$params['cps'],//计费模式开关CPS
//            'cpa' => empty($params['cpa'])? '0':$params['cpa'],//计费模式开关CPA
            'domain_limit' => $params['domain_limit'],//域名限制
            'adv_money' => $params['adv_money'],
//            'pv_step' => $params['pv_step'],//PV成长值
            'maximum_clicks' => $params['maximum_clicks'],//计费次数限制
            'bigclick_status' => $params['bigclick_status'],//计费次数限制
//            'opne_affiliate_register' => empty($params['opne_affiliate_register'])?
//                '0':$params['opne_affiliate_register'],//开放站长注册
//            'opne_advertiser_register' => empty($params['opne_advertiser_register'])?
//                '0':$params['opne_advertiser_register'],//开放广告商注册
//            'register_status' => $params['register_status'],//注册验证
//            'add_website' => $params['add_website'],//新增网站
//            'site_status' => $params['site_status'],//联盟标志
//            '24_hours_register_num' => $params['24_hours_register_num'],//24小时允许注册
//            'ban_ip_register' => $params['ban_ip_register'],//屏蔽以下IP注册
//            'login_check_code' => $params['login_check_code'],//会员登录注册码
//            'registered_check_code' => $params['registered_check_code'],//会员注册验证码
//            'register_add_money_on' => $params['register_add_money_on'],//注册赠送钱
        );
        return $data;
    }

    /**
     * 服务器设置
     * param data 参数数组
     */
    private function _dataService($params)
    {
        $array = array();
        $array['js_server'] = explode('/',$params['js_server']);
        $array['img_server'] = explode('/',$params['img_server']);
        $array['jump_server'] = explode('/',$params['jump_server']);
        $array['https_server'] = explode('/',$params['https_server']);
        $array['adv_server'] = explode('/',$params['adv_server']);
        //处理提交数据  去掉最后面 /
        if(empty($array['js_server'][1])){
            $array['js_server'][1] = !empty($array['js_server'][2]) ? $array['js_server'][2] : '';
        }else{
            $array['js_server'][1] = $array['js_server'][1];
        }
        if(empty($array['img_server'][1])){
            $array['img_server'][1] = !empty($array['img_server'][2]) ? $array['img_server'][2] : '';
        }else{
            $array['img_server'][1] = $array['img_server'][1];
        }
        if(empty($array['jump_server'][1])){
            $array['jump_server'][1] = !empty($array['jump_server'][2]) ? $array['jump_server'][2] : '';
        }else{
            $array['jump_server'][1] = $array['jump_server'][1];
        }
        if(empty($array['https_server'][1])){
            $array['https_server'][1] = !empty($array['https_server'][2]) ? $array['https_server'][2] : '';
        }
        if(empty($array['adv_server'][1])){
            $array['adv_server'][1] = !empty($array['adv_server'][2]) ? $array['adv_server'][2] : '';
        }
        $params['js_server'] = $array['js_server'][0].'//'.$array['js_server'][1];
        $params['img_server'] = $array['img_server'][0].'//'.$array['img_server'][1];
        $params['jump_server'] = $array['jump_server'][0].'//'.$array['jump_server'][1];
        $params['https_server'] = $array['https_server'][0].'//'.$array['https_server'][1];
        $params['adv_server'] = $array['adv_server'][0].'//'.$array['adv_server'][1];
        //从页面获取服务器设置的值
        $data = array(
//            'main_server' => empty($params['main_server'])? '0':$params['main_server'],//主服务器
            'js_server' => empty($params['js_server'])? '0':$params['js_server'],//js服务器
            'img_server' => empty($params['img_server'])? '0':$params['img_server'],//图片服务器
            'jump_server' => empty($params['jump_server'])? '0':$params['jump_server'],//跳转服务器
            'https_server' => empty($params['https_server'])? '0':$params['https_server'],//跳转服务器
            'adv_server' => empty($params['adv_server'])? '0':$params['adv_server'],//广告域名服务器
        );
        return $data;
    }

    /**
     * 最低付款金额
     * param data 参数数组
     */
    private function _dataFinance($params)
    {
        //从页面获取最低付款金额的值
        $data = array(
            'least_money' => empty($params['least_money'])? '0':$params['least_money'],//最低付款金额
        );
        return $data;
    }

    /**
     * 组装权限页面的数据
     */
    private function _getRoles($res)
    {
        $data = array();
        foreach($res as $key=>$value){
            $arr = explode('-',$value['name']);
            switch($arr[0]){
                case 'Setting':
                    $data['setting'][] = $value;
                    break;
                case 'Deduction':
                    $data['deduction'][] = $value;
                    break;
                case 'Users':
                    $data['users'][] = $value;
                    break;
                case 'Plan':
                    $data['plan'][] = $value;
                    break;
                case 'Ads':
                    $data['ads'][] = $value;
                    break;
                case 'Adtpl':
                    $data['adtpl'][] = $value;
                    break;
                case 'Site':
                    $data['site'][] = $value;
                    break;
                case 'Classes':
                    $data['classes'][] = $value;
                    break;
                case 'Report':
                    $data['report'][] = $value;
                    break;
                case 'Paylog':
                    $data['paylog'][] = $value;
                    break;
                case 'Auxiliary':
                    $data['Auxiliary'][] = $value;
                    break;
                case 'Operationlog':
                    $data['Operationlog'][] = $value;
                    break;
                case 'Monitor':
                    $data['monitor'][] = $value;
                    break;
                case 'Adzdomain':
                    $data['Adzdomain'][] = $value;
                    break;
                case 'Auth':
                    $data['auth'][] = $value;
                    break;
                case 'Bigclick':
                    $data['bigclick'][] = $value;
                    break;
                case 'Clickcpm':
                    $data['clickcpm'][] = $value;
                    break;
                case 'Tackjs':
                    $data['Tackjs'][] = $value;
                    break;
                case 'Newtest':
                    $data['Newtest'][] = $value;
                    break;
                case 'Cpatype':
                    $data['Cpatype'][] = $value;
                    break;
            }
        }
        return $data;
    }

}