<?php
if(cfr('ZBSANN')) {
   $altercfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
   if ($altercfg['ANNOUNCEMENTS']) {
       
       
       
       
       
       
   } else {
        show_window(__('Error'), __('This module is disabled'));
   }
} else {
    	show_error(__('Access denied'));
}

?>