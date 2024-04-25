<?php

/*
 * UHW brute attempts cleanup
 */
if (ubRouting::get('action') == 'uhwbrutecleanup') {
    $uhw = new UHW();
    $uhw->flushAllBrute();
    die('OK:UHWBRUTECLEANUP');
}