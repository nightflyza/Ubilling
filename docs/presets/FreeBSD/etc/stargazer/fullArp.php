#!/usr/local/bin/php
<?php

$config=parse_ini_file(dirname(__FILE__)."/config");
if (extension_loaded('mysqli')) {
    $loginDB = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    function DB_query($query) {
        global $loginDB;
        $result = $loginDB->query($query);
        return($result);
    }
    function DB_real_escape_string($parametr) {
        global $loginDB;
        $result = $loginDB->real_escape_string($parametr);
        return($result);
    }
    function DB_fetch_array($data) {
        $result = mysqli_fetch_assoc($data);
        return($result);
    }
} else {
    $link = mysql_connect($config['host'], $config['username'], $config['password']);
    mysql_select_db($config['database']);
    function DB_query($query) {
        $result = mysql_query($query);
        return($result);
    }
    function DB_real_escape_string($parametr) {
        $result = mysql_real_escape_string($parametr);
        return($result);
    }
    function DB_fetch_array($data) {
        $result = mysql_fetch_array($data, MYSQL_ASSOC);
        return($result);
    }
}
$ipq = 'SELECT `ip`,`mac` FROM `nethosts`';
$data = DB_query($ipq);
           while ($line2 = DB_fetch_array($data)) {
                print($line2['ip'].' '.$line2['mac']."\n");
           }

?>
