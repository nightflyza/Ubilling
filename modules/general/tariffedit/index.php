<?php

if (cfr('TARIFFEDIT')) {
    $alter_conf = $ubillingConfig->getAlter();

    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);
        // change tariff  if need
        if (isset($_POST['newtariff'])) {

            $tariff = $_POST['newtariff'];
            if (!isset($_POST['nextmonth'])) {
                $billing->settariff($login, $tariff);
                log_register('CHANGE Tariff (' . $login . ') ON `' . $tariff . '`');
                //cache cleanup
                zb_UserGetAllDataCacheClean();
                //optional user reset
                if ($alter_conf['TARIFFCHGRESET']) {
                    $billing->resetuser($login);
                    log_register('RESET User (' . $login . ')');
                }
            } else {
                $billing->settariffnm($login, $tariff);
                log_register('CHANGE TariffNM (' . $login . ') ON `' . $tariff . '`');
            }

            //auto credit option handling
            if ($alter_conf['TARIFFCHGAUTOCREDIT']) {
                $newtariffprice = zb_TariffGetPrice($tariff);
                $billing->setcredit($login, $newtariffprice);
                log_register("CHANGE AutoCredit (" . $login . ") ON `" . $newtariffprice . '`');
            }

            if (isset($alter_conf['SIGNUP_PAYMENTS']) && !empty($alter_conf['SIGNUP_PAYMENTS'])) {
                if (isset($_POST['charge_signup_price'])) {
                    $has_paid = zb_UserGetSignupPricePaid($login);
                    $old_price = zb_UserGetSignupPrice($login);
                    $new_price = zb_TariffGetAllSignupPrices();
                    if (!isset($new_price[$tariff])) {
                        zb_TariffCreateSignupPrice($tariff, 0);
                        $new_price = zb_TariffGetAllSignupPrices();
                    }
                    if ($new_price[$tariff] >= $has_paid) {
                        $cash = $old_price - $new_price[$tariff];
                        zb_UserChangeSignupPrice($login, $new_price[$tariff]);
                        $billing->addcash($login, $cash);
                        log_register("CHARGE SignupPriceFee(" . $login . ") " . $cash . " ACCORDING TO " . $tariff);
                    } else {
                        show_window('', wf_modalOpened(__('Error'), __('You may not setup connection payment less then user has already paid!'), '400', '150'));
                    }
                }
            }
        }

        $current_tariff = zb_UserGetStargazerData($login);
        $current_tariff = $current_tariff['Tariff'];
        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';


        //check is user corporate?
        if ($alter_conf['USER_LINKING_ENABLED']) {
            if ($alter_conf['USER_LINKING_TARIFF']) {
                if (cu_IsChild($login)) {
                    $allchildusers = cu_GetAllLinkedUsers();
                    $parent_link = $allchildusers[$login];
                    rcms_redirect("?module=corporate&userlink=" . $parent_link . "&control=tariff");
                }

                if (cu_IsParent($login)) {
                    $allparentusers = cu_GetAllParentUsers();
                    $parent_link = $allparentusers[$login];
                    rcms_redirect("?module=corporate&userlink=" . $parent_link . "&control=tariff");
                }
            }
        }


// Edit form construct
        $fieldname = __('Current tariff');
        $fieldkey = 'newtariff';

//DDT locks
        $form = '';
        $formAccessible = true;
        $additionalDelimiter = '';
        if (@$alter_conf['DDT_ENABLED']) {
            $ddt = new DoomsDayTariffs(true);
            $messages = new UbillingMessageHelper();
            $currentDDTTariffs = $ddt->getCurrentTariffsDDT();
            if (isset($currentDDTTariffs[$current_tariff])) {
                $ddtOptions = $currentDDTTariffs[$current_tariff];
                $form .= $messages->getStyledMessage(__('Current tariff') . ' ' . $current_tariff . ' ' . __('will be changed to') . ' ' . $ddtOptions['tariffmove'] . ' ' . __('automatically'), 'info');
                $additionalDelimiter .= wf_delimiter(0);
                //form lock
                $dwiTaskData = $ddt->getTaskCreated($login);
                if ($dwiTaskData) {
                    $form .= $messages->getStyledMessage(__('On') . ' ' . $dwiTaskData['date'] . ' ' . __('is already planned tariff change to') . ' ' . $dwiTaskData['param'], 'warning');
                    $formAccessible = false;
                }
            }
        }
//old style tariff selector
        if ($formAccessible) {
            $form .= $additionalDelimiter;
            if (!isset($_GET['oldform'])) {
                $form .= web_EditorTariffFormWithoutLousy($fieldname, $fieldkey, $useraddress, $current_tariff);
            } else {
                $form .= web_EditorTariffForm($fieldname, $fieldkey, $useraddress, $current_tariff);
            }


            $form .= wf_Link('?module=tariffedit&username=' . $login, wf_img('skins/done_icon.png') . ' ' . __('Popular tariff selector'), false, 'ubButton');
            $form .= wf_Link('?module=tariffedit&username=' . $login . '&oldform=true', wf_img('skins/categories_icon.png') . ' ' . __('Full tariff selector'), false, 'ubButton');
        }
        $form .= wf_delimiter();

        $form .= web_UserControls($login);
// show form
        show_window(__('Edit tariff'), $form);
    }
} else {
    show_error(__('You cant control this module'));
}
?>
