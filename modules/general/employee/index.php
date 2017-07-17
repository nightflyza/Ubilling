<?php

// check for right of current admin on this module
if (cfr('EMPLOYEEDIR')) {
    $ubCache = new UbillingCache();

    if (wf_CheckPost(array('addemployee', 'employeename'))) {
        em_EmployeeAdd($_POST['employeename'], $_POST['employeejob'], @$_POST['employeemobile'], @$_POST['employeetelegram'], @$_POST['employeeadmlogin'], @$_POST['editadtagid']);
        $ubCache->delete('EMPLOYEE_LOGINS');
        rcms_redirect("?module=employee");
    }

    if (isset($_GET['delete'])) {
        em_EmployeeDelete($_GET['delete']);
        $ubCache->delete('EMPLOYEE_LOGINS');
        rcms_redirect("?module=employee");
    }

    if (wf_CheckPost(array('addjobtype', 'newjobtype'))) {
        stg_add_jobtype($_POST['newjobtype'], $_POST['newjobcolor']);
    }

    if (isset($_GET['deletejob'])) {
        stg_delete_jobtype($_GET['deletejob']);
        rcms_redirect("?module=employee");
    }

    if (!wf_CheckGet(array('edit'))) {
        if (!wf_CheckGet(array('editjob'))) {
            //display normal tasks
            em_EmployeeShowForm();
            em_JobTypeForm();
        } else {
            //show jobeditor
            $editjob = vf($_GET['editjob']);

            //edit job subroutine
            if (wf_CheckPost(array('editjobtype'))) {
                simple_update_field('jobtypes', 'jobname', $_POST['editjobtype'], "WHERE `id`='" . $editjob . "'");
                simple_update_field('jobtypes', 'jobcolor', $_POST['editjobcolor'], "WHERE `id`='" . $editjob . "'");
                log_register('JOBTYPE CHANGE [' . $editjob . '] `' . $_POST['editjobtype'] . '`');
                rcms_redirect("?module=employee");
            }

            //edit jobtype form
            $jobdata = stg_get_jobtype_name($editjob);
            $jobcolor = stg_get_jobtype_color($editjob);
            $jobinputs = wf_TextInput('editjobtype', 'Job type', $jobdata, true, 20);
            $jobinputs.= wf_ColPicker('editjobcolor', __('Color'), $jobcolor, true, 10);
            $jobinputs.=wf_Submit('Save');
            $jobform = wf_Form("", "POST", $jobinputs, 'glamour');
            show_window(__('Edit'), $jobform);
            show_window('', wf_BackLink('?module=employee', 'Back', true, 'ubButton'));
        }
    } else {
        $editemployee = vf($_GET['edit'], 3);

        //if someone editing employee
        if (isset($_POST['editname'])) {
            simple_update_field('employee', 'name', $_POST['editname'], "WHERE `id`='" . $editemployee . "'");
            simple_update_field('employee', 'appointment', $_POST['editappointment'], "WHERE `id`='" . $editemployee . "'");
            simple_update_field('employee', 'mobile', $_POST['editmobile'], "WHERE `id`='" . $editemployee . "'");
            simple_update_field('employee', 'telegram', $_POST['edittelegram'], "WHERE `id`='" . $editemployee . "'");
            simple_update_field('employee', 'admlogin', $_POST['editadmlogin'], "WHERE `id`='" . $editemployee . "'");
            simple_update_field('employee', 'tagid', $_POST['editadtagid'], "WHERE `id`='" . $editemployee . "'");

            if (wf_CheckPost(array('editactive'))) {
                simple_update_field('employee', 'active', '1', "WHERE `id`='" . $editemployee . "'");
            } else {
                simple_update_field('employee', 'active', '0', "WHERE `id`='" . $editemployee . "'");
            }
            log_register('EMPLOYEE CHANGE [' . $editemployee . ']');
            $ubCache->delete('EMPLOYEE_LOGINS');
            rcms_redirect("?module=employee");
        }


        $employeedata = stg_get_employee_data($editemployee);
        if ($employeedata['active']) {
            $actflag = true;
        } else {
            $actflag = false;
        }
        $editinputs = wf_TextInput('editname', 'Real Name', $employeedata['name'], true, 20);
        $editinputs.=wf_TextInput('editappointment', 'Appointment', $employeedata['appointment'], true, 20);
        $editinputs.=wf_TextInput('editmobile', __('Mobile'), $employeedata['mobile'], true, 20);
        $editinputs.=wf_TextInput('edittelegram', __('Chat ID') . ' ' . __('Telegram'), $employeedata['telegram'], true, 20);
        $editinputs.=wf_TextInput('editadmlogin', __('Administrator'), $employeedata['admlogin'], true, 20);
        $editinputs.=em_TagSelector('editadtagid', __('Tag'), $employeedata['tagid'], true);
        $editinputs.=wf_CheckInput('editactive', 'Active', true, $actflag);
        $editinputs.=wf_Submit('Save');
        $editform = wf_Form('', 'POST', $editinputs, 'glamour');
        show_window(__('Edit'), $editform);
        show_window('', wf_BackLink('?module=employee', 'Back', true, 'ubButton'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
