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
                        deb($salary->payrollRenderSearch($_POST['prdatefrom'], $_POST['prdateto'], $_POST['premployeeid']));
                    } else {
                        //multiple employee report
                        deb('TO DO');
                    }
                }
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
?>