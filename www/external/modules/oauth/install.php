<?php
$db =&db();
$table = DB_PREFIX.'oauth';

$sql = "
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `u_id` int(10) DEFAULT NULL,
  `qq_openid` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `add_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qq_openid` (`qq_openid`),
  UNIQUE KEY `u_id` (`u_id`)
)
";

$db->query($sql);