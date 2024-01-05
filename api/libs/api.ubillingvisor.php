<?php

/**
 * Surveillance accounting and management implementation
 */
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
     * Contains all available tariffs fees as tariff=>fee
     *
     * @var array
     */
    protected $allTariffPrices = array();

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
     * Contains all available users payment IDs
     *
     * @var array
     */
    protected $allPaymentIDs = array();

    /**
     * Contains available DVR handler types
     *
     * @var array
     */
    protected $dvrTypes = array();

    /**
     * Visor charge mode from VISOR_CHARGE_MODE config option.
     *
     * @var int
     */
    protected $chargeMode = 1;

    /**
     * Trassir Server integration flag
     *
     * @var bool
     */
    protected $trassirEnabled = false;

    /**
     * WolfRecorder integration flag
     *
     * @var bool
     */
    protected $wolfRecorderEnabled = false;

    /**
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains preloaded channels to visor user bindings as visorId=>data
     *
     * @var array
     */
    protected $allChannels = array();

    /**
     * Contains channel to users bindings as channelGuid=>visorId
     *
     * @var array
     */
    protected $channelUsers = array();

    /**
     * Contains available secrets bindings with auth data as visorId=>secretsData
     *
     * @var array
     */
    protected $allSecrets = array();

    /**
     * Channels binginds database model
     *
     * @var object
     */
    protected $chans = '';

    /**
     * NVR secrets data model placeholder
     *
     * @var object
     */
    protected $secrets = '';

    /**
     * Available channel record modes
     *
     * @var array
     */
    protected $recordModes = array();

    /**
     * Default channel preview size
     *
     * @var string
     */
    protected $chanPreviewSize = '30%';

    /**
     * Quality percent for channels small preview
     *
     * @var int
     */
    protected $chanPreviewQuality = 1;

    /**
     * Channels preview 
     *
     * @var int
     */
    protected $chanPreviewFramerate = 1000; // 1 fps

    /**
     * Quality percent of large channel preview
     *
     * @var int
     */
    protected $chanBigPreviewQuality = 95;

    /**
     * Large preview framerate
     *
     * @var int
     */
    protected $chanBigPreviewFramerate = 1000;

    /**
     * Global Trassir NVR stream preview container type. Now supported: mjpeg or hls.
     *
     * @var string
     */
    protected $chanPreviewContainer = 'mjpeg';

    /**
     * TrassirServer debug flag
     *
     * @var bool
     */
    protected $trassirDebug = false;

    /**
     * Contains array of users with protected from unprivileged staff
     *
     * @var array
     */
    protected $protectedUserIds = array();

    /**
     * Users database abstraction layer
     *
     * @var object
     */
    protected $usersDb = '';

    /**
     * Cameras database abstraction layer
     *
     * @var object
     */
    protected $camsDb = '';

    /**
     * DVRs database abstraction layer
     *
     * @var object
     */
    protected $dvrsDb = '';

    /**
     * Channels database abstraction layer
     *
     * @var object
     */
    protected $chansDb = '';

    /**
     * Secrets database abstraction layer
     *
     * @var object
     */
    protected $secretsDb = '';

    /**
     * Use or not cached users data?
     *
     * @var bool
     */
    protected $cachedUsersFlag = false;

    /**
     * Basic module URLs
     */
    const URL_ME = '?module=visor';
    const URL_USERS = '&users=true';
    const URL_CAMS = '&cams=true';
    const URL_USERCAMS = '&ajaxusercams=';
    const URL_ALLCAMS = '&ajaxallcams=true';
    const URL_DVRS = '&dvrs=true';
    const URL_CHANS = '&channels=true';
    const URL_HEALTH = '&health=true';
    const URL_CHANEDIT = '&editchannel=';
    const URL_AJUSERS = '&ajaxusers=true';
    const URL_DELUSER = '&deleteuserid=';
    const URL_DELDVR = '&deletedvrid=';
    const URL_USERVIEW = '&showuser=';
    const URL_CAMPROFILE = '?module=userprofile&username=';
    const URL_CAMVIEW = '&showcamera=';
    const URL_TARCHANGE = '&tariffchanges=true';

    /**
     * Some default database tables names
     */
    const TABLE_USERS = 'visor_users';
    const TABLE_CAMS = 'visor_cams';
    const TABLE_DVRS = 'visor_dvrs';
    const TABLE_CHANS = 'visor_chans';
    const TABLE_SECRETS = 'visor_secrets';

    /**
     * Other stuff
     */
    const PATH_MODELS = 'content/documents/visormodels/';

    public function __construct() {
        $this->loadConfigs();
        $this->loadDvrTypes();
        $this->initMessages();
        $this->initDbLayers();
        $this->loadUserData();
        $this->loadUsers();
        $this->loadTariffPricing();
        $this->loadPaymentIds();
        $this->loadCams();
        $this->loadDvrs();
        $this->loadRecordModes();
        $this->loadChans();
        $this->loadSecrets();
    }

    /**
     * Inits all required database abstraction layers
     * 
     * @return void
     */
    protected function initDbLayers() {
        $this->usersDb = new NyanORM(self::TABLE_USERS);
        $this->camsDb = new NyanORM(self::TABLE_CAMS);
        $this->dvrsDb = new NyanORM(self::TABLE_DVRS);
        $this->chansDb = new NyanORM(self::TABLE_CHANS);
        $this->secretsDb = new NyanORM(self::TABLE_SECRETS);
    }

    /**
     * Loads reqired configs
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (@$this->altCfg['VISOR_CHARGE_MODE']) {
            $this->chargeMode = $this->altCfg['VISOR_CHARGE_MODE'];
        }

        if ($this->altCfg['WOLFRECORDER_ENABLED']) {
            $this->wolfRecorderEnabled = true;
        }

        if (@$this->altCfg['TRASSIRMGR_ENABLED']) {
            $this->trassirEnabled = true;
        }

        if (@$this->altCfg['TRASSIRHLS_ENABLED']) {
            $this->chanPreviewContainer = 'hls';
        }

        if (@$this->altCfg['TRASSIR_DEBUG']) {
            $this->trassirDebug = $this->altCfg['TRASSIR_DEBUG'];
        }

        if (@$this->altCfg['VISOR_PROTUSERIDS']) {
            $rawProtUsers = explode(',', $this->altCfg['VISOR_PROTUSERIDS']);
            $this->protectedUserIds = array_flip($rawProtUsers);
        }

        if (@$this->altCfg['VISOR_CACHED_USERDATA']) {
            $this->cachedUsersFlag = true;
        }
    }

    /**
     * Sets available DVR types
     * 
     * @return void
     */
    protected function loadDvrTypes() {
        $this->dvrTypes = array(
            'generic' => __('No')
        );

        if ($this->wolfRecorderEnabled) {
            $this->dvrTypes += array('wolfrecorder' => __('WolfRecorder'));
        }

        if ($this->trassirEnabled) {
            $this->dvrTypes += array('trassir' => __('Trassir Server'));
        }
    }

    /**
     * Sets default available channel record modes
     * 
     * @return void
     */
    protected function loadRecordModes() {
        $this->recordModes = array(
            1 => __('Permanent record'),
            2 => __('Manual record'),
            3 => __('On detector')
        );
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
        if ($this->cachedUsersFlag) {
            $this->allUserData = zb_UserGetAllDataCache();
        } else {
            $this->allUserData = zb_UserGetAllData();
        }
    }

    /**
     * Loads tariffs pricing data from database into protected prop
     * 
     * @return void
     */
    protected function loadTariffPricing() {
        $this->allTariffPrices = zb_TariffGetPricesAll();
    }

    /**
     * Loads available channels bindings from database
     * 
     * @return void
     */
    protected function loadChans() {
        $chansTmp = $this->chansDb->getAll();
        if (!empty($chansTmp)) {
            foreach ($chansTmp as $io => $each) {
                $this->allChannels[$each['visorid']][] = $each;
                $this->channelUsers[$each['chan']] = $each['visorid'];
            }
        }
    }

    /**
     * Loads available secrets bindings from database
     * 
     * @return void
     */
    protected function loadSecrets() {
        $this->allSecrets = $this->secretsDb->getAll('visorid');
    }

    /**
     * Loads available payment IDs from database
     * 
     * @return void
     */
    protected function loadPaymentIds() {
        if ($this->altCfg['OPENPAYZ_SUPPORT']) {
            if ($this->altCfg['OPENPAYZ_REALID']) {
                $openPayz = new OpenPayz(false, true);
                $this->allPaymentIDs = $openPayz->getCustomersPaymentIds();
            } else {
                if (!empty($this->allUserData)) {
                    foreach ($this->allUserData as $io => $each) {
                        $this->allPaymentIDs[$each['login']] = ip2int($each['ip']);
                    }
                }
            }
        }
    }

    /**
     * Loads all visor users data into protected property
     * 
     * @return void
     */
    protected function loadUsers() {
        $this->usersDb->orderBy('id', 'DESC');
        $this->allUsers = $this->usersDb->getAll('id');
    }

    /**
     * Loads all visor cameras data into protected property
     * 
     * @return void
     */
    protected function loadCams() {
        $this->camsDb->orderBy('id', 'DESC');
        $this->allCams = $this->camsDb->getAll('id');
    }

    /**
     * Loads all visor DVR data into protected property
     * 
     * @return void
     */
    protected function loadDvrs() {
        $this->dvrsDb->orderBy('id', 'DESC');
        $this->allDvrs = $this->dvrsDb->getAll('id');
    }

    /**
     * Renders default controls panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        $result .= wf_Link(self::URL_ME . self::URL_USERS, wf_img('skins/ukv/users.png') . ' ' . __('Users'), false, 'ubButton') . ' ';
        if (cfr('VISOREDIT')) {
            $result .= wf_modalAuto(wf_img('skins/ukv/add.png') . ' ' . __('Users registration'), __('Users registration'), $this->renderUserCreateForm(), 'ubButton') . ' ';
        }
        $result .= wf_Link(self::URL_ME . self::URL_CAMS, wf_img('skins/photostorage.png') . ' ' . __('Cams'), false, 'ubButton') . ' ';
        if (cfr('VISOREDIT')) {
            $result .= wf_Link(self::URL_ME . self::URL_DVRS, wf_img('skins/icon_restoredb.png') . ' ' . __('DVRs'), false, 'ubButton') . ' ';
            if ($this->trassirEnabled OR $this->wolfRecorderEnabled) {
                $result .= wf_Link(self::URL_ME . self::URL_CHANS, wf_img('skins/play.png') . ' ' . __('Channels'), false, 'ubButton') . ' ';
                $result .= wf_Link(self::URL_ME . self::URL_HEALTH, wf_img('skins/log_icon_small.png') . ' ' . __('DVR health'), false, 'ubButton') . ' ';
            }
        }

        if (@$this->altCfg['DDT_ENABLED']) {
            $result .= wf_Link(self::URL_ME . self::URL_TARCHANGE, wf_img_sized('skins/icon_tariff.gif', '', '16') . ' ' . __('Tariff will change'), false, 'ubButton') . ' ';
        }
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
        $columns = array('ID', 'Date', 'Name', 'Phone', 'Primary account', 'Balance', 'Charge', 'Tariffing', 'Cams', 'Actions');
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
                $tariffingLabel = '';
                $data[] = $each['id'];
                $data[] = $each['regdate'];
                $visorUserLabel = $this->iconVisorUser() . ' ' . $each['realname'];
                $visorUserLink = wf_Link(self::URL_ME . self::URL_USERVIEW . $each['id'], $visorUserLabel);
                $data[] = $visorUserLink;
                $data[] = $each['phone'];
                if (!empty($each['primarylogin'])) {
                    $primaryAccount = $each['primarylogin'];
                    $userAddress = @$this->allUserData[$primaryAccount]['fulladress'];
                    $primAccLink = wf_Link(self::URL_CAMPROFILE . $each['primarylogin'], web_profile_icon() . ' ' . $userAddress);
                    if (isset($this->allUserData[$primaryAccount])) {
                        $primaryAccountCash = $this->allUserData[$primaryAccount]['Cash'];
                        if ($each['chargecams']) {
                            $tariffingLabel = wf_img_sized('skins/icon_ok.gif', __('Funds for cameras will be charged from the main account at the end of the month'), 16);
                        } else {
                            $tariffingLabel = $tariffingNotice = wf_img_sized('skins/icon_lock.png', __('All cameras live by themselves'), 16);
                        }

                        if ($this->allUserData[$primaryAccount]['Passive'] AND $each['chargecams']) {
                            $tariffingLabel = wf_img_sized('skins/icon_passive.gif', __('Main account is frozen') . '. ' . __('All cameras live by themselves'), 16);
                        }
                    }
                } else {
                    $primAccLink = '';
                    $primaryAccountCash = '';
                    $tariffingLabel = wf_img_sized('skins/delete_small.png', __('All cameras live by themselves') . ', ' . __('no primary account set'), 16);
                }


                $data[] = $primAccLink;
                $data[] = $primaryAccountCash;

                $chargeFlag = ($each['chargecams']) ? web_bool_led(true) . ' ' . __('Yes') : web_bool_led(false) . ' ' . __('No');
                $data[] = $chargeFlag;
                $data[] = $tariffingLabel;
                $data[] = $this->getUserCamerasCount($each['id']);
                $actLinks = '';
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
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new user in database
     * 
     * @return int
     */
    public function createUser() {
        $result = '';
        if (ubRouting::checkPost(array('newusercreate', 'newusername'))) {
            $newRealName = ubRouting::post('newusername');
            $newRealNameF = ubRouting::filters($newRealName, 'mres');
            $newPhone = ubRouting::post('newuserphone', 'mres');
            $newChargeCams = (ubRouting::checkPost('newuserchargecams')) ? 1 : 0;
            $date = curdatetime();

            $this->usersDb->data('regdate', $date);
            $this->usersDb->data('realname', $newRealNameF);
            $this->usersDb->data('phone', $newPhone);
            $this->usersDb->data('chargecams', $newChargeCams);
            $this->usersDb->create();

            $newId = $this->usersDb->getLastId();
            log_register('VISOR USER CREATE [' . $newId . '] NAME `' . $newRealName . '`');
            $result = $newId;
        }
        return($result);
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
     * Returns camera ID if login have camera associated
     * 
     * @param string $login
     * 
     * @return int/void
     */
    protected function getCameraIdByLogin($login) {
        $result = '';
        if (!empty($this->allCams)) {
            foreach ($this->allCams as $io => $each) {
                if ($each['login'] == $login) {
                    $result = $each['id'];
                    break;
                }
            }
        }
        return($result);
    }

    /**
     * Checks is some account already someones primary or not
     * 
     * @param string $userLogin
     * 
     * @return bool
     */
    protected function isPrimaryAccountFree($userLogin) {
        $result = true;
        if (!empty($userLogin)) {
            if (!empty($this->allUsers)) {
                foreach ($this->allUsers as $io => $each) {
                    if ($each['primarylogin'] == $userLogin) {
                        $result = false;
                        break;
                    }
                }
            }
        }
        return($result);
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
     * Returns userId by its associated primary account
     * 
     * @param string $userLogin
     * 
     * @return int/void
     */
    public function getPrimaryAccountUserId($userLogin) {
        $result = '';
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                if ($each['primarylogin'] == $userLogin) {
                    $result = $each['id'];
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
                if (!isset($this->allChannels[$userId])) {
                    $query = "DELETE from `" . self::TABLE_USERS . "` WHERE `id`='" . $userId . "';";
                    nr_query($query);
                    log_register('VISOR USER DELETE [' . $userId . ']');
                } else {
                    $result .= __('Channel have user assigned');
                }
            } else {
                $result .= __('User have some cameras associated');
            }
        } else {
            $result .= __('User not exists');
        }
        return ($result);
    }

    /**
     * Returns user primary camera controls if primary available
     * 
     * @param int $userId
     * 
     * @return string
     */
    protected function renderUserPrimaryAccount($userId) {
        $result = '';
        if (isset($this->allUsers[$userId])) {
            $userData = $this->allUsers[$userId];
            $primaryAccount = $userData['primarylogin'];
            if (!empty($primaryAccount)) {
                if (isset($this->allUserData[$primaryAccount])) {
                    $cells = wf_TableCell(__('Primary account'), '30%', 'row2');
                    $linkLabel = (@$this->allUserData[$primaryAccount]['fulladress']) ? $this->allUserData[$primaryAccount]['fulladress'] : $primaryAccount;
                    $primaLink = wf_Link(self::URL_CAMPROFILE . $primaryAccount, web_profile_icon() . ' ' . $linkLabel);
                    $cells .= wf_TableCell($primaLink);
                    $rows = wf_TableRow($cells, 'row3');

                    $cells = wf_TableCell(__('Balance'), '30%', 'row2');
                    $cells .= wf_TableCell($this->allUserData[$primaryAccount]['Cash']);
                    $rows .= wf_TableRow($cells, 'row3');

                    if ($this->altCfg['OPENPAYZ_SUPPORT']) {
                        $cells = wf_TableCell(__('Payment ID'), '30%', 'row2');
                        $cells .= wf_TableCell($this->allPaymentIDs[$primaryAccount]);
                        $rows .= wf_TableRow($cells, 'row3');
                        $result .= $rows;
                    }

                    //tariffing notice here
                    $tariffingNotice = '';
                    if ($userData['chargecams']) {
                        $tariffingNotice = wf_img_sized('skins/icon_ok.gif', '', 12) . ' ';
                        $tariffingNotice .= __('Funds for cameras will be charged from the main account at the end of the month');
                    } else {
                        $tariffingNotice = wf_img_sized('skins/icon_lock.png', '', 12) . ' ';
                        $tariffingNotice .= __('All cameras live by themselves');
                    }

                    if ($this->allUserData[$primaryAccount]['Passive'] AND $userData['chargecams']) {
                        $tariffingNotice = wf_img_sized('skins/icon_passive.gif', __('Freezed'), 12) . ' ';
                        $tariffingNotice .= __('Main account is frozen') . '. ' . __('All cameras live by themselves');
                    }


                    $cells = wf_TableCell(__('Tariffing'), '30%', 'row2');
                    $cells .= wf_TableCell($tariffingNotice);
                    $rows = wf_TableRow($cells, 'row3');
                    $result .= $rows;
                } else {
                    $cells = wf_TableCell(__('Primary account'), '30%', 'row2');
                    $cells .= wf_TableCell(__('Not exists') . ': ' . $primaryAccount);
                    $rows = wf_TableRow($cells, 'row3');
                    $result .= $rows;
                }
            } else {
                $cells = wf_TableCell(__('Tariffing'), '30%', 'row2');
                $noPrimAccLabel = wf_img_sized('skins/delete_small.png', '', 12) . ' ' . __('All cameras live by themselves') . ', ' . __('no primary account set');
                $cells .= wf_TableCell($noPrimAccLabel);
                $rows = wf_TableRow($cells, 'row3');
                $result .= $rows;
            }
        }

        return($result);
    }

    /**
     * 
     * @param int $userId
     * 
     * @return array
     */
    protected function createUserSecret($userId) {
        $result = array();
        $userId = ubRouting::filters($userId, 'int');
        if (isset($this->allUsers[$userId])) {
            if (!isset($this->allSecrets[$userId])) {
                $loginProposal = 'view' . $userId;
                $passwordProposal = zb_rand_digits(8);

                $this->secretsDb->data('visorid', $userId);
                $this->secretsDb->data('login', $loginProposal);
                $this->secretsDb->data('password', $passwordProposal);
                $this->secretsDb->create();

                log_register('VISOR USER [' . $userId . '] CREATE SECRET');
            } else {
                $result = $this->allSecrets[$userId];
            }
        }
        return($result);
    }

    /**
     * Renders visor user global NVR secrets data
     * 
     * @param int $userId
     * 
     * @return string
     */
    protected function renderUserSecrets($userId) {
        $result = '';
        $userId = ubRouting::filters($userId, 'int');
        if (isset($this->allUsers[$userId])) {
            if (isset($this->allSecrets[$userId])) {
                $secretData = $this->allSecrets[$userId];
            } else {
                $this->createUserSecret($userId);
                //update current instance data
                $this->loadSecrets();
                $secretData = $this->allSecrets[$userId];
            }

            $rows = '';
            $cells = wf_TableCell(__('DVR login'), '', 'row2');
            $cells .= wf_TableCell($secretData['login']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('DVR password'), '', 'row2');
            $cells .= wf_TableCell($secretData['password']);
            $rows .= wf_TableRow($cells, 'row3');
            $result .= $rows;
        }
        return($result);
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
        $userId = ubRouting::filters($userId, 'int');
        if (isset($this->allUsers[$userId])) {
            $userData = $this->allUsers[$userId];
            if (!empty($userData)) {
                $userCamsCount = $this->getUserCamerasCount($userId);

                $cells = wf_TableCell(__('Name'), '30%', 'row2');
                $cells .= wf_TableCell($userData['realname']);
                $rows = wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Phone'), '', 'row2');
                $cells .= wf_TableCell($userData['phone']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Charge'), '', 'row2');
                $chargeFlag = ($userData['chargecams']) ? wf_img_sized('skins/icon_active.gif', '', '12', '12') . ' ' . __('Yes') : wf_img_sized('skins/icon_inactive.gif', '', '12', '12') . ' ' . __('No');
                $cells .= wf_TableCell($chargeFlag);
                $rows .= wf_TableRow($cells, 'row3');
                //global NVR secrets
                if (!$this->isChansProtected($userId)) {
                    $rows .= $this->renderUserSecrets($userId);
                }

                //primary user account inline
                $rows .= $this->renderUserPrimaryAccount($userId);
                //additional cameras fee
                if ($userCamsCount > 0) {
                    $cells = wf_TableCell(__('Total surveillance price'), '', 'row2');
                    $cells .= wf_TableCell($this->getUserCamerasPricing($userId));
                    $rows .= wf_TableRow($cells, 'row3');
                }

                $result .= wf_TableBody($rows, '100%', 0, '');

                $result .= $this->renderUserControls($userId);

                if ($userCamsCount > 0) {
                    $result .= $this->renderCamerasContainer(self::URL_ME . self::URL_USERCAMS . $userId);
                } else {
                    $result .= $this->messages->getStyledMessage(__('User have no cameras assigned'), 'warning');
                }

                //assigned channels preview & assign forms
                $result .= $this->renderUserAssignedChannels($userId);
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('User not exists') . ' [' . $userId . ']', 'error');
        }
        return ($result);
    }

    /**
     * Renders channels available on all of DVRs that can be assigned to this user
     * 
     * @param int $userId
     * 
     * @return string
     */
    protected function renderUnassignedChannels($userId) {
        $result = '';
        if (cfr('VISOREDIT')) {
            $userId = ubRouting::filters($userId, 'int');
            $unassignedCount = 0;
            $chanControlLinks = '';
            if ($this->trassirEnabled OR $this->wolfRecorderEnabled) {
                if (!empty($this->allDvrs)) {
                    foreach ($this->allDvrs as $io => $eachDvr) {
                        if ($eachDvr['type'] == 'trassir') {
                            $dvrGate = new TrassirServer($eachDvr['ip'], $eachDvr['login'], $eachDvr['password'], $eachDvr['apikey'], $eachDvr['port'], $this->trassirDebug);
                            $dvrChannels = $dvrGate->getChannels();
                            if (!empty($dvrChannels)) {
                                foreach ($dvrChannels as $eachChanGuid => $eachChanName) {
                                    //not assigned to anyone
                                    if (!isset($this->channelUsers[$eachChanGuid])) {
                                        $chanEditLink = self::URL_ME . self::URL_CHANEDIT . $eachChanGuid . '&dvrid=' . $eachDvr['id'] . '&useridpreset=' . $userId;
                                        $chanControlLinks .= wf_Link($chanEditLink, web_edit_icon() . ' ' . $eachChanGuid . ' (' . $eachChanName . ')', false, 'ubButton') . ' ';
                                        $unassignedCount++;
                                    }
                                }
                            }
                        }

                        if ($eachDvr['type'] == 'wolfrecorder') {
                            $apiUrl = $this->getWolfRecorderApiUrl($eachDvr['id']);
                            $dvrGate = new WolfRecorder($apiUrl, $eachDvr['apikey']);
                            $dvrChannels = $dvrGate->channelsGetAll();
                            if (!empty($dvrChannels)) {
                                foreach ($dvrChannels as $eachChanId => $eachChanCameraId) {
                                    //not assigned to anyone
                                    if (!isset($this->channelUsers[$eachChanId])) {
                                        $chanEditLink = self::URL_ME . self::URL_CHANEDIT . $eachChanId . '&dvrid=' . $eachDvr['id'] . '&useridpreset=' . $userId;
                                        $chanControlLinks .= wf_Link($chanEditLink, web_edit_icon() . ' ' . $eachChanId, false, 'ubButton') . ' ';
                                        $unassignedCount++;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($unassignedCount > 0) {
                $result .= wf_tag('h2') . __('No user assigned') . wf_tag('h2', true);
                $result .= $chanControlLinks;
            }
        }
        return($result);
    }

    /**
     * Checks is channel operations protected for unpriviliged users?
     * 
     * @param int $userId
     * 
     * @return bool
     */
    protected function isChansProtected($userId) {
        $result = false;
        if (cfr('ROOT')) {
            //thats is superuser
            $result = false;
        } else {
            //is userId private?
            if (isset($this->protectedUserIds[$userId])) {
                $result = true;
            }
        }
        return($result);
    }

    /**
     * Renders list of user assigned channels with their preview and optional assign form
     * 
     * @param int $userId
     * 
     * @return string
     */
    protected function renderUserAssignedChannels($userId) {
        $result = '';
        $userId = ubRouting::filters($userId, 'int');
        if ($this->trassirEnabled OR $this->wolfRecorderEnabled) {
            if (ubRouting::checkGet('chanspreview')) {

                if (!$this->isChansProtected($userId)) {
                    $result .= wf_tag('h2', false) . __('Channels') . wf_tag('h2', true);
                    $result .= wf_tag('div', false);

                    //assigned channels list
                    if (isset($this->allChannels[$userId])) {
                        if (!empty($this->allChannels[$userId])) {
                            foreach ($this->allChannels[$userId] as $io => $eachChan) {
                                $chanDvrData = $this->allDvrs[$eachChan['dvrid']];
                                if ($chanDvrData['type'] == 'trassir') {
                                    $dvrGate = new TrassirServer($chanDvrData['ip'], $chanDvrData['login'], $chanDvrData['password'], $chanDvrData['apikey'], $chanDvrData['port'], $this->trassirDebug);

                                    $streamUrl = $dvrGate->getLiveVideoStream($eachChan['chan'], 'main', $this->chanPreviewContainer, $this->chanPreviewQuality, $this->chanPreviewFramerate, $chanDvrData['customurl']);
                                    $result .= wf_tag('div', false, 'whiteboard', 'style="width:' . $this->chanPreviewSize . ';"');
                                    $chanEditLabel = web_edit_icon() . ' ' . __('Edit') . ' ' . __('channel');
                                    if (cfr('VISOREDIT')) {
                                        $channelEditControl = wf_Link(self::URL_ME . self::URL_CHANEDIT . $eachChan['chan'] . '&dvrid=' . $eachChan['dvrid'], $chanEditLabel);
                                    } else {
                                        $channelEditControl = '';
                                    }
                                    $result .= $eachChan['chan'];
                                    $result .= wf_tag('br');
                                    $result .= $this->renderChannelPlayer($streamUrl, '90%', true);

                                    $result .= wf_tag('div', false, 'todaysig');
                                    $result .= $channelEditControl;
                                    $result .= wf_tag('div', true);

                                    $result .= wf_CleanDiv();
                                    $result .= wf_tag('div', true);
                                }

                                if ($chanDvrData['type'] == 'wolfrecorder') {
                                    $apiUrl = $this->getWolfRecorderApiUrl($chanDvrData['id']);
                                    $webUrl = ($chanDvrData['customurl']) ? $chanDvrData['customurl'] : $apiUrl;
                                    $dvrGate = new WolfRecorder($apiUrl, $chanDvrData['apikey']);
                                    $channelScreenShotReply = $dvrGate->channelsGetScreenshot($eachChan['chan']);
                                    $channelScreenshot = 'skins/noimage.jpg';
                                    if (isset($channelScreenShotReply['screenshot'])) {
                                        if ($channelScreenShotReply['screenshot']) {
                                            $channelScreenshot = $webUrl . $channelScreenShotReply['screenshot'];
                                        }
                                    }


                                    $result .= wf_tag('div', false, 'whiteboard', 'style="width:' . $this->chanPreviewSize . ';"');
                                    $chanEditLabel = web_edit_icon() . ' ' . __('Edit') . ' ' . __('channel');
                                    if (cfr('VISOREDIT')) {
                                        $channelEditControl = wf_Link(self::URL_ME . self::URL_CHANEDIT . $eachChan['chan'] . '&dvrid=' . $eachChan['dvrid'], $chanEditLabel);
                                    } else {
                                        $channelEditControl = '';
                                    }
                                    $result .= $eachChan['chan'];
                                    $result .= wf_tag('br');
                                    $result .= wf_img_sized($channelScreenshot, '', '90%');

                                    $result .= wf_tag('div', false, 'todaysig');
                                    $result .= $channelEditControl;
                                    $result .= wf_tag('div', true);

                                    $result .= wf_CleanDiv();
                                    $result .= wf_tag('div', true);
                                }
                            }
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage(__('User have no channels assigned'), 'warning');
                    }

                    $result .= wf_CleanDiv();
                    $result .= wf_tag('div', true, '');

                    //unassigned channels list
                    $result .= $this->renderUnassignedChannels($userId);

                    $result .= wf_delimiter();
                    $result .= wf_BackLink(self::URL_ME . self::URL_USERVIEW . $userId);
                } else {
                    log_register('VISOR USER [' . $userId . '] CHAN ACCESS VIOLATION');
                    show_error(__('What are your forgot there') . '?');
                }
            } else {
                if (!$this->isChansProtected($userId)) {
                    $result .= wf_delimiter();
                    $result .= wf_Link(self::URL_ME . self::URL_USERVIEW . $userId . '&chanspreview=true', web_green_led() . ' ' . __('Channels'), false, 'ubButton');
                }
            }
        }
        return($result);
    }

    /**
     * Returns user assigned cameras fee
     * 
     * @param int $userId
     * 
     * @return float
     */
    protected function getUserCamerasPricing($userId) {
        $result = 0;
        $allCameras = $this->getUserCameras($userId);
        if (!empty($allCameras)) {
            foreach ($allCameras as $io => $each) {
                $cameraLogin = $each['login'];
                if (isset($this->allUserData[$cameraLogin])) {
                    $cameraTariff = $this->allUserData[$cameraLogin]['Tariff'];
                    if (isset($this->allTariffPrices[$cameraTariff])) {
                        $result += $this->allTariffPrices[$cameraTariff];
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Renders Visor user defaults controls set
     * 
     * @param int $userId
     * 
     * @return string
     */
    protected function renderUserControls($userId) {
        $result = '';
        if (cfr('VISOREDIT')) {
            if (isset($this->allUsers[$userId])) {
                $taskB = wf_tag('div', false, 'dashtask', 'style="height:75px; width:75px;"');
                $taskE = wf_tag('div', true);

                $result .= $taskB . wf_modalAuto(wf_img('skins/ukv/useredit.png', __('Edit user')), __('Edit user'), $this->renderUserEditInterface($userId)) . __('Edit') . $taskE;
                $result .= $taskB . wf_modalAuto(wf_img('skins/icon_king_big.png', __('Primary account')), __('Primary account'), $this->renderUserPrimaryEditForm($userId)) . __('Primary') . $taskE;
                $result .= $taskB . wf_modalAuto(wf_img('skins/annihilation.gif', __('Deleting user')), __('Deleting user'), $this->renderUserDeletionForm($userId), '') . __('Delete') . $taskE;

                $result .= wf_CleanDiv();
            }
        }
        return($result);
    }

    /**
     * Renders user primary account editing interface
     * 
     * @param int $userId
     * 
     * @return string
     */
    protected function renderUserPrimaryEditForm($userId) {
        $result = '';
        if (isset($this->allUsers[$userId])) {
            $currentUserData = $this->allUsers[$userId];
            $currentPrimaryAccount = $currentUserData['primarylogin'];
            $allUserCameras = $this->getUserCameras($userId);
            $camerasTmp = array();
            $selectedCamera = '';
            $camerasTmp[''] = '-';
            if (!empty($allUserCameras)) {

                foreach ($allUserCameras as $io => $each) {
                    if ($each['login'] == $currentPrimaryAccount) {
                        $selectedCamera = $each['login'];
                    }
                    $camerasTmp[$each['login']] = @$this->allUserData[$each['login']]['fulladress'] . ' - ' . @$this->allUserData[$each['login']]['ip'];
                }
            }

            $inputs = '';
            $inputs = wf_Selector('newprimarycameralogin', $camerasTmp, __('Camera'), $selectedCamera, true);
            $inputs .= __('Or') . wf_tag('br');
            $inputs .= wf_TextInput('newprimaryuserlogin', __('Login'), $currentPrimaryAccount, true, 20);
            $inputs .= wf_HiddenInput('editprimarycamerauserid', $userId);
            $inputs .= wf_delimiter();
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Sets some account as primary for some user
     * 
     * @param int $userId
     * @param string $login
     * 
     * @return void
     */
    protected function setPrimaryAccount($userId, $login = '') {
        $userId = vf($userId, 3);
        $login = trim($login);

        if (isset($this->allUsers[$userId])) {
            $userCameras = $this->getUserCameras($userId);

            $currentPrimary = $this->allUsers[$userId]['primarylogin'];
            if ($currentPrimary != $login) {
                if ($this->isPrimaryAccountFree($login)) {
                    //setting primary account in profile
                    $this->usersDb->where('id', '=', $userId);
                    $this->usersDb->data('primarylogin', $login);
                    $this->usersDb->save();

                    // dropping all camera primary flags
                    $this->camsDb->where('visorid', '=', $userId);
                    $this->camsDb->data('primary', '0');
                    $this->camsDb->save();
                    log_register('VISOR USER [' . $userId . '] CHANGE PRIMARY (' . $login . ')');
                    $cameraId = $this->getCameraIdByLogin($login);
                    if (!empty($cameraId)) {
                        //setting camera account as primary
                        $this->camsDb->where('id', '=', $cameraId);
                        $this->camsDb->data('primary', '1');
                        $this->camsDb->save();
                    }
                } else {
                    log_register('VISOR USER [' . $userId . '] FAIL PRIMARY BUSY');
                }
            }
        } else {
            log_register('VISOR USER [' . $userId . '] FAIL PRIMARY NOUSER');
        }
    }

    /**
     * Catches primary editing request and saves changes if required
     * 
     * @return void
     */
    public function savePrimary() {
        if (wf_CheckPost(array('editprimarycamerauserid'))) {
            $userId = vf($_POST['editprimarycamerauserid'], 3);
            $newPrimaryLogin = (wf_CheckPost(array('newprimarycameralogin'))) ? $_POST['newprimarycameralogin'] : '';
            if (wf_CheckPost(array('newprimaryuserlogin')) AND !wf_CheckPost(array('newprimarycameralogin'))) {
                $newPrimaryLogin = $_POST['newprimaryuserlogin'];
            }
            $this->setPrimaryAccount($userId, $newPrimaryLogin);
        }
    }

    /**
     * user deletion form
     * 
     * @param int $userId existing user ID
     * 
     * @return string
     */
    protected function renderUserDeletionForm($userId) {
        $userId = vf($userId, 3);
        $inputs = __('Be careful, this module permanently deletes user and all data associated with it. Opportunities to raise from the dead no longer.') . ' <br>
               ' . __('To ensure that we have seen the seriousness of your intentions to enter the word сonfirm the field below.');
        $inputs .= wf_HiddenInput('userdeleteprocessing', $userId);
        $inputs .= wf_delimiter();
        $inputs .= wf_tag('input', false, '', 'type="text" name="deleteconfirmation" autocomplete="off"');
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('I really want to stop suffering User'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
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
        $columns = array('ID', 'Primary', 'User', 'Address', 'DVR', 'IP', 'Tariff', 'Active', 'Balance', 'Credit', 'Actions');
        if ($this->altCfg['DN_ONLINE_DETECT']) {
            $columns = array('ID', 'Primary', 'User', 'Address', 'DVR', 'IP', 'Tariff', 'Active', 'Online', 'Balance', 'Credit', 'Actions');
        }
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
        $dnFlag = ($this->altCfg['DN_ONLINE_DETECT']) ? true : false;

        if (isset($this->allUsers[$userId])) {
            $allUserCams = $this->getUserCameras($userId);
            if (!empty($allUserCams)) {
                foreach ($allUserCams as $io => $each) {
                    $cameraUserData = @$this->allUserData[$each['login']];
                    $data[] = $each['id'];
                    $primaryFlag = ($each['primary']) ? web_bool_led(true) . ' ' . __('Yes') : web_bool_led(false) . ' ' . __('No');
                    $data[] = $primaryFlag;
                    $visorLinkLabel = $this->iconVisorUser() . ' ' . @$this->allUsers[$each['visorid']]['realname'];
                    $visorUserLink = wf_Link(self::URL_ME . self::URL_USERVIEW . $each['visorid'], $visorLinkLabel);
                    $data[] = $visorUserLink;
                    $cameraLinkLabel = web_profile_icon() . ' ' . @$cameraUserData['fulladress'];
                    $cameraLink = wf_Link(self::URL_CAMPROFILE . $each['login'], $cameraLinkLabel);
                    $data[] = $cameraLink;
                    $cameraDvr = (!empty($each['dvrid'])) ? @$this->allDvrs[$each['dvrid']]['name'] : __('No');
                    $data[] = $cameraDvr;
                    $data[] = @$cameraUserData['ip'];
                    $data[] = @$cameraUserData['Tariff'];
                    $cameraCash = @$cameraUserData['Cash'];
                    $cameraCredit = @$cameraUserData['Credit'];
                    $cameraState = '';
                    if ($cameraCash >= '-' . $cameraCredit) {
                        $cameraState = web_bool_led(true) . ' ' . __('Yes');
                    } else {
                        $cameraState = web_bool_led(false) . ' ' . __('No');
                    }
                    $data[] = $cameraState;
                    if ($dnFlag) {
                        $onlineState = web_bool_star(false) . ' ' . __('No');
                        if (file_exists(DATA_PATH . 'dn/' . $each['login'])) {
                            $onlineState = web_bool_star(true) . ' ' . __('Yes');
                        }
                        $data[] = $onlineState;
                    }
                    $data[] = $cameraCash;
                    $data[] = $cameraCredit;
                    $actLinks = wf_Link(self::URL_ME . self::URL_CAMVIEW . $each['id'], web_edit_icon() . ' ' . __('Edit') . ' ' . __('camera'));
                    $data[] = $actLinks;
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
     * @param int size
     * 
     * @return string
     */
    public function iconVisorUser($size = '') {
        $size = vf($size, 3);
        $result = (!empty($size)) ? wf_img('skins/icon_camera_small.png') : wf_img_sized('skins/icon_camera_small.png', '', $size, $size);
        return($result);
    }

    /**
     * Renders ajax json backend for all available cameras
     * 
     * @return void
     */
    public function ajaxAllCams() {
        $json = new wf_JqDtHelper();
        $dnFlag = ($this->altCfg['DN_ONLINE_DETECT']) ? true : false;

        if (!empty($this->allCams)) {
            foreach ($this->allCams as $io => $each) {
                $cameraUserData = @$this->allUserData[$each['login']];
                $data[] = $each['id'];
                $primaryFlag = ($each['primary']) ? web_bool_led(true) . ' ' . __('Yes') : web_bool_led(false) . ' ' . __('No');
                $data[] = $primaryFlag;
                $visorLinkLabel = $this->iconVisorUser() . ' ' . @$this->allUsers[$each['visorid']]['realname'];
                $visorUserLink = wf_Link(self::URL_ME . self::URL_USERVIEW . $each['visorid'], $visorLinkLabel);
                $data[] = $visorUserLink;
                $cameraLinkLabel = web_profile_icon() . ' ' . $cameraUserData['fulladress'];
                $cameraLink = wf_Link(self::URL_CAMPROFILE . $each['login'], $cameraLinkLabel);
                $data[] = $cameraLink;
                $cameraDvr = (!empty($each['dvrid'])) ? @$this->allDvrs[$each['dvrid']]['name'] : __('No');
                $data[] = $cameraDvr;
                $data[] = @$cameraUserData['ip'];
                $data[] = @$cameraUserData['Tariff'];
                $cameraCash = @$cameraUserData['Cash'];
                $cameraCredit = @$cameraUserData['Credit'];
                $cameraState = '';
                if ($cameraCash >= '-' . $cameraCredit) {
                    $cameraState = web_bool_led(true) . ' ' . __('Yes');
                } else {
                    $cameraState = web_bool_led(false) . ' ' . __('No');
                }
                $data[] = $cameraState;
                if ($dnFlag) {
                    $onlineState = web_bool_star(false) . ' ' . __('No');
                    if (file_exists(DATA_PATH . 'dn/' . $each['login'])) {
                        $onlineState = web_bool_star(true) . ' ' . __('Yes');
                    }
                    $data[] = $onlineState;
                }
                $data[] = $cameraCash;
                $data[] = $cameraCredit;
                $actLinks = wf_Link(self::URL_ME . self::URL_CAMVIEW . $each['id'], web_edit_icon() . ' ' . __('Edit') . ' ' . __('camera'));
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Renders initial camera creation interface
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function renderCameraCreateInterface($userLogin) {
        $result = '';
        if (!empty($this->allUsers)) {
            if (cfr('VISOREDIT')) {
                $usersTmp = array();
                $usersTmp[''] = '-';
                foreach ($this->allUsers as $io => $each) {
                    $usersTmp[$each['id']] = $each['realname'];
                }
                $inputs = '';
                if ($this->altCfg['VISOR_USERSEL_SEARCHBL']) {
                    $inputs .= wf_SelectorSearchable('newcameravisorid', $usersTmp, __('The user who will be assigned a new camera'), '', false);
                } else {
                    $inputs .= wf_Selector('newcameravisorid', $usersTmp, __('The user who will be assigned a new camera'), '', false);
                }
                $inputs .= wf_delimiter();
                $inputs .= wf_HiddenInput('newcameralogin', $userLogin);
                $inputs .= wf_Submit(__('Create'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $failLabel = __('This user account is not associated with any existing Visor user or any camera account') . '. ';
                $failLabel .= __('Contact your system administrator to fix this issue') . '.';
                $result .= $this->messages->getStyledMessage($failLabel, 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('No existing Visor users avaliable, you must create one at least to assign cameras'), 'error');
        }
        return($result);
    }

    /**
     * Creates new camera account and assigns it to existing user
     * 
     * @return void
     */
    public function createCamera() {
        if (ubRouting::checkPost(array('newcameravisorid', 'newcameralogin'))) {
            $newVisorId = ubRouting::post('newcameravisorid', 'int');
            $newCameraLogin = ubRouting::post('newcameralogin');
            $newCameraLoginF = ubRouting::filters($newCameraLogin, 'mres');
            if (isset($this->allUsers[$newVisorId])) {
                if (!empty($newCameraLoginF)) {
                    $this->camsDb->data('visorid', $newVisorId);
                    $this->camsDb->data('login', $newCameraLoginF);
                    $this->camsDb->data('primary', 0);
                    $this->camsDb->create();
                    $newId = $this->camsDb->getLastId();
                    log_register('VISOR CAMERA CREATE [' . $newId . '] ASSIGN [' . $newVisorId . '] LOGIN (' . $newCameraLogin . ')');
                } else {
                    log_register('VISOR CAMERA CREATE FAIL EMPTY_LOGIN');
                }
            } else {
                log_register('VISOR CAMERA CREATE FAIL VISORID_NOT_EXISTS');
            }
        }
    }

    /**
     * Creates channel to user binding in database
     * 
     * @param int $visorId
     * @param int $dvrId
     * @param string $channelGuid
     * 
     * @return void
     */
    public function assignChannel($visorId, $dvrId, $channelGuid) {
        $visorId = ubRouting::filters($visorId, 'int');
        $dvrId = ubRouting::filters($dvrId, 'int');
        $channelGuid = ubRouting::filters($channelGuid, 'mres');
        $this->chansDb->data('visorid', $visorId);
        $this->chansDb->data('dvrid', $dvrId);
        $this->chansDb->data('chan', $channelGuid);
        $this->chansDb->create();
        log_register('VISOR USER [' . $visorId . '] ASSIGN CHAN `' . $channelGuid . '` ON DVR [' . $dvrId . ']');
    }

    /**
     * Deletes channel to user binding in database
     * 
     * @param int $visorId
     * @param int $dvrId
     * @param string $channelGuid
     * 
     * @return void
     */
    public function unassignChannel($visorId, $dvrId, $channelGuid) {
        $visorId = ubRouting::filters($visorId, 'int');
        $dvrId = ubRouting::filters($dvrId, 'int');
        $channelGuid = ubRouting::filters($channelGuid, 'mres');
        $this->chansDb->where('visorid', '=', $visorId);
        $this->chansDb->where('dvrid', '=', $dvrId);
        $this->chansDb->where('chan', '=', $channelGuid);
        $this->chansDb->delete();
        log_register('VISOR USER [' . $visorId . '] UNASSIGN CHAN `' . $channelGuid . '` ON DVR [' . $dvrId . ']');
    }

    /**
     * Renders users editing interface
     * 
     * @param int $userId
     * 
     * @return string
     */
    protected function renderUserEditInterface($userId) {
        $result = '';
        $userId = vf($userId, 3);
        if (isset($this->allUsers[$userId])) {
            $currentUserData = $this->allUsers[$userId];
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs = wf_HiddenInput('edituserid', $userId);
            $inputs .= wf_TextInput('editusername', __('Name') . $sup, $currentUserData['realname'], true, 25);
            $inputs .= wf_TextInput('edituserphone', __('Phone'), $currentUserData['phone'], true, 20, 'mobile');
            $inputs .= wf_CheckInput('edituserchargecams', __('Charge money from primary account for linked camera users if required'), true, $currentUserData['chargecams']);
            $inputs .= wf_delimiter();
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Catches and saves user editing request if required
     * 
     * @return void
     */
    public function saveUser() {
        if (ubRouting::checkPost(array('edituserid', 'editusername'))) {
            $editUserId = ubRouting::post('edituserid', 'int');
            if (isset($this->allUsers[$editUserId])) {
                $currentUserData = $this->allUsers[$editUserId];
                $where = " WHERE `id`='" . $editUserId . "'";
                $newUserName = ubRouting::post('editusername', 'mres');
                $newUserPhone = ubRouting::post('edituserphone', 'mres');
                $newCharge = (ubRouting::checkPost('edituserchargecams')) ? 1 : 0;
                $changedFlag = false;
                if ($currentUserData['realname'] != $newUserName) {
                    $changedFlag = true;
                    $this->usersDb->data('realname', $newUserName);
                    log_register('VISOR USER [' . $editUserId . '] CHANGE NAME `' . $newUserName . '`');
                }

                if ($currentUserData['phone'] != $newUserPhone) {
                    $changedFlag = true;
                    $this->usersDb->data('phone', $newUserPhone);
                    log_register('VISOR USER [' . $editUserId . '] CHANGE PHONE `' . $newUserPhone . '`');
                }

                if ($currentUserData['chargecams'] != $newCharge) {
                    $changedFlag = true;
                    $this->usersDb->data('chargecams', $newCharge);
                    log_register('VISOR USER [' . $editUserId . '] CHANGE CHARGE `' . $newUserPhone . '`');
                }

                //commiting changes to DB
                if ($changedFlag) {
                    $this->usersDb->where('id', '=', $editUserId);
                    $this->usersDb->save();
                }
            }
        }
    }

    /**
     * Returns existing camera deletion form
     * 
     * @param int $cameraId
     * 
     * @return string
     */
    protected function renderCameraDeletionForm($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        $result = '';
        if (isset($this->allCams[$cameraId])) {
            $inputs = __('To ensure that we have seen the seriousness of your intentions to enter the word сonfirm the field below.');
            $inputs .= wf_delimiter();
            $inputs .= wf_tag('input', false, '', 'type="text" name="deleteconfirmation" autocomplete="off"');
            $inputs .= wf_tag('br');
            $inputs .= wf_HiddenInput('cameradeleteprocessing', $cameraId);
            $inputs .= wf_tag('br');
            $inputs .= wf_Submit(__('Delete camera'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Deletes existing camera from database
     * 
     * @param int $cameraId
     * 
     * @return void/string on error
     */
    public function deleteCamera($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        $result = '';
        if (isset($this->allCams[$cameraId])) {
            $cameraData = $this->allCams[$cameraId];
            $this->camsDb->where('id', '=', $cameraId);
            $this->camsDb->delete();
            log_register('VISOR CAMERA DELETE [' . $cameraId . '] ASSIGNED [' . $cameraData['visorid'] . '] LOGIN (' . $cameraData['login'] . ')');
        } else {
            $result .= __('Something went wrong') . ': ' . __('No such camera exists') . ' [' . $cameraId . ']';
        }
        return($result);
    }

    /**
     * Renders camera profile with editing forms
     * 
     * @param int $cameraId
     * 
     * @return string 
     */
    public function renderCameraForm($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        $result = '';
        if (isset($this->allCams[$cameraId])) {
            $cameraData = $this->allCams[$cameraId];
            $camProfile = $cameraData['login'];
            $usersTmp = array();
            $dvrTmp = array('' => '-');

            if (!empty($this->allUsers)) {
                foreach ($this->allUsers as $io => $each) {
                    $usersTmp[$each['id']] = $each['realname'];
                }
            }

            if (!empty($this->allDvrs)) {
                foreach ($this->allDvrs as $io => $each) {
                    $dvrFull = false;
                    $dvrLabel = $each['ip'];
                    if ($each['camlimit'] > 0) {
                        $dvrCamsNow = $this->getDvrCameraCount($each['id']);
                        if ($dvrCamsNow >= $each['camlimit']) {
                            $dvrFull = true;
                        }
                    }

                    if (!empty($each['name'])) {
                        $dvrLabel .= ' - ' . $each['name'];
                    }


                    $dvrLabel .= ' (' . $dvrCamsNow . '/' . $each['camlimit'] . ')';
                    if ($dvrFull) {
                        $dvrLabel .= ' ' . __('full') . '!';
                    }
                    $dvrTmp[$each['id']] = $dvrLabel;
                }
            }

            //is camera internet user exists?
            if (isset($this->allUserData[$camProfile])) {
                $camProfileData = $this->allUserData[$camProfile];

                $cells = wf_TableCell(__('User'), '30%', 'row2');
                $visorUserLink = wf_Link(self::URL_ME . self::URL_USERVIEW . $cameraData['visorid'], $this->iconVisorUser('12') . ' ' . @$this->allUsers[$cameraData['visorid']]['realname']);
                $cells .= wf_TableCell($visorUserLink);
                $rows = wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Address'), '30%', 'row2');
                $camProfileLink = wf_Link(self::URL_CAMPROFILE . $camProfile, web_profile_icon() . ' ' . @$camProfileData['fulladress']);
                $cells .= wf_TableCell($camProfileLink);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('IP'), '30%', 'row2');
                $cells .= wf_TableCell($camProfileData['ip']);
                $rows .= wf_TableRow($cells, 'row3');
                $cells = wf_TableCell(__('Tariff'), '30%', 'row2');
                $cells .= wf_TableCell($camProfileData['Tariff']);
                $rows .= wf_TableRow($cells, 'row3');
                $cameraState = '';
                $cameraCash = $camProfileData['Cash'];
                $cameraCredit = $camProfileData['Credit'];
                if ($cameraCash >= '-' . $cameraCredit) {
                    $cameraState = wf_img_sized('skins/icon_active.gif', '', '12', '12') . ' ' . __('Yes');
                } else {
                    $cameraState = wf_img_sized('skins/icon_inactive.gif', '', '12', '12') . ' ' . __('No');
                }
                $cells = wf_TableCell(__('Active'), '30%', 'row2');
                $cells .= wf_TableCell($cameraState);
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Balance'), '30%', 'row2');
                $cells .= wf_TableCell($cameraCash);
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Credit'), '30%', 'row2');
                $cells .= wf_TableCell($cameraCredit);
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Camera login'), '30%', 'row2');
                $cells .= wf_TableCell($cameraData['camlogin']);
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Camera password'), '30%', 'row2');
                $cells .= wf_TableCell($cameraData['campassword']);
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('Port'), '30%', 'row2');
                $cells .= wf_TableCell($cameraData['port']);
                $rows .= wf_TableRow($cells, 'row3');

                if (!empty($this->allDvrs[$cameraData['dvrid']]['name'])) {
                    $curCamDvrLabel = $this->allDvrs[$cameraData['dvrid']]['ip'] . ' - ' . $this->allDvrs[$cameraData['dvrid']]['name'];
                } else {
                    $curCamDvrLabel = @$this->allDvrs[$cameraData['dvrid']]['ip'];
                }
                $cells = wf_TableCell(__('DVR'), '30%', 'row2');
                $cells .= wf_TableCell($curCamDvrLabel);
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('DVR login'), '30%', 'row2');
                $cells .= wf_TableCell($cameraData['dvrlogin']);
                $rows .= wf_TableRow($cells, 'row3');

                $cells = wf_TableCell(__('DVR password'), '30%', 'row2');
                $cells .= wf_TableCell($cameraData['dvrpassword']);
                $rows .= wf_TableRow($cells, 'row3');

                $result .= wf_TableBody($rows, '100%', 0);
                $result .= wf_tag('br');

                $inputs = '';
                $inputs .= wf_HiddenInput('editcameraid', $cameraId);
                $inputs .= wf_Selector('editvisorid', $usersTmp, __('User'), $cameraData['visorid'], true);
                $loginPreset = (!empty($cameraData['camlogin'])) ? $cameraData['camlogin'] : 'admin';
                $inputs .= wf_TextInput('editcamlogin', __('Camera login'), $loginPreset, true, 15);
                $inputs .= wf_TextInput('editcampassword', __('Camera password'), $cameraData['campassword'], true, 15);
                $portPreset = ($cameraData['port'] != 0) ? $cameraData['port'] : 80;
                $inputs .= wf_TextInput('editport', __('Port'), $portPreset, true, 5);
                $inputs .= wf_tag('br');
                $inputs .= wf_Selector('editdvrid', $dvrTmp, __('DVR'), $cameraData['dvrid'], true);
                $inputs .= wf_TextInput('editdvrlogin', __('DVR login'), $cameraData['dvrlogin'], true, 15);
                $inputs .= wf_TextInput('editdvrpassword', __('DVR password'), $cameraData['dvrpassword'], true, 15);
                $inputs .= wf_tag('br');
                $inputs .= wf_Submit(__('Save'));

                $cameraEditForm = wf_Form('', 'POST', $inputs, 'glamour');

                $result .= wf_Link(self::URL_ME . self::URL_USERVIEW . $cameraData['visorid'], $this->iconVisorUser() . ' ' . __('Back to user profile'), false, 'ubButton');
                if (cfr('VISOREDIT')) {
                    $result .= wf_modalAuto(web_edit_icon() . ' ' . __('Edit'), __('Edit'), $cameraEditForm, 'ubButton');
                    $result .= wf_modalAuto(web_delete_icon() . ' ' . __('Delete'), __('Delete'), $this->renderCameraDeletionForm($cameraId), 'ubButton');
                    if ($this->wolfRecorderEnabled) {
                        $result .= $this->renderWolfRecorderCameraControls($cameraId);
                    }
                    if ($this->trassirEnabled) {
                        $result .= $this->renderTrassirCameraControls($cameraId);
                    }
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('User not exists') . ' (' . $cameraData['login'] . ')', 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('No such camera exists') . ' [' . $cameraId . ']', 'error');
        }
        return($result);
    }

    /**
     * Returns popular and most frequently used camera models for some protocol/vendor
     * 
     * @param string $protocol
     * 
     * @return array
     */
    protected function getPopularCameraModels($protocol) {
        $result = array();
        if (file_exists(self::PATH_MODELS . $protocol)) {
            $allModels = rcms_scandir(self::PATH_MODELS . $protocol . '/');
            if (!empty($allModels)) {
                foreach ($allModels as $io => $each) {
                    $result[$each] = $each . ' *';
                }
            }
        }
        return($result);
    }

    /**
     * Returns camera "model mismatch" warning editing form. Also catches change requests.
     * 
     * @param int $cameraId
     * @param int $curState
     * 
     * @return string
     */
    protected function renderTrassirCameraMismatchForm($cameraId, $curState) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCams[$cameraId])) {
            $cameraData = $this->allCams[$cameraId];
            $cameraDvrId = $cameraData['dvrid'];
            $dvrData = $this->allDvrs[$cameraDvrId];
            $cameraUserData = $this->allUserData[$cameraData['login']];
            $cameraIp = $cameraUserData['ip'];

            //change model mismatch warning request catched
            if (ubRouting::checkPost('disablemodelmismatchcameraid')) {
                $newDisableState = (ubRouting::checkPost('modelmismatchdisabled')) ? 1 : 0; //need int as param
                $trassirGate = new TrassirServer($dvrData['ip'], $dvrData['login'], $dvrData['password'], $dvrData['apikey'], $dvrData['port'], $this->trassirDebug);
                $trassirGate->setModelMismatch($cameraIp, $newDisableState);
                log_register('VISOR CAMERA [' . $cameraId . '] MMIS `' . $newDisableState . '` ON DVR [' . $cameraDvrId . '] AS `' . $cameraIp . '`');
                ubRouting::nav(self::URL_ME . '&' . self::URL_CAMVIEW . $cameraId); //preventing form data duplication
            }


            if ($curState == 1 OR $curState == 0) {
                $inputs = wf_HiddenInput('disablemodelmismatchcameraid', $cameraId);
                $inputs .= wf_CheckInput('modelmismatchdisabled', __('Model mismatch warning disabled on this DVR'), false, $curState);
                $inputs .= wf_Submit(__('Save'));
                $result .= wf_tag('br');
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                //may be caused by wrong camera IP or NVR connection issues
                $result .= $this->messages->getStyledMessage(__('Cant detect mismatch warning state for camera') . ' ' . $cameraIp, 'warning'); // Awesome Oo
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Camera') . ' ' . __('Not exists') . ' [' . $cameraId . ']', 'error');
        }
        return($result);
    }

    /**
     * Rders camera DVR registering form if its not registered yet
     * 
     * @param int $cameraId
     * 
     * @return string
     */
    protected function renderTrassirCameraCreateForm($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCams[$cameraId])) {
            $cameraData = $this->allCams[$cameraId];
            $cameraDvrId = $cameraData['dvrid'];
            $dvrData = $this->allDvrs[$cameraDvrId];
            $cameraUserData = $this->allUserData[$cameraData['login']];
            $cameraIp = $cameraUserData['ip'];

            $trassir = new TrassirServer($dvrData['ip'], $dvrData['login'], $dvrData['password'], $dvrData['apikey'], $dvrData['port'], $this->trassirDebug);
            $serverHealth = $trassir->getHealth();
            //dummy connection check
            if (!empty($serverHealth)) {
                $result .= $this->messages->getStyledMessage(__('DVR') . ' ' . $dvrData['name'] . ': ' . __('Connected'), 'success');
                $allCameraIps = $trassir->getAllCameraIps();
                if (isset($allCameraIps[$cameraIp])) {
                    $successLabel = __('Camera') . ': ' . __('Registered') . ' ' . __('On') . ' ' . __('DVR') . ' ' . $dvrData['name'];
                    $result .= $this->messages->getStyledMessage($successLabel, 'success');
                    //Model mismatch disabling interface
                    $curMissmatchState = $trassir->getModelMismatch($cameraIp);
                    $result .= $this->renderTrassirCameraMismatchForm($cameraId, $curMissmatchState);
                } else {
                    //here registering form.. MB...
                    $result .= $this->messages->getStyledMessage(__('Camera is not registered at') . ' ' . $dvrData['name'], 'warning');
                    $protoTmp = $trassir->getCameraProtocols();
                    if (!empty($protoTmp)) {
                        $supportedCameraProtocols = array('TRASSIR' => 'TRASSIR', 'Hikvision' => 'Hikvision'); //popular protocols
                        //Protocols received from DVR
                        foreach ($protoTmp as $io => $each) {
                            $supportedCameraProtocols[$each] = $each;
                        }

                        //camera registering form processing
                        if (!ubRouting::checkPost(array('newtrassircamera', 'newtrassircameraprotocol', 'newtrassircameramodel'))) {
                            $supportedCameraModels = array();

                            $newCamProtocol = (ubRouting::checkPost('newtrassircameraprotocol')) ? ubRouting::post('newtrassircameraprotocol') : '';

                            $inputs = wf_HiddenInput('newtrassircamera', 'true');
                            if (!empty($newCamProtocol)) {
                                //getting protocol supported models
                                $supportedCameraModelsTmp = $trassir->getCameraModels($newCamProtocol);
                                //Protocol is supported on NVR
                                if (!empty($supportedCameraModelsTmp)) {
                                    $supportedCameraModels = $this->getPopularCameraModels($newCamProtocol); //frequently used models
                                }

                                $supportedCameraModels += $supportedCameraModelsTmp;

                                $inputs .= $newCamProtocol . ' ';
                                $inputs .= wf_HiddenInput('newtrassircameraprotocol', $newCamProtocol);
                                $inputs .= wf_Selector('newtrassircameramodel', $supportedCameraModels, __('Model'), '', false) . ' ';
                                $inputs .= wf_Submit(__('Create camera') . ' ' . __('on') . ' ' . __('DVR') . ' ' . $dvrData['name']);
                            } else {
                                $inputs .= wf_Selector('newtrassircameraprotocol', $supportedCameraProtocols, __('Device vendor'), '', false) . ' ';
                                $inputs .= wf_Submit(__('Continue'));
                            }
                            $result .= wf_delimiter();
                            $result .= wf_Form('', 'POST', $inputs, 'glamour');
                        } else {
                            //or just push that camera to DVR
                            $trassir->createCamera(ubRouting::post('newtrassircameraprotocol'), ubRouting::post('newtrassircameramodel'), $cameraIp, $cameraData['port'], $cameraData['camlogin'], $cameraData['campassword']);
                            log_register('VISOR CAMERA [' . $cameraId . '] CONNECTED DVR [' . $cameraDvrId . '] AS `' . $cameraIp . '`');
                            ubRouting::nav(self::URL_ME . '&' . self::URL_CAMVIEW . $cameraId); //preventing form data duplication
                        }
                    }
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('DVR connection error') . ' [' . $dvrData['id'] . ']', 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Camera') . ' ' . __('Not exists') . ' [' . $cameraId . ']', 'error');
        }
        return($result);
    }

    /**
     * Renders camera DVR registering form if its not registered yet
     * 
     * @param int $cameraId
     * 
     * @return string
     */
    protected function renderWolfRecorderCameraCreateForm($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCams[$cameraId])) {
            $cameraData = $this->allCams[$cameraId];
            $cameraDvrId = $cameraData['dvrid'];
            $dvrData = $this->allDvrs[$cameraDvrId];
            $cameraUserData = $this->allUserData[$cameraData['login']];
            $cameraIp = $cameraUserData['ip'];
            $apiUrl = $this->getWolfRecorderApiUrl($dvrData['id']);
            $wolfRecorder = new WolfRecorder($apiUrl, $dvrData['apikey']);
            $isCameraRegistered = $wolfRecorder->camerasIsRegistered($cameraIp);

            //dummy connection check
            if ($wolfRecorder->noError($isCameraRegistered)) {
                $result .= $this->messages->getStyledMessage(__('DVR') . ' ' . $dvrData['name'] . ': ' . __('Connected'), 'success');
                if ($isCameraRegistered['registered']) {
                    $wrCameraId = '';
                    if (isset($isCameraRegistered['id'])) {
                        $wrCameraId = $isCameraRegistered['id'];
                    }
                    //nothing to do here
                    $successLabel = __('Camera') . ': ' . __('Registered') . ' ' . __('On') . ' ' . __('DVR') . ' ' . $dvrData['name'] . ' ' . __('as') . ' [' . $wrCameraId . ']';
                    $result .= $this->messages->getStyledMessage($successLabel, 'success');
                    //excepting recorder process check
                    if ($wrCameraId) {
                        $wrCameraId = $isCameraRegistered['id'];
                        $recorderIsRunning = $wolfRecorder->recordersIsRunning($wrCameraId);
                        if ($wolfRecorder->noError($recorderIsRunning)) {
                            $recState = ($recorderIsRunning['running']) ? true : false;
                            if ($recState) {
                                $result .= $this->messages->getStyledMessage(__('Recording now is running'), 'success');
                            } else {
                                $result .= $this->messages->getStyledMessage(__('Recording is not running'), 'warning');
                            }
                        }
                    }
                } else {
                    //here registering form.. MB...
                    $result .= $this->messages->getStyledMessage(__('Camera is not registered at') . ' ' . $dvrData['name'], 'warning');
                    $modelsTmp = $wolfRecorder->modelsGetAll();

                    if (!empty($modelsTmp)) {
                        $supportedCameraModels = array();
                        //models received from DVR
                        foreach ($modelsTmp as $io => $each) {
                            $supportedCameraModels[$each['id']] = $each['modelname'];
                        }

                        //camera registering form processing
                        if (!ubRouting::checkPost(array('newwolfrecordercamera', 'newwolfrecordercameramodel'))) {
                            $storagesTmp = $wolfRecorder->storagesGetAll();
                            if (!empty($storagesTmp)) {
                                $availableStorages = array(0 => __('Auto'));
                                foreach ($storagesTmp as $io => $each) {
                                    $availableStorages[$each['id']] = __($each['name']);
                                }
                                $inputs = wf_HiddenInput('newwolfrecordercamera', 'true');
                                $inputs .= wf_Selector('newwolfrecordercameramodel', $supportedCameraModels, __('Model'), '', false) . ' ';
                                $inputs .= wf_Selector('newwolfrecordercamerastorage', $availableStorages, __('Storage'), '', false) . ' ';
                                $inputs .= wf_Submit(__('Create camera') . ' ' . __('on') . ' ' . __('DVR') . ' ' . $dvrData['name']);
                                $result .= wf_delimiter();
                                $result .= wf_Form('', 'POST', $inputs, 'glamour');
                            } else {
                                $result .= $this->messages->getStyledMessage(__('Storages is not available'), 'error');
                            }
                        } else {
                            //or just push that camera to DVR
                            $newCamStorageId = (ubRouting::checkPost('newwolfrecordercamerastorage')) ? ubRouting::post('newwolfrecordercamerastorage', 'int') : 0; //explict storage?
                            $newCamAct = 1; //enabled by default
                            $newCamDesc = zb_UserGetFullAddress($cameraData['login']); //address as default decription
                            $wolfRecorder->camerasCreate(ubRouting::post('newwolfrecordercameramodel'), $cameraIp, $cameraData['camlogin'], $cameraData['campassword'], $newCamAct, $newCamStorageId, $newCamDesc);
                            log_register('VISOR CAMERA [' . $cameraId . '] CONNECTED DVR [' . $cameraDvrId . '] AS `' . $cameraIp . '`');
                            ubRouting::nav(self::URL_ME . '&' . self::URL_CAMVIEW . $cameraId); //preventing form data duplication
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Models') . ' ' . __('is empty'), 'error');
                    }
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('DVR connection error') . ' [' . $dvrData['id'] . ']', 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Camera') . ' ' . __('Not exists') . ' [' . $cameraId . ']', 'error');
        }
        return($result);
    }

    /**
     * Renders IP device controls if camera is served by trassir based DVR
     * 
     * @param int $cameraId
     * 
     * @return string
     */
    protected function renderTrassirCameraControls($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCams[$cameraId])) {
            $cameraData = $this->allCams[$cameraId];
            $cameraDvrId = $cameraData['dvrid'];
            //DVR assigned
            if ($cameraDvrId) {
                if (isset($this->allDvrs[$cameraDvrId])) {
                    $dvrData = $this->allDvrs[$cameraDvrId];
                    //Here we go! That DVR can be managable
                    if ($dvrData['type'] == 'trassir') {
                        if (!empty($cameraData['camlogin'])) {
                            if (!empty($cameraData['campassword'])) {
                                if (!empty($cameraData['port'])) {
                                    if (isset($this->allUserData[$cameraData['login']])) {
                                        //DVD configuration is acceptable?
                                        if (!empty($dvrData['login']) AND !empty($dvrData['password']) AND !empty($dvrData['port']) AND !empty($dvrData['apikey'])) {
                                            //Camera looks like it may be registgered on DVR
                                            $result .= $this->renderTrassirCameraCreateForm($cameraId);
                                        } else {
                                            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('DVR') . ' ' . __('Configuration') . ' ' . __('is empty'), 'error');
                                        }
                                    } else {
                                        $result .= $this->messages->getStyledMessage(__('Camera') . ' ' . __('User') . ' ' . __('Not exists') . ' (' . $cameraData['login'] . ')', 'error');
                                    }
                                } else {
                                    $result .= $this->messages->getStyledMessage(__('Camera') . ' ' . __('Port') . ' ' . __('is empty'), 'error');
                                }
                            } else {
                                $result .= $this->messages->getStyledMessage(__('Camera password') . ' ' . __('is empty'), 'error');
                            }
                        } else {
                            $result .= $this->messages->getStyledMessage(__('Camera login') . ' ' . __('is empty'), 'error');
                        }
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('DVR') . ' ' . __('Not exists') . ' [' . $cameraDvrId . ']', 'error');
                }
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Camera') . ' ' . __('Not exists') . ' [' . $cameraId . ']', 'error');
        }

        return($result);
    }

    /**
     * Renders IP device controls if camera is served by WolfRecorder NVR
     * 
     * @param int $cameraId
     * 
     * @return string
     */
    protected function renderWolfRecorderCameraControls($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCams[$cameraId])) {
            $cameraData = $this->allCams[$cameraId];
            $cameraDvrId = $cameraData['dvrid'];
            //DVR assigned
            if ($cameraDvrId) {
                if (isset($this->allDvrs[$cameraDvrId])) {
                    $dvrData = $this->allDvrs[$cameraDvrId];
                    //Here we go! That DVR can be managable
                    if ($dvrData['type'] == 'wolfrecorder') {
                        if (!empty($cameraData['camlogin'])) {
                            if (!empty($cameraData['campassword'])) {
                                if (!empty($cameraData['port'])) {
                                    if (isset($this->allUserData[$cameraData['login']])) {
                                        //DVD configuration is acceptable?
                                        if (!empty($dvrData['login']) AND !empty($dvrData['password']) AND !empty($dvrData['port']) AND !empty($dvrData['apikey'])) {
                                            //Camera looks like it may be registgered on DVR
                                            $result .= $this->renderWolfRecorderCameraCreateForm($cameraId);
                                        } else {
                                            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('DVR') . ' ' . __('Configuration') . ' ' . __('is empty'), 'error');
                                        }
                                    } else {
                                        $result .= $this->messages->getStyledMessage(__('Camera') . ' ' . __('User') . ' ' . __('Not exists') . ' (' . $cameraData['login'] . ')', 'error');
                                    }
                                } else {
                                    $result .= $this->messages->getStyledMessage(__('Camera') . ' ' . __('Port') . ' ' . __('is empty'), 'error');
                                }
                            } else {
                                $result .= $this->messages->getStyledMessage(__('Camera password') . ' ' . __('is empty'), 'error');
                            }
                        } else {
                            $result .= $this->messages->getStyledMessage(__('Camera login') . ' ' . __('is empty'), 'error');
                        }
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('DVR') . ' ' . __('Not exists') . ' [' . $cameraDvrId . ']', 'error');
                }
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Camera') . ' ' . __('Not exists') . ' [' . $cameraId . ']', 'error');
        }

        return($result);
    }

    /**
     * Catches camera editing request and saves data if required
     * 
     * @return void
     */
    public function saveCamera() {
        if (wf_CheckPost(array('editcameraid'))) {
            $cameraId = ubRouting::post('editcameraid', 'int');
            if (isset($this->allCams[$cameraId])) {
                $changedFlag = false;
                $cameraData = $this->allCams[$cameraId];
                $where = " WHERE `id`='" . $cameraId . "'";

                $newVisorId = ubRouting::post('editvisorid', 'int');
                $newCamLogin = ubRouting::post('editcamlogin', 'mres');
                $newCamPassword = ubRouting::post('editcampassword', 'mres');
                $newPort = ubRouting::post('editport', 'int');
                $newDvrId = ubRouting::post('editdvrid', 'int');
                $newDvrLogin = ubRouting::post('editdvrlogin', 'mres');
                $newDvrPassword = ubRouting::post('editdvrpassword', 'mres');

                if ($newVisorId != $cameraData['visorid']) {
                    $changedFlag = true;
                    $this->camsDb->data('visorid', $newVisorId);
                    log_register('VISOR CAMERA [' . $cameraId . '] CHANGE ASSIGN [' . $newVisorId . ']');
                }

                if ($newCamLogin != $cameraData['camlogin']) {
                    $changedFlag = true;
                    $this->camsDb->data('camlogin', $newCamLogin);
                    log_register('VISOR CAMERA [' . $cameraId . '] CHANGE LOGIN `' . $newCamLogin . '`');
                }

                if ($newCamPassword != $cameraData['campassword']) {
                    $changedFlag = true;
                    $this->camsDb->data('campassword', $newCamPassword);
                    log_register('VISOR CAMERA [' . $cameraId . '] CHANGE PASSWORD `' . $newCamPassword . '`');
                }

                if ($newPort != $cameraData['port']) {
                    $changedFlag = true;
                    $this->camsDb->data('port', $newPort);
                    log_register('VISOR CAMERA [' . $cameraId . '] CHANGE PORT `' . $newPort . '`');
                }

                if ($newDvrId != $cameraData['dvrid']) {
                    $changedFlag = true;
                    $this->camsDb->data('dvrid', $newDvrId);
                    if (!empty($newDvrId)) {
                        log_register('VISOR CAMERA [' . $cameraId . '] CHANGE DVR [' . $newDvrId . ']');
                    } else {
                        log_register('VISOR CAMERA [' . $cameraId . '] UNSET DVR');
                    }
                }

                if ($newDvrLogin != $cameraData['dvrlogin']) {
                    $changedFlag = true;
                    $this->camsDb->data('dvrlogin', $newDvrLogin);
                    log_register('VISOR CAMERA [' . $cameraId . '] CHANGE DVRLOGIN `' . $newDvrLogin . '`');
                }

                if ($newDvrLogin != $cameraData['dvrpassword']) {
                    $changedFlag = true;
                    $this->camsDb->data('dvrpassword', $newDvrPassword);
                    log_register('VISOR CAMERA [' . $cameraId . '] CHANGE DVRPASSWORD `' . $newDvrPassword . '`');
                }

                //commiting changes to DB
                if ($changedFlag) {
                    $this->camsDb->where('id', '=', $cameraId);
                    $this->camsDb->save();
                }
            }
        }
    }

    /**
     * Renders DVR creation form
     * 
     * @return string
     */
    protected function renderDVRsCreateForm() {
        $result = '';
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

        $inputs = wf_HiddenInput('newdvr', 'true');
        $inputs .= wf_TextInput('newdvrname', __('Name'), '', true, 15);
        $inputs .= wf_Selector('newdvrtype', $this->dvrTypes, __('Type'), '', true);
        $inputs .= wf_TextInput('newdvrip', __('IP') . $sup, '', true, 15, 'ip');
        $inputs .= wf_TextInput('newdvrport', __('Port'), '', true, 5, 'digits');
        $inputs .= wf_TextInput('newdvrlogin', __('Login'), '', true, 20);
        $inputs .= wf_TextInput('newdvrpassword', __('Password'), '', true, 20);
        $inputs .= wf_TextInput('newdvrapiurl', __('API URL'), '', true, 20, 'url');
        $inputs .= wf_TextInput('newdvrapikey', __('API key'), '', true, 20);
        $inputs .= wf_TextInput('newdvrcamlimit', __('Cameras limit'), '0', true, 3, 'digits');
        $inputs .= wf_TextInput('newdvrcustomurl', __('Custom preview URL'), '', true, 20);
        $inputs .= wf_Submit(__('Create'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Catches new DVR creation request/performs new DVR registering
     * 
     * @return void
     */
    public function createDVR() {
        if (ubRouting::checkPost(array('newdvr', 'newdvrip'))) {
            $ip = ubRouting::post('newdvrip');
            $ip_f = ubRouting::filters($ip, 'mres');
            $port = ubRouting::post('newdvrport', 'int');
            $login = ubRouting::post('newdvrlogin', 'mres');
            $password = ubRouting::post('newdvrpassword', 'mres');
            $name = ubRouting::post('newdvrname', 'mres');
            $type = ubRouting::post('newdvrtype', 'mres');
            $apiurl = ubRouting::post('newdvrapiurl', 'mres');
            $apikey = ubRouting::post('newdvrapikey', 'mres');
            $camlimit = ubRouting::post('newdvrcamlimit', 'int');
            $customurl = ubRouting::post('newdvrcustomurl', 'mres');

            $this->dvrsDb->data('ip', $ip_f);
            $this->dvrsDb->data('port', $port);
            $this->dvrsDb->data('login', $login);
            $this->dvrsDb->data('password', $password);
            $this->dvrsDb->data('apiurl', $apiurl);
            $this->dvrsDb->data('apikey', $apikey);
            $this->dvrsDb->data('name', $name);
            $this->dvrsDb->data('type', $type);
            $this->dvrsDb->data('camlimit', $camlimit);
            $this->dvrsDb->data('customurl', $customurl);
            $this->dvrsDb->create();

            $newId = $this->dvrsDb->getLastId();

            log_register('VISOR DVR CREATE [' . $newId . '] IP `' . $ip . '`');
        }
    }

    /**
     * Renders DVR editing form
     * 
     * @param int $dvrId
     * 
     * @return string
     */
    protected function renderDVREditForm($dvrId) {
        $dvrId = vf($dvrId, 3);
        $result = '';
        if (isset($this->allDvrs[$dvrId])) {
            $dvrData = $this->allDvrs[$dvrId];
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

            $inputs = wf_HiddenInput('editdvrid', $dvrId);
            $inputs .= wf_TextInput('editdvrname', __('Name'), $dvrData['name'], true, 15);
            $inputs .= wf_Selector('editdvrtype', $this->dvrTypes, __('Type'), $dvrData['type'], true);
            $inputs .= wf_TextInput('editdvrip', __('IP') . $sup, $dvrData['ip'], true, 15, 'ip');
            $inputs .= wf_TextInput('editdvrport', __('Port'), $dvrData['port'], true, 5, 'digits');
            $inputs .= wf_TextInput('editdvrlogin', __('Login'), $dvrData['login'], true, 12);
            $inputs .= wf_TextInput('editdvrpassword', __('Password'), $dvrData['password'], true, 12);
            $inputs .= wf_TextInput('editdvrapiurl', __('API URL'), $dvrData['apiurl'], true, 20, 'url');
            $inputs .= wf_TextInput('editdvrapikey', __('API key'), $dvrData['apikey'], true, 20);
            $inputs .= wf_TextInput('editdvrcamlimit', __('Cameras limit'), $dvrData['camlimit'], true, 20);
            $inputs .= wf_TextInput('editdvrcustomurl', __('Custom preview URL'), $dvrData['customurl'], true, 20);
            $inputs .= wf_tag('br');
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('No such DVR exists'), 'error');
        }
        return($result);
    }

    /**
     * Catches DVR modification request and saves new data to database if it was changed
     * 
     * @return void
     */
    public function saveDVR() {
        if (ubRouting::checkPost(array('editdvrid', 'editdvrip'))) {
            $dvrId = ubRouting::post('editdvrid', 'int');

            if (isset($this->allDvrs[$dvrId])) {
                $saveRequired = false;
                $dvrData = $this->allDvrs[$dvrId];
                $where = " WHERE `id`='" . $dvrId . "'";
                $newIp = ubRouting::post('editdvrip', 'mres');
                $newPort = ubRouting::post('editdvrport', 'int');
                $newLogin = ubRouting::post('editdvrlogin', 'mres');
                $newPassword = ubRouting::post('editdvrpassword', 'mres');
                $newName = ubRouting::post('editdvrname', 'mres');
                $newType = ubRouting::post('editdvrtype', 'mres');
                $newApiurl = ubRouting::post('editdvrapiurl', 'mres');
                $newApikey = ubRouting::post('editdvrapikey', 'mres');
                $newCamlimit = ubRouting::post('editdvrcamlimit', 'int');
                $newCustomUrl = ubRouting::post('editdvrcustomurl', 'mres');

                if ($dvrData['ip'] != $newIp) {
                    $saveRequired = true;
                    $this->dvrsDb->data('ip', $newIp);
                    log_register('VISOR DVR [' . $dvrId . '] CHANGE IP `' . $newIp . '`');
                }

                if ($dvrData['port'] != $newPort) {
                    $saveRequired = true;
                    $this->dvrsDb->data('port', $newPort);
                    log_register('VISOR DVR [' . $dvrId . '] CHANGE PORT `' . $newPort . '`');
                }

                if ($dvrData['login'] != $newLogin) {
                    $saveRequired = true;
                    $this->dvrsDb->data('login', $newLogin);
                    log_register('VISOR DVR [' . $dvrId . '] CHANGE LOGIN `' . $newLogin . '`');
                }

                if ($dvrData['password'] != $newPassword) {
                    $saveRequired = true;
                    $this->dvrsDb->data('password', $newPassword);
                    log_register('VISOR DVR [' . $dvrId . '] CHANGE PASSWORD `' . $newPassword . '`');
                }

                if ($dvrData['name'] != $newName) {
                    $saveRequired = true;
                    $this->dvrsDb->data('name', $newName);
                    log_register('VISOR DVR [' . $dvrId . '] CHANGE NAME `' . $newName . '`');
                }

                if ($dvrData['type'] != $newType) {
                    $saveRequired = true;
                    $this->dvrsDb->data('type', $newType);
                    log_register('VISOR DVR [' . $dvrId . '] CHANGE TYPE `' . $newType . '`');
                }
                if ($dvrData['apiurl'] != $newApiurl) {
                    $saveRequired = true;
                    $this->dvrsDb->data('apiurl', $newApiurl);
                    log_register('VISOR DVR [' . $dvrId . '] CHANGE APIURL `' . $newApiurl . '`');
                }

                if ($dvrData['apikey'] != $newApikey) {
                    $saveRequired = true;
                    $this->dvrsDb->data('apikey', $newApikey);
                    log_register('VISOR DVR [' . $dvrId . '] CHANGE APIKEY `' . $newApikey . '`');
                }

                if ($dvrData['camlimit'] != $newCamlimit) {
                    $saveRequired = true;
                    $this->dvrsDb->data('camlimit', $newCamlimit);
                    log_register('VISOR DVR [' . $dvrId . '] CHANGE CAMLIMIT `' . $newCamlimit . '`');
                }

                if ($dvrData['customurl'] != $newCustomUrl) {
                    $saveRequired = true;
                    $this->dvrsDb->data('customurl', $newCustomUrl);
                    log_register('VISOR DVR [' . $dvrId . '] CHANGE CUSTOMURL `' . $newCustomUrl . '`');
                }

                if ($saveRequired) {
                    $this->dvrsDb->where('id', '=', $dvrId);
                    $this->dvrsDb->save();
                }
            }
        }
    }

    /**
     * Returns count of cameras (channels) registered on some existing DVR
     * 
     * @param int $dvrId
     * 
     * @return int
     */
    protected function getDvrCameraCount($dvrId) {
        $result = 0;
        if (!empty($this->allCams)) {
            foreach ($this->allCams as $io => $each) {
                if ($each['dvrid'] == $dvrId) {
                    $result++;
                }
            }
        }
        return($result);
    }

    /**
     * Renders tariffs changes report based on DDT log
     * 
     * @return string
     */
    public function renderTariffChangesReport() {
        $result = '';
        $curMonth = curmonth();

        if (@$this->altCfg['DDT_ENABLED']) {
            $ddtDb = new NyanORM('ddt_users');
            $allDoomedUsers = $ddtDb->getAll();
            $reportTmp = array();
            if (!empty($allDoomedUsers)) {
                foreach ($allDoomedUsers as $io => $each) {
                    if (ispos($each['enddate'], $curMonth)) {
                        if ($this->getCameraIdByLogin($each['login'])) {
                            $reportTmp[$io] = $each;
                        }
                    }
                }

                //rendering report
                if (!empty($reportTmp)) {
                    $cells = wf_TableCell(__('Camera'));
                    $cells .= wf_TableCell(__('Tariff'));
                    $cells .= wf_TableCell(__('End date'));
                    $cells .= wf_TableCell(__('New tariff'));
                    $cells .= wf_TableCell(__('User'));
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($reportTmp as $io => $each) {
                        $cameraUserId = $this->getCameraUser($each['login']);
                        $cameraUserData = @$this->allUserData[$each['login']];

                        $visorLinkLabel = $this->iconVisorUser() . ' ' . @$this->allUsers[$cameraUserId]['realname'];
                        $visorUserLink = wf_Link(self::URL_ME . self::URL_USERVIEW . $cameraUserId, $visorLinkLabel);

                        $cameraLinkLabel = web_profile_icon() . ' ' . @$cameraUserData['fulladress'];
                        $cameraLink = wf_Link(self::URL_CAMPROFILE . $each['login'], $cameraLinkLabel);
                        $cells = wf_TableCell($cameraLink);
                        $cells .= wf_TableCell($each['curtariff']);
                        $cells .= wf_TableCell($each['enddate']);
                        $cells .= wf_TableCell($each['nexttariff']);
                        $cells .= wf_TableCell($visorUserLink);
                        $rows .= wf_TableRow($cells, 'row5');
                    }

                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing found'), 'success');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('This module is disabled') . ': ' . __('Doomsday tariffs'), 'error');
        }
        return($result);
    }

    /**
     * Returns usable WolfRecorder API URL depends on existing DVR settings
     * 
     * @param int $dvrId
     * 
     * @return string
     */
    protected function getWolfRecorderApiUrl($dvrId) {
        $result = '';
        if (isset($this->allDvrs[$dvrId])) {
            $dvrData = $this->allDvrs[$dvrId];
            //just use explict URL?
            if (!empty($dvrData['apiurl'])) {
                $result = $dvrData['apiurl'];
            } else {
                //try to guess
                $proto = 'http://';
                $port = ($dvrData['port']) ? ':' . $dvrData['port'] . '/' : '/';
                $host = $dvrData['ip'];
                $defaultUrl = 'wr/';
                $result = $proto . $host . $port . $defaultUrl;
            }
        }
        return($result);
    }

    /**
     * Renders available DVRs health report
     * 
     * @return string
     */
    public function renderDVRsHealth() {
        $result = '';
        if (!empty($this->allDvrs)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Disks'));
            $cells .= wf_TableCell(__('Database'));
            $cells .= wf_TableCell(__('Network'));
            $cells .= wf_TableCell(__('Channels') . ' / ' . __('Online'));
            $cells .= wf_TableCell(__('Uptime'));
            $cells .= wf_TableCell(__('CPU load'));
            $cells .= wf_TableCell(__('Archive days'));

            $rows = wf_TableRowStyled($cells, 'row1');

            foreach ($this->allDvrs as $io => $each) {
                if ($each['type'] == 'trassir') {
                    if (!empty($each['ip']) AND !empty($each['login']) AND !empty($each['password']) AND !empty($each['apikey']) AND !empty($each['port'])) {
                        $dvrGate = new TrassirServer($each['ip'], $each['login'], $each['password'], $each['apikey'], $each['port'], $this->trassirDebug);
                        $health = $dvrGate->getHealth();
                        $cells = wf_TableCell($each['id']);
                        $cells .= wf_TableCell($each['ip']);
                        $cells .= wf_TableCell($each['name']);
                        $cells .= wf_TableCell(web_bool_led($health['disks']));
                        $cells .= wf_TableCell(web_bool_led($health['database']));
                        $cells .= wf_TableCell(web_bool_led($health['network']));
                        $cells .= wf_TableCell($health['channels_total'] . ' / ' . $health['channels_online']);
                        $cells .= wf_TableCell(zb_formatTime($health['uptime']));
                        $cells .= wf_TableCell($health['cpu_load'] . '%');
                        $cells .= wf_TableCell($health['disks_stat_main_days'] . ' / ' . $health['disks_stat_subs_days']);

                        $rows .= wf_TableRow($cells, 'row5');
                    }
                }

                if ($each['type'] == 'wolfrecorder') {
                    if (!empty($each['ip']) AND !empty($each['apikey'])) {
                        $apiUrl = $this->getWolfRecorderApiUrl($each['id']);
                        $dvrGate = new WolfRecorder($apiUrl, $each['apikey']);
                        if ($dvrGate->connectionOk()) {
                            $health = $dvrGate->systemGetHealth();
                            $cells = wf_TableCell($each['id']);
                            $cells .= wf_TableCell($each['ip']);
                            $cells .= wf_TableCell($each['name']);
                            $cells .= wf_TableCell(web_bool_led($health['storages']));
                            $cells .= wf_TableCell(web_bool_led($health['database']));
                            $cells .= wf_TableCell(web_bool_led($health['network']));
                            $cells .= wf_TableCell($health['channels_total'] . ' / ' . $health['channels_online']);
                            $cells .= wf_TableCell($health['uptime']);
                            $cells .= wf_TableCell($health['loadavg'] . ' LA');
                            $cells .= wf_TableCell('-');
                            $rows .= wf_TableRow($cells, 'row5');
                        } else {
                            $failLabel = __('Connection') . ' ' . __('Failed');
                            $cells = wf_TableCell($each['id']);
                            $cells .= wf_TableCell($each['ip']);
                            $cells .= wf_TableCell($each['name']);
                            $cells .= wf_TableCell($failLabel);
                            $cells .= wf_TableCell($failLabel);
                            $cells .= wf_TableCell($failLabel);
                            $cells .= wf_TableCell($failLabel);
                            $cells .= wf_TableCell($failLabel);
                            $cells .= wf_TableCell($failLabel);
                            $cells .= wf_TableCell('-');
                            $rows .= wf_TableRow($cells, 'row5');
                        }
                    }
                }
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Renders existing DVRs list wit some controls
     * 
     * @return string
     */
    public function renderDVRsList() {
        $result = '';
        if (!empty($this->allDvrs)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('Port'));
            $cells .= wf_TableCell(__('Cameras'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allDvrs as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['name']);
                $cells .= wf_TableCell($each['ip']);
                $cells .= wf_TableCell($each['port']);
                $cells .= wf_TableCell($this->getDvrCameraCount($each['id']) . ' / ' . $each['camlimit']);
                $actLinks = wf_JSAlert(self::URL_ME . self::URL_DELDVR . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . $each['ip'], $this->renderDVREditForm($each['id']));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        $result .= wf_delimiter();
        $result .= wf_modalAuto(wf_img('skins/ukv/add.png') . ' ' . __('Create'), __('Create'), $this->renderDVRsCreateForm(), 'ubButton');

        return($result);
    }

    /**
     * Checks is DVR used by some existing cameras
     * 
     * @param int $dvrId
     * 
     * @return bool
     */
    protected function isDVRProtected($dvrId) {
        $dvrId = ubRouting::filters($dvrId, 'int');
        $result = false;
        if (!empty($this->allCams)) {
            foreach ($this->allCams as $io => $each) {
                if ($each['dvrid'] == $dvrId) {
                    $result = true;
                }
            }
        }
        return($result);
    }

    /**
     * Deletes existing DVR from database
     * 
     * @param int $dvrId
     * 
     * @return void/string on error
     */
    public function deleteDVR($dvrId) {
        $dvrId = ubRouting::filters($dvrId, 'int');
        $result = '';
        if (isset($this->allDvrs[$dvrId])) {
            if (!$this->isDVRProtected($dvrId)) {
                $dvrData = $this->allDvrs[$dvrId];
                $this->dvrsDb->where('id', '=', $dvrId);
                $this->dvrsDb->delete();
                log_register('VISOR DVR DELETE [' . $dvrId . '] IP `' . $dvrData['ip'] . '`');
            } else {
                $result .= __('Something went wrong') . ': ' . __('This DVR is used for some cameras');
                log_register('VISOR DVR DELETE [' . $dvrId . '] TRY');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('No such DVR exists') . ' [' . $dvrId . ']';
        }
        return($result);
    }

    /**
     * Renders preview of channels from all Trassir based DVRs
     * 
     * @return string
     */
    public function renderChannelsPreview() {
        $result = '';
        $chanCount = 0;
        //chan controls here
        $result .= wf_Link(self::URL_ME . self::URL_CHANS, web_yellow_led() . ' ' . __('No user assigned'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . self::URL_CHANS . '&allchannels=true', web_green_led() . ' ' . __('All channels'), false, 'ubButton') . ' ';
        $result .= wf_delimiter();
        $allFlag = (ubRouting::checkGet('allchannels')) ? true : false;

        if (!empty($this->allDvrs)) {
            $result .= wf_tag('div', false, '');
            foreach ($this->allDvrs as $io => $eachDvr) {
                if ($eachDvr['type'] == 'trassir') {
                    $dvrGate = new TrassirServer($eachDvr['ip'], $eachDvr['login'], $eachDvr['password'], $eachDvr['apikey'], $eachDvr['port'], $this->trassirDebug);
                    $serverHealth = $dvrGate->getHealth();
                    if (!empty($serverHealth)) {
                        if (isset($serverHealth['channels_health'])) {
                            $dvrChannels = $serverHealth['channels_health'];
                            if (!empty($dvrChannels)) {
                                foreach ($dvrChannels as $ia => $eachChan) {
                                    $renderChannel = false;
                                    if ($allFlag) {
                                        $renderChannel = true;
                                    } else {
                                        if (!isset($this->channelUsers[$eachChan['guid']])) {
                                            $renderChannel = true;
                                        }
                                    }

                                    if ($renderChannel) {
                                        $streamUrl = $dvrGate->getLiveVideoStream($eachChan['guid'], 'main', $this->chanPreviewContainer, $this->chanPreviewQuality, $this->chanPreviewFramerate, $eachDvr['customurl']);
                                        $result .= wf_tag('div', false, 'whiteboard', 'style="width:' . $this->chanPreviewSize . ';"');
                                        $channelEditControl = wf_Link(self::URL_ME . self::URL_CHANEDIT . $eachChan['guid'] . '&dvrid=' . $eachDvr['id'], web_edit_icon(__('Edit') . ' ' . __('channel')));
                                        $result .= $eachChan['name'] . ' / ' . $eachChan['guid'] . ' @ ' . $eachDvr['id'];
                                        $result .= wf_tag('br');
                                        $result .= wf_tag('div', false, '', 'style="overflow:hidden; height:220px; max-height:250px;"');
                                        $result .= $this->renderChannelPlayer($streamUrl, '90%');
                                        $result .= wf_tag('div', true);
                                        $assignedUserId = (isset($this->channelUsers[$eachChan['guid']])) ? $this->channelUsers[$eachChan['guid']] : '';
                                        $assignedUserLabel = (isset($this->allUsers[$assignedUserId])) ? $this->iconVisorUser() . ' ' . $this->allUsers[$assignedUserId]['realname'] : '';
                                        $userAssignedLink = ($assignedUserId) ? wf_Link(self::URL_ME . self::URL_USERVIEW . $assignedUserId, $assignedUserLabel) : __('No');
                                        $userLinkClass = ($assignedUserId) ? 'todaysig' : 'undone';
                                        $result .= wf_tag('div', false, $userLinkClass);
                                        $result .= $channelEditControl . ' ' . __('User') . ': ' . $userAssignedLink;
                                        $result .= wf_tag('div', true);
                                        $result .= __('Signal') . ' ' . web_bool_led($eachChan['signal']);
                                        $result .= wf_CleanDiv();
                                        $result .= wf_tag('div', true);
                                        $chanCount++;
                                    }
                                }
                            } else {
                                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
                            }
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage(__('DVR connection error') . ': [' . $eachDvr['id'] . ']', 'error');
                    }
                }

                if ($eachDvr['type'] == 'wolfrecorder') {
                    $apiUrl = $this->getWolfRecorderApiUrl($eachDvr['id']);
                    $webUrl = ($eachDvr['customurl']) ? $eachDvr['customurl'] : $apiUrl;
                    $dvrGate = new WolfRecorder($apiUrl, $eachDvr['apikey']);
                    if ($dvrGate->connectionOk()) {
                        $dvrChannels = $dvrGate->channelsGetAll();
                        if (!empty($dvrChannels)) {
                            $allChannelScreenshots = $dvrGate->channelsGetScreenshotsAll();
                            $allRunningRecorders = $dvrGate->recordersGetAll();
                            foreach ($dvrChannels as $eachChan => $eachCamId) {
                                $renderChannel = false;
                                if ($allFlag) {
                                    $renderChannel = true;
                                } else {
                                    if (!isset($this->channelUsers[$eachChan])) {
                                        $renderChannel = true;
                                    }
                                }

                                if ($renderChannel) {
                                    $screenShotUrl = '';
                                    if (isset($allChannelScreenshots[$eachChan])) {
                                        $screenShotUrl = $webUrl . $allChannelScreenshots[$eachChan];
                                    } else {
                                        $screenShotUrl = 'skins/noimage.jpg';
                                    }

                                    $recorderState = (isset($allRunningRecorders[$eachCamId])) ? true : false;
                                    $result .= wf_tag('div', false, 'whiteboard', 'style="width:' . $this->chanPreviewSize . ';"');
                                    $channelEditControl = wf_Link(self::URL_ME . self::URL_CHANEDIT . $eachChan . '&dvrid=' . $eachDvr['id'], web_edit_icon(__('Edit') . ' ' . __('channel')));
                                    $result .= $eachChan . ' @ ' . $eachDvr['id'];
                                    $result .= wf_tag('br');
                                    $result .= wf_tag('div', false, '', 'style="overflow:hidden; height:220px; max-height:250px;"');
                                    $result .= wf_img_sized($screenShotUrl, '', '90%');
                                    $result .= wf_tag('div', true);
                                    $assignedUserId = (isset($this->channelUsers[$eachChan])) ? $this->channelUsers[$eachChan] : '';
                                    $assignedUserLabel = (isset($this->allUsers[$assignedUserId])) ? $this->iconVisorUser() . ' ' . $this->allUsers[$assignedUserId]['realname'] : '';
                                    $userAssignedLink = ($assignedUserId) ? wf_Link(self::URL_ME . self::URL_USERVIEW . $assignedUserId, $assignedUserLabel) : __('No');
                                    $userLinkClass = ($assignedUserId) ? 'todaysig' : 'undone';
                                    $result .= wf_tag('div', false, $userLinkClass);
                                    $result .= $channelEditControl . ' ' . __('User') . ': ' . $userAssignedLink;
                                    $result .= wf_tag('div', true);
                                    $result .= __('Recording') . ' ' . web_bool_led($recorderState);
                                    $result .= wf_CleanDiv();
                                    $result .= wf_tag('div', true);
                                    $chanCount++;
                                }
                            }
                        } else {
                            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage(__('DVR connection error') . ': [' . $eachDvr['id'] . ']', 'error');
                    }
                }
            }

            //all channels assigned, no channels registered alert
            if ($chanCount == 0) {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }

            $result .= wf_CleanDiv();
            $result .= wf_tag('div', true);
        } else {
            $result .= $this->messages->getStyledMessage(__('DVRs') . ' ' . __('Not exists'), 'warning');
        }
        return($result);
    }

    /**
     * Renders channel record mode editing form
     * 
     * @param string $channelGuid
     * @param int $dvrId
     * @param int $currentModeId
     * 
     * @return string
     */
    protected function renderChannelRecordForm($channelGuid, $dvrId, $currentModeId) {
        $result = '';
        $channelGuid = ubRouting::filters($channelGuid, 'mres');
        $dvrId = ubRouting::filters($dvrId, 'int');
        $currentModeId = ubRouting::filters($currentModeId, 'int');

        $inputs = wf_HiddenInput('recordchannelguid', $channelGuid);
        $inputs .= wf_HiddenInput('recordchanneldvrid', $dvrId);
        $inputs .= wf_Selector('recordchannelmode', $this->recordModes, __('Archive record mode'), $currentModeId, false) . ' ';
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Changes some channel record mode
     * 
     * @return void
     */
    public function saveChannelRecordMode() {
        if (ubRouting::checkPost(array('recordchannelguid', 'recordchanneldvrid', 'recordchannelmode'))) {
            $channellGuid = ubRouting::post('recordchannelguid', 'mres');
            $dvrId = ubRouting::post('recordchanneldvrid', 'int');
            $mode = ubRouting::post('recordchannelmode', 'int');
            if (isset($this->allDvrs[$dvrId])) {
                $dvrData = $this->allDvrs[$dvrId];
                if ($dvrData['type'] == 'trassir') {
                    $trassir = new TrassirServer($dvrData['ip'], $dvrData['login'], $dvrData['password'], $dvrData['apikey'], $dvrData['port'], $this->trassirDebug);
                    //channel avail check
                    $allChannels = $trassir->getChannels();
                    if (isset($allChannels[$channellGuid])) {
                        $trassir->setChannelRecordMode($channellGuid, $mode);
                        log_register('VISOR DVR [' . $dvrId . '] CHAN `' . $channellGuid . '` SET RECMODE [' . $mode . ']');
                    }
                }
            }
        }
    }

    /**
     * Returns channel preview container/player based on stream type
     * 
     * @param string $streamUrl
     * @param string $width
     * @param bool $autoPlay
     * 
     * @return string
     */
    protected function renderChannelPlayer($streamUrl, $width, $autoPlay = false) {
        $result = '';

        if ($this->chanPreviewContainer == 'mjpeg') {
            $result .= wf_img_sized($streamUrl, '', $width);
        }

        if ($this->chanPreviewContainer == 'hls') {
            $autoPlayMode = ($autoPlay) ? 'true' : 'false';
            $uniqId = 'hlsplayer' . wf_InputId();
            $result .= wf_tag('script', false, '', 'src="modules/jsc/playerjs/playerjs.js"') . wf_tag('script', true);
            $result .= wf_tag('div', false, '', 'id="' . $uniqId . '" style="width:' . $width . ';"') . wf_tag('div', true);
            $result .= wf_tag('script', false);
            $result .= 'var player = new Playerjs({id:"' . $uniqId . '", file:"' . $streamUrl . '", autoplay:' . $autoPlayMode . '});';
            $result .= wf_tag('script', true);
        }
        return($result);
    }

    /**
     * Renders channel editing form
     * 
     * @param string $channelGuid
     * @param int $dvrId
     * 
     * @return string
     */
    public function renderChannelEditForm($channelGuid, $dvrId) {
        $result = '';
        $channelGuid = ubRouting::filters($channelGuid, 'mres');
        $dvrId = ubRouting::filters($dvrId, 'int');
        if (isset($this->allDvrs[$dvrId])) {
            $curUserId = '';
            if (isset($this->channelUsers[$channelGuid])) {
                //already assigned to someone
                $curUserId = $this->channelUsers[$channelGuid];
            } else {
                $curUserId = (ubRouting::checkGet('useridpreset')) ? ubRouting::get('useridpreset', 'int') : '';
            }
            //some users preparing
            $usersTmp = array('' => '-');
            if (!empty($this->allUsers)) {
                foreach ($this->allUsers as $io => $each) {
                    $usersTmp[$each['id']] = $each['realname'];
                }
            }


            $inputs = wf_HiddenInput('editchannelguid', $channelGuid);
            $inputs .= wf_HiddenInput('editchanneldvrid', $dvrId);
            if ($this->altCfg['VISOR_USERSEL_SEARCHBL']) {
                $inputs .= wf_SelectorSearchable('editchannelvisorid', $usersTmp, __('User'), $curUserId, false) . ' ';
            } else {
                $inputs .= wf_Selector('editchannelvisorid', $usersTmp, __('User'), $curUserId, false) . ' ';
            }
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');

            $result .= wf_tag('br');
            $dvrData = $this->allDvrs[$dvrId];
            if ($dvrData['type'] == 'trassir') {
                $trassir = new TrassirServer($dvrData['ip'], $dvrData['login'], $dvrData['password'], $dvrData['apikey'], $dvrData['port'], $this->trassirDebug);
                $channelUrl = $trassir->getLiveVideoStream($channelGuid, 'main', $this->chanPreviewContainer, $this->chanBigPreviewQuality, $this->chanBigPreviewFramerate, $dvrData['customurl']);
                $result .= $this->renderChannelPlayer($channelUrl, '60%', true);
                $result .= wf_delimiter();
                //Channel record mode form here
                $currentRecordMode = $trassir->getChannelRecordMode($channelGuid);
                $result .= $this->renderChannelRecordForm($channelGuid, $dvrId, $currentRecordMode);
            }

            if ($dvrData['type'] == 'wolfrecorder') {
                $apiUrl = $this->getWolfRecorderApiUrl($dvrId);
                $webUrl = ($dvrData['customurl']) ? $dvrData['customurl'] : $apiUrl;
                $wolfRecorder = new WolfRecorder($apiUrl, $dvrData['apikey']);
                if ($wolfRecorder->connectionOk()) {
                    $screenshotUrl = '';
                    $screenshotReply = $wolfRecorder->channelsGetScreenshot($channelGuid);
                    if ($wolfRecorder->noError($screenshotReply)) {
                        if (isset($screenshotReply['screenshot']) AND !empty($screenshotReply['screenshot'])) {
                            $screenshotUrl = $webUrl . $screenshotReply['screenshot'];
                        } else {
                            $screenshotUrl = 'skins/noimage.jpg';
                        }


                        $result .= wf_img_sized($screenshotUrl, '', '70%');
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Oh no') . ': ' . __('DVR') . ' ' . __('Connection') . ' ' . __('Failed'), 'error');
                    }
                }
            }

            if (!isset($this->channelUsers[$channelGuid])) {
                $result .= $this->messages->getStyledMessage(__('Channel without assigned user'), 'warning');
                $result .= wf_delimiter();
            } else {
                $result .= $this->messages->getStyledMessage(__('Channel have user assigned') . ': ' . @$this->allUsers[$this->channelUsers[$channelGuid]]['realname'], 'success');
                $result .= wf_delimiter();
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('No such DVR exists') . ' [' . $dvrId . ']', 'error');
        }
        $result .= wf_link(self::URL_ME . self::URL_CHANS, wf_img('skins/play.png') . ' ' . __('Channels'), false, 'ubButton');
        if (isset($this->channelUsers[$channelGuid])) {
            $assignedUserId = $this->channelUsers[$channelGuid];
            $result .= wf_link(self::URL_ME . self::URL_USERVIEW . $assignedUserId, $this->iconVisorUser() . ' ' . @$this->allUsers[$assignedUserId]['realname'], false, 'ubButton');
        } else {
            if (!empty($curUserId)) {
                $result .= wf_link(self::URL_ME . self::URL_USERVIEW . $curUserId, $this->iconVisorUser() . ' ' . @$this->allUsers[$curUserId]['realname'], false, 'ubButton');
            }
        }
        return($result);
    }

    /**
     * Catches channel to user assign request and do required actions (update/delete)
     * 
     * @return void
     */
    public function saveChannelAssign() {
        if (ubRouting::checkPost(array('editchannelguid', 'editchanneldvrid'))) {
            $channelGuid = ubRouting::post('editchannelguid', 'mres');
            $dvrId = ubRouting::post('editchanneldvrid', 'int');
            if (ubRouting::checkPost('editchannelvisorid')) {
                //create/update of assign
                $visorId = ubRouting::post('editchannelvisorid', 'int'); //new channel owner ID
                if (isset($this->channelUsers[$channelGuid])) {
                    $oldChannelOwnerId = $this->channelUsers[$channelGuid];
                    //change existing assign
                    if ($visorId != $this->channelUsers[$channelGuid]) {
                        $this->unassignChannel($this->channelUsers[$channelGuid], $dvrId, $channelGuid);
                        $this->regenerateDvrChannelAcl($oldChannelOwnerId, $dvrId); //NVR sync on channel owner change for old owner
                        $this->assignChannel($visorId, $dvrId, $channelGuid);
                        $this->regenerateDvrChannelAcl($visorId, $dvrId); //NVR sync on channel owner change for new owner
                    }
                } else {
                    //create new channel assign
                    $this->assignChannel($visorId, $dvrId, $channelGuid);
                    $this->regenerateDvrChannelAcl($visorId, $dvrId); //NVR sync on new channel assign
                }
            } else {
                //existing assign deletion
                if (isset($this->channelUsers[$channelGuid])) {
                    $currentUserAssignId = $this->channelUsers[$channelGuid];
                    if (!empty($currentUserAssignId)) {
                        $this->unassignChannel($currentUserAssignId, $dvrId, $channelGuid);
                        $this->regenerateDvrChannelAcl($currentUserAssignId, $dvrId); //NVR sync on assign deletion
                    }
                }
            }
        }
    }

    /**
     * Regenerates all ACL for some visor user on Some DVR
     * 
     * @param int $visorId
     * @param int $dvrId
     * 
     * @return string
     */
    public function regenerateDvrChannelAcl($visorId, $dvrId) {
        $result = '';
        $visorId = ubRouting::filters($visorId, 'int');
        $dvrId = ubRouting::filters($dvrId, 'int');

        if (!empty($dvrId) AND !empty($visorId)) {
            if (isset($this->allSecrets[$visorId])) {
                if (isset($this->allDvrs[$dvrId])) {
                    if (isset($this->allUsers[$visorId])) {
                        $dvrData = $this->allDvrs[$dvrId];
                        //getting currently assigned channels on Visor
                        $this->chansDb->where('visorid', '=', $visorId);
                        $this->chansDb->where('dvrid', '=', $dvrId);
                        $userChans = $this->chansDb->getAll();

                        //Trassir Server Here
                        if ($dvrData['type'] == 'trassir') {
                            $secretData = $this->allSecrets[$visorId];
                            $dvrGate = new TrassirServer($dvrData['ip'], $dvrData['login'], $dvrData['password'], $dvrData['apikey'], $dvrData['port'], $this->trassirDebug);
                            $userRegistered = $dvrGate->getUserGuid($secretData['login']);

                            if (!$userRegistered) {
                                //perform creating user on DVR
                                $dvrGate->createUser($secretData['login'], $secretData['password']);
                                log_register('VISOR USER [' . $visorId . '] REGISTERED ON DVR [' . $dvrId . '] AS `' . $secretData['login'] . '` SYNC');
                            }
                            //setting valid ACL for this DVR
                            $dvrChans = array();
                            if (!empty($userChans)) {
                                foreach ($userChans as $io => $eachChan) {
                                    $dvrChans[] = $eachChan['chan'];
                                }
                            }

                            $aclGate = new TrassirServer($dvrData['ip'], $dvrData['login'], $dvrData['password'], $dvrData['apikey'], $dvrData['port'], $this->trassirDebug);
                            $aclGate->assignUserChannels($secretData['login'], $dvrChans);
                            log_register('VISOR USER [' . $visorId . '] REGEN ACL ON DVR [' . $dvrId . '] AS `' . $secretData['login'] . '` SYNC');
                        }
                        //WolfRecorder regeneration
                        if ($dvrData['type'] == 'wolfrecorder') {
                            $secretData = $this->allSecrets[$visorId];
                            $apiUrl = $this->getWolfRecorderApiUrl($dvrId);
                            $wolfRecorder = new WolfRecorder($apiUrl, $dvrData['apikey']);
                            $userRegistered = $wolfRecorder->usersIsRegistered($secretData['login']);
                            if (!$userRegistered['registered']) {
                                $wolfRecorder->usersCreate($secretData['login'], $secretData['password']);
                                log_register('VISOR USER [' . $visorId . '] REGISTERED ON DVR [' . $dvrId . '] AS `' . $secretData['login'] . '` SYNC');
                            }

                            //here channels ACL assigns
                            $dvrChans = array();
                            if (!empty($userChans)) {
                                foreach ($userChans as $io => $eachChan) {
                                    $dvrChans[] = $eachChan['chan'];
                                }
                            }

                            $existingChansAcl = $wolfRecorder->aclsGetChannels($secretData['login']);
                            $visorChans = array();

                            if (!empty($userChans)) {
                                foreach ($userChans as $io => $eachChan) {
                                    $visorChans[$eachChan['chan']] = $eachChan['chan'];
                                }
                            }

                            //puhing new to DVR
                            if (!empty($visorChans)) {
                                //sync visor=>dvr
                                foreach ($visorChans as $io => $eachChan) {
                                    if (!isset($existingChansAcl[$eachChan])) {
                                        $wolfRecorder->aclsAssignChannel($secretData['login'], $eachChan);
                                        log_register('VISOR USER [' . $visorId . '] CREATE ACL ON DVR [' . $dvrId . '] AS `' . $secretData['login'] . '` CHAN `' . $eachChan . '`');
                                    }
                                }
                            }
                            //deleting not existing in visor
                            if (!empty($existingChansAcl)) {
                                foreach ($existingChansAcl as $eachChan => $eachCam) {
                                    if (!isset($visorChans[$eachChan])) {
                                        $wolfRecorder->aclsDeassignChannel($secretData['login'], $eachChan);
                                        log_register('VISOR USER [' . $visorId . '] DELETE ACL ON DVR [' . $dvrId . '] AS `' . $secretData['login'] . '` CHAN `' . $eachChan . '`');
                                    }
                                }
                            }
                            log_register('VISOR USER [' . $visorId . '] REGEN ACL ON DVR [' . $dvrId . '] AS `' . $secretData['login'] . '` SYNC');
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Returns JSON list of channel preview URLs of channels assigned for user
     * 
     * @param int $visorId
     * @param bool $maxQual
     * 
     * @return string
     */
    public function getUserChannelsPreviewJson($visorId, $maxQual = false) {
        $result = '';
        $visorId = ubRouting::filters($visorId, 'int');
        $urlTmp = array();
        if (isset($this->allChannels[$visorId])) {
            foreach ($this->allChannels[$visorId] as $io => $each) {
                if (isset($this->allDvrs[$each['dvrid']])) {
                    $dvrData = $this->allDvrs[$each['dvrid']];
                    if ($dvrData['type'] == 'trassir') {
                        $trassir = new TrassirServer($dvrData['ip'], $dvrData['login'], $dvrData['password'], $dvrData['apikey'], $dvrData['port'], $this->trassirDebug);
                        if (!$maxQual) {
                            $url = $trassir->getLiveVideoStream($each['chan'], 'main', $this->chanPreviewContainer, $this->chanPreviewQuality, $this->chanPreviewFramerate, $dvrData['customurl']);
                        } else {
                            $url = $trassir->getLiveVideoStream($each['chan'], 'main', $this->chanPreviewContainer, $this->chanBigPreviewQuality, $this->chanBigPreviewFramerate, $dvrData['customurl']);
                        }
                        $urlTmp[$each['chan']] = $url;
                    }

                    if ($dvrData['type'] == 'wolfrecorder') {
                        $apiUrl = $this->getWolfRecorderApiUrl($dvrData['id']);
                        $webUrl = ($dvrData['customurl']) ? $dvrData['customurl'] : $apiUrl;
                        $wolfRecorder = new WolfRecorder($apiUrl, $dvrData['apikey']);
                        if ($maxQual) {
                            $liveStreamRaw = $wolfRecorder->channelsGetLiveStream($each['chan']);
                            if (isset($liveStreamRaw['livestream'])) {
                                $url = $webUrl . $liveStreamRaw['livestream'] . '&file=stream.m3u8';
                                $urlTmp[$each['chan']] = $url;
                            }
                        } else {
                            $screenshotRaw = $wolfRecorder->channelsGetScreenshot($each['chan']);
                            if (isset($screenshotRaw['screenshot'])) {
                                if (!empty($screenshotRaw['screenshot'])) {
                                    $url = $webUrl . $screenshotRaw['screenshot'];
                                    $urlTmp[$each['chan']] = $url;
                                }
                            }
                        }
                    }
                }
            }
        }
        $result = json_encode($urlTmp);
        return($result);
    }

    /**
     * Returns some DVRs authorization data if user have some channels assigned on managable DVRs
     * 
     * @param int $visorId
     * 
     * @return string
     */
    public function getUserDvrAuthData($visorId) {
        $result = array();
        $visorId = ubRouting::filters($visorId, 'int');
        if (isset($this->allUsers[$visorId])) {
            if (isset($this->allSecrets[$visorId])) {
                $secretsData = $this->allSecrets[$visorId];
                if (isset($this->allChannels[$visorId])) {
                    if (!empty($this->allChannels[$visorId])) {
                        $allChanData = $this->allChannels[$visorId];
                        foreach ($allChanData as $io => $each) {
                            if (isset($this->allDvrs[$each['dvrid']])) {
                                $dvrData = $this->allDvrs[$each['dvrid']];
                                $result[$each['dvrid']]['dvrid'] = $dvrData['id'];
                                $result[$each['dvrid']]['ip'] = $dvrData['ip'];
                                $result[$each['dvrid']]['port'] = $dvrData['port'];
                                $result[$each['dvrid']]['login'] = $secretsData['login'];
                                $result[$each['dvrid']]['password'] = $secretsData['password'];
                                if ($dvrData['type'] == 'trassir') {
                                    $result[$each['dvrid']]['weburl'] = 'https://' . $dvrData['ip'] . ':' . $dvrData['port'] . '/webgui/';
                                }

                                if ($dvrData['type'] == 'wolfrecorder') {
                                    $prefill = '';
                                    if (!empty($secretsData['login']) AND !empty($secretsData['password'])) {
                                        $prefill = '?authprefill=' . $secretsData['login'] . '|' . $secretsData['password'];
                                    }
                                    if (empty($dvrData['apiurl'])) {
                                        $result[$each['dvrid']]['weburl'] = 'http://' . $dvrData['ip'] . '/wr/' . $prefill;
                                    } else {
                                        $result[$each['dvrid']]['weburl'] = $dvrData['apiurl'] . $prefill;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $result = json_encode($result);

        return($result);
    }

    /**
     * Returns existing DVR name and IP
     * 
     * @param int $dvrId
     * 
     * @return string
     */
    public function getDvrLabel($dvrId) {
        $result = '';
        if (isset($this->allDvrs[$dvrId])) {
            $result .= $this->allDvrs[$dvrId]['name'] . ' - ' . $this->allDvrs[$dvrId]['ip'];
        }
        return($result);
    }

    /**
     * Returns existing DVR name
     * 
     * @param int $dvrId
     * 
     * @return string
     */
    public function getDvrName($dvrId) {
        $result = '';
        if (isset($this->allDvrs[$dvrId])) {
            $result .= $this->allDvrs[$dvrId]['name'];
        }
        return($result);
    }

    /**
     * Performs default fee charge processing to prevent cameras offline
     * 
     * @return void
     */
    public function chargeProcessing() {
        $chargedCounter = 0;
        if (!empty($this->allUsers)) {
            //we need some fresh data
            $this->allUserData = zb_UserGetAllData();
            //and tariffs fee
            $allTariffsFee = zb_TariffGetPricesAll();
            foreach ($this->allUsers as $eachUserId => $eachUserData) {
                if (($eachUserData['chargecams']) AND (!empty($eachUserData['primarylogin']))) {
                    if (isset($this->allUserData[$eachUserData['primarylogin']])) {
                        //further actions is required
                        $primaryAccountData = $this->allUserData[$eachUserData['primarylogin']];
                        $primaryAccountLogin = $primaryAccountData['login'];
                        $primaryAccountBalance = $primaryAccountData['Cash'];
                        $primaryAccountCredit = $primaryAccountData['Credit'];
                        $primaryAccountTariff = $primaryAccountData['Tariff'];
                        $primaryPossibleBalance = $primaryAccountBalance + $primaryAccountCredit; //global primary balance counter
                        $primaryAccountFee = $allTariffsFee[$primaryAccountTariff];
                        //loading user cameras
                        $userCameras = $this->getUserCameras($eachUserId);
                        if (!empty($userCameras)) {
                            foreach ($userCameras as $eachCameraId => $eachCameraData) {
                                if (isset($this->allUserData[$eachCameraData['login']])) {
                                    $cameraUserData = $this->allUserData[$eachCameraData['login']];
                                    $cameraLogin = $cameraUserData['login'];
                                    $cameraTariff = $cameraUserData['Tariff'];
                                    if (isset($allTariffsFee[$cameraTariff])) {
                                        $cameraBalance = $cameraUserData['Cash'];
                                        $cameraCredit = $cameraUserData['Credit'];
                                        $cameraFee = $allTariffsFee[$cameraTariff];
                                        $cameraLack = ($cameraBalance + $cameraCredit) - $cameraFee;
                                        //this camera needs some money to continue functioning
                                        if ($cameraLack < 0) {
                                            //is this not a same user?
                                            if ($cameraLogin != $primaryAccountLogin) {
                                                $chargeThisCam = false;
                                                //camera online priority
                                                if ($this->chargeMode == 1) {
                                                    $chargeThisCam = true;
                                                }

                                                //primary account internet priority
                                                if ($this->chargeMode == 2) {
                                                    $primaryPossibleBalance = ($primaryPossibleBalance) - abs($cameraLack);
                                                    if ($primaryPossibleBalance >= '-' . $primaryAccountCredit) {
                                                        //that doesnt disable primary account
                                                        $chargeThisCam = true;
                                                    } else {
                                                        //and this will
                                                        $chargeThisCam = false;
                                                    }
                                                }

                                                //dont charge money for frozen cameras
                                                if ($cameraUserData['Passive'] == 1) {
                                                    $chargeThisCam = false;
                                                }

                                                //perform money movement from primary account
                                                if ($chargeThisCam) {
                                                    //charge some money from primary account
                                                    zb_CashAdd($primaryAccountLogin, $cameraLack, 'add', 1, 'VISORCHARGE:' . $eachCameraId);
                                                    //and put in onto camera account
                                                    zb_CashAdd($cameraLogin, abs($cameraLack), 'correct', 1, 'VISORPUSH:' . $eachUserId);
                                                    //correcting operation here to prevent figure that as true payment in reports.
                                                    $chargedCounter++;
                                                }
                                            }
                                        }
                                    } else {
                                        log_register('VISOR CAMERA [' . $eachCameraId . '] CHARGE FAIL NO_TARIFF `' . $cameraTariff . '`');
                                    }
                                } else {
                                    log_register('VISOR CAMERA [' . $eachCameraId . '] CHARGE FAIL NO_USER (' . $eachCameraData['login'] . ')');
                                }
                            }
                        }
                    } else {
                        log_register('VISOR USER [' . $eachUserId . '] PRIMARY NO_USER (' . $eachUserData['primarylogin'] . ')');
                    }
                }
            }
            //flush old cached users data
            if ($chargedCounter > 0) {
                zb_UserGetAllDataCacheClean();
            }
        }
    }
}
