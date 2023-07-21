<?php

/**
 * Returns database backup creation form
 * 
 * @return string
 */
function web_BackupForm() {
    $backupinputs = __('This will create a backup copy of all tables in the database') . wf_tag('br');
    $backupinputs .= wf_HiddenInput('createbackup', 'true');
    $backupinputs .= wf_CheckInput('imready', 'I`m ready', true, false);
    $backupinputs .= wf_Submit('Create');
    $form = wf_Form('', 'POST', $backupinputs, 'glamour');

    return($form);
}

/**
 * Renders list of available database backup dumps
 * 
 * @return string
 */
function web_AvailableDBBackupsList() {
    $backupsPath = DATA_PATH . 'backups/sql/';
    $availbacks = rcms_scandir($backupsPath);
    $messages = new UbillingMessageHelper();
    $result = $messages->getStyledMessage(__('No existing DB backups here'), 'warning');
    if (!empty($availbacks)) {
        $cells = wf_TableCell(__('Creation date'));
        $cells .= wf_TableCell(__('Size'));
        $cells .= wf_TableCell(__('Filename'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($availbacks as $eachDump) {
            if (is_file($backupsPath . $eachDump)) {
                $fileDate = filectime($backupsPath . $eachDump);
                $fileDate = date("Y-m-d H:i:s", $fileDate);
                $fileSize = filesize($backupsPath . $eachDump);
                $fileSize = stg_convert_size($fileSize);
                $encodedDumpPath = base64_encode($backupsPath . $eachDump);
                $downloadLink = wf_Link('?module=backups&download=' . $encodedDumpPath, $eachDump, false, '');
                $actLinks = wf_JSAlert('?module=backups&deletedump=' . $encodedDumpPath, web_delete_icon(), __('Removing this may lead to irreparable results')) . ' ';
                $actLinks .= wf_Link('?module=backups&download=' . $encodedDumpPath, wf_img('skins/icon_download.png', __('Download')), false, '');
                $actLinks .= wf_JSAlert('?module=backups&restore=true&restoredump=' . $encodedDumpPath, wf_img('skins/icon_restoredb.png', __('Restore DB')), __('Are you serious'));

                $cells = wf_TableCell($fileDate);
                $cells .= wf_TableCell($fileSize);
                $cells .= wf_TableCell($downloadLink);
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
    }

    return ($result);
}

/**
 * Renders list of important configs with some download controls
 * 
 * @return string
 */
function web_ConfigsUbillingList() {
    $downloadable = array(
        'config/billing.ini',
        'config/mysql.ini',
        'config/alter.ini',
        'config/ymaps.ini',
        'config/photostorage.ini',
        'config/config.ini',
        'config/dhcp/global.template',
        'config/dhcp/subnets.template',
        'config/dhcp/option82.template',
        'config/dhcp/option82_vpu.template',
        'userstats/config/mysql.ini',
        'userstats/config/userstats.ini',
        'userstats/config/tariffmatrix.ini'
    );


    if (!empty($downloadable)) {
        $cells = wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Size'));
        $cells .= wf_TableCell(__('Filename'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($downloadable as $eachConfig) {
            if (file_exists($eachConfig)) {
                $fileDate = filectime($eachConfig);
                $fileDate = date("Y-m-d H:i:s", $fileDate);
                $fileSize = filesize($eachConfig);
                $fileSize = stg_convert_size($fileSize);
                $downloadLink = wf_Link('?module=backups&download=' . base64_encode($eachConfig), $eachConfig, false, '');

                $cells = wf_TableCell($fileDate);
                $cells .= wf_TableCell($fileSize);
                $cells .= wf_TableCell($downloadLink);
                $rows .= wf_TableRow($cells, 'row5');
            } else {
                $cells = wf_TableCell('');
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell($eachConfig);
                $rows .= wf_TableRow($cells, 'row5');
            }
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
    }

    return ($result);
}

/**
 * Shows database cleanup form
 * 
 * @return string
 */
function web_DBCleanupForm() {
    $oldLogs = zb_DBCleanupGetLogs();
    $oldDetailstat = zb_DBCleanupGetDetailstat();
    $cleanupData = $oldLogs + $oldDetailstat;
    $result = '';
    $totalRows = 0;
    $totalSize = 0;
    $totalCount = 0;

    $cells = wf_TableCell(__('Table name'));
    $cells .= wf_TableCell(__('Rows'));
    $cells .= wf_TableCell(__('Size'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($cleanupData)) {
        foreach ($cleanupData as $io => $each) {
            $cells = wf_TableCell($each['name']);
            $cells .= wf_TableCell($each['rows']);
            $cells .= wf_TableCell(stg_convert_size($each['size']), '', '', 'sorttable_customkey="' . $each['size'] . '"');
            $actlink = wf_JSAlert("?module=backups&tableclean=" . $each['name'], web_delete_icon(), 'Are you serious');
            $cells .= wf_TableCell($actlink);
            $rows .= wf_TableRow($cells, 'row5');
            $totalRows = $totalRows + $each['rows'];
            $totalSize = $totalSize + $each['size'];
            $totalCount = $totalCount + 1;
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    $result .= wf_tag('b') . __('Total') . ': ' . $totalCount . ' / ' . $totalRows . ' / ' . stg_convert_size($totalSize) . wf_tag('b', true);

    return ($result);
}

/**
 * Dumps database to file and returns filename
 * 
 * @param bool $silent
 * 
 * @return string
 */
function zb_BackupDatabase($silent = false) {
    global $ubillingConfig;
    $backname = '';
    $backupProcess = new StarDust('BACKUPDB');
    if ($backupProcess->notRunning()) {
        $backupProcess->start();
        $alterConf = $ubillingConfig->getAlter();
        $mysqlConf = rcms_parse_ini_file(CONFIG_PATH . 'mysql.ini');

        $backname = DATA_PATH . 'backups/sql/ubilling-' . date("Y-m-d_H_i_s", time()) . '.sql';
        $command = $alterConf['MYSQLDUMP_PATH'] . ' --host ' . $mysqlConf['server'] . ' -u ' . $mysqlConf['username'] . ' -p' . $mysqlConf['password'] . ' ' . $mysqlConf['db'] . ' > ' . $backname;
        shell_exec($command);

        if (!$silent) {
            show_success(__('Backup saved') . ': ' . $backname);
        }

        log_register('BACKUP CREATE `' . $backname . '`');
        $backupProcess->stop();
    } else {
        log_register('BACKUP ALREADY RUNNING SKIPPED');
    }
    return ($backname);
}

/**
 * Destroy or flush table in database
 * 
 * @param $tablename  string table name 
 * @return void
 */
function zb_DBTableCleanup($tablename) {
    $tablename = vf($tablename);
    $method = 'DROP';
    if (!empty($tablename)) {
        $query = $method . " TABLE `" . $tablename . "`";
        nr_query($query);
        log_register("DBCLEANUP `" . $tablename . "`");
    }
}

/**
 * Auto Cleans all deprecated data
 * 
 * @return string count of cleaned tables
 */
function zb_DBCleanupAutoClean() {
    $oldLogs = zb_DBCleanupGetLogs();
    $oldDstat = zb_DBCleanupGetDetailstat();
    $allClean = $oldLogs + $oldDstat;
    $counter = 0;
    if (!empty($allClean)) {
        foreach ($allClean as $io => $each) {
            zb_DBTableCleanup($each['name']);
            $counter++;
        }
    }
    return ($counter);
}

/**
 * Gets list of old stargazer log_ tables exept current month
 * 
 * @return array
 */
function zb_DBCleanupGetLogs() {
    $logs_query = "SHOW TABLE STATUS WHERE `Name` LIKE 'logs_%'";
    $allogs = simple_queryall($logs_query);
    $oldlogs = array();
    $skiplog = 'logs_' . date("m") . '_' . date("Y");
    if (!empty($allogs)) {
        foreach ($allogs as $io => $each) {
            $filtered = array_values($each);
            $oldlogs[$filtered[0]]['name'] = $each['Name'];
            $oldlogs[$filtered[0]]['rows'] = $each['Rows'];
            $oldlogs[$filtered[0]]['size'] = $each['Data_length'];
        }
    }

    if (!empty($oldlogs)) {
        unset($oldlogs[$skiplog]);
    }

    return ($oldlogs);
}

/**
 * Gets list of old stargazer detailstat_ tables exept current month
 * 
 * @return array
 */
function zb_DBCleanupGetDetailstat() {
    $detail_query = "SHOW TABLE STATUS WHERE `Name` LIKE 'detailstat_%'";
    $all = simple_queryall($detail_query);
    $old = array();
    $skip = 'detailstat_' . date("m") . '_' . date("Y");
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $filtered = array_values($each);
            $old[$filtered[0]]['name'] = $each['Name'];
            $old[$filtered[0]]['rows'] = $each['Rows'];
            $old[$filtered[0]]['size'] = $each['Data_length'];
        }
    }

    if (!empty($old)) {
        unset($old[$skip]);
    }

    return ($old);
}
