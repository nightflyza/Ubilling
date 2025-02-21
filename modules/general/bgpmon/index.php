<?php

if (cfr('BGPMON')) {
    if ($ubillingConfig->getAlterParam('BGPMON_ENABLED')) {
        $bgpMon = new BGPMon();
        //forced polling
        if (ubRouting::checkGet($bgpMon::ROUTE_REFRESH)) {
            if (cfr('ROOT')) {
                $bgpMon->flushCache();
                $bgpMon->pollAllDevsStats();
                ubRouting::nav($bgpMon::URL_ME);
            }
        }

        //editing peers name/description
        if (ubRouting::checkPost($bgpMon::PROUTE_PEER_IP)) {
            if (cfr('ROOT')) {
                $bgpMon->savePeerName();
                ubRouting::nav($bgpMon::URL_ME . '&' . $bgpMon::ROUTE_EDIT_NAMES . '=' . ubRouting::post($bgpMon::PROUTE_PEER_IP));
            }
        }

        if (cfr('ROOT')) {
            show_window('', $bgpMon->renderControls());
        }

        if (ubRouting::checkGet($bgpMon::ROUTE_EDIT_NAMES)) {
            //peers descriptions editor
            $peerIp = ubRouting::get($bgpMon::ROUTE_EDIT_NAMES, 'mres');
            show_window(__('Edit') . ': ' . $peerIp, $bgpMon->renderPeersEditForm($peerIp));
        } else {
            //rendering report
            show_window(__('BGP peers state'), $bgpMon->renderReport());
            zb_BillingStats();
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
