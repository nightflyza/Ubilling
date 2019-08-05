<?php

/*
 * handlersrebuild action
 */

if ($_GET['action'] == 'handlersrebuild') {
    multinet_rebuild_all_handlers();
    log_register("REMOTEAPI HANDLERSREBUILD");
    die('OK:HANDLERSREBUILD');
}