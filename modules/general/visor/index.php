<?php

if (cfr('VISOR')) {

    $altCfg = $ubillingConfig->getAlter();

    if ($altCfg['VISOR_ENABLED']) {



        $visor = new UbillingVisor();
        //basic controls
        show_window('', $visor->panel());


        //users listing
        if (wf_CheckGet(array('ajaxusers'))) {
            $visor->ajaxUsersList();
        }

        //user cameras listing
        if (wf_CheckGet(array('ajaxusercams'))) {
            $visor->ajaxUserCams($_GET['ajaxusercams']);
        }

        //all available cameras listing
        if (wf_CheckGet(array('ajaxallcams'))) {
            $visor->ajaxAllCams();
        }

        //users creation
        if (wf_CheckPost(array('newusercreate', 'newusername'))) {
            $visor->createUser();
            rcms_redirect($visor::URL_ME . $visor::URL_USERS);
        }

        //all cameras listing
        if (wf_CheckGet(array('cams'))) {
            show_window(__('Cams'), $visor->renderCamerasContainer($visor::URL_ME . $visor::URL_ALLCAMS));
        }

        //users deletion
        if (wf_CheckGet(array('deleteuserid'))) {
            $deletionResult = $visor->deleteUser($_GET['deleteuserid']);
            if (empty($deletionResult)) {
                rcms_redirect($visor::URL_ME . $visor::URL_USERS);
            } else {
                show_error($deletionResult);
                show_window('', wf_BackLink($visor::URL_ME . $visor::URL_USERS));
            }
        }

        //camera creation
        if (wf_CheckPost(array('newcameravisorid', 'newcameralogin'))) {
            $visor->createCamera();
            rcms_redirect($visor::URL_ME . $visor::URL_USERVIEW . $_POST['newcameravisorid']);
        }



        //users list rendering
        if (wf_CheckGet(array('users'))) {
            $userCreateForm = $visor->renderUserCreateForm();
            $userCreateControls = ' ' . wf_modalAuto(web_add_icon(__('User registration')), __('User registration'), $userCreateForm);
            show_window(__('Users') . $userCreateControls, $visor->renderUsers());
        }


        //camera user detection on black magic action
        if (wf_CheckGet(array('username'))) {
            $userLogin = $_GET['username'];
            $userIdDetected = $visor->getCameraUser($userLogin);
            if (!empty($userIdDetected)) {
                rcms_redirect($visor::URL_ME . $visor::URL_USERVIEW . $userIdDetected);
            } else {
                //new camera creation interface
                show_window(__('Create camera'), $visor->renderCameraCreateInterface($userLogin));
                show_window('', web_UserControls($userLogin));
            }
        }


        //user profile rendering
        if (wf_CheckGet(array('showuser'))) {
            show_window(__('User profile'), $visor->renderUserProfile($_GET['showuser']));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>