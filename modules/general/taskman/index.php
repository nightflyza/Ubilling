<?php
if(cfr('TASKMAN')) {
    
    //if someone creates new task
    if (isset($_POST['createtask'])) {
    if (wf_CheckPost(array('newstartdate','newtaskaddress','newtaskphone'))) {
        if (wf_CheckPost(array('typicalnote'))) {
         $newjobnote=$_POST['typicalnote'].' '.$_POST['newjobnote'];    
        } else {
         $newjobnote=$_POST['newjobnote'];      
        }
        ts_CreateTask($_POST['newstartdate'],@$_POST['newstarttime'], $_POST['newtaskaddress'],@$_POST['newtasklogin'], $_POST['newtaskphone'], $_POST['newtaskjobtype'], $_POST['newtaskemployee'], $newjobnote);
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
        ts_ModifyTask($taskid, $_POST['modifystartdate'], $_POST['modifystarttime'], $_POST['modifytaskaddress'], @$_POST['modifytasklogin'],$_POST['modifytaskphone'], $_POST['modifytaskjobtype'], $_POST['modifytaskemployee'], $_POST['modifytaskjobnote']);
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
            log_register('TASKMAN DONE ['.$editid.']');
            //generate job for some user
            if (wf_CheckPost(array('generatejob','generatelogin','generatejobid'))) {
                stg_add_new_job($_POST['generatelogin'], curdatetime(), $_POST['editemployeedone'], $_POST['generatejobid'], 'TASKID:['.$_POST['changetask'].']');
                log_register("TASKMAN GENJOB (".$_POST['generatelogin'].') VIA ['.$_POST['changetask'].']');
            }
            
            
        } else {
           show_window(__('Error'), __('All fields marked with an asterisk are mandatory')); 
        }
    }
    
    //setting task undone
    if (isset($_GET['setundone'])) {
        $undid=vf($_GET['setundone'],3);
        simple_update_field('taskman', 'status', '0', "WHERE `id`='".$undid."'");
        simple_update_field('taskman', 'enddate', 'NULL', "WHERE `id`='".$undid."'");
        log_register("TASKMAN UNDONE [".$undid.']');
        rcms_redirect("?module=taskman");
    }
    
    
    //deleting task 
    if (isset($_GET['deletetask'])) {
        $delid=vf($_GET['deletetask'],3);
        ts_DeleteTask($delid);
        rcms_redirect("?module=taskman");
    }
    
    
    if (!wf_CheckGet(array('probsettings'))) { 
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
        if (!wf_CheckGet(array('print'))) {
            if (!wf_CheckGet(array('lateshow'))) {
                //custom jobtypes color styling
                $customJobColorStyle=ts_GetAllJobtypesColorStyles();
                //show full calendar view
                show_window('',$customJobColorStyle.wf_FullCalendar($showtasks));
            } else {
                show_window(__('Show late'),ts_ShowLate());
            }
        } else {
            //printable result
            if (wf_CheckPost(array('printdatefrom','printdateto'))) {
                ts_PrintTasks($_POST['printdatefrom'],$_POST['printdateto']);
            }
            //show printing form
            show_window(__('Tasks printing'), ts_PrintDialogue());
        }
    } else {
        //sms post sending
        if (wf_CheckPost(array('postsendemployee','postsendsmstext'))) {
                $smsDataRaw=ts_SendSMS($_POST['postsendemployee'], $_POST['postsendsmstext']);
                if (!empty($smsDataRaw)) {
                  $smsDataSave=  serialize($smsDataRaw);
                  $smsDataSave= "'".base64_encode($smsDataSave)."'";
                  simple_update_field('taskman', 'smsdata', $smsDataSave, "WHERE `id`='".$_GET['edittask']."'");
                  //flushing dark void
                  $darkVoid=new DarkVoid();
                  $darkVoid->flushCache();
                  rcms_redirect('?module=taskman&edittask='.$_GET['edittask']);
                }
        }
        
        //sms data flush
        if (wf_CheckGet(array('flushsmsdata'))) {
            ts_FlushSMSData($_GET['flushsmsdata']);
            rcms_redirect('?module=taskman&edittask='.$_GET['flushsmsdata']);
        }
        
        //display task change form
        ts_TaskChangeForm($_GET['edittask']);
        //additional comments 
        $altCfg=$ubillingConfig->getAlter();
        if ($altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments=new ADcomments('TASKMAN');
            show_window(__('Additional comments'), $adcomments->renderComments($_GET['edittask']));
        }
    }
    
    } else {
        show_window(__('Typical problems'), ts_TaskProblemsEditForm());
    }
    
} else {
    show_error(__('Access denied'));
}

?>