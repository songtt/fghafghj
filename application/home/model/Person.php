<?php
namespace app\home\model;
use think\Model;

class Person extends Model
{    
    public function getone()
    {
        // 查询数据
        // $one =Db::table('person')->select();
        $one = Person::all();
        return $one;
    }
}
