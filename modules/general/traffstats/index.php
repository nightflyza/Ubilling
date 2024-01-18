<?php

if (cfr('TRAFFSTATS')) {

    if ($ubillingConfig->getAlterParam('BANDWIDTHD_PROXY')) {
        if (ubRouting::checkGet('loadimg')) {
            $remoteImageUrl = base64_decode(ubRouting::get('loadimg'));
            $remoteImageUrl = trim($remoteImageUrl);
            if (!empty($remoteImageUrl)) {
                $remoteImg = new OmaeUrl($remoteImageUrl);
                $remoteImg->setTimeout(1);
                $rawImg = $remoteImg->response();
                $recvErr = $remoteImg->error();
                if (empty($recvErr) and !ispos($rawImg, '404')) {
                    die($rawImg);
                } else {
                    $noImage = file_get_contents('skins/noimage.jpg');
                    die($noImage);
                }
            } else {
                $noImage = file_get_contents('skins/noimage.jpg');
                die($noImage);
            }
        }
    }



    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username');
        $traffStats = new TraffStats($login);
        $useraddress = zb_UserGetFullAddress($login);
        $trafficReport = $traffStats->renderUserTraffStats();
        show_window(__('Traffic stats') . ' ' . $useraddress . ' (' . $login . ')', $trafficReport);
    }
} else {
    show_error(__('Access denied'));
}
