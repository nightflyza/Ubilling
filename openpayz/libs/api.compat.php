<?php

// replace for content output
if (!function_exists('show_window')) {

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

//show error
if (!function_exists('show_error')) {

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

/**
 * Returns current date and time in mysql DATETIME view Y-m-d H:i:s
 * 
 * @return string
 */
function curdatetime() {
    $currenttime = date("Y-m-d H:i:s");
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
 * Returns current year
 * 
 * @return string
 */
function curyear() {
    $currentyear = date("Y");
    return($currentyear);
}

//dummy lang function
if (!function_exists('__')) {

    function __($str) {
        return($str);
    }

}

/**
 * Shows redirection javascript. 
 * 
 * @param string $url
 */
if (!function_exists('rcms_redirect')) {

    function rcms_redirect($url, $header = false) {
        if ($header) {
            @header('Location: ' . $url);
        } else {
            echo '<script language="javascript">document.location.href="' . $url . '";</script>';
        }
    }

}

?>
