<?php

if (cfr('SMSHIST')) {

    if ($ubillingConfig->getAlterParam('SMS_HISTORY_ON')) {
        $SMSHist = new SMSHistory();
        $inputs = $SMSHist->renderControls();

        $FilterDateFrom = ( wf_CheckGet(array('smshistdatefrom')) ) ? $_GET['smshistdatefrom'] : '"' . curdate() . '"';
        $FilterDateTo = ( wf_CheckGet(array('smshistdateto')) ) ? $_GET['smshistdateto'] : '"' . curdate() . '"';
        $FilterMsgStatus = ( wf_CheckGet(array('msgstatus')) ) ? $_GET['msgstatus'] : 'all';

        if ($FilterDateFrom == $FilterDateTo) {
            $WhereString = " WHERE DATE(`date_send`) = " . $FilterDateFrom;
        } else {
            $WhereString = " WHERE DATE(`date_send`) BETWEEN " . $FilterDateFrom . " AND " . $FilterDateTo;
        }

        switch ($FilterMsgStatus) {
            case 'delivered':
                $WhereString .= " AND `delivered` = 1";
                break;
            case 'undelivered':
                $WhereString .= " AND `delivered` = 0";
                break;
            case 'unknown':
                $WhereString .= " AND `delivered` = 0 AND `no_statuschk` = 0";
                break;
        }

        if (wf_CheckGet(array('ajax'))) {
            $WhereString = ( wf_CheckGet(array('usrlogin')) ) ? " WHERE `login` = '" . $_GET['usrlogin'] . "'" : $WhereString;
            $SMSHistoryData = $SMSHist->getSMSHistoryData($WhereString);
            $SMSHist->renderJSON($SMSHistoryData);
        }

        show_window(__('SMS messages history'), $inputs . $SMSHist->renderJQDT());
        zb_BillingStats(true);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>