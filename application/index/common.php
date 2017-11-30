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
// | @Desp: index模块公共函数库
// +----------------------------------------------------------------------
use think\Db;
use think\Request;
use think\Cookie;
use think\Session;
use think\Config;
use think\View;
use app\index\model\User;
use think\helper\hash\Md5;

/**
 * @desc   中国网建sms接口
 * @access public
 * @param  string  $phone_numer  目的手机号码
 * @param  string  $content      短信内容
 * @return integer 状态码                        >0成功 <0失败
 */
function send_sms($phone_numer, $content)
{
    //API接口
    $url = 'http://utf8.sms.webchinese.cn/?Uid='.Config::get('site.sms_key').'&Key='.Config::get('site.sms_pass').'&smsMob='.$phone_numer.'&smsText='.$content;
    
    //初始化
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    //将curl_exec()获取的信息以字符串返回，而不是直接输出。 
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30 );  //设置连接等待时间
    curl_setopt($ch, CURLOPT_ENCODING, "gzip" );    //Accept-Encoding
    
    //执行命令
    $data = curl_exec($ch);
    
    //关闭url请求
    curl_close($ch);
    
    return $data;
}
/**
 * @desc   获取用户信息
 * @access public
 * @param  string  $user_id_or_name  用户id或者用户名
 * @param  integer $type             查找类型:0(默认)通过user_id查找,1通过user_name查找，
 *                                          2通过mobile_phone查找,3通过第三方唯一标示查找，4通过微信unionid查找
 * @param  string  $oauth            第三方来源：''(默认)
 * @return mixed                     成功返回用户记录  失败返回null
 */
function get_user_info($user_id_or_name, $type = 0, $oauth = '')
{      
    //根据不同情况初始化查询条件
    $map = array();
    if($type == 0) $map['user_id']      = $user_id_or_name;     //通过user_id查找
    if($type == 1) $map['user_name']    = $user_id_or_name;     //通过user_name查找
    if($type == 2) $map['mobile_phone'] = $user_id_or_name;     //通过mobile_phone查找
    if($type == 3){                                             //通过第三方唯一标示查找
        $map['openid'] = $user_id_or_name;
        $map['oauth']  = $oauth;
    }
    if($type == 4){                                             //通过微信unionid查找
        $map['unionid'] = $user_id_or_name;
        $map['oauth']   = $oauth;
    }
    //数据表查询
    $user = Db::table('tp_users')->where($map)->find();
    return $user;   
}
/**
 * @desc   用户密码加密
 * @access public
 * @param  string  $str              用户密码（明文）
 * @return string  $str              Md5加密后的密码（密文）
 */
function encrypt($str){
    return md5(Config::get("AUTH_CODE").$str);
}
/**
 * @desc   获取网站商品的分类树信息
 * @access public
 * @param  null
 * @return array  分类树数组              
 */
function weido_get_goods_category_tree(){
}
