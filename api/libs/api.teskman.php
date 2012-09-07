<?php
 function stg_show_employee_form() {
     $show_q="SELECT * from `employee`";
     $allemployee=simple_queryall($show_q);
     $result='<table width="100%" border="0"><tbody>';
     $result.='<tr class="row1">
         <td>'.__('ID').'</td>
         <td>'.__('Real Name').'</td>
         <td>'.__('Active').'</td>
         <td>'.__('Appointment').'</td>
         <td>'.__('Actions').'</td></tr>';
     if (!empty ($allemployee)) {
         foreach ($allemployee as $ion=>$eachemployee) {
         $result.='<tr class="row3">';
         $result.='<td>'.$eachemployee['id'].'</td>';
         $result.='<td>'.$eachemployee['name'].'</td>';
         $result.='<td>'.web_bool_led($eachemployee['active']).'</td>';
         $result.='<td>'.$eachemployee['appointment'].'</td>';
         $result.='<td>
                 '.  wf_JSAlert('?module=employee&delete='.$eachemployee['id'], web_delete_icon(), 'Removing this may lead to irreparable results').'
                 '.  wf_JSAlert('?module=employee&edit='.$eachemployee['id'], web_edit_icon(), 'Are you serious').'
                 </td>';
         $result.='</tr>';
         }
       }
      $result.='
          <form action="" method="POST">
          <input type="hidden" name="addemployee" value="true">
          <tr class="row2">
          <td></td>
          
          <td><input type="text"  name="employeename" size="30"></td>
          <td></td>
          <td><input type="text"  name="employeejob" size="30"></td>
          
          <td><input type="submit" value="'.__('Add').'"></td>
          </tr>
          </form>
          ';
      $result.='</tbody></table>';
      show_window(__('Employee'),$result);
   }

function stg_show_jobtype_form() {
     $show_q="SELECT * from `jobtypes`";
     $alljobs=simple_queryall($show_q);
     $result='<table width="100%" border="0"><tbody>';
     $result.='<tr class="row1"><td>ID</td><td>'.__('Job type').'</td><td>'.__('Actions').'</td></tr>';
     if (!empty ($alljobs)) {
         foreach ($alljobs as $ion=>$eachjob) {
         $result.='<tr class="row3">';
         $result.='<td>'.$eachjob['id'].'</td>';
         $result.='<td>'.$eachjob['jobname'].'</td>';
         $actionlinks= wf_JSAlert('?module=employee&deletejob='.$eachjob['id'], web_delete_icon(), 'Removing this may lead to irreparable results') .' ';
         $actionlinks.=wf_JSAlert('?module=employee&editjob='.$eachjob['id'], web_edit_icon(), 'Are you serious');
         $result.='<td>'. $actionlinks.'</td>';
         $result.='</tr>';
         }
       }
       
      $result.='
          <form action="" method="POST">
          <input type="hidden" name="addjobtype" value="true">
          <tr class="row2">
          <td></td>
          <td><input type="text"  name="newjobtype" size="30"></td>
          <td><img src="skins/icon_add.gif" border="0"><input type="submit" value="'.__('Add').'"></td>
          </tr>
          </form>
          ';
      $result.='</tbody></table>';
      show_window(__('Job types'),$result);
   }

function stg_add_employee($name,$job) {
        $name=mysql_real_escape_string(trim($name));
        $job=mysql_real_escape_string(trim($job));
        $query="
            INSERT INTO `employee` (
                `id` ,
                `name` ,
                `appointment`,
                `active`
                )
                VALUES (
                NULL , '".$name."', '".$job."', '1'
                );
                ";
     nr_query($query);
     stg_putlogevent('EMPLOYEE ADD '.$name.' JOB '.$job);
    }
function stg_delete_employee($id) {
     $query="DELETE from `employee` WHERE `id`=".$id;
     nr_query($query);
     stg_putlogevent('EMPLOYEE DEL '.$id);
    }

 function stg_add_jobtype($jobtype) {
        $jobtype=mysql_real_escape_string(trim($jobtype));
        $query="
            INSERT INTO `jobtypes` (
                `id` ,
                `jobname`
                )
                VALUES (
                NULL , '".$jobtype."'
                );
                ";
     nr_query($query);
     stg_putlogevent('JOBTYPEADD '.$jobtype);
    }

 function stg_delete_jobtype($id) {
     $query="DELETE from `jobtypes` WHERE `id`=".$id;
     nr_query($query);
     stg_putlogevent('JOBTYPEDEL '.$id);
    }

function stg_get_employee_name($id) {
$query='SELECT `name` from `employee` WHERE `id`="'.$id.'"';
$employee=simple_query($query);
return($employee['name']);
}

function stg_get_employee_data($id) {
$query='SELECT *  from `employee` WHERE `id`="'.$id.'"';
$employee=simple_query($query);
return($employee);
}

function stg_get_jobtype_name($id) {
$query='SELECT `jobname` from `jobtypes` WHERE `id`="'.$id.'"';
$jobtype=simple_query($query);
return($jobtype['jobname']);
}


function stg_worker_selector() {
    $query="SELECT * from `employee` WHERE `active`='1'";
    $allemployee=simple_queryall($query);
    $result='<select name="worker">';
    if (!empty ($allemployee)) {
        foreach ($allemployee as $io=>$eachwrker) {
        $result.='<option value="'.$eachwrker['id'].'">'.$eachwrker['name'].'</option>';
        }
    }
    $result.='</select>';
    return($result);
}

function stg_jobtype_selector() {
    $query="SELECT * from `jobtypes`";
    $alljobtypes=simple_queryall($query);
    $result='<select name="jobtype">';
    if (!empty ($alljobtypes)) {
        foreach ($alljobtypes as $io=>$eachjobtype) {
        $result.='<option value="'.$eachjobtype['id'].'">'.$eachjobtype['jobname'].'</option>';
        }
    }
    $result.='</select>';
    return($result);
}

function stg_show_jobs($username) {
    $query_jobs='SELECT * FROM `jobs` WHERE `login`="'.$username.'"';
    $alljobs=simple_queryall($query_jobs);
    $result='<table width="100%" border="0"><tbody>';
        $result.='<tr class="row1">';
        $result.='<td width="5%">ID</td>';
        $result.='<td width="15%">'.__('Date').'</td>';
        $result.='<td width="20%">'.__('Worker').'</td>';
        $result.='<td width="20%">'.__('Job type').'</td>';
        $result.='<td width="40%">'.__('Notes').'</td>';
        $result.='<td></td>';
        $result.='</tr>';

    if (!empty ($alljobs)) {
        foreach ($alljobs as $ion=>$eachjob) {
        $result.='<tr class="row3">';
        $result.='<td>'.$eachjob['id'].'</td>';
        $result.='<td>'.$eachjob['date'].'</td>';
        $result.='<td>'.stg_get_employee_name($eachjob['workerid']).'</td>';
        $result.='<td>'.stg_get_jobtype_name($eachjob['jobid']).'</td>';
        $result.='<td>'.$eachjob['note'].'</td>';
        $result.='<td>'.  wf_JSAlert('?module=jobs&username='.$username.'&deletejob='.$eachjob['id'].'', web_delete_icon(), 'Are you serious').'</td>';
        $result.='</tr>';
        }
     }
    $result.='</tbody></table>';
     $result.='<table width="100%" border="0"><tbody>';
        $result.='<tr class="row1">';
        $result.='<td width="5%">ID</td>';
        $result.='<td width="15%">'.__('Date').'</td>';
        $result.='<td width="20%">'.__('Worker').'</td>';
        $result.='<td width="20%">'.__('Job type').'</td>';
        $result.='<td width="40%">'.__('Notes').'</td>';
        $result.='<td></td>';
        $result.='</tr>';

        $result.='<tr class="row3">
            <td>ID</td>
            <td>'.date("Y-m-d H:i:s").'</td>
        <form action="" method="POST">
        <input type="hidden" name="addjob" value="true">
        <input type="hidden" name="jobdate" value="'.date("Y-m-d H:i:s").'">
        <td>'.stg_worker_selector().'</td>
        <td>'.stg_jobtype_selector().'</td>
        <td><input type"text" size="20" name="notes">
        <input type="submit" value="'.__('Add').'"></td>
        </form>
        <td>'.  web_add_icon().'</td>
        </tr>
        </tbody></table>
        ';
    show_window(__('Jobs'), $result);
}

function stg_delete_job($jobid) {
    $jobid=vf($jobid);
    $query="DELETE from `jobs` WHERE `id`='".$jobid."'";
    nr_query($query);
    log_register("DELETE JOB ".$jobid);
}


function stg_add_new_job($login,$date,$worker_id,$jobtype_id,$job_notes) {
$job_notes=mysql_real_escape_string(trim($job_notes));
$datetime=curdatetime();
$query="INSERT INTO `jobs` (
       `id` ,
        `date` ,
        `jobid` ,
        `workerid` ,
        `login` ,
        `note` 
        )
        VALUES (
           NULL , '".$datetime."', '".$jobtype_id."', '".$worker_id."', '".$login."', '".$job_notes."'
            );
    ";
nr_query($query);
log_register("ADD JOB ".$worker_id." ".$jobtype_id." ".$login);
}

//
// New Task management API - old is shitty and exists only for backward compatibility
//

function ts_DetectUserByAddress($address) {
    $alladdress= zb_AddressGetFulladdresslist();
    $alladdress=  array_flip($alladdress);
    if (isset($alladdress[$address])) {
        return ($alladdress[$address]);
    } else {
        return(false);
    }
}

   function ts_GetAllEmployee() {
        $query="SELECT * from `employee`";
        $allemployee=  simple_queryall($query);
        $result=array();
        if (!empty($allemployee)) {
            foreach ($allemployee as $io=>$each) {
                $result[$each['id']]=$each['name'];
            }
        }
        return ($result);
    }
    
    function ts_GetAllJobtypes() {
        $query="SELECT * from `jobtypes`";
        $alljt=  simple_queryall($query);
        $result=array();
        if (!empty($alljt)) {
            foreach ($alljt as $io=>$each) {
                $result[$each['id']]=$each['jobname'];
            }
        }
        return ($result);
    }
    
       function ts_GetActiveEmployee () {
        $query="SELECT * from `employee` WHERE `active`='1'";
        $allemployee=  simple_queryall($query);
        $result=array();
        if (!empty($allemployee)) {
            foreach ($allemployee as $io=>$each) {
                $result[$each['id']]=$each['name'];
            }
        }
        return ($result);
    }
    
       function ts_JGetJobsReport() {
       $allemployee=  ts_GetAllEmployee();
       $alljobtypes= ts_GetAllJobtypes();
       $cyear=  curyear();
       
       $query="SELECT * from `jobs` WHERE `date` LIKE '".$cyear."-%' ORDER BY `id` DESC";
       $alljobs=  simple_queryall($query);
       
       $i=1;
       $jobcount=sizeof($alljobs);
       $result='';
       
       if (!empty($alljobs)) {
           foreach ($alljobs as $io=>$eachjob) {
               if ($i!=$jobcount) {
                    $thelast=',';
                } else {
                    $thelast='';
                }
               
               $startdate=strtotime($eachjob['date']);
               $startdate=date("Y, n-1, j",$startdate);
               
               $result.="
                      {
                        title: '".$allemployee[$eachjob['workerid']]." - ".@$alljobtypes[$eachjob['jobid']]."',
                        start: new Date(".$startdate."),
                        end: new Date(".$startdate."),
                        url: '?module=userprofile&username=".$eachjob['login']."'
		      }
                    ".$thelast;
               $i++;
           }
       }
       return ($result);
   } 
   
   
    function ts_JGetUndoneTasks() {
        $allemployee=  ts_GetAllEmployee();
        $alljobtypes= ts_GetAllJobtypes();
        
        $query="SELECT * from `taskman` WHERE `status`='0' ORDER BY `date` ASC";
        $allundone=  simple_queryall($query);
        $result='';
        $i=1;
        $taskcount=sizeof($allundone);
        
        if (!empty($allundone)) {
            foreach ($allundone as $io=>$eachtask) {
                if ($i!=$taskcount) {
                    $thelast=',';
                } else {
                    $thelast='';
                }
                
                $startdate=strtotime($eachtask['startdate']);
                $startdate=date("Y, n-1, j",$startdate);
                if ($eachtask['enddate']!='') {
                    $enddate=strtotime($eachtask['enddate']);
                    $enddate=date("Y, n-1, j",$enddate);
                } else {
                    $enddate=$startdate;
                }
          
                $result.="
                      {
                        title: '".$eachtask['address']." - ".@$alljobtypes[$eachtask['jobtype']]."',
                        start: new Date(".$startdate."),
                        end: new Date(".$enddate."),
                        className : 'undone',
                        url: '?module=taskman&edittask=".$eachtask['id']."'
                        
		      } 
                    ".$thelast;
            }
        }
     
        return ($result);
    }
    
    function ts_JGetDoneTasks() {
        $allemployee=  ts_GetAllEmployee();
        $alljobtypes= ts_GetAllJobtypes();
        
        $query="SELECT * from `taskman` WHERE `status`='1' ORDER BY `date` ASC";
        $allundone=  simple_queryall($query);
        $result='';
        $i=1;
        $taskcount=sizeof($allundone);
        
        if (!empty($allundone)) {
            foreach ($allundone as $io=>$eachtask) {
                if ($i!=$taskcount) {
                    $thelast=',';
                } else {
                    $thelast='';
                }
                
                $startdate=strtotime($eachtask['startdate']);
                $startdate=date("Y, n-1, j",$startdate);
                if ($eachtask['enddate']!='') {
                    $enddate=strtotime($eachtask['enddate']);
                    $enddate=date("Y, n-1, j",$enddate);
                } else {
                    $enddate=$startdate;
                }
          
                $result.="
                      {
                        title: '".$eachtask['address']." - ".@$allemployee[$eachtask['employeedone']]."',
                        start: new Date(".$startdate."),
                        end: new Date(".$enddate."),
                        url: '?module=taskman&edittask=".$eachtask['id']."'
		      }
                    ".$thelast;
            }
        }
     
        return ($result);
    }
    
    function ts_JGetAllTasks() {
        $allemployee=  ts_GetAllEmployee();
        $alljobtypes= ts_GetAllJobtypes();
        
        $query="SELECT * from `taskman`  ORDER BY `id` ASC";
        $allundone=  simple_queryall($query);
        $result='';
        $i=1;
        $taskcount=sizeof($allundone);
        
        if (!empty($allundone)) {
            foreach ($allundone as $io=>$eachtask) {
                if ($i!=$taskcount) {
                    $thelast=',';
                } else {
                    $thelast='';
                }
                
                $startdate=strtotime($eachtask['startdate']);
                $startdate=date("Y, n-1, j",$startdate);
                if ($eachtask['enddate']!='') {
                    $enddate=strtotime($eachtask['enddate']);
                    $enddate=date("Y, n-1, j",$enddate);
                } else {
                    $enddate=$startdate;
                }
                
                if ($eachtask['status']==0) {
                    $coloring="className : 'undone',";
                } else {
                    $coloring='';
                }
          
                $result.="
                      {
                        title: '".$eachtask['address']." - ".@$alljobtypes[$eachtask['jobtype']]."',
                        start: new Date(".$startdate."),
                        end: new Date(".$enddate."),
                        ".$coloring."
                        url: '?module=taskman&edittask=".$eachtask['id']."'
		      }
                    ".$thelast;
            }
        }
     
        return ($result);
    }
    
    
    function ts_TaskCreateForm() {
        $alljobtypes= ts_GetAllJobtypes();
        $allemployee= ts_GetActiveEmployee();
        $inputs='<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
        $inputs.=  wf_HiddenInput('createtask', 'true');
        $inputs.=wf_DatePicker('newstartdate').' <label>'.__('Target date').'<sup>*</sup></label><br><br>';
        $inputs.=wf_TextInput('newtaskaddress', __('Address').'<sup>*</sup>', '', true, '30');
        $inputs.='<br>';
        $inputs.=wf_TextInput('newtaskphone', __('Phone').'<sup>*</sup>', '', true, '30');
        $inputs.='<br>';
        $inputs.=wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
        $inputs.='<br>';
        $inputs.=wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
        $inputs.='<br>';
        $inputs.='<label>'.__('Job note').'</label><br>';
        $inputs.=wf_TextArea('newjobnote', '', '', true, '35x5');
        $inputs.=wf_Submit(__('Create new task'));
        $result=  wf_Form("", 'POST', $inputs, 'glamour');
        $result.=__('All fields marked with an asterisk are mandatory');
        return ($result);
    }
    
    
    function ts_ShowPanel() {
        $createform=  ts_TaskCreateForm();
        $result=  wf_modal(__('Create task'), __('Create task'), $createform, 'ubButton', '420', '500');
        $result.=wf_Link('?module=taskman&show=undone', __('Undone tasks'), false, 'ubButton');
        $result.=wf_Link('?module=taskman&show=done', __('Done tasks'), false, 'ubButton');
        $result.=wf_Link('?module=taskman&show=all', __('List all tasks'), false, 'ubButton');
        return ($result);
    }
    
    
     function ts_CreateTask($startdate,$address,$phone,$jobtypeid,$employeeid,$jobnote) {
        $curdate=curdatetime();
        $admin=  whoami();
        $address=  str_replace('\'', '`', $address);
        $address=  mysql_real_escape_string($address);
        $phone=  mysql_real_escape_string($phone);
        $jobtypeid=vf($jobtypeid,3);
        $employeeid=vf($employeeid,3);
        $jobnote=  mysql_real_escape_string($jobnote);
        
        $query="INSERT INTO `taskman` (
                            `id` ,
                            `date` ,
                            `address` ,
                            `jobtype` ,
                            `jobnote` ,
                            `phone` ,
                            `employee` ,
                            `employeedone` ,
                            `donenote` ,
                            `startdate` ,
                            `enddate` ,
                            `admin` ,
                            `status`
                                       )
                                VALUES (
                                    NULL ,
                                    '".$curdate."',
                                    '".$address."',
                                    '".$jobtypeid."',
                                    '".$jobnote."',
                                    '".$phone."',
                                    '".$employeeid."',
                                    'NULL',
                                    NULL ,
                                    '".$startdate."',
                                    NULL ,
                                    '".$admin."',
                                    '0'
                    );";
        nr_query($query);
        log_register("TASKMAN CREATE ".$address);
    }
    
 function ts_GetTaskData($taskid) {
        $taskid=vf($taskid,3);
        $query="SELECT * from `taskman` WHERE `id`='".$taskid."'";
        $result=  simple_query($query);
        return ($result);
    }   
    
    
      function ts_TaskChangeForm($taskid) {
        $taskid=vf($taskid,3);
        $taskdata=  ts_GetTaskData($taskid);
        $result='';
        $allemployee= ts_GetAllEmployee();
        $activeemployee=  ts_GetActiveEmployee();
        $alljobtypes= ts_GetAllJobtypes();
        
        if (!empty($taskdata)) {
            //not done task
            $login_detected=ts_DetectUserByAddress($taskdata['address']);
            if ($login_detected) {
                $addresslink=wf_Link("?module=userprofile&username=".$login_detected, web_profile_icon().' '.$taskdata['address'], false);
            } else {
                $addresslink=$taskdata['address'];
            }
            
            $tablecells=  wf_TableCell(__('Task creation date').' / '.__('Administrator'),'30%');
            $tablecells.=  wf_TableCell($taskdata['date'].' / '.$taskdata['admin']);
            $tablerows=  wf_TableRow($tablecells,'row3');
            
            $tablecells=  wf_TableCell(__('Target date'));
            $tablecells.=  wf_TableCell('<strong>'.$taskdata['startdate'].'</strong>');
            $tablerows.=  wf_TableRow($tablecells,'row3');
            
            $tablecells=  wf_TableCell(__('Task address'));
            $tablecells.=  wf_TableCell($addresslink);
            $tablerows.=  wf_TableRow($tablecells,'row3');
            
            $tablecells=  wf_TableCell(__('Phone'));
            $tablecells.=  wf_TableCell($taskdata['phone']);
            $tablerows.=  wf_TableRow($tablecells,'row3');
            
            $tablecells=  wf_TableCell(__('Job type'));
            $tablecells.=  wf_TableCell(@$alljobtypes[$taskdata['jobtype']]);
            $tablerows.=  wf_TableRow($tablecells,'row3');
            
            $tablecells=  wf_TableCell(__('Who should do'));
            $tablecells.=  wf_TableCell(@$allemployee[$taskdata['employee']]);
            $tablerows.=  wf_TableRow($tablecells,'row3');
            
            $tablecells=  wf_TableCell(__('Job note'));
            $tablecells.=  wf_TableCell($taskdata['jobnote']);
            $tablerows.=  wf_TableRow($tablecells,'row3');
            
            $result.=wf_TableBody($tablerows, '100%', '0', 'glamour');
            // show task preview
            show_window(__('View task'),$result);
            
            //if task undone
            if ($taskdata['status']==0) {
            
            $inputs=  wf_HiddenInput('changetask', $taskid);
            $inputs.=wf_DatePicker('editenddate').' <label>'.__('Finish date').'<sup>*</sup></label> <br>';
            $inputs.='<br>';
            $inputs.=wf_Selector('editemployeedone', $activeemployee, __('Worker done'), $taskdata['employee'], true);
            $inputs.='<br>';
            $inputs.='<label>'.__('Finish note').'</label> <br>';
            $inputs.=wf_TextArea('editdonenote', '', '', true, '35x3');
            $inputs.='<br>';
            $inputs.=wf_Submit(__('This task is done'));
            
            $form=  wf_Form("", 'POST', $inputs, 'glamour');
                
            //show editing form
            show_window(__('If task is done'),$form);
            
            } else {
                $donecells=  wf_TableCell(__('Finish date'),'30%');
                $donecells.=wf_TableCell($taskdata['enddate']);
                $donerows=  wf_TableRow($donecells,'row3');
                
                $donecells=  wf_TableCell(__('Worker done'));
                $donecells.=wf_TableCell($allemployee[$taskdata['employeedone']]);
                $donerows.=wf_TableRow($donecells,'row3');
                
                $donecells=  wf_TableCell(__('Finish note'));
                $donecells.=wf_TableCell($taskdata['donenote']);
                $donerows.=wf_TableRow($donecells,'row3');
                
               $doneresult= wf_TableBody($donerows,'100%','0','glamour');
               $doneresult.=wf_JSAlert('?module=taskman&deletetask='.$taskid, web_delete_icon(__('Remove this task - it is an mistake')),__('Removing this may lead to irreparable results'));
               $doneresult.=wf_JSAlert('?module=taskman&setundone='.$taskid,  wf_img('skins/icon_key.gif',__('No work was done')),__('Are you serious'));
               
               show_window(__('Task is done'),$doneresult);
            }
        }
        
    }
    
    function ts_DeleteTask($taskid) {
      $taskid=vf($taskid,3);
      $query="DELETE from `taskman` WHERE `id`='".$taskid."'";
      nr_query($query);
      log_register("TASKMAN DELETE ".$taskid);
      
  }

?>
