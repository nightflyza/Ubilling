<?php
if(cfr('TASKMAN')) {

    function taskman_panel() {
        $panel='
          | <a href="?module=taskman&action=create">'.__('Create task').'</a> |
            <a href="?module=taskman&action=list">'.__('List all tasks').'</a> |
            <a href="?module=taskman&action=listtoday">'.__('Today tasks').'</a> |
            <a href="?module=taskman&action=listtomorrow">'.__('Tomorrow tasks').'</a> |
            <a href="?module=taskman&action=listtodaydone">'.__('Today done').'</a> |
            <a href="?module=taskman&action=listexpired">'.__('Expired tasks').'</a> |
           ';
        return($panel);
    }

    function taskman_createtask_form() {
        global $system;
        $form='
            <table border="0" width="100%">
            <form method="POST" action="">
            <tr class="row3">
             <td width="20%">
                '.__('Current date').'
             </td>
             <td>
             <input type="text" name="date" value="'.date("Y-m-d H:i:s").'">
            </td>
            </tr>

            <tr class="row3">
            <td>
             '.__('Task address').'
             </td>
             <td>
             <input type="text" name="address" value="">
             </td>
            </tr>

            <tr class="row3">
            <td>
            '.__('Job type').'
             </td>
             <td>
             '.  stg_jobtype_selector().'
             </td>
            </tr>

            <tr  class="row3">
            <td>
            '.__('Job note').' 
             </td>
             <td>
             <input type="text" name="jobnote" value="">
             </td>
            </tr>

            <tr  class="row3">
            <td>
            '.__('Phone').'
             </td>
             <td>
             <input type="text" name="phone" value="">
             </td>
            </tr>

            <tr class="row3">
            <td>
            '.__('Worker').'
             </td>
             <td>
             '.  stg_worker_selector().'
             </td>
            </tr>

             <tr class="row3">
            <td>
             '.__('Target date').'
              </td>
             <td>
             '.  web_CalendarControl('startdate').'
               </td>
            </tr>

            <tr  class="row3">
            <td>
            </td>
            <td>
             <input  type="hidden" name="admin" value="'.whoami().'">
             <input  type="hidden" name="createnewtask" value="true">
             <input  type="submit" value="'.__('Save').'">
            </td>
            </tr>
            </form>
            </table>
            ';
        return($form);
    }

    function taskman_get_task_data($task_id) {
        $task_id=vf($task_id);
        $query="SELECT * from `taskman` WHERE `id`='".$task_id."'";
        $task_data=simple_query($query);
        return($task_data);
    }

    function taskman_edittask_form($task_id) {
        $task_data=taskman_get_task_data($task_id);
        $form='
            <table border="0" width="100%">
            <form method="POST" action="">
            <tr class="row3">
             <td width="20%">
                '.__('Create date').'
             </td>
             <td>
            '.$task_data['date'].'
            </td>
            </tr>

            <tr class="row3">
            <td>
             '.__('Task address').'
             </td>
             <td>
              '.$task_data['address'].'
             </td>
            </tr>

            <tr class="row3">
            <td>
            '.__('Job type').'
             </td>
             <td>
             '.stg_get_jobtype_name($task_data['jobtype']).'
             </td>
            </tr>

            <tr  class="row3">
            <td>
            '.__('Job note').'
             </td>
             <td>
           '.$task_data['jobnote'].'
             </td>
            </tr>

            <tr  class="row3">
            <td>
            '.__('Phone').'
             </td>
             <td>
            '.$task_data['phone'].'
             </td>
            </tr>

            <tr class="row3">
            <td>
            '.__('Worker').'
             </td>
             <td>
             '. stg_get_employee_name($task_data['employee']).'
             </td>
            </tr>

            <tr class="row3">
            <td>
             '.__('Target date').'
              </td>
             <td>
            '.$task_data['startdate'].'
               </td>
            </tr>

            <tr class="row3">
            <td>
             '.__('Administrator').'
              </td>
             <td>
            '.$task_data['admin'].'
             </td>
            </tr>

            <tr class="row3">
            <td>
             '.__('Worker done').'
              </td>
             <td>
           '.stg_get_employee_name($task_data['employeedone']).'  '.stg_worker_selector().'
             </td>
            </tr>

            <tr class="row3">
            <td>
             '.__('Finish date').'
            </td>
            <td>
            '.$task_data['enddate'].' '.  web_CalendarControl('enddate').' *'.__('After setting up finish date task will be marked as done').'
            </td>
            </tr>

             <tr class="row3">
            <td>
             '.__('Finish note').'
            </td>
            <td>
            <input type="text" name="donenote" value="'.$task_data['donenote'].'" size="50">
            </td>
            </tr>

            <tr  class="row3">
            <td>
            </td>
            <td>
            <input  type="hidden" name="modifytask" value="'.$task_data['id'].'">
            <input  type="submit" value="'.__('Save').'">
            </td>
            </tr>

            </form>
            </table>
             <a href="?module=taskman&deletetask='.$task_data['id'].'">'.__('Delete').'</a>
            ';
        return($form);
    }

    function taskman_create_task() {
        $date=mysql_real_escape_string(($_POST['date']));
        $address=mysql_real_escape_string($_POST['address']);
        $jobtype=$_POST['jobtype'];
        $jobnote=mysql_real_escape_string($_POST['jobnote']);
        $phone=mysql_real_escape_string($_POST['phone']);
        $employee=$_POST['worker'];
        $startdate=mysql_real_escape_string($_POST['startdate']);
        $admin=$_POST['admin'];
        $query="
            INSERT INTO `taskman` (
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
            `admin`
            )
            VALUES (
            '', '".$date."', '".$address."', '".$jobtype."', '".$jobnote."', '".$phone."', '".$employee."', '', NULL , '".$startdate."', NULL , '".$admin."'
            );
            ";
        nr_query($query);
        stg_putlogevent("CREATETASK ".$address);
    }

    function taskman_modify_task($task_id) {
        $employeedone=$_POST['worker'];
        $enddate=$_POST['enddate'];
        $donenote=mysql_real_escape_string($_POST['donenote']);
        $query="UPDATE `taskman`
            SET
            `employeedone` = '".$employeedone."',
            `donenote` = '".$donenote."',
            `enddate` = '".$enddate."'
             WHERE `id` = ".$task_id."
            ";
        nr_query($query);
        stg_putlogevent("EDITTASK ".$task_id);
    }

    function taskman_delete_task($task_id) {
        $query="DELETE from `taskman` WHERE `id`=".$task_id;
        nr_query($query);
        stg_putlogevent("DELETETASK ".$task_id);
    }


function taskman_list_alltasks() {

  $query="SELECT * from `taskman` ORDER BY `date` DESC ";
  $alltasks=simple_queryall($query);
  $result='<table width="100%" class="sortable" border="0">';
   $result.='<tr class="row1">
            <td>ID</td>
            <td>'.__('Create date').'</td>
            <td>'.__('Task address').'</td>
            <td>'.__('Job type').'</td>
            <td>'.__('Phone').'</td>
            <td>'.__('Worker').'</td>
            <td>'.__('Worker done').'</td>
            <td>'.__('Target date').'</td>
            <td>'.__('Finish date').'</td>
            <td>'.__('Actions').'</td>
            </tr>';
  if (!empty ($alltasks)) {
      foreach ($alltasks as $io=>$eachtask) {
        $result.='<tr class="row3">
            <td>'.$eachtask['id'].'</td>
            <td>'.$eachtask['date'].'</td>
            <td>'.$eachtask['address'].'</td>
            <td>'.stg_get_jobtype_name($eachtask['jobtype']).'</td>
            <td>'.$eachtask['phone'].'</td>
            <td>'.stg_get_employee_name($eachtask['employee']).'</td>
            <td>'.stg_get_employee_name($eachtask['employeedone']).'</td>
            <td>'.$eachtask['startdate'].'</td>
            <td>'.$eachtask['enddate'].'</td>
            <td>
            <a href="?module=taskman&modify='.$eachtask['id'].'">'.__('Edit').'</a>
            
            </td>
            </tr>';
      }

  }
  $result.='</table>';
  return($result);
}

function taskman_list_todaytasks() {
  $curdate=date("Y-m-d");
  $query="SELECT * from `taskman` where `startdate` LIKE '%".$curdate."%' AND `enddate` IS NULL ";
  $alltasks=simple_queryall($query);
  $result='<table width="100%" class="sortable" border="0">';
   $result.='<tr class="row1">
            <td>ID</td>
            <td>'.__('Create date').'</td>
            <td>'.__('Task address').'</td>
            <td>'.__('Job type').'</td>
            <td>'.__('Phone').'</td>
            <td>'.__('Worker').'</td>
            <td>'.__('Worker done').'</td>
            <td>'.__('Target date').'</td>
            <td>'.__('Finish date').'</td>
            <td>'.__('Actions').'</td>
            </tr>';
  if (!empty ($alltasks)) {
      foreach ($alltasks as $io=>$eachtask) {
        $result.='<tr class="row3">
            <td>'.$eachtask['id'].'</td>
            <td>'.$eachtask['date'].'</td>
            <td>'.$eachtask['address'].'</td>
            <td>'.stg_get_jobtype_name($eachtask['jobtype']).'</td>
            <td>'.$eachtask['phone'].'</td>
            <td>'.stg_get_employee_name($eachtask['employee']).'</td>
            <td>'.stg_get_employee_name($eachtask['employeedone']).'</td>
            <td>'.$eachtask['startdate'].'</td>
            <td>'.$eachtask['enddate'].'</td>
            <td>
            <a href="?module=taskman&modify='.$eachtask['id'].'">'.__('Edit').'</a>

            </td>
            </tr>';
      }

  }
  $result.='</table>';
  return($result);
}

function taskman_list_tomorrowtasks() {
  $tomorrow = mktime(0, 0, 0, date("m"), date("d")+1, date("y"));
  $tomdate=date("Y-m-d",$tomorrow);
  $query="SELECT * from `taskman` where `startdate` LIKE '%".$tomdate."%' AND `enddate` IS NULL;";
  $alltasks=simple_queryall($query);
  $result='<table width="100%" class="sortable" border="0">';
   $result.='<tr class="row1">
            <td>ID</td>
            <td>'.__('Create date').'</td>
            <td>'.__('Task address').'</td>
            <td>'.__('Job type').'</td>
            <td>'.__('Phone').'</td>
            <td>'.__('Worker').'</td>
            <td>'.__('Worker done').'</td>
            <td>'.__('Target date').'</td>
            <td>'.__('Finish date').'</td>
            <td>'.__('Actions').'</td>
            </tr>';
  if (!empty ($alltasks)) {
      foreach ($alltasks as $io=>$eachtask) {
        $result.='<tr class="row3">
            <td>'.$eachtask['id'].'</td>
            <td>'.$eachtask['date'].'</td>
            <td>'.$eachtask['address'].'</td>
            <td>'.stg_get_jobtype_name($eachtask['jobtype']).'</td>
            <td>'.$eachtask['phone'].'</td>
            <td>'.stg_get_employee_name($eachtask['employee']).'</td>
            <td>'.stg_get_employee_name($eachtask['employeedone']).'</td>
            <td>'.$eachtask['startdate'].'</td>
            <td>'.$eachtask['enddate'].'</td>
            <td>
            <a href="?module=taskman&modify='.$eachtask['id'].'">'.__('Edit').'</a>

            </td>
            </tr>';
      }

  }
  $result.='</table>';
  return($result);
}

function taskman_list_todaydone() {
  $curdate=date("Y-m-d");
  $query="SELECT * from `taskman` where `enddate` LIKE '%".$curdate."%' AND `enddate` IS NOT NULL;";
  $alltasks=simple_queryall($query);
  $result='<table width="100%" class="sortable" border="0">';
   $result.='<tr class="row1">
            <td>ID</td>
            <td>'.__('Create date').'</td>
            <td>'.__('Task address').'</td>
            <td>'.__('Job type').'</td>
            <td>'.__('Phone').'</td>
            <td>'.__('Worker').'</td>
            <td>'.__('Worker done').'</td>
            <td>'.__('Target date').'</td>
            <td>'.__('Finish date').'</td>
            <td>'.__('Actions').'</td>
            </tr>';
  if (!empty ($alltasks)) {
      foreach ($alltasks as $io=>$eachtask) {
        $result.='<tr class="row3">
            <td>'.$eachtask['id'].'</td>
            <td>'.$eachtask['date'].'</td>
            <td>'.$eachtask['address'].'</td>
            <td>'.stg_get_jobtype_name($eachtask['jobtype']).'</td>
            <td>'.$eachtask['phone'].'</td>
            <td>'.stg_get_employee_name($eachtask['employee']).'</td>
            <td>'.stg_get_employee_name($eachtask['employeedone']).'</td>
            <td>'.$eachtask['startdate'].'</td>
            <td>'.$eachtask['enddate'].'</td>
            <td>
            <a href="?module=taskman&modify='.$eachtask['id'].'">'.__('Edit').'</a>

            </td>
            </tr>';
      }

  }
  $result.='</table>';
  return($result);
}


function taskman_list_expired() {
  $curdate=date("Y-m-d");
  $query="SELECT * from `taskman` where `startdate` < '".$curdate."' AND `enddate` IS NULL;";
  $alltasks=simple_queryall($query);
  $result='<table width="100%" class="sortable" border="0">';
   $result.='<tr class="row1">
            <td>ID</td>
            <td>'.__('Create date').'</td>
            <td>'.__('Task address').'</td>
            <td>'.__('Job type').'</td>
            <td>'.__('Phone').'</td>
            <td>'.__('Worker').'</td>
            <td>'.__('Worker done').'</td>
            <td>'.__('Target date').'</td>
            <td>'.__('Finish date').'</td>
            <td>'.__('Actions').'</td>
            </tr>';
  if (!empty ($alltasks)) {
      foreach ($alltasks as $io=>$eachtask) {
        $result.='<tr class="row3">
            <td>'.$eachtask['id'].'</td>
            <td>'.$eachtask['date'].'</td>
            <td>'.$eachtask['address'].'</td>
            <td>'.stg_get_jobtype_name($eachtask['jobtype']).'</td>
            <td>'.$eachtask['phone'].'</td>
            <td>'.stg_get_employee_name($eachtask['employee']).'</td>
            <td>'.stg_get_employee_name($eachtask['employeedone']).'</td>
            <td>'.$eachtask['startdate'].'</td>
            <td>'.$eachtask['enddate'].'</td>
            <td>
            <a href="?module=taskman&modify='.$eachtask['id'].'">'.__('Edit').'</a>

            </td>
            </tr>';
      }

  }
  $result.='</table>';
  return($result);
}


///////// main code


if (isset($_POST['createnewtask'])) {
    taskman_create_task();
    rcms_redirect("?module=taskman&action=list");
}

if (isset($_POST['modifytask'])) {
    taskman_modify_task($_POST['modifytask']);
}

if (isset($_GET['deletetask'])) {
    taskman_delete_task($_GET['deletetask']);
    rcms_redirect("?module=taskman&action=list");
}

show_window(__('Manage tasks'),taskman_panel());


if (isset($_GET['action'])) {
if ($_GET['action']=='create') {
show_window(__('Create task'),taskman_createtask_form());
}
if ($_GET['action']=='list') {
show_window(__('Task list'),taskman_list_alltasks());
}
if ($_GET['action']=='listtoday') {
show_window(__('Today tasks'),  taskman_list_todaytasks());
}
if ($_GET['action']=='listtomorrow') {
show_window(__('Tomorrow tasks'), taskman_list_tomorrowtasks());
}
if ($_GET['action']=='listtodaydone') {
show_window(__('Today done'), taskman_list_todaydone());
}
if ($_GET['action']=='listexpired') {
show_window(__('Expired tasks'), taskman_list_expired());
}
}

if (isset($_GET['modify'])) {
    show_window(__('Edit task'),taskman_edittask_form($_GET['modify']));
}

}
else {
	show_error(__('Access denied'));
}
?>