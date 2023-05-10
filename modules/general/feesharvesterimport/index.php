<?php

if (cfr('ROOT')) {
    if ($ubillingConfig->getAlterParam('FEES_HARVESTER')) {
        set_time_limit(0);
        $billCfg = $ubillingConfig->getBilling();
        $altCfg = $ubillingConfig->getAlter();
        $baseUrl = '?module=feesharvesterimport';
        $fundsFlow = new FundsFlow();

        $moduleControls = '';
        $moduleControls .= wf_Link($baseUrl . '&migrate=full', wf_img('skins/icon_restoredb.png') . ' ' . __('Migrate all fees data for all time'), false, 'ubButton');
        $moduleControls .= wf_Link($baseUrl . '&migrate=month', wf_img('skins/icon_restoredb.png') . ' ' . __('Migrate fees data by selected month'), false, 'ubButton');
        show_window(__('Migrate previous fees data into database'), $moduleControls);

        //rendering some stats
        if (!ubRouting::checkGet('migrate')) {
            $stgLogPath = $altCfg['STG_LOG_PATH'];
            $wcPath = '/usr/bin/wc'; // WTF???
            $catPath = $billCfg['CAT'];
            $grepPath = $billCfg['GREP'];

            if (file_exists($stgLogPath)) {
                //counting fees from stargazer log
                $command = $catPath . ' ' . $stgLogPath . ' | ' . $grepPath . ' "' . $fundsFlow::MASK_FEE . '" | ' . $wcPath . ' -l';
                $feeRecordsCount = shell_exec($command);
                $feeRecordsCount = trim($feeRecordsCount);

                //counting fees from DB
                $feesDb = new NyanORM($fundsFlow::TABLE_FEES);
                $feesDb->where('cashtype', '=', '0');
                $feesDb->where('admin', '=', 'stargazer');
                $feeDbCount = $feesDb->getFieldsCount();

                show_info(__('Stargazer log file contains') . ' ' . $feeRecordsCount . ' ' . __('fee records'));
                show_info(__('Ubilling database contains') . ' ' . $feeDbCount . ' ' . __('fee records'));
                if ($feeRecordsCount > $feeDbCount) {
                    show_warning(__('May be not all of fee records imported to database'));
                } else {
                    show_success(__('Looks like all data currently synced'));
                }
            } else {
                show_error(__('File not found') . ': ' . $stgLogPath);
            }

            show_window('', wf_BackLink('?module=taskbar'));
        } else {
            $migrationMode = ubRouting::get('migrate');
            //migration confirmation interface for selected time range
            if (!ubRouting::checkPost('runmigration')) {
                if ($migrationMode == 'full') {
                    $inputs = '';
                    $inputs .= wf_HiddenInput('runmigration', 'true');
                    $inputs .= wf_CheckInput('agree', __('I`m ready'), false, false) . ' ';
                    $inputs .= wf_Submit(__('Migrate all fees data for all time'));
                    $confirmationForm = wf_Form('', 'POST', $inputs, 'glamour');
                    show_window('', $confirmationForm);
                }

                if ($migrationMode == 'month') {
                    $inputs = '';
                    $inputs .= wf_HiddenInput('runmigration', 'true');
                    $inputs .= wf_YearSelectorPreset('migrateyear', __('Year'), false, curyear()) . ' ';
                    $inputs .= wf_MonthSelector('migratemonth', __('Month'), date("m"), false) . ' ';
                    $inputs .= wf_CheckInput('agree', __('I`m ready'), true, false) . ' ';
                    $inputs .= wf_delimiter(0);
                    $inputs .= wf_Submit(__('Migrate fees data by selected month'));
                    $confirmationForm = wf_Form('', 'POST', $inputs, 'glamour');
                    show_window('', $confirmationForm);
                }

                show_info(__('This process make take a while, please be patient'));
            } else {
                //do some migration here
                if (ubRouting::checkPost('agree')) {
                    //process manager init
                    $harvesterProcess = new StarDust('FEESHARVESTER');

                    //full migration here
                    if ($migrationMode == 'full') {
                        if ($harvesterProcess->notRunning()) {
                            $harvesterProcess->start();
                            $harvestedFees = $fundsFlow->harvestFees();
                            $harvesterProcess->stop();
                            ubRouting::nav($baseUrl);
                        } else {
                            show_error(__('Migration') . ': ' . __('Already running'));
                        }
                    }

                    //month migration
                    if ($migrationMode == 'month') {
                        if (ubRouting::checkPost(array('migrateyear', 'migratemonth'))) {
                            $migrateYear = ubRouting::post('migrateyear', 'int');
                            $migrateMonth = ubRouting::post('migratemonth', 'int');
                            $customPeriod = $migrateYear . '-' . $migrateMonth;
                            if ($harvesterProcess->notRunning()) {
                                $harvesterProcess->start();
                                $harvestedFees = $fundsFlow->harvestFees($customPeriod);
                                $harvesterProcess->stop();
                                ubRouting::nav($baseUrl);
                            } else {
                                show_error(__('Migration') . ': ' . __('Already running'));
                            }
                        } else {
                            show_error(__('Something went wrong'));
                        }
                    }
                } else {
                    show_error(__('You are not mentally prepared for this'));
                }
            }
            show_window('', wf_BackLink($baseUrl));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}