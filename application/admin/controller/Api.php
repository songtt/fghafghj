<?php
namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Db;
use PDO;
use app\user\common\Encrypt as Encrypt;
use think\Cookie;
class Api 
{

    public function adinfo()
    { 
        // $request = Request::instance();
        // $params = $request->post();
        // $a = json_encode($params);
        // return $a;

        $request = Request::instance();
        $params = $request->post();
        $ad_id = $params['ad_id'];
        $pid = $params['pid'];
        $adzid = $params['adz_id'];
        // $uid = $params['uid'];

        $res = $this->adinfodo($params);
        $res = json_encode($res);
        return $res;
    }

    /**
     * 实时更新独立IP
     */
    public function updateIp()
    {
        $pdo = new PDO('mysql:host=101.201.107.95;dbname=lezunsys;port=3306','bdroot','Pd36EmQf');
//        $pdo = new PDO('mysql:host=localhost;dbname=lezunsys;port=3306','root','');
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        //创建临时表
        $sql = 'CREATE TEMPORARY TABLE `lz_realtimeip_log` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT \'主键id\',
          `uid` int(8) DEFAULT \'0\' COMMENT \'站长id\',
          `pid` int(8) DEFAULT \'0\' COMMENT \'计划id\',
          `ad_id` int(8) DEFAULT \'0\' COMMENT \'广告id\',
          `ip` varchar(20) DEFAULT \'\' COMMENT \'IP地址\',
          `regional` varchar(40) DEFAULT \'\' COMMENT \'地域\',
          `type` varchar(10) DEFAULT \'\' COMMENT \'类型(计费模式)\',
          `day` date DEFAULT \'0000-00-00\' COMMENT \'日\',
          `validity` varchar(10) DEFAULT \'\' COMMENT \'有效性\',
          `stress_number` int(8) DEFAULT \'0\' COMMENT \'重复次数\',
          `stress_click` int(8) DEFAULT NULL COMMENT \'重复点击\',
          `records_time` varchar(20) DEFAULT \'\' COMMENT \'记录时间\',
          `ctime` int(10) DEFAULT \'0\' COMMENT \'创建时间\',
          `adz_id` int(11) DEFAULT NULL COMMENT \'添加广告位id\',
          PRIMARY KEY (`id`),
          KEY `ip` (`ip`) USING BTREE,
          KEY `day` (`day`) USING BTREE,
          KEY `ad_id` (`ad_id`) USING BTREE,
          KEY `uid` (`uid`) USING BTREE
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8';
        $unlock = $pdo->prepare($sql);
        $unlock->execute();

        //锁表
        $sql =  'LOCK TABLES lz_realtimeip WRITE';
        $lock = $pdo->prepare($sql);
        $lock->execute();
        //将tmp表中的数据更新到临时表中
        $sql = 'insert into lz_realtimeip_log(uid,pid,ad_id,adz_id,ip) select uid,pid,ad_id,adz_id,ip from lz_realtimeip';
        $unlock = $pdo->prepare($sql);
        $unlock->execute();
        //清空数据
        $sql = 'truncate lz_realtimeip';
        $res =$pdo->prepare($sql);
        $res->execute();
        //解锁
        $sql = 'UNLOCK TABLES';
        $unlock = $pdo->prepare($sql);
        $unlock->execute();

        //在临时表中查询不同报表的uv数和ui数
        $sql = 'SELECT count(DISTINCT ip) as count,pid,uid,ad_id,adz_id FROM lz_realtimeip_log GROUP BY pid';
        $num = $pdo->prepare($sql);
        $num->execute();
        $ui_plan = $num->fetchAll();
        $sql = 'SELECT count(DISTINCT ip) as count,pid,uid,ad_id,adz_id FROM lz_realtimeip_log GROUP BY uid';
        $num = $pdo->prepare($sql);
        $num->execute();
        $ui_web = $num->fetchAll();
        $sql = 'SELECT count(DISTINCT ip) as count,pid,uid,ad_id,adz_id FROM lz_realtimeip_log GROUP BY ad_id';
        $num = $pdo->prepare($sql);
        $num->execute();
        $ui_ads = $num->fetchAll();
        $sql = 'SELECT count(DISTINCT ip) as count,pid,uid,ad_id,adz_id FROM lz_realtimeip_log GROUP BY adz_id';
        $num = $pdo->prepare($sql);
        $num->execute();
        $ui_adzone = $num->fetchAll();

        // 查询数据报表中的uv和ui数
        $sql = 'SELECT pid,uid,ad_id,adz_id,ui_plan,ui_web,ui_ads,ui_adzone FROM lz_stats_new WHERE day=?';
        $num = $pdo->prepare($sql);
        $num->execute(array(date("Y-m-d",strtotime("-1 day"))));
        $res = $num->fetchAll();

        //将此次插入到临时表中的uv和ui数更新到统计表中
        foreach($res as $key=>$value){
            foreach($ui_plan as $k=>$v){
                if(($value['pid'] == $v['pid']) && ($value['uid'] == $v['uid']) && ($value['ad_id'] == $v['ad_id']) && ($value['adz_id'] == $v['adz_id'])){
                    $res[$key]['ui_plan'] = $value['ui_plan'] + $v['count'];
                }
            }
            $res[$key]['ui_plan'] = empty($res[$key]['ui_plan']) ? $value['ui_plan'] : $res[$key]['ui_plan'];
            foreach($ui_web as $k=>$v){
                if(($value['pid'] == $v['pid']) && ($value['uid'] == $v['uid']) && ($value['ad_id'] == $v['ad_id']) && ($value['adz_id'] == $v['adz_id'])){
                    $res[$key]['ui_web'] = $value['ui_web'] + $v['count'];
                }
            }
            $res[$key]['ui_web'] = empty($res[$key]['ui_web']) ? $value['ui_web'] : $res[$key]['ui_web'];
            foreach($ui_ads as $k=>$v){
                if(($value['pid'] == $v['pid']) && ($value['uid'] == $v['uid']) && ($value['ad_id'] == $v['ad_id']) && ($value['adz_id'] == $v['adz_id'])){
                    $res[$key]['ui_ads'] = $value['ui_ads'] + $v['count'];
                }
            }
            $res[$key]['ui_ads'] = empty($res[$key]['ui_ads']) ? $value['ui_ads'] : $res[$key]['ui_ads'];
            foreach($ui_adzone as $k=>$v){
                if(($value['pid'] == $v['pid']) && ($value['uid'] == $v['uid']) && ($value['ad_id'] == $v['ad_id']) && ($value['adz_id'] == $v['adz_id'])){
                    $res[$key]['ui_adzone'] = $value['ui_adzone'] + $v['count'];
                }
            }
            //将修改好的数据更新到数据报表中
            $sql = 'UPDATE lz_stats_new SET ui_plan=?,ui_web=?,ui_ads=?,ui_adzone=?
            WHERE pid=? AND uid=? AND ad_id=? AND adz_id=?';
            $num = $pdo->prepare($sql);
            $num->execute(array($res[$key]['ui_plan'],$res[$key]['ui_web'],$res[$key]['ui_ads'],$res[$key]['ui_adzone'],
                $value['pid'],$value['uid'],$value['ad_id'],$value['adz_id']));
        }

        //删除临时表
        $sql = 'drop table lz_realtimeip_log';
        $num = $pdo->prepare($sql);
        $num->execute();
        unset($ui_plan);unset($ui_web);unset($ui_ads);unset($ui_adzone);unset($res);
    }

    /**
     * 实时更新独立访客和独立IP
     */
    public function updateUv()
    {
        $pdo = new PDO('mysql:host=101.201.107.95;dbname=lezunsys;port=3306','bdroot','Pd36EmQf');
//        $pdo = new PDO('mysql:host=localhost;dbname=lezunsys;port=3306','root','');
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        //创建临时表
        $sql = 'CREATE TEMPORARY TABLE `lz_browser_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `uid` int(11) NOT NULL DEFAULT \'0\',
              `browser` varchar(20) DEFAULT \'\' COMMENT \'浏览器名称\',
              `ver` varchar(30) DEFAULT \'\' COMMENT \'版本\',
              `kernel` varchar(30) DEFAULT \'\' COMMENT \'内核\',
              `ad_id` int(11) DEFAULT NULL,
              `ip` varchar(20) DEFAULT NULL,
              `num` int(6) DEFAULT \'0\',
              `day` date DEFAULT \'0000-00-00\',
              `cookies` varchar(30) DEFAULT NULL COMMENT \'独立访客cookies\',
              `pid` int(11) DEFAULT NULL COMMENT \'计划id\',
              `adz_id` int(11) DEFAULT NULL COMMENT \'广告位id\',
              PRIMARY KEY (`id`),
              KEY `uid` (`uid`) USING BTREE,
              KEY `day` (`day`) USING BTREE,
              KEY `browser` (`browser`) USING BTREE,
              KEY `ip` (`ip`) USING BTREE,
              KEY `ad_id` (`ad_id`) USING BTREE,
              KEY `cookies` (`cookies`) USING BTREE
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8';
        $unlock = $pdo->prepare($sql);
        $unlock->execute();

        //锁表
        $sql =  'LOCK TABLES lz_browser WRITE';
        $lock = $pdo->prepare($sql);
        $lock->execute();
        //将tmp表中的数据更新到临时表中
        $sql = 'insert into lz_browser_log(uid,pid,ad_id,adz_id,cookies) select uid,pid,ad_id,adz_id,cookies from lz_browser';
        $unlock = $pdo->prepare($sql);
        $unlock->execute();
        //清空数据
        $sql = 'truncate lz_browser';
        $res =$pdo->prepare($sql);
        $res->execute();
        //解锁
        $sql = 'UNLOCK TABLES';
        $unlock = $pdo->prepare($sql);
        $unlock->execute();

        //在临时表中查询不同报表的uv数和
        $sql = 'SELECT count(DISTINCT cookies) as count,pid,uid,ad_id,adz_id FROM lz_browser_log GROUP BY pid';
        $num = $pdo->prepare($sql);
        $num->execute();
        $uv_plan = $num->fetchAll();
        $sql = 'SELECT count(DISTINCT cookies) as count,pid,uid,ad_id,adz_id FROM lz_browser_log GROUP BY uid';
        $num = $pdo->prepare($sql);
        $num->execute();
        $uv_web = $num->fetchAll();
        $sql = 'SELECT count(DISTINCT cookies) as count,pid,uid,ad_id,adz_id FROM lz_browser_log GROUP BY ad_id';
        $num = $pdo->prepare($sql);
        $num->execute();
        $uv_ads = $num->fetchAll();
        $sql = 'SELECT count(DISTINCT cookies) as count,pid,uid,ad_id,adz_id FROM lz_browser_log GROUP BY adz_id';
        $num = $pdo->prepare($sql);
        $num->execute();
        $uv_adzone = $num->fetchAll();

        // 查询数据报表中的uv和ui数
        $sql = 'SELECT pid,uid,ad_id,adz_id,uv_plan,uv_web,uv_ads,uv_adzone FROM lz_stats_new WHERE day=?';
        $num = $pdo->prepare($sql);
        $num->execute(array(date("Y-m-d",strtotime("-1 day"))));
        $res = $num->fetchAll();

        //将此次插入到临时表中的uv和ui数更新到统计表中
        foreach($res as $key=>$value){
            foreach($uv_plan as $k=>$v){
                if(($value['pid'] == $v['pid']) && ($value['uid'] == $v['uid']) && ($value['ad_id'] == $v['ad_id']) && ($value['adz_id'] == $v['adz_id'])){
                    $res[$key]['uv_plan'] = $value['uv_plan'] + $v['count'];
                }
            }
            $res[$key]['uv_plan'] = empty($res[$key]['uv_plan']) ? $value['uv_plan'] : $res[$key]['uv_plan'];
            foreach($uv_web as $k=>$v){
                if(($value['pid'] == $v['pid']) && ($value['uid'] == $v['uid']) && ($value['ad_id'] == $v['ad_id']) && ($value['adz_id'] == $v['adz_id'])){
                    $res[$key]['uv_web'] = $value['uv_web'] + $v['count'];
                }
            }
            $res[$key]['uv_web'] = empty($res[$key]['uv_web']) ? $value['uv_web'] : $res[$key]['uv_web'];
            foreach($uv_ads as $k=>$v){
                if(($value['pid'] == $v['pid']) && ($value['uid'] == $v['uid']) && ($value['ad_id'] == $v['ad_id']) && ($value['adz_id'] == $v['adz_id'])){
                    $res[$key]['uv_ads'] = $value['uv_ads'] + $v['count'];
                }
            }
            $res[$key]['uv_ads'] = empty($res[$key]['uv_ads']) ? $value['uv_ads'] : $res[$key]['uv_ads'];
            foreach($uv_adzone as $k=>$v){
                if(($value['pid'] == $v['pid']) && ($value['uid'] == $v['uid']) && ($value['ad_id'] == $v['ad_id']) && ($value['adz_id'] == $v['adz_id'])){
                    $res[$key]['uv_adzone'] = $value['uv_adzone'] + $v['count'];
                }
            }
            $res[$key]['uv_adzone'] = empty($res[$key]['uv_adzone']) ? $value['uv_adzone'] : $res[$key]['uv_adzone'];
            //将修改好的数据更新到数据报表中
            $sql = 'UPDATE lz_stats_new SET uv_plan=?,uv_web=?,uv_ads=?,uv_adzone=?
            WHERE pid=? AND uid=? AND ad_id=? AND adz_id=?';
            $num = $pdo->prepare($sql);
            $num->execute(array($res[$key]['uv_plan'],$res[$key]['uv_web'],$res[$key]['uv_ads'],$res[$key]['uv_adzone'],$value['pid'],
                $value['uid'],$value['ad_id'],$value['adz_id']));
        }

        //删除临时表
        $sql = 'drop table lz_browser_log';
        $num = $pdo->prepare($sql);
        $num->execute();
        unset($uv_plan);unset($uv_web);unset($uv_ads);unset($uv_adzone);unset($res);
    }

    /**
     *  
     */
    private function adinfodo($params)
    {
        $ad_id = $params['ad_id'];
        $pid = $params['pid'];
        $adzid = $params['adz_id'];
        // $uid = $params['uid'];

        Db::connect('mysql://root:1234@127.0.0.1:3306/thinkphp#utf8');
        $sql = 'select a.adz_id,a.uid,a.zonename,a.plantype,a.adtpl_id,a.adstyle_id,a.width,
            a.height,a.htmlcontrol,b.style_id,b.viewjs,b.iframejs,c.tpl_id,c.adtype_id,d.ad_id,
            d.tpl_id as adstplid,d.imageurl,d.url,d.files,d.pid,d.web_deduction AS ads_web_deduction,
            d.adv_deduction AS ads_adv_deduction,e.pid AS pids,e.plan_type,e.uid AS planuid,e.gradation,e.price,
            e.price_1,e.price_2,e.price_3,e.price_4,e.price_5,e.pricedv,
            e.deduction,e.web_deduction AS plan_web_deduction,e.budget,f.uid AS userid,
            f.web_deduction AS user_web_deduction,f.adv_deduction AS user_adv_deduction
            from lz_adzone as a left join lz_adstyle as b on a.adstyle_id = b.style_id left join lz_admode as c
            on a.adtpl_id = c.tpl_id left join lz_ads as d on c.tpl_id =d.tpl_id LEFT JOIN lz_plan AS e
            ON d.pid = e.pid LEFT JOIN lz_users AS f ON a.uid = f.uid
            where a.adz_id=? AND d.ad_id=?   AND e.pid=?';
        $res =Db::query($sql,[$adzid,$ad_id,$pid]);


        var_dump($res);exit;
        if(!empty($res)){
            //判断该广告是否分站长星级，并且查询不同站长的单价
            if($res[0]['gradation'] == 1){
                $gradation = $pdo->prepare("select star from lz_site WHERE siteurl=?");
                $gradation->execute(array($_GET['siteUrl']));
                $gradation = $gradation->fetchAll();

                switch($gradation[0]['star']){
                    case 1 : $res[0]['price'] = $res[0]['price_1']; break;
                    case 2 : $res[0]['price'] = $res[0]['price_2']; break;
                    case 3 : $res[0]['price'] = $res[0]['price_3']; break;
                    case 4 : $res[0]['price'] = $res[0]['price_4']; break;
                    case 5 : $res[0]['price'] = $res[0]['price_5']; break;
                }
            }
        }
        
        if(!empty($res)){
            $List = $res[0];
        }
        
        return $List;

    }
    /**
     * 状态码注释： 0：语句不支持 1：查询为空 2:token 验证失败，重新登录
     **/
    public function queryEasy()
    {
        $request = Request::instance();
        $params = $request->post();
        $get_token = $request->get('token');
        $cookie_token = Cookie::get('token');

        if(($cookie_token == $get_token) && (!empty($params))){

            $content = $params['content'];
            if(stristr($content,'select') && !empty($content)){
                $data = Db::query($content);
                if(empty($data)){
                    $data = '1';
                }
            }else{
                $data = '0';
            }
        }else{
            $data = '2';
        }
        return json_encode($data);
    }

    /**
     * 验证登录
     **/
    public function curlLogin()
    {
        $request = Request::instance();
        $params = $request->post();

        $username = $params['username'];
        $Encrypt = new Encrypt();
        $password = $Encrypt->fb_ucenter_encrypt($params['password']);
        $sql = 'SELECT username,password FROM lz_administrator WHERE username="'.$username.'" AND password="'.$password.'" ';
        $data = Db::query($sql);
        if(!empty($data)){
            $token = md5($data[0]['username'].$data[0]['password']);
            $cookie = new Cookie();
            $cookie::set('token',$token,180);
        }else{
            $token = '0';
        }

        return json_encode($token);
    }

    
}