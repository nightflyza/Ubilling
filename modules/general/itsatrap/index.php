<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['ITSATRAP_ENABLED']) {
    if (cfr('ITSATRAP')) {
        $itsatrap = new ItSaTrap();

        //saving new data source and lines limit
        if (ubRouting::checkPost('newdatasource', false)) {
            if (cfr('ITSATRAPCFG')) {
                $itsatrap->saveBasicConfig();
                ubRouting::nav($itsatrap::URL_ME . $itsatrap::URL_CONFIG);
            } else {
                show_error(__('Access denied'));
            }
        }

        //new trap type creation
        if (ubRouting::checkPost(array('newname', 'newmatch', 'newcolor'))) {
            if (cfr('ITSATRAPCFG')) {
                $itsatrap->createTrapType();
                ubRouting::nav($itsatrap::URL_ME . $itsatrap::URL_CONFIG);
            } else {
                show_error(__('Access denied'));
            }
        }

        //existing trap editing
        if (ubRouting::checkPost(array('edittraptypeid', 'editname', 'editmatch', 'editcolor'))) {
            if (cfr('ITSATRAPCFG')) {
                $itsatrap->saveTrapType();
                ubRouting::nav($itsatrap::URL_ME . $itsatrap::URL_CONFIG);
            } else {
                show_error(__('Access denied'));
            }
        }

        //trap type deletion
        if (ubRouting::checkGet('deletetrapid')) {
            if (cfr('ITSATRAPCFG')) {
                $deletionResult = $itsatrap->deleteTrapType(ubRouting::get('deletetrapid', 'int'));
                if (empty($deletionResult)) {
                    ubRouting::nav($itsatrap::URL_ME . $itsatrap::URL_CONFIG);
                } else {
                    show_error($deletionResult);
                }
            } else {
                show_error(__('Access denied'));
            }
        }


        //current trap events background data
        if (ubRouting::checkGet('ajaxtrapslist')) {
            $itsatrap->ajTrapList();
        }


        //some interface here
        if (cfr('ITSATRAPCFG')) {
            // we dont need control panel if no CFG rights.
            show_window('', $itsatrap->renderControls());
        }

        //render some configuration forms and controls
        if (ubRouting::get('config')) {
            show_window(__('Configuration'), $itsatrap->renderConfigForm());
            show_window(__('Available SNMP trap types'), $itsatrap->renderTrapTypesList());
            show_window('', $itsatrap->renderTrapCreateForm());
        } else {
            //rendering raw results
            if (ubRouting::get('rawdata')) {
                show_window(__('RAW') . ' ' . __('Data'), $itsatrap->renderRawData());
            } else {
                //normal trap events display
                show_window(__('Events'), $itsatrap->renderTrapEventsList());
            }
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}

