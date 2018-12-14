<?php
namespace app\user\api;
use app\user\model\User as User;
use think\Db;
use app\user\common\Encrypt as Encrypt;

class DelApi
{       

    /**
     * 日志
     */
    public function del($id,$name = '')
    {
        //获取操作时间
        $time = date("Y-m-d H:m:s");
        //获取操作人名称
        $text = $this->_dellog($id,$name);
        if($name == 'adtpl'){
            $text = $text.'删除时间'.$time."\n";
            $this->writeFile('del.log',$text);
        }else{
            foreach ($text as $key => $value) {
                $text = $value.'删除时间'.$time."\n";
                $this->writeFile('del.log',$text);
            }
        }
        
       
    }

    /**
     * 日志写入
     */
    private function writeFile($file,$str,$mode='a+')
    {
        $fp = @fopen($file,$mode);
        if($fp){
            @fwrite($fp,$str."\n");
            @fclose($fp);
        }
    }

    private function _dellog($id,$name)
    {
        if($name == 'users'){   //删除会员管理
            $sql = 'SELECT * FROM lz_users WHERE uid=?';
            $sqlres = Db::query($sql,[$id]);
            $all = implode('","',$sqlres['0']);
            $res[] = 'INSERT INTO lz_users VALUES ("'.$all.'");删除会员管理-';
        }elseif ($name == 'plan') {   //删除计划及以下的广告
            //计划数据
            $sql = 'SELECT * FROM lz_plan WHERE pid=?';
            $sqlall = Db::query($sql,[$id]);
            $sqlres = $sqlall[0];   
            $sqlres['checkplan'] = str_replace('"','\"',$sqlres['checkplan']);
            $arr = 'INSERT INTO lz_plan VALUES ("'.$sqlres['pid'].'","'.$sqlres['uid'].'","'.$sqlres['plan_name'].'","'.$sqlres['bigpname'].'","'.$sqlres['run_terminal'].'","'.$sqlres['run_type'].'",
                "'.$sqlres['run_model'].'","'.$sqlres['gradation'].'","'.$sqlres['price'].'","'.$sqlres['price_1'].'","'.$sqlres['price_2'].'","'.$sqlres['price_3'].'","'.$sqlres['price_4'].'",
                "'.$sqlres['price_5'].'","'.$sqlres['pricedv'].'","'.$sqlres['mobile_price'].'","'.$sqlres['price_info'].'","'.$sqlres['budget'].'","'.$sqlres['plan_type'].'","'.$sqlres['deduction'].'",
                "'.$sqlres['web_deduction'].'","'.$sqlres['clearing'].'","'.$sqlres['restrictions'].'","'.$sqlres['resuid'].'","'.$sqlres['sitelimit'].'","'.$sqlres['limitsiteid'].'","'.$sqlres['adzlimit'].'",
                "'.$sqlres['limitadzid'].'","'.$sqlres['pkey'].'",
                "'.$sqlres['linkurl'].'","'.$sqlres['cookie'].'","'.$sqlres['checkplan'].'","'.$sqlres['class_id'].'","'.$sqlres['ads_sel_views'].'","'.$sqlres['ads_sel_status'].'","'.$sqlres['status'].'",
                "'.$sqlres['ctime'].'","'.$sqlres['priority'].'");删除计划-';
           
            // 计划下的广告
            $sqlads = 'SELECT * FROM lz_ads WHERE pid=?';
            $adsSqlall = Db::query($sqlads,[$id]);
            $res = array();
            $i = 0;
            foreach ($adsSqlall as $key => $value) {
                $all = implode('","',$adsSqlall[$key]);
                $res[$i++] = 'INSERT INTO lz_ads VALUES ("'.$all.'");删除计划下广告-';
            }
            $res[$i] = $arr;
        }elseif($name == 'plans'){  //批量删除计划及下面的广告
            $res = array();
            $i = 0;
            foreach ($id as $key => $value) {
                $sql = 'SELECT * FROM lz_plan WHERE pid=?';
                $sqlall = Db::query($sql,[$value]);
                $sqlres = $sqlall[0]; 
                $sqlres['checkplan'] = str_replace('"','\"',$sqlres['checkplan']);
                $res[$i++] = 'INSERT INTO lz_plan VALUES ("'.$sqlres['pid'].'","'.$sqlres['uid'].'","'.$sqlres['plan_name'].'","'.$sqlres['bigpname'].'","'.$sqlres['run_terminal'].'","'.$sqlres['run_type'].'",
                "'.$sqlres['run_model'].'","'.$sqlres['gradation'].'","'.$sqlres['price'].'","'.$sqlres['price_1'].'","'.$sqlres['price_2'].'","'.$sqlres['price_3'].'","'.$sqlres['price_4'].'",
                "'.$sqlres['price_5'].'","'.$sqlres['pricedv'].'","'.$sqlres['mobile_price'].'","'.$sqlres['price_info'].'","'.$sqlres['budget'].'","'.$sqlres['plan_type'].'","'.$sqlres['deduction'].'",
                "'.$sqlres['web_deduction'].'","'.$sqlres['clearing'].'","'.$sqlres['restrictions'].'","'.$sqlres['resuid'].'","'.$sqlres['sitelimit'].'","'.$sqlres['limitsiteid'].'","'.$sqlres['adzlimit'].'",
                "'.$sqlres['limitadzid'].'","'.$sqlres['pkey'].'","'.$sqlres['linkurl'].'","'.$sqlres['cookie'].'","'.$sqlres['checkplan'].'","'.$sqlres['class_id'].'","'.$sqlres['ads_sel_views'].'",
                "'.$sqlres['ads_sel_status'].'","'.$sqlres['status'].'",
                "'.$sqlres['ctime'].'","'.$sqlres['priority'].'");批量删除计划-';
                //计划下的广告
                $sqlads = 'SELECT * FROM lz_ads WHERE pid=?';
                $adsSqlall = Db::query($sqlads,[$value]);
                foreach ($adsSqlall as $k => $v) {
                    $all = implode('","',$adsSqlall[$k]);
                    $res[$i++] = 'INSERT INTO lz_ads VALUES ("'.$all.'");批量删除计划下广告-';
                }
            }
        }elseif($name == 'ad'){   //删除广告
            $sql = 'SELECT * FROM lz_ads WHERE ad_id=?';
            $sqlall = Db::query($sql,[$id]);
            $all = $sqlall[0];
            $all = implode('","',$all);
            $res[] = 'INSERT INTO lz_ads VALUES ("'.$all.'");删除广告-';
        }elseif ($name == 'ads') {  //批量删除广告
            $res = array();
            foreach ($id as $key => $value) {
                $sql = 'SELECT * FROM lz_ads WHERE ad_id=?';
                $sqlall = Db::query($sql,[$value]);
                $all = implode('","',$sqlall[0]);
                $res[$key] = 'INSERT INTO lz_ads VALUES ("'.$all.'");批量删除广告-';
            }
        }elseif ($name == 'site') {   //删除网站
            $sql = 'SELECT * FROM lz_site WHERE site_id=?';
            $sqlall = Db::query($sql,[$id]);
            $all = $sqlall[0];
            $all = implode('","',$all);
            $res[] = 'INSERT INTO lz_site VALUES ("'.$all.'");删除网站-';
        }elseif($name == 'adzone'){     //删除广告位
            $sql = 'SELECT * FROM lz_adzone WHERE adz_id=?';
            $sqlall = Db::query($sql,[$id]);
            $all = $sqlall[0];
            $all['htmlcontrol'] = str_replace('"','\"',$all['htmlcontrol']);
            $all = implode('","',$all);
            $res[] = 'INSERT INTO lz_adzone VALUES ("'.$all.'");删除广告位-';
        }elseif($name == 'adtpl'){      //删除广告样式
            $sql = 'SELECT * FROM lz_adstyle WHERE style_id=?';
            $sqlall = Db::query($sql,[$id]);
            $all = $sqlall[0]; 
            $all['htmlcontrol'] = str_replace('"','\"',$all['htmlcontrol']);
            $all['specs'] = str_replace('"','\"',$all['specs']);
            $all = implode('","',$all);
            $res = 'INSERT INTO lz_adstyle VALUES ("'.$all.'");删除广告样式-';
        }elseif ($name == 'home_site') {
            $sql = 'SELECT * FROM lz_site WHERE site_id=?';
            $sqlall = Db::query($sql,[$id]);
            $all = $sqlall[0]; 
            $all = implode('","',$all);
            $res[] = 'INSERT INTO lz_site VALUES ("'.$all.'");删除客户端网站-';
        }elseif ($name == 'home_adz') {
            $sql = 'SELECT * FROM lz_adzone WHERE adz_id=?';
            $sqlall = Db::query($sql,[$id]);
            $all = $sqlall[0]; 
            $all['htmlcontrol'] = str_replace('"','\"',$all['htmlcontrol']);
            $all = implode('","',$all);
            $res[] = 'INSERT INTO lz_adzone VALUES ("'.$all.'");删除客户端广告位-';
        }
            return $res;
        
    }
    

}