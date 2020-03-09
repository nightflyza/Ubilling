<?php

//associated agent data
if ($_GET['action'] == 'getagentdata') {
    if (isset($_GET['param'])) {
        $userLogin = $_GET['param'];
        $allUserData = zb_UserGetAllDataCache();
        if (isset($allUserData[$userLogin])) {
            $userData = $allUserData[$userLogin];
            $userAddress = $userData['cityname'] . ' ' . $userData['streetname'] . ' ' . $userData['buildnum'] . '/' . $userData['apt'];
        } else {
            $userAddress = '';
        }
        $agentData = zb_AgentAssignedGetDataFast($userLogin, $userAddress);
        die(json_encode($agentData));
    } else {
        die('ERROR:NO_LOGIN_PARAM');
    }
}