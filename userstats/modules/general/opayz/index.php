<?php

$user_ip=zbs_UserDetectIp('debug');
$us_config=zbs_LoadConfig();
if ($us_config['OPENPAYZ_ENABLED']) {
$paysys=explode(",",$us_config['OPENPAYZ_PAYSYS']);    
$payid=ip2int($user_ip);

$forms='';
if (!empty ($paysys)) {
    foreach ($paysys as $eachpaysys) {
       $forms.='<center>
           <form action="'.$us_config['OPENPAYZ_URL'].$eachpaysys.'/" method="GET">
           <input type="hidden" name="customer_id" value="'.$payid.'">
           <input type="Submit" value="'.strtoupper($eachpaysys).'">
           </form> 
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
