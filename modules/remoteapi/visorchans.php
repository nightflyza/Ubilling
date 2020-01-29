<?php

//visor channels backend
if (ubRouting::get('action') == 'visorchans') {
    if ($alterconf['VISOR_ENABLED']) {
        if (ubRouting::checkGet(array('param', 'userid'))) {
            if ($alterconf['TRASSIRMGR_ENABLED']) {
                $chanCall = ubRouting::get('param');
                $visor = new UbillingVisor();
                switch ($chanCall) {
                    case 'preview':
                        die($visor->getUserChannelsPreviewJson(ubRouting::get('userid', 'int')));
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
    