<?php

/**
 * Renders employee list with required controls and creation form
 * 
 * @return void
 */
function em_EmployeeShowForm() {
    $show_q = "SELECT * from `employee`";
    $allemployee = simple_queryall($show_q);

    $cells = wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('Real Name'));
    $cells.= wf_TableCell(__('Active'));
    $cells.= wf_TableCell(__('Appointment'));
    $cells.= wf_TableCell(__('Mobile'));
    $cells.= wf_TableCell(__('Administrator'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allemployee)) {
        foreach ($allemployee as $ion => $eachemployee) {
            $cells = wf_TableCell($eachemployee['id']);
            $cells.= wf_TableCell($eachemployee['name']);
            $cells.= wf_TableCell(web_bool_led($eachemployee['active']));
            $cells.= wf_TableCell($eachemployee['appointment']);
            $cells.= wf_TableCell($eachemployee['mobile']);
            $admlogin = $eachemployee['admlogin'];
            if (!empty($admlogin)) {
                if (file_exists(USERS_PATH . $admlogin)) {
                    $admlogin = wf_Link('?module=permissions&edit=' . $admlogin, web_profile_icon() . ' ' . $admlogin, false);
                }
            }
            $cells.= wf_TableCell($admlogin);
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
    $inputs.= wf_TableCell(wf_TextInput('employeeadmlogin', '', '', false, 10));
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
            $actionlinks.=wf_JSAlert('?module=employee&editjob=' . $eachjob['id'], web_edit_icon(), 'Are you serious');
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

    $result = wf_TableBody($rows, '100%', '0', 'sortable');

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
function em_EmployeeAdd($name, $job, $mobile = '', $admlogin = '') {
    $name = mysql_real_escape_string(trim($name));
    $job = mysql_real_escape_string(trim($job));
    $mobile = mysql_real_escape_string($mobile);
    $admlogin = mysql_real_escape_string($admlogin);
    $query = "INSERT INTO `employee` (`id` , `name` , `appointment`, `mobile`, `admlogin`,`active`)
            VALUES (NULL , '" . $name . "', '" . $job . "','" . $mobile . "', '" . $admlogin . "' , '1'); ";
    nr_query($query);
    log_register('EMPLOYEE ADD `' . $name . '` JOB `' . $job . '`');
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
    $cells.=wf_tableCell(__('Date'));
    $cells.=wf_TableCell(__('Worker'));
    $cells.=wf_TableCell(__('Job type'));
    $cells.=wf_TableCell(__('Notes'));
    $cells.=wf_TableCell('');
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
            $cells.=wf_tableCell($eachjob['date']);
            $cells.=wf_TableCell(@$allemployee[$eachjob['workerid']]);
            $cells.=wf_TableCell(@$alljobtypes[$eachjob['jobid']]);
            $cells.=wf_TableCell($jobnote);
            $cells.=wf_TableCell(wf_JSAlert('?module=jobs&username=' . $username . '&deletejob=' . $eachjob['id'] . '', web_delete_icon(), 'Are you serious'));
            $rows.= wf_TableRow($cells, 'row3');
        }
    }

    //onstruct job create form
    $curdatetime = curdatetime();
    $inputs = wf_HiddenInput('addjob', 'true');
    $inputs.=wf_HiddenInput('jobdate', $curdatetime);
    $inputs.=wf_TableCell('');
    $inputs.=wf_tableCell($curdatetime);
    $inputs.=wf_TableCell(stg_worker_selector());
    $inputs.=wf_TableCell(stg_jobtype_selector());
    $inputs.=wf_TableCell(wf_TextInput('notes', '', '', false, '20'));
    $inputs.=wf_TableCell(wf_Submit('Create'));
    $inputs = wf_TableRow($inputs, 'row2');

    $addform = wf_Form("", 'POST', $inputs, '');

    if ((!empty($activeemployee)) AND ( !empty($alljobtypes))) {
        $rows.=$addform;
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
    log_register("DELETE JOB [" . $jobid . "]");
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
    log_register("ADD JOB W:[" . $worker_id . "] J:[" . $jobtype_id . "] (" . $login . ")");
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

    if (($curmonth != 1) AND ( $curmonth != 12)) {
        $query = "SELECT * from `taskman` WHERE `status`='0' AND `startdate` LIKE '" . $curyear . "-%' " . $appendQuery . " ORDER BY `date` ASC";
    } else {
        $query = "SELECT * from `taskman` WHERE `status`='0' " . $appendQuery . " ORDER BY `date` ASC";
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

    if (($curmonth != 1) AND ( $curmonth != 12)) {
        $query = "SELECT * from `taskman` WHERE `status`='1' AND `startdate` LIKE '" . $curyear . "-%' " . $appendQuery . " ORDER BY `date` ASC";
    } else {
        $query = "SELECT * from `taskman` WHERE `status`='1' " . $appendQuery . " ORDER BY `date` ASC";
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

    if (($curmonth != 1) AND ( $curmonth != 12)) {
        $query = "SELECT * from `taskman` WHERE `startdate` LIKE '" . $curyear . "-%' " . $appendQuery . " ORDER BY `date` ASC";
    } else {
        if ($appendQuery) {
            $appendQuery = str_replace('AND', 'WHERE', $appendQuery);
        }
        $query = "SELECT * from `taskman` " . $appendQuery . " ORDER BY `date` ASC";
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
    if ($altercfg['WATCHDOG_ENABLED']) {
        $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, false);
    } else {
        $smsInputs = '';
    }

    $inputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
    $inputs.= wf_HiddenInput('createtask', 'true');
    $inputs.=wf_DatePicker('newstartdate');
    $inputs.=wf_TimePickerPreset('newstarttime', '', '', false);
    $inputs.=wf_tag('label') . __('Target date') . wf_tag('sup') . '*' . wf_tag('sup', true) . wf_tag('label', true);
    $inputs.=wf_delimiter();

    if (!$altercfg['SEARCHADDR_AUTOCOMPLETE']) {
        $inputs.=wf_TextInput('newtaskaddress', __('Address') . '<sup>*</sup>', '', true, '30');
    } else {
        $allAddress = zb_AddressGetFulladdresslistCached();
        natsort($allAddress);
        $inputs.=wf_AutocompleteTextInput('newtaskaddress', $allAddress, __('Address') . '<sup>*</sup>', '', true, '30');
    }
    $inputs.=wf_tag('br');
    //hidden for new task login input
    $inputs.=wf_HiddenInput('newtasklogin', '');

    $inputs.=wf_TextInput('newtaskphone', __('Phone') . '<sup>*</sup>', '', true, '30');
    $inputs.=wf_tag('br');
    $inputs.=wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
    $inputs.=wf_tag('br');
    $inputs.=wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
    $inputs.=wf_tag('br');
    $inputs.=ts_TaskTypicalNotesSelector();
    $inputs.=wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
    $inputs.=wf_TextArea('newjobnote', '', '', true, '35x5');
    $inputs.=$smsInputs;
    $inputs.=wf_Submit(__('Create new task'));
    $result = wf_Form("", 'POST', $inputs, 'glamour');
    $result.=__('All fields marked with an asterisk are mandatory');
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
    $altercfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $alljobtypes = ts_GetAllJobtypes();
    $allemployee = ts_GetActiveEmployee();

    //construct sms sending inputs
    if ($altercfg['WATCHDOG_ENABLED']) {
        $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, false);
    } else {
        $smsInputs = '';
    }

    $sup = wf_tag('sup', false) . '*' . wf_tag('sup', true);

    $inputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
    $inputs.=wf_HiddenInput('createtask', 'true');
    $inputs.=wf_DatePicker('newstartdate');
    $inputs.=wf_TimePickerPreset('newstarttime', '', '', false);
    $inputs.=wf_tag('label') . __('Target date') . $sup . wf_tag('label', true);
    $inputs.=wf_delimiter();
    $inputs.=wf_TextInput('newtaskaddress', __('Address') . $sup, $address, true, '30');
    //hidden for new task login input
    $inputs.=wf_HiddenInput('newtasklogin', $login);
    $inputs.=wf_tag('br');
    $inputs.=wf_TextInput('newtaskphone', __('Phone') . $sup, $mobile . ' ' . $phone, true, '30');
    $inputs.=wf_tag('br');
    $inputs.=wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
    $inputs.=wf_tag('br');
    $inputs.=wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
    $inputs.=wf_tag('br');
    $inputs.=wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
    $inputs.=ts_TaskTypicalNotesSelector();
    $inputs.=wf_TextArea('newjobnote', '', '', true, '35x5');
    $inputs.=$smsInputs;
    $inputs.=wf_Submit(__('Create new task'));
    if (!empty($login)) {
        $inputs.=wf_AjaxLoader();
        $inputs.=' ' . wf_AjaxLink('?module=prevtasks&username=' . $login, wf_img_sized('skins/icon_search_small.gif', __('Previous user tasks')), 'taskshistorycontainer', false, '');
        $inputs.=wf_tag('br');
        $inputs.=wf_tag('div', false, '', 'id="taskshistorycontainer"') . wf_tag('div', true);
    }
    $result = wf_Form("?module=taskman&gotolastid=true", 'POST', $inputs, 'glamour');
    $result.=__('All fields marked with an asterisk are mandatory');
    return ($result);
}

/**
 * Renders list of all previous user tasks by current year
 * 
 * @param string $login
 * 
 * @return string
 */
function ts_PreviousUserTasksRender($login) {
    $result = '';
    if (!empty($login)) {
        $alljobtypes = ts_GetAllJobtypes();
        $allemployee = ts_GetActiveEmployee();
        $dateMask=date("Y").'-%';
        
        $query = "SELECT * from `taskman` WHERE `login`='" . $login . "' AND `date` LIKE '".$dateMask."' ORDER BY `id` DESC;";
        $allTasks = simple_queryall($query);
        if (!empty($allTasks)) {
            $result.=wf_tag('hr');
            foreach ($allTasks as $io => $each) {
                $taskColor=($each['status']) ? 'donetask' : 'undone';
                $result.=wf_tag('div', false, $taskColor, 'style="width:400px;"');
                $taskdata=$each['startdate'].' - '.@$alljobtypes[$each['jobtype']].', '.@$allemployee[$each['employee']];
                $result.= wf_link('?module=taskman&edittask='.$each['id'],  wf_img('skins/icon_edit.gif')).' '.$taskdata;
                $result.= wf_tag('div', true);
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
    if ($altercfg['WATCHDOG_ENABLED']) {
        $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, false);
    } else {
        $smsInputs = '';
    }

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

    $inputs = '<!--ugly hack to prevent datepicker autoopen -->';
    $inputs.= wf_tag('input', false, '', 'type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"');
    $inputs.=wf_HiddenInput('createtask', 'true');
    $inputs.=wf_DatePicker('newstartdate');
    $inputs.=wf_TimePickerPreset('newstarttime', '', '', false);
    $inputs.=wf_tag('label') . __('Target date') . $sup . wf_tag('label', true);
    $inputs.=wf_delimiter();
    $inputs.=wf_TextInput('newtaskaddress', __('Address') . $sup, $address, true, '30');
    $inputs.=wf_HiddenInput('newtasklogin', $login);
    $inputs.=wf_tag('br');
    $inputs.=wf_TextInput('newtaskphone', __('Phone') . $sup, $mobile . ' ' . $phone, true, '30');
    $inputs.=wf_tag('br');
    $inputs.=wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
    $inputs.=wf_tag('br');
    $inputs.=wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
    $inputs.=wf_tag('br');
    $inputs.=wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
    $inputs.=ts_TaskTypicalNotesSelector();
    $inputs.=wf_TextArea('newjobnote', '', '', true, '35x5');
    $inputs.=$smsInputs;
    $inputs.=wf_Submit(__('Create new task'));
    $result = wf_Form("?module=taskman&gotolastid=true", 'POST', $inputs, 'glamour');
    $result.=__('All fields marked with an asterisk are mandatory');
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
    if ($altercfg['WATCHDOG_ENABLED']) {
        $smsInputs = wf_CheckInput('newtasksendsms', __('Send SMS'), false, false);
    } else {
        $smsInputs = '';
    }

    $inputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
    $inputs.=wf_HiddenInput('createtask', 'true');
    $inputs.=wf_DatePicker('newstartdate');
    $inputs.=wf_TimePickerPreset('newstarttime', '', '', false);
    $inputs.=wf_tag('label') . __('Target date') . wf_tag('sup') . '*' . wf_tag('sup', true) . wf_tag('label', true);
    $inputs.=wf_delimiter();
    $inputs.=wf_TextInput('newtaskaddress', __('Address') . '<sup>*</sup>', $address, true, '30');
    //hidden for new task login input
    $inputs.=wf_HiddenInput('newtasklogin', '');
    $inputs.=wf_tag('br');
    $inputs.=wf_TextInput('newtaskphone', __('Phone') . '<sup>*</sup>', $phone, true, '30');
    $inputs.=wf_tag('br');
    $inputs.=wf_Selector('newtaskjobtype', $alljobtypes, __('Job type'), '', true);
    $inputs.=wf_tag('br');
    $inputs.=wf_Selector('newtaskemployee', $allemployee, __('Who should do'), '', true);
    $inputs.=wf_tag('br');
    $inputs.=wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
    $inputs.=ts_TaskTypicalNotesSelector();
    $inputs.=wf_TextArea('newjobnote', '', '', true, '35x5');
    $inputs.=$smsInputs;
    $inputs.=wf_Submit(__('Create new task'));
    $result = wf_Form("?module=taskman&gotolastid=true", 'POST', $inputs, 'glamour');
    $result.=__('All fields marked with an asterisk are mandatory');
    return ($result);
}

/**
 * Returns taskman controls
 * 
 * @return string
 */
function ts_ShowPanel() {
    $createform = ts_TaskCreateForm();
    $result = wf_modal(wf_img('skins/add_icon.png') . ' ' . __('Create task'), __('Create task'), $createform, 'ubButton', '450', '550');
    $result.=wf_Link('?module=taskman&show=undone', wf_img('skins/undone_icon.png') . ' ' . __('Undone tasks'), false, 'ubButton');
    $result.=wf_Link('?module=taskman&show=done', wf_img('skins/done_icon.png') . ' ' . __('Done tasks'), false, 'ubButton');
    $result.=wf_Link('?module=taskman&show=all', wf_img('skins/icon_calendar.gif') . ' ' . __('All tasks'), false, 'ubButton');
    if (cfr('TASKMANSEARCH')) {
        $result.=wf_Link('?module=tasksearch', web_icon_search() . ' ' . __('Tasks search'), false, 'ubButton');
    }

    if (cfr('TASKMANTRACK')) {
        $result.=wf_Link('?module=taskmantrack', wf_img('skins/track_icon.png') . ' ' . __('Tracking'), false, 'ubButton');
    }
    $result.=wf_Link('?module=taskman&print=true', wf_img('skins/icon_print.png') . ' ' . __('Tasks printing'), false, 'ubButton');

    //show type selector
    $whoami = whoami();
    $employeeid = ts_GetEmployeeByLogin($whoami);
    if ($employeeid) {
        $result.=wf_delimiter();
        $curselected = (isset($_POST['displaytype'])) ? $_POST['displaytype'] : '';
        $displayTypes = array('all' => __('Show tasks for all users'), 'onlyme' => __('Show only mine tasks'));
        $inputs = wf_Selector('displaytype', $displayTypes, '', $curselected, false);
        $inputs.= wf_Submit('Show');
        $showTypeForm = wf_Form('', 'POST', $inputs, 'glamour');
        $result.=$showTypeForm;
    }

    return ($result);
}

/**
 * Stores SMS for some employee for further sending with watchdog run
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
    $employeeName = $empData['name'];
    $result = array();
    if (!empty($mobile)) {
        if (ispos($mobile, '+')) {
            $message = str_replace('\r\n', ' ', $message);
            $message = zb_TranslitString($message);
            $message = trim($message);

            $number = trim($mobile);
            $filename = 'content/tsms/ts_' . zb_rand_string(8);
            $storedata = 'NUMBER="' . $number . '"' . "\n";
            $storedata.='MESSAGE="' . $message . '"' . "\n";
            $result['number'] = $number;
            $result['message'] = $message;
            file_put_contents($filename, $storedata);
            log_register("TASKMAN SEND SMS `" . $number . "` FOR `" . $employeeName . "`");
        } else {
            throw new Exception('BAD_MOBILE_FORMAT');
        }
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
    $altercfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $curdate = curdatetime();
    $admin = whoami();
    $address = str_replace('\'', '`', $address);
    $address = mysql_real_escape_string($address);
    $login = mysql_real_escape_string($login);
    $phone = mysql_real_escape_string($phone);
    $startdate = mysql_real_escape_string($startdate);
    $jobSendTime = (!empty($starttime)) ? ' ' . date("H:i", strtotime($starttime)) : '';

    if (!empty($starttime)) {
        $starttime = "'" . mysql_real_escape_string($starttime) . "'";
    } else {
        $starttime = 'NULL';
    }
    $jobtypeid = vf($jobtypeid, 3);
    $employeeid = vf($employeeid, 3);
    $jobnote = mysql_real_escape_string($jobnote);

    $smsData = 'NULL';
    //store sms for backround processing via watchdog
    if ($altercfg['WATCHDOG_ENABLED']) {
        if (isset($_POST['newtasksendsms'])) {
            $newSmsText = $address . ' ' . $phone . ' ' . $jobnote . $jobSendTime;
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

    //flushing darkvoid
    $darkVoid = new DarkVoid();
    $darkVoid->flushCache();

    log_register("TASKMAN CREATE `" . $address . "`");
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
    if (!empty($taskdata)) {
        $inputs = wf_HiddenInput('modifytask', $taskid);
        $inputs.='<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhackmod" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
        if (cfr('TASKMANDATE')) {
            $inputs.=wf_DatePickerPreset('modifystartdate', $taskdata['startdate']);
        } else {
            $inputs.=wf_HiddenInput('modifystartdate', $taskdata['startdate']);
        }
        $inputs.=wf_TimePickerPreset('modifystarttime', $taskdata['starttime'], '', false);
        $inputs.=wf_tag('label') . __('Target date') . wf_tag('sup') . '*' . wf_tag('sup', true) . wf_tag('label', true);
        $inputs.=wf_delimiter();
        $inputs.=wf_tag('br');
        if ($altercfg['SEARCHADDR_AUTOCOMPLETE']) {
            $alladdress = zb_AddressGetFulladdresslistCached();
            natsort($alladdress);
            $inputs.=wf_AutocompleteTextInput('modifytaskaddress', $alladdress, __('Address') . '<sup>*</sup>', $taskdata['address'], true, '30');
        } else {
            $inputs.=wf_TextInput('modifytaskaddress', __('Address') . '<sup>*</sup>', $taskdata['address'], true, '30');
        }
        $inputs.=wf_tag('br');
        //custom login text input
        $inputs.=wf_TextInput('modifytasklogin', __('Login'), $taskdata['login'], true, 30);
        $inputs.=wf_tag('br');
        $inputs.=wf_TextInput('modifytaskphone', __('Phone') . '<sup>*</sup>', $taskdata['phone'], true, '30');
        $inputs.=wf_tag('br');
        $inputs.=wf_Selector('modifytaskjobtype', $alljobtypes, __('Job type'), $taskdata['jobtype'], true);
        $inputs.=wf_tag('br');
        $inputs.=wf_Selector('modifytaskemployee', $activeemployee, __('Who should do'), $taskdata['employee'], true);
        $inputs.=wf_tag('br');
        $inputs.=wf_tag('label') . __('Job note') . wf_tag('label', true) . wf_tag('br');
        $inputs.=wf_TextArea('modifytaskjobnote', '', $taskdata['jobnote'], true, '35x5');
        $inputs.=wf_Submit(__('Save'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');
        $result.=__('All fields marked with an asterisk are mandatory');
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
    if (!empty($starttime)) {
        $starttime = "'" . mysql_real_escape_string($starttime) . "'";
    } else {
        $starttime = 'NULL';
    }

    $address = str_replace('\'', '`', $address);
    $address = mysql_real_escape_string($address);
    $login = mysql_real_escape_string($login);
    $phone = mysql_real_escape_string($phone);
    $jobtypeid = vf($jobtypeid, 3);
    $employeeid = vf($employeeid, 3);

    simple_update_field('taskman', 'startdate', $startdate, "WHERE `id`='" . $taskid . "'");
    nr_query("UPDATE `taskman` SET `starttime` = " . $starttime . " WHERE `id`='" . $taskid . "'"); //that shit for preventing quotes
    simple_update_field('taskman', 'address', $address, "WHERE `id`='" . $taskid . "'");
    simple_update_field('taskman', 'login', $login, "WHERE `id`='" . $taskid . "'");
    simple_update_field('taskman', 'phone', $phone, "WHERE `id`='" . $taskid . "'");
    simple_update_field('taskman', 'jobtype', $jobtypeid, "WHERE `id`='" . $taskid . "'");
    simple_update_field('taskman', 'employee', $employeeid, "WHERE `id`='" . $taskid . "'");
    simple_update_field('taskman', 'jobnote', $jobnote, "WHERE `id`='" . $taskid . "'");
    log_register("TASKMAN MODIFY [" . $taskid . "] `" . $address . '`');
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
            if ($altercfg['WATCHDOG_ENABLED']) {
                $smsAddress = str_replace('\'', '`', $taskdata['address']);
                $smsAddress = mysql_real_escape_string($smsAddress);
                $smsPhone = mysql_real_escape_string($taskdata['phone']);
                $smsJobTime = (!empty($taskdata['starttime'])) ? ' ' . date("H:i", strtotime($taskdata['starttime'])) : '';
                $smsJobNote = mysql_real_escape_string($taskdata['jobnote']);
                $smsEmployee = vf($taskdata['employee']);

                $newSmsText = $smsAddress . ' ' . $smsPhone . ' ' . $smsJobNote . $smsJobTime;

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

        $tablecells = wf_TableCell(__('ID'), '30%');
        $tablecells.= wf_TableCell($taskdata['id']);
        $tablerows = wf_TableRow($tablecells, 'row3');

        $tablecells = wf_TableCell(__('Task creation date') . ' / ' . __('Administrator'));
        $tablecells.= wf_TableCell($taskdata['date'] . ' / ' . $taskdata['admin']);
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

        //Salary accounting
        if ($altercfg['SALARY_ENABLED']) {
            if (cfr('SALARYTASKSVIEW')) {
                $salary = new Salary();
                show_window(__('Additional jobs done'), $salary->taskJobCreateForm($_GET['edittask']));
            }
        }

        //warehouse integration
        if ($altercfg['WAREHOUSE_ENABLED']) {
            if (cfr('WAREHOUSE')) {
                $warehouse = new Warehouse();
                show_window(__('Additionally spent materials'), $warehouse->taskMaterialsReport($_GET['edittask']));
            }
        }


        //if task undone
        if ($taskdata['status'] == 0) {
            $sup = wf_tag('sup') . '*' . wf_tag('sup', false);
            $inputs = wf_HiddenInput('changetask', $taskid);
            $inputs.=wf_DatePicker('editenddate') . wf_tag('label', false) . __('Finish date') . $sup . wf_tag('label', true) . wf_tag('br');
            $inputs.=wf_tag('br');
            $inputs.=wf_Selector('editemployeedone', $activeemployee, __('Worker done'), $taskdata['employee'], true);
            $inputs.=wf_tag('br');
            $inputs.=wf_tag('label', false) . __('Finish note') . wf_tag('label', true) . wf_tag('br');
            $inputs.=wf_TextArea('editdonenote', '', '', true, '35x3');
            $inputs.=wf_tag('br');
            $inputs.= $jobgencheckbox;
            $inputs.=wf_Submit(__('This task is done'));


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
            $donecells.=wf_TableCell($taskdata['enddate']);
            $donerows = wf_TableRow($donecells, 'row3');

            $donecells = wf_TableCell(__('Worker done'));
            $donecells.=wf_TableCell($allemployee[$taskdata['employeedone']]);
            $donerows.=wf_TableRow($donecells, 'row3');

            $donecells = wf_TableCell(__('Finish note'));
            $donecells.=wf_TableCell($taskdata['donenote']);
            $donerows.=wf_TableRow($donecells, 'row3');

            $doneresult = wf_TableBody($donerows, '100%', '0', 'glamour');

            if (cfr('TASKMANDELETE')) {
                $doneresult.=wf_JSAlertStyled('?module=taskman&deletetask=' . $taskid, web_delete_icon() . ' ' . __('Remove this task - it is an mistake'), $messages->getDeleteAlert(), 'ubButton');
            }

            if (cfr('TASKMANDONE')) {
                $doneresult.='&nbsp;';
                $doneresult.=wf_JSAlertStyled('?module=taskman&setundone=' . $taskid, wf_img('skins/icon_key.gif') . ' ' . __('No work was done'), $messages->getEditAlert(), 'ubButton');
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
    $query = "DELETE from `taskman` WHERE `id`='" . $taskid . "'";
    nr_query($query);
    log_register("TASKMAN DELETE " . $taskid);
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
    $result = wf_Link("?module=taskman", __('Back'), true, 'ubButton');

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
    $inputs = wf_DatePickerPreset('printdatefrom', curdate()) . ' ' . __('From');
    $inputs.= wf_DatePickerPreset('printdateto', curdate()) . ' ' . __('To');
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
        $result.='<script language="javascript"> 
                        window.print();
                    </script>';
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
 * Returns count of undone tasks - used by DarkVoid
 * 
 * @return array
 */
function ts_GetUndoneTasksArray($year = '') {
    $result = array();
    $curdate = curdate();
    $curyear = (!empty($year)) ? $year : curyear();
    $query = "SELECT * from `taskman` WHERE `status` = '0' AND `startdate` <= '" . $curdate . "'";
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

?>
