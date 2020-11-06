<?php

/**
 * Additional users mobile numbers
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
     * System message helper object placeholder
     *
     * @var obejct
     */
    protected $messages = '';

    /**
     * Basic mobule URL
     */
    const URL_ME = '?module=mobileedit';

    /**
     * Creates new MobilesExt instance
     * 
     * 
     * @return void
     */
    public function __construct() {
        $this->initMessages();
        $this->loadAlter();
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
     * Loads all additional mobiles data from database
     * 
     * @return void
     */
    protected function loadAllMobiles() {
        //loading all additional mobiles from database
        $query = "SELECT * from `mobileext`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allMobiles[$each['id']] = $each;
            }
        }
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
        $loginF = mysql_real_escape_string($login);
        $mobileF = mysql_real_escape_string($mobile);
        $notesF = mysql_real_escape_string($notes);
        if ((!empty($loginF)) AND ( !empty($mobileF))) {
            $query = "INSERT INTO `mobileext` (`id`,`login`,`mobile`,`notes`) VALUES "
                    . "(NULL,'" . $loginF . "','" . $mobileF . "','" . $notesF . "');";
            nr_query($query);
            $newId = simple_get_lastid('mobileext');
            $result = $newId;
            log_register('MOBILEEXT CREATE (' . $login . ') MOBILE `' . $mobile . '` [' . $newId . ']');
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
        $mobileId = vf($mobileId, 3);
        if (isset($this->allMobiles[$mobileId])) {
            $mobileData = $this->allMobiles[$mobileId];
            $query = "DELETE from `mobileext` WHERE `id`='" . $mobileId . "';";
            nr_query($query);
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
        $mobileId = vf($mobileId, 3);
        if (isset($this->allMobiles[$mobileId])) {
            $mobileData = $this->allMobiles[$mobileId];
            $where = "WHERE `id`='" . $mobileId . "';";
            if ((!empty($mobile)) AND ( $mobileData['mobile'] != $mobile)) {
                simple_update_field('mobileext', 'mobile', $mobile, $where);
                log_register('MOBILEEXT CHANGE (' . $mobileData['login'] . ') MOBILE ON `' . $mobile . '` [' . $mobileId . ']');
            }

            if ($mobileData['notes'] != $notes) {
                simple_update_field('mobileext', 'notes', $notes, $where);
                log_register('MOBILEEXT CHANGE (' . $mobileData['login'] . ') NOTES');
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
            $inputs = wf_HiddenInput('newmobileextlogin', $login);
            $inputs .= wf_TextInput('newmobileextnumber', __('New mobile'), '', false, '20', $formFilter);
            $inputs .= wf_TextInput('newmobileextnotes', __('New notes'), '', false, '40');
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
            $inputs = wf_HiddenInput('editmobileextid', $mobileId);
            $inputs .= wf_TextInput('editmobileextnumber', __('Mobile'), $mobileData['mobile'], true, '20', $formFilter);
            $inputs .= wf_TextInput('editmobileextnotes', __('Notes'), $mobileData['notes'], true, '40');
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
                $actLinks = wf_JSAlert(self::URL_ME . '&username=' . $login . '&deleteext=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
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
    public function fastAskoziaAttachForm($login) {
        $result = '';
        $askNum = new AskoziaNum();
        $askoziaLog = $askNum->parseLog();
        $telepathy = new Telepathy(false, true, false, false);
        $telepathy->usePhones();
        if (!empty($askoziaLog)) {
            $numsTmp = array();
            $curdate = curdate();
            $curhour = date("H:");
            foreach ($askoziaLog as $io => $each) {
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
                    $inputs = wf_HiddenInput('newmobileextlogin', $login);
                    $inputs .= wf_Selector('newmobileextnumber', $numsTmp, __('New mobile'), '', false);
                    $inputs .= wf_TextInput('newmobileextnotes', __('New notes'), '', false, '40');
                    $inputs .= wf_Submit(__('Create'));
                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                    $result .= wf_CleanDiv();
                }
                show_window(__('Some of numbers which calls us today'), $result);
            }
        }
    }

}
