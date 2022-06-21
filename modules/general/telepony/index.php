<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['TELEPONY_ENABLED']) {


    /**
     * Renders date selection form
     * 
     * @return string
     */
    function web_TelePonyDateForm() {
        $inputs = '';
        $inputs .= wf_DatePickerPreset('datefrom', curdate()) . ' ' . __('From');
        $inputs .= wf_DatePickerPreset('dateto', curdate()) . ' ' . __('To');
        $inputs .= wf_Submit(__('Show'));
        $result = wf_Form("", "POST", $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders incoming calls stats if it exists
     * 
     * @return void
     */
    function zb_TelePonyRenderNumLog() {
        global $ubillingConfig;
        $billCfg = $ubillingConfig->getBilling();
        $logPath = PBXNum::LOG_PATH;
        $catPath = $billCfg['CAT'];
        $grepPath = $billCfg['GREP'];
        $replyOffset = 5;
        $numberOffset = 2;
        $loginOffset = 7;
        $replyCount = 0;
        $replyStats = array();
        $replyNames = array(
            0 => __('Not found'),
            1 => __('Active'),
            2 => __('Debt'),
            3 => __('Frozen')
        );

        $result = '';
        if (file_exists($logPath)) {
            if (!wf_CheckPost(array('numyear', 'nummonth'))) {
                $curYear = curyear();
                $curMonth = date("m");
            } else {
                $curYear = vf($_POST['numyear'], 3);
                $curMonth = vf($_POST['nummonth'], 3);
            }
            $parseDate = $curYear . '-' . $curMonth;

            $dateInputs = wf_YearSelectorPreset('numyear', __('Year'), false, $curYear) . ' ';
            $dateInputs .= wf_MonthSelector('nummonth', __('Month'), $curMonth, false) . ' ';
            $dateInputs .= wf_Submit(__('Show'));
            $result .= wf_Form('', 'POST', $dateInputs, 'glamour');

            $rawLog = shell_exec($catPath . ' ' . $logPath . ' | ' . $grepPath . ' ' . $parseDate . '-');
            if (!empty($rawLog)) {
                $rawLog = explodeRows($rawLog);
                if (!empty($rawLog)) {
                    foreach ($rawLog as $io => $each) {
                        if (!empty($each)) {
                            $line = explode(' ', $each);
                            $callReply = $line[$replyOffset];
                            if (isset($replyStats[$callReply])) {
                                $replyStats[$callReply] ++;
                            } else {
                                $replyStats[$callReply] = 1;
                            }
                            $replyCount++;
                        }
                    }

                    if (!empty($replyStats)) {
                        $cells = wf_TableCell(__('Calls'));
                        $cells .= wf_TableCell(__('Count'));
                        $rows = wf_TableRow($cells, 'row1');
                        foreach ($replyStats as $replyCode => $callsCount) {
                            $cells = wf_TableCell($replyNames[$replyCode]);
                            $cells .= wf_TableCell($callsCount);
                            $rows .= wf_TableRow($cells, 'row3');
                        }
                        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                        $result .= __('Total') . ': ' . $replyCount;
                    }
                }
            }

            if (filesize($logPath) > 10) {
                show_window(__('Stats') . ' ' . __('on') . ' ' . $curYear . '-' . $curMonth, $result);
            }
        }
    }

    /**
     * Returns raw CDR for selected period of time
     * 
     * @param string $cdrConf
     * @param string $dateFrom
     * @param string $dateTo
     * 
     * @return array/bool
     */
    function zb_TelePonyGetCDR($cdrConf = '', $dateFrom = '', $dateTo = '') {
        $result = array();

        if (!empty($cdrConf)) {
            $cdrConf = explode('|', $cdrConf);
            if (sizeof($cdrConf) == 5) {
                if ($dateFrom AND $dateTo) {
                    $dateFrom .= ' 00:00:00';
                    $dateTo .= ' 23:59:59';
                }

                $host = $cdrConf[0];
                $login = $cdrConf[1];
                $password = $cdrConf[2];
                $db = $cdrConf[3];
                $table = $cdrConf[4];

                $cdr = new PBXCdr($host, $login, $password, $db, $table);
                $result = $cdr->getCDR($dateFrom, $dateTo);
            } else {
                $result = false;
            }
        }

        return($result);
    }

    /**
     * Renders some calls history
     * 
     * @param string $cdrConf
     * 
     * @return string
     */
    function zb_TelePonyRenderCDR($cdrConf = '') {
        $result = '';
        $messages = new UbillingMessageHelper();
        $dateFrom = ubRouting::post('datefrom');
        $dateTo = ubRouting::post('dateto');
        $rawCdr = zb_TelePonyGetCDR($cdrConf, $dateFrom, $dateTo);

        if ($rawCdr !== false) {
            debarr($rawCdr);
        } else {
            $result .= $messages->getStyledMessage(__('Wrong element format') . ': TELEPONY_CDR ' . __('is corrupted'), 'error');
        }
        return($result);
    }

    if (cfr('TELEPONY')) {
        //showing call history form
        if ($altCfg['TELEPONY_CDR']) {
            show_window(__('Calls history'), web_TelePonyDateForm());
        }

        //basic calls stats
        zb_TelePonyRenderNumLog();

        //rendering calls history here
        if ($altCfg['TELEPONY_CDR']) {
            if (ubRouting::checkPost(array('datefrom', 'dateto'))) {
                show_window(__('TelePony') . ' - ' . __('Calls history'), zb_TelePonyRenderCDR($altCfg['TELEPONY_CDR']));
            }
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('This module is disabled'));
}

