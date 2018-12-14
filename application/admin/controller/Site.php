<?php
/* 
 * 网站与广告位管理
 * date   2016-6-2
 */
namespace app\admin\controller;

use think\Controller;
use think\Loader;
use think\Request;
use think\Hook;
use think\Cache;
use app\user\api\DelApi as DelApi;


class Site extends Admin
{
    public function _initialize()
    {
        parent::_initialize();
        header("Cache-control: private");
    }

    /**
     * 网站管理列表
     */
    public function index()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $pageParam = $request->param('');
        if (empty($pageParam)) {
            $pageParam = array('status' => 'site_all',
                'index' => '',
                'search' => '');
        } else {
            $pageParam['search'] = empty($pageParam['search']) ? '' : $pageParam['search'];
            $pageParam['index'] = empty($pageParam['index']) ? '' : $pageParam['index'];
        }
        $total = Loader::model('site')->siteCount($pageParam);
        $Page = new \org\PageUtil($total, $pageParam);
        $show = $Page->show($request->action(), $pageParam);
        $siteList = Loader::model('site')->siteList($Page->firstRow, $Page->listRows, $pageParam);
        //获取今日访问和昨日访问
        $siteList = $this->_getViews($siteList);
        $judge_name = $request->session('uname');
        $this->assign('judge_name', $judge_name);
        $this->assign('one', $pageParam);
        $this->assign('site_list', $siteList);
        $this->assign('page', $show);
        return $this->fetch('site-list');
    }

    /**
     * 网站管理列表导出
     */
    public function siteExcel()
    {
        $request = Request::instance();
        $pageParam = $request->param('');

        if (empty($pageParam)) {
            $pageParam = array('status' => 'site_all',
                'index' => '',
                'search' => '');
        } else {
            $pageParam['search'] = empty($pageParam['search']) ? '' : $pageParam['search'];
            $pageParam['index'] = empty($pageParam['index']) ? '' : $pageParam['index'];
        }

        $total = Loader::model('site')->siteListCount($pageParam);

        $siteList = Loader::model('site')->siteList('0', $total, $pageParam);

        //获取今日访问和昨日访问  
        $siteList = $this->_getViews($siteList);

        // 对数组今日访问 进行倒序排序
        $posiList = $this->f_order($siteList, 'todayViews', '2');

        require_once "../extend/org/vendor/autoload.php";
        //修改内存
        ini_set('memory_limit', '500M');
        //修改时间
        ini_set("max_execution_time", "0");

        //统计数据个数
        $num_count = count($posiList) + 1;
        $objPHPExcel = new \ PHPExcel();

        $objPHPExcel->getProperties();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $objPHPExcel->getActiveSheet()->getStyle('D2')->getNumberFormat()->setFormatCode("0");
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);

        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '站长id')
            ->setCellValue('B1', '站长名称')
            ->setCellValue('C1', '网站名称')
            ->setCellValue('D1', '网站地址')
            ->setCellValue('E1', '网站类型')
            ->setCellValue('F1', '今日访问')
            ->setCellValue('G1', '昨日访问');
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:G1' . $num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($posiList as $key => $value) {

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $value['uid'])
                ->setCellValue('B' . $i, $value['username'])
                ->setCellValue('C' . $i, $value['sitename'])
                ->setCellValue('D' . $i, $value['siteurl'])
                ->setCellValue('E' . $i, $value['class_name'])
                ->setCellValue('F' . $i, $value['todayViews'])
                ->setCellValue('G' . $i, $value['yesterdayViews']);
            $i++;
        }
        // 重命名工作表
        $objPHPExcel->getActiveSheet()->setTitle('sheet1');
        // 将活动表索引设置为第一个表，所以将此作为第一张表打开
        $objPHPExcel->setActiveSheetIndex(0);

        //设置表的名字
        $filename = '网站管理列表数据导出';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        // IE 9浏览器设置
        header('Cache-Control: max-age=1');
        // header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // 过去的日期
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        //导出Excel
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;


    }

    /*
     *content: 根据数组某个字段进行排序
     * $arr    需要排序的数组
     * $field  数组里的某个字段
     * sort    1为正序排序  2为倒序排序
     * time :  2016年12月21日19:02:33
     */
    public function f_order($arr, $field, $sort)
    {
        $order = array();
        foreach ($arr as $kay => $value) {
            $order[] = $value[$field];
        }
        if ($sort == 1) {
            array_multisort($order, SORT_ASC, $arr);
        } else {
            array_multisort($order, SORT_DESC, $arr);
        }
        return $arr;
    }

    /**
     * 网站列表 add
     */
    public function siteAdd()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        //区分 添加
        $params = $request->get('id');
        $this->assign('id', $params);
        // 网站分类
        $class_list = Loader::model('site')->classList();
        $this->assign('class_list', $class_list);
        $params = $request->post();
        if ($request->isPost()) {
            //删除指定字符
            $site_url = str_replace('http:', '', $params['siteurl']);
            $site_url = str_replace('https:', '', $site_url);
            $site_url = explode('/', $site_url);
            $site_url = isset($site_url[2]) ? $site_url[2] : $site_url[0];
            $params['siteurl'] = $site_url;

            $data = array(
                'uid' => $params['uid'],
                'sitename' => trim($params['sitename']),
                'siteurl' => trim($params['siteurl']),
                'https' => $params['https'],
                'class_id' => $params['class_id'],
                'beian' => $params['sitebeian'],
                'web_deduction' => empty($params['web_deduction']) ? 0 : $params['web_deduction'],
                'adv_deduction' => empty($params['adv_deduction']) ? 0 : $params['adv_deduction'],
                'star' => $params['star'],
                'site_cnzz_id' => $params['site_cnzz_id'],
                'status' => 1,
                'add_time' => date("Y-m-d H:i:s", time()),
                'ctime' => time(),
            );

            $add = Loader::model('site')->siteAdd($data);
            //写操作日志
            $this->logWrite('0031', $data['sitename']);
            if ($add > 0) {
                $this->redirect('index');
            } else {
                $this->_error();
            }
        }
        return $this->fetch('site-add');
    }

    /**
     * 查询是否有此用户
     */
    public function userOne()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->post();
        $user_find = Loader::model('site')->userOne($params['username']);
        if ($user_find == true) {
            echo $user_find['uid'];
        } else {
            echo 0;
        }
    }

    /**
     * 修改 网站状态 0 锁定 1 激活
     */
    public function siteEditStatus()
    {
        Hook::listen('auth', $this->_uid); //权限
        $params = input('');
        if (!empty($params)) {
            $update = Loader::model('site')->siteEditStatus($params['id'], $params['status']);
            if ($update > 0) {
                //判断操作日志的写入内容
                if ($params['status'] == 1) {
                    //写操作日志
                    $this->logWrite('0032', $params['id']);
                } else {
                    //写操作日志
                    $this->logWrite('0033', $params['id']);
                }
                $this->_success();
            } else {
                $this->_error();
            }
        }
    }

    /**
     * 更改网站列表扣量
     */
    public function deduction()
    {
//        Hook::listen('auth',$this->_uid,'plan-changePrice'); //权限
        $params = Request::instance()->post();
        $data['site_id'] = $params['site_id'];
        if ($params['type'] == 'web') {
            $data['web_deduction'] = $params['money'];
        } else {
            $data['adv_deduction'] = $params['money'];
        }
        $res = Loader::model('site')->deduction($data);
        if ($res >= 0) {
            $this->_success();
        } else {
            $this->_error();
        }
    }

    /**
     * 编辑 网站信息
     */
    public function siteEdit()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        // 区分编辑
        $id = $request->get('id');
        $this->assign('id', $id);
        // 网站分类
        $class_list = Loader::model('site')->classList();
        $this->assign('class_list', $class_list);
        //显示默认数据
        $siteOne = Loader::model('site')->siteOne($id);

        $this->assign('site_one', $siteOne);
        if ($request->isPost()) {
            $params = $request->post();
            $data = array(
                'uid' => $params['uid'],
                'sitename' => $params['sitename'],
                'siteurl' => trim($params['siteurl']),
                'https' => $params['https'],
                'class_id' => $params['class_id'],
                'beian' => $params['sitebeian'],
                'star' => $params['star'],
                'web_deduction' => empty($params['web_deduction']) ? 0 : $params['web_deduction'],
                'adv_deduction' => empty($params['adv_deduction']) ? 0 : $params['adv_deduction'],
                'site_cnzz_id' => empty($params['site_cnzz_id']) ? 0 : $params['site_cnzz_id'],
                'add_time' => date("Y-m-d H:i:s", time()),
                'ctime' => time(),
            );
            $edit = Loader::model('site')->siteEdit($id, $data);
            //写操作日志
            $this->logWrite('0034', $data['sitename']);
            if ($edit > 0) {
                $this->redirect('index');
            } else {
                $this->error();
            }
        }
        return $this->fetch('site-edit');
    }

    /**
     * 网站列表 删除
     */
    public function siteDel()
    {
        Hook::listen('auth', $this->_uid); //权限
        $params = input('');
        $UserApi = new DelApi();
        $UserApi->del($params['id'], 'site');
        $res = Loader::model('site')->delOne($params['id']);
        if ($res > 0) {
            //写操作日志
            $this->logWrite('0035', $params['id']);
            $this->_success();
        } else {
            $this->error();
        }
    }

    /**
     * 广告位管理  列表
     */
    public function adzone()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $pageParam = $request->param();
        if (!isset($pageParam['status']) && empty($pageParam['search']) && empty($pageParam['adzone'])) {
            $pageParam = array('status' => 'adzone_all',
                'search' => '',
                'adzone' => '',
                'style_id' => empty($pageParam['style_id']) ? '0' : $pageParam['style_id']);
        } else {
            $pageParam['search'] = empty($pageParam['search']) ? '' : trim($pageParam['search']);
            $pageParam['adzone'] = empty($pageParam['adzone']) ? '' : $pageParam['adzone'];
            //判断是否是从广告样式跳转过来的
            $pageParam['style_id'] = empty($pageParam['style_id']) ? '0' : $pageParam['style_id'];
        }
        //处理投放设备,投放尺寸,投放模式没有值得情况
        $pageParam['system_type'] = empty($pageParam['system_type']) ? '-1' : $pageParam['system_type'];
        $pageParam['adzsize'] = empty($pageParam['adzsize']) ? '-1' : $pageParam['adzsize'];
        $pageParam['adtpl_id'] = empty($pageParam['adtpl_id']) ? '-1' : $pageParam['adtpl_id'];
        //分页
        $total = Loader::model('site')->adzoneCount($pageParam);
        $Page = new \org\PageUtil($total, $pageParam);
        $show = $Page->show($request->action(), $pageParam);
        $adzoneList = Loader::model('site')->adzone($Page->firstRow, $Page->listRows, $pageParam);
        //获取所有的广告模式
        $pageParam['admode'] = Loader::model('site')->getAdtype();
        $this->assign('one', $pageParam);
        $this->assign('adzone_list', $adzoneList);
        $this->assign('page', $show);
        return $this->fetch('adzone-list');
    }

    /**
     * 广告位包天详情
     */
    public function zonecopylist()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        //分页功能
        $pageParam = $request->param('');
        $total = Loader::model('site')->adzoneCopyCount($pageParam['id']);

        $Page = new \org\PageUtil($total, $pageParam);
        $show = $Page->show($request->action(), $pageParam);
        $adzoneList = Loader::model('site')->adzoneCopy($pageParam['id'], $Page->firstRow, $Page->listRows);
        $this->assign('adzone_list', $adzoneList);
        $this->assign('page', $show);
        return $this->fetch('adzonecopy-list');
    }

    /**
     * 广告位包天详情
     */
    public function cpdEdit()
    {
        $request = Request::instance();
        $params = $request->param('');
        if ($request->isPost()) {
            //修改包天价钱
            Loader::model('site')->updateCpdMoney($params);
            $this->redirect('Site/zonecopylist?id=' . $params['adz_id']);
        } else {
            //获取该广告位该天的包天价格
            $cpd_money = Loader::model('site')->getCpdMoney($params['id']);
            $this->assign('cpd_money', $cpd_money);
            return $this->fetch('cpd-edit');
        }
    }

    /**
     * 广告位包天详情 删除
     */
    public function batchDel()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $params = $request->param('');
        if (!isset($params['id'])) {
            $this->redirect('Site/adzone');
        }
        foreach ($params['id'] as $key => $value) {
            //删除包天详情  不操作users表
            $res = Loader::model('site')->delCpdDay($value);
        }
        if ($res >= 0) {
            //写操作日志
            $this->logWrite('0036');
            $this->redirect('Site/adzone');
        } else {
            $this->error();
        }
    }

    /**
     * 广告位激活/锁定
     */
    public function activate()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $status = $request->post('status');
        $adzId = $request->post('adz_id');
        $res = Loader::model('Site')->updateStatus($adzId, $status);
        if ($res > 0) {
            //重新激活广告位设置提醒消息
            if ($status == 1) {
                //获取广告位的信息
                $remindData = Loader::model('Site')->getone($adzId);
                if ($remindData['system_type'] == 1) {
                    $remindData['terminal'] = 'ios+android';
                } elseif ($remindData['system_type'] == 2) {
                    $remindData['terminal'] = 'ios';
                } else {
                    $remindData['terminal'] = 'android';
                }
                //插入数据到提醒表中
                Loader::model('Site')->remindingAdd($remindData);
            }
            //判断操作日志的写入内容
            if ($status == 1) {
                //写操作日志
                $this->logWrite('0037', $adzId);
            } else {
                //写操作日志
                $this->logWrite('0038', $adzId);
            }
            $this->_success();

        } else {
            $this->_error('修改失败');
        }
    }

    /**
     * 广告位列表 删除
     */
    public function adzoneDel()
    {
        Hook::listen('auth', $this->_uid); //权限
        $params = input('');
        $UserApi = new DelApi();
        $UserApi->del($params['id'], 'adzone');
        $res = Loader::model('site')->adzoneDel($params['id']);
        if ($res > 0) {
            //写操作日志
            $this->logWrite('0039', $params['id']);
            $this->_success();
        } else {
            $this->error();
        }
    }

    /**
     * 广告位管理  编辑
     */
    public function adzoneEdit()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        if ($request->isPost()) {
            $params = $request->post();
            //数据组装
            $data = $this->_dataAdzEdit($params);
            if (($data['cpd_startday'] != false) && ($data['cpd_endday'] != false) && ($params['cpd_status'] == 1)) {
                //更新广告位副表
                $arr = $this->_updateCopy($params['id'], $data);
                foreach ($arr as $key => $value) {
                    Loader::model('site')->adzoneCopyInsert($value);
                }
                $update = Loader::model('site')->adzoneEdit($params['id'], $data);
            } elseif ($params['cpd_status'] == 0) {
                $update = Loader::model('site')->adzoneEdit($params['id'], $data);
                //当关闭包天功能后 删除今天之后所有包天记录
                $day = date('Y-m-d');
                $delCpd = Loader::model('site')->delCpd($params['id'], $day);
            } else {
                $update = Loader::model('site')->adzoneEdit($params['id'], $data);
            }
            if ($update >= 0) {
                //写操作日志
                $this->logWrite('0040', $data['zonename']);
                $this->_success();
            } else {
                $this->error();
            }
        } else {
            $id = $request->get('id');
            $adzoneOne = Loader::model('site')->adzoneOne($id);
            if (empty($adzoneOne)) {
                $this->redirect('Site/adzone');
            }

            $adzoneOne = $this->_region($adzoneOne);

            $this->assign('adzoneres', $adzoneOne);
            //广告类型
            $admOne = Loader::model('site')->admodeOne($adzoneOne['adtpl_id']);
            //获取广告样式
            $ad_style = Loader::model('site')->getStyle($admOne['tpl_id']);
            //获取广告位类型
            $ad_class = Loader::model('site')->getClass($adzoneOne['class_id']);
            //广告计划分类
            $adzplanclass = Loader::model('site')->adzplanclass();
            if (empty($admOne)) {
                $this->redirect('Site/adzone');
            }
            $adzoneDay = Loader::model('site')->adzoneDay($id);
            //广告位定向弹窗 唤醒
            $adzData['protype'] = Loader::model('site')->getProType();
            //广告位定向弹窗 城市池
            $adzData['adzCity'] = $this->_cityData();
            $adzData['adzHour'] = ["00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10",
                "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23"];
            //广告位附加定向
            $adzData['adzSite'] = Loader::model('site')->getAdzSite($id);
            $name = $request->session('uname');
            $this->assign('ad_style', $ad_style);
            $this->assign('adzData', $adzData);
            $this->assign('admres', $admOne);
            $this->assign('ad_class', $ad_class);
            $this->assign('adzoneDay', $adzoneDay);
            $this->assign('adzplanclass', $adzplanclass);
            $this->assign('name', $name);
            unset($adzData);
            return $this->fetch('adzone-edit');
        }
    }

    /**
     * 处理城市池数据
     */
    private function _cityData()
    {
        $adzCity = Loader::model('site')->getCity();
        $data = array();
        foreach ($adzCity as $key => $value) {
            $city = unserialize($value['city']);
            $data[$key]['id'] = $value['id'];
            $data[$key]['city_name'] = $value['city_name'];
            $data[$key]['province'] = implode(',', $city['city_province']);
            $data[$key]['city'] = implode(',', $city['city_province']) . ',' . implode(',', $city['city_data']);
        }
        return $data;
    }

    /**
     * 地域限制5份 反序列化
     */
    private function _region($adzoneOne)
    {
        //广告位屏蔽计划
        $adzoneOne['plan_check'] = unserialize($adzoneOne['plan_check']);
        $planProvince = !isset($adzoneOne['plan_check']['plan_province']) ? array():$adzoneOne['plan_check']['plan_province'];
        $planCity = !isset($adzoneOne['plan_check']['plan_city']) ? array():$adzoneOne['plan_check']['plan_city'];
        $adzoneOne['plan_check']['plan_province'] = implode(',',$planProvince);
        $adzoneOne['plan_check']['plan_city'] = implode(',',$planCity);
        $adzoneOne['plan_check']['name'] = !isset($adzoneOne['plan_check']['name']) ? '':$adzoneOne['plan_check']['name'];
        $adzoneOne['plan_check']['city_limit'] = !isset($adzoneOne['plan_check']['city_limit']) ? 0:$adzoneOne['plan_check']['city_limit'];
        $adzoneOne['plan_check']['contrast'] = !isset($adzoneOne['plan_check']['contrast']) ? 0:$adzoneOne['plan_check']['contrast'];

        //屏蔽手机型号
        $adzua = unserialize($adzoneOne['adz_ua']);
        $adzoneOne['adz_uaOn'] = empty($adzua['adz_uaOn']) ? 0 : $adzua['adz_uaOn'];
        $adzoneOne['adz_uaLimit'] = empty($adzua['adz_uaLimit']) ? 0 : $adzua['adz_uaLimit'];
        $adzoneOne['adz_ua'] = $adzua['adz_ua'];
        //全屏广告
        $adzoneOne['clickhight'] = unserialize($adzoneOne['clickhight']);
        $adzoneOne['clickhight']['isacl_6'] = empty($adzoneOne['clickhight']['isacl_6']) ? 0 : $adzoneOne['clickhight']['isacl_6'];
        //投放地域
        $adzoneOne['checkadz'] = unserialize($adzoneOne['checkadz']);
        $adzoneOne['province_1'] = '';
        $adzoneOne['province_2'] = '';
        $adzoneOne['province_3'] = '';
        $adzoneOne['province_4'] = '';
        $adzoneOne['province_5'] = '';
        $adzoneOne['city_1'] = '';
        $adzoneOne['city_2'] = '';
        $adzoneOne['city_3'] = '';
        $adzoneOne['city_4'] = '';
        $adzoneOne['city_5'] = '';
        $checkadz_isacl = explode('&', empty($adzoneOne['checkadz']['isacl']) ? '&&&&' : $adzoneOne['checkadz']['isacl']);
        $adzoneOne['checkadz']['isacl_1'] = empty($checkadz_isacl[0]) ? 0 : $checkadz_isacl[0];
        $adzoneOne['checkadz']['isacl_2'] = empty($checkadz_isacl[1]) ? 0 : $checkadz_isacl[1];
        $adzoneOne['checkadz']['isacl_3'] = empty($checkadz_isacl[2]) ? 0 : $checkadz_isacl[2];
        $adzoneOne['checkadz']['isacl_4'] = empty($checkadz_isacl[3]) ? 0 : $checkadz_isacl[3];
        $adzoneOne['checkadz']['isacl_5'] = empty($checkadz_isacl[4]) ? 0 : $checkadz_isacl[4];
        $checkadz_comparison = explode('&', empty($adzoneOne['checkadz']['comparison']) ? '&&&&' : $adzoneOne['checkadz']['comparison']);
        $adzoneOne['checkadz']['comparison_1'] = empty($checkadz_comparison[0]) ? 0 : $checkadz_comparison[0];
        $adzoneOne['checkadz']['comparison_2'] = empty($checkadz_comparison[1]) ? 0 : $checkadz_comparison[1];
        $adzoneOne['checkadz']['comparison_3'] = empty($checkadz_comparison[2]) ? 0 : $checkadz_comparison[2];
        $adzoneOne['checkadz']['comparison_4'] = empty($checkadz_comparison[3]) ? 0 : $checkadz_comparison[3];
        $adzoneOne['checkadz']['comparison_5'] = empty($checkadz_comparison[4]) ? 0 : $checkadz_comparison[4];
        $checkadz_adzcycle1 = explode('&', empty($adzoneOne['checkadz']['adzcycle1']) ? '&&&&' : $adzoneOne['checkadz']['adzcycle1']);
        $adzoneOne['checkadz']['adzcycle1_1'] = !empty($checkadz_adzcycle1[0]) ? $checkadz_adzcycle1[0] : 1;
        $adzoneOne['checkadz']['adzcycle1_2'] = !empty($checkadz_adzcycle1[1]) ? $checkadz_adzcycle1[1] : 1;
        $adzoneOne['checkadz']['adzcycle1_3'] = !empty($checkadz_adzcycle1[2]) ? $checkadz_adzcycle1[2] : 1;
        $adzoneOne['checkadz']['adzcycle1_4'] = !empty($checkadz_adzcycle1[3]) ? $checkadz_adzcycle1[3] : 1;
        $adzoneOne['checkadz']['adzcycle1_5'] = !empty($checkadz_adzcycle1[4]) ? $checkadz_adzcycle1[4] : 1;
        $checkadz_adzcycle2 = explode('&', empty($adzoneOne['checkadz']['adzcycle2']) ? '&&&&' : $adzoneOne['checkadz']['adzcycle2']);
        $adzoneOne['checkadz']['adzcycle2_1'] = !empty($checkadz_adzcycle2[0]) ? $checkadz_adzcycle2[0] : 1;
        $adzoneOne['checkadz']['adzcycle2_2'] = !empty($checkadz_adzcycle2[1]) ? $checkadz_adzcycle2[1] : 1;
        $adzoneOne['checkadz']['adzcycle2_3'] = !empty($checkadz_adzcycle2[2]) ? $checkadz_adzcycle2[2] : 1;
        $adzoneOne['checkadz']['adzcycle2_4'] = !empty($checkadz_adzcycle2[3]) ? $checkadz_adzcycle2[3] : 1;
        $adzoneOne['checkadz']['adzcycle2_5'] = !empty($checkadz_adzcycle2[4]) ? $checkadz_adzcycle2[4] : 1;
        $checkadz_adznum = explode('&', empty($adzoneOne['checkadz']['adznum']) ? '&&&&' : $adzoneOne['checkadz']['adznum']);
        $adzoneOne['checkadz']['adznum_1'] = !empty($checkadz_adznum[0]) ? $checkadz_adznum[0] : 1;
        $adzoneOne['checkadz']['adznum_2'] = !empty($checkadz_adznum[1]) ? $checkadz_adznum[1] : 1;
        $adzoneOne['checkadz']['adznum_3'] = !empty($checkadz_adznum[2]) ? $checkadz_adznum[2] : 1;
        $adzoneOne['checkadz']['adznum_4'] = !empty($checkadz_adznum[3]) ? $checkadz_adznum[3] : 1;
        $adzoneOne['checkadz']['adznum_5'] = !empty($checkadz_adznum[4]) ? $checkadz_adznum[4] : 1;
        $checkadz_adzjs = explode('&', empty($adzoneOne['checkadz']['adzjs']) ? '&&&&' : $adzoneOne['checkadz']['adzjs']);
        $adzoneOne['checkadz']['adzjs_1'] = !empty($checkadz_adzjs[0]) ? $checkadz_adzjs[0] : '';
        $adzoneOne['checkadz']['adzjs_2'] = !empty($checkadz_adzjs[1]) ? $checkadz_adzjs[1] : '';
        $adzoneOne['checkadz']['adzjs_3'] = !empty($checkadz_adzjs[2]) ? $checkadz_adzjs[2] : '';
        $adzoneOne['checkadz']['adzjs_4'] = !empty($checkadz_adzjs[3]) ? $checkadz_adzjs[3] : '';
        $adzoneOne['checkadz']['adzjs_5'] = !empty($checkadz_adzjs[4]) ? $checkadz_adzjs[4] : '';
        //连接符替换
        $adzoneOne['checkadz']['adzjs_1'] = str_replace('#', '&', $checkadz_adzjs[0]);
        $adzoneOne['checkadz']['adzjs_2'] = str_replace('#', '&', $checkadz_adzjs[1]);
        $adzoneOne['checkadz']['adzjs_3'] = str_replace('#', '&', $checkadz_adzjs[2]);
        $adzoneOne['checkadz']['adzjs_4'] = str_replace('#', '&', $checkadz_adzjs[3]);
        $adzoneOne['checkadz']['adzjs_5'] = str_replace('#', '&', $checkadz_adzjs[4]);
        $province_arr = array();
        if (empty($adzoneOne['checkadz']['province'])) {
            $adzoneOne['checkadz']['province'] = '&&&&';
        }
        if (is_array($adzoneOne['checkadz']['province'])) {
            $province = $adzoneOne['checkadz']['province'];
            $province_arr['adz_1'] = !isset($province[0]) ? '' : $province[0];
            $province_arr['adz_2'] = !isset($province[1]) ? '' : $province[1];
            $province_arr['adz_3'] = !isset($province[2]) ? '' : $province[2];
            $province_arr['adz_4'] = !isset($province[3]) ? '' : $province[3];
            $province_arr['adz_5'] = !isset($province[4]) ? '' : $province[4];
        } else {
            $province = explode('&', $adzoneOne['checkadz']['province']);
            $province_arr['adz_1'] = !isset($province[0]) ? '' : unserialize($province[0]);
            $province_arr['adz_2'] = !isset($province[1]) ? '' : unserialize($province[1]);
            $province_arr['adz_3'] = !isset($province[2]) ? '' : unserialize($province[2]);
            $province_arr['adz_4'] = !isset($province[3]) ? '' : unserialize($province[3]);
            $province_arr['adz_5'] = !isset($province[4]) ? '' : unserialize($province[4]);
        }
        $city_arr = array();
        if (empty($adzoneOne['checkadz']['city'])) {
            $adzoneOne['checkadz']['city'] = '&&&&';
        }
        if (is_array($adzoneOne['checkadz']['city'])) {
            $city = implode(',', $adzoneOne['checkadz']['city']);
            $city_arr['adz_1'] = $city;
            $city_arr['adz_2'] = '';
            $city_arr['adz_3'] = '';
            $city_arr['adz_4'] = '';
            $city_arr['adz_5'] = '';
        } else {
            $city = explode('&', $adzoneOne['checkadz']['city']);
            $city_arr['adz_1'] = !isset($city[0]) ? '' : unserialize($city[0]);
            $city_arr['adz_2'] = !isset($city[1]) ? '' : unserialize($city[1]);
            $city_arr['adz_3'] = !isset($city[2]) ? '' : unserialize($city[2]);
            $city_arr['adz_4'] = !isset($city[3]) ? '' : unserialize($city[3]);
            $city_arr['adz_5'] = !isset($city[4]) ? '' : unserialize($city[4]);
        }

        //省份
        $num = 1;
        foreach ($province_arr as $key => $value) {
            $province = 'province_' . $num;
            if (!empty($value)) {
                if (is_array($value)) {
                    $adzoneOne[$province] = implode(',', $value);
                } else {
                    $adzoneOne[$province] = $value;
                }
                unset($checkadz_adzjs['province']);
            } else {
                $adzoneOne[$province] = '';
            }
            $num++;
        }

        //城市
        $num_c = 1;
        foreach ($city_arr as $key => $value) {
            $city = 'city_' . $num_c;
            if (!empty($value)) {
                if (is_array($value)) {
                    $adzoneOne[$city] = implode(',', $value);
                } else {
                    $adzoneOne[$city] = $value;
                }
                unset($checkadz_adzjs['city']);
            } else {
                $adzoneOne[$city] = '';
            }
            $num_c++;
        }
        return $adzoneOne;
    }

    /**
     * 广告位编辑组装数据
     */
    private function _dataAdzEdit($params)
    {
        if (!empty($params['plan_class_allow'])) {
            $plan_class_allow = implode(',', $params['plan_class_allow']);
        } else {
            $plan_class_allow = 0;
        }
        if (!isset($params['city_isacl_1'])) {
            $params['city_isacl_1'] = '';
            $params['city_isacl_2'] = '';
            $params['city_isacl_3'] = '';
            $params['city_isacl_4'] = '';
            $params['city_isacl_5'] = '';
        }
        if (!isset($params['comparison_1'])) {
            $params['comparison_1'] = '';
            $params['comparison_2'] = '';
            $params['comparison_3'] = '';
            $params['comparison_4'] = '';
            $params['comparison_5'] = '';
        }
        if (!isset($params['adzcycle1_1'])) {
            $params['adzcycle1_1'] = '';
            $params['adzcycle1_2'] = '';
            $params['adzcycle1_3'] = '';
            $params['adzcycle1_4'] = '';
            $params['adzcycle1_5'] = '';
        }
        if (!isset($params['adzcycle2_1'])) {
            $params['adzcycle2_1'] = '';
            $params['adzcycle2_2'] = '';
            $params['adzcycle2_3'] = '';
            $params['adzcycle2_4'] = '';
            $params['adzcycle2_5'] = '';
        }
        if (!isset($params['adznum_1'])) {
            $params['adznum_1'] = '';
            $params['adznum_2'] = '';
            $params['adznum_3'] = '';
            $params['adznum_4'] = '';
            $params['adznum_5'] = '';
        }
        $params['adzjs_1'] = !isset($params['adzjs_1']) ? '' : htmlspecialchars_decode($params['adzjs_1']);
        $params['adzjs_2'] = !isset($params['adzjs_2']) ? '' : htmlspecialchars_decode($params['adzjs_2']);
        $params['adzjs_3'] = !isset($params['adzjs_3']) ? '' : htmlspecialchars_decode($params['adzjs_3']);
        $params['adzjs_4'] = !isset($params['adzjs_4']) ? '' : htmlspecialchars_decode($params['adzjs_4']);
        $params['adzjs_5'] = !isset($params['adzjs_5']) ? '' : htmlspecialchars_decode($params['adzjs_5']);
        // //替换连接符
        $params['adzjs_1'] = str_replace("&", '#', $params['adzjs_1']);
        $params['adzjs_2'] = str_replace('&', '#', $params['adzjs_2']);
        $params['adzjs_3'] = str_replace('&', '#', $params['adzjs_3']);
        $params['adzjs_4'] = str_replace('&', '#', $params['adzjs_4']);
        $params['adzjs_5'] = str_replace('&', '#', $params['adzjs_5']);

        $city_isacl = $params['city_isacl_1'] . '&' . $params['city_isacl_2'] . '&' . $params['city_isacl_3'] . '&' . $params['city_isacl_4'] . '&' . $params['city_isacl_5'];
        $comparison = $params['comparison_1'] . '&' . $params['comparison_2'] . '&' . $params['comparison_3'] . '&' . $params['comparison_4'] . '&' . $params['comparison_5'];
        $adzcycle1 = $params['adzcycle1_1'] . '&' . $params['adzcycle1_2'] . '&' . $params['adzcycle1_3'] . '&' . $params['adzcycle1_4'] . '&' . $params['adzcycle1_5'];
        $adzcycle2 = $params['adzcycle2_1'] . '&' . $params['adzcycle2_2'] . '&' . $params['adzcycle2_3'] . '&' . $params['adzcycle2_4'] . '&' . $params['adzcycle2_5'];
        $adznum = $params['adznum_1'] . '&' . $params['adznum_2'] . '&' . $params['adznum_3'] . '&' . $params['adznum_4'] . '&' . $params['adznum_5'];
        $adzjs = $params['adzjs_1'] . '&' . $params['adzjs_2'] . '&' . $params['adzjs_3'] . '&' . $params['adzjs_4'] . '&' . $params['adzjs_5'];
        $provinceregion1 = serialize(isset($params['city_provinceregion1']) ? $params['city_provinceregion1'] : '');
        $provinceregion2 = serialize(isset($params['city_provinceregion2']) ? $params['city_provinceregion2'] : '');
        $provinceregion3 = serialize(isset($params['city_provinceregion3']) ? $params['city_provinceregion3'] : '');
        $provinceregion4 = serialize(isset($params['city_provinceregion4']) ? $params['city_provinceregion4'] : '');
        $provinceregion5 = serialize(isset($params['city_provinceregion5']) ? $params['city_provinceregion5'] : '');
        $province = $provinceregion1 . '&' . $provinceregion2 . '&' . $provinceregion3 . '&' . $provinceregion4 . '&' . $provinceregion5;
        $dataregion1 = serialize(isset($params['city_dataregion1']) ? $params['city_dataregion1'] : '');
        $dataregion2 = serialize(isset($params['city_dataregion2']) ? $params['city_dataregion2'] : '');
        $dataregion3 = serialize(isset($params['city_dataregion3']) ? $params['city_dataregion3'] : '');
        $dataregion4 = serialize(isset($params['city_dataregion4']) ? $params['city_dataregion4'] : '');
        $dataregion5 = serialize(isset($params['city_dataregion5']) ? $params['city_dataregion5'] : '');
        $city = $dataregion1 . '&' . $dataregion2 . '&' . $dataregion3 . '&' . $dataregion4 . '&' . $dataregion5;
        //地域限制
        $checkadz = array(
            'isacl' => $city_isacl,
            'comparison' => $comparison,
            'province' => $province,
            'city' => $city,
            'adzcycle1' => $adzcycle1,
            'adzcycle2' => $adzcycle2,
            'adznum' => $adznum,
            'adzjs' => $adzjs,
        );
        //全屏广告
        $clickhight = array(
            'isacl_6' => $params['isacl_6'],
            'adzcycle1_6' => $params['adzcycle1_6'],
            'adzcycle2_6' => $params['adzcycle2_6'],
            'adznum_6' => $params['adznum_6'],
        );
        //屏蔽手机型号
        $adzUa = array(
            'adz_uaOn' => empty($params['adz_uaOn']) ? 0 : $params['adz_uaOn'],
            'adz_uaLimit' => empty($params['adz_uaLimit']) ? 0 : $params['adz_uaLimit'],
            'adz_ua' => $params['adz_ua'],
        );
        //广告位屏蔽计划
        $planLimit = $params['plan_limit'];
        //处理手动填写造成的问题
        $params['plan_name'] = !isset($params['plan_name']) ? '' : $params['plan_name'];
        $planName = rtrim(str_replace('，',',',trim($params['plan_name'])),',');
        $planCheck = array(
            'city_limit' => !isset($params['city_limit']) ? 0 : $params['city_limit'],
            'contrast' => !isset($params['contrast']) ? 0 : $params['contrast'],
            'plan_province' => !isset($params['city_provinceplan']) ? array() : $params['city_provinceplan'],
            'plan_city' => !isset($params['city_dataplan']) ? array() : $params['city_dataplan'],
            'plan_hour' => !isset($params['plan_hour']) ? array() : $params['plan_hour'],
            'name' => $planName
        );
        $data = array(
            'zonename' => $params['zonename'],
            'cpd_status' => $params['cpd_status'],
            'false_close' => $params['false_close'],
            'point_close' => $params['point_close'],
            'web_deduction' => empty($params['web_deduction']) ? 0 : $params['web_deduction'],
            'adv_deduction' => empty($params['adv_deduction']) ? 0 : $params['adv_deduction'],
            'class_id' => $params['ad_class'],
            'star' => $params['star'],
            'adstyle_id' => $params['ad_style'],
            'cpd' => $params['cpd_status'] == 0 ? 0 : $params['cpd'],
            'cpd_startday' => $params['cpd_status'] == 0 ? 0 : substr($params['time'], 0, 10),
            'cpd_endday' => $params['cpd_status'] == 0 ? 0 : substr($params['time'], 13, 23),
            'plan_class_allow' => $plan_class_allow,
            'adz_type' => empty($params['adz_type']) ? '' : $params['adz_type'],
            'checkadz' => serialize($checkadz),
            'clickhight' => serialize($clickhight),
            'adz_ua' => serialize($adzUa),
            'system_type' => empty($params['system_type']) ? '0' : $params['system_type'],
            'plan_limit' => $planLimit,
            'plan_check' => serialize($planCheck),
        );
        return $data;
    }

    /**
     * 获取今日访问和昨日访问
     */
    private function _getViews($siteList)
    {
        //实例化缓存
        $cache_model = new Cache();
        $cache_keys_today = 'site_today _views'; //缓存key
        $cache_keys_yesterday = 'site_yesterday _views'; //缓存key
        $catch_today_time = 600; //设置缓存时间,10分钟
        $catch_yesterday_time = 86400 - (time() - strtotime(date("Y-m-d"))); //设置缓存时间,时间为截止今天晚上12点剩余时间
        $todayViews = $cache_model->get($cache_keys_today);
        if (empty($todayViews)) {
            $todayViews = Loader::model('site')->siteViews(date("Y-m-d"));
            $cache_model->set($cache_keys_today, $todayViews, $catch_today_time);//设置缓存
        }
        $yesterdayViews = $cache_model->get($cache_keys_yesterday);
        if (empty($yesterdayViews)) {
            $yesterdayViews = Loader::model('site')->siteViews(date("Y-m-d", strtotime("-1 day")));
            $cache_model->set($cache_keys_yesterday, $yesterdayViews, $catch_yesterday_time);//设置缓存
        }

        foreach ($siteList as $key => $value) {
            //将今日访问组装到数组中
            foreach ($todayViews as $key1 => $value1) {
                if ($value['site_id'] == $value1['site_id']) {
                    $siteList[$key]['todayViews'] = $value1['views'];
                }
            }
            empty($siteList[$key]['todayViews']) ? $siteList[$key]['todayViews'] = 0 : $siteList[$key]['todayViews'];
            //将昨日访问组装到数组中
            foreach ($yesterdayViews as $key1 => $value1) {
                if ($value['site_id'] == $value1['site_id']) {
                    $siteList[$key]['yesterdayViews'] = $value1['views'];
                }
            }
            empty($siteList[$key]['yesterdayViews']) ? $siteList[$key]['yesterdayViews'] = 0 : $siteList[$key]['yesterdayViews'];
        }
        return $siteList;
    }

    /**
     * 更新广告位副表
     */
    private function _updateCopy($id, $data)
    {
        //查询站长余额
//        $selectWebMoney = Loader::model('site')->selectWebMoney($id);
        //查询已经包月时间
        $lastday = Loader::model('site')->selectlastday($id);
        //将页面所填的时间段转化为每一天（例如：18，19,20）
        $day = array();
        for ($time = 0; strtotime($data['cpd_startday']) + $time <= strtotime($data['cpd_endday']); $time += 3600 * 24) {
            $day[] = date("Y-m-d", strtotime($data['cpd_startday']) + $time);
        };
        //判断当前所填的时间段跟已有的时间是否冲突
        $copyDay = array();
        foreach ($lastday as $key => $value) {
            foreach ($day as $k => $v) {
                if ($value['cpd_day'] == $v) {
                    $copyDay[] = $value['cpd_day'];
                }
            }
        }
        //如果所填包天时间已经存在的情况下提示错误类型
        if (!empty($copyDay)) {
            $this->_error('该广告位的包天日期不能重复');
        }
        //若包天日期小于当前日期，则把之前跑量的结算去除
//        $lastMoney = 0;
//        foreach($day as $key=>$value){
//            if($value <= date("Y-m-d")){
//                $thisMoney = Loader::model('site')->getLastMoney($id,$value);
//                $lastMoney = $lastMoney + (empty($thisMoney['0']['sumpay']) ? 0 : $thisMoney['0']['sumpay']);
//            }
//        }
        // //计算站长余额
        // $money['money'] = $selectWebMoney['money'] + (count($day))*($data['cpd']) - $lastMoney;
        // //更新站长余额
        // $updateWebMoney = Loader::model('site')->updateWebMoney($selectWebMoney['uid'],$money);
        // if ($updateWebMoney<0){
        //     $this->error();
        // }
        foreach ($day as $key => $value) {
            $arr[] = array(
                'cpd_day' => $value,
                'adz_id' => $id,
                'zonename' => $data['zonename'],
                'cpd' => $data['cpd'],
            );
        }
        $arr = empty($arr) ? array() : $arr;
        return $arr;
    }

    /**
     *  广告位定向编辑
     */
    public function adzRule()
    {
        $request = Request::instance();
        $id = $request->param();
        //广告位附加定向
        $adzSite = Loader::model('site')->getAdzRule($id);
        $getAdzRule = $this->_getAdzRule($adzSite);
        //查询链接池
        $getAdzRule = $this->_getAdzUrl($getAdzRule);
        //广告位定向弹窗 唤醒
        $adzData['protype'] = Loader::model('site')->getProType();
        //广告位定向弹窗 城市池
        $adzData['adzCity'] = $this->_cityData();
        $adzData['adzHour'] = $getAdzRule['hour_rule'];
        $hour = ["00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23"];
        $this->assign('hour', $hour);
        //广告位附加定向
        $this->assign('adzData', $adzData);
        $this->assign('adzRule', $getAdzRule);
        return $this->fetch('adzrule-edit');
    }

    /**
     * 广告位附加新增定向设置功能
     */
    public function adzsite()
    {
        $request = Request::instance();
        $param = $request->param();
        //整理组合数据
        $data = $this->_getAdzSite($param);
        $res = Loader::model('site')->adzSiteInsert($data);
        unset($data);
        if ($res) {
            $this->_success('', '添加成功！');
        } else {
            $this->_error('添加失败！');
        }
    }

    /**
     * 广告位附加编辑定向设置功能
     */
    public function adzsiteEdit()
    {
        $request = Request::instance();
        $param = $request->param();
        //整理组合数据
        $data = $this->_getAdzSite($param);
        $where['id'] = $param['id'];
        $res = Loader::model('site')->adzSiteUpdate($where, $data);
        unset($data);
        if ($res) {
            $this->_success('', '添加成功！');
        } else {
            $this->_error('添加失败！');
        }
    }

    /**
     *  广告位附加定向设置
     *  搜索点弹池或者跳转池中的连接
     */
    public function seleUrl()
    {
        //广告位附加定向设置
        $request = Request::instance();
        $param = $request->param();
        $res = Loader::model('site')->getUrl($param);
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($res));
    }

    /**
     * 整理组合广告位数据 添加广告位附加定向
     */
    private function _getAdzSite($param)
    {
        $data = array();
        //广告位id
        $data['adz_id'] = $param['adz_id'];
        //规则名称
        $data['rule_name'] = $param['rule_name'];
        // 是否开启唤醒  默认关闭 0关闭   1打开
        $data['wake_on'] = empty($param['wake_on']) ? 0 : $param['wake_on'];
        //唤醒类别选择
        $param['wake_pro'] = empty($param['wake_pro']) ? array() : $param['wake_pro'];
        $data['wake_pro'] = serialize($param['wake_pro']);
        //唤醒产品在广告位下频率 默认100%
        $data['wake_num'] = empty($param['wake_num']) ? 100 : $param['wake_num'];
        //广告位定向跳转点弹  端口设置
        $data['jp_port'] = empty($param['jp_port']) ? 0 : $param['jp_port'];
        //是否开启点弹或者跳转 默认关闭 0关闭 1开启
        $data['jp_on'] = empty($param['jp_on']) ? 0 : $param['jp_on'];
        //选择点弹或者跳转  默认选择点弹  0点弹 1跳转
        $data['jp_type'] = empty($param['jp_type']) ? 0 : $param['jp_type'];
        if($data['jp_type'] == '0'){
            //同一个独立访客点弹或跳转的次数  默认1次
            if(isset($param['jp_ips'])){
                $data['jp_ip'] = empty($param['jp_ips']) ? 1 : $param['jp_ips'];
            }else{
                $data['jp_ip'] = empty($param['jp_ip']) ? 1 : $param['jp_ip'];
            }
        }elseif($data['jp_type'] == '1'){
            //同一个独立访客点弹或跳转的次数  默认1次
            $data['jp_ip'] = empty($param['jp_ip']) ? 1 : $param['jp_ip'];
        }
        //点弹设置  默认延时点弹 0延时点弹  1次数点弹
        $data['point_site'] = empty($param['point_site']) ? 0 : $param['point_site'];
        //延时点弹时间   默认0s 即没有点弹时间立即开启点弹功能
        $data['point_time'] = empty($param['point_time']) ? 0 : $param['point_time'];
        //次数点弹  默认1  即第一次触碰屏幕就有点弹效果
        $data['point_num'] = empty($param['point_num']) ? '' : $param['point_num'];
        //点弹链接
        $param['point_url'] = empty($param['point_url']) ? array() : $param['point_url'];
        $data['point_url'] = serialize($param['point_url']);
        //跳转链接
        $param['jump_url'] = empty($param['jump_url']) ? array() : $param['jump_url'];
        $data['jump_url'] = serialize($param['jump_url']);
        //跳转时间
        $param['jump_time'] = empty($param['jump_time']) ? array() : $param['jump_time'];
        $data['jump_time'] = serialize($param['jump_time']);
        //是否开启特殊js 默认关闭  0关闭 1打开
        $data['js_on'] = empty($param['js_on']) ? 0 : $param['js_on'];
        $param['adz_jsUrl'] = empty($param['adz_jsUrl']) ? '' : $param['adz_jsUrl'];
        $adz_js = array(
            'day_star' => empty($param['day_star']) ? 1 : $param['day_star'],
            'day_end' => empty($param['day_end']) ? 1 : $param['day_end'],
            'adz_jsNum' => empty($param['adz_jsNum']) ? 1 : $param['adz_jsNum'],
            'adz_jsUrl' => htmlspecialchars_decode($param['adz_jsUrl']),
        );
        $data['js_check'] = serialize($adz_js);
        //规则  地域限制
        $param['city_province'] = empty($param['city_province']) ? array() : $param['city_province'];
        $param['city_data'] = empty($param['city_data']) ? array() : $param['city_data'];
        $map = array(
            'city_isacl' => $param['city_isacl'],
            'comparison' => $param['comparison'],
            'city_province' => serialize($param['city_province']),
            'city_data' => serialize($param['city_data']),
        );
        $data['map_rule'] = serialize($map);
        //规则  时间限制
        $param['hour'] = empty($param['hour']) ? array() : $param['hour'];
        $data['hour_rule'] = serialize($param['hour']);
        $data['ctime'] = time();
        return $data;
    }

    /**
     *  处理广告位附加定向编辑数据
     */
    private function _getAdzRule($param)
    {
        $data = array();
        $data['id'] = $param['id'];
        //广告位id
        $data['adz_id'] = $param['adz_id'];
        //规则名称
        $data['rule_name'] = $param['rule_name'];
        // 是否开启唤醒  默认关闭 0关闭   1打开
        $data['wake_on'] = $param['wake_on'];
        //唤醒类别选择
        $data['wake_pro'] = unserialize($param['wake_pro']);
        //唤醒产品在广告位下频率 默认100%
        $data['wake_num'] = $param['wake_num'];
        //广告位定向点弹跳转 端口投放设置
        $data['jp_port'] = empty($param['jp_port']) ? 0 : $param['jp_port'];
        //是否开启点弹或者跳转 默认关闭 0关闭 1开启
        $data['jp_on'] = empty($param['jp_on']) ? 0 : $param['jp_on'];
        //选择点弹或者跳转  默认选择点弹  0点弹 1跳转
        $data['jp_type'] = $param['jp_type'];
        //同一个独立访客点弹或跳转的次数  默认1次
        $data['jp_ip'] = $param['jp_ip'];
        //点弹设置  默认延时点弹 0延时点弹  1次数点弹
        $data['point_site'] = $param['point_site'];
        //延时点弹时间   默认0s 即没有点弹时间立即开启点弹功能
        $data['point_time'] = $param['point_time'];
        //次数点弹  默认1  即第一次触碰屏幕就有点弹效果
        $data['point_num'] = $param['point_num'];
        //点弹链接
        $data['point_url'] = unserialize($param['point_url']);
        //跳转链接
        $data['jump_url'] = unserialize($param['jump_url']);
        //跳转时间
        $data['jump_time'] = unserialize($param['jump_time']);
        //是否开启特殊js 默认关闭  0关闭 1打开
        $data['js_on'] = $param['js_on'];
        $data['js_check'] = unserialize($param['js_check']);
        //规则  地域限制
        $data['map_rule'] = unserialize($param['map_rule']);
        $data['map_rule']['city_province'] = unserialize($data['map_rule']['city_province']);
        $data['map_rule']['city_data'] = unserialize($data['map_rule']['city_data']);
        //规则  时间限制
        $data['hour_rule'] = unserialize($param['hour_rule']);
        return $data;
    }

    /**
     * 查询链接池
     */
    private function _getAdzUrl($param)
    {
        $param['purl'] = json_encode(array());
        $param['jurl'] = json_encode(array());
        if ($param['jp_type'] == 0) {
            $str = implode(',', $param['point_url']);
            $type = 1;
            $pRes = $this->_getUrlRes($str, $type, $param['point_url']);
            $param['purl'] = json_encode($pRes);
        } else {
            $str = implode(',', $param['jump_url']);
            $type = 2;
            $jRes = $this->_getUrlRes($str, $type, $param['jump_url']);
            $param['jurl'] = json_encode($jRes);
        }
        $param['jump_time'] = json_encode($param['jump_time']);
        $param['province'] = implode(',', $param['map_rule']['city_province']);
        $param['city'] = implode(',', $param['map_rule']['city_data']);
        return $param;
    }

    /**
     *  查询链接池 处理数据
     */
    private function _getUrlRes($str, $type, $param)
    {
        $urlRes = array();
        //若填入的链接为空则不查询
        if ($str) {
            $data = Loader::model('site')->getAuleUrl($str, $type);
        } else {
            $data = array();
        }
        //查询到的数据为排重后的链接，需要将查询到的链接拼接至填入的id中,因为一个落地页地址会重复填写多次需求二次沟通确认
        if ($data) {
            $res = array();
            foreach ($data as $key => $value) {
                $res[$value['id']] = $value;
            }
            unset($data);
            $urlRes = array();
            foreach ($param as $key => $value) {
                if (isset($res[$value])) {
                    $urlRes[] = $res[$value];
                }
            }
        }
        return $urlRes;
    }

    /**
     * 复制广告位定向
     */
    public function adzCopy()
    {
        $request = Request::instance();
        Hook::listen('auth', $this->_uid); //权限
        $param = $request->param();
        $data = Loader::model('site')->getCopy($param);
        foreach ($data as $key => $value) {
            $value['adz_id'] = $param['adz_id'];
            Loader::model('site')->adzSiteInsert($value);
        }
        if (empty($data)) {
            $this->_error('复制失败');
        } else {
            //写操作日志
            $this->logWrite('0065', $param['adz_id']);
            $this->_success('', '复制成功');
        }
    }

    /**
     * 通过站长id获取出他所有的手机型号
     */
    public function userPhoneModel()
    {
        $request = Request::instance();
        $param = $request->param();
        $data = Loader::model('site')->phoneModel($param);
        $res = [];
        foreach ($data as $k=>$v){
           $res[] = $v['model'];

        }
      $model = array_unique($res);
        $phone = '';
        foreach ($model as $k=>$v){
            $phone .= ','.$v;
        }
        $phone = substr($phone,1);
        if($phone){
            return $phone;
        }else{
            return '暂无';
        }
    }

    /**
     *  广告位规则列表
     */
    public function ruleList()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $param = $request->param('');
        $param['status'] = !isset($param['status']) ? 2 : $param['status'];
        $param['index'] = !isset($param['index']) ? '' : $param['index'];
        $param['search'] = !isset($param['search']) ? '' : $param['search'];
        //查询数据
        $res = Loader::model('site')->ruleList($param);
        //处理数据
        $data = $this->_getRuleData($res);
        //分页
        $total = count($data);
        $Page = new \org\PageUtil($total,$param);
        $show = $Page->show($request->action(),$param);
        $data = array_slice($data,$Page->firstRow,$Page->listRows);
        //渲染页面
        $this->assign('adzRule',$data);
        $this->assign('page',$show);
        $this->assign('param',$param);

        return $this->fetch('adzrule-list');

    }

    /***
     *  广告位规则激活锁定
     */
    public function ruleStatus()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->param('');
        $res = Loader::model('site')->ruleStatus($params);
        if ($res > 0) {
            $params['status'] = $params['status'] == 0 ? '锁定' : '激活';
            $this->logWrite('0066', $params['adz_id'], $params['rule_name'],$params['status']);
            $this->_success();
        } else {
            $this->_error('修改失败');
        }
    }

    /***
     *  删除广告位规则
     */
    public function ruleDel()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->param('');
        $res = Loader::model('site')->ruleDel($params['id']);
        if ($res > 0) {
            $this->logWrite('0067', $params['adz_id'], $params['rule_name']);
            $this->_success();
        } else {
            $this->_error('删除失败');
        }
    }

    /**
     *  广告位规则列表数据处理
     */
    private function _getRuleData($data)
    {
        foreach ($data as $key => $value)
        {
            $map = unserialize($value['map_rule']);
            if ($map['city_isacl'] == 0){
                $data[$key]['comparison'] = 2;
                $data[$key]['city_data'] = '/';
            }else{
                $data[$key]['comparison'] = $map['comparison'];
                $data[$key]['city_data'] = implode(',',unserialize($map['city_data']));
            }
            $data[$key]['hour'] = implode(',',unserialize($value['hour_rule']));
        }
        return $data;
    }
}
