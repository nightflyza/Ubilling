#!/usr/local/bin/php
<?php

$config=parse_ini_file(dirname(__FILE__)."/config");
$loginDB = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
$ipq='SELECT `ip`,`mac` FROM `nethosts`';
$data=$loginDB->query($ipq);
           while ($line2 = mysqli_fetch_assoc($data)) {
                print($line2['ip'].' '.$line2['mac']."\n");
           }

?>
