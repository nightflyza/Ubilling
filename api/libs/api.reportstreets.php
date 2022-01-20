<?php

/**
 * Streets report base class
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

    /**
     * Contains all preprocessed assigns for some agents as fullstreet=>agentid
     *
     * @var array
     */
    protected $allAssigns = array();

    /**
     * Contains available agents as id=>name
     *
     * @var array
     */
    protected $agents = array();

    /**
     * Build passports object placeholder
     *
     * @var object
     */
    protected $buildPassport = '';

    /**
     * Contains build passports enabling flag
     *
     * @var bool
     */
    protected $passportsFlag = false;

    public function __construct() {
        $this->setDates();
        $this->initPayments();
        $this->loadCities();
        $this->loadStreets();
        $this->initBuildPassports();
        $this->loadBuilds();
        $this->loadApts();
        $this->countApts();
        $this->countBuilds();
        $this->loadAllAssigns();
        $this->loadAgents();
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
     * Inits builds passports instance if enabled for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function initBuildPassports() {
        global $ubillingConfig;
        if ($ubillingConfig->getAlterParam('BUILD_EXTENDED')) {
            $this->passportsFlag = true;
            $this->buildPassport = new BuildPassport();
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
     * loads available assigns from database into private prop
     * 
     * @return void
     */
    protected function loadAllAssigns() {
        $assignsTmp = zb_AgentAssignGetAllData();

        if (!empty($assignsTmp)) {
            foreach ($assignsTmp as $io => $each) {
                $this->allAssigns[$each['streetname']] = $each['ahenid'];
            }
        }
    }

    /**
     * loads contragent data into protected prop
     * 
     * @return void
     */
    protected function loadAgents() {
        $tmpArr = array();
        $tmpArr = zb_ContrAhentGetAllData();

        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->agents[$each['id']] = $each['contrname'];
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
                $this->streets[$each['id']]['aptstotal'] = 0;
                $this->streets[$each['id']]['anthills'] = 0;
                $this->streets[$each['id']]['anthillusers'] = 0;
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
                $aptsTotal = 0;
                $antHill = 0;
                if ($this->passportsFlag) {
                    $eachPassport = $this->buildPassport->getPassportData($each['id']);
                    if (@$eachPassport['anthill']) {
                        $aptsTotal = $eachPassport['apts'];
                        $antHill = 1;
                    }
                }

                $this->builds[$each['id']]['aptstotal'] = $aptsTotal;
                $this->builds[$each['id']]['anthill'] = $antHill;
                $this->builds[$each['id']]['anthillusers'] = 0;
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
                        if ($this->builds[$eachapt['buildid']]['anthill']) {
                            $this->builds[$eachapt['buildid']]['anthillusers'] ++;
                        }
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
                        if ($eachbuild['anthill']) {
                            $this->streets[$eachbuild['streetid']]['aptstotal'] += $eachbuild['aptstotal'];
                            $this->streets[$eachbuild['streetid']]['anthillusers'] += $eachbuild['anthillusers'];
                            $this->streets[$eachbuild['streetid']]['anthills'] ++;
                        }
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
            $cells .= wf_TableCell(__('Contrahent name'));
            $cells .= wf_TableCell(__('Builds'));
            if ($this->passportsFlag) {
                $cells .= wf_TableCell(__('Anthill'));
                $cells .= wf_TableCell(__('Apartments'));
            }
            $cells .= wf_TableCell(__('Users'));
            if ($this->passportsFlag) {
                $cells .= wf_TableCell(wf_img_sized('skins/ymaps/build.png', __('Users') . ': ' . __('Apartment house'), 12));
                $cells .= wf_TableCell(wf_img_sized('skins/ymaps/coverage.png', __('Coverage'), 12));
            }
            $cells .= wf_TableCell(__('Visual'));
            $cells .= wf_TableCell(__('Level'));
            $cells .= wf_TableCell(__('Money'));
            $rows = wf_TableRow($cells, 'row1');


            foreach ($this->streets as $streetid => $each) {
                $streetAgentId = 0;
                $addrString = @$this->cities[$each['cityid']] . ' ' . $each['streetname'];
                if (!empty($this->allAssigns)) {
                    foreach ($this->allAssigns as $streetAssign => $agentId) {
                        if (ispos($addrString, $streetAssign)) {
                            $streetAgentId = $agentId; //gotcha motherfucker!
                        }
                    }
                }
                if ($streetAgentId != 0) {
                    $streetAgentName = $this->agents[$streetAgentId];
                    $cellsClass = 'todaysig';
                } else {
                    $streetAgentName = '';
                    $cellsClass = 'row3';
                }

                $cells = wf_TableCell($streetid, '', $cellsClass);
                $cells .= wf_TableCell(@$this->cities[$each['cityid']]);
                $cells .= wf_TableCell($each['streetname']);
                $cells .= wf_TableCell($streetAgentName);
                $cells .= wf_TableCell($each['buildcount']);
                if ($this->passportsFlag) {
                    $cells .= wf_TableCell($each['anthills']);
                    $cells .= wf_TableCell($each['aptstotal']);
                }
                $usersCount = $each['usercount'];


                $cells .= wf_TableCell($usersCount);
                if ($this->passportsFlag) {
                    $anthillUsers = $each['anthillusers'];
                    $cells .= wf_TableCell($anthillUsers);
                    if ($anthillUsers > 0) {
                        $coveragePercent = zb_PercentValue($each['aptstotal'], $anthillUsers) . '%';
                    } else {
                        $coveragePercent = '';
                    }
                    $cells .= wf_TableCell($coveragePercent);
                }
                $cells .= wf_TableCell(web_bar($each['usercount'], $this->totalusercount), '15%', '', 'sorttable_customkey="' . $each['usercount'] . '"');
                $cells .= wf_TableCell($this->getLevel($each['usercount'], $each['buildcount']));

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
            $messages = new UbillingMessageHelper();
            $result = $messages->getStyledMessage(__('Nothing found'), 'warning');
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
