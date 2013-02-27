<?php
// check for right of current admin on this module
if (cfr('CATVPROFILE')) {
    
 
  catv_GlobalControlsShow();
  
  if (wf_CheckGet(array('userid'))) {
      catv_UserProfileShow($_GET['userid']);
      
  }  
    
    
} else {
      show_error(__('You cant control this module'));
}

?>