<?php
namespace app\user\common;
class Encrypt 
{ 
    /**
     * 系统非常规MD5加密方法
     * param  string $str 要加密的字符串
     * return string 
     */
    public function fb_ucenter_md5($str, $key = 'ThinkUCenter'){
        return '' === $str ? '' : md5(sha1($str) . $key);
    }

    /**
     * 系统加密方法
     * param string $data 要加密的字符串
     * param string $key  加密密钥
     * param int $expire  过期时间 (单位:秒)
     * return string 
     */
    public function fb_ucenter_encrypt($data, $key = 'lezun', $expire = 0) {
        $key  = md5($key);
        $data = base64_encode($data);
        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        $char = $str = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x=0;
            $char  .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data,$i,1)) + (ord(substr($char,$i,1)))%256);
        }
        return str_replace('=', '', base64_encode($str));
    }

    /**
     * 系统解密方法
     * param string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
     * param string $key  加密密钥
     * return string 
     */
    public function fb_ucenter_decrypt($data, $key){
        $key    = md5($key);
        $x      = 0;
        $data   = base64_decode($data);
        $len  = strlen($data);
        $l    = strlen($key);
        $char = $str = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char  .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            }else{
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return base64_decode($str);
    }
}
