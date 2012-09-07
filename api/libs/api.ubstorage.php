<?php

/*
 * UBkey-value storage API
 */

function zb_StorageSet($key,$value) {
    $key=  mysql_real_escape_string($key);
    $value= mysql_real_escape_string($value);
    $query_clean="DELETE from `ubstorage` WHERE `key`='".$key."'";
    nr_query($query_clean);
    $query_create="INSERT INTO `ubstorage` (`id` ,`key` ,`value`) VALUES (NULL , '".$key."', '".$value."');";
    nr_query($query_create);
}


function zb_StorageGet($key) {
    $key=mysql_real_escape_string($key);
    $query="SELECT `value` from `ubstorage` WHERE `key`='".$key."'";
    $fetchdata=  simple_query($query);
    if (!empty($fetchdata)) {
        $result=$fetchdata['value'];
    } else {
        $result='';
    }
    return ($result);
}


function zb_StorageFindKeys($keypattern) {
    $keypattern=  mysql_real_escape_string($keypattern);
    $query="SELECT `key` from `ubstorage` WHERE `key` LIKE '%".$keypattern."%'";
    $result=  simple_queryall($query);
    return ($result);
}


function zb_StorageDelete($key) {
    $key=mysql_real_escape_string($key);
    $query="DELETE from `ubstorage` WHERE `key`='".$key."'";
    nr_query($query);
}

?>
