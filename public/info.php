<?php
$id = 13;
var_dump($_SERVER['HTTP_REFERER']);exit;
if (!strpos($_SERVER['HTTP_REFERER'], 'lzbd.com') && $id != 10 || $id!=16){

    echo 111;
}else{
    echo 222;
}