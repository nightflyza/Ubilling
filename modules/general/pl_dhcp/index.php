<?php

if (cfr('PLDHCP')) {
    $alter_conf = $ubillingConfig->getAlter();
    if ($alter_conf['DHCP_ENABLED']) {

        if (ubRouting::checkGet('username')) {
            $userLogin = ubRouting::get('username');
            $userMac = '';
            $userIp = '';
            if (ubRouting::checkGet(array('userip', 'usermac'))) {
                $userIp = ubRouting::get('userip');
                $userMac = ubRouting::get('usermac');
            } else {
                $userData = zb_UserGetStargazerData($userLogin);
                if (!empty($userData)) {
                    $userIp = $userData['IP'];
                    $userMac = zb_MultinetGetMAC($userIp);
                }
            }

            $plDhcp = new DHCPPL($userLogin, $userIp, $userMac);
            //rendering current user mac info
            show_window('', $plDhcp->getMacLabel());

            //rendering user dhcp log data
            $winControl = '';
            if ($userLogin AND $userIp AND $userMac) {
                if (ubRouting::get('zen')) {
                    $winControl = wf_Link($plDhcp::URL_ME . '&username=' . $userLogin, wf_img('skins/log_icon_small.png', __('Normal')));
                } else {
                    $zenUrl = $plDhcp::URL_ME . '&username=' . $userLogin . '&userip=' . $userIp . '&usermac=' . $userMac . '&zen=true';
                    $winControl = wf_Link($zenUrl, wf_img('skins/zen.png', __('Zen')));
                }
            }

            if (ubRouting::checkGet('zen')) {
                $zenFlow = new ZenFlow($plDhcp->getFlowId(), $plDhcp->render(), $plDhcp->getTimeout());
                show_window($winControl . ' ' . __('User DHCP log') . ', ' . __('Zen'), $zenFlow->render());
            } else {
                show_window($winControl . ' ' . __('User DHCP log'), $plDhcp->render());
            }


            show_window('', web_UserControls($userLogin));
        } else {
            show_error(__('Strange exception') . ': ' . __('Empty login'));
            show_window('', wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}

