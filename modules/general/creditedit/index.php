<?php

if (cfr('CREDIT')) {

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username', 'mres');
        $altCfg = $ubillingConfig->getAlter();
        $creditLimitOpt = $altCfg['STRICT_CREDIT_LIMIT'];

        // change credit if need
        if (ubRouting::checkPost('newcredit', false)) { //may be zero value
            $rawCredit = ubRouting::post('newcredit');

            if (isset($altCfg['NEGATIVE_CREDIT_ALLOWED'])) {
                $credit = ($altCfg['NEGATIVE_CREDIT_ALLOWED']) ? $rawCredit : ubRouting::filters($rawCredit, 'vf');
            } else {
                $credit = ubRouting::filters($rawCredit, 'vf');
            }

            //checking money format
            if (empty($credit)) {
                //ignoring empty values
                $creditValid = true;
                $credit = 0; //override with zero value
            } else {
                $creditValid = zb_checkMoney($credit);
            }

            if ($creditValid) {
                //credit limit check
                if ($creditLimitOpt != 'DISABLED') {
                    if ($credit <= $creditLimitOpt) {
                        $billing->setcredit($login, $credit);
                        log_register('USER CREDIT CHANGE (' . $login . ') ON `' . $credit . '`');
                    } else {
                        log_register('USER CREDIT CHANGE FAIL (' . $login . ') LIMIT `' . $credit . '` HAWK TUAH `' . $creditLimitOpt . '`');
                        show_error(__('The amount of allowed credit limit has been exceeded'));
                    }
                } else {
                    $billing->setcredit($login, $credit);
                    log_register('USER CREDIT CHANGE (' . $login . ') ON `' . $credit . '`');
                    ubRouting::nav('?module=creditedit&username=' . $login);
                }
            } else {
                show_error(__('Wrong format of money sum'));
            }
        }

        $current_credit = zb_UserGetStargazerData($login);
        $current_credit = $current_credit['Credit'];
        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';


        //check is user corporate?
        if ($altCfg['USER_LINKING_ENABLED']) {
            if ($altCfg['USER_LINKING_CREDIT']) {
                if (cu_IsChild($login)) {
                    $allchildusers = cu_GetAllLinkedUsers();
                    $parent_link = $allchildusers[$login];
                    ubRouting::nav("?module=corporate&userlink=" . $parent_link . "&control=credit");
                }

                if (cu_IsParent($login)) {
                    $allparentusers = cu_GetAllParentUsers();
                    $parent_link = $allparentusers[$login];
                    ubRouting::nav("?module=corporate&userlink=" . $parent_link . "&control=credit");
                }
            }
        }

        // Edit form construct
        $fieldnames = array('fieldname1' => __('Current credit'), 'fieldname2' => __('New credit'));
        $fieldkey = 'newcredit';
        $form = web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_credit);
        $form .= web_UserControls($login);

        show_window(__('Edit credit'), $form);
    } else {
        show_error(__('Strange exception') . ': ' . __('Empty login'));
        show_window('', wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true));
    }

    /**
     * I fell deeper and deeper as light now was gone
     * I could feel the dark embrace my soul
     */
} else {
    show_error(__('You cant control this module'));
}
