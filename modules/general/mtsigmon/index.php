<?php

if (cfr('MTSIGMON')) {

// Main code part

    $alter_config = $ubillingConfig->getAlter();
    if ($alter_config['MTSIGMON_ENABLED']) {

        $sigmon = new MTsigmon();

        if ( wf_CheckGet(array('IndividualRefresh')) && wf_getBoolFromVar($_GET['IndividualRefresh'], true) ) {
            if ( wf_CheckGet(array('apid')) and !wf_CheckGet(array('cpeMAC')) ) { $sigmon->MTDevicesPolling(false, vf($_GET['apid'], 3)); }

            if ( wf_CheckGet(array('GetAPInfo')) && wf_getBoolFromVar($_GET['GetAPInfo'], true) && wf_CheckGet(array('apid')) ) {
                $APInfoData = $sigmon->getAPEssentialData($_GET['apid'], true, true);
                die($APInfoData);
            }

            if ( wf_CheckGet(array('cpeMAC')) ) {
                if ( wf_CheckGet(array('getGraphs')) && wf_getBoolFromVar($_GET['getGraphs'], true) ) {
                    $getDataFromAP = ( wf_CheckGet(array('fromAP')) && wf_getBoolFromVar($_GET['fromAP'], true) );

                    if ($getDataFromAP) {
                        $SignalGraph = $sigmon->renderSignalGraphs( $_GET['cpeMAC'], true,
                                                                    wf_getBoolFromVar($_GET['showTitle'], true),
                                                                    wf_getBoolFromVar($_GET['showXLabel'], true),
                                                                    wf_getBoolFromVar($_GET['showYLabel'], true),
                                                                    wf_getBoolFromVar($_GET['showRangeSelector'], true)
                                                                  );
                    } else {
                        $SignalGraph = $sigmon->renderSignalGraphs( $_GET['cpeMAC'], false,
                                                                    wf_getBoolFromVar($_GET['showTitle'], true),
                                                                    wf_getBoolFromVar($_GET['showXLabel'], true),
                                                                    wf_getBoolFromVar($_GET['showYLabel'], true),
                                                                    wf_getBoolFromVar($_GET['showRangeSelector'], true)
                                                                  );
                    }

                    if ( empty($SignalGraph)) {die();}

                    if ( wf_CheckGet(array('returnInSpoiler')) && wf_getBoolFromVar($_GET['returnInSpoiler'], true)) {
                        $WCPE = new WifiCPE();
                        $SpoilerTitle = ($getDataFromAP) ? __('Signal data from AP') : __('Signal data from CPE');
                        $GraphContainerID = ( ($getDataFromAP) ? 'SpoilerCPE_' : 'SpoilerAP_') . wf_InputId();
                        $GraphContainerSelector = '#' . $GraphContainerID . ' .spoiler_body';
                        $GraphRefreshButton = $WCPE->getAPCPEGraphRefreshButton($_GET['cpeMAC'], $GraphContainerSelector, $getDataFromAP, false);
                        $SignalGraph = wf_Spoiler($SignalGraph, $GraphRefreshButton . '&nbsp&nbsp' . $SpoilerTitle, false, $GraphContainerID, '', '', '', 'style="margin: 10px auto; display: table;"');
                    }

                    die($SignalGraph);
                }

                if (wf_CheckGet(array('apid'))) {
                    die ( json_encode( $sigmon->getCPESignalData($_GET['cpeMAC'], $_GET['apid'], '', '', true, true ) ) );
                }

                if (wf_CheckGet(array('cpeIP', 'cpeCommunity'))) {
                    die ( json_encode( $sigmon->getCPESignalData($_GET['cpeMAC'], 0, $_GET['cpeIP'], $_GET['cpeCommunity'], false, true ) ) );
                }
            }
        } else {
            // force MT polling
            if (wf_CheckGet(array('forcepoll'))) {
                $sigmon->MTDevicesPolling(true);

                if (wf_CheckGet(array('username'))) {
                    rcms_redirect($sigmon::URL_ME . '&username=' . vf($_GET['username']));
                } else {
                    rcms_redirect($sigmon::URL_ME);
                }
            }

            // getting MT json data for list
            if (wf_CheckGet(array('ajaxmt', 'mtid'))) {
                $sigmon->renderMTsigmonList(vf($_GET['mtid'], 3));
            }

            // rendering availavle MT LIST
            show_window(__('Wireless APs signal monitor'), $sigmon->controls());
            $sigmon->renderMTList();
        }
    } else {
        show_error(__('This module disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>