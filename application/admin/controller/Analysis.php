<?php
/* 流量分析
 * date   2016-10-12
 *author zhangzz
 */
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Hook;




class Analysis extends Admin
{
    /**
     * 趋势分析
     */
    /* 全部信息 */
    public function trend()
    {

        return $this->fetch('analysis-trend');

    }

    /**
     * 搜索引擎
     */
    /* 全部信息 */
    public function search()
    {

        return $this->fetch('analysis-search');    
        
    }

    /**
     * 客户端属性
     */
    /* 全部信息 */
    public function client()
    {

        return $this->fetch('analysis-client');        
        
    }

    /**
     * 地域分布
     */
    /* 全部信息 */
    public function region()
    {

        return $this->fetch('analysis-region');  

    }
}
   
