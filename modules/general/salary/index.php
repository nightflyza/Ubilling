<?php

if (cfr('SALARY')) {
    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['SALARY_ENABLED']) {
        $greed = new Avarice();
        $beggar = $greed->runtime('SALARY');
        if (!empty($beggar)) {
            $salary = new Salary();

            if (method_exists($salary, $beggar['M']['PANEL'])) {
                show_window('', $salary->$beggar['M']['PANEL']());
            }

// jobtype pricing creation
            if (wf_CheckPost(array('newjobtypepriceid', 'newjobtypepriceunit'))) {
                if (method_exists($salary, $beggar['M']['JPADD'])) {
                    $salary->$beggar['M']['JPADD']($_POST['newjobtypepriceid'], $_POST['newjobtypeprice'], $_POST['newjobtypepriceunit'], $_POST['newjobtypepricetime']);
                }
                if (isset($beggar['U']['JPL'])) {
                    rcms_redirect($beggar['U']['JPL']);
                }
            }

//jobtype price deletion
            if (wf_CheckGet(array('deletejobprice'))) {
                if (method_exists($salary, $beggar['M']['JPFLUSH'])) {
                    $salary->$beggar['M']['JPFLUSH']($_GET['deletejobprice']);
                }
                if (isset($beggar['U']['JPL'])) {
                    rcms_redirect($beggar['U']['JPL']);
                }
            }
//saving jobprices into database
            if (wf_CheckPost(array($beggar['U']['JPCPE']))) {
                if (method_exists($salary, $beggar['M']['JPSAVE'])) {
                    $salary->$beggar['M']['JPSAVE']($_POST[$beggar['U']['JPCPE']]);
                }
                if (isset($beggar['U']['JPL'])) {
                    rcms_redirect($beggar['U']['JPL']);
                }
            }

//listing avalable job pricings            
            if (wf_CheckGet(array($beggar['U']['JPCG']))) {
                if (method_exists($salary, $beggar['VP']['JPAF'])) {
                    $jpCf = $salary->$beggar['VP']['JPAF']();
                } else {
                    $jpCf = '';
                }
                if ($jpCf) {
                    show_window(__('Job types pricing'), $jpCf);
                } else {
                    show_warning(__('No available job types for pricing'));
                }
                show_window(__('Available job types pricing'), $salary->$beggar['M']['JPLIST']());
                show_window('', wf_Link($salary::URL_ME, __('Back'), false, 'ubButton'));
            }

            /**
              Come sing along with the pirate song
              Hail to the wind, hooray to the glory
              We're gonna fight 'til the battle's won
              On the raging sea
             */
            //creation new employee wage
            if (wf_CheckPost(array('newemployeewageemployeeid', 'newemployeewage'))) {
                $salary->employeeWageCreate($_POST['newemployeewageemployeeid'], $_POST['newemployeewage'], $_POST['newemployeewagebounty'], $_POST['newemployeewageworktime']);
                rcms_redirect($salary::URL_ME . '&' . $salary::URL_WAGES);
            }

            //editing existing employee
            if (wf_CheckPost(array('editemployeewageemployeeid', 'editemployeewage'))) {
                $salary->employeeWageEdit($_POST['editemployeewageemployeeid'], $_POST['editemployeewage'], $_POST['editemployeewagebounty'], $_POST['editemployeewageworktime']);
                rcms_redirect($salary::URL_ME . '&' . $salary::URL_WAGES);
            }


            //deleting employee wage
            if (wf_CheckGet(array('deletewage'))) {
                $salary->employeeWageDelete($_GET['deletewage']);
                rcms_redirect($salary::URL_ME . '&' . $salary::URL_WAGES);
            }

//listing available employee wages
            if (wf_CheckGet(array($beggar['U']['EWCG']))) {
                $ewCf = $salary->$beggar['VP']['EWAF']();
                if ($ewCf) {
                    show_window(__('Employee wages'), $ewCf);
                } else {
                    show_warning(__('No available workers for wage creation'));
                }
                show_window(__('Available employee wages'), $salary->employeeWagesRender());
                show_window('', wf_Link($salary::URL_ME, __('Back'), false, 'ubButton'));
            }
//rendering payroll report
            if (wf_CheckGet(array('payroll'))) {
                //printable per-employee report
                if (wf_CheckGet(array('print', 'e', 'df', 'dt'))) {
                    $reportPrintTitle = __('Payroll') . ': ' . $salary->getEmployeeName($_GET['e']) . ' ' . __('from') . ' ' . $_GET['df'] . ' ' . __('to') . ' ' . $_GET['dt'];
                    $salary->reportPrintable($reportPrintTitle, $salary->payrollRenderSearch($_GET['df'], $_GET['dt'], $_GET['e']));
                }


                show_window(__('Search'), $salary->payrollRenderSearchForm());
                //job state processing confirmation
                if (wf_CheckPost(array('prstateprocessing'))) {
                    show_window(__('Confirmation'), $salary->payrollStateProcessingForm());
                }

                if (wf_CheckPost(array('prstateprocessingconfirmed'))) {
                    $salary->payrollStateProcessing();
                }


                if (wf_CheckPost(array('prdatefrom', 'prdateto'))) {
                    if (wf_CheckPost(array('premployeeid'))) {
                        //single employee report
                        $reportTitle = __('Payroll') . ': ' . $salary->getEmployeeName($_POST['premployeeid']) . ' ' . __('from') . ' ' . $_POST['prdatefrom'] . ' ' . __('to') . ' ' . $_POST['prdateto'] . ' ';

                        $printLink = wf_tag('a', false, '', 'href="' . $salary::URL_ME . '&' . salary::URL_PAYROLL . '&print=true&e=' . $_POST['premployeeid'] . '&df=' . $_POST['prdatefrom'] . '&dt=' . $_POST['prdateto'] . '" TARGET="_BLANK"');
                        $printLink.= web_icon_print();
                        $printLink.= wf_tag('a', true);
                        $reportTitle.=$printLink;
                        show_window($reportTitle, $salary->payrollRenderSearch($_POST['prdatefrom'], $_POST['prdateto'], $_POST['premployeeid']));
                    } else {
                        //multiple employee report
                        $reportTitle = __('Payroll') . ': ' . __('All') . ' ' . __('Employee') . ' ' . __('from') . ' ' . $_POST['prdatefrom'] . ' ' . __('to') . ' ' . $_POST['prdateto'];
                        show_window($reportTitle, $salary->payrollRenderSearchDate($_POST['prdatefrom'], $_POST['prdateto']));
                    }
                }
                show_window('', wf_Link($salary::URL_ME, __('Back'), false, 'ubButton'));
            }

//rendering factor control report 
            if (wf_CheckGet(array('factorcontrol'))) {
                show_window(__('Search'), $salary->facontrolRenderSearchForm());
                if (wf_CheckPost(array('facontroljobtypeid', 'facontrolmaxfactor'))) {
                    show_window(__('Factor control'), $salary->facontrolRenderSearch($_POST['facontroljobtypeid'], $_POST['facontrolmaxfactor']));
                }
                show_window('', wf_Link($salary::URL_ME, __('Back'), false, 'ubButton'));
            }


// tasks without assinged jobs report
            if (wf_CheckGet(array('twjreport'))) {
                show_window(__('Search'), $salary->twjReportSearchForm());
                if (wf_CheckPost(array('twfdatefrom', 'twfdateto'))) {
                    show_window(__('Tasks without jobs'), $salary->twjReportSearch($_POST['twfdatefrom'], $_POST['twfdateto']));
                }
                show_window('', wf_Link($salary::URL_ME, __('Back'), false, 'ubButton'));
            }
//timesheets reports
            if (wf_CheckGet(array('timesheets'))) {
                //creating of new timesheet
                if (wf_CheckPost(array('newtimesheet', 'newtimesheetdate', '_employeehours'))) {
                    $tsSheetCreateResult = $salary->timesheetCreate();
                    if ($tsSheetCreateResult == 0) {
                        //succeful creation
                        rcms_redirect($salary::URL_ME . '&' . $salary::URL_TSHEETS);
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
                    $timesheetsControls.= wf_Link($salary::URL_ME.'&'.$salary::URL_TSHEETS.'&print=true', web_icon_print() . ' ' . __('Print'), false, 'ubButton');
                    show_window('', $timesheetsControls);
                    if (!wf_CheckGet(array('showdate'))) {
                        if (wf_CheckGet(array('print'))) {
                            //printing soubrutine
                            show_window('', $salary->timesheetRenderPrintableForm());
                            if (wf_CheckPost(array('tsheetprintyear','tsheetprintmonth'))) {
                                die($salary->timesheetRenderPrintable($_POST['tsheetprintyear'], $_POST['tsheetprintmonth']));
                            }
                            
                            show_window('', wf_Link($salary::URL_ME . '&' . $salary::URL_TSHEETS, __('Back'), false, 'ubButton'));
                        } else {
                         //render available timesheets list by date
                        show_window(__('Timesheets'), $salary->timesheetsListRender());
                        show_window('', wf_Link($salary::URL_ME, __('Back'), false, 'ubButton'));
                        }
                    } else {
                        //saving changes for single timesheet row
                        if (wf_CheckPost(array('edittimesheetid'))) {
                            $salary->timesheetSaveChanges();
                            rcms_redirect($salary::URL_ME . '&' . $salary::URL_TSHEETS . '&showdate=' . $_GET['showdate']);
                        }
                        //render timesheet by date (edit form)
                        show_window(__('Timesheet') . ' ' . $_GET['showdate'], $salary->timesheetEditForm($_GET['showdate']));
                        show_window('', wf_Link($salary::URL_ME . '&' . $salary::URL_TSHEETS, __('Back'), false, 'ubButton'));
                    }
                } else {
                    show_warning(__('No available workers for timesheets'));
                }
            }
            
          $salary->summaryReport();
            
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Permission denied'));
}
?>