<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/13 0013
 * Time: 下午 4:39
 */

namespace app\api\controller;


use app\common\WriteLog;
use think\Request;

class GetClickType
{
    /**
     * @return bool
     */
    public function checkType()
    {
        $request = Request::instance();
        $data = $request->param('');
        var_dump($data['data']);
//        $type = $data->param['type'];
        $file='./test.log';
        $type='2'."\n";
        return WriteLog::writeFile($file,$type);
    }
}