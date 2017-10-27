<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$sorm = new SormYahont();

show_window('debug 7.1', $sorm->getNasData());
show_window('debug 6.7', $sorm->getPaymentsSummary()); //export this
show_window('debug 6.4', $sorm->getCashPayments());
show_window('debug 6.3', $sorm->getOpenPayzTransactions());
show_window('debug 6.2', $sorm->getPaycardsTransactions());
show_window('debug 4.2', $sorm->getServicesData());
show_window('debug 4.1', $sorm->getUserData());
?>
