<?php

/*
 * Cumulatiove discounts processing
 */
if (ubRouting::get('action') == 'cudiscounts') {
    if ($alterconf['CUD_ENABLED']) {
        $discounts = new CumulativeDiscounts();
        $discounts->processDiscounts();
        die('OK:CUDISCOUNTS');
    } else {
        die('ERROR:CUDISCOUNTS_DISABLED');
    }
}