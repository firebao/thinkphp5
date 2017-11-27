<?php
// +----------------------------------------------------------------------
// | WeiDo
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;
use think\Db;

class Brand extends Model
{
    /**
     * 取得品牌列表
     * @return array
     */
    public function BrandList()
    {
        $sql = 'SELECT brand_id, brand_name FROM ' . 'tp_brand' . ' ORDER BY sort_order';
        $res = Db::query($sql);
        return $res;
    }
}