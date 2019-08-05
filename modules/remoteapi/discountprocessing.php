<?php

/*
 * Discount processing
 */
if ($_GET['action'] == 'discountprocessing') {
    if ($alterconf['DISCOUNTS_ENABLED']) {
        //default debug=true
        zb_DiscountProcessPayments(true);
        die('OK:DISCOUNTS_PROCESSING');
    } else {
        die('ERROR:DISCOUNTS_DISABLED');
    }
}