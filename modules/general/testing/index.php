<?php

//just dummy module for testing purposes
error_reporting(E_ALL);


class payments extends NyanORM {}
$payments = new payments();
$payments->where('date', 'LIKE', curyear().'-%');
$yearPayments=$payments->getAll();
debarr($yearPayments);


?>
