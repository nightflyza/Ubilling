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
            $dataTmp = array();
            $timelineData = array();
            if (!empty($this->allDataRaw)) {
                $allAdmins = $this->getAdministratorNames();
                foreach ($this->allDataRaw as $io => $each) {
                    $dataTmp[$each['admin']][] = $each['date'];
                }

                if (!empty($dataTmp)) {
                    foreach ($dataTmp as $eachAdmin => $eachDate) {
                        $adminName = (isset($allAdmins[$eachAdmin])) ? $allAdmins[$eachAdmin] : $eachAdmin;
                        $timelineData[$adminName]['start'] = reset($eachDate);
                        $timelineData[$adminName]['end'] = end($eachDate);
                    }
                }
                $result .= $this->renderTimeline($timelineData);
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
            $result .= $this->renderDateForm();
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

                            var options = {
                              height: 800,
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
         * Returns date selection form
         * 
         * @return string
         */
        protected function renderDateForm() {
            $result = '';
            $inputs = wf_DatePickerPreset(self::PROUTE_DATE, $this->showDate, true);
            $inputs .= wf_Submit(__('Show'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            return($result);
        }

    }

    $admStats = new AdministratorStats();
    show_window('', wf_BackLink('?module=permissions'));
    show_window(__('Administrators timeline'), $admStats->renderReport());
} else {
    show_error(__('Access denied'));
}
