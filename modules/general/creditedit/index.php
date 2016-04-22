<?php

if (cfr('CREDIT')) {

    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);
        $alterconf = $ubillingConfig->getAlter();
        $credit_limit = $alterconf['STRICT_CREDIT_LIMIT'];

        // change credit if need
        if (isset($_POST['newcredit'])) {
            $rawCredit = $_POST['newcredit'];

            if (isset($alterconf['NEGATIVE_CREDIT_ALLOWED'])) {
                $credit = ($alterconf['NEGATIVE_CREDIT_ALLOWED']) ? $rawCredit : vf($rawCredit);
            } else {
                $credit = vf($rawCredit);
            }
            //checking money format
            if (zb_checkMoney($credit)) {
                //credit limit check
                if ($credit_limit != 'DISABLED') {
                    if ($credit <= $credit_limit) {
                        $billing->setcredit($login, $credit);
                        log_register('CHANGE Credit (' . $login . ') ON ' . $credit);
                    }
                } else {
                    $billing->setcredit($login, $credit);
                    log_register('CHANGE Credit (' . $login . ') ON ' . $credit);
                }
                rcms_redirect("?module=creditedit&username=" . $login);
            } else {
                show_error(__('Wrong format of money sum'));
            }
        }

        $current_credit = zb_UserGetStargazerData($login);
        $current_credit = $current_credit['Credit'];
        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';


        //check is user corporate?
        $alter_conf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
        if ($alter_conf['USER_LINKING_ENABLED']) {
            if ($alter_conf['USER_LINKING_CREDIT']) {
                if (cu_IsChild($login)) {
                    $allchildusers = cu_GetAllLinkedUsers();
                    $parent_link = $allchildusers[$login];
                    rcms_redirect("?module=corporate&userlink=" . $parent_link . "&control=credit");
                }

                if (cu_IsParent($login)) {
                    $allparentusers = cu_GetAllParentUsers();
                    $parent_link = $allparentusers[$login];
                    rcms_redirect("?module=corporate&userlink=" . $parent_link . "&control=credit");
                }
            }
        }

// Edit form construct
        $fieldnames = array('fieldname1' => __('Current credit'), 'fieldname2' => __('New credit'));
        $fieldkey = 'newcredit';
        $form = web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_credit);
        $form.=web_UserControls($login);

        show_window(__('Edit credit'), $form);
    }
} else {
    show_error(__('You cant control this module'));
}
?>
