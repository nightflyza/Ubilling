<?php

/**
 * Configuration section
 */
define('UBILLING_SERIAL', 'UBxxxxxxxxxxxxxxxxxx'); // your Ubilling instance serial number
define('UBILLING_URL', 'http://localhost/billing/'); //your Ubilling URL
define('BACK_URL_OK', 'http://ubilling.net.ua/'); // URL to redirect user after succefull saving call request
define('BACK_URL_FAIL', 'http://ubilling.net.ua/?fail=true'); // URL to redirect user after some fail occurred
define('BOT_PROTECTION', true); // enable spam-bot protection?
define('BOT_CATCH', 'lastname'); // name of invisible POST field to catch for bots detection
define('CATCHFIELD', 'callmebackmobile'); // name of POST variable for catching input phone number
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


