<?php

if (cfr('REPORTFINANCE')) {

    if (!wf_CheckGet(array('analytics'))) {
        if (!wf_CheckPost(array('yearsel'))) {
            $show_year = curyear();
        } else {
            $show_year = $_POST['yearsel'];
        }

        // Exclude some Cash types ID from query
        $dopWhere = '';
        if ($ubillingConfig->getAlterParam('REPORT_FINANCE_IGNORE_ID')) {
            $exIdArr = array_map('trim', explode(',', $ubillingConfig->getAlterParam('REPORT_FINANCE_IGNORE_ID')));
            $exIdArr = array_filter($exIdArr);
            // Create and WHERE to query
            if (!empty($exIdArr)) {
                $dopWhere = ' AND ';
                $dopWhere .= ' `cashtypeid` != ' . implode(' AND `cashtypeid` != ', $exIdArr);
            }
        }

        $dateSelectorPreset = (wf_CheckPost(array('showdatepayments'))) ? $_POST['showdatepayments'] : curdate();
        $dateinputs = wf_DatePickerPreset('showdatepayments', $dateSelectorPreset);
        $dateinputs .= wf_Submit(__('Show'));
        $dateform = wf_Form("?module=report_finance", 'POST', $dateinputs, 'glamour');


        $yearinputs = wf_YearSelectorPreset('yearsel', '', false, $show_year);
        $yearinputs .= wf_Submit(__('Show'));
        $yearform = wf_Form("?module=report_finance", 'POST', $yearinputs, 'glamour');


        $controlcells = wf_TableCell(wf_tag('h3', false, 'title') . __('Year') . wf_tag('h3', true));
        $controlcells .= wf_TableCell(wf_tag('h3', false, 'title') . __('Payments by date') . wf_tag('h3', true));
        $controlcells .= wf_TableCell(wf_tag('h3', false, 'title') . __('Payment search') . wf_tag('h3', true));
        $controlcells .= wf_TableCell(wf_tag('h3', false, 'title') . __('Analytics') . wf_tag('h3', true));
        $controlcells .= wf_TableCell(wf_tag('h3', false, 'title') . __('ARPU') . wf_tag('h3', true));
        if ($ubillingConfig->getAlterParam('PAYMENTCARDS_ENABLED')) {
            $controlcells .= wf_TableCell(wf_tag('h3', false, 'title') . __('Selling') . wf_tag('h3', true));
        }
        if ($ubillingConfig->getAlterParam('AGENTS_ASSIGN') == '2') {
            $controlcells .= wf_TableCell(wf_tag('h3', false, 'title') . __('Agent payments') . wf_tag('h3', true));
        }
        $controlrows = wf_TableRow($controlcells);

        $controlcells = wf_TableCell($yearform);
        $controlcells .= wf_TableCell($dateform);
        $controlcells .= wf_TableCell(wf_Link("?module=payfind", web_icon_search() . ' ' . __('Find'), false, 'ubButton'));
        $controlcells .= wf_TableCell(wf_Link("?module=report_finance&analytics=true", wf_img('skins/icon_stats.gif') . ' ' . __('Show'), false, 'ubButton'));
        $controlcells .= wf_TableCell(wf_Link("?module=report_arpu", wf_img('skins/ukv/report.png') . ' ' . __('Show'), false, 'ubButton'));
        if ($ubillingConfig->getAlterParam('AGENTS_ASSIGN') == '2') {
            $controlcells .= wf_TableCell(wf_Link("?module=report_agentfinance", wf_img('skins/corporate_small.png') . ' ' . __('Search'), false, 'ubButton'));
        }
        if ($ubillingConfig->getAlterParam('PAYMENTCARDS_ENABLED')) {
            $controlcells .= wf_TableCell(wf_Link("?module=report_selling", wf_img('skins/menuicons/selling.png') . ' ' . __('Show'), false, 'ubButton'));
        }
        $controlrows .= wf_TableRow($controlcells);

        $controlgrid = wf_TableBody($controlrows, '100%', 0, '');
        show_window('', $controlgrid);

//display year payments summary 
        web_PaymentsShowGraph($show_year);


        if (!isset($_GET['month'])) {

// payments by somedate
            if (isset($_POST['showdatepayments'])) {
                $paydate = mysql_real_escape_string($_POST['showdatepayments']);
                $paydate = (!empty($paydate)) ? $paydate : curdate();
                $fixerControl = (cfr('ROOT')) ? wf_Link('?module=paymentsfixer', ' ' . wf_img('skins/icon_repair.gif', __('Unprocessed payments repair'))) : '';
                show_window(__('Payments by date') . ' ' . $paydate . $fixerControl, web_PaymentsShow("SELECT * from `payments` WHERE `date` LIKE '" . $paydate . "%' " . $dopWhere . " ORDER by `date` DESC;"));
            } else {

// today payments
                $today = curdate();
                show_window(__('Today payments'), web_PaymentsShow("SELECT * from `payments` WHERE `date` LIKE '" . $today . "%' " . $dopWhere . " ORDER by `date` DESC;"));
            }
        } else {
            // show monthly payments
            $paymonth = mysql_real_escape_string($_GET['month']);

            show_window(__('Month payments'), web_PaymentsShow("SELECT * from `payments` WHERE `date` LIKE '" . $paymonth . "%' " . $dopWhere . " ORDER by `date` DESC;"));
        }
    } else {
        //show finance analytics info
        if (wf_CheckPost(array('anyearsel'))) {
            $currentYear = $_POST['anyearsel'];
        } else {
            $currentYear = date("Y");
        }



        $anControls = wf_BackLink("?module=report_finance");
        $anControls .= wf_Link('?module=metabolism', web_icon_charts() . ' ' . __('Metabolism'), false, 'ubButton');

        $yearinputs = wf_YearSelectorPreset('anyearsel', __('Year'), false, $currentYear) . ' ';
        $yearinputs .= wf_Submit(__('Show'));
        $anControls.= wf_delimiter();
        $anControls .= wf_Form('', 'POST', $yearinputs, 'glamour');
        $graphs = '';
        show_window('', $anControls);
        $ubCache = new UbillingCache();

        //try to cache rendered charts
        $graphs .= $ubCache->getCallback('ANALYTICSCHARTS_' . $currentYear, function() {
            if (wf_CheckPost(array('anyearsel'))) {
                $currentYear = $_POST['anyearsel'];
            } else {
                $currentYear = date("Y");
            }
            return (web_AnalyticsAllGraphs($currentYear));
        }, 3600);
        show_window(__('Analytics'), $graphs);
    }


    zb_BillingStats(true);
} else {
    show_error(__('You cant control this module'));
}
?>
