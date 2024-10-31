<?php

/**
 * Returns current date and time in mysql DATETIME view Y-m-d H:i:s
 * 
 * @return string
 */
function curdatetime() {
    $currenttime = date("Y-m-d H:i:s");
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
 * Returns current year
 * 
 * @return string
 */
function curyear() {
    $currentyear = date("Y");
    return ($currentyear);
}

if (!function_exists('__')) {

    /**
     * Dummy i18n function. Yep in this case it returns the same string.
     * 
     * @param string $str
     * 
     * @return string
     */
    function __($str) {
        return ($str);
    }
}

if (!function_exists('show_window')) {

    /**
     * Dummy replacement function for content output
     * 
     * @param string $title
     * @param sting $data
     * @param string $align
     * 
     * @return void
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

if (!function_exists('show_error')) {

    /**
     * Dummy replacement for error notices
     * 
     * @param string $data
     * 
     * @return void
     */
    function show_error($data) {
        show_window('Error', $data);
    }
}

/**
 * Fast debug output
 * 
 * @param string $data
 * 
 * @return void
 */
function deb($data) {
    show_window('DEBUG', $data);
}

/**
 * Fast debug output of array
 * 
 * @param array $data
 * 
 * @return void
 */
function debarr($data) {
    $result = print_r($data, true);
    $result = '<pre>' . $result . '</pre>';
    show_window('DEBUG', $result);
}

if (!function_exists('rcms_redirect')) {

    /**
     * Dummy redirect function
     * 
     * @param string $url
     * @param bool $header
     * 
     * @return void
     */
    function rcms_redirect($url, $header = false) {
        if ($header) {
            @header('Location: ' . $url);
        } else {
            echo '<script language="javascript">document.location.href="' . $url . '";</script>';
        }
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
