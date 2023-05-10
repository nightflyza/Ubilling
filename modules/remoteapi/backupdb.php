<?php

/**
 * Database backup
 */
if (ubRouting::get('action') == 'backupdb') {
    if ($alterconf['MYSQLDUMP_PATH']) {
        $backpath = zb_BackupDatabase(true);
        if (@$alterconf['BACKUPS_MAX_AGE']) {
            zb_BackupsRotate($alterconf['BACKUPS_MAX_AGE']);
        }
    } else {
        die(__('You missed an important option') . ': MYSQLDUMP_PATH');
    }
    die('OK:BACKUPDB ' . $backpath);
}