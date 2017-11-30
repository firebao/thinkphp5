<?php
// +----------------------------------------------------------------------
// | WeiDo LogSms短信服务模型
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | @Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
// | @Version: v1.0
// +----------------------------------------------------------------------
// | @Desp: 实现围兜网注册等界面短信验证功能
// +----------------------------------------------------------------------
namespace app\index\model;

use think\Model;
use think\Config;
use think\Request;
use think\Session;

class LogSms extends Model 
{
	
    protected $table = "tp_log_sms";   //数据表
    protected $autoWriteTimestamp = 'datetime';
	
	/**
	 * @desc   发送短信，并在数据表中建立发送短讯记录
	 * @access public
	 * @param  mixed  $phone_number  目的手机号码
	 * @param  mixed  $content       短信内容
	 * @param  mixed  $verfy_code    验证码  
	 * @return array  {'status', 'msg'}  
	 */
	public function sendSms($phone_number, $content, $verfy_code)
	{
		//变量定义,请求参数，user_id,用户ip
	    $request = Request::instance();
		$user_id = Session::get('user_id');
		$ip = real_ip();
		
	    //检测网站配置项是否打开短信验证码验证是否正确
	    if (Config::get('site.send_sms_verfy') == 1) {
	        
	    	$sms_verfy = $request->post('sSmsVerfy/s','');	    	   
	    	if (!captcha_check($sms_verfy)) {
	    	    return array('status'=>-29999, 'msg'=>'验证码不正确!');
	    	};
			
		}
		
		//检测是否超过每日短信发送数
		$map      = array();
		$date     = date('Y-m-d');		
		$map['sms_phone_number']  = $phone_number;
		$map['create_time']       = ['>', $date.'00:00:00'];
		$map['create_time']       = ['<=', $date.'23:59:59'];
			
		$sms_result = $this->where($map)->field('count(sms_id) as counts, max(create_time) as create_time')->find();
		//号码每日发送数不能超过设定值（20条）,避免恶意浪费短信
		if ($sms_result->getAttr('counts') > (int)Config::get('site.sms_limit')){		    
			return array('status' => -20000, 'msg' => '请勿频繁发送短信验证!');
		}
		//相同号码发送验证码2分钟只能点击发送1次
		if ($sms_result->getAttr('create_time') != '' && ((time() - strtotime($sms_result->create_time)) < 120)){		    
			return array('status' => -20001, 'msg' => '请勿频繁发送短信验证!');
		}
		
		//检测IP是否超过发短信次数
		$map = array();
		$map['sms_ip']            = $ip;
		$map['create_time']       = ['>', $date.'00:00:00'];
		$map['create_time']       = ['<=', $date.'23:59:59'];
		$ip_result = $this->where($map)->field('count(sms_id) as counts, max(create_time) as create_time')->find();
	    //相同ip每日发送数不能超过设定值（20条）,避免恶意浪费短信
		if ($ip_result->getAttr('counts') > (int)Config::get('site.sms_limit')){		    
			return array('status' => -20003, 'msg' => '请勿频繁发送短信验证!');
		}
		//相同ip发送验证码2分钟只能点击发送1次
		if ($ip_result->getAttr('create_time') != '' && ((time() - strtotime($ip_result->getAttr('create_time'))) < 120)){		    
			return array('status' => -20004, 'msg' => '请勿频繁发送短信验证!');
		}
		
		//发送短信验证码
		$code = send_sms($phone_number, $content);
		
		//数据表添加短信发送记录
	    $data = array();
		$data['sms_user_id']       = $user_id;                //短信发送用户id
		$data['sms_phone_number']  = $phone_number;           //目标手机号
		$data['sms_content']       = $content;                //短信内容
		$data['sms_return_code']   = $code;                   //网建API返回的状态码
		$data['sms_code']          = $verfy_code;             //短信发送的验证码
		$data['sms_ip']            = $ip;                     //短息发送用户的ip地址
		$data['create_time']       = date('Y-m-d H:i:s');     //短信发送时间
		$this->data($data);
		$this->save();
		
		if (intval($code) > 0) {
			return array('status' =>  1, 'msg' => '短信发送成功!');
		} else {
			return array('status' => -1, 'msg' => '短信发送失败!');
	    }
	}
	/**
	 * @desc   验证短信验证码是否有效
	 * @access public
	 * @param  null
	 * @return array {'status', 'msg'}
	 */
	public function checkSms()
	{
	    $result    = array();
	    $request   = Request::instance();
	    $mobile    = $request->post('phone');
	    $code      = $request->post('phone-code/s');	    
	    $now_time  = date('Y-m-d H:i:s');
        $map = array();
	    $map['sms_phone_number'] = $mobile;    //手机号码
	    $map['sms_return_code'] = 1;           //短信发送成功标志
	    $result = $this->where($map)->order('sms_id DESC')->find();
	    if ($result) {
	        $sms_create_time   = $result->create_time;                  //验证码发送时间
	        $sms_code          = $result->sms_code;                     //手机验证码
	        $flag              = $this->check_time($now_time, $sms_create_time);
	        
	        if (!$flag) {
	            return array('status' => '-1', 'msg' => '验证码过期，请刷新后重新获取');	           
	        }	
	        if ($code != $sms_code) {
	            return array('status' => '-2', 'msg' => '验证码错误，请重新输入');
	        }
	        return array('status' => '1', 'msg' => '验证通过');
	    } else {
	        return array('status'=> '-2', 'msg' => '验证码错误，请重新输入');
	    }
    }
    /**
     * @desc    验证验证码时间是否过期
     * @access  public
     * @param   string  $now_time_str       现在的时间  如：'2017-10-15 13:29:55'
     * @param   string  $sms_code_time_str  验证码发送的时间 如：'2017-10-15 14:30:00'
     * @return  bool    true：未过期    false：已过期
     */
	public function check_time($now_time_str, $sms_code_time_str)
	{
	    $now_time      = strtotime($now_time_str);
	    $sms_code_time = strtotime($sms_code_time_str);
	    $period        = floor(($now_time - $sms_code_time) / 60);     //60s
	    
	    if ($period >= 0 && $period <= 30) {                           //过期时间设置为30分钟
	        return true;
	    } else {
	        return false;
	    }
	}
}