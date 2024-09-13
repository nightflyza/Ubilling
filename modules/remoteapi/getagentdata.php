<?php

//associated agent data
if (ubRouting::get('action') == 'getagentdata') {
    if (ubRouting::checkGet('param')) {
        $userLogin = ubRouting::get('param');
        $gr=new GRes();
        $agentData=$gr->getUserAssignedAgentData($userLogin);
        header('Content-Type: application/json');
        die(json_encode($agentData));
    } else {
        die('ERROR:NO_LOGIN_PARAM');
    }
}
