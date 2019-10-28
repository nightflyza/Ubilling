<?php

/**
 * Configuration section
 */
define('UBILLING_SERIAL', 'UB9a81783f16973effadf5277fb9cf95c0');
define('UBILLING_URL', 'http://localhost/dev/ubilling/');
define('BACK_URL_OK', 'http://ubilling.net.ua/');
define('BACK_URL_FAIL', 'http://ubilling.net.ua/?fail=true');
define('BOT_PROTECTION', true);
define('BOT_CATCH', 'lastname'); // name of invisible field to catch for bots detection
define('CATCHFIELD', 'callmebackmobile');
/**
 * End of config
 */
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


//primary controller part
if (isset($_POST[CATCHFIELD])) {
    if (!empty($_POST[CATCHFIELD])) {
        $phoneNumber = $_POST[CATCHFIELD];
        $phoneNumber = preg_replace('/\0/s', '', $phoneNumber);
        $phoneNumber = preg_replace("#[^0-9]#Uis", '', $phoneNumber);
        if (!empty($phoneNumber)) {
            $everythingOk = true; // default allow flag
            if (BOT_PROTECTION) {
                if (isset($_POST[BOT_CATCH])) {
                    if (!empty($_POST[BOT_CATCH])) {
                        $everythingOk = false;
                    }
                }
            }

            //performing 
            if ($everythingOk) {
                $apiUrl = UBILLING_URL . '/?module=remoteapi&key=' . UBILLING_SERIAL . '&action=callmeback&param=' . $phoneNumber;
                @$apiResult = file_get_contents($apiUrl);
                rcms_redirect(BACK_URL_OK);
            } else {
                rcms_redirect(BACK_URL_FAIL);
            }
        } else {
            rcms_redirect(BACK_URL_FAIL);
        }
    } else {
        rcms_redirect(BACK_URL_FAIL);
    }
}


