<?php

if (ubRouting::get('action') == 'stickynotify') {
    if (@$alterconf['STICKY_NOTES_ENABLED']) {
        if (@$alterconf['SENDDOG_ENABLED']) {
            $stickyNotify = new StickyNotify();
            $stickyNotify->run();
        } else {
            die('ERROR:SENDDOG_DISABLED');
        }
    } else {
        die('ERROR:STICKYNOTIFY_DISABLED');
    }
}