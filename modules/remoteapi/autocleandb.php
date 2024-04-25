<?php

/*
 * database cleanup
 */
if (ubRouting::get('action') == 'autocleandb') {
    $cleancount = zb_DBCleanupAutoClean();
    die('OK:AUTOCLEANDB ' . $cleancount);
}
