<?php

/* * **********************
 * Config section
 * *********************** */

////MySQL database settings
$db_host = 'localhost';
$db_database = 'stg';
$db_login = 'mylogin';
$db_password = 'newpassword';

// DN switcher files path
$dn_path = "/etc/stargazer/dn/";

//speed size
$speed_size = 'Kbit/s';

/* * **********************
 * End of config section
 * *********************** */

/**
 * Advanced php5 scandir analog with some filters
 * 
 * @param string $directory Directory to scan
 * @param string $exp  Filter expression - like *.ini or *.dat
 * @param string $type Filter type - all or dir
 * @param bool $do_not_filter
 * 
 * @return array
 */
function rcms_scandir($directory, $exp = '', $type = 'all', $do_not_filter = false) {
    $dir = $ndir = array();
    if (!empty($exp)) {
        $exp = '/^' . str_replace('*', '(.*)', str_replace('.', '\\.', $exp)) . '$/';
    }
    if (!empty($type) && $type !== 'all') {
        $func = 'is_' . $type;
    }
    if (is_dir($directory)) {
        $fh = opendir($directory);
        while (false !== ($filename = readdir($fh))) {
            if (substr($filename, 0, 1) != '.' || $do_not_filter) {
                if ((empty($type) || $type == 'all' || $func($directory . '/' . $filename)) && (empty($exp) || preg_match($exp, $filename))) {
                    $dir[] = $filename;
                }
            }
        }
        closedir($fh);
        natsort($dir);
    }
    return $dir;
}

/**
 * Returns result as some query to database
 * 
 * @global string $db_host
 * @global string $db_database
 * @global string $db_login
 * @global string $db_password
 * @param string $query
 * 
 * @return array
 */
function simple_queryall($query) {
    global $db_host, $db_database, $db_login, $db_password;
    $result = array();

    if (!extension_loaded('mysql')) {
        // init mysql link
        $dblink = mysqli_connect($db_host, $db_login, $db_password, $db_database);
        //executing query
        $queried = mysqli_query($dblink, $query);
        //getting result as array
        while ($row = mysqli_fetch_assoc($queried)) {
            $result[] = $row;
        }
        //closing link
        mysqli_close($dblink);
    } else {
        // init mysql link
        $dblink = mysql_connect($db_host, $db_login, $db_password);
        //selecting stargazer database
        mysql_select_db($db_database, $dblink);
        //executing query
        $queried = mysql_query($query);
        //getting result as array
        while ($row = mysql_fetch_assoc($queried)) {
            $result[] = $row;
        }
        //closing link
        mysql_close($dblink);
    }
    //return result of query as array
    return($result);
}

/**
 * Returns user tariff names array as login=>tariff
 * 
 * @return array
 */
function dshape_GetAllUserTariffs() {
    $query = "SELECT `login`,`Tariff` from `users`";
    $alltariffs = simple_queryall($query);
    $result = array();
    if (!empty($alltariffs)) {
        foreach ($alltariffs as $io => $eachtariff) {
            $result[$eachtariff['login']] = $eachtariff['Tariff'];
        }
    }
    return ($result);
}

/**
 * Returns list of available shaper time rules as tariff=>rule
 * 
 * @return array
 */
function dshape_GetTimeRules() {
    $now = date('H:i:s');
    $query = "SELECT `tariff`,`speed` from `dshape_time` WHERE  '" . $now . "'  > `threshold1` AND '" . $now . "' < `threshold2`";
    $result = array();
    $allrules = simple_queryall($query);
    if (!empty($allrules)) {
        foreach ($allrules as $io => $eachrule) {
            $result[$eachrule['tariff']] = $eachrule['speed'];
        }
    }
    return ($result);
}

/**
 * Switches speed directly with dummynet
 * 
 * @param int $speed
 * @param int $mark
 * @param string $speed_size
 * 
 * @return void
 */
function dshape_SwitchSpeed($speed, $mark, $speed_size = 'Kbit/s') {
    $shape_command = '/sbin/ipfw -q pipe ' . trim($mark) . ' config bw ' . $speed . '' . $speed_size . ' queue 32Kbytes' . "\n";
    shell_exec($shape_command);
}

//parse all online users speed data
$online_users = rcms_scandir($dn_path);
$connect_data = array();
if (!empty($online_users)) {
    foreach ($online_users as $ia => $eachdata) {
        $connect_data[$eachdata] = file_get_contents($dn_path . $eachdata);
    }
}
//getting tariffs and rules data
$AllUserTariffs = dshape_GetAllUserTariffs();
$AllTimeRules = dshape_GetTimeRules();


$debugdata = '#### Shape start' . date("d-M-Y H:i:s") . "####\n";

if (!empty($online_users)) {
    if (!empty($AllTimeRules)) {
        foreach ($online_users as $eachuser) {
            $normal_data = explode(':', $connect_data[$eachuser]);
            $normal_speed = $normal_data[0];
            $normal_mark = $normal_data[1];
            $new_speed = $normal_data[0];
            $user_tariff = $AllUserTariffs[$eachuser];
            $debugdata.='user login:' . $eachuser . "\n";
            $debugdata.='normal mark:' . trim($normal_mark) . "\n";
            $debugdata.='user tariff:' . $user_tariff . "\n";

            // check is now time to change speed?
            if (isset($AllTimeRules[$user_tariff])) {
                $new_speed = $AllTimeRules[$user_tariff];
            }
            $debugdata.='normal speed:' . $normal_speed . "\n";
            $debugdata.='new speed:' . $new_speed . "\n";
            $debugdata.='===============' . "\n";
            dshape_SwitchSpeed($new_speed, $normal_mark, $speed_size);
        }
    }
}
$debugdata.='####Shape end ' . date("d-M-Y H:i:s") . "####\n";

//debug output 
print($debugdata);
?>

