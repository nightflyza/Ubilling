<?php
if(cfr('WATCHDOG')) {
   $altercfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
   if ($altercfg['WATCHDOG_ENABLED']) {
   $interface=new WatchDogInterface();
   $interface->loadAllTasks();
   $interface->loadSettings();
   
   //manual run of existing tasks
   if (wf_CheckGet(array('manual'))) {
           $watchdog=new WatchDog();
           $watchdog->processTask();
           rcms_redirect("?module=watchdog");
   }
   //deleting existing task
   if (wf_CheckGet(array('delete'))) {
       $interface->deleteTask($_GET['delete']);
       rcms_redirect("?module=watchdog");
   }
   
   //adding new task
   if (wf_CheckPost(array('newname','newchecktype','newparam','newoperator'))) {
       if (isset($_POST['newactive'])) {
           $newActivity=1;
       } else {
           $newActivity=0;
       }
       $interface->createTask($_POST['newname'], $_POST['newchecktype'], $_POST['newparam'], $_POST['newoperator'], $_POST['newcondition'], $_POST['newaction'], $newActivity);
       rcms_redirect("?module=watchdog");
   }
   
   //changing task
   if (wf_CheckPost(array('editname'))) {
       $interface->changeTask();
       rcms_redirect("?module=watchdog");
   }
   
   //changing watchdog settings
   if (wf_CheckPost(array('changealert'))) {
       $interface->saveSettings();
       rcms_redirect("?module=watchdog");
      }
      
   
   
   //show sms queue
   if (wf_CheckGet(array('showsmsqueue'))) {
       show_window('', wf_Link('?module=watchdog', __('Back'), true, 'ubButton'));
       show_window(__('View SMS sending queue'), $interface->showSMSqueue());
       
   } else {
   //show watchdog main control panel
   show_window('', $interface->panel());
   
   if (!wf_CheckGet(array('edit'))) {
    //show interface controls   
    show_window(__('Available Watchdog tasks'),$interface->listAllTasks());
   } else {
       //show task edit form
       show_window(__('Edit task'),$interface->editTaskForm($_GET['edit']));
   }
   }
   
   } else {
       show_window(__('Error'), __('This module is disabled'));
   }
    
} else {
	show_error(__('Access denied'));
}

?>