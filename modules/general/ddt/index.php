<?php

if (cfr('DDT')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['DDT_ENABLED']) {
        if (@$altCfg['DEALWITHIT_ENABLED']) {
            $ddt = new DoomsDayTariffs();

            if (ubRouting::checkPost('createnewddtsignal')) {
                $creationResult = $ddt->createTariffDDT();
                if (empty($creationResult)) {
                    ubRouting::nav($ddt::URL_ME);
                } else {
                    show_error($creationResult);
                    show_window('', wf_BackLink($ddt::URL_ME));
                }
            }

            if (ubRouting::checkGet('deleteddtariff')) {
                $deletionResult = $ddt->deleteTariffDDT(ubRouting::get('deleteddtariff'));
                if (empty($deletionResult)) {
                    ubRouting::nav($ddt::URL_ME);
                } else {
                    show_error($deletionResult);
                    show_window('', wf_BackLink($ddt::URL_ME));
                }
            }

            if (ubRouting::checkPost($ddt::PROUTE_CH_CREATE)) {
                $chRcR = $ddt->createChargeRule();
                if (empty($chRcR)) {
                    ubRouting::nav($ddt::URL_ME);
                } else {
                    show_error($chRcR);
                    show_window('', wf_BackLink($ddt::URL_ME));
                }
            }

            if (ubRouting::checkGet($ddt::ROUTE_CH_DELETE)) {
                $chRdR = $ddt->deleteChargeRule(ubRouting::get($ddt::ROUTE_CH_DELETE));
                if (empty($chRdR)) {
                    ubRouting::nav($ddt::URL_ME);
                } else {
                    show_error($chRdR);
                    show_window('', wf_BackLink($ddt::URL_ME));
                }
            }

            if (ubRouting::checkGet($ddt::ROUTE_CH_HISTAJX)) {
                $ddt->getChargesHistoryAjax();
            }

            show_window('', $ddt->renderControls());

            if (!ubRouting::checkGet('history')) {
                show_window(__('Available doomsday tariffs'), $ddt->renderTariffsList());
                show_window(__('Forced tariffs charge'), $ddt->renderChargeOpsList());
            } else {
                if (!ubRouting::checkGet('mode')) {
                    if (ubRouting::checkGet('ajax')) {
                        $ddt->getHistoryAjax();
                    }

                    show_window(__('History'), $ddt->renderHistoryContainer(ubRouting::get('username')));
                } else {
                    $hMode = ubRouting::get('mode', 'gigasafe');
                    switch ($hMode) {
                        case 'fch':
                            show_window(__('Forced charges history'), $ddt->renderChargesHistoryContainer());
                            break;
                    }
                }
            }
            zb_BillingStats(true);
        } else {
            show_error(__('Module') . ' "' . __('Deal with it') . '" ' . __('disabled'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
