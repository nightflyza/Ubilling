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

//fast debug output
function deb($data) {
    show_window('DEBUG', $data);
}

//fast debug output of array
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

//returns current date in mysql DATETIME view
function curdate() {
    $currentdate = date("Y-m-d");
    return($currentdate);
}

//returns current month in mysql DATETIME view
function curmonth() {
    $currentmonth = date("Y-m");
    return($currentmonth);
}

//returns current year
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

/** * Shows redirection javascript. 
  @param string $url
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

function simple_update_field($tablename, $field, $value, $where = '') {
    $tablename = mysql_real_escape_string($tablename);
    $value = mysql_real_escape_string($value);
    $field = mysql_real_escape_string($field);
    $query = "UPDATE `" . $tablename . "` SET `" . $field . "` = '" . $value . "' " . $where . "";
    nr_query($query);
}

?>
