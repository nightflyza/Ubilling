<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {



    $inputData = array(
        32 => 64,
        3 => 6,
        4 => 8,
        5 => 10,
    );


    $mrnn = new MRNN();
    $mrnn->setDebug(0);
    $accel = true;
    $mrnn->learnDataSet($inputData, $accel);
    $trainStats = $mrnn->getTrainStats();
    
 
    $inputValue = 365535;
    show_info($inputValue . ' *2 = ' . $mrnn->processInputData($inputValue) . ' <- Oo ');

    deb($mrnn->visualizeTrain($trainStats));
}