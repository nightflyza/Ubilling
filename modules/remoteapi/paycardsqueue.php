<?php

//paymentards queue processing
if (ubRouting::get('action') == 'paycardsqueue') {
    if ($ubillingConfig->getAlterParam('PAYMENTCARDS_ENABLED')) {
        $paycardsProcessed = zb_CardsQueueProcessing();
        die('PAYCARDS_QUEUE:' . $paycardsProcessed);
    } else {
        die('ERROR:PAYCARDS_DISABLED');
    }
}
