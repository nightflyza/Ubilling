<?php

class UbillingBranches {

    /**
     * Contains current user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Contains available branches as id=>branch data
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
     * Contains branches admins as id=>data
     *
     * @var array
     */
    protected $branchesAdmins = array();

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    public function __construct() {
        $this->setLogin();
        $this->loadAlter();
        $this->loadBranches();
        $this->loadBranchesAdmins();
        $this->setMyBranches();
        $this->loadBranchesUsers();
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
     * Loads available branches from database
     * 
     * @return void
     */
    protected function loadBranches() {
        $query = "SELECT * from `branches`";
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
            $query = "SELECT * from `branchesadmins`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->branchesAdmins[$each['id']] = $each;
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
        $query = "INSERT INTO `branches` (`id`,`name`) VALUES ";
        $query.="(NULL,'" . $nameF . "');";
        nr_query($query);
        $newId = simple_get_lastid('branches');
        log_register('BRANCH CREATE [' . $newId . '] `' . $name . '`');
        return ($newId);
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
            log_register('BRANCH DELETE [' . $branchId . ']');
        }
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
            $query = "INSERT INTO `branchesadmins` (`id`,`branchid`,`admin`) VALUES";
            $query.="(NULL,'" . $branchId . "','" . $adminF . "');";
            nr_query($query);
            log_register('BRANCH ASSIGN [' . $branchId . '] ADMIN `' . $admin . '`');
        } else {
            throw new Exception('EX_BRANCHID_NOT_EXISTS');
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
            $query = "INSERT INTO `branchesusers` (`id`,`branchid`,`login`) VALUES ";
            $query.="(NULL,'" . $branchId . "','" . $loginF . "');";
            nr_query($query);
            log_register('BRANCH ASSIGN [' . $branchId . '] USER (' . $login . ')');
        } else {
            throw new Exception('EX_BRANCHID_NOT_EXISTS');
        }
    }

}

?>