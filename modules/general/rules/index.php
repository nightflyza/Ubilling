<?php
if($system->checkForRight('RULES')) {

  if (wf_CheckPost(array('newrulename'))) {
      $createrulename=trim($_POST['newrulename']);
      if (!empty($createrulename)) {
      zb_DirectionAdd($_POST['newrulenumber'], $_POST['newrulename']); 
      zb_StargazerSIGHUP();
      rcms_redirect("?module=rules");
      } else {
          show_window(__('Error'), __('Required fields'));
      }
      
  }
  
  if (isset($_GET['delete'])) {
      zb_DirectionDelete($_GET['delete']);
      zb_StargazerSIGHUP();
      rcms_redirect("?module=rules");
  }
  
  
  if (isset($_GET['edit'])) {
      $editruleid=vf($_GET['edit'],3);
      if (wf_CheckPost(array('editrulename'))) {
          $newrulename=$_POST['editrulename'];
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