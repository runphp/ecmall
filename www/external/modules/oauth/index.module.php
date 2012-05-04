<?php

class OauthModule extends IndexbaseModule
{
  var $_configs;

  function __construct()
  {
    $this->OauthModule();
  }

  function OauthModule()
  {
    parent::__construct();
    Lang::load(lang_file('member'));
    $model_module =& m('module');
    $find_data = $model_module->find('index:' . MODULE);
    $info = current($find_data);
    $this->_configs = unserialize($info['module_config']);
    $this->_configs['qq']['callback'] = SITE_URL."/index.php?module=oauth&act=qqcallback";
  }

  function index()
  {
    $this->display('index.html');
  }

  function qq()
  {
    $o_qq = Oauth_qq::getInstance($this->_configs['qq']);
    $o_qq->login();
  }

  function qqcallback()
  {
    $o_qq = Oauth_qq::getInstance($this->_configs['qq']);
    $o_qq->callback();
    if($openid = $o_qq->get_openid())
    {
      $oauth_model =& m("oauth");
      $user = $oauth_model->get("qq_openid = '{$openid}'");

      //登录
      if ($user && 0 < $user['u_id']) {
        $this->_do_login($user['u_id']);
        $this->show_message(Lang::get('login_successed') . $synlogin,
                'back_before_login', rawurldecode($_POST['ret_url']),
                'enter_member_center', 'index.php?app=member'
        );
        exit;
      }
       
      //记录openid
      if(!$user)
      {
      /*   $data = array(
           'qq_openid' => $openid,
           'add_time'  => gmtime(),
        );

        $oauth_model->add($data, ture); */
      }

      //去绑定帐号
      header('Location:index.php?module=oauth&act=login&ret_url=' . rawurlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']));

      return;
    }
    else
    {
      return;
    }
  }

  function login()
  {
    
    if ($this->visitor->has_login)
    {
      $this->show_warning('has_login');

      return;
    }

    if (!isset($_SESSION["openid"])) {
      header('Location:index.php?module=oauth&act=qq');
      return;
    }

    if (!IS_POST)
    {
      if (!empty($_GET['ret_url']))
      {
        $ret_url = trim($_GET['ret_url']);
      }
      else
      {
        if (isset($_SERVER['HTTP_REFERER']))
        {
          $ret_url = $_SERVER['HTTP_REFERER'];
        }
        else
        {
          $ret_url = SITE_URL . '/index.php';
        }
      }
      /* 防止登陆成功后跳转到登陆、退出的页面 */
      $ret_url = strtolower($ret_url);
      if (str_replace(array('act=login', 'act=logout', 'act=qqcallback'), '', $ret_url) != $ret_url)
      {
        $ret_url = SITE_URL . '/index.php';
      }

      if (Conf::get('captcha_status.login'))
      {
        $this->assign('captcha', 1);
      }
      $this->import_resource(array('script' => 'jquery.plugins/jquery.validate.js'));
      $this->assign('ret_url', rawurlencode($ret_url));
      $this->_curlocal(LANG::get('user_login'));
      $this->_config_seo('title', Lang::get('user_login') . ' - ' . Conf::get('site_title'));
      $this->display('login.html');
      /* 同步退出外部系统 */
      if (!empty($_GET['synlogout']))
      {
        $ms =& ms();
        echo $synlogout = $ms->user->synlogout();
      }
    }
    else
    {
      if (Conf::get('captcha_status.login') && base64_decode($_SESSION['captcha']) != strtolower($_POST['captcha']))
      {
        $this->show_warning('captcha_failed');

        return;
      }

      $user_name = trim($_POST['user_name']);
      $password  = $_POST['password'];

      $ms =& ms();
      $user_id = $ms->user->auth($user_name, $password);
      if (!$user_id)
      {
        /* 未通过验证，提示错误信息 */
        $this->show_warning($ms->user->get_error());

        return;
      }
      else
      {
        /* 通过验证，执行登陆操作 */
        $this->_do_login($user_id);
        $oauth_model =& m("oauth");
        $oauth_model->add(array(
          'id'        => NULL,
          'u_id'      => $user_id,
          'qq_openid' => $_SESSION["openid"],
          'add_time'  => gmtime(),
          
        ));
        /* 同步登陆外部系统 */
        $synlogin = $ms->user->synlogin($user_id);
      }

      $this->show_message(Lang::get('login_successed') . $synlogin,
                'back_before_login', rawurldecode($_POST['ret_url']),
                'enter_member_center', 'index.php?app=member'
      );
    }
  }

  function register()
  {
    if ($this->visitor->has_login)
    {
      $this->show_warning('has_login');
  
      return;
    }
    if (!IS_POST)
    {
      if (!empty($_GET['ret_url']))
      {
        $ret_url = trim($_GET['ret_url']);
      }
      else
      {
        if (isset($_SERVER['HTTP_REFERER']))
        {
          $ret_url = $_SERVER['HTTP_REFERER'];
        }
        else
        {
          $ret_url = SITE_URL . '/index.php';
        }
      }
      $this->assign('ret_url', rawurlencode($ret_url));
      $this->_curlocal(LANG::get('user_register'));
      $this->_config_seo('title', Lang::get('user_register') . ' - ' . Conf::get('site_title'));
  
      if (Conf::get('captcha_status.register'))
      {
        $this->assign('captcha', 1);
      }
  
      /* 导入jQuery的表单验证插件 */
      $this->import_resource('jquery.plugins/jquery.validate.js');
      $this->display('register.html');
    }
    else
    {
      if (!$_POST['agree'])
      {
        $this->show_warning('agree_first');
  
        return;
      }
      if (Conf::get('captcha_status.register') && base64_decode($_SESSION['captcha']) != strtolower($_POST['captcha']))
      {
        $this->show_warning('captcha_failed');
        return;
      }
      if ($_POST['password'] != $_POST['password_confirm'])
      {
        /* 两次输入的密码不一致 */
        $this->show_warning('inconsistent_password');
        return;
      }
  
      /* 注册并登陆 */
      $user_name = trim($_POST['user_name']);
      $password  = $_POST['password'];
      $email     = trim($_POST['email']);
      $passlen = strlen($password);
      $user_name_len = strlen($user_name);
      if ($user_name_len < 3 || $user_name_len > 25)
      {
        $this->show_warning('user_name_length_error');
  
        return;
      }
      if ($passlen < 6 || $passlen > 20)
      {
        $this->show_warning('password_length_error');
  
        return;
      }
      if (!is_email($email))
      {
        $this->show_warning('email_error');
  
        return;
      }
  
      $ms =& ms(); //连接用户中心
      $user_id = $ms->user->register($user_name, $password, $email);
  
      if (!$user_id)
      {
        $this->show_warning($ms->user->get_error());
  
        return;
      }
      $this->_hook('after_register', array('user_id' => $user_id));
      //登录
      $this->_do_login($user_id);
      $oauth_model =& m("oauth");
      $oauth_model->add(array(
                'id'        => NULL,
                'u_id'      => $user_id,
                'qq_openid' => $_SESSION["openid"],
                'add_time'  => gmtime(),
      
      ));
      /* 同步登陆外部系统 */
      $synlogin = $ms->user->synlogin($user_id);
  
      #TODO 可能还会发送欢迎邮件
  
      $this->show_message(Lang::get('register_successed') . $synlogin,
                  'back_before_register', rawurldecode($_POST['ret_url']),
                  'enter_member_center', 'index.php?app=member',
                  'apply_store', 'index.php?app=apply'
      );
    }
  }
  
  
}
/**
 *
 * qq登录
 * @author heui
 *
 */
class Oauth_qq
{
  private static $_instance;
  private $config = array();

  private function __construct($config)
  {
    $this->Oauth_qq($config);
  }

  public static function getInstance($config)
  {
    if(!isset(self::$_instance))
    {
      $c=__CLASS__;
      self::$_instance = new $c($config);
    }
    return self::$_instance;
  }

  private function Oauth_qq($config)
  {
    $this->config = $config;
    $_SESSION["appid"]    = $this->config['appid'];
    $_SESSION["appkey"]   = $this->config['appkey'];
    $_SESSION["callback"] = $this->config['callback'];
    $_SESSION["scope"] = "get_user_info,add_share,list_album,add_album,upload_pic,add_topic,add_one_blog,add_weibo";
  }

  function login()
  {
    $_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
    $login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
    . $_SESSION["appid"] . "&redirect_uri=" . urlencode($_SESSION["callback"])
    . "&state=" . $_SESSION['state']
    . "&scope=".$_SESSION["scope"];
    header("Location:$login_url");
  }

  function callback()
  {
    if($_REQUEST['state'] == $_SESSION['state']) //csrf
    {
      $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
      . "client_id=" . $_SESSION["appid"]. "&redirect_uri=" . urlencode($_SESSION["callback"])
      . "&client_secret=" . $_SESSION["appkey"]. "&code=" . $_REQUEST["code"];

      $response = get_url_contents($token_url);
      if (strpos($response, "callback") !== false)
      {
        $lpos = strpos($response, "(");
        $rpos = strrpos($response, ")");
        $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
        $msg = json_decode($response);
        if (isset($msg->error))
        {
          echo "<h3>error:</h3>" . $msg->error;
          echo "<h3>msg  :</h3>" . $msg->error_description;
          exit;
        }
      }

      $params = array();
      parse_str($response, $params);

      $_SESSION["access_token"] = $params["access_token"];
    }
    else
    {
      echo("The state does not match. You may be a victim of CSRF.");
    }
  }

  function get_openid()
  {
    $graph_url = "https://graph.qq.com/oauth2.0/me?access_token="
    . $_SESSION['access_token'];

    $str  = get_url_contents($graph_url);
    if (strpos($str, "callback") !== false)
    {
      $lpos = strpos($str, "(");
      $rpos = strrpos($str, ")");
      $str  = substr($str, $lpos + 1, $rpos - $lpos -1);
    }

    $user = json_decode($str);
    if (isset($user->error))
    {
      echo "<h3>error:</h3>" . $user->error;
      echo "<h3>msg  :</h3>" . $user->error_description;
      exit;
    }

    //set openid to session
    return $_SESSION["openid"] = $user->openid;
  }

  function get_user_info()
  {
    $get_user_info = "https://graph.qq.com/user/get_user_info?"
    . "access_token=" . $_SESSION['access_token']
    . "&oauth_consumer_key=" . $_SESSION["appid"]
    . "&openid=" . $_SESSION["openid"]
    . "&format=json";

    $info = get_url_contents($get_user_info);
    $arr = json_decode($info, true);

    return $arr;
  }

  public function __clone()
  {
    trigger_error('Clone is not allow' ,E_USER_ERROR);
  }

}

/* 公用函数 */
if (!function_exists("do_post"))
{
  function do_post($url, $data)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_URL, $url);
    $ret = curl_exec($ch);

    curl_close($ch);
    return $ret;
  }
}
if (!function_exists("get_url_contents"))
{
  function get_url_contents($url)
  {
    if (ini_get("allow_url_fopen") == "1")
    return file_get_contents($url);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_URL, $url);
    $result =  curl_exec($ch);
    curl_close($ch);

    return $result;
  }
}
?>
