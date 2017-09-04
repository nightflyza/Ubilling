<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

ini_set('max_execution_time', 10);


$learningFlag = true;
$predicationFlag = true;
$graphFlag = true;

$maxUsers = 100; //*1000
$maxMoney = 1000; //*1000

$sampleData = array();
$sampleDataTmp = array(
    0 => array(9981, 7773, 1487, 619757),
    1 => array(10075, 7766, 1570, 557142),
    2 => array(10165, 7823, 1584, 633676),
    3 => array(10229, 7815, 1643, 542070),
    4 => array(10326, 7883, 1659, 651981),
    5 => array(10427, 7880, 1741, 609111),
    6 => array(10531, 7793, 1872, 605112),
  
);

//  7 => array(10682, 7989, 1750, 683215)

//sample data normalization
if (!empty($sampleDataTmp)) {
    foreach ($sampleDataTmp as $io => $each) {
        $sampleData[$io] = array(round(($each[0] / 1000),2), round(($each[1] / 1000),2), round(($each[2] / 1000),2), round(($each[3] / 1000),2));
    }
}

require_once 'api/vendor/ANN/Loader.php';

use ANN\Network;
use ANN\InputValue;
use ANN\OutputValue;
use ANN\Values;
use ANN\NetworkGraph;

if ($learningFlag) {
    try {
        $objNetwork = Network::loadFromFile('content/documents/ann/profit.dat');
    } catch (Exception $e) {
        deb('Creating a new one...');

        $objNetwork = new Network(2, 5, 1);


        $objTotalUsers = new InputValue(0, $maxUsers); // Total users count
        $objTotalUsers->saveToFile('content/documents/ann/input_totalusers.dat');


        $objActiveUsers = new InputValue(0, $maxUsers);  // Active users count
        $objActiveUsers->saveToFile('content/documents/ann/input_activeusers.dat');

        $objInactiveUsers = new InputValue(0, $maxUsers);  // Inactive users count
        $objInactiveUsers->saveToFile('content/documents/ann/input_inactiveusers.dat');


        $objMoney = new OutputValue(0, $maxMoney); // Summ of received total cash
        $objMoney->saveToFile('content/documents/ann/output_money.dat');


        $objValues = new Values;

//        $objValues->train()
//                ->input(
//                        $objTotalUsers->getInputValue($sampleData[0][0]), $objActiveUsers->getInputValue($sampleData[0][1]), $objInactiveUsers->getInputValue($sampleData[0][2])
//                )
//                ->output(
//                        $objMoney->getOutputValue($sampleData[0][3])
//                )
//                   ->input(
//                        $objTotalUsers->getInputValue($sampleData[1][0]), $objActiveUsers->getInputValue($sampleData[1][1]), $objInactiveUsers->getInputValue($sampleData[1][2])
//                )
//                ->output(
//                        $objMoney->getOutputValue($sampleData[1][3])
//                )
//                  ->input(
//                        $objTotalUsers->getInputValue($sampleData[2][0]), $objActiveUsers->getInputValue($sampleData[2][1]), $objInactiveUsers->getInputValue($sampleData[2][2])
//                )
//                ->output(
//                        $objMoney->getOutputValue($sampleData[2][3])
//                )
//                   ->input(
//                        $objTotalUsers->getInputValue($sampleData[3][0]), $objActiveUsers->getInputValue($sampleData[3][1]), $objInactiveUsers->getInputValue($sampleData[3][2])
//                )
//                ->output(
//                        $objMoney->getOutputValue($sampleData[3][3])
//                )
//                ->input(
//                        $objTotalUsers->getInputValue($sampleData[4][0]), $objActiveUsers->getInputValue($sampleData[4][1]), $objInactiveUsers->getInputValue($sampleData[4][2])
//                )
//                ->output(
//                        $objMoney->getOutputValue($sampleData[4][3])
//        );

        if (!empty($sampleData)) {
            foreach ($sampleData as $io => $eachData) {
                $objValues->train()->input($objTotalUsers->getInputValue($eachData[0]), $objActiveUsers->getInputValue($eachData[1]), $objInactiveUsers->getInputValue($eachData[2]))->output($objMoney->getOutputValue($eachData[3]));
            }
        }


        $objValues->saveToFile('content/documents/ann/values_money.dat');

        unset($objValues);
        unset($objTotalUsers);
        unset($objActiveUsers);
        unset($objInactiveUsers);
        unset($objMoney);
    }

    try {
        $objTotalUsers = InputValue::loadFromFile('content/documents/ann/input_totalusers.dat'); // Total users count

        $objActiveUsers = InputValue::loadFromFile('content/documents/ann/input_activeusers.dat'); // Active users count
        $objInactiveUsers = InputValue::loadFromFile('content/documents/ann/input_inactiveusers.dat'); // Inactive users count

        $objMoney = OutputValue::loadFromFile('content/documents/ann/output_money.dat'); // Quantity of sold ice-creams
    } catch (Exception $e) {
        die('Error loading value objects');
    }

    try {
        $objValues = Values::loadFromFile('content/documents/ann/values_money.dat');
    } catch (Exception $e) {
        die('Loading of values failed');
    }

    $objNetwork->setValues($objValues); // to be called as of version 2.0.6

    $boolTrained = $objNetwork->train();

    $trainedFlag = ($boolTrained) ? 'Network trained' : 'Network not trained completely. Please re-run the script';
    deb($trainedFlag);
    $objNetwork->saveToFile('content/documents/ann/profit.dat');

    ob_start();
    $objNetwork->printNetwork();
    $annDebugData = ob_get_contents();
    ob_end_clean();
    show_window(__('ANN learning debug'), $annDebugData . wf_CleanDiv());
}
if ($predicationFlag) {
    //predication here
    try {
        $objNetwork = Network::loadFromFile('content/documents/ann/profit.dat');
    } catch (Exception $e) {
        die('Network not found');
    }

    try {
        $objTotalUsers = InputValue::loadFromFile('content/documents/ann/input_totalusers.dat');
        $objActiveUsers = InputValue::loadFromFile('content/documents/ann/input_activeusers.dat');
        $objInactiveUsers = InputValue::loadFromFile('content/documents/ann/input_inactiveusers.dat');
        $objMoney = OutputValue::loadFromFile('content/documents/ann/output_money.dat');
    } catch (Exception $e) {
        die('Error loading value objects');
    }

    try {
        $objValues = Values::loadFromFile('content/documents/ann/values_money.dat');
    } catch (Exception $e) {
        die('Loading of values failed');
    }

    //mixing new data here
//    $objValues->input(// input values appending the loaded ones
//            $objTotalUsers->getInputValue(10.682), $objActiveUsers->getInputValue(7.989), $objInactiveUsers->getInputValue(1.750)
//    );

    $objNetwork->setValues($objValues);

    $arrOutputs = $objNetwork->getOutputs();


    $i = 0;
    foreach ($arrOutputs as $arrOutput) {
        foreach ($arrOutput as $floatOutput) {
            $i++;
            show_window('', 'Result ' . $i . ': ' . $objMoney->getRealOutputValue($floatOutput));
        }
    }
}

if ($graphFlag) {
    try {
        $objNetworkG = Network::loadFromFile('content/documents/ann/profit.dat');
    } catch (Exception $e) {
        die('Network not found');
    }

    $objNetworkImage = new NetworkGraph($objNetworkG);

    $objNetworkImage->saveToFile('api/vendor/ANN/annetwork.png');
    show_window('Network graph', wf_img('api/vendor/ANN/annetwork.png'));
}
?>