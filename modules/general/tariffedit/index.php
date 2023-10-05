<?php

if (cfr('TARIFFEDIT')) {
    $altCfg = $ubillingConfig->getAlter();

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username', 'mres');
        $userData = zb_UserGetStargazerData($login);
        $skipLousyTariffsFlag = (ubRouting::checkGet('fulltariffslist')) ? false : true;

        //checking is user data available?
        if (!empty($userData)) {
            $userAddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';
            $current_tariff = '';
            if (isset($userData['Tariff'])) {
                $current_tariff = $userData['Tariff'];
            }


            //change tariff  if request received
            if (ubRouting::checkPost('newtariff')) {
                $tariff = ubRouting::post('newtariff');
                if (!ubRouting::checkPost('nextmonth')) {
                    $billing->settariff($login, $tariff);
                    log_register('CHANGE Tariff (' . $login . ') ON `' . $tariff . '`');
                    //cache cleanup
                    zb_UserGetAllDataCacheClean();
                    //optional user reset
                    if ($altCfg['TARIFFCHGRESET']) {
                        $billing->resetuser($login);
                        log_register('RESET User (' . $login . ')');
                    }
                } else {
                    $billing->settariffnm($login, $tariff);
                    log_register('CHANGE TariffNM (' . $login . ') ON `' . $tariff . '`');
                }

                //auto credit option handling
                if ($altCfg['TARIFFCHGAUTOCREDIT']) {
                    $newtariffprice = zb_TariffGetPrice($tariff);
                    $billing->setcredit($login, $newtariffprice);
                    log_register("CHANGE AutoCredit (" . $login . ") ON `" . $newtariffprice . '`');
                }

                //signup payments processing
                if (isset($altCfg['SIGNUP_PAYMENTS']) && !empty($altCfg['SIGNUP_PAYMENTS'])) {
                    if (ubRouting::checkPost('charge_signup_price')) {
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

                //redirecting back to original form
                if ($skipLousyTariffsFlag) {
                    ubRouting::nav('?module=tariffedit&username=' . $login);
                } else {
                    ubRouting::nav('?module=tariffedit&username=' . $login . '&fulltariffslist=true');
                }
            }

            //check is user corporate?
            if ($altCfg['USER_LINKING_ENABLED']) {
                if ($altCfg['USER_LINKING_TARIFF']) {
                    if (cu_IsChild($login)) {
                        $allchildusers = cu_GetAllLinkedUsers();
                        $parent_link = $allchildusers[$login];
                        ubRouting::nav("?module=corporate&userlink=" . $parent_link . "&control=tariff");
                    }

                    if (cu_IsParent($login)) {
                        $allparentusers = cu_GetAllParentUsers();
                        $parent_link = $allparentusers[$login];
                        ubRouting::nav("?module=corporate&userlink=" . $parent_link . "&control=tariff");
                    }
                }
            }


//DDT locks
            $form = '';
            $formAccessible = true;
            $additionalDelimiter = '';
            if (@$altCfg['DDT_ENABLED']) {
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

// Edit form construct
            $fieldname = __('Current tariff');
            $fieldkey = 'newtariff';

//rendering tariff selector
            if ($formAccessible) {
                $form .= $additionalDelimiter;
                $form .= web_EditorTariffForm($fieldname, $fieldkey, $userAddress, $current_tariff, $skipLousyTariffsFlag);

                $form .= wf_Link('?module=tariffedit&username=' . $login, wf_img('skins/done_icon.png') . ' ' . __('Popular tariff selector'), false, 'ubButton');
                $form .= wf_Link('?module=tariffedit&username=' . $login . '&fulltariffslist=true', wf_img('skins/categories_icon.png') . ' ' . __('Full tariff selector'), false, 'ubButton');
            }
            $form .= wf_delimiter();

            $form .= web_UserControls($login);
// render form
            show_window(__('Edit tariff'), $form);
        } else {
            show_error(__('Strange exception') . ': EX_EMPTY_USER_DATA');
            show_window('', wf_tag('center') . wf_img('skins/unicornchainsawwrong.png') . wf_tag('center', true));
        }
    } else {
        show_error(__('Strange exception') . ': EX_TARIFFEDIT_NO_USERNAME');
        show_window('', wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true));
    }
} else {
    show_error(__('You cant control this module'));
}
