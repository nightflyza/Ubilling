<?php

/*
 * GlobalSearch cache rebuild
 */
if ($_GET['action'] == 'rebuildglscache') {
    $globalSearch = new GlobalSearch();
    $globalSearch->ajaxCallback(true);
    die('OK:REBUILDGLSCACHE');
}

