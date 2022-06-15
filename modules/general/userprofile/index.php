<?php

if (cfr('USERPROFILE')) {
    if ($ubillingConfig->getAlterParam('ROS_NAS_PPPOE_SESSION_INFO_IN_PROFLE')) {
        if (ubRouting::checkPost('GetPPPoEInfo') and ubRouting::checkPost('usrlogin')) {
            $infoBlock = zb_GetROSPPPoESessionInfo(ubRouting::post('usrlogin'), wf_getBoolFromVar(ubRouting::post('returnAsHTML'), true), wf_getBoolFromVar(ubRouting::post('returnInSpoiler'), true));
            die($infoBlock);
        }
    }

    if (ubRouting::checkGet('username', false)) {
        $login = ubRouting::get('username', 'mres');
        $login = trim($login);
        try {
            $profile = new UserProfile($login);
            show_window(__('User profile'), $profile->render());

            if (ubRouting::checkGet('justregistered')) {
                if (!$ubillingConfig->getAlterParam('BORING_USERREG')) {
                    $newUserRegisteredNotification = '';
                    @$awesomeness = rcms_scandir('skins/awesomeness/');
                    if (!empty($awesomeness)) {
                        $awesomenessRnd = array_rand($awesomeness);
                        $awesomeness = $awesomeness[$awesomenessRnd];
                        $newUserRegisteredNotification .= wf_tag('center') . wf_img_sized('skins/awesomeness/' . $awesomeness, '', '256') . wf_tag('center', true);
                    }
                    $messages = new UbillingMessageHelper();
                    $newUserRegisteredNotification .= $messages->getStyledMessage(__('Its incredible, but you now have a new user') . '!', 'success');
                    $newUserRegisteredNotification .= wf_CleanDiv();
                    $newUserRegisteredNotification .= wf_tag('br');
                    $newUserRegisteredNotification .= web_UserControls($login);
                    show_window('', wf_modalOpenedAuto(__('Success') . '!', $newUserRegisteredNotification));
                } else {
                    /**
                     * And how do you live such a boring life? 
                     * Do you like to return after a boring job to your boring house 
                     * to wait for the end of your boring life among boring gray walls?
                     */
                }
            }
        } catch (Exception $exception) {
            show_error(__('Strange exception') . ': ' . $exception->getMessage());
            show_window('', wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true));
        }
    } else {
        throw new Exception('GET_NO_USERNAME');
    }
} else {
    show_error(__('Access denied'));
}

