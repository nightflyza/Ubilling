<?php

/**
 * Insurance prototype
 */
class Insurance {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Home insurance database abstraction layer placeholder
     *
     * @var object
     */
    protected $hinsDb = '';

    /**
     * Contains all of available home insurance requests as id=>reqdata
     *
     * @var array
     */
    protected $allHinsReq = array();

    /**
     * System messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains all available user data as login=>userdata
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains additional comments 
     *
     * @var object
     */
    protected $adComments = '';

    /**
     * Predefined routes, URLs etc..
     */
    const ADSCOPE_HINS = 'HINS';
    const HINS_TABLE = 'ins_homereq';
    const URL_ME = '?module=insurance';
    const ROUTE_AJHINSLIST = 'ajhinslist';
    const ROUTE_VIEWHINSREQ = 'viewrequest';
    const ROUTE_HINSDONE = 'sethinsdone';
    const ROUTE_HINSUNDONE = 'sethinsundone';

    /**
     * Creates new zastrahuy bratuhu zastrahuy
     * 
     * @param bool $loadAllData
     */
    public function __construct($loadAllData = true) {
        $this->initDatabaseLayers();
        if ($loadAllData) {
            $this->initMessages();
            $this->loadConfigs();
            $this->loadUserData();
            $this->loadHinsRequests();
            $this->initAdComments();
        }
    }

    /**
     * Preloads system configs for further usage
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
     * Inits additional comments instance
     * 
     * @return void
     */
    protected function initAdComments() {
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $this->adComments = new ADcomments(self::ADSCOPE_HINS);
        }
    }

    /**
     * Loads existing users data into protected prop
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Inits all of required database abstraction layers
     * 
     * @return void
     */
    protected function initDatabaseLayers() {
        $this->hinsDb = new NyanORM(self::HINS_TABLE);
    }

    /**
     * Loads all home insurance requests
     * 
     * @return void
     */
    protected function loadHinsRequests() {
        $this->allHinsReq = $this->hinsDb->getAll('id');
    }

    /**
     * Inits system messages helper instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Renders available home insurance requests
     * 
     * @return string
     */
    public function renderHinsRequestsList() {
        $result = '';
        $columns = array('ID', 'Date', 'User', 'Address', 'Real Name', 'Mobile', 'Email', 'Processed', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&' . self::ROUTE_AJHINSLIST . '=true', false, __('Request'), 100, $opts);
        return($result);
    }

    /**
     * Renders available home insurance requests json backend
     * 
     * @return void
     */
    public function ajHinsList() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allHinsReq)) {
            foreach ($this->allHinsReq as $io => $each) {
                $data[] = $each['id'];
                $data[] = $each['date'];
                $userAddress = @$this->allUserData[$each['login']]['fulladress'];
                $userLink = wf_Link(UserProfile::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . $userAddress);
                $data[] = $userLink;
                $data[] = $each['address'];
                $data[] = $each['realname'];
                $data[] = $each['mobile'];
                $data[] = $each['email'];
                $adcIndicator = '';
                if ($this->adComments) {
                    $adcIndicator = ' ' . $this->adComments->getCommentsIndicator($each['id']);
                }
                $data[] = web_bool_led($each['state']) . $adcIndicator;
                $reqControls = wf_Link(self::URL_ME . '&' . self::ROUTE_VIEWHINSREQ . '=' . $each['id'], web_icon_search() . ' ' . __('Show'));
                $data[] = $reqControls;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Renders home insurance request controls
     * 
     * @param int $requestId
     * 
     * @return string
     */
    protected function renderHinsControls($requestId) {
        $result = '';
        $requestId = ubRouting::filters($requestId, 'int');
        $result .= wf_BackLink(self::URL_ME);
        if (isset($this->allHinsReq[$requestId])) {
            $requestData = $this->allHinsReq[$requestId];
            if ($requestData['state']) {
                $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_HINSUNDONE . '=' . $requestId, wf_img_sized('skins/icon_inactive.gif', '', '10') . ' ' . __('Open'), false, 'ubButton');
            } else {
                $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_HINSDONE . '=' . $requestId, wf_img_sized('skins/icon_active.gif', '', '10') . ' ' . __('Close'), false, 'ubButton');
            }
        }
        return($result);
    }

    /**
     * Renders some home insurance request body
     * 
     * @param int $requestId
     * 
     * @return string
     */
    public function renderHinsRequest($requestId) {
        $requestId = ubRouting::filters($requestId, 'int');
        $result = '';

        if (isset($this->allHinsReq[$requestId])) {
            $reqData = $this->allHinsReq[$requestId];

            $cells = wf_TableCell(__('ID'), '', 'row2');
            $cells .= wf_TableCell($reqData['id']);
            $rows = wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Date'), '', 'row2');
            $cells .= wf_TableCell($reqData['date']);
            $rows .= wf_TableRow($cells, 'row3');
            $userAddress = @$this->allUserData[$reqData['login']]['fulladress'];
            $userLink = wf_Link(UserProfile::URL_PROFILE . $reqData['login'], web_profile_icon() . ' ' . $userAddress);
            $cells = wf_TableCell(__('User'), '', 'row2');
            $cells .= wf_TableCell($userLink);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Address'), '', 'row2');
            $cells .= wf_TableCell($reqData['address']);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Real Name'), '', 'row2');
            $cells .= wf_TableCell($reqData['realname']);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Mobile'), '', 'row2');
            $cells .= wf_TableCell($reqData['mobile']);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Email'), '', 'row2');
            $cells .= wf_TableCell($reqData['email']);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Processed'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($reqData['state']));
            $rows .= wf_TableRow($cells, 'row3');
            $result .= wf_TableBody($rows, '100%', 0, '');

            //request controls
            $result .= $this->renderHinsControls($requestId);

            //adcomments here
            if ($this->adComments) {
                $result .= wf_delimiter();
                $result .= $this->adComments->renderComments($requestId);
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_WRONG_ID', 'error');
        }


        return($result);
    }

    /**
     * Sets home insurance request as done
     * 
     * @param int $requestId
     * 
     * @return void
     */
    public function setHinsDone($requestId) {
        $requestId = ubRouting::filters($requestId, 'int');
        if (isset($this->allHinsReq[$requestId])) {
            $this->hinsDb->data('state', 1);
            $this->hinsDb->where('id', '=', $requestId);
            $this->hinsDb->save();
            $darkVoid = new DarkVoid();
            $darkVoid->flushCache();
            log_register('INSURANCE HINS [' . $requestId . '] DONE');
        }
    }

    /**
     * Sets home insurance request as undone
     * 
     * @param int $requestId
     * 
     * @return void
     */
    public function setHinsUnDone($requestId) {
        $requestId = ubRouting::filters($requestId, 'int');
        if (isset($this->allHinsReq[$requestId])) {
            $this->hinsDb->data('state', 0);
            $this->hinsDb->where('id', '=', $requestId);
            $this->hinsDb->save();
            $darkVoid = new DarkVoid();
            $darkVoid->flushCache();
            log_register('INSURANCE HINS [' . $requestId . '] UNDONE');
        }
    }

    /**
     * Returns count of unprocessed home insurance requests
     * 
     * @return int
     */
    public function getUnprocessedHinsReqCount() {
        $this->hinsDb->where('state', '=', 0);
        $result = $this->hinsDb->getFieldsCount();
        return($result);
    }

}
