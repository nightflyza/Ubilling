<?php

if (cfr('DDT')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['DDT_ENABLED']) {
        if (@$altCfg['DEALWITHIT_ENABLED']) {
            $greed = new Avarice();
            $avidity = $greed->runtime('DOOMSDAYTARIFFS');
            if (!empty($avidity)) {
                $ddt = new DoomsDayTariffs();

                if (ubRouting::checkPost($avidity['V']['CS'])) {
                    $avidity_m = $avidity['M']['ODIN'];
                    $avidity['V']['CR'] = $ddt->$avidity_m();
                    if (empty($avidity['V']['CR'])) {
                        ubRouting::nav($ddt::URL_ME);
                    } else {
                        show_error($avidity['V']['CR']);
                        show_window('', wf_BackLink($ddt::URL_ME));
                    }
                }

                if (ubRouting::checkGet($avidity['V']['DT'])) {
                    $avidity_m = $avidity['M']['CHIKA'];
                    $avidity['V']['DR'] = $ddt->$avidity_m(ubRouting::get($avidity['V']['DT']));
                    if (empty($avidity['V']['DR'])) {
                        ubRouting::nav($ddt::URL_ME);
                    } else {
                        show_error($avidity['V']['DR']);
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

                $avidity_m = $avidity['M']['ASAKO'];
                show_window('', $ddt->$avidity_m());

                if (!ubRouting::checkGet($avidity['Y']['TWELVE'])) {
                    $avidity_m = $avidity['M']['MEGUMIN'];
                    show_window(__($avidity['L']['RAM']), $ddt->$avidity_m());
                    show_window(__('Forced tariffs charge'), $ddt->renderChargeOpsList());
                } else {
                    if (!ubRouting::checkGet('mode')) {
                        if (ubRouting::checkGet($avidity['V']['SAD'])) {
                            $avidity_m = $avidity['M']['BUTTRUE'];
                            $ddt->$avidity_m();
                        }

                        $avidity_m = $avidity['M']['SUBARU'];
                        show_window(__($avidity['L']['LYAK']), $ddt->$avidity_m(ubRouting::get($avidity['V']['ZHEKA'])));
                    } else {
                        $hMode=ubRouting::get('mode','gigasafe');
                        switch ($hMode) {
                            case 'fch':
                                show_window(__('Forced charges history'),$ddt->renderChargesHistoryContainer());
                                break;
                        }
                        
                    }
                }
                zb_BillingStats(true);
            } else {
                show_error(__('No license key available'));
            }
        } else {
            show_error(__('Module') . ' "' . __('Deal with it') . '" ' . __('disabled'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
