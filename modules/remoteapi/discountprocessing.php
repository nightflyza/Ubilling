<?php

/*
 * Discount processing
 */
if (ubRouting::get('action') == 'discountprocessing') {
    if ($alterconf['DISCOUNTS_ENABLED']) {
        $runAllowedFlag = true;
        if (ubRouting::checkGet('lastday')) {
            if (date("d") != date("t")) {
                $runAllowedFlag = false;
            }
        }

        if ($runAllowedFlag) {
            $discounts = new Discounts();
            $discounts->processPayments();
            die('OK:DISCOUNTS_PROCESSING');
        } else {
            die('OK:DISCOUNTS_SKIPPED');
        }
    } else {
        die('ERROR:DISCOUNTS_DISABLED');
    }
}