<?php
if (cfr('BACKUP')) {
set_time_limit (0);

if (isset ($_POST['createbackup'])) {
    if (isset($_POST['imready'])) {
        zb_backup_tables();
        
    } else {
        show_error(__('You are not mentally prepared for this'));
    }
    
      
}

//tables cleanup
if (wf_CheckGet(array('tableclean'))) {
    zb_DBTableCleanup($_GET['tableclean']);
    rcms_redirect("?module=backups");
}

    

show_window(__('Create backup'), web_BackupForm());
show_window(__('Database cleanup'),web_DBCleanupForm());

} else {
      show_error(__('You cant control this module'));
}

?>
