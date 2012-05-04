<?php

return array(
  'id'    => 'oauth',
  'name'  => Lang::get('oauth'),
  'desc'  => Lang::get('oauth_desc'),
  'version'   => '1.0',
  'author'    => 'heui',
  'website'   => 'http://www.heui.org',
  'menu'  => array(
    array(
      'text'  => Lang::get('oauth_manage'),
      'act'   => 'index',
    ),
  ),
/*   'config'  => array(
	'appid' => array(
	  'text' => 'QQ APP ID',
	  'type' => 'text',
	  'size' => 10,
	),
	'appkey' => array(
	  'text' => 'QQ KEY',
	  'type' => 'text',
	  'size' => 32,
	),
  ), */
);

?>
