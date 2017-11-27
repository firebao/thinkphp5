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
     * @param  null
     * @return array('status', 'msg')
     */
    public function regist()
    {
       $request = Request::instance();
       $user_name = $request->post('username/s', '');
       $mobile_phone = $request->post('phone/s', '');
       $password = $request->post('password/s', '');
       $confirm_password = $request->post('confirm_password/s', '');
       
       //验证用户名、手机号码、密码是否为空
       if(!$user_name || !$password || !$mobile_phone) {
           return array('status' => -1, 'msg'=>'请输入用户名/手机号码/密码');
       }
       //验证两次密码是否匹配
       if($confirm_password != $password) {
           return array('status'=>-1, 'msg'=>'两次输入密码不一致');
       }
       //验证是否存在用户名
       if(get_user_info($user_name, 1) || get_user_info($mobile_phone, 2)) { 
           return array('status'=>-1,'msg'=>'账号已存在');
       }
       //注册数据save
       $this->user_name = $user_name;
       $this->mobile_phone = $mobile_phone;      
       $this->password = encrypt($password);
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
    
    public function login($username, $password, $remember)
    {
        if ($this->password != md5($password)){
            return false;
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
            update_user_info();      // 更新用户信息
            recalculate_price();     // 重新计算购物车中的商品价格：目的是当用户登录时享受会员价格，当用户退出登录时不享受会员价格
            return true;
        }    
    }
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
     * 取得用户等级信息
     * @access  public
     * @return  正常情况：array('rank_name', 'next_rank_name','next_rank') 
     *          特殊等级：array('rank_name')  
     *          获取失败：array()
     */
    public function get_user_rank()
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
     * 查询用户手机是否存在
     * @access public
     * @param  string   $user_phone     电话号码
     * @param  string   $user_id        用户id(默认为0)
     * @return array
     */
    public function check_user_phone_exist($user_phone, $user_id = 0)
    {
        $result = array();
        $request = Request::instance();
        $user_id = $user_id > 0 ? $user_id : $request->post('userId/d');
               
        $map = array();
        $map['flag']            = 1;
        $map['mobile_phone']    = $user_phone;        
        $result = $this->where($map)->select();

        if (!$result) {
            $result = array('status' => 1,  'msg' => '电话号码未被占用');          //电话号码未被占用，返回状态码1
        } else {
            $result = array('status' => -1,  'msg' => '电话号码已占用');           //电话号码已占用，返回状态码-1
        }       
        return $result ;
    }
    
}
