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
// | @Desp: 应用模块application公共函数库
// +----------------------------------------------------------------------
use think\Request;
use think\Cookie;
use think\Config;
use think\Db;
use think\Session;

/**
 * @desc  判断用户是否是移动端访问网站
 * @access public
 * @param  null
 * @return bool true:是移动端  false:不是移动端
 */
function weido_is_mobile() 
{
    //所有http请求信息
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
    
    //如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;
    
    //如果有HTTP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_PROFILE']))
        return true;
    
    //粗略判断手机发送的客户端标识
    $preg_exp = '/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i';    
    if (preg_match($preg_exp, strtolower($_SERVER['HTTP_USER_AGENT'])))      
        return true;
    //详细判断手机发送的客户端标识
    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
    $mobile_agents = array(
        'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
        'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
        'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
        'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
        'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
        'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
        'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
        'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
        'wapr','webc','winw','winw','xda','xda-'
    );
    if (in_array($mobile_ua, $mobile_agents))
        return true;
    
    //判断http协议,如果只支持wml并且不支持html那一定是移动设备，如果支持wml和html但是wml在html之前也是移动设备
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'vnd.wap.wml') !== false) &&
            ((strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false) || 
            (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml')) < (strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
            return true;
    }
          
    return false;
}
/**
 * @desc    统计访问信息，并将访问信息录入数据库
 * @access  public
 * @param   null
 * @return  void
 */
function visit_stats()
{

    //网站配置中的统计访问信息关闭则直接返回
    if (Config::has('site.visit_stats') && Config::get('site.visit_stats') == 'off'){
        return;
    }
    
    //获取当前时间
    $time = time();
    
    //检查客户端是否存在访问统计次数的cookie 
    $visit_times = Cookie::has('visit_times') ? Cookie::get('visit_times') + 1 : 1;
    Cookie::set('visit_times', $visit_times, $time + 86400 * 365);
    $browser  = get_user_browser();     //浏览器信息
    $os       = get_os();               //操作系统信息
    $ip       = real_ip();              //ip地址
    $area     = geoip($ip);             //ip地址对应的物理地址

    //浏览器访问语言
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $pos  = strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], ';');
        $lang = addslashes(($pos !== false) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, $pos) : $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    } else {
        $lang = '';
    }
    //访问来源
    if (!empty($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 9) {
        $pos = strpos($_SERVER['HTTP_REFERER'], '/', 9);
        if ($pos !== false) {
            $domain = substr($_SERVER['HTTP_REFERER'], 0, $pos);
            $path   = substr($_SERVER['HTTP_REFERER'], $pos);
            //保存搜索引擎关键字
            if (!empty($domain) && !empty($path)){
                save_searchengine_keyword($domain, $path);
            }
        } else {
            $domain = $path = '';
        }
    } else {
        $domain = $path = '';
    }
    //保存统计数据到stats数据表中
    $data = array(
        'ip_address' => $ip,
        'visit_times' => $visit_times,
        'browser' => $browser,
        'system' => $os,
        'language' => $lang,
        'area' => $area,
        'referer_domain' => addslashes($domain),
        'referer_path' => addslashes($path),
        'access_url' => htmlspecialchars(addslashes($_SERVER['PHP_SELF'])),
        'access_time' => time(),
    );
    Db::table('tp_stats')->insert($data);
}


/**
 * 取得上次的过滤条件
 * @param   string  $param_str  参数字符串，由list函数的参数组成，默认为空
 * @return  mixed   array('filter' => $filter, 'map' => $map)|false
 */
if (!function_exists('get_filter')) {
    function get_filter($param_str = '')
    {
        $filter_file = basename(Request::instance()->server('PHP_SELF'), '.php');
        if ($param_str) $filter_file .= $param_str;

        //判断输入参数中有没有'use_last_filter'参数并且有没有相应的Cookie
        $last_filter_file = Cookie::get('last_filter_file');
        if (isset($_GET['use_last_filter']) 
            && (isset($last_filter_file))
            && ($last_filter_file == sprintf('%X', crc32($filter_file)))) {
            return array(
                'filter' => unserialize(urldecode($last_filter)),
                'map'    => Cookie::get('last_filter_map')
            );
        } else {
            return false;
        } 
    }
}
if (!function_exists('set_filter')) {
    /**
     * 保存过滤条件
     * @param   array   $filter     过滤条件
     * @param   array   $map        查询where数组
     * @param   string  $param_str  参数字符串，由list函数的参数组成
     */
    function set_filter($filter, $map, $param_str = '') {
        $filterfile = basename(Request::instance()->server('PHP_SELF'), '.php');
        if ($param_str) {
            $filterfile .= $param_str;
        }
        Cookie::set('last_filter_file', sprintf('%X', crc32($filterfile)), time()+600);
        Cookie::set('last_filter', urlencode(serialize($filter)), time() + 600);
        Cookie::set('last_filter_map', $map, time()+600);
    }
}

/**
 * 生成一个用户自定义时区日期的GMT时间戳
 * @access  public
 * @param   int     $hour
 * @param   int     $minute
 * @param   int     $second
 * @param   int     $month
 * @param   int     $day
 * @param   int     $year
 * @return void
 */
if (!function_exists('local_mktime')) {
    function local_mktime($hour = NULL, $minute= NULL, $second = NULL, $month = NULL, $day = NULL, $year = NULL)
    {
        $timezone = isset($_SESSION['timezone']) ? $_SESSION['timezone'] : Config::get('timezone');
        $time = mktime($hour, $minute, $second, $month, $day, $year) - $timezone * 3600;    
        return $time;
    }
}
/**
 * 获得浏览器名称和版本
 * @access  public
 * @return  string
 */
function get_user_browser()
{
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        return '';
    }

    $agent       = $_SERVER['HTTP_USER_AGENT'];
    $browser     = '';
    $browser_ver = '';

    if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)) {
        $browser     = 'Internet Explorer';
        $browser_ver = $regs[1];
    } elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)) {
        $browser     = 'FireFox';
        $browser_ver = $regs[1];
    } elseif (preg_match('/Maxthon/i', $agent, $regs)) {
        $browser     = '(Internet Explorer ' .$browser_ver. ') Maxthon';
        $browser_ver = '';
    } elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)) {
        $browser     = 'Opera';
        $browser_ver = $regs[1];
    } elseif (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs)) {
        $browser     = 'OmniWeb';
        $browser_ver = $regs[2];
    } elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs)) {
        $browser     = 'Netscape';
        $browser_ver = $regs[2];
    } elseif (preg_match('/safari\/([^\s]+)/i', $agent, $regs)) {
        $browser     = 'Safari';
        $browser_ver = $regs[1];
    } elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs)) {
        $browser     = '(Internet Explorer ' .$browser_ver. ') NetCaptor';
        $browser_ver = $regs[1];
    } elseif (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs)) {
        $browser     = 'Lynx';
        $browser_ver = $regs[1];
    }

    if (!empty($browser)) {
        return addslashes($browser . ' ' . $browser_ver);
    } else {
        return 'Unknow browser';
    }
}
/**
 * 获得客户端的操作系统
 *
 * @access  private
 * @return  void
 */
function get_os()
{
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        return 'Unknown';
    }

    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $os    = '';

    if (strpos($agent, 'win') !== false) {
        if (strpos($agent, 'nt 5.1') !== false) {
            $os = 'Windows XP';
        } elseif (strpos($agent, 'nt 5.2') !== false) {
            $os = 'Windows 2003';
        } elseif (strpos($agent, 'nt 5.0') !== false) {
            $os = 'Windows 2000';
        } elseif (strpos($agent, 'nt 6.0') !== false) {
            $os = 'Windows Vista';
        } elseif (strpos($agent, 'nt') !== false) {
            $os = 'Windows NT';
        } elseif (strpos($agent, 'win 9x') !== false && strpos($agent, '4.90') !== false) {
            $os = 'Windows ME';
        } elseif (strpos($agent, '98') !== false) {
            $os = 'Windows 98';
        } elseif (strpos($agent, '95') !== false) {
            $os = 'Windows 95';
        } elseif (strpos($agent, '32') !== false) {
            $os = 'Windows 32';
        } elseif (strpos($agent, 'ce') !== false) {
            $os = 'Windows CE';
        }
    } elseif (strpos($agent, 'linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($agent, 'unix') !== false) {
        $os = 'Unix';
    } elseif (strpos($agent, 'sun') !== false && strpos($agent, 'os') !== false) {
        $os = 'SunOS';
    } elseif (strpos($agent, 'ibm') !== false && strpos($agent, 'os') !== false) {
        $os = 'IBM OS/2';
    } elseif (strpos($agent, 'mac') !== false && strpos($agent, 'pc') !== false) {
        $os = 'Macintosh';
    } elseif (strpos($agent, 'powerpc') !== false) {
        $os = 'PowerPC';
    } elseif (strpos($agent, 'aix') !== false) {
        $os = 'AIX';
    } elseif (strpos($agent, 'hpux') !== false) {
        $os = 'HPUX';
    } elseif (strpos($agent, 'netbsd') !== false) {
        $os = 'NetBSD';
    } elseif (strpos($agent, 'bsd') !== false) {
        $os = 'BSD';
    } elseif (strpos($agent, 'osf1') !== false) {
        $os = 'OSF1';
    } elseif (strpos($agent, 'irix') !== false) {
        $os = 'IRIX';
    } elseif (strpos($agent, 'freebsd') !== false) {
        $os = 'FreeBSD';
    } elseif (strpos($agent, 'teleport') !== false) {
        $os = 'teleport';
    } elseif (strpos($agent, 'flashget') !== false) {
        $os = 'flashget';
    } elseif (strpos($agent, 'webzip') !== false) {
        $os = 'webzip';
    } elseif (strpos($agent, 'offline') !== false) {
        $os = 'offline';
    } else {
        $os = 'Unknown';
    }

    return $os;
}
/**
 * @desc    获得用户的真实IP地址
 * @access  public
 * @param   null
 * @return  string
 */
function real_ip()
{
    static $realip = NULL;

    if ($realip !== NULL) {
        return $realip;
    }

    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip) {
                $ip = trim($ip);
                if ($ip != 'unknown') {
                    $realip = $ip;
                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            } else {
                $realip = '0.0.0.0';
            }
        }
    } else
    {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }

    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

    return $realip;
}
/**
 * @desc    获得用户IP地址所属地区
 * @param   string  $ip
 * @return  string
 */
function geoip($ip)
{
    static $fp = NULL, $offset = array(), $index = NULL;

    $ip    = gethostbyname($ip);
    $ipdot = explode('.', $ip);
    $ip    = pack('N', ip2long($ip));

    $ipdot[0] = (int)$ipdot[0];
    $ipdot[1] = (int)$ipdot[1];
    if ($ipdot[0] == 10 || 
        $ipdot[0] == 127 || 
        ($ipdot[0] == 192 && $ipdot[1] == 168) || 
        ($ipdot[0] == 172 && ($ipdot[1] >= 16 && $ipdot[1] <= 31))) {
        return 'LAN';
    }

    if ($fp === NULL){
        $fp = fopen(ROOT_PATH . 'common/ipdata.dat', 'rb');
        if ($fp === false) {
            return 'Invalid IP data file';
        }
        $offset = unpack('Nlen', fread($fp, 4));
        if ($offset['len'] < 4) {
            return 'Invalid IP data file';
        }
        $index  = fread($fp, $offset['len'] - 4);
    }

    $length = $offset['len'] - 1028;
    $start  = unpack('Vlen', $index[$ipdot[0] * 4] . $index[$ipdot[0] * 4 + 1] . $index[$ipdot[0] * 4 + 2] . $index[$ipdot[0] * 4 + 3]);
    for ($start = $start['len'] * 8 + 1024; $start < $length; $start += 8) {
        if ($index{$start} . $index{$start + 1} . $index{$start + 2} . $index{$start + 3} >= $ip) {
            $index_offset = unpack('Vlen', $index{$start + 4} . $index{$start + 5} . $index{$start + 6} . "\x0");
            $index_length = unpack('Clen', $index{$start + 7});
            break;
        }
    }

    fseek($fp, $offset['len'] + $index_offset['len'] - 1024);
    $area = fread($fp, $index_length['len']);

    fclose($fp);
    $fp = NULL;

    return $area;
}
/**
 * 保存搜索引擎关键字
 * @access  public
 * @return  void
 */
function save_searchengine_keyword($domain, $path)
{
    if (strpos($domain, 'google.com.tw') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'GOOGLE TAIWAN';
        $keywords = urldecode($regs[1]); // google taiwan
    }
    if (strpos($domain, 'google.cn') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'GOOGLE CHINA';
        $keywords = urldecode($regs[1]); // google china
    }
    if (strpos($domain, 'google.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'GOOGLE';
        $keywords = urldecode($regs[1]); // google
    } elseif (strpos($domain, 'baidu.') !== false && preg_match('/wd=([^&]*)/i', $path, $regs)) {
        $searchengine = 'BAIDU';
        $keywords = urldecode($regs[1]); // baidu
    } elseif (strpos($domain, 'baidu.') !== false && preg_match('/word=([^&]*)/i', $path, $regs)) {
        $searchengine = 'BAIDU';
        $keywords = urldecode($regs[1]); // baidu
    } elseif (strpos($domain, '114.vnet.cn') !== false && preg_match('/kw=([^&]*)/i', $path, $regs)) {
        $searchengine = 'CT114';
        $keywords = urldecode($regs[1]); // ct114
    } elseif (strpos($domain, 'iask.com') !== false && preg_match('/k=([^&]*)/i', $path, $regs)) {
        $searchengine = 'IASK';
        $keywords = urldecode($regs[1]); // iask
    } elseif (strpos($domain, 'soso.com') !== false && preg_match('/w=([^&]*)/i', $path, $regs)) {
        $searchengine = 'SOSO';
        $keywords = urldecode($regs[1]); // soso
    } elseif (strpos($domain, 'sogou.com') !== false && preg_match('/query=([^&]*)/i', $path, $regs)) {
        $searchengine = 'SOGOU';
        $keywords = urldecode($regs[1]); // sogou
    } elseif (strpos($domain, 'so.163.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'NETEASE';
        $keywords = urldecode($regs[1]); // netease
    } elseif (strpos($domain, 'yodao.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'YODAO';
        $keywords = urldecode($regs[1]); // yodao
    } elseif (strpos($domain, 'zhongsou.com') !== false && preg_match('/word=([^&]*)/i', $path, $regs)) {
        $searchengine = 'ZHONGSOU';
        $keywords = urldecode($regs[1]); // zhongsou
    } elseif (strpos($domain, 'search.tom.com') !== false && preg_match('/w=([^&]*)/i', $path, $regs)) {
        $searchengine = 'TOM';
        $keywords = urldecode($regs[1]); // tom
    } elseif (strpos($domain, 'live.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'MSLIVE';
        $keywords = urldecode($regs[1]); // MSLIVE
    } elseif (strpos($domain, 'tw.search.yahoo.com') !== false && preg_match('/p=([^&]*)/i', $path, $regs)) {
        $searchengine = 'YAHOO TAIWAN';
        $keywords = urldecode($regs[1]); // yahoo taiwan
    } elseif (strpos($domain, 'cn.yahoo.') !== false && preg_match('/p=([^&]*)/i', $path, $regs)) {
        $searchengine = 'YAHOO CHINA';
        $keywords = urldecode($regs[1]); // yahoo china
    } elseif (strpos($domain, 'yahoo.') !== false && preg_match('/p=([^&]*)/i', $path, $regs)) {
        $searchengine = 'YAHOO';
        $keywords = urldecode($regs[1]); // yahoo
    } elseif (strpos($domain, 'msn.com.tw') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'MSN TAIWAN';
        $keywords = urldecode($regs[1]); // msn taiwan
    } elseif (strpos($domain, 'msn.com.cn') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'MSN CHINA';
        $keywords = urldecode($regs[1]); // msn china
    } elseif (strpos($domain, 'msn.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'MSN';
        $keywords = urldecode($regs[1]); // msn
    }

    if (!empty($keywords)) {
        $data = [
            'date' => date('Y-m-d'),
            'searchengine' => $searchengine,
            'keyword' => htmlspecialchars(addslashes($keywords)),
            'count' => ['exp', 'count+1']];
        if (!Db::table('tp_keywords')->where('keywords', $keywords)->find()){
            Db::table('tp_keywords')->insert($data);
        } else {
            Db::table('tp_keywords')->where('keywords', $keywords)->update($data);
        }              
    }
}

/**
 * 取得自定义导航栏列表
 * @param   string      $type    位置，如top、bottom、middle
 * @return  array         列表
 */
function get_navigator($ctype = '', $catlist = array())
{
   
}
/**
 * 分配帮助信息
 *
 * @access  public
 * @return  array
 */
function get_shop_help()
{
   
}
/**
 * 验证输入的邮件地址是否合法
 * @access  public
 * @param   string      $email      需要验证的邮件地址
 * @return bool
 */
function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
        if (preg_match($chars, $user_email)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}
/**
 * 重写 URL 地址
 *
 * @access  public
 * @param   string  $app        执行程序
 * @param   array   $params     参数数组
 * @param   string  $append     附加字串
 * @param   integer $page       页数
 * @param   string  $keywords   搜索关键词字符串
 * @return  void
 */
function build_uri($app, $params, $append = '', $page = 0, $keywords = '', $size = 0)
{
    static $rewrite = NULL;

    if ($rewrite === NULL)
    {
        $rewrite = intval(Config::get('site.rewrite'));
    }

    $args = array('cid'   => 0,
        'gid'   => 0,
        'bid'   => 0,
        'acid'  => 0,
        'aid'   => 0,
        'sid'   => 0,
        'gbid'  => 0,
        'auid'  => 0,
        'sort'  => '',
        'order' => '',
    );

    extract(array_merge($args, $params));

    $uri = '';
    switch ($app)
    {
        case 'category':
            if (empty($cid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'category-' . $cid;
                    if (isset($bid))
                    {
                        $uri .= '-b' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '-min'.$price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '-max'.$price_max;
                    }
                    if (isset($filter_attr))
                    {
                        $uri .= '-attr' . $filter_attr;
                    }
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                }
                else
                {
                    $uri = 'category.php?id=' . $cid;
                    if (!empty($bid))
                    {
                        $uri .= '&amp;brand=' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '&amp;price_min=' . $price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '&amp;price_max=' . $price_max;
                    }
                    if (!empty($filter_attr))
                    {
                        $uri .='&amp;filter_attr=' . $filter_attr;
                    }

                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
            }

            break;
        case 'goods':
            if (empty($gid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'goods-' . $gid : 'goods.php?id=' . $gid;
            }

            break;
        case 'brand':
            if (empty($bid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'brand-' . $bid;
                    if (isset($cid))
                    {
                        $uri .= '-c' . $cid;
                    }
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                }
                else
                {
                    $uri = 'brand.php?id=' . $bid;
                    if (!empty($cid))
                    {
                        $uri .= '&amp;cat=' . $cid;
                    }
                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
            }

            break;
        case 'article_cat':
            if (empty($acid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'article_cat-' . $acid;
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                    if (!empty($keywords))
                    {
                        $uri .= '-' . $keywords;
                    }
                }
                else
                {
                    $uri = 'article_cat.php?id=' . $acid;
                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                    if (!empty($keywords))
                    {
                        $uri .= '&amp;keywords=' . $keywords;
                    }
                }
            }

            break;
        case 'article':
            if (empty($aid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'article-' . $aid : 'article.php?id=' . $aid;
            }

            break;
        case 'group_buy':
            if (empty($gbid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'group_buy-' . $gbid : 'group_buy.php?act=view&amp;id=' . $gbid;
            }

            break;
        case 'auction':
            if (empty($auid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'auction-' . $auid : 'auction.php?act=view&amp;id=' . $auid;
            }

            break;
        case 'snatch':
            if (empty($sid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'snatch-' . $sid : 'snatch.php?id=' . $sid;
            }

            break;
        case 'search':
            break;
        case 'exchange':
            if ($rewrite)
            {
                $uri = 'exchange-' . $cid;
                if (isset($price_min))
                {
                    $uri .= '-min'.$price_min;
                }
                if (isset($price_max))
                {
                    $uri .= '-max'.$price_max;
                }
                if (!empty($page))
                {
                    $uri .= '-' . $page;
                }
                if (!empty($sort))
                {
                    $uri .= '-' . $sort;
                }
                if (!empty($order))
                {
                    $uri .= '-' . $order;
                }
            }
            else
            {
                $uri = 'exchange.php?cat_id=' . $cid;
                if (isset($price_min))
                {
                    $uri .= '&amp;integral_min=' . $price_min;
                }
                if (isset($price_max))
                {
                    $uri .= '&amp;integral_max=' . $price_max;
                }

                if (!empty($page))
                {
                    $uri .= '&amp;page=' . $page;
                }
                if (!empty($sort))
                {
                    $uri .= '&amp;sort=' . $sort;
                }
                if (!empty($order))
                {
                    $uri .= '&amp;order=' . $order;
                }
            }

            break;
        case 'exchange_goods':
            if (empty($gid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'exchange-id' . $gid : 'exchange.php?id=' . $gid . '&amp;act=view';
            }

            break;
        default:
            return false;
            break;
    }

    if ($rewrite)
    {
        if ($rewrite == 2 && !empty($append))
        {
            $uri .= '-' . urlencode(preg_replace('/[\.|\/|\?|&|\+|\\\|\'|"|,]+/', '', $append));
        }

        $uri .= '.html';
    }
    if (($rewrite == 2) && (strpos(strtolower(EC_CHARSET), 'utf') !== 0))
    {
        $uri = urlencode($uri);
    }
    return $uri;
}
/**
 * 获取网站域名
 * @access public
 * @param
 * @return string 本网站域名
 */
function WEIDODomain()
{
    $server = $_SERVER['HTTP_HOST'];
    $http = is_ssl()?'https://':'http://';
    return $http.$server.__ROOT__;
}
/**
 * 判断是否SSL协议
 * @access public
 * @param 
 * @return boolean
 */
function is_ssl() {
    if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
        return true;
    } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
        return true;
    }
    return false;
}