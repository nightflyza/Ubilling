<?php

if (cfr('SIGREQ')) {
    if (wf_CheckGet(array('ip'))) {
        $ip = vf($_GET['ip']);
        $whois = new UbillingWhois($ip);
        if (wf_CheckGet(array('ajax'))) {
            die($whois->renderData());
        } else {
            show_window(__('Whois'), $whois->renderData());
        }
    } else {
        show_error(__('Something went wrong') . ': EX_GET_NO_IP');
    }
} else {
    show_error(__('Access denied'));
}
?>