<?php

set_time_limit(0);

if (cfr('SWITCHPOLL')) {
    $fdbarchive = new FDBArchive();


    if (ubRouting::get('ajax')) {
        $fdbarchive->ajArchiveData();
    }

    show_window('', $fdbarchive->renderNavigationPanel());
    show_window(__('FDB') . ' ' . __('Archive'), $fdbarchive->renderArchive());
} else {
    show_error(__('Access denied'));
}