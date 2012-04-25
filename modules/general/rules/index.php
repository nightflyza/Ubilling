<?php
if($system->checkForRight('RULES')) {

  if (isset($_POST['newrulename'])) {
      zb_DirectionAdd($_POST['newrulenumber'], $_POST['newrulename']);
      rcms_redirect("?module=rules");
  }
  
  if (isset($_GET['delete'])) {
      zb_DirectionDelete($_GET['delete']);
      rcms_redirect("?module=rules");
  }
  
  
  if (isset($_GET['edit'])) {
      $editruleid=vf($_GET['edit'],3);
      if (isset($_POST['editrulename'])) {
          $newrulename=  mysql_real_escape_string($_POST['editrulename']);
          simple_update_field('directions', 'rulename', $newrulename, "WHERE `id`='".$editruleid."'");
          log_register("CHANGE TrafficClass ".$editruleid." ON ".$newrulename);
          rcms_redirect("?module=rules");
   }
      
      web_DirectionsEditForm($editruleid);
      
  } else {
  web_DirectionsShow();
  web_DirectionAddForm();
  }
}
else {
	show_error(__('Access denied'));
}
?>