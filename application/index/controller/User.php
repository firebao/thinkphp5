<?php
// +----------------------------------------------------------------------
// | WeiDo 用户中心控制器 UserController
// +----------------------------------------------------------------------
// | Copyright (c) 2015  All rights reserved.
// +----------------------------------------------------------------------
// | @Author: 围兜工作室 <318348750@qq.com>
// +----------------------------------------------------------------------
// | @Version: v1.0
// +----------------------------------------------------------------------
// | @Desp: 实现围兜网用户相关的服务接口
// +----------------------------------------------------------------------
namespace app\index\controller;

use think\Config;
use app\index\model\User as UserModel;
use think\Request;
use think\Session;
use app\index\model\LogSms;
use think\captcha\Captcha;
use think\Response;



class User extends Bace
{
    public $user_id = 0;        //用户id
    public $user    = array();  //用户信息
    
    /**
     * @desc   User控制器初始化
     * @access public
     * @param  null
     * @return null
     */
    public function _initialize()
    {
        parent::_initialize();
        //推荐
        $affiliate = unserialize(Config::get('site.affiliate'));
        $this->assign('affiliate', $affiliate); 
        $request = Request::instance();
        $action = $request->action();
        //不需要登录的操作或自己验证是否登录(如ajax处理)的action
        $not_login_arr = array(
            'login',
            'register',
            'action_register',
            'get_password',
            'send_pwd_email',
            'password', 
            'signin', 
            'add_tag', 
            'collect', 
            'return_to_cart', 
            'logout', 
            'email_list', 
            'validate_email', 
            'send_hash_mail', 
            'order_query', 
            'is_register', 
            'check_email',
            'clear_history',
            'qpassword_name', 
            'get_passwd_question',            
            'check_answer',
            'get_verify',
            'get_phone_verify',
            'get_phone_verify_code'
        );
        //user控制器的action列表 
        $ui_arr = array(
            'index',
            'register',
            'action_register',
            'login', 
            'profile', 
            'order_list', 
            'order_detail', 
            'address_list', 
            'collection_list',
            'message_list', 
            'tag_list', 
            'get_password', 
            'reset_password', 
            'booking_list', 
            'add_booking', 
            'account_raply',
            'account_deposit', 
            'account_log', 
            'account_detail', 
            'act_account', 
            'pay', 
            'default', 
            'bonus', 
            'group_buy', 
            'group_buy_detail', 
            'affiliate', 
            'comment_list',
            'validate_email',
            'track_packages', 
            'transform_points',
            'qpassword_name', 
            'get_passwd_question', 
            'check_answer',            
            'delivery_info',
            'get_verify',
            'get_phone_verify',
            'get_phone_verify_code'
        );

        //用户未登录，控制器action权限判断
        if (empty(Session::get('user_id'))) {                      

            //控制器操作不在未登录允许操作列表中
            if (!in_array($action, $not_login_arr)) {              
                if (in_array($action, $ui_arr)) {
                    //如果需要登录,并是显示页面的操作，记录当前操作，用于登录后跳转到相应操作
                    $this->back_url =  $request->url();
                    //跳转到登录界面
                    $this->redirect('user/login');
                } else {
                    //非法操作
                    header('HTTP/1.0 404 Not Found');
                    header('Content-Type:text/html; charset=utf-8');
                    die('非法操作');
                }
            }
        }
        //如果需要显示页面，对页面变量进行赋值
        if (in_array($action, $ui_arr)) {
            $this->assign_template();
            //$this->assign('ur_here', $this->assign_ur_here(0, '用户中心'));
            $this->assign('car_off', Config::get('site.anonymous_buy'));

            //是否显示积分兑换 
            if (!empty(Config::get('points_rule')) && unserialize(Config::get('points_rule'))) {
                $this->assign('show_transform_points', 1);
            }
            $this->assign('helps', get_shop_help());        // 网店帮助
        }
        
    }
    /**
     * @desc    用户注册页面显示
     * @access  public
     * @param   null
     * @return  mixed 
     */
    public function register()
    {
        //用户已经登录，跳转到用户中心界面
        if ($this->user_id > 0) {
            $this->redirect('index/user/index');
        }
        //导航栏
        $this->assign('ur_here', $this->assign_ur_here(0, '用户中心'));    
        //注册是否关闭
        $this->assign('shop_reg_closed', Config::get('site.shop_reg_closed'));
        
        return $this->fetch();
    }
    /**
     * @desc    ajax数据用户注册
     * @access  public
     * @param   null
     * @return  mixed 
     */
    public function action_register()
    {
        
        $request = Request::instance();       

        if ($request->isAjax()) {
            
            if (!Config::get('site.shop_reg_closed')) {  //关闭注册
                
                return "<div>非法操作:注册功能已关闭！</div>";
                
            } else {

                $user   = new UserModel();
                $sms    = new LogSms();
                $result = array();
                $check_phone_code_result = array();
                $session_id = session_id();             //会话ID
                
                //验证手机验证码是否正确
                $check_phone_code_result = $sms->check_sms();
                if ($check_phone_code_result['status'] != 1)    return $check_phone_code_result;
                
                //模型层用户注册
                $result = $user->regist();
                
                    if ($result['status'] > 0) {//注册成功
                        //加载用户信息
                        $user = $user->user_id;
                        if (!empty($user)) {
                            session('user_id', $user);
                        }
                
                    }
            }
            return $result;                                                 
        }
    }
    //验证用户注册邮箱
    public function validate_email()
    {
        $request = Request::instance();
        $hash = $request->get('hash', '', 'trim');
        if ($hash) {
            //hash解码
            $id = register_hash('decode', $hash);
            if ($id > 0) {
                $user = UserModel::get($id);
                $user->is_validated = 1;
                $user->save();
                $this->success('激活成功',url('user/index'));
                //show_message(sprintf($_LANG['validate_ok'], $row['user_name'], $row['email']),$_LANG['profile_lnk'], 'user.php');
            }
        }
        $this->error('激活失败');
        //show_message($_LANG['validate_fail']);
    }
    //用户登录页面
    public function login()
    {
        $request = Request::instance();
        //登录请求处理
        if ($request->isAjax()){
            //请求变量处理
            $username = $request->post('user_name', '', 'trim');
            $password = $request->post('password', '' , 'trim');
            
            //验证码处理
            $captcha = Config::get('site.captcha');           
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && Session::get('login_fail') > 2))) {
                if (!$request->has('captcha', 'post', true)) {
                    $this->error('验证码不能为空！');                    
                }   
                //判断验证码是否正确
                if (!captcha_check($request->post('captcha'))) {
                    $this->error('验证码错误!');
                }
            }
            //用户登录操作
            $user = UserModel::get(['user_name' => $username]);
            if ($user->login($username, $password, $request->has('remember_me','post'))) {
                $this->success('登录成功', 'user/index');
            } else {
                $_SESSION['login_fail'] ++ ;
                $this->error('登录失败');
            }
        //登录界面显示
        } else { 
            if (empty($this->back_act)) {
                if (empty($this->back_act) && isset($_SERVER['HTTP_REFERER'])) {
                    $this->back_url = strpos($_SERVER['HTTP_REFERER'], 'user.html') ? url('user/index') : $_SERVER['HTTP_REFERER'];
                } else {
                    $this->back_act = url('user/index');
                }
            }        
            
            //验证码相关设置
            $captcha = intval(Config::get('site.captcha'));
            $this->assign('enabled_captcha', 0);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                $this->assign('enabled_captcha', 1);
            }
            //模板变量赋值
            $this->assign('back_act', $this->back_act);
            return $this->fetch();  
        }  
    }
    public function index()
    {
        $user = UserModel::get(Session::get('user_id'));
        $rank_info = $user->get_user_rank();
        if ($rank_info) {
            $this->assign('rank_name', sprintf('您的等级是 %s ', $rank_info['rank_name']));
            if (!empty($rank['next_rank_name'])) {
                $this->assign('next_rank_name', sprintf(',您还差 %s 积分达到 %s', $rank_info['next_rank'], $rank_info['next_rank_name']));
            }
        }
        $this->assign('rank_name',111);
        //$this->assign('info',        get_user_default($user_id));
        //$this->assign('user_notice', $_CFG['user_notice']);
        //$this->assign('prompt',      get_user_prompt($user_id));
        return $this->fetch();
    }
    /**
     * @desc    判断用户名是否已经被注册
     * @access  public
     * @param   null
     * @return  bool  true：未被注册  false：已被注册
     */
    public function is_register()
    {
        if (input("?username") ) {
            
            $username   = input("username/s");
            $user       = new UserModel();
            $map        = array('user_name' => $username);
            $result     = $user->get($map);
            
            if ($result) {
                return false;
            } else {
                return true;
            }
        }
    }
    public function pay()
    {
        echo 'pay';
    }
    public function logout()
    {
        if (empty($this->back_url) && isset($_SERVER['HTTP_REFERER'])) {
            $this->back_url = strpos($_SERVER['HTTP_REFERER'], 'user.html') ? url('user/index') : $_SERVER['HTTP_REFERER'];
        }
        $user = UserModel::get(Session::get('user_id'));
        $user->logout();
        $this->success('退出登录成功', url('index/index'));
    }
    public function profile()
    {
        $user = UserModel::get(Session::get('user_id'));
        $this->assign('user', $user);
        return $this->fetch();
    }
    public function favorite()
    {
        echo "我的收藏";
    }
    public function order()
    {
        echo "我的订单";
    }
    /**
     * 手机验证码获取(找回密码操作)
     * @access public
     * @param
     * @return ajax
     */
    public function get_phone_verify()
    {        
        if(session('findPass.userPhone') == '') {            
            return array('status' => -1);            
        }
        
        $phone_verify = mt_rand(100000,999999);
        
        $user = session('findPass');
        $user['phoneVerify'] = $phone_verify;
        
        session('findPass', $user);
        $msg = "亲爱的围兜网用户，您的验证码为:".$phone_verify."，请在30分钟内输入.【围兜网】"; 

        $sms = new LogSms();
        $sms->sendSMS(0, session('findPass.userPhone'), $msg, 'getPhoneVerify', $phone_verify);
        $rv['time']=120;
        
        return $rv;
    }
    /**
     * @desc    手机获取验证码
     * @access  public
     * @param   null
     * @return  array('Status', 'msg')
     */
    public function get_phone_verify_code()
    {
        //变量定义
        $res        = array();
        $user       = new UserModel();
        $sms        = new LogSms();
        $request    = Request::instance();
        $user_phone = $request->post('sUserPhone/s', '', 'trim');
        $regexp     = "/^((1[3,5,8][0-9])|(14[5,7])|(17[0,6,7,8])|(19[7]))\d{8}$/";
        
        
        //验证手机号码格式是否正确       
        if (!preg_match($regexp, $user_phone)) {
            return array('status' => -1, 'msg' => '手机号格式不正确!');
        }
        
        //检查手机号码是否已注册
        $res = $user->check_user_phone_exist($user_phone, Session::get('user_id/d'));
        if ($res["status"] != 1) {
            return array('status' => -2, 'msg' => '手机号码已注册!');           
        }
        //生成手机验证码
        $phone_verify = rand(100000, 999999);
        $msg = "亲爱的围兜网用户，您的验证码为:".$phone_verify."，请在30分钟内输入.【围兜网】";
        
        //发送短信验证码        
        $result = $sms->send_sms($user_phone, $msg, $phone_verify);
  
        return $result;
    }
    /**
     * @desc    产生验证码图片
     * @access  public
     * @param   null
     * @return  Response
     */
    public function get_verify()
    {
        $captcha = new Captcha();
        
        $captcha->length = 4;               //验证码长度
        $captcha->codeSet = '0123456789';   //验证码字符集合
        $captcha->useCurve = false;         //背景线
        
        return $captcha->entry();
    }
}