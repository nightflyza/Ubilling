<?php

//just dummy module for testing purposes
error_reporting(E_ALL);


if (ubRouting::checkGet('paymentstest')) {

    class payments extends NyanORM {
        
    }

    $payments = new payments();

    $payments->setDebug(true);
    $payments->where('id', '>', '100');
    $payments->where('date', 'LIKE', '2019%');
    $payments->where('summ', '>', '10');
    $payments->where('note', 'IS NOT', 'NULL');
    $payments->orWhere('id', '=', '2');
    $payments->orderBy('id', 'DESC');
    $payments->orderBy('summ', 'ASC');


    debarr($payments);

    $rawPayments = $payments->getAll();

    debarr($rawPayments);


//    $switches=new nya_switches();
//    $switches->setDebug(true);
//    $switches->where('ip', 'LIKE', '172.16%');
//    $switches->orWhere('geo', '=', '');
//    $switches->orderBy('id', 'DESC');
//    
//    debarr($switches->getAll('id'));
}
?>
