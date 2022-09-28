<?php

/**
 * Returns tag id selector
 * 
 * @param string $name
 * @param string $label
 * @return string
 */
function em_TagSelector($name, $label = '', $selected = '', $br = false) {
    $alltypes = stg_get_alltagnames();
    $allltags = array('NULL' => '-');
    if (!empty($alltypes)) {
        foreach ($alltypes as $io => $eachtype) {
            $allltags[$io] = $eachtype . " (" . $io . ")";
        }
    }

    $result = wf_Selector($name, $allltags, $label, $selected, $br);
    return ($result);
}

/**
 * Renders employee list with required controls and creation form
 * 
 * @return void
 */
function em_EmployeeRenderList() {
    $result = '';
    $allEmployee = ts_GetAllEmployeeData();
    $allTagNames = stg_get_alltagnames();
    $messages = new UbillingMessageHelper();

    if (!empty($allEmployee)) {
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Real Name'));
        $cells .= wf_TableCell(__('Active'));
        $cells .= wf_TableCell(__('Appointment'));
        $cells .= wf_TableCell(__('Mobile'));
        $cells .= wf_TableCell(__('Chat ID') . ' ' . __('Telegram'));
        $cells .= wf_TableCell(__('Administrator'));
        $cells .= wf_TableCell(__('Tag'));
        $cells .= wf_TableCell(__('Monthly top up limit'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($allEmployee as $ion => $eachemployee) {
            $cells = wf_TableCell($eachemployee['id']);
            $cells .= wf_TableCell($eachemployee['name']);
            $cells .= wf_TableCell(web_bool_led($eachemployee['active']), '', '', 'sorttable_customkey="' . $eachemployee['active'] . '"');
            $cells .= wf_TableCell($eachemployee['appointment']);
            $cells .= wf_TableCell($eachemployee['mobile']);
            $cells .= wf_TableCell($eachemployee['telegram']);
            $admlogin = $eachemployee['admlogin'];
            if (!empty($admlogin)) {
                if (file_exists(USERS_PATH . $admlogin)) {
                    $admlogin = wf_Link('?module=permissions&edit=' . $admlogin, web_profile_icon() . ' ' . $admlogin, false);
                }
            }
            $cells .= wf_TableCell($admlogin);
            $employeeTagId = $eachemployee['tagid'];
            $employeeTagName = (!empty($employeeTagId)) ? $allTagNames[$employeeTagId] : '';
            $employeeTagLabel = (!empty($employeeTagName)) ? $employeeTagName . ' (' . $employeeTagId . ')' : '';
            $cells .= wf_TableCell($employeeTagLabel);
            $cells .= wf_TableCell($eachemployee['amountLimit']);
            $urlDelete = '?module=employee&deleteemployee=' . $eachemployee['id'];
            $urlEdit = '?module=employee&editemployee=' . $eachemployee['id'];
            $urlCancel = '?module=employee';

            $actions = wf_ConfirmDialog($urlDelete, web_delete_icon(), $messages->getDeleteAlert(), '', $urlCancel, __('Delete') . '?');
            $actions .= wf_Link($urlEdit, web_edit_icon());
            $cells .= wf_TableCell($actions);
            $rows .= wf_TableRow($cells, 'row5');
        }
//existing employee list
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
    } else {
        $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
    }


    $creationLink = wf_modalAuto(wf_img_sized('skins/add_icon.png', __('Create new employee')), __('Create new employee'), em_EmployeeCreateForm());
    show_window(__('Employee') . ' ' . $creationLink, $result);
}

/**
 * Renders employee creation form
 * 
 * @return string
 */
function em_EmployeeCreateForm() {
    $result = '';
    $inputs = wf_HiddenInput('addemployee', 'true');
    $inputs .= wf_TextInput('employeename', 'Employee real name', '', true, 25);
    $inputs .= wf_TextInput('employeejob', 'Appointment', '', true, 15);
    $inputs .= wf_TextInput('employeemobile', 'Mobile', '', true, 15, 'mobile');
    $inputs .= wf_TextInput('employeetelegram', 'Chat ID', '', true, 15, 'digits');
    $inputs .= wf_TextInput('employeeadmlogin', 'Administrator', '', true, 15);
    $inputs .= em_TagSelector('editadtagid', __('Tag'));
    $inputs .= wf_delimiter(0);
    $inputs .= wf_TextInput('amountLimit', 'Monthly top up limit', '', true, 5, 'finance');
    $inputs .= wf_delimiter(0);
    $inputs .= wf_Submit(__('Create new employee'));

    $result .= wf_Form('', 'POST', $inputs, 'glamour');
    return($result);
}

/**
 * Saves changes in existing employee
 * 
 * @param int $editemployee
 * 
 * @return void
 */
function em_EmployeeSave($editemployee) {
    $editemployee = ubRouting::filters($editemployee, 'int');
    if (ubRouting::checkPost('editname')) {
        $employeeDb = new NyanORM('employee');
        $actFlag = (ubRouting::checkPost('editactive')) ? 1 : 0;
        $amountLim = (ubRouting::checkPost('amountLimit')) ? ubRouting::post('amountLimit') : 0;
        $employeeDb->data('name', ubRouting::post('editname', 'mres'));
        $employeeDb->data('appointment', ubRouting::post('editappointment', 'mres'));
        $employeeDb->data('mobile', ubRouting::post('editmobile', 'mres'));
        $employeeDb->data('telegram', ubRouting::post('edittelegram', 'mres'));
        $employeeDb->data('admlogin', ubRouting::post('editadmlogin', 'mres'));
        $employeeDb->data('tagid', ubRouting::post('editadtagid', 'int'));
        $employeeDb->data('amountLimit', $amountLim);
        $employeeDb->data('active', $actFlag);
        $employeeDb->where('id', '=', $editemployee);
        $employeeDb->save();

        log_register('EMPLOYEE CHANGE [' . $editemployee . ']');
    }
}

/**
 * Renders employee editing form
 * 
 * @param int $editemployee
 * 
 * @return string
 */
function em_employeeEditForm($editemployee) {
    $result = '';
    $editemployee = ubRouting::filters($editemployee, 'int');
    $employeedata = stg_get_employee_data($editemployee);
    $actflag = ($employeedata['active']) ? true : false;

    $editinputs = wf_TextInput('editname', 'Real Name', $employeedata['name'], true, 20);
    $editinputs .= wf_TextInput('editappointment', 'Appointment', $employeedata['appointment'], true, 20);
    $editinputs .= wf_TextInput('editmobile', __('Mobile'), $employeedata['mobile'], true, 20);
    $editinputs .= wf_TextInput('edittelegram', __('Chat ID') . ' ' . __('Telegram'), $employeedata['telegram'], true, 20, 'digits');
    $editinputs .= wf_TextInput('editadmlogin', __('Administrator'), $employeedata['admlogin'], true, 20);
    $editinputs .= em_TagSelector('editadtagid', __('Tag'), $employeedata['tagid'], true);
    $editinputs .= wf_TextInput('amountLimit', __('Monthly top up limit'), $employeedata['amountLimit'], true, 20, 'finance');
    $editinputs .= wf_CheckInput('editactive', 'Active', true, $actflag);
    $editinputs .= wf_Submit('Save');
    $result .= wf_Form('', 'POST', $editinputs, 'glamour');

    return($result);
}

/**
 * Renders existing job type editing form
 * 
 * @param int $editjobId
 * 
 * @return string
 */
function em_JobTypeEditForm($editjobId) {
    $result = '';
    $editjobId = ubRouting::filters($editjobId, 'int');
    $jobdata = stg_get_jobtype_name($editjobId);
    $jobcolor = stg_get_jobtype_color($editjobId);
    $jobinputs = wf_TextInput('editjobtype', 'Job type', $jobdata, true, 20);
    $jobinputs .= wf_ColPicker('editjobcolor', __('Color'), $jobcolor, true, 10);
    $jobinputs .= wf_Submit('Save');
    $result .= wf_Form('', 'POST', $jobinputs, 'glamour');
    return($result);
}

/**
 * Saves changes in existing job type
 * 
 * @param type $editjobId
 * 
 * @return void
 */
function em_JobTypeSave($editjobId) {
    if (ubRouting::checkPost('editjobtype')) {
        $jobTypesDb = new NyanORM('jobtypes');
        $jobTypesDb->data('jobname', ubRouting::post('editjobtype', 'mres'));
        $jobTypesDb->data('jobcolor', ubRouting::post('editjobcolor', 'mres'));
        $jobTypesDb->where('id', '=', $editjobId);
        $jobTypesDb->save();
        log_register('JOBTYPE CHANGE [' . $editjobId . '] `' . ubRouting::post('editjobtype') . '`');
    }
}

/**
 * Renders jobtypes edit/creation/deletion form and list
 * 
 * @return void
 */
function em_JobTypeRenderList() {
    $result = '';
    $messages = new UbillingMessageHelper();
    $allJobTypes = ts_GetAllJobtypesData();

    if (!empty($allJobTypes)) {
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Job type'));
        $cells .= wf_TableCell(__('Color'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($allJobTypes as $ion => $eachjob) {
            $cells = wf_TableCell($eachjob['id']);
            $cells .= wf_TableCell($eachjob['jobname']);
            $jobColor = (!empty($eachjob['jobcolor'])) ? wf_tag('font', false, '', 'color="' . $eachjob['jobcolor'] . '"') . $eachjob['jobcolor'] . wf_tag('font', true) : '';
            $cells .= wf_TableCell($jobColor);

            $urlDelete = '?module=employee&deletejob=' . $eachjob['id'];
            $urlEdit = '?module=employee&editjob=' . $eachjob['id'];
            $urlCancel = '?module=employee';

            $actionlinks = wf_ConfirmDialog($urlDelete, web_delete_icon(), $messages->getDeleteAlert(), '', $urlCancel, __('Delete') . '?');
            $actionlinks .= wf_Link($urlEdit, web_edit_icon());


            $cells .= wf_TableCell($actionlinks);
            $rows .= wf_TableRow($cells, 'row5');
        }
        $result .= wf_TableBody($rows, '100%', '0', 'sortable');
    } else {
        $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
    }


    $createJtLabel = __('Create') . ' ' . __('Job type');
    $creationLink = wf_modalAuto(wf_img_sized('skins/add_icon.png', $createJtLabel), $createJtLabel, em_JobTypeCreateForm());
    show_window(__('Job types') . ' ' . $creationLink, $result);
}

/**
 * Returns new job type creation form
 * 
 * @return string
 */
function em_JobTypeCreateForm() {
    $result = '';
    $inputs = wf_HiddenInput('addjobtype', 'true');
    $inputs .= wf_TextInput('newjobtype', __('Job type'), '', true, 30);
    $inputs .= wf_ColPicker('newjobcolor', __('Color'), '', true, 8);
    $inputs .= wf_Submit(__('Create'));

    $result .= wf_Form('', 'POST', $inputs, 'glamour');

    return($result);
}

/**
 * Creates new employee in database
 * 
 * @param string $name
 * @param string $job
 * @param string $mobile
 * @param string $admlogin
 * 
 * @return void
 */
function em_EmployeeAdd($name, $job, $mobile = '', $telegram = '', $admlogin = '', $tagid = '', $amountLimit = '') {
    $name = mysql_real_escape_string(trim($name));
    $job = mysql_real_escape_string(trim($job));
    $mobile = mysql_real_escape_string($mobile);
    $telegram = mysql_real_escape_string($telegram);
    $admlogin = mysql_real_escape_string($admlogin);
    $tagid = mysql_real_escape_string($tagid);
    $amountLimit = (empty($amountLimit)) ? 0 : $amountLimit;
    $query = "INSERT INTO `employee` (`id` , `name` , `appointment`, `mobile`, `telegram`, `admlogin`, `active`, `tagid`, `amountLimit`)
              VALUES (NULL , '" . $name . "', '" . $job . "','" . $mobile . "','" . $telegram . "' ,'" . $admlogin . "' , '1', " . $tagid . ", " . $amountLimit . "); ";
    nr_query($query);
    $employee_id = simple_query("SELECT LAST_INSERT_ID() as id");
    $employee_id = $employee_id['id'];
    log_register('EMPLOYEE ADD [' . $employee_id . ']`' . $name . '` JOB `' . $job . '`');
}

/**
 * Deletes existing employee from database
 * 
 * @param int $id
 * 
 * @return void
 */
function em_EmployeeDelete($id) {
    $id = vf($id, 3);
    $query = "DELETE from `employee` WHERE `id`=" . $id;
    nr_query($query);
    log_register('EMPLOYEE DEL [' . $id . ']');
}

/**
 * Creates new jobtype in database
 * 
 * @param string $jobtype
 * @param string $jobcolor
 * 
 * @return void
 */
function stg_add_jobtype($jobtype, $jobcolor) {
    $jobtype = mysql_real_escape_string(trim($jobtype));
    $jobcolor = mysql_real_escape_string($jobcolor);

    $query = "INSERT INTO `jobtypes` (`id` , `jobname`, `jobcolor`)
              VALUES (NULL , '" . $jobtype . "', '" . $jobcolor . "');";
    nr_query($query);
    log_register('JOBTYPE ADD `' . $jobtype . '`');
}

/**
 * Deletes existing job type from database
 * 
 * @param int $id
 * 
 * @return void
 */
function stg_delete_jobtype($id) {
    $id = vf($id, 3);
    $query = "DELETE from `jobtypes` WHERE `id`=" . $id;
    nr_query($query);
    log_register('JOBTYPE DEL [' . $id . ']');
}

/**
 * Returns employee name from database by its ID
 * 
 * @param int $id
 * @return string
 */
function stg_get_employee_name($id) {
    $id = vf($id, 3);
    $query = 'SELECT `name` from `employee` WHERE `id`="' . $id . '"';
    $employee = simple_query($query);
    return($employee['name']);
}

/**
 * Returns employee data array by its ID
 * 
 * @param int $id
 * @return array
 */
function stg_get_employee_data($id) {
    $id = vf($id, 3);
    $query = 'SELECT *  from `employee` WHERE `id`="' . $id . '"';
    $employee = simple_query($query);
    return($employee);
}

/**
 * Returns jobtype name by its ID
 * 
 * @param int $id
 * @return string
 */
function stg_get_jobtype_name($id) {
    $query = 'SELECT `jobname` from `jobtypes` WHERE `id`="' . $id . '"';
    $jobtype = simple_query($query);
    return($jobtype['jobname']);
}

/**
 * Returns jobtype color by its ID
 * 
 * @param int $id
 * @return string
 */
function stg_get_jobtype_color($id) {
    $query = 'SELECT `jobcolor` from `jobtypes` WHERE `id`="' . $id . '"';
    $jobcolor = simple_query($query);
    return($jobcolor['jobcolor']);
}

/**
 * Renders list with controls for jobs done for some user
 * 
 * @param string $username
 * 
 * @return void
 */
function web_showPreviousJobs($username) {
    $query_jobs = 'SELECT * FROM `jobs` WHERE `login`="' . $username . '" ORDER BY `id` DESC';
    $alljobs = simple_queryall($query_jobs);
    $allemployee = ts_GetAllEmployee();
    $alljobtypes = ts_GetAllJobtypes();
    $activeemployee = ts_GetActiveEmployee();

    $cells = wf_TableCell(__('ID'));
    $cells .= wf_tableCell(__('Date'));
    $cells .= wf_TableCell(__('Worker'));
    $cells .= wf_TableCell(__('Job type'));
    $cells .= wf_TableCell(__('Notes'));
    if (cfr('JOBSMGMT')) {
        $cells .= wf_TableCell(__('Actions'));
    }
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($alljobs)) {
        foreach ($alljobs as $ion => $eachjob) {
//backlink to taskman if some TASKID inside
            if (ispos($eachjob['note'], 'TASKID:[')) {
                $taskid = vf($eachjob['note'], 3);
                $jobnote = wf_Link("?module=taskman&&edittask=" . $taskid, __('Task is done') . ' #' . $taskid, false, '');
            } else {
                $jobnote = $eachjob['note'];
            }

            $cells = wf_TableCell($eachjob['id']);
            $cells .= wf_tableCell($eachjob['date']);
            $cells .= wf_TableCell(@$allemployee[$eachjob['workerid']]);
            $cells .= wf_TableCell(@$alljobtypes[$eachjob['jobid']]);
            $cells .= wf_TableCell($jobnote);
            if (cfr('JOBSMGMT')) {
                $cells .= wf_TableCell(wf_JSAlert('?module=jobs&username=' . $username . '&deletejob=' . $eachjob['id'] . '', web_delete_icon(), 'Are you serious'));
            }
            $rows .= wf_TableRow($cells, 'row3');
        }
    }

    if (cfr('JOBSMGMT')) {
//onstruct job create form
        $curdatetime = curdatetime();
        $inputs = wf_HiddenInput('addjob', 'true');
        $inputs .= wf_HiddenInput('jobdate', $curdatetime);
        $inputs .= wf_TableCell('');
        $inputs .= wf_tableCell($curdatetime);
        $inputs .= wf_TableCell(wf_Selector('worker', $activeemployee, '', '', false));
        $inputs .= wf_TableCell(wf_Selector('jobtype', $alljobtypes, '', '', false));
        $inputs .= wf_TableCell(wf_TextInput('notes', '', '', false, '20'));
        $inputs .= wf_TableCell(wf_Submit('Create'));
        $inputs = wf_TableRow($inputs, 'row2');

        $addform = wf_Form("", 'POST', $inputs, '');

        if ((!empty($activeemployee)) AND ( !empty($alljobtypes))) {
            $rows .= $addform;
        } else {
            show_error(__('No job types and employee available'));
        }
    }

    $result = wf_TableBody($rows, '100%', '0', '');

    show_window(__('Jobs'), $result);
}

/**
 * Deletes some job from database by its ID
 * 
 * @param int $jobid
 * 
 * @return void
 */
function stg_delete_job($jobid) {
    $jobid = vf($jobid, 3);
    $query = "DELETE from `jobs` WHERE `id`='" . $jobid . "'";
    nr_query($query);
    log_register('JOB DELETE [' . $jobid . ']');
}

/**
 * Creates new job in database
 * 
 * @param string $login
 * @param string $date
 * @param int $worker_id
 * @param int $jobtype_id
 * @param string $job_notes
 * 
 * @return void
 */
function stg_add_new_job($login, $date, $worker_id, $jobtype_id, $job_notes) {
    $job_notes = mysql_real_escape_string(trim($job_notes));
    $datetime = curdatetime();
    $query = "INSERT INTO `jobs` (`id` , `date` , `jobid` , `workerid` , `login` ,`note`) VALUES (
              NULL , '" . $datetime . "', '" . $jobtype_id . "', '" . $worker_id . "', '" . $login . "', '" . $job_notes . "'); ";
    nr_query($query);
    log_register('JOB ADD W:[' . $worker_id . '] J:[' . $jobtype_id . '] (' . $login . ')');
}

//
// New Task management API - old is shitty and exists only for backward compatibility
//

/**
 * Returns login detected by address
 * 
 * @param string $address
 * @return string
 */
function ts_DetectUserByAddress($address) {
    $telepathy = new Telepathy(false, true);
    return($telepathy->getLogin($address));
}

/**
 * Returns array of all existing employees as id=>name
 * 
 * @return array
 */
function ts_GetAllEmployee() {
    $query = "SELECT * from `employee`";
    $allemployee = simple_queryall($query);
    $result = array();
    if (!empty($allemployee)) {
        foreach ($allemployee as $io => $each) {
            $result[$each['id']] = $each['name'];
        }
    }
    return ($result);
}

/**
 * Returns array of all existing employees as id=>employeeData
 * 
 * @return array
 */
function ts_GetAllEmployeeData() {
    $query = "SELECT * from `employee`";
    $allemployee = simple_queryall($query);
    $result = array();
    if (!empty($allemployee)) {
        foreach ($allemployee as $io => $each) {
            $result[$each['id']] = $each;
        }
    }
    return ($result);
}

/**
 * Returns array of available jobtypes as id=>name
 * 
 * @return array
 */
function ts_GetAllJobtypes() {
    $query = "SELECT * from `jobtypes`";
    $alljt = simple_queryall($query);
    $result = array();
    if (!empty($alljt)) {
        foreach ($alljt as $io => $each) {
            $result[$each['id']] = $each['jobname'];
        }
    }
    return ($result);
}

/**
 * Returns array of available jobtype colors as id=>color
 * 
 * @return array
 */
function ts_GetAllJobColors() {
    $query = "SELECT * from `jobtypes`";
    $alljt = simple_queryall($query);
    $result = array();
    if (!empty($alljt)) {
        foreach ($alljt as $io => $each) {
            $color = (!empty($each['jobcolor'])) ? $each['jobcolor'] : '';
            $result[$each['id']] = $color;
        }
    }
    return ($result);
}

/**
 * Returns array of all jobtypes data as id=>jobtype data
 * 
 * @return array
 */
function ts_GetAllJobtypesData() {
    $query = "SELECT * from `jobtypes`";
    $alljt = simple_queryall($query);
    $result = array();
    if (!empty($alljt)) {
        foreach ($alljt as $io => $each) {
            $result[$each['id']] = $each;
        }
    }
    return ($result);
}

/**
 * Returns all jobtypes custom stylesheets for jq fullcalendar listing
 * 
 * @return string
 */
function ts_GetAllJobtypesColorStyles() {
    $customJobColorStyle = '<style>';
    $alljobcolors = ts_GetAllJobColors();
    if (!empty($alljobcolors)) {
        foreach ($alljobcolors as $jcio => $eachjobcolor) {
            if (!empty($eachjobcolor)) {
                $customJobColorStyleName = 'jobcolorcustom_' . $jcio;
                $customJobColorStyle .= '.' . $customJobColorStyleName . ',
                                                   .' . $customJobColorStyleName . ' div,
                                                   .' . $customJobColorStyleName . ' span {
                                                        background-color: ' . $eachjobcolor . '; 
                                                        border-color: ' . $eachjobcolor . '; 
                                                        color: #FFFFFF;           
                                                    }';
            }
        }
    }
//anyone optional coloring

    $customJobColorStyle .= '.jobcoloranyone,
                                                   .jobcoloranyone div,
                                                   .jobcoloranyone span {
                                                        background-color: #d04e00; 
                                                        border-color: #d04e00; 
                                                        color: #FFFFFF;       
                                                    }';
    $customJobColorStyle .= '</style>' . "\n";
    return ($customJobColorStyle);
}

/**
 * Returns array of active employees as id=>name
 * 
 * @return array
 */
function ts_GetActiveEmployee() {
    $query = "SELECT * from `employee` WHERE `active`='1'";
    $allemployee = simple_queryall($query);
    $result = array();
    if (!empty($allemployee)) {
        foreach ($allemployee as $io => $each) {
            $result[$each['id']] = $each['name'];
        }
    }
    return ($result);
}

/**
 * Returns array of active employees as id=>login
 * 
 * @return array
 */
function ts_GetActiveEmployeeLogins() {
    $query = "SELECT * from `employee` WHERE `active`='1'";
    $allemployee = simple_queryall($query);
    $result = array();
    if (!empty($allemployee)) {
        foreach ($allemployee as $io => $each) {
            if (!empty($each['admlogin'])) {
                $result[$each['id']] = $each['admlogin'];
            }
        }
    }
    return ($result);
}

/**
 * Returns jq fullcalendar data for jobreport module
 * 
 * @return string
 */
function ts_JGetJobsReport() {
    $allemployee = ts_GetAllEmployee();
    $alljobtypes = ts_GetAllJobtypes();
    $cyear = curyear();

    $query = "SELECT * from `jobs` WHERE `date` LIKE '" . $cyear . "-%' ORDER BY `id` DESC";
    $alljobs = simple_queryall($query);

    $i = 1;
    $jobcount = sizeof($alljobs);
    $result = '';

    if (!empty($alljobs)) {
        foreach ($alljobs as $io => $eachjob) {
            if ($i != $jobcount) {
                $thelast = ',';
            } else {
                $thelast = '';
            }

            $startdate = strtotime($eachjob['date']);
            $startdate = date("Y, n-1, j", $startdate);

            $result .= "
                      {
                        title: '" . $allemployee[$eachjob['workerid']] . " - " . @$alljobtypes[$eachjob['jobid']] . "',
                        start: new Date(" . $startdate . "),
                        end: new Date(" . $startdate . "),
                        url: '?module=userprofile&username=" . $eachjob['login'] . "'
                      }
                    " . $thelast;
            $i++;
        }
    }
    return ($result);
}

/**
 * Returns data for jq fullcalendar widget with undone tasks
 * 
 * @global object $ubillingConfig
 * @return string
 */
function ts_JGetUndoneTasks() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['TASKMAN_ANYONE_COLORING']) {
        $anyoneId = $altCfg['TASKMAN_ANYONE_COLORING'];
    } else {
        $anyoneId = false;
    }

    $showAllYearsTasks = $ubillingConfig->getAlterParam('TASKMAN_SHOW_ALL_YEARS_TASKS');
    $advFiltersEnabled = $ubillingConfig->getAlterParam('TASKMAN_ADV_FILTERS');
    $branchConsider = ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')
            and $ubillingConfig->getAlterParam('TASKMAN_BRANCHES_CONSIDER_ON'));

//ADcomments init
    if ($altCfg['ADCOMMENTS_ENABLED']) {
        $adcomments = new ADcomments('TASKMAN');
        $adcFlag = true;
    } else {
        $adcFlag = false;
    }

    $allemployee = ts_GetAllEmployee();
    $alljobdata = ts_getAllJobtypesData();
    $curyear = curyear();
    $curmonth = date("m");
    $appendQueryJOIN = '';
    $appendQuerySelect = '';

//per employee filtering
    $displaytype = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : 'all';
//administrator is cursed of some branch
    if (ts_isMeBranchCursed()) {
        $displaytype = 'onlyme';
    }
    if ($displaytype == 'onlyme') {
        $whoami = whoami();
        $curempid = ts_GetEmployeeByLogin($whoami);
        $appendQuery = " AND `employee`='" . $curempid . "'";
    } else {
        if (ispos($displaytype, 'displayempid')) {
            $displayEmployeeId = ubRouting::filters($displaytype, 'int');
            $appendQuery = " AND `employee`='" . $displayEmployeeId . "'";
        } else {
            $appendQuery = '';
        }
    }

    if ($advFiltersEnabled) {
        $appendQuery .= ts_AdvFiltersQuery();
    }

    if ($branchConsider) {
        $appendQueryJOIN = " LEFT JOIN `branchesusers` USING(`login`) 
                             LEFT JOIN `branches` ON `branchesusers`.`branchid` = `branches`.`id` ";
        $appendQuerySelect = ", `branches`.`name` AS `branch_name` ";
    }

    if (!$showAllYearsTasks AND ( $curmonth != 1 AND $curmonth != 12)) {
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname`" . $appendQuerySelect . " FROM `taskman` 
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id`
                      " . $appendQueryJOIN . " 
                    WHERE `status`='0' AND `startdate` LIKE '" . $curyear . "-%' " . $appendQuery . " ORDER BY `date` ASC";
    } else {
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname`" . $appendQuerySelect . " FROM `taskman`  
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id`
                      " . $appendQueryJOIN . " 
                    WHERE `status`='0' " . $appendQuery . " ORDER BY `date` ASC";
    }

    $allundone = simple_queryall($query);
    $result = '';
    $i = 1;
    $taskcount = sizeof($allundone);
    $branchName = '';

    if (!empty($allundone)) {
        foreach ($allundone as $io => $eachtask) {
            if ($i != $taskcount) {
                $thelast = ',';
            } else {
                $thelast = '';
            }

            $startdate = strtotime($eachtask['startdate']);
            $startdate = date("Y, n-1, j", $startdate);

            if ($eachtask['enddate'] != '') {
                $enddate = strtotime($eachtask['enddate']);
                $enddate = date("Y, n-1, j", $enddate);
            } else {
                $enddate = $startdate;
            }

//custom task color preprocessing
            if (isset($alljobdata[$eachtask['jobtype']])) {
                if (!empty($alljobdata[$eachtask['jobtype']]['jobcolor'])) {
                    $jobColorClass = 'jobcolorcustom_' . $eachtask['jobtype'];
                } else {
                    $jobColorClass = 'undone';
                }
            } else {
                $jobColorClass = 'undone';
            }

//anyone employee coloring
            if ($anyoneId) {
                if ($eachtask['employee'] == $anyoneId) {
                    $jobColorClass = 'jobcoloranyone';
                }
            }

//time ordering
            if (!empty($eachtask['starttime'])) {
                $startTime = $eachtask['starttime'];
                $startTime = substr($startTime, 0, 5) . ' ';
                $startTimeTimestamp = ', ' . str_replace(':', ', ', $startTime);
            } else {
                $startTime = '';
                $startTimeTimestamp = '';
            }

//adcomments detect
            if ($adcFlag) {
                $adcommentsCount = $adcomments->getCommentsCount($eachtask['id']);
            } else {
                $adcommentsCount = 0;
            }

            if ($adcommentsCount > 0) {
                $adcText = ' (' . $adcommentsCount . ')';
            } else {
                $adcText = '';
            }

// get users's branch
            $branchName = ($branchConsider) ? ' ' . $eachtask['branch_name'] . ' ' : '';

            $result .= "
                      {
                        id: " . $eachtask['id'] . ",
                        title: '" . $startTime . $branchName . $eachtask['address'] . " - " . @$alljobdata[$eachtask['jobtype']]['jobname'] . $adcText . "',
                        start: new Date(" . $startdate . $startTimeTimestamp . "),
                        end: new Date(" . $enddate . "),
                        className : '" . $jobColorClass . "',
                        url: '?module=taskman&edittask=" . $eachtask['id'] . "'                        
                      } 
                    " . $thelast;
        }
    }

    return ($result);
}

/**
 * Returns data for jq fullcalendar widget with done tasks
 * 
 * @global object $ubillingConfig
 * @return string
 */
function ts_JGetDoneTasks() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $showAllYearsTasks = $ubillingConfig->getAlterParam('TASKMAN_SHOW_ALL_YEARS_TASKS');
    $showExtendedDone = $ubillingConfig->getAlterParam('TASKMAN_SHOW_DONE_EXTENDED');
    $extendedDoneAlterStyling = $ubillingConfig->getAlterParam('TASKMAN_DONE_EXTENDED_ALTERSTYLING');
    $advFiltersEnabled = $ubillingConfig->getAlterParam('TASKMAN_ADV_FILTERS');
    $branchConsider = ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')
            and $ubillingConfig->getAlterParam('TASKMAN_BRANCHES_CONSIDER_ON'));

//ADcomments init
    if ($altCfg['ADCOMMENTS_ENABLED']) {
        $adcomments = new ADcomments('TASKMAN');
        $adcFlag = true;
    } else {
        $adcFlag = false;
    }
    $allemployee = ts_GetAllEmployee();

// unnecessary call - isn't it?
//$alljobtypes = ts_GetAllJobtypes();

    $curyear = curyear();
    $curmonth = date("m");
    $appendQueryJOIN = '';
    $appendQuerySelect = '';

//per employee filtering
    $displaytype = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : 'all';
//administrator is cursed of some branch
    if (ts_isMeBranchCursed()) {
        $displaytype = 'onlyme';
    }
    if ($displaytype == 'onlyme') {
        $whoami = whoami();
        $curempid = ts_GetEmployeeByLogin($whoami);
        $appendQuery = " AND `employee`='" . $curempid . "'";
    } else {
        if (ispos($displaytype, 'displayempid')) {
            $displayEmployeeId = ubRouting::filters($displaytype, 'int');
            $appendQuery = " AND `employee`='" . $displayEmployeeId . "'";
        } else {
            $appendQuery = '';
        }
    }

    if ($advFiltersEnabled) {
        $appendQuery .= ts_AdvFiltersQuery();
    }

    if ($branchConsider) {
        $appendQueryJOIN = " LEFT JOIN `branchesusers` USING(`login`) 
                             LEFT JOIN `branches` ON `branchesusers`.`branchid` = `branches`.`id` ";
        $appendQuerySelect = ", `branches`.`name` AS `branch_name` ";
    }

    if (!$showAllYearsTasks AND ( $curmonth != 1 AND $curmonth != 12)) {
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname`" . $appendQuerySelect . " FROM `taskman` 
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id`
                      " . $appendQueryJOIN . " 
                    WHERE `status`='1' AND `startdate` LIKE '" . $curyear . "-%' " . $appendQuery . " ORDER BY `date` ASC";
    } else {
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname`" . $appendQuerySelect . " FROM `taskman` 
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id`
                      " . $appendQueryJOIN . "  
                    WHERE `status`='1' " . $appendQuery . " ORDER BY `date` ASC";
    }

    $allundone = simple_queryall($query);
    $result = '';
    $i = 1;
    $taskcount = sizeof($allundone);
    $branchName = '';

    if (!empty($allundone)) {
        foreach ($allundone as $io => $eachtask) {
            if ($i != $taskcount) {
                $thelast = ',';
            } else {
                $thelast = '';
            }

            $startdate = strtotime($eachtask['startdate']);
            $startdate = date("Y, n-1, j", $startdate);
            if ($eachtask['enddate'] != '') {
                $enddate = strtotime($eachtask['enddate']);
                $enddate = date("Y, n-1, j", $enddate);
            } else {
                $enddate = $startdate;
            }


//adcomments detect
            if ($adcFlag) {
                $adcommentsCount = $adcomments->getCommentsCount($eachtask['id']);
            } else {
                $adcommentsCount = 0;
            }

            if ($adcommentsCount > 0) {
                $adcText = ' (' . $adcommentsCount . ')';
            } else {
                $adcText = '';
            }

            $doneemploee = (!empty($allemployee[$eachtask['employeedone']])) ? $allemployee[$eachtask['employeedone']] : '';

            if ($showExtendedDone) {
                if ($extendedDoneAlterStyling) {
                    $jobtype = (!empty($eachtask['jobname'])) ? ' - <span style="color: #1d1ab2;"><b>' . __('Job type') . ': </b></span>' . $eachtask['jobname'] : '';
                    $jobnote = (!empty($eachtask['jobnote'])) ? ' - <span style="color: #1d1ab2;"><b>' . __('Job note') . ': </b></span>' . $eachtask['jobnote'] : '';
                    $donenote = (!empty($eachtask['donenote'])) ? ' - <span style="color: #1d1ab2;"><b>' . __('Finish note') . ': </b></span>' . $eachtask['donenote'] : '';
                    $donedate = (!empty($eachtask['enddate'])) ? ' - <span style="color: #1d1ab2;"><b>' . __('Finish date') . ': </b></span>' . $eachtask['enddate'] : '';

                    $doneemploee = (!empty($allemployee[$eachtask['employeedone']])) ? '<b>' . $allemployee[$eachtask['employeedone']] . '</b>' : '';
                } else {
                    $jobtype = (!empty($eachtask['jobname'])) ? ' - ' . __('Job type') . ': ' . $eachtask['jobname'] : '';
                    $jobnote = (!empty($eachtask['jobnote'])) ? ' - ' . __('Job note') . ': ' . $eachtask['jobnote'] : '';
                    $donenote = (!empty($eachtask['donenote'])) ? ' - ' . __('Finish note') . ': ' . $eachtask['donenote'] : '';
                    $donedate = (!empty($eachtask['enddate'])) ? ' - ' . __('Finish date') . ': ' . $eachtask['enddate'] : '';
                }
                $extendInfo = $jobtype . $jobnote . $donenote . $donedate;
            } else {
                $extendInfo = '';
            }

// get users's branch
            $branchName = ($branchConsider) ? ' ' . $eachtask['branch_name'] . ' ' : '';

            $result .= "
                      {
                        id: " . $eachtask['id'] . ",
                        title: '" . $branchName . $eachtask['address'] . " - " . $doneemploee . $adcText . mysql_real_escape_string($extendInfo) . "',
                        start: new Date(" . $startdate . "),
                        end: new Date(" . $enddate . "),
                        url: '?module=taskman&edittask=" . $eachtask['id'] . "',
                        constraint: {start: '00:00', end: '00:00', dow: []}
                      }
                    " . $thelast;
        }
    }

    return ($result);
}

/**
 * Returns data for jq fullcalendar widget with all tasks
 * 
 * @global object $ubillingConfig
 * @return string
 */
function ts_JGetAllTasks() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $showAllYearsTasks = $ubillingConfig->getAlterParam('TASKMAN_SHOW_ALL_YEARS_TASKS');
    $advFiltersEnabled = $ubillingConfig->getAlterParam('TASKMAN_ADV_FILTERS');
    $branchConsider = ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')
            and $ubillingConfig->getAlterParam('TASKMAN_BRANCHES_CONSIDER_ON'));

//ADcomments init
    if ($altCfg['ADCOMMENTS_ENABLED']) {
        $adcomments = new ADcomments('TASKMAN');
        $adcFlag = true;
    } else {
        $adcFlag = false;
    }
    $allemployee = ts_GetAllEmployee();
    $alljobdata = ts_GetAllJobtypesData();

    $curyear = curyear();
    $curmonth = date("m");
    $appendQueryJOIN = '';
    $appendQuerySelect = '';

//per employee filtering
    $displaytype = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : 'all';
//administrator is cursed of some branch
    if (ts_isMeBranchCursed()) {
        $displaytype = 'onlyme';
    }
    if ($displaytype == 'onlyme') {
        $whoami = whoami();
        $curempid = ts_GetEmployeeByLogin($whoami);
        $appendQuery = " AND `employee`='" . $curempid . "'";
    } else {
        if (ispos($displaytype, 'displayempid')) {
            $displayEmployeeId = ubRouting::filters($displaytype, 'int');
            $appendQuery = " AND `employee`='" . $displayEmployeeId . "'";
        } else {
            $appendQuery = '';
        }
    }

    if ($advFiltersEnabled) {
        $appendQuery .= ts_AdvFiltersQuery();
    }

    if ($branchConsider) {
        $appendQueryJOIN = " LEFT JOIN `branchesusers` USING(`login`) 
                             LEFT JOIN `branches` ON `branchesusers`.`branchid` = `branches`.`id` ";
        $appendQuerySelect = ", `branches`.`name` AS `branch_name` ";
    }

    if (!$showAllYearsTasks AND ( $curmonth != 1 AND $curmonth != 12)) {
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname`" . $appendQuerySelect . " FROM `taskman` 
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id`
                      " . $appendQueryJOIN . " 
                    WHERE `startdate` LIKE '" . $curyear . "-%' " . $appendQuery . " ORDER BY `date` ASC";
    } else {
        if ($appendQuery) {
//$appendQuery = str_replace('AND', 'WHERE', $appendQuery);
            $appendQuery = preg_replace('/AND/', 'WHERE', $appendQuery, 1);
        }
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname`" . $appendQuerySelect . " FROM `taskman` 
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id`
                      " . $appendQueryJOIN . "
                      " . $appendQuery . " ORDER BY `date` ASC";
    }

    $allundone = simple_queryall($query);
    $result = '';
    $i = 1;
    $taskcount = sizeof($allundone);
    $branchName = '';

    if (!empty($allundone)) {
        foreach ($allundone as $io => $eachtask) {
            if ($i != $taskcount) {
                $thelast = ',';
            } else {
                $thelast = '';
            }

            $startdate = strtotime($eachtask['startdate']);
            $startdate = date("Y, n-1, j", $startdate);

//time ordering
            if (!empty($eachtask['starttime'])) {
                $startTime = $eachtask['starttime'];
                $startTime = substr($startTime, 0, 5) . ' ';
                $startTimeTimestamp = ', ' . str_replace(':', ', ', $startTime);
            } else {
                $startTime = '';
                $startTimeTimestamp = '';
            }

            if ($eachtask['enddate'] != '') {
                $enddate = strtotime($eachtask['enddate']);
                $enddate = date("Y, n-1, j", $enddate);
            } else {
                $enddate = $startdate;
            }

            if ($eachtask['status'] == 0) {
                $coloring = "className : 'undone',";
                if (isset($alljobdata[$eachtask['jobtype']])) {
                    if (!empty($alljobdata[$eachtask['jobtype']]['jobcolor'])) {
                        $coloring = "className : 'jobcolorcustom_" . $eachtask['jobtype'] . "',";
                    } else {
                        $coloring = "className : 'undone',";
                    }
                } else {
                    $jobColorClass = "className : 'undone',";
                }
            } else {
                $coloring = '';
            }

//adcomments detect
            if ($adcFlag) {
                $adcommentsCount = $adcomments->getCommentsCount($eachtask['id']);
            } else {
                $adcommentsCount = 0;
            }

            if ($adcommentsCount > 0) {
                $adcText = ' (' . $adcommentsCount . ')';
            } else {
                $adcText = '';
            }

// get users's branch
            $branchName = ($branchConsider) ? ' ' . $eachtask['branch_name'] . ' ' : '';

            $result .= "
                      {
                        id: " . $eachtask['id'] . ",
                        title: '" . $startTime . $branchName . $eachtask['address'] . " - " . @$alljobdata[$eachtask['jobtype']]['jobname'] . $adcText . "',
                        start: new Date(" . $startdate . $startTimeTimestamp . "),
                        end: new Date(" . $enddate . "),
                        " . $coloring . "
                        url: '?module=taskman&edittask=" . $eachtask['id'] . "',
                        " . ($eachtask['status'] == 1 ? "constraint: {start: '00:00', end: '00:00', dow: []}" : "") . "
                      }
                    " . $thelast;
        }
    }

    return ($result);
}

/**
 * Returns typical notes selector for task creation dialogues
 * 
 * @param bool $settings
 * @return string
 */
function ts_TaskTypicalNotesSelector($settings = true) {
    global $ubillingConfig;
    $noLengthCut = $ubillingConfig->getAlterParam('TASKMAN_NO_TYPICALNOTES_CUT');

    $rawNotes = zb_StorageGet('PROBLEMS');
    if ($settings) {
        $settingsControl = wf_Link("?module=taskman&probsettings=true", wf_img('skins/settings.png', __('Settings')), false, '');
    } else {
        $settingsControl = '';
    }
    if (!empty($rawNotes)) {
        $rawNotes = base64_decode($rawNotes);
        $rawNotes = unserialize($rawNotes);
    } else {
        $emptyArray = array();
        $newNotes = serialize($emptyArray);
        $newNotes = base64_encode($newNotes);
        zb_StorageSet('PROBLEMS', $newNotes);
        $rawNotes = $emptyArray;
    }

    $typycalNotes = array('' => '-');

    if (!empty($rawNotes)) {
        foreach ($rawNotes as $eachnote) {
            if (!$noLengthCut and mb_strlen($eachnote, 'utf-8') > 20) {
                $shortNote = mb_substr($eachnote, 0, 20, 'utf-8') . '...';
            } else {
                $shortNote = $eachnote;
            }

            $typycalNotes[$eachnote] = $shortNote;
        }
    }

    $selectorAlterWidth = ($noLengthCut) ? 'style="width: 70%"' : '';
    $selector = wf_Selector('typicalnote', $typycalNotes, __('Problem') . ' ' . $settingsControl, '', true, false, '', '', $selectorAlterWidth);
    return ($selector);
}

/**
 * Returns task creation form
 * 
 * @global object $ubillingConfig
 * @return string
 */
function ts_TaskCreateForm() {
    global $ubillingConfig;
    $altercfg = $ubillingConfig->getAlter();

    $alljobtypes = ts_GetAllJobtypes();
    $allemployee = ts_GetActiveEmployee();

    if (!empty($alljobtypes) AND ! empty($allemployee)) {
//construct sms sending inputs
        if ($altercfg['SENDDOG_ENABLED']) {
            $smsCheckBox = (@$altercfg['TASKMAN_SMS_PROFILE_CHECK']) ? true : false;
            $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, $smsCheckBox);
// SET checkbed TELEGRAM for creating task from Userprofile if TASKMAN_TELEGRAM_PROFILE_CHECK == 1
            $telegramInputsCheck = (isset($altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) && $altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) ? TRUE : FALSE;
            $telegramInputs = wf_CheckInput('newtasksendtelegram', __('Telegram'), false, $telegramInputsCheck);
        } else {
            $smsInputs = '';
            $telegramInputs = '';
        }

        $inputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
        $inputs .= wf_HiddenInput('createtask', 'true');
        $inputs .= wf_DatePicker('newstartdate');
        $inputs .= wf_TimePickerPreset('newstarttime', '', '', false);
        $inputs .= wf_tag('label') . __('Target date') . wf_tag('sup') . '*' . wf_tag('sup', true) . wf_tag('label', true);
        $inputs .= wf_delimiter();

        if (!$altercfg['SEARCHADDR_AUTOCOMPLETE']) {
            $inputs .= wf_TextInput('newtaskaddress', __('Address') . '<sup>*</sup>', '', true, '30');
        } else {
            if (!@$altercfg['TASKMAN_SHORT_AUTOCOMPLETE']) {
                $allAddress = zb_AddressGetFulladdresslistCached();
            } else {
                $allAddress = zb_AddressGetStreetsWithBuilds();
            }
            $inputs .= wf_AutocompleteTextInput('newtaskaddress', $allAddress, __('Address') . '<sup>*</sup>', '', true, '30');
        }
        $inputs .= wf_tag('br');
//hidden for new task login input
        $inputs .= wf_HiddenInput('newtasklogin', '');
        $inputs .= wf_TextInput('newtaskphone', __('Phone') . '<sup>*</sup>', '', true, '30');
        $inputs .= wf_tag('br');
        $inputs .= wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
        $inputs .= wf_tag('br');
        $inputs .= wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
        $inputs .= wf_tag('br');
        $inputs .= ts_TaskTypicalNotesSelector();
        $inputs .= wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
        $inputs .= wf_TextArea('newjobnote', '', '', true, '35x5');
        $inputs .= $smsInputs;
        $inputs .= $telegramInputs;
        $inputs .= wf_Submit(__('Create new task'));

        $result = wf_Form("", 'POST', $inputs, 'glamour');
        $result .= __('All fields marked with an asterisk are mandatory');
    } else {
        $messages = new UbillingMessageHelper();
        $result = $messages->getStyledMessage(__('No job types and employee available'), 'error');
    }
    return ($result);
}

/**
 * Returns task creation form for userprofile usage
 * DEPRECATED: use ts_TaskCreateFormUnified instead this
 * 
 * @param string $address
 * @param string $mobile
 * @param string $phone
 * @param string $login
 * @return  string
 */
function ts_TaskCreateFormProfile($address, $mobile, $phone, $login) {
    global $ubillingConfig;

    $alljobtypes = ts_GetAllJobtypes();
    $allemployee = ts_GetActiveEmployee();

    if (!empty($alljobtypes) AND ! empty($allemployee)) {
// telepaticheskoe ugadivanie po tegu, kto dolzhen vipolnit rabotu
        $query = "SELECT `employee`.`id` FROM `tags` INNER JOIN employee USING (tagid) WHERE `login` = '" . $login . "'";
        $telepat_who_should_do = simple_query($query);

//construct sms sending inputs
        if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
            $smsCheckBox = ($ubillingConfig->getAlterParam('TASKMAN_SMS_PROFILE_CHECK')) ? true : false;
            $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, $smsCheckBox);
// SET checkbed TELEGRAM for creating task from Userprofile if TASKMAN_TELEGRAM_PROFILE_CHECK == 1
            $telegramInputsCheck = ($ubillingConfig->getAlterParam('TASKMAN_TELEGRAM_PROFILE_CHECK')) ? TRUE : FALSE;
            $telegramInputs = wf_CheckInput('newtasksendtelegram', __('Telegram'), false, $telegramInputsCheck);
        } else {
            $smsInputs = '';
            $telegramInputs = '';
        }

//new task creation data/time generation
        if ($ubillingConfig->getAlterParam('TASKMAN_NEWTASK_AUTOTIME') == 1) {
            $TaskDate = new DateTime();
            $TaskDate->add(new DateInterval('PT1H'));
            $newTaskDate = $TaskDate->format('Y-m-d');
            $newTaskTime = $TaskDate->format('H:i');
        } elseif ($ubillingConfig->getAlterParam('TASKMAN_NEWTASK_AUTOTIME') == 2) {
            $TaskDate = new DateTime();
            $TaskDate->add(new DateInterval('P1D'));
            $TaskDate->setTime(8, 00);
// В воскресенье работать не хочу
            if ($newTaskDate = $TaskDate->format('w') == 0) {
                $TaskDate->add(new DateInterval('P1D'));
            }
            $newTaskDate = $TaskDate->format('Y-m-d');
            $newTaskTime = $TaskDate->format('H:i');
        } elseif ($ubillingConfig->getAlterParam('TASKMAN_NEWTASK_AUTOTIME') == 3) {
            $TaskDate = new DateTime();
            $TaskDate->add(new DateInterval('P1D'));
            $TaskDate->setTime(8, 00);
            $newTaskDate = $TaskDate->format('Y-m-d');
            $newTaskTime = $TaskDate->format('H:i');
        } else {
            $newTaskDate = '';
            $newTaskTime = '';
        }

        $employeeSorting = ($ubillingConfig->getAlterParam('TASKMAN_NEWTASK_EMPSORT')) ? true : false;

        $sup = wf_tag('sup', false) . '*' . wf_tag('sup', true);

        $inputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
        $inputs .= wf_HiddenInput('createtask', 'true');
        $inputs .= wf_DatePickerPreset('newstartdate', $newTaskDate);
        $inputs .= wf_TimePickerPreset('newstarttime', $newTaskTime, '', false);
        $inputs .= wf_tag('label') . __('Target date') . $sup . wf_tag('label', true);
        $inputs .= wf_delimiter();
        $inputs .= wf_TextInput('newtaskaddress', __('Address') . $sup, $address, true, '30');
//hidden for new task login input
        $inputs .= wf_HiddenInput('newtasklogin', $login);
        $inputs .= wf_tag('br');
        $inputs .= wf_TextInput('newtaskphone', __('Phone') . $sup, $mobile . ' ' . $phone, true, '30');
        $inputs .= wf_tag('br');
        $inputs .= wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
        $inputs .= wf_tag('br');
        $inputs .= wf_Selector('newtaskemployee', $allemployee, __('Who should do'), @$telepat_who_should_do['id'], true, $employeeSorting);
        $inputs .= wf_tag('br');
        $inputs .= wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
        $inputs .= ts_TaskTypicalNotesSelector();
        $inputs .= wf_TextArea('newjobnote', '', '', true, '35x5');
        $inputs .= $smsInputs;
        $inputs .= $telegramInputs;
        $inputs .= wf_Submit(__('Create new task'));
        if (!empty($login)) {
            $inputs .= wf_AjaxLoader();
            $inputs .= ' ' . wf_AjaxLink('?module=prevtasks&username=' . $login, wf_img_sized('skins/icon_search_small.gif', __('Previous user tasks')), 'taskshistorycontainer', false, '');
            $inputs .= wf_tag('br');
            $inputs .= wf_tag('div', false, '', 'id="taskshistorycontainer"') . wf_tag('div', true);
        }
        $result = wf_Form("?module=taskman&gotolastid=true", 'POST', $inputs, 'glamour');
        $result .= __('All fields marked with an asterisk are mandatory');
    } else {
        $messages = new UbillingMessageHelper();
        $result = $messages->getStyledMessage(__('No job types and employee available'), 'error');
    }
    return ($result);
}

/**
 * Renders list of all previous user tasks by all time
 * 
 * @param string $login
 * @param string $address
 * @param bool $noFixedWidth
 * @param bool $arrayResult
 * 
 * @return string/array
 */
function ts_PreviousUserTasksRender($login, $address = '', $noFixedWidth = false, $arrayResult = false) {
    $result = '';
    $userTasks = array();
    $telepathyTasks = array();
    $telepathy = new Telepathy(false, true);
    $cache = new UbillingCache();
    $addressLoginsCache = $cache->get('ADDRESSTELEPATHY', 2592000);
    if (empty($addressLoginsCache)) {
        $addressLoginsCache = array();
    }

    $alljobtypes = ts_GetAllJobtypes();
    $allemployee = ts_GetAllEmployee();
    $query = "SELECT * from `taskman` ORDER BY `id` DESC;";
    $rawTasks = simple_queryall($query);
    if (!empty($rawTasks)) {
        if (!$noFixedWidth) {
            $result .= wf_tag('hr');
        }
        foreach ($rawTasks as $io => $each) {
            if (!empty($login)) {
                if ($each['login'] == $login) {
                    $userTasks[$each['id']] = $each;
                }

//address guessing
                if (isset($addressLoginsCache[$each['address']])) {
                    $guessedLogin = $addressLoginsCache[$each['address']];
                } else {
                    $guessedLogin = $telepathy->getLogin($each['address']);
                    if ($guessedLogin) {
                        @$addressLoginsCache[$each['address']] = $guessedLogin;
                    }
                }

                if ($guessedLogin == $login) {
                    if (!isset($userTasks[$each['id']])) {
                        $userTasks[$each['id']] = $each;
                        $telepathyTasks[$each['id']] = $each['id'];
                    }
                }
            } else {
//just address guessing
                if (!empty($address)) {
                    if ($address == $each['address']) {
                        $userTasks[$each['id']] = $each;
                        $telepathyTasks[$each['id']] = $each['id'];
                    }
                }
            }
        }
//cache update
        $cache->set('ADDRESSTELEPATHY', $addressLoginsCache, 2592000);

        if (!$arrayResult) {
            if (!empty($userTasks)) {
                foreach ($userTasks as $io => $each) {
                    $telepathyFlag = (isset($telepathyTasks[$each['id']])) ? wf_tag('sup') . wf_tag('abbr', false, '', 'title="' . __('telepathically guessed') . '"') . '(?)' . wf_tag('abbr', true) . wf_tag('sup', true) : '';
                    $taskColor = ($each['status']) ? 'donetask' : 'undone';
                    $divStyle = ($noFixedWidth) ? 'style="padding: 2px; margin: 2px;"' : 'style="width:400px;"';
                    $result .= wf_tag('div', false, $taskColor, $divStyle);
                    $taskdata = $each['startdate'] . ' - ' . @$alljobtypes[$each['jobtype']] . ', ' . @$allemployee[$each['employee']] . ' ' . $telepathyFlag;
                    $result .= wf_link('?module=taskman&edittask=' . $each['id'], wf_img('skins/icon_edit.gif')) . ' ' . $taskdata;
                    $result .= wf_tag('div', true);
                }
            }
        } else {
            $result = $userTasks;
        }
    }

    return ($result);
}

/**
 * Renders list of all previous build user tasks by all time
 * 
 * @param int $buildId
 * @param bool $noFixedWidth
 * @param bool $arrayResult
 * 
 * @return string/array
 */
function ts_PreviousBuildTasksRender($buildId, $noFixedWidth = false, $arrayResult = false) {
    $result = '';
    $buildTasks = array();
    $tmpResult = array(
        'today' => '',
        'month' => '',
        'year' => '',
    );
    $allJobTypes = ts_GetAllJobtypes();
    $allEmployee = ts_GetAllEmployee();
    $allUserBuilds = zb_AddressGetBuildUsers();
    $curYear = curyear();
    $curMonth = curmonth();
    $curDay = curdate();
    $query = "SELECT * from `taskman` WHERE `startdate` LIKE '" . $curYear . "-%' ORDER BY `id` DESC;";
    $rawTasks = simple_queryall($query);
    if (!empty($rawTasks)) {
        if (!$noFixedWidth) {
            $result .= wf_tag('hr');
        }

        foreach ($rawTasks as $io => $each) {
            if (!empty($each['login'])) {
                if (isset($allUserBuilds[$each['login']])) {
                    $taskBuildId = $allUserBuilds[$each['login']];
                    if ($taskBuildId == $buildId) {
                        $buildTasks[$each['id']] = $each;
                    }
                }
            }
        }


        if (!$arrayResult) {
            if (!empty($buildTasks)) {
                foreach ($buildTasks as $io => $each) {
                    $resultScope = 'year'; //default scope
                    $taskColor = ($each['status']) ? 'donetask' : 'undone';
                    $divStyle = ($noFixedWidth) ? 'style="padding: 2px; margin: 2px;"' : 'style="width:400px;"';
                    $taskdata = $each['startdate'] . ' ' . $each['address'] . ' - ' . @$allJobTypes[$each['jobtype']] . ', ' . @$allEmployee[$each['employee']];
//this month?
                    if (ispos($each['startdate'], $curMonth)) {
                        $resultScope = 'month';
                    }
//or today?
                    if (ispos($each['startdate'], $curDay)) {
                        $resultScope = 'today';
                    }
                    $tmpResult[$resultScope] .= wf_tag('div', false, $taskColor, $divStyle);
                    $tmpResult[$resultScope] .= wf_link('?module=taskman&edittask=' . $each['id'], wf_img('skins/icon_edit.gif')) . ' ' . $taskdata;
                    $tmpResult[$resultScope] .= wf_tag('div', true);
                }
//build result body
                if (!empty($tmpResult['today'])) {
                    $result .= wf_tag('fieldset') . wf_tag('legend') . __('Today') . wf_tag('legend', true)
                            . $tmpResult['today'] . wf_tag('fieldset', true);
                }

                if (!empty($tmpResult['month'])) {
                    $result .= wf_tag('fieldset') . wf_tag('legend') . __('Month') . wf_tag('legend', true)
                            . $tmpResult['month'] . wf_tag('fieldset', true);
                }

                if (!empty($tmpResult['year'])) {
                    $result .= wf_tag('fieldset') . wf_tag('legend') . __('Year') . ' ' . $curYear . wf_tag('legend', true)
                            . $tmpResult['year'] . wf_tag('fieldset', true);
                }
            }
        } else {
            $result = $buildTasks;
        }
    }

    return ($result);
}

/**
 * Returns task creation form unified (use this shit in your further code!)
 * 
 * @param string $address
 * @param string $mobile
 * @param string $phone
 * @param string $login
 * @param string $customData
 * @param string $notes
 * 
 * @return  string
 */
function ts_TaskCreateFormUnified($address, $mobile, $phone, $login = '', $customData = '', $notes = '') {
    global $ubillingConfig;
    $altercfg = $ubillingConfig->getAlter();
    $alljobtypes = ts_GetAllJobtypes();
    $allemployee = ts_GetActiveEmployee();

    if (!empty($alljobtypes) AND ! empty($allemployee)) {

//construct sms sending inputs
        if ($altercfg['SENDDOG_ENABLED']) {
            $smsCheckBox = ($ubillingConfig->getAlterParam('TASKMAN_SMS_PROFILE_CHECK')) ? true : false;
            $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, $smsCheckBox);
// SET checkbed TELEGRAM for creating task from Userprofile if TASKMAN_TELEGRAM_PROFILE_CHECK == 1
            $telegramInputsCheck = (isset($altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) && $altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) ? TRUE : FALSE;
            $telegramInputs = wf_CheckInput('newtasksendtelegram', __('Telegram'), false, $telegramInputsCheck);
        } else {
            $smsInputs = '';
            $telegramInputs = '';
        }

        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

        $inputs = '<!--ugly hack to prevent datepicker autoopen -->';
        $inputs .= wf_tag('input', false, '', 'type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"');
        $inputs .= wf_HiddenInput('createtask', 'true');
        $inputs .= wf_DatePicker('newstartdate');
        $inputs .= wf_TimePickerPreset('newstarttime', '', '', false);
        $inputs .= wf_tag('label') . __('Target date') . $sup . wf_tag('label', true);
        $inputs .= wf_delimiter();
        $inputs .= wf_TextInput('newtaskaddress', __('Address') . $sup, $address, true, '30');
        $inputs .= wf_HiddenInput('newtasklogin', $login);
        $inputs .= wf_tag('br');
        $inputs .= wf_TextInput('newtaskphone', __('Phone') . $sup, $mobile . ' ' . $phone, true, '30');
        $inputs .= wf_tag('br');
        $inputs .= wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
        $inputs .= wf_tag('br');
        $inputs .= wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
        $inputs .= wf_tag('br');
        $inputs .= wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
        $inputs .= ts_TaskTypicalNotesSelector();
        $notesPreset = (!empty($notes)) ? $notes : '';

        $inputs .= wf_TextArea('newjobnote', '', $notesPreset, true, '35x5');
        if (!empty($customData)) {
            $inputs .= $customData;
        }
        $inputs .= $smsInputs;
        $inputs .= $telegramInputs;
        $inputs .= wf_Submit(__('Create new task'));
        $result = wf_Form("?module=taskman&gotolastid=true", 'POST', $inputs, 'glamour');
        $result .= __('All fields marked with an asterisk are mandatory');
    } else {
        $messages = new UbillingMessageHelper();
        $result = $messages->getStyledMessage(__('No job types and employee available'), 'error');
    }
    return ($result);
}

/**
 * Returns task creation form for sigreq usage
 * DEPRECATED: use ts_TaskCreateFormUnified instead this
 * 
 * @param string $address
 * @param string $phone
 * @return string
 */
function ts_TaskCreateFormSigreq($address, $phone) {
    global $ubillingConfig;
    $altercfg = $ubillingConfig->getAlter();
    $alljobtypes = ts_GetAllJobtypes();
    $allemployee = ts_GetActiveEmployee();

    if (!empty($alljobtypes) AND ! empty($allemployee)) {
//construct sms sending inputs
        if ($altercfg['SENDDOG_ENABLED']) {
            $smsCheckBox = ($ubillingConfig->getAlterParam('TASKMAN_SMS_PROFILE_CHECK')) ? true : false;
            $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, $smsCheckBox);
// SET checkbed TELEGRAM for creating task from Userprofile if TASKMAN_TELEGRAM_PROFILE_CHECK == 1
            $telegramInputsCheck = (isset($altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) && $altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) ? TRUE : FALSE;
            $telegramInputs = wf_CheckInput('newtasksendtelegram', __('Telegram'), false, $telegramInputsCheck);
        } else {
            $smsInputs = '';
            $telegramInputs = '';
        }

        $inputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
        $inputs .= wf_HiddenInput('createtask', 'true');
        $inputs .= wf_DatePicker('newstartdate');
        $inputs .= wf_TimePickerPreset('newstarttime', '', '', false);
        $inputs .= wf_tag('label') . __('Target date') . wf_tag('sup') . '*' . wf_tag('sup', true) . wf_tag('label', true);
        $inputs .= wf_delimiter();
        $inputs .= wf_TextInput('newtaskaddress', __('Address') . '<sup>*</sup>', $address, true, '30');
//hidden for new task login input
        $inputs .= wf_HiddenInput('newtasklogin', '');
        $inputs .= wf_tag('br');
        $inputs .= wf_TextInput('newtaskphone', __('Phone') . '<sup>*</sup>', $phone, true, '30');
        $inputs .= wf_tag('br');
        $inputs .= wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
        $inputs .= wf_tag('br');
        $inputs .= wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
        $inputs .= wf_tag('br');
        $inputs .= wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
        $inputs .= ts_TaskTypicalNotesSelector();
        $inputs .= wf_TextArea('newjobnote', '', '', true, '35x5');
        $inputs .= $smsInputs;
        $inputs .= $telegramInputs;
        $inputs .= wf_Submit(__('Create new task'));
        $result = wf_Form("?module=taskman&gotolastid=true", 'POST', $inputs, 'glamour');
        $result .= __('All fields marked with an asterisk are mandatory');
    } else {
        $messages = new UbillingMessageHelper();
        $result = $messages->getStyledMessage(__('No job types and employee available'), 'error');
    }
    return ($result);
}

/**
 * Returns taskman controls
 * 
 * @return string
 */
function ts_ShowPanel() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $branchCurseFlag = ts_isMeBranchCursed();

    $tools = '';
    $result = '';
    if (!$branchCurseFlag) {
        $createform = ts_TaskCreateForm();
        $result .= wf_modal(wf_img('skins/add_icon.png') . ' ' . __('Create task'), __('Create task'), $createform, 'ubButton', '450', '550');
    }
    $result .= wf_Link('?module=taskman&show=undone', wf_img('skins/undone_icon.png') . ' ' . __('Undone tasks'), false, 'ubButton');
    $result .= wf_Link('?module=taskman&show=done', wf_img('skins/done_icon.png') . ' ' . __('Done tasks'), false, 'ubButton');
    $result .= wf_Link('?module=taskman&show=all', wf_img('skins/icon_calendar.gif') . ' ' . __('All tasks'), false, 'ubButton');

    if (cfr('TASKMANSEARCH')) {
        $tools .= wf_Link('?module=tasksearch', web_icon_search() . ' ' . __('Tasks search'), false, 'ubButton');
    }

    if (cfr('TASKMANTRACK')) {
        $tools .= wf_Link('?module=taskmantrack', wf_img('skins/track_icon.png') . ' ' . __('Tracking'), false, 'ubButton');
    }

    if (cfr('TASKMANTIMING')) {
        $tools .= wf_Link('?module=taskmantiming', wf_img('skins/clock.png') . ' ' . __('Task timing report'), false, 'ubButton');
    }

    if (cfr('TASKMANADMREP')) {
        $tools .= wf_Link('?module=taskmanadmreport', wf_img('skins/mcdonalds.png') . ' ' . __('Hataraku Maou-sama!'), false, 'ubButton');
    }

    if (cfr('TASKMANNWATCHLOG')) {
        $tools .= wf_Link('?module=taskman&show=logs', wf_img('skins/icon_note.gif') . ' ' . __('Logs'), false, 'ubButton');
    }


    if (cfr('TASKMANSEARCH')) {
        $tools .= wf_Link(TasksDuplicates::URL_ME, wf_img('skins/icon_clone.png') . ' ' . __('Tasks duplicates'), false, 'ubButton');
    }


    $tools .= wf_Link('?module=report_taskmanmap', wf_img('skins/swmapsmall.png') . ' ' . __('Tasks map'), false, 'ubButton');
    $tools .= wf_Link('?module=taskman&print=true', wf_img('skins/icon_print.png') . ' ' . __('Tasks printing'), false, 'ubButton');

    if (cfr(('SALARY'))) {
        if ($ubillingConfig->getAlterParam('SALARY_ENABLED')) {
            $tools .= wf_Link(TasksLaborTime::URL_ME, wf_img('skins/icon_time_small.png') . ' ' . __('Employee timeline'), false, 'ubButton');
        }
    }


    if (!$branchCurseFlag) {
        $result .= wf_modalAuto(web_icon_extended() . ' ' . __('Tools'), __('Tools'), $tools, 'ubButton');
    }

//show type selector
    $whoami = whoami();
    $employeeid = ts_GetEmployeeByLogin($whoami);
    $advFiltersEnabled = $ubillingConfig->getAlterParam('TASKMAN_ADV_FILTERS');

    if ($employeeid OR $advFiltersEnabled) {
        $result .= wf_delimiter();
        $inputs = '';

        if ($employeeid) {
            $curselected = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : '';
            $displayTypes = array(
                'all' => __('Show tasks for all users'),
                'onlyme' => __('Show only mine tasks')
            );
//some other employee
            $activeEmployeeTmp = ts_GetActiveEmployee();
            if (!empty($activeEmployeeTmp)) {
                foreach ($activeEmployeeTmp as $actId => $empName) {
                    $displayTypes['displayempid' . $actId] = $empName;
                }
            }
            $inputs .= wf_Selector('displaytype', $displayTypes, '', $curselected, false, '', '', 'col-1-2-occupy');
        }

        if ($advFiltersEnabled) {
            $inputs .= ts_AdvFiltersControls();
        }

        $formClasses = ($advFiltersEnabled ? 'glamour form-grid-6cols form-grid-6cols-label-right' : 'glamour form-grid-2cols');
        $submitClasses = ($advFiltersEnabled ? 'ubButton' : 'inline-grid-button');
        $submitOpts = 'style="width: 100%";';
        $inputs .= wf_SubmitClassed(true, $submitClasses, '', __('Show'), '', $submitOpts);
        $showTypeForm = wf_Form('', 'POST', $inputs, $formClasses);
        if (!$branchCurseFlag) {
            $result .= $showTypeForm;
        }
    }

    return ($result);
}

/**
 * Stores SMS for some employee for further sending with senddog run
 * 
 * @param int $employeeid
 * @param string $message
 * @return array
 * @throws Exception
 */
function ts_SendSMS($employeeid, $message) {
    $query = "SELECT `mobile`,`name` from `employee` WHERE `id`='" . $employeeid . "'";
    $empData = simple_query($query);
    $mobile = $empData['mobile'];
    $result = array();
    $sms = new UbillingSMS();
    if (!empty($mobile)) {
        if (ispos($mobile, '+')) {
            $sms->sendSMS($mobile, $message, true, 'TASKMAN');
            $result['number'] = $mobile;
            $result['message'] = $message;
        } else {
            throw new Exception('BAD_MOBILE_FORMAT ' . $mobile);
        }
    }
    return ($result);
}

/**
 * Marks some task as done
 * 
 * @return void
 */
function ts_TaskIsDone() {
    $editid = vf($_POST['changetask']);
    simple_update_field('taskman', 'enddate', $_POST['editenddate'], "WHERE `id`='" . $editid . "'");
    simple_update_field('taskman', 'employeedone', $_POST['editemployeedone'], "WHERE `id`='" . $editid . "'");
    simple_update_field('taskman', 'donenote', $_POST['editdonenote'], "WHERE `id`='" . $editid . "'");
    simple_update_field('taskman', 'change_admin', $_POST['change_admin'], "WHERE `id`='" . $editid . "'");
    simple_update_field('taskman', 'status', '1', "WHERE `id`='" . $editid . "'");
    $logQuery = "INSERT INTO `taskmandone` (`id`,`taskid`,`date`) VALUES ";
    $logQuery .= "(NULL,'" . $editid . "','" . curdatetime() . "');";
    nr_query($logQuery);
    $LogTaskArr = array('editenddate' => $_POST['editenddate'], 'editemployeedone' => $_POST['editemployeedone'], 'editdonenote' => $_POST['editdonenote']);
    $queryLogTask = ("
        INSERT INTO `taskmanlogs` (`id`, `taskid`, `date`, `admin`, `ip`, `event`, `logs`)
        VALUES (NULL, '" . $editid . "', CURRENT_TIMESTAMP, '" . whoami() . "', '" . @$_SERVER['REMOTE_ADDR'] . "', 'done', '" . serialize($LogTaskArr) . "')
    ");
    nr_query($queryLogTask);
    log_register('TASKMAN DONE [' . $editid . ']');
}

/**
 * Stores Telegram message for some employee
 * 
 * @param int $employeeid
 * @param string $message
 * @param array $taskdata
 * 
 * @return array
 */
function ts_SendTelegram($employeeid, $message, $taskdata = array()) {
    $employeeid = vf($employeeid, 3);
    $query = "SELECT `telegram`,`name` from `employee` WHERE `id`='" . $employeeid . "'";
    $empData = simple_query($query);
    $chatId = $empData['telegram'];
    $telegram = new UbillingTelegram();
    $result = array();
    if (!empty($chatId)) {
        $telegram->sendMessage($chatId, $message, false, 'TASKMAN');
        $result['chatid'] = $chatId;
        $result['message'] = $message;
//optional geo sending
        if (!empty($taskdata)) {
            if (isset($taskdata['geo'])) {
                ts_SendTelegramVenue($chatId, @$taskdata['jobtype'], @$taskdata['address'], $taskdata['geo']);
            }
        }
    }
    return ($result);
}

/**
 * Sends task location to some chatid
 * 
 * @param string $chatId
 * @param string $title
 * @param string $address
 * @param string $geo
 * 
 * @return void
 */
function ts_SendTelegramVenue($chatId, $title, $address, $geo) {
    global $ubillingConfig;
    if ($ubillingConfig->getAlterParam('TASKMAN_SEND_LOCATION')) {
        $telegram = new UbillingTelegram();
        $message = 'title:{' . $title . '}address:(' . $address . ')sendVenue:[' . $geo . ']';
        $telegram->sendMessage($chatId, $message, false, 'TASKMANGEO');
    }
}

/**
 * Flushes sms data for some task
 * 
 * @param int $taskid
 * 
 * @return void
 */
function ts_FlushSMSData($taskid) {
    $taskid = vf($taskid, 3);
    $query = "UPDATE `taskman` SET `smsdata`=NULL WHERE `id`='" . $taskid . "';";
    nr_query($query);
    $queryLogTask = ("
        INSERT INTO `taskmanlogs` (`id`, `taskid`, `date`, `admin`, `ip`, `event`, `logs`)
        VALUES (NULL, '" . $taskid . "', CURRENT_TIMESTAMP, '" . whoami() . "', '" . @$_SERVER['REMOTE_ADDR'] . "', 'flushsms', '')
    ");
    nr_query($queryLogTask);
    log_register('TASKMAN FLUSH SMS [' . $taskid . ']');
}

/**
 * Creates new task in database
 * 
 * @param string $startdate
 * @param string $starttime
 * @param string $address
 * @param string $login
 * @param string $phone
 * @param int $jobtypeid
 * @param int $employeeid
 * @param string $jobnote
 * 
 * @return void
 */
function ts_CreateTask($startdate, $starttime, $address, $login, $phone, $jobtypeid, $employeeid, $jobnote) {
    global $ubillingConfig;

    $curdate = curdatetime();
    $admin = whoami();
    $address = str_replace('\'', '`', $address);
    $address = mysql_real_escape_string($address);
    $address = trim($address);
    $login = mysql_real_escape_string($login);
    $phone = mysql_real_escape_string($phone);
    $startdate = mysql_real_escape_string($startdate);
    $jobSendTime = date("H:i", strtotime($curdate));
    $taskDataGeo = array();

    if (!empty($starttime)) {
        $starttimeRaw = $starttime;
        $starttime = "'" . mysql_real_escape_string($starttime) . "'";
    } else {
        $starttimeRaw = '';
        $starttime = 'NULL';
    }
    $jobtypeid = vf($jobtypeid, 3);
    $employeeid = vf($employeeid, 3);
    $jobnote = mysql_real_escape_string($jobnote);

    $smsData = 'NULL';
//store messages for backround processing via senddog for SMS
    if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
        $jobtype = ts_GetAllJobtypes();
//SMS sending
        if (isset($_POST['newtasksendsms'])) {
            $newSmsText = $address . ' ' . $phone . ' ' . @$jobtype[$jobtypeid] . ' ' . $jobnote . $starttimeRaw;
            $smsDataRaw = ts_SendSMS($employeeid, $newSmsText);
            if (!empty($smsDataRaw)) {
                $smsData = serialize($smsDataRaw);
                $smsData = "'" . base64_encode($smsData) . "'";
            }
        }
    }

    $query = "INSERT INTO `taskman` (`id` , `date` , `address` , `login` , `jobtype` , `jobnote` , `phone` , `employee` , `employeedone` ,`donenote` , `startdate` ,`starttime`, `enddate` , `admin` , `status`,`smsdata`)
              VALUES (NULL , '" . $curdate . "', '" . $address . "', '" . $login . "', '" . $jobtypeid . "', '" . $jobnote . "', '" . $phone . "', '" . $employeeid . "',NULL, NULL , '" . $startdate . "'," . $starttime . ",NULL , '" . $admin . "', '0'," . $smsData . ");";
    nr_query($query);

    $taskid = simple_query("SELECT LAST_INSERT_ID() as id");
    $taskid = $taskid['id'];

//store messages for backround processing via senddog for Telegramm
    if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
        if (!empty($login)) {
            $userData = zb_UserGetAllData($login);
        }
//Telegram sending
        if (isset($_POST['newtasksendtelegram'])) {
            $newTelegramText = __('ID') . ': ' . $taskid . '\r\n';
            $newTelegramText .= __('Address') . ': ' . $address . '\r\n';
            if (!empty($login)) {
                $newTelegramText .= __('Real Name') . ': ' . @$userData[$login]['realname'] . '\r\n';
            }
            $newTelegramText .= __('Job type') . ': ' . @$jobtype[$jobtypeid] . '\r\n';
            $newTelegramText .= __('Phone') . ': ' . $phone . '\r\n';
            $newTelegramText .= __('Job note') . ': ' . $jobnote . '\r\n';
            $newTelegramText .= __('Target date') . ': ' . $startdate . ' ' . $starttimeRaw . '\r\n';
            $newTelegramText .= __('Create date') . ': ' . $jobSendTime . '\r\n';
            if (!empty($login)) {

                $userCableSeal = '';
                if ($ubillingConfig->getAlterParam('CONDET_ENABLED')) {
                    $userCondet = new ConnectionDetails();
                    $userCableSeal = $userCondet->getByLogin($login);
                    if (!empty($userCableSeal)) {
                        $userCableSeal = __('Cable seal') . ': ' . $userCableSeal['seal'] . '\r\n'; // kabelnyi tyulenchik
                    }
                }

                $newTelegramText .= __('Login') . ': ' . $login . '\r\n';
                $newTelegramText .= __('Password') . ': ' . @$userData[$login]['Password'] . '\r\n';
                $newTelegramText .= __('Contract') . ': ' . @$userData[$login]['contract'] . '\r\n';
                $newTelegramText .= __('IP') . ': ' . @$userData[$login]['ip'] . '\r\n';
                $newTelegramText .= __('MAC') . ': ' . @$userData[$login]['mac'] . '\r\n';
                $newTelegramText .= __('Tariff') . ': ' . @$userData[$login]['Tariff'] . '\r\n';

//data preprocessing for geo sending
                if (@isset($userData[$login]['geo'])) {
                    if (!empty($userData[$login]['geo'])) {
                        $taskDataGeo['jobtype'] = @$jobtype[$jobtypeid];
                        $taskDataGeo['address'] = $address;
                        $taskDataGeo['geo'] = $userData[$login]['geo'];
                    }
                }

                if ($ubillingConfig->getAlterParam('SWITCHPORT_IN_PROFILE')) {
                    $allAssigns = zb_SwitchesGetAssignsAll();
                    if (isset($allAssigns[$login])) {
                        $newTelegramText .= __('Switch') . ': ' . @$allAssigns[$login]['label'] . '\r\n';
                    }
                }

                if (!empty($userCableSeal)) {
                    $newTelegramText .= $userCableSeal;
                }
            }

//some hack to append UKV users cable seals and maybe something else
            if (wf_CheckPost(array('unifiedformtelegramappend'))) {
                $newTelegramText .= $_POST['unifiedformtelegramappend'];
            }

//appending task direct URL to task
            $fullBillingUrl = $ubillingConfig->getAlterParam('FULL_BILLING_URL');
            $appendTaskLinkFlag = $ubillingConfig->getAlterParam('TASKMAN_SEND_TASKURL');
            if (!empty($fullBillingUrl) AND $appendTaskLinkFlag) {
                $newTelegramText .= '<a href="' . $fullBillingUrl . '/?module=taskman&edittask=' . $taskid . '">🔍 ' . __('View task') . '</a> parseMode:{html}';
            }


//telegram messages sending
            ts_SendTelegram($employeeid, $newTelegramText, $taskDataGeo);
        }
    }

//flushing darkvoid
    $darkVoid = new DarkVoid();
    $darkVoid->flushCache();

    $queryLogTask = ("
        INSERT INTO `taskmanlogs` (`id`, `taskid`, `date`, `admin`, `ip`, `event`, `logs`) 
        VALUES (NULL, '" . $taskid . "', CURRENT_TIMESTAMP, '" . whoami() . "', '" . @$_SERVER['REMOTE_ADDR'] . "', 'create', '" . serialize($address) . "')
    ");
    nr_query($queryLogTask);

    log_register('TASKMAN CREATE [' . $taskid . '] `' . $address . '`');
}

/**
 * Returns array of task data by its ID
 * 
 * @param int $taskid
 * @return array
 */
function ts_GetTaskData($taskid) {
    $taskid = vf($taskid, 3);
    $query = "SELECT * from `taskman` WHERE `id`='" . $taskid . "'";
    $result = simple_query($query);
    return ($result);
}

/**
 * Returns task editing form
 * 
 * @global object $ubillingConfig
 * @param int $taskid
 * @return string
 */
function ts_TaskModifyForm($taskid) {
    global $ubillingConfig;
    $altercfg = $ubillingConfig->getAlter();
    $taskid = vf($taskid, 3);
    $taskdata = ts_GetTaskData($taskid);
    $result = '';
    $allemployee = ts_GetAllEmployee();
    $activeemployee = ts_GetActiveEmployee();
    $alljobtypes = ts_GetAllJobtypes();
//construct sms sending inputs
    if ($altercfg['SENDDOG_ENABLED']) {
        $smsCheckBox = ($ubillingConfig->getAlterParam('TASKMAN_SMS_PROFILE_CHECK')) ? true : false;
        $smsInputs = wf_CheckInput('changetasksendsms', __('Send SMS'), false, $smsCheckBox);
// SET checkbed TELEGRAM for creating task from Userprofile if TASKMAN_TELEGRAM_PROFILE_CHECK == 1
        $telegramInputsCheck = (isset($altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) && $altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) ? TRUE : FALSE;
        $telegramInputs = wf_CheckInput('changetasksendtelegram', __('Telegram'), false, $telegramInputsCheck);
    } else {
        $smsInputs = '';
        $telegramInputs = '';
    }
    if (!empty($taskdata)) {
        $inputs = wf_HiddenInput('modifytask', $taskid);
        $inputs .= '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhackmod" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
        if (cfr('TASKMANDATE')) {
            $inputs .= wf_DatePickerPreset('modifystartdate', $taskdata['startdate']);
        } else {
            $inputs .= wf_HiddenInput('modifystartdate', $taskdata['startdate']);
        }
        $inputs .= wf_TimePickerPreset('modifystarttime', $taskdata['starttime'], '', false);
        $inputs .= wf_tag('label') . __('Target date') . wf_tag('sup') . '*' . wf_tag('sup', true) . wf_tag('label', true);
        $inputs .= wf_delimiter();
        $inputs .= wf_tag('br');
        if ($altercfg['SEARCHADDR_AUTOCOMPLETE']) {
            $alladdress = zb_AddressGetFulladdresslistCached();
//Commented because significantly reduces performance. Waiting for feedback.
//natsort($alladdress);
            $inputs .= wf_AutocompleteTextInput('modifytaskaddress', $alladdress, __('Address') . '<sup>*</sup>', $taskdata['address'], true, '30');
        } else {
            $inputs .= wf_TextInput('modifytaskaddress', __('Address') . '<sup>*</sup>', $taskdata['address'], true, '30');
        }
        $inputs .= wf_tag('br');
//custom login text input
        $inputs .= wf_TextInput('modifytasklogin', __('Login'), $taskdata['login'], true, 30);
        $inputs .= wf_tag('br');
        $inputs .= wf_TextInput('modifytaskphone', __('Phone') . '<sup>*</sup>', $taskdata['phone'], true, '30');
        $inputs .= wf_tag('br');
        $inputs .= wf_Selector('modifytaskjobtype', $alljobtypes, __('Job type'), $taskdata['jobtype'], true);
        $inputs .= wf_tag('br');
        $inputs .= wf_Selector('modifytaskemployee', $activeemployee, __('Who should do'), $taskdata['employee'], true);
        $inputs .= wf_tag('br');
        $inputs .= wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
        $inputs .= wf_TextArea('modifytaskjobnote', '', $taskdata['jobnote'], true, '35x5');
        $inputs .= $smsInputs;
        $inputs .= $telegramInputs;
        $inputs .= wf_Submit(__('Save'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');
        $result .= __('All fields marked with an asterisk are mandatory');
    }

    return ($result);
}

/**
 * Updates task params in database
 * 
 * @param int $taskid
 * @param string $startdate
 * @param string $starttime
 * @param string $address
 * @param string $login
 * @param string $phone
 * @param int $jobtypeid
 * @param int $employeeid
 * @param string $jobnote
 * 
 * @return void
 */
function ts_ModifyTask($taskid, $startdate, $starttime, $address, $login, $phone, $jobtypeid, $employeeid, $jobnote) {
    global $ubillingConfig;
    $taskid = vf($taskid, 3);
    $startdate = mysql_real_escape_string($startdate);
    $starttimeRaw = (!empty($starttime)) ? $starttime : '';
    $starttime = (!empty($starttime)) ? "'" . date("H:i:s", strtotime(mysql_real_escape_string($starttime))) . "'" : 'NULL';

    $address = str_replace('\'', '`', $address);
    $address = mysql_real_escape_string($address);
    $login = mysql_real_escape_string($login);
    $phone = mysql_real_escape_string($phone);
    $jobtypeid = vf($jobtypeid, 3);
    $employeeid = vf($employeeid, 3);
    $org_taskdata = ts_GetTaskData($taskid);
    $jobtype = ts_GetAllJobtypes();

    simple_update_field('taskman', 'startdate', $startdate, "WHERE `id`='" . $taskid . "'");
    nr_query("UPDATE `taskman` SET `starttime` = " . $starttime . " WHERE `id`='" . $taskid . "'"); //That shit for preventing quotes. Dont touch this.
    simple_update_field('taskman', 'address', $address, "WHERE `id`='" . $taskid . "'");
    simple_update_field('taskman', 'login', $login, "WHERE `id`='" . $taskid . "'");
    simple_update_field('taskman', 'phone', $phone, "WHERE `id`='" . $taskid . "'");
    simple_update_field('taskman', 'jobtype', $jobtypeid, "WHERE `id`='" . $taskid . "'");
    simple_update_field('taskman', 'employee', $employeeid, "WHERE `id`='" . $taskid . "'");
    simple_update_field('taskman', 'jobnote', $jobnote, "WHERE `id`='" . $taskid . "'");

    $smsData = 'NULL';
//SMS sending
    if (isset($_POST['changetasksendsms'])) {
        $newSmsText = $address . ' ' . $phone . ' ' . @$jobtype[$jobtypeid] . ' ' . $jobnote . $starttimeRaw;
        $smsDataRaw = ts_SendSMS($employeeid, $newSmsText);
        if (!empty($smsDataRaw)) {
            $smsData = serialize($smsDataRaw);
            $smsData = "'" . base64_encode($smsData) . "'";
        }
    }

//Telegram sending
    if (isset($_POST['changetasksendtelegram'])) {
        if (!empty($login)) {
            $userData = zb_UserGetAllData($login);
        }
        $newTelegramText = __('ID') . ': ' . $taskid . '\r\n';
        $newTelegramText .= __('Address') . ': ' . $address . '\r\n';
        if (!empty($login)) {
            $newTelegramText .= __('Real Name') . ': ' . @$userData[$login]['realname'] . '\r\n';
        }
        $newTelegramText .= __('Job type') . ': ' . @$jobtype[$jobtypeid] . '\r\n';
        $newTelegramText .= __('Phone') . ': ' . $phone . '\r\n';
        $newTelegramText .= __('Job note') . ': ' . $jobnote . '\r\n';
        $newTelegramText .= __('Target date') . ': ' . $startdate . ' ' . $starttimeRaw . '\r\n';
        if (!empty($login)) {
            $userCableSeal = '';
            if ($ubillingConfig->getAlterParam('CONDET_ENABLED')) {
                $userCondet = new ConnectionDetails();
                $userCableSeal = $userCondet->getByLogin($login);
                if (!empty($userCableSeal)) {
                    $userCableSeal = __('Cable seal') . ': ' . $userCableSeal['seal'] . '\r\n';
                }
            }

            $newTelegramText .= __('Login') . ': ' . $login . '\r\n';
            $newTelegramText .= __('Password') . ': ' . @$userData[$login]['Password'] . '\r\n';
            $newTelegramText .= __('Contract') . ': ' . @$userData[$login]['contract'] . '\r\n';
            $newTelegramText .= __('IP') . ': ' . @$userData[$login]['ip'] . '\r\n';
            $newTelegramText .= __('MAC') . ': ' . @$userData[$login]['mac'] . '\r\n';
            $newTelegramText .= __('Tariff') . ': ' . @$userData[$login]['Tariff'] . '\r\n';

            if ($ubillingConfig->getAlterParam('SWITCHPORT_IN_PROFILE')) {
                $allAssigns = zb_SwitchesGetAssignsAll();
                if (isset($allAssigns[$login])) {
                    $newTelegramText .= __('Switch') . ': ' . @$allAssigns[$login]['label'] . '\r\n';
                }
            }

            if (!empty($userCableSeal)) {
                $newTelegramText .= $userCableSeal;
            }
        }

//appending task direct URL to task
        $fullBillingUrl = $ubillingConfig->getAlterParam('FULL_BILLING_URL');
        $appendTaskLinkFlag = $ubillingConfig->getAlterParam('TASKMAN_SEND_TASKURL');
        if (!empty($fullBillingUrl) AND $appendTaskLinkFlag) {
            $newTelegramText .= '<a href="' . $fullBillingUrl . '/?module=taskman&edittask=' . $taskid . '">🔍 ' . __('View task') . '</a> parseMode:{html}';
        }

        ts_SendTelegram($employeeid, $newTelegramText);
    }

// Unset parametr, that we dont diff
    unset($org_taskdata['date'], $org_taskdata['employeedone'], $org_taskdata['donenote'], $org_taskdata['enddate'], $org_taskdata['admin'], $org_taskdata['status'], $org_taskdata['change_admin'], $org_taskdata['smsdata']);
    $new_taskdata = array(
        'id' => $taskid,
        'address' => $address,
        'login' => $login,
        'jobtype' => $jobtypeid,
        'jobnote' => $jobnote,
        'phone' => $phone,
        'employee' => $employeeid,
        'startdate' => $startdate,
        'starttime' => date("H:i:s", strtotime($starttimeRaw))
    );
    $cahged_taskdata = (array_diff_assoc($org_taskdata, $new_taskdata));
    $log_data = '';
    $log_data_arr = array();
    foreach ($cahged_taskdata as $par => $value) {
        $log_data .= __($par) . ':`' . $value . '` => `' . $new_taskdata[$par] . '`';
        $log_data_arr[$par]['old'] = $value;
        $log_data_arr[$par]['new'] = $new_taskdata[$par];
    }
    $queryLogTask = ("
        INSERT INTO `taskmanlogs` (`id`, `taskid`, `date`, `admin`, `ip`, `event`, `logs`)
        VALUES (NULL, '" . $taskid . "', CURRENT_TIMESTAMP, '" . whoami() . "', '" . @$_SERVER['REMOTE_ADDR'] . "', 'modify', '" . serialize($log_data_arr) . "')
    ");
    nr_query($queryLogTask);
    log_register('TASKMAN MODIFY [' . $taskid . '] `' . $address . '`' . ' CHANGED [' . $log_data . ']');
}

/**
 * Returns all available admin_login=>employee name pairs
 * 
 * @return string serialized array
 */
function ts_GetAllEmployeeLogins() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $namesFlag = (@$altCfg['ADMIN_NAMES']) ? true : false;
    $result = array();
    $query = "SELECT `admlogin`,`name` from `employee`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            if (!empty($each['admlogin'])) {
                if ($namesFlag) {
                    $result[$each['admlogin']] = $each['name'];
                } else {
                    $result[$each['admlogin']] = $each['admlogin'];
                }
            }
        }
    }
    $result = serialize($result);
    return ($result);
}

/**
 * Returns all available admin_login=>employee name pairs from cache if available
 * 
 * @return string serialized array
 */
function ts_GetAllEmployeeLoginsCached() {
    $result = '';
    $cache = new UbillingCache();
    $cacheTime = 86400;
    $result = $cache->getCallback('EMPLOYEE_LOGINS', function () {
        return (ts_GetAllEmployeeLogins());
    }, $cacheTime);

    return ($result);
}

/**
 * Returns all available admin_login=>employee name pairs from cache if available
 * 
 * @return array
 */
function ts_GetAllEmployeeLoginsAssocCached() {
    $result = array();
    $raw = ts_GetAllEmployeeLoginsCached();
    if (!empty($raw)) {
        $result = unserialize($raw);
    }
    return ($result);
}

/**
 * Checks some task for duplicates by login/address fields
 * 
 * @param array $taskData
 * @param int $optionValue 1 - today duplicates, any other digit - days around
 * 
 * @return void
 */
function ts_CheckDailyDuplicates($taskData, $optionValue = 1) {
    if (!empty($taskData)) {
        if (!empty($taskData['startdate'])) {
            $allTasksDuplicates = array();
            $loginDuplicates = array();
            $addressDuplicates = array();
            $result = '';
            $tasksDb = new NyanORM('taskman');
            if (!empty($taskData['login'])) {
                $tasksDb->where('id', '!=', $taskData['id']);
//just the same date
                if ($optionValue == 1) {
                    $tasksDb->where('startdate', 'LIKE', $taskData['startdate'] . '%');
                } else {
//configurable days count interval
                    $startDateTimestamp = strtotime($taskData['startdate']);
                    $daysOffset = round($optionValue * 86400); //int
                    $dayBegin = date("Y-m-d", ($startDateTimestamp - $daysOffset)); // -X days
                    $dayEnd = date("Y-m-d", ($startDateTimestamp + $daysOffset)); // +X days
                    $tasksDb->where('startdate', 'BETWEEN', $dayBegin . "' AND '" . $dayEnd);
                }


                $tasksDb->where('login', '=', $taskData['login']);
                $loginDuplicates = $tasksDb->getAll('id');
            }


            if (!empty($taskData['address'])) {
                $tasksDb->where('id', '!=', $taskData['id']);
//just the same date
                if ($optionValue == 1) {
                    $tasksDb->where('startdate', 'LIKE', $taskData['startdate'] . '%');
                } else {
//configurable days count interval
                    $startDateTimestamp = strtotime($taskData['startdate']);
                    $daysOffset = round($optionValue * 86400); //int
                    $dayBegin = date("Y-m-d", ($startDateTimestamp - $daysOffset)); // -X days
                    $dayEnd = date("Y-m-d", ($startDateTimestamp + $daysOffset)); // +X days
                    $tasksDb->where('startdate', 'BETWEEN', $dayBegin . "' AND '" . $dayEnd);
                }

                $tasksDb->where('address', '=', $taskData['address']);
                $addressDuplicates = $tasksDb->getAll('id');
            }


            $allTasksDuplicates = $loginDuplicates + $addressDuplicates;

            if (!empty($allTasksDuplicates)) {
                $messages = new UbillingMessageHelper();
                foreach ($allTasksDuplicates as $io => $each) {
                    $taskLink = ' ' . __('ID') . wf_Link('?module=taskman&edittask=' . $each['id'], '[' . $each['id'] . '] ');
                    $result .= $messages->getStyledMessage(__('Duplicate') . $taskLink . $each['startdate'] . ' ' . $each['address'], 'warning');
                }

                $windowLabel = __('Tasks with duplicate address created for same day');
                if ($optionValue > 1) {
                    $windowLabel .= ' ' . __('or in') . ' +-' . $optionValue . ' ' . __('days');
                }
                show_window($windowLabel, $result);
            }
        }
    }
}

/**
 * Shows task editing/management form aka task profile
 * 
 * @global object $ubillingConfig
 * @param int $taskid
 * 
 * @return void
 */
function ts_TaskChangeForm($taskid) {
    global $ubillingConfig;

    $altercfg = $ubillingConfig->getAlter();
    $taskid = vf($taskid, 3);
    $taskdata = ts_GetTaskData($taskid);
    $result = '';
    $allemployee = ts_GetAllEmployee();
    $activeemployee = ts_GetActiveEmployee();
    @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
    $alljobtypes = ts_GetAllJobtypes();
    $messages = new UbillingMessageHelper();
    $smsData = '';
    $branchName = '';

    if (!empty($taskdata)) {
//not done task
        if (empty($taskdata['login'])) {
            $login_detected = ts_DetectUserByAddress($taskdata['address']);
            if ($login_detected) {
                $addresslink = wf_Link("?module=userprofile&username=" . $login_detected, web_profile_icon() . ' ' . $taskdata['address'], false);
                $loginType = ' (' . __('telepathically guessed') . ')';
                $taskLogin = $login_detected;
            } else {
                $addresslink = $taskdata['address'];
                $loginType = ' (' . __('No') . ' - ' . __('telepathically guessed') . ')';
                $taskLogin = '';
            }
        } else {
            $addresslink = wf_Link("?module=userprofile&username=" . $taskdata['login'], web_profile_icon() . ' ' . $taskdata['address'], false);
            $taskLogin = $taskdata['login'];
            $loginType = '';
        }

//job generation form
        if ($taskLogin) {
            $jobgencheckbox = wf_CheckInput('generatejob', __('Generate job performed for this task'), true, true);
            $jobgencheckbox .= wf_HiddenInput('generatelogin', $taskLogin);
            $jobgencheckbox .= wf_HiddenInput('generatejobid', $taskdata['jobtype']);
            $jobgencheckbox .= wf_delimiter();
        } else {
            $jobgencheckbox = '';
        }

//modify form handlers
        $modform = '';
        if (cfr('TASKMANTRACK')) {
            $modform .= wf_Link('?module=taskmantrack&trackid=' . $taskid, wf_img('skins/track_icon.png', __('Track this task'))) . ' ';
        }
//warehouse mass-outcome helper
        if (cfr('WAREHOUSEOUTRESERVE') OR cfr('WAREHOUSEOUT')) {
            if ($altercfg['WAREHOUSE_ENABLED']) {
                if ($altercfg['TASKMAN_WAREHOUSE_HLPR']) {
                    if ($taskdata['status'] == 0) {
                        $massOutUrl = Warehouse::URL_ME . '&' . Warehouse::URL_RESERVE . '&massoutemployee=' . $taskdata['employee'] . '&taskidpreset=' . $taskid;
                        $modform .= wf_Link($massOutUrl, wf_img('skins/drain_icon.png', __('Mass outcome')), false, '', 'target="_BLANK"');
                    }
                }
            }
        }
//task editing limitations
        if (cfr('TASKMANEDITTASK')) {
            $modform .= wf_modal(web_edit_icon(), __('Edit'), ts_TaskModifyForm($taskid), '', '450', '550') . ' ';
        }

//modform end
//extracting sms data
        if (!empty($taskdata['smsdata'])) {
            $rawSmsData = $taskdata['smsdata'];
            $rawSmsData = base64_decode($rawSmsData);
            $rawSmsData = unserialize($rawSmsData);

            $smsDataCells = wf_TableCell(__('Mobile'), '', 'row2');
            $smsDataCells .= wf_TableCell($rawSmsData['number']);
            $smsDataRows = wf_TableRow($smsDataCells, 'row3');
            $smsDataCells = wf_TableCell(__('Message'), '', 'row2');
            $smsDataCells .= wf_TableCell($rawSmsData['message']);
            $smsDataRows .= wf_TableRow($smsDataCells, 'row3');
            $smsDataTable = wf_TableBody($smsDataRows, '100%', '0', 'glamour');

            $smsDataFlushControl = wf_delimiter() . wf_JSAlert('?module=taskman&edittask=' . $taskid . '&flushsmsdata=' . $taskid, web_delete_icon(), __('Are you serious'));

            $smsData = wf_modal(wf_img('skins/icon_sms_micro.gif', __('SMS sent to employees')), __('SMS sent to employees'), $smsDataTable . $smsDataFlushControl, '', '400', '200');
        } else {
//post sending form
            if ($altercfg['SENDDOG_ENABLED']) {
                $smsAddress = str_replace('\'', '`', $taskdata['address']);
                $smsAddress = mysql_real_escape_string($smsAddress);
                $smsPhone = mysql_real_escape_string($taskdata['phone']);
                $smsJobTime = (!empty($taskdata['starttime'])) ? ' ' . date("H:i", strtotime($taskdata['starttime'])) : '';
                $smsJobNote = mysql_real_escape_string($taskdata['jobnote']);
                $smsEmployee = vf($taskdata['employee']);
                $taskJobTypeId = $taskdata['jobtype'];
                $taskJobTypeName = @$alljobtypes[$taskJobTypeId];
                $newSmsText = $smsAddress . ' ' . $smsPhone . ' ' . $taskJobTypeName . ' ' . $smsJobNote . $smsJobTime;

                $smsDataCells = wf_TableCell(__('Employee'), '', 'row2');
                $smsDataCells .= wf_TableCell(@$allemployee[$taskdata['employee']]);
                $smsDataRows = wf_TableRow($smsDataCells, 'row3');
                $smsDataCells = wf_TableCell(__('Message'), '', 'row2');
                $smsDataCells .= wf_TableCell(zb_TranslitString($newSmsText));
                $smsDataRows .= wf_TableRow($smsDataCells, 'row3');

                $smsDataTable = wf_TableBody($smsDataRows, '100%', '0', 'glamour');

                $smsInputs = $smsDataTable;
                $smsInputs .= wf_HiddenInput('postsendemployee', $smsEmployee);
                $smsInputs .= wf_HiddenInput('postsendsmstext', $newSmsText);
                $smsInputs .= wf_Submit(__('Send SMS'));
                $smsForm = wf_Form('', 'POST', $smsInputs, '');

                $smsData = wf_modal(wf_img_sized('skins/icon_mobile.gif', __('Send SMS'), '10'), __('Send SMS'), $smsForm, '', '400', '200');
            }
        }

        $administratorName = (isset($employeeLogins[$taskdata['admin']])) ? $employeeLogins[$taskdata['admin']] : $taskdata['admin'];

        $tablecells = wf_TableCell(__('ID'), '30%');
        $tablecells .= wf_TableCell($taskdata['id']);
        $tablerows = wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Task creation date') . ' / ' . __('Administrator'));
        $tablecells .= wf_TableCell($taskdata['date'] . ' / ' . $administratorName);
        $tablerows .= wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Target date'));
        $tablecells .= wf_TableCell(wf_tag('strong') . $taskdata['startdate'] . ' ' . $taskdata['starttime'] . wf_tag('strong', true));
        $tablerows .= wf_TableRow($tablecells, 'row3');

//here some build passport data
        $bpData = '';
        if ($altercfg['BUILD_EXTENDED']) {
            if (!empty($taskLogin)) {
                if (cfr('BUILDPASSPORT')) {
                    $allUserBuilds = zb_AddressGetBuildUsers();
                    if (isset($allUserBuilds[$taskLogin])) {
                        $taskUserBuildId = $allUserBuilds[$taskLogin];
                        $buildPassport = new BuildPassport();
                        $buildPassportData = $buildPassport->renderPassportData($taskUserBuildId);
                        if (!empty($buildPassportData)) {
                            $bpLink = $buildPassport::URL_PASSPORT . '&' . $buildPassport::ROUTE_BUILD . '=' . $taskUserBuildId;
                            $bpLink .= '&back=' . base64_encode('taskman&edittask=' . $taskid);
                            $buildPassportData = wf_CleanDiv() . $buildPassportData;
                            $buildPassportData .= wf_delimiter(0) . wf_Link($bpLink, wf_img('skins/icon_buildpassport.png') . ' ' . __('Go to build passport'), false, 'ubButton');
                            $bpData .= wf_modal(wf_img_sized('skins/icon_buildpassport.png', __('Build passport'), 12), __('Build passport'), $buildPassportData, '', 700);
                        }
                    }
                }
            }
        }

        $tablecells = wf_TableCell(__('Task address') . $bpData);
        $tablecells .= wf_TableCell($addresslink);
        $tablerows .= wf_TableRow($tablecells, 'row3');

// getting user's branch name
        $branchConsider = (!empty($taskLogin)
                and $ubillingConfig->getAlterParam('BRANCHES_ENABLED')
                and $ubillingConfig->getAlterParam('TASKMAN_BRANCHES_CONSIDER_ON'));
        if ($branchConsider) {
            $branches = new UbillingBranches();
            $branchName = $branches->userGetBranchName($taskLogin);
            $branchName = (empty($branchName) ? '' : wf_Link($branches::URL_ME . '&userbranch=' . $taskLogin, $branchName));

            $tablecells = wf_TableCell(__('Branch'));
            $tablecells .= wf_TableCell($branchName);
            $tablerows .= wf_TableRow($tablecells, 'row3');
        }

        $tablecells = wf_TableCell(__('Login'));
        $tablecells .= wf_TableCell($taskLogin . $loginType);
        $tablerows .= wf_TableRow($tablecells, 'row3');

        if (!empty($taskLogin)) {
            $allUserLogins = zb_UserGetAllDataCache();
            if (isset($allUserLogins[$taskLogin])) {
                $UserIpMAC = zb_UserGetAllData($taskLogin);
                $tablecells = wf_TableCell(__('IP'));
                $tablecells .= wf_TableCell(@$UserIpMAC[$taskLogin]['ip']);
                $tablerows .= wf_TableRow($tablecells, 'row3');

                $tablecells = wf_TableCell(__('MAC'));
                $tablecells .= wf_TableCell(@$UserIpMAC[$taskLogin]['mac']);
                $tablerows .= wf_TableRow($tablecells, 'row3');

                if (@$altercfg['TASKMAN_SHOW_USERTAGS']) {
                    $userTags = __('No');
                    $userTagsRaw = zb_UserGetAllTags($taskLogin);
                    if (!empty($userTagsRaw)) {
                        $userTagsRaw = $userTagsRaw[$taskLogin];
                        $userTags = implode(', ', $userTagsRaw);
                    }
                    $tablecells = wf_TableCell(__('Tags'));
                    $tablecells .= wf_TableCell($userTags);
                    $tablerows .= wf_TableRow($tablecells, 'row3');
                }

                if (@$altercfg['SWITCHPORT_IN_PROFILE']) {
                    $allAssigns = zb_SwitchesGetAssignsAll();
                    if (isset($allAssigns[$taskLogin])) {
                        $tablecells = wf_TableCell(__('Switch'));
                        $tablecells .= wf_TableCell(@$allAssigns[$taskLogin]['label']);
                        $tablerows .= wf_TableRow($tablecells, 'row3');
                    }
                }
            }
        }

        $tablecells = wf_TableCell(__('Phone'));
        $tablecells .= wf_TableCell($taskdata['phone']);
        $tablerows .= wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Job type'));
        $tablecells .= wf_TableCell(@$alljobtypes[$taskdata['jobtype']]);
        $tablerows .= wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Who should do'));
        $tablecells .= wf_TableCell(@$allemployee[$taskdata['employee']] . ' ' . $smsData);
        $tablerows .= wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Job note'));
        $tablecells .= wf_TableCell(nl2br($taskdata['jobnote']));
        $tablerows .= wf_TableRow($tablecells, 'row3');

        if (@$altercfg['TASKRANKS_ENABLED']) {
            $taskRanksReadOnly = (!cfr('TASKRANKS')) ? true : false;

            $taskFails = new Stigma('TASKFAILS', $taskid);
            if (!$taskRanksReadOnly) {
                $taskFails->stigmaController('TASKMAN:Task checklist fails');
            }

            $taskRanks = new Stigma('TASKRANKS', $taskid);

            if (!$taskRanksReadOnly) {
                $taskRanks->stigmaController('TASKMAN:Score');
            }

            $taskRanksInterface = '';
            $taskRanksInterface .= __('Task checklist fails') . wf_delimiter(0);
            $taskRanksInterface .= $taskFails->render($taskid, 128, $taskRanksReadOnly) . wf_delimiter(0);
            $taskRanksInterface .= __('User rating of task completion') . wf_delimiter(0);
            $taskRanksInterface .= $taskRanks->render($taskid, 64, $taskRanksReadOnly);
            $taskHaveFails = $taskFails->haveState($taskid);
            $taskHaveRank = $taskRanks->haveState($taskid);
            $rankTextLabels = '';
            if ($taskHaveFails) {
                $rankTextLabels .= ' ' . $taskFails->textRender($taskid, ' ', '10') . ' ';
            }

            if ($taskHaveRank) {
                $rankTextLabels .= __('Score') . ': ' . $taskRanks->textRender($taskid, '', '10');
            }

            $ranksModalLabel = __('Edit');
            if (!$taskRanksReadOnly) {
                $taskRanksModal = wf_modalAuto($ranksModalLabel, __('Quality control'), $taskRanksInterface, '');
            } else {
                $taskRanksModal = '';
            }

            $tablecells = wf_TableCell(__('Quality control'));
            $tablecells .= wf_TableCell($taskRanksModal . $rankTextLabels);
            $tablerows .= wf_TableRow($tablecells, 'row3');
        }

        $result .= wf_TableBody($tablerows, '100%', '0', 'glamour');
        $result .= wf_CleanDiv();
// show task preview
        show_window(__('View task') . ' ' . $modform, $result);

// Task logs
        if (cfr('TASKMANNWATCHLOG')) {
            show_window(__('View log'), ts_renderLogsListAjax($taskid));
        }

//Task duplicates check
        if (@$altercfg['TASKMAN_DUPLICATE_CHECK']) {
            ts_CheckDailyDuplicates($taskdata, $altercfg['TASKMAN_DUPLICATE_CHECK']);
        }

//Salary accounting
        if ($altercfg['SALARY_ENABLED']) {
            if (cfr('SALARYTASKSVIEW')) {
                $salary = new Salary($taskid);
                show_window(__('Additional jobs done'), $salary->taskJobCreateForm($taskid));
            }
        }

//warehouse integration
        if ($altercfg['WAREHOUSE_ENABLED']) {
            if (cfr('WAREHOUSE') OR cfr('WAREVIEW')) {
                $warehouse = new Warehouse($taskid);
                show_window(__('Additionally spent materials'), $warehouse->taskMaterialsReport($taskid));
            }
        }

//if task undone
        if ($taskdata['status'] == 0) {
            $sup = wf_tag('sup') . '*' . wf_tag('sup', false);
            $inputs = wf_HiddenInput('changetask', $taskid);
            $inputs .= wf_HiddenInput('change_admin', whoami());
            if ((cfr('TASKMANNODONDATE')) AND ( !cfr('ROOT'))) {
//manual done date selection forbidden
                $inputs .= wf_HiddenInput('editenddate', curdate());
            } else {
                $inputs .= wf_DatePicker('editenddate') . wf_tag('label', false) . __('Finish date') . $sup . wf_tag('label', true) . wf_tag('br');
            }
            $inputs .= wf_tag('br');
            $inputs .= wf_Selector('editemployeedone', $activeemployee, __('Worker done'), $taskdata['employee'], true);
            $inputs .= wf_tag('br');
            $inputs .= wf_tag('label', false) . __('Finish note') . wf_tag('label', true) . wf_tag('br');
            $inputs .= wf_TextArea('editdonenote', '', '', true, '35x3');
            $inputs .= wf_tag('br');
            $inputs .= $jobgencheckbox;
            $inputs .= wf_Submit(__('This task is done'));

            $form = wf_Form("", 'POST', $inputs, 'glamour');

            if (cfr('TASKMANDELETE')) {
                show_window('', wf_JSAlertStyled('?module=taskman&deletetask=' . $taskid, web_delete_icon() . ' ' . __('Remove this task - it is an mistake'), $messages->getDeleteAlert(), 'ubButton'));
            }

//show editing form
            if (cfr('TASKMANDONE')) {
                show_window(__('If task is done'), $form);
            }
        } else {
            $donecells = wf_TableCell(__('Finish date'), '30%');
            $donecells .= wf_TableCell($taskdata['enddate']);
            $donerows = wf_TableRow($donecells, 'row3');

            $donecells = wf_TableCell(__('Worker done'));
            $donecells .= wf_TableCell((empty($allemployee[$taskdata['employeedone']]) ? '' : $allemployee[$taskdata['employeedone']]));
            $donerows .= wf_TableRow($donecells, 'row3');

            $donecells = wf_TableCell(__('Finish note'));
            $donecells .= wf_TableCell($taskdata['donenote']);
            $donerows .= wf_TableRow($donecells, 'row3');

            $administratorChange = (isset($employeeLogins[$taskdata['change_admin']])) ? $employeeLogins[$taskdata['change_admin']] : $taskdata['change_admin'];

            $donecells = wf_TableCell(__('Administrator'));
            $donecells .= wf_TableCell($administratorChange);
            $donerows .= wf_TableRow($donecells, 'row3');

            $doneresult = wf_TableBody($donerows, '100%', '0', 'glamour');

            if (cfr('TASKMANDELETE')) {
                $doneresult .= wf_JSAlertStyled('?module=taskman&deletetask=' . $taskid, web_delete_icon() . ' ' . __('Remove this task - it is an mistake'), $messages->getDeleteAlert(), 'ubButton');
            }

            if (cfr('TASKMANDONE')) {
                $doneresult .= '&nbsp;';
                $doneresult .= wf_JSAlertStyled('?module=taskman&setundone=' . $taskid, wf_img('skins/icon_key.gif') . ' ' . __('No work was done'), $messages->getEditAlert(), 'ubButton');
            }

            show_window(__('Task is done'), $doneresult);
        }
    }
}

/**
 * Deletes existing task from database
 * 
 * @param int $taskid
 * 
 * @return void
 */
function ts_DeleteTask($taskid) {
    $taskid = vf($taskid, 3);
// Before delete task - write task data to log
    $task_data = ts_GetTaskData($taskid);
    $queryLogTask = ("
        INSERT INTO `taskmanlogs` (`id`, `taskid`, `date`, `admin`, `ip`, `event`, `logs`) 
        VALUES (NULL, '" . $taskid . "', CURRENT_TIMESTAMP, '" . whoami() . "', '" . @$_SERVER['REMOTE_ADDR'] . "', 'delete',
        '" . serialize($task_data) . "')
    ");
    nr_query($queryLogTask);
    $query = "DELETE from `taskman` WHERE `id`='" . $taskid . "'";
    nr_query($query);
    log_register('TASKMAN DELETE [' . $taskid . ']');
}

/**
 * Find all log for task
 *
 * @param int $taskid
 *
 * @return void
 */
function ts_GetLogTask($taskid) {
    $result = '';
    if (!empty($taskid)) {
        $query = "SELECT * FROM `taskmanlogs` WHERE taskid = '" . $taskid . "' ORDER BY `id` ASC";
    } else {
        $renderYear = (ubRouting::checkGet('renderyear')) ? ubRouting::get('renderyear', 'int') : curyear();
        if ($renderYear == '1488') {
//all time tasks
            $renderYear = '%';
        }
        $query = "SELECT * FROM `taskmanlogs` WHERE `date` LIKE '" . $renderYear . "-%' ORDER BY `id` DESC";
    }
    $resultLog = simple_queryall($query);
    if (!empty($resultLog)) {
        $result = $resultLog;
    }
    return($result);
}

/**
 * Render panel for tas logs
 * 
 * @param int $taskid
 * 
 * @return void
 */
function ts_renderLogsListAjax($taskid = '') {
    $result = '';
// Task logs
    if (cfr('TASKMANNWATCHLOG')) {
        $resultLogAjax = '';

        $columns = array('ID');
        if (empty($taskid)) {
            $opts = '"order": [[ 0, "desc" ]]';
            $columns[] = 'Task ID';
        } else {
            $opts = '"order": [[ 0, "asc" ]]';
        }
        $columns[] = 'Date';
        $columns[] = 'Login';
        $columns[] = 'IP';
        $columns[] = 'Events';
        $columns[] = 'Logs';
        $renderYear = (ubRouting::checkPost('renderyear')) ? ubRouting::post('renderyear', 'int') : '';
        $module_link = (empty($taskid)) ? '?module=taskman&ajaxlog=true' : '?module=taskman&ajaxlog=true&edittask=' . $taskid;
        if (!empty($renderYear)) {
            $module_link .= '&renderyear=' . $renderYear;
        }
        $result = wf_JqDtLoader($columns, $module_link, false, 'Logs', 100, $opts);
        if (empty($taskid)) {
//appending year selector form
            $inputs = wf_YearSelectorPreset('renderyear', __('Year'), false, $renderYear, true) . ' ';
            $inputs .= wf_Submit(__('Show'));
            $result .= wf_delimiter(0);
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
    }
    return ($result);
}

/**
 * Find all log for task
 *
 * @param int $taskid
 *
 * @return void
 */
function ts_renderLogsDataAjax($taskid = '') {
    $taskid = vf($taskid, 3);

    $result_log = ts_GetLogTask($taskid);
    @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
    $json = new wf_JqDtHelper();

    if (!empty($result_log)) {
        $allemployee = ts_GetAllEmployee();
        $alljobtypes = ts_GetAllJobtypes();
        $taskStates = new TaskStates(true);

        foreach ($result_log as $each) {
            $administratorChange = (isset($employeeLogins[$each['admin']])) ? $employeeLogins[$each['admin']] : $each['admin'];

            $data[] = $each['id'];
            if (empty($taskid)) {
                $data[] = wf_link('?module=taskman&edittask=' . $each['taskid'], $each['taskid']);
            }
            $data[] = $each['date'];
            $data[] = $administratorChange;
            $data[] = $each['ip'];



            if ($each['event'] == 'create') {
                $data[] = __('Create task');
                $data_event = @unserialize($each['logs']);
            } elseif ($each['event'] == 'modify') {
                $data[] = __('Edit task');
                $data_event = '';
                $logDataArr = @unserialize($each['logs']);
                if (isset($logDataArr['address'])) {
                    $data_event .= wf_tag('b') . __('Task address') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="green"') . $logDataArr['address']['old'] . wf_tag('font', true);
                    $data_event .= " => ";
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['address']['new'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');
                }
                if (isset($logDataArr['login'])) {
                    $data_event .= wf_tag('b') . __('Login') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="green"') . $logDataArr['login']['old'] . wf_tag('font', true);
                    $data_event .= " => ";
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['login']['new'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');
                }
                if (isset($logDataArr['jobtype'])) {

                    $jobTypeIdOld = $logDataArr['jobtype']['old'];
                    $jobTypeIdNew = $logDataArr['jobtype']['new'];
                    $jobtypeOld = @$alljobtypes[$jobTypeIdOld];
                    $jobtypeNew = @$alljobtypes[$jobTypeIdNew];

                    $data_event .= wf_tag('b') . __('Job type') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="green"') . $jobtypeOld . wf_tag('font', true);
                    $data_event .= " => ";
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $jobtypeNew . wf_tag('font', true);
                    $data_event .= wf_tag('br');
                }
                if (isset($logDataArr['jobnote'])) {
                    $data_event .= wf_tag('b') . __('Job note') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="green"') . $logDataArr['jobnote']['old'] . wf_tag('font', true);
                    $data_event .= " => ";
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['jobnote']['new'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');
                }
                if (isset($logDataArr['phone'])) {
                    $data_event .= wf_tag('b') . __('phone') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="green"') . $logDataArr['phone']['old'] . wf_tag('font', true);
                    $data_event .= " => ";
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['phone']['new'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');
                }
                if (isset($logDataArr['employee'])) {
                    $employeeIdOld = $logDataArr['employee']['old'];
                    $employeeIdNew = $logDataArr['employee']['new'];
                    $employeeOld = @$allemployee[$employeeIdOld];
                    $employeeNew = @$allemployee[$employeeIdNew];

                    $data_event .= wf_tag('b') . __('Worker') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="green"') . $employeeOld . wf_tag('font', true);
                    $data_event .= " => ";
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $employeeNew . wf_tag('font', true);
                    $data_event .= wf_tag('br');
                }
                if (isset($logDataArr['startdate'])) {
                    $data_event .= wf_tag('b') . __('Target date') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="green"') . $logDataArr['startdate']['old'] . wf_tag('font', true);
                    $data_event .= " => ";
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['startdate']['new'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');
                }
                if (isset($logDataArr['starttime'])) {
                    $data_event .= wf_tag('b') . __('Target date') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="green"') . $logDataArr['starttime']['old'] . wf_tag('font', true);
                    $data_event .= " => ";
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['starttime']['new'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');
                }

                if (isset($logDataArr['taskstate'])) {

                    $data_event .= wf_tag('b') . __('Task state') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="green"') . $taskStates->getStateName($logDataArr['taskstate']['old']) . wf_tag('font', true);
                    $data_event .= " => ";
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $taskStates->getStateName($logDataArr['taskstate']['new']) . wf_tag('font', true);
                    $data_event .= wf_tag('br');
                }

                if (isset($logDataArr['taskparam'])) {
                    $oldParam = (!empty($logDataArr['taskparam']['old'])) ? $logDataArr['taskparam']['old'] : 'none';
                    $newParam = (!empty($logDataArr['taskparam']['new'])) ? $logDataArr['taskparam']['new'] : 'none';
                    $paramName = (!empty($logDataArr['taskparam']['name'])) ? $logDataArr['taskparam']['name'] : 'Parameter';
                    $data_event .= wf_tag('b') . __($paramName) . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="green"') . __($oldParam) . wf_tag('font', true);
                    $data_event .= " => ";
                    $data_event .= wf_tag('font', false, '', 'color="red"') . __($logDataArr['taskparam']['new']) . wf_tag('font', true);
                    $data_event .= wf_tag('br');
                }
            } elseif ($each['event'] == 'done') {
                $data[] = __('Task is done');
                $data_event = '';
                $logDataArr = @unserialize($each['logs']);

                $data_event .= wf_tag('b') . __('Finish date') . ": " . wf_tag('b', true);
                $data_event .= wf_tag('font', false, '', 'color="green"') . $logDataArr['editenddate'] . wf_tag('font', true);
                $data_event .= wf_tag('br');

                $data_event .= wf_tag('b') . __('Worker done') . ": " . wf_tag('b', true);
                $data_event .= wf_tag('font', false, '', 'color="green"') . @$allemployee[$logDataArr['editemployeedone']] . wf_tag('font', true);
                $data_event .= wf_tag('br');

                $data_event .= wf_tag('b') . __('Finish note') . ": " . wf_tag('b', true);
                $data_event .= wf_tag('font', false, '', 'color="green"') . $logDataArr['editdonenote'] . wf_tag('font', true);
                $data_event .= wf_tag('br');
            } elseif ($each['event'] == 'setundone') {
                $data[] = __('No work was done');
                $data_event = wf_tag('font', false, '', 'color="red"') . wf_tag('b') . __('No work was done') . wf_tag('b', true) . wf_tag('font', true);
            } elseif ($each['event'] == 'delete') {
                $data[] = __('Task delete');
                $data_event = '';
                $logDataArr = @unserialize($each['logs']);
                if ($logDataArr) {
                    $data_event .= wf_tag('b') . __('Create date') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['date'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');

                    $data_event .= wf_tag('b') . __('Task address') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['address'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');

                    $data_event .= wf_tag('b') . __('Login') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['login'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');

                    $data_event .= wf_tag('b') . __('Job type') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="red"') . @$alljobtypes[$logDataArr['jobtype']] . wf_tag('font', true);
                    $data_event .= wf_tag('br');

                    $data_event .= wf_tag('b') . __('Job note') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['jobnote'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');

                    $data_event .= wf_tag('b') . __('Phone') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['phone'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');

                    $data_event .= wf_tag('b') . __('Worker') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="red"') . @$allemployee[$logDataArr['employee']] . wf_tag('font', true);
                    $data_event .= wf_tag('br');

                    $data_event .= wf_tag('b') . __('Worker done') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="red"') . @$allemployee[$logDataArr['employeedone']] . wf_tag('font', true);
                    $data_event .= wf_tag('br');

                    $data_event .= wf_tag('b') . __('Target date') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="red"') . $logDataArr['startdate'] . " " . $logDataArr['starttime'] . wf_tag('font', true);
                    $data_event .= wf_tag('br');

                    $data_event .= wf_tag('b') . __('Admin') . ": " . wf_tag('b', true);
                    $data_event .= wf_tag('font', false, '', 'color="red"') . @$employeeLogins[$logDataArr['admin']] . wf_tag('font', true);
                    $data_event .= wf_tag('br');

                    $data_event .= wf_tag('b') . __('Status') . ": " . wf_tag('b', true);
                    $data_event .= web_bool_led($logDataArr['status']);
                    $data_event .= wf_tag('br');
                } else {
                    $data[] = __('Log data corrupted');
                }
            } else {
                $data[] = __($each['event']);
                $data_event = $each['logs'];
            }

            $data[] = $data_event;
            $json->addRow($data);
            unset($data);
        }
    }

    $json->getJson();
}

/**
 * Logs some parameter change for some task
 * 
 * @param int $taskId existing task ID
 * @param string $parameter parameter name which changed
 * @param string $oldValue old parameter value
 * @param string $newValue new parameter value
 * @param bool $weblog log to weblogs table?
 * 
 * @retrun void
 */
function ts_logTaskChange($taskId, $parameter, $oldValue, $newValue, $weblog = false) {
    $taskId = ubRouting::filters($taskId, 'int');
    $parameter = ubRouting::filters($parameter, 'mres');
    $oldValue = ubRouting::filters($oldValue, 'mres');
    $newValue = ubRouting::filters($newValue, 'mres');


    $log_data_arr = array();

    $logData['taskparam']['name'] = $parameter;
    $logData['taskparam']['old'] = $oldValue;
    $logData['taskparam']['new'] = $newValue;
    $storeLogData = serialize($logData);

    $taskmanLogs = new NyanORM('taskmanlogs');
    $taskmanLogs->data('taskid', $taskId);
    $taskmanLogs->data('date', curdatetime());
    $taskmanLogs->data('admin', whoami());
    $taskmanLogs->data('ip', @$_SERVER['REMOTE_ADDR']);
    $taskmanLogs->data('event', 'modify');
    $taskmanLogs->data('logs', $storeLogData);
    $taskmanLogs->create();

    if ($weblog) {
        log_register('TASKSTATE CHANGE TASK [' . $taskId . '] PARAM `' . $parameter . '` ON  `' . $newValue . '`');
    }
}

/**
 * Returns task typical problems editing form
 * 
 * @return string
 */
function ts_TaskProblemsEditForm() {
    $rawNotes = zb_StorageGet('PROBLEMS');

//extract old or create new typical problems array
    if (!empty($rawNotes)) {
        $rawNotes = base64_decode($rawNotes);
        $rawNotes = unserialize($rawNotes);
    } else {
        $emptyArray = array();
        $newNotes = serialize($emptyArray);
        $newNotes = base64_encode($newNotes);
        zb_StorageSet('PROBLEMS', $newNotes);
        $rawNotes = $emptyArray;
    }

//adding and deletion subroutines
    if (wf_CheckPost(array('createtypicalnote'))) {
        $toPush = strip_tags($_POST['createtypicalnote']);
        array_push($rawNotes, $toPush);
        $newNotes = serialize($rawNotes);
        $newNotes = base64_encode($newNotes);
        zb_StorageSet('PROBLEMS', $newNotes);
        log_register('TASKMAN ADD TYPICALPROBLEM');
        rcms_redirect("?module=taskman&probsettings=true");
    }

    if (wf_CheckPost(array('deletetypicalnote', 'typicalnote'))) {
        $toUnset = $_POST['typicalnote'];
        if (($delkey = array_search($toUnset, $rawNotes)) !== false) {
            unset($rawNotes[$delkey]);
        }

        $newNotes = serialize($rawNotes);
        $newNotes = base64_encode($newNotes);
        zb_StorageSet('PROBLEMS', $newNotes);
        log_register('TASKMAN DELETE TYPICALPROBLEM');
        rcms_redirect("?module=taskman&probsettings=true");
    }

    $rows = '';
    $result = wf_BackLink("?module=taskman", '', true);

    if (!empty($rawNotes)) {
        foreach ($rawNotes as $eachNote) {
            $cells = wf_TableCell($eachNote);
            $rows .= wf_TableRow($cells, 'row3');
        }
    }

    $result .= wf_TableBody($rows, '100%', '0', '');
    $result .= wf_delimiter();

    $addinputs = wf_TextInput('createtypicalnote', __('Create'), '', true, '20');
    $addinputs .= wf_Submit(__('Save'));
    $addform = wf_Form("", "POST", $addinputs, 'glamour');
    $result .= $addform;

    $delinputs = ts_TaskTypicalNotesSelector(false);
    $delinputs .= wf_HiddenInput('deletetypicalnote', 'true');
    $delinputs .= wf_Submit(__('Delete'));
    $delform = wf_Form("", "POST", $delinputs, 'glamour');
    $result .= $delform;

    return ($result);
}

/**
 * Checks is current administrator cursed by some branch?
 * 
 * @global object $ubillingConfig
 * 
 * @return bool
 */
function ts_isMeBranchCursed() {
    global $ubillingConfig;
    $result = false;
    if ($ubillingConfig->getAlterParam('BRANCHES_ENABLED') OR $ubillingConfig->getAlterParam('TASKMAN_GULAG')) {
        if (cfr('ROOT')) {
            $result = false;
        } else {
            if (cfr('BRANCHES') OR cfr('TASKMANGULAG')) {
                if (cfr('TSUNCURSED')) {
//glag and branches curse excluding right
                    $result = false;
                } else {
                    $result = true;
                }
            }
        }
    } else {
        $result = false;
    }
    return($result);
}

/**
 * Returns tasks by date printing dialogue
 * 
 * @return string
 */
function ts_PrintDialogue() {
    global $ubillingConfig;
    $advFiltersEnabled = $ubillingConfig->getAlterParam('TASKMAN_ADV_FILTERS');

    $submitOpts = '';
    $tmpInputs = '';
    $inputs = '';
    $inputs .= wf_tag('span', false, 'col-1-2-occupy');
    $inputs .= wf_tag('h3');
    $inputs .= __('From') . wf_nbsp(2);
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_DatePickerPreset('printdatefrom', curdate());
    $inputs .= wf_nbsp(8);
    $inputs .= wf_tag('h3');
    $inputs .= __('To') . wf_nbsp(2);
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_DatePickerPreset('printdateto', curdate());
    $inputs .= wf_tag('span', true);

    if ($advFiltersEnabled) {
        $whoami = whoami();
        $employeeid = ts_GetEmployeeByLogin($whoami);

        if ($employeeid) {
            $curselected = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : '';
            $displayTypes = array('all' => __('Show tasks for all users'), 'onlyme' => __('Show only mine tasks'));
            $tmpInputs .= wf_Selector('displaytype', $displayTypes, '', $curselected, false, false, '', 'col-3-4-occupy');
        }

        $tmpInputs .= ts_AdvFiltersControls();
        $tmpInputs .= wf_tag('span', false, 'col-1-2-occupy', '');
        $tmpInputs .= wf_CheckInput('nopagebreaks', __('No page breaks for each employee'), false, false);
        $tmpInputs .= wf_tag('span', true);
    }

    $inputs .= $tmpInputs;
    $inputs .= wf_tag('span', false, '');
    $inputs .= wf_CheckInput('tableview', __('Grid view'), false, true) . ' ';
    $inputs .= wf_tag('span', true);
    $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Print'), '', $submitOpts);
    $result = wf_Form("", 'POST', $inputs, 'glamour form-grid-4cols form-grid-4cols-label-right ');

    return ($result);
}

/**
 * Renders printable tasks filtered by dates
 * 
 * @param string $datefrom
 * @param string $dateto
 * 
 * @return void
 */
function ts_PrintTasks($datefrom, $dateto) {
    global $ubillingConfig;
    $advFiltersEnabled = $ubillingConfig->getAlterParam('TASKMAN_ADV_FILTERS');
    $branchConsider = ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')
            and $ubillingConfig->getAlterParam('TASKMAN_BRANCHES_CONSIDER_ON'));

    $datefrom = mysql_real_escape_string($datefrom);
    $dateto = mysql_real_escape_string($dateto);
    $allemployee = ts_GetAllEmployee();
    $alljobtypes = ts_GetAllJobtypes();
    $advFilter = '';
    $appendQueryJOIN = '';

    $result = wf_tag('style');
    $result .= '
        table.gridtable {
            font-family: verdana,arial,sans-serif;
            font-size:9pt;
            color:#333333;
            border-width: 1px;
            border-color: #666666;
            border-collapse: collapse;
        }
        table.gridtable th {
            border-width: 1px;
            padding: 3px;
            border-style: solid;
            border-color: #666666;
            background-color: #dedede;
        }
        table.gridtable td {
            border-width: 1px;
            padding: 3px;
            border-style: solid;
            border-color: #666666;
            background-color: #ffffff;
        }
        ';
    $result .= wf_tag('style', true);

    if ($advFiltersEnabled) {
        $advFilter = ts_AdvFiltersQuery();
        $appendQueryJOIN = ($branchConsider) ? " LEFT JOIN `branchesusers` USING(`login`) 
                                                 LEFT JOIN `branches` ON `branchesusers`.`branchid` = `branches`.`id` " : "";
    }

    $displaytype = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : 'all';
    if ($displaytype == 'onlyme') {
        $whoami = whoami();
        $curempid = ts_GetEmployeeByLogin($whoami);
        $appendQuery = " AND `employee`='" . $curempid . "'";
    } else {
        $appendQuery = '';
    }

    $query = "SELECT * FROM `taskman` 
                    LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id`
                    " . $appendQueryJOIN . " 
                WHERE `startdate` BETWEEN '" . $datefrom . " 00:00:00' AND '" . $dateto . " 23:59:59' AND `status`='0'"
            . " " . $advFilter . " " . $appendQuery;
    $alltasks = simple_queryall($query);

    if (!empty($alltasks)) {
        foreach ($alltasks as $io => $each) {
            $rows = '';
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell($each['id']);
            $rows .= wf_TableRow($cells);

            $cells = wf_TableCell(__('Target date'));
            $cells .= wf_TableCell($each['startdate'] . ' ' . @$each['starttime']);
            $rows .= wf_TableRow($cells);

            $cells = wf_TableCell(__('Task address'));
            $cells .= wf_TableCell($each['address']);
            $rows .= wf_TableRow($cells);

            $cells = wf_TableCell(__('Phone'));
            $cells .= wf_TableCell($each['phone']);
            $rows .= wf_TableRow($cells);

            $cells = wf_TableCell(__('Job type'));
//$cells.= wf_TableCell(@$alljobtypes[$each['jobtype']]);
            $cells .= wf_TableCell($each['jobname']);
            $rows .= wf_TableRow($cells);

            $cells = wf_TableCell(__('Who should do'));
            $cells .= wf_TableCell(@$allemployee[$each['employee']]);
            $rows .= wf_TableRow($cells);

            $cells = wf_TableCell(__('Job note'));
            $cells .= wf_TableCell($each['jobnote']);
            $rows .= wf_TableRow($cells);
            $tasktable = wf_TableBody($rows, '100%', '0', 'gridtable');
            $result .= wf_tag('div', false, '', 'style="width: 300px; height: 250px; float: left; border: dashed; border-width:1px; margin:5px; page-break-inside: avoid;"');
            $result .= $tasktable;
            $result .= wf_tag('div', true);
        }
        $result .= wf_tag('script', false, '', 'language="javascript"');
        $result .= 'window.print();';
        $result .= wf_tag('script', true);
    } else {
//$_POST['showemptyqueryerror'] = 'true';

        $messages = new UbillingMessageHelper();
        $result = '<link rel="stylesheet" href="./skins/ubng/css/ubilling.css" type="text/css">';
        $result .= $messages->getStyledMessage(__('Query returned empty result'), 'warning');
    }

    die($result);
}

/**
 * Renders printable tasks filtered by dates
 * 
 * @param string $datefrom
 * @param string $dateto
 * 
 * @return void
 */
function ts_PrintTasksTable($datefrom, $dateto, $nopagebreaks = false) {
    global $ubillingConfig;
    $advFiltersEnabled = $ubillingConfig->getAlterParam('TASKMAN_ADV_FILTERS');
    $branchConsider = ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')
            and $ubillingConfig->getAlterParam('TASKMAN_BRANCHES_CONSIDER_ON'));

    $datefrom = mysql_real_escape_string($datefrom);
    $dateto = mysql_real_escape_string($dateto);
    $allemployee = ts_GetAllEmployee();
    $alljobtypes = ts_GetAllJobtypes();
    $advFilter = '';
    $appendQueryJOIN = '';
    $tmpArr = array();
    $pageBreakStyle = ($nopagebreaks) ? '' : 'page-break-after: always;';
    $result = wf_tag('style');
    $result .= '
        table.gridtable {
            font-family: verdana,arial,sans-serif;
            font-size:9pt;
            color:#333333;
            border-width: 1px;
            border-color: #666666;
            border-collapse: collapse;
            ' . $pageBreakStyle . '
        }
        
       .row1 {
          background-color: #000000;
          color: #FFFFFF;
          font-weight: bolder;
          font-size: larger;
        }
        
        table.gridtable td {
            border-width: 1px;
            padding: 3px;
            border-style: solid;
            border-color: #666666;
        }
      
        ';
    $result .= wf_tag('style', true);

    if ($advFiltersEnabled) {
        $advFilter = ts_AdvFiltersQuery();
        $appendQueryJOIN = ($branchConsider) ? " LEFT JOIN `branchesusers` USING(`login`) 
                                                 LEFT JOIN `branches` ON `branchesusers`.`branchid` = `branches`.`id` " : "";
    }

    $displaytype = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : 'all';
    if ($displaytype == 'onlyme') {
        $whoami = whoami();
        $curempid = ts_GetEmployeeByLogin($whoami);
        $appendQuery = " AND `employee`='" . $curempid . "'";
    } else {
        $appendQuery = '';
    }

    $orderFilter = 'address';
    $customOrderFilter = $ubillingConfig->getAlterParam('TASKMAN_PRINT_ORDER');
    if ($customOrderFilter) {
        $orderFilter = mysql_real_escape_string($customOrderFilter);
    }

    $query = "SELECT * FROM `taskman` 
                    LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id`
                    " . $appendQueryJOIN . "  
                WHERE `startdate` BETWEEN '" . $datefrom . " 00:00:00' AND '" . $dateto . " 23:59:59' AND `status`='0'"
            . $advFilter . " " . $appendQuery . " " . "ORDER BY `taskman`.`" . $orderFilter . "`";

    $alltasks = simple_queryall($query);

    if (!empty($alltasks)) {
        foreach ($alltasks as $io => $each) {
            $tmpArr[$each['employee']][] = $each;
        }

        if (!empty($tmpArr)) {
            foreach ($tmpArr as $eachEmployeeId => $eachEmployeeTasks) {
                if (!empty($eachEmployeeId)) {
                    $result .= wf_tag('h2') . @$allemployee[$eachEmployeeId] . wf_tag('h2', true);
                    if (!empty($eachEmployeeTasks)) {
                        $cells = wf_TableCell(__('Target date'));
                        $cells .= wf_TableCell(__('Task address'));
                        $cells .= wf_TableCell(__('Phone'));
                        $cells .= wf_TableCell(__('Job type'));
                        $cells .= wf_TableCell(__('Job note'));
                        $cells .= wf_TableCell(__('Additional comments'));
                        $rows = wf_TableRow($cells, 'row1');
                        foreach ($eachEmployeeTasks as $io => $each) {
                            $cells = wf_TableCell($each['startdate'] . ' ' . wf_tag('b') . @$each['starttime'] . wf_tag('b', true));
                            $cells .= wf_TableCell($each['address']);
                            $cells .= wf_TableCell($each['phone']);
//$cells.= wf_TableCell(@$alljobtypes[$each['jobtype']]);
                            $cells .= wf_TableCell($each['jobname']);
                            $cells .= wf_TableCell(nl2br($each['jobnote']));
                            $cells .= wf_TableCell('');
                            $rows .= wf_TableRow($cells, 'row3');
                        }
                        $result .= wf_TableBody($rows, '100%', 0, 'gridtable');
                    }
                }
            }
        }

        $result .= wf_tag('script', false, '', 'language="javascript"');
        $result .= 'window.print();';
        $result .= wf_tag('script', true);
    } else {
//$_POST['showemptyqueryerror'] = 'true';

        $messages = new UbillingMessageHelper();
        $result = '<link rel="stylesheet" href="./skins/ubng/css/ubilling.css" type="text/css">';
        $result .= $messages->getStyledMessage(__('Query returned empty result'), 'warning');
    }

    die($result);
}

/**
 * Returns list of expired undone tasks
 * 
 * @return string
 */
function ts_ShowLate() {
    $allemployee = ts_GetAllEmployee();
    $alljobtypes = ts_GetAllJobtypes();
    $curyear = curyear();
    $curmonth = date("m");
    $curdate = curdate();
    if (($curmonth != 1) AND ( $curmonth != 12)) {
        $query = "SELECT * from `taskman` WHERE `status`='0' AND `startdate` LIKE '" . $curyear . "-%' AND `startdate`< '" . $curdate . "' ORDER BY `startdate` ASC";
    } else {
        $query = "SELECT * from `taskman` WHERE `status`='0' AND `startdate`< '" . $curdate . "' ORDER BY `startdate` ASC";
    }

    $cells = wf_TableCell(__('Target date'));
    $cells .= wf_TableCell(__('Task address'));
    $cells .= wf_TableCell(__('Phone'));
    $cells .= wf_TableCell(__('Job type'));
    $cells .= wf_TableCell(__('Who should do'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['startdate']);
            $cells .= wf_TableCell($each['address']);
            $cells .= wf_TableCell($each['phone']);
            $cells .= wf_TableCell(@$alljobtypes[$each['jobtype']]);
            $cells .= wf_TableCell(@$allemployee[$each['employee']]);
            $actions = wf_Link('?module=taskman&edittask=' . $each['id'], web_edit_icon(), false, '');
            $cells .= wf_TableCell($actions);
            $rows .= wf_TableRow($cells, 'row3');
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    return ($result);
}

/**
 * Gets employee by administrator login
 * 
 * @param string $login logged in administrators login
 * 
 * @return mixed 
 */
function ts_GetEmployeeByLogin($login) {
    $login = mysql_real_escape_string($login);
    $query = "SELECT `id` from `employee` WHERE `admlogin`='" . $login . "'";
    $raw = simple_query($query);
    if (!empty($raw)) {
        $result = $raw['id'];
    } else {
        $result = false;
    }
    return ($result);
}

/**
 * Returns count of undone tasks - used by DarkVoid
 * 
 * @return int
 */
function ts_GetUndoneCountersAll() {
    $result = 0;
    $curdate = curdate();
    $curyear = curyear();
    $query = "SELECT `id` from `taskman` WHERE `status` = '0' AND `startdate` <= '" . $curdate . "' AND `date` LIKE '" . $curyear . "-%';";
    $allundone = simple_queryall($query);
    if (!empty($allundone)) {
        $result = sizeof($allundone);
    }
    return ($result);
}

/**
 * Returns array of all tasks address as taskId=>address
 * 
 * @return array
 */
function ts_GetAllTasksAddress() {
    $result = array();
    $all = simple_queryall("SELECT `id`,`address` from `taskman`");
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['id']] = $each['address'];
        }
    }
    return($result);
}

/**
 * Returns array of undone tasks - used by Warehouse and another weird things as id=>taskdata
 * 
 * @param bool $allTime get undone tasks not only before current date
 * 
 * @return array
 */
function ts_GetUndoneTasksArray($allTime = false) {
    $result = array();
    if (!$allTime) {
        $curdate = curdate();
        $dateFilters = "AND `startdate` <= '" . $curdate . "'";
    } else {
        $dateFilters = '';
    }

    $orders = "ORDER BY `address`,`jobtype` ASC";
    $query = "SELECT * from `taskman` WHERE `status` = '0' " . $dateFilters . " " . $orders;

    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['id']] = $each;
        }
    }
    return ($result);
}

/**
 * Returns count of undone tasks only for current admin login - used by DarkVoid
 * 
 * @return int
 */
function ts_GetUndoneCountersMy() {
    $result = 0;
    $curdate = curdate();
    $curyear = curyear();
    $mylogin = whoami();
    $adminQuery = "SELECT `id` from `employee` WHERE `admlogin`='" . $mylogin . "'";
    $adminId = simple_query($adminQuery);
    if (!empty($adminId)) {
        $adminId = $adminId['id'];
        $query = "SELECT `id` from `taskman` WHERE `employee`='" . $adminId . "' AND `status` = '0' AND `startdate` <= '" . $curdate . "' AND `date` LIKE '" . $curyear . "-%';";
        $allundone = simple_queryall($query);
        if (!empty($allundone)) {
            $result = sizeof($allundone);
        }
    }
    return ($result);
}

/**
 * Returns all of available tasks as id=>data
 * 
 * @return array
 */
function ts_GetAllTasks() {
    $result = array();
    $query = "SELECT * from `taskman`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['id']] = $each;
        }
    }
    return ($result);
}

/**
 * Returns all tasks short data as taskid=>data
 * 
 * @return array
 */
function ts_GetAllTasksQuickData() {
    $result = array();
    $query = "SELECT `id`,`address`,`startdate`,`employee` from `taskman`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['id']]['address'] = $each['address'];
            $result[$each['id']]['startdate'] = $each['startdate'];
            $result[$each['id']]['employee'] = $each['employee'];
        }
    }
    return ($result);
}

function ts_AdvFiltersControls() {
    global $ubillingConfig;
    $branchConsider = ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')
            and $ubillingConfig->getAlterParam('TASKMAN_BRANCHES_CONSIDER_ON'));
    $branchAdvFltON = $ubillingConfig->getAlterParam('TASKMAN_ADV_FILTERS_BRANCHES_ON');

    $alljobtypes = ts_GetAllJobtypes();
    $alljobtypes = array('0' => __('Any')) + $alljobtypes;
    $selectedjobtype = (ubRouting::checkPost('filtertaskjobtypeexact') ? ubRouting::post('filtertaskjobtypeexact') : '');
    $jobtypecontains = (ubRouting::checkPost('filtertaskjobtype') ? ubRouting::post('filtertaskjobtype') : '');
    $addresscontains = (ubRouting::checkPost('filtertaskaddr') ? ubRouting::post('filtertaskaddr') : '');
    $jobnotecontains = (ubRouting::checkPost('filtertaskjobnote') ? ubRouting::post('filtertaskjobnote') : '');
    $phonecontains = (ubRouting::checkPost('filtertaskphone') ? ubRouting::post('filtertaskphone') : '');
    $branchcontains = (ubRouting::checkPost('filtertaskbranch') ? ubRouting::post('filtertaskbranch') : '');

    $inputs = '';
    $inputs .= wf_tag('h3');
    $inputs .= __('Job type');
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_Selector('filtertaskjobtypeexact', $alljobtypes, '', $selectedjobtype);

    $inputs .= wf_tag('h3');
    $inputs .= __('Job type contains');
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_TextInput('filtertaskjobtype', '', $jobtypecontains);

    if ($branchConsider and $branchAdvFltON) {
        $inputs .= wf_tag('h3');
        $inputs .= __('Branch contains');
        $inputs .= wf_tag('h3', true);
        $inputs .= wf_TextInput('filtertaskbranch', '', $branchcontains);
    }

    $inputs .= wf_tag('h3');
    $inputs .= __('Address contains');
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_TextInput('filtertaskaddr', '', $addresscontains);

    $inputs .= wf_tag('h3');
    $inputs .= __('Job note contains');
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_TextInput('filtertaskjobnote', '', $jobnotecontains);

    $inputs .= wf_tag('h3');
    $inputs .= __('Job phone contains');
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_TextInput('filtertaskphone', '', $phonecontains);

    return($inputs);
}

function ts_AdvFiltersQuery() {
    global $ubillingConfig;
    $branchConsider = ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')
            and $ubillingConfig->getAlterParam('TASKMAN_BRANCHES_CONSIDER_ON'));
    $branchAdvFltON = $ubillingConfig->getAlterParam('TASKMAN_ADV_FILTERS_BRANCHES_ON');

    $appendQuery = '';

    if (ubRouting::checkPost('filtertaskjobtypeexact')) {
        $appendQuery .= " AND `jobtype` = " . ubRouting::post('filtertaskjobtypeexact');
    } elseif (ubRouting::checkPost('filtertaskjobtype')) {
        $appendQuery .= " AND `jobname` LIKE '%" . ubRouting::post('filtertaskjobtype') . "%'";
    }

    if (ubRouting::checkPost('filtertaskaddr')) {
        $appendQuery .= " AND `address` LIKE '%" . ubRouting::post('filtertaskaddr') . "%'";
    }

    if (ubRouting::checkPost('filtertaskjobnote')) {
        $appendQuery .= " AND `jobnote` LIKE '%" . ubRouting::post('filtertaskjobnote') . "%'";
    }

    if (ubRouting::checkPost('filtertaskphone')) {
        $appendQuery .= " AND `phone` LIKE '%" . ubRouting::post('filtertaskphone') . "%'";
    }

    if ($branchConsider and $branchAdvFltON and ubRouting::checkPost('filtertaskbranch')) {
        $appendQuery .= " AND `branches`.`name` LIKE '%" . ubRouting::post('filtertaskbranch') . "%'";
    }

    return ($appendQuery);
}

/**
 * Returns all tasks array filtered by some date
 * 
 * @param string $date
 * 
 * @return array
 */
function ts_getAllTasksByDate($date) {
    $date = ubRouting::filters($date, 'mres');
    $query = "SELECT * from `taskman` WHERE `startdate` LIKE '" . $date . "%'";
    $result = simple_queryall($query);
    return($result);
}
