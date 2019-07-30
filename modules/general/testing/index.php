<?php

//just dummy module for testing purposes
error_reporting(E_ALL);


if (ubRouting::checkGet('paymentstest')) {

    class payments extends NyanORM {
        
    }

    $payments = new payments();

    $payments->where('id', '>', '100');
    $payments->where('date', 'LIKE', '2019%');
    $payments->where('summ', '>', '10');
    $payments->where('note', 'IS NOT', 'NULL');
    $rawPayments = $payments->getAll('id');

    debarr($rawPayments);
    
}
?>
