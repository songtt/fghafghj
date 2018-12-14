<?php
/* 数据监控
 * @date   2017-8-11
 */
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Hook;
use think\Cache;

class Monitor extends Admin
{
    /**
     * pv监控列表
     */
    public function pvMonitor()
    {
        //查询出昨天所有浏览数大于1000的站长，然后查出过去7天所有的站长的浏览数，循环遍历，匹配到昨天id的判断是否显示，然后unset掉，
        //剩余的平均数大于1000的另行处理
        //然后把有问题的站查询一下前7天是否都有数据，如果都有则是暴增暴涨，如果开始没数据，则为新上的站
        $request = Request::instance();
        //权限
        Hook::listen('auth',$this->_uid);
        $pageParam = $request->param('');
        $pageParam['yester'] = empty($pageParam['time']) ? date("Y-m-d",strtotime("-1 day")) : substr($pageParam['time'], 0,10);
        $pageParam['end'] = date("Y-m-d",strtotime("".$pageParam['yester']." -1 day"));
        $pageParam['front'] = date("Y-m-d",strtotime("".$pageParam['yester']." -7 day"));
        $pageParam['time'] = empty($pageParam['time']) ? date("Y-m-d",strtotime("-1 day")) : substr($pageParam['time'], 0,10);

        //查询昨天站长的浏览数
        $last_views = Loader::model('Monitor')->getlastviews($pageParam);
        //查询昨天之前7天站长的浏览数
        $seven_views = Loader::model('Monitor')->getsevenviews($pageParam);
        //数据组装
        $data = $this->_pvdate($pageParam,$last_views,$seven_views);
        $Page = new \org\PageUtil(count($data),$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        $data= array_slice($data,$Page->firstRow,$Page->listRows);

        $this->assign('param',$pageParam);
        $this->assign('one',$data);
        $this->assign('page',$show);
        return $this->fetch('monitor-pvlist');
    }

    /**
     * 点击率监控列表
     */
    public function clickMonitor()
    {
        $request = Request::instance();
        //权限
        Hook::listen('auth',$this->_uid);
        $pageParam = $request->param('');
        $pageParam['yester'] = empty($pageParam['time']) ? date("Y-m-d",strtotime("-1 day")) : substr($pageParam['time'], 0,10);
        $pageParam['end'] = date("Y-m-d",strtotime("".$pageParam['yester']." -1 day"));
        $pageParam['front'] = date("Y-m-d",strtotime("".$pageParam['yester']." -7 day"));
        $pageParam['time'] = empty($pageParam['time']) ? date("Y-m-d",strtotime("-1 day")) : substr($pageParam['time'], 0,10);
        //查询昨天站长的浏览数
        $last_click = Loader::model('Monitor')->getlastclick($pageParam);
        //查询昨天之前7天站长的浏览数
        $seven_click = Loader::model('Monitor')->getsevenclick($pageParam);
        //数据组装
        $data = $this->_clickdate($pageParam,$last_click,$seven_click);
        $Page = new \org\PageUtil(count($data),$pageParam);
        $show = $Page->show(Request::instance()->action(),$pageParam);
        $data= array_slice($data,$Page->firstRow,$Page->listRows);

        $this->assign('param',$pageParam);
        $this->assign('one',$data);
        $this->assign('page',$show);
        return $this->fetch('monitor-clicklist');
    }

    /**
     * 拼接数据
     */
    private function _pvdate($pageParam,$last_views,$seven_views)
    {
        $notice = $pageParam['yester'].'日';
        $data = array();
        //遍历循环，找出昨日和前7日数据都超过2000，且暴增暴减的
        foreach($last_views as $key=>$value){
            foreach($seven_views as $k=>$v){
                if($value['uid'] == $v['uid']){
                    if(($value['views'] - $v['views']) >= $v['views']/2 || ($value['views'] - $v['views']) <= -$v['views']/2){
                        $data[]  = array(
                            'uid' => $value['uid'],
                            'username' => $value['username'],
                            'notice' => $notice.'访问量为'.$value['views'].';前7日平均访问量为'.$v['views'].';增减量为  '.ceil(($value['views'] - $v['views'])/$v['views'] * 100).'%',
                            'status' => ($value['views'] - $v['views'])> 0 ? 1 : 2 //1为暴增，2为暴减
                        );
                    }
                    //删除掉昨日和前7日数据都超过2000，无暴增暴减的数据
                    unset($last_views[$key]);
                    unset($seven_views[$k]);
                }
            }
        }
        //处理前7日数据中平均pv小于2000的（若sql中直接查询平均pv大于2000的，如果前7天有无数据的情况，会造成数据不全，故sql中查的是总和大于2000的，
        //因此需要把小于2000的，且无暴增暴减的，重新洗一下数据，去掉无用的）
        foreach($seven_views as $key=>$value){
            //如果前7日平均浏览数小于2000，则查询最前面一天是否有数据，如果没有，则是新上的站，且数据有异常，如果有，直接舍弃
            if($value['views'] < 2000){
                $res = Loader::model('Monitor')->getfrontviews($pageParam,$value['uid']);
                if(!empty($res)){
                    unset($seven_views[$key]);
                }else{
                    $data[]  = array(
                        'uid' => $value['uid'],
                        'username' => $value['username'],
                        'notice' => $notice.'访问量不足2000，前7日平均访问量'.$value['views'].'，且前7日有无数据情况',
                        'status' => 3
                    );
                }
            }
        }
        //将前7天数据大于2000，昨日访问量不足2000的放入数组中
        foreach($seven_views as $key=>$value){
            $data[]  = array(
                'uid' => $value['uid'],
                'username' => $value['username'],
                'notice' =>  $notice.'访问量不足2000，前7天平均访问量为'.$value['views'],
                'status' => 3
            );
        }
        //将昨日数据大于2000，前7天访问量不足2000的放入数组中
        foreach($last_views as $key=>$value){
            $data[]  = array(
                'uid' => $value['uid'],
                'username' => $value['username'],
                'notice' =>  $notice.'访问量'.$value['views'].'，前7日平均访问量不足2000',
                'status' => 3
            );
        }
        return $data;
    }

    /**
     * 拼接数据
     */
    private function _clickdate($pageParam,$last_click,$seven_click)
    {
        $notice = $pageParam['yester'].'日';
        $data = array();
        //遍历循环，找出昨日和前7日数据都超过2000，且暴增暴减的
        foreach($last_click as $key=>$value){
            foreach($seven_click as $k=>$v){
                if($value['uid'] == $v['uid']){
                    if($v['click_percent'] == 0){//点击率为0的情况
                        $data[]  = array(
                            'uid' => $value['uid'],
                            'username' => $value['username'],
                            'notice' => $notice.'浏览数为'.$value['views'].'，点击率为'.($value['click_percent']*100).'%，前7天平均浏览数为'.$v['views'].'，点击率为'.($v['click_percent']*100).'%',
                            'status' => ($value['click_percent'] - $v['click_percent'])> 0 ? 1 : 2//1为暴增，2为暴减
                        );
                    }elseif(($value['click_percent'] - $v['click_percent'])/$v['click_percent'] >= 0.5 ||
                        ($value['click_percent'] - $v['click_percent'])/$v['click_percent'] <= -0.5 ){
                        $data[]  = array(
                            'uid' => $value['uid'],
                            'username' => $value['username'],
                            'notice' => $notice.'浏览数为'.$value['views'].'，点击率为'.($value['click_percent']*100).'%，前7天平均浏览数为'.$v['views'].'，点击率为'.($v['click_percent']*100).'%',
                            'status' => ($value['click_percent'] - $v['click_percent'])> 0 ? 1 : 2//1为暴增，2为暴减
                        );
                    }
                    //删除掉昨日和前7日数据都超过2000，无暴增暴减的数据
                    unset($last_click[$key]);
                    unset($seven_click[$k]);
                }
            }
        }
        //处理前7日数据中平均pv小于2000的（若sql中直接查询平均pv大于2000的，如果前7天有无数据的情况，会造成数据不全，故sql中查的是总和大于2000的，
        //因此需要把小于2000的，且无暴增暴减的，重新洗一下数据，去掉无用的）
        foreach($seven_click as $key=>$value){
            //如果前7日平均浏览数小于2000，则查询最前面一天是否有数据，如果没有，则是新上的站，且数据有异常，如果有，直接舍弃
            if($value['views'] < 2000){
                $res = Loader::model('Monitor')->getfrontviews($pageParam,$value['uid']);
                if(!empty($res)){
                    unset($seven_click[$key]);
                }else{
                    $data[]  = array(
                        'uid' => $value['uid'],
                        'username' => $value['username'],
                        'notice' => $notice.'访问量不足2000，前7日平均访问量'.$value['views'].'，点击率为且前7日有无数据情况',
                        'status' => 3
                    );
                }
            }
        }
        //将前7天数据大于2000，昨日访问量不足2000的放入数组中
        foreach($seven_click as $key=>$value){
            $data[]  = array(
                'uid' => $value['uid'],
                'username' => $value['username'],
                'notice' => $notice.'访问量不足2000，前7天平均访问量为'.$value['views'].'点击率为'.($value['click_percent']*100).'%',
                'status' => 3
            );
        }
        //将昨日数据大于2000，前7天访问量不足2000的放入数组中
        foreach($last_click as $key=>$value){
            $data[]  = array(
                'uid' => $value['uid'],
                'username' => $value['username'],
                'notice' => $notice.'访问量'.$value['views'].'，点击率为'.($value['click_percent']*100).'%,前7日平均访问量不足2000',
                'status' => 3
            );
        }
        return $data;
    }

	/**
     * pv监控列表数据导出
     */
    public function monitorExcel()
    {

        $request = Request::instance();
        $pageParam = $request->param('');

        $pageParam['yester'] = empty($pageParam['time']) ? date("Y-m-d",strtotime("-1 day")) : substr($pageParam['time'], 0,10);
        $pageParam['end'] = date("Y-m-d",strtotime("".$pageParam['yester']." -1 day"));
        $pageParam['front'] = date("Y-m-d",strtotime("".$pageParam['yester']." -7 day"));
        $pageParam['time'] = empty($pageParam['time']) ? date("Y-m-d",strtotime("-1 day")) : substr($pageParam['time'], 0,10);

        //查询昨天站长的浏览数
        $last_views = Loader::model('Monitor')->getlastviews($pageParam);
        //查询昨天之前7天站长的浏览数
        $seven_views = Loader::model('Monitor')->getsevenviews($pageParam);
        //数据组装
        $data = $this->_pvdate($pageParam,$last_views,$seven_views);

        require_once  "../extend/org/vendor/autoload.php";
        //修改内存
        ini_set('memory_limit','500M');
        //修改时间
        ini_set("max_execution_time", "0");

        //统计数据个数
        $num_count = count($data)+1;
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
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);

        // 添加表头数据
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '站长id')
            ->setCellValue('B1', '站长名称')
            ->setCellValue('C1', '状态')
            ->setCellValue('D1', '备注');
        //设置自动筛选
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:D1'.$num_count);

        // 设置表中的内容
        $i = 2;  //  从表中第二行开始
        foreach ($data as $key => $value) {

            if($value['status'] == 1){
                $value['symbol'] = '↑';
            }elseif($value['status'] == 2){
                $value['symbol'] = '↓';
            }else{
                $value['symbol'] = '—';
            }

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $value['uid'])
                ->setCellValue('B'.$i, $value['username'])
                ->setCellValue('C'.$i, $value['symbol'])
                ->setCellValue('D'.$i, $value['notice']);
            $i++;
        }
        // 重命名工作表
        $objPHPExcel->getActiveSheet()->setTitle('sheet1');
        // 将活动表索引设置为第一个表，所以将此作为第一张表打开
        $objPHPExcel->setActiveSheetIndex(0);

        //设置表的名字
        $filename='pv监控数据导出'.$pageParam['time']; 

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
