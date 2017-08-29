<?php

if (cfr('PLIPCHANGE')) {

    $ipChange = new IpChange();

//rendering ajax IP selector container data
    if (wf_CheckGet(array('ajserviceid'))) {
        die($ipChange->ajIpSelector($_GET['ajserviceid']));
    }

    if (wf_CheckGet(array('username'))) {
        //user is here
        $userLogin = $_GET['username'];
        $ipChange->setLogin($userLogin);
        $ipChange->initUserParams();

        //change IP if required
        if (wf_CheckPost(array('ipselector', 'serviceselector'))) {
            $changeResult = $ipChange->changeUserIp($_POST['serviceselector'], $_POST['ipselector']);
            if (empty($changeResult)) {
                rcms_redirect($ipChange::URL_ME . '&username=' . $userLogin);
            } else {
                show_error($changeResult);
            }
        }

        //rendering interface
        show_window('', $ipChange->renderCurrentIp());
        show_window(__('Change user IP'), $ipChange->renderMainForm());
        if ((!cfr('BRANCHES')) OR ( cfr('ROOT'))) {
            show_window(__('IP usage stats'), $ipChange->renderFreeIpStats());
        }
        show_window('', web_UserControls($userLogin));
    } else {
        show_error(__('Something went wrong'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
