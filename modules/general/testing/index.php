<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {


    $inputData = array(
        28.4 => 1,
        2840 => 100,
        28400 => 1000,
        284000 => 10056.72,
    );


    $mrnn = new MRNN();
    $accel = true;
    $mrnn->learnDataSet($inputData, $accel);
    $trainStats = $mrnn->getTrainStats();
    $mrnn->processInputData(14);

    $chartData = array(
        0 => array('Epoch', 'Error')
    );
    if (!empty($trainStats)) {
        foreach ($trainStats as $neuron => $neuronStats) {
            if (!empty($neuronStats)) {
                foreach ($neuronStats as $epoch => $error) {
                    $chartData[] = array($epoch, $error);
                }
            }
        }
    }

    //most low effective UAH to USD converter ever!!!! OMG!
    $inputValue = 28.4;
    show_info($inputValue . ' UAH IS ' . $mrnn->processInputData($inputValue) . ' USD');

    deb(wf_gchartsLine($chartData, 'Train network', '800px', '400px', ''));
}