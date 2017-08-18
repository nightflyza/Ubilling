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

    $style = '
     <style>

    .dashtask {
    float: left !important;
    display: block !important;
    padding: 10px;
    margin: 5px;
    border:solid 1px #EEE;
    border-radius:10px;
    text-align: center;
    font-size: 8pt;
    overflow: hidden;
    box-shadow: 3px 3px 4px rgba(0,0,0,0);
    line-height: 100%;
    width:200px;
    height:200px;
    }

    .dashtask:hover {
    -webkit-box-shadow: 3px 3px 4px rgba(50, 50, 50, 0.75);
    -moz-box-shadow:    3px 3px 4px rgba(50, 50, 50, 0.75);
    box-shadow:         3px 3px 4px rgba(50, 50, 50, 0.75);
        }
        </style>';

    $inputs = '';
    $forms = '';

    if (!empty($paysys)) {
        foreach ($paysys as $eachpaysys) {
            if (isset($opz_config[$eachpaysys])) {
                $paysys_desc = $opz_config[$eachpaysys];
            } else {
                $paysys_desc = '';
            }

            $skinPath = zbs_GetCurrentSkinPath();
            $iconsPath = $skinPath . 'paysys/';
            $iconsExt = '.png';
            if (file_exists($iconsPath . $eachpaysys . $iconsExt)) {
                $paysysIcon = $iconsPath . $eachpaysys . $iconsExt;
            } else {
                $paysysIcon = '';
            }

            $inputs = la_tag('div', false, 'dashtask');
            $inputs.=la_HiddenInput('customer_id', $payid);

            if (empty($paysysIcon)) {
                $inputs.=la_Submit(strtoupper($eachpaysys));
                $inputs.=la_tag('br');
                $inputs.=$paysys_desc;
            } else {
                $fullDesc = ' alt="' . strtoupper($eachpaysys) . ' - ' . $paysys_desc . '" title="' . strtoupper($eachpaysys) . ' - ' . $paysys_desc . '" ';
                $iconParams = 'width="200" height="200" ';
                $inputs.=la_tag('input', false, '', 'type="image" src="' . $paysysIcon . '"' . $fullDesc . $iconParams);
            }
            $inputs.=la_tag('div', true);


            $forms.= la_Form($us_config['OPENPAYZ_URL'] . $eachpaysys . '/', 'GET', $inputs, '', '', false);
        }


        show_window(__('Online payments'), $style);
        show_window('', $forms);
    } else {
        show_window(__('Sorry'), __('No available payment systems'));
    }
} else {
    show_window(__('Sorry'), __('Unfortunately online payments are disabled'));
}
?>
