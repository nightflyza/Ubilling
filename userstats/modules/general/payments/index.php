<?php

function zbs_ShowUserPayments($login) {
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
            $result.='
                <tr class="row2">
                   <td>'.$eachpayment['date'].'</td>
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
