<?php

if (cfr('REPORTFINANCE')) {

//year inputs
    $renderYear = (ubRouting::checkPost('yearsel')) ? ubRouting::post('yearsel', 'int') : curyear();
    $yearinputs = wf_YearSelectorPreset('yearsel', '', false, $renderYear);
    $yearinputs .= wf_Submit(__('Show'));
    $yearform = wf_Form('', 'POST', $yearinputs, 'glamour');

//date inputs
    $dateSelectorPreset = (ubRouting::checkPost('showdatepayments')) ? ubRouting::post('showdatepayments') : curdate();
    $dateinputs = wf_DatePickerPreset('showdatepayments', $dateSelectorPreset);
    $dateinputs .= wf_Submit(__('Show'));
    $dateform = wf_Form("?module=report_finance", 'POST', $dateinputs, 'glamour');


//rendering module controls
    $controlgrid = web_FinRepControls(__('Year'), $yearform);
    $controlgrid .= web_FinRepControls(__('Payments by date'), $dateform);
    if (cfr('PAYFIND')) {
        $controlgrid .= web_FinRepControls(__('Payment search'), wf_Link("?module=payfind", web_icon_search() . ' ' . __('Find'), false, 'ubButton'));
    }
    if (cfr('REPORTSIGNUP')) {
        $controlgrid .= web_FinRepControls(__('Metabolism'), wf_Link("?module=metabolism", web_icon_charts() . ' ' . __('Show'), false, 'ubButton'));
    }
    $controlgrid .= web_FinRepControls(__('ARPU'), wf_Link("?module=report_arpu", wf_img('skins/ukv/report.png') . ' ' . __('Show'), false, 'ubButton'));

    if ($ubillingConfig->getAlterParam('AGENTS_ASSIGN') == '2') {
        if (cfr('PAYFIND')) {
            $controlgrid .= web_FinRepControls(__('Agent payments'), wf_Link("?module=report_agentfinance", wf_img('skins/corporate_small.png') . ' ' . __('Search'), false, 'ubButton'));
        }
    }
    if ($ubillingConfig->getAlterParam('PAYMENTCARDS_ENABLED')) {
        $controlgrid .= web_FinRepControls(__('Selling'), wf_Link("?module=report_selling", wf_img('skins/menuicons/selling.png') . ' ' . __('Show'), false, 'ubButton'));
    }
    show_window('', $controlgrid . wf_CleanDiv());


//display year payments summary 
    if (!ubRouting::checkGet('branchreport')) {
        web_PaymentsShowGraph($renderYear);
    } else {
        web_PaymentsShowGraphPerBranch($renderYear);
    }

// Exclude some Cash types ID from query
    $dopWhere = '';
    if ($ubillingConfig->getAlterParam('REPORT_FINANCE_IGNORE_ID')) {
        $exIdArr = array_map('trim', explode(',', $ubillingConfig->getAlterParam('REPORT_FINANCE_IGNORE_ID')));
        $exIdArr = array_filter($exIdArr);
// Create and WHERE to query
        if (!empty($exIdArr)) {
            $dopWhere = ' AND `cashtypeid` != ' . implode(' AND `cashtypeid` != ', $exIdArr);
        }
    }

    if (!ubRouting::checkGet('month')) {
// payments by somedate
        if (ubRouting::checkPost('showdatepayments')) {
            $paydate = ubRouting::post('showdatepayments', 'mres');
            $paydate = (!empty($paydate)) ? $paydate : curdate();
            $fixerControl = (cfr('ROOT')) ? wf_Link('?module=paymentsfixer', ' ' . wf_img('skins/icon_repair.gif', __('Unprocessed payments repair'))) : '';
            $datePaymentsQuery = "SELECT * from `payments` WHERE `date` LIKE '" . $paydate . "%' " . $dopWhere . " ORDER by `date` DESC;";
            show_window(__('Payments by date') . ' ' . $paydate . $fixerControl, web_PaymentsShow($datePaymentsQuery));
        } else {
// today payments
            $todayPaymentsQuery = "SELECT * from `payments` WHERE `date` LIKE '" . curdate() . "%' " . $dopWhere . " ORDER by `date` DESC;";
            show_window(__('Today payments'), web_PaymentsShow($todayPaymentsQuery));
        }
    } else {
// show monthly payments
        $paymonth = ubRouting::get('month', 'mres');
        $monthPaymentsQuery = "SELECT * from `payments` WHERE `date` LIKE '" . $paymonth . "%' " . $dopWhere . " ORDER by `date` DESC;";
        show_window(__('Month payments'), web_PaymentsShow($monthPaymentsQuery));
    }

    zb_BillingStats(true);
} else {
    show_error(__('You cant control this module'));
}
    