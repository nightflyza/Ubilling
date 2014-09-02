<?php
// check for right of current admin on this module
if (cfr('CFTYPES')) {
    
  //if someone deleting type
  if (isset($_GET['delete'])) {
      cf_TypeDelete($_GET['delete']);
      rcms_redirect("?module=cftypes");
  }
  
  //if someone adding field
  if (isset($_POST['newtype'])) {
      cf_TypeAdd($_POST['newtype'], $_POST['newname']);
      rcms_redirect("?module=cftypes");
  }
  
  //if someone edits type
  if (isset($_POST['editname'])) {
      $typeid=vf($_POST['editid']);
      $typename=$_POST['editname'];
      $typetype=$_POST['edittype'];
      simple_update_field('cftypes', 'type', $typetype, 'WHERE `id` = "'.$typeid.'"');
      simple_update_field('cftypes', 'name', $typename, 'WHERE `id` = "'.$typeid.'"');
      log_register("CFTYPE CHANGE [".$typeid."] `".$typename."`");
      rcms_redirect("?module=cftypes");
  }
  
  if (!isset($_GET['edit'])) {
  show_window(__('Available custom profile field types'),cf_TypesShow());
  show_window(__('Create new field type'),cf_TypeAddForm());
  } else {
      //editing type
      $typeid=vf($_GET['edit']);
      cf_TypeEditForm($typeid);
      
  }

  
} else {
      show_error(__('You cant control this module'));
}

?>
