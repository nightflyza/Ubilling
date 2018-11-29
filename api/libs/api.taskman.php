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
function em_EmployeeShowForm() {
    $show_q = "SELECT * from `employee`";
    $allemployee = simple_queryall($show_q);
    $allTagNames = stg_get_alltagnames();

    $cells = wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('Real Name'));
    $cells.= wf_TableCell(__('Active'));
    $cells.= wf_TableCell(__('Appointment'));
    $cells.= wf_TableCell(__('Mobile'));
    $cells.= wf_TableCell(__('Chat ID') . ' ' . __('Telegram'));
    $cells.= wf_TableCell(__('Administrator'));
    $cells.= wf_TableCell(__('Tag'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allemployee)) {
        foreach ($allemployee as $ion => $eachemployee) {
            $cells = wf_TableCell($eachemployee['id']);
            $cells.= wf_TableCell($eachemployee['name']);
            $cells.= wf_TableCell(web_bool_led($eachemployee['active']));
            $cells.= wf_TableCell($eachemployee['appointment']);
            $cells.= wf_TableCell($eachemployee['mobile']);
            $cells.= wf_TableCell($eachemployee['telegram']);
            $admlogin = $eachemployee['admlogin'];
            if (!empty($admlogin)) {
                if (file_exists(USERS_PATH . $admlogin)) {
                    $admlogin = wf_Link('?module=permissions&edit=' . $admlogin, web_profile_icon() . ' ' . $admlogin, false);
                }
            }
            $cells.= wf_TableCell($admlogin);
            $employeeTagId = $eachemployee['tagid'];
            $employeeTagName = (!empty($employeeTagId)) ? $allTagNames[$employeeTagId] : '';
            $employeeTagLabel = (!empty($employeeTagName)) ? $employeeTagName . ' (' . $employeeTagId . ')' : '';
            $cells.= wf_TableCell($employeeTagLabel);
            $actions = wf_JSAlert('?module=employee&delete=' . $eachemployee['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $actions.= wf_JSAlert('?module=employee&edit=' . $eachemployee['id'], web_edit_icon(), 'Are you serious');
            $cells.= wf_TableCell($actions);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }

    //new employee create form inputs  
    $inputs = wf_HiddenInput('addemployee', 'true');
    $inputs.= wf_TableCell('');
    $inputs.= wf_TableCell(wf_TextInput('employeename', '', '', false, 30));
    $inputs.= wf_TableCell('');
    $inputs.= wf_TableCell(wf_TextInput('employeejob', '', '', false, 20));
    $inputs.= wf_TableCell(wf_TextInput('employeemobile', '', '', false, 15));
    $inputs.= wf_TableCell(wf_TextInput('employeetelegram', '', '', false, 15));
    $inputs.= wf_TableCell(wf_TextInput('employeeadmlogin', '', '', false, 10));
    $inputs.= wf_TableCell(em_TagSelector('editadtagid'));
    $inputs.= wf_TableCell(wf_Submit(__('Create')));
    $inputs = wf_TableRow($inputs, 'row2');
    $addForm = wf_Form("", 'POST', $inputs, '');
    $rows.=$addForm;

    $result = wf_TableBody($rows, '100%', '0', '');

    show_window(__('Employee'), $result);
}

/**
 * Renders jobtypes edit/creation/deletion form and list
 * 
 * @return void
 */
function em_JobTypeForm() {
    $show_q = "SELECT * from `jobtypes`";
    $alljobs = simple_queryall($show_q);

    $cells = wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('Job type'));
    $cells.= wf_TableCell(__('Color'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($alljobs)) {
        foreach ($alljobs as $ion => $eachjob) {
            $cells = wf_TableCell($eachjob['id']);
            $cells.= wf_TableCell($eachjob['jobname']);
            $jobColor = (!empty($eachjob['jobcolor'])) ? wf_tag('font', false, '', 'color="' . $eachjob['jobcolor'] . '"') . $eachjob['jobcolor'] . wf_tag('font', true) : '';
            $cells.= wf_TableCell($jobColor);
            $actionlinks = wf_JSAlert('?module=employee&deletejob=' . $eachjob['id'], web_delete_icon(), 'Removing this may lead to irreparable results') . ' ';
            $actionlinks.= wf_JSAlert('?module=employee&editjob=' . $eachjob['id'], web_edit_icon(), 'Are you serious');
            $cells.= wf_TableCell($actionlinks);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }

    $inputs = wf_HiddenInput('addjobtype', 'true');
    $inputs.= wf_TableCell('');
    $inputs.= wf_TableCell(wf_TextInput('newjobtype', '', '', false, '30'));
    $inputs.= wf_TableCell(wf_ColPicker('newjobcolor', __('Color'), '', false, 8));
    $inputs.= wf_TableCell(wf_Submit(__('Create')));
    $inputs = wf_TableRow($inputs, 'row2');
    $createForm = wf_Form("", 'POST', $inputs, '');
    $rows.= $createForm;

    $result = wf_TableBody($rows, '100%', '0', '');

    show_window(__('Job types'), $result);
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
function em_EmployeeAdd($name, $job, $mobile = '', $telegram = '', $admlogin = '', $tagid = '') {
    $name = mysql_real_escape_string(trim($name));
    $job = mysql_real_escape_string(trim($job));
    $mobile = mysql_real_escape_string($mobile);
    $telegram = mysql_real_escape_string($telegram);
    $admlogin = mysql_real_escape_string($admlogin);
    $tagid = mysql_real_escape_string($tagid);
    $query = "INSERT INTO `employee` (`id` , `name` , `appointment`, `mobile`, `telegram`, `admlogin`, `active`, `tagid`)
              VALUES (NULL , '" . $name . "', '" . $job . "','" . $mobile . "','" . $telegram . "' ,'" . $admlogin . "' , '1', " . $tagid . "); ";
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
 * Returns employee selector box
 * 
 * @return string
 */
function stg_worker_selector() {
    $query = "SELECT * from `employee` WHERE `active`='1'";
    $allemployee = simple_queryall($query);
    $employeez = array();
    if (!empty($allemployee)) {
        foreach ($allemployee as $io => $eachwrker) {
            $employeez[$eachwrker['id']] = $eachwrker['name'];
        }
    }
    $result = wf_Selector('worker', $employeez, '', '', false);
    return($result);
}

/**
 * Returns jobtype selector box
 * 
 * @return string
 */
function stg_jobtype_selector() {
    $query = "SELECT * from `jobtypes` ORDER by `id` ASC";
    $alljobtypes = simple_queryall($query);
    $params = array();
    if (!empty($alljobtypes)) {
        foreach ($alljobtypes as $io => $eachjobtype) {
            $params[$eachjobtype['id']] = $eachjobtype['jobname'];
        }
    }
    $result = wf_Selector('jobtype', $params, '', '', false);
    return($result);
}

/**
 * Renders list with controls for jobs done for some user
 * 
 * @param string $username
 * 
 * @return void
 */
function stg_show_jobs($username) {
    $query_jobs = 'SELECT * FROM `jobs` WHERE `login`="' . $username . '" ORDER BY `id` ASC';
    $alljobs = simple_queryall($query_jobs);
    $allemployee = ts_GetAllEmployee();
    $alljobtypes = ts_GetAllJobtypes();
    $activeemployee = ts_GetActiveEmployee();

    $cells = wf_TableCell(__('ID'));
    $cells.= wf_tableCell(__('Date'));
    $cells.= wf_TableCell(__('Worker'));
    $cells.= wf_TableCell(__('Job type'));
    $cells.= wf_TableCell(__('Notes'));
    $cells.= wf_TableCell('');
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
            $cells.= wf_tableCell($eachjob['date']);
            $cells.= wf_TableCell(@$allemployee[$eachjob['workerid']]);
            $cells.= wf_TableCell(@$alljobtypes[$eachjob['jobid']]);
            $cells.= wf_TableCell($jobnote);
            $cells.= wf_TableCell(wf_JSAlert('?module=jobs&username=' . $username . '&deletejob=' . $eachjob['id'] . '', web_delete_icon(), 'Are you serious'));
            $rows.= wf_TableRow($cells, 'row3');
        }
    }

    //onstruct job create form
    $curdatetime = curdatetime();
    $inputs = wf_HiddenInput('addjob', 'true');
    $inputs.= wf_HiddenInput('jobdate', $curdatetime);
    $inputs.= wf_TableCell('');
    $inputs.= wf_tableCell($curdatetime);
    $inputs.= wf_TableCell(stg_worker_selector());
    $inputs.= wf_TableCell(stg_jobtype_selector());
    $inputs.= wf_TableCell(wf_TextInput('notes', '', '', false, '20'));
    $inputs.= wf_TableCell(wf_Submit('Create'));
    $inputs = wf_TableRow($inputs, 'row2');

    $addform = wf_Form("", 'POST', $inputs, '');

    if ((!empty($activeemployee)) AND ( !empty($alljobtypes))) {
        $rows.= $addform;
    } else {
        show_error(__('No job types and employee available'));
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
            $result[$each['id']]['jobname'] = $each['jobname'];
            $result[$each['id']]['jobcolor'] = $each['jobcolor'];
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
                $customJobColorStyle.='.' . $customJobColorStyleName . ',
                                                   .' . $customJobColorStyleName . ' div,
                                                   .' . $customJobColorStyleName . ' span {
                                                        background-color: ' . $eachjobcolor . '; 
                                                        border-color: ' . $eachjobcolor . '; 
                                                        color: #FFFFFF;           
                                                    }';
            }
        }
    }
    $customJobColorStyle.='</style>' . "\n";
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

            $result.="
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

    //per employee filtering
    $displaytype = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : 'all';
    if ($displaytype == 'onlyme') {
        $whoami = whoami();
        $curempid = ts_GetEmployeeByLogin($whoami);
        $appendQuery = " AND `employee`='" . $curempid . "'";
    } else {
        $appendQuery = '';
    }

    if (isset($altCfg['TASKMAN_ADV_FILTERS']) and $altCfg['TASKMAN_ADV_FILTERS']) {
        $appendQuery .= ts_AdvFiltersQuery();
    }

    if (($curmonth != 1) AND ( $curmonth != 12)) {
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname` FROM `taskman` 
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id` 
                    WHERE `status`='0' AND `startdate` LIKE '" . $curyear . "-%' " . $appendQuery . " ORDER BY `date` ASC";
    } else {
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname` FROM `taskman`  
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id` 
                    WHERE `status`='0' " . $appendQuery . " ORDER BY `date` ASC";
    }

    $allundone = simple_queryall($query);
    $result = '';
    $i = 1;
    $taskcount = sizeof($allundone);

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

            $result.="
                      {
                        title: '" . $startTime . $eachtask['address'] . " - " . @$alljobdata[$eachtask['jobtype']]['jobname'] . $adcText . "',
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
    //ADcomments init
    if ($altCfg['ADCOMMENTS_ENABLED']) {
        $adcomments = new ADcomments('TASKMAN');
        $adcFlag = true;
    } else {
        $adcFlag = false;
    }
    $allemployee = ts_GetAllEmployee();
    $alljobtypes = ts_GetAllJobtypes();

    $curyear = curyear();
    $curmonth = date("m");

    //per employee filtering
    $displaytype = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : 'all';
    if ($displaytype == 'onlyme') {
        $whoami = whoami();
        $curempid = ts_GetEmployeeByLogin($whoami);
        $appendQuery = " AND `employee`='" . $curempid . "'";
    } else {
        $appendQuery = '';
    }

    if (isset($altCfg['TASKMAN_ADV_FILTERS']) and $altCfg['TASKMAN_ADV_FILTERS']) {
        $appendQuery .= ts_AdvFiltersQuery();
    }

    if (($curmonth != 1) AND ( $curmonth != 12)) {
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname` FROM `taskman` 
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id` 
                    WHERE `status`='1' AND `startdate` LIKE '" . $curyear . "-%' " . $appendQuery . " ORDER BY `date` ASC";
    } else {
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname` FROM `taskman` 
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id` 
                    WHERE `status`='1' " . $appendQuery . " ORDER BY `date` ASC";
    }

    $allundone = simple_queryall($query);
    $result = '';
    $i = 1;
    $taskcount = sizeof($allundone);

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

            $result.="
                      {
                        title: '" . $eachtask['address'] . " - " . @$allemployee[$eachtask['employeedone']] . $adcText . "',
                        start: new Date(" . $startdate . "),
                        end: new Date(" . $enddate . "),
                        url: '?module=taskman&edittask=" . $eachtask['id'] . "'
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

    //per employee filtering
    $displaytype = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : 'all';
    if ($displaytype == 'onlyme') {
        $whoami = whoami();
        $curempid = ts_GetEmployeeByLogin($whoami);
        $appendQuery = " AND `employee`='" . $curempid . "'";
    } else {
        $appendQuery = '';
    }

    if (isset($altCfg['TASKMAN_ADV_FILTERS']) and $altCfg['TASKMAN_ADV_FILTERS']) {
        $appendQuery .= ts_AdvFiltersQuery();
    }

    if (($curmonth != 1) AND ( $curmonth != 12)) {
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname` FROM `taskman` 
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id` 
                    WHERE `startdate` LIKE '" . $curyear . "-%' " . $appendQuery . " ORDER BY `date` ASC";
    } else {
        if ($appendQuery) {
            //$appendQuery = str_replace('AND', 'WHERE', $appendQuery);
            $appendQuery = preg_replace('AND', 'WHERE', $appendQuery, 1);
        }
        $query = "SELECT `taskman`.*, `jobtypes`.`jobname` FROM `taskman` 
                      LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id` " . $appendQuery . " ORDER BY `date` ASC";
    }

    $allundone = simple_queryall($query);
    $result = '';
    $i = 1;
    $taskcount = sizeof($allundone);

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


            $result.="
                      {
                        title: '" . $startTime . $eachtask['address'] . " - " . @$alljobdata[$eachtask['jobtype']]['jobname'] . $adcText . "',
                        start: new Date(" . $startdate . $startTimeTimestamp . "),
                        end: new Date(" . $enddate . "),
                        " . $coloring . "
                        url: '?module=taskman&edittask=" . $eachtask['id'] . "'
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
            if (mb_strlen($eachnote, 'utf-8') > 20) {
                $shortNote = mb_substr($eachnote, 0, 20, 'utf-8') . '...';
            } else {
                $shortNote = $eachnote;
            }
            $typycalNotes[$eachnote] = $shortNote;
        }
    }

    $selector = wf_Selector('typicalnote', $typycalNotes, __('Problem') . ' ' . $settingsControl, '', true);
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
    //construct sms sending inputs
    if ($altercfg['SENDDOG_ENABLED']) {
        $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, false);
        // SET checkbed TELEGRAM for creating task from Userprofile if TASKMAN_TELEGRAM_PROFILE_CHECK == 1
        $telegramInputsCheck = (isset($altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) && $altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) ? TRUE : FALSE;
        $telegramInputs = wf_CheckInput('newtasksendtelegram', __('Telegram'), false, $telegramInputsCheck);
    } else {
        $smsInputs = '';
        $telegramInputs = '';
    }

    $inputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
    $inputs.= wf_HiddenInput('createtask', 'true');
    $inputs.= wf_DatePicker('newstartdate');
    $inputs.= wf_TimePickerPreset('newstarttime', '', '', false);
    $inputs.= wf_tag('label') . __('Target date') . wf_tag('sup') . '*' . wf_tag('sup', true) . wf_tag('label', true);
    $inputs.= wf_delimiter();

    if (!$altercfg['SEARCHADDR_AUTOCOMPLETE']) {
        $inputs.= wf_TextInput('newtaskaddress', __('Address') . '<sup>*</sup>', '', true, '30');
    } else {
        if (!@$altercfg['TASKMAN_SHORT_AUTOCOMPLETE']) {
            $allAddress = zb_AddressGetFulladdresslistCached();
        } else {
            $allAddress= zb_AddressGetStreetsWithBuilds();
        }
        $inputs.= wf_AutocompleteTextInput('newtaskaddress', $allAddress, __('Address') . '<sup>*</sup>', '', true, '30');
    }
    $inputs.= wf_tag('br');
    //hidden for new task login input
    $inputs.= wf_HiddenInput('newtasklogin', '');
    $inputs.= wf_TextInput('newtaskphone', __('Phone') . '<sup>*</sup>', '', true, '30');
    $inputs.= wf_tag('br');
    $inputs.= wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
    $inputs.= wf_tag('br');
    $inputs.= wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
    $inputs.= wf_tag('br');
    $inputs.= ts_TaskTypicalNotesSelector();
    $inputs.= wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
    $inputs.= wf_TextArea('newjobnote', '', '', true, '35x5');
    $inputs.= $smsInputs;
    $inputs.= $telegramInputs;
    $inputs.= wf_Submit(__('Create new task'));
    $result = wf_Form("", 'POST', $inputs, 'glamour');
    $result.= __('All fields marked with an asterisk are mandatory');
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

    // telepaticheskoe ugadivanie po tegu, kto dolzhen vipolnit rabotu
    $query = "SELECT `employee`.`id` FROM `tags` INNER JOIN employee USING (tagid) WHERE `login` = '" . $login . "'";
    $telepat_who_should_do = simple_query($query);

    //construct sms sending inputs
    if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
        $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, false);
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
        // В воскресенье работать работать не хочу
        if ($newTaskDate = $TaskDate->format('w') == 0) {
            $TaskDate->add(new DateInterval('P1D'));
        }
        $newTaskDate = $TaskDate->format('Y-m-d');
        $newTaskTime = $TaskDate->format('H:i');
    } else {
        $newTaskDate = '';
        $newTaskTime = '';
    }

    $employeeSorting = ($ubillingConfig->getAlterParam('TASKMAN_NEWTASK_EMPSORT')) ? true : false;

    $sup = wf_tag('sup', false) . '*' . wf_tag('sup', true);

    $inputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
    $inputs.= wf_HiddenInput('createtask', 'true');
    $inputs.= wf_DatePickerPreset('newstartdate', $newTaskDate);
    $inputs.= wf_TimePickerPreset('newstarttime', $newTaskTime, '', false);
    $inputs.= wf_tag('label') . __('Target date') . $sup . wf_tag('label', true);
    $inputs.= wf_delimiter();
    $inputs.= wf_TextInput('newtaskaddress', __('Address') . $sup, $address, true, '30');
    //hidden for new task login input
    $inputs.= wf_HiddenInput('newtasklogin', $login);
    $inputs.= wf_tag('br');
    $inputs.= wf_TextInput('newtaskphone', __('Phone') . $sup, $mobile . ' ' . $phone, true, '30');
    $inputs.= wf_tag('br');
    $inputs.= wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
    $inputs.= wf_tag('br');
    $inputs.= wf_Selector('newtaskemployee', $allemployee, __('Who should do'), $telepat_who_should_do['id'], true, $employeeSorting);
    $inputs.= wf_tag('br');
    $inputs.= wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
    $inputs.= ts_TaskTypicalNotesSelector();
    $inputs.= wf_TextArea('newjobnote', '', '', true, '35x5');
    $inputs.= $smsInputs;
    $inputs.= $telegramInputs;
    $inputs.= wf_Submit(__('Create new task'));
    if (!empty($login)) {
        $inputs.= wf_AjaxLoader();
        $inputs.= ' ' . wf_AjaxLink('?module=prevtasks&username=' . $login, wf_img_sized('skins/icon_search_small.gif', __('Previous user tasks')), 'taskshistorycontainer', false, '');
        $inputs.= wf_tag('br');
        $inputs.= wf_tag('div', false, '', 'id="taskshistorycontainer"') . wf_tag('div', true);
    }
    $result = wf_Form("?module=taskman&gotolastid=true", 'POST', $inputs, 'glamour');
    $result.= __('All fields marked with an asterisk are mandatory');
    return ($result);
}

/**
 * Renders list of all previous user tasks by all time
 * 
 * @param string $login
 * 
 * @return string
 */
function ts_PreviousUserTasksRender($login) {
    $result = '';
    $userTasks = array();
    $telepathyTasks = array();
    $telepathy = new Telepathy(false, true);

    if (!empty($login)) {
        $alljobtypes = ts_GetAllJobtypes();
        $allemployee = ts_GetActiveEmployee();
        $query = "SELECT * from `taskman` ORDER BY `id` DESC;";
        $rawTasks = simple_queryall($query);
        if (!empty($rawTasks)) {
            $result.= wf_tag('hr');
            foreach ($rawTasks as $io => $each) {
                if ($each['login'] == $login) {
                    $userTasks[$each['id']] = $each;
                }
                //address guessing
                if ($telepathy->getLogin($each['address']) == $login) {
                    if (!isset($userTasks[$each['id']])) {
                        $userTasks[$each['id']] = $each;
                        $telepathyTasks[$each['id']] = $each['id'];
                    }
                }
            }

            if (!empty($userTasks)) {
                foreach ($userTasks as $io => $each) {
                    $telepathyFlag = (isset($telepathyTasks[$each['id']])) ? wf_tag('sup') . wf_tag('abbr', false, '', 'title="' . __('telepathically guessed') . '"') . '(?)' . wf_tag('abbr', true) . wf_tag('sup', true) : '';
                    $taskColor = ($each['status']) ? 'donetask' : 'undone';
                    $result.= wf_tag('div', false, $taskColor, 'style="width:400px;"');
                    $taskdata = $each['startdate'] . ' - ' . @$alljobtypes[$each['jobtype']] . ', ' . @$allemployee[$each['employee']] . ' ' . $telepathyFlag;
                    $result.= wf_link('?module=taskman&edittask=' . $each['id'], wf_img('skins/icon_edit.gif')) . ' ' . $taskdata;
                    $result.= wf_tag('div', true);
                }
            }
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
 * @return  string
 */
function ts_TaskCreateFormUnified($address, $mobile, $phone, $login = '') {
    global $ubillingConfig;
    $altercfg = $ubillingConfig->getAlter();
    $alljobtypes = ts_GetAllJobtypes();
    $allemployee = ts_GetActiveEmployee();

    //construct sms sending inputs
    if ($altercfg['SENDDOG_ENABLED']) {
        $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, false);
        // SET checkbed TELEGRAM for creating task from Userprofile if TASKMAN_TELEGRAM_PROFILE_CHECK == 1
        $telegramInputsCheck = (isset($altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) && $altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) ? TRUE : FALSE;
        $telegramInputs = wf_CheckInput('newtasksendtelegram', __('Telegram'), false, $telegramInputsCheck);
    } else {
        $smsInputs = '';
        $telegramInputs = '';
    }

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

    $inputs = '<!--ugly hack to prevent datepicker autoopen -->';
    $inputs.= wf_tag('input', false, '', 'type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"');
    $inputs.= wf_HiddenInput('createtask', 'true');
    $inputs.= wf_DatePicker('newstartdate');
    $inputs.= wf_TimePickerPreset('newstarttime', '', '', false);
    $inputs.= wf_tag('label') . __('Target date') . $sup . wf_tag('label', true);
    $inputs.= wf_delimiter();
    $inputs.= wf_TextInput('newtaskaddress', __('Address') . $sup, $address, true, '30');
    $inputs.= wf_HiddenInput('newtasklogin', $login);
    $inputs.= wf_tag('br');
    $inputs.= wf_TextInput('newtaskphone', __('Phone') . $sup, $mobile . ' ' . $phone, true, '30');
    $inputs.= wf_tag('br');
    $inputs.= wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
    $inputs.= wf_tag('br');
    $inputs.= wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
    $inputs.= wf_tag('br');
    $inputs.= wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
    $inputs.= ts_TaskTypicalNotesSelector();
    $inputs.= wf_TextArea('newjobnote', '', '', true, '35x5');
    $inputs.= $smsInputs;
    $inputs.= $telegramInputs;
    $inputs.= wf_Submit(__('Create new task'));
    $result = wf_Form("?module=taskman&gotolastid=true", 'POST', $inputs, 'glamour');
    $result.= __('All fields marked with an asterisk are mandatory');
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
    $alljobtypes = ts_GetAllJobtypes();
    $allemployee = ts_GetActiveEmployee();
    $altercfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");

    //construct sms sending inputs
    if ($altercfg['SENDDOG_ENABLED']) {
        $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, false);
        // SET checkbed TELEGRAM for creating task from Userprofile if TASKMAN_TELEGRAM_PROFILE_CHECK == 1
        $telegramInputsCheck = (isset($altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) && $altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) ? TRUE : FALSE;
        $telegramInputs = wf_CheckInput('newtasksendtelegram', __('Telegram'), false, $telegramInputsCheck);
    } else {
        $smsInputs = '';
        $telegramInputs = '';
    }

    $inputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
    $inputs.= wf_HiddenInput('createtask', 'true');
    $inputs.= wf_DatePicker('newstartdate');
    $inputs.= wf_TimePickerPreset('newstarttime', '', '', false);
    $inputs.= wf_tag('label') . __('Target date') . wf_tag('sup') . '*' . wf_tag('sup', true) . wf_tag('label', true);
    $inputs.= wf_delimiter();
    $inputs.= wf_TextInput('newtaskaddress', __('Address') . '<sup>*</sup>', $address, true, '30');
    //hidden for new task login input
    $inputs.= wf_HiddenInput('newtasklogin', '');
    $inputs.= wf_tag('br');
    $inputs.= wf_TextInput('newtaskphone', __('Phone') . '<sup>*</sup>', $phone, true, '30');
    $inputs.= wf_tag('br');
    $inputs.= wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
    $inputs.= wf_tag('br');
    $inputs.= wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
    $inputs.= wf_tag('br');
    $inputs.= wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
    $inputs.= ts_TaskTypicalNotesSelector();
    $inputs.= wf_TextArea('newjobnote', '', '', true, '35x5');
    $inputs.= $smsInputs;
    $inputs.= $telegramInputs;
    $inputs.= wf_Submit(__('Create new task'));
    $result = wf_Form("?module=taskman&gotolastid=true", 'POST', $inputs, 'glamour');
    $result.= __('All fields marked with an asterisk are mandatory');
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

    $createform = ts_TaskCreateForm();
    $tools = '';
    $result = wf_modal(wf_img('skins/add_icon.png') . ' ' . __('Create task'), __('Create task'), $createform, 'ubButton', '450', '550');
    $result.= wf_Link('?module=taskman&show=undone', wf_img('skins/undone_icon.png') . ' ' . __('Undone tasks'), false, 'ubButton');
    $result.= wf_Link('?module=taskman&show=done', wf_img('skins/done_icon.png') . ' ' . __('Done tasks'), false, 'ubButton');
    $result.= wf_Link('?module=taskman&show=all', wf_img('skins/icon_calendar.gif') . ' ' . __('All tasks'), false, 'ubButton');
    if (cfr('TASKMANSEARCH')) {
        $tools.= wf_Link('?module=tasksearch', web_icon_search() . ' ' . __('Tasks search'), false, 'ubButton');
    }

    if (cfr('TASKMANTRACK')) {
        $tools.= wf_Link('?module=taskmantrack', wf_img('skins/track_icon.png') . ' ' . __('Tracking'), false, 'ubButton');
    }

    if (cfr('TASKMANTIMING')) {
        $tools.= wf_Link('?module=taskmantiming', wf_img('skins/clock.png') . ' ' . __('Task timing report'), false, 'ubButton');
    }

    if (cfr('TASKMANNWATCHLOG')) {
        $tools.= wf_Link('?module=taskman&show=logs', wf_img('skins/icon_note.gif') . ' ' . __('Logs'), false, 'ubButton');
    }

    $tools.= wf_Link('?module=taskman&print=true', wf_img('skins/icon_print.png') . ' ' . __('Tasks printing'), false, 'ubButton');

    $result.= wf_modalAuto(web_icon_extended() . ' ' . __('Tools'), __('Tools'), $tools, 'ubButton');

    //show type selector
    $whoami = whoami();
    $employeeid = ts_GetEmployeeByLogin($whoami);
    if ($employeeid) {
        $result.= wf_delimiter();
        $curselected = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : '';
        $displayTypes = array('all' => __('Show tasks for all users'), 'onlyme' => __('Show only mine tasks'));
        $inputs = wf_Selector('displaytype', $displayTypes, '', $curselected, false);

        if (isset($altCfg['TASKMAN_ADV_FILTERS']) and $altCfg['TASKMAN_ADV_FILTERS']) {
            $inputs.= ts_AdvFiltersControls();
        }

        $inputs.= wf_Submit('Show');
        $showTypeForm = wf_Form('', 'POST', $inputs, 'glamour');
        $result.= $showTypeForm;
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
    $logQuery.= "(NULL,'" . $editid . "','" . curdatetime() . "');";
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
 * 
 * @return array
 */
function ts_SendTelegram($employeeid, $message) {
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
    }
    return ($result);
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
        //Telegram sending
        if (isset($_POST['newtasksendtelegram'])) {
            $newTelegramText = __('ID') . ': ' . $taskid . '\r\n';
            $newTelegramText.= __('Address') . ': ' . $address . '\r\n';
            $newTelegramText.= __('Job type') . ': ' . @$jobtype[$jobtypeid] . '\r\n';
            $newTelegramText.= __('Phone') . ': ' . $phone . '\r\n';
            $newTelegramText.= __('Job note') . ': ' . $jobnote . '\r\n';
            $newTelegramText.= __('Target date') . ': ' . $startdate . ' ' . $starttimeRaw . '\r\n';
            $newTelegramText.= __('Create date') . ': ' . $jobSendTime . '\r\n';
            if (!empty($login)) {
                $userData = zb_UserGetAllData($login);

                $newTelegramText.= __('Login') . ': ' . $login . '\r\n';
                $newTelegramText.= __('Password') . ': ' . @$userData[$login]['Password'] . '\r\n';
                $newTelegramText.= __('Contract') . ': ' . @$userData[$login]['contract'] . '\r\n';
                $newTelegramText.= __('IP') . ': ' . @$userData[$login]['ip'] . '\r\n';
                $newTelegramText.= __('MAC') . ': ' . @$userData[$login]['mac'] . '\r\n';
                $newTelegramText.= __('Tariff') . ': ' . @$userData[$login]['Tariff'] . '\r\n';
            }
            ts_SendTelegram($employeeid, $newTelegramText);
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
        $smsInputs = wf_CheckInput('changetasksendsms', __('Send SMS'), false, false);
        // SET checkbed TELEGRAM for creating task from Userprofile if TASKMAN_TELEGRAM_PROFILE_CHECK == 1
        $telegramInputsCheck = (isset($altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) && $altercfg['TASKMAN_TELEGRAM_PROFILE_CHECK']) ? TRUE : FALSE;
        $telegramInputs = wf_CheckInput('changetasksendtelegram', __('Telegram'), false, $telegramInputsCheck);
    } else {
        $smsInputs = '';
        $telegramInputs = '';
    }
    if (!empty($taskdata)) {
        $inputs = wf_HiddenInput('modifytask', $taskid);
        $inputs.= '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhackmod" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
        if (cfr('TASKMANDATE')) {
            $inputs.= wf_DatePickerPreset('modifystartdate', $taskdata['startdate']);
        } else {
            $inputs.= wf_HiddenInput('modifystartdate', $taskdata['startdate']);
        }
        $inputs.= wf_TimePickerPreset('modifystarttime', $taskdata['starttime'], '', false);
        $inputs.= wf_tag('label') . __('Target date') . wf_tag('sup') . '*' . wf_tag('sup', true) . wf_tag('label', true);
        $inputs.= wf_delimiter();
        $inputs.= wf_tag('br');
        if ($altercfg['SEARCHADDR_AUTOCOMPLETE']) {
            $alladdress = zb_AddressGetFulladdresslistCached();
            //Commented because significantly reduces performance. Waiting for feedback.
            //natsort($alladdress);
            $inputs.= wf_AutocompleteTextInput('modifytaskaddress', $alladdress, __('Address') . '<sup>*</sup>', $taskdata['address'], true, '30');
        } else {
            $inputs.= wf_TextInput('modifytaskaddress', __('Address') . '<sup>*</sup>', $taskdata['address'], true, '30');
        }
        $inputs.= wf_tag('br');
        //custom login text input
        $inputs.= wf_TextInput('modifytasklogin', __('Login'), $taskdata['login'], true, 30);
        $inputs.= wf_tag('br');
        $inputs.= wf_TextInput('modifytaskphone', __('Phone') . '<sup>*</sup>', $taskdata['phone'], true, '30');
        $inputs.= wf_tag('br');
        $inputs.= wf_Selector('modifytaskjobtype', $alljobtypes, __('Job type'), $taskdata['jobtype'], true);
        $inputs.= wf_tag('br');
        $inputs.= wf_Selector('modifytaskemployee', $activeemployee, __('Who should do'), $taskdata['employee'], true);
        $inputs.= wf_tag('br');
        $inputs.= wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
        $inputs.= wf_TextArea('modifytaskjobnote', '', $taskdata['jobnote'], true, '35x5');
        $inputs.= $smsInputs;
        $inputs.= $telegramInputs;
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');
        $result.= __('All fields marked with an asterisk are mandatory');
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
        $newTelegramText = __('ID') . ': ' . $taskid . '\r\n';
        $newTelegramText.= __('Address') . ': ' . $address . '\r\n';
        $newTelegramText.= __('Job type') . ': ' . @$jobtype[$jobtypeid] . '\r\n';
        $newTelegramText.= __('Phone') . ': ' . $phone . '\r\n';
        $newTelegramText.= __('Job note') . ': ' . $jobnote . '\r\n';
        $newTelegramText.= __('Target date') . ': ' . $startdate . ' ' . $starttimeRaw . '\r\n';
        if (!empty($login)) {
            $userData = zb_UserGetAllData($login);

            $newTelegramText.= __('Login') . ': ' . $login . '\r\n';
            $newTelegramText.= __('Password') . ': ' . @$userData[$login]['Password'] . '\r\n';
            $newTelegramText.= __('Contract') . ': ' . @$userData[$login]['contract'] . '\r\n';
            $newTelegramText.= __('IP') . ': ' . @$userData[$login]['ip'] . '\r\n';
            $newTelegramText.= __('MAC') . ': ' . @$userData[$login]['mac'] . '\r\n';
            $newTelegramText.= __('Tariff') . ': ' . @$userData[$login]['Tariff'] . '\r\n';
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
        $log_data.= __($par) . ':`' . $value . '` => `' . $new_taskdata[$par] . '`';
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
 * Shows task editing/management form
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
            $jobgencheckbox.= wf_HiddenInput('generatelogin', $taskLogin);
            $jobgencheckbox.= wf_HiddenInput('generatejobid', $taskdata['jobtype']);
            $jobgencheckbox.= wf_delimiter();
        } else {
            $jobgencheckbox = '';
        }

        //modify form handlers
        $modform = '';
        if (cfr('TASKMANTRACK')) {
            $modform.= wf_Link('?module=taskmantrack&trackid=' . $taskid, wf_img('skins/track_icon.png', __('Track this task')));
        }
        $modform.= wf_modal(web_edit_icon(), __('Edit'), ts_TaskModifyForm($taskid), '', '450', '550');
        //modform end
        //extracting sms data
        if (!empty($taskdata['smsdata'])) {
            $rawSmsData = $taskdata['smsdata'];
            $rawSmsData = base64_decode($rawSmsData);
            $rawSmsData = unserialize($rawSmsData);

            $smsDataCells = wf_TableCell(__('Mobile'), '', 'row2');
            $smsDataCells.= wf_TableCell($rawSmsData['number']);
            $smsDataRows = wf_TableRow($smsDataCells, 'row3');
            $smsDataCells = wf_TableCell(__('Message'), '', 'row2');
            $smsDataCells.= wf_TableCell($rawSmsData['message']);
            $smsDataRows.= wf_TableRow($smsDataCells, 'row3');
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
                $smsDataCells.= wf_TableCell(@$allemployee[$taskdata['employee']]);
                $smsDataRows = wf_TableRow($smsDataCells, 'row3');
                $smsDataCells = wf_TableCell(__('Message'), '', 'row2');
                $smsDataCells.= wf_TableCell(zb_TranslitString($newSmsText));
                $smsDataRows.= wf_TableRow($smsDataCells, 'row3');

                $smsDataTable = wf_TableBody($smsDataRows, '100%', '0', 'glamour');

                $smsInputs = $smsDataTable;
                $smsInputs.= wf_HiddenInput('postsendemployee', $smsEmployee);
                $smsInputs.= wf_HiddenInput('postsendsmstext', $newSmsText);
                $smsInputs.= wf_Submit(__('Send SMS'));
                $smsForm = wf_Form('', 'POST', $smsInputs, '');

                $smsData = wf_modal(wf_img_sized('skins/icon_mobile.gif', __('Send SMS'), '10'), __('Send SMS'), $smsForm, '', '400', '200');
            }
        }

        $administratorName = (isset($employeeLogins[$taskdata['admin']])) ? $employeeLogins[$taskdata['admin']] : $taskdata['admin'];

        $tablecells = wf_TableCell(__('ID'), '30%');
        $tablecells.= wf_TableCell($taskdata['id']);
        $tablerows = wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Task creation date') . ' / ' . __('Administrator'));
        $tablecells.= wf_TableCell($taskdata['date'] . ' / ' . $administratorName);
        $tablerows.= wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Target date'));
        $tablecells.= wf_TableCell(wf_tag('strong') . $taskdata['startdate'] . ' ' . $taskdata['starttime'] . wf_tag('strong', true));
        $tablerows.= wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Task address'));
        $tablecells.= wf_TableCell($addresslink);
        $tablerows.= wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Login'));
        $tablecells.= wf_TableCell($taskLogin . $loginType);
        $tablerows.= wf_TableRow($tablecells, 'row3');

        if (!empty($taskLogin) and in_array($taskLogin, zb_UserGetAllStargazerLogins())) {
            $UserIpMAC = zb_UserGetAllData($taskLogin);

            $tablecells = wf_TableCell(__('IP'));
            $tablecells.= wf_TableCell(@$UserIpMAC[$taskLogin]['ip']);
            $tablerows.= wf_TableRow($tablecells, 'row3');

            $tablecells = wf_TableCell(__('MAC'));
            $tablecells.= wf_TableCell(@$UserIpMAC[$taskLogin]['mac']);
            $tablerows.= wf_TableRow($tablecells, 'row3');
        }

        $tablecells = wf_TableCell(__('Phone'));
        $tablecells.= wf_TableCell($taskdata['phone']);
        $tablerows.= wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Job type'));
        $tablecells.= wf_TableCell(@$alljobtypes[$taskdata['jobtype']]);
        $tablerows.= wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Who should do'));
        $tablecells.= wf_TableCell(@$allemployee[$taskdata['employee']] . ' ' . $smsData);
        $tablerows.= wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Job note'));
        $tablecells.= wf_TableCell(nl2br($taskdata['jobnote']));
        $tablerows.= wf_TableRow($tablecells, 'row3');

        $result.= wf_TableBody($tablerows, '100%', '0', 'glamour');
        $result.= wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);
        // show task preview
        show_window(__('View task') . ' ' . $modform, $result);

        // Task logs
        if (cfr('TASKMANNWATCHLOG')) {
            show_window(__('View log'), ts_renderLogsListAjax($taskid));
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
            if (cfr('WAREHOUSE')) {
                $warehouse = new Warehouse($taskid);
                show_window(__('Additionally spent materials'), $warehouse->taskMaterialsReport($taskid));
            }
        }

        //if task undone
        if ($taskdata['status'] == 0) {
            $sup = wf_tag('sup') . '*' . wf_tag('sup', false);
            $inputs = wf_HiddenInput('changetask', $taskid);
            $inputs.= wf_HiddenInput('change_admin', whoami());
            if ((cfr('TASKMANNODONDATE')) AND ( !cfr('ROOT'))) {
                //manual done date selection forbidden
                $inputs.= wf_HiddenInput('editenddate', curdate());
            } else {
                $inputs.= wf_DatePicker('editenddate') . wf_tag('label', false) . __('Finish date') . $sup . wf_tag('label', true) . wf_tag('br');
            }
            $inputs.= wf_tag('br');
            $inputs.= wf_Selector('editemployeedone', $activeemployee, __('Worker done'), $taskdata['employee'], true);
            $inputs.= wf_tag('br');
            $inputs.= wf_tag('label', false) . __('Finish note') . wf_tag('label', true) . wf_tag('br');
            $inputs.= wf_TextArea('editdonenote', '', '', true, '35x3');
            $inputs.= wf_tag('br');
            $inputs.= $jobgencheckbox;
            $inputs.= wf_Submit(__('This task is done'));

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
            $donecells.= wf_TableCell($taskdata['enddate']);
            $donerows = wf_TableRow($donecells, 'row3');

            $donecells = wf_TableCell(__('Worker done'));
            $donecells.= wf_TableCell($allemployee[$taskdata['employeedone']]);
            $donerows.= wf_TableRow($donecells, 'row3');

            $donecells = wf_TableCell(__('Finish note'));
            $donecells.= wf_TableCell($taskdata['donenote']);
            $donerows.= wf_TableRow($donecells, 'row3');

            $administratorChange = (isset($employeeLogins[$taskdata['change_admin']])) ? $employeeLogins[$taskdata['change_admin']] : $taskdata['change_admin'];

            $donecells = wf_TableCell(__('Administrator'));
            $donecells.= wf_TableCell($administratorChange);
            $donerows.= wf_TableRow($donecells, 'row3');

            $doneresult = wf_TableBody($donerows, '100%', '0', 'glamour');

            if (cfr('TASKMANDELETE')) {
                $doneresult.= wf_JSAlertStyled('?module=taskman&deletetask=' . $taskid, web_delete_icon() . ' ' . __('Remove this task - it is an mistake'), $messages->getDeleteAlert(), 'ubButton');
            }

            if (cfr('TASKMANDONE')) {
                $doneresult.= '&nbsp;';
                $doneresult.= wf_JSAlertStyled('?module=taskman&setundone=' . $taskid, wf_img('skins/icon_key.gif') . ' ' . __('No work was done'), $messages->getEditAlert(), 'ubButton');
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
        $query = "SELECT * FROM `taskmanlogs` ORDER BY `id` DESC";
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

        $module_link = (empty($taskid)) ? '?module=taskman&ajaxlog=true' : '?module=taskman&ajaxlog=true&edittask=' . $taskid;
        $result = wf_JqDtLoader($columns, $module_link, false, 'Logs', 100, $opts);
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
                $data_event = unserialize($each['logs']);
            } elseif ($each['event'] == 'modify') {
                $data[] = __('Edit task');
                $data_event = '';
                $logDataArr = @unserialize($each['logs']);
                if (isset($logDataArr['address'])) {
                    $data_event.= wf_tag('b') . __('Task address') . ": " . wf_tag('b', true);
                    $data_event.= wf_tag('font', false, '', 'color="green"') . $logDataArr['address']['old'] . wf_tag('font', true);
                    $data_event.= " => ";
                    $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['address']['new'] . wf_tag('font', true);
                    $data_event.= wf_tag('br');
                }
                if (isset($logDataArr['login'])) {
                    $data_event.= wf_tag('b') . __('Login') . ": " . wf_tag('b', true);
                    $data_event.= wf_tag('font', false, '', 'color="green"') . $logDataArr['login']['old'] . wf_tag('font', true);
                    $data_event.= " => ";
                    $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['login']['new'] . wf_tag('font', true);
                    $data_event.= wf_tag('br');
                }
                if (isset($logDataArr['jobtype'])) {

                    $jobTypeIdOld = $logDataArr['jobtype']['old'];
                    $jobTypeIdNew = $logDataArr['jobtype']['new'];
                    $jobtypeOld = @$alljobtypes[$jobTypeIdOld];
                    $jobtypeNew = @$alljobtypes[$jobTypeIdNew];

                    $data_event.= wf_tag('b') . __('Job type') . ": " . wf_tag('b', true);
                    $data_event.= wf_tag('font', false, '', 'color="green"') . $jobtypeOld . wf_tag('font', true);
                    $data_event.= " => ";
                    $data_event.= wf_tag('font', false, '', 'color="red"') . $jobtypeNew . wf_tag('font', true);
                    $data_event.= wf_tag('br');
                }
                if (isset($logDataArr['jobnote'])) {
                    $data_event.= wf_tag('b') . __('Job note') . ": " . wf_tag('b', true);
                    $data_event.= wf_tag('font', false, '', 'color="green"') . $logDataArr['jobnote']['old'] . wf_tag('font', true);
                    $data_event.= " => ";
                    $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['jobnote']['new'] . wf_tag('font', true);
                    $data_event.= wf_tag('br');
                }
                if (isset($logDataArr['phone'])) {
                    $data_event.= wf_tag('b') . __('phone') . ": " . wf_tag('b', true);
                    $data_event.= wf_tag('font', false, '', 'color="green"') . $logDataArr['phone']['old'] . wf_tag('font', true);
                    $data_event.= " => ";
                    $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['phone']['new'] . wf_tag('font', true);
                    $data_event.= wf_tag('br');
                }
                if (isset($logDataArr['employee'])) {
                    $employeeIdOld = $logDataArr['employee']['old'];
                    $employeeIdNew = $logDataArr['employee']['new'];
                    $employeeOld = @$allemployee[$employeeIdOld];
                    $employeeNew = @$allemployee[$employeeIdNew];

                    $data_event.= wf_tag('b') . __('Worker') . ": " . wf_tag('b', true);
                    $data_event.= wf_tag('font', false, '', 'color="green"') . $employeeOld . wf_tag('font', true);
                    $data_event.= " => ";
                    $data_event.= wf_tag('font', false, '', 'color="red"') . $employeeNew . wf_tag('font', true);
                    $data_event.= wf_tag('br');
                }
                if (isset($logDataArr['startdate'])) {
                    $data_event.= wf_tag('b') . __('Target date') . ": " . wf_tag('b', true);
                    $data_event.= wf_tag('font', false, '', 'color="green"') . $logDataArr['startdate']['old'] . wf_tag('font', true);
                    $data_event.= " => ";
                    $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['startdate']['new'] . wf_tag('font', true);
                    $data_event.= wf_tag('br');
                }
                if (isset($logDataArr['starttime'])) {
                    $data_event.= wf_tag('b') . __('Target date') . ": " . wf_tag('b', true);
                    $data_event.= wf_tag('font', false, '', 'color="green"') . $logDataArr['starttime']['old'] . wf_tag('font', true);
                    $data_event.= " => ";
                    $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['starttime']['new'] . wf_tag('font', true);
                    $data_event.= wf_tag('br');
                }
            } elseif ($each['event'] == 'done') {
                $data[] = __('Task is done');
                $data_event = '';
                $logDataArr = unserialize($each['logs']);

                $data_event.= wf_tag('b') . __('Finish date') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="green"') . $logDataArr['editenddate'] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Worker done') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="green"') . @$allemployee[$logDataArr['editemployeedone']] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Finish note') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="green"') . $logDataArr['editdonenote'] . wf_tag('font', true);
                $data_event.= wf_tag('br');
            } elseif ($each['event'] == 'setundone') {
                $data[] = __('No work was done');
                $data_event = wf_tag('font', false, '', 'color="red"') . wf_tag('b') . __('No work was done') . wf_tag('b', true) . wf_tag('font', true);
            } elseif ($each['event'] == 'delete') {
                $data[] = __('Task delete');
                $data_event = '';
                $logDataArr = unserialize($each['logs']);

                $data_event.= wf_tag('b') . __('Create date') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['date'] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Task address') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['address'] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Login') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['login'] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Job type') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="red"') . @$alljobtypes[$logDataArr['jobtype']] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Job note') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['jobnote'] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Phone') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['phone'] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Worker') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="red"') . @$allemployee[$logDataArr['employee']] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Worker done') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="red"') . @$allemployee[$logDataArr['employeedone']] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Target date') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="red"') . $logDataArr['startdate'] . " " . $logDataArr['starttime'] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Admin') . ": " . wf_tag('b', true);
                $data_event.= wf_tag('font', false, '', 'color="red"') . @$employeeLogins[$logDataArr['admin']] . wf_tag('font', true);
                $data_event.= wf_tag('br');

                $data_event.= wf_tag('b') . __('Status') . ": " . wf_tag('b', true);
                $data_event.= web_bool_led($logDataArr['status']);
                $data_event.= wf_tag('br');
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
            $rows.= wf_TableRow($cells, 'row3');
        }
    }

    $result.= wf_TableBody($rows, '100%', '0', '');
    $result.= wf_delimiter();

    $addinputs = wf_TextInput('createtypicalnote', __('Create'), '', true, '20');
    $addinputs.= wf_Submit(__('Save'));
    $addform = wf_Form("", "POST", $addinputs, 'glamour');
    $result.= $addform;

    $delinputs = ts_TaskTypicalNotesSelector(false);
    $delinputs.= wf_HiddenInput('deletetypicalnote', 'true');
    $delinputs.= wf_Submit(__('Delete'));
    $delform = wf_Form("", "POST", $delinputs, 'glamour');
    $result.= $delform;

    return ($result);
}

/**
 * Returns tasks by date printing dialogue
 * 
 * @return string
 */
function ts_PrintDialogue() {
    $inputs = wf_DatePickerPreset('printdatefrom', curdate()) . ' ' . __('From') . ' ';
    $inputs.= wf_DatePickerPreset('printdateto', curdate()) . ' ' . __('To') . ' ';
    $inputs.= wf_CheckInput('tableview', __('Grid view'), false, true) . ' ';
    $inputs.= wf_Submit(__('Print'));
    $result = wf_Form("", 'POST', $inputs, 'glamour');
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
    $datefrom = mysql_real_escape_string($datefrom);
    $dateto = mysql_real_escape_string($dateto);
    $allemployee = ts_GetAllEmployee();
    $alljobtypes = ts_GetAllJobtypes();
    $result = wf_tag('style');
    $result.= '
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
    $result.= wf_tag('style', true);

    $query = "select * from `taskman` where `startdate` BETWEEN '" . $datefrom . " 00:00:00' AND '" . $dateto . " 23:59:59' AND `status`='0'";
    $alltasks = simple_queryall($query);
    if (!empty($alltasks)) {
        foreach ($alltasks as $io => $each) {
            $rows = '';
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell($each['id']);
            $rows.= wf_TableRow($cells);

            $cells = wf_TableCell(__('Target date'));
            $cells.= wf_TableCell($each['startdate'] . ' ' . @$each['starttime']);
            $rows.= wf_TableRow($cells);

            $cells = wf_TableCell(__('Task address'));
            $cells.= wf_TableCell($each['address']);
            $rows.= wf_TableRow($cells);

            $cells = wf_TableCell(__('Phone'));
            $cells.= wf_TableCell($each['phone']);
            $rows.= wf_TableRow($cells);

            $cells = wf_TableCell(__('Job type'));
            $cells.= wf_TableCell(@$alljobtypes[$each['jobtype']]);
            $rows.= wf_TableRow($cells);

            $cells = wf_TableCell(__('Who should do'));
            $cells.= wf_TableCell(@$allemployee[$each['employee']]);
            $rows.= wf_TableRow($cells);

            $cells = wf_TableCell(__('Job note'));
            $cells.= wf_TableCell($each['jobnote']);
            $rows.= wf_TableRow($cells);
            $tasktable = wf_TableBody($rows, '100%', '0', 'gridtable');
            $result.= wf_tag('div', false, '', 'style="width: 300px; height: 250px; float: left; border: dashed; border-width:1px; margin:5px; page-break-inside: avoid;"');
            $result.= $tasktable;
            $result.= wf_tag('div', true);
        }
        $result.= wf_tag('script', false, '', 'language="javascript"');
        $result.= 'window.print();';
        $result.= wf_tag('script', true);
        die($result);
    }
}

/**
 * Renders printable tasks filtered by dates
 * 
 * @param string $datefrom
 * @param string $dateto
 * 
 * @return void
 */
function ts_PrintTasksTable($datefrom, $dateto) {
    $datefrom = mysql_real_escape_string($datefrom);
    $dateto = mysql_real_escape_string($dateto);
    $allemployee = ts_GetAllEmployee();
    $alljobtypes = ts_GetAllJobtypes();
    $tmpArr = array();
    $result = wf_tag('style');
    $result.= '
        table.gridtable {
            font-family: verdana,arial,sans-serif;
            font-size:9pt;
            color:#333333;
            border-width: 1px;
            border-color: #666666;
            border-collapse: collapse;
            page-break-after: always;
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
    $result.= wf_tag('style', true);

    $query = "select * from `taskman` where `startdate` BETWEEN '" . $datefrom . " 00:00:00' AND '" . $dateto . " 23:59:59' AND `status`='0' ORDER BY `address`";
    $alltasks = simple_queryall($query);
    if (!empty($alltasks)) {
        foreach ($alltasks as $io => $each) {
            $tmpArr[$each['employee']][] = $each;
        }

        if (!empty($tmpArr)) {
            foreach ($tmpArr as $eachEmployeeId => $eachEmployeeTasks) {
                if (!empty($eachEmployeeId)) {
                    $result.=wf_tag('h2') . @$allemployee[$eachEmployeeId] . wf_tag('h2', true);
                    if (!empty($eachEmployeeTasks)) {
                        $cells = wf_TableCell(__('Target date'));
                        $cells.= wf_TableCell(__('Task address'));
                        $cells.= wf_TableCell(__('Phone'));
                        $cells.= wf_TableCell(__('Job type'));
                        $cells.= wf_TableCell(__('Job note'));
                        $cells.= wf_TableCell(__('Additional comments'));
                        $rows = wf_TableRow($cells, 'row1');
                        foreach ($eachEmployeeTasks as $io => $each) {
                            $cells = wf_TableCell($each['startdate'] . ' ' . wf_tag('b') . @$each['starttime'] . wf_tag('b', true));
                            $cells.= wf_TableCell($each['address']);
                            $cells.= wf_TableCell($each['phone']);
                            $cells.= wf_TableCell(@$alljobtypes[$each['jobtype']]);
                            $cells.= wf_TableCell(nl2br($each['jobnote']));
                            $cells.= wf_TableCell('');
                            $rows.= wf_TableRow($cells, 'row3');
                        }
                        $result.=wf_TableBody($rows, '100%', 0, 'gridtable');
                    }
                }
            }
        }

        $result.= wf_tag('script', false, '', 'language="javascript"');
        $result.= 'window.print();';
        $result.= wf_tag('script', true);
        die($result);
    }
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
    $cells.= wf_TableCell(__('Task address'));
    $cells.= wf_TableCell(__('Phone'));
    $cells.= wf_TableCell(__('Job type'));
    $cells.= wf_TableCell(__('Who should do'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['startdate']);
            $cells.= wf_TableCell($each['address']);
            $cells.= wf_TableCell($each['phone']);
            $cells.= wf_TableCell(@$alljobtypes[$each['jobtype']]);
            $cells.= wf_TableCell(@$allemployee[$each['employee']]);
            $actions = wf_Link('?module=taskman&edittask=' . $each['id'], web_edit_icon(), false, '');
            $cells.= wf_TableCell($actions);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    return ($result);
}

/**
 * Gets employee by administrator login
 * 
 * @param sting $login logged in administrators login
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
 * Returns count of undone tasks - used by Warehouse and another weird things
 * 
 * @return array
 */
function ts_GetUndoneTasksArray() {
    $result = array();
    $curdate = curdate();
    $filters = "ORDER BY `address`,`jobtype` ASC";
    $query = "SELECT * from `taskman` WHERE `status` = '0' AND `startdate` <= '" . $curdate . "' " . $filters;
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

function ts_AdvFiltersControls() {
    $alljobtypes = ts_GetAllJobtypes();
    $alljobtypes = array('0' => __('Any')) + $alljobtypes;
    $selectedjobtype = ( wf_CheckPost(array('filtertaskjobtypeexact')) ) ? $_POST['filtertaskjobtypeexact'] : '';
    $jobtypecontains = ( wf_CheckPost(array('filtertaskjobtype')) ) ? $_POST['filtertaskjobtype'] : '';
    $addresscontains = ( wf_CheckPost(array('filtertaskaddr')) ) ? $_POST['filtertaskaddr'] : '';
    $jobnotecontains = ( wf_CheckPost(array('filtertaskjobnote')) ) ? $_POST['filtertaskjobnote'] : '';

    $inputs = wf_tag('h3', false, '', 'style="margin: 1px 5px 1px 10px; display: inline-block"');
    $inputs .= __('Job type');
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_Selector('filtertaskjobtypeexact', $alljobtypes, '', $selectedjobtype);

    $inputs .= wf_tag('h3', false, '', 'style="margin: 1px 5px 1px 10px; display: inline-block"');
    $inputs .= __('Job type contains');
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_TextInput('filtertaskjobtype', '', $jobtypecontains, true);
    $inputs .= wf_tag('br');

    $inputs .= wf_tag('h3', false, '', 'style="margin: 1px 5px 1px 1px; display: inline-block"');
    $inputs .= __('Address contains');
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_TextInput('filtertaskaddr', '', $addresscontains);

    $inputs .= wf_tag('h3', false, '', 'style="margin: 1px 5px 1px 10px; display: inline-block"');
    $inputs .= __('Job note contains');
    $inputs .= wf_tag('h3', true);
    $inputs .= wf_TextInput('filtertaskjobnote', '', $jobnotecontains);
    $inputs .= '&nbsp&nbsp&nbsp';

    return($inputs);
}

function ts_AdvFiltersQuery() {
    $AppendQuery = '';

    if (wf_CheckPost(array('filtertaskjobtypeexact'))) {
        $AppendQuery .= " AND `jobtype` = " . $_POST['filtertaskjobtypeexact'];
    } elseif (wf_CheckPost(array('filtertaskjobtype'))) {
        $AppendQuery .= " AND `jobname` LIKE '%" . $_POST['filtertaskjobtype'] . "%'";
    }

    if (wf_CheckPost(array('filtertaskaddr'))) {
        $AppendQuery .= " AND `address` LIKE '%" . $_POST['filtertaskaddr'] . "%'";
    }

    if (wf_CheckPost(array('filtertaskjobnote'))) {
        $AppendQuery .= " AND `jobnote` LIKE '%" . $_POST['filtertaskjobnote'] . "%'";
    }

    return ($AppendQuery);
}

?>
