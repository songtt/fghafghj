<?php
namespace app\admin\controller;

use think\Loader;
use think\Request;

class Feedback extends Admin
{
	/**
	 *  用户提交反馈信息
	 */
	public function feed()
	{
		$request = Request::instance();
    $params = $request->post();
    if(!empty($params)){
        //上传文件
        $file = request()->file('image');
        if(!empty($file)){
          // 移动到框架应用根目录/public/uploads/ 目录下
          $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
          $params['image'] ='\uploads\\'.$info->getSaveName(); 
        }
        if(empty($params['name']) && empty($params['message'])){
          $this->redirect('feed');
        }
        $data = $this->_add($params);
        $res = Loader::model('Feedback')->add($data);
        $this->redirect('message');
     
    }
		return $this->fetch('feed');
	}


	/**
	 *  用户反馈信息展示
	 */
	public function message()
	{
      //反馈问题
      $res = Loader::model('Feedback')->info();
      $this->assign('one',$res);
  		return $this->fetch('message');
	}

    /**
     * 回复
     */
    public function reply()
    {
        $request = Request::instance();
        $params = $request->param();
        $data = $this->_addRe($params);

        $res = Loader::model('Feedback')->addOne($data);
        $this->redirect('message');
    }

	 /**
     * 组装数据 
     */
    private function _add($params)
    {
      //组建字段数组
      $data =array(
          'name' => $params['name'],
          'message' => $params['message'],
          'image' => $params['image'],
          'ctime' => time(),
      );
         return $data;
    }
    
  /**
   *  将回复内容插入数据表
   */
  private function _addRe($params)
  {
    $data = array(
      'uid' => $params['id'],
      'reply' => $params['reply'],
      'ctime' => time(),
      );
    return $data;
  }

}