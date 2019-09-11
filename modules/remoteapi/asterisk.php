<?php

/**
 * Ubilling remote API for Asterisk and other CRM
 * -----------------------------
 * 
 * Format: /?module=remoteapi&key=[ubserial]&action=[action]&number=[+380XXXXXXXXX]&param=[parameter]
 * 
 * Avaible parameter: login, swstatus
 * 
 */
if ($_GET['action'] == 'asterisk') {
    if ($alterconf['ASTERISK_ENABLED']) {
        if (wf_CheckGet(array('number'))) {
            if (wf_CheckGet(array('param'))) {
                $ignoreCache = wf_CheckGet(array('ignorecache'));
                $getMoney = wf_CheckGet(array('getmoney'));
                $number = trim($_GET['number']);
                $askNum = new AskoziaNum();
                $askNum->setNumber($number);

                if ($_GET['param'] == 'userstatus') {
                    $askNum->renderReply(false, $ignoreCache, $getMoney);
                } else {
                    $askNum->renderReply(true, $ignoreCache, $getMoney);

                    $asterisk = new Asterisk();
                    $result = $asterisk->AsteriskGetInfoApi($number, $_GET['param']);
                    die($result);
                }
            } else {
                die('ERROR: NOT HAVE PARAMETR');
            }
        } else {
            die('ERROR: NOT HAVE NUMBER');
        }
    } else {
        die('ERROR: ASTERISK DISABLED');
    }
}

            
