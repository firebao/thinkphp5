<?php
// +----------------------------------------------------------------------
// | WeiDo
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | @Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
// | @Version: v1.0
// +----------------------------------------------------------------------
// | @Desp: Area模型模块
// +----------------------------------------------------------------------
namespace app\index\model;

use think\Model;
use think\Request;
use think\Config;
use think\Session;

class Area extends Model{
    
    protected $table = "tp_area";
    /**
    * 定位所在城市
    * @access  public
    * @return  integer $areaID2
    */
    public function getDefaultCity()
    {
        //请求信息中是否包含所在城市id，如没有默认为0
        $request = Request::instance();
        $areaId2  = $request->post('city/d', 0);
    
        //保存的会话信息中是否包含所在城市id
        if($areaId2 == 0) {            
            $areaId2 = $request->session('areaId2/d');
        }
        //检验城市有效性
        if($areaId2 > 0) {            
            $map['isShow']      = 1;
            $map['areaFlag']    = 1;
            $map['areaType']    = 1;
            $map['areaId']      = $areaId2;            
            $result             = $this->where($map)->field('areaId')->find();

	        if ($result['areaId'] == '') {
	            $areaId2 = 0;
	        } 	            	        
        } else {            
            $areaId2 = $request->cookie('areaId2/d');            
        }
        //还未获得所在城市id，从网站配置中得到默认城市id
        if($areaId2 == 0) {            
        	$areaId2 = (int)Config::get('site.defaultCity');       	
        }
        //保存所在城市id到session
        Session::set('areaId2', $areaId2);        
        return $areaId2;
    }
    /**
    * 获取区域信息
    * @access  public
    * @param   integer $areaId区域ID
    * @return  object 区域信息
    */
    public function getArea($areaId)
    {

        $map['areaFlag'] = 1;
        $map['isShow'] = 1;
        $map['areaId'] = $areaId;
        return $this->where($map)->find()->getData();
    }
    /**
    * 获取省份列表
    * @access  public
    * @param   
    * @return  array 区域信息
    */
    public function getProvinceList()
    {
        $result = array();
        
        $map['isShow'] = 1;
        $map['areaFlag'] = 1;
        $map['areaType'] = 0; //0表示省份
        
        $list = $this->where($map)->cache('WEIDO_CACHE_CITY_001', 31536000)->field('areaId,areaName')->order('parentId, areaSort')->select();
       
        foreach ($list as $value){
            
            $result[$value->getData('areaId')] = $value->getData();
            
        }

        return $result;
    }   
    /**
    * 获取所有城市，根据字母分类
    * @access  public
    * @param   
    * @return  array 分类城市信息
    */
    public function getCityGroupByKey()
    {
        $result = array();
        $map['isShow'] = 1;
        $map['areaFlag'] = 1;
        $map['areaType'] = 1; //1表示城市
        
        $list = $this->where($map)
            ->cache('WEIDO_CACHE_CITY_000', 31536000)
            ->field('areaId,areaName,areaKey')
            ->order('areaKey, areaSort')
            ->select();
        
        foreach ($list as $value){

            $result[$value->getData('areaKey')][] = $value->getData();
            
        }

        return $result;
    }
    /**
    * 通过省份获取城市列表
    * @access public
    * @param  $provinceId 0 省份ID
    * @return array 所属省份的城市信息
    */
    public function getCityListByProvince($provinceId = 0)
    {
        $result = array();
        $map['isShow'] = 1;
        $map['areaFlag'] = 1;
        $map['areaType'] = 1;
        $map['parentId'] = $provinceId;
        
        $list = $this->where($map)
            ->cache('WEIDO_CACHE_CITY_002'.$provinceId, 31536000)
            ->field('areaId,areaName')
            ->order('parentId,areaSort')
            ->select();
            
        foreach ($list as $value){
            
            $result[] = $value->getData();
            
        }
        return $result;
    }

}
	  

