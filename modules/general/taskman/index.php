<?php

if (cfr('TASKMAN')) {
    $altCfg = $ubillingConfig->getAlter();

    /**
     * json reply for tasks log
     */
    if (ubRouting::checkGet('ajaxlog')) {
        ts_renderLogsDataAjax(ubRouting::get('edittask'));
    }

    /**
     * fullcalendar default display options
     */
    $fullCalendarOpts = '';
    if (isset($altCfg['TASKMAN_DEFAULT_VIEW'])) {
        if (!empty($altCfg['TASKMAN_DEFAULT_VIEW'])) {
            $fullCalendarOpts = "defaultView: '" . $altCfg['TASKMAN_DEFAULT_VIEW'] . "',";
        }
    }

    /**
     * new task creation
     */
    if (ubRouting::checkPost('createtask')) {
        if (ubRouting::checkPost(array('newstartdate', 'newtaskaddress', 'newtaskphone'))) {
            if (ubRouting::checkPost(array('typicalnote'))) {
                $newTaskNote = ubRouting::post('typicalnote') . ' ' . ubRouting::post('newjobnote');
            } else {
                $newTaskNote = ubRouting::post('newjobnote');
            }
            //date validation
            if (zb_checkDate(ubRouting::post('newstartdate'))) {
                $newTaskDate = ubRouting::post('newstartdate');
                $newTaskTime = ubRouting::post('newstarttime');
                $newTaskAddress = ubRouting::post('newtaskaddress');
                $newTaskLogin = ubRouting::post('newtasklogin');
                $newTaskPhone = ubRouting::post('newtaskphone');
                $newTaskJobType = ubRouting::post('newtaskjobtype');
                $newTaskEmployee = ubRouting::post('newtaskemployee');
                ts_CreateTask($newTaskDate, $newTaskTime, $newTaskAddress, $newTaskLogin, $newTaskPhone, $newTaskJobType, $newTaskEmployee, $newTaskNote);
                //capabdir redirects
                if (ubRouting::checkPost(array('unifiedformcapabdirgobackflag', 'unifiedformcapabdirgobackid'))) {
                    $capabUrl = CapabilitiesDirectory::URL_ME . CapabilitiesDirectory::URL_CAPAB;
                    ubRouting::nav($capabUrl . ubRouting::post('unifiedformcapabdirgobackid'));
                } else {
                    //normal redirects
                    if (!ubRouting::checkGet('gotolastid')) {
                        ubRouting::nav('?module=taskman');
                    } else {
                        $lasttaskid = simple_get_lastid('taskman');
                        ubRouting::nav('?module=taskman&edittask=' . $lasttaskid);
                    }
                }
            } else {
                show_error(__('Wrong date format'));
            }
        } else {
            show_error(__('All fields marked with an asterisk are mandatory'));
        }
    }


    /**
     * existing task editing
     */
    if (ubRouting::checkPost('modifytask')) {
        if (ubRouting::checkPost(array('modifystartdate', 'modifytaskaddress', 'modifytaskphone'))) {
            if (zb_checkDate(ubRouting::post('modifystartdate'))) {
                $taskId = ubRouting::post('modifytask');
                $edTaskDate = ubRouting::post('modifystartdate');
                $edTaskTime = ubRouting::post('modifystarttime');
                $edTaskAddress = ubRouting::post('modifytaskaddress');
                $edTaskLogin = ubRouting::post('modifytasklogin');
                $edTaskPhone = ubRouting::post('modifytaskphone');
                $edTaskJobType = ubRouting::post('modifytaskjobtype');
                $edTaskEmployee = ubRouting::post('modifytaskemployee');
                $edTaskNote = ubRouting::post('modifytaskjobnote');
                ts_ModifyTask($taskId, $edTaskDate, $edTaskTime, $edTaskAddress, $edTaskLogin, $edTaskPhone, $edTaskJobType, $edTaskEmployee, $edTaskNote);
                ubRouting::nav('?module=taskman&edittask=' . $taskId);
            } else {
                show_error(__('Wrong date format'));
            }
        } else {
            show_error(__('All fields marked with an asterisk are mandatory'));
        }
    }

    /**
     * task start date change on drag and drop actions
     */
    if (ubRouting::checkPost(array('object_id', 'new_start_time'))) {
        $taskID = ubRouting::post('object_id');
        $newStartDT = ubRouting::post('new_start_time');
        $taskData = ts_GetTaskData($taskID);

        if (!empty($taskData)) {
            $newStartDT = date('Y-m-d', strtotime($newStartDT));
            $curTaskTime = $taskData['starttime'];
            $curTaskAddress = $taskData['address'];
            $curTaskLogin = $taskData['login'];
            $curTaskPhone = $taskData['phone'];
            $curTaskJobType = $taskData['jobtype'];
            $curTaskEmployee = $taskData['employee'];
            $curTaskNote = $taskData['jobnote'];
            ts_ModifyTask($taskID, $newStartDT, $curTaskTime, $curTaskAddress, $curTaskLogin, $curTaskPhone, $curTaskJobType, $curTaskEmployee, $curTaskNote);
            die('SUCCESS');
        } else {
            die('FAIL');
        }
    }

    /**
     * Setting task as done
     */
    if (ubRouting::checkPost('changetask')) {
        if (ubRouting::checkPost(array('editenddate', 'editemployeedone'))) {
            if (zb_checkDate(ubRouting::post('editenddate'))) {
                //setting task as done
                ts_TaskIsDone();

                //flushing darkvoid after changing task
                $darkVoid = new DarkVoid();
                $darkVoid->flushCache();

                //generate job for some user
                if (ubRouting::checkPost(array('generatejob', 'generatelogin', 'generatejobid'))) {
                    $newJobLogin = ubRouting::post('generatelogin');
                    $newJobTime = curdatetime();
                    $newJobEmployeeDone = ubRouting::post('editemployeedone');
                    $newJobJobType = ubRouting::post('generatejobid');
                    $newJobTaskId = ubRouting::post('changetask');
                    $newJobNote = 'TASKID:[' . $newJobTaskId . ']';
                    stg_add_new_job($newJobLogin, $newJobTime, $newJobEmployeeDone, $newJobJobType, $newJobNote);
                    log_register('TASKMAN GENJOB (' . $newJobLogin . ') VIA [' . $newJobTaskId . ']');
                }
            } else {
                show_error(__('Wrong date format'));
            }
        } else {
            show_error(__('All fields marked with an asterisk are mandatory'));
        }
    }

    /**
     * setting task as undone
     */
    if (ubRouting::checkGet('setundone')) {
        //setting task as undone
        ts_TaskIsUnDone();

        //flushing darkvoid after setting task as undone
        $darkVoid = new DarkVoid();
        $darkVoid->flushCache();

        ubRouting::nav('?module=taskman');
    }

    /**
     * deleting existing task 
     */
    if (ubRouting::checkGet('deletetask')) {
        $deleteTaskId = ubRouting::get('deletetask', 'int');
        if (cfr('TASKMANDELETE')) {
            ts_DeleteTask($deleteTaskId);
            //flushing darkvoid after task deletion
            $darkVoid = new DarkVoid();
            $darkVoid->flushCache();
            ubRouting::nav('?module=taskman');
        } else {
            show_error(__('Access denied'));
            log_register('TASKMAN DELETE ACCESS FAIL [' . $deleteTaskId . '] ADMIN {' . whoami() . '}');
        }
    }

    /**
     * normal taskman interface rendering here
     */
    show_window(__('Manage tasks'), ts_ShowPanel());

    //calendar tasks filter selection
    if (ubRouting::checkGet('show')) {
        if (ubRouting::get('show') == 'undone') {
            $showtasks = ts_JGetUndoneTasks();
        }

        if (ubRouting::get('show') == 'done') {
            $showtasks = ts_JGetDoneTasks();
        }

        if (ubRouting::get('show') == 'all') {
            $showtasks = ts_JGetAllTasks();
        }
    } else {
        $showtasks = ts_JGetUndoneTasks();
    }

    if (!ubRouting::checkGet('edittask')) {
        if (!ubRouting::checkGet('print')) {
            if (ubRouting::checkGet('show') AND (ubRouting::get('show') == 'logs' and cfr('TASKMANNWATCHLOG'))) {
                /**
                 * Task logs rendering
                 */
                show_window(__('View log'), ts_renderLogsListAjax());
            } else {

                $showExtendedDone = $ubillingConfig->getAlterParam('TASKMAN_SHOW_DONE_EXTENDED');
                $extendedDoneAlterStyling = $ubillingConfig->getAlterParam('TASKMAN_DONE_EXTENDED_ALTERSTYLING');
                $extendedDoneAlterStylingBool = ($extendedDoneAlterStyling > 0);
                $extendedDoneAlterListOnly = ($extendedDoneAlterStylingBool and $extendedDoneAlterStyling == 2);

                //custom jobtypes color styling
                $customJobColorStyle = ts_GetAllJobtypesColorStyles();
                /** ////////////////////////////////////////
                 * rendering of primary full calendar view
                 * //////////////////////////////////////// */
                show_window('', $customJobColorStyle . wf_FullCalendar($showtasks, $fullCalendarOpts, $extendedDoneAlterStylingBool, $extendedDoneAlterListOnly, '?module=taskman'));
            }
        } else {
            /**
             * printable results
             */
            if (ubRouting::checkPost(array('printdatefrom', 'printdateto'))) {
                if (!ubRouting::checkPost('tableview')) {
                    ts_PrintTasks(ubRouting::post('printdatefrom'), ubRouting::post('printdateto'));
                } else {
                    ts_PrintTasksTable(ubRouting::post('printdatefrom'), ubRouting::post('printdateto'), ubRouting::checkPost('nopagebreaks'));
                }
            }

            /**
             * show printing form
             */
            show_window(__('Tasks printing'), ts_PrintDialogue());
        }
    } else {
        /**
         * SMS post sending
         */
        if (ubRouting::checkPost(array('postsendemployee', 'postsendsmstext'))) {
            $smsDataRaw = ts_SendSMS(ubRouting::post('postsendemployee'), ubRouting::post('postsendsmstext'));
            if (!empty($smsDataRaw)) {
                $smsDataSave = serialize($smsDataRaw);
                $smsDataSave = base64_encode($smsDataSave);
                simple_update_field('taskman', 'smsdata', $smsDataSave, "WHERE `id`='" . ubRouting::get('edittask') . "'");
                //flushing dark void
                $darkVoid = new DarkVoid();
                $darkVoid->flushCache();
                ubRouting::nav('?module=taskman&edittask=' . ubRouting::get('edittask'));
            }
        }

        /**
         * sms data flush
         */
        if (ubRouting::checkGet('flushsmsdata')) {
            ts_FlushSMSData(ubRouting::get('flushsmsdata'));
            ubRouting::nav('?module=taskman&edittask=' . ubRouting::get('flushsmsdata'));
        }

        /**
         * Salary accounting actions
         */
        if ($altCfg['SALARY_ENABLED']) {
            //salary job deletion
            if (ubRouting::checkGet('deletejobid')) {
                $salary = new Salary(ubRouting::get('edittask'));
                $salary->deleteJob(ubRouting::get('deletejobid'));
                ubRouting::nav($salary::URL_TS . ubRouting::get('edittask'));
            }

            //salary job editing
            if (ubRouting::checkPost(array('editsalaryjobid', 'editsalaryemployeeid', 'editsalaryjobtypeid'))) {
                $salary = new Salary(ubRouting::get('edittask'));
                $salary->jobEdit(ubRouting::post('editsalaryjobid'), ubRouting::post('editsalaryemployeeid'), ubRouting::post('editsalaryjobtypeid'), ubRouting::post('editsalaryfactor'), ubRouting::post('editsalaryoverprice'), ubRouting::post('editsalarynotes'));
                ubRouting::nav($salary::URL_TS . ubRouting::get('edittask'));
            }

            //salary job creation
            if (ubRouting::checkPost(array('newsalarytaskid', 'newsalaryemployeeid', 'newsalaryjobtypeid'))) {
                $salary = new Salary(ubrouting::get('edittask'));
                $salary->createSalaryJob(ubRouting::post('newsalarytaskid'), ubRouting::post('newsalaryemployeeid'), ubRouting::post('newsalaryjobtypeid'), ubRouting::post('newsalaryfactor'), ubRouting::post('newsalaryoverprice'), ubRouting::post('newsalarynotes'));
                ubRouting::nav($salary::URL_TS . ubRouting::get('edittask'));
            }
        }

        /**
         * start of task body rendering
         */
        $taskData = ts_GetTaskData(ubRouting::get('edittask'));
        if (!empty($taskData)) {
            $taskExistsFlag = true;
        } else {
            $taskExistsFlag = false;
        }

        /**
         * access restrictions here
         */
        $taskAccess = true;
        $cursedFlag = ts_isMeBranchCursed();
        if ($cursedFlag) {
            if ($taskExistsFlag) {
                if ($taskData['status']) {
                    //task is already done - grant access to anyone. In GULAG too.
                    $taskAccess = true;
                } else {
                    //task is open. Check is this mine?
                    $taskAccess = false;
                    $taskEmployeeId = $taskData['employee'];
                    $myLogin = whoami();
                    $myEmployeeId = ts_GetEmployeeByLogin($myLogin);
                    if (!empty($myEmployeeId)) {
                        if ($taskEmployeeId == $myEmployeeId) {
                            $taskAccess = true;
                        }
                    }
                }
            } else {
                $taskAccess = false;
            }
        }

        if ($taskExistsFlag) {
            if ($taskAccess) {
                /**
                 * display task change form aka task profile
                 */
                ts_TaskChangeForm(ubRouting::get('edittask'));

                /**
                 * Task States support
                 */
                if (@$altCfg['TASKSTATES_ENABLED']) {
                    //existing task?
                    if (!empty($taskData)) {
                        $taskState = $taskData['status'];
                        $taskStates = new TaskStates();
                        show_window(__('Task state'), $taskStates->renderStatePanel(ubRouting::get('edittask'), $taskState));
                        if (ubRouting::checkGet('changestate', 'edittask')) {
                            $newStateSetResult = $taskStates->setTaskState(ubRouting::get('edittask'), ubRouting::get('changestate'));
                            if (empty($newStateSetResult)) {
                                die($taskStates->renderStatePanel(ubRouting::get('edittask'), $taskState));
                            } else {
                                $messages = new UbillingMessageHelper();
                                die($messages->getStyledMessage($newStateSetResult, 'error'));
                            }
                        }
                    } else {
                        show_error(__('Something went wrong') . ': TASKID_NOT_EXISTS [' . ubRouting::get('edittask') . ']');
                    }
                }

                /**
                 * Employee task notices
                 */
                if (@$altCfg['TASKWHATIDO_ENABLED']) {
                    if (!empty($taskData)) {
                        $taskWhatIdoReadOnly = ($taskData['status']) ? true : false;
                        $taskWhatIdo = new Stigma('TASKWHATIDO', ubRouting::get('edittask'));
                        $taskWhatIdo->stigmaController('TASKMAN:Done');
                        show_window(__('What I did on the task'), $taskWhatIdo->render(ubRouting::get('edittask'), '128', $taskWhatIdoReadOnly));
                    }
                }


                /**
                 * photostorage integration
                 */
                if ($altCfg['PHOTOSTORAGE_ENABLED']) {
                    $photoStorage = new PhotoStorage('TASKMAN', ubRouting::get('edittask'));
                    $renderPhotoControlFlag = true;
                    if (@$altCfg['TASKSTATES_ENABLED']) {
                        if (isset($taskState)) {
                            if ($taskState) {
                                //task already closed
                                $renderPhotoControlFlag = false;
                            }
                        } else {
                            //task not exists
                            $renderPhotoControlFlag = false;
                        }
                    }

                    if ($renderPhotoControlFlag) {
                        $photostorageControl = wf_Link('?module=photostorage&scope=TASKMAN&mode=list&itemid=' . ubRouting::get('edittask'), wf_img('skins/photostorage.png') . ' ' . __('Upload images'), false, 'ubButton');
                        $photostorageControl .= wf_delimiter();
                    } else {
                        $messages = new UbillingMessageHelper();
                        $photostorageControl = $messages->getStyledMessage(__('You cant attach images for already closed task'), 'warning') . wf_delimiter();
                    }
                    $photosList = $photoStorage->renderImagesRaw();
                    show_window(__('Photostorage'), $photostorageControl . $photosList);
                }

                /**
                 * additional comments 
                 */
                if ($altCfg['ADCOMMENTS_ENABLED']) {
                    $adcomments = new ADcomments('TASKMAN');
                    show_window(__('Additional comments'), $adcomments->renderComments(ubrouting::get('edittask')));
                }
            } else {
                show_error(__('Access denied'));
                log_register('TASKMAN TASK ACCESS FAIL [' . ubRouting::get('edittask') . '] ADMIN {' . whoami() . '}');
            }
        } else {
            show_error(__('Something went wrong') . ': ' . __('Task') . ' [' . ubRouting::get('edittask') . ']' . ' ' . __('Not exists'));
        }
    }

    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
