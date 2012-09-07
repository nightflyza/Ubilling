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
    

show_window(__('Create backup'), web_BackupForm());

} else {
      show_error(__('You cant control this module'));
}

?>
