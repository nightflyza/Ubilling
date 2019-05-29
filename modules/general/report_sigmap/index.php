<?php

if (cfr('REPORTSIGNUP') AND cfr('USERSMAP')) {

    class ReportSigMap {

        /**
         * Contains all available users data
         *
         * @var array
         */
        protected $allUserData = array();

        /**
         * Contains system maps configuration as key=>value
         *
         * @var array
         */
        protected $mapsCfg = array();

        /**
         * Contains selected year to show
         *
         * @var int
         */
        protected $showYear = '';

        /**
         * Contains selected month to show
         *
         * @var int
         */
        protected $showMonth = '';

        /**
         * Contains default signups data source table
         *
         * @var string
         */
        protected $dataTable = 'userreg';

        /**
         * Contains count of users without build geo assigned
         *
         * @var int
         */
        protected $noGeoBuilds = 0;

        /**
         * Contains count of users whitch is not present currently in database
         *
         * @var int
         */
        protected $deletedUsers = 0;

        /**
         * Contains count of registered users by period
         *
         * @var int
         */
        protected $registeredUsers = 0;

        /**
         * System message helper object placeholder
         *
         * @var object
         */
        protected $messages = '';

        /**
         * Creates new report instance
         */
        public function __construct() {
            $this->setDateData();
            $this->initMessages();
            $this->loadMapsConfig();
            $this->loadUsers();
        }

        /**
         * Loads system maps configuration file
         * 
         * @global object $ubillingConfig
         * 
         * @return void
         */
        protected function loadMapsConfig() {
            global $ubillingConfig;
            $this->mapsCfg = $ubillingConfig->getYmaps();
        }

        /**
         * Inits system message helper object instance for further usage
         * 
         * @return void
         */
        protected function initMessages() {
            $this->messages = new UbillingMessageHelper();
        }

        /**
         * Loads all users cached data
         * 
         * @return void
         */
        protected function loadUsers() {
            $this->allUserData = zb_UserGetAllDataCache();
        }

        /**
         * Sets selected year/month properties of current as defaults
         * 
         * @return void
         */
        protected function setDateData() {
            if (wf_CheckPost(array('showyear'))) {
                $this->showYear = vf($_POST['showyear'], 3);
            } else {
                $this->showYear = curyear();
            }

            if (wf_CheckPost(array('showmonth'))) {
                $this->showMonth = vf($_POST['showmonth'], 3);
            } else {
                $this->showMonth = date('m');
            }
        }

        /**
         * Returns array of user signups filtered by year/month
         * 
         * @return array
         */
        protected function getRegisteredUsers() {
            $monthFilter = ($this->showMonth != '1488') ? $this->showMonth : '';
            $query = "SELECT * from `" . $this->dataTable . "` WHERE `date` LIKE '" . $this->showYear . "-" . $monthFilter . "%'";
            $result = simple_queryall($query);
            return ($result);
        }

        /**
         * Returns list of formatted placemarks for map rendering
         * 
         * @param array $userSignups
         * 
         * @return string
         */
        protected function getPlacemarks($userSignups) {
            $result = '';
            $buildsData = array();
            $buildsCounters = array();
            if (!empty($userSignups)) {
                foreach ($userSignups as $io => $each) {
                    if (isset($this->allUserData[$each['login']])) {
                        $userData = $this->allUserData[$each['login']];
                        if (!empty($userData['geo'])) {
                            $signupDate = date("Y-m-d", strtotime($each['date']));
                            $userLink = $signupDate . ': ' . wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $userData['fulladress']);
                            $userLink = trim($userLink);
                            if (!isset($buildsData[$userData['geo']])) {
                                $buildsData[$userData['geo']]['data'] = $userLink;
                                $buildsData[$userData['geo']]['count'] = 1;
                            } else {
                                $buildsData[$userData['geo']]['data'] .=trim(wf_tag('br')) . $userLink;
                                $buildsData[$userData['geo']]['count'] ++;
                            }
                        } else {
                            $this->noGeoBuilds++;
                        }
                    } else {
                        $this->deletedUsers++;
                    }
                    $this->registeredUsers++;
                }

                if (!empty($buildsData)) {

                    foreach ($buildsData as $coords => $usersInside) {
                        if ($usersInside['count'] > 1) {
                            $placeMarkIcon = 'twirl#buildingsIcon';
                        } else {
                            $placeMarkIcon = 'twirl#houseIcon';
                        }
                        $result.= generic_mapAddMark($coords, $usersInside['data'], __('Users') . ': ' . $usersInside['count'], '', $placeMarkIcon, '', $this->mapsCfg['CANVAS_RENDER']);
                    }
                }
            }
            return ($result);
        }

        /**
         * Returns year/month filtering form
         * 
         * @return string
         */
        public function renderDateForm() {
            $result = '';
            $inputs = wf_YearSelectorPreset('showyear', __('Year'), false, $this->showYear) . ' ';
            $inputs.= wf_MonthSelector('showmonth', __('Month'), $this->showMonth, false, true) . ' ';
            $inputs.= wf_Submit(__('Show'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');

            return ($result);
        }

        /**
         * Renders report as map
         * 
         * @return string
         */
        public function renderMap() {
            $result = '';
            $allSignups = $this->getRegisteredUsers();
            $placemarks = $this->getPlacemarks($allSignups);
            $result.=generic_MapContainer();
            $result.=generic_MapInit($this->mapsCfg['CENTER'], $this->mapsCfg['ZOOM'], $this->mapsCfg['TYPE'], $placemarks, '', $this->mapsCfg['LANG'], 'ubmap');
            return ($result);
        }

        /**
         * Renders deleted users or unknown geo builds stats if they available
         * 
         * @return string
         */
        public function renderStats() {
            $result = '';
            if ($this->registeredUsers) {
                $result.=$this->messages->getStyledMessage(__('Total users registered') . ': ' . $this->registeredUsers, 'success');
            }
            if ($this->registeredUsers AND $this->noGeoBuilds) {
                $result.=$this->messages->getStyledMessage(__('Users rendered on map') . ': ' . ($this->registeredUsers - $this->noGeoBuilds), 'info');
            }
            if ($this->noGeoBuilds) {
                $result.=$this->messages->getStyledMessage(__('Builds without geo location assigned') . ': ' . $this->noGeoBuilds, 'warning');
            }
            if ($this->deletedUsers) {
                $result.=$this->messages->getStyledMessage(__('Already deleted users') . ': ' . $this->deletedUsers, 'error');
            }

            return ($result);
        }

    }

    $sigmap = new ReportSigMap();
    
    show_window(__('Filters'), $sigmap->renderDateForm());
    show_window(__('Signups map'), $sigmap->renderMap());
    show_window('', $sigmap->renderStats());
    show_window('', wf_BackLink('?module=report_signup'));
} else {
    show_error(__('Access denied'));
}
?>