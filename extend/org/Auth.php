<?php
/* 
 * 权限认证
 *----------------------------------------------------------------
 * @author: wangxz<3528319@qq.com>
 * @date : 2016-8-23 14:49:44
 *----------------------------------------------------------------
 */
namespace org;
use think\Db;

class Auth
{
    
    //默认配置
    protected $_config = array(
        'AUTH_ON' => true, //认证开关
        'AUTH_TYPE' => 1, // 认证方式，1为时时认证；2为登录认证。
        'AUTH_GROUP' => 'auth_group', //用户组数据表名
        'AUTH_GROUP_ACCESS' => 'auth_group_access', //用户组明细表
        'AUTH_RULE' => 'auth_rule', //权限规则表
        'AUTH_ADMIN' => 'administrator'//用户管理员表
    );

    public function __construct() {
        // if (!empty($this->_config)) {
        //     $this->_config = array_merge($this->_config, $this->_config);
        // }
    }
   
    public function check($name, $uid, $relation='or') {
        if (!$this->_config['AUTH_ON'])  return true;
        $authList = $this->getAuthList($uid);
        if (is_string($name)) {
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //有权限的name
        foreach ($authList as $val) {
            if (in_array($val, $name))
                $list[] = $val;
        }
        if ($relation=='or' and !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation=='and' and empty($diff)) {
            return true;
        }
        return false;
    }

    //获得用户组，外部也可以调用
    public function getGroups($uid) {
        static $groups = array();
        if (isset($groups[$uid]))
            return $groups[$uid];
        // $user_groups = Db::name($this->_config['AUTH_GROUP_ACCESS'].' a')->where("a.uid='$uid' and g.status='1'")->join($this->_config['AUTH_GROUP']." g on a.group_id=g.id")->select();
        $sql = 'SELECT * FROM lz_auth_group_access as a LEFT JOIN lz_auth_group as g 
        ON a.group_id=g.id WHERE a.uid=? ';
        $user_groups = Db::query($sql,[$uid]);
        $groups[$uid]=$user_groups?$user_groups:array();
        return $groups[$uid];
    }

    //获得权限列表
    protected function getAuthList($uid) {
        static $_authList = array();
        if (isset($_authList[$uid])) {
            return $_authList[$uid];
        }
        if(isset($_SESSION['_AUTH_LIST_'.$uid])){
            return $_SESSION['_AUTH_LIST_'.$uid];
        }
        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = array();
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid] = array();
            return array();
        }
        //读取用户组所有权限规则
        $map=array(
            'id'=>array('in',$ids),
            'status'=>1
        );
        $rules = Db::name($this->_config['AUTH_RULE'])->where($map)->select();
        //循环规则，判断结果。
        $authList = array();
        foreach ($rules as $r) {
            if (!empty($r['condition'])) {
                //条件验证
                $user = $this->getUserInfo($uid);
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $r['condition']);
                //dump($command);//debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = $r['name'];
                }
            } else {
                //存在就通过
                $authList[] = $r['name'];
            }
        }
        $_authList[$uid] = $authList;
        if($this->_config['AUTH_TYPE']==2){
            //session结果
            $_SESSION['_AUTH_LIST_'.$uid]=$authList;
        }
        return $authList;
    }

    //获得用户资料,根据自己的情况读取数据库
    protected function getUserInfo($uid) {
        static $userinfo=array();
        if(!isset($userinfo[$uid])){
             $userinfo[$uid]=Db::name($this->_config['AUTH_USER'])->find($uid);
        }
        return $userinfo[$uid];
    }

}