<?php

class UbillingBranches {

    /**
     * Contains current user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Contains available branches as branchid=>admin login
     *
     * @var array
     */
    protected $myBranches = array();

    /**
     * Contains available branches as id=>branch data
     *
     * @var array
     */
    protected $branches = array();

    /**
     * Contains login assins for branches as login=>branchid
     *
     * @var array
     */
    protected $branchesLogins = array();

    /**
     * Users logins allowed for current administrator as login=>branchid
     *
     * @var array
     */
    protected $myUsers = array();

    /**
     * Contains branches admins as id=>data
     *
     * @var array
     */
    protected $branchesAdmins = array();

    /**
     * Contains array of id=>assigndata assigns for cities
     *
     * @var array
     */
    protected $branchesCities = array();

    /**
     * Contains available cities names as cityid=>cityname
     *
     * @var array
     */
    protected $allCityNames = array();

    /**
     * Contains array of accessible cities for current administrator as cityid=>cityname
     *
     * @var array
     */
    protected $myCities = array();

    /**
     * Contains array of id=>assigndata for tariffs
     *
     * @var array
     */
    protected $branchesTariffs = array();

    /**
     * Contains all available tariffs as tariffname=>fee
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains array of accessible tariffs for current administrator as tariffname=>tariffname
     * 
     * @var array
     */
    protected $myTariffs = array();

    /**
     * Contains array of id=>assingdata for services
     *
     * @var array
     */
    protected $branchesServices = array();

    /**
     * Contains array of available services as id=>name
     *
     * @var array
     */
    protected $allServices = array();

    /**
     * Contains array of accessible services for current administrator as serviceid=>servicename
     *
     * @var array
     */
    protected $myServices = array();

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system mussages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Branches enabled flag
     *
     * @var bool
     */
    protected $branchesEnabled = false;

    const URL_ME = '?module=branches';
    const URL_USERPROFILE = '?module=userprofile&username=';
    const URL_TRAFFSTATS = '?module=traffstats&username=';
    const EX_NO_BRANCH = 'EX_BRANCHID_NOT_EXISTS';
    const EX_NO_NAME = 'EX_EMPTY_BRANCH_NAME';
    const EX_NO_USER = 'EX_EMPTY_LOGIN';
    const EX_NO_CITY = 'EX_EMPTY_CITY';
    const EX_NO_TARIFF = 'EX_EMPTY_TARIFF';
    const EX_NO_SERVICE = 'EX_EMPTY_SERVICE';
    const EX_NO_ADMIN = 'EX_EMPTY_ADMIN';

    public function __construct() {
        $this->loadAlter();
        if ($this->altCfg['BRANCHES_ENABLED']) {
            $this->branchesEnabled = true;
            $this->setLogin();
            $this->initMessages();
            $this->loadBranches();
            $this->loadBranchesAdmins();
            $this->setMyBranches();
            $this->loadBranchesUsers();
        } else {
            $this->branchesEnabled = false;
        }
    }

    /**
     * Sets current user login
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Loads system alter config into protected property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits system messages helper object for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads available branches from database
     * 
     * @return void
     */
    protected function loadBranches() {
        $query = "SELECT * from `branches` ORDER BY `id` DESC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->branches[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available branches admins from database
     * 
     * @return void
     */
    protected function loadBranchesAdmins() {
        if (!empty($this->branches)) {
            $query = "SELECT * from `branchesadmins` ORDER BY `id` DESC";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->branchesAdmins[$each['id']] = $each;
                }
            }
        }
    }

    /**
     * Loads cities assigns from database into protected prop. Must be executed first before isMyCity() usage.
     * 
     * @return void
     */
    public function loadCities() {
        $this->allCityNames = zb_AddressGetFullCityNames();
        $query = "SELECT * from `branchescities`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->branchesCities[$each['id']]['branchid'] = $each['branchid'];
                $this->branchesCities[$each['id']]['cityid'] = $each['cityid'];

                if (isset($this->myBranches[$each['branchid']])) {
                    $this->myCities[$each['cityid']] = $this->allCityNames[$each['cityid']];
                }
            }
        }
    }

    /**
     * Loads cities assigns from database into protected prop
     * 
     * @return void
     */
    public function loadTariffs() {
        $this->allTariffs = zb_TariffGetPricesAll();

        $query = "SELECT * from `branchestariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->branchesTariffs[$each['id']]['branchid'] = $each['branchid'];
                $this->branchesTariffs[$each['id']]['tariff'] = $each['tariff'];

                if (isset($this->myBranches[$each['branchid']])) {
                    $this->myTariffs[$each['tariff']] = $each['tariff'];
                }
            }
        }
    }

    /**
     * Loads services assings from database into protected properties
     * 
     * @return void
     */
    public function loadServices() {
        $servicesTmp = multinet_get_services();
        if (!empty($servicesTmp)) {
            foreach ($servicesTmp as $io => $each) {
                $this->allServices[$each['id']] = $each['desc'];
            }
        }

        $query = "SELECT * from `branchesservices`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->branchesServices[$each['id']]['branchid'] = $each['branchid'];
                $this->branchesServices[$each['id']]['serviceid'] = $each['serviceid'];

                if (isset($this->myBranches[$each['branchid']])) {
                    $this->myServices[$each['serviceid']] = $this->allServices[$each['serviceid']];
                }
            }
        }
    }

    /**
     * Gets current administrator branches IDs and sets it intoprotected prop
     * 
     * @return void
     */
    protected function setMyBranches() {
        if (!empty($this->branchesAdmins)) {
            foreach ($this->branchesAdmins as $io => $each) {
                if ($each['admin'] == $this->myLogin) {
                    $this->myBranches[$each['branchid']] = $this->myLogin;
                }
            }
        }
    }

    /**
     * Loads available user-branch pairs from database
     * 
     * @return void
     */
    protected function loadBranchesUsers() {
        $query = "SELECT * from `branchesusers`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->branchesLogins[$each['login']] = $each['branchid'];
                if (isset($this->myBranches[$each['branchid']])) {
                    $this->myUsers[$each['login']] = $each['branchid'];
                }
            }
        }
    }

    /**
     * Creates new branch
     * 
     * @param string $name
     * 
     * @return int
     */
    public function createBranch($name) {
        $nameF = mysql_real_escape_string($name);

        if (!empty($nameF)) {
            $query = "INSERT INTO `branches` (`id`,`name`) VALUES ";
            $query.="(NULL,'" . $nameF . "');";
            nr_query($query);
            $newId = simple_get_lastid('branches');
            log_register('BRANCH CREATE [' . $newId . '] `' . $name . '`');
            return ($newId);
        } else {
            throw new Exception(self::EX_NO_NAME);
        }
    }

    /**
     * Checks is branch have assigned users
     * 
     * @param int $branchId
     * 
     * @return bool
     */
    public function isBranchProtected($branchId) {
        $branchId = vf($branchId, 3);
        $result = false;
        if (isset($this->branches[$branchId])) {
            if (!empty($this->branchesLogins)) {
                foreach ($this->branchesLogins as $eachLogin => $eachId) {
                    if ($branchId == $eachId) {
                        $result = true;
                        break;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes branch by its ID
     * 
     * @param int $branchId
     * 
     * @return void
     */
    public function deleteBranch($branchId) {
        $branchId = vf($branchId, 3);
        if (isset($this->branches[$branchId])) {
            $query = "DELETE from `branches` WHERE `id`='" . $branchId . "';";
            nr_query($query);
            //admins cleanup
            $queryAdmins = "DELETE from `branchesadmins` WHERE `branchid`='" . $branchId . "';";
            nr_query($queryAdmins);
            log_register('BRANCH DELETE [' . $branchId . ']');
        }
    }

    /**
     * Updates existing branch name
     * 
     * @param int $branchId
     * @param string $branchName
     * 
     * @return void
     */
    public function editBranch($branchId, $branchName) {
        $branchId = vf($branchId, 3);
        if (isset($this->branches[$branchId])) {
            simple_update_field('branches', 'name', $branchName, "WHERE `id`='" . $branchId . "'");
            log_register('BRANCH UPDATE [' . $branchId . '] `' . $branchName . '`');
        }
    }

    /**
     * Checks is admin assigned to some branch, to prevent duplicates
     * 
     * @param int $branchId
     * @param string $adminLogin
     * 
     * @return bool
     */
    protected function isAdminBranchAssigned($branchId, $adminLogin) {
        $result = false;
        if (!empty($this->branchesAdmins)) {
            foreach ($this->branchesAdmins as $io => $each) {
                if (($each['branchid'] == $branchId) AND ( $each['admin'] == $adminLogin)) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Assigns administrator with some existing branch
     * 
     * @param int $branchId
     * @param string $admin
     * @throws Exception
     * 
     * @return void
     */
    public function adminAssignBranch($branchId, $admin) {
        $branchId = vf($branchId, 3);
        $admin = trim($admin);
        $adminF = mysql_real_escape_string($admin);
        if (isset($this->branches[$branchId])) {
            if (!empty($adminF)) {
                if (!$this->isAdminBranchAssigned($branchId, $admin)) {
                    $query = "INSERT INTO `branchesadmins` (`id`,`branchid`,`admin`) VALUES";
                    $query.="(NULL,'" . $branchId . "','" . $adminF . "');";
                    nr_query($query);
                    log_register('BRANCH ASSIGN [' . $branchId . '] ADMIN {' . $admin . '}');
                }
            } else {
                throw new Exception(self::EX_NO_ADMIN);
            }
        } else {
            throw new Exception(self::EX_NO_BRANCH);
        }
    }

    /**
     * Deassigns administrator with some existing branch
     * 
     * @param int $branchId
     * @param string $admin
     * @throws Exception
     * 
     * @return void
     */
    public function adminDeassignBranch($branchId, $admin) {
        $branchId = vf($branchId, 3);
        $admin = trim($admin);
        $adminF = mysql_real_escape_string($admin);
        if (isset($this->branches[$branchId])) {
            if (!empty($adminF)) {
                $query = "DELETE from `branchesadmins` WHERE `branchid`='" . $branchId . "' AND `admin`='" . $adminF . "';";
                nr_query($query);
                log_register('BRANCH DEASSIGN [' . $branchId . '] ADMIN {' . $admin . '}');
            } else {
                throw new Exception(self::EX_NO_ADMIN);
            }
        } else {
            throw new Exception(self::EX_NO_BRANCH);
        }
    }

    /**
     * Assigns user login with existing branch ID
     * 
     * @param type $branchId
     * @param type $login
     * @throws Exception
     * 
     * @return void
     */
    public function userAssignBranch($branchId, $login) {
        $branchId = vf($branchId, 3);
        $login = trim($login);
        $loginF = mysql_real_escape_string($login);
        if (isset($this->branches[$branchId])) {
            if (!empty($loginF)) {
                $query = "INSERT INTO `branchesusers` (`id`,`branchid`,`login`) VALUES ";
                $query.="(NULL,'" . $branchId . "','" . $loginF . "');";
                nr_query($query);
                log_register('BRANCH ASSIGN [' . $branchId . '] USER (' . $login . ')');
            } else {
                throw new Exception(self::EX_NO_USER);
            }
        } else {
            throw new Exception(self::EX_NO_BRANCH);
        }
    }

    /**
     * Deletes user assigned branch
     * 
     * @param string $login
     * 
     * @return void
     */
    public function userDeleteBranch($login) {
        $login = trim($login);
        $loginF = mysql_real_escape_string($login);
        $currentBranch = @$this->branchesLogins[$login];
        if (!empty($currentBranch)) {
            $query = "DELETE from `branchesusers` WHERE `login`='" . $loginF . "';";
            nr_query($query);
            log_register('BRANCH UNASSIGN [' . $currentBranch . '] USER (' . $login . ')');
        }
    }

    /**
     * Checks is user accessible by current administrator
     * 
     * @param string $login
     * 
     * @return bool
     */
    public function isMyUser($login) {
        $result = false;
        if ($this->branchesEnabled) {
            if (cfr('ROOT')) {
                $result = true;
            } else {
                if (cfr('BRANCHES')) {
                    if (isset($this->myUsers[$login])) {
                        $result = true;
                    } else {
                        $result = false;
                    }
                } else {
                    $result = true;
                }
            }
        } else {
            $result = true;
        }
        return ($result);
    }

    /**
     * Checks is city accessible by current administrator
     * 
     * @param int $cityId
     * 
     * @return bool
     */
    public function isMyCity($cityId) {
        $result = false;
        if ($this->branchesEnabled) {
            if (cfr('ROOT')) {
                $result = true;
            } else {
                if (cfr('BRANCHES')) {
                    if (isset($this->myCities[$cityId])) {
                        $result = true;
                    } else {
                        $result = false;
                    }
                } else {
                    $result = true;
                }
            }
        } else {
            $result = true;
        }
        return ($result);
    }

    /**
     * Checks is tariff accessible by current administrator
     * 
     * @param string $tariffName
     * 
     * @return bool
     */
    public function isMyTariff($tariffName) {
        $result = false;
        if ($this->branchesEnabled) {
            if (cfr('ROOT')) {
                $result = true;
            } else {
                if (cfr('BRANCHES')) {
                    if (isset($this->myTariffs[$tariffName])) {
                        $result = true;
                    } else {
                        $result = false;
                    }
                } else {
                    $result = true;
                }
            }
        } else {
            $result = true;
        }
        return ($result);
    }

    /**
     * Checks is service accessible by current administrator
     * 
     * @param int $serviceId
     * 
     * @return bool
     */
    public function isMyService($serviceId) {
        $result = false;
        if ($this->branchesEnabled) {
            if (cfr('ROOT')) {
                $result = true;
            } else {
                if (cfr('BRANCHES')) {
                    if (isset($this->myServices[$serviceId])) {
                        $result = true;
                    } else {
                        $result = false;
                    }
                } else {
                    $result = true;
                }
            }
        } else {
            $result = true;
        }
        return ($result);
    }

    /**
     * Checks is branch accessible by current administrator
     * 
     * @param int $branchId
     * 
     * @return bool
     */
    public function isMyBranch($branchId) {
        $result = false;
        if ($this->branchesEnabled) {
            if (cfr('ROOT')) {
                $result = true;
            } else {
                if (cfr('BRANCHES')) {
                    if (isset($this->myBranches[$branchId])) {
                        $result = true;
                    } else {
                        $result = false;
                    }
                } else {
                    $result = true;
                }
            }
        } else {
            $result = true;
        }
        return ($result);
    }

    /**
     * Returns user assigned branch
     * 
     * @param string $login
     * 
     * @return int
     */
    public function userGetBranch($login) {
        $result = '';
        if (isset($this->branchesLogins[$login])) {
            $result = $this->branchesLogins[$login];
        }
        return ($result);
    }

    /**
     * Returns user branch name by his login
     * 
     * @param string $login
     * 
     * @return string
     */
    public function userGetBranchName($login) {
        $result = '';
        $branchId = $this->userGetBranch($login);
        if (!empty($branchId)) {
            $result = $this->getBranchName($branchId);
        }
        return ($result);
    }

    /**
     * Perfoms city to branch assign
     * 
     * @param int $branchId
     * @param int $cityId
     * 
     * @return void
     */
    public function cityAssignBranch($branchId, $cityId) {
        $branchId = vf($branchId, 3);
        $cityId = vf($cityId, 3);
        if (isset($this->branches[$branchId])) {
            if (!empty($cityId)) {
                $query = "INSERT INTO `branchescities` (`id`,`branchid`,`cityid`) VALUES ";
                $query.="(NULL,'" . $branchId . "','" . $cityId . "');";
                nr_query($query);
                log_register('BRANCH ASSIGN [' . $branchId . '] CITY [' . $cityId . ']');
            } else {
                throw new Exception(self::EX_NO_CITY);
            }
        } else {
            throw new Exception(self::EX_NO_BRANCH);
        }
    }

    /**
     * Performs deletion of city assignation to some branch
     * 
     * @param int $branchId
     * @param int $cityId
     * @throws Exception
     * 
     * @return void
     */
    public function cityDeassignBranch($branchId, $cityId) {
        $branchId = vf($branchId, 3);
        $cityId = vf($cityId, 3);
        if (isset($this->branches[$branchId])) {
            if (!empty($cityId)) {
                $query = "DELETE from `branchescities` WHERE `branchid`='" . $branchId . "' AND `cityid`='" . $cityId . "';";
                nr_query($query);
                log_register('BRANCH DEASSIGN [' . $branchId . '] CITY [' . $cityId . ']');
            } else {
                throw new Exception(self::EX_NO_CITY);
            }
        } else {
            throw new Exception(self::EX_NO_BRANCH);
        }
    }

    /**
     * Perfoms tariff to branch assign
     * 
     * @param int $branchId
     * @param string $tariff
     * 
     * @return void
     */
    public function tariffAssignBranch($branchId, $tariff) {
        $branchId = vf($branchId, 3);
        $tariffF = mysql_real_escape_string($tariff);
        if (isset($this->branches[$branchId])) {
            if (!empty($tariff)) {
                $query = "INSERT INTO `branchestariffs` (`id`,`branchid`,`tariff`) VALUES ";
                $query.="(NULL,'" . $branchId . "','" . $tariffF . "');";
                nr_query($query);
                log_register('BRANCH ASSIGN [' . $branchId . '] TARIFF `' . $tariff . '`');
            } else {
                throw new Exception(self::EX_NO_TARIFF);
            }
        } else {
            throw new Exception(self::EX_NO_BRANCH);
        }
    }

    /**
     * Performs deletion of tariff assignation to some branch
     * 
     * @param int $branchId
     * @param string $tariff
     * @throws Exception
     * 
     * @return void
     */
    public function tariffDeassignBranch($branchId, $tariff) {
        $branchId = vf($branchId, 3);
        $tariffF = mysql_real_escape_string($tariff);
        if (isset($this->branches[$branchId])) {
            if (!empty($tariff)) {
                $query = "DELETE from `branchestariffs` WHERE `branchid`='" . $branchId . "' AND `tariff`='" . $tariffF . "';";
                nr_query($query);
                log_register('BRANCH DEASSIGN [' . $branchId . '] TARIFF `' . $tariff . '`');
            } else {
                throw new Exception(self::EX_NO_TARIFF);
            }
        } else {
            throw new Exception(self::EX_NO_BRANCH);
        }
    }

    /**
     * Perfoms service to branch assign
     * 
     * @param int $branchId
     * @param int $serviceId
     * 
     * @return void
     */
    public function serviceAssignBranch($branchId, $serviceId) {
        $branchId = vf($branchId, 3);
        $serviceId = vf($serviceId, 3);
        if (isset($this->branches[$branchId])) {
            if (!empty($serviceId)) {
                $query = "INSERT INTO `branchesservices` (`id`,`branchid`,`serviceid`) VALUES ";
                $query.="(NULL,'" . $branchId . "','" . $serviceId . "');";
                nr_query($query);
                log_register('BRANCH ASSIGN [' . $branchId . '] SERVICE [' . $serviceId . ']');
            } else {
                throw new Exception(self::EX_NO_SERVICE);
            }
        } else {
            throw new Exception(self::EX_NO_BRANCH);
        }
    }

    /**
     * Performs deletion of service assignation to some branch
     * 
     * @param int $branchId
     * @param int $serviceId
     * @throws Exception
     * 
     * @return void
     */
    public function serviceDeassignBranch($branchId, $serviceId) {
        $branchId = vf($branchId, 3);
        $tariff = vf($serviceId, 3);
        if (isset($this->branches[$branchId])) {
            if (!empty($serviceId)) {
                $query = "DELETE from `branchesservices` WHERE `branchid`='" . $branchId . "' AND `serviceid`='" . $serviceId . "';";
                nr_query($query);
                log_register('BRANCH DEASSIGN [' . $branchId . '] SERVICE [' . $serviceId . ']');
            } else {
                throw new Exception(self::EX_NO_SERVICE);
            }
        } else {
            throw new Exception(self::EX_NO_BRANCH);
        }
    }

    /**
     * Renders branches module control panel interface
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        //hide control panel on user branch editing
        if (!wf_CheckGet(array('userbranch'))) {
            if (cfr('BRANCHES')) {
                $result.=wf_Link(self::URL_ME . '&userlist=true', wf_img('skins/ukv/users.png') . ' ' . __('Users'), false, 'ubButton') . ' ';
                if (cfr('BRANCHESREG')) {
                    $result.=wf_Link('?module=userreg&branchesback=true', wf_img('skins/ukv/add.png') . ' ' . __('Users registration'), false, 'ubButton') . ' ';
                }
                if (cfr('BRANCHESFINREP')) {
                    $result.=wf_Link(self::URL_ME . '&finreport=true', wf_img('skins/icon_dollar.gif') . ' ' . __('Finance report'), false, 'ubButton') . ' ';
                }
                if (cfr('BRANCHESSIGREP')) {
                    $result.=wf_Link(self::URL_ME . '&sigreport=true', wf_img('skins/ukv/report.png') . ' ' . __('Signup report'), false, 'ubButton') . ' ';
                }
            }
            if (cfr('BRANCHESCONF')) {
                $result.=wf_Link(self::URL_ME . '&settings=true', wf_img('skins/icon_extended.png') . ' ' . __('Settings'), false, 'ubButton') . ' ';
            }
        }
        return ($result);
    }

    /**
     * Renders user list container
     * 
     * @return string
     */
    public function renderUserList() {
        $result = '';
        if ($this->altCfg['DN_ONLINE_DETECT']) {
            $columns = array('Full address', 'Real Name', 'Branch', 'IP', 'Tariff', 'Active', 'Online', 'Traffic', 'Balance', 'Credit');
        } else {
            $columns = array('Full address', 'Real Name', 'Branch', 'IP', 'Tariff', 'Active', 'Traffic', 'Balance', 'Credit');
        }
        $result = wf_JqDtLoader($columns, self::URL_ME . '&userlist=true&ajaxuserlist=true', false, __('Users'), 50, '');
        return ($result);
    }

    /**
     * Returns branch name by its ID
     * 
     * @param int $branchId
     * 
     * @return string
     */
    public function getBranchName($branchId) {
        $result = '';
        if (isset($this->branches[$branchId])) {
            $result = $this->branches[$branchId]['name'];
        }
        return ($result);
    }

    /**
     * Builds and renders users list JSON data
     * 
     * @return void
     */
    public function renderUserListJson() {
        $json = new wf_JqDtHelper();
        if (!empty($this->branchesLogins)) {
            $allAddress = zb_AddressGetFulladdresslistCached();
            $allRealNames = zb_UserGetAllRealnames();
            $allUserData = zb_UserGetAllStargazerDataAssoc();
            $dnFlag = $this->altCfg['DN_ONLINE_DETECT'] ? true : false;
            foreach ($this->branchesLogins as $login => $branchId) {
                if ($this->isMyUser($login)) {
                    if (isset($allUserData[$login])) {
                        $userLinks = wf_Link(self::URL_TRAFFSTATS . $login, web_stats_icon()) . ' ';
                        $userLinks.=wf_Link(self::URL_USERPROFILE . $login, web_profile_icon());
                        @$userAddress = $allAddress[$login];
                        @$userRealName = $allRealNames[$login];
                        $activeFlag = ($allUserData[$login]['Cash'] >= -$allUserData[$login]['Credit']) ? web_bool_led(true) . ' ' . __('Yes') : web_bool_led(false) . ' ' . __('No');

                        $data[] = $userLinks . ' ' . $userAddress;
                        $data[] = $userRealName;
                        $data[] = $this->getBranchName($branchId);
                        $data[] = $allUserData[$login]['IP'];
                        $data[] = $allUserData[$login]['Tariff'];
                        $data[] = $activeFlag;
                        if ($dnFlag) {
                            $onlineFlag = (file_exists('content/dn/' . $login)) ? web_star() . ' ' . __('Yes') : web_star_black() . ' ' . __('No');
                            $data[] = $onlineFlag;
                        }
                        $data[] = zb_TraffToGb(($allUserData[$login]['D0'] + $allUserData[$login]['U0']));
                        $data[] = $allUserData[$login]['Cash'];
                        $data[] = $allUserData[$login]['Credit'];
                        $json->addRow($data);
                        unset($data);
                    }
                }
            }
        }
        $json->getJson();
    }

    /**
     * Renders finance report
     * 
     * @return string
     */
    public function renderFinanceReport() {
        $result = '';
        $whereFilter = '';
        $totalSumm = 0;
        $totalCount = 0;
        $monthArr = months_array_localized();
        $allAddress = zb_AddressGetFulladdresslistCached();
        $allRealNames = zb_UserGetAllRealnames();
        $paymentTypes = zb_CashGetAllCashTypes();
        $allservicenames = zb_VservicesGetAllNamesLabeled();

        $inputs = wf_YearSelector('yearsel', __('Year') . ' ', false);
        $inputs.= wf_Selector('monthsel', $monthArr, __('Month') . ' ', date("m"), false);
        $inputs.=wf_Submit(__('Payments by month'));
        $monthForm = wf_Form('', 'POST', $inputs, 'glamour');
        $monthForm.=wf_CleanDiv();


        $inputsDate = wf_DatePickerPreset('datesel', curdate());
        $inputsDate.= wf_Submit(__('Payments by date'));
        $dateForm = wf_Form('', 'POST', $inputsDate, 'glamour');
        $dateForm.= wf_CleanDiv();

        $controlCells = wf_TableCell($monthForm);
        $controlCells.= wf_TableCell($dateForm);
        $controlRows = wf_TableRow($controlCells);
        $result.= wf_TableBody($controlRows, '60%', 0, '');

        $filterDate = (wf_CheckPost(array('yearsel'))) ? vf($_POST['yearsel'], 3) : curyear();
        if (wf_CheckPost(array('monthsel'))) {
            $filterDate.='-' . vf($_POST['monthsel'], 3);
        } else {
            $filterDate.='-' . date("m");
        }

        $whereFilter = "WHERE `date` LIKE '" . $filterDate . "-%' ";
        if (wf_CheckPost(array('datesel'))) {
            $filterDate = mysql_real_escape_string($_POST['datesel']);
            $whereFilter = "WHERE `date` LIKE '" . $filterDate . "%' ";
        }

        $query = "SELECT * from `payments` " . $whereFilter . " ORDER BY `id` DESC";
        $all = simple_queryall($query);

        if (!empty($all)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Cash'));
            $cells.= wf_TableCell(__('Login'));
            $cells.= wf_TableCell(__('Full address'));
            $cells.= wf_TableCell(__('Real Name'));
            $cells.= wf_TableCell(__('Branch'));
            $cells.= wf_TableCell(__('Cash type'));
            $cells.= wf_TableCell(__('Notes'));
            $cells.= wf_TableCell(__('Admin'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($all as $io => $each) {
                if (isset($this->branchesLogins[$each['login']])) {
                    if ($this->isMyUser($each['login'])) {
                        $cells = wf_TableCell($each['id']);
                        $cells.= wf_TableCell($each['date']);
                        $cells.= wf_TableCell($each['summ']);
                        $loginLink = wf_Link(self::URL_USERPROFILE . $each['login'], web_profile_icon() . ' ' . $each['login']);
                        $cells.= wf_TableCell($loginLink);
                        $cells.= wf_TableCell(@$allAddress[$each['login']]);
                        $cells.= wf_TableCell(@$allRealNames[$each['login']]);
                        $cells.= wf_TableCell(@$this->getBranchName($this->branchesLogins[$each['login']]));
                        $cells.= wf_TableCell(__($paymentTypes[$each['cashtypeid']]));
                        $cells.= wf_TableCell(zb_TranslatePaymentNote($each['note'], $allservicenames));
                        $cells.= wf_TableCell($each['admin']);
                        $rows.= wf_TableRow($cells, 'row3');
                        if ($each['summ'] > 0) {
                            $totalSumm+=$each['summ'];
                            $totalCount++;
                        }
                    }
                }
            }

            $result.=wf_tag('h3') . __('Payments by') . ' ' . $filterDate . wf_tag('h3', true);
            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            $result.=wf_tag('b');
            $result.=__('Total money') . ': ' . $totalSumm;
            $result.=wf_tag('br');
            $result.=__('Payments count') . ': ' . $totalCount;
            $result.= wf_tag('b', true);
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

    /**
     * Renders branch users signup report
     * 
     * @return string
     */
    public function renderSignupReport() {
        $result = '';
        $showYear = (wf_CheckPost(array('yearsel'))) ? vf($_POST['yearsel'], 3) : curyear();
        $query = "SELECT * from `userreg` WHERE `date` LIKE '" . $showYear . "-%' ORDER BY `id` DESC";
        $all = simple_queryall($query);
        $yearTmp = array();
        $yearCount = 0;
        $todayCount = 0;
        $monthNames = months_array_localized();
        $curDate = curdate();
        $curMonth = curmonth();
        $monthSignupsTmp = array();
        //preparing per year stats array
        foreach ($monthNames as $monthNum => $monthName) {
            $yearTmp[$monthNum] = 0;
        }

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                if (isset($this->branchesLogins[$each['login']])) {
                    if ($this->isMyUser($each['login'])) {
                        if (ispos($each['date'], $curDate)) {
                            $todayCount++;
                        }
                        if (ispos($each['date'], $curMonth)) {
                            $monthSignupsTmp[$each['id']] = $each;
                        }
                        $regTimestamp = strtotime($each['date']);
                        $regMonth = date("m", $regTimestamp);
                        $yearTmp[$regMonth] ++;
                        $yearCount++;
                    }
                }
            }

            $result.=$this->messages->getStyledMessage(__('Today signups') . ': ' . wf_tag('b') . $todayCount . wf_tag('b', true), 'info');
            $inputs = wf_YearSelector('yearsel', 'Year') . ' ';
            $inputs.= wf_Submit(__('Show'));
            $result.= wf_delimiter();
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
            $result.=wf_CleanDiv();

            $cells = wf_TableCell('');
            $cells.= wf_TableCell(__('Month'));
            $cells.= wf_TableCell(__('Signups'));
            $cells.= wf_TableCell(__('Visual'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($yearTmp as $eachMonth => $monthCount) {
                $cells = wf_TableCell($eachMonth);
                $cells.= wf_TableCell($monthNames[$eachMonth]);
                $cells.= wf_TableCell($monthCount);
                $cells.= wf_TableCell(web_bar($monthCount, $yearCount));
                $rows.= wf_TableRow($cells, 'row3');
            }

            $result.=wf_tag('br') . wf_tag('b') . __('User signups by year') . ' ' . $showYear . wf_tag('b', true) . wf_tag('br');
            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            $result.= wf_tag('b') . __('Total') . ': ' . $yearCount . wf_tag('b', true);

            if (!empty($monthSignupsTmp)) {
                $cells = wf_TableCell(__('ID'));
                $cells.= wf_TableCell(__('Date'));
                $cells.= wf_TableCell(__('Administrator'));
                $cells.= wf_TableCell(__('Login'));
                $cells.= wf_TableCell(__('Branch'));
                $cells.= wf_TableCell(__('Full address'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($monthSignupsTmp as $io => $each) {
                    $cells = wf_TableCell($each['id']);
                    $cells.= wf_TableCell($each['date']);
                    $cells.= wf_TableCell($each['admin']);
                    $cells.= wf_TableCell($each['login']);
                    $cells.= wf_TableCell(@$this->getBranchName($this->branchesLogins[$each['login']]));
                    $userLink = wf_Link(self::URL_USERPROFILE . $each['login'], web_profile_icon()) . ' ';
                    $cells.= wf_TableCell($userLink . $each['address']);
                    $rows.= wf_TableRow($cells, 'row3');
                }

                $result.=wf_tag('br') . wf_tag('b') . __('Current month user signups') . wf_tag('b', true) . wf_tag('br');
                $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }


        return ($result);
    }

    /**
     * Returns branch editing form
     * 
     * @param int $branchId
     * 
     * @return string
     */
    protected function renderBranchEditForm($branchId) {
        $branchId = vf($branchId, 3);
        $result = '';
        if (isset($this->branches[$branchId])) {
            $inputs = wf_HiddenInput('editbranch', 'true');
            $inputs.= wf_HiddenInput('editbranchid', $branchId);
            $inputs.= wf_TextInput('editbranchname', __('Name'), $this->branches[$branchId]['name'], true);
            $inputs.= wf_Submit(__('Save'));
            $result.= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result.= self::EX_NO_BRANCH;
        }
        return ($result);
    }

    /**
     * Renders list of available branches and reqired controls for its management
     * 
     * @return string
     */
    protected function renderBranchesConfigForm() {
        $result = '';
        if (!empty($this->branches)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Name'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->branches as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['name']);
                $actControls = wf_JSAlert(self::URL_ME . '&settings=true&deletebranch=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $actControls.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderBranchEditForm($each['id']), '') . ' ';

                $cells.= wf_TableCell($actControls);
                $rows.= wf_TableRow($cells, 'row3');
            }
            $result.= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.=$this->messages->getStyledMessage(__('No branches available'), 'warning');
        }

        $inputs = wf_HiddenInput('newbranch', 'true');
        $inputs.= wf_TextInput('newbranchname', __('New branch name'), '', false) . ' ';
        $inputs.= wf_Submit(__('Create'));
        $createForm = wf_Form('', 'POST', $inputs, 'glamour');

        $result.=$createForm;

        return ($result);
    }

    /**
     * Returns branches admins assignation list and config form
     * 
     * @return string
     */
    protected function renderAdminConfigForm() {
        $result = '';
        if (!empty($this->branches)) {
            if (!empty($this->branchesAdmins)) {
                $cells = wf_TableCell(__('ID'));
                $cells.= wf_TableCell(__('Branch'));
                $cells.= wf_TableCell(__('Admin'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($this->branchesAdmins as $io => $each) {
                    $cells = wf_TableCell($each['id']);
                    $cells.= wf_TableCell($this->getBranchName($each['branchid']));
                    $cells.= wf_TableCell($each['admin']);
                    $actControls = wf_JSAlert(self::URL_ME . '&settings=true&deleteadmin=' . $each['admin'] . '&adminbranchid=' . $each['branchid'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells.= wf_TableCell($actControls);
                    $rows.= wf_TableRow($cells, 'row3');
                }
                $result.= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result.=$this->messages->getStyledMessage(__('No branches admins available'), 'warning');
                $result.= wf_tag('br');
            }

            //admin assign form
            $allAdmins = rcms_scandir('content/users/');
            $adminsTmp = array();
            if (!empty($allAdmins)) {
                foreach ($allAdmins as $io => $each) {
                    $adminsTmp[$each] = $each;
                }
            }

            $branchesTmp = array();
            if (!empty($this->branches)) {
                foreach ($this->branches as $io => $each) {
                    $branchesTmp[$io] = $each['name'];
                }
            }

            $inputs = wf_Selector('newadminbranch', $branchesTmp, __('Branch'), '', false) . ' ';
            $inputs.=wf_Selector('newadminlogin', $adminsTmp, __('Admin'), '', false) . ' ';
            $inputs.=wf_Submit(__('Assign'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Returns branches=>cities assign list and config form
     * 
     * @return string
     */
    public function renderCitiesConfigForm() {
        $result = '';
        //manually preloading cities bingings
        $this->loadCities();
        if (!empty($this->branches)) {
            if (!empty($this->branchesCities)) {
                $cells = wf_TableCell(__('ID'));
                $cells.=wf_TableCell(__('Branch'));
                $cells.= wf_TableCell(__('City'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($this->branchesCities as $io => $each) {
                    $cells = wf_TableCell($io);
                    $cells.=wf_TableCell($this->getBranchName($each['branchid']));
                    $cells.= wf_TableCell($this->allCityNames[$each['cityid']]);
                    $actControls = wf_JSAlert(self::URL_ME . '&settings=true&deletecity=' . $each['cityid'] . '&citybranchid=' . $each['branchid'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells.= wf_TableCell($actControls);
                    $rows.= wf_TableRow($cells, 'row3');
                }
                $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result.=$this->messages->getStyledMessage(__('No branches cities assigns available'), 'warning');
            }

            //assign form
            $branchesTmp = array();
            if (!empty($this->branches)) {
                foreach ($this->branches as $io => $each) {
                    $branchesTmp[$io] = $each['name'];
                }
            }

            $inputs = wf_Selector('newcitybranchid', $branchesTmp, __('Branch'), '', false) . ' ';
            $inputs.= wf_Selector('newcityid', $this->allCityNames, __('City'), '', false) . ' ';
            $inputs.= wf_Submit(__('Assign'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Returns branches=>tariffs assign list and config form
     * 
     * @return string
     */
    public function renderTariffsConfigForm() {
        $result = '';
        //manually preloading tariffs bingings
        $this->loadTariffs();
        if (!empty($this->branches)) {
            if (!empty($this->branchesTariffs)) {
                $cells = wf_TableCell(__('ID'));
                $cells.=wf_TableCell(__('Branch'));
                $cells.= wf_TableCell(__('Tariff'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($this->branchesTariffs as $io => $each) {
                    $cells = wf_TableCell($io);
                    $cells.=wf_TableCell($this->getBranchName($each['branchid']));
                    $cells.= wf_TableCell($each['tariff']);
                    $actControls = wf_JSAlert(self::URL_ME . '&settings=true&deletetariff=' . $each['tariff'] . '&tariffbranchid=' . $each['branchid'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells.= wf_TableCell($actControls);
                    $rows.= wf_TableRow($cells, 'row3');
                }
                $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result.=$this->messages->getStyledMessage(__('No branches tariffs assigns available'), 'warning');
            }

            //assign form
            $branchesTmp = array();
            if (!empty($this->branches)) {
                foreach ($this->branches as $io => $each) {
                    $branchesTmp[$io] = $each['name'];
                }
            }
            $tariffsTmp = array();
            if (!empty($this->allTariffs)) {
                foreach ($this->allTariffs as $tariffName => $tariffFee) {
                    $tariffsTmp[$tariffName] = $tariffName;
                }
            }

            $inputs = wf_Selector('newtariffbranchid', $branchesTmp, __('Branch'), '', false) . ' ';
            $inputs.= wf_Selector('newtariffname', $tariffsTmp, __('Tariff'), '', false) . ' ';
            $inputs.= wf_Submit(__('Assign'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Returns branches=>services assign list and config form
     * 
     * @return string
     */
    public function renderServicesConfigForm() {
        $result = '';
        //manually preloading services bindings
        $this->loadServices();
        if (!empty($this->branches)) {
            if (!empty($this->branchesServices)) {
                $cells = wf_TableCell(__('ID'));
                $cells.=wf_TableCell(__('Branch'));
                $cells.= wf_TableCell(__('Service'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($this->branchesServices as $io => $each) {
                    $cells = wf_TableCell($io);
                    $cells.=wf_TableCell($this->getBranchName($each['branchid']));
                    $cells.= wf_TableCell($this->allServices[$each['serviceid']]);
                    $actControls = wf_JSAlert(self::URL_ME . '&settings=true&deleteservice=' . $each['serviceid'] . '&servicebranchid=' . $each['branchid'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells.= wf_TableCell($actControls);
                    $rows.= wf_TableRow($cells, 'row3');
                }
                $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result.=$this->messages->getStyledMessage(__('No branches services assigns available'), 'warning');
            }

            //assign form
            $branchesTmp = array();
            if (!empty($this->branches)) {
                foreach ($this->branches as $io => $each) {
                    $branchesTmp[$io] = $each['name'];
                }
            }


            $inputs = wf_Selector('newservicebranchid', $branchesTmp, __('Branch'), '', false) . ' ';
            $inputs.= wf_Selector('newserviceid', $this->allServices, __('Service'), '', false) . ' ';
            $inputs.= wf_Submit(__('Assign'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Returns branches management form
     * 
     * @return string
     */
    public function renderSettingsBranches() {
        $result = '';
        if (cfr('BRANCHESCONF')) {
            $result.= wf_tag('h3') . __('Branches') . wf_tag('h3', true);
            $result.=$this->renderBranchesConfigForm();
            $result.= wf_tag('h3') . __('Administrators') . wf_tag('h3', true);
            $result.=$this->renderAdminConfigForm();
            $result.= wf_tag('h3') . __('Cities') . wf_tag('h3', true);
            $result.=$this->renderCitiesConfigForm();
            $result.= wf_tag('h3') . __('Tariffs') . wf_tag('h3', true);
            $result.=$this->renderTariffsConfigForm();
            $result.= wf_tag('h3') . __('Services') . wf_tag('h3', true);
            $result.=$this->renderServicesConfigForm();
        } else {
            $result = $this->messages->getStyledMessage(__('Access denied'), 'error');
        }
        return ($result);
    }

    /**
     * Contols user module branch access rights
     * 
     * @return void
     */
    public function accessControl() {
        if (($this->myLogin != 'guest') AND ( $this->myLogin != 'external')) {
            if ($this->branchesEnabled) {
                $controlVars = array('username', 'login', 'inetlogin', 'userlogin');
                foreach ($controlVars as $io => $each) {
                    if (wf_CheckGet(array($each))) {
                        if (!$this->isMyUser($_GET[$each])) {
                            log_register('BRANCH ACCESS FAIL (' . $_GET[$each] . ') ADMIN {' . $this->myLogin . '}');
                            die('Access denied');
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns selector widget for accessible branches
     * 
     * @param string $name
     * @param int $selected
     * 
     * @return string
     */
    public function branchSelector($name, $selected = '') {
        $result = '';
        if (!empty($this->branches)) {
            $params = array();
            foreach ($this->branches as $branchId => $branchData) {
                if ($this->isMyBranch($branchId)) {
                    $params[$branchId] = $this->getBranchName($branchId);
                }
            }
            if (!empty($params)) {
                $result = wf_Selector($name, $params, $result, $selected, false);
            }
        }
        return ($result);
    }

    /**
     * Renders users assign/editing branch form
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function renderUserBranchFrom($userLogin) {
        $result = '';
        $allUserAddress = zb_AddressGetFullCityaddresslist();
        $currentBranchId = $this->userGetBranch($userLogin);
        $currentBranchName = $this->getBranchName($currentBranchId);

        $cells = wf_TableCell(__('User'), '', 'row2');
        $cells.= wf_TableCell(@$allUserAddress[$userLogin] . ' (' . $userLogin . ')');
        $rows = wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Current branch'), '', 'row2');
        $cells.= wf_TableCell($currentBranchName);
        $rows.= wf_TableRow($cells, 'row3');

        $branchControls = $this->branchSelector('newuserbranchid', $currentBranchId);
        if (cfr('ROOT')) {
            $branchControls.= ' ' . wf_CheckInput('newuserbranchdelete', __('Delete branch'), false, false);
        }
        $cells = wf_TableCell(__('New branch'), '', 'row2');
        $cells.= wf_TableCell($branchControls);
        $rows.= wf_TableRow($cells, 'row3');



        $inputs = wf_TableBody($rows, '100%', 0, '');
        $inputs.= wf_HiddenInput('newuserbranchlogin', $userLogin);
        $inputs.= wf_Submit(__('Change'));

        $result.= wf_Form('', 'POST', $inputs, '');
        $result.=wf_delimiter();
        $result.=web_UserControls($userLogin);
        return ($result);
    }

    /**
     * Catches and performs user branch changing if required
     * 
     * @return void
     */
    public function catchUserBranchEditRequest() {
        $result = '';
        if (wf_CheckPost(array('newuserbranchid', 'newuserbranchlogin'))) {
            $allUsers = zb_UserGetAllStargazerDataAssoc();
            $userLogin = $_POST['newuserbranchlogin'];
            if (isset($allUsers[$userLogin])) {
                $currentBranchId = $this->userGetBranch($userLogin);
                $newBranchId = $_POST['newuserbranchid'];
                //change is really reqired?
                if (!wf_CheckPost(array('newuserbranchdelete'))) {
                    if ($currentBranchId != $newBranchId) {
                        if ($this->isMyBranch($newBranchId)) {
                            $this->userDeleteBranch($userLogin);
                            $this->userAssignBranch($newBranchId, $userLogin);
                            rcms_redirect(self::URL_ME . '&userbranch=' . $userLogin);
                        } else {
                            $result = $this->messages->getStyledMessage(__('Access denied'), 'error');
                        }
                    }
                } else {
                    if (cfr('ROOT')) {
                        $this->userDeleteBranch($userLogin);
                        rcms_redirect(self::URL_ME . '&userbranch=' . $userLogin);
                    } else {
                        $result = $this->messages->getStyledMessage(__('Access denied'), 'error');
                    }
                }
            } else {
                $result = $this->messages->getStyledMessage(__('No such user available'), 'error');
            }
        }

        //something happens
        if (!empty($result)) {
            show_window(__('Result'), $result);
        }
    }

}

?>