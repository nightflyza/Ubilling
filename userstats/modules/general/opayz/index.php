<?php

$user_ip = zbs_UserDetectIp('debug');
$us_config = zbs_LoadConfig();
if ($us_config['OPENPAYZ_ENABLED']) {
    $paysys = explode(",", $us_config['OPENPAYZ_PAYSYS']);
    if ($us_config['OPENPAYZ_REALID']) {
        $user_login = zbs_UserGetLoginByIp($user_ip);
        $payid = zbs_PaymentIDGet($user_login);
    } else {
        $payid = ip2int($user_ip);
    }
//extracting paysys description
    if (file_exists('config/opayz.ini')) {
        $opz_config = parse_ini_file('config/opayz.ini');
    } else {
        $opz_config = array();
    }


    $inputs = '';
    $forms = '';

    if (!empty($paysys)) {
        foreach ($paysys as $eachpaysys) {
            if (isset($opz_config[$eachpaysys])) {
                $paysys_desc = $opz_config[$eachpaysys];
            } else {
                $paysys_desc = '';
            }

            $inputs = la_tag('center', false);

            $inputs.=la_HiddenInput('customer_id', $payid);
            $inputs.=la_Submit(strtoupper($eachpaysys));
            $inputs.=la_tag('br');
            $inputs.=$paysys_desc;
            $inputs.=la_tag('center', true);
            $inputs.=la_tag('br');


            $forms.= la_Form($us_config['OPENPAYZ_URL'] . $eachpaysys . '/', 'GET', $inputs, '');
        }


        show_window('', $forms);
    } else {
        show_window(__('Sorry'), __('No available payment systems'));
    }
} else {
    show_window(__('Sorry'), __('Unfortunately online payments are disabled'));
}
?>
