<?php

error_reporting(E_ALL);
if (cfr('ROOT')) {
    set_time_limit(0);
    $teleport = new UnicornTeleport();
    if (ubRouting::checkGet(UnicornTeleport::ROUTE_DOWNLOAD)) {
        $teleport->catchFileDownload();
    } else {
        show_window(__('Unicorn Teleport'), $teleport->renderTeleportForm());
    }

    show_window('', wf_BackLink(DevConsole::URL_ME));
} else {
    show_error(__('Access denied'));
}