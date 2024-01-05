<?php

if (cfr('LIFESTORY')) {
    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username', 'mres');

//weblogs user parsing    
        $searchType = (ubRouting::checkGet('strict')) ? true : false;
        $form = web_GrepLogByUser($login, $searchType);

//raw database fields display
        if (cfr('ROOT')) {
            $userDataRaw = zb_UserGetStargazerData($login);
            if (!empty($userDataRaw)) {
                $userdump = print_r($userDataRaw, true);
                $userdump = wf_tag('pre') . $userdump . wf_tag('pre', true);
                $form .= wf_modal(wf_img('skins/brain.png') . ' ' . __('User inside'), __('User inside'), $userdump, 'ubButton', '800', '600') . ' ';
                //nethosts data
                $userip = $userDataRaw['IP'];
                $nethostRaw = zb_MultinetGetNethostData($userip);
                if (!empty($nethostRaw)) {
                    $nethostsCount = sizeof($nethostRaw);
                    if ($nethostsCount > 1) {
                        show_error(__('Strange exception') . ': DUPLICATE_NETHOST_DATA');
                    }
                    $nethostdump = print_r($nethostRaw, true);
                    $nethostdump = wf_tag('pre') . ($nethostdump) . wf_tag('pre', true);
                    $form .= wf_modal(wf_img('skins/menuicons/multinet.png') . ' ' . __('User Networking'), __('User Networking'), $nethostdump, 'ubButton', '400', '400') . ' ';
                } else {
                    show_error(__('Strange exception') . ': EMPTY_NETHOST_DATA');
                }
            } else {
                show_error(__('Strange exception') . ': EMPTY_DATABASE_USERDATA');
            }
        }

        if (ubRouting::checkGet('strict')) {
            $form .= wf_Link('?module=lifestory&username=' . $login, wf_img('skins/icon_search_small.gif') . ' ' . __('Normal search'), false, 'ubButton');
        } else {
            $form .= wf_Link('?module=lifestory&username=' . $login . '&strict=true', wf_img('skins/track_icon.png') . ' ' . __('Strict search'), false, 'ubButton');
        }

        $form .= wf_delimiter() . web_UserControls($login);

        show_window(__('User lifestory'), $form);
    } else {
        show_error(__('Strange exception') . ': GET_NO_USERNAME');
    }
} else {
    show_error(__('You cant control this module'));
}

