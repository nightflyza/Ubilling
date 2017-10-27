<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$sorm = new SormYahont();

show_window('debug 6.3', $sorm->getOpenPaysTransactions());
show_window('debug 6.2', $sorm->getPaycardsTransactions());
show_window('debug 4.2', $sorm->getServicesData());
show_window('debug 4.1', $sorm->getUserData());
?>
