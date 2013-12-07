<?php
if(cfr('USERPROFILE')) {
 if (isset ($_GET['username'])) {
        $login=vf($_GET['username']);
        if (!empty($login)) {
            show_window(__('User profile'),web_ProfileShow($login));
        } else {
          throw new Exception ('GET_EMPTY_USERNAME');  
        }
      } else {
          throw new Exception ('GET_NO_USERNAME');
      }

}
?>
