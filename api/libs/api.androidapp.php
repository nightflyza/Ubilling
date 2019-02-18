<?php
class AndroidApp {

    /**
     * Contains data for next convert on json data
     *
     * @var array
     */
    protected $json = array();

    /**
     * USER LOGGED flag 
     *
     * @var bool
     */
    protected $loggedIn = false;

    /**
     * Access status flag 
     *
     * @var bool
     */
    public $access = false;

    /**
     * Operation status flag 
     *
     * @var bool
     */
    protected $success = true;

    /**
     * Debug status flag 
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Some information massege
     *
     * @var void
     */
    protected $message = '';

    /**
     * Main data conteiner
     *
     * @var array
     */
    protected $data = array();

    /**
     * Contains debug message and information
     *
     * @var array
     */
    protected $debug_message = array();

    /**
     * Contains current user login
     *
     * @var string
     */
    protected $adminLogin = '';

    /**
     * Contains action for API
     *
     * @var string
     */
    protected $getModuleAction = '';

    /**
     * Contains date at function curdate()
     *
     * @var string
     */
    protected $getDate = '';

    /**
     * Contains date at function curdate()
     *
     * @var string
     */
    protected $getStartDate = '';

    /**
     * Contains date at function curdate()
     *
     * @var string
     */
    protected $getEndDate = '';

    /**
     * Change default $getDate that getting from $_GET
     *
     * @var bool
     */
    protected $setGetDate = false;

    /**
     * Change default $getStartDate that getting from $_GET
     *
     * @var bool
     */
    protected $setGetStartDate = false;

    /**
     * Return all needed permissions
     *
     * @var array
     */
    protected $permissions = array();

    /**
     * Return all checking permissions
     *
     * @var array
     */
    protected $needRights = array();

    /**
     * Conteins users data
     *
     * @var array
     */
    protected $usersData = array();

    /**
     * Contains admns Name as admin_login => admin_name
     *
     * @var array
     */
    protected $adminsName = array();

    /**
     * UbillingCache object placeholder
     *
     * @var object
     */
    protected $cache = '';

    public function __construct() {
        // Check if user logged
        if (LOGGED_IN){
            // Only once need change this parametr
            $this->loggedIn = true;
            // Check who logged
            $this->setLogin();
            $this->loadAdminsName();
            if (cfr('ANDROID')) { 
                $this->access = true;
                $this->initDebug();
                $this->setGetModuleAction();
                $this->setGetDate();
//              $this->loadData();
            }
        } else {
            $this->json['message'] = 'First you need login';
        }
    }

    /**
     * Check getting module
     *
     * @return void
     */
    protected function setGetModuleAction() {
        if (wf_CheckGet(array('action'))){
            $this->getModuleAction = vf($_GET['action']);
        } else {
            $this->getModuleAction = 'taskman';
        }
    }

    /**
     * Check getting date
     *
     * @return void
     */
    protected function setGetDate() {
        $this->getDate = curdate();
        $this->getStartDate = curdate();
        $this->getEndDate = curdate();
        // Change parametrs days if needed
        if (wf_CheckGet(array('date'))){
            $this->getDate =  date( "Y-m-d", strtotime($_GET['date']));
            $this->setGetDate = true;
        } elseif (wf_CheckGet(array('startdate'))) {
            $this->getStartDate = date( "Y-m-d", strtotime($_GET['startdate']));
            // Check if we getting endDate
            if (wf_CheckGet(array('enddate'))) {
                $testEndDate = date( "Y-m-d", strtotime($_GET['enddate']));
                // We check that we are not trying to cheat
                if ($testEndDate > $this->getStartDate) {
                    $this->getEndDate = $testEndDate;
                }
            }
            $this->setGetStartDate = true;
        }
        $this->DebugMessageAdd('date', array('getDate' => $this->getDate, 'getStartDate' => $this->getStartDate, 'getEndDate' => $this->getEndDate));
    }

    /**
     *
     *
     * @return void
     */
    public function loadData() {
        if($this->access) {
            switch ($this->getModuleAction) {
                case 'getallcashtypes':
                    $this->getAllCashTypes();
                    break;
                case 'getalljobtypes':
                    $this->getJobTypes();
                    break;
                case 'admins':
                         $this->data = unserialize(ts_GetAllEmployeeLoginsCached());
                    break;
                case 'emploees':
                        $this->data = ts_GetAllEmployee();
                    break;
                case 'usersearch':
                    if (cfr('USERSEARCH')) {
                        $this->renderSerchUsersData();
                    } else {
                        $this->updateSuccessAndMessage('Permission denied');
                        $this->DebugMessageAdd('Permission denied for', array('function' => 'loadData', 'cfr' => 'USERSEARCH', 'getModuleAction' => 'usersearch'));
                    }
                    $this->permissionCheckAdd('usersearch');
                    break;
                case 'userprofile':
                case 'addcash':
                case 'useredit':
                    if (cfr('USERPROFILE')) {
                        $this->renderUserData();
                    } else {
                        $this->updateSuccessAndMessage('Permission denied');
                        $this->DebugMessageAdd('Permission denied for', array('function' => 'loadData', 'cfr' => 'USERPROFILE', 'getModuleAction' => 'usersearch'));
                    }
                    $this->permissionCheckAdd('userprofile');
                    break;
                case 'pl_dhcp':
                    if (cfr('PLDHCP')) {
                        $this->renderUserData();
                    } else {
                        $this->updateSuccessAndMessage('Permission denied');
                        $this->DebugMessageAdd('Permission denied for', array('function' => 'loadData', 'cfr' => 'PLDHCP', 'getModuleAction' => 'pl_dhcp'));
                    }
                    $this->permissionCheckAdd('pl_dhcp');
                    break;
                case 'pl_pinger':
                    if (cfr('PLPINGER')) {
                        $this->renderUserData();
                    } else {
                        $this->updateSuccessAndMessage('Permission denied');
                        $this->DebugMessageAdd('Permission denied for', array('function' => 'loadData', 'cfr' => 'PLPINGER', 'getModuleAction' => 'pl_pinger'));
                    }
                    $this->permissionCheckAdd('pl_pinger');
                    break;
                case 'taskmanundone':
                    if (cfr('TASKMAN')) {
                        $this->getTasks(false, true);
                    } else {
                        $this->updateSuccessAndMessage('Permission denied');
                        $this->DebugMessageAdd('Permission denied for', array('function' => 'loadData', 'cfr' => 'TASKMAN', 'getModuleAction' => 'taskman'));
                    }
                    $this->permissionCheckAdd('taskman');
                    $this->permissionCheckAdd('userprofile');
                    
                    break; 
                case 'taskmandone':
                    if (cfr('TASKMAN')) {
                        $this->getTasks(true, false);
                    } else {
                        $this->updateSuccessAndMessage('Permission denied');
                        $this->DebugMessageAdd('Permission denied for', array('function' => 'loadData', 'cfr' => 'TASKMAN', 'getModuleAction' => 'taskmandone'));
                    }
                    $this->permissionCheckAdd('taskman');
                    $this->permissionCheckAdd('userprofile');
                    
                    break;
                case 'taskman':
                    if (cfr('TASKMAN')) {
                        $this->getTasks();
                    } else {
                        $this->updateSuccessAndMessage('Permission denied');
                        $this->DebugMessageAdd('Permission denied for', array('function' => 'loadData', 'cfr' => 'TASKMAN', 'getModuleAction' => 'taskman'));
                    }
                    $this->permissionCheckAdd('taskman');
                    $this->permissionCheckAdd('userprofile');
                    
                    break;
                default:
                    if (cfr('TASKMAN')) {
                        $this->getTasks();
                        $this->permissionCheckAdd('taskman');
                        $this->permissionCheckAdd('userprofile');
                    } else {
                        $this->success = false;
                        $this->message = __('Permission denied');
                        $this->updateSuccessAndMessage('Permission denied');
                        $this->DebugMessageAdd('Permission denied for', array('function' => 'loadData', 'cfr' => 'TASKMAN', 'getModuleAction' => 'default'));
                    }
            }
            // Set check permissons for modules that use global
            $this->permissionCheckAdd('taskmansearch');
            $this->permissionCheckAdd('taskman');
            $this->permissionCheckAdd('userprofile');
            $this->permissionCheckAdd('useredit');
            $this->permissionCheckAdd('pl_dhcp');
            $this->permissionCheckAdd('pl_pinger');
        }
    }

    /**
     * Get user dhcp log
     * 
     * @return void
     */
    public function getUserDhcpLog($login) {
        global $ubillingConfig;
        if (!empty($login)) {
            $this->usersData = zb_UserGetAllData($login);
            // Check that we have some data user
            if (current($this->usersData)) {
                $config = $ubillingConfig->getBilling();
                $alter_conf = $ubillingConfig->getAlter();
                $cat_path = $config['CAT'];
                $grep_path = $config['GREP'];
                $tail_path = $config['TAIL'];
                $sudo_path = $config['SUDO'];
                $leasefile = $ubillingConfig->getAlterParam('NMLEASES');
                $command = $sudo_path.' '.$cat_path.' '.$leasefile.' | '.$grep_path.' '.$this->usersData[$login]['mac'].' | '.$tail_path.'  -n 30';
                $output = shell_exec($command);
                $this->usersData[$login]['dhcp'] = $output;
                } else { 
                    $this->updateSuccessAndMessage('Username cannot be empty');
                }
        }
    }

    /**
     * Get user ping result
     * 
     * @return void
     */
    public function getUserPingResult($login) {
        global $ubillingConfig;
        if (!empty($login)) {
            $this->usersData = zb_UserGetAllData($login);
            // Check that we have some data user
            if (current($this->usersData)) {
                $config = $ubillingConfig->getBilling();
                $alter_conf = $ubillingConfig->getAlter();
                $ping_path = $config['PING'];
                $sudo_path = $config['SUDO'];
                $command = $sudo_path . ' ' . $ping_path . ' -i 0.01 -c 10 ' . $this->usersData[$login]['ip'];
                $output = shell_exec($command);
                $this->usersData[$login]['ping'] = $output;
                } else { 
                    $this->updateSuccessAndMessage('Username cannot be empty');
                }
        }
    }

    /**
     * Initalizes system cache object for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Clear scope cache object
     * 
     * @return void
     */
    protected function clearScopeCache() {
        $this->cache->delete('ADCOMMENTS_TASKMAN');
    }

    /**
     * Creates new comment in database
     * 
     * @param string $text text for new comment
     * 
     * @return void
     */
    public function createComment($id, $text) {
        $curdate = curdatetime();
        $text = strip_tags($text);
        $text = mysql_real_escape_string($text);
        $query = "INSERT INTO `adcomments` (`id`, `scope`, `item`, `date`, `admin`, `text`) "
                . "VALUES (NULL, 'TASKMAN', '" . $id . "', '" . $curdate . "', '" . $this->adminLogin . "', '" . $text . "');";
        nr_query($query);
        log_register("ADCOMM CREATE SCOPE `TASKMAN` ITEM [" . $id . "]");
        $this->initCache();
        $this->clearScopeCache();
    }

    /**
     * 
     * 
     * @return void
     */
    public function getUserData($login) {
        global $ubillingConfig;
        if (!empty($login)) {
            $this->usersData = zb_UserGetAllData($login);
            // Check that we have some data user
            if (current($this->usersData)) {
                // check thate need add contract date
                if ($ubillingConfig->getAlterParam('CONTRACTDATE_IN_PROFILE')) {
                    $contract = $this->usersData[$login]['contract'];
                    if (!empty($contract)) {
                        $allContractDates = zb_UserContractDatesGetAll();
                        $contractDate = (isset($allContractDates[$contract])) ? $allContractDates[$contract] : '';
                        $this->usersData[$login]['contractdate'] = $contractDate;
                    }
                }
                //additional mobile data
                if ($ubillingConfig->getAlterParam('MOBILES_EXT')) {
                    $extMob = new MobilesExt();
                    $allExt = array_column($extMob->getUserMobiles($login), 'mobile');
                    $additionalNumbers = implode(', ', $allExt);
                    $this->usersData[$login]['additionalNumbers'] = $additionalNumbers;
                }
                // User payment ID 
                if ($ubillingConfig->getAlterParam('OPENPAYZ_REALID')) {
                    $this->usersData[$login]['paymantid'] = zb_PaymentIDGet($login);
                } else {
                    $this->usersData[$login]['paymantid'] = ip2int($this->usersData[$login]['ip']);
                }
                $this->usersData[$login]['notes'] = zb_UserGetNotes($login);
                // gets and preformats last activity time
                if ($ubillingConfig->getAlterParam('PROFILE_LAT')) {
                    //if ($this->usersData[$login]['LastActivityTime'] != 0) {
                        //$data = date("Y-m-d H:i:s", $this->usersData[$login]['LastActivityTime']);
                    //  $this->usersData[$login]['LastActivityTime'] = $data;
                    //}
                }
                //Returns user connection details with optional controls inside if enabled
                if ($ubillingConfig->getAlterParam('CONDET_ENABLED')) {
					$conDet = new ConnectionDetails();
					$this->usersData[$login]['ConnectionDetails'] = $conDet->renderData($login);
				}

            }
        } else { 
            $this->updateSuccessAndMessage('Username cannot be empty');
        }
    }

    /**
     * 
     * 
     * @return void
     */
    protected function renderUserData() {
        if (!empty($this->usersData)) {
            //$login = key($this->usersData);
            //$this->data = $this->usersData[$login];
            $this->data = $this->usersData;
            $this->DebugMessageAdd('function', array('renderUserData' => $this->usersData));
        } else {
            $this->updateSuccessAndMessage('EMPTY_DATABASE_USERDATA');
            $this->DebugMessageAdd('function', array('login' => @$_GET['username']));
        }
    }

    /**
     * 
     * 
     * @return void
     */
    public function searchUsersQuery($query) {
        if (strlen($query) >= 3) {
            $this->usersData = array_intersect_key(zb_UserGetAllDataCache(), array_flip(zb_UserSearchAllFields($query, false)));
        } else { 
            $this->success = false;
            $this->message = __('At least 3 characters are required for search');
        }
    }

    /**
     * 
     * 
     * @return void
     */
    protected function renderSerchUsersData() {
        $this->data = $this->usersData;
        $this->DebugMessageAdd('SQLquery', array('renderSerchUsersData' => $this->usersData));
    }

    /**
     * 
     * 
     * @return void
     */
    protected function getTasks($showDone = false, $showUndone = false) {
        global $ubillingConfig;
        $SQLwhere = '';
        $SQLwhereArr = array();

        // Check if we want get tasks for all emploees
        if(isset($_GET['emploee']) and $_GET['emploee'] == 'all') {
            $SQLwhereArr['emploee'] = '';
        } elseif (isset($_GET['emploee']) and !empty($_GET['emploee'])) {
            $SQLwhereArr['emploee'] = "`employee`='" . vf($_GET['emploee'], 3) . "'" ;
        } else {
            $SQLwhereArr['emploee'] = "`employee`='" . ts_GetEmployeeByLogin($this->adminLogin) . "'" ;
        }

        // Check if need show undone tasks
        if ($showUndone) {
            $SQLwhereArr['status'] = "status = '0'";
        }
        // Check if need show only done tasks
        if ($showDone) {
            $SQLwhereArr['status'] = "status = '1'";
        }

        if ($showUndone) {
            $SQLwhereArr['date'] = "startdate < '". curdate() . "'";
        } 

        if ($this->setGetDate){
            $SQLwhereArr['date'] = "(`startdate` = '" . $this->getDate . "' OR `enddate` = '" . $this->getDate . "')";
        }

        if ($this->setGetStartDate){
            $SQLwhereArr['date'] = "`startdate` BETWEEN '" . $this->getStartDate . "' AND '" . $this->getEndDate . "'";
        }

        // Create and WHERE to query
        if (!empty($SQLwhereArr)) {
            $SQLwhereArrFilter = array_filter($SQLwhereArr);
            $SQLwhere = " WHERE " . implode(" AND ", $SQLwhereArrFilter);
        }

        $query = "SELECT `taskman`.*, `jobtypes`.`jobname` 
                    FROM `taskman`  
                    LEFT JOIN `jobtypes` ON `taskman`.`jobtype` = `jobtypes`.`id` 
                    " . $SQLwhere . "
                    ORDER BY `date` ASC";
        $tasksArr = simple_queryall($query);

        //additional comments 
        if ($ubillingConfig->getAlterParam('ADCOMMENTS_ENABLED')) {
            array_walk($tasksArr, 
                function ($item, $key) use (&$tasksArr) {
                    $query = "SELECT * from `adcomments` WHERE `scope`='TASKMAN' AND `item`='" . $item['id'] . "' ORDER BY `date` ASC;";
                    $all = simple_queryall($query);

                    $tasksArr[$key]['comments'] = $all;
                    return($tasksArr);
                }
            );
        }

        $this->data = $tasksArr;
        $this->DebugMessageAdd('SQLwhere', $SQLwhere);
        $this->DebugMessageAdd('SQLwhereArr', $SQLwhereArr);
        $this->DebugMessageAdd('SQLwhereArrFilter', $SQLwhereArrFilter);
        $this->DebugMessageAdd('SQLwhereImlode', implode(" AND ", $SQLwhereArr));
        $this->DebugMessageAdd('SQLwhereImlode', implode(" AND ", $SQLwhereArrFilter));
        $this->DebugMessageAdd('SQLquery', array('GetUndoneTasksForToDay' => $query));
    }

    /**
     * Return array of all available cashtypes as id=>name
     * 
     * @return void
     */
    protected function getAllCashTypes() {
        $result = zb_CashGetAllCashTypes();
        $this->data = $result;
        $this->DebugMessageAdd('Use function', array('function' => 'getAllCashTypes'));
    }

    /**
     * 
     * 
     * @return void
     */
    protected function getJobTypes() {
        $result = ts_GetAllJobtypes();
        $this->data = $result;
        $this->DebugMessageAdd('Use function', array('function' => 'getJobTypes'));
    }

     /**
     * Sets current user login
     * 
     * @return void
     */
    protected function initDebug() {
        if(isset($_GET['debug']) and $_GET['debug'] == 'true' ) {
            if ($this->access and cfr('ANDROIDDEBUG')) {
                $this->debug = true;
            } else {
                $this->success = false;
                $this->message = __('Permission denied');
            }
        }
    }

     /**
     * Sets current user login
     * 
     * @return void
     */
    protected function setLogin() {
        $this->adminLogin = whoami();
    }

    /**
     * Loads admis Name
     * 
     * @return void
     */
    protected function loadAdminsName() {
        @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
        if ( ! empty($employeeLogins)) {
            foreach ($employeeLogins as $login => $name){
                $this->adminsName[$login] = $name;
            }
        }
    }

    /**
     * Function for add debug information from function
     *
     * @return array
     */
    protected function DebugMessageAddArr($module, $data) {
        $this->debug_message[$module][] = $data;
    }

    /**
     * Function for add GLOBAL debug information
     *
     * @return array
     */
    protected function DebugGlobalMessageAdd() {
        $this->debug_message['DEBUG_POST'] = $_POST;
        $this->debug_message['DEBUG_GET'] = $_GET;
        $this->debug_message['DEBUG_COOKIE'] = $_COOKIE;
    }
    
    /**
     * Function for add debug information
     *
     * @return array
     */
    public function DebugMessageAdd($module = '', $data = '') {
        if ($this->debug) {
            $this->DebugMessageAddArr($module, $data);
        }
    }

    /**
     * Function for add information about module permission
     *
     * @return array
     */
    protected function permissionCheckAdd($module = '') {
        global $system;
        if (!empty($module)) {
            $permission_arr = @$system->modules['main'][$module]['rights'];
            if (!empty($permission_arr)) {
                foreach($permission_arr as $right => $desc) {
                    $this->permissions[$right]['desc'] = $desc;
                    $this->permissions[$right]['rights'] = cfr($right);
                }
            }
        }
    }

    /**
     * Function for add debug information
     *
     * @return array
     */
    public function checkRight($right = '') {
        $result = false;
        if (!empty($right)) {
                    $this->needRights[] = $right;
                    $result = cfr($right);
        }
        return $result;
    }

    /**
     * Check getting date
     *
     * @return void
     */
    public function updateSuccessAndMessage($message = 'SOME_ERROR') {
        $this->success = false;
        $this->message = __($message);
    }

    /**
     * Crete Json objects
     *
     * @return array
     */
    protected function CreateJsonData() {
        // Load default debug message
        if ($this->debug) {
            $this->DebugGlobalMessageAdd();
        }

        $this->json['logged_in'] = $this->loggedIn;
        $this->json['access'] = $this->access;
        $this->json['success'] = $this->success;
        $this->json['admin'] = $this->adminLogin;
        $this->json['admin_name'] = (isset($this->adminsName[$this->adminLogin])) ? $this->adminsName[$this->adminLogin] : $this->adminLogin;
        $this->json['message'] = $this->message;
        $this->json['module'] = $this->getModuleAction;
        $this->json['needrights'] = $this->needRights;
        $this->json['rights'] = $this->permissions;
        $this->json['data'] = $this->data;
        $this->json['debug'] = $this->debug_message;
    }

    /**
     * GENERAL FUNCTION
     * Render Json objects
     *
     * @return array/json
     */
    public function RenderJson() {
        $this->CreateJsonData();

        // Send main headers
        header('Last-Modified: ' . gmdate('r')); 
        header('Content-Type: application/json; charset=UTF-8');
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1 
        header("Pragma: no-cache");

        return (json_encode($this->json, JSON_PRETTY_PRINT));
    }
}
?>
