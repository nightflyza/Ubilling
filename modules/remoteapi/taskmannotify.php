<?php

if (ubRouting::get('action') == 'taskmannotify') {
    if (@$alterconf['SENDDOG_ENABLED']) {
        $taskmanNotify = new TaskmanNotify();
        $taskmanNotify->run();
        die('OK:TASKMANNOTIFY');
    } else {
        die('ERROR:SENDDOG_DISABLED');
    }
}