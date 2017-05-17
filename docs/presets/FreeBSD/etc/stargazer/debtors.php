#!/usr/local/bin/php
<?php
include('mysql.php');
$data_q='SELECT `ip` FROM `users` WHERE `Cash`< -`Credit`';
$data=DB_query($data_q);
           while ($row = DB_fetch_array($data)) {
                shell_exec("/sbin/ipfw -q table 47 add ".$row['ip']);
           }
 
?>
