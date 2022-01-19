<?php

/**
 * Report for filtering and display basic builds info
 */
class ReportBuilds {

    /**
     * Contains alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains basic city data as cityid=>name
     *
     * @var array
     */
    protected $allCities = array();

    /**
     * Contains full streets data as streetid=>streetdata
     *
     * @var array
     */
    protected $allStreets = array();

    /**
     * Contains all streets names array as streetid=>streetname
     *
     * @var array
     */
    protected $allStreetNames = array();

    /**
     * Contains full builds data as id=>builddata
     *
     * @var array
     */
    protected $allBuilds = array();

    /**
     * Contains array of build apartments as buildid=>aptsData
     *
     * @var array
     */
    protected $allApts = array();

    /**
     * Just BUILD_EXTENDED option based flag
     *
     * @var bool
     */
    protected $buildPassportsFlag = false;

    /**
     * Is ADcomments enabled flag?
     *
     * @var bool
     */
    protected $adCommentsFlag = false;

    /**
     * Build passports instance placeholder
     *
     * @var object
     */
    protected $buildPassports = '';

    /**
     * System messages helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some routes, urls, defines etc
     */
    const URL_ME = '?module=report_builds';
    const ROUTE_AJLIST = 'ajaxbuildslist';
    const ROUTE_EXPORTS = 'exportcontrols';
    const PROUTE_FILTERS = 'applynewfilters';
    const PROUTE_FILTERCITY = 'filtercityid';
    const PROUTE_FILTERSTREET = 'filterstreetid';

    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
        $this->loadCities();
        $this->loadStreets();
        $this->loadBuilds();
        $this->loadApartments();
        $this->initBuildPassports();
    }

    /**
     * Preloads some required configs and sores it in protected properties
     * 
     * @global object $ubillingConfig
     * 
     * @return vod
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (@$this->altCfg['BUILD_EXTENDED']) {
            $this->buildPassportsFlag = true;
        }

        if (@$this->altCfg['ADCOMMENTS_ENABLED']) {
            $this->adCommentsFlag = true;
        }
    }

    /**
     * Inits message helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads city data from database
     * 
     * @return void
     */
    protected function loadCities() {
        $this->allCities = zb_AddressGetFullCityNames();
    }

    /**
     * Loads streets data from database
     * 
     * @return void
     */
    protected function loadStreets() {
        $this->allStreets = zb_AddressGetStreetsDataAssoc('ORDER BY `streetname` ASC');
        if (!empty($this->allStreets)) {
            foreach ($this->allStreets as $io => $each) {
                $this->allStreetNames[$each['id']] = $each['streetname'];
            }
        }
    }

    /**
     * Loads builds data from database
     * 
     * @return void
     */
    protected function loadBuilds() {
        $this->allBuilds = zb_AddressGetBuildAllDataAssoc();
    }

    /**
     * Loads apartments data from database
     * 
     * @return void
     */
    protected function loadApartments() {
        $aptTmp = zb_AddressGetAptAllData();
        if (!empty($aptTmp)) {
            foreach ($aptTmp as $io => $each) {
                $this->allApts[$each['buildid']][] = $each;
            }
        }
    }

    /**
     * Inits build passports object for further usage
     * 
     * @return void
     */
    protected function initBuildPassports() {
        if ($this->buildPassportsFlag) {
            $this->buildPassports = new BuildPassport();
        }
    }

    /**
     * Returns city id of build
     * 
     * @param int $buildId
     * 
     * @return int
     */
    protected function getCityOfBuild($buildId) {
        $result = 0;
        $streetId = $this->getStreetOfBuild($buildId);
        if ($streetId) {
            if (isset($this->allStreets[$streetId])) {
                $streetData = $this->allStreets[$streetId];
                $result = $streetData['cityid'];
            }
        }
        return($result);
    }

    /**
     * Returns street id of build
     * 
     * @param int $buildId
     * 
     * @return int
     */
    protected function getStreetOfBuild($buildId) {
        $result = 0;
        if (isset($this->allBuilds[$buildId])) {
            $result = $this->allBuilds[$buildId]['streetid'];
        }
        return($result);
    }

    /**
     * Returns apartments count in some build
     * 
     * @param int $buildId
     * 
     * @return int
     */
    protected function getAptCount($buildId) {
        $result = 0;
        if (isset($this->allApts[$buildId])) {
            $result = sizeof($this->allApts[$buildId]);
        }
        return($result);
    }

    /**
     * Renders report container
     * 
     * @return string
     */
    public function renderBuilds() {
        $result = '';
        if (!empty($this->allBuilds)) {
            $columns = array(
                'City',
                'Street',
                'Building number',
                'Users',
                'Actions'
            );

            if ($this->buildPassportsFlag) {
                $columns = array(
                    'City',
                    'Street',
                    'Building number',
                    'Owner',
                    'Phone',
                    'Type',
                    'Floors',
                    'Entrances',
                    'Apartments',
                    'Users',
                    '%',
                    'Access',
                    'Actions'
                );
            }
            $opts = '"order": [[ 1, "asc" ]]';

            //optional ID column
            if (cfr('ROOT')) {
                $columns = array_merge(array('ID'), $columns);
                $opts = '"order": [[ 2, "asc" ]]';
            }

            //optional export options
            if (ubRouting::checkGet(self::ROUTE_EXPORTS)) {
                $opts .= ', "dom": \'<"F"lfB>rti<"F"ps>\',  buttons: [\'csv\', \'excel\', \'pdf\']';
            }


            $filters = '';

            if (ubRouting::checkPost(self::PROUTE_FILTERS)) {
                //filters form catched?
                if (ubRouting::checkPost(self::PROUTE_FILTERCITY)) {
                    $filters .= '&' . self::PROUTE_FILTERCITY . '=' . ubRouting::post(self::PROUTE_FILTERCITY);
                }

                if (ubRouting::checkPost(self::PROUTE_FILTERSTREET)) {
                    $filters .= '&' . self::PROUTE_FILTERSTREET . '=' . ubRouting::post(self::PROUTE_FILTERSTREET);
                }
            }

            $ajaxSource = self::URL_ME . '&' . self::ROUTE_AJLIST . '=true' . $filters;
            $result .= wf_JqDtLoader($columns, $ajaxSource, false, __('Builds'), 100, $opts);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Renders filters form
     * 
     * @return string
     */
    public function renderFiltersForm() {
        $result = '';
        if (!empty($this->allCities) AND ! empty($this->allStreets)) {
            $cityArr = array('' => __('Any'));
            $cityArr += $this->allCities;
            $streetArr = array('' => __('Any'));
            if (ubRouting::checkPost(self::PROUTE_FILTERCITY)) {
                //filter streets by some selected city
                $filterCityId = ubRouting::post(self::PROUTE_FILTERCITY);
                foreach ($this->allStreets as $io => $each) {
                    if ($each['cityid'] == $filterCityId) {
                        $streetArr[$each['id']] = $each['streetname'];
                    }
                }
            } else {
                //full streets list
                $streetArr += $this->allStreetNames;
            }


            $inputs = wf_HiddenInput(self::PROUTE_FILTERS, 'true');
            $inputs .= wf_SelectorAC(self::PROUTE_FILTERCITY, $cityArr, __('City'), ubRouting::post(self::PROUTE_FILTERCITY), false) . ' ';
            $inputs .= wf_SelectorAC(self::PROUTE_FILTERSTREET, $streetArr, __('Street'), ubRouting::post(self::PROUTE_FILTERSTREET), false) . ' ';
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Renders json build data array
     * 
     * @return void
     */
    public function renderAjBuildList() {
        $json = new wf_JqDtHelper();

        $cityFilter = ubRouting::get(self::PROUTE_FILTERCITY, 'int');
        $streetFilter = ubRouting::get(self::PROUTE_FILTERSTREET, 'int');
        $backUrl = '&back=' . base64_encode('report_builds');
        $passportUrl = BuildPassport::URL_PASSPORT . $backUrl . '&' . BuildPassport::ROUTE_BUILD . '=';
        if ($this->adCommentsFlag) {
            $adComments = new ADcomments('BUILDS');
        }

        $actBoxStyle = wf_tag('div', false, '', 'style="width:60px;"');
        $actBoxStyleEnd = wf_tag('div', true);

        if (!empty($this->allBuilds)) {
            foreach ($this->allBuilds as $io => $each) {
                $filtersPassed = true;
                $buildId = $each['id'];
                $buildCity = $this->getCityOfBuild($buildId);
                $buildStreet = $this->getStreetOfBuild($buildId);

                $cityName = (isset($this->allCities[$buildCity])) ? $this->allCities[$buildCity] : __('Missed');
                $streetName = (isset($this->allStreets[$buildStreet])) ? $this->allStreets[$buildStreet]['streetname'] : __('Missed');
                $userCount = $this->getAptCount($buildId);
                //some optional filtering here
                if ($cityFilter) {
                    if ($buildCity != $cityFilter) {
                        $filtersPassed = false;
                    }
                }

                if ($streetFilter) {
                    if ($buildStreet != $streetFilter) {
                        $filtersPassed = false;
                    }
                }

                if ($filtersPassed) {
                    if (cfr('ROOT')) {
                        $data[] = $each['id'];
                    }
                    $data[] = $cityName;
                    $data[] = $streetName;
                    $data[] = $each['buildnum'];

                    if ($this->buildPassportsFlag) {
                        $buildPassport = $this->buildPassports->getPassportData($buildId);
                        if (!empty($buildPassport)) {
                            //some passport data available
                            $ownerLabel = $buildPassport['owner'] . ' ' . $buildPassport['ownername'] . ' ' . $buildPassport['ownercontact'];
                            $ownerPhone = $buildPassport['ownerphone'];
                            $floors = $buildPassport['floors'];
                            $type = ($buildPassport['anthill']) ? wf_img('skins/ymaps/build.png', __('Apartment house')) : wf_img('skins/ymaps/home.png');
                            $entrances = $buildPassport['entrances'];
                            $apts = $buildPassport['apts'];
                            $accessNotices = $buildPassport['accessnotices'];
                        } else {
                            $ownerLabel = '';
                            $ownerPhone = '';
                            $type = wf_img('skins/ymaps/home.png');
                            $floors = '';
                            $entrances = '';
                            $apts = '';
                            $accessNotices = '';
                        }

                        $data[] = $ownerLabel;
                        $data[] = $ownerPhone;
                        $data[] = $type;
                        $data[] = $floors;
                        $data[] = $entrances;
                        $data[] = $apts;
                    }

                    $data[] = $userCount;

                    if ($this->buildPassportsFlag) {
                        $signupsPercent = '';
                        if (($apts > 0)) {
                            $signupsPercent = zb_PercentValue($apts, $userCount);
                        }

                        $data[] = $signupsPercent;
                        $data[] = $accessNotices;
                    }

                    $actionLinks = '';
                    if ($this->buildPassportsFlag) {
                        if ($this->adCommentsFlag) {
                            $actionLinks .= $adComments->getCommentsIndicator($each['id']) . ' ';
                        }
                        $actionLinks .= wf_Link($passportUrl . $each['id'], wf_img('skins/icon_passport.gif', __('Build passport'))) . ' ';
                    }

                    if (!empty($each['geo'])) {
                        $actionLinks .= wf_Link("?module=usersmap&findbuild=" . $each['geo'], wf_img('skins/icon_search_small.gif', __('Find on map')), false) . ' ';
                    } else {
                        if (cfr('BUILDS')) {
                            $actionLinks .= wf_Link('?module=usersmap&locfinder=true&placebld=' . $each['id'], wf_img('skins/ymaps/target.png', __('Place on map')), false, '') . ' ';
                        }
                    }


                    $data[] = $actBoxStyle . $actionLinks . $actBoxStyleEnd;
                    $json->addRow($data);
                    unset($data);
                }
            }
        }
        $json->getJson();
    }

}
