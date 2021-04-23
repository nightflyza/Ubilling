<?php

/*
 * Per month freezing fees
 */
if (ubRouting::checkGet('action') and ubRouting::get('action') == 'freezemonth') {
    $processingDebug = (ubRouting::checkGet('param') and ubRouting::get('param') == 'debug2ublog');
    $money = new FundsFlow();
    $money->runDataLoders();
    $money->makeFreezeMonthFee($processingDebug);
    die('OK:FREEZEMONTH');
}