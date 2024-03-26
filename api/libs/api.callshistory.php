<?php

/**
 * Performs view/search/display of incoming calls data received with PBXNum
 */
class CallsHistory {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Calls log data source table
     *
     * @var string
     */
    protected $dataSource = '';

    /**
     * Contains previously loaded calls as id=>callData
     *
     * @var array
     */
    protected $allCalls = array();

    /**
     * May contains login filter for calls
     *
     * @var string
     */
    protected $loginSearch = '';

    /**
     * Contains user assigned tags as login=>usertags
     *
     * @var array
     */
    protected $userTags = array();

    /**
     * Incoming calls database abstraction layer
     *
     * @var object
     */
    protected $callsDb = '';

    /**
     * URL of user profile route
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Default module URL
     */
    const URL_ME = '?module=callshist';

    /**
     * Creates new CallsHistory instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfig();
        $this->initDb();
    }

    /**
     * Sets user login to filter
     * 
     * @param string $login
     * 
     * @return void
     */
    public function setLogin($login = '') {
        $this->loginSearch = ubRouting::filters($login, 'mres');
    }

    /**
     * Loads required configs and sets some options
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->dataSource = PBXNum::LOG_TABLE;
    }

    /**
     * Inits incoming calls database abstraction layer
     * 
     * @return void
     */
    protected function initDb() {
        $this->callsDb = new NyanORM($this->dataSource);
    }

    /**
     * Loads some calls list into protected property
     * 
     * @return void
     */
    protected function loadCalls() {
        if (!empty($this->loginSearch)) {
            //login search with full date range
            $this->callsDb->where('login', '=', $this->loginSearch);
        } else {
            //or just for current year
            $this->callsDb->where('date', 'LIKE', curyear() . '-%');
        }

        $this->allCalls = $this->callsDb->getAll('id');
    }

    /**
     * Loads existing tagtypes and usertags into protected props for further usage
     * 
     * @return void
     */
    protected function loadUserTags() {
        $this->userTags = zb_UserGetAllTags();
    }

    /**
     * Renders user tags if available
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    protected function renderUserTags($userLogin) {
        $result = '';
        if (!empty($userLogin)) {
            if (isset($this->userTags[$userLogin])) {
                if (!empty($this->userTags[$userLogin])) {
                    $result .= implode(', ', $this->userTags[$userLogin]);
                }
            }
        }
        return ($result);
    }

    /**
     * Renders calls log container
     * 
     * @return string
     */
    public function renderCalls() {
        $result = '';
        $columns = array('Date', 'Number', 'User', 'Tariff', 'Tags');
        $opts = '"order": [[ 0, "desc" ]]';
        $loginFilter = (!empty($this->loginSearch)) ? '&username=' . $this->loginSearch : '';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&ajaxcalls=true' . $loginFilter, false, __('Calls'), 100, $opts);
        return ($result);
    }

    /**
     * Renders ajax data source with loaded calls history
     * 
     * @return void
     */
    public function renderCallsAjaxList() {
        //loading some data
        $this->loadCalls();
        $this->loadUserTags();
        $allUserData = zb_UserGetAllDataCache();

        $json = new wf_JqDtHelper();
        $directionIcon = wf_img('skins/calls/incoming.png'); //thinking about future
        if (!empty($this->allCalls)) {
            foreach ($this->allCalls as $io => $each) {
                if (!empty($each['login'])) {
                    $userRealName = @$allUserData[$each['login']]['realname'];
                    $userTariff = @$allUserData[$each['login']]['Tariff'];
                    $userLink = wf_Link(self::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . @$allUserData[$each['login']]['fulladress']) . ' ' . $userRealName;
                    $userTags = $this->renderUserTags($each['login']);
                } else {
                    $userLink = '';
                    $userRealName = '';
                    $userTariff = '';
                    $userTags = '';
                }

                $data[] = $directionIcon . ' ' . $each['date'];
                $data[] = $each['number'];
                $data[] = $userLink;
                $data[] = $userTariff;
                $data[] = $userTags;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Updates data for calls without previously guessed user login
     * 
     * @param bool $rawResult
     * 
     * @return string|array
     */
    public function updateUnknownLogins($rawResult = false) {
        set_time_limit(0);
        $messages = new UbillingMessageHelper();
        $this->loadCalls();
        $telepathy = new Telepathy(false, true, false, true);
        $telepathy->usePhones();

        $result = '';
        $countGuessed = 0;
        $countMissed = 0;
        if (!empty($this->allCalls)) {
            foreach ($this->allCalls as $io => $each) {
                //user unknown
                if (empty($each['login'])) {
                    $detectedLogin = $telepathy->getByPhone($each['number'], true, true);
                    if (!empty($detectedLogin)) {
                        $this->callsDb->data('login', $detectedLogin);
                        $this->callsDb->where('id', '=', $each['id']);
                        $this->callsDb->save();
                        $notification = $each['date'] . ' ' . $each['number'] . ' ' . __('Assigned') . ' ' . $detectedLogin;
                        $result .= $messages->getStyledMessage($notification, 'success');
                        $countGuessed++;
                    } else {
                        $countMissed++;
                    }
                }
            }
        }

        if ($rawResult) {
            $result = array(
                'GUESSED' => $countGuessed,
                'MISSED' => $countMissed,
            );
        } else {
            $result .= $messages->getStyledMessage(__('telepathically guessed') . ': ' . $countGuessed, 'info');
            $result .= $messages->getStyledMessage(__('skipped') . ': ' . $countMissed, 'warning');
        }

        //some logging, why not?
        log_register('CALLSHIST USERS UPDATE GUESSED `' . $countGuessed . '` MISSED`' . $countMissed . '`');

        return ($result);
    }
}
