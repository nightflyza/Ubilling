<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {
    
    $ranks=new Stigma('TASKRANKS');
    debarr($ranks->getReportData());
    
}