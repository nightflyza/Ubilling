<?php

if (cfr('TRAFFSTATS')) {

    if ($ubillingConfig->getAlterParam('BANDWIDTHD_PROXY')) {
        if (ubRouting::checkGet('loadimg')) {
            $remoteImageUrl = base64_decode(ubRouting::get('loadimg'));
            if (!empty($remoteImageUrl)) {
                $remoteImg = new OmaeUrl($remoteImageUrl);
                $remoteImg->setTimeout(1);
                $rawImg = $remoteImg->response();
                $recvErr = $remoteImg->error();
                if (empty($recvErr) AND ! ispos($rawImg, '404')) {
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


    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);
        $useraddress = zb_UserGetFullAddress($login);
        show_window(__('Traffic stats') . ' ' . $useraddress . ' (' . $login . ')', web_UserTraffStats($login) . web_UserControls($login));
    }
} else {
    show_error(__('Access denied'));
}
?>