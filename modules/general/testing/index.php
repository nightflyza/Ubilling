<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {
    $pid = new StarDust('someprocess');
    if (ubRouting::checkGet('run')) {
        $pid->start();
        sleep(3);
        $pid->stop();
    }
    
    debarr($pid);

    if ($pid->isRunning()) {
        show_warning('Процес запущено і він ще триває');
    } else {
        show_success('Процес зараз не запущено');
    }
}
