<?php

set_time_limit(0);
/*
 * Ubilling remote API
 * -----------------------------
 * 
 * Basic format: /?module=remoteapi&key=[ubserial]&action=[action][&param=[parameter]]
 * 
 */



$alterconf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
if ($alterconf['REMOTEAPI_ENABLED']) {
    if (isset($_GET['key'])) {
        $key = vf($_GET['key']);
        $hostid_q = "SELECT * from `ubstats` WHERE `key`='ubid'";
        $hostid = simple_query($hostid_q);
        if (!empty($hostid)) {
            $serial = $hostid['value'];
            if ($key == $serial) {
                //key ok
                if (isset($_GET['action'])) {

                    //Loading separate api calls controllers
                    $allRemoteApiModules = rcms_scandir(REMOTEAPI_PATH, '*.php');
                    $disabledRemoteApiCalls = array();
                    if (!@empty($alterconf['REMOTEAPI_DISABLE_CALLS'])) {
                        $disabledRemoteApiCalls = explode(',', $alterconf['REMOTEAPI_DISABLE_CALLS']);
                        $disabledRemoteApiCalls = array_flip($disabledRemoteApiCalls);
                    }
                    if (!empty($allRemoteApiModules)) {
                        foreach ($allRemoteApiModules as $rmodIndex => $eachRModuleController) {
                            $eachRModuleControllerName = basename($eachRModuleController, '.php');
                            if (!isset($disabledRemoteApiCalls[$eachRModuleControllerName])) {
                                require_once (REMOTEAPI_PATH . $eachRModuleController);
                            }
                        }
                    }


                    /*
                     * Exceptions handling
                     */
                } else {
                    die('ERROR:GET_NO_ACTION');
                }
            } else {
                die('ERROR:GET_WRONG_KEY');
            }
        } else {
            die('ERROR:NO_UBSERIAL_EXISTS');
        }
    } else {
        /*
         * Ubilling instance identify handler
         */
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'identify') {
                $idhostid_q = "SELECT * from `ubstats` WHERE `key`='ubid'";
                $idhostid = simple_query($idhostid_q);
                if (!empty($idhostid)) {
                    $idserial = $idhostid['value'];
                } else {
                    $idserial = zb_InstallBillingSerial();
                }

                //saving serial into temp file required for initial crontab setup
                if (@$_GET['param'] == 'save') {
                    file_put_contents('exports/ubserial', $idserial);
                }

                //render result
                die(substr($idserial, -4));
            }
        } else {
            die('ERROR:GET_NO_KEY');
        }
    }
} else {
    die('ERROR:API_DISABLED');
}
?>
