<?php

/**
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
    return ($currenttime);
}

/**
 * returns current time in mysql DATETIME view
 * 
 * @return string
 */
function curtime() {
    $currenttime = date("H:i:s");
    return ($currenttime);
}

/**
 * Returns current date in mysql DATETIME view
 * 
 * @return string
 */
function curdate() {
    $currentdate = date("Y-m-d");
    return ($currentdate);
}

/**
 * Returns current year-month in mysql DATETIME view
 * 
 * @return string
 */
function curmonth() {
    $currentmonth = date("Y-m");
    return ($currentmonth);
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
 * Returns current year as just Y
 * 
 * @return string
 */
function curyear() {
    $currentyear = date("Y");
    return ($currentyear);
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
        return ($str);
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
        return (true);
    } else {
        return (false);
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
    return ($mylogin);
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


if (!function_exists('each2')) {

    /**
     * (PHP 4, PHP 5, PHP 7)<br/>
     * This function has been DEPRECATED as of PHP 7.2.0, and REMOVED as of PHP 8.0.0. 
     * Return the current key and value pair from an array and advance the array cursor
     * @link http://php.net/manual/en/function.each.php
     * @param array $array <p>
     * The input array.
     * </p>
     * @return array the current key and value pair from the array
     * <i>array</i>. This pair is returned in a four-element
     * array, with the keys 0, 1,
     * key, and value. Elements
     * 0 and key contain the key name of
     * the array element, and 1 and value
     * contain the data.
     * </p>
     * <p>
     * If the internal pointer for the array points past the end of the
     * array contents, <b>each</b> returns
     * <b>FALSE</b>.
     */
    function each2($arr) {
        $key = key($arr);
        $result = ($key === null) ? false : array($key, current($arr), 'key' => $key, 'value' => current($arr));
        next($arr);
        return $result;
    }
}


if (!function_exists('ispos_array')) {
    /**
     * Checks for substring in a string or array of strings
     *
     * @param string $string
     * @param string|array $search
     *
     * @return bool
     */
    function ispos_array($string, $search) {
        if (is_array($search)) {
            foreach ($search as $eachStr) {
                if (strpos($string, $eachStr) !== false) {
                    return (true);
                }
            }

            return (false);
        } else {
            if (strpos($string, $search) === false) {
                return (false);
            } else {
                return (true);
            }
        }
    }
}


if (!function_exists('json_validate')) {
    /**
     * Validates a JSON string. PHP <8.3 replacement.
     * 
     * @param string $json The JSON string to validate.
     * @param int $depth Maximum depth. Must be greater than zero.
     * @param int $flags Bitmask of JSON decode options.
     * @return bool Returns true if the string is a valid JSON, otherwise false.
     */
    function json_validate($json, $depth = 512, $flags = 0) {
        if (!is_string($json)) {
            return false;
        }

        try {
            json_decode($json, false, $depth, $flags | JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }
}
