<?php

if ($system->checkForRight('STREETEPORT')) {

    /**
     * streets report base class
     */
    class ReportStreets {

        /**
         * Contains cities as id=>name
         * 
         * @var array
         */
        protected $cities = array();

        /**
         * Contains streets as id=>streetdata 
         *
         * @var array
         */
        protected $streets = array();

        /**
         * Contains builds as id=>builddata
         *
         * @var array
         */
        protected $builds = array();

        /**
         * contains apt related shit
         *
         * @var array
         */
        protected $apts = array();

        /**
         * Payments abstraction layer placeholder
         *
         * @var object
         */
        protected $payments = '';

        /**
         * Total user counter
         *
         * @var int
         */
        protected $totalusercount = 0;

        /**
         * Contains payments search year
         *
         * @var int
         */
        protected $year = '';

        /**
         * Contains payments search month with lezding zero
         *
         * @var string
         */
        protected $month = '';

        public function __construct() {
            $this->setDates();
            $this->initPayments();
            $this->loadCities();
            $this->loadStreets();
            $this->loadBuilds();
            $this->loadApts();

            $this->countApts();
            $this->countBuilds();
        }

        /**
         * Internal dates setter
         * 
         * @return void
         */
        protected function setDates() {
            if (ubRouting::checkPost(array('showyear', 'showmonth'))) {
                $this->year = ubRouting::post('showyear', 'int');
                $this->month = ubRouting::post('showmonth', 'int');
            } else {
                $this->year = curyear();
                $this->month = date("m");
            }
        }

        /**
         * Inits payments abstraction layer
         * 
         * @return void
         */
        protected function initPayments() {
            $this->payments = new NyanORM('payments');
            if (!empty($this->year) AND ! empty($this->month)) {
                $dateFilter = $this->year . '-' . $this->month . '-%';
                $this->payments->where('date', 'LIKE', $dateFilter);
                $this->payments->where('summ', '>', 0);
            }
        }

        /**
         * loads available cities from database into private data property
         * 
         * @return void
         */
        protected function loadCities() {
            $query = "SELECT * from `city`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->cities[$each['id']] = $each['cityname'];
                }
            }
        }

        /**
         * loads available streets from database into private data property
         * 
         * @return void
         */
        protected function loadStreets() {
            $query = "SELECT * from `street`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->streets[$each['id']]['streetname'] = $each['streetname'];
                    $this->streets[$each['id']]['cityid'] = $each['cityid'];
                    $this->streets[$each['id']]['buildcount'] = 0;
                    $this->streets[$each['id']]['usercount'] = 0;
                }
            }
        }

        /**
         * loads available builds from database into private data property
         * 
         * @return void
         */
        protected function loadBuilds() {
            $query = "SELECT * from `build`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->builds[$each['id']]['buildnum'] = $each['buildnum'];
                    $this->builds[$each['id']]['streetid'] = $each['streetid'];
                    $this->builds[$each['id']]['aptcount'] = 0;
                }
            }
        }

        /**
         * loads available apts from database into private data property
         * 
         * @return void
         */
        protected function loadApts() {
            $query = "SELECT * from `apt`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->apts[$each['id']]['apt'] = $each['apt'];
                    $this->apts[$each['id']]['buildid'] = $each['buildid'];
                }
            }
        }

        /**
         * prepares builds data for render report
         * 
         * @return void
         */
        protected function countApts() {
            if (!empty($this->builds)) {
                if (!empty($this->apts)) {
                    foreach ($this->apts as $io => $eachapt) {
                        if (isset($this->builds[$eachapt['buildid']])) {
                            $this->builds[$eachapt['buildid']]['aptcount'] ++;
                            $this->totalusercount++;
                        }
                    }
                }
            }
        }

        /**
         * prepares streets data for render report
         * 
         * @return void
         */
        protected function countBuilds() {
            if (!empty($this->streets)) {
                if (!empty($this->builds)) {
                    foreach ($this->builds as $io => $eachbuild) {
                        if (isset($this->streets[$eachbuild['streetid']])) {
                            $this->streets[$eachbuild['streetid']]['buildcount'] ++;
                            $this->streets[$eachbuild['streetid']]['usercount'] = $this->streets[$eachbuild['streetid']]['usercount'] + $eachbuild['aptcount'];
                        }
                    }
                }
            }
        }

        /**
         * returns colorized register level for street
         * 
         * @param int $usercount  Registered apts (users) count on the street
         * @param int $buildcount Builds count on the street
         * 
         * @return string
         */
        protected function getLevel($usercount, $buildcount) {
            if (($usercount != 0) AND ( $buildcount != 0)) {
                $level = $usercount / $buildcount;
            } else {
                $level = 0;
            }
            $level = round($level, 2);
            $color = 'black';
            if ($level < 2) {
                $color = 'red';
            }
            if ($level >= 3) {
                $color = 'green';
            }
            $result = wf_tag('font', false, '', 'color="' . $color . '"') . $level . wf_tag('font', true);
            return ($result);
        }

        /**
         * renders report by prepeared data
         * 
         * @return string
         */
        public function render() {
            $addrPayments = array(); //city + street => payments total
            $allPayments = $this->payments->getAll();
            $totalPaymentsSumm = 0;
            if (!empty($allPayments)) {
                $allUsers = zb_UserGetAllDataCache();
                foreach ($allPayments as $io => $each) {
                    if (isset($allUsers[$each['login']])) {
                        $userData = $allUsers[$each['login']];
                        $userCity = $userData['cityname'];
                        $userStreet = $userData['streetname'];
                        $userAddr = $userCity . ' ' . $userStreet;
                        if (isset($addrPayments[$userAddr])) {
                            $addrPayments[$userAddr] += $each['summ'];
                        } else {
                            $addrPayments[$userAddr] = $each['summ'];
                        }
                    }
                }
            }

            if (!empty($this->streets)) {

                $cells = wf_TableCell(__('ID'));
                $cells .= wf_TableCell(__('City'));
                $cells .= wf_TableCell(__('Street'));
                $cells .= wf_TableCell(__('Builds'));
                $cells .= wf_TableCell(__('Users'));
                $cells .= wf_TableCell(__('Visual'));
                $cells .= wf_TableCell(__('Level'));
                $cells .= wf_TableCell(__('Money'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($this->streets as $streetid => $each) {
                    $cells = wf_TableCell($streetid);
                    $cells .= wf_TableCell(@$this->cities[$each['cityid']]);
                    $cells .= wf_TableCell($each['streetname']);
                    $cells .= wf_TableCell($each['buildcount']);
                    $cells .= wf_TableCell($each['usercount']);
                    $cells .= wf_TableCell(web_bar($each['usercount'], $this->totalusercount), '15%', '', 'sorttable_customkey="' . $each['usercount'] . '"');
                    $cells .= wf_TableCell($this->getLevel($each['usercount'], $each['buildcount']));
                    $addrString = @$this->cities[$each['cityid']] . ' ' . $each['streetname'];
                    $paymentsSumm = (isset($addrPayments[$addrString])) ? $addrPayments[$addrString] : '0';
                    $totalPaymentsSumm += $paymentsSumm;
                    $cells .= wf_TableCell($paymentsSumm);
                    $rows .= wf_TableRow($cells, 'row5');
                }

                $result = wf_TableBody($rows, '100%', '0', 'sortable');
                $result .= __('Users') . ': ' . $this->totalusercount;
                $result .= wf_tag('br');
                $result .= __('Payments') . ': ' . $totalPaymentsSumm;
            } else {
                $result = __('Nothing found');
            }
            return ($result);
        }

        /**
         * Renders payments date selection form
         * 
         * @return string
         */
        public function renderDateForm() {
            $result = '';
            $inputs = wf_YearSelectorPreset('showyear', __('Year'), false, $this->year) . ' ';
            $inputs .= wf_MonthSelector('showmonth', __('Month'), $this->month, false) . ' ';
            $inputs .= wf_Submit(__('Search') . ' ' . __('payments'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            return($result);
        }

    }

    $streetReport = new ReportStreets();
    show_window(__('Payments'), $streetReport->renderDateForm());
    show_window(__('Streets report'), $streetReport->render());
} else {
    show_error(__('Access denied'));
}
?>
