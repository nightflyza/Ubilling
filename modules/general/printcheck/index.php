<?php

if ( cfr('PRINTCHECK') ) {
    if ( isset($_GET['paymentid']) ) {
        $paymentid = $_GET['paymentid'];
        $alter = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
        if ( !empty($alter['DOCX_SUPPORT']) && !empty($alter['DOCX_CHECK']) ) {
            @$payment = zb_PaymentGetData($paymentid); // id, date, summ...
            @$payment['idenc'] = zb_NumEncode($payment['id']);
            @$payment['summ_lit'] = num2str($payment['summ']);
            @$payment['summ_exp'] = explode('.', $payment['summ']);
            @$payment['summ_cels'] = ( !empty($payment['summ_exp'][0]) ) ? $payment['summ_exp'][0] : '0';
            @$payment['summ_cops'] = ( !empty($payment['summ_exp'][1]) ) ? $payment['summ_exp'][1] : '00';
            @$payment['daypayid'] = zb_PrintCheckGetDayNum($payment['id'], $payment['date']);
            @$user['login'] = $payment['login'];
            @$user['realname'] = zb_UserGetRealName($user['login']);
            @$user['address'] = zb_UserGetFullAddress($user['login']);
            @$user['contract'] = zb_UserGetContract($user['login']);
            @$user['email'] = zb_UserGetEmail($user['login']);
            @$user['phone'] = zb_UserGetPhone($user['login']);
            @$user['mobile'] = zb_UserGetMobile($user['login']);
            @$user['agent'] = zb_AgentAssignedGetData($user['login']);
            @$cashier = zb_PrintCheckLoadCassNames(true);
            @$current['day'] = date('d');
            @$current['month'] = date('m');
            @$current['monty_lit'] = months_array($current['month']);
            @$current['month_loc'] = rcms_date_localise($current['monty_lit']);
            @$current['year'] = date('Y');
            
            // Forming parse template:
            $template['PAYID']      = ( !empty($payment['id']) )    ? $payment['id']    : '';
            $template['PAYIDENC']   = ( !empty($payment['idenc']) ) ? $payment['idenc'] : '';
            $template['AGENTEDRPO'] = ( !empty( $user['agent']['edrpo']) )      ? $user['agent']['edrpo']       : '';
            $template['AGENTNAME']  = ( !empty($user['agent']['contrname']) )   ? $user['agent']['contrname']   : '';
            $template['PAYDATE']    = ( !empty($payment['date']) ) ? $payment['date'] : '';
            $template['PAYSUMM']    = ( !empty($payment['summ']) ) ? $payment['summ'] : '';
            $template['PAYSUMM_CELS']   = ( !empty($payment['summ_cels']) ) ? $payment['summ_cels'] : '';  // rev. 3179 +
            $template['PAYSUMM_COPS']   = ( !empty($payment['summ_cops']) ) ? $payment['summ_cops'] : '';  // rev. 3179 +
            $template['PAYSUMM_LIT']    = ( !empty($payment['summ_lit']) )  ? $payment['summ_lit']  : '';
            $template['LOGIN']      = ( !empty($user['login']) )    ? $user['login']    : ''; // rev. 3179 +
            $template['REALNAME']   = ( !empty($user['realname']) ) ? $user['realname'] : '';
            $template['ADDRESS']    = ( !empty($user['address']) )  ? $user['address']  : '';
            $template['CONTRACT']   = ( !empty($user['contract']) ) ? $user['contract'] : ''; // rev. 3179 +
            $template['EMAIL']      = ( !empty($user['email']) )    ? $user['email']    : ''; // rev. 3179 +
            $template['PHONE']      = ( !empty($user['phone']) )    ? $user['phone']    : ''; // rev. 3179 +
            $template['MOBILE']     = ( !empty($user['mobile']) )   ? $user['mobile']   : ''; // rev. 3179 +
            $template['BUHNAME']    = '';
            $template['CASNAME']    = ( !empty($cashier) ) ? $cashier : '';
            $template['PAYTARGET']  = '';
            $template['CDAY']   = ( !empty($current['day']) )       ? $current['day']       : '';
            $template['CMONTH'] = ( !empty($current['month_loc']) ) ? $current['month_loc'] : '';
            $template['CYEAR']  = ( !empty($current['year']) )      ? $current['year']      : '';
            $template['DAYPAYID'] = ( !empty($payment['daypayid']) ) ? $payment['daypayid'] : '';
            
            $docx = new DOCXTemplate(CONFIG_PATH . '/printcheck.docx');
            $docx->set($template);
            $docx->downloadAs('check-' . $payment['id'] . '.docx');
        } else {
            print(zb_PrintCheck($paymentid));
            die();
        }
    }
} else show_error(__('You cant control this module'));

?>