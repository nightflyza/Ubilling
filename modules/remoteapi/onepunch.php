<?php

//One-Punch Scripts startup
if (ubRouting::get('action') == 'onepunch') {
    if ($alterconf['ONEPUNCH_ENABLED']) {
        if (ubRouting::checkGet('param')) {
            $onePunchScriptAlias = ubRouting::get('param');
            $onePunchScripts = new OnePunch($onePunchScriptAlias);
            $onePunchScriptCode = $onePunchScripts->getScriptContent($onePunchScriptAlias);
            eval($onePunchScriptCode);
            die('OK:ONEPUNCH');
        } else {
            die('ERROR:NO_PARAM');
        }
    } else {
        die('ERROR:ONEPUNCH_DISABLED');
    }
}
