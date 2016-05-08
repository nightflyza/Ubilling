<?php

if (cfr('TASKMAN')) {
    $altCfg = $ubillingConfig->getAlter();

    //if someone creates new task
    if (isset($_POST['createtask'])) {
        if (wf_CheckPost(array('newstartdate', 'newtaskaddress', 'newtaskphone'))) {
            if (wf_CheckPost(array('typicalnote'))) {
                $newjobnote = $_POST['typicalnote'] . ' ' . $_POST['newjobnote'];
            } else {
                $newjobnote = $_POST['newjobnote'];
            }
            //date validyty check
            if (zb_checkDate($_POST['newstartdate'])) {
                ts_CreateTask($_POST['newstartdate'], @$_POST['newstarttime'], $_POST['newtaskaddress'], @$_POST['newtasklogin'], $_POST['newtaskphone'], $_POST['newtaskjobtype'], $_POST['newtaskemployee'], $newjobnote);
                if (!isset($_GET['gotolastid'])) {
                    rcms_redirect("?module=taskman");
                } else {
                    $lasttaskid = simple_get_lastid('taskman');
                    rcms_redirect("?module=taskman&edittask=" . $lasttaskid);
                }
            } else {
                show_error(__('Wrong date format'));
            }
        } else {
            show_error(__('All fields marked with an asterisk are mandatory'));
        }
    }


    //modify task sub
    if (isset($_POST['modifytask'])) {
        if (wf_CheckPost(array('modifystartdate', 'modifytaskaddress', 'modifytaskphone'))) {
            if (zb_checkDate($_POST['modifystartdate'])) {
                $taskid = $_POST['modifytask'];
                ts_ModifyTask($taskid, $_POST['modifystartdate'], $_POST['modifystarttime'], $_POST['modifytaskaddress'], @$_POST['modifytasklogin'], $_POST['modifytaskphone'], $_POST['modifytaskjobtype'], $_POST['modifytaskemployee'], $_POST['modifytaskjobnote']);
                rcms_redirect("?module=taskman&edittask=" . $taskid);
            } else {
                show_error(__('Wrong date format'));
            }
        } else {
            show_error(__('All fields marked with an asterisk are mandatory'));
        }
    }

    //if marking task as done
    if (isset($_POST['changetask'])) {
        if (wf_CheckPost(array('editenddate', 'editemployeedone'))) {
            if (zb_checkDate($_POST['editenddate'])) {
                //editing task sub
                $editid = vf($_POST['changetask']);
                simple_update_field('taskman', 'enddate', $_POST['editenddate'], "WHERE `id`='" . $editid . "'");
                simple_update_field('taskman', 'employeedone', $_POST['editemployeedone'], "WHERE `id`='" . $editid . "'");
                simple_update_field('taskman', 'donenote', $_POST['editdonenote'], "WHERE `id`='" . $editid . "'");
                simple_update_field('taskman', 'status', '1', "WHERE `id`='" . $editid . "'");

                //flushing darkvoid after changing task
                $darkVoid = new DarkVoid();
                $darkVoid->flushCache();

                log_register('TASKMAN DONE [' . $editid . ']');

                //generate job for some user
                if (wf_CheckPost(array('generatejob', 'generatelogin', 'generatejobid'))) {
                    stg_add_new_job($_POST['generatelogin'], curdatetime(), $_POST['editemployeedone'], $_POST['generatejobid'], 'TASKID:[' . $_POST['changetask'] . ']');
                    log_register("TASKMAN GENJOB (" . $_POST['generatelogin'] . ') VIA [' . $_POST['changetask'] . ']');
                }
            } else {
                show_error(__('Wrong date format'));
            }
        } else {
            show_error(__('All fields marked with an asterisk are mandatory'));
        }
    }

    //setting task undone
    if (isset($_GET['setundone'])) {
        $undid = vf($_GET['setundone'], 3);
        simple_update_field('taskman', 'status', '0', "WHERE `id`='" . $undid . "'");
        simple_update_field('taskman', 'enddate', 'NULL', "WHERE `id`='" . $undid . "'");
        log_register("TASKMAN UNDONE [" . $undid . ']');

        //flushing darkvoid after setting task as undone
        $darkVoid = new DarkVoid();
        $darkVoid->flushCache();


        rcms_redirect("?module=taskman");
    }


    //deleting task 
    if (isset($_GET['deletetask'])) {
        $delid = vf($_GET['deletetask'], 3);
        ts_DeleteTask($delid);
        //flushing darkvoid after task deletion
        $darkVoid = new DarkVoid();
        $darkVoid->flushCache();
        rcms_redirect("?module=taskman");
    }


    if (!wf_CheckGet(array('probsettings'))) {
        show_window(__('Manage tasks'), ts_ShowPanel());

        if (isset($_GET['show'])) {
            if ($_GET['show'] == 'undone') {
                $showtasks = ts_JGetUndoneTasks();
            }

            if ($_GET['show'] == 'done') {
                $showtasks = ts_JGetDoneTasks();
            }

            if ($_GET['show'] == 'all') {
                $showtasks = ts_JGetAllTasks();
            }
        } else {
            $showtasks = ts_JGetUndoneTasks();
        }

        if (!isset($_GET['edittask'])) {
            if (!wf_CheckGet(array('print'))) {
                if (!wf_CheckGet(array('lateshow'))) {
                    //custom jobtypes color styling
                    $customJobColorStyle = ts_GetAllJobtypesColorStyles();
                    //show full calendar view
                    show_window('', $customJobColorStyle . wf_FullCalendar($showtasks));
                } else {
                    show_window(__('Show late'), ts_ShowLate());
                }
            } else {
                //printable result
                if (wf_CheckPost(array('printdatefrom', 'printdateto'))) {
                    ts_PrintTasks($_POST['printdatefrom'], $_POST['printdateto']);
                }
                //show printing form
                show_window(__('Tasks printing'), ts_PrintDialogue());
            }
        } else {
            //sms post sending
            if (wf_CheckPost(array('postsendemployee', 'postsendsmstext'))) {
                $smsDataRaw = ts_SendSMS($_POST['postsendemployee'], $_POST['postsendsmstext']);
                if (!empty($smsDataRaw)) {
                    $smsDataSave = serialize($smsDataRaw);
                    $smsDataSave = "'" . base64_encode($smsDataSave) . "'";
                    simple_update_field('taskman', 'smsdata', $smsDataSave, "WHERE `id`='" . $_GET['edittask'] . "'");
                    //flushing dark void
                    $darkVoid = new DarkVoid();
                    $darkVoid->flushCache();
                    rcms_redirect('?module=taskman&edittask=' . $_GET['edittask']);
                }
            }

            //sms data flush
            if (wf_CheckGet(array('flushsmsdata'))) {
                ts_FlushSMSData($_GET['flushsmsdata']);
                rcms_redirect('?module=taskman&edittask=' . $_GET['flushsmsdata']);
            }

            /**
             * Salary accounting actions
             */
            if ($altCfg['SALARY_ENABLED']) {
                $salary = new Salary();
                //salary job deletion
                if (wf_CheckGet(array('deletejobid'))) {
                    $salary->deleteJob($_GET['deletejobid']);
                    rcms_redirect($salary::URL_TS . $_GET['edittask']);
                }

                //salary job editing
                if (wf_CheckPost(array('editsalaryjobid', 'editsalaryemployeeid', 'editsalaryjobtypeid'))) {
                    $salary->jobEdit($_POST['editsalaryjobid'], $_POST['editsalaryemployeeid'], $_POST['editsalaryjobtypeid'], $_POST['editsalaryfactor'], $_POST['editsalaryoverprice'], $_POST['editsalarynotes']);
                    rcms_redirect($salary::URL_TS . $_GET['edittask']);
                }

                //salary job creation
                if (wf_CheckPost(array('newsalarytaskid', 'newsalaryemployeeid', 'newsalaryjobtypeid'))) {
                    $salary->createSalaryJob($_POST['newsalarytaskid'], $_POST['newsalaryemployeeid'], $_POST['newsalaryjobtypeid'], $_POST['newsalaryfactor'], $_POST['newsalaryoverprice'], $_POST['newsalarynotes']);
                    rcms_redirect($salary::URL_TS . $_GET['edittask']);
                }
            }



            //display task change form
            ts_TaskChangeForm($_GET['edittask']);

            //photostorage integration
            if ($altCfg['PHOTOSTORAGE_ENABLED']) {
                $photoStorage = new PhotoStorage('TASKMAN', $_GET['edittask']);
                $photostorageControl = wf_Link('?module=photostorage&scope=TASKMAN&mode=list&itemid=' . $_GET['edittask'], wf_img('skins/photostorage.png') . ' ' . __('Upload images'), false, 'ubButton');
                $photostorageControl.=wf_delimiter();
                $photosList = $photoStorage->renderImagesRaw();
                show_window(__('Photostorage'), $photostorageControl . $photosList);
            }

            //additional comments 
            if ($altCfg['ADCOMMENTS_ENABLED']) {
                $adcomments = new ADcomments('TASKMAN');
                show_window(__('Additional comments'), $adcomments->renderComments($_GET['edittask']));
            }
        }
    } else {
        show_window(__('Typical problems'), ts_TaskProblemsEditForm());
    }
} else {
    show_error(__('Access denied'));
}
?>