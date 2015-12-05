<?php

class MegogoApi {

    /**
     * System alter.ini config stored as array key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Partner ID property via MG_PARTNERID
     *
     * @var string
     */
    protected $partnerId = '';

    /**
     * Users ID prefixes via MG_PREFIX
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * Auth salt value via MG_SALT
     *
     * @var string
     */
    protected $salt = '';

    /**
     * subscribe/unsubscribe API URL
     *
     * @var string 
     */
    protected $urlApi = '';

    /**
     * Authorization API URL
     *
     * @var string
     */
    protected $urlAuth = '';

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
    }

    /**
     * Loads system alter config into private prop
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets basic configurable options for further usage
     * 
     * @return void
     */
    protected function setOptions() {
        $this->partnerId = $this->altCfg['MG_PARTNERID'];
        $this->prefix = $this->altCfg['MG_PREFIX'];
        $this->salt = $this->altCfg['MG_SALT'];
        $this->urlApi = 'http://billing.megogo.net/partners/';
        $this->urlAuth = 'http://megogo.net/auth/by_partners/';
    }

    /**
     * Subscribes user to some service
     * 
     * @param string $login Existing user login to subscribe
     * @param string $service Valid serviceid
     * 
     * @return bool
     */
    public function subscribe($login, $service) {
        $result = false;
        $query = $this->urlApi . $this->partnerId . '/subscription/subscribe?userId=' . $this->prefix . $login . '&serviceId=' . $service;
        $queryResult = file_get_contents($query);
        if (!empty($queryResult)) {
            $queryResult = json_decode($queryResult);
            if ($queryResult->successful) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Unsubscribes user for some service
     * 
     * @param string $login Existing user login to subscribe
     * @param string $service Valid serviceid
     * 
     * @return bool
     */
    public function unsubscribe($login, $service) {
        $result = false;
        $query = $this->urlApi . $this->partnerId . '/subscription/unsubscribe?userId=' . $this->prefix . $login . '&serviceId=' . $service;
        $queryResult = file_get_contents($query);
        if (!empty($queryResult)) {
            $queryResult = json_decode($queryResult);
            if ($queryResult->successful) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Returns auth codes
     * 
     * @param string $login Existing user login
     * @return strig
     */
    public function authCode($login) {
        $result = '';
        $hashData = $this->prefix . $login . $this->partnerId . $this->salt;
        $token = md5($hashData);
        $result = $this->urlAuth . 'dialog?isdn=' . $this->prefix . $login . '&partner_key=' . $this->partnerId . '&token=' . $token;
        return ($result);
    }

}

class MegogoInterface {

    /**
     * System alter.ini config stored as array key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System messages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains available megogo service tariffs id=>tariffdata
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains available and active megogo service subscriptions as id=>data
     *
     * @var array
     */
    protected $allSubscribers = array();

    /**
     * Contains all subscribtions history by all of users id=>data
     *
     * @var array
     */
    protected $allHistory = array();

    const URL_ME = '?module=testing';
    const URL_TARIFFS = 'tariffs=true';
    const URL_SUBS = 'subscriptions=true';
    const URL_AJSUBS = 'ajsubs=true';

    public function __construct() {
        $this->loadAlter();
        $this->initMessages();
        $this->loadTariffs();
        $this->loadSubscribers();
        $this->loadHistory();
    }

    /**
     * Loads system alter config into private prop
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Initializes system message helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads existing tariffs from database for further usage
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `mg_tariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffs[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing subscribers data
     * 
     * @return void
     */
    protected function loadSubscribers() {
        $query = "SELECT * from `mg_subscribers`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allSubscribers[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing subscribers data
     * 
     * @return void
     */
    protected function loadHistory() {
        $query = "SELECT * from `mg_history`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allHistory[$each['id']] = $each;
            }
        }
    }

    /**
     * Returns tariffs Megogo service ID
     * 
     * @param int $tariffid
     * 
     * @return string
     */
    public function getTariffServiceId($tariffid) {
        $tariffid = vf($tariffid, 3);
        $result = '';
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                if ($each['id'] == $tariffid) {
                    $result = $each['serviceid'];
                }
            }
        }
        return ($result);
    }

    /**
     * Create subscription
     * 
     * @param string $login
     * @param int $tariffid
     * 
     * @return void
     */
    public function createSubscribtion($login, $tariffid) {
        $curdatetime = curdatetime();
        $loginF = mysql_real_escape_string($login);
        $tariffid = vf($tariffid, 3);
        $activeFlag = 1;
        if (isset($this->allTariffs[$tariffid])) {
            $tariffData = $this->allTariffs[$tariffid];
            $query = "INSERT INTO `mg_subscribers` (`id`,`login`,`tariffid`,`actdate`,`active`,`primary`,`freeperiod`) VALUES";
            $query.="(NULL,'" . $loginF . "','" . $tariffid . "','" . $curdatetime . "','" . $activeFlag . "','" . $tariffData['primary'] . "','" . $tariffData['freeperiod'] . "');";
            nr_query($query);
            log_register('MEGOGO SUBSCRIBE (' . $login . ') TARIFF [' . $tariffid . ']');
            $mgApi = new MegogoApi();
            $mgApi->subscribe($login, $tariffData['serviceid']);
            log_register('MEGOGO ACTIVATED (' . $login . ') SERVICE [' . $tariffData['serviceid'] . ']');

            $queryHistory = "INSERT INTO `mg_history` (`id`,`login`,`tariffid`,`actdate`,`freeperiod`) VALUES";
            $queryHistory.="(NULL,'" . $loginF . "','" . $tariffid . "','" . $curdatetime . "'," . $tariffData['freeperiod'] . ")";
            nr_query($queryHistory);
        }
    }

    /**
     * Deletes existing subscription
     * 
     * @param string $login
     * @param int $tariffid
     * 
     * @return void
     */
    public function deleteSubscribtion($login, $tariffid) {
        $curdatetime = curdatetime();
        $loginF = mysql_real_escape_string($login);
        $tariffid = vf($tariffid, 3);
        $activeFlag = 1;
        if (isset($this->allTariffs[$tariffid])) {
            $tariffData = $this->allTariffs[$tariffid];
            $query = "DELETE from `mg_subscribers` WHERE `login`='" . $loginF . "' AND `tariffid`='" . $tariffid . "';";
            nr_query($query);
            log_register('MEGOGO UNSUBSCRIBE (' . $login . ') TARIFF [' . $tariffid . ']');
            $mgApi = new MegogoApi();
            $mgApi->unsubscribe($login, $tariffData['serviceid']);
            log_register('MEGOGO DEACTIVATED (' . $login . ') SERVICE [' . $tariffData['serviceid'] . ']');
        }
    }

    /**
     * Returns primary controls panel
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result.=wf_Link(self::URL_ME . '&' . self::URL_SUBS, wf_img('skins/ukv/users.png') . ' ' . __('Subscriptions'), false, 'ubButton') . ' ';
        $result.=wf_Link(self::URL_ME . '&' . self::URL_TARIFFS, wf_img('skins/ukv/dollar.png') . ' ' . __('Tariffs'), false, 'ubButton') . ' ';
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
        $cells.= wf_TableCell(__('Tariff name'));
        $cells.= wf_TableCell(__('Fee'));
        $cells.= wf_TableCell(__('Service ID'));
        $cells.= wf_TableCell(__('Primary'));
        $cells.= wf_TableCell(__('Free period'));
        $cells.= wf_TableCell(__('Actions'));

        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['name']);
                $cells.= wf_TableCell($each['fee']);
                $cells.= wf_TableCell($each['serviceid']);
                $cells.= wf_TableCell(web_bool_led($each['primary']));
                $cells.= wf_TableCell(web_bool_led($each['freeperiod']));
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_TARIFFS . '&deletetariffid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');

        return ($result);
    }

    /**
     * Returns tariff creation form
     * 
     * @return string
     */
    public function tariffCreateForm() {
        $result = '';

        $inputs = wf_TextInput('newtariffname', __('Tariff name'), '', true, '10');
        $inputs.= wf_TextInput('newtarifffee', __('Fee'), '', true, '5');
        $inputs.= wf_TextInput('newtariffserviceid', __('Service ID'), '', true, '10');
        $inputs.= wf_CheckInput('newtariffprimary', __('Primary'), true, false);
        $inputs.= wf_CheckInput('newtarifffreeperiod', __('Free period'), true, false);
        $inputs.= wf_Submit(__('Create'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Creates new tariff in database
     * 
     * @return void/string on error
     */
    public function tariffCreate() {
        $result = '';
        if (wf_CheckPost(array('newtariffname', 'newtarifffee', 'newtariffserviceid'))) {
            $nameF = mysql_real_escape_string($_POST['newtariffname']);
            $feeF = mysql_real_escape_string($_POST['newtarifffee']);
            $serviceidF = mysql_real_escape_string($_POST['newtariffserviceid']);
            $primary = wf_CheckPost(array('newtariffprimary')) ? 1 : 0;
            $freePeriod = wf_CheckPost(array('newtarifffreeperiod')) ? 1 : 0;

            if (zb_checkMoney($feeF)) {
                $query = "INSERT INTO `mg_tariffs` (`id`,`name`,`fee`,`serviceid`,`primary`,`freeperiod`) VALUES ";
                $query.= "(NULL,'" . $nameF . "','" . $feeF . "','" . $serviceidF . "','" . $primary . "','" . $freePeriod . "')";
                nr_query($query);
                $newId = simple_get_lastid('mg_tariffs');
                log_register('MEGOGO TARIFF CREATE [' . $newId . '] `' . $_POST['newtariffname'] . '` FEE `' . $_POST['newtarifffee'] . '`');
            } else {
                $result = $this->messages->getStyledMessage(__('Wrong format of a sum of money to pay'), 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('No all of required fields is filled'), 'error');
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
    public function tariffDelete($tariffId) {
        $tariffId = vf($tariffId, 3);
        $result = '';
        if (isset($this->allTariffs[$tariffId])) {
            //TODO: need tariff assigned by some users protector method
            $query = "DELETE from `mg_tariffs` WHERE `id`='" . $tariffId . "';";
            nr_query($query);
            log_register('MEGOGO TARIFF DELETE [' . $tariffId . ']');
        } else {
            $result = $this->messages->getStyledMessage(__('Not existing item'), 'error');
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
        $columns = array(__('ID'), __('User'), __('Current tariff'), __('Date'), __('Active'), __('Primary'), __('Free period'));
        $result = wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_SUBS . '&' . self::URL_AJSUBS, true, __('Subscriptions'), '100');
        return ($result);
    }

    /**
     * Renders ajax data subscriptions
     * 
     * @return void
     */
    public function subscribtionsListAjax() {
        $result = '{ 
                  "aaData": [ ';
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                $userLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false);
                $userLink = trim($userLink);
                $userLink = str_replace('"', '', $userLink);
                $actFlag = web_bool_led(web_bool_led($each['active'], false));
                $actFlag = str_replace('"', '', $actFlag);
                $primFlag = web_bool_led(web_bool_led($each['primary'], false));
                $primFlag = str_replace('"', '', $primFlag);
                $freeperiodFlag = web_bool_led(web_bool_led($each['freeperiod'], false));
                $freeperiodFlag = str_replace('"', '', $freeperiodFlag);

                $result.='
                    [
                    "' . $each['id'] . '",
                    "' . $userLink . '",
                    "' . @$this->allTariffs[$each['tariffid']]['name'] . '",
                    "' . $each['actdate'] . '",
                    "' . $actFlag . '",
                    "' . $primFlag . '",
                    "' . $freeperiodFlag . '"
                    ],';
            }
        }

        $result = zb_CutEnd($result);
        $result.='] 
        }';
        die($result);
    }

}

?>