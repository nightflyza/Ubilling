<?php
if (cfr('BACKUP')) {
set_time_limit (0);

if (isset ($_POST['createbackup'])) {
    if (isset($_POST['imready'])) {
        zb_backup_tables();
    } else {
        show_error(__('Вы морально не готовы к этому'));
    }
    
      
}
    

show_window(__('Create backup'), web_BackupForm());

} else {
      show_error(__('You cant control this module'));
}

?>
