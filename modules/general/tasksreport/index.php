<?php

if (cfr('TASKREPORT')) {

    class TasksReport {

        /**
         * System alter config stored as key=>value
         *
         * @var array
         */
        protected $altCfg = array();

        /**
         * jobtypes IDs for report
         *
         * @var array
         */
        protected $reportJobtypes = array();

        /**
         * Signup tasks jobtype ID
         *
         * @var int
         */
        protected $signupJobtypeId = 0;

        /**
         * Available jobtypes data as jobtypeid=>data
         *
         * @var array
         */
        protected $jobtypes = array();

        /**
         * Report date from 
         *
         * @var string
         */
        protected $dateFrom = '';

        /**
         * Report date to
         *
         * @var string
         */
        protected $dateTo = '';

        /**
         * Contains all tasks with reportJobtypes perfromed between search intervals
         *
         * @var array
         */
        protected $allTasks = array();

        /**
         * System messages helper object placeholder
         *
         * @var object
         */
        protected $messages = '';

        /**
         * Creates new TasksReport object instance
         * 
         * @return void
         */
        public function __construct() {
            $this->loadConfigs();
            $this->preprocessConfigs();
            $this->initMessages();
            $this->loadJobtypes();
            $this->setDates();
            $this->loadTasks();
        }

        /**
         * Loads main configuration options
         * 
         * @global object $ubillingConfig
         * 
         * @return void
         */
        protected function loadConfigs() {
            global $ubillingConfig;
            $this->altCfg = $ubillingConfig->getAlter();
        }

        /**
         * Preprocess config options into protected properties
         * 
         * @return void
         */
        protected function preprocessConfigs() {
            if (!empty($this->altCfg['TASKREPORT_JOBTYPES'])) {
                $jobtypesTmp = explode(',', $this->altCfg['TASKREPORT_JOBTYPES']);
                $this->reportJobtypes = array_flip($jobtypesTmp);
            }

            if (!empty($this->altCfg['TASKREPORT_SIGNUPJOBTYPE'])) {
                $this->signupJobtypeId = $this->altCfg['TASKREPORT_SIGNUPJOBTYPE'];
            }
        }

        /**
         * Inits system messages object
         * 
         * @return void
         */
        protected function initMessages() {
            $this->messages = new UbillingMessageHelper();
        }

        /**
         * Sets current report dates
         * 
         * @return void
         */
        protected function setDates() {
            if (wf_CheckPost(array('dateto', 'datefrom'))) {
                $this->dateFrom = mysql_real_escape_string($_POST['datefrom']);
                $this->dateTo = mysql_real_escape_string($_POST['dateto']);
            } else {
                $this->dateFrom = date("Y-m") . '-01';
                $this->dateTo = curdate();
            }
        }

        /**
         * Loads available jobtypes data
         * 
         * @return void
         */
        protected function loadJobtypes() {
            $query = "SELECT * from `jobtypes`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->jobtypes[$each['id']] = $each;
                }
            }
        }

        /**
         * Loads tasks for report in selected time range, into protected property for further usage
         * 
         * @return void
         */
        protected function loadTasks() {
            $query = "SELECT * from `taskman` WHERE `startdate`  BETWEEN '" . $this->dateFrom . "' AND '" . $this->dateTo . "'";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    if (isset($this->reportJobtypes[$each['jobtype']])) {
                        $this->allTasks[$each['id']] = $each;
                    }
                }
            }
        }

        /**
         * Renders default from-to date controls form
         * 
         * @return string
         */
        public function renderDatesForm() {
            $result = '';
            $inputs = __('Date') . ' ' . wf_DatePickerPreset('datefrom', $this->dateFrom, true) . ' ' . __('From') . ' ';
            $inputs.= wf_DatePickerPreset('dateto', $this->dateTo, true) . ' ' . __('To') . ' ';
            $inputs.= wf_Submit(__('Show'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            return ($result);
        }

        /**
         * Renders report by preloaded data
         * 
         * @return string
         */
        public function renderReport() {
            $result = '';
            $count = 1;
            if (!empty($this->allTasks)) {
                $cells = wf_TableCell('№');
                $cells.= wf_TableCell(__('ID'));
                $cells.= wf_TableCell(__('Address'));
                $cells.= wf_TableCell(__('Type'));
                $cells.= wf_TableCell(__('Spent on task'));
                $cells.= wf_TableCell(__('Tariff fee'));
                $cells.= wf_TableCell(__('Paid by user'));
                $cells.= wf_TableCell(__('Notes'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($this->allTasks as $io => $each) {
                    $typeColor = (!empty($this->jobtypes[$each['jobtype']]['jobcolor'])) ? $this->jobtypes[$each['jobtype']]['jobcolor'] : '';
                    if (!empty($typeColor)) {
                        $styleStart = wf_tag('font', false, '', 'color="' . $typeColor . '"');
                        $styleEnd = wf_tag('font', true);
                    } else {
                        $styleStart = '';
                        $styleEnd = '';
                    }

                    $cells = wf_TableCell($count);
                    $cells.= wf_TableCell($each['id']);
                    $cells.= wf_TableCell($each['address']);
                    $cells.= wf_TableCell($styleStart . $this->jobtypes[$each['jobtype']]['jobname'] . $styleEnd);
                    $cells.= wf_TableCell(__('Spent on task'));
                    $cells.= wf_TableCell(__('Tariff fee'));
                    $cells.= wf_TableCell(__('Paid by user'));
                    $cells.= wf_TableCell(__('Notes'));
                    $rows.= wf_TableRow($cells, 'row3');
                    $count++;
                }

                $result = wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
            }
            return ($result);
        }

    }

    $report = new TasksReport();
    show_window(__('Search'), $report->renderDatesForm());
    show_window(__('Tasks report'), $report->renderReport());
} else {
    show_error(__('Access denied'));
}
?>