<?php

$altCfg = $ubillingConfig->getAlter();

if ($altCfg['MG_ENABLED']) {
    if (cfr('MEGOGO')) {
        $interface = new MegogoInterface();


        //primary control panelas
        show_window('', $interface->renderPanel());
        //tariffs management
        if (wf_CheckGet(array('tariffs'))) {

            //tariff creation
            if (wf_CheckPost(array('newtariffname'))) {
                $tariffCreateResult = $interface->tariffCreate();
                if (!$tariffCreateResult) {
                    rcms_redirect($interface::URL_ME . '&' . $interface::URL_TARIFFS);
                } else {
                    show_window(__('Something went wrong'), $tariffCreateResult);
                }
            }

            //tariff editing
            if (wf_CheckPost(array('edittariffid', 'edittariffname'))) {
                $tariffSaveResult = $interface->tariffSave();
                if (!$tariffSaveResult) {
                    rcms_redirect($interface::URL_ME . '&' . $interface::URL_TARIFFS);
                } else {
                    show_window(__('Something went wrong'), $tariffSaveResult);
                }
            }

            //tariff deletion
            if (wf_CheckGet(array('deletetariffid'))) {
                $tariffDeletionResult = $interface->tariffDelete($_GET['deletetariffid']);
                if (!$tariffDeletionResult) {
                    rcms_redirect($interface::URL_ME . '&' . $interface::URL_TARIFFS);
                } else {
                    show_window(__('Something went wrong'), $tariffDeletionResult);
                }
            }

            show_window(__('Available tariffs'), $interface->renderTariffs());
            show_window(__('Create new tariff'), $interface->tariffCreateForm());
        }

        //subscriptions management
        if (wf_CheckGet(array('subscriptions'))) {
            //jqdt data renderer
            if (wf_CheckGet(array('ajsubs'))) {
                $interface->subscribtionsListAjax();
            }
            //active subscriptions list
            show_window(__('Subscriptions'), $interface->renderSubscribtions());
        }

        //subscriptions manual control
        if (wf_CheckGet(array('subview'))) {
            $subId = $_GET['subid'];
            if (wf_CheckGet(array('subid', 'maction'))) {
                $mactionResult = $interface->catchManualAction();
                if (!$mactionResult) {
                    rcms_redirect($interface::URL_ME . '&' . $interface::URL_SUBVIEW . '&subid=' . $subId);
                } else {
                    show_window(__('Something went wrong'), $mactionResult);
                }
            }
            show_window(__('Edit'), $interface->renderSubManagerForm($subId));
            show_window('', wf_Link($interface::URL_ME . '&' . $interface::URL_SUBS, __('Back'), false, 'ubButton'));
        }

        //subscriptions report
        if (wf_CheckGet(array('reports'))) {
            if ($altCfg['MG_SPREAD']) {
                //daily accounting report
                show_window(__('Subscriptions report'), $interface->renderSubscribtionsReportDaily());
            } else {
                //montly accounting report
                show_window(__('Subscriptions report'), $interface->renderSubscribtionsReportMonthly());
            }
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>