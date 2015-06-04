<?php

/*
 * Framework abstraction functions and wrappers for old code backward compatibility
 */

if (!function_exists('show_error')) {

    /**
     * Shows default error notice
     * 
     * @param string $data
     */
    function show_error($data) {
        show_window('Error', $data);
    }

}

/**
 * Fast debug text data output
 * 
 * @param string $data
 */
function deb($data) {
    show_window('DEBUG', $data);
}

/**
 * Fast debug output of array
 * 
 * @param string $data
 */
function debarr($data) {
    $result = print_r($data, true);
    $result = '<pre>' . $result . '</pre>';
    show_window('DEBUG', $result);
}

/**
 * Returns current date and time in mysql DATETIME view
 * 
 * @return string
 */
function curdatetime() {
    $currenttime = date("Y-m-d H:i:s");
    return($currenttime);
}

/**
 * returns current time in mysql DATETIME view
 * 
 * @return string
 */
function curtime() {
    $currenttime = date("H:i:s");
    return($currenttime);
}

/**
 * Returns current date in mysql DATETIME view
 * 
 * @return string
 */
function curdate() {
    $currentdate = date("Y-m-d");
    return($currentdate);
}

/**
 * Returns current year-month in mysql DATETIME view
 * 
 * @return string
 */
function curmonth() {
    $currentmonth = date("Y-m");
    return($currentmonth);
}

/**
 * Returns previous year-month in mysql DATETIME view
 * 
 * @return string
 */
function prevmonth() {
    $result = date("Y-m", strtotime("-1 months"));
    return ($result);
}

/**
 * Returns current year
 * 
 * @return string
 */
function curyear() {
    $currentyear = date("Y");
    return($currentyear);
}

/**
 * Just system logging subroutine
 * 
 * @param string $event
 */
function log_register($event) {
    $admin_login = whoami();
    @$ip = $_SERVER['REMOTE_ADDR'];
    if (!$ip) {
        $ip = '127.0.0.1';
    }
    $current_time = curdatetime();
    $event = mysql_real_escape_string($event);
    $query = "INSERT INTO `weblogs` (`id`,`date`,`admin`,`ip`,`event`) VALUES(NULL,'" . $current_time . "','" . $admin_login . "','" . $ip . "','" . $event . "')";
    nr_query($query);
}

if (!function_exists('stg_putlogevent')) {

    /**
     * stg_putlogevent dummy wrapper for log_register() - only for backward compat
     * 
     * @param string $event
     */
    function stg_putlogevent($event) {
        log_register($event);
    }

}


if (!function_exists('__')) {

    /**
     * Dummy i18n function
     * 
     * @param string $str
     * @return string
     */
    function __($str) {
        return($str);
    }

}

if (!function_exists('show_window')) {

    /**
     * Replace for system content output
     * 
     * @param string $title
     * @param string $data
     * @param string $align
     */
    function show_window($title, $data, $align = "left") {
        $result = '
        <table width="100%" border="0" id="window">
        <tr>
            <td align="center">
            <b>' . $title . '</b>
            </td>
        </tr>
        <tr>
            <td align="' . $align . '">
            ' . $data . '
            </td>
        </tr>
        </table>
        ';
        print($result);
    }

}

/**
 * Check for right via module
 * 
 * @global object $system
 * @param string $right
 * @return bool
 */
function cfr($right) {
    global $system;
    // uncomment following to run phpunit tests (realy ugly hack, i know)
    // run as: phpunit --bootstrap puboot.php tests
    // if (empty($system)) {@$system = new rcms_system(); }
    if ($system->checkForRight($right)) {
        return(true);
    } else {
        return(false);
    }
}

/**
 * Replace for $system->user['username']
 * 
 * @global object $system
 * @return string
 */
function whoami() {
    global $system;
    @$mylogin = $system->user['username'];
    if (empty($mylogin)) {
        $mylogin = 'external';
    }
    return($mylogin);
}

if (!function_exists('rcms_redirect')) {

    /**
     * Shows redirection javascript. 
     * 
     * @param string $url
     * @param bool $header
     */
    function rcms_redirect($url, $header = false) {
        if ($header) {
            @header('Location: ' . $url);
        } else {
            echo '<script language="javascript">document.location.href="' . $url . '";</script>';
        }
    }

}
?>
