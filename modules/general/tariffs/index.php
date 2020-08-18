<?php

// check for right of current admin on this module
if (cfr('TARIFFS')) {

    $dirs = getAllDirs();

    if (empty($dirs)) {
        $alert = wf_tag('script', false, '', 'type="text/javascript"');
        $alert .= 'alert("' . __('Error') . ': ' . __('No traffic classes available, now you will be redirected to the module with which you can add traffic classes') . '");';
        $alert .= wf_tag('script', true);
        print($alert);
        rcms_redirect("?module=rules");
        die();
    }


    if (isset($_POST['options']['TARIFF'])) {
        $newtariffname = $_POST['options']['TARIFF'];
        $newtariffname = zb_TariffNameFilter($newtariffname);
        $tariffoptions = $_POST['options'];
        $tariffoptions['Fee'] = trim($tariffoptions['Fee']);
        if (!empty($newtariffname)) {
            $billing->createtariff($newtariffname);
            $billing->edittariff($newtariffname, $tariffoptions);
            log_register("TARIFF CREATE `" . $newtariffname . "`");
        }
    }

    if (isset($_GET['action'])) {
        if (isset($_GET['tariffname'])) {
            $tariffname = $_GET['tariffname'];

            if ($_GET['action'] == 'delete') {
                if (!zb_TariffProtected($tariffname)) {
                    $billing->deletetariff($tariffname);
                    log_register("TARIFF DELETE `" . $tariffname . "`");
                    zb_LousyTariffDelete($tariffname);
                    zb_TariffDeleteSpeed($tariffname);
                    $dshaper = new DynamicShaper();
                    $dshaper->flushTariff($tariffname);
                    rcms_redirect('?module=tariffs');
                } else {
                    log_register("TARIFF DELETE TRY USED `" . $tariffname . "`");
                    show_error(__('Tariff is used by some users'));
                    show_window('', wf_BackLink('?module=tariffs', '', true));
                }
            }

            if ($_GET['action'] == 'edit') {
                if (isset($_POST['options']['Fee'])) {
                    $tariffoptions = $_POST['options'];
                    $tariffoptions['Fee'] = trim($tariffoptions['Fee']);
                    $billing->edittariff($tariffname, $tariffoptions);
                    log_register("TARIFF CHANGE `" . $tariffname . "`");
                    rcms_redirect('?module=tariffs&action=edit&tariffname=' . $tariffname);
                }
                show_window(__('Edit Tariff'), web_TariffEditForm($tariffname));
                show_window('', wf_BackLink('?module=tariffs'));
            }
        }

        if ($_GET['action'] == 'new') {
            show_window(__('Create new tariff'), web_TariffCreateForm());
            show_window('', wf_BackLink("?module=tariffs"));
            if (isset($_POST['options']['TARIFF'])) {
                $tariffnameredirect = zb_TariffNameFilter($_POST['options']['TARIFF']);
                if (!empty($tariffnameredirect)) {

                    rcms_redirect('?module=tariffs&action=edit&tariffname=' . zb_TariffNameFilter($_POST['options']['TARIFF']));
                }
            }
        }
    }

    if (!wf_CheckGet(array('action'))) {
        show_window(__('Available tariffs'), web_TariffLister());
    }
} else {
    show_error(__('You cant control this module'));
}
?>
