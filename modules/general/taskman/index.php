<?php
if(cfr('TASKMAN')) {
    
  
    
    //if someone creates new task
    if (isset($_POST['createtask'])) {
    if (wf_CheckPost(array('newstartdate','newtaskaddress','newtaskphone'))) {
        ts_CreateTask($_POST['newstartdate'], $_POST['newtaskaddress'], $_POST['newtaskphone'], $_POST['newtaskjobtype'], $_POST['newtaskemployee'], $_POST['newjobnote']);
        if (!isset($_GET['gotolastid'])) {
            rcms_redirect("?module=taskman");
        } else {
            $lasttaskid=  simple_get_lastid('taskman');
            rcms_redirect("?module=taskman&edittask=".$lasttaskid);
        }
        
    } else {
        show_window(__('Error'), __('All fields marked with an asterisk are mandatory'));
     }
    }
    
    
    //modify task sub
    if (isset($_POST['modifytask'])) {
       if (wf_CheckPost(array('modifystartdate','modifytaskaddress','modifytaskphone'))) {
        $taskid=$_POST['modifytask'];
        ts_ModifyTask($taskid, $_POST['modifystartdate'], $_POST['modifytaskaddress'], $_POST['modifytaskphone'], $_POST['modifytaskjobtype'], $_POST['modifytaskemployee'], $_POST['modifytaskjobnote']);
        rcms_redirect("?module=taskman&edittask=".$taskid);
    } else {
        show_window(__('Error'), __('All fields marked with an asterisk are mandatory'));
     }
    }
    
    //if marking task as done
    if (isset($_POST['changetask'])) {
        if (wf_CheckPost(array('editenddate','editemployeedone'))) {
            //editing task sub
            $editid=vf($_POST['changetask']);
            simple_update_field('taskman', 'enddate', $_POST['editenddate'], "WHERE `id`='".$editid."'");
            simple_update_field('taskman', 'employeedone', $_POST['editemployeedone'], "WHERE `id`='".$editid."'");
            simple_update_field('taskman', 'donenote', $_POST['editdonenote'], "WHERE `id`='".$editid."'");
            simple_update_field('taskman', 'status', '1', "WHERE `id`='".$editid."'");
            log_register('TASKMAN DONE '.$editid);
            
            
        } else {
           show_window(__('Error'), __('All fields marked with an asterisk are mandatory')); 
        }
    }
    
    //setting task undone
    if (isset($_GET['setundone'])) {
        $undid=vf($_GET['setundone'],3);
        simple_update_field('taskman', 'status', '0', "WHERE `id`='".$undid."'");
        simple_update_field('taskman', 'enddate', 'NULL', "WHERE `id`='".$undid."'");
        log_register("TASKMAN UNDONE ".$undid);
        rcms_redirect("?module=taskman");
    }
    
    
    //deleting task 
    if (isset($_GET['deletetask'])) {
        $delid=vf($_GET['deletetask'],3);
        ts_DeleteTask($delid);
        rcms_redirect("?module=taskman");
    }
    
    
    
    show_window(__('Manage tasks'),ts_ShowPanel());
    
    if (isset($_GET['show'])) {
        if ($_GET['show']=='undone') {
             $showtasks=ts_JGetUndoneTasks();
        }
        
        if ($_GET['show']=='done') {
             $showtasks=ts_JGetDoneTasks();
        }
        
        if ($_GET['show']=='all') {
             $showtasks=ts_JGetAllTasks();
        }
        
    } else {
        $showtasks=ts_JGetUndoneTasks();
    }
    
    if (!isset($_GET['edittask'])) {
        show_window('',wf_FullCalendar($showtasks));
    } else {
        ts_TaskChangeForm($_GET['edittask']);
    }
    
    
} else {
    show_error(__('Access denied'));
}

?>