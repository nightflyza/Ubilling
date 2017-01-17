<?php

if (cfr('POLICEDOG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['POLICEDOG_ENABLED']) {
        $greed = new Avarice();
        $avidity = $greed->runtime('POLICEDOG');
        if (!empty($avidity)) {
            $policedog = new $avidity['O']['INIT']();
            //render interface
            show_window('', $policedog->$avidity['M']['FACE']());

            //create new MAC records
            if (wf_CheckPost(array($avidity['P']['PULL']))) {
                $createResult = $policedog->$avidity['M']['SAVE']();
                if (empty($createResult)) {
                    rcms_redirect($policedog::URL_ME);
                } else {
                    show_window(__('Something went wrong'), $createResult);
                }
            }

            //mac deletion
            if (wf_CheckGet(array($avidity['P']['MDEL']))) {
                $policedog->$avidity['M']['KILL']($_GET[$avidity['P']['MDEL']]);
                rcms_redirect($policedog::URL_ME);
            }

            //alert deletion
            if (wf_CheckGet(array($avidity['P']['ADEL']))) {
                $dVoid = new DarkVoid();
                $dVoid->flushCache();
                $policedog->$avidity['M']['KILLA']($_GET[$avidity['P']['ADEL']]);
                rcms_redirect($policedog::URL_ME . '&show=fastscan');
            }


            if (!wf_CheckGet(array('show'))) {
                //rendering database list
                show_window(__('Wanted MAC database'), $policedog->renderWandedMacList());
            } else {
                $showOpt = $_GET['show'];
                switch ($showOpt) {
                    case 'ajwlist':
                        $policedog->renderWantedMacListAjaxReply();
                        break;
                    case 'fastscan':
                        if (wf_CheckGet(array('forcefast'))) {
                            $policedog->fastScan();
                            rcms_redirect($policedog::URL_ME . '&show=fastscan');
                        }
                        show_window(__('Fast scan'), $policedog->$avidity['L']['RUN']());
                        break;
                    case 'deepscan':
                        show_window(__('Deep scan'), $policedog->$avidity['M']['SLOW']());
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
?>