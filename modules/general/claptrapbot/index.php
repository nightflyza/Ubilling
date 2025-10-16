<?php

$clapTrapEnabled = $ubillingConfig->getAlterParam('CLAPTRAPBOT_ENABLED');
if ($clapTrapEnabled) {
    $botToken = $ubillingConfig->getAlterParam('CLAPTRAPBOT_TOKEN');
    if (!empty($botToken)) {
            $botAuthString=$ubillingConfig->getAlterParam('CLAPTRAPBOT_AUTH_STRING');
            $botAuthOk=false;
            if (!empty($botAuthString)) {
                if (ubRouting::checkGet('auth')) {
                    if (ubRouting::get('auth') == $botAuthString) {
                        $botAuthOk=true;
                    }
                }
            } else {
                $botAuthOk=true;
            }

            if ($botAuthOk) {
                $botDebugFlag = $ubillingConfig->getAlterParam('CLAPTRAPBOT_DEBUG');
                $botDebugFlag = ($botDebugFlag) ? true : false;
                $clapTrap = new ClapTrapBot($botToken);
                $clapTrap->setDebug($botDebugFlag);
    
               
                $clapTrap->listen();
                die();
            } else {
                show_error(__('Authentication failed'));
            }
   
    } else {
        show_error(__('Bot token is not set'));
    }
} else {
    show_error(__('This module is disabled'));
}
