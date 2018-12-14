<?php
   /**
     * 系统解密方法
     * param string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
     * param string $key  加密密钥
     * return string 
     */
    function fb_ucenter_decrypt($data, $key = 'lezun'){
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


    $a = fb_ucenter_decrypt('yX1vpJfTed2WpaN/hXdwcw');
    var_dump($a);exit;