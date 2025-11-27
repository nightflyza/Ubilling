<?php

$result = '';
if (cfr('USERPROFILE')) {
    if (wf_CheckGet(array('username'))) {
        $result = '';
        $login = mysql_real_escape_string($_GET['username']);
        if (!empty($login)) {
            $query = "SELECT `date` FROM `weblogs` WHERE `event` = 'PASSIVE CHANGE (" . $login . ") ON `1`' ORDER BY `id` DESC LIMIT 1";
            $passiveTime_data = simple_query($query);
            if (!empty($passiveTime_data)) {
                $result = ' ('.__('User passive').' '.$passiveTime_data['date'].')';
            }
        }
    } else {
        $result = __('Strange exeption');
    }
} else {
    $result = __('Access denied');
}

die($result);
