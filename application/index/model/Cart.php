<?php
// +----------------------------------------------------------------------
// | WeiDo Cart购物车模型
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | @Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
// | @Version: v1.0
// +----------------------------------------------------------------------
// | @Desp: 实现围兜网用户类业务逻辑层与数据层操作
// +----------------------------------------------------------------------
namespace app\index\model;

use think\Model;

class Cart extends Model
{
    protected $goods;                       //商品模型
    protected $spec_goods_price;            //商品规格模型
    protected $goods_buy_num;               //购买的商品数量
    protected $session_id;                  //session_id
    protected $user_id = 0;                 //user_id
    protected $user_goods_type_count = 0;   //用户购物车的全部商品种类
    protected $table = "tp_cart";
    
    /**
     * @desc    Cart构造函数
     * @access  public
     * @return  void
     */
    public function __construct()
    {
        parent::__construct();
        $this->session_id = session_id();
    }
    /**
     * @desc    获取购物车信息
     * @access  public
     * @return  string
     */
    public function cart_info()
    {
        
    }
    /**
     * @desc    获取购物车信息
     * @access  public
     * @param   int     $user_id  用户id
     * @return  void
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }
    /**
     * @desc    用户登录后对购物车商品进行合并
     * @access  public
     * @return  void
     */
    public function doUserLoginHandle()
    {
        //判断session_id与user_id是否为空
        if (empty($this->session_id) || empty($this->user_id)) {
            return;
        }
        //登录后将当前session_id购物车的商品的 user_id改为当前登录的id       
        $this->save(['user_id' => $this->user_id], ['session_id' => $this->session_id, 'user_id' => 0]);
        //查找购物车两件完全相同的商品
        $cart_id_arr = $this->field('rec_id')
            ->where(['user_id' => $this->user_id])
            ->group('goods_id')
            ->having('count(goods_id) > 1')
            ->select();
        if (!empty($cart_id_arr)) {
            $arr2 = array();
            foreach($cart_id_arr as $key => $val){
                $arr2[] = $val['rec_id'];
            } 
            // 删除购物车完全相同的商品
            $this->delete($arr2); 
        }        
    }
}