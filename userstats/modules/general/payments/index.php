<?php

function zbs_ShowUserPayments($login) {
    $usConfig=  zbs_LoadConfig();
    $allpayments=zbs_CashGetUserPayments($login);
    $result='<table width="100%" border="0">';
    if (!$usConfig['PAYMENTSTIMEHIDE']) {
        $dateCells='<td>'.__('Date').'</td>';
    } else {
        $dateCells='';
    }
    $result.='
                <tr class="row1">
                   '.$dateCells.'
                   <td>'.__('Payment').'</td>
                   <td>'.__('Balance').'</td>
                </tr>
                ';   
    if (!empty ($allpayments)) {
        foreach ($allpayments as $io=>$eachpayment) {
                    if (!$usConfig['PAYMENTSTIMEHIDE']) {
                        $dateCells='<td>'.$eachpayment['date'].'</td>';
                    } else {
                        $dateCells='';
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
}

$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
zbs_ShowUserPayments($user_login);

?>
