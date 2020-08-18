<?php

if (cfr('TARIFFS')) {

    //basic check for availability of any existing traffic classes
    $dirs = getAllDirs();
    if (empty($dirs)) {
        $alert = wf_tag('script', false, '', 'type="text/javascript"');
        $alert .= 'alert("' . __('Error') . ': ' . __('No traffic classes available, now you will be redirected to the module with which you can add traffic classes') . '");';
        $alert .= wf_tag('script', true);
        print($alert);
        ubRouting::nav('?module=rules');
        die();
    }


    //new stargazer tariff creation
    if (ubRouting::checkPost('options') AND ubRouting::get('action') == 'new') {
        $newTariffOptions = ubRouting::post('options');
        if (isset($newTariffOptions['TARIFF'])) {
            $newTariffName = $newTariffOptions['TARIFF'];
            $newTariffName = zb_TariffNameFilter($newTariffName);

            //check for tariff fee valid money format
            if (zb_checkMoney($newTariffOptions['Fee'])) {
                if (!empty($newTariffName)) {
                    $currentTariffData = billing_gettariff($newTariffName);
                    if (empty($currentTariffData)) {
                        //The same tariff doesnt exists
                        $billing->createtariff($newTariffName); //just creating empty tariff
                        $billing->edittariff($newTariffName, $newTariffOptions); //and set options property to it
                        log_register('TARIFF CREATE `' . $newTariffName . '`');
                        ubRouting::nav('?module=tariffs&action=edit&tariffname=' . $newTariffName);
                    } else {
                        log_register('TARIFF CREATE `' . $newTariffName . '` FAIL ALREADY EXIST');
                    }
                }
            } else {
                log_register('TARIFF CREATE `' . $newTariffName . '` FAIL FEE `' . $newTariffOptions['Fee'] . '`');
            }
        }
    }

    if (ubRouting::checkGet('action')) {
        $tariffName = ubRouting::get('tariffname');
        if ($tariffName) {
            //existing tariff deletion
            if (ubRouting::get('action') == 'delete') {
                if (!zb_TariffProtected($tariffName)) { // is tariff is not used by any users?
                    $billing->deletetariff($tariffName); //tariff deletion here
                    log_register("TARIFF DELETE `" . $tariffName . "`");
                    zb_LousyTariffDelete($tariffName);
                    zb_TariffDeleteSpeed($tariffName);
                    $dshaper = new DynamicShaper();
                    $dshaper->flushTariff($tariffName);
                    ubRouting::nav('?module=tariffs');
                } else {
                    log_register("TARIFF DELETE TRY USED `" . $tariffName . "`");
                    show_error(__('Tariff is used by some users'));
                    show_window('', wf_BackLink('?module=tariffs', '', true));
                }
            }

            //existing tariff editing here
            if (ubRouting::get('action') == 'edit') {
                $tariffOptions = ubRouting::post('options');
                if (!empty($tariffOptions)) {

                    if (zb_checkMoney($tariffOptions['Fee'])) {
                        $billing->edittariff($tariffName, $tariffOptions); //pushing new tariff options to stargazer
                        log_register('TARIFF CHANGE `' . $tariffName . '`');
                        rcms_redirect('?module=tariffs&action=edit&tariffname=' . $tariffName);
                    } else {
                        log_register('TARIFF CHANGE `' . $tariffName . '` FAIL FEE `' . $tariffOptions['Fee'] . '`');
                    }
                }

                //rendering tariff editing form
                show_window(__('Edit Tariff'), web_TariffEditForm($tariffName));
                show_window('', wf_BackLink('?module=tariffs'));
            }
        }

        //new tariff creation form rendering
        if (ubRouting::get('action') == 'new') {
            show_window(__('Create new tariff'), web_TariffCreateForm());
            show_window('', wf_BackLink("?module=tariffs"));
        }
    }

    ///just available tariffs list rendering
    if (!ubRouting::checkGet('action')) {
        show_window(__('Available tariffs'), web_TariffLister());
    }
} else {
    show_error(__('You cant control this module'));
}

