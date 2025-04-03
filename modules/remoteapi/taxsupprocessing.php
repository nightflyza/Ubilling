<?php

/*
 * Taxa suplimentara goes here
 */
if (ubRouting::get('action') == 'taxsupprocessing') {
    if ($alterconf['TAXSUP_ENABLED']) {
        $taxsup = new TaxSup();
        $taxsup->processingFees();
        die('OK:TAXSUP_PROCESSING');
    } else {
        die('ERROR:TAXSUP_DISABLED');
    }
}
