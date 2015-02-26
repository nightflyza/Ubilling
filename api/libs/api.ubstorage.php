<?php

/*
 * UBkey-value storage API
 */

/**
 * Sets key=>value in ubstorage
 * 
 * @param string $key
 * @param string $value
 */
function zb_StorageSet($key, $value) {
    $key = mysql_real_escape_string($key);
    $value = mysql_real_escape_string($value);
    $query_clean = "DELETE from `ubstorage` WHERE `key`='" . $key . "'";
    nr_query($query_clean);
    $query_create = "INSERT INTO `ubstorage` (`id` ,`key` ,`value`) VALUES (NULL , '" . $key . "', '" . $value . "');";
    nr_query($query_create);
}

/**
 * Returns value or empty data from ubstorage if key not exists
 * 
 * @param string $key
 * @return string
 */
function zb_StorageGet($key) {
    $key = mysql_real_escape_string($key);
    $query = "SELECT `value` from `ubstorage` WHERE `key`='" . $key . "'";
    $fetchdata = simple_query($query);
    if (!empty($fetchdata)) {
        $result = $fetchdata['value'];
    } else {
        $result = '';
    }
    return ($result);
}

/**
 * Returns array of keys in ubstorage if they contains search pattern
 * 
 * @param string $keypattern
 * @return array
 */
function zb_StorageFindKeys($keypattern) {
    $keypattern = mysql_real_escape_string($keypattern);
    $query = "SELECT `key` from `ubstorage` WHERE `key` LIKE '%" . $keypattern . "%'";
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Deletes ubstorage database record by key name
 * 
 * @param string $key
 */
function zb_StorageDelete($key) {
    $key = mysql_real_escape_string($key);
    $query = "DELETE from `ubstorage` WHERE `key`='" . $key . "'";
    nr_query($query);
}

?>
