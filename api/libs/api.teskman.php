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
          
          <td><input type="text"  name="employeename" size="30" required></td>
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
          <td><input type="text"  name="newjobtype" size="30" required></td>
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
     stg_putlogevent('EMPLOYEE DEL ['.$id.']');
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
     stg_putlogevent('JOBTYPEDEL ['.$id.']');
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
    $query="SELECT * from `jobtypes` ORDER by `id` ASC";
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
    $query_jobs='SELECT * FROM `jobs` WHERE `login`="'.$username.'" ORDER BY `id` ASC';
    $alljobs=simple_queryall($query_jobs);
    $allemployee=  ts_GetAllEmployee();
    $alljobtypes= ts_GetAllJobtypes();
    $activeemployee=  ts_GetActiveEmployee();
    
    $cells= wf_TableCell(__('ID'));
    $cells.=wf_tableCell(__('Date'));
    $cells.=wf_TableCell(__('Worker'));
    $cells.=wf_TableCell(__('Job type'));
    $cells.=wf_TableCell(__('Notes'));
    $cells.=wf_TableCell('');
    $rows=  wf_TableRow($cells, 'row1');
    
    if (!empty ($alljobs)) {
        foreach ($alljobs as $ion=>$eachjob) {
            //backlink to taskman if some TASKID inside
            if (ispos($eachjob['note'], 'TASKID:[')) {
                $taskid=vf($eachjob['note'],3);
                $jobnote=  wf_Link("?module=taskman&&edittask=".$taskid, __('Task is done').' #'.$taskid, false, '');
                
            } else {
                $jobnote=$eachjob['note'];
            }
            
            $cells= wf_TableCell($eachjob['id']);
            $cells.=wf_tableCell($eachjob['date']);
            $cells.=wf_TableCell(@$allemployee[$eachjob['workerid']]);
            $cells.=wf_TableCell(@$alljobtypes[$eachjob['jobid']]);
            $cells.=wf_TableCell($jobnote);
            $cells.=wf_TableCell(wf_JSAlert('?module=jobs&username='.$username.'&deletejob='.$eachjob['id'].'', web_delete_icon(), 'Are you serious'));
            $rows.=  wf_TableRow($cells, 'row3');
            
        }
     }
    
    //onstruct job create form
    $curdatetime=curdatetime();
    $inputs= wf_HiddenInput('addjob', 'true') ;
    $inputs.=wf_HiddenInput('jobdate', $curdatetime) ;
    $inputs.=wf_TableCell('');
    $inputs.=wf_tableCell($curdatetime);
    $inputs.=wf_TableCell(stg_worker_selector());
    $inputs.=wf_TableCell(stg_jobtype_selector());
    $inputs.=wf_TableCell(wf_TextInput('notes', '', '', false, '20'));
    $inputs.=wf_TableCell(wf_Submit('Create'));
    $inputs=wf_TableRow($inputs, 'row2');
  
    $addform=  wf_Form("", 'POST', $inputs, '');
           
        if ((!empty($activeemployee)) AND (!empty($alljobtypes))) {
            $rows.=$addform;
        } else {
            show_window(__('Error'),__('No job types and employee available'));
        }
        
        $result=  wf_TableBody($rows, '100%', '0', '');
        
    show_window(__('Jobs'), $result);
}

function stg_delete_job($jobid) {
    $jobid=vf($jobid);
    $query="DELETE from `jobs` WHERE `id`='".$jobid."'";
    nr_query($query);
    log_register("DELETE JOB [".$jobid."]");
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
log_register("ADD JOB W:[".$worker_id."] J:[".$jobtype_id."] (".$login.")");
}

//
// New Task management API - old is shitty and exists only for backward compatibility
//

function ts_DetectUserByAddress($address) {
    $address= strtolower_utf8($address);
    $usersAddress= zb_AddressGetFulladdresslist();
    $alladdress=array();
    if (!empty($usersAddress)) {
        foreach ($usersAddress as $login=>$eachaddress) {
            $alladdress[$login]=  strtolower_utf8($eachaddress);
        }
    }
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
        $curyear=curyear();
        $curmonth=date("m");
        if ($curmonth!=1) {
            $query="SELECT * from `taskman` WHERE `status`='0' AND `startdate` LIKE '".$curyear."-%' ORDER BY `date` ASC";
        } else {
            $query="SELECT * from `taskman` WHERE `status`='0' ORDER BY `date` ASC";
        }
        
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
        
        $curyear=curyear();
        $curmonth=date("m");
        if ($curmonth!=1) {
            $query="SELECT * from `taskman` WHERE `status`='1' AND `startdate` LIKE '".$curyear."-%' ORDER BY `date` ASC";
        } else {
            $query="SELECT * from `taskman` WHERE `status`='1' ORDER BY `date` ASC";
        }
        
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
        
        $curyear=curyear();
        $curmonth=date("m");
        
        if ($curmonth!=1) {
            $query="SELECT * from `taskman` WHERE `startdate` LIKE '".$curyear."-%' ORDER BY `date` ASC";
        } else {
            $query="SELECT * from `taskman` ORDER BY `date` ASC";
        }
        
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
    
    function ts_TaskTypicalNotesSelector($settings=true) {
    
        $rawNotes=  zb_StorageGet('PROBLEMS');
        if ($settings) {
            $settingsControl=  wf_Link("?module=taskman&probsettings=true", wf_img('skins/settings.png',__('Settings')), false, '');
        } else {
            $settingsControl='';
        }
        if (!empty($rawNotes)) {
            $rawNotes=  base64_decode($rawNotes);
            $rawNotes=  unserialize($rawNotes);
        } else {
          $emptyArray=array();
          $newNotes= serialize($emptyArray);
          $newNotes= base64_encode($newNotes);
          zb_StorageSet('PROBLEMS', $newNotes);
          $rawNotes=$emptyArray;
        }
        
        $typycalNotes=array(''=>'-');
        
        if (!empty($rawNotes)) {
            foreach ($rawNotes as $eachnote) {
                if (mb_strlen($eachnote,'utf-8')>20) { 
                    $shortNote=mb_substr($eachnote, 0, 20,'utf-8').'...';
                } else {
                    $shortNote=$eachnote;
                }
                $typycalNotes[$eachnote]=$shortNote;
            }
        }
        
        $selector=  wf_Selector('typicalnote', $typycalNotes, __('Problem').' '.$settingsControl, '', true);
        return ($selector);
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
        $inputs.=ts_TaskTypicalNotesSelector();
        $inputs.='<label>'.__('Job note').'</label><br>';
        $inputs.=wf_TextArea('newjobnote', '', '', true, '35x5');
        $inputs.=wf_Submit(__('Create new task'));
        $result=  wf_Form("", 'POST', $inputs, 'glamour');
        $result.=__('All fields marked with an asterisk are mandatory');
        return ($result);
    }
    
    function ts_TaskCreateFormProfile($address,$mobile,$phone) {
        $alljobtypes= ts_GetAllJobtypes();
        $allemployee= ts_GetActiveEmployee();
        $inputs='<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
        $inputs.=wf_HiddenInput('createtask', 'true');
        $inputs.=wf_DatePicker('newstartdate').' <label>'.__('Target date').'<sup>*</sup></label><br><br>';
        $inputs.=wf_TextInput('newtaskaddress', __('Address').'<sup>*</sup>', $address, true, '30');
        $inputs.='<br>';
        $inputs.=wf_TextInput('newtaskphone', __('Phone').'<sup>*</sup>', $mobile.' '.$phone, true, '30');
        $inputs.='<br>';
        $inputs.=wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
        $inputs.='<br>';
        $inputs.=wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
        $inputs.='<br>';
        $inputs.='<label>'.__('Job note').'</label><br>';
        $inputs.=ts_TaskTypicalNotesSelector();
        $inputs.=wf_TextArea('newjobnote', '', '', true, '35x5');
        $inputs.=wf_Submit(__('Create new task'));
        $result=  wf_Form("?module=taskman&gotolastid=true", 'POST', $inputs, 'glamour');
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
    
    
    function ts_TaskModifyForm($taskid) {
        $taskid=vf($taskid,3);
        $taskdata=  ts_GetTaskData($taskid);
        $result='';
        $allemployee= ts_GetAllEmployee();
        $activeemployee=  ts_GetActiveEmployee();
        $alljobtypes= ts_GetAllJobtypes();
        if (!empty($taskdata)) {
        $inputs=wf_HiddenInput('modifytask', $taskid);
        $inputs.=wf_TextInput('modifystartdate', __('Target date').'<sup>*</sup>', $taskdata['startdate'], false);
        $inputs.='<br>';
        $inputs.=wf_TextInput('modifytaskaddress', __('Address').'<sup>*</sup>', $taskdata['address'], true, '30');
        $inputs.='<br>';
        $inputs.=wf_TextInput('modifytaskphone', __('Phone').'<sup>*</sup>', $taskdata['phone'], true, '30');
        $inputs.='<br>';
        $inputs.=wf_Selector('modifytaskjobtype', $alljobtypes, __('Job type'), $taskdata['jobtype'], true);
        $inputs.='<br>';
        $inputs.=wf_Selector('modifytaskemployee', $activeemployee, __('Who should do'), $taskdata['employee'], true);
        $inputs.='<br>';
        $inputs.='<label>'.__('Job note').'</label><br>';
        $inputs.=wf_TextArea('modifytaskjobnote', '', $taskdata['jobnote'], true, '35x5');
        $inputs.=wf_Submit(__('Save'));
        $result=  wf_Form("", 'POST', $inputs, 'glamour');
        $result.=__('All fields marked with an asterisk are mandatory');
            
        }
        
        
        return ($result);
    }
    
    
        function ts_ModifyTask($taskid,$startdate,$address,$phone,$jobtypeid,$employeeid,$jobnote) {
        $taskid=vf($taskid,3);
        $startdate=  mysql_real_escape_string($startdate);
        $address=  str_replace('\'', '`', $address);
        $address=  mysql_real_escape_string($address);
        $phone=  mysql_real_escape_string($phone);
        $jobtypeid=vf($jobtypeid,3);
        $employeeid=vf($employeeid,3);
        
        simple_update_field('taskman', 'startdate', $startdate, "WHERE `id`='".$taskid."'");
        simple_update_field('taskman', 'address', $address, "WHERE `id`='".$taskid."'");
        simple_update_field('taskman', 'phone', $phone, "WHERE `id`='".$taskid."'");
        simple_update_field('taskman', 'jobtype', $jobtypeid, "WHERE `id`='".$taskid."'");
        simple_update_field('taskman', 'employee', $employeeid, "WHERE `id`='".$taskid."'");
        simple_update_field('taskman', 'jobnote', $jobnote, "WHERE `id`='".$taskid."'");
        log_register("TASKMAN MODIFY ".$address);
        
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
            
            //job generation form
            if ($login_detected) {
                $jobgencheckbox=  wf_CheckInput('generatejob', __('Generate job performed for this task'), true, true);
                $jobgencheckbox.= wf_HiddenInput('generatelogin', $login_detected);
                $jobgencheckbox.= wf_HiddenInput('generatejobid', $taskdata['jobtype']);
                $jobgencheckbox.= wf_delimiter();
                
            } else {
                $jobgencheckbox='';
            }
            
            //modify form handlers
            $modform=  wf_modal(web_edit_icon(), __('Edit'), ts_TaskModifyForm($taskid), '', '420', '500');
            //modform end
            
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
            $tablecells.=  wf_TableCell(nl2br($taskdata['jobnote']));
            $tablerows.=  wf_TableRow($tablecells,'row3');
            
            $result.=wf_TableBody($tablerows, '100%', '0', 'glamour');
            // show task preview
            show_window(__('View task').' '.$modform,$result);
            
            //if task undone
            if ($taskdata['status']==0) {
            
            $inputs=  wf_HiddenInput('changetask', $taskid);
            $inputs.=wf_DatePicker('editenddate').' <label>'.__('Finish date').'<sup>*</sup></label> <br>';
            $inputs.='<br>';
            $inputs.=wf_Selector('editemployeedone', $activeemployee, __('Worker done'), $taskdata['employee'], true);
            $inputs.=wf_tag('br');
            $inputs.='<label>'.__('Finish note').'</label> <br>';
            $inputs.=wf_TextArea('editdonenote', '', '', true, '35x3');
            $inputs.=wf_tag('br');
            $inputs.= $jobgencheckbox;
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
               $doneresult.='&nbsp;';
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
  
  function ts_TaskProblemsEditForm() {
        $rawNotes=  zb_StorageGet('PROBLEMS');
        
        //extract old or create new typical problems array
        if (!empty($rawNotes)) {
            $rawNotes=  base64_decode($rawNotes);
            $rawNotes=  unserialize($rawNotes);
        } else {
          $emptyArray=array();
          $newNotes= serialize($emptyArray);
          $newNotes= base64_encode($newNotes);
          zb_StorageSet('PROBLEMS', $newNotes);
          $rawNotes=$emptyArray;
        }
        
        //adding and deletion subroutines
        if (wf_CheckPost(array('createtypicalnote'))) {
            $toPush=strip_tags($_POST['createtypicalnote']);
            array_push($rawNotes, $toPush);
            $newNotes= serialize($rawNotes);
            $newNotes= base64_encode($newNotes);
            zb_StorageSet('PROBLEMS', $newNotes);
            log_register('TASKMAN ADD TYPICALPROBLEM');
            rcms_redirect("?module=taskman&probsettings=true");
        }
        
        if (wf_CheckPost(array('deletetypicalnote','typicalnote'))) {
            $toUnset=$_POST['typicalnote'];
            if (($delkey = array_search($toUnset, $rawNotes)) !== false) {
                unset($rawNotes[$delkey]);
            }
  
            $newNotes= serialize($rawNotes);
            $newNotes= base64_encode($newNotes);
            zb_StorageSet('PROBLEMS', $newNotes);
            log_register('TASKMAN DELETE TYPICALPROBLEM');
            rcms_redirect("?module=taskman&probsettings=true");
            
        }
        
    
        $rows='';
        $result=  wf_Link("?module=taskman", __('Back'), true, 'ubButton');
        
        if (!empty($rawNotes)) {
            foreach ($rawNotes as $eachNote) {
                $cells=  wf_TableCell($eachNote);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }
        
        $result.=  wf_TableBody($rows, '100%', '0', '');
        $result.=  wf_delimiter();
        
        $addinputs=  wf_TextInput('createtypicalnote', __('Create'), '', true, '20');
        $addinputs.= wf_Submit(__('Save'));
        $addform=  wf_Form("", "POST", $addinputs, 'glamour');
        $result.= $addform;
        
        $delinputs=  ts_TaskTypicalNotesSelector(false);
        $delinputs.= wf_HiddenInput('deletetypicalnote','true');
        $delinputs.= wf_Submit(__('Delete'));
        $delform= wf_Form("", "POST", $delinputs, 'glamour');
        $result.= $delform;
        
        return ($result);
    
  }

?>
