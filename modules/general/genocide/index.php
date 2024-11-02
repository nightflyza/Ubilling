<?php

if (cfr('GENOCIDE')) {
    /**
     * What is dead may never die
     */
    class Genocide {
        /**
         * Default usage percent value
         *
         * @var int
         */
        protected $lookupPercent = 35;
        /**
         * Current day number
         *
         * @var int
         */
        protected $dayNum = 0;
        /**
         * Current year
         *
         * @var int
         */
        protected $curYear = 0;
        /**
         * Current month
         *
         * @var int
         */
        protected $curMonth = 0;
        /**
         * Tariffs with existing limits as tariffName=>speed
         *
         * @var array
         */
        protected $controlTariffs = array();
        /**
         * Contains limited tariffs as tariffName=>daily bytes limit
         *
         * @var array
         */
        protected $tariffLimits = array();
        /**
         * Contains full users cached data as login=>userData
         *
         * @var array
         */
        protected $allUsersData = array();
        /**
         * Contains stargezer users table content as login=>stgData
         *
         * @var array
         */
        protected $allUserStgData = array();
        /**
         * Message helper instance
         *
         * @var object
         */
        protected $messages = '';
        /**
         * Contains preloaded all users traffic summary as login=>bytes
         *
         * @var array
         */
        protected $allUsersTraffic = array();
        /**
         * Limit rules database abstraction layer
         *
         * @var object
         */
        protected $genocideDb = '';

        const URL_ME = '?module=genocide';

        public function __construct() {
            $this->initMessages();
            $this->setDateProps();
            $this->initDbs();
            $this->loadUsersData();
            $this->loadControlTariffs();
            $this->loadUsersTraffic();
            $this->recalcLimits();
        }

        /**
         * Sets the date properties for the current instance.
         *
         * @return void
         */
        protected function setDateProps() {
            $this->dayNum = date("j");
            $this->curYear = date("Y");
            $this->curMonth = date("n");
        }

        /**
         * Initializes the messages property with an instance of UbillingMessageHelper.
         *
         * @return void
         */
        protected function initMessages() {
            $this->messages = new UbillingMessageHelper();
        }

        /**
         * Inits database abstraction layer
         *
         * @return void
         */
        protected function initDbs() {
            $this->genocideDb = new NyanORM('genocide');
        }


        /**
         * Loads user data and stargazer data into the properties.
         *
         * @return void
         */
        protected function loadUsersData() {
            $this->allUsersData = zb_UserGetAllDataCache();
            $this->allUserStgData = zb_UserGetAllStargazerDataAssoc();
        }

        /**
         * Sets the normal percentage value.
         *
         * This method sets the normal percentage value by applying a filter to ensure
         * it is a float, and then recalculates the limits based on the new percentage.
         *
         * @param float $percent The percentage value to set.
         * @return void
         */
        public function setNormalPercent($percent) {
            $this->lookupPercent = ubRouting::filters($percent, 'float');
            $this->recalcLimits();
        }

        /**
         * Loads control tariffs from the database and populates the controlTariffs property.
         *
         * @return void
         */
        protected function loadControlTariffs() {
            $all = $this->genocideDb->getAll();
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->controlTariffs[$each['tariff']] = $each['speed'];
                }
            }
        }

        /**
         * Creates a new limit entry in the database with the specified tariff and speed.
         *
         * @param string $tariff The tariff to be added. 
         * @param int $speed The speed to be added. 
         * 
         * @return void
         */
        public function createLimit($tariff, $speed) {
            $tariff = ubRouting::filters($tariff, 'mres');
            $speed = ubRouting::filters($speed, 'int');
            $this->genocideDb->data('tariff', $tariff);
            $this->genocideDb->data('speed', $speed);
            $this->genocideDb->create();
            log_register("GENOCIDE ADD `" . $tariff . "`");
        }

        /**
         * Deletes a limit based on the provided tariff name
         *
         * @param string $tariff The tariff to be deleted.
         * 
         * @return void
         */
        public function deleteLimit($tariff) {
            $tariff = ubRouting::filters($tariff, 'mres');
            $this->genocideDb->where('tariff', '=', $tariff);
            $this->genocideDb->delete();
            log_register("GENOCIDE DELETE `" . $tariff . "`");
        }

        /**
         * Calculate the number of bytes transferred in a day given a speed in kilobits per second (kbps).
         *
         * @param int $kbps The speed in kilobits per second.
         * 
         * @return float
         */
        protected function calculateBytesPerDay($kbps) {
            $bytesPerSecond = ($kbps * 1000) / 8;
            $secondsInDay = 24 * 60 * 60;
            $result = $bytesPerSecond * $secondsInDay;
            return ($result);
        }

        /**
         * Recalculates the limits for each tariff in the controlTariffs array.
         * 
         * This method iterates over the controlTariffs array, calculates the bytes per day
         * for each tariff based on its speed, and then calculates the percentage of bytes
         * using the lookupPercent value. The calculated percentage of bytes is then stored
         * in the tariffLimits array for each corresponding tariff.
         * 
         * @return void
         */
        protected function recalcLimits() {
            if (!empty($this->controlTariffs)) {
                foreach ($this->controlTariffs as $tariff => $speed) {
                    $bytesPerDay = $this->calculateBytesPerDay($speed);
                    $percentBytes = zb_Percent($bytesPerDay, $this->lookupPercent);
                    $this->tariffLimits[$tariff] = $percentBytes; //one day limit
                }
            }
        }

        /**
         * Load and aggregate users' traffic data from different sources.
         *
         * This method loads users' traffic data from the main records, Ishimura database or OphanimFlow,
         * and aggregates the data into the $allUsersTraffic property.
         *
         * @return void
         */
        protected function loadUsersTraffic() {
            global $ubillingConfig;
            $ishimuraFlag = $ubillingConfig->getAlterParam(MultiGen::OPTION_ISHIMURA);
            $ophanimFlag = $ubillingConfig->getAlterParam(OphanimFlow::OPTION_ENABLED);
            if (!empty($this->allUserStgData)) {
                foreach ($this->allUserStgData as $io => $each) {
                    $this->allUsersTraffic[$each['login']] = ($each['D0'] + $each['U0']);
                }
            }

            if ($ishimuraFlag) {
                $ishimuraDb = new NyanORM(MultiGen::NAS_ISHIMURA);

                $ishimuraDb->where('year', '=', $this->curYear);
                $ishimuraDb->where('month', '=', $this->curMonth);
                $all = $ishimuraDb->getAll();
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        if (isset($this->allUsersTraffic[$each['login']])) {
                            $this->allUsersTraffic[$each['login']] += ($each['D0'] + $each['U0']);
                        }
                    }
                }
            }

            if ($ophanimFlag) {
                $ophanimFlow = new OphanimFlow();
                $all = $ophanimFlow->getAllUsersAggrTraff();
                if (!empty($all)) {
                    if (!empty($all)) {
                        foreach ($all as $login => $counters) {
                            if (isset($this->allUsersTraffic[$login])) {
                                $this->allUsersTraffic[$login] += $counters;
                            }
                        }
                    }
                }
            }
        }


        /**
         * Returns creation form
         *
         * @return string
         */
        function renderCreateForm() {
            $result = '';
            $allTariffs = zb_TariffGetAllData();

            $tariffParams = array();
            if (!empty($allTariffs)) {
                foreach ($allTariffs as $io => $tariffData) {
                    if (!isset($this->controlTariffs[$tariffData['name']])) {
                    $tariffParams[$tariffData['name']] = $tariffData['name'];
                    }
                }
            }
            $addinputs = web_tariffselector();
            $addinputs = wf_Selector('tariffsel', $tariffParams, __('Tariff'), '', false);
            $addinputs .= wf_TextInput('newgenocide', __('Speed') . ' (' . ('Kbit/s') . ')', '', false, '10', 'digits');
            $addinputs .= wf_Submit('Create');
            $result = wf_Form('', 'POST', $addinputs, 'glamour');
            return ($result);
        }

        /**
         * Returns array of users with exceeded daily traffic limit this month 
         *
         * @return array
         */
        protected function getGenocideUsers() {
            $result = array();
            foreach ($this->allUsersData as $eachLogin => $eachUserData) {
                $userTariff = $eachUserData['Tariff'];
                if (isset($this->tariffLimits[$userTariff])) {
                    if (isset($this->allUsersTraffic[$eachLogin])) {
                        $curDayLimit = $this->tariffLimits[$userTariff] * $this->dayNum;
                        if ($this->allUsersTraffic[$eachLogin] > $curDayLimit) {
                            $result[$eachLogin] = $this->allUsersTraffic[$eachLogin];
                        }
                    }
                }
            }
            return ($result);
        }

        /**
         * Renders form to set lookup percent
         *
         * @return string
         */
        protected function renderPercentForm() {
            $result = '';
            $geninputs = wf_TextInput('lookupPercent', 'Normal bandwidth load', $this->lookupPercent, false, '2', 'digits') . ' %';
            $geninputs .= wf_HiddenInput('change_settings', 'true') . ' ';
            $geninputs .= wf_Submit('Change');
            $result .= wf_Form('', 'POST', $geninputs, 'glamour');
            return ($result);
        }

        /**
         * Renders available genocide tariffs list
         *
         * @return string
         */
        protected function renderTariffList() {
            $result = '';
            if (!empty($this->controlTariffs)) {

                $result .= $this->renderPercentForm();
                $result .= wf_delimiter();


                $tablecells = wf_TableCell(__('Tariff'));
                $tablecells .= wf_TableCell(__('Normal day band'));
                $tablecells .= wf_TableCell(__('Current date normal band'));
                $tablecells .= wf_TableCell(__('Speed'));
                $tablecells .= wf_TableCell(__('Actions'));
                $tablerows = wf_TableRow($tablecells, 'row1');

                $i = 0;

                foreach ($this->controlTariffs as $eachtariff => $speed) {
                    $tablecells = wf_TableCell($eachtariff);
                    $tablecells .= wf_TableCell(stg_convert_size($this->tariffLimits[$eachtariff]));
                    $tablecells .= wf_TableCell(stg_convert_size($this->tariffLimits[$eachtariff] * $this->dayNum));
                    $tablecells .= wf_TableCell($speed);

                    $gactions = wf_JSAlert(self::URL_ME . '&delete=' . $eachtariff, web_delete_icon(), 'Are you serious');
                    $tablecells .= wf_TableCell($gactions);
                    $tablerows .= wf_TableRow($tablecells, 'row5');
                    $i++;
                }

                $result .= wf_TableBody($tablerows, '100%', '0', '');
                $result .= wf_delimiter();
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
            return ($result);
        }

        /**
         * Renders users report part
         *
         * @return string
         */
        protected function renderUsers() {
            $result = '';
            $genocideUsers = $this->getGenocideUsers();
            if (!empty($genocideUsers)) {
                $tablecells = wf_TableCell(__('Login'));
                $tablecells .= wf_TableCell(__('Full address'));
                $tablecells .= wf_TableCell(__('Real Name'));
                $tablecells .= wf_TableCell(__('Tariff'));
                $tablecells .= wf_TableCell(__('IP'));
                $tablecells .= wf_TableCell(__('Traffic'));
                $tablerows = wf_TableRow($tablecells, 'row1');

                foreach ($genocideUsers as $eachLogin => $eachTraffic) {
                    $userData = $this->allUsersData[$eachLogin];
                    $profilelink = wf_Link(UserProfile::URL_PROFILE . $eachLogin, web_profile_icon() . ' ' . $eachLogin);
                    $tablecells = wf_TableCell($profilelink);
                    $tablecells .= wf_TableCell($userData['fulladress']);
                    $tablecells .= wf_TableCell($userData['realname']);
                    $tablecells .= wf_TableCell($userData['Tariff']);
                    $tablecells .= wf_TableCell($userData['ip'], '', '', 'sorttable_customkey="' . ip2int($userData['ip']) . '"');
                    $tablecells .= wf_TableCell(stg_convert_size($eachTraffic), '', '', 'sorttable_customkey="' . $eachTraffic . '"');
                    $tablerows .= wf_TableRow($tablecells, 'row5');
                }
                $result .= wf_TableBody($tablerows, '100%', '0', 'sortable');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing found'), 'success');
            }

            return ($result);
        }

        /**
         * Renders report body with some controls
         *
         * @return void
         */
        public function renderReport() {
            $result = '';
            $result .= $this->renderTariffList();
            $result .= $this->renderUsers();

            return ($result);
        }
    }

    $genocide = new Genocide();

    //setting lookup percent
    if (ubRouting::checkPost('change_settings')) {
        $genocide->setNormalPercent(ubRouting::post('lookupPercent', 'float'));
    }

    //creating new limit
    if (ubRouting::checkPost(array('tariffsel', 'newgenocide'))) {
        $genocide->createLimit(ubRouting::post('tariffsel'), ubRouting::post('newgenocide', 'int'));
        ubRouting::nav($genocide::URL_ME);
    }

    //existing limits deletion
    if (ubRouting::checkGet('delete')) {
        $genocide->deleteLimit(ubRouting::get('delete'));
        ubRouting::nav($genocide::URL_ME);
    }

    $creationDialog = wf_modalAuto(web_icon_create(), __('Create'), $genocide->renderCreateForm());
    show_window(__('Genocide') . ' ' . $creationDialog, $genocide->renderReport());
} else {
    show_error(__('Access denied'));
}
