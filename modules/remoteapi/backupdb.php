<?php

/*
 * database backup
 */
if ($_GET['action'] == 'backupdb') {
    if ($alterconf['MYSQLDUMP_PATH']) {
        $backpath = zb_backup_database(true);
        if (@$alterconf['BACKUPS_MAX_AGE']) {
            zb_backups_rotate($alterconf['BACKUPS_MAX_AGE']);
        }
    } else {
        die(__('You missed an important option') . ': MYSQLDUMP_PATH');
    }
    die('OK:BACKUPDB ' . $backpath);
}