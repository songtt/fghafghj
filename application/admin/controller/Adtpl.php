<?php
/* 广告模板管理
 * date   2016-6-2
 */
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Hook;
use app\user\api\DelApi as DelApi;


class Adtpl extends Admin
{
    /**
     * 广告类型
     */
    public function adtype()
    {
        Hook::listen('auth',$this->_uid); //权限
        $res = Loader::model('Adtpl')->getLst();
        $this->assign('type_list',$res);
        return $this->fetch('adtpl-type');
    }

    /**
     * 广告类型添加
     */
    public function addType()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if($request->isPost()){
            $params = $request->post();
            if(!empty($params['statstype'])){
                $type_array = $params['statstype'];
                //转化字符串在赋给原数组
                $params['statstype'] = implode($type_array);
            }

            $res = Loader::model('Adtpl')->addOne($params);
            if($res>0){
                //保存成功
                $this->redirect('adtype',['cmd_flag' => 'add']);
            }else{
                $this->_error();
            }
        } else {
            return $this->fetch('type-add');
        }
    }

    /**
     * 广告类型edit
     */
    public function editType()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        if ($request->isPost()){
            $params = $request->post();
            $params['statstype'] = join(',',$params['statstype']);
            $res = Loader::model('Adtpl')->editTypeOne($params);
            if($res>=0){
                //保存成功
                $this->redirect('adtype',['cmd_flag' => 'add']);
            }else{
                $this->_error();
            }
        }else{
            $aid = $request->get('id');
            $res = Loader::model('Adtpl')->getTypeOne($aid);
            // stype定义处理
            $res = $this->_doStype($res);
            $this->assign('one',$res);
            return $this->fetch('type-edit');
        }
    }

    /**
     * stats_type  定义处理
     */
    private function _doStype($res)
    {
        $res['stats_type'] = explode(',', $res['stats_type']);
        $res['stype']['CPC'] = '';
        $res['stype']['CPM'] = '';
        $res['stype']['CPV'] = '';
        $res['stype']['CPS'] = '';
        $res['stype']['CPA'] = '';
        $res['stype']['CPAS'] = '';
        foreach($res['stats_type'] as $value){
            $res['stype'][$value] = $value;
        }
        return $res;
    }

    /**
     * 广告类型 delect
     */
    public function delType()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $id = $request->param('id');
        $res = Loader::model('Adtpl')->delOne($id);
        if($res>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 广告模式
     */
    public function adtpl()
    {
        Hook::listen('auth',$this->_uid); //权限
        $tpl_list = Loader::model("Adtpl")->adtplList();
        $this->assign('tpl_list',$tpl_list);
        return $this->fetch('adtpl-tpl');
    }

    /**
     * 处理数据 $data
     */
    private function _doAddTpl($params)
    {
        $html_arr = array(
            'control_text'=>$params['control_text'],
            'control_type'=>$params['control_type'],
            'control_name'=>$params['control_name'],
            'control_id'=>$params['control_id'],
            'control_describe'=>$params['control_describe'],
        );
        $data = array(
            'adtype_id'=>$params['adtypeid'],
            'tplname'=>$params['name'],
            'htmlcontrol'=>serialize($html_arr),
            'sort'=>$params['sort'],
            'ctime'=>time(),
        );
        return $data;
    }

    /**
     * 广告模式 add
     */
    public function addTpl()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //获取广告类型值
        $type_list = Loader::model('Adtpl')->getLst();
        $this->assign('type_list',$type_list);
        $params = $request->post();
        if(!in_array('',$params) && !empty($_POST)){
            $data = $this->_doAddTpl($params);
            $data['type'] = empty($params['type']) ? 2:$params['type'];
            $inster = Loader::model('Adtpl')->addTpl($data);
            if($inster>0){
                $this->redirect('adtpl');
            }else{
                $this->_error();
            }
        }
        return $this->fetch('adtpl-add');
    }

    /**
     * 处理数据 $data
     */
    private function _doAdEdit($params)
    {
        $html_arr = array(
            'control_text'=>$params['control_text'],
            'control_type'=>$params['control_type'],
            'control_name'=>$params['control_name'],
            'control_id'=>$params['control_id'],
            'control_describe'=>$params['control_describe'],
        );
        $data = array(
            'adtype_id'=>$params['adtypeid'],
            'tplname'  =>$params['name'],
            'htmlcontrol'=>serialize($html_arr),
            'sort'       =>$params['sort'],
            'ctime'     =>time(),
        );
        return $data;
    }

    /**
     * 广告模式  edit
     */
    public function adtplEdit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        //获取广告类型值
        $type_list = Loader::model('Adtpl')->getLst();
        $this->assign('type_list',$type_list);
        //显示默认
        $id = $request->get('id');
        $tpl_list = Loader::model('Adtpl')->adtplListOne($id);
        if(empty($tpl_list)){
            $this->redirect('adtpl');
        }
        $this->assign('tpl_list',$tpl_list);
        //控件
        $htmlcont = unserialize($tpl_list['htmlcontrol']);
        // 循环值
        $this->assign('htmlcont',$htmlcont);
        // 循环 条件（几次）
         $this->assign('cunt',count($htmlcont['control_text']));
        if(!in_array('',$params) && !empty($_POST)){
            $data = $this->_doAdEdit($params);
            $inster = Loader::model('Adtpl')->adtplEdit($id,$data);
            if($inster>=0){
                $this->redirect('adtpl');
            }else{
                $this->_error();
            }
        }
        return $this->fetch('adtpl-edit');
    }

    /**
     *  广告模式  delect
     */
    public function adtplDelect()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $id = $request->get('id');
        $dele = Loader::model('Adtpl')->adtplDelect($id);
        if($dele>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     *  广告模式  更改状态
     */
    public function adtplStat()
    {
        Hook::listen('auth',$this->_uid); //权限
        $params = input('');
        if(!empty($params)){
            $update = Loader::model('Adtpl')->adtplStatEdit($params['id'],$params['status']);

            if($update>0){
                $this->_success();
            }else{
                $this->_error();
            }
        }

    }

    /**
     * 广告样式
     */
    public function adstyle()
    {
        Hook::listen('auth',$this->_uid); //权限
        $adstyle_list = Loader::model('Adtpl')->adstyleList();
        foreach($adstyle_list as $key => $value){
            $adstyle_list[$key]['ctime'] = date("Y-m-d",$value['ctime']);
        }
        $this->assign('adstyle_list',$adstyle_list);
        return $this->fetch('adtpl-style');
    }

    /**
     * 广告样式已删除列表
     */
    public function adstyleDelist()
    {
        Hook::listen('auth',$this->_uid,'adtpl-adstyle'); //权限
        $adstyle_list = Loader::model('Adtpl')->adstyleDelist();
        $this->assign('adstyle_list',$adstyle_list);
        return $this->fetch('adtpl-styledel');
    }

    /**
     * 处理数据 $data
     */
    private function _doAdstyleAdd($params)
    {
        $html_arr = array(
            'control_text'=>$params['control_text'],
            'control_type'=>$params['control_type'],
            'control_name'=>$params['control_name'],
            'control_id'=>$params['control_id'],
            'control_describe'=>$params['control_describe'],
        );

        $data = array(
            'tpl_id'=>$params['adtypeid'],
            'stylename'=>$params['name'],
            'sort'   =>$params['sort'],
            'viewjs' =>$params['viewjs'],
            'iframejs' =>$params['yang'],
            'htmlcontrol'=>serialize($html_arr),
            'specs'     =>serialize($params['specs']),
            'ctime'     =>time(),
        );
        return $data;
    }

    /**
     *  广告样式 添加
     */
    public function adstyleAdd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        //获取广告类型值
        $type_list = Loader::model('Adtpl')->adtplList();
        $this->assign('type_list',$type_list);
        $params = $request->post();
        if($request->isPost()){
            $data = $this->_doAdstyleAdd($params);
            $add = Loader::model('Adtpl')->adstyleAdd($data);
            if($add>0){
                $this->redirect('adstyle');
            }else{
                $this->_error();
            }
        }else{
            $adspecs_list = Loader::model('Adtpl')->adspecsList();
            $this->assign('adspecs_list',$adspecs_list);
        }
        return $this->fetch('adstyle-add');
    }

    /**
     * _doAdstyleEdit  处理数据 $data
     */
    private function _doAdstyleEdit($params)
    {
        $html_arr = array(
            'control_text'=>$params['control_text'],
            'control_type'=>$params['control_type'],
            'control_name'=>$params['control_name'],
            'control_id'=>$params['control_id'],
            'control_describe'=>$params['control_describe'],
        );
        if(empty($params['specs'])){
            $params['specs'] = '';
        }
        $data = array(
            'tpl_id'=>$params['adtypeid'],
            'stylename'=>$params['name'],
            'sort'   =>$params['sort'],
            'viewjs' =>$params['viewjs'],
            'iframejs' =>$params['yang'],
            'htmlcontrol'=>serialize($html_arr),
            'specs'=>serialize($params['specs']),
        );
        return $data;
    }

    /**
     *  广告样式  修改
     */
    public function adstyleEdit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        if($request->isPost()){

            $data = $this->_doAdstyleEdit($params);
            $id = $request->get('id');
            $upda = Loader::model('Adtpl')->adstyleUpdete($id,$data);
            if($upda>=0){
                //写操作日志
                $this->logWrite('0061',$id);
                $this->redirect('adstyle');
            }else{
                $this->_error();
            }
        }else{
            //获取广告类型值
            $type_list = Loader::model('Adtpl')->adtplList();
            $this->assign('type_list', $type_list);
            //显示 数据
            $id = $request->get('id');
            $adstyle = Loader::model('Adtpl')->adstyleFind($id);
            if(empty($adstyle)){
                $this->redirect('adstyle');
            }
            //控件
            $htmlcont = unserialize($adstyle['htmlcontrol']);
            // 循环值
            $this->assign('htmlcont',$htmlcont);
            // 循环 条件（几次）
            $this->assign('cunt',count($htmlcont['control_text']));
            //尺寸
            $specs =json_encode(unserialize($adstyle['specs']));
            $spcunt = count(unserialize($adstyle['specs']));
            //循环 尺寸数据
            $adspecs_list = Loader::model('Adtpl')->adspecsList();
            $this->assign('adspecs_list',$adspecs_list);
            $this->assign('specs', $specs);
            $this->assign('spcunt', $spcunt);
            $this->assign('adstyle', $adstyle);
        }
        return $this->fetch('adstyle-edit');
    }

    /**
     *  广告样式  delect
     */
    public function adstyleDelect()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $id = $request->param('id');
        $UserApi = new DelApi();
        $UserApi->del($id,'adtpl');
        $dele = Loader::model('Adtpl')->adstyleDelect($id);
        if($dele>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

    /**
     * 广告尺寸
     */
    public function adspecs()
    {
        Hook::listen('auth',$this->_uid); //权限
        $adspecs_list = Loader::model('Adtpl')->adspecsList();
        $this->assign('adspecs_list',$adspecs_list);
        return $this->fetch('adtpl-specs');
    }

    /**
     * 广告尺寸  add
     */
    public function adspecsAdd()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $params = $request->post();
        if(!in_array('',$params) && !empty($_POST)){
            $data = array(
                'width'=>$params['width'],
                'height'=>$params['height'],
                'sort'  =>$params['sort']
            );
            $add = Loader::model('Adtpl')->adspecsAdd($data);
            if($add>0){
                $this->redirect('adspecs');
            }else{
                $this->_error();
            }
        }
        return $this->fetch('adspecs-add');
    }

    /**
     *  广告尺寸  edit
     */
    public function adspecsEdit()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $id = $request->get('id');
        $adspecs_list = Loader::model('Adtpl')->adspecsFind($id);
        $this->assign('adspecs_list',$adspecs_list);
        $params = $request->post();
        if(!in_array('',$params) && !empty($_POST)){
            $data = array(
                'width'=>$params['width'],
                'height'=>$params['height'],
                'sort'  =>$params['sort']
            );
            $edit = Loader::model('Adtpl')->adspecsEdit($id,$data);
            if($edit>=0){
                $this->redirect('adspecs');
            }else{
                $this->_error();
            }
        }
        return $this->fetch('adspecs-edit');
    }
    
    /**
     *  广告尺寸  delect
     */
    public function adspecsDelect()
    {
        $request = Request::instance();
        Hook::listen('auth',$this->_uid); //权限
        $id = $request->get('id');
        $dele = Loader::model('Adtpl')->adspecsDelect($id);
        if($dele>0){
            $this->_success();
        }else{
            $this->_error();
        }
    }

}