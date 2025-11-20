<?php

/**
 * TrinityTV low-level API implementation
 *
 * http://trinity-tv.net/
 * http://partners.trinity-tv.net/
 */
class TrinityTvApi {

    /**
     * Partner ID
     *
     * @var string
     */
    protected $partnerId = '';

    /**
     * Key to generate an authorization request
     *
     * @var string
     */
    protected $salt = '';

    /**
     * API URL
     *
     * @var string
     */
    protected $urlApi = 'http://partners.trinity-tv.net/partners';

    /**
     * Debug flag
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Default debug log path
     */
    const LOG_PATH = 'exports/trinitytv.log';

    /**
     * 
     * @param string $partnerId
     * @param string $salt
     * @param string $urlApi
     * @param bool $debug
     * 
     * @return void
     */
    public function __construct($partnerId = '', $salt = '', $urlApi = '', $debug = false) {
        $this->partnerId = $partnerId;
        $this->salt = $salt;
        $this->debug = $debug;

        if (!empty($urlApi)) {
            $this->urlApi = $urlApi;
        }
    }

    /**
     * Add subscription to user
     *
     * @param int $localid
     * @param $subscrid
     * @return bool|mixed
     */
    public function createUser($localid, $subscrid) {
        $requestid = $this->getRequestId();

        $hash = md5($requestid . $this->partnerId . $localid . $subscrid . $this->salt);

        $uri = $this->urlApi . '/user/create?requestid=' . $requestid . '&partnerid=' . $this->partnerId . '&localid=' . $localid . '&subscrid=' . $subscrid . '&hash=' . $hash;

        return $this->sendRequest($uri);
    }

    /**
     * Change User Data
     *
     * @param int $localid
     * @param string $lastname
     * @param string $firstname
     * @param string $middlename
     * @param string $address
     * @return bool|mixed
     */
    public function updateUser($localid = 0, $lastname = '', $firstname = '', $middlename = '', $address = '') {
        $requestid = $this->getRequestId();

        $firstname = urlencode($firstname);
        $lastname = urlencode($lastname);
        $middlename = urlencode($middlename);
        $address = urlencode($address);

        $hash = md5($requestid . $this->partnerId . $localid . $firstname . $lastname . $middlename . $address . $this->salt);

        $uri = $this->urlApi . '/user/updateuser?requestid=' . $requestid . '&partnerid=' . $this->partnerId . '&localid=' . $localid . '&lastname=' . $lastname . '&firstname=' . $firstname . '&middlename=' . $middlename . '&address=' . $address . '&hash=' . $hash;

        return $this->sendRequest($uri);
    }

    /**
     * Getting a list of users and their statuses.
     *
     * @return bool|mixed
     */
    public function listUsers() {
        $requestid = $this->getRequestId();

        $hash = md5($requestid . $this->partnerId . $this->salt);

        $uri = $this->urlApi . '/user/subscriberlist?requestid=' . $requestid . '&partnerid=' . $this->partnerId . '&hash=' . $hash;

        return $this->sendRequest($uri);
    }

    /**
     * Suspending and Restoring a Subscription
     *
     * @param int $localid
     * @param string $operationid
     * @return bool|mixed
     */
    public function subscription($localid = 0, $operationid = 'suspend') {
        $requestid = $this->getRequestId();

        $hash = md5($requestid . $this->partnerId . $localid . $operationid . $this->salt);

        $uri = $this->urlApi . '/user/subscription?requestid=' . $requestid . '&partnerid=' . $this->partnerId . '&localid=' . $localid . '&operationid=' . $operationid . '&hash=' . $hash;

        return $this->sendRequest($uri);
    }

    /**
     * Getting the list of subscriptions of the user.
     *
     * @param int $localid
     * @return bool|mixed
     */
    public function subscriptionInfo($localid = 0) {
        $requestid = $this->getRequestId();

        $hash = md5($requestid . $this->partnerId . $localid . $this->salt);

        $uri = $this->urlApi . '/user/subscriptioninfo?requestid=' . $requestid . '&partnerid=' . $this->partnerId . '&localid=' . $localid . '&hash=' . $hash;

        return $this->sendRequest($uri);
    }

    /**
     * Authorization MAC / UUID device
     *
     * @param int $localid
     * @param string $mac
     * @param string $uuid
     * @return bool|mixed
     */
    public function addMacDevice($localid = 0, $mac = '', $uuid = '') {
        $requestid = $this->getRequestId();

        // The string, mac device subscriber, 12 characters in uppercase
        $mac = str_replace(array(
            "-",
            ":"
                ), "", strtoupper($mac));

        $hash = md5($requestid . $this->partnerId . $localid . $mac . $this->salt);

        $uri = $this->urlApi . '/user/autorizemac?requestid=' . $requestid . '&partnerid=' . $this->partnerId . '&localid=' . $localid . '&mac=' . $mac . '&uuid=' . $uuid . '&hash=' . $hash;

        return $this->sendRequest($uri);
    }

    /**
     * Authorization of MAC / UUID device by code
     *
     * @param int $localid
     * @param string $code
     * @return bool|mixed
     */
    public function addCodeMacDevice($localid = 0, $code = '') {
        $requestid = $this->getRequestId();

        $hash = md5($requestid . $this->partnerId . $localid . $code . $this->salt);

        $uri = $this->urlApi . '/user/autorizebycode?requestid=' . $requestid . '&partnerid=' . $this->partnerId . '&localid=' . $localid . '&code=' . $code . '&hash=' . $hash;

        return $this->sendRequest($uri);
    }

    /**
     * Deauthorize MAC / UUID devices
     *
     * @param int $localid
     * @param string $mac
     * @param string $uuid
     * @return bool|mixed
     */
    public function deleteMacDevice($localid = 0, $mac = '', $uuid = '') {
        $requestid = $this->getRequestId();

        // The string, mac device subscriber, 12 characters in uppercase
        $mac = str_replace(array(
            "-",
            ":"
                ), "", strtoupper($mac));

        $hash = md5($requestid . $this->partnerId . $localid . $mac . $this->salt);

        $uri = $this->urlApi . '/user/deletemac?requestid=' . $requestid . '&partnerid=' . $this->partnerId . '&localid=' . $localid . '&mac=' . $mac . '&uuid=' . $uuid . '&hash=' . $hash;

        return $this->sendRequest($uri);
    }

    /**
     * Listing authorized MAC / UUID devices
     *
     * @param int $localid
     * @return bool|mixed
     */
    public function listDevices($localid = 0) {
        $requestid = $this->getRequestId();

        $hash = md5($requestid . $this->partnerId . $localid . $this->salt);

        $uri = $this->urlApi . '/user/listmac?requestid=' . $requestid . '&partnerid=' . $this->partnerId . '&localid=' . $localid . '&hash=' . $hash;

        return $this->sendRequest($uri);
    }

    /**
     * Generate Unique number
     *
     * @return mixed
     */
    private function getRequestId() {

        list($usec, $sec) = explode(' ', microtime());

        return str_replace('.', '', ((float) $sec . (float) $usec));
    }

    /**
     * Send request
     *
     * @param $url
     * @return bool|mixed
     */
    private function sendRequest($url) {
        if ($this->debug) {
            file_put_contents(self::LOG_PATH, curdatetime() . "\n", FILE_APPEND);
            file_put_contents(self::LOG_PATH, '>>>>>QUERY>>>>>' . "\n", FILE_APPEND);
            file_put_contents(self::LOG_PATH, print_r($url, true) . "\n", FILE_APPEND);
        }
        /**
         * Masked notices output, due 500 errors on some requests.
         */
        $response = @file_get_contents($url);

        if ($this->debug) {
            file_put_contents(self::LOG_PATH, '<<<<<RESPONSE<<<<<' . "\n", FILE_APPEND);
            if (!empty($response)) {
                file_put_contents(self::LOG_PATH, print_r(json_decode($response, true), true) . "\n", FILE_APPEND);
            } else {
                file_put_contents(self::LOG_PATH, 'EMPTY_RESPONCE_RECEIVED' . "\n", FILE_APPEND);
            }
            file_put_contents(self::LOG_PATH, '==================' . "\n", FILE_APPEND);
        }

        if (!empty($response)) {
            return json_decode($response);
        }



        return false;
    }

}

/**
 * TrinityTV OTT service implementation
 */
class TrinityTv {

    /**
     * TrinityTV API object
     *
     * @var string
     */
    protected $api = '';

    /**
     * Contains all of available trinity tariffs as id=>data
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains available and active trinity service subscriptions as id=>data
     *
     * @var array
     */
    protected $allSubscribers = array();

    /**
     * Contains all of internet users data as login=>data
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains default channel icon size
     *
     * @var int
     */
    protected $chanIconSize = 32;

    /**
     * Contains array of currently suspended users without base tariff
     *
     * @var array
     */
    protected $suspended = array();

    /**
     * Devices count rendering flag in subscribers list.
     *
     * @var int
     */
    protected $renderDevices = 0;

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * * Default tariffs viewing URL
     */
    const URL_TARIFFS = 'tariffs=true';

    /**
     * Basic module path
     */
    const URL_ME = '?module=trinitytv';

    /**
     * Default user profile viewing URL
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Default subscriber profile viewing URL
     */
    const URL_SUBSCRIBER = '?module=trinitytv&subscriberid=';
    const URL_SUBS = 'subscriptions=true';
    const URL_AJSUBS = 'ajsubs=true';
    const URL_AJDEVS = 'ajdevices=true';
    const URL_SUBVIEW = 'subview=true';
    const URL_REPORTS = 'reports=true';
    const URL_DEVICES = 'devices=true';
    const TABLE_SUBS = 'trinitytv_subscribers';
    const TABLE_TARIFFS = 'trinitytv_tariffs';
    const TABLE_DEVICES = 'trinitytv_devices';
    const TABLE_SUSPENDS = 'trinitytv_suspend';

    /**
     * Creates new TriityTV instance
     */
    public function __construct() {
        $this->loadConfigs();
        $this->initApi();
        $this->initMessages();
        $this->loadTariffs();
        $this->loadUsers();
        $this->loadSubscribers();
        $this->loadSuspended();
    }

    /**
     * Loads required configs into protected props
     * 
     * @global type $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();

        //some other options setup
        if (isset($this->altCfg['TRINITYTV_RDEVS'])) {
            $this->renderDevices = $this->altCfg['TRINITYTV_RDEVS'];
        }
    }

    /**
     * Trys to render human-readable tariff name
     *
     * @param int $tariffId
     *
     * @return string
     */
    protected function getTariffName($tariffId) {
        $result = '';

        if (isset($this->allTariffs[$tariffId])) {
            $result .= $this->allTariffs[$tariffId]['name'];
        } else {
            $result .= $tariffId;
        }
        return ($result);
    }

    /**
     * Get all devices
     *
     * @return array
     */
    private function getDevices() {
        $result = array();

        $query = "SELECT * from " . self::TABLE_DEVICES;
        $devices = simple_queryall($query);

        if (!empty($devices)) {
            foreach ($devices AS $device) {
                $result[$device['id']] = $device;
            }
        }

        return $result;
    }

    /**
     * Get subscriber devices
     *
     * @param $subscriberId
     * @return array
     */
    private function getSubscriberDevices($subscriberId) {
        $result = array();
        $subscriberId = mysql_real_escape_string($subscriberId);

        $query = "SELECT * from `" . self::TABLE_DEVICES . "` WHERE `subscriber_id` = " . $subscriberId;
        $devices = simple_queryall($query);

        if (!empty($devices)) {
            foreach ($devices AS $device) {
                $result[$device['id']] = $device;
            }
        }

        return $result;
    }

    /**
     * Get device id
     *
     * @param $subscriberId
     * @param $mac
     * @return bool
     */
    private function getDeviceId($subscriberId, $mac) {
        $subscriberId = mysql_real_escape_string($subscriberId);

        $query = "SELECT * from `" . self::TABLE_DEVICES . "` WHERE `subscriber_id` = " . $subscriberId;
        $devices = simple_queryall($query);

        if (!empty($devices)) {
            foreach ($devices AS $device) {
                if ($device['mac'] == $mac) {
                    return $device['id'];
                }
            }
        }

        return false;
    }

    /**
     * Returns local subscriber ID from database
     *
     * @param string $userLogin
     *
     * @return int
     */
    public function getSubscriberId($userLogin) {
        $result = '';
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $subscriber) {
                if ($subscriber['login'] == $userLogin) {
                    $result = $subscriber['id'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns local subscriber login by ID from database
     *
     * @param int $subscriberId
     *
     * @return string
     */
    public function getSubscriberLogin($subscriberId) {
        $result = '';
        if (!empty($this->allSubscribers)) {
            if (isset($this->allSubscribers[$subscriberId])) {
                $result = $this->allSubscribers[$subscriberId]['login'];
            }
        }
        return ($result);
    }

    /**
     * Returns tariff local data
     *
     * @param int $tariffId
     *
     * @return array
     */
    protected function getTariffData($tariffId) {
        $result = array();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariff) {
                if ($tariff['id'] == $tariffId) {
                    $result = $tariff;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns device vendor lookup concrols + ajax container. wf_AjaxLoader required.
     * 
     * @param string $mac
     * 
     * @return string
     */
    protected function renderVendorLookup($mac) {
        $result = '';
        if (@$this->altCfg['MACVEN_ENABLED']) {
            if (!empty($mac)) {
                $containerName = 'DEVMCVENCNT_' . zb_rand_string(8);
                $lookupVendorLink = wf_AjaxLink('?module=macvendor&mac=' . $mac . '&raw=true', wf_img('skins/macven.gif', __('Device vendor')), $containerName, false, '');
                $lookupVendorLink .= wf_tag('span', false, '', 'id="' . $containerName . '"') . '' . wf_tag('span', true);
                $result .= $lookupVendorLink;
            }
        }
        return($result);
    }

    /**
     * Renders available tariffs list
     *
     * @param $subscriberId
     * @return string
     */
    public function renderDevices($subscriberId) {
        $result = '';
        $result .= wf_AjaxLoader();
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('MAC address'));
        $cells .= wf_TableCell(__('Date'));
        if (@$this->altCfg['MACVEN_ENABLED']) {
            $cells .= wf_TableCell(__('Manufacturer'));
        }
        $cells .= wf_TableCell(__('Actions'));


        $rows = wf_TableRow($cells, 'row1');

        // Add device
        $result .= wf_modalAuto(wf_img('skins/switch_models.png') . ' ' . __('Assign device') . ' ' . __('by MAC'), __('Assign device'), $this->renderDeviceAddForm($subscriberId), 'ubButton');

        // Add device by MAC
        $result .= wf_modalAuto(wf_img('skins/switch_models.png') . ' ' . __('Assign device') . ' ' . __('by code'), __('Assign device'), $this->renderDeviceByCodeAddForm($subscriberId), 'ubButton');
        $result .= wf_delimiter();

        $devices = $this->getSubscriberDevices($subscriberId);
        if (!empty($devices)) {
            foreach ($devices as $device) {
                $cells = wf_TableCell($device['id']);
                $cells .= wf_TableCell($device['mac']);
                $cells .= wf_TableCell($device['created_at']);
                if (@$this->altCfg['MACVEN_ENABLED']) {
                    $cells .= wf_TableCell($this->renderVendorLookup($device['mac']));
                }

                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_SUBSCRIBER . $subscriberId . '&deletedeviceid=' . $device['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row3');
            }
        }

        $result .= wf_TableBody($rows, '100%', 0, 'sortable');

        return ($result);
    }

    /**
     * Renders available tariffs list
     *
     * @return string
     */
    public function renderTariffs() {
        $result = '';

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Tariff name'));
        $cells .= wf_TableCell(__('Fee'));
        $cells .= wf_TableCell(__('Service ID'));

        $cells .= wf_TableCell(__('Actions'));

        $rows = wf_TableRow($cells, 'row1');

        // Кнопка создать новый тариф
        $result .= wf_modalAuto(wf_img('skins/ukv/add.png') . ' ' . __('Create new tariff'), __('Create new tariff'), $this->renderTariffCreateForm(), 'ubButton');

        $result .= "<br><br> ";

        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariff) {
                $cells = wf_TableCell($tariff['id']);
                $cells .= wf_TableCell($tariff['name']);
                $cells .= wf_TableCell($tariff['fee']);
                $cells .= wf_TableCell($tariff['serviceid']);
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_TARIFFS . '&deletetariffid=' . $tariff['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit tariff'), $this->tariffEditForm($tariff['id']));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
        }

        $result .= wf_TableBody($rows, '100%', 0, 'sortable');

        return ($result);
    }

    /**
     * Renders tariff creation form
     *
     * @return string
     */
    public function renderTariffCreateForm() {
        $result = '';

        $inputs = wf_TextInput('newtariffname', __('Tariff name'), '', true, '20');
        $inputs .= wf_TextInput('newtariffdesc', __('Description'), '', true, '20');
        $inputs .= wf_TextInput('newtarifffee', __('Fee'), '', true, '5');
        $inputs .= wf_TextInput('newtariffserviceid', __('Service ID'), '', true, '10');
        $inputs .= '<hr>';
        $inputs .= wf_Submit(__('Create'));

        $result = wf_Form('', 'POST', $inputs, 'glamour __StreetEditForm');

        return ($result);
    }

    /**
     * Returns tariff editing form
     *
     * @param int $tariffId
     *
     * @return string
     */
    protected function tariffEditForm($tariffId) {
        $result = '';
        $inputs = wf_HiddenInput('edittariffid', $tariffId);
        $inputs .= wf_TextInput('edittariffname', __('Tariff name'), $this->allTariffs[$tariffId]['name'], true, '20');
        $inputs .= wf_TextInput('edittariffdesc', __('Description'), $this->allTariffs[$tariffId]['description'], true, '20');
        $inputs .= wf_TextInput('edittarifffee', __('Fee'), $this->allTariffs[$tariffId]['fee'], true, '5');
        $inputs .= wf_TextInput('edittariffserviceid', __('Service ID'), $this->allTariffs[$tariffId]['serviceid'], true, '10');
        $inputs .= '<hr>';
        $inputs .= wf_Submit(__('Save'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Catches tariff editing form data
     *
     * @return void/string on error
     */
    public function updateTariff() {
        $result = '';
        if (wf_CheckPost(array(
                    'edittariffid',
                    'edittariffname',
                    'edittariffdesc',
                    'edittariffserviceid'
                ))) {
            $tariffId = vf($_POST['edittariffid'], 3);
            if (isset($this->allTariffs[$tariffId])) {
                $where = " WHERE `id`='" . $tariffId . "';";
                simple_update_field(self::TABLE_TARIFFS, 'name', $_POST['edittariffname'], $where);
                simple_update_field(self::TABLE_TARIFFS, 'description', $_POST['edittariffdesc'], $where);
                simple_update_field(self::TABLE_TARIFFS, 'fee', $_POST['edittarifffee'], $where);
                simple_update_field(self::TABLE_TARIFFS, 'serviceid', $_POST['edittariffserviceid'], $where);
                log_register('TRINITY TARIFF EDIT [' . $tariffId . '] `' . $_POST['edittariffname'] . '` FEE `' . $_POST['edittarifffee'] . '`');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('No all of required fields is filled'), 'error');
        }
        return ($result);
    }

    /**
     * Creates new tariff in database
     *
     * @return void/string on error
     */
    public function createTariff() {
        $result = '';
        if (wf_CheckPost(array(
                    'newtariffname',
                    'newtariffdesc',
                    'newtariffserviceid'
                ))) {
            $nameF = mysql_real_escape_string($_POST['newtariffname']);
            $feeF = mysql_real_escape_string($_POST['newtarifffee']);
            $desc = mysql_real_escape_string($_POST['newtariffdesc']);
            $serviceidF = mysql_real_escape_string($_POST['newtariffserviceid']);

            if (zb_checkMoney($feeF)) {
                $query = "INSERT INTO `" . self::TABLE_TARIFFS . "` (`id`,`name`,`description`,`fee`,`serviceid`) VALUES ";
                $query .= "(NULL,'" . $nameF . "','" . $desc . "','" . $feeF . "','" . $serviceidF . "')";
                nr_query($query);
                $newId = simple_get_lastid(self::TABLE_TARIFFS);
                log_register('TRINITY TARIFF CREATE [' . $newId . '] `' . $_POST['newtariffname'] . '` FEE `' . $_POST['newtarifffee'] . '`');
            } else {
                $result = $this->messages->getStyledMessage(__('Wrong format of a sum of money to pay'), 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('No all of required fields is filled'), 'error');
        }
        return ($result);
    }

    /**
     * Checks is tariff used by some users
     *
     * @param int $tariffid
     *
     * @return bool
     */
    protected function tariffProtected($tariffid) {
        $result = false;
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $subscriber) {
                if ($subscriber['tariffid'] == $tariffid) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes existing tariff from database
     *
     * @param int $tariffId
     *
     * @return void/string
     */
    public function deleteTariff($tariffId) {
        $tariffId = vf($tariffId, 3);
        $result = '';
        if (isset($this->allTariffs[$tariffId])) {
            if (!$this->tariffProtected($tariffId)) {
                $query = "DELETE from `" . self::TABLE_TARIFFS . "` WHERE `id`='" . $tariffId . "';";
                nr_query($query);
                log_register('TRINITYTV TARIFF DELETE [' . $tariffId . ']');
            } else {
                $result = $this->messages->getStyledMessage(__('Tariff is used by some users'), 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Not existing item'), 'error');
        }
        return ($result);
    }

    /**
     * Inits API object for further usage
     */
    protected function initApi() {
        $partnerId = '';
        $salt = '';

        if (!empty($this->altCfg['TRINITYTV_PARTNER_ID'])) {
            $partnerId = $this->altCfg['TRINITYTV_PARTNER_ID'];
        }

        if (!empty($this->altCfg['TRINITYTV_SALT'])) {
            $salt = $this->altCfg['TRINITYTV_SALT'];
        }

        if (isset($this->altCfg['TRINITYTV_DEBUG'])) {
            $debug = $this->altCfg['TRINITYTV_DEBUG'];
        } else {
            $debug = false;
        }

        $this->api = new TrinityTvApi($partnerId, $salt, '', $debug);
    }

    /**
     * Inits system message helper
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads existing tariffs from database
     *
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from " . self::TABLE_TARIFFS;
        $tariffs = simple_queryall($query);
        if (!empty($tariffs)) {
            foreach ($tariffs as $tariff) {
                $this->allTariffs[$tariff['id']] = $tariff;
            }
        }
    }

    /**
     * Loads existing suspended users
     *
     * @return void
     */
    protected function loadSuspended() {
        $query = "SELECT * from " . self::TABLE_SUSPENDS;
        $suspends = simple_queryall($query);
        if (!empty($suspends)) {
            foreach ($suspends as $suspend) {
                $this->suspended[$suspend['login']] = $suspend['id'];
            }
        }
    }

    /**
     * Loads existing subscribers data
     *
     * @return void
     */
    protected function loadSubscribers() {
        $query = "SELECT * from " . self::TABLE_SUBS;
        $subscribers = simple_queryall($query);
        if (!empty($subscribers)) {
            foreach ($subscribers as $subscriber) {
                $this->allSubscribers[$subscriber['id']] = $subscriber;
            }
        }
    }

    /**
     * Loads internet users data into protected property for further usage
     *
     * @return void
     */
    protected function loadUsers() {
        $this->allUsers = zb_UserGetAllData();
    }

    /**
     * Renders form to manual tariff changing
     *
     * @param int $subscriberId
     *
     * @return string
     */
    protected function renderManualTariffForm($subscriberId) {
        $subscriberId = vf($subscriberId, 3);
        $result = '';
        $subcribersData = @$this->allSubscribers[$subscriberId];

        $baseTariffs = array();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariff) {
                $baseTariffs[$tariff['id']] = $tariff['name'];
            }
        }

        $inputs = '';
        $inputs .= wf_Selector('changebasetariff', $baseTariffs, __('Tariff'), $subcribersData['tariffid'], true);
        $inputs .= wf_CheckInput('dontchargefeenow', __('Dont charge fee now'), true, true);
        $inputs .= wf_tag('br');
        $inputs .= wf_Submit(__('Save'));

        $result .= wf_Form(self::URL_SUBSCRIBER . $subscriberId, 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Performs editing of user tariffs
     *
     * @param int $subscriberId
     * @param int $tariffId
     * @param bool $chargeFee
     *
     * @return void
     */
    public function changeTariffs($subscriberId, $tariffId, $chargeFee = true) {
        $tariffId = vf($tariffId, 3);

        $subscriberId = vf($subscriberId, 3);

        // Get the tariff properties
        $tariff = $this->getTariffData($tariffId);

        if (isset($this->allSubscribers[$subscriberId])) {

            $userLogin = $this->allSubscribers[$subscriberId]['login'];
            $currentTariffId = $this->allSubscribers[$subscriberId]['tariffid'];

            // Change the tariff if needed
            if ($currentTariffId != $tariffId) {
                // Create a subscription on the Trinity
                $response = $this->api->createUser($subscriberId, $tariff['serviceid']);

                if (isset($response->result) AND $response->result == 'success') {
                    simple_update_field(self::TABLE_SUBS, 'tariffid', $tariffId, "WHERE `id`='" . $subscriberId . "'");
                    log_register('TRINITYTV SET TARIFF [' . $tariffId . ']  (' . $userLogin . ') AS [' . $subscriberId . ']');
                }
            }

            //do something awful with user balance
            if ($chargeFee) {
                // Calculating new tariff fee
                $tariffFee = $tariff['fee'];
                $currentDayOfMonth = date("d");
                $currentMonthDayCount = date("t");
                $tariffFeeDaily = $this->getDaylyFee($tariffFee);
                $tariffFee = ($currentMonthDayCount - $currentDayOfMonth) * $tariffFeeDaily;

                // Charging fee to the end of month
                zb_CashAdd($userLogin, '-' . $tariffFee, 'add', 1, 'TRINITYTV:' . $tariffId);
                log_register('TRINITYTV FEE (' . $userLogin . ') - ' . $tariffFee);
            } else {
                //just log this change as zero charge
                zb_CashAdd($userLogin, '-0', 'add', 1, 'TRINITYTV:' . $tariffId);
                log_register('TRINITYTV FEE (' . $userLogin . ') - 0');
            }

            // Trying activate user if he is not active now.
            if ($this->allSubscribers[$subscriberId]['active'] != 1) {
                simple_update_field(self::TABLE_SUBS, 'active', '1', "WHERE `id`='" . $subscriberId . "'");
                log_register('TRINITYTV RESURRECT USER (' . $userLogin . ') AS [' . $subscriberId . ']');
            }
        }
    }

    /**
     * Renders manual device assign form
     *
     * @return string
     */
    protected function renderDeviceAddForm($subscriberId) {
        $result = '';
        $inputs = wf_HiddenInput('device', 'true');
        $inputs .= wf_HiddenInput('subscriberid', $subscriberId);
        $userlogin = $this->getSubscriberLogin($subscriberId);
        $inputs .= wf_HiddenInput('userlogin', $userlogin);
        $inputs .= wf_TextInput('mac', __('MAC'), '', true, 20, 'mac');
        $inputs .= wf_Submit(__('Assign'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders manual device assign form
     *
     * @return string
     */
    protected function renderDeviceByCodeAddForm($subscriberId) {
        $result = '';
        $inputs = wf_HiddenInput('manualassigndevice', 'true');
        $inputs .= wf_HiddenInput('subscriberid', $subscriberId);
        $userlogin = $this->getSubscriberLogin($subscriberId);
        $inputs .= wf_HiddenInput('userlogin', $userlogin);
        $inputs .= wf_TextInput('code', __('Code'), '', true, 20);
        $inputs .= wf_Submit(__('Assign'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     *  Check exists device by MAC
     * @param $mac
     * @return bool
     */
    public function existsDevice($mac) {
        $mac = strtoupper($mac);

        $query = "SELECT * from `" . self::TABLE_DEVICES . "` WHERE `mac` ='" . $mac . "'";
        $devices = simple_queryall($query);

        if (!empty($devices)) {
            return true;
        }

        return false;
    }

    /**
     * Assigns some device by code to some subscriber
     *
     * @param $userLogin
     * @param $code
     * @return string
     */
    public function addDeviceByCode($userLogin, $code) {
        $result = '';

        $subscriberId = $this->getSubscriberId($userLogin);
        $subscriberId = vf($subscriberId, 3); //int
        $code = vf(strtoupper($code)); //alphanumeric

        if (isset($this->allSubscribers[$subscriberId])) {
            $response = $this->api->addCodeMacDevice($subscriberId, $code);

            if (isset($response->result) AND $response->result == 'success') {

                @$mac = vf(strtoupper($response->mac)); //alphanumeric

                $query = "INSERT INTO `" . self::TABLE_DEVICES . "` (`login`, `subscriber_id`, `mac`, `created_at`) VALUES ";
                $query .= "('" . $this->allSubscribers[$subscriberId]['login'] . "', '" . $subscriberId . "','" . $mac . "', NOW() )";
                nr_query($query);

                $userLogin = $this->getSubscriberLogin($subscriberId);
                log_register('TRINITYTV DEVICE ADD `' . $mac . '` FOR (' . $userLogin . ') AS [' . $subscriberId . ']');
            } else {
                $result = __('Strange exeption') . ': ' . @$response->result;
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('User not exists');
        }

        return ($result);
    }

    /**
     * Assigns some device uniq to some subscriber
     *
     * @param $userLogin
     * @param $mac
     * @return string
     */
    public function addDevice($userLogin, $mac) {
        $result = '';

        $subscriberId = $this->getSubscriberId($userLogin);
        $subscriberId = vf($subscriberId, 3); //int
        $mac = vf(strtoupper($mac)); //alphanumeric

        if (isset($this->allSubscribers[$subscriberId])) {

            $existsDevice = $this->existsDevice($mac);
            if (!$existsDevice) {
                $response = $this->api->addMacDevice($subscriberId, $mac);

                if (isset($response->result) AND $response->result == 'success') {

                    $query = "INSERT INTO `" . self::TABLE_DEVICES . "` (`login`, `subscriber_id`, `mac`, `created_at`) VALUES ";
                    $query .= "('" . $this->allSubscribers[$subscriberId]['login'] . "', '" . $subscriberId . "','" . $mac . "', NOW() )";
                    nr_query($query);

                    $userLogin = $this->getSubscriberLogin($subscriberId);
                    log_register('TRINITYTV DEVICE ADD `' . $mac . '` FOR (' . $userLogin . ') AS [' . $subscriberId . ']');
                } else {
                    $result .= __('Strange exeption') . ': ' . @$response->result;
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('Device exists');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('User not exists');
        }

        return ($result);
    }

    /**
     * Renders profile controls
     *
     * @return string
     */
    protected function renderProfileControls($subscriberId) {
        $subscriberId = vf($subscriberId, 3);
        $result = wf_tag('br');
        $result .= wf_Link(self::URL_ME . '&subscriberid=' . $subscriberId . '&blockuser=true', web_bool_led(0) . ' ' . __('Block user'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&subscriberid=' . $subscriberId . '&unblockuser=true', web_bool_led(1) . ' ' . __('Unblock user'), false, 'ubButton');
        $result .= wf_modalAuto(web_edit_icon() . ' ' . __('Edit tariff'), __('Edit tariff'), $this->renderManualTariffForm($subscriberId), 'ubButton');
        return ($result);
    }

    /**
     * Sets user local and remote profile as active or not
     *
     * @param int $subscriberId
     * @param bool $state
     *
     * @return void
     */
    public function setSubscriberActive($subscriberId, $state) {
        $subscriberId = vf($subscriberId, 3);
        if (isset($this->allSubscribers[$subscriberId])) {

            $userLogin = $this->allSubscribers[$subscriberId]['login'];
            $where = "WHERE `id`='" . $subscriberId . "'";
            if ($state) {
                // Разблокируем
                $this->api->subscription($subscriberId, 'resume');
                simple_update_field(self::TABLE_SUBS, 'active', '1', $where);
                log_register('TRINITYTV UNBLOCK USER (' . $userLogin . ') AS [' . $subscriberId . ']');
                $this->suspendUser($userLogin, false);
            } else {

                // Блокируем
                $this->api->subscription($subscriberId, 'suspend');
                simple_update_field(self::TABLE_SUBS, 'active', '0', $where);
                log_register('TRINITYTV BLOCK USER (' . $userLogin . ') AS [' . $subscriberId . ']');
                $this->suspendUser($userLogin, true);
            }
        }
    }

    /**
     * Sets user as suspended or not to preventing his automatic ressurection
     *
     * @param string $userLogin
     * @param bool $state
     *
     * @return void
     */
    protected function suspendUser($userLogin, $state) {
        $login_f = mysql_real_escape_string($userLogin);
        $subscriberId = $this->getSubscriberId($userLogin);
        if ($state) {
            $query = "INSERT INTO " . self::TABLE_SUSPENDS . " (`id`,`login`) VALUES (NULL,'" . $login_f . "');";
            nr_query($query);
            log_register('TRINITYTV SUSPEND USER (' . $userLogin . ') AS [' . $subscriberId . ']');
        } else {
            $query = "DELETE FROM " . self::TABLE_SUSPENDS . " WHERE `login`='" . $login_f . "'";
            nr_query($query);
            log_register('TRINITYTV UNSUSPEND USER (' . $userLogin . ') AS [' . $subscriberId . ']');
        }
    }

    /**
     * Renders some user profile info
     *
     * @param int $subscriberId
     *
     * @return string
     */
    public function renderUserInfo($subscriberId) {
        $subscriberId = vf($subscriberId, 3);
        $result = '';

        $subscriber = @$this->allSubscribers[$subscriberId];

        if (!empty($subscriber)) {
            $remoteServiceData = $this->api->subscriptionInfo($subscriberId);

            $result .= wf_tag('b') . __('Local profile') . wf_tag('b', true) . wf_tag('br');
            $rows = '';


            $cells = wf_TableCell(__('ID'), '', 'row2');
            $cells .= wf_TableCell($subscriber['id']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Real Name'), '', 'row2');
            $cells .= wf_TableCell($this->allUsers[$subscriber['login']]['realname']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Login'), '', 'row2');
            $cells .= wf_TableCell($subscriber['login']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Full address'), '', 'row2');
            $userAddress = @$this->allUsers[$subscriber['login']]['fulladress'];
            $userLink = wf_Link(self::URL_PROFILE . $subscriber['login'], web_profile_icon() . ' ' . $userAddress);
            $cells .= wf_TableCell($userLink);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Contract') . ' ' . __('Trinity'), '', 'row2');
            $cells .= wf_TableCell($subscriber['contracttrinity']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Tariff'), '', 'row2');
            $cells .= wf_TableCell($this->getTariffName($subscriber['tariffid']));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Date'), '', 'row2');
            $cells .= wf_TableCell($this->getTariffName($subscriber['actdate']));
            $rows .= wf_TableRow($cells, 'row3');

            $remoteServiceStatus = $remoteServiceData->subscriptions->subscrstatus;

            $cells = wf_TableCell(__('Status') . ' ' . __('local'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($subscriber['active']));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Status') . ' ' . __('Trinity'), '', 'row2');
            $cells .= wf_TableCell($remoteServiceStatus);
            $rows .= wf_TableRow($cells, 'row3');

            $result .= wf_TableBody($rows, '100%', 0);
        }

        $result .= $this->renderProfileControls($subscriberId);

        return ($result);
    }

    /**
     * Renders default module controls
     *
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result .= wf_Link(self::URL_ME . '&' . self::URL_SUBS, wf_img('skins/ukv/users.png') . ' ' . __('Subscriptions'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::URL_TARIFFS, wf_img('skins/ukv/dollar.png') . ' ' . __('Tariffs'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::URL_DEVICES, wf_img('skins/switch_models.png') . ' ' . __('Devices'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::URL_REPORTS, wf_img('skins/ukv/report.png') . ' ' . __('Reports'), false, 'ubButton') . ' ';

        return ($result);
    }

    /**
     * Renders new subscriber registration form
     *
     * @return string
     */
    protected function renderUserRegisterForm() {

        $baseTariffs = array();
        foreach ($this->allTariffs as $tariff) {
            $baseTariffs[$tariff['id']] = $tariff['name'];
        }

        $result = '';
        $loginPreset = (wf_CheckGet(array('username'))) ? $_GET['username'] : '';
        $inputs = wf_HiddenInput('manualregister', 'true');
        $inputs .= wf_TextInput('manualregisterlogin', __('Login'), $loginPreset, true, '15');

        $inputs .= wf_Selector('manualregistertariff', $baseTariffs, __('Tariff'), '', true);
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders tariff editing form
     *
     * @param int $tariffId
     *
     * @return string
     */
    protected function renderTariffEditForm($tariffId) {
        $tariffId = vf($tariffId, 3);
        $result = '';
        if (isset($this->allTariffs[$tariffId])) {
            $tariffData = $this->allTariffs[$tariffId];
            if (!empty($tariffData)) {
                $tariffsTypes = array(
                    'base' => __('Base'),
                    'bundle' => __('Bundle'),
                    'promo' => __('Promo')
                );

                $inputs = wf_HiddenInput('edittariffid', $tariffId);
                $inputs .= wf_TextInput('edittariffname', __('Tariff name'), $tariffData['tariffname'], true, 25);
                $inputs .= wf_Selector('edittarifftype', $tariffsTypes, __('Type'), $tariffData['type'], true);
                $inputs .= wf_TextInput('edittarifffee', __('Fee'), $tariffData['fee'], true, 3, 'finance');
                $inputs .= wf_Submit(__('Save'));

                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            }
        }
        return ($result);
    }

    /**
     * Renders list of available tariffs
     *
     * @return string
     */
    public function renderTariffsList() {
        $result = '';
        if (!empty($this->allTariffs)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Tariff') . ' ' . __('Code'));
            $cells .= wf_TableCell(__('Tariff name'));
            $cells .= wf_TableCell(__('Type'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allTariffs as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['tariffid']);
                $cells .= wf_TableCell($each['tariffname']);
                $cells .= wf_TableCell(__($each['type']));
                $cells .= wf_TableCell($each['fee']);
                $actLinks = wf_JSAlert(self::URL_ME . '&tariffs=true&deleteid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderTariffEditForm($each['id'])) . ' ';
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Deletes device assigned to some subscriberid
     *
     * @param string $userLogin
     * @param string $mac
     *
     * @return void
     */
    public function deleteDevice($userLogin, $mac) {
        $result = '';
        $subscriberId = $this->getSubscriberId($userLogin);
        $deviceId = $this->getDeviceId($subscriberId, $mac);
        $deviceId = vf($deviceId, 3);

        $allDevices = $this->getDevices();
        if (isset($allDevices[$deviceId])) {

            // Delete a subscription on the Trinity
            $response = $this->api->deleteMacDevice($allDevices[$deviceId]['subscriber_id'], $allDevices[$deviceId]['mac']);

            $query = "DELETE from `" . self::TABLE_DEVICES . "` WHERE `id`='" . $deviceId . "';";
            nr_query($query);
            log_register('TRINITYTV DEVICE DELETE `' . $allDevices[$deviceId]['mac'] . '` FOR (' . $userLogin . ')');

            if (isset($response->result) AND $response->result == 'success') {
                
            } else {
                $result = __('Something went wrong') . ": Trinity response " . @$response->result;
            }
        } else {
            $result = __('Not existing item');
        }

        return ($result);
    }

    /**
     * Deletes device assigned to some subscriberid
     *
     * @param string $deviceId
     *
     * @return void
     */
    public function deleteDeviceById($deviceId) {
        $result = '';

        $deviceId = vf($deviceId, 3);

        $allDevices = $this->getDevices();
        if (isset($allDevices[$deviceId])) {
            $deviceData = $allDevices[$deviceId];
            // Delete a subscription on the Trinity
            $response = $this->api->deleteMacDevice($allDevices[$deviceId]['subscriber_id'], $allDevices[$deviceId]['mac']);

            $query = "DELETE from `" . self::TABLE_DEVICES . "` WHERE `id`='" . $deviceId . "';";
            nr_query($query);
            log_register('TRINITYTV DEVICE DELETE `' . $allDevices[$deviceId]['mac'] . '` FOR (' . $deviceData['login'] . ')');

            if (isset($response->result) AND $response->result == 'success') {
                
            } else {
                $result = __('Something went wrong') . ": Trinity response " . @$response->result;
            }
        } else {
            $result = __('Not existing item');
        }

        return ($result);
    }

    /**
     * Deletes some device by its ID if it assigned to specified login (for remoteapi callback)
     * 
     * @param int $deviceId
     * @param string $userLogin
     * 
     * @return void/string on error
     */
    public function deleteDeviceByIdProtected($deviceId, $userLogin) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        $allDevices = $this->getDevices();
        //device exists?
        if (isset($allDevices[$deviceId])) {
            $deviceData = $allDevices[$deviceId];
            //have correct assign for user that requested deletion?
            if ($deviceData['login'] == $userLogin) {
                $result .= $this->deleteDeviceById($deviceId);
            } else {
                //masking actual devices quantity and assigns from user
                $result .= __('No such device');
            }
        } else {
            $result .= __('No such device');
        }
        return($result);
    }

    /**
     * Deletes subscription
     * 
     * @param string $login
     * 
     * @return string
     */
    public function deleteSubscribtion($login) {
        $result = '';

        if (isset($this->allUsers[$login])) {

            $subscriberId = $this->getSubscriberId($login);

            if (!empty($subscriberId)) {
                $this->setSubscriberActive($subscriberId, false);
            }
        } else {
            $result = __('Something went wrong') . ': ' . __('User not exist') . ' - ' . $login;
            log_register('TRINITYTV FAIL SUBSCRIBER REGISTER (' . $login . ') NOLOGIN');
        }

        return ($result);
    }

    /**
     * Creates new user profile
     *
     * @param $login
     * @param $tariffId
     * @return string
     */
    public function createSubscribtion($login, $tariffId) {
        $tariffId = vf($tariffId, 3);
        $login_f = mysql_real_escape_string($login);
        $curdate = curdatetime();
        $result = '';

        if (isset($this->allUsers[$login])) {
            if (isset($this->allTariffs[$tariffId])) {
                $subscriberId = $this->getSubscriberId($login);
                //not existing subscriber
                if (empty($subscriberId)) {
                    //getting new tariff data
                    $tariff = $this->getTariffData($tariffId);
                    //and tariff exists
                    if (!empty($tariff)) {
                        // Create Subscriber In Ubilling
                        $query = "INSERT INTO `" . self::TABLE_SUBS . "` (`login`,`tariffid`,`actdate`,`active`) VALUES ";
                        $query .= "('" . $login_f . "','" . $tariffId . "','" . $curdate . "','1');";
                        nr_query($query);
                        $subscriberID = simple_get_lastid(self::TABLE_SUBS);

                        // Create a subscription on the Trinity
                        $response = $this->api->createUser($subscriberID, $tariff['serviceid']);
                        if (isset($response->result) AND $response->result == 'success') {
                            $contractID = $response->contracttrinity;

                            //Push contracttrinity to DB
                            simple_update_field(self::TABLE_SUBS, 'contracttrinity', $contractID, 'WHERE `id`=' . $subscriberID);
                            log_register('TRINITYTV SUBSCRIBER REGISTER (' . $login . ') AS [' . $subscriberID . ']');
                            $this->loadSubscribers();
                        }

                        // charge fee to the end of month
                        $tariffFee = $tariff['fee'];
                        $currentDayOfMonth = date("d");
                        $currentMonthDayCount = date("t");
                        $tariffFeeDaily = $this->getDaylyFee($tariffFee);

                        $tariffFee = ($currentMonthDayCount - $currentDayOfMonth) * $tariffFeeDaily;

                        zb_CashAdd($login, '-' . $tariffFee, 'add', 1, 'TRINITYTV:' . $tariffId);
                        log_register('TRINITYTV FEE (' . $login . ') -' . $tariffFee);
                    } else {
                        $result .= 'Wrong tariff';
                    }
                } else {
                    // Change tariff AND activate
                    $this->changeTariffs($subscriberId, $tariffId);
                }
            } else {
                $result = 'Wrong tariff';
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('User not exist') . ' - ' . $login;
            log_register('TRINITYTV FAIL SUBSCRIBER REGISTER (' . $login . ') NOLOGIN');
        }

        return ($result);
    }

    /**
     * Returns valid dayly fee for some tariff/month
     *
     * @param float $tariffFee
     *
     * @return float
     */
    protected function getDaylyFee($tariffFee) {
        $monthDays = date("t");
        $result = round(($tariffFee / $monthDays), 2);
        return ($result);
    }

    /**
     * Charges fee for some tariff
     *
     * @param string $userLogin
     * @param int $tariffId
     *
     * @return void
     */
    protected function chargeFee($userLogin, $tariffId) {
        $tariffData = $this->getTariffData($tariffId);

        $subscriberID = $this->getSubscriberId($userLogin);
        $tariffFee = $tariffData['fee'];

        zb_CashAdd($userLogin, '-' . $tariffFee, 'add', 1, 'TRINITYTV:' . $tariffId);
        log_register('TRINITYTV CHARGE TARIFF [' . $tariffId . '] FEE `' . $tariffFee . '` FOR (' . $userLogin . ') AS [' . $subscriberID . ']');
    }

    /**
     * Renders default subscriptions report
     *
     * @return string
     */
    public function renderSubscribtionsReportMonthly() {
        $result = '';
        $selectedMonth = (wf_CheckPost(array('monthsel'))) ? $_POST['monthsel'] : date("m");
        $selectedYear = (wf_CheckPost(array('yearsel'))) ? $_POST['yearsel'] : date("Y");
        $inputs = wf_YearSelectorPreset('yearsel', __('Year'), false, $selectedYear) . ' ';
        $inputs .= wf_MonthSelector('monthsel', __('Month'), $selectedMonth, false) . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        $curYear = (wf_CheckPost(array('yearsel'))) ? vf($_POST['yearsel'], 3) : curyear();
        $curMonth = (wf_CheckPost(array('monthsel'))) ? vf($_POST['monthsel'], 3) : date("m");

        $query = "SELECT * from `payments` WHERE `date` LIKE '" . $curYear . "-" . $curMonth . "%' AND `note` LIKE 'TRINITYTV:%';";
        $payments = simple_queryall($query);
        $tmpArr = array();

        if (!empty($payments)) {
            foreach ($payments as $payment) {
                $tariffId = explode(':', $payment['note']);
                $tariffId = $tariffId[1];
                if (isset($tmpArr[$tariffId])) {
                    $tmpArr[$tariffId]['summ'] = $tmpArr[$tariffId]['summ'] + abs($payment['summ']);
                    $tmpArr[$tariffId]['count'] ++;
                } else {
                    $tmpArr[$tariffId]['summ'] = abs($payment['summ']);
                    $tmpArr[$tariffId]['count'] = 1;
                }
            }
        }

        if (!empty($tmpArr)) {
            $cells = wf_TableCell(__('Tariff'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Users'));
            $cells .= wf_TableCell(__('Total payments'));
            $rows = wf_TableRow($cells, 'row1');
            $totalUsers = 0;
            $totalSumm = 0;

            foreach ($tmpArr as $io => $each) {
                $totalUsers = $totalUsers + $each['count'];
                $totalSumm = $totalSumm + $each['summ'];

                $cells = wf_TableCell(@$this->allTariffs[$io]['name']);
                $cells .= wf_TableCell(@$this->allTariffs[$io]['fee']);
                $cells .= wf_TableCell($each['count']);
                $cells .= wf_TableCell($each['summ']);
                $rows .= wf_TableRow($cells, 'row3');
            }

            $cells = wf_TableCell(wf_tag('b') . __('Total') . wf_tag('b', true));
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell($totalUsers);
            $cells .= wf_TableCell($totalSumm);
            $rows .= wf_TableRow($cells, 'row2');

            $result .= wf_TableBody($rows, '100%', 0, '');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }


        return ($result);
    }

    /**
     * Returns data container for active subscriptions
     *
     * @return string
     */
    public function renderSubscribtions() {
        $result = '';

        // Кнопка создать подписку
        $result .= wf_modalAuto(wf_img('skins/ukv/add.png') . ' ' . __('Users registration'), __('Registration'), $this->renderUserRegisterForm(), 'ubButton');
        $result .= wf_delimiter();

        if ($this->renderDevices) {
            $columns = array(
                __('ID'),
                __('Login'),
                __('Real Name'),
                __('Full address'),
                __('Cash'),
                __('Current tariff'),
                __('Date'),
                __('Devices'),
                __('Active'),
                __('Actions')
            );
        } else {
            $columns = array(
                __('ID'),
                __('Login'),
                __('Real Name'),
                __('Full address'),
                __('Cash'),
                __('Current tariff'),
                __('Date'),
                __('Active'),
                __('Actions')
            );
        }

        $orderOpts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_SUBS . '&' . self::URL_AJSUBS, false, __('Subscriptions'), '100', $orderOpts);
        return ($result);
    }

    /**
     * Returns array of devices assigned for subscribers as subscriberId=>devcount
     * 
     * @return array
     */
    protected function getUserDevicesCount() {
        $result = array();
        $allDevices = $this->getDevices();
        if (!empty($allDevices)) {
            foreach ($allDevices as $io => $each) {
                if (isset($result[$each['subscriber_id']])) {
                    $result[$each['subscriber_id']] ++;
                } else {
                    $result[$each['subscriber_id']] = 1;
                }
            }
        }
        return($result);
    }

    /**
     * Renders ajax data subscriptions
     *
     * @return void
     */
    public function subscribtionsListAjax() {

        $json = new wf_JqDtHelper();

        if (!empty($this->allSubscribers)) {
            if ($this->renderDevices) {
                $devCounters = $this->getUserDevicesCount();
            }
            foreach ($this->allSubscribers as $subscriber) {

                $userAddress = @$this->allUsers[$subscriber['login']]['fulladress'];
                $userLink = wf_Link(self::URL_PROFILE . $subscriber['login'], web_profile_icon() . ' ' . $userAddress);
                $actLinks = wf_Link(self::URL_ME . '&subscriberid=' . $subscriber['id'], web_edit_icon());

                $data[] = $subscriber['id'];
                $data[] = $subscriber['login'];
                $data[] = @$this->allUsers[$subscriber['login']]['realname'];
                $data[] = $userLink;
                $data[] = @$this->allUsers[$subscriber['login']]['Cash'];
                $data[] = $this->getTariffName($subscriber['tariffid']);
                $data[] = $subscriber['actdate'];
                if ($this->renderDevices) {
                    $devicesCount = 0;
                    if (isset($devCounters[$subscriber['id']])) {
                        $devicesCount = $devCounters[$subscriber['id']];
                    } else {
                        $devicesCount = 0;
                    }
                    $data[] = $devicesCount;
                }
                $data[] = web_bool_led($subscriber['active'], true);
                $data[] = $actLinks;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Renders devices report container
     * 
     * @return string
     */
    public function renderDevicesList() {
        $result = '';
        $columns = array('ID', 'MAC', 'Date', 'Real Name', 'Full address', 'Subscriptions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_DEVICES . '&' . self::URL_AJDEVS, false, __('Devices'), 100, $opts);
        return($result);
    }

    /**
     * Returns JSON data with available devices info
     * 
     * @return void
     */
    public function devicesListAjax() {
        $json = new wf_JqDtHelper();
        $allDevices = $this->getDevices();
        if (!empty($allDevices)) {
            foreach ($allDevices as $io => $each) {

                $data[] = $each['id'];
                $data[] = $each['mac'];
                $data[] = $each['created_at'];
                $data[] = @$this->allUsers[$each['login']]['realname'];
                $userLink = wf_Link(self::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . @$this->allUsers[$each['login']]['fulladress']);
                $data[] = $userLink;
                $subLink = wf_Link(self::URL_SUBSCRIBER . $each['subscriber_id'], web_edit_icon());
                $data[] = $subLink;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Charges all users tariffs fee, disables it when users go down
     *
     * @return void
     */
    public function subscriptionFeeProcessing() {
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $subscriber) {

                if ($subscriber['active']) {
                    if (isset($this->allUsers[$subscriber['login']])) {
                        if (!$this->allUsers[$subscriber['login']]['Passive']) {
                            if (!empty($subscriber['tariffid'])) {
                                $this->chargeFee($subscriber['login'], $subscriber['tariffid']);
                            }
                        }
                    }
                }
            }

            //checking for debtors/freezed users and disabling it
            $this->loadUsers();
            foreach ($this->allSubscribers as $subscriber) {
                if ($subscriber['active']) {
                    if (isset($this->allUsers[$subscriber['login']])) {
                        $userData = $this->allUsers[$subscriber['login']];
                        if ($userData['Passive']) {
                            //user is frozen by some reason - need to disable him
                            $this->api->subscription($subscriber['id'], 'suspend');

                            simple_update_field(self::TABLE_SUBS, 'active', '0', "WHERE `id`='" . $subscriber['id'] . "'");
                            log_register('TRINITYTV BLOCK FROZEN USER (' . $subscriber['login'] . ') AS [' . $subscriber['id'] . ']');
                        }

                        //if user have debt after charging fee - we need to block him too
                        if ($userData['Cash'] < '-' . $userData['Credit']) {

                            $this->api->subscription($subscriber['id'], 'suspend');
                            simple_update_field(self::TABLE_SUBS, 'active', '0', "WHERE `id`='" . $subscriber['id'] . "'");
                            log_register('TRINITYTV BLOCK DEBTOR USER (' . $subscriber['login'] . ') AS [' . $subscriber['id'] . ']');
                        }
                    }
                }
            }
        }
    }

    /**
     * Resurrects some users if their was disabled by inactivity
     *
     * @return void
     */
    public function resurrectAllSubscribers() {
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $subscriber) {
                if (!$subscriber['active']) {
                    if (isset($this->allUsers[$subscriber['login']])) {
                        $userData = $this->allUsers[$subscriber['login']];
                        if (($userData['Passive'] == 0) AND ( $userData['Cash'] >= '-' . $userData['Credit'])) {
                            if (!empty($subscriber['tariffid'])) {
                                //check is user resurrection suspended?
                                if (!isset($this->suspended[$subscriber['login']])) {
                                    //unblock this user
                                    $this->api->subscription($subscriber['id'], 'resume');

                                    simple_update_field(self::TABLE_SUBS, 'active', '1', "WHERE `id`='" . $subscriber['id'] . "'");
                                    log_register('TRINITYTV RESURRECT USER (' . $subscriber['login'] . ') AS [' . $subscriber['id'] . ']');
                                }
                            }
                        }
                    }
                }
            }
        }
    }

}

?>
