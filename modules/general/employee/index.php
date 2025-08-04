<?php

// check for right of current admin on this module
if (cfr('EMPLOYEEDIR')) {
    $ubCache = new UbillingCache();

    //new employee creation
    if (ubRouting::checkPost(array('addemployee', 'employeename'))) {
        $newEmpName = ubRouting::post('employeename');
        $newEmpJob = ubRouting::post('employeejob');
        $newEmpMobile = ubRouting::post('employeemobile');
        $newEmpTlg = ubRouting::post('employeetelegram');
        $newEmpAdmLogin = ubRouting::post('employeeadmlogin');
        $newEmpTagId = ubRouting::post('editadtagid');
        $newEmpAmLim = ubRouting::post('amountLimit');
        $newEmpBirthDate = ubRouting::post('birthdate');

        em_EmployeeAdd($newEmpName, $newEmpJob, $newEmpMobile, $newEmpTlg, $newEmpAdmLogin, $newEmpTagId, $newEmpAmLim, $newEmpBirthDate);
        $ubCache->delete('EMPLOYEE_LOGINS');
        ubRouting::nav('?module=employee');
    }

    //existing employee deletion
    if (ubRouting::checkGet('deleteemployee', false)) {
        em_EmployeeDelete(ubRouting::get('deleteemployee'));
        $ubCache->delete('EMPLOYEE_LOGINS');
        ubRouting::nav('?module=employee');
    }

    //jobtype creation
    if (ubRouting::checkPost(array('addjobtype', 'newjobtype'))) {
        stg_add_jobtype(ubRouting::post('newjobtype'), ubRouting::post('newjobcolor'));
        ubRouting::nav('?module=employee');
    }

    //existing jobtype deletion
    if (ubRouting::checkGet('deletejob', false)) {
        stg_delete_jobtype(ubRouting::get('deletejob'));
        ubRouting::nav('?module=employee');
    }

    //existing job type editing
    if (ubRouting::checkGet('editjob', false)) {
        $editjobId = ubRouting::get('editjob', 'int');

        //updating jobtype
        if (ubRouting::checkPost('editjobtype')) {
            em_JobTypeSave($editjobId);
            ubRouting::nav('?module=employee');
        }

        //rendering job type editing form
        show_window(__('Edit') . ' ' . __('Job type'), em_JobTypeEditForm($editjobId));
        show_window('', wf_BackLink('?module=employee', 'Back', true, 'ubButton'));
    }

    //existing employee editing
    if (ubRouting::checkGet('editemployee', false)) {
        $editemployee = ubRouting::get('editemployee', 'int');

        //updating employee
        if (ubRouting::checkPost('editname')) {
            em_EmployeeSave($editemployee);
            $ubCache->delete('EMPLOYEE_LOGINS');
            ubRouting::nav('?module=employee');
        }

        //rendering employee editing form
        show_window(__('Edit') . ' ' . __('Worker'), em_employeeEditForm($editemployee));
        show_window('', wf_BackLink('?module=employee', 'Back', true, 'ubButton'));
    }


    //listing available employee and jobtypes
    if (!ubRouting::checkGet('editemployee') AND ! ubRouting::checkGet('editjob')) {
        em_EmployeeRenderList();
        em_JobTypeRenderList();
    }
} else {
    show_error(__('You cant control this module'));
}
