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
                 <a href="?module=employee&edit='.$eachemployee['id'].'">'.  web_edit_icon().'</a>
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
         $result.='<td>'.  wf_JSAlert('?module=employee&deletejob='.$eachjob['id'], web_delete_icon(), 'Removing this may lead to irreparable results').'</td>';
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


?>
