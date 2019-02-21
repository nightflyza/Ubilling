<?php

if (cfr('EMPLOYEE')) {
    if (isset($_GET['username'])) {
        $username = $_GET['username'];

        if (isset($_POST['addjob'])) {
            $date = $_POST['jobdate'];
            $worker_id = $_POST['worker'];
            $jobtype_id = $_POST['jobtype'];
            $job_notes = $_POST['notes'];
            stg_add_new_job($username, $date, $worker_id, $jobtype_id, $job_notes);
            rcms_redirect("?module=jobs&username=" . $username);
        }

        if (isset($_GET['deletejob'])) {
            stg_delete_job($_GET['deletejob']);
            rcms_redirect("?module=jobs&username=" . $username);
        }
        //just jobs
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
?>


