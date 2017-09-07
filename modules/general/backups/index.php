<?php

if (cfr('BACKUP')) {
    set_time_limit(0);
    $alterConf = $ubillingConfig->getAlter();

    if (!wf_CheckGet(array('restore'))) {
        if (isset($_POST['createbackup'])) {
            if (isset($_POST['imready'])) {
                if (!empty($alterConf['MYSQLDUMP_PATH'])) {
                    //run system mysqldump command
                    zb_backup_database();
                } else {
                    show_error(__('You missed an important option') . ': MYSQLDUMP_PATH');
                }
            } else {
                show_error(__('You are not mentally prepared for this'));
            }
        }

//downloading mysql dump
        if (wf_CheckGet(array('download'))) {
            if (cfr('ROOT')) {
                $filePath = base64_decode($_GET['download']);
                zb_DownloadFile($filePath);
            } else {
                show_error(__('Access denied'));
            }
        }


//deleting dump
        if (wf_CheckGet(array('deletedump'))) {
            if (cfr('ROOT')) {
                $deletePath = base64_decode($_GET['deletedump']);
                if (file_exists($deletePath)) {
                    rcms_delete_files($deletePath);
                    log_register('BACKUP DELETE `' . $deletePath . '`');
                    rcms_redirect('?module=backups');
                } else {
                    show_error(__('Not existing item'));
                }
            } else {
                show_error(__('Access denied'));
            }
        }

        function web_AvailableDBBackupsList() {
            $backupsPath = DATA_PATH . 'backups/sql/';
            $availbacks = rcms_scandir($backupsPath);
            $messages = new UbillingMessageHelper();
            $result = $messages->getStyledMessage(__('No existing DB backups here'), 'warning');
            if (!empty($availbacks)) {
                $cells = wf_TableCell(__('Date'));
                $cells.= wf_TableCell(__('Size'));
                $cells.= wf_TableCell(__('Filename'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($availbacks as $eachDump) {
                    $fileDate = filectime($backupsPath . $eachDump);
                    $fileDate = date("Y-m-d H:i:s", $fileDate);
                    $fileSize = filesize($backupsPath . $eachDump);
                    $fileSize = stg_convert_size($fileSize);
                    $encodedDumpPath = base64_encode($backupsPath . $eachDump);
                    $downloadLink = wf_Link('?module=backups&download=' . $encodedDumpPath, $eachDump, false, '');
                    $actLinks = wf_JSAlert('?module=backups&deletedump=' . $encodedDumpPath, web_delete_icon(), __('Removing this may lead to irreparable results')) . ' ';
                    $actLinks.= wf_Link('?module=backups&download=' . $encodedDumpPath, wf_img('skins/icon_download.png', __('Download')), false, '');
                    $actLinks.= wf_JSAlert('?module=backups&restore=true&restoredump=' . $encodedDumpPath, wf_img('skins/icon_restoredb.png', __('Restore DB')), __('Are you serious'));

                    $cells = wf_TableCell($fileDate);
                    $cells.= wf_TableCell($fileSize);
                    $cells.= wf_TableCell($downloadLink);
                    $cells.= wf_TableCell($actLinks);
                    $rows.= wf_TableRow($cells, 'row3');
                }
                $result = wf_TableBody($rows, '100%', '0', 'sortable');
            }

            return ($result);
        }

        function web_ConfigsUbillingList() {
            $downloadable = array(
                'config/billing.ini',
                'config/mysql.ini',
                'config/alter.ini',
                'config/ymaps.ini',
                'config/catv.ini',
                'config/photostorage.ini',
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
                $cells.= wf_TableCell(__('Size'));
                $cells.= wf_TableCell(__('Filename'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($downloadable as $eachConfig) {
                    if (file_exists($eachConfig)) {
                        $fileDate = filectime($eachConfig);
                        $fileDate = date("Y-m-d H:i:s", $fileDate);
                        $fileSize = filesize($eachConfig);
                        $fileSize = stg_convert_size($fileSize);
                        $downloadLink = wf_Link('?module=backups&download=' . base64_encode($eachConfig), $eachConfig, false, '');

                        $cells = wf_TableCell($fileDate);
                        $cells.= wf_TableCell($fileSize);
                        $cells.= wf_TableCell($downloadLink);
                        $rows.= wf_TableRow($cells, 'row3');
                    } else {
                        $cells = wf_TableCell('');
                        $cells.= wf_TableCell('');
                        $cells.= wf_TableCell($eachConfig);
                        $rows.= wf_TableRow($cells, 'row3');
                    }
                }
                $result = wf_TableBody($rows, '100%', '0', 'sortable');
            }

            return ($result);
        }

//tables cleanup
        if (wf_CheckGet(array('tableclean'))) {
            zb_DBTableCleanup($_GET['tableclean']);
            rcms_redirect("?module=backups");
        }


        show_window(__('Create backup'), web_BackupForm());
        show_window(__('Available database backups'), web_AvailableDBBackupsList());
        show_window(__('Important Ubilling configs'), web_ConfigsUbillingList());
        show_window(__('Database cleanup'), web_DBCleanupForm());
    } else {
        //database restoration functionality
        if (cfr('ROOT')) {
            if (!empty($alterConf['MYSQL_PATH'])) {
                if (wf_CheckGet(array('restoredump'))) {
                    $mysqlConf = rcms_parse_ini_file(CONFIG_PATH . 'mysql.ini');
                    $billingConf = $ubillingConfig->getBilling();
                    $restoreFilename = base64_decode($_GET['restoredump']);
                    if (file_exists($restoreFilename)) {
                        if (($billingConf['NOSTGCHECKPID']) AND ( !file_exists($billingConf['STGPID']))) {
                            if (!isset($_POST['lastchanceok'])) {
                                $lastChanceInputs = __('Restoring a database from a dump, completely and permanently destroy your current database. Think again if you really want it.');
                                $lastChanceInputs.=wf_tag('br');
                                $lastChanceInputs.=__('Filename') . ': ' . $restoreFilename;
                                $lastChanceInputs.=wf_tag('br');
                                $lastChanceInputs.= wf_CheckInput('lastchanceok', __('I`m ready'), true, false);
                                $lastChanceInputs.= wf_Submit(__('Restore DB'));
                                $lastChanceForm = wf_Form('', 'POST', $lastChanceInputs, 'glamour');
                                show_window(__('Warning'), $lastChanceForm);
                                show_window('', wf_BackLink('?module=backups', __('Back'), true, 'ubButton'));
                            } else {
                                $restoreCommand = $alterConf['MYSQL_PATH'] . ' -u ' . $mysqlConf['username'] . ' -p' . $mysqlConf['password'] . ' ' . $mysqlConf['db'] . ' --default-character-set=utf8 < ' . $restoreFilename;
                                show_window(__('Result'), shell_exec($restoreCommand));
                            }
                        } else {
                            log_register("BACKUP RESTORE TRY WITH RUNNING STARGAZER");
                            show_error(__('You can restore database only with enabled NOSTGCHECKPID option and stopped Stargazer'));
                            show_window('', wf_BackLink('?module=backups', __('Back'), true, 'ubButton'));
                        }
                    } else {
                        show_error(__('Strange exeption') . ': NOT_EXISTING_DUMP_FILE');
                    }
                } else {
                    show_error(__('Strange exeption') . ': GET_NO_DUMP_FILENAME');
                }
            } else {
                show_error(__('You missed an important option') . ': MYSQL_PATH');
            }
        } else {
            show_error(__('You cant control this module'));
        }
        //////////////////////////////////////////////////////
    }
} else {
    show_error(__('You cant control this module'));
}
?>
