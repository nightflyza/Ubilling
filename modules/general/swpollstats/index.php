<?php

if (cfr('SWITCHPOLL')) {

    $swpollstats = new SwPollStats();
    $swpollstats->render();

} else {
    show_error(__('Access denied'));
}