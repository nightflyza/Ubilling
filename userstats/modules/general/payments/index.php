<?php

function zbs_ShowUserPayments($login) {
    $usConfig=  zbs_LoadConfig();
    if ($usConfig['PAYMENTS_ENABLED']) {
    $allpayments=zbs_CashGetUserPayments($login);
    $result='<table width="100%" border="0">';

    $result.='
                <tr class="row1">
                   <td>'.__('Date').'</td>
                   <td>'.__('Payment').'</td>
                   <td>'.__('Balance').'</td>
                </tr>
                ';   
    if (!empty ($allpayments)) {
        foreach ($allpayments as $io=>$eachpayment) {
                    if ($usConfig['PAYMENTSTIMEHIDE']) {
                        $timestamp=  strtotime($eachpayment['date']);
                        $cleanDate= date("Y-m-d",$timestamp);
                        $dateCells='<td>'.$cleanDate.'</td>';
                    } else {
                        $dateCells='<td>'.$eachpayment['date'].'</td>';
                    }
            $result.='
                <tr class="row2">
                   '.$dateCells.'
                   <td>'.$eachpayment['summ'].'</td>
                   <td>'.$eachpayment['balance'].'</td>
                </tr>
         ';   
        }
     
    }
    $result.='</table>';
    show_window(__('Last payments'),$result);
    } else {
        $result=__('This module is disabled');
        show_window(__('Sorry'),$result);
    }
    
}

$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
zbs_ShowUserPayments($user_login);

?>
