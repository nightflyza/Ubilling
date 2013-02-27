<?php

$user_ip=zbs_UserDetectIp('debug');
$us_config=zbs_LoadConfig();
if ($us_config['OPENPAYZ_ENABLED']) {
$paysys=explode(",",$us_config['OPENPAYZ_PAYSYS']);    
if ($us_config['OPENPAYZ_REALID']) {
    $user_login=zbs_UserGetLoginByIp($user_ip);
    $payid=  zbs_PaymentIDGet($user_login);
} else {
    $payid=ip2int($user_ip);
}
//extracting paysys description
if (file_exists('config/opayz.ini')) {
    $opz_config=  parse_ini_file('config/opayz.ini');
} else {
    $opz_config=array();
}


$forms='';
if (!empty ($paysys)) {
    foreach ($paysys as $eachpaysys) {
       if (isset($opz_config[$eachpaysys])) {
           $paysys_desc=$opz_config[$eachpaysys];
       } else {
           $paysys_desc='';
       }
       
       $forms.='<center>
           <form action="'.$us_config['OPENPAYZ_URL'].$eachpaysys.'/" method="GET">
           <input type="hidden" name="customer_id" value="'.$payid.'">
           <input type="Submit" value="'.strtoupper($eachpaysys).'">
           </form> 
           '.$paysys_desc.'
           </center> <br>
           ';
       
    }
    show_window('',$forms);
} else {
    show_window(__('Sorry'), __('No available payment systems'));
}

    
} else {
    show_window(__('Sorry'),__('Unfortunately online payments is disabled'));
}

?>
