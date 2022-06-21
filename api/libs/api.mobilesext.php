<?php

/**
 * Additional users mobile numbers basic class
 */
class MobilesExt {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all additiona mobile numbers as id=>data
     *
     * @var array
     */
    protected $allMobiles = array();

    /**
     * Additional mobiles database abstraction layer here.
     *
     * @var object
     */
    protected $mobilesDb = '';

    /**
     * System message helper object placeholder
     *
     * @var obejct
     */
    protected $messages = '';

    /**
     * Some predefined stuff such as routes, URLs, etc..
     */
    const URL_ME = '?module=mobileedit';
    const TABLE_MOBILES = 'mobileext';
    const ROUTE_LOGIN = 'username';
    const ROUTE_DELETE_ID = 'deletemobileextid';
    const PROUTE_NEW_LOGIN = 'newmobileextlogin';
    const PROUTE_NEW_NUMBER = 'newmobileextnumber';
    const PROUTE_NEW_NOTES = 'newmobileextnotes';
    const PROUTE_ED_ID = 'editmobileextid';
    const PROUTE_ED_NUMBER = 'editmobileextnumber';
    const PROUTE_ED_NOTES = 'editmobileextnotes';

    /**
     * Creates new MobilesExt instance
     * 
     * @return void
     */
    public function __construct() {
        /**
         * Шива-Шиво, чому так паршиво?
         * Чому, повинні все це бачити на живо?
         */
        $this->initMessages();
        $this->loadAlter();
        $this->initDb();
        $this->loadAllMobiles();
    }

    /**
     * Inits system messages helper object for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads system alter config
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
     * Inits database abstraction layer for further usage.
     * 
     * @return void
     */
    protected function initDb() {
        $this->mobilesDb = new NyanORM(self::TABLE_MOBILES);
    }

    /**
     * Loads all additional mobiles data from database
     * 
     * @return void
     */
    protected function loadAllMobiles() {
        $this->allMobiles = $this->mobilesDb->getAll('id');
    }

    /**
     * Returns filtered array for some user phones as id => data or as login => array_of_mobiles
     *
     * @param string $login
     * @param bool $loginAsKey
     * 
     * @return array
     */
    public function getUserMobiles($login, $loginAsKey = false) {
        $result = array();
        if (!empty($login)) {
            if (!empty($this->allMobiles)) {
                foreach ($this->allMobiles as $io => $each) {
                    if ($each['login'] == $login) {
                        if ($loginAsKey) {
                            $result[$login][] = $each['mobile'];
                        } else {
                            $result[$each['id']] = $each;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Creates new additional mobile for some user
     * 
     * @param string $login
     * @param string $mobile
     * @param string $notes
     * 
     * @return int
     */
    public function createUserMobile($login, $mobile, $notes = '') {
        $result = '';
        if ((!empty($login)) AND ( !empty($mobile))) {
            $this->mobilesDb->data('login', ubRouting::filters($login, 'mres'));
            $this->mobilesDb->data('mobile', ubRouting::filters($mobile, 'mres'));
            $this->mobilesDb->data('notes', ubRouting::filters($notes, 'mres'));
            $this->mobilesDb->create();
            $result = $this->mobilesDb->getLastId();
            log_register('MOBILEEXT CREATE (' . $login . ') MOBILE `' . $mobile . '` [' . $result . ']');
        }
        return ($result);
    }

    /**
     * Deletes some additional mobile record from database by its ID
     * 
     * @param int $mobileId
     * 
     * @return void
     */
    public function deleteUserMobile($mobileId) {
        $mobileId = ubRouting::filters($mobileId, 'int');
        if (isset($this->allMobiles[$mobileId])) {
            $mobileData = $this->allMobiles[$mobileId];
            $this->mobilesDb->where('id', '=', $mobileId);
            $this->mobilesDb->delete();
            log_register('MOBILEEXT DELETE (' . $mobileData['login'] . ') MOBILE `' . $mobileData['mobile'] . '` [' . $mobileId . ']');
        }
    }

    /**
     * Changes additional mobile database records if required
     * 
     * @param int $mobileId
     * @param string $mobile
     * @param string $notes
     * 
     * @return void
     */
    public function updateUserMobile($mobileId, $mobile, $notes = '') {
        $mobileId = ubRouting::filters($mobileId, 'int');
        if (isset($this->allMobiles[$mobileId])) {
            $mobileData = $this->allMobiles[$mobileId];
            $somethingChanged = false;

            if ((!empty($mobile)) AND ( $mobileData['mobile'] != $mobile)) {
                $somethingChanged = true;
                $this->mobilesDb->data('mobile', ubRouting::filters($mobile, 'mres'));
                log_register('MOBILEEXT CHANGE (' . $mobileData['login'] . ') MOBILE ON `' . $mobile . '` [' . $mobileId . ']');
            }

            if ($mobileData['notes'] != $notes) {
                $somethingChanged = true;
                $this->mobilesDb->data('notes', ubRouting::filters($notes, 'mres'));
                log_register('MOBILEEXT CHANGE (' . $mobileData['login'] . ') NOTES');
            }

            //push changes to DB
            if ($somethingChanged) {
                $this->mobilesDb->where('id', '=', $mobileId);
                $this->mobilesDb->save();
            }
        }
    }

    /**
     * Renders create form for some user
     * 
     * @return string
     */
    public function renderCreateForm($login) {
        $result = '';
        if (!empty($login)) {
            $formFilter = (@$this->altCfg['MOBILE_FILTERS_DISABLED']) ? '' : 'mobile';
            $inputs = wf_HiddenInput(self::PROUTE_NEW_LOGIN, $login);
            $inputs .= wf_TextInput(self::PROUTE_NEW_NUMBER, __('New mobile'), '', false, '20', $formFilter);
            $inputs .= wf_TextInput(self::PROUTE_NEW_NOTES, __('New notes'), '', false, '40');
            $inputs .= wf_Submit(__('Create'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_CleanDiv();
        }
        return ($result);
    }

    /**
     * Renders additional mobile edit form
     * 
     * @param int $mobileId
     * 
     * @return string
     */
    protected function renderEditForm($mobileId) {
        $result = '';
        $mobileId = vf($mobileId, 3);
        if (isset($this->allMobiles[$mobileId])) {
            $formFilter = (@$this->altCfg['MOBILE_FILTERS_DISABLED']) ? '' : 'mobile';
            $mobileData = $this->allMobiles[$mobileId];
            $inputs = wf_HiddenInput(self::PROUTE_ED_ID, $mobileId);
            $inputs .= wf_TextInput(self::PROUTE_ED_NUMBER, __('Mobile'), $mobileData['mobile'], true, '20', $formFilter);
            $inputs .= wf_TextInput(self::PROUTE_ED_NOTES, __('Notes'), $mobileData['notes'], true, '40');
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_CleanDiv();
        }
        return ($result);
    }

    /**
     * Returns list of all user additional mobiles with required controls
     * 
     * @param string $login
     * 
     * @return string
     */
    public function renderUserMobilesList($login) {
        $result = '';
        $userMobiles = $this->getUserMobiles($login);
        if (!empty($userMobiles)) {
            $cells = wf_TableCell(__('Mobile'));
            $cells .= wf_TableCell(__('Notes'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($userMobiles as $io => $each) {
                $cells = wf_TableCell($each['mobile']);
                $cells .= wf_TableCell($each['notes']);
                $deleteUrl = self::URL_ME . '&' . self::ROUTE_LOGIN . '=' . $login . '&' . self::ROUTE_DELETE_ID . '=' . $each['id'];
                $cancelUrl = self::URL_ME . '&' . self::ROUTE_LOGIN . '=' . $login;
                $dialogTitle = __('Delete') . ' ' . __('Additional mobile') . '?';
                $alertLabel = __('Delete') . ' ' . __('Additional mobile') . ' ' . $each['mobile'] . '? ' . $this->messages->getDeleteAlert();
                $actLinks = wf_ConfirmDialog($deleteUrl, web_delete_icon(), $alertLabel, '', $cancelUrl, $dialogTitle);
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . $each['mobile'], $this->renderEditForm($each['id']));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row3');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        }
        return ($result);
    }

    /**
     * Returns all available additional mobiles data as id=>data
     * 
     * @return array
     */
    public function getAllMobiles() {
        return ($this->allMobiles);
    }

    /**
     * Returns array of all users additional mobiles as login=>mobiles array
     * 
     * @return array
     */
    public function getAllUsersMobileNumbers() {
        $result = array();
        if (!empty($this->allMobiles)) {
            foreach ($this->allMobiles as $io => $each) {
                $result[$each['login']][] = $each['mobile'];
            }
        }
        return($result);
    }

    /**
     * Returns all additional mobiles data as mobile=>login
     * 
     * @return array
     */
    public function getAllMobilesUsers() {
        $result = array();
        if (!empty($this->allMobiles)) {
            foreach ($this->allMobiles as $io => $each) {
                $result[$each['mobile']] = $each['login'];
            }
        }
        return ($result);
    }

    /**
     * Renders fast ext mobile add form
     * 
     * @param string $login
     * 
     * @return void
     */
    public function fastNumAttachForm($login) {
        $result = '';
        $pbxNum = new PBXNum();
        $inCallsLog = $pbxNum->parseLog();
        $telepathy = new Telepathy(false, true, false, false);
        $telepathy->usePhones();
        if (!empty($inCallsLog)) {
            $numsTmp = array();
            $curdate = curdate();
            $curhour = date("H:");
            foreach ($inCallsLog as $io => $each) {
                //only today calls
                if ($each['date'] == $curdate) {
                    if ((empty($each['login'])) AND ( $each['reply'] == 0)) {
                        //just for last hour
                        if (substr($each['time'], 0, 3) == $curhour) {
                            if (!empty($each['number'])) {
                                //is this really unknown number?
                                $detectedLogin = $telepathy->getByPhone($each['number'], true, true);
                                if (empty($detectedLogin)) {
                                    $numsTmp[$each['number']] = $each['time'] . ' - ' . $each['number'];
                                }
                            }
                        }
                    }
                }
            }

            //new extmobile form rendering
            if (!empty($numsTmp)) {
                if (!empty($login)) {
                    $inputs = wf_HiddenInput(self::PROUTE_NEW_LOGIN, $login);
                    $inputs .= wf_Selector(self::PROUTE_NEW_NUMBER, $numsTmp, __('New mobile'), '', false);
                    $inputs .= wf_TextInput(self::PROUTE_NEW_NOTES, __('New notes'), '', false, '40');
                    $inputs .= wf_Submit(__('Create'));
                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                    $result .= wf_CleanDiv();
                }
                show_window(__('Some of numbers which calls us today'), $result);
            }
        }
    }

}
