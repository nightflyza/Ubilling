<?php

if (ubRouting::get('action') == 'ptvui') {
    if ($ubillingConfig->getAlterParam('PTV_ENABLED')) {
        $ptv = new PTV();
        if (ubRouting::checkGet('subdata')) {
            $ptv->usReplyUserData(ubRouting::get('subdata'));
        }

        if (ubRouting::checkGet('tardata')) {
            $ptv->usReplyTariffs();
        }

        if (ubRouting::checkGet('fulldata')) {
            $ptv->usReplyUserFullData(ubRouting::get('fulldata'));
        }

        if (ubRouting::checkGet('newdev')) {
            $ptv->createDevice(ubRouting::get('newdev'));
        }

        if (ubRouting::checkGet(array('deldev', 'subid'))) {
            $ptv->deleteDevice(ubRouting::get('subid', 'int'), ubRouting::get('deldev'));
        }

        if (ubRouting::checkGet('newpl')) {
            $ptv->createPlayList(ubRouting::get('newpl'));
        }

        if (ubRouting::checkGet(array('delpl', 'subid'))) {
            $ptv->deletePlaylist(ubRouting::get('subid', 'int'), ubRouting::get('delpl'));
        }
    } else {
        $replyError = array('error' => 'ERROR: PTV_DISABLED');
        die(json_encode($replyError));
    }
} 