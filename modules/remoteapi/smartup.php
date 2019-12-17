<?php

//Basic smartup callbacks
if (ubRouting::get('action') == 'smartup') {
    if ($alterconf['SMARTUP_ENABLED']) {
        $smartup = new SmartUP();
        $callback = ubRouting::get('param');
        if (!empty($callback)) {
            switch ($callback) {
                case 'user':
                    if (ubRouting::checkGet('ip')) {
                        $authResult = $smartup->getAuthByIP(ubRouting::get('ip'));
                        $smartup->renderReply($authResult);
                    } else {
                        die('ERROR: NO IP');
                    }
                    break;
                case 'info':
                    if (ubRouting::checkGet('login')) {
                        $userInfoResult = $smartup->getUserInfo(ubRouting::get('login'));
                        $smartup->renderReply($userInfoResult);
                    } else {
                        die('ERROR: NO LOGIN');
                    }
                    break;
                default :
                    die('ERROR: WRONG PARAM');
                    break;
            }
        } else {
            die('ERROR: EMPTY PARAM');
        }
    } else {
        die('ERROR: SMARTUP DISABLED');
    }
}