<?php

if (ubRouting::get('action') == 'ophanimtraff') {
    if ($ubillingConfig->getAlterParam(OphanimFlow::OPTION_ENABLED)) {
        set_time_limit(600);
        $ophTraff = new OphanimFlow();
        $ophTraff->traffDataProcessing();
        die('OK:OPHANIMTRAFF_DONE');
    } else {
        die('ERROR:OPHANIMTRAFF_DIABLED');
    }
}