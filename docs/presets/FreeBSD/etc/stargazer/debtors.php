#!/usr/local/bin/php
<?php
 
$config=parse_ini_file(dirname(__FILE__)."/config");
$link = mysql_connect($config['host'], $config['username'], $config['password']);
mysql_select_db($config['database']);
$data_q='SELECT `ip` FROM `users` WHERE `Cash`< -`Credit`';
$data=mysql_query($data_q);
           while ($row = mysql_fetch_array($data, MYSQL_ASSOC)) {
                shell_exec("/sbin/ipfw -q table 47 add ".$row['ip']);
           }
 
?>
