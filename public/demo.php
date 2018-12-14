<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <script src="http://www.lzbd.com/static/jquery/jquery.min.js"></script>
</head>

<body>

<?php

// 读取文件夹和子文件夹
function tree($dir)
{
    //过滤图片文件后缀格式
    $img_array = array('gif','png','log','jpg','sample','txt');
    $mydir = dir($dir);
    while($file = $mydir->read())
    {

        if($file != '.' && $file != '..')
        {
            $array_suffix = explode('.',$file);
            $text_suffix = isset($array_suffix[1])?$array_suffix[1]:'';

            //过滤图片列表
            if(!in_array($text_suffix,$img_array)) {
                if (is_dir("$dir/$file")) {
                    echo '目录名：<a href="javascript:;"  onclick="get(this);">' . $dir . "/" . '<font color="red">' . $file . '</font></a><br/>';
                    tree("$dir/$file");
                } else {
                    echo '文件名：<a href="javascript:;"onclick="get(this);">' . $dir . "/" . $file . '</a><br/>';
                }
            }
        }
    }
    
    $mydir->close();
}


/*
    定义的path目录
    $path = "C:/aaa/bbb/ccc/ddd/eee";
    调用makeDir函数自动生成目录
    makeDir($path);
    $path = "C:/aaa/bbb/ccc/ddd/eee.txt";
    调用makeDir函数自动生成目录在ddd目录下有一eee.txt的文件
    makeDir($path，true);
    参数说明：
        $path需要生成的路径，前面什么都不加默认生成在本目录下
        例如：/aaa/bbb/ccc/ddd/eee
        $hasfile是否生成文件，非零为生成文件
        文件名在path中包含。
    */
function makeDir($path, $hasfile)
{
//标记是否生成最后的文件，控制循环的次数
    $falg = 0;
    if ($hasfile) {
        $falg = 1;
    }
    //将path按 / 分割
    $dirs = explode('/', $path);

    $dircount = count($dirs);
    $makedir = $dirs[0];
    for ($i = 1; $i < ($dircount+1) - $falg; $i++) {
        //判断生成目录的位置
        if (!strcmp($makedir, "")) {
            $makedir = ".";
        }

        //目录名称
        $makedir = $makedir . "/" . $dirs[$i];

        //判断是否已含有本目录
        if (is_dir($makedir)) {
            echo $makedir . "目录已存在<br/>";
            continue;
        }
        $text = stripos($makedir,'.');

        if($text == false){

                //创建目录
                if (mkdir($makedir)) {
                    echo $makedir . "--目录创建成功<br/>";
                }
        }

    }

    //创建文件
    if ($hasfile){
        $filename = $makedir ;

        //判断文件是否存在
        if (!is_file($filename)) {

            $text = stripos($makedir,'.');
            if($text == true){
                if (touch($filename)){
                    //不为空进入
                    if(!empty($_POST)){
                        //读取文件
                        $cont = @file_get_contents($_POST['url']);
                        //写入文件
                        file_put_contents($filename,$cont,FILE_APPEND);//记录日志
                        echo "文件创建成功<br/>";
                    }
                }
            }

        } else {
            $text = stripos($makedir,'.');
            if($text == true){
                echo "文件更新成功<br/>";
            }else{
                echo "目录已存在<br/>";
            }

        }
    }
}


$dir = 'D:/www/lezun/shangxian';

if(!empty($_POST)){

    $url = str_replace("D:",$dir,$_POST['url']);
    makeDir($url,true);
}
tree('D:/www/lezun');


?>

<script language='JavaScript'>

    function get(a)
    {

        var url = a.innerText;
//        document.getElementById("form").submit();

        $.post('http://www.lzbd.com/demo.php',{url:url},function(data){

        })
        a.style.color = "red";
    }
</script>
</body>
</html>




















