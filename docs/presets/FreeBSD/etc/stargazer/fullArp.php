#!/usr/local/bin/php
<?php
include('mysql.php');
$ipq = 'SELECT `ip`,`mac` FROM `nethosts`';
$data = DB_query($ipq);
           while ($line2 = DB_fetch_array($data)) {
                print($line2['ip'].' '.$line2['mac']."\n");
           }

?>
