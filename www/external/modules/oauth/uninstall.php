<?php
$db =&db();
$table = DB_PREFIX.'oauth';

$sql = "
DROP TABLE `{$table}`
";

$db->query($sql);