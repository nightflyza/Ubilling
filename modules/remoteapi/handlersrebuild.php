<?php

/*
 * handlersrebuild action
 */

if (ubRouting::get('action')== 'handlersrebuild') {
    multinet_rebuild_all_handlers();
    log_register("REMOTEAPI HANDLERSREBUILD");
    die('OK:HANDLERSREBUILD');
}