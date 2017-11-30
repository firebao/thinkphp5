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
use think\Db;
use SebastianBergmann\GlobalState\CodeExporter;
use think\Validate;
use app\index\model\Cart;



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
        $request = Request::instance();
        $action = $request->action();
        //判断用户是否已经登录
        if (session('?user')) {
            $user = session('user');
            $user = Db::table('tp_users')->where("user_id = {$user['user_id']}")->find();   //从数据库更新用户信息
            session('user', $user);
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user);
            $this->assign('user_id', $this->user_id);
        } else {
             //不需要登录的操作或自己验证是否登录(如ajax处理)的action
            $not_login_arr = array(
                'login', 'action_login', 'register','action_register',
                'get_phone_verify','get_phone_verify_code','get_verify', 
                'index', 'is_register', 'phone_is_register', 
                );
            //用户未登录，判断控制器操作访问权限,没有权限访问登录操作
            if (!in_array($action, $not_login_arr)) {
                $this->redirect('user/login');
            }
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
        //注册是否关闭,如果注册关闭html页面显示注册关闭页面
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
            //关闭注册
            if (!Config::get('site.shop_reg_closed')) {                  
                return "<div>非法操作:注册功能已关闭！</div>";                
            } else {
                //表单数据后台验证
                $data = array();
                $data['username'] = input('username/s', '');
                $data['phone'] = input('phone/s', '');
                $data['password'] = input('password/s', '');
                $data['confirm_password'] = input('confirm_password/s', '');
                $result = $this->validate($data, 'User');
                //表单数据验证失败
                if ($result !== true) {
                    $this->error($result);
                }
                //创建用户模型对象，sms模型对象
                $user = new UserModel();
                $sms = new LogSms();                
                //验证手机验证码是否正确
                $check_phone_code_result = $sms->checkSms();
                if ($check_phone_code_result['status'] != 1)    
                    return $check_phone_code_result;              
                //用户模型层用户注册
                $result = $user->regist($data);
                    //注册成功
                    if ($result['status'] > 0) {
                        //加载用户信息
                        $user_id = $user->user_id;
                        if (!empty($user_id)) {
                            //设置session
                            session('user_id', $user_id);
                            session('user', $user->toArray());
                        }
                        $this->success('注册成功', url('index/user/index'));
                    }
            }
            return $result;                                                 
        }
    }
 
     /**
     * @desc    用户登录页面显示
     * @access  public
     * @param   null
     * @return  mixed 
     */
    public function login()
    {
        //用户已经登录，跳转到用户中心界面
        if ($this->user_id > 0) {
            $this->redirect('index/user/index');
        }
        $ct = new Captcha();
        //记录执行登录操作页面，以便登录成功后页面跳转
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url("index/user/index");
        $this->assign('referurl',$referurl);
        return $this->fetch();
    }
      /**
     * @desc    ajax数据用户登录
     * @access  public
     * @param   null
     * @return  mixed 
     */
    public function action_login()
    {
        $data = array();
        $data['username'] = trim(input('post.username/s'));
        $data['password'] = trim(input('post.password/s'));
        $remember = trim(input('post/remember/s', ''));
        $captcha = input('post.captcha/s');    	
    	//判断验证码是否正确
    	$ct = new Captcha();
    	if (!$ct->check($captcha)) {
    	   $this->error('验证码错误');
    	}
    	//模型层用户登录
    	$user = new UserModel();
    	$result = $user->login($data);
    	//模型层登录成功
    	if($result['code'] == 1){
    	    $login_user = $result['result'];
    	    //设置session
    	    $user_id = $login_user['user_id'];
    	    if (!empty($user_id)) {
    	        //设置session
    	        session('user_id', $user_id);
    	        session('user', $login_user->toArray());
    	    }
    	    //用户勾选记住我，设置cookie
            if ($remember == 'on'){
                cookie('user_id', $user_id);
                cookie('user_name', $login_user['user_name']);
                cookie('password', $login_user['password']);
            }
    	    //cookie设置用户昵称
    	    $nickname = empty($login_user['nick_name']) ? $login_user['user_name'] : $login_user['nick_name'];
    	    cookie('nick_name', $nickname);
            //更新购物车信息
    	    $cart = new Cart();
    	    $cart->setUserId($user_id);
    	    $cart->doUserLoginHandle(); 
    	    //跳转到前页面
    	    $this->success('登录成功',input('post.referurl'));
    	} else {
    	    return $result;
    	}
    	
    }
    public function index()
    {       
        dump(Session::get());
        return '用户中心';
    }

    /**
     * @desc    用户登出操作
     * @access  public
     * @param   null
     * @return  mixed
     */
    public function logout()
    {
        if (empty($this->back_url) && isset($_SERVER['HTTP_REFERER'])) {
            $this->back_url = strpos($_SERVER['HTTP_REFERER'], 'user.html') ? url('user/index') : $_SERVER['HTTP_REFERER'];
        }
        $user = UserModel::get(Session::get('user_id'));
        $user->logout();
        $this->success('退出登录成功', url('index/index'));
    }
     /**
     * @desc    我的收藏页面
     * @access  public
     * @param   null
     * @return  mixed
     */
    public function favorite()
    {
        echo "我的收藏";
    }
     /**
     * @desc    订单列表页面
     * @access  public
     * @param   null
     * @return  mixed
     */
    public function orderList()
    {
        echo "我的订单";
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
            $result     = $user->checkUserNameExist($username);
            if ($result['status'] == 1) {
                return true;
            } else {
                return false;
            }
        }
    }
    /**
     * @desc    判断手机号码是否已经被注册
     * @access  public
     * @param   null
     * @return  bool  true：未被注册  false：已被注册
     */
    public function phone_is_register()
    {
        if (input("?phone") ) {
   
            $phone      = input("phone/s");
            $user       = new UserModel();
            $result = $user->checkUserPhoneExist($phone);
            if ($result['status'] == 1) {
                return true;
            } else {
                return false;
            }
    
        }
    }
    /**
     * @desc    手机获取验证码
     * @access  public
     * @param   null
     * @return  array('status', 'msg')
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
        $res = $user->checkUserPhoneExist($user_phone);
        if ($res["status"] != 1) {
            return array('status' => -2, 'msg' => '手机号码已注册!');           
        }
        //生成手机验证码
        $phone_verify = rand(100000, 999999);
        $msg = "亲爱的围兜网用户，您的验证码为:".$phone_verify."，请在30分钟内输入.【围兜网】";
        
        //发送短信验证码        
        $result = $sms->sendSms($user_phone, $msg, $phone_verify);
  
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
