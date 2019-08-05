<?php

//One-Punch Scripts startup
if ($_GET['action'] == 'onepunch') {
    if ($alterconf['ONEPUNCH_ENABLED']) {
        if (wf_CheckGet(array('param'))) {
            $onePunchScriptAlias = $_GET['param'];
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