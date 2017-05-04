<?php
if (cfr('RESET')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // reset user if need
       $billing->resetuser($login);
       log_register("RESET User (".$login.")");
       rcms_redirect("?module=userprofile&username=".$login);   
      
}

} else {
      show_error(__('You cant control this module'));
}

?>
