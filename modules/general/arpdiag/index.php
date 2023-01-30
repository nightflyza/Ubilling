<?php

if (cfr('ARPDIAG')) {
    $alterconf = $ubillingConfig->getAlter();
    if ($alterconf['ARPDIAG_ENABLED']) {
        $arpDiag = new ArpDiag();
        if (ubRouting::checkGet('ajaxarp')) {
            $arpDiag->ajaxReplyArp();
        }
        //module controls here
        show_window('', $arpDiag->renderPanel());

        if (ubRouting::checkGet('arptable')) {
            //switch MAC assign
            if (ubRouting::checkGet(array('swassign', 'swmac'))) {
                $arpDiag->assignSwitchMac(ubRouting::get('swassign'), ubRouting::get('swmac'));
                ubRouting::nav($arpDiag::URL_ME . '&arptable=true');
            }
            //rendering local arp table
            show_window(__('Local ARP table'), $arpDiag->renderArpTable());
        } else {
            //rendering arp issues?
            show_window(__('Diagnosing problems with the ARP'), $arpDiag->renderReport());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}

