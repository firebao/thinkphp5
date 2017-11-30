<?php
// +----------------------------------------------------------------------
// | WeiDo User模型
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
use think\Session;
use think\Cookie;
use think\Db;
use think\Request;

class User extends Model
{
    protected $table = "tp_users";
    
    /**
     * @desc   用户注册
     * @access public
     * @param  array $data 注册数据
     * @return array('status', 'msg')
     */
    public function regist($data)
    {
       
       //注册数据save
       $this->user_name = $data['username'];
       $this->mobile_phone = $data['phone'];      
       $this->password = encrypt($data['password']);
       $this->reg_time = time();
       $this->last_login = time();
       
       if($this->save()) {              
          //TODO:会员注册赠送积分
          //TODO:记录日志流水
           return array('status' => 1, 'msg' => '注册成功');
       } else {
           return array('status' => -1, 'msg' => '注册失败');
       }
    }
    /**
     * @desc   用户登录
     * @access public
<<<<<<< HEAD
     * @param  array $data 用户登录信息数组
     * @return array('status', 'msg')
     */
    public function login($data)
    {
        $result = array();
        $username = $data['username'];
        $password = $data['password'];
        if(!$username || !$password)
            $result = array('code' => 0, 'msg' => '请填写账号或密码');
        $user = $this->where("mobile_phone", $username)->whereOr('user_name', $username)->find();
        if(!$user) {
            $result = array('code' => -1, 'msg' => '账号不存在!');
        } elseif (encrypt($password) != $user['password']) {
            $result = array('code' => -2, 'msg'=>'密码错误!');
        } elseif ($user['is_lock'] == 1) {
            $result = array('code' => -3, 'msg'=>'账号异常已被锁定！！！');
        }else{
            //查询用户信息之后, 查询用户的登记昵称
            $user_rank = $user['user_rank'];
            $level_name = Db::table("tp_user_rank")->where("rank_id", $user_rank)->value("rank_name");
            $user['level_name'] = $level_name;
        
            $result = array('code'=>1,'msg'=>'登陆成功','result'=>$user);
        }
        return $result;
    }
     /**
     * @desc   第三方用户登录
     * @access public
     * @param  array $data
     * @return array('status', 'msg')
     */
    public function thirdLogin($data = array())
    {
    }
     /**
     * @desc   修改密码
     * @access public
     * @param  string $user_id
     * @param  string $new_password
     * @return array('status', 'msg')
     */
    public function editPass($user_id, $new_password)
    {
    }
     /**
     * @desc   修改用户资料
     * @access public
     * @param  string $user_id
     * @param  string $new_data
     * @return array('status', 'msg')
     */
    public function editUser($user_id, $new_data)
    {
    }
     /**
=======
     * @param  string $username 用户名
     * @param  string $password 用户密码
     * @param  string $remember 记住我
     * @return array('status', 'msg')
     */
    public function login($username, $password, $remember)
    {
        if ($this->password != encrypt($password)){
            return array('status' => -1, 'msg' => '密码错误');
        } else {
            //设置session
            Session::set('user_id', $this->user_id);
            Session::set('user_name', $this->user_name);
            Session::set('email', $this->email);            
            //设置cookie
            if ($remember == 'on'){
                Cookie::set('user_id', $this->user_id);
                Cookie::set('user_name', $this->user_name);
            }
            // 更新用户信息
            update_user_info(); 
            // 重新计算购物车中的商品价格：目的是当用户登录时享受会员价格，当用户退出登录时不享受会员价格
            recalculate_price();     
            return array('status' => 1, 'msg' => '登录成功');
        }    
    }
     /**
     * @desc   第三方用户登录
     * @access public
     * @param  array $data
     * @return array('status', 'msg')
     */
    public function thirdLogin($data = array())
    {
    }
     /**
     * @desc   修改密码
     * @access public
     * @param  string $user_id
     * @param  string $new_password
     * @return array('status', 'msg')
     */
    public function editPass($user_id, $new_password)
    {
    }
     /**
     * @desc   修改用户资料
     * @access public
     * @param  string $user_id
     * @param  string $new_data
     * @return array('status', 'msg')
     */
    public function editUser($user_id, $new_data)
    {
    }
     /**
>>>>>>> 729c57105f77bfb5024a3d8660591ccdf57386ce
     * @desc   用户登出
     * @access public
     * @param  null
     * @return void
     */
    public function logout()
    {
        //删除session
        Session::delete('user_id');
        Session::delete('user_name');
        Session::delete('email');
        //删除cookie
        Cookie::delete('user_id');
        Cookie::delete('user_name');
    }
     /**
     * @desc    取得当前用户信息
     * @access  public
     * @param   int      $user_id
     * @return  array('status', 'msg')
     */
    public function getUserInfo($user_id)
    {
        
    }
     /**
     * @desc    取得当前用户的最后一笔订单信息
     * @access  public
     * @param   int      $user_id
     * @return  array
     */
    public function getLastOrder($user_id)
    {
        
    }
    /**
     * @desc    取得用户等级信息
     * @access  public
     * @return  正常情况：array('rank_name', 'next_rank_name','next_rank') 
     *          特殊等级：array('rank_name')  
     *          获取失败：array()
     */
    public function getUserRank()
    {
        $user_rank = Session::get('user_rank');
        
        if (!empty($user_rank)) {            
            //根据Session中的user_rank获取用户的等级名称
            $row = Db::table('tp_user_rank')
                ->where('rank_id',$user_rank)
                ->field('rank_name, special_rank')
                ->find();
            
            //获取用户等级信息失败
            if (empty($row)) {
                return array();
            }
            $rank_name = $row['rank_name'];
            
            //用户等级为特殊等级直接返回等级名称
            if ($row['special_rank']) {
                return array('rank_name' => $rank_name);
            } else {
                //获取当前用户等级的下一级信息
                $user_rank = $this->user_rank;
                $res = Db::table('tp_user_rank')
                    ->where('min_points', '>', $user_rank)
                    ->field('rank_name, min_points')
                    ->order('min_points')
                    ->limit(1)
                    ->find();
                $next_rank_name = $res['rank_name']; //下一等级的等级名称
                $next_rank = $res['min_points'] - $user_rank; //距离下一等级还差多少积分
                return array('rank_name'=>$rank_name, 'next_rank_name'=>$next_rank_name, 'next_rank'=>$next_rank);
            }
            
        } else {
            return array();
        }
    }
     /**
     * @desc    取得当前用户账户资金记录
     * @access  public
     * @param   int      $user_id
     * @return  array
     */
    public function getAccountLog($user_id)
    {
    }
     /**
     * @desc    取得当前用户的优惠券信息
     * @access  public
     * @param   int      $user_id
     * @return  array
     */
    public function getCoupon($user_id)
    {
    }
     /**
     * @desc    取得当前用户的商品收藏列表
     * @access  public
     * @param   int      $user_id
     * @return  array
     */
    public function getGoodsCollect($user_id)
    {
    }
     /**
     * @desc    取得当前用户评论信息
     * @access  public
     * @param   int      $user_id
     * @return  array
     */
    public function getComment($user_id)
    {
    }
    /**
     * @desc   查询用户手机是否存在
     * @access public
     * @param  string   $user_phone     电话号码
     * @return array
     */
    public function checkUserPhoneExist($user_phone)
    {
        $result = array();                      
        $map = array();
        $map['mobile_phone']    = $user_phone;        
        $result = $this->where($map)->select();

        if (empty($result)) {
            $result = array('status' => 1,  'msg' => '电话号码未被占用');          //电话号码未被占用，返回状态码1
        } else {
            $result = array('status' => -1,  'msg' => '电话号码已占用');           //电话号码已占用，返回状态码-1
        }       
        return $result ;
    }
     /**
     * @desc   查询登录名是否存在
     * @access public
<<<<<<< HEAD
     * @param  string   $user_phone     用户名
=======
     * @param  string   $user_phone     电话号码
     * @param  string   $user_id        用户id(默认为0)
>>>>>>> 729c57105f77bfb5024a3d8660591ccdf57386ce
     * @return array
     */
    public function checkUserNameExist($username)
    {
        $result = array();
        $map = array();
<<<<<<< HEAD
        $map['user_name']    = $username;
        $result = $this->where($map)->select();
        
        if (empty($result)) {
            $result = array('status' => 1,  'msg' => '登录名未被占用');          //登录名未被占用，返回状态码1
        } else {
            $result = array('status' => -1,  'msg' => '登录名已占用');           //登录名已占用，返回状态码-1
        }
        return $result;       
=======
        $map['flag']        = 1;
        $map['username']    = $username;
        $result = $this->where($map)->selsct();
        
        if (!$result) {
            $result = array('status' => 1,  'msg' => '登录名未被占用');          //登录名未被占用，返回状态码1
        } else {
            $result = array('status' => -1,  'msg' => '登录名已占用');           //登录名已占用，返回状态码-1
        }       
>>>>>>> 729c57105f77bfb5024a3d8660591ccdf57386ce
    }
}
