<?php

if (cfr('SALARYTSHEETS')) {
    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['SALARY_ENABLED']) {
        $greed = new Avarice();
        $beggar = $greed->runtime('SALARY');
        if (!empty($beggar)) {
            $salary = new Salary();



            //creating of new timesheet
            if (wf_CheckPost(array('newtimesheet', 'newtimesheetdate', '_employeehours'))) {
                $tsSheetCreateResult = $salary->timesheetCreate();
                if ($tsSheetCreateResult == 0) {
                    //succeful creation
                    rcms_redirect('?module=salary_timesheets');
                } else {
                    if ($tsSheetCreateResult == 1) {
                        //date duplicate
                        show_error(__('Timesheets with that date already exist'));
                    }
                }
            }

            $tsCf = $salary->timesheetCreateForm();
            if ($tsCf) {
                $timesheetsControls = wf_modal(web_add_icon() . ' ' . __('Create'), __('Create') . ' ' . __('Timesheet'), $tsCf, 'ubButton', '800', '600');
                show_window('', $timesheetsControls);
                if (!wf_CheckGet(array('showdate'))) {
                    //render available timesheets list by date
                    show_window(__('Timesheets'), $salary->timesheetsListRender('?module=salary_timesheets'));
                } else {
                    //saving changes for single timesheet row
                    if (wf_CheckPost(array('edittimesheetid'))) {
                        $salary->timesheetSaveChanges();
                        rcms_redirect('?module=salary_timesheets&showdate=' . $_GET['showdate']);
                    }
                    //render timesheet by date (edit form)
                    show_window(__('Timesheet') . ' ' . $_GET['showdate'], $salary->timesheetEditForm($_GET['showdate']));
                    show_window('', wf_Link('?module=salary_timesheets', __('Back'), false, 'ubButton'));
                }
            } else {
                show_warning(__('No available workers for timesheets'));
            }
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Permission denied'));
}