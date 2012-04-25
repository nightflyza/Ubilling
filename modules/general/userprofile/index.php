<?php
if(cfr('USERPROFILE')) {
 if (isset ($_GET['username'])) {
        $login=vf($_GET['username']);
        show_window(__('User profile'),web_ProfileShow($login));
      }
        

}
?>
