<?php

$result = '';
if (cfr('USERPROFILE')) {
    if (wf_CheckGet(array('tariff'))) {
        $tariffName = ubRouting::get('tariff', 'mres');
        $tariffNameRaw = ubRouting::get('tariff');
        $tariffInfo = '';
        if ($tariffName == '*_NO_TARIFF_*') {
            $messages = new UbillingMessageHelper();
            $tariffInfo = $messages->getStyledMessage(__('No tariff'), 'warning');
        } else {
            $altCfg = $ubillingConfig->getAlter();
            $powerTariffFlag = false;

            if (@$altCfg['PT_ENABLED']) {
                $powerTariffs = new PowerTariffs(false);
                if ($powerTariffs->isPowerTariff($tariffName)) {
                    //Thats is power tariff
                    $powerTariffFlag = true;
                } else {
                    //user have an normal tariff
                    $powerTariffFlag = false;
                }
            }



            if ($powerTariffFlag) {
                $tariffPrice = $powerTariffs->getPowerTariffPrice($tariffName);
            } else {
                $tariffPrice = zb_TariffGetPrice($tariffName);
            }

            $tariffPeriods = zb_TariffGetPeriodsAll();
            $tariffSpeeds = zb_TariffGetAllSpeeds();

            $speedDown = (isset($tariffSpeeds[$tariffName])) ? $tariffSpeeds[$tariffName]['speeddown'] : __('No');
            $speedUp = (isset($tariffSpeeds[$tariffName])) ? $tariffSpeeds[$tariffName]['speedup'] : __('No');

            $period = (isset($tariffPeriods[$tariffName])) ? __($tariffPeriods[$tariffName]) : __('No');

            $cells = wf_TableCell(__('Fee'), '', 'row1');
            $cells .= wf_TableCell($tariffPrice);
            $rows = wf_TableRow($cells, 'row2');

            $cells = wf_TableCell(__('Download speed'), '', 'row1');
            $cells .= wf_TableCell($speedDown);
            $rows .= wf_TableRow($cells, 'row2');

            $cells = wf_TableCell(__('Upload speed'), '', 'row1');
            $cells .= wf_TableCell($speedUp);
            $rows .= wf_TableRow($cells, 'row2');

            $cells = wf_TableCell(__('Period'), '', 'row1');
            $cells .= wf_TableCell($period);
            $rows .= wf_TableRow($cells, 'row2');

            if (@$altCfg['PT_ENABLED']) {
                $cells = wf_TableCell(__('Power tariff'), '', 'row1');
                $tariffTypeLabel = ($powerTariffFlag) ? __('Yes') : __('No');
                $cells .= wf_TableCell($tariffTypeLabel);
                $rows .= wf_TableRow($cells, 'row2');

                if ($powerTariffFlag) {
                    $userLogin = ubRouting::get('username');
                    $cells = wf_TableCell(__('Day'), '', 'row1');
                    $personalDayOffset = $powerTariffs->getUserOffsetDay($userLogin);
                    $cells .= wf_TableCell($personalDayOffset);
                    $rows .= wf_TableRow($cells, 'row2');
                }
            }

            $tariffInfo = wf_TableBody($rows, '40%', 0, '');
        }
        $result = $tariffInfo;
    } else {
        $result = __('Strange exeption');
    }
} else {
    $result = __('Access denied');
}

//rendering results
if (!ubRouting::checkGet('debug')) {
    die($result);
} else {
    deb($result);
}
?>