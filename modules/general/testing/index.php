<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {


//    $inputData = array(
//        28.4 => 1,
//        2840 => 100,
//        28400 => 1000,
//        284000 => 10056.72,
//    );

    $inputData = array(
        9230 => 208.47,
        9204 => 211.59,
        9218 => 206.75,
        8827 => 213.45,
        9050 => 213.86,
        9148 => 216.97,
        9246 => 218.49,
        9355 => 216.24,
        9576 => 215.14,
    );


    $mrnn = new MRNN();
    //$mrnn->setDebug(true);
    $accel = true;
    $mrnn->learnDataSet($inputData, $accel);
    $trainStats = $mrnn->getTrainStats();
    

    

    //most low effective UAH to USD converter ever!!!! OMG!
    $inputValue = 9763;
    show_info($inputValue . ' users do  ' . $mrnn->processInputData($inputValue) . ' ARPU in october');

    deb($mrnn->visualizeTrain($trainStats));
}