<?php

/**
 * Tasks manager map-view rendering class
 */
class TasksMap {

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
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

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
     * Contains selected jobtype to show or empty for all jobs
     *
     * @var int/void
     */
    protected $showJobType = '';

    /**
     * Contains default tasks data source table
     *
     * @var string
     */
    protected $dataTable = 'taskman';

    /**
     * Contains count of users without build geo assigned
     *
     * @var int
     */
    protected $noGeoBuilds = 0;

    /**
     * Contains count of users whitch is not present currently in database or tasks without login
     *
     * @var int
     */
    protected $deletedUsers = 0;

    /**
     * Contains count of tasks by period
     *
     * @var int
     */
    protected $tasksExtracted = 0;

    /**
     * Contains available jobtypes
     *
     * @var array
     */
    protected $allJobTypes = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Tasks source database abstraction layer pleceholder
     *
     * @var object
     */
    protected $tasksDb = '';

    /**
     * Creates new report instance
     */
    public function __construct() {
        $this->setFiltersData();
        $this->initMessages();
        $this->initDatasource();
        $this->loadConfigs();
        $this->loadUsers();
        $this->loadJobTypes();
    }

    /**
     * Loads system maps and alter configuration files
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->mapsCfg = $ubillingConfig->getYmaps();
        $this->altCfg = $ubillingConfig->getAlter();
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
     * Loads all available jobtypes into protected property
     * 
     * @return void
     */
    protected function loadJobTypes() {
        $this->allJobTypes = ts_GetAllJobtypes();
    }

    /**
     * Inits data source database abstraction layer
     * 
     * @return void
     */
    protected function initDatasource() {
        $this->tasksDb = new NyanORM($this->dataTable);
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
    protected function setFiltersData() {
        if (ubRouting::checkPost('showyear')) {
            $this->showYear = ubRouting::post('showyear', 'int');
        } else {
            $this->showYear = curyear();
        }

        if (ubRouting::checkPost('showmonth')) {
            $this->showMonth = ubRouting::post('showmonth', 'int');
        } else {
            $this->showMonth = date('m');
        }

        if (ubRouting::checkPost('showjobtype')) {
            $this->showJobType = ubRouting::post('showjobtype', 'int');
        }
    }

    /**
     * Returns array of tasks filtered by year/month
     * 
     * @return array
     */
    protected function getPlannedTasks() {
        $monthFilter = ($this->showMonth != '1488') ? $this->showMonth : '';
        $this->tasksDb->where('startdate', 'LIKE', $this->showYear . "-" . $monthFilter . "%");
        if ($this->showJobType) {
            $this->tasksDb->where('jobtype', '=', $this->showJobType);
        }
        $result = $this->tasksDb->getAll();

        return ($result);
    }

    /**
     * Returns array of tasks planned for current day
     * 
     * @param int $employeeId optional employee ID to get tasks for
     * 
     * @return array
     */
    public function getTodayTasks($employeeId = '') {
        $employeeId = ubRouting::filters($employeeId, 'int');
        $this->tasksDb->where('startdate', 'LIKE', curdate() . '%');
        if ($employeeId) {
            $this->tasksDb->where('employee', '=', $employeeId);
        }

        $result = $this->tasksDb->getAll();
        return($result);
    }

    /**
     * Returns list of formatted placemarks for map rendering
     * 
     * @param array $userTasks
     * 
     * @return string
     */
    public function getPlacemarks($userTasks) {
        $result = '';
        $buildsData = array();
        $buildsCounters = array();
        if (!empty($userTasks)) {
            foreach ($userTasks as $io => $each) {
                if (isset($this->allUserData[$each['login']])) {
                    $userData = $this->allUserData[$each['login']];
                    if (!empty($userData['geo'])) {
                        $taskDate = $each['startdate'];
                        $userLink = '';
                        $userLink .= $taskDate . ': ' . wf_Link('?module=taskman&edittask=' . $each['id'], web_bool_led($each['status']) . ' ' . @$this->allJobTypes[$each['jobtype']]);
                        $userLink = trim($userLink) . ', ';
                        $userLink .= wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $userData['fulladress']);
                        $userLink = trim($userLink);
                        if (!isset($buildsData[$userData['geo']])) {
                            $buildsData[$userData['geo']]['data'] = $userLink;
                            $buildsData[$userData['geo']]['count'] = 1;
                        } else {
                            $buildsData[$userData['geo']]['data'] .= trim(wf_tag('br')) . $userLink;
                            $buildsData[$userData['geo']]['count'] ++;
                        }
                    } else {
                        $this->noGeoBuilds++;
                    }
                } else {
                    $this->deletedUsers++;
                }
                $this->tasksExtracted++;
            }

            if (!empty($buildsData)) {
                foreach ($buildsData as $coords => $usersInside) {
                    if ($usersInside['count'] > 1) {
                        if ($usersInside['count'] > 3) {
                            $placeMarkIcon = 'twirl#redIcon';
                        } else {
                            $placeMarkIcon = 'twirl#yellowIcon';
                        }
                    } else {
                        $placeMarkIcon = 'twirl#lightblueIcon';
                    }
                    $result .= generic_mapAddMark($coords, $usersInside['data'], __('Tasks') . ': ' . $usersInside['count'], '', $placeMarkIcon, '', $this->mapsCfg['CANVAS_RENDER']);
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
    public function renderFiltersForm() {
        $result = '';
        $inputs = wf_YearSelectorPreset('showyear', __('Year'), false, $this->showYear) . ' ';
        $inputs .= wf_MonthSelector('showmonth', __('Month'), $this->showMonth, false, true) . ' ';
        $jobTypes = array('' => __('Any'));
        $jobTypes += $this->allJobTypes;
        $inputs .= wf_Selector('showjobtype', $jobTypes, __('Job type'), $this->showJobType, false) . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Renders report as map
     * 
     * @return string
     */
    public function renderMap() {
        $result = '';
        $allTasks = $this->getPlannedTasks();
        $placemarks = $this->getPlacemarks($allTasks);
        $result .= generic_MapContainer();
        $result .= generic_MapInit($this->mapsCfg['CENTER'], $this->mapsCfg['ZOOM'], $this->mapsCfg['TYPE'], $placemarks, '', $this->mapsCfg['LANG'], 'ubmap');
        return ($result);
    }

    /**
     * Renders deleted users or unknown geo builds stats if they available
     * 
     * @return string
     */
    public function renderStats() {
        $result = '';
        if ($this->tasksExtracted) {
            $result .= $this->messages->getStyledMessage(__('Total tasks') . ': ' . $this->tasksExtracted, 'success');
        }
        if ($this->tasksExtracted AND $this->noGeoBuilds) {
            $result .= $this->messages->getStyledMessage(__('Tasks rendered on map') . ': ' . ($this->tasksExtracted - $this->noGeoBuilds - $this->deletedUsers), 'info');
        }
        if ($this->noGeoBuilds) {
            $result .= $this->messages->getStyledMessage(__('Builds without geo location assigned') . ': ' . $this->noGeoBuilds, 'warning');
        }
        if ($this->deletedUsers) {
            $result .= $this->messages->getStyledMessage(__('Already deleted users or tasks without user') . ': ' . $this->deletedUsers, 'error');
        }

        return ($result);
    }

}
