<?php

if (cfr('PRINTCHECK')) {
    if (ubRouting::checkGet('paymentid')) {
        $paymentid = ubRouting::get('paymentid', 'int');
        $alter = $ubillingConfig->getAlter();
        if (!empty($alter['DOCX_SUPPORT']) and !empty($alter['DOCX_CHECK'])) {
            $morph = new UBMorph();
            @$payment = zb_PaymentGetData($paymentid); // id, date, summ...
            @$payment['idenc'] = zb_NumEncode($payment['id']);
            @$payment['summ_lit'] = $morph->sum2str($payment['summ']);
            @$payment['summ_exp'] = explode('.', $payment['summ']);
            @$payment['summ_cels'] = (!empty($payment['summ_exp'][0])) ? $payment['summ_exp'][0] : '0';
            @$payment['summ_cops'] = (!empty($payment['summ_exp'][1])) ? $payment['summ_exp'][1] : '00';
            @$payment['daypayid'] = zb_PrintCheckGetDayNum($payment['id'], $payment['date']);
            @$user['login'] = $payment['login'];
            @$user['realname'] = zb_UserGetRealName($user['login']);
            @$user['address'] = zb_UserGetFullAddress($user['login']);
            @$user['contract'] = zb_UserGetContract($user['login']);
            @$user['email'] = zb_UserGetEmail($user['login']);
            @$user['phone'] = zb_UserGetPhone($user['login']);
            @$user['mobile'] = zb_UserGetMobile($user['login']);
            @$user['agent'] = zb_AgentAssignedGetDataFast($user['login'], $user['address']);
            @$cashier = zb_PrintCheckLoadCassNames(true);
            @$current['day'] = date('d');
            @$current['month'] = date('m');
            @$current['monty_lit'] = months_array($current['month']);
            @$current['month_loc'] = rcms_date_localise($current['monty_lit']);
            @$current['year'] = date('Y');

            // Forming parse template:
            $template['PAYID']      = (!empty($payment['id']))    ? $payment['id']    : '';
            $template['PAYIDENC']   = (!empty($payment['idenc'])) ? $payment['idenc'] : '';
            $template['AGENTEDRPO'] = (!empty($user['agent']['edrpo']))      ? $user['agent']['edrpo']       : '';
            $template['AGENTNAME']  = (!empty($user['agent']['contrname']))   ? $user['agent']['contrname']   : '';
            $template['PAYDATE']    = (!empty($payment['date'])) ? $payment['date'] : '';
            $template['PAYSUMM']    = (!empty($payment['summ'])) ? $payment['summ'] : '';
            $template['PAYSUMM_CELS']   = (!empty($payment['summ_cels'])) ? $payment['summ_cels'] : '';
            $template['PAYSUMM_COPS']   = (!empty($payment['summ_cops'])) ? $payment['summ_cops'] : '';
            $template['PAYSUMM_LIT']    = (!empty($payment['summ_lit']))  ? $payment['summ_lit']  : '';
            $template['PAYNOTE']    = (!empty($payment['note']))  ? $payment['note']  : '';
            $template['LOGIN']      = (!empty($user['login']))    ? $user['login']    : '';
            $template['REALNAME']   = (!empty($user['realname'])) ? $user['realname'] : '';
            $template['ADDRESS']    = (!empty($user['address']))  ? $user['address']  : '';
            $template['FULLADDRESS'] = (!empty($user['address']))  ? $user['address']  : '';
            $template['CONTRACT']   = (!empty($user['contract'])) ? $user['contract'] : '';
            $template['EMAIL']      = (!empty($user['email']))    ? $user['email']    : '';
            $template['PHONE']      = (!empty($user['phone']))    ? $user['phone']    : '';
            $template['MOBILE']     = (!empty($user['mobile']))   ? $user['mobile']   : '';
            $template['BUHNAME']    = '';
            $template['CASNAME']    = (!empty($cashier)) ? $cashier : '';
            $template['PAYTARGET']  = '';
            $template['CDAY']   = (!empty($current['day']))       ? $current['day']       : '';
            $template['CMONTH'] = (!empty($current['month_loc'])) ? $current['month_loc'] : '';
            $template['CYEAR']  = (!empty($current['year']))      ? $current['year']      : '';
            $template['DAYPAYID'] = (!empty($payment['daypayid'])) ? $payment['daypayid'] : '';
            //contragent full data
            $template['AGENTID'] = (!empty($user['agent']['id'])) ? $user['agent']['id']  : '';
            $template['AGENTBANKACC'] = (!empty($user['agent']['bankacc'])) ? $user['agent']['bankacc']  : '';
            $template['AGENTBANKNAME'] = (!empty($user['agent']['bankname'])) ? $user['agent']['bankname']  : '';
            $template['AGENTBANKCODE'] = (!empty($user['agent']['bankcode'])) ? $user['agent']['bankcode']  : '';
            $template['AGENTIPN'] = (!empty($user['agent']['ipn'])) ? $user['agent']['ipn']  : '';
            $template['AGENTLICENSE'] = (!empty($user['agent']['licensenum'])) ? $user['agent']['licensenum']  : '';;
            $template['AGENTJURADDR'] = (!empty($user['agent']['juraddr'])) ? $user['agent']['juraddr']  : '';;
            $template['AGENTPHISADDR'] = (!empty($user['agent']['phisaddr'])) ? $user['agent']['phisaddr']  : '';;
            $template['AGENTPHONE'] = (!empty($user['agent']['phone'])) ? $user['agent']['phone']  : '';;

            // printing template:
            $templatesPath = DATA_PATH . 'documents/';
            $templateName = 'printcheck.docx';
            if (ubRouting::checkGet('th')) {
                $templateName = 'printcheck_th.docx';
            }
            if (file_exists($templatesPath . $templateName)) {
                $docx = new DOCXTemplate($templatesPath . $templateName);
                $docx->set($template);
                $docx->downloadAs('check-' . $payment['id'] . '.docx');
            } else {
                show_error(__('Template') . ' ' . $templatesPath . $templateName . ' ' . __('not exists'));
            }
        } else {
            print(zb_PrintCheck($paymentid, $alter['OPENPAYZ_REALID']));
            die();
        }
    }
} else {
    show_error(__('You cant control this module'));
}
