<?php

if (cfr('BACKUP')) {
    set_time_limit(0);
    $alterConf = $ubillingConfig->getAlter();

    if (!ubRouting::checkGet('restore')) {
//new database backup creation
        if (ubRouting::post('createbackup')) {
            if (ubRouting::post('imready')) {
                if (!empty($alterConf['MYSQLDUMP_PATH'])) {
                    //run system mysqldump command
                    zb_BackupDatabase();
                } else {
                    show_error(__('You missed an important option') . ': MYSQLDUMP_PATH');
                }
            } else {
                show_error(__('You are not mentally prepared for this'));
            }
        }

//downloading mysql dump or another configs backup
        if (ubRouting::checkGet('download')) {
            if (cfr('BACKUPDL')) {
                $filePath = base64_decode(ubRouting::get('download'));
                zb_DownloadFile($filePath);
            } else {
                show_error(__('Access denied'));
            }
        }


//deleting database dump
        if (ubRouting::checkGet('deletedump')) {
            //thats require root rights by security reasons
            if (cfr('ROOT')) {
                $deletePath = base64_decode(ubRouting::get('deletedump'));
                if (file_exists($deletePath)) {
                    rcms_delete_files($deletePath);
                    log_register('BACKUP DELETE `' . $deletePath . '`');
                    ubRouting::nav('?module=backups');
                } else {
                    show_error(__('Not existing item'));
                }
            } else {
                show_error(__('Access denied'));
            }
        }

//tables cleanup
        if (ubRouting::checkGet('tableclean')) {
            if (cfr('ROOT')) {
                zb_DBTableCleanup(ubRouting::get('tableclean'));
                ubRouting::nav('?module=backups');
            } else {
                show_error(__('Access denied'));
            }
        }


        show_window(__('Create backup'), web_BackupForm());
        show_window(__('Available database backups'), web_AvailableDBBackupsList());
        show_window(__('Important Ubilling configs'), web_ConfigsUbillingList());
        show_window(__('Database cleanup'), web_DBCleanupForm());
    } else {
        //database restoration functionality here
        if (cfr('ROOT')) {
            if (!empty($alterConf['MYSQL_PATH'])) {
                if (ubRouting::checkGet('restoredump')) {
                    $mysqlConf = rcms_parse_ini_file(CONFIG_PATH . 'mysql.ini');
                    $billingConf = $ubillingConfig->getBilling();
                    $restoreFilename = base64_decode(ubRouting::get('restoredump'));
                    if (file_exists($restoreFilename)) {
                        if (($billingConf['NOSTGCHECKPID']) AND ( !file_exists($billingConf['STGPID']))) {
                            if (!ubRouting::checkPost('lastchanceok')) {
                                $lastChanceInputs = __('Restoring a database from a dump, completely and permanently destroy your current database. Think again if you really want it.');
                                $lastChanceInputs .= wf_tag('br');
                                $lastChanceInputs .= __('Filename') . ': ' . $restoreFilename;
                                $lastChanceInputs .= wf_tag('br');
                                $lastChanceInputs .= wf_CheckInput('lastchanceok', __('I`m ready'), true, false);
                                $lastChanceInputs .= wf_Submit(__('Restore DB'));
                                $lastChanceForm = wf_Form('', 'POST', $lastChanceInputs, 'glamour');
                                show_window(__('Warning'), $lastChanceForm);
                                show_window('', wf_BackLink('?module=backups', __('Back'), true, 'ubButton'));
                            } else {
                                $restoreCommand = $alterConf['MYSQL_PATH'] . ' --host ' . $mysqlConf['server'] . ' -u ' . $mysqlConf['username'] . ' -p' . $mysqlConf['password'] . ' ' . $mysqlConf['db'] . ' --default-character-set=utf8 < ' . $restoreFilename . ' 2>&1';
                                $restoreResult = shell_exec($restoreCommand);
                                if (ispos($restoreResult, 'command line interface')) {
                                    $restoreResult = '';
                                }
                                if (empty($restoreResult)) {
                                    show_success(__('Success') . '! ' . __('Database') . ' ' . $mysqlConf['db'] . ' ' . __('is restored to server') . ' ' . $mysqlConf['server']);
                                } else {
                                    show_error(__('Something went wrong'));
                                    show_window(__('Result'), $restoreResult);
                                }
                                show_window('', wf_BackLink('?module=backups'));
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
    }
} else {
    show_error(__('You cant control this module'));
}
