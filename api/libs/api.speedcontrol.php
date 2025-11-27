<?php

/**
 * Speed Control Report implementation
 */
class SpeedControl {

    /**
     * Speed overrides database abstraction layer
     *
     * @var object
     */
    protected $overridesDb = '';

    /**
     * Contains users with non zero speed overrides
     *
     * @var array
     */
    protected $overridedUsers = array();

    /**
     * Contains all available users data as login=>userData
     *
     * @var array
     */
    protected $allUsersData = array();

    /**
     * Contains existing tariffs speed data as tariffName=>speeddown/speedup
     *
     * @var array
     */
    protected $allTariffSpeeds = array();

    /**
     * system messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';


    //some predefined stuff here
    const DATA_SOURCE = 'userspeeds';
    const URL_ME = '?module=speedcontrol';
    const ROUTE_FIX = 'fix';

    /**
     * Submit and surrender unto Caesar
     * What is his rightful due
     * Complete oppression, no catharsis
     * In emphatic contempt for all of life
     */
    public function __construct() {
        $this->initMessages();
        $this->initOverDb();
        $this->loadUsersOverrides();
        if (!empty($this->overridedUsers)) {
            $this->loadUsersData();
            $this->loadTariffSpeeds();
        }
    }

    /**
     * Initializes the messages helper.
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Initializes the overrides database abstraction layer.
     * 
     * @return void
     */
    protected function initOverDb() {
        $this->overridesDb = new NyanORM(self::DATA_SOURCE);
    }

    /**
     * Loads all tariff speeds.
     * 
     * @return void
     */
    protected function loadTariffSpeeds() {
        $this->allTariffSpeeds = zb_TariffGetAllSpeeds();
    }

    /**
     * Loads all user speed overrides from the database.
     * 
     * @return void
     */
    protected function loadUsersOverrides() {
        $query = "SELECT `login` from `userspeeds` WHERE `speed` NOT LIKE '0'";
        $alloverrides = simple_queryall($query);
        $this->overridesDb->where('speed', 'NOT LIKE', '0');
        $this->overridedUsers = $this->overridesDb->getAll('login');
    }

    /**
     * Loads all user data.
     * 
     * @return void
     */
    protected function loadUsersData() {
        $this->allUsersData = zb_UserGetAllDataCache();
    }


    /**
     * Renders the speed control report of users with some speed overrides set
     *
     * @return string 
     */
    public function render() {
        $result = '';
        if (!empty($this->overridedUsers)) {
            $tablecells = wf_TableCell(__('Login'));
            $tablecells .= wf_TableCell(__('Real Name'));
            $tablecells .= wf_TableCell(__('Full address'));
            $tablecells .= wf_TableCell(__('Tariff'));
            $tablecells .= wf_TableCell(__('Tariff speeds'));
            $tablecells .= wf_TableCell(__('Speed override'));
            $tablecells .= wf_TableCell(__('Actions'));
            $tablerows = wf_TableRow($tablecells, 'row1');

            foreach ($this->overridedUsers as $io => $each) {
                if (isset($this->allUsersData[$each['login']])) {
                    $userLogin = $each['login'];
                    $userData = $this->allUsersData[$userLogin];
                    $userTariff = $userData['Tariff'];

                    if (isset($this->allTariffSpeeds[$userTariff])) {
                        $normalSpeedDown = $this->allTariffSpeeds[$userTariff]['speeddown'];
                        $normalSpeedUp = $this->allTariffSpeeds[$userTariff]['speedup'];
                    } else {
                        $normalSpeedDown = '-';
                        $normalSpeedUp = '-';
                    }
                    $tablecells = wf_TableCell(wf_Link(UserProfile::URL_PROFILE . $userLogin, web_profile_icon() . ' ' . $each['login']));
                    $tablecells .= wf_TableCell($userData['realname']);
                    $tablecells .= wf_TableCell($userData['fulladress']);
                    $tablecells .= wf_TableCell($userData['Tariff']);

                    $tablecells .= wf_TableCell($normalSpeedDown . '/' . $normalSpeedUp);
                    $tablecells .= wf_TableCell($each['speed']);
                    $fixlink = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_FIX . '=' . $userLogin, wf_img('skins/icon_repair.gif', __('Fix')), $this->messages->getEditAlert());
                    $tablecells .= wf_TableCell($fixlink);
                    $tablerows .= wf_TableRow($tablecells, 'row5');
                }
            }
            $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'success');
        }
        return ($result);
    }


    /**
     * Drops the speed override for a user.
     *
     * @param string $userLogin The login of the user.
     * 
     * @return void
     */
    public function dropOverride($userLogin) {
        global $billing;
        global $ubillingConfig;

        $login = ubRouting::filters($userLogin, 'mres');
        zb_UserDeleteSpeedOverride($login);
        zb_UserCreateSpeedOverride($login, 0);
        log_register('SPEEDCONTROL FIX (' . $login . ')');

        // Reset user if needed
        $billing->resetuser($login);
        log_register('RESET (' . $login . ')');

        // Resurrect user if they are disconnected
        if ($ubillingConfig->getAlterParam('RESETHARD')) {
            zb_UserResurrect($login);
        }
    }
}
