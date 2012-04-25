<?php
// check for right of current admin on this module
if (cfr('EMPLOYEE')) {

   if (isset ($_POST['addemployee'])) {
   stg_add_employee($_POST['employeename'], $_POST['employeejob']);
   }
   
   if (isset ($_GET['delete'])) {
   stg_delete_employee($_GET['delete']);
   }
   
   if (isset ($_POST['addjobtype'])) {
   stg_add_jobtype($_POST['newjobtype']);
   }
   
   if (isset ($_GET['deletejob'])) {
   stg_delete_jobtype($_GET['deletejob']);
   }
   
   if (!wf_CheckGet(array('edit'))) {
       stg_show_employee_form();
       stg_show_jobtype_form();
   } else {
       $editemployee=vf($_GET['edit']);
       
       //if someone editing employee
       if (isset($_POST['editname'])) {
           simple_update_field('employee', 'name', $_POST['editname'], "WHERE `id`='".$editemployee."'");
           simple_update_field('employee', 'appointment', $_POST['editappointment'], "WHERE `id`='".$editemployee."'");
           if (wf_CheckPost(array('editactive'))) {
               simple_update_field('employee', 'active', '1', "WHERE `id`='".$editemployee."'");
           } else {
               simple_update_field('employee', 'active', '0', "WHERE `id`='".$editemployee."'");
           }
           log_register('EMPLOYEE CHANGE '.$editemployee);
           rcms_redirect("?module=employee");
           
       }
       
       
       $employeedata=stg_get_employee_data($editemployee);
       if ($employeedata['active']) {
           $actflag=true;
       } else {
           $actflag=false;
       }
       $editinputs=wf_TextInput('editname','Real Name' , $employeedata['name'], true, 20);
       $editinputs.=wf_TextInput('editappointment','Appointment' , $employeedata['appointment'], true, 20);
       $editinputs.=wf_CheckInput('editactive', 'Active', true, $actflag);
       $editinputs.=wf_Submit('Save');
       $editform=wf_Form('', 'POST', $editinputs, 'glamour');
       show_window(__('Edit'), $editform);
       show_window('', wf_Link('?module=employee', 'Back', true, 'ubButton'));
       
   }
   
  
  
    
} else {
      show_error(__('You cant control this module'));
}

?>
