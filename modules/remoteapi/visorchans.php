<?php

//visor channels backend
if (ubRouting::get('action') == 'visorchans') {
    if ($alterconf['VISOR_ENABLED']) {
        if (ubRouting::checkGet(array('param', 'userid'))) {
            if ($alterconf['WOLFRECORDER_ENABLED'] or $alterconf['TRASSIRMGR_ENABLED']) {
                $chanCall = ubRouting::get('param');
                $visor = new UbillingVisor();
                $maxQual = (ubRouting::checkGet('fullsize')) ? true : false;
                header('Content-Type: application/json');
                switch ($chanCall) {
                    case 'preview':
                        die($visor->getUserChannelsPreviewJson(ubRouting::get('userid', 'int'), $maxQual));
                        break;
                    case 'authdata':
                        die($visor->getUserDvrAuthData(ubRouting::get('userid', 'int')));
                        break;
                    default:
                        die(json_encode(array()));
                        break;
                }
            } else {
                //no another NVR supported yet
                die(json_encode(array()));
            }
        } else {
            die('ERROR: NO PARAM OR USERID');
        }
    } else {
        die('ERROR: VISOR DISABLED');
    }
}
