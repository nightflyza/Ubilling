<?php

/**
 * Warm/Cold calls tasks generation class
 */

/**
 * TODO:
 *  - users filtering
 *  - smszilla calllists filtering
 *  - saving results as separate annoy-reports
 *  - extend management permissions?
 *  - calling report via stigmata engine
 */
class Annoyance {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains internet users data as login=>stgUserdata
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains extended cached users data as login=>data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains available tariffs array as tariffname=>tariffData
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains available tariffs as tariffname=>tariffname
     *
     * @var array
     */
    protected $allTariffNames = array();

    /**
     * Contains available user extended mobiles data as login=>mobiles arr
     *
     * @var array
     */
    protected $mobilesExt = array();

    //routes, URLs, etc...
    const URL_ME = '?module=report_annoyance';
    const PROUTE_FILTERUSERS = 'runusersannoying';
    const PROUTE_TARIFF_FILTER = 'userstarifffilter';
    const PROUTE_ACTIVE_FILTER = 'useractivefilter';

    /**
     * Creates new report instance
     */
    public function __construct() {
        $this->loadAlter();
        $this->loadUserData();
        $this->loadTariffs();
        $this->loadMobilesExt();
    }

    /**
     * Preloads required configs for further usage
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
     * Loads users additional mobiles from database
     * 
     * @return void
     */
    protected function loadMobilesExt() {
        if ($this->altCfg['MOBILES_EXT']) {
            $mobilesExt = new MobilesExt();
            $this->mobilesExt = $mobilesExt->getAllUsersMobileNumbers();
        }
    }

    /**
     * Loads available users data from database into protected props
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUsers = zb_UserGetAllStargazerDataAssoc();
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Loads available tariffs data from database
     *
     * @return void 
     */
    protected function loadTariffs() {
        $this->allTariffs = zb_TariffGetAllData();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $this->allTariffNames[$each['name']] = $each['name'];
            }
        }
    }

    /**
     * Returns form with some existing users filters
     * 
     * @return string
     */
    public function renderUsersFilterForm() {
        $result = '';
        $inputs = wf_HiddenInput(self::PROUTE_FILTERUSERS, 'true');
        $tariffsArr = array('' => __('Any'));
        $tariffsArr += $this->allTariffNames;
        $inputs .= wf_Selector(self::PROUTE_TARIFF_FILTER, $tariffsArr, __('Tariff'), ubRouting::post(self::PROUTE_TARIFF_FILTER), false) . ' ';
        $inputs .= wf_CheckInput(self::PROUTE_ACTIVE_FILTER, __('User is active'), false, ubRouting::post(self::PROUTE_ACTIVE_FILTER)) . ' ';
        $inputs .= wf_Submit('Search');
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Performs filtering of existing userbase
     * 
     * @return string
     */
    public function runUsersFilter() {
        $result = '';
        //TODO: 
        return($result);
    }

}
