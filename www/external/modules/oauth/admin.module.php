<?php

class OauthModule extends AdminbaseModule
{

  function __construct()
  {
    $this->OauthModule();
  }

  function OauthModule()
  {
    parent::__construct();

     
  }

  function index()
  {
    $model_module =& m('module');
    if (!IS_POST)
    {
      $find_data = $model_module->find('index:' . MODULE);
      $info = current($find_data);
      $config = unserialize($info['module_config']);
      $this->assign('configs', $config);
      $this->display('index.html');
    }
    else
    {
      $data = array();
      $data['qq'] = $_POST['qq'];
      $model_module->edit('index:' . MODULE, array('module_config' => serialize($data)));
      $this->show_message('edit_oauth_successed', 'continue_edit', 'index.php?module=oauth');
    }
  }

}

?>