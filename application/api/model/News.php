<?php
namespace app\api\model;
use PDO;
use think\Model;
use org\IpSearch as IpSearch;
use org\SredisUtil as SredisUtil;
use app\user\common\Encrypt as Encrypt;

date_default_timezone_set('PRC');
$start_timea = microtime(true);

class News extends \think\Model
{
    //返回数据库连接对象
    private function getPdo($sredis)
    {
        $db_link = $sredis->db_link;
        $db_root = $sredis->db_root;
        $db_password = $sredis->db_password;
        try{
            $pdo = new PDO($db_link,$db_root,$db_password);
        }catch(PDOException $e){
            //echo '数据库连接失败'.$e->getMessage();
        }
        return $pdo;
    }


    //前期预处理
    public function prepareData($adz_id)
    {
        import("extend.org");
        $cost_type = array('cpm','cpv');
        $IpSearch = new IpSearch('qqzeng-ip-utf8.dat');
        $sredis = new SredisUtil();

        $url = $sredis->curl_url;
        $cilck_url = $sredis->cilck_url;
        $sign = $sredis->sign;
        $pdo = $this->getPdo($sredis);
        
        //输出log
        $log_test_file = 'slog.txt';
        $log_test_str = ''.date('Y-m-d H:i:s').'';

        $id = addslashes($adz_id);
        $id = htmlspecialchars($id);

        $user_IP = $this->getIp();
        $ipInfos = $this->getIpLookup($user_IP,$IpSearch);

        if($id == 49){
            if($ipInfos[3] == '杭州'){
                $id = 6312;
            }
        }

        // 用户所在省份和城市
        $user_city = $ipInfos[2].'-'.$ipInfos[3];
        $user_city = ''.'-'.'';


        //全局服务器配置地址
        $globalSql = $pdo->prepare("select img_server,js_server,jump_server,adv_server,domain_limit,mycom from lz_setting");
        $globalSql->execute();
        $globalRes = $globalSql->fetchAll();
        if (empty($globalRes[0])) 
        {
            $log_test_str.=' ERROR10000'."\n";
            $this->writeFile($log_test_file,$log_test_str);
            $res = ['code'=>'3001','msg'=>'服务器地址配置错误'];
            return json_encode($res);
            exit;
        }

        //平台域名
        $platformUrl = $globalRes[0]['jump_server'];
        //获取当前网站域名
        //$user_site_url = !isset($_SERVER["HTTP_REFERER"]) ? '' : $_SERVER["HTTP_REFERER"];
        //$user_site_url = !isset($_SERVER["HTTP_REFERER"]) ? '' : 'http://www.baidu.com';
        //$user_site_url = explode('/',$user_site_url);
        //$adsthisUrl = !empty($user_site_url) ? '' : $user_site_url[2];
        
        if(!isset($_SERVER["HTTP_REFERER"]) && empty($_SERVER["HTTP_REFERER"])){
            $user_site_url = '';
            $adsthisUrl = $user_site_url;
        }

        //图片服务器地址
        $img_server = $globalRes[0]['img_server'];
        $img_server = empty($img_server) ? $adsthisUrl : $img_server;

        $adzLimitSql = $pdo->prepare("select adz_id,uid,htmlcontrol,minutes,adtpl_id,width,height,class_id,plan_class_allow from lz_adzone where adz_id=? AND status=1");
        $adzLimitSql->execute(array($id));
        $adzList = $adzLimitSql->fetchAll();

        if(empty($adzList[0])) 
        {
            $log_test_str.=' 广告位id'.$id.' ERROR10001'."\n";
            $this->writeFile($log_test_file,$log_test_str);
            $res = ['code'=>'3002','msg'=>'广告位没有激活'];
            return json_encode($res);
            exit;
        }

        //查询出该广告位所有可展示的广告
        $ad_id_arr = $pdo->prepare("SELECT ad_id FROM lz_ads WHERE tpl_id=? AND width=? AND height=? AND status=?");
        $ad_id_arr->execute(array($adzList[0]['adtpl_id'],$adzList[0]['width'],$adzList[0]['height'],1));
        $ad_id_arr = $ad_id_arr->fetchAll();
        //将查询出来的数据转换为字符串
        $ad_id = '';
        foreach($ad_id_arr as $key=>$value){
            $ad_id = $ad_id.','.$value['ad_id'];
        }
        $ad_id = substr($ad_id,1);
        $adzList[0]['show_adid'] = $ad_id;
        if (empty($ad_id)) 
        {
            $res = ['code'=>'3003','msg'=>'无可展示的广告'];
            return json_encode($res);
            exit;
        }

        //站长id
        $uid = $adzList[0]['uid'];
        // 查看当前域名属于哪个站长的
        $userSql = $pdo->prepare("select uid,domain_limit from lz_users_log WHERE uid=? ");
        $userSql->execute(array($uid));
        $userRes = $userSql->fetchAll();
        //该站长是否开启域名限制 0默认 1开启 2关闭(默认就根据全局限制)
        if((count($userRes)>0 && !empty($userRes[0]['domain_limit'])) && ($userRes[0]['domain_limit'] == 0)) 
        {
            //全局开启域名限制,则只有审核的网站可以显示广告 0关闭 1开启
            if($globalRes[0]['domain_limit'] == 0) 
            {
                $siteRes = $this->openDomainLimit($uid,$adsthisUrl,$pdo);
                //当前网站是否存在，不存在为空
                if(empty($siteRes)) 
                {
                    $siteRes = array(
                        "site_id" => 0,
                        "uid" => 0,
                        "siteurl" => 0,
                        "web_deduction" => 0,
                        "adv_deduction" => 0,
                        'site_cnzz_id' => '',
                    );
                }
                $domain_type = 2;
            }
            else
            {
                //站长下面所有的站都显示  开启限制
                $siteRes = $this->openDomainLimit($uid,$adsthisUrl,$pdo);
                if (empty($siteRes)) 
                {
                    $log_test_str.=' 站长id'.$uid.' 广告投放的网站:'.$adsthisUrl.' ERROR10002'."\n";
                    $this->writeFile($log_test_file,$log_test_str);
                    $res = ['code'=>'3004','msg'=>'该网站没有添加,域名被限制'];
                    return json_encode($res);
                    exit;
                }
                $domain_type = 1;
            }
        }
        elseif((count($userRes)>0 && !empty($userRes[0]['domain_limit'])) && $userRes[0]['domain_limit'] == 1) 
        {
            //站长下面所有的站都显示  开启限制
            $siteRes = $this->openDomainLimit($uid,$adsthisUrl,$pdo);
            if (empty($siteRes)) 
            {
                $log_test_str.=' 站长id'.$uid.' 广告投放的网站:'.$adsthisUrl.' ERROR10003'."\n";
                $this->writeFile($log_test_file,$log_test_str);
                $res = ['code'=>'3004','msg'=>'该网站没有添加,域名被限制'];
                return json_encode($res);
                exit;
            }
            $domain_type = 1;
        }
        else
        {
            $siteRes = $this->openDomainLimit($uid,$adsthisUrl,$pdo);
            //当前网站是否存在，不存在为空
            if(empty($siteRes)) 
            {
                $siteRes= array(
                    "site_id" => 0,
                    "uid" => 0,
                    "siteurl" => 0,
                    "web_deduction" => 0,
                    "adv_deduction" => 0,
                    'site_cnzz_id' => '',
                );
            }
            $domain_type = 2;
        }

        $html = unserialize($adzList[0]['htmlcontrol']);
        //广告位选中的广告
        $array_adid = explode(',',$adzList[0]['show_adid']);
        $array_adid = array_filter($array_adid);
        //提出key值,在转字符串ad_id 广告id
        $ad_id = $adzList[0]['show_adid'];
        $ad_id = rtrim($ad_id,',');

        if(!empty($adzList[0]['htmlcontrol'])) 
        {
            @$style_htmlcontrol = unserialize($adzList[0]['htmlcontrol'])['position'];
        }else{
            $style_htmlcontrol = '';
        }

        // 样式的展示选择
        if(!empty($adzList[0]['adtpl_id'])) 
        {
            if ($adzList[0]['adtpl_id'] == 1) {
                $showType = isset($style_htmlcontrol[0]) ? $style_htmlcontrol[0] : 0;
                $showType = $showType . ':0;';
            }elseif($adzList[0]['adtpl_id'] == 17 && $style_htmlcontrol[0] == 'bottom') {
                $showType = isset($style_htmlcontrol[0]) ? $style_htmlcontrol[0] : 0;
                $styCss = 'top';
            }elseif($adzList[0]['adtpl_id'] == 17 && $style_htmlcontrol[0] == 'top') {
                $showType = isset($style_htmlcontrol[0]) ? $style_htmlcontrol[0] : 0;
                $styCss = 'bottom';
            }else{
                $showType = isset($style_htmlcontrol[0]) ? $style_htmlcontrol[0] : 0;
            }
        }else{
            $showType = '';
        }

        $styCss = isset($styCss) ? $styCss : '';
        $adidArr = array();
        if(!empty($adzList[0]['show_adid'])){
            $adidArr = explode(',',$adzList[0]['show_adid']);
        }else{
            $adidArr = '';
        }
        $limit_pid = '0';
        $limit_new_pid = '';

        //广告位没有选择广告
        if(empty($adidArr)){
            $log_test_str.=' 广告位id'.$id.' ERROR10004'."\n";
            $this->writeFile($log_test_file,$log_test_str);
            $res = ['code'=>'3005','msg'=>'该广告位下没有可投放的广告'];
            return json_encode($res);
            exit;
        }

        //得到激活ad的集合
        $ad_sql = $pdo->prepare('SELECT a.ad_id,a.pid as adpid,a.priority AS adpriority,
                    b.pid,b.restrictions,b.priority,b.resuid,
                    b.checkplan,b.sitelimit,b.limitsiteid,b.class_id 
                    FROM lz_ads AS a LEFT JOIN lz_plan AS b ON a.pid = b.pid
                    LEFT JOIN lz_users AS c ON a.uid = c.uid WHERE a.ad_id
                    IN ('.$ad_id.') AND b.status = 1 AND a.status =1 AND c.money > c.adv_money'
                  );
        $ad_sql->execute(array());
        $ad_sql_list = $ad_sql->fetchAll();

        if(!empty($adzList[0]['uid'])){
            //广告位属于哪个站长id的
            $userid = $adzList[0]['uid'];   
        }else{
            $log_test_str.=' ERROR10005'."\n";
            $this->writeFile($log_test_file,$log_test_str);
            $res = ['code'=>'3006','msg'=>'该广告位没有所属站长'];
            return json_encode($res);
            exit;
        }

        $sites = $pdo->prepare("select site_id,uid,siteurl,class_id from lz_site WHERE siteurl=?");
        $sites->execute(array($adsthisUrl));
        $site_res = $sites->fetchAll();

        //广告位分类id
        $adz_class_id = $adzList[0]['class_id'];
        //广告位里面的计划分类
        $adz_plan_class_allow = $adzList[0]['plan_class_allow'];

        $i = 0;
        foreach($ad_sql_list as $key => $val) 
        {
            if(!empty($ad_sql_list[$i]['pid']))
            {
                $checkplanid = $ad_sql_list[$i]['pid'];
                // 获取当前广告的计划设置的投放限制和定向设置
                $checkplan = unserialize($ad_sql_list[$i]['checkplan']);
                // 查看限制站长id是否开启 状态
                $lim_uid_status = (int)$ad_sql_list[$i]['restrictions'];
                $checkplan['city']['isacl'] = (int)$checkplan['city']['isacl'];
                $checkplan['expire_date']['isdate'] = (int)$checkplan['expire_date']['isdate'];
                $checkplan['week']['isacl'] = (int)$checkplan['week']['isacl'];
                $checkplan['siteclass']['isacl'] = (int)$checkplan['siteclass']['isacl'];
                $checkplan['mobile']['isacl'] = (int)$checkplan['mobile']['isacl'];
                //广告位分类
                $checkplan['adzclass']['isacl'] = (int)$checkplan['adzclass']['isacl'];
                $checkplan['run_model']['isacl'] = (int)$checkplan['run_model']['isacl'];
                // 查看限制站长id 数组
                $lim_uid_array = explode(',', $ad_sql_list[$i]['resuid']);
                //今天 星级几 和 现在几时
                $week_day = (date('w') == 0) ? 7 : date('w');
                $day_hours = date('H');
                //限制网站  0 不限制 1 允许 2 屏蔽  和 网站id 数组
                $checkplan['sitelimit'] = (int)$ad_sql_list[$i]['sitelimit'];
                $checkplan['limitsiteid'] = explode(',', $ad_sql_list[$i]['limitsiteid']);
                //计划分类
                $checkplan['class_id'] = $ad_sql_list[$i]['class_id'];
            }
            else
            {
                $checkplan['expire_date']['isdate'] = '';
                $lim_uid_status = '';
                $checkplan['city']['isacl'] = '';
                $checkplan['week']['isacl'] = '';
                $checkplan['sitelimit'] = '';
                $checkplan['siteclass']['isacl'] = '';
                $checkplan['adzclass']['isacl'] = '';
            }

            //获取当前广告的计划是否到达结束日期 0没有结束时间 1有结束时间 $lim_uid_status 限制站长id 0 不限制 1允许 2 屏蔽
            if($checkplan['expire_date']['isdate'] == 0 && $lim_uid_status == 0 && $checkplan['city']['isacl'] == 0 && $checkplan['week']['isacl'] == 0 && $checkplan['sitelimit'] == 0 && $checkplan['siteclass']['isacl'] == 0 && $checkplan['mobile']['isacl'] == 0 && $checkplan['run_model']['isacl'] == 0 && $checkplan['adzclass']['isacl'] == 0 && $checkplan['class_id'] == 0) {

            }
            else
            {
                $time = strtotime(date('Y-m-d', time()));
                //结束时间转时间戳
                $expireTime = strtotime($checkplan['expire_date']['year'] . '-' . $checkplan['expire_date']['month'] . '-' . $checkplan['expire_date']['day']);
                //时间状态为 0时，则没有结束时间限制
                if($checkplan['expire_date']['isdate'] == 0) 
                {
                    // 站长限制 2级
                    $this->lim_webid_methods($lim_uid_status,$checkplan,$user_city,$week_day,$day_hours,$ad_sql_list,$i,$userid,$lim_uid_array,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow);

                }
                elseif(($expireTime - $time) >= 0 && $checkplan['expire_date']['isdate'] == 1) 
                {
                    // 站长限制 2级
                    $this->lim_webid_methods($lim_uid_status,$checkplan,$user_city,$week_day,$day_hours,$ad_sql_list,$i,$userid,$lim_uid_array,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow);
                } 
                else 
                {
                    $xianzhi_log_test='投放限制-结束日期不满足';
                    //不满足条件的存cookies 存pid
                    $this->cookie_limit_pid($ad_sql_list,$i);
                }
            }
            $i++;
        }

        // 处理不满足条件的计划id
        $array_pid = explode(',', $limit_pid);
        // 去重
        $array_pid = array_unique($array_pid);
        // 数组转字符串存cookies
        $limit_pid = isset($limit_pid) ? $array_pid : 0;
        // 用户IP
        $uIP = $this->getIp();
        //当前日期
        $dayTime = date('Y-m-d', time()); 

        $_COOKIE['ran_i'] = isset($_COOKIE['ran_i']) ? $_COOKIE['ran_i'] : 'null';
        if(empty($ad_sql_list[$_COOKIE['ran_i']]['pid'])) 
        {
            $i = 0;
            $_COOKIE['ran_i'] = $i;
            setcookie('ran_i', $i, time() + 86400);
        }
        $i = $_COOKIE['ran_i'];
        $pidarr_sel = array();
        foreach($ad_sql_list as $key => $val) 
        {
            if ($val['priority'] <= 0) {
                $val['adpriority'] = 1;
            }
            $pidarr_sel['plan'][$val['pid']] = $val['priority'];
            if ($val['adpriority'] <= 0) {
                $val['adpriority'] = 1;
            }
            $pidarr_sel['ads'][$val['pid']][$val['ad_id']] = $val['adpriority'];
        }

        $array_pid = array_flip($limit_pid);
        //数组key对比（不满足条件的pid 和 全部pid 对比）
        $contrast_arr = array_diff_key($pidarr_sel['plan'],$array_pid);
        //对比为空说明广告对应的计划都不满足条件
        if(empty($contrast_arr)) 
        {
            $log_test_str.= $xianzhi_log_test;
            $log_test_str.= ' '.implode(',', $limit_pid);
            $log_test_str.= ' id-'.$id;

            $log_test_str.=' ERROR10007'."\n";
            $this->writeFile($log_test_file,$log_test_str);
            $res = ['code'=>'3007','msg'=>'所有计划限制都不满足,无可投放的广告'];
            return json_encode($res);
            exit;
        }
        else
        {
            // 在赋给原值
            $pidarr_sel['plan'] = $contrast_arr;
        }
        $pid_adid_sel_arr = array();
        //得到随机计划
        $final_pid = $this->cookie_checker_plan($pidarr_sel['plan'],$sredis,$uIP);
        if(($id == 6312)){
            $final_pid = 5159;
        }
        if(empty($final_pid)){
            $log_test_str.=' ERROR10008'."\n";
            $this->writeFile($log_test_file,$log_test_str);
            $res = ['code'=>'3007','msg'=>'所有计划限制都不满足,无可投放的广告'];
            return json_encode($res);
            exit;
        }

        $adid_arr = $pidarr_sel['ads'][$final_pid];
        foreach($adid_arr as $key => $value) 
        {
            if (empty($value)) {
                $value = 1;
            }
            $adid_arr[$key] = $value;
        }

        $today = strtotime(date("Y-m-d"), time());
        $end = $today + 60 * 60 * 24;
        //独立访客存储时间
        $cookTime = $end - time();
        //独立访客
        $old_cookies = isset($_COOKIE['baseCookies']) ? $_COOKIE['baseCookies'] : '';

        if(empty($old_cookies)) 
        {
            $baseCookies = base64_encode($this->getMillisecond());
            $_COOKIE['baseCookies'] = $baseCookies;
            $old_cookies = $baseCookies;
            setcookie('baseCookies', $baseCookies, time() + $cookTime);
        }else{
            $old_cookies = $_COOKIE['baseCookies'];
        }

        $final_ad_id = $this->cookie_checkerG($pdo,$adid_arr,$final_pid,$sredis,$uIP);

        //随机得到的广告id
        $rand = $final_ad_id;
 
        //$baseUrl = "$platformUrl/$cilck_url" . $id .$sign. 'blogid=' . $rand . '&siteurl=' . $adsthisUrl.'&siteid='.$siteRes['site_id'].'&uid='.$adzList[0]['uid'].'&pid='.$final_pid.'&userip='.$user_IP;

        //随机得到的计划id
        $pid = $final_pid;
        //展示的广告信息
        $list = $this->advInformation($pdo,$id,$rand,$adzList,$pid);
        //统计点击量  click_url
        /*$baseUrl = "$platformUrl/$cilck_url" . $id .$sign.'blogid='. $rand . '&siteurl=' . $adsthisUrl.'&siteid='.$siteRes['site_id'].'&uid='.$list['uid'].
'&pid='.$final_pid.'&userip='.$uIP.'&tpl_id='.$list['tpl_id'].'&pid='.$list['pid'].'&plantype='.$list['plantype'].'&planuid='.$list['planuid'];*/

        //手机型号
        $modle = $this->model();
        //手机系统版本
        $system_version = $this->getOS();

        $statsParams = array(
            'adz_id' => $id,
            'ad_id'  => $rand,
            'pid'    => $final_pid,
            'uid'    => $list['uid'],
            'tc_id'  => $list['tc_id'],
            'tpl_id' => $list['tpl_id'],
            'plan_type' => $list['plan_type'],
            'planuid'   => $list['planuid'],
            'site_id'   => $siteRes['site_id'],
            'ip_infos_useradd' => $ipInfos[1] . '/' . $ipInfos[2],
            'user_ip' => $uIP,
            'base_cookies' => $old_cookies,
            //'browser'=>$arrayBrowse[2],
            //'ver'=>$arrayBrowse[3],
            //'kernel'=>$arrayBrowse[0],
            'modle_name'=>$modle,
            'system_version'=>$system_version,
        );
        
        //获取服务器地址
        $serverUrl = $this->service($sredis,$globalRes);
        $urlImg = ''.$serverUrl."/ad/chapv.php?id=".$statsParams['adz_id'].'&ad_id='.$statsParams['ad_id'].
        '&pid='.$statsParams['pid'].'&uid='.$statsParams['uid'].'&tc_id='.$statsParams['tc_id'].'&tpl_id='.$statsParams['tpl_id'].
        '&plan_type='.$statsParams['plan_type'].'&planuid='.$statsParams['planuid'].'&site_id='.$statsParams['site_id'].'&ip_infos_useradd='.$statsParams['ip_infos_useradd'].'&user_ip='.$statsParams['user_ip'].
        '&base_cookies='.$statsParams['base_cookies'].'&modle_name='.$statsParams['modle_name'].'&system_version='.
        $statsParams['system_version'];

        //对返回的urlImg字符串加密
        $encrypt = new Encrypt;
        $urlImg = $encrypt->fb_ucenter_encrypt($urlImg);
        $urlList = [
                    'code'=>'200',
                    'msg'=>'success',
                    'data'=>['imageUrl'=>$list['imageurl'],'url'=>$list['url'],
                    'statsParams'=>$urlImg],    
                   ];
        return json_encode($urlList);
    }

    //获取毫秒
    private function getMillisecond()
    {
        list($t1,$t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    //开启限制
    private function openDomainLimit($uid,$adsthisUrl,$pdo)
    {
        if(!empty($adsthisUrl))
        {
            // 查看当前域名是否存在和站点扣量
            $sites = $pdo->prepare("select site_id,uid,siteurl,web_deduction,adv_deduction,site_cnzz_id from lz_site WHERE uid=? AND siteurl=? AND status=1");
            $sites->execute(array($uid, $adsthisUrl));
            $siteRes = $sites->fetchAll();
        }
        //绝对查询为空,然后模糊查询(例如：绝对查询 www.lezun.com  模糊查询 lezun.com)
        if (empty($siteRes)) 
        {
            $fuzzy_url_array = explode('.',$adsthisUrl);
            $url_count = count($fuzzy_url_array);

            if($url_count == 2){
                $fuzzy_url_array = $fuzzy_url_array[0] . '.' . $fuzzy_url_array[1];
            }elseif($url_count == 3){
                $fuzzy_url_array = $fuzzy_url_array[1] . '.' . $fuzzy_url_array[2];
            }elseif($url_count > 3){
                //截取域名数组的后 2 段
                $on_key = $url_count-2;
                $down_key = $url_count-1;
                $fuzzy_url_array = $fuzzy_url_array[$on_key] . '.' . $fuzzy_url_array[$down_key];
            }else{
                $fuzzy_url_array = '';
            }
            $num = strpos($fuzzy_url_array,"/");
            if(!empty($num)){
                $fuzzy_url_array = substr($fuzzy_url_array,0,$num);
            }

            if(empty($fuzzy_url_array)){
                $siteRes = '';
            }else{
                // 查看当前域名是否存在和站点扣量
                $fuzzySites = $pdo->prepare("select site_id,uid,siteurl,web_deduction,adv_deduction,site_cnzz_id from lz_site WHERE uid = ? AND siteurl LIKE '%$fuzzy_url_array' AND status=1 ");
                $fuzzySites->execute(array($uid));
                $siteRes = $fuzzySites->fetchAll();
            }
        }
        return $siteRes;
    }

    //站长限制2级
    private function lim_webid_methods($lim_uid_status,$checkplan,$user_city,$week_day,$day_hours,$ad_sql_list,$i,$userid,$lim_uid_array,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow)
    {
        global $xianzhi_log_test;
        global $log_test_str ;
        $panduan = in_array($userid, $lim_uid_array,true);

        // 0 不限制 1 允许 2 屏蔽
        if($lim_uid_status == 0) 
        {
            // 投放地域 3级
            $this->lim_provincial_city_methods($checkplan,$user_city,$week_day,$day_hours,$ad_sql_list,$i,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow);
        }
        elseif($lim_uid_status == 1 && in_array($userid,$lim_uid_array,true)) 
        {
            // 投放地域 3级
            $this->lim_provincial_city_methods($checkplan,$user_city,$week_day,$day_hours,$ad_sql_list,$i,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow);
        }
        elseif($lim_uid_status == 1 && !$panduan) 
        {
            //不满足条件的存cookies存pid
            $this->cookie_limit_pid($ad_sql_list,$i);
        } 
        else 
        {
            //屏蔽以下站长(不包含当前广告位对应的站长)
            if($lim_uid_status == 2 && !in_array($userid,$lim_uid_array,true)) 
            {
                //投放地域3级
                $this->lim_provincial_city_methods($checkplan,$user_city,$week_day,$day_hours,$ad_sql_list,$i,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow);
            } 
            else 
            {
                $xianzhi_log_test='投放限制-站长限制不满足';
                //不满足条件的存cookies 存pid
                $this->cookie_limit_pid($ad_sql_list,$i);
            }
        }
    }


    //投放地域3级
    private function lim_provincial_city_methods($checkplan,$user_city,$week_day,$day_hours,$ad_sql_list,$i,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow)
    {
        global $xianzhi_log_test;
        if(!empty($checkplan['city']['province'])) 
        {
            for ($a = 0; $a < count($checkplan['city']['province']); $a++) 
            {
                for ($b = 0; $b < count($checkplan['city']['data']); $b++) 
                {
                    $provincial_city[] = $checkplan['city']['province'][$a].'-'.$checkplan['city']['data'][$b];
                }
            }
        } 
        else 
        {
            $provincial_city = '';
        }
        //投放地区限制 0不限制 1选择区域
        if($checkplan['city']['isacl'] == 0) 
        {
            //周期日程4级
            $this->lim_week_methods($checkplan,$week_day,$day_hours,$ad_sql_list,$i,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow);
        } 
        else 
        {
            //选择区域 1允许 0拒绝 包含此地区
            if($checkplan['city']['comparison'] == 1 && @in_array($user_city,$provincial_city,true)) {
                // 周期日程4级
                $this->lim_week_methods($checkplan,$week_day,$day_hours,$ad_sql_list,$i,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow);   
            } 
            //选择区域 1允许不包含此地区
            elseif ($checkplan['city']['comparison'] == 1 && @!in_array($user_city,$provincial_city,true)) 
            {
                $xianzhi_log_test='定向设置-投放地域不满足';
                //不满足条件的存cookies 存pid
                $this->cookie_limit_pid($ad_sql_list,$i);
            } 
            //选择区域 0 拒绝 不包含此地区
            elseif ($checkplan['city']['comparison'] == 0 && @!in_array($user_city, $provincial_city,true)) 
            {
                //周期日程4级
                $this->lim_week_methods($checkplan,$week_day,$day_hours,$ad_sql_list,$i,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow);
            } 
            else
            {
                $xianzhi_log_test='定向设置-投放地域不满足';
                //不满足条件的存cookies 存pid
                $this->cookie_limit_pid($ad_sql_list,$i);
            }
        }
    }


    //周期日程4级
    private function lim_week_methods($checkplan,$week_day,$day_hours,$ad_sql_list,$i,$site_res,$pdo,$adz_class_id,$adz_plan_class_allow)
    {
        global $xianzhi_log_test;
        //投放周期日程 0不限制 1限制
        if($checkplan['week']['isacl'] == 0) 
        {
            // 网站限制5级
            $this->lim_site_methods($checkplan,$site_res,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        } 
        elseif($checkplan['week']['isacl'] == 1 && !empty($checkplan['week']['data'][$week_day])) 
        {
            //查看当前时间是否满足周期日程
            if(in_array($day_hours,$checkplan['week']['data'][$week_day],true) == true) 
            {
                //网站限制5级
                $this->lim_site_methods($checkplan,$site_res,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
            } 
            else 
            {
                $xianzhi_log_test='定向设置-周期日程不满足';
                //不满足条件的存cookies存pid
                $this->cookie_limit_pid($ad_sql_list,$i);
            }
        } 
        else 
        {
            $xianzhi_log_test='定向设置-周期日程不满足';
            //不满足条件的存cookies存pid
            $this->cookie_limit_pid($ad_sql_list,$i);
        }
    }


    //网站限制5级
    private function lim_site_methods($checkplan,$site_res,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow)
    {
        global $xianzhi_log_test;
        global $log_test_str;
        
        //网站限制 0不限制
        if($checkplan['sitelimit'] == 0) 
        {
            // 网站类型6级
            $this->lim_siteclass_methods($checkplan,$site_res,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        }
        //1 允许(包含当前广告位投放网站的id) 
        elseif ($checkplan['sitelimit'] == 1 && in_array($site_res[0]['site_id'],$checkplan['limitsiteid'],true)) 
        {
            //网站类型6级
            $this->lim_siteclass_methods($checkplan,$site_res,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        }
        //1 允许(不包含当前广告位投放网站的id) 
        elseif ($checkplan['sitelimit'] == 1 && !in_array($site_res[0]['site_id'], $checkplan['limitsiteid'],true)) 
        {
            $xianzhi_log_test='投放限制-网站限制不满足1';
            //不满足条件的存cookies存pid
            $this->cookie_limit_pid($ad_sql_list, $i);        
        }
        //1 屏蔽(不包含当前广告位投放网站的id) 
        elseif ($checkplan['sitelimit'] == 2 && !in_array($site_res[0]['site_id'], $checkplan['limitsiteid'],true)) 
        {
            // 网站类型6级
            $this->lim_siteclass_methods($checkplan,$site_res,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        }
        //2 屏蔽(包含当前广告位投放网站的id) 
        else 
        {
            $xianzhi_log_test=' 投放限制-网站限制不满足2';
            //不满足条件的存cookies存pid
            $this->cookie_limit_pid($ad_sql_list,$i);
        }
    }


    //网站类型6级
    private function lim_siteclass_methods($checkplan,$site_res,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow)
    {
        global $xianzhi_log_test;
        //网站类型 0不限 1限制
        if($checkplan['siteclass']['isacl'] == 0) 
        {
            //投放设备设置7级
            $this->lim_mobile_methods($checkplan,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        } 
        //1 允许(包含当前广告位投放网站的分类)
        elseif($checkplan['siteclass']['choose'] == 1 && @in_array($site_res[0]['class_id'],$checkplan['siteclass']['data'])) 
        {
            //投放设备设置7级
            $this->lim_mobile_methods($checkplan,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        }
        // 1允许(不包含当前广告位投放网站的分类) 
        elseif($checkplan['siteclass']['choose'] == 1 && @!in_array($site_res[0]['class_id'],$checkplan['siteclass']['data'])) 
        {
            $xianzhi_log_test='定向设置-网站类型不满足';
            //不满足条件的存cookies 存pid
            $this->cookie_limit_pid($ad_sql_list,$i);
        }
        // 0 拒绝(不包含当前广告位投放网站的分类) 
        elseif($checkplan['siteclass']['choose'] == 0 && @!in_array($site_res[0]['class_id'],$checkplan['siteclass']['data'])) 
        {
            //投放设备设置7级
            $this->lim_mobile_methods($checkplan,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        }
        // 0 拒绝(包含当前广告位投放网站的分类) 
        else 
        {
            $xianzhi_log_test='定向设置-网站类型不满足';
            //不满足条件的存cookies 存pid
            $this->cookie_limit_pid($ad_sql_list,$i);
        }
    }


    //投放设备设置7级
    private function lim_mobile_methods($checkplan,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow)
    {
        global $xianzhi_log_test;
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        
        //分析数据
        $is_pc = (strpos($agent,'windows nt')) ? true : false;
        $is_iphone = (strpos($agent,'iphone')) ? true : false;
        $is_ipad = (strpos($agent,'ipad')) ? true : false;
        $is_android = (strpos($agent,'android')) ? true : false;
        $is_wp = (strpos($agent,'wp')) ? true : false;
        
        //输出数据
        if($is_iphone||$is_ipad){
            $mobile = 'ios';
        }elseif($is_pc){
            $mobile = 'pc';
        }elseif($is_wp){
            $mobile = 'android';
        }else{
            $mobile = 'android';
        }
        
        //投放设备 0不限 1限制
        if($checkplan['mobile']['isacl'] == 0) 
        {
            //投放机型8级
            $this->lim_run_model_methods($checkplan,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        }
        // 1允许(包含当前设备) 
        elseif ($checkplan['mobile']['isacl'] == 1 && @in_array($mobile,$checkplan['mobile']['data'],true)) 
        {
            //投放机型8级
            $this->lim_run_model_methods($checkplan,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        } 
        //1允许(不包含当前设备)
        else 
        {
            //不满足条件的存cookies 存pid
            $this->cookie_limit_pid($ad_sql_list,$i);
        }
    }


    //投放机型8级
    private function lim_run_model_methods($checkplan,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow)
    {
        global $xianzhi_log_test;
        //手机型号
        $phone_model = $this->phoneModel();
        //投放机型 isacl:  0 不限制  1 指定终端  data: 3 高端  2 中端  1 低端
        if($checkplan['run_model']['isacl'] == 0) 
        {
            //广告位分类限制 9级
            $this->lim_adzclass_methods($checkplan,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        }
        // 1允许(包含当前设备) 
        elseif ($checkplan['run_model']['isacl'] == 1 && @in_array($phone_model,$checkplan['run_model']['data'],true)) 
        {
            //广告位分类限制9级
            $this->lim_adzclass_methods($checkplan,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow);
        }
        // 1允许(不包含当前设备) 
        else 
        {
            $xianzhi_log_test='投放机型不满足';
            //不满足条件的存cookies 存pid
            $this->cookie_limit_pid($ad_sql_list,$i);
        }
    }


    //广告位分类限制9级
    private function lim_adzclass_methods($checkplan,$ad_sql_list,$i,$pdo,$adz_class_id,$adz_plan_class_allow)
    {
        global $xianzhi_log_test;
        //广告位分类限制 0不限 1限制
        if($checkplan['adzclass']['isacl'] == 0) 
        {
            //广告位里面的计划分类限制 10 级
            $this->lim_planclass_methods($checkplan,$ad_sql_list,$i,$adz_plan_class_allow);
        }
        // 1允许(包含当前广告位分类) 
        elseif($checkplan['adzclass']['choose'] == 1 && @in_array($adz_class_id,$checkplan['adzclass']['data'])) 
        {
            //广告位里面的计划分类限制10级
            $this->lim_planclass_methods($checkplan,$ad_sql_list,$i,$adz_plan_class_allow);
        }
        // 1允许(不包含当前广告位分类) 
        elseif($checkplan['adzclass']['choose'] == 1 && @!in_array($adz_class_id,$checkplan['adzclass']['data'])) 
        {
            $xianzhi_log_test='定向设置-广告位分类不满足';
            $this->cookie_limit_pid($ad_sql_list,$i);
        }
        // 0 拒绝(不包含当前广告位分类) 
        elseif($checkplan['adzclass']['choose'] == 0 && @!in_array($adz_class_id,$checkplan['adzclass']['data'])) 
        {
            //广告位里面的计划分类限制10级
            $this->lim_planclass_methods($checkplan,$ad_sql_list,$i,$adz_plan_class_allow);
        }
        // 0 拒绝(包含当前广告位分类) 
        else 
        {
            $xianzhi_log_test='定向设置-广告位分类不满足';
            //不满足条件的存cookies 存pid
            $this->cookie_limit_pid($ad_sql_list,$i);
        }
    }


    //广告位里面的计划分类限制10级
    private function lim_planclass_methods($checkplan,$ad_sql_list,$i,$adz_plan_class_allow)
    {
        global $xianzhi_log_test;
        if(!empty($adz_plan_class_allow))
        {
            $adz_plan_class_allow = explode(',',$adz_plan_class_allow);
        }
        //广告位里面的计划分类限制0不限
        if($checkplan['class_id'] == 0 || empty($adz_plan_class_allow)) 
        {
            // 1允许(广告位包含当前计划分类)
        } 
        elseif($checkplan['class_id'] != 0 && @in_array($checkplan['class_id'], $adz_plan_class_allow)) 
        {
            // 允许(广告位不包含当前计划分类)
        } 
        else 
        {
            $xianzhi_log_test='广告位不包含当前计划分类不满足';
            //不满足条件的存cookies 存pid
            $this->cookie_limit_pid($ad_sql_list,$i);
        }
    }


    //获取手机型号
    private function model()
    {
        //获取手机系统及型号
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        //返回值中是否有Android这个关键字
        if (stristr($_SERVER['HTTP_USER_AGENT'], 'Android') && stristr($user_agent, 'Build')) 
        {
            $sub_end = stristr($user_agent, 'uild',true); //截取uild之前的字符串
            $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
            $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
            $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
            $sub_results = substr($sub_end, $sub_start, $sub_cha);
            if(empty($sub_results))
            {
                $sub_results = 'Android';
            }
            return $sub_results;   //返回手机型号
        }elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Android')){
            return 'Android';
        }elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'iPhone')){
            return 'iPhone';
        }else{
            return 'win系统';
        }
    }


    private function getOS()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];//这里只进行IOS和Android两个操作系统的判断，其他操作系统原理一样
        if (strpos($ua, 'Android') !== false) {
            preg_match("/(?<=Android )[\d\.]{1,}/", $ua, $version);
            if(empty($version)){
                $version[0] = '';
            }
            return 'Android:' . $version[0];
        } elseif (strpos($ua, 'iPhone') !== false) {
            preg_match("/(?<=CPU iPhone OS )[\d\_]{1,}/", $ua, $version);
            if(empty($version)){
                $version[0] = '';
            }
            return 'iPhone:' . str_replace('_', '.', $version[0]);
        } elseif (strpos($ua, 'iPad') !== false) {
            preg_match("/(?<=CPU OS )[\d\_]{1,}/", $ua, $version);
            if(empty($version)){
                $version[0] = '';
            }
            return 'iPad:' . str_replace('_', '.', $version[0]);
        }
    }


    //获取手机型号,区分高中低手机
    private function phoneModel()
    {
        //获取手机系统及型号
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        //返回值中是否有Android这个关键字
        if(stristr($_SERVER['HTTP_USER_AGENT'],'Android')) 
        {
            if(stristr($_SERVER['HTTP_USER_AGENT'],'SAMSUNG') || stristr($_SERVER['HTTP_USER_AGENT'], 'SM')){
                //三星手机
                $samsung_model = $this->samsungModel($user_agent);
                return $samsung_model;
            }elseif(stristr($_SERVER['HTTP_USER_AGENT'],'MZ')){
                //魅族手机
                $mz_model = $this->mzModel($user_agent);
                return $mz_model;
            }elseif(stristr($_SERVER['HTTP_USER_AGENT'],'vivo')){
                //vivo手机
                $vivo_model = $this->vivoModel($user_agent);
                return $vivo_model;
            }elseif(strstr($_SERVER['HTTP_USER_AGENT'], 'MI')){
                //小米手机
                $mi_model = $this->miModel($user_agent);
                return $mi_model;
            }elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'HTC')){
                //HTC手机
                $htc_model = $this->htcModel($user_agent);
                return $htc_model;
            }elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'OPPO')){
                //oppo手机
                $oppo_model = $this->oppoModel($user_agent);
                return $oppo_model;
            }elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'HUAWE')){
                //华为手机
                return '2';
            }elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'GIONEE')){
                //金立手机
                $gionee_model = $this->gioneeModel($user_agent);
                return $gionee_model;
            }else{
                //其他安卓手机全部为低端机
                return '1';
            }
        }
        else
        {
            // 返回值中是否有iphone关键字，iphone全部为高端机
            if(stristr($_SERVER['HTTP_USER_AGENT'], 'iPhone')){
                return '3';
            }else{
                return 'win系统';
            }
        }
    }


    //三星手机
    private function samsungModel($user_agent)
    {
        //返回值中是否有SAMSUNG/SM这个关键字
        $sub_end = stristr($user_agent, 'uild',true); //截取uild之前的字符串
        $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
        //三星手机型号中W/-G高端机，-A/-N中端机，其他低端机
        if(stristr($sub_results,'W') || stristr($sub_results,'-G')){
            return '3'; //高端机
        }elseif(stristr($sub_results,'-A') || stristr($sub_results,'-N')){
            return '2'; //中端机
        }else{
            return '1'; //低端机
        }
    }


    //魅族手机
    private function mzModel($user_agent)
    {
        //返回值中是否有mz这个关键字
        $sub_end = stristr($user_agent, 'uild',true); //截取uild之前的字符串
        $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
        //魅族手机型号中MX/PRO中端机，其他低端机
        if(stristr($sub_results,'mx') || stristr($sub_results,'pro')){
            return '2';
        }else{
            return '1';
        }
    }

    //vivo手机
    private function vivoModel($user_agent)
    {
        //返回值中是否有vivo这个关键字
        $sub_end = stristr($user_agent, 'uild',true); //截取uild之前的字符串
        $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
        //vivo手机型号中X高端机，Y中端机，其他低端机
        if(stristr($sub_results,'x')){
            return '3';
        }elseif(stristr($sub_results,'y')){
            return '2';
        }else{
            return '1';
        }
    }

    //小米手机
    private function miModel($user_agent)
    {
        //返回值中是否有mi这个关键字
        $sub_end = stristr($user_agent, 'uild',true); //截取uild之前的字符串
        $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
        //小米手机型号中NOTE/MI中端机，其他低端机
        if(strstr($sub_results,'NOTE') || strstr($sub_results,'MI')){
            return '2';
        }else{
            return '1';
        }
    }

    //HTC手机
    private function htcModel($user_agent)
    {
        //返回值中是否有HTC这个关键字
        $sub_end = stristr($user_agent, 'uild',true); //截取uild之前的字符串
        $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
        //htc手机型号中E/M中端机，其他低端机
        if(strstr($sub_results,'M') || strstr($sub_results,'E')){
            return '2';
        }else{
            return '1';
        }
    }

    //OPPO手机
    private function oppoModel($user_agent)
    {
        //返回值中是否有OPPO这个关键字
        $sub_end = stristr($user_agent, 'uild',true); //截取uild之前的字符串
        $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
        //oppo手机型号中有R/N高端机，A中端机，其他低端机
        if(stristr($sub_results,'R') || stristr($sub_results,'N')){
            return '3';
        }elseif(stristr($sub_results,'A')){
            return '2';
        }else{
            return '1';
        }
    }

    //金立手机
    private function gioneeModel($user_agent)
    {
        //返回值中是否有GIONEE这个关键字
        $sub_start = strripos($user_agent,'GIONEE');
        $sub_end = substr($user_agent, $sub_start);
        $sub_two = strpos($sub_end,'/');
        $sub_results = substr($sub_end,0,$sub_two);
        if(strstr($sub_results,'-GN') || strstr($sub_results, '-M')){
            return '2';
        }else{
            return '1';
        }
    }


    // 存不满足条件的计划id
    private function cookie_limit_pid($ad_sql_list,$i)
    {
        global $limit_pid;
        $limit_pid = $limit_pid.','.$ad_sql_list[$i]['pid'];
    }


    private function cookie_checkerG($pdo,$ad_id_arr,$pid,$sredis,$uIP)
    {       
        $result = '';
        $type = "randg";
        //判断redis里面，本次访问的用户是否访问过此广告id
        $user_adid = $sredis->handler()->HMGET($uIP,array('adid'))['adid'];
        if(!empty($user_adid)) 
        {
            $redis_adid_arr = array_flip(explode(',', $user_adid));
            $inter_adids = array_diff_key($ad_id_arr,$redis_adid_arr);
            //当前计划的广告都展示过，清除此计划下面的广告，在加上本次的广告id
            if(empty($inter_adids)){
                foreach($ad_id_arr as $key=>$value){
                    if(array_key_exists($key,$redis_adid_arr)){
                        unset($redis_adid_arr[$key]);
                    }
                }
            }
            //如果广告都被展示，则重新来
            if(empty($inter_adids)) 
            {
                $result = $this->get_rand($ad_id_arr);
                //本次用户访问的广告id + 别的计划下面已显示的广告id 存redis
                $text_adid = implode(',',array_flip($redis_adid_arr));
                $user_text_adid =$text_adid.','.$result;
                $array_adid =array(
                    'adid' => $user_text_adid,
                );
                $sredis->handler()->HMSET($uIP,$array_adid);
            } 
            else 
            {
                $result = $this->get_rand($inter_adids);
                //本次用户访问的广告id + 以前的广告id  存redis
                $user_text_adid = $result.','.$user_adid;
                $array_adid =array(
                    'adid' => $user_text_adid,
                );
                $sredis->handler()->HMSET($uIP,$array_adid);
            }

        } 
        else 
        {
            $result = $this->get_rand($ad_id_arr);
            $array_adid =array(
                'adid' => $result
            );
            //本次用户访问的广告id存redis
            $sredis->handler()->HMSET($uIP,$array_adid);
        }
        return $result;
    }


    private function cookie_checker_plan($ad_id_arr,$sredis,$uIP)
    {
        $type = "randj";
        $pidcount = count($ad_id_arr);

        $result = '';
        if($pidcount <= 1) 
        {
            $res = array_keys($ad_id_arr);
            return $res[0];
        } 
        else 
        {
            //判断redis里面，本次访问的用户是否访问过此计划id
            $user_pid = $sredis->handler()->HMGET($uIP,array('pid'))['pid'];
            if(!empty($user_pid)) 
            {
                $redis_pid_arr = array_flip(explode(',', $user_pid));
                $inter_adids = array_diff_key($ad_id_arr,$redis_pid_arr);
                //如果广告都被展示，则重新来
                if(empty($inter_adids)) 
                {
                    $result = $this->get_rand($ad_id_arr);
                    $array_pid =array(
                        'pid'  => $result
                    );
                    //本次用户访问的广告id存redis
                    $sredis->handler()->HMSET($uIP,$array_pid);
                    //本次用户访问的计划id存redis
                    //$sredis->set($uIP,$result);
                } 
                else 
                {
                    $result = $this->get_rand($inter_adids);
                    //本次用户访问的计划id + 以前的计划id  存redis
                    //$user_text_pid = $result.','.$user_pid;
                    $array_pid =array(
                        'pid'  => $user_pid.','.$result
                    );
                    //本次用户访问的广告id存redis
                    $sredis->handler()->HMSET($uIP,$array_pid);
                    //$sredis->set($uIP,$user_text_pid);
                }
            } 
            else 
            {
                $result = $this->get_rand($ad_id_arr);
                $array_pid =array(
                    'pid'  => $result
                );
                //本次用户访问的广告id存redis
                $sredis->handler()->HMSET($uIP,$array_pid);
                //本次用户访问的计划id存redis
                //$sredis->handler()->HMSET($uIP,$array_pid);
            }
            return $result;
        }
    }


    private function get_rand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) 
        {
            //抽取随机数
            $randNum = mt_rand(1,$proSum);             
            if($randNum <= $proCur) 
            {
                //得出结果
                $result = $key;                         
                break;
            }
            else 
            {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }


    //获取用户IP
    private function getIp()
    {
        $realip = '';
        $unknown = 'unknown';
        if (isset($_SERVER)) 
        {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) 
            {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach($arr as $ip)
                {
                    $ip = trim($ip);
                    if($ip != 'unknown')
                    {
                        $realip = $ip;
                        break;
                    }
                }
            }
            elseif(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], $unknown)) 
            {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            }
            elseif(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) 
            {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
            else 
            {
                $realip = $unknown;
            }
        } 
        else 
        {
            if (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)) 
            {
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            }
            elseif(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)) 
            {
                $realip = getenv("HTTP_CLIENT_IP");
            } 
            elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)) 
            {
                $realip = getenv("REMOTE_ADDR");
            }
            else 
            {
                $realip = $unknown;
            }
        }
        $realip = preg_match("/[\d\.]{7,15}/", $realip, $matches) ? $matches[0] : $unknown;
        return $realip;
    }


    //根据用户ip获取地理位置
    private function getIpLookUp($ip='',$IpSearch)
    {
        header("Content-Type: text/html;charset=utf-8");
        $res = $IpSearch->get($ip);
        if(!empty($res)){
            $res = explode('|', $res);
        }else{
            $res = array(
                0 => '',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
            );
        }
        return $res;
    }


    private function writeFile($file,$str,$mode='a+')
    {
        $fp = @fopen($file,$mode);
        if(!$fp){

        } else {
            @fwrite($fp,$str);
            @fclose($fp);
        }
    }


    //展示的广告信息
    private function advInformation($pdo,$id,$rand,$adzList,$pid)
    {
        global $log_test_str;
        global $log_test_file;

        $prep = $pdo->prepare("SELECT a.uid,a.plantype,a.width,a.height,a.htmlcontrol,a.false_close,b.viewjs,b.iframejs,c.tpl_id,
        d.imageurl,d.url,d.files,d.tc_id,d.pid,e.plan_type,e.uid AS planuid FROM lz_adzone AS a
        LEFT JOIN lz_adstyle AS b ON a.adstyle_id = b.style_id LEFT JOIN lz_admode AS c
        ON a.adtpl_id = c.tpl_id LEFT JOIN lz_ads AS d ON c.tpl_id =d.tpl_id LEFT JOIN lz_plan AS e ON d.pid = e.pid
        WHERE a.adz_id=? AND d.ad_id=? AND a.uid=? AND e.pid=? AND d.status = 1");
        $prep->execute(array($id,$rand,$adzList[0]['uid'],$pid));
        $res = $prep->fetchAll();

        if(empty($res) || count($res)<0){
            //展示广告信息有误
            $log_test_str.=' ERROR10010'."\n";
            writeFile($log_test_file,$log_test_str);
            $res = ['status'=>'3008','msg'=>'展示广告信息有误'];
            return json_encode($res);
            exit;
        }
        $list = $res[0];
        return $list;
    }

    // 判断信息是否完整
    private function replaceList($list)
    {
        if(!empty($list)) 
        {
            // 把字符串 \ 转化 /
            if($list['files'] == 1){
                $list['imageurl'] = "$img_server" . str_replace('\\', '/', $list['imageurl']);
            }else{
                $list['imageurl'] = str_replace('\\', '/', $list['imageurl']);
            }
            $list['imageurl'] = str_replace('./', '/', $list['imageurl']);
        }
        return $list;
    }

    //服务器地址
    private function service($sredis,$globalRes)
    {
        $domain = '';
        if(!empty($globalRes[0]['adv_server'])){
            $globalRes[0]['adv_server'] = explode('//',$globalRes[0]['adv_server']);
            //判断是否为空
            if(!empty($globalRes[0]['adv_server'][1])){
                $domain = $globalRes[0]['adv_server'][1];
            }else{
                $domain = $sredis->redirect_url;
            }

        }else{
            $domain = $sredis->redirect_url;
        }
        return $domain;
    }
}

/*$start_timea = microtime(true);
$end_timea = microtime(true);
$ctime = round($end_timea-$start_timea,3);*/

?>