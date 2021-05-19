<?php

/**
 * YouTV Ubilling abstraction layer
 *
 * https://documenter.getpostman.com/view/13165103/TVYAhgRP#4807007c-a159-4210-9f61-d51807aa36ee
 */
class YTV {

    /**
     * Contains sytem alter.config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains login preloaded from config
     *
     * @var string
     */
    protected $login = '';

    /**
     * Contains password preloaded from config
     *
     * @var string
     */
    protected $password = '';

    /**
     * Contains dealer id preloaded from config
     *
     * @var string
     */
    protected $dealerID = '';


    /**
     * ProstoTV low-level API abstraction layer
     *
     * @var object
     */
    protected $api = '';

    /**
     * Contains all available system users data as login=>userdata
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains all subscribers data as login=>subscriberData
     *
     * @var array
     */
    protected $allSubscribers = array();

    /**
     * Contains all tariffs data as serviceid=>tariffData
     *
     * @var string
     */
    protected $allTariffs = array();

    /**
     * Subscribers database abstraction layer
     *
     * @var object
     */
    protected $subscribersDb = '';

    /**
     * Tariffs database abstraction layer
     *
     * @var object
     */
    protected $tariffsDb = '';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Predefined routes, options etc.
     */
    const OPTION_LOGIN = 'YOUTV_LOGIN';
    const OPTION_PASSWORD = 'YOUTV_PASSWORD';
    const OPTION_DEALER_ID = 'YOUTV_DEALER_ID';
    const TABLE_SUBSCRIBERS = 'youtv_subscribers';
    const TABLE_TARIFFS = 'youtv_tariffs';
    const UNDEF = 'undefined_';
    const NEW_WINDOW = 'TARGET="_BLANK"';
    const URL_ME = '?module=youtv';
    const URL_USERPROFILE = '?module=userprofile&username=';
    const ROUTE_SUBLIST = 'subscribers';
    const ROUTE_SUBAJ = 'ajaxlist';
    const ROUTE_SUBVIEW = 'showsubscriber';
    const ROUTE_TARIFFS = 'tariffs';
    const ROUTE_BUNDLES = 'bundles';
    const ROUTE_PLDEL = 'deleteplaylist';
    const ROUTE_SUBID = 'subscriberid';
    const ROUTE_DEVCREATE = 'createdevice';
    const ROUTE_DEVDEL = 'deletedevice';
    const ROUTE_SUBLOOKUP = 'username';
    const ROUTE_TARDEL = 'deletetariff';
    const PROUTE_SUBREG = 'registersubscriber';
    const PROUTE_CREATETARIFFID = 'newtariffserviceid';
    const PROUTE_CREATETARIFFMAIN = 'newtariffmainflag';
    const PROUTE_CREATETARIFFNAME = 'newtariffname';
    const PROUTE_CREATETARIFFCHANS = 'newtariffchans';
    const PROUTE_CREATETARIFFFEE = 'newtarifffee';
    const PROUTE_TARIFFEDITSUBID = 'changetariffsubscriberid';
    const PROUTE_SETMAINTARIFFID = 'changemaintariffserviceid';
    const PROUTE_SETADDTARIFFID = 'changeaddionaltariffs';

    /**
     * I keep my eyes low, looking for my rival
     * Eyes Low
     * Playing with the rifle
     * White dope
     * Feeling homicidal
     * Ride slow
     * Fucking up your spinal
     */
    public function __construct() {
        $this->initMessages();
        $this->loadConfig();
        $this->setOptions();
        $this->initApi();
        $this->loadUserData();
        $this->initSubscribersDb();
        $this->initTariffsDb();
        $this->loadSubscribers();
        $this->loadTariffs();
    }

    /**
     * Preloads required configs into protected props
     *
     * @global object $ubillingConfig
     *
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets required properties via config options
     *
     * @return void
     */
    protected function setOptions() {
        $this->login = $this->altCfg[self::OPTION_LOGIN];
        $this->password = $this->altCfg[self::OPTION_PASSWORD];
        $this->dealerID = $this->altCfg[self::OPTION_DEALER_ID];
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
     * Inits low-level API for further usage
     *
     * @return void
     */
    protected function initApi() {
        require_once ('api/libs/api.youtv.php');
        $this->api = new YouTV($this->login, $this->password, $this->dealerID);
    }

    /**
     * Inits subscribers database abstraction layer
     *
     * @return void
     */
    protected function initSubscribersDb() {
        $this->subscribersDb = new NyanORM(self::TABLE_SUBSCRIBERS);
    }

    /**
     * Inits tariffs database abstraction layer
     *
     * @return void
     */
    protected function initTariffsDb() {
        $this->tariffsDb = new NyanORM(self::TABLE_TARIFFS);
    }

    /**
     * Loads available subscribers from database
     *
     * @return void
     */
    protected function loadSubscribers() {
        $this->allSubscribers = $this->subscribersDb->getAll('login');
    }

    /**
     * Loads available tariffs from database
     *
     * @return void
     */
    protected function loadTariffs() {
        $this->allTariffs = $this->tariffsDb->getAll('serviceid');
    }

    /**
     * Loads available system users data
     *
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllData();
    }


    /**
     *  Registers a new user
     *
     * @param $userLogin
     * @return string
     * @throws Exception
     */
    public function userRegister($userLogin) {
        $result = '';

        $userLogin = ubRouting::filters($userLogin, 'mres');
        //user exists
        if (isset($this->allUserData[$userLogin])) {

            //email no empty
            if(!empty($this->allUserData[$userLogin]['email'])){

                //not registered yet
                if (!isset($this->allSubscribers[$userLogin])) {
                    $userData = $this->allUserData[$userLogin];
                    $newPassword = $userData['Password'];
                    $userRealName = $userData['realname'];
                    $userEmail = $userData['email'];

                    $response = $this->api->createUser($userLogin, $userRealName, $userEmail, $newPassword);

                    if(isset($response['data']['id'])){
                        //log subscriber
                        $newId = $response['data']['id'];
                        $this->subscribersDb->data('date', curdatetime());
                        $this->subscribersDb->data('subscriberid', $newId);
                        $this->subscribersDb->data('login', $userLogin);
                        $this->subscribersDb->data('active', '1');
                        $this->subscribersDb->create();

                        log_register('YouTV SUB REGISTER (' . $userLogin . ') AS [' . $newId . ']');
                    }

                    if(isset($response['errors']) and is_array($response['errors'])){
                        foreach ($response['errors']  as $io => $each ){
                            $result .= " <br>". $io.": " . $each[0] ;
                        }
                    }
                } else {
                    $result .= __('Something went wrong') . ': ' . __('User duplicate') . ' - ' . $userLogin;
                    log_register('YouTV SUB REGISTER (' . $userLogin . ') DUPLICATE FAIL');
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('Empty email') . ' - ' . $userLogin;
                log_register('YouTV SUB REGISTER (' . $userLogin . ') EMPTY EMAIL FAIL');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('User not exist') . ' - ' . $userLogin;
            log_register('YouTV SUB REGISTER (' . $userLogin . ') NOTEXIST FAIL');
        }

        return($result);
    }

    /**
     * Returns subscriber remote data
     *
     * @param $userLogin
     * @return bool
     */
    public function getUserData($userLogin) {

        if (isset($this->allSubscribers[$userLogin])) {
            $subscriberId = $this->allSubscribers[$userLogin]['subscriberid'];

            $response = $this->api->getUser($subscriberId);

            if (isset($response['data'])) {
                return  $response['data'];
            }
        }

        return false;
    }

    /**
     * Checks is some subscriberId associated with registered user?
     *
     * @param int $subscriberId
     *
     * @return bool
     */
    public function isValidSubscriber($subscriberId) {
        $subscriberId = ubRouting::filters($subscriberId, 'int');
        $result = false;
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if ($each['subscriberid'] == $subscriberId) {
                    $result = true;
                }
            }
        }
        return($result);
    }


    /**
     * Returns existing subscriber user login by its ID
     *
     * @param int $subscriberId
     *
     * @return string/bool
     */
    public function getSubscriberLogin($subscriberId) {
        $result = false;
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if ($each['subscriberid'] == $subscriberId) {
                    $result = $each['login'];
                }
            }
        }
        return($result);
    }

    /**
     * Returns subscripber ID by some of the users login
     *
     * @param string $userLogin
     *
     * @return int/bool
     */
    public function getSubscriberId($userLogin) {
        $result = false;
        if (isset($this->allSubscribers[$userLogin])) {
            $result = $this->allSubscribers[$userLogin]['subscriberid'];
        }
        return($result);
    }





    /**
     * Renders available subscribers JSON list
     *
     * @return void
     */
    public function renderSubsribersAjReply() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $userLogin => $eachSub) {
                if (isset($this->allUserData[$userLogin])) {
                    $data[] = $eachSub['subscriberid'];
                    $data[] = $eachSub['date'];
                    $userAddress = @$this->allUserData[$userLogin]['fulladress'];
                    $userRealName = @$this->allUserData[$userLogin]['realname'];
                    $profileLink = wf_Link(self::URL_USERPROFILE . $userLogin, web_profile_icon());
                    $subViewUrl = self::URL_ME . '&' . self::ROUTE_SUBVIEW . '=' . $userLogin;
                    $actLinks = wf_Link($subViewUrl, web_edit_icon());
                    $data[] = $profileLink . ' ' . $userAddress;
                    $data[] = $userRealName;
                    $data[] = $userLogin;
                    $data[] = $actLinks;
                    $json->addRow($data);
                    unset($data);
                }
            }
        }
        $json->getJson();
    }

    /**
     * Renders existing subscribers list container
     *
     * @return string
     */
    public function renderSubscribersList() {
        $result = '';
        $columns = array('ID', 'Date', 'Address', 'Real Name', 'Login','Actions');
        $opts = '"order": [[ 1, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&' . self::ROUTE_SUBAJ . '=true', false, __('Subscriptions'), 50, $opts);
        return($result);
    }

    /**
     * Renders subscriber services
     *
     * @param array $subData
     *
     * @return string
     */
    protected function renderServices($subData) {
        $result = '';

        if (!empty($subData['subscriptions'])) {


            foreach ($subData['subscriptions'] as $io => $each) {

                // is active
                if(strtotime($each['expires_at']) > time()){
                    if (isset($this->allTariffs[$each['price']])) {
                        $tariffLabel = $this->allTariffs[$each['price']]['name'];
                    } else {
                        $tariffLabel = $each['id'];
                    }
                    $result .= $tariffLabel . ' ';
                }
            }
        } else {
            $result .= __('No tariff');
        }
        return($result);
    }

    /**
     * Renders basic subscriber profile
     *
     * @param string $userLogin
     *
     * @return string
     */
    public function renderSubscriber($userLogin) {
        $result = '';
        if (isset($this->allUserData[$userLogin])) {
            if (isset($this->allSubscribers[$userLogin])) {
                $subscriberId = $this->allSubscribers[$userLogin]['subscriberid'];
                $subData = $this->getUserData($userLogin);
                $userData = $this->allUserData[$userLogin];

                if ($subData != false) {
                    $cells = wf_TableCell(__('Address'), '', 'row2');
                    $cells .= wf_TableCell(wf_Link(self::URL_USERPROFILE . $userLogin, web_profile_icon() . ' ' . $userData['fulladress']));
                    $rows = wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('ID'), '30%', 'row2');
                    $cells .= wf_TableCell($subData['id']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Cash'), '', 'row2');
                    $cells .= wf_TableCell($userData['Cash']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Credit'), '', 'row2');
                    $cells .= wf_TableCell($userData['Credit']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('IP'), '', 'row2');
                    $cells .= wf_TableCell($userData['ip']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Tariffs'), '', 'row2');
                    $cells .= wf_TableCell($this->renderServices($subData));
                    $rows .= wf_TableRow($cells, 'row3');
                    $result .= wf_TableBody($rows, '100%', 0, '');

                    //some user controls here
                    $result .= wf_delimiter(0);
                    $result .= $this->renderSubscriberControls($subscriberId, $subData);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Empty reply received'), 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_SUBSCRIBER_NOT_EXISTS', 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('User not exists'), 'error');
        }
        return($result);
    }

    /**
     * Renders users tariff change form
     *
     * @param int $subscriberId
     *
     * @return string
     */
    protected function renderUserTariffEditForm($subscriberId) {
        $result = '';
        if (!empty($this->allTariffs)) {

            $mainTariffsArr = array();
            $additionalTariffsArr = array();
            $userLogin = $this->getSubscriberLogin($subscriberId);
            $currentMainTariff = $this->allSubscribers[$userLogin]['maintariff'];
            foreach ($this->allTariffs as $io => $each) {
                if ($each['main']) {
                    $mainTariffsArr[$each['serviceid']] = $each['name'];
                } else {
                    $additionalTariffsArr[$each['serviceid']] = $each['name'];
                }
            }

            if (!empty($mainTariffsArr)) {
                $inputs = wf_HiddenInput(self::PROUTE_TARIFFEDITSUBID, $subscriberId);
                $inputs .= wf_Selector(self::PROUTE_SETMAINTARIFFID, $mainTariffsArr, __('Primary') . ' ' . __('Tariff'), $currentMainTariff, true);


                $inputs .= wf_Submit(__('Save'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Tariffs') . ': ' . __('Not exists'), 'error');
        }
        return($result);
    }

    /**
     * Sets primary tariff for some subscriber
     *
     * @param int $subscriberId
     * @param int $tariffId
     *
     * @return void
     */
    public function setMainTariff($subscriberId, $tariffId) {
        $tariffId = ubRouting::filters($tariffId, 'int');
        $subscriberId = ubRouting::filters($subscriberId, 'int');
        if ($this->isValidSubscriber($subscriberId)) {
            $userLogin = $this->getSubscriberLogin($subscriberId);
            $currentTariff = $this->allSubscribers[$userLogin]['maintariff'];

            //deleting old service if required
            if ($currentTariff) {
                $this->api->blockUser($subscriberId);
                log_register('YouTV SUB (' . $userLogin . ') UNSET TARIFF [' . $currentTariff . '] AS [' . $subscriberId . ']');
            }

            //database update
            $this->subscribersDb->data('maintariff', $tariffId);
            $this->subscribersDb->data('active', 1);
            $this->subscribersDb->where('subscriberid', '=', $subscriberId);
            $this->subscribersDb->save();

            //push to service API
            $this->api->subscriptions($subscriberId, $tariffId);

            //put log record
            log_register('YouTV SUB (' . $userLogin . ') SET TARIFF [' . $tariffId . '] AS [' . $subscriberId . ']');

        }
    }

    /**
     * Returns some subscriber controls
     *
     * @param int $subscriberId
     * @param array $subData
     *
     * @return string
     */
    protected function renderSubscriberControls($subscriberId, $subData = array()) {
        $result = '';
        if ($this->isValidSubscriber($subscriberId)) {

            $result .= wf_modalAuto(wf_img('skins/icon_tariff.gif') . ' ' . __('Edit tariff'), __('Tariff'), $this->renderUserTariffEditForm($subscriberId), 'ubButton');

            if (!empty($subData)) {
                $userScheme = wf_tag('pre') . print_r($subData, true) . wf_tag('pre', true);
                $result .= wf_modal(wf_img('skins/brain.png') . ' ' . __('User inside'), __('User inside'), $userScheme, 'ubButton', '800', '600');
            }
        }
        return($result);
    }

    /**
     * Renders basic user registration form
     *
     * @return string
     */
    protected function renderUserRegisterForm() {
        $result = '';
        $inputs = wf_TextInput(self::PROUTE_SUBREG, __('Login'), '', false, 20);

        $inputs .= wf_Submit(__('Register'));
        $result .= wf_Form("", 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders primary module controls
     *
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Users registration'), __('Users registration'), $this->renderUserRegisterForm(), 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SUBLIST . '=true', wf_img('skins/ukv/users.png') . ' ' . __('Subscriptions'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_TARIFFS . '=true', wf_img('skins/ukv/dollar.png') . ' ' . __('Tariffs'), false, 'ubButton') . ' ';
        return($result);
    }


    /**
     * Renders new tariff creation form
     *
     * @return string
     */
    protected function renderTariffCreateForm() {
        $result = '';
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs = wf_TextInput(self::PROUTE_CREATETARIFFID, __('Service ID') . $sup, '', true, 5, 'digits');
        $inputs .= wf_TextInput(self::PROUTE_CREATETARIFFNAME, __('Tariff name') . $sup, '', true, 20);
        $inputs .= wf_CheckInput(self::PROUTE_CREATETARIFFMAIN, __('Primary'), true, true);
        $inputs .= wf_TextInput(self::PROUTE_CREATETARIFFCHANS, __('Description'), '', true, 20);
        $inputs .= wf_TextInput(self::PROUTE_CREATETARIFFFEE, __('Fee') . $sup, '', true, 4, 'finance');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Create'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Creates new tariff in database
     *
     * @return void/string on error
     */
    public function createTariff() {
        $result = '';
        if (ubRouting::checkPost(array(self::PROUTE_CREATETARIFFNAME, self::PROUTE_CREATETARIFFID))) {
            $tariffId = ubRouting::post(self::PROUTE_CREATETARIFFID, 'int');
            $tariffName = ubRouting::post(self::PROUTE_CREATETARIFFNAME, 'mres');
            $tariffMain = (ubRouting::checkPost(self::PROUTE_CREATETARIFFMAIN)) ? 1 : 0;
            $tariffChans = ubRouting::post(self::PROUTE_CREATETARIFFCHANS, 'mres');
            $tariffFee = ubRouting::post(self::PROUTE_CREATETARIFFFEE);
            if ($tariffId) {
                if (!isset($this->allTariffs[$tariffId])) {
                    if ($tariffName) {
                        if (zb_checkMoney($tariffFee)) {
                            $this->tariffsDb->data('serviceid', $tariffId);
                            $this->tariffsDb->data('main', $tariffMain);
                            $this->tariffsDb->data('name', $tariffName);
                            $this->tariffsDb->data('chans', $tariffChans);
                            $this->tariffsDb->data('fee', $tariffFee);
                            $this->tariffsDb->create();
                            log_register('PTV TARIFF CREATE `' . $tariffName . '` AS [' . $tariffId . '] FEE `' . $tariffFee . '`');
                        } else {
                            $result .= __('Wrong format of money sum');
                        }
                    } else {
                        $result .= __('Wrong tariff name');
                    }
                } else {
                    $result .= __('Duplicate element ID');
                    log_register('PTV TARIFF CREATE `' . $tariffName . '` AS [' . $tariffId . '] DUPLICATE FAIL');
                }
            } else {
                $result .= __('Wrong tariff id');
            }
        }
        return($result);
    }

    /**
     * Deletes existing tariff from database
     *
     * @param int $tariffId
     *
     * @return void/string on error
     */
    public function deleteTariff($tariffId) {
        $result = '';
        $tariffId = ubRouting::filters($tariffId, 'int');
        if (isset($this->allTariffs[$tariffId])) {
            if ($this->isTariffProtected($tariffId)) {
                $result = __('Tariff is used by some users');
            } else {
                $tariffData = $this->allTariffs[$tariffId];
                $tariffName = $tariffData['name'];
                $tariffFee = $tariffData['fee'];
                $this->tariffsDb->where('serviceid', '=', $tariffId);
                $this->tariffsDb->delete();
                log_register('PTV TARIFF DELETE `' . $tariffName . '` AS [' . $tariffId . '] FEE `' . $tariffFee . '`');
            }
        } else {
            $result .= __('Tariff not exists');
        }

        return($result);
    }

    /**
     * Checks is some tariff protected of usage by some user
     *
     * @param int $tariffId
     *
     * @return bool
     */
    protected function isTariffProtected($tariffId) {
        $result = false;
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if ($each['maintariff'] == $tariffId) {
                    $result = true;
                }
                if (ispos($each['addtariffs'], $tariffId)) {
                    $result = true;
                }
            }
        }
        return($result);
    }

    /**
     * Renders list of tariffs available for users
     *
     * @return string
     */
    public function renderTariffs() {
        $result = '';
        $result .= wf_modalAuto(web_add_icon() . ' ' . __('Create new tariff'), __('Create new tariff'), $this->renderTariffCreateForm(), 'ubButton');
        $result .= wf_delimiter();
        if (!empty($this->allTariffs)) {
            $cells = wf_TableCell(__('Service ID'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Primary'));
            $cells .= wf_TableCell(__('Description'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allTariffs as $io => $each) {
                $cells = wf_TableCell($each['serviceid']);
                $cells .= wf_TableCell($each['name']);
                $cells .= wf_TableCell(web_bool_led($each['main']));
                $cells .= wf_TableCell($each['chans']);
                $cells .= wf_TableCell($each['fee']);
                $tariffsCancelUrl = self::URL_ME . '&' . self::ROUTE_TARIFFS . '=true';
                $tariffsDeleteUrl = self::URL_ME . '&' . self::ROUTE_TARDEL . '=' . $each['serviceid'];
                $tariffControls = wf_ConfirmDialog($tariffsDeleteUrl, web_delete_icon() . ' ' . __('Delete'), $this->messages->getDeleteAlert(), '', $tariffsCancelUrl);
                $cells .= wf_TableCell($tariffControls);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return($result);
    }

    /**
     * Charges some tariff fee from user account
     *
     * @param string $userLogin
     * @param int $tariffId
     *
     * @return void
     */
    public function chargeUserFee($userLogin, $tariffId) {
        if (isset($this->allUserData[$userLogin])) {
            $subscriberId = $this->getSubscriberId($userLogin);

            if (isset($this->allTariffs[$tariffId])) {
                $tariffFee = $this->allTariffs[$tariffId]['fee'];
                zb_CashAdd($userLogin, '-' . $tariffFee, 'add', 1, 'YOUTV:' . $tariffId);
                log_register('YouTV CHARGE TARIFF [' . $tariffId . '] FEE `' . $tariffFee . '` FOR (' . $userLogin . ') AS [' . $subscriberId . ']');
            } else {
                log_register('YouTV CHARGE FAIL NOTARIFF [' . $tariffId . '] FOR (' . $userLogin . ') AS [' . $subscriberId . ']');
            }
        } else {
            log_register('YouTV CHARGE FAIL NOUSER (' . $userLogin . ')');
        }
    }

    /**
     * Performs fee processing of all registered subscribers
     *
     * @return void
     */
    public function feeProcessing() {
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $eachSub) {
                $userLogin = $eachSub['login'];
                if (isset($this->allUserData[$userLogin])) {
                    //user subscription is active now
                    if ($eachSub['active']) {
                        if (($this->allUserData[$userLogin]['Passive'] == 0) and ($this->allUserData[$userLogin]['Cash'] >= '-' . $this->allUserData[$userLogin]['Credit'])) {
                            // можно снимать деньги
                            $this->chargeUserFee($eachSub['login'], $eachSub['maintariff']);
                        } else {
                            // бомж, блокируем активную подписку
                            $this->usUnsubscribe($eachSub['subscriberid'], $eachSub['maintariff']);
                        }
                    }
                } else {
                    log_register('YouTV CHARGE (' . $userLogin . ') AS [' . $eachSub . '] FAIL MISS');
                }
            }
        }
    }

    /**
     * Renders JSON reply for some userstats frontend requests
     *
     * @param array $reply
     *
     * @return void
     */
    protected function jsonRenderReply($reply) {
        $reply = json_encode($reply);
        die($reply);
    }

    /**
     * Renders user subscription data for some login
     *
     * @param string $userLogin
     *
     * @return void
     */
    public function usReplyUserData($userLogin) {
        $reply = array();
        if (isset($this->allSubscribers[$userLogin])) {
            $reply = $this->allSubscribers[$userLogin];
        }
        $this->jsonRenderReply($reply);
    }

    /**
     * Renders subscriber full data
     *
     * @param string $userLogin
     *
     * @return void
     */
    public function usReplyUserFullData($userLogin) {
        $reply = $this->getUserData($userLogin);
        $this->jsonRenderReply($reply);
    }

    /**
     * Renders available tariffs list
     *
     * @return void
     */
    public function usReplyTariffs() {
        $reply = array();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                if ($each['main']) {
                    $reply[$io] = $each;
                }
            }
        }
        $this->jsonRenderReply($reply);
    }

    /**
     * Just deactivates service for user account
     *
     * @param int $subscriberId
     * @param int $tariffId
     *
     * @return void
     */
    public function usUnsubscribe($subscriberId, $tariffId) {
        $reply = array();
        $userLogin = $this->getSubscriberLogin($subscriberId);

        $this->api->blockUser($subscriberId);

        $this->subscribersDb->data('active', '0');
        $this->subscribersDb->data('maintariff', '0');
        $this->subscribersDb->where('subscriberid', '=', $subscriberId);
        $this->subscribersDb->save();
        log_register('YouTV SUB (' . $userLogin . ') UNSET TARIFF [' . $tariffId . '] AS [' . $subscriberId . ']');
        $this->jsonRenderReply($reply);
    }

    /**
     * Subscribes user to some service
     *
     * @param string $subscriberId
     * @param int $tariffId
     *
     * @return void
     */
    public function usSubscribe($userLogin, $tariffId) {
        if (isset($this->allTariffs[$tariffId])) {
            //may be thats new user?
            $subscriberId = $this->getSubscriberId($userLogin);
            if (!$this->isValidSubscriber($subscriberId)) {
                $this->userRegister($userLogin);
                //update subscriberId
                $this->loadSubscribers();
                $subscriberId = $this->getSubscriberId($userLogin);
            }

            //just switch tariff
            if ($this->isValidSubscriber($subscriberId)) {
                $this->setMainTariff($subscriberId, $tariffId);
            }

            //charge tariff fee after
            $this->chargeUserFee($userLogin, $tariffId);
        }
    }
}
