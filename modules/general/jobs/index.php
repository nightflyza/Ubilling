<?php

if (cfr('EMPLOYEE')) {
    if (ubRouting::checkGet('username')) {
        $username = ubRouting::get('username');

        if (ubRouting::checkPost('addjob')) {
            if (cfr('JOBSMGMT')) {
                $date = ubRouting::post('jobdate');
                $worker_id = ubRouting::post('worker');
                $jobtype_id = ubRouting::post('jobtype');
                $job_notes = ubRouting::post('notes');
                stg_add_new_job($username, $date, $worker_id, $jobtype_id, $job_notes);
                ubRouting::nav("?module=jobs&username=" . $username);
            } else {
                show_error(__('Access denied'));
                log_register('JOB ADD FAIL ACCESS VIOLATION');
            }
        }

        if (ubRouting::checkGet('deletejob')) {
            if (cfr('JOBSMGMT')) {
                stg_delete_job(ubRouting::get('deletejob'));
                ubRouting::nav("?module=jobs&username=" . $username);
            } else {
                show_error(__('Access denied'));
                log_register('JOB DELETE FAIL ACCESS VIOLATION');
            }
        }
        //just render jobs list
        web_showPreviousJobs($username);

        //previous tasks
        if (cfr('TASKMAN')) {
            $previousUserTasks = ts_PreviousUserTasksRender($username, '', true);
            if (!empty($previousUserTasks)) {
                show_window(__('Previous user tasks'), $previousUserTasks);
            }
        }
        show_window('', web_UserControls($username));
    }
} else {
    show_error(__('Access denied'));
}


