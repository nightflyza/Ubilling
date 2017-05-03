#!/usr/local/bin/php
<?php

$config=parse_ini_file(dirname(__FILE__)."/config");
$link = mysql_connect($config['host'], $config['username'], $config['password']);
mysql_select_db($config['database']);
$ipq='SELECT `ip`,`mac` FROM `nethosts`';
$data=mysql_query($ipq);
           while ($line2 = mysql_fetch_array($data, MYSQL_ASSOC)) {
                print($line2['ip'].' '.$line2['mac']."\n");
           }

?>
