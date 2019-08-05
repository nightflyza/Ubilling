<?php

//associated agent data
if ($_GET['action'] == 'getagentdata') {
    if (isset($_GET['param'])) {
        $userLogin = $_GET['param'];
        $allUserAddress = zb_AddressGetFulladdresslistCached();
        $userAddress = @$allUserAddress[$userLogin];
        $agentData = zb_AgentAssignedGetDataFast($userLogin, $userAddress);
        die(json_encode($agentData));
    } else {
        die('ERROR:NO_LOGIN_PARAM');
    }
}