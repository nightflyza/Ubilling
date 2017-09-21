<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

ini_set('max_execution_time', 30);
//ini_set('precision', '5');

$learningFlag = false;
$predicationFlag = false;
$graphFlag = true;
$testFlag = false;

$outputsCount = 1;
$hiddenLayersCount = 3;
$neuronsCount = 6;

$maxUsers = 10000;
$maxMoney = 10000;
$totalLimits = 10;
$cashLimits = 100;
$dataPath = 'content/documents/ann/';


$sampleData = array();

$horseData = simple_queryall("SELECT * from `exhorse`");
if (!empty($horseData)) {
    foreach ($horseData as $io => $each) {
        $sampleDataTmp[] = array($each['u_totalusers'], $each['u_activeusers'], $each['u_inactiveusers'], $each['f_paymentscount'], $each['f_totalmoney']);
    }
}

//$sampleDataTmp[7]=array(10682, 7989, 1750,7105, 683215);

if (wf_CheckGet(array('clean'))) {
    zb_ANNCleanNetworkAll();
    rcms_redirect('?module=testing');
}

if (wf_CheckGet(array('learn'))) {
    $learningFlag = true;
}

if (wf_CheckGet(array('predicate'))) {
    $predicationFlag = true;
}

if (wf_CheckGet(array('test'))) {
    $testFlag = true;
}

$controls = wf_Link('?module=testing&clean=true', __('Clean network data'), false, 'ubButton') . ' ';
$controls.= wf_Link('?module=testing&learn=true', __('Learn network'), false, 'ubButton') . ' ';
$controls.= wf_Link('?module=testing&predicate=true', __('Get data'), false, 'ubButton') . ' ';
$controls.= wf_Link('?module=testing&predicate=true&test=true', __('Test network'), false, 'ubButton') . ' ';
show_window('', $controls);

//sample data normalization
if (!empty($sampleDataTmp)) {
    foreach ($sampleDataTmp as $io => $each) {
        $sampleData[$io] = array(round(($each[0] / $maxUsers), 6), round(($each[1] / $maxUsers), 6), round(($each[2] / $maxUsers), 6), round(($each[3] / $maxUsers), 6), round(($each[4] / $maxMoney), 6));
    }
}

if ($learningFlag) {
    debarr($sampleData);
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
        show_info('Creating a new one...');

        $objNetwork = new Network($hiddenLayersCount, $neuronsCount, $outputsCount);


        $objTotalUsers = new InputValue(0, $totalLimits); // Total users count
        $objTotalUsers->saveToFile('content/documents/ann/input_totalusers.dat');


        $objActiveUsers = new InputValue(0, $totalLimits);  // Active users count
        $objActiveUsers->saveToFile('content/documents/ann/input_activeusers.dat');

        $objInactiveUsers = new InputValue(0, $totalLimits);  // Inactive users count
        $objInactiveUsers->saveToFile('content/documents/ann/input_inactiveusers.dat');

        $objPayCount = new InputValue(0, $totalLimits);  // payments count
        $objPayCount->saveToFile('content/documents/ann/input_paymentscount.dat');


        $objMoney = new OutputValue(0, $cashLimits); // Summ of received total cash
        $objMoney->saveToFile('content/documents/ann/output_money.dat');


        $objValues = new Values;

        //filling sample data
        if (!empty($sampleData)) {
            foreach ($sampleData as $io => $eachData) {
                $objValues->train()->input($objTotalUsers->getInputValue($eachData[0]), $objActiveUsers->getInputValue($eachData[1]), $objInactiveUsers->getInputValue($eachData[2]), $objPayCount->getInputValue($eachData[3]))->output($objMoney->getOutputValue($eachData[4]));
            }
        }


        $objValues->saveToFile('content/documents/ann/values_money.dat');

        unset($objValues);
        unset($objTotalUsers);
        unset($objActiveUsers);
        unset($objInactiveUsers);
        unset($objMoney);
        unset($objPayCount);
    }

    try {
        $objTotalUsers = InputValue::loadFromFile('content/documents/ann/input_totalusers.dat'); // Total users count

        $objActiveUsers = InputValue::loadFromFile('content/documents/ann/input_activeusers.dat'); // Active users count
        $objInactiveUsers = InputValue::loadFromFile('content/documents/ann/input_inactiveusers.dat'); // Inactive users count
        $objPayCount = InputValue::loadFromFile('content/documents/ann/input_paymentscount.dat'); // Payments count
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
    if ($boolTrained) {
        show_success($trainedFlag);
    } else {
        show_warning($trainedFlag);
    }

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
        $objPayCount = InputValue::loadFromFile('content/documents/ann/input_paymentscount.dat');
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
    if ($testFlag) {
        $objValues->input(// input values appending the loaded ones
                $objTotalUsers->getInputValue(1.0682), $objActiveUsers->getInputValue(0.7989), $objInactiveUsers->getInputValue(0.750), $objPayCount->getInputValue(0.7105)
        );
    }

    $objNetwork->setValues($objValues);

    $arrOutputs = $objNetwork->getOutputs();


    $i = 0;
    foreach ($arrOutputs as $arrOutput) {
        foreach ($arrOutput as $floatOutput) {
            $i++;
            show_window('', 'Result ' . $i . ': ' . ($objMoney->getRealOutputValue($floatOutput) * $maxMoney));
        }
    }
}

if ($graphFlag) {
    try {
        $objNetworkG = Network::loadFromFile('content/documents/ann/profit.dat');
        $objNetworkImage = new NetworkGraph($objNetworkG);
        $objNetworkImage->saveToFile('api/vendor/ANN/annetwork.png');
        show_window('Network graph', wf_img_sized('api/vendor/ANN/annetwork.png', '', '100%'));
    } catch (Exception $e) {
        show_error('Network not found');
    }
}

function zb_ANNCleanNetworkAll() {
    global $dataPath;
    $all = rcms_scandir($dataPath, '*.dat');
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            unlink($dataPath . $each);
        }
    }
}

?>