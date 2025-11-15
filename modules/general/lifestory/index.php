<?php

if (cfr('LIFESTORY')) {
    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username', 'login');

//weblogs user parsing    
        $deepSearch = (ubRouting::checkGet('deep')) ? true : false;
        $form = web_GrepLogByUser($login, $deepSearch);
//some module controls here
$lifestoryControls = '';
$lifestoryControls .= wf_BackLink(UserProfile::URL_PROFILE . $login);

//raw database fields display
        if (cfr('ROOT')) {
            $userDataRaw = zb_UserGetStargazerData($login);
            if (!empty($userDataRaw)) {
                $userdump=web_RenderSomeArrayAsTable($userDataRaw);
                $lifestoryControls .= wf_modal(wf_img('skins/brain.png') . ' ' . __('User inside'), __('User inside'), $userdump, 'ubButton', '800', '600') . ' ';
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
                    $nethostdump=web_RenderSomeArrayAsTable($nethostRaw);
                    $lifestoryControls .= wf_modal(wf_img('skins/menuicons/multinet.png') . ' ' . __('User Networking'), __('User Networking'), $nethostdump, 'ubButton', '400', '400') . ' ';
                } else {
                    show_error(__('Strange exception') . ': EMPTY_NETHOST_DATA');
                }
            } else {
                show_error(__('Strange exception') . ': EMPTY_DATABASE_USERDATA');
            }
        }

        $lifestoryDefaultDepth = $ubillingConfig->getAlterParam('LIFESTORY_DEFAULT_DEPTH', 0);
        if ($lifestoryDefaultDepth > 0) {
            if (!ubRouting::checkGet('deep')) {
                $lifestoryControls .= wf_Link('?module=lifestory&username=' . $login . '&deep=true', wf_img('skins/track_icon.png') . ' ' . __('Deep search'), false, 'ubButton');
            } else {
                $lifestoryControls .= wf_Link('?module=lifestory&username=' . $login , wf_img('skins/icon_search_small.gif') . ' ' . __('Normal search'), false, 'ubButton');
            }
        }

        //user navigation controls here
        $form .= wf_delimiter() . web_UserControls($login);

        $wTitle=__('User lifestory');
        if ($lifestoryDefaultDepth > 0) {
            if ($deepSearch) {
                $wTitle .= ', '.__('all').' '.__('events');
            } else {
                $wTitle .= ', '.__('latest') . ' ' . $lifestoryDefaultDepth . ' '.__('events');
            }
        }


        //module controls here
        show_window('', $lifestoryControls);

        //rendering lifestory
        show_window($wTitle, $form);
    } else {
        show_error(__('Strange exception') . ': GET_NO_USERNAME');
    }
} else {
    show_error(__('You cant control this module'));
}

