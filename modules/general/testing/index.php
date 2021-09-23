<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {

    $test=new Stigma('TASKWHATIDO');
    debarr($test);
    debarr($test->getItemStates(244));
    
}