<?php

/*
 * Per month freezing fees
 */
if ($_GET['action'] == 'freezemonth') {
    $money = new FundsFlow();
    $money->runDataLoders();
    $money->makeFreezeMonthFee();
    die('OK:FREEZEMONTH');
}