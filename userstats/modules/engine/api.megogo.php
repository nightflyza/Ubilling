<?php

class MegogoFrontend {

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

    /**
     * Contains system config as key=>value
     *
     * @var array
     */
    protected $usConfig = array();

    /**
     * Current instance user login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * Contains Ubilling RemoteAPI URL
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * Contains Ubilling RemoteAPI Key
     *
     * @var string
     */
    protected $apiKey = '';

    public function __construct() {
        $this->loadUsConfig();
        $this->setOptions();
        $this->loadTariffs();
        $this->loadSubscribers();
        $this->loadHistory();
    }

    /**
     * Sets current user login
     * 
     * @return void
     */
    public function setLogin($login) {
        $this->userLogin = $login;
    }

    /**
     * Loads userstats config into protected usConfig variable
     * 
     * @return void
     */
    protected function loadUsConfig() {
        $this->usConfig = zbs_LoadConfig();
    }

    /**
     * Sets required object options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->apiUrl = $this->usConfig['API_URL'];
        $this->apiKey = $this->usConfig['API_KEY'];
    }

    /**
     * Loads existing tariffs from database for further usage
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `mg_tariffs` ORDER BY `primary` DESC";
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
     * Checks is user subscribed for some tariff or not?
     * 
     * @param string $login
     * @param int $tariffid
     * 
     * @return bool
     */
    protected function isUserSubscribed($login, $tariffid) {
        $result = false;
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if (($each['login'] == $login) AND ( $each['tariffid'] == $tariffid)) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders tariffs list with subscribtion form
     * 
     * @return string
     */
    public function renderSubscribeForm() {
        $result = '';
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $headerType = ($each['primary']) ? 'mgheaderprimary' : 'mgheader';
                $freePeriodLabel = ($each['primary']) ? __('Available') : __('Unavailable');
                $tariffInfo = la_tag('div', false, $headerType) . $each['name'] . la_tag('div', true);
                $cells = la_TableCell(la_tag('b') . __('Fee') . la_tag('b', true));
                $cells.= la_TableCell($each['fee'] . ' ' . $this->usConfig['currency']);
                $rows = la_TableRow($cells);
                $cells = la_TableCell(la_tag('b') . __('Free period') . la_tag('b', true));
                $cells.= la_TableCell($freePeriodLabel);
                $rows.= la_TableRow($cells);
                $tariffInfo.=la_TableBody($rows, '100%', 0);
                $tariffInfo.=la_delimiter();
                if ($this->isUserSubscribed($this->userLogin, $each['id'])) {
                    $subscribeControl = la_Link('?module=megogo&unsubscribe=' . $each['id'], __('Unsubscribe'), false, 'mgunsubcontrol');
                } else {
                    $subscribeControl = la_Link('?module=megogo&subscribe=' . $each['id'], __('Subscribe'), false, 'mgsubcontrol');
                }

                $tariffInfo.=$subscribeControl;
                $result.=la_tag('div', false, 'mgcontainer') . $tariffInfo . la_tag('div', true);
            }
        }
        return ($result);
    }

    /**
     * Runs default subscribtion routine
     * 
     * @return void/string on error
     */
    public function pushSubscribeRequest($tariffid) {
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=mgcontrol&param=subscribe&userlogin=' . $this->userLogin . '&tariffid=' . $tariffid;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Runs default unsubscribtion routine
     * 
     * @return void/string on error
     */
    public function pushUnsubscribeRequest($tariffid) {
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=mgcontrol&param=unsubscribe&userlogin=' . $this->userLogin . '&tariffid=' . $tariffid;
        @$result = file_get_contents($action);
        return ($result);
    }
    
    
    /**
     * 
     * 
     * @return string
     */
    public function getAuthButtonURL() {
        $result='';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=mgcontrol&param=auth&userlogin=' . $this->userLogin;
        $result=  file_get_contents($action);
        return ($result);
    }

}

?>