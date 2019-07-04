<?php

class UbillingVisor {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all stargazer user data as login=>data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains all visor users data as id=>data
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains all visor cameras data as id=>data
     *
     * @var array
     */
    protected $allCams = array();

    /**
     * Contains all visor dvrs data as id=>data
     *
     * @var array
     */
    protected $allDvrs = array();

    /**
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Basic module URLs
     */
    const URL_ME = '?module=visor';
    const URL_USERS = '&users=true';
    const URL_CAMS = '&cams=true';
    const URL_USERCAMS = '&ajaxusercams=';
    const URL_ALLCAMS = '&ajaxallcams=true';
    const URL_DVRS = '&dvrs=true';
    const URL_AJUSERS = '&ajaxusers=true';
    const URL_DELUSER = '&deleteuserid=';
    const URL_USERVIEW = '&showuser=';
    const URL_CAMPROFILE = '?module=userprofile&username=';

    /**
     * Some default tables names
     */
    const TABLE_USERS = 'visor_users';
    const TABLE_CAMS = 'visor_cams';
    const TABLE_DVRS = 'visor_dvrs';

    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
        $this->loadUserData();
        $this->loadUsers();
        $this->loadCams();
        $this->loadDvrs();
    }

    /**
     * Loads reqired configss
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
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
     * Loads all existing users data from database
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Loads all visor users data into protected property
     * 
     * @return void
     */
    protected function loadUsers() {
        $query = "SELECT * from `visor_users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUsers[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads all visor cameras data into protected property
     * 
     * @return void
     */
    protected function loadCams() {
        $query = "SELECT * from `visor_cams`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allCams[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads all visor DVR data into protected property
     * 
     * @return void
     */
    protected function loadDvrs() {
        $query = "SELECT * from `visor_dvrs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allDvrs[$each['id']] = $each;
            }
        }
    }

    /**
     * Renders default controls panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        $result .= wf_Link(self::URL_ME . self::URL_USERS, wf_img('skins/ukv/users.png') . ' ' . __('Users'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . self::URL_CAMS, wf_img('skins/photostorage.png') . ' ' . __('Cams'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . self::URL_DVRS, wf_img('skins/icon_restoredb.png') . ' ' . __('DVRs'), false, 'ubButton') . ' ';
        return ($result);
    }

    /**
     * Renders available users list container
     * 
     * @return string
     */
    public function renderUsers() {
        $result = '';
        $opts = '"order": [[ 0, "desc" ]]';
        $columns = array('ID', 'Date', 'Name', 'Phone', 'Charge', 'Cams', 'Actions');
        $result .= wf_JqDtLoader($columns, self::URL_ME . self::URL_AJUSERS, false, 'Users', 50, $opts);
        return ($result);
    }

    /**
     * Renders users datatables data
     * 
     * @return void
     */
    public function ajaxUsersList() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                $data[] = $each['id'];
                $data[] = $each['regdate'];
                $visorUserLabel = $this->iconVisorUser() . ' ' . $each['realname'];
                $visorUserLink = wf_Link(self::URL_ME . self::URL_USERVIEW . $each['id'], $visorUserLabel);
                $data[] = $visorUserLink;
                $data[] = $each['phone'];
                $data[] = web_bool_led($each['chargecams'], true);
                $data[] = $this->getUserCamerasCount($each['id']);
                $actLinks = '';
                $actLinks .= wf_JSAlert(self::URL_ME . self::URL_DELUSER . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_Link(self::URL_ME . self::URL_USERVIEW . $each['id'], web_edit_icon());
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Renders visor user creation form
     * 
     * @return string
     */
    public function renderUserCreateForm() {
        $result = '';
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs = wf_HiddenInput('newusercreate', 'true');
        $inputs .= wf_TextInput('newusername', __('Name') . $sup, '', true, 25);
        $inputs .= wf_TextInput('newuserphone', __('Phone'), '', true, 20, 'mobile');
        $inputs .= wf_CheckInput('newuserchargecams', __('Charge money from primary account for linked camera users if required'), true, false);
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new user in database
     * 
     * @return void
     */
    public function createUser() {
        if (wf_CheckPost(array('newusercreate', 'newusername'))) {
            $newRealName = mysql_real_escape_string($_POST['newusername']);
            $newPhone = mysql_real_escape_string($_POST['newuserphone']);
            $newChargeCams = (wf_CheckPost(array('newuserchargecams'))) ? 1 : 0;
            $date = curdatetime();
            $query = "INSERT INTO `" . self::TABLE_USERS . "` (`id`,`regdate`,`realname`,`phone`,`chargecams`) VALUES "
                    . "(NULL,'" . $date . "','" . $newRealName . "','" . $newPhone . "','" . $newChargeCams . "');";
            nr_query($query);
            $newId = simple_get_lastid(self::TABLE_USERS);
            log_register('VISOR USER CREATE [' . $newId . ']');
        }
    }

    /**
     * Returns array of cameras associated to some user
     * 
     * @param int $userId
     * 
     * @return array
     */
    protected function getUserCameras($userId) {
        $result = array();
        if (!empty($this->allCams)) {
            foreach ($this->allCams as $io => $each) {
                if ($each['visorid'] == $userId) {
                    $result[$each['id']] = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns camera user assigned visor user ID if exists
     * 
     * @param string $userLogin
     * 
     * @return int/void
     */
    public function getCameraUser($userLogin) {
        $result = '';
        if (!empty($this->allCams)) {
            foreach ($this->allCams as $io => $each) {
                if ($each['login'] == $userLogin) {
                    $result = $each['visorid'];
                    break;
                }
            }
        }
        return($result);
    }

    /**
     * Returns count of associated user cameras
     * 
     * @param int $userId
     * 
     * @return int
     */
    protected function getUserCamerasCount($userId) {
        $result = 0;
        $userCameras = $this->getUserCameras($userId);
        if (!empty($userCameras)) {
            $result = sizeof($userCameras);
        }
        return ($result);
    }

    /**
     * Deletes user from database
     * 
     * @param int $userId
     * 
     * @return void/string on error
     */
    public function deleteUser($userId) {
        $result = '';
        $userId = vf($userId, 3);
        if (isset($this->allUsers[$userId])) {
            $camerasCount = $this->getUserCamerasCount($userId);
            if ($camerasCount == 0) {
                $query = "DELETE from `" . self::TABLE_USERS . "` WHERE `id`='" . $userId . "';";
                nr_query($query);
                log_register('VISOR USER DELETE [' . $userId . ']');
            } else {
                $result .= __('User have some cameras associated');
            }
        } else {
            $result .= __('User not exists');
        }
        return ($result);
    }

    /**
     * Renders visor users profile with associated cameras and some controls
     * 
     * @param int $userId
     * 
     * @return string
     */
    public function renderUserProfile($userId) {
        $result = '';
        $userId = vf($userId, 3);
        if (isset($this->allUsers[$userId])) {
            $userData = $this->allUsers[$userId];
            if (!empty($userData)) {
                $cells = wf_TableCell(__('Name'), '', 'row2');
                $cells .= wf_TableCell($userData['realname']);
                $rows = wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Phone'), '', 'row2');
                $cells .= wf_TableCell($userData['phone']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Charge'), '', 'row2');
                $cells .= wf_TableCell(web_bool_led($userData['chargecams'], false));
                $rows .= wf_TableRow($cells, 'row3');

                $result .= wf_TableBody($rows, '100%', 0, '');
                $userCamsCount = $this->getUserCamerasCount($userId);
                if ($userCamsCount > 0) {
                    $result .= $this->renderCamerasContainer(self::URL_ME . self::URL_USERCAMS . $userId);
                } else {
                    $result .= $this->messages->getStyledMessage(__('User have no cameras assigned'), 'warning');
                }
            }
        }
        return ($result);
    }

    /**
     * Renders default cameras view container
     * 
     * @param string $url
     * 
     * @return string
     */
    public function renderCamerasContainer($url) {
        $result = '';
        $opts = '"order": [[ 0, "desc" ]]';
        $columns = array('ID', 'Primary', 'User', 'Address', 'IP', 'Actions');
        $result .= wf_JqDtLoader($columns, $url, false, __('Cams'), 50, $opts);
        return($result);
    }

    /**
     * Renders ajax json backend for some user assigned cameras
     * 
     * @param int $userId
     * 
     * @return void
     */
    public function ajaxUserCams($userId) {
        $userId = vf($userId, 3);
        $json = new wf_JqDtHelper();
        if (isset($this->allUsers[$userId])) {
            $allUserCams = $this->getUserCameras($userId);
            if (!empty($allUserCams)) {
                foreach ($allUserCams as $io => $each) {
                    $cameraUserData = @$this->allUserData[$each['login']];
                    $data[] = $each['id'];
                    $data[] = web_bool_led($each['primary']);
                    $visorLinkLabel = $this->iconVisorUser() . ' ' . @$this->allUsers[$each['visorid']]['realname'];
                    $visorUserLink = wf_Link(self::URL_ME . self::URL_USERVIEW . $each['visorid'], $visorLinkLabel);
                    $data[] = $visorUserLink;
                    $cameraLinkLabel = web_profile_icon() . ' ' . $cameraUserData['fulladress'];
                    $cameraLink = wf_Link(self::URL_CAMPROFILE . $each['login'], $cameraLinkLabel);
                    $data[] = $cameraLink;
                    $data[] = @$cameraUserData['ip'];
                    $data[] = 'ACTIONS_TODO';
                    $json->addRow($data);
                    unset($data);
                }
            }
        }
        $json->getJson();
    }

    /**
     * Returns default user icon coode
     * 
     * @return string
     */
    public function iconVisorUser() {
        $result = wf_img('skins/icon_camera_small.png');
        return($result);
    }

    /**
     * Renders ajax json backend for all available cameras
     * 
     * @return void
     */
    public function ajaxAllCams() {
        $json = new wf_JqDtHelper();


        if (!empty($this->allCams)) {
            foreach ($this->allCams as $io => $each) {
                $cameraUserData = @$this->allUserData[$each['login']];
                $data[] = $each['id'];
                $data[] = web_bool_led($each['primary']);
                $visorLinkLabel = $this->iconVisorUser() . ' ' . @$this->allUsers[$each['visorid']]['realname'];
                $visorUserLink = wf_Link(self::URL_ME . self::URL_USERVIEW . $each['visorid'], $visorLinkLabel);
                $data[] = $visorUserLink;
                $cameraLinkLabel = web_profile_icon() . ' ' . $cameraUserData['fulladress'];
                $cameraLink = wf_Link(self::URL_CAMPROFILE . $each['login'], $cameraLinkLabel);
                $data[] = $cameraLink;
                $data[] = @$cameraUserData['ip'];
                $data[] = 'ACTIONS_TODO';
                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

}
