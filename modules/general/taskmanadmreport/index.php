<?php

if (cfr('TASKMANADMREP')) {

    class HatarakuMaouSama {

        /**
         * Year to render report
         *
         * @var int
         */
        protected $showYear = '';

        /**
         * Month number with leading zero to render
         *
         * @var string
         */
        protected $showMonth = '';

        /**
         * Available jobtypes as jobtypeid=>name
         *
         * @var array
         */
        protected $allJobtypes = array();

        /**
         * Contains all available employee realnames as login=>name
         *
         * @var array
         */
        protected $allEmployeeLogins = array();

        /**
         * Creates new report instance
         */
        public function __construct() {
            $this->setDates();
            $this->loadJobtypes();
            $this->loadEmployeeData();
        }

        /**
         * Loads available jobtypes from database
         * 
         * @return void
         */
        protected function loadJobtypes() {
            $this->allJobtypes = ts_GetAllJobtypes();
        }

        /**
         * Loads active employees from database
         * 
         * @return void
         */
        protected function loadEmployeeData() {
            @$this->allEmployeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
        }

        /**
         * Sets dates to protected props
         * 
         * @return void
         */
        protected function setDates() {
            if (wf_CheckPost(array('showyear', 'showmonth'))) {
                $this->showYear = vf($_POST['showyear'], 3);
                $this->showMonth = vf($_POST['showmonth'], 3);
            } else {
                $this->showYear = curyear();
                $this->showMonth = date("m");
            }
        }

        /**
         * Renders date selection form
         * 
         * @return string
         */
        public function renderDateForm() {
            $result = '';
            $inputs = wf_YearSelectorPreset('showyear', __('Year'), false, $this->showYear) . ' ';
            $inputs.=wf_MonthSelector('showmonth', __('Month'), $this->showMonth, false) . ' ';
            $inputs.=wf_Submit(__('Show'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
            return ($result);
        }

        /**
         * Renders per-admininstrator tasks creation report
         * 
         * @return string
         */
        public function renderReport() {
            $result = '';
            $query = "SELECT * from `taskman` WHERE `date` LIKE '" . $this->showYear . "-" . $this->showMonth . "-%';";
            $all = simple_queryall($query);
            if (!empty($all)) {
                $tmpArr = array();
                $totalJobsCount = 0;
                foreach ($all as $io => $each) {
                    if (isset($tmpArr[$each['admin']])) {
                        if (isset($tmpArr[$each['admin']][$each['jobtype']])) {
                            $tmpArr[$each['admin']][$each['jobtype']] ++;
                        } else {
                            $tmpArr[$each['admin']][$each['jobtype']] = 1;
                        }
                    } else {
                        $tmpArr[$each['admin']][$each['jobtype']] = 1;
                    }
                    $totalJobsCount++;
                }

                if (!empty($tmpArr)) {
                    foreach ($tmpArr as $adminLogin => $eachJobs) {
                        $admLabel = (isset($this->allEmployeeLogins[$adminLogin])) ? $this->allEmployeeLogins[$adminLogin] : $adminLogin;
                        $result.=wf_tag('h2') . $admLabel . wf_tag('h2', true);
                        if (!empty($eachJobs)) {
                            $cells = wf_TableCell(__('Job type'));
                            $cells.=wf_TableCell(__('Count'), '20%');
                            $cells.=wf_TableCell(__('Visual'), '20%');
                            $rows = wf_TableRow($cells, 'row1');
                            foreach ($eachJobs as $jobTypeId => $jobCount) {
                                $cells = wf_TableCell(@$this->allJobtypes[$jobTypeId]);
                                $cells.=wf_TableCell($jobCount);
                                $cells.=wf_TableCell(web_bar($jobCount, $totalJobsCount), '', '', 'sorttable_customkey="' . $jobCount . '"');
                                $rows.= wf_TableRow($cells, 'row3');
                            }
                            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
                        }
                    }
                }
            } else {
                $messages = new UbillingMessageHelper();
                $result.=$messages->getStyledMessage(__('Nothing to show'), 'info');
            }
            return ($result);
        }

    }

    $admReport = new HatarakuMaouSama();
    show_window('', $admReport->renderDateForm());
    show_window(__('Tasks creation per-admin stats'), $admReport->renderReport());
    show_window('', wf_BackLink('?module=taskman'));
} else {
    show_error(__('Access denied'));
}
?>