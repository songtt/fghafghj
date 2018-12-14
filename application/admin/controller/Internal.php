<?php
/** 数据库连接设置
 * date   2016-11-22
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Loader;
use think\Request;
use think\Hook;

class Internal extends Controller
{
    /**
     * 数据报表
     */
    public function internal()
    {
        $request = Request::instance();

            if($request->isPost()){
                $params = $request->post();
                $adminTxt = fopen('admin.txt','w');

                $content = $this::mysqlConnection($params['type']);

                $text = fwrite($adminTxt,$content);
                if($text == true){
                    fclose($adminTxt);
                    $this->redirect('internal/internal',['type'=>'admin']);
                }
            }else{
                if($request->param('type') == 'admin'){
                    if(is_file('admin.txt')){
                        $adminTxt = file_get_contents('admin.txt');
                    }else{
                        //没有文件时，创建并写入
                        $content = 'mysql:host=101.201.107.95;dbname=lezunsys;port=3306'.','.'bdroot'.','.'Pd36EmQf';
                        $adminTxt = fopen('admin.txt','w');
                        $adminTxt = fwrite($adminTxt,$content);
                    }
                    $adminTxt = $this::theMysqlConnection($adminTxt);
                    $this->assign('type',$adminTxt);
                }else{
                    $this->success('参数错误','index/login');
                }
            }

        return $this->fetch('index');
   }
//写入文件
    static private function mysqlConnection($adminTxt){
        switch($adminTxt){
            case 0:
                $content ="mysql:host=localhost;dbname=lezunsys;port=3306,root,";
                break;
            case 1:
                $content = "mysql:host=101.201.107.95;dbname=testlezun;port=3306,bdroot,Pd36EmQf";
                break;
            default:
                $content = "mysql:host=101.201.107.95;dbname=lezunsys;port=3306,bdroot,Pd36EmQf";
        }
        return $content;
    }
//默认选中
    static private function theMysqlConnection($adminTxt){
        switch($adminTxt){
            case "mysql:host=localhost;dbname=lezunsys;port=3306,root,":
                $content = 0;
                break;
            case "mysql:host=101.201.107.95;dbname=testlezun;port=3306,bdroot,Pd36EmQf":
                $content = 1;
                break;
            default:
                $content = 2;
        }
        return $content;
    }

}