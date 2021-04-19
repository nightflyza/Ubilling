<?php


class ExtContras {
    /**
     * Database abstraction layer with for `extcontras` table
     *
     * @var object
     */
    protected $dbExtContras = null;

    /**
     * Database abstraction layer with for `extcontras_profiles` table
     *
     * @var object
     */
    protected $dbECProfiles = null;

    /**
     * Database abstraction layer with for `extcontras_contracts` table
     *
     * @var object
     */
    protected $dbECContracts = null;

    /**
     * Database abstraction layer with for `extcontras_address` table
     *
     * @var object
     */
    protected $dbECAddress = null;

    /**
     * Database abstraction layer with for `extcontras_periods` table
     *
     * @var object
     */
    protected $dbECPeriods = null;

    /**
     * Database abstraction layer with for `extcontras_money` table
     *
     * @var object
     */
    protected $dbECMoney = null;

    /**
     * Contains all extcontras records from DB as ecid => ecdata
     *
     * @var array
     */
    protected $allExtContras = array();

    /**
     * Contains all extcontras profiles records from DB ecprofileid => ecprofiledata
     *
     * @var array
     */
    protected $allECProfiles = array();

    /**
     * Contains all extcontras contracts records from DB eccontractid => eccontractdata
     *
     * @var array
     */
    protected $allECContracts = array();

    /**
     * Contains all extcontras addresses records from DB ecaddressid => ecaddressdata
     *
     * @var array
     */
    protected $allECAddresses = array();

    /**
     * Contains all extcontras periods records from DB ecperiodid => ecperioddata
     *
     * @var array
     */
    protected $allECPeriods = array();

    /**
     * Contains all extcontras money records from DB ecmoneyid => ecmoneydata
     *
     * @var array
     */
    protected $allECMoney = array();

    /**
     * System config object placeholder
     *
     * @var null
     */
    protected $ubConfig = null;

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = null;

    /**
     * System files storage placeholder
     *
     * @var object
     */
    protected $fileStorage = null;

    /**
     * Placeholder for EXTCONTRAS_EDIT_ALLOWED_DAYS alter.ini option
     *
     * @var int
     */
    protected $ecEditablePreiod = 60;

    /**
     * Placeholder for cfr('EXTCONTRASRO')
     *
     * @var bool
     */
    protected $ecReadOnlyAccess = false;

    /**
     * Routes, static defines etc
     */
    const URL_ME = '?module=extcontras';
    const URL_DICTPROFILES  = 'dictprofiles=true';
    const URL_DICTCONTRACTS = 'dictcontracts=true';
    const URL_DICTADDRESS   = 'dictaddress=true';
    const URL_DICTPERIODS   = 'dictperiods=true';
    const URL_FINOPERATIONS = 'finoperations=true';
    const INP_PERIODSELECT  = 'periodselector';
    const ROUTE_BOXLIST = 'ajboxes';
    const ROUTE_BOXNAV = 'boxidnav';
    const ROUTE_MAP = 'boxmap';
    const ROUTE_BOXEDIT = 'editboxid';
    const ROUTE_BOXDEL = 'deleteboxid';
    const ROUTE_LINKDEL = 'deletelinkid';
    const TABLE_EXTCONTRAS      = 'extcontras';
    const TABLE_ECPROFILES      = 'extcontras_profiles';
    const TABLE_ECCONTRACTS     = 'extcontras_contracts';
    const TABLE_ECADDRESS       = 'extcontras_address';
    const TABLE_ECPERIODS       = 'extcontras_periods';
    const TABLE_ECMONEY         = 'extcontras_money';

    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig     = $ubillingConfig;
        $this->messages     = new UbillingMessageHelper();
        $this->fileStorage  = new FileStorage();

        $this->loadOptions();
        $this->initDBEntities();
        $this->loadAllData();
    }

    /**
     * Inits DB NyanORM objects
     */
    protected function initDBEntities() {
        $this->dbExtContras  = new NyanORM(self::TABLE_EXTCONTRAS);
        $this->dbECProfiles  = new NyanORM(self::TABLE_ECPROFILES);
        $this->dbECContracts = new NyanORM(self::TABLE_ECCONTRACTS);
        $this->dbECAddress   = new NyanORM(self::TABLE_ECADDRESS);
        $this->dbECPeriods   = new NyanORM(self::TABLE_ECPERIODS);
        $this->dbECMoney     = new NyanORM(self::TABLE_ECMONEY);
    }

    /**
     * Loads alter.ini options
     */
    protected function loadOptions() {
        $this->ecEditablePreiod = $this->ubConfig->getAlterParam('EXTCONTRAS_EDIT_ALLOWED_DAYS');
        $this->ecEditablePreiod = empty($this->ecEditablePreiod) ? 60 : $this->ecEditablePreiod;
        $this->ecReadOnlyAccess = (!cfr('EXTCONTRASRW'));
    }

    /**
     * Gets external contragents records from DB
     */
    protected function loadExtContras() {
        $this->allExtContras = $this->dbExtContras->getAll('id');
    }

    /**
     * Gets external contragents profiles records from DB
     */
    protected function loadECProfiles() {
        $this->allECProfiles = $this->dbECProfiles->getAll('id');
    }

    /**
     * Gets external contragents contracts records from DB
     */
    protected function loadECContracts() {
        $this->allECContracts = $this->dbECContracts->getAll('id');
    }

    /**
     * Gets external contragents addresses records from DB
     */
    protected function loadECAddresses() {
        $this->allECAddresses = $this->dbECAddress->getAll('id');
    }

    /**
     * Gets external contragents periods records from DB
     */
    protected function loadECPeriods() {
        $this->allECPeriods = $this->dbECPeriods->getAll('id');
    }

    /**
     * Gets external contragents money records from DB
     */
    protected function loadECMoney() {
        $this->allECMoney = $this->dbECMoney->getAll('id');
    }

    /**
     * Unified data loader
     */
    protected function loadAllData() {
        $this->loadExtContras();
        $this->loadECProfiles();
        $this->loadECContracts();
        $this->loadECAddresses();
        $this->loadECPeriods();
        $this->loadECMoney();
    }

    /**
     * Renders main module controls
     *
     * @return string
     */
    public function renderMainControls() {
        $result = '';

        $result.= wf_Link(self::URL_ME . '&' . self::URL_FINOPERATIONS, wf_img_sized('skins/ukv/dollar.png') . ' ' . __('Financial operations'), false, 'ubButton');

        // dictionaries form
        $dictControls = wf_Link(self::URL_ME . '&' . self::URL_DICTPROFILES, wf_img_sized('skins/extcontrasprofiles.png') . ' ' . __('Profiles dictionary'), false, 'ubButton');
        $dictControls.= wf_Link(self::URL_ME . '&' . self::URL_DICTCONTRACTS, wf_img_sized('skins/corporate_small.png') . ' ' . __('Contracts dictionary'), false, 'ubButton');
        $dictControls.= wf_Link(self::URL_ME . '&' . self::URL_DICTADDRESS, wf_img_sized('skins/extcontrasaddr.png') . ' ' . __('Address dictionary'), false, 'ubButton');
        $dictControls.= wf_Link(self::URL_ME . '&' . self::URL_DICTPERIODS, wf_img_sized('skins/clock.png') . ' ' . __('Periods dictionary'), false, 'ubButton');
        $result.= wf_modalAuto(web_icon_extended() . ' ' . __('Dictionaries'), __('Dictionaries'), $dictControls, 'ubButton');

        return ($result);
    }

    /**
     * Returns period dropdown selector
     *
     * @return string
     */
    public function renderPeriodSelector() {
        $tmpArray = array();

        if (!empty($this->allECPeriods)) {
            foreach ($this->allECPeriods as $eachID => $eachPeriod) {
                $tmpArray[$eachID] = $eachPeriod['period_name'];
            }
        }

        return (wf_Selector(self::INP_PERIODSELECT, $tmpArray, __('Select period')));
    }

    public function periodAdd() {

    }

    public function periodAddForm() {

    }

    public function periodListRender() {

    }

    public function periodIsProtected() {

    }
}