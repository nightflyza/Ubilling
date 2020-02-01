<?php

if (cfr('SWITCHESEDIT')) {


    if (ubRouting::checkGet('switchid')) {
        $history = new SwitchHistory(ubRouting::get('switchid', 'int'));
        show_window(__('History of switch life'), $history->renderReport());
    } else {
        show_error(__('Something went wrong') . ': EX_NO_SWITCHID');
    }
} else {
    show_error(__('Access denied'));
}