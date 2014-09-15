<?php
if(cfr('USERPROFILE')) {
 if (isset ($_GET['username'])) {
        $login=vf($_GET['username']);
        $profile=new UserProfile($login);
        show_window(__('User profile'),$profile->render());
     
      } else {
          throw new Exception ('GET_NO_USERNAME');
      }

}
?>
