<?php

if (cfr('TASKMANTIMING')) {

    class TaskmanTimingReport {

        /**
         * Contains task real done dates as taskid=>data
         *
         * @var array
         */
        protected $doneTasks = array();

        /**
         * Base module URL
         */
        const URL_ME = '?module=taskmantiming';

        /**
         * Task preview URL
         */
        const URL_TASK = '?module=taskman&edittask=';

        /**
         * Creates new taskman timing report instance
         */
        public function __construct() {
            $this->loadTasksDone();
        }

        /**
         * Loads task done dates from database
         * 
         * @return void
         */
        protected function loadTasksDone() {
            $qeury = "SELECT * from `taskmandone`";
            $all = simple_queryall($qeury);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->doneTasks[$each['taskid']] = $each;
                }
            }
        }

        /**
         * Returns default date search form
         * 
         * @return string
         */
        public function renderSearchForm() {
            $result = '';
            $dateFrom = (wf_CheckPost(array('datefrom'))) ? $_POST['datefrom'] : date("Y-m-") . '01';
            $dateTo = (wf_CheckPost(array('dateto'))) ? $_POST['dateto'] : curdate();
            $inputs = wf_DatePickerPreset('datefrom', $dateFrom, true) . ' ' . __('From') . ' ';
            $inputs.=wf_DatePickerPreset('dateto', $dateTo, true) . ' ' . __('To') . ' ';
            $inputs.= wf_Submit(__('Show'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
            return ($result);
        }

        /**
         * Renders container for report
         * 
         * @return string
         */
        public function renderReportContainer() {
            $dateFrom = (wf_CheckPost(array('datefrom'))) ? $_POST['datefrom'] : date("Y-m-") . '01';
            $dateTo = (wf_CheckPost(array('dateto'))) ? $_POST['dateto'] : curdate();
            $result = '';
            /**
             * Shizukesa ga shimikomu you de iki wo tometa gozen goji
             * Hijou kaidan de tsume wo kamu  asu wa docchi da? THE DAY HAS COME
             */
            $columns = array('ID', 'Task address', 'Job type', 'Task creation date', 'Target date', 'Finish date', 'Time', 'From creation', 'Reality');
            $opts = '"order": [[ 0, "desc" ]]';
            $result.=wf_JqDtLoader($columns, self::URL_ME . '&ajax=true&datefrom=' . $dateFrom . '&dateto=' . $dateTo, false, __('Tasks'), 100, $opts);
            return ($result);
        }

        /**
         * Returns array of done tasks by some period
         * 
         * @param string $dateFrom
         * @param string $dateTo
         * 
         * @return array
         */
        protected function getDoneTasks($dateFrom, $dateTo) {
            $result = array();
            $query = "SELECT * from `taskman` WHERE `status`='1' AND `date` BETWEEN '" . $dateFrom . "' AND '" . $dateTo . " 23:59:59'";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $result[$each['id']] = $each;
                }
            }
            return ($result);
        }

        /**
         * Renders time duration in seconds into formatted human-readable view in days
         *      
         * @param int $seconds
         * 
         * @return string
         */
        function formatTimeDays($seconds) {
            $init = $seconds;
            $days = floor($seconds / 86400);
            if ($init >= 86400) {
                $result = $days . ' ' . __('days');
            } else {
                if ($init > 0) {
                    $result = '0 ' . __('days');
                } else {
                    $result = $days . ' ' . __('days');
                }
            }

            return ($result);
        }

        /**
         * Renders JSON data for report
         * 
         * @return void
         */
        public function ajaxSearchReply() {
            $dateFrom = (wf_CheckGet(array('datefrom'))) ? $_GET['datefrom'] : date("Y-m-") . '01';
            $dateTo = (wf_CheckGet(array('dateto'))) ? $_GET['dateto'] : curdate();
            $json = new wf_JqDtHelper();
            $allTasks = $this->getDoneTasks($dateFrom, $dateTo);
            if (!empty($allTasks)) {
                $allJobTypes = ts_GetAllJobtypes();

                foreach ($allTasks as $io => $each) {
                    $startTimestamp = strtotime($each['startdate']);
                    $endTimestamp = strtotime($each['enddate']);
                    $creationTimestamp = strtotime($each['date']);
                    $creationDays = date("Y-m-d", $creationTimestamp);
                    $creationDays = strtotime($creationDays);
                    $deltaPlanned = ($endTimestamp >= $startTimestamp) ? $endTimestamp - $startTimestamp : '-' . ($startTimestamp - $endTimestamp);
                    $deltaReal = ($endTimestamp >= $creationDays) ? $endTimestamp - $creationDays : '-' . ($creationDays - $endTimestamp);
                    $cruelReality = '';
                    if (isset($this->doneTasks[$each['id']])) {
                        $taskDoneDate = $this->doneTasks[$each['id']]['date'];
                        $taskDoneTimestamp = strtotime($taskDoneDate);
                        $cruelReality = $taskDoneTimestamp - $creationTimestamp;
                        if ($cruelReality > 0) {
                            $cruelReality = zb_formatTime($cruelReality);
                        }
                    }
                    $data[] = wf_Link(self::URL_TASK . $each['id'], $each['id']);
                    $data[] = $each['address'];
                    $data[] = $allJobTypes[$each['jobtype']];
                    $data[] = $each['date'];
                    $data[] = $each['startdate'];
                    $data[] = $each['enddate'];
                    $data[] = $this->formatTimeDays($deltaPlanned);
                    $data[] = $this->formatTimeDays($deltaReal);
                    $data[] = $cruelReality;
                    $json->addRow($data);
                    unset($data);
                }
            }
            $json->getJson();
        }

    }

    $taskmanTiming = new TaskmanTimingReport();

    if (wf_CheckGet(array('ajax', 'datefrom', 'dateto'))) {
        $taskmanTiming->ajaxSearchReply();
    }
    show_window('', wf_BackLink('?module=taskman'));
    show_window(__('Task timing report'), $taskmanTiming->renderSearchForm());
    show_window('', $taskmanTiming->renderReportContainer());
} else {
    show_error(__('Access denied'));
}
?>