<?php
// +----------------------------------------------------------------------
// | WeiDo Cart购物车模型
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
namespace app\index\model;

use think\Model;

class Cart extends Model
{
    protected $table = "tp_cart";
    
    /**
     * 获取购物车信息
     * @access  public
     * @return  string
     */
    public static function cart_info()
    {
        
    }
}