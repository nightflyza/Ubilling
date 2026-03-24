<?php

if (cfr('POLICEDOG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['POLICEDOG_ENABLED']) {
        $policedog = new PoliceDog();
        //render interface
        show_window('', $policedog->panel());

            //create new MAC records
            if (ubRouting::checkPost(array('newmacupload'))) {
                $createResult = $policedog->catchCreateMacRequest();
                if (empty($createResult)) {
                    ubRouting::nav($policedog::URL_ME);
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
            if (ubRouting::checkGet(array('delmacid'))) {
                $policedog->deleteWantedMac(ubRouting::get('delmacid'));
                ubRouting::nav($policedog::URL_ME);
            }

            //alert deletion
            if (ubRouting::checkGet(array('delalertid'))) {
                $dVoid = new DarkVoid();
                $dVoid->flushCache();
                $policedog->deleteAlert(ubRouting::get('delalertid'));
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
                        if (ubRouting::checkGet(array('forcefast'))) {
                            $policedog->fastScan();
                            ubRouting::nav($policedog::URL_ME . '&show=fastscan');
                        }
                        show_window(__('Fast scan'), $policedog->renderFastScan());
                        break;
                    case 'deepscan':
                        show_window(__('Deep scan'), $policedog->RenderDeepScan());
                        break;
                }
            }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
