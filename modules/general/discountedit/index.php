<?php

if (cfr('DISCOUNTS')) {

    if ($ubillingConfig->getAlterParam('DISCOUNTS_ENABLED')) {
        if (ubRouting::checkGet('username')) {
            $login = ubRouting::get('username');
            $discounts = new Discounts();
            //catch new user discount
            if (ubRouting::checkPost($discounts::PROUTE_PERCENT, false)) {
                $discounts->saveDiscount($login);
                ubRouting::nav('?module=discountedit&username=' . $login);
            }
            $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';
            $currentUserDiscount = $discounts->getUserDiscount($login);


            $fieldnames = array('fieldname1' => __('Current discount') . ' (%)', 'fieldname2' => __('New discount'));
            $fieldkey = $discounts::PROUTE_PERCENT;
            $discountEditor = web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $currentUserDiscount, 'digits');

            show_window(__('Change discount'), $discountEditor);
            show_window('', web_UserControls($login));
            zb_BillingStats(true, 'discounts');
        } else {
            show_error(__('Strange exception') . ': ' . __('Empty login'));
            show_window('', wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}