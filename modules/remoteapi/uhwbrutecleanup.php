<?php

/*
 * UHW brute attempts cleanup
 */
if ($_GET['action'] == 'uhwbrutecleanup') {
    $uhw = new UHW();
    $uhw->flushAllBrute();
    die('OK:UHWBRUTECLEANUP');
}