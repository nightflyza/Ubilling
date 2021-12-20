<?php

class OllTVService {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System messages helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Olltv low-level API layer 
     *
     * @var object
     */
    protected $api = '';

    /**
     * OllTv subscribers database abstraction layer
     *
     * @var object
     */
    protected $subscribersDb = '';

    /**
     * OllTv tariffs database abstraction layer
     *
     * @var object
     */
    protected $tariffsDb = '';

    /**
     * Contains all available users data as login=>userData
     *
     * @var array
     */
    protected $allUsersData = array();

    /**
     * Contains pseudo-mail domain to generate subs emails
     *
     * @var string
     */
    protected $mailDomain = '';

    /**
     * Contains all existing subscribers data as login=>data
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains all available tariffs as id=>tariffData
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains all available tariff names as tariffId=>name
     *
     * @var array
     */
    protected $allTariffNames = array();

    /**
     * Country code to skip from mobile numbers
     *
     * @var string
     */
    protected $countryCode = '+38';

    //some predefined routes, urls, paths etc
    const LOG_PATH = 'exports/olltv.log';
    const TABLE_SUBSCRIBERS = 'ot_users';
    const TABLE_TARIFFS = 'ot_tariffs';
    const URL_ME = '?module=olltv';
    const ROUTE_SUBLIST = 'subscribers';
    const ROUTE_TARIFFS = 'tariffs';
    const ROUTE_DELTARIFF = 'deletetariffid';
    const ROUTE_AJSUBSLIST = 'ajsubscriberslist';
    const ROUTE_SUBSCRIBER = 'showsubscriber';
    const ROUTE_SUBSEARCH = 'username';
    const PROUTE_NEWTARIFF = 'createnewtariff';
    const PROUTE_EDITTARIFF = 'editariffid';
    const PROUTE_TARIFFNAME = 'newtariffname';
    const PROUTE_TARIFFALIAS = 'newtariffalias';
    const PROUTE_TARIFFFEE = 'newtarifffee';
    const PROUTE_TARIFFMAIN = 'newtariffmain';
    const PROUTE_SUBSETTARIF = 'settariffsublogin';
    const PROUTE_SUBTARIFFID = 'subsetariffid';

    /**
     * Creates new OLLTV service instance
     * 
     * @return object
     */
    public function __construct() {
        $this->initMessages();
        $this->loadAlter();
        $this->setOptions();
        $this->initApi();
        $this->loadUserData();
        $this->initSubscribers();
        $this->loadSubscribers();
        $this->initTariffs();
        $this->loadTariffs();
    }

    /**
     * Loads some required config data
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     *  Sets some properties
     * 
     * @return void
     */
    protected function setOptions() {
        $this->mailDomain = $this->altCfg['OLLTV_DOMAIN'];
    }

    /**
     * Inits messages helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits Olltv low-level API layer
     * 
     * @return void
     */
    protected function initApi() {
        if (!empty($this->altCfg['OLLTV_LOGIN']) AND ! empty($this->altCfg['OLLTV_PASSWORD'])) {
            $this->api = new OllTv($this->altCfg['OLLTV_LOGIN'], $this->altCfg['OLLTV_PASSWORD'], false, self::LOG_PATH, $this->altCfg['OLLTV_DEBUG']);
        } else {
            throw new Exception('EX_EMPTY_OLLTVOPTIONS');
        }
    }

    /**
     * Inits subscribers database abstraction layer
     * 
     * @return void
     */
    protected function initSubscribers() {
        $this->subscribersDb = new NyanORM(self::TABLE_SUBSCRIBERS);
    }

    /**
     * Loads available subscribers data from database
     * 
     * @return void
     */
    protected function loadSubscribers() {
        $this->allUsers = $this->subscribersDb->getAll('login');
    }

    /**
     * Inits tariffs database abstraction layer
     * 
     * @return void
     */
    protected function initTariffs() {
        $this->tariffsDb = new NyanORM(self::TABLE_TARIFFS);
    }

    /**
     * Loads available subscribers data from database
     * 
     * @return void
     */
    protected function loadTariffs() {
        $this->allTariffs = $this->tariffsDb->getAll('id');
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $this->allTariffNames[$each['id']] = $each['name'];
            }
        }
    }

    /**
     * Loads all available users data from database
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUsersData = zb_UserGetAllData();
    }

    /**
     * Transforms stdObject into array
     * 
     * @param mixed $data
     * 
     * @return array
     */
    protected function makeArray($data) {
        $result = array();
        if (!empty($data)) {
            $result = json_decode(json_encode($data), true);
        }
        return($result);
    }

    /**
     * Returns existing users array
     * 
     * @return array
     */
    public function getUserList() {
        $result = $this->makeArray($this->api->getUserList());
        return($result);
    }

    /**
     * Generates user pseudo-mail or returns real mail if it exists in database
     * 
     * @param string $login
     * 
     * @return string
     */
    protected function generateMail($login) {
        if (!empty($this->mailDomain)) {
            $result = $login . '@' . $this->mailDomain;
        }

        if (isset($this->allUsersData[$login])) {
            if (!empty($this->allUsersData[$login]['email'])) {
                $result = $this->allUsersData[$login]['email'];
            }
        }
        return($result);
    }

    /**
     * Prepares mobile number for registration
     * 
     * @param string $mobile
     * 
     * @return string
     */
    protected function prepareMobile($mobile) {
        $result = '';
        if (!empty($mobile)) {
            $result = str_replace($this->countryCode, '', $mobile);
        }
        return($result);
    }

    /**
     * Creates new subscriber depends on system user data
     * 
     * @param string $login Existing user login
     * 
     * @return void/string on error
     */
    public function createSubscriber($login) {
        $result = '';
        if (!isset($this->allUsers[$login])) {
            if (isset($this->allUsersData[$login])) {
                $userData = $this->allUsersData[$login];
                $mail = $this->generateMail($login);
                if (!empty($mail)) {
                    $mobile = $this->prepareMobile($userData['mobile']);
                    if (!empty($mobile)) {
                        $addParams = array('phone' => $mobile);
                        $creationResult = $this->api->addUser($mail, $login, $addParams);
                        if ($creationResult) {
                            $result = $creationResult;
                            //registering new subscriber in local database
                            $this->subscribersDb->data('date', curdatetime());
                            $this->subscribersDb->data('remoteid', $creationResult);
                            $this->subscribersDb->data('login', $login);
                            $this->subscribersDb->data('email', $mail);
                            $this->subscribersDb->data('phone', $mobile);
                            $this->subscribersDb->create();
                            log_register('OLLTV CREATE SUBSCRIBER (' . $login . ') AS [' . $creationResult . ']');
                        }
                    } else {
                        $result .= 'Empty mobile';
                    }
                } else {
                    $result .= 'Empty email';
                }
            } else {
                $result .= 'User not exists';
            }
        } else {
            $result .= 'User already exists';
        }
        return($result);
    }

    /**
     * Returns existing olltv subscriber data
     * 
     * @param string $login
     * 
     * @return array
     */
    public function getSubscriberData($login) {
        $result = $this->makeArray($this->api->getUserInfo(array('account' => $login)));
        return($result);
    }

    /**
     * Deletes existing subscriber
     * 
     * @param string $login
     * 
     * @return void
     */
    public function deleteSubscriber($login) {
        $params = array('account' => $login);
        $subscriberData = $this->getSubscriberData($login);
        if (!empty($subscriberData)) {
            $this->api->deleteAccount($params);
            $this->subscribersDb->where('login', '=', $login);
            $this->subscribersDb->delete();
            log_register('OLLTV DELETE SUBSCRIBER (' . $login . ')');
        }
    }

    /**
     * Renders existing subscribers list
     * 
     * @return string
     */
    public function renderSubscribersList() {
        $result = '';
        if (!empty($this->allUsers)) {
            $columns = array('ID', 'Login', 'Real Name', 'Full address', 'Cash', 'Current tariff', 'Date', 'Active', 'Actions');
            $opts = '"order": [[ 0, "desc" ]]';
            $result .= wf_JqDtLoader($columns, self::URL_ME . '&' . self::ROUTE_AJSUBSLIST . '=true', false, __('Users'), 100, $opts);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return($result);
    }

    /**
     * Renders existing subscribers ajax list
     */
    public function ajSubscribersList() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $eachLogin => $subData) {
                $userData = $this->allUsersData[$eachLogin];
                $userAddress = (isset($userData['fulladress'])) ? $userData['fulladress'] : '';
                if (!empty($userAddress)) {
                    $userLink = wf_Link(UserProfile::URL_PROFILE . $eachLogin, web_profile_icon() . ' ' . $userAddress);
                } else {
                    $userLink = $eachLogin;
                }
                $data[] = $subData['id'];
                $data[] = $subData['login'];
                $data[] = @$userData['realname'];
                $data[] = $userLink;
                $data[] = @$userData['Cash'];
                $data[] = @$this->allTariffNames[$subData['tariffid']];
                $data[] = $subData['date'];
                $data[] = web_bool_led($subData['active'], true);
                $subControls = wf_Link(self::URL_ME . '&' . self::ROUTE_SUBSCRIBER . '=' . $subData['login'], web_edit_icon());
                $data[] = $subControls;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Renders module controls
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SUBLIST . '=true', wf_img('skins/ukv/users.png') . ' ' . __('Subscriptions'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_TARIFFS . '=true', wf_img('skins/ukv/dollar.png') . ' ' . __('Tariffs'), false, 'ubButton');
        return($result);
    }

    /**
     * Renders available tariffs list
     * 
     * @return string
     */
    public function renderTariffsList() {
        $result = '';
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new tariff'), __('Create new tariff'), $this->renderTariffCreateForm(), 'ubButton');
        $result .= wf_delimiter(1);

        if (!empty($this->allTariffs)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Service ID'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Primary'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allTariffs as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['name']);
                $cells .= wf_TableCell($each['alias']);
                $cells .= wf_TableCell($each['fee']);
                $cells .= wf_TableCell(web_bool_led($each['main']));
                $delUrl = self::URL_ME . '&' . self::ROUTE_DELTARIFF . '=' . $each['id'];
                $tariffControls = wf_JSAlert($delUrl, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $tariffControls .= wf_modalAuto(web_edit_icon(), __('Edit tariff') . ' ' . $each['name'], $this->renderTariffEditForm($each['id']));

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
     * Renders new tariff creation form
     * 
     * @return string
     */
    protected function renderTariffCreateForm() {
        $result = '';
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs = wf_HiddenInput(self::PROUTE_NEWTARIFF, 'true');
        $inputs .= wf_TextInput(self::PROUTE_TARIFFNAME, __('Tariff name') . $sup, '', true, 20);
        $inputs .= wf_TextInput(self::PROUTE_TARIFFALIAS, __('Service ID') . $sup, '', true, 20);
        $inputs .= wf_TextInput(self::PROUTE_TARIFFFEE, __('Fee') . $sup, '', true, 5, 'finance');
        $inputs .= wf_CheckInput(self::PROUTE_TARIFFMAIN, __('Primary'), true, true);
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders existing tariff editing form
     * 
     * @param int $tariffId
     * 
     * @return string
     */
    protected function renderTariffEditForm($tariffId) {
        $result = '';
        $tariffId = ubRouting::filters($tariffId, 'int');
        if (isset($this->allTariffs[$tariffId])) {
            $tariffData = $this->allTariffs[$tariffId];
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs = wf_HiddenInput(self::PROUTE_EDITTARIFF, $tariffId);
            $inputs .= wf_TextInput(self::PROUTE_TARIFFNAME, __('Tariff name') . $sup, $tariffData['name'], true, 20);
            $inputs .= wf_TextInput(self::PROUTE_TARIFFALIAS, __('Service ID') . $sup, $tariffData['alias'], true, 20);
            $inputs .= wf_TextInput(self::PROUTE_TARIFFFEE, __('Fee') . $sup, $tariffData['fee'], true, 5, 'finance');
            $inputs .= wf_CheckInput(self::PROUTE_TARIFFMAIN, __('Primary'), true, $tariffData['main']);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Tariff') . ' [' . $tariffId . ']' . ' ' . __('Not exists'), 'error');
        }
        return($result);
    }

    /**
     * Creates new tariff in database
     * 
     * @return void
     */
    public function createTariff() {
        if (ubRouting::checkPost(self::PROUTE_NEWTARIFF)) {
            if (ubRouting::checkPost(array(self::PROUTE_TARIFFNAME, self::PROUTE_TARIFFALIAS))) {
                $this->tariffsDb->data('name', ubRouting::post(self::PROUTE_TARIFFNAME, 'mres'));
                $this->tariffsDb->data('alias', ubRouting::post(self::PROUTE_TARIFFALIAS, 'mres'));
                $this->tariffsDb->data('fee', ubRouting::post(self::PROUTE_TARIFFFEE));
                $this->tariffsDb->data('period', 'month');
                $isMain = (ubRouting::checkPost(self::PROUTE_TARIFFMAIN)) ? 1 : 0;
                $this->tariffsDb->data('main', $isMain);
                $this->tariffsDb->create();
                $newId = $this->tariffsDb->getLastId();
                log_register('OLLTV CREATE TARIFF [' . $newId . ']');
            }
        }
    }

    /**
     * Saves tariff data in database
     * 
     * @return void
     */
    public function saveTariff() {
        if (ubRouting::checkPost(self::PROUTE_EDITTARIFF)) {
            $tariffId = ubRouting::post(self::PROUTE_EDITTARIFF, 'int');

            if (ubRouting::checkPost(array(self::PROUTE_TARIFFNAME, self::PROUTE_TARIFFALIAS))) {
                $this->tariffsDb->where('id', '=', $tariffId);
                $this->tariffsDb->data('name', ubRouting::post(self::PROUTE_TARIFFNAME, 'mres'));
                $this->tariffsDb->data('alias', ubRouting::post(self::PROUTE_TARIFFALIAS, 'mres'));
                $this->tariffsDb->data('fee', ubRouting::post(self::PROUTE_TARIFFFEE));
                $this->tariffsDb->data('period', 'month');
                $isMain = (ubRouting::checkPost(self::PROUTE_TARIFFMAIN)) ? 1 : 0;
                $this->tariffsDb->data('main', $isMain);
                $this->tariffsDb->save();
                log_register('OLLTV SAVE TARIFF [' . $tariffId . ']');
            }
        }
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
        if (!$this->isTariffProtected($tariffId)) {
            $this->tariffsDb->where('id', '=', $tariffId);
            $this->tariffsDb->delete();
            log_register('OLLTV DELETE TARIFF [' . $tariffId . ']');
        } else {
            log_register('OLLTV DELETE TARIFF FAIL [' . $tariffId . '] IS_PROTECTED');
        }
        return($result);
    }

    /**
     * Returns existing subscriberId by user login
     * 
     * @param string $login
     * 
     * @return int/bool
     */
    public function getSubscriberId($login) {
        $result = false;
        if (isset($this->allUsers[$login])) {
            $result = $this->allUsers[$login]['id'];
        }
        return($result);
    }

    /**
     * Renders tariff apply form for some subscriber login
     * 
     * @param string $subscriberLogin
     * 
     * @return string
     */
    public function renderTariffChangeForm($subscriberLogin) {
        $result = '';
        $subData = $this->allUsers[$subscriberLogin];
        $inputs = wf_HiddenInput(self::PROUTE_SUBSETTARIF, $subscriberLogin);
        $tariffSelector = array('' => '-');
        $tariffSelector += $this->allTariffNames;
        $inputs .= wf_Selector(self::PROUTE_SUBTARIFFID, $tariffSelector, __('Tariff'), $subData['tariffid'], false) . ' ';
        $inputs .= wf_Submit(__('Change'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders existing subscriber profile
     * 
     * @param login $login
     * 
     * @return string
     */
    public function renderSubscriberProfile($login) {
        $result = '';
        if (isset($this->allUsers[$login])) {
            $subData = $this->allUsers[$login];
            $userData = $this->allUsersData[$login];
            $remoteData = $this->api->getUserInfo(array('account' => $login));

            $cells = wf_TableCell(__('Real Name'), '50%', 'row2');
            $cells .= wf_TableCell($userData['realname']);
            $rows = wf_TableRow($cells, 'row3');


            $cells = wf_TableCell(__('Login'), '', 'row2');
            $cells .= wf_TableCell($subData['login']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Full address'), '', 'row2');
            $userLink = wf_Link(UserProfile::URL_PROFILE . $subData['login'], web_profile_icon() . ' ' . $userData['fulladress']);
            $cells .= wf_TableCell($userLink);
            $rows .= wf_TableRow($cells, 'row3');


            $cells = wf_TableCell(__('Email'), '', 'row2');
            $cells .= wf_TableCell($subData['email']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Mobile'), '', 'row2');
            $cells .= wf_TableCell($subData['phone']);
            $rows .= wf_TableRow($cells, 'row3');


            $cells = wf_TableCell(__('Tariff'), '', 'row2');
            $tariffName = (isset($this->allTariffNames[$subData['tariffid']])) ? $this->allTariffNames[$subData['tariffid']] : __('No');
            $cells .= wf_TableCell($tariffName);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Date'), '', 'row2');
            $cells .= wf_TableCell($subData['date']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Cash'), '', 'row2');
            $cells .= wf_TableCell($userData['Cash']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Active'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($subData['active']));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Activation code'), '', 'row2');
            $cells .= wf_TableCell($subData['code']);
            $rows .= wf_TableRow($cells, 'row3');

            if ($this->altCfg['OLLTV_DEBUG']) {
                $cells = wf_TableCell(__('User inside'), '', 'row2');
                $remoteProfile = wf_tag('pre') . print_r($remoteData, true) . wf_tag('pre', true);
                $cells .= wf_TableCell(wf_modalAuto(__('Show'), __('User inside'), $remoteProfile));
                $rows .= wf_TableRow($cells, 'row3');
            }

            $result .= wf_TableBody($rows, '100%', 0);
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }
        return($result);
    }

    /**
     * Renders user devices if they available
     * 
     * @param string $login
     * 
     * @return string
     */
    public function renderUserDevices($login) {
        $result = '';
        $userDevices = $this->api->getDeviceList($login);
        if (!empty($userDevices)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Serial number'));
            $cells .= wf_TableCell(__('MAC'));
            $cells .= wf_TableCell(__('Code'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($userDevices as $io => $eachDevice) {
                $eachDevice = $this->makeArray($eachDevice);
                $cells = wf_TableCell($eachDevice['ID']);
                $cells .= wf_TableCell($eachDevice['date_added']);
                $cells .= wf_TableCell($eachDevice['serial_number']);
                $cells .= wf_TableCell($eachDevice['mac']);
                $cells .= wf_TableCell($eachDevice['binding_code']);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Sets some tariff for selected subscriber
     * 
     * @param string $login
     * @param int $tariffId
     * 
     * @return void/string on error
     */
    public function setSubTariffId($login, $tariffId) {
        $result = '';
        if (isset($this->allUsers[$login])) {
            $userData = $this->allUsers[$login];
            if (isset($this->allTariffs[$tariffId])) {
                $tariffData = $this->allTariffs[$tariffId];
                $check = $this->api->checkBundle(array('account' => $login), $tariffData['alias']);

                if ($check !== false) {
                    //unsubscribe old tariff if required
                    if ($userData['tariffid']) {
                        $oldUserTariffData = $this->allTariffs[$userData['tariffid']];
                        $oldUserTariffAlias = $oldUserTariffData['alias'];
                        $bundleDeleteResult = $this->api->disableBundle(array('account' => $login), $oldUserTariffAlias, 'subs_no_device');
                        log_register('OLLTV SUBSCRIBER (' . $login . ') UNSET TARIFF [' . $userData['tariffid'] . ']');
                    }

                    $bundleSetResult = $this->api->enableBundle(array('account' => $login), $tariffData['alias'], 'subs_no_device');
                    if ($bundleSetResult) {
                        $this->subscribersDb->where('id', '=', $userData['id']);
                        //devices activation code here
                        $this->subscribersDb->data('code', $bundleSetResult);

                        //write tariff to sub profile
                        $this->subscribersDb->data('tariffid', $tariffId);
                        $this->subscribersDb->data('active', 1);
                        $this->subscribersDb->save();
                        log_register('OLLTV SUBSCRIBER (' . $login . ') SET TARIFF [' . $tariffId . ']');
                    }
                } else {
                    log_register('OLLTV SUBSCRIBER (' . $login . ') TARIFF [' . $userTariffId . '] NOT_ALLOWED');
                    $result .= 'Tariff not allowed';
                }
            } else {
                //unsub on empty tariff
                if (empty($tariffId)) {
                    $userTariffId = $userData['tariffid'];
                    if (isset($this->allTariffs[$userTariffId])) {
                        $userTariffAlias = $this->allTariffs[$userTariffId]['alias'];
                        $bundleDeleteResult = $this->api->disableBundle(array('account' => $login), $userTariffAlias, 'subs_no_device');
                        $this->subscribersDb->where('id', '=', $userData['id']);
                        //devices activation code cleanup here
                        $this->subscribersDb->data('code', '');
                        //write tariff to sub profile
                        $this->subscribersDb->data('tariffid', 0);
                        $this->subscribersDb->data('active', 0);
                        $this->subscribersDb->save();
                        log_register('OLLTV SUBSCRIBER (' . $login . ') DROP TARIFF [' . $userTariffId . ']');
                    } else {
                        $result .= 'Tariff not exists';
                    }
                }
            }
        } else {
            $result .= 'Subscriber not exists';
        }

        return($result);
    }

    /**
     * Suspends existing user
     * 
     * @param string $login
     * 
     * @return void
     */
    public function suspendSubscriber($login) {
        if (isset($this->allUsers[$login])) {
            $userData = $this->allUsers[$login];
            $userTariff = $userData['tariffid'];
            if (isset($this->allTariffs[$userTariff])) {
                $tariffData = $this->allTariffs[$userTariff];
                if ($userData['active']) {
                    $bundleSuspResult = $this->api->disableBundle(array('account' => $login), $tariffData['alias'], 'subs_negative_balance');
                    $this->subscribersDb->where('id', '=', $userData['id']);
                    $this->subscribersDb->data('active', '0');
                    $this->subscribersDb->save();
                    log_register('OLLTV SUBSCRIBER (' . $login . ') SUSPEND TARIFF [' . $userTariff . ']');
                } else {
                    log_register('OLLTV SUBSCRIBER (' . $login . ') SUSPEND FAIL NOT_ACTIVE');
                }
            } else {
                log_register('OLLTV SUBSCRIBER (' . $login . ') SUSPEND FAIL NO_TARIFF [' . $userTariff . ']');
            }
        } else {
            log_register('OLLTV SUBSCRIBER (' . $login . ') SUSPEND FAIL USER_NOT_EXISTS');
        }
    }

    /**
     * Unsuspends existing user
     * 
     * @param string $login
     * 
     * @return void
     */
    public function unsuspendSubscriber($login) {
        if (isset($this->allUsers[$login])) {
            $userData = $this->allUsers[$login];
            $userTariff = $userData['tariffid'];
            if (isset($this->allTariffs[$userTariff])) {
                $tariffData = $this->allTariffs[$userTariff];
                if (!$userData['active']) {
                    $bundleSuspResult = $this->api->enableBundle(array('account' => $login), $tariffData['alias'], 'subs_renew');
                    $this->subscribersDb->where('id', '=', $userData['id']);
                    $this->subscribersDb->data('active', '1');
                    $this->subscribersDb->save();
                    log_register('OLLTV SUBSCRIBER (' . $login . ') UNSUSPEND TARIFF [' . $userTariff . ']');
                } else {
                    log_register('OLLTV SUBSCRIBER (' . $login . ') UNSUSPEND FAIL ALREADY_ACTIVE');
                }
            } else {
                log_register('OLLTV SUBSCRIBER (' . $login . ') UNSUSPEND FAIL NO_TARIFF [' . $userTariff . ']');
            }
        } else {
            log_register('OLLTV SUBSCRIBER (' . $login . ') UNSUSPEND FAIL USER_NOT_EXISTS');
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
        if (isset($this->allUsers[$userLogin])) {
            $reply = $this->allUsers[$userLogin];
        }
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
                $reply[$io] = $each;
            }
        }
        $this->jsonRenderReply($reply);
    }

    /**
     * Renders user devices list
     * 
     * @param string $userLogin
     * 
     * @return void
     */
    public function usReplyDevices($userLogin) {
        $reply = array();
        $userDevices = $this->api->getDeviceList($userLogin);
        if (!empty($userDevices)) {
            foreach ($userDevices as $io => $eachDevice) {
                $eachDevice = $this->makeArray($eachDevice);
                $reply[] = $eachDevice;
            }
        }
        $this->jsonRenderReply($reply);
    }

    /**
     * Just deactivates service from user account
     * 
     * @param string $userLogin
     * @param int $tariffId
     * 
     * @return void
     */
    public function usUnsubscribe($userLogin, $tariffId) {
        $reply = array();
        if (isset($this->allUsers[$userLogin])) {
            $userData = $this->allUsers[$userLogin];
            $userTariffId = $userData['tariffid'];
            if ($userTariffId == $tariffId) {
                $this->setSubTariffId($userLogin, 0);
            } else {
                log_register('OLLTV SUBSCRIBER (' . $login . ') DROP TARIFF [' . $tariffId . '] FAILED [' . $userTariffId . '] MISMATCH');
            }
        }

        $this->jsonRenderReply($reply);
    }

    /**
     * Subscribes user to some service
     * 
     * @param string $userLogin
     * @param int $tariffId
     * 
     * @return void
     */
    public function usSubscribe($userLogin, $tariffId) {
        $reply = array();
        if (isset($this->allTariffs[$tariffId])) {
            //may be thats new user?
            $subscriberId = $this->getSubscriberId($userLogin);
            if (!$subscriberId) {
                $creationResult = $this->createSubscriber($userLogin);
                if (!empty($creationResult)) {
                    $reply['error'] = $creationResult;
                }
                //update subscriberId
                $this->loadSubscribers();
                $subscriberId = $this->getSubscriberId($userLogin);
            }

            //subscriber exists
            if ($subscriberId) {
                //just switch tariff
                $tariffChangeResult = $this->setSubTariffId($userLogin, $tariffId);

                if (empty($tariffChangeResult)) {
                    //charge tariff fee after: TODO
                    $this->chargeUserFee($userLogin, $tariffId);
                } else {
                    $reply['error'] = $tariffChangeResult;
                }
            } else {
                $reply['error'] = 'Something went wrong';
            }
        } else {
            $reply['error'] = 'Wrong tariff';
        }
        $this->jsonRenderReply($reply);
    }

    /**
     * Charges some tariff fee from existing user account
     * 
     * @param string $userLogin
     * @param int $tariffId
     * 
     * @return void/string on error
     */
    public function chargeUserFee($userLogin, $tariffId) {
        $result = '';
        if (isset($this->allUsers[$userLogin]) AND isset($this->allUsersData[$userLogin])) {
            if (isset($this->allTariffs[$tariffId])) {
                $subscriberId = $this->getSubscriberId($userLogin);
                $tariffFee = $this->allTariffs[$tariffId]['fee'];
                zb_CashAdd($userLogin, '-' . $tariffFee, 'add', 1, 'OLLTV:' . $tariffId);
                log_register('OLLTV CHARGE TARIFF [' . $tariffId . '] FEE `' . $tariffFee . '` FOR (' . $userLogin . ') AS [' . $subscriberId . ']');
            } else {
                log_register('OLLTV CHARGE FAIL NOTARIFF [' . $tariffId . '] FOR (' . $userLogin . ') AS [' . $subscriberId . ']');
            }
        } else {
            log_register('OLLTV CHARGE FAIL NOUSER (' . $userLogin . ')');
        }
        return($result);
    }

    /**
     * Check is tariff used by someone of existing users?
     * 
     * @param int $tariffId
     * 
     * @return bool
     */
    public function isTariffProtected($tariffId) {
        $tariffId = ubRouting::filters($tariffId, 'int');
        $result = false;
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                if ($each['tariffid'] == $tariffId) {
                    $result = true;
                }
            }
        }
        return($result);
    }

}
