<?php
// +----------------------------------------------------------------------
// | WeiDo 区域控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;

use think\Request;
use app\index\model\Area as AreaModel;


class Area extends Bace
{
    /**
     * 通过省份获取城市列表
     * @access public
     * @param
     * @return
     */
    public function getCityListByProvince()
    {
        $request = Request::instance();
        $provinceId = $request->post('provinceId/d');
        $area = new AreaModel();
        $cityList = $area->getCityListByProvince($provinceId);
        return $cityList;      
    }
    
}