<?php

if (cfr('TRAFFSTATS')) {
    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);
        $useraddress = zb_UserGetFullAddress($login);
        show_window(__('Traffic stats') . ' ' . $useraddress . ' (' . $login . ')', web_UserTraffStats($login) . web_UserControls($login));
    }
} else {
    show_error(__('Access denied'));
}
?>