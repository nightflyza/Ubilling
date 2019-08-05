<?php

//paymentards queue processing
if ($_GET['action'] == 'paycardsqueue') {
    $paycardsProcessed = zb_CardsQueueProcessing();
    die('PAYCARDS_QUEUE:' . $paycardsProcessed);
}