<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {



    $inputData = array(
        9 => 3,
        169 => 13,
        144 => 12,
        121 => 11,
        225 => 15,
        256 => 16,
        65536 => 256
    );



    //debarr($inputData);
    $mrnn = new MRNN();
    $mrnn->setDebug(0);
    $accel = true;
    $mrnn->learnDataSet($inputData, $accel);
    $trainStats = $mrnn->getTrainStats();
    //$mrnn->setWeight(0.10251040756612);


    $inputValue = 100;
    show_info($inputValue . ' sqrt = ' . $mrnn->processInputData($inputValue) . ' <- Oo '); //most expensive sqrt ever

    deb($mrnn->visualizeTrain($trainStats));
    
  
}