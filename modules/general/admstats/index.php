<?php

if (cfr('ROOT')) {

    /**
     * Renders administrators activity time report
     */
    class AdministratorStats {

        /**
         * Contains current instance date to render report
         *
         * @var string
         */
        protected $showDate = '';

        /**
         * Data source abstraction layer placeholder
         *
         * @var object
         */
        protected $dataSource = '';

        /**
         * Contains preloaded report data
         *
         * @var array
         */
        protected $allDataRaw = array();

        /**
         * Contains system message helper object instance
         *
         * @var object
         */
        protected $messages = '';

        /**
         * Some predefined static routes, etc
         */
        const TABLE_DATASOURCE = 'weblogs';
        const PROUTE_DATE = 'admstatsdate';
        const PROUTE_SUMMARY = 'admstatssummary';

        public function __construct() {
            $this->setDate();
            $this->initDataSource();
            $this->initMessages();
        }

        /**
         * Sets report date
         * 
         * @return void
         */
        protected function setDate() {
            if (ubRouting::checkPost(self::PROUTE_DATE)) {
                $pDate = ubRouting::post(self::PROUTE_DATE, 'mres');
                if (zb_checkDate($pDate)) {
                    $this->showDate = $pDate;
                } else {
                    $this->showDate = curdate();
                }
            } else {
                $this->showDate = curdate();
            }
        }

        /**
         * Inits data source database abstraction layer
         * 
         * @return void
         */
        protected function initDataSource() {
            $this->dataSource = new NyanORM(self::TABLE_DATASOURCE);
        }

        /**
         * Inits system message helper for further usage
         * 
         * @return void
         */
        protected function initMessages() {
            $this->messages = new UbillingMessageHelper();
        }

        /**
         * Loads raw data from database
         * 
         * @return void
         */
        protected function loadReportData() {
            $this->dataSource->selectable('id,date,admin');
            $this->dataSource->where('date', 'LIKE', $this->showDate . '%');
            $this->dataSource->where('admin', '!=', 'guest');
            $this->dataSource->where('admin', '!=', 'external');
            $this->dataSource->orderBy('id', 'ASC');
            $this->allDataRaw = $this->dataSource->getAll();
        }

        /**
         * Renders administrators work-time stats
         * 
         * @return string
         */
        public function renderReport() {
            $this->loadReportData();
            $result = '';
            $result .= $this->renderControlsForm();
            $dataTmp = array();
            $timelineData = array();
            if (!empty($this->allDataRaw)) {
                $allAdmins = $this->getAdministratorNames();
                foreach ($this->allDataRaw as $io => $each) {
                    $dataTmp[$each['admin']][] = $each['date'];
                }

                if (!empty($dataTmp)) {
                    foreach ($dataTmp as $eachAdmin => $eachDate) {
                        $adminLabel = (isset($allAdmins[$eachAdmin])) ? $allAdmins[$eachAdmin] : $eachAdmin;
                        $startTime = reset($eachDate);
                        $endTime = end($eachDate);
                        $startTimeStamp = strtotime($startTime);
                        $endTimeStamp = strtotime($endTime);
                        $workTime = $endTimeStamp - $startTimeStamp;
                        $timelineData[$adminLabel]['start'] = $startTime;
                        $timelineData[$adminLabel]['end'] = $endTime;
                        $timelineData[$adminLabel]['worktime'] = $workTime;
                    }
                }

                //normal timeline render
                if (!ubRouting::checkPost(self::PROUTE_SUMMARY)) {
                    $result .= $this->renderTimeline($timelineData);
                } else {
                    //just administrators summary
                    $result .= $this->renderTimeSummary($timelineData);
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
            }
            return($result);
        }

        /**
         * Returns cached administrators logins as login=>realname
         * 
         * @return array
         */
        protected function getAdministratorNames() {
            $result = ts_GetAllEmployeeLoginsCached();
            $result = unserialize($result);
            return($result);
        }

        /**
         * Renders report in human-viewable format
         * 
         * @return string
         */
        protected function renderTimeline($timelineData) {
            $result = '';
            $containerId = 'timeline' . wf_InputId();
            $timelineCode = "['Activity', 'Start Time', 'End Time'],";

            if (!empty($timelineData)) {
                foreach ($timelineData as $key => $eachTime) {
                    $startTime = $eachTime['start'];
                    $startTime = strtotime($startTime);
                    $startYear = date("Y", $startTime);
                    $startMonth = date("n", $startTime) - 1;
                    $startDay = date("d", $startTime);
                    $startHour = date("H", $startTime);
                    $startMinute = date("i", $startTime);

                    $endTime = $eachTime['end'];
                    $endTime = strtotime($endTime);
                    $endYear = date("Y", $endTime);
                    $endMonth = date("n", $endTime) - 1;
                    $endDay = date("d", $endTime);
                    $endHour = date("H", $endTime);
                    $endMinute = date("i", $endTime);

                    $timelineCode .= "
                            ['" . $key . "',
                             new Date(" . $startYear . ", " . $startMonth . ", " . $startDay . ", " . $startHour . ", " . $startMinute . "),
                             new Date(" . $endYear . ", " . $endMonth . ", " . $endDay . ", " . $endHour . ", " . $endMinute . ")],";
                }
                $timelineCode = zb_CutEnd($timelineCode);
            }


            $result .= wf_tag('div', false, '', 'id="' . $containerId . '"') . wf_tag('div', true);
            $result .= wf_tag('script', false, '', 'type="text/javascript" src="https://www.gstatic.com/charts/loader.js"') . wf_tag('script', true);
            $result .= wf_tag('script', false);
            $result .= "google.charts.load('current', {'packages':['timeline']});";
            $result .= "google.charts.setOnLoadCallback(drawChart);";
            $result .= " function drawChart() {
                          var data = google.visualization.arrayToDataTable([
                          " . $timelineCode . "
                          ]);
                          
                            var paddingHeight = 50;
                            var rowHeight = data.getNumberOfRows() * 41;
                            var chartHeight = rowHeight + paddingHeight;

                            var options = {
                              height: chartHeight,
                              hAxis: {
                                     format: 'HH:mm'
                                     }
                            };

                            var chart = new google.visualization.Timeline(document.getElementById('" . $containerId . "'));

                            chart.draw(data, options);
                          }
                        ";
            $result .= wf_tag('script', true);

            return($result);
        }

        /**
         * Renders administrator worktime stats summary
         * 
         * @param array $timelineData
         * 
         * @return string
         */
        protected function renderTimeSummary($timelineData) {
            $result = '';
            $worktimeStats = array();
            $totalWorktime = 0;

            if (!empty($timelineData)) {
                foreach ($timelineData as $key => $eachTime) {
                    $worktimeStats[$key] = $eachTime['worktime'];
                    $totalWorktime += $eachTime['worktime'];
                }
            }

            if (!empty($worktimeStats)) {
                $cells = wf_TableCell(__('Administrator'));
                $cells .= wf_TableCell(__('Time'));
                $cells .= wf_TableCell(__('Visual'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($worktimeStats as $eachAdmin => $worktime) {
                    $cells = wf_TableCell($eachAdmin);
                    $cells .= wf_TableCell(zb_formatTime($worktime), '', '', 'sorttable_customkey="' . $worktime . '"');
                    $cells .= wf_TableCell(web_bar($worktime, $totalWorktime), '40%', '', 'sorttable_customkey="' . $worktime . '"');
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            }
            return($result);
        }

        /**
         * Returns date selection form
         * 
         * @return string
         */
        protected function renderControlsForm() {
            $result = '';
            $inputs = wf_DatePickerPreset(self::PROUTE_DATE, $this->showDate, true) . ' ';
            $inputs .= wf_CheckInput(self::PROUTE_SUMMARY, __('No charts'), false, ubRouting::checkPost(self::PROUTE_SUMMARY)) . ' ';
            $inputs .= wf_Submit(__('Show'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_CleanDiv();
            return($result);
        }

    }

    $admStats = new AdministratorStats();
    show_window('', wf_BackLink('?module=permissions'));
    show_window(__('Administrators timeline'), $admStats->renderReport());
    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
