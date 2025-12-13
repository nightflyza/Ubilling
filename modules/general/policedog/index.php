<?php

if (cfr('POLICEDOG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['POLICEDOG_ENABLED']) {
        $greed = new Avarice();
        $avidity = $greed->runtime('POLICEDOG');
        if (!empty($avidity)) {
            $policedog = new $avidity['O']['INIT']();
            //render interface
            $avidity_m_face = $avidity['M']['FACE'];
            show_window('', $policedog->$avidity_m_face());

            //create new MAC records
            if (ubRouting::checkPost(array($avidity['P']['PULL']))) {
                $avidity_m_save = $avidity['M']['SAVE'];
                $createResult = $policedog->$avidity_m_save();
                if (empty($createResult)) {
                    rcms_redirect($policedog::URL_ME);
                } else {
                    show_window(__('Something went wrong'), $createResult);
                }
            }
            /**
             * Come on in and join our big top
             * I am your only ringmaster
             *
             * Everyone will leave here bewitched
             * Your pulse going faster
             * Yes, it's magic, must be magic
             * Pure black magic that we cannot control
             */
            //mac deletion
            if (ubRouting::checkGet(array($avidity['P']['MDEL']))) {
                $avidity_m_kill = $avidity['M']['KILL'];
                $policedog->$avidity_m_kill($_GET[$avidity['P']['MDEL']]);
                ubRouting::nav($policedog::URL_ME);
            }

            //alert deletion
            if (ubRouting::checkGet(array($avidity['P']['ADEL']))) {
                $dVoid = new DarkVoid();
                $dVoid->flushCache();
                $avidity_m_killa = $avidity['M']['KILLA'];
                $policedog->$avidity_m_killa($_GET[$avidity['P']['ADEL']]);
                ubRouting::nav($policedog::URL_ME . '&show=fastscan');
            }


            if (!ubRouting::checkGet(array('show'))) {
                //rendering database list
                show_window(__('Wanted MAC database'), $policedog->renderWandedMacList());
                zb_billingStats(true);
            } else {
                $showOpt = ubRouting::get('show');
                switch ($showOpt) {
                    case 'ajwlist':
                        $policedog->renderWantedMacListAjaxReply();
                        break;
                    case 'fastscan':
                        if (wf_CheckGet(array('forcefast'))) {
                            $policedog->fastScan();
                            rcms_redirect($policedog::URL_ME . '&show=fastscan');
                        }
                        $avidity_l_run = $avidity['L']['RUN'];
                        show_window(__('Fast scan'), $policedog->$avidity_l_run());
                        break;
                    case 'deepscan':
                        $avidity_m_slow = $avidity['M']['SLOW'];
                        show_window(__('Deep scan'), $policedog->$avidity_m_slow());
                        break;
                }
            }
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
