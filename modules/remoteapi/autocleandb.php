<?php

/*
 * database cleanup
 */
if ($_GET['action'] == 'autocleandb') {
    $cleancount = zb_DBCleanupAutoClean();
    die('OK:AUTOCLEANDB ' . $cleancount);
}