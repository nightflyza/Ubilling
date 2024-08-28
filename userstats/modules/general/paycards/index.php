<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

//paymentcards  options
$pc_enabled = $us_config['PC_ENABLED'];
$pc_brute = $us_config['PC_BRUTE'];

if (ubRouting::checkGet('agentpaycards')) {
    $pcAgentCall = true;
    $pcAgentResult = array();
    $pcAgentOutputFormat = 'xml';

    if (ubRouting::checkGet('json')) {
        $pcAgentOutputFormat = 'json';
    }
} else {
    $pcAgentCall = false;
}

// Check if Paycards module is enabled
if ($pc_enabled) {
    //check is that user idiot?
    if (!zbs_PayCardCheckBrute($user_ip, $pc_brute)) {
        $payCardInput = '';
        //receive payment card input in any way
        if (ubRouting::checkGet('paycard')) {
            $payCardInput = ubRouting::get('paycard');
        }

        if (ubRouting::checkPost('paycard')) {
            $payCardInput = ubRouting::post('paycard');
        }

        //add cash routine with checks
        if (!empty($payCardInput)) {

            $series = false;
            if ($us_config['PC_SERIES_AND_SN']) {
                $serialNumber = substr($payCardInput, $us_config['PC_SERIES_LENGTH']);
                $series = str_replace($serialNumber, '', $payCardInput);
                $payCardInput = $serialNumber;
            }

            //use this card
            if (zbs_PaycardCheck($payCardInput, $series)) {
                if (!@$us_config['PC_QUEUED']) {
                    zbs_PaycardUse($payCardInput, $pcAgentCall);
                } else {
                    //or mark it for queue processing
                    zbs_PaycardQueue($payCardInput, $pcAgentCall);
                }

                if ($pcAgentCall) {
                    $pcAgentResult[] = array("result" => $pcAgentOutputFormat === 'xml' ? "true" : true);
                    $pcAgentResult[] = array("message" => "Card is successfully used");
                }
            } else {
                if ($pcAgentCall) {
                    $pcAgentResult[] = array("result" => $pcAgentOutputFormat === 'xml' ? "false" : false);
                    $pcAgentResult[] = array("message" => "Invalid card");
                } else {
                    show_window(__('Error'), __('Payment card invalid'));
                }
            }
        } else if ($pcAgentCall) {
            $pcAgentResult[] = array("result" => $pcAgentOutputFormat === 'xml' ? "false" : false);
            $pcAgentResult[] = array("message" => "No card number provided");
        } else {
            //show form
            show_window(__('Payment cards'), zbs_PaycardsShowForm());
        }
    } else {
        //yeh, he is an idiot
        if ($pcAgentCall) {
            $pcAgentResult[] = array("result" => $pcAgentOutputFormat === 'xml' ? "false" : false);
            $pcAgentResult[] = array("message" => "Too many attempts");
        } else {
            show_window(__('Error'), __('Sorry, but you have a limit number of attempts'));
        }
    }
} else {
    if ($pcAgentCall) {
        $pcAgentResult[] = array("result" => $pcAgentOutputFormat === 'xml' ? "false" : false);
        $pcAgentResult[] = array("message" => "Paycards module is disabled");
    } else {
        show_window(__('Sorry'), __('Payment cards are disabled at this moment'));
    }
}

if ($pcAgentCall) {
    XMLAgent::renderResponse($pcAgentResult, 'data', '', $pcAgentOutputFormat, false);
}
