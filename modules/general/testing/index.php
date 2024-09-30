<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {
    $gr=new GRes();
    $gr->setAmount(200);
    $gr->setUserLogin('sometestuser');
    debarr($gr->getStrategyData(1));
}
