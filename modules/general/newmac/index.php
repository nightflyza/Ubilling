<?php
if (cfr('NEWMAC')) {
  
show_window(__('Unknown MAC address'),zb_NewMacShow());

} else {
      show_error(__('You cant control this module'));
}

?>
