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
     * Routes, static defines, etc
     */
    const URL_ME = '?module=extcontras';
    const URL_DICTPROFILES  = 'dictprofiles';
    const URL_DICTCONTRACTS = 'dictcontracts';
    const URL_DICTADDRESS   = 'dictaddress';
    const URL_DICTPERIODS   = 'dictperiods';
    const URL_FINOPERATIONS = 'finoperations';


    const CTRL_PROFILE_NAME     = 'profname';
    const CTRL_PROFILE_EDRPO    = 'profedrpo';
    const CTRL_PROFILE_CONTACT  = 'profcontact';
    const CTRL_PROFILE_MAIL     = 'profmail';

    const DBFLD_COMMON_ID       = 'id';
    const DBFLD_PROFILE_NAME    = 'name';
    const DBFLD_PROFILE_EDRPO   = 'edrpo';
    const DBFLD_PROFILE_CONTACT = 'contact';
    const DBFLD_PROFILE_MAIL    = 'email';


    const CTRL_PERIOD_SELECTOR  = 'prdselector';
    const CTRL_PERIOD_NAME      = 'prdname';
    const DBFLD_PERIOD_NAME     = 'period_name';


    const ROUTE_CREATE_ACTION   = 'doCreate';
    const ROUTE_EDIT_ACTION     = 'makeEdit';
    const ROUTE_EDIT_REC_ID     = 'editRecID';
    const ROUTE_CLONE_ACTION    = 'makeclone';
    const ROUTE_PROFILE_ACTS    = 'profileacts';
    const ROUTE_PERIOD_ACTS     = 'periodacts';

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
     * Gets external counterparties records from DB
     */
    protected function loadExtContras() {
        $this->allExtContras = $this->dbExtContras->getAll('id');
    }

    /**
     * Gets external counterparties profiles records from DB
     */
    protected function loadECProfiles() {
        $this->allECProfiles = $this->dbECProfiles->getAll('id');
    }

    /**
     * Gets external counterparties contracts records from DB
     */
    protected function loadECContracts() {
        $this->allECContracts = $this->dbECContracts->getAll('id');
    }

    /**
     * Gets external counterparties addresses records from DB
     */
    protected function loadECAddresses() {
        $this->allECAddresses = $this->dbECAddress->getAll('id');
    }

    /**
     * Gets external counterparties periods records from DB
     */
    protected function loadECPeriods() {
        $this->allECPeriods = $this->dbECPeriods->getAll('id');
    }

    /**
     * Gets external counterparties money records from DB
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
        $inputs = '';

        $inputs.= wf_Link(self::URL_ME . '&' . self::URL_FINOPERATIONS, wf_img_sized('skins/ukv/dollar.png') . ' ' . __('External counterparties list'), false, 'ubButton');

        // dictionaries form
        $dictControls = wf_Link(self::URL_ME . '&' . self::URL_DICTPROFILES . '=true', wf_img_sized('skins/extcontrasprofiles.png') . ' ' . __('Counterparties profiles dictionary'), false, 'ubButton');
        $dictControls.= wf_Link(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true', wf_img_sized('skins/corporate_small.png') . ' ' . __('Contracts dictionary'), false, 'ubButton');
        $dictControls.= wf_Link(self::URL_ME . '&' . self::URL_DICTADDRESS . '=true', wf_img_sized('skins/extcontrasaddr.png') . ' ' . __('Address dictionary'), false, 'ubButton');
        $dictControls.= wf_Link(self::URL_ME . '&' . self::URL_DICTPERIODS . '=true', wf_img_sized('skins/clock.png') . ' ' . __('Periods dictionary'), false, 'ubButton');
        $inputs.= wf_modalAuto(web_icon_extended() . ' ' . __('Dictionaries'), __('Dictionaries'), $dictControls, 'ubButton');

        return ($inputs);
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

        return (wf_Selector(self::CTRL_PERIOD_SELECTOR, $tmpArray, __('Select period')));
    }


    public function renderDictCreateButton($routeURL, $title) {
        $linkID = wf_InputId();
        $dynamicOpener = wf_Link('#', web_add_icon() . ' ' . $title, false, 'ubButton', 'id="' . $linkID . '"')
                         . wf_JSAjaxModalOpener(self::URL_ME, array($routeURL => 'true'), $linkID, true, 'POST');

        return ($dynamicOpener);
    }

    public function renderErrorMsg($title, $message) {
        $errormes = $this->messages->getStyledMessage($message, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
        return(wf_modalAutoForm($title, $errormes, ubRouting::post('errfrmid'), '', true));
    }

    public function profileCreadit($profileID = 0) {
/*
        $prfName    = ubRouting::post(self::CTRL_PROFILE_NAME);
        $prfContact = ubRouting::post(self::CTRL_PROFILE_CONTACT);
        $prfEDRPO   = ubRouting::post(self::CTRL_PROFILE_EDRPO);
        $prfEmail   = ubRouting::post(self::CTRL_PROFILE_MAIL);

'fi', FILTER_VALIDATE_BOOLEAN
 */
        $this->dbECProfiles->setDebug(true, true);
file_put_contents('zxcv', print_r($_POST, true));
        $this->dbECProfiles->data(self::DBFLD_PROFILE_NAME, ubRouting::post(self::CTRL_PROFILE_NAME));
        $this->dbECProfiles->data(self::DBFLD_PROFILE_CONTACT, ubRouting::post(self::CTRL_PROFILE_CONTACT));
        $this->dbECProfiles->data(self::DBFLD_PROFILE_EDRPO, ubRouting::post(self::CTRL_PROFILE_EDRPO));
        $this->dbECProfiles->data(self::DBFLD_PROFILE_MAIL, ubRouting::post(self::CTRL_PROFILE_MAIL));

        if (!empty($profileID)) {
            $this->dbECProfiles->where(self::DBFLD_COMMON_ID, '=', $profileID);
            $this->dbECProfiles->save(true, true);
        } else {
            $this->dbECProfiles->create();
        }
    }

    public function profileWebForm($editAction = false, $cloneAction = false, $profileID = 0) {
        $winID      = ubRouting::post('modalWindowId');
        $winBodyID  = ubRouting::post('modalWindowBodyId');
        $inputs     = '';
        $prfName    = '';
        $prfContact = '';
        $prfEDRPO   = '';
        $prfEmail   = '';

        if (($editAction or $cloneAction) and !empty($this->allECProfiles[$profileID])) {
            $profile    = $this->allECProfiles[$profileID];
            $prfName    = $profile[self::DBFLD_PROFILE_NAME];
            $prfContact = $profile[self::DBFLD_PROFILE_CONTACT];
            $prfEDRPO   = $profile[self::DBFLD_PROFILE_EDRPO];
            $prfEmail   = $profile[self::DBFLD_PROFILE_MAIL];
        }

        $submitCapt = ($editAction) ? __('Edit') : ($cloneAction) ? __('Clone') : __('Create');
        $formCapt   = ($editAction) ? __('Edit counterparty profile') :
                      ($cloneAction) ? __('Clone counterparty profile') :
                      __('Create counterparty profile');

        $inputs.= wf_TextInput(self::CTRL_PROFILE_NAME, __('Name'), $prfName, true);
        $inputs.= wf_TextInput(self::CTRL_PROFILE_CONTACT, __('Contact data'), $prfContact, true);
        $inputs.= wf_TextInput(self::CTRL_PROFILE_EDRPO, __('EDRPO/INN'), $prfEDRPO, true);
        $inputs.= wf_TextInput(self::CTRL_PROFILE_MAIL, __('E-mail'), $prfEmail, true);
        $inputs.= wf_SubmitClassed(true, 'ubButton', '', $submitCapt);
        $inputs.= wf_HiddenInput(self::ROUTE_PROFILE_ACTS, 'true');

        if ($editAction) {
            $inputs.= wf_HiddenInput(self::ROUTE_EDIT_ACTION, 'true');
            $inputs.= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $profileID);
        } else {
            $inputs.= wf_HiddenInput(self::ROUTE_CREATE_ACTION, 'true');
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_DICTPROFILES . '=true','POST', $inputs, 'glamour');
        $inputs = wf_modalAutoForm($formCapt, $inputs, $winID, $winBodyID, true);

        return ($inputs);
    }

    public function profileEdit() {

    }

    public function profileEditForm() {

    }

    public function periodCreate() {

    }

    public function periodWebForm($editAction = false, $periodID = 0) {
        $winID      = ubRouting::post('modalWindowId');
        $winBodyID  = ubRouting::post('modalWindowBodyId');
        $inputs     = '';
        $prdName    = '';

        if ($editAction and !empty($this->allECPeriods[$periodID])) {
            $period  = $this->allECProfiles[$periodID];
            $prdName = $period[self::DBFLD_PERIOD_NAME];
        }

        $submitCapt = ($editAction) ? __('Edit') : __('Create');
        $formCapt   = ($editAction) ? __('Edit period') : __('Create period');

        $inputs.= wf_TextInput(self::CTRL_PERIOD_NAME, __('Name'), $prdName, true);

        $inputs.= wf_SubmitClassed(true, 'ubButton', '', $submitCapt);
        $inputs.= ($editAction) ? wf_HiddenInput(self::ROUTE_EDIT_ACTION, true) : '';

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_DICTPERIODS . '=true','POST', $inputs, 'glamour');
        $inputs = wf_modalAutoForm($formCapt, $inputs, $winID, $winBodyID, true);

        return ($inputs);
    }

    public function periodEdit($periodID) {

    }

    public function periodEditForm() {
        $inputs = '';

    }

    public function periodListRender() {

    }

    public function periodIsProtected() {

    }

    /**
     * Returns true if record with such field value already exists
     *
     * @param $dbEntity
     * @param $dbFieldName
     * @param $dbFieldVal
     * @param int $excludeRecID record ID to make exclusion on. For record editing purposes generally
     *
     * @return mixed|string
     */
    public function checkRecExists($dbEntity, $dbFieldName, $dbFieldVal, $excludeRecID = 0) {
        $result = '';
        $dbEntity->selectable('id');
        $dbEntity->where($dbFieldName, '=', $dbFieldVal);

        if (!empty($excludeRecID)) {
            $dbEntity->where('id', '!=', $excludeRecID);
        }

        $result = $dbEntity->getAll();
        $result = empty($result) ? '' : $result[0]['id'];

        return ($result);
    }
}