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
     * Contains all DB entities objects in array($tableName => $dbEntity)
     *
     * @var array
     */
    protected $dbEntitiesAll = array();

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
     * Placeholder for FILESTORAGE_ENABLED alter.ini option
     *
     * @var bool
     */
    protected $fileStorageEnabled = false;

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
     * Placeholder for mandatory fields SUP mark
     *
     * @var string
     */
    protected $supFrmFldMark = '';

    /**
     * Routes, static defines, etc
     */
    const URL_ME = '?module=extcontras';
    const URL_DICTPROFILES  = 'dictprofiles';
    const URL_DICTCONTRACTS = 'dictcontracts';
    const URL_DICTADDRESS   = 'dictaddress';
    const URL_DICTPERIODS   = 'dictperiods';
    const URL_FINOPERATIONS = 'finoperations';

    const DBFLD_COMMON_ID       = 'id';

    const CTRL_PROFILE_NAME     = 'profname';
    const CTRL_PROFILE_EDRPO    = 'profedrpo';
    const CTRL_PROFILE_CONTACT  = 'profcontact';
    const CTRL_PROFILE_MAIL     = 'profmail';

    const DBFLD_PROFILE_NAME    = 'name';
    const DBFLD_PROFILE_EDRPO   = 'edrpo';
    const DBFLD_PROFILE_CONTACT = 'contact';
    const DBFLD_PROFILE_MAIL    = 'email';

    const CTRL_CTRCT_DTSTART    = 'ctrctdtstart';
    const CTRL_CTRCT_DTEND      = 'ctrctdtend';
    const CTRL_CTRCT_FILENAME   = 'ctrctfilename';
    const CTRL_CTRCT_NUMBER     = 'ctrctnumber';
    const CTRL_CTRCT_SUBJECT    = 'ctrctsubject';
    const CTRL_CTRCT_AUTOPRLNG  = 'ctrctautoprolong';
    const CTRL_CTRCT_FULLSUM    = 'ctrctfullsum';
    const CTRL_CTRCT_NOTES      = 'ctrctnotes';

    const DBFLD_CTRCT_DTSTART   = 'date_start';
    const DBFLD_CTRCT_DTEND     = 'date_end';
    const DBFLD_CTRCT_FILENAME  = 'filename';
    const DBFLD_CTRCT_NUMBER    = 'number';
    const DBFLD_CTRCT_SUBJECT   = 'subject';
    const DBFLD_CTRCT_AUTOPRLNG = 'autoprolong';
    const DBFLD_CTRCT_FULLSUM   = 'fullsum';
    const DBFLD_CTRCT_NOTES     = 'notes';

    const CTRL_ADDRESS_ADDR     = 'addraddress';
    const CTRL_ADDRESS_SUM      = 'addrsumm';
    const CTRL_ADDRESS_CTNOTES  = 'addrctrctnotes';
    const CTRL_ADDRESS_NOTES    = 'addrnotes';

    const DBFLD_ADDRESS_ADDR    = 'address';
    const DBFLD_ADDRESS_SUM     = 'summ';
    const DBFLD_ADDRESS_CTNOTES = 'contract_notes';
    const DBFLD_ADDRESS_NOTES   = 'notes';

    const CTRL_PERIOD_SELECTOR  = 'prdselector';
    const CTRL_PERIOD_NAME      = 'prdname';
    const DBFLD_PERIOD_NAME     = 'period_name';

    const CTRL_MONEY_CONTRASID  = 'moneycontrasrecid';
    const CTRL_MONEY_ACCRUALID  = 'moneyaccrualid';
    const CTRL_MONEY_DATE       = 'moneydate';
    const CTRL_MONEY_SUMACCRUAL = 'moneysummaccrual';
    const CTRL_MONEY_SUNPAYMENT = 'moneysummpayment';
    const CTRL_MONEY_TOUS       = 'moneytous';
    const CTRL_MONEY_FROMUS     = 'moneyfromus';

    const DBFLD_MONEY_CONTRASID = 'contras_rec_id';
    const DBFLD_MONEY_ACCRUALID = 'accrual_id';
    const DBFLD_MONEY_DATE      = 'date';
    const DBFLD_MONEY_SMACCRUAL = 'summ_accrual';
    const DBFLD_MONEY_SMPAYMENT = 'summ_payment';
    const DBFLD_MONEY_TOUS      = 'to_us';
    const DBFLD_MONEY_FROMUS    = 'from_us';


    const ROUTE_ACTION_CREATE   = 'doCreate';
    const ROUTE_ACTION_EDIT     = 'doEdit';
    const ROUTE_ACTION_CLONE    = 'doClone';
    const ROUTE_ACTION_DELETE   = 'doRemove';
    const ROUTE_EDIT_REC_ID     = 'editRecID';
    const ROUTE_DELETE_REC_ID   = 'deleteRecID';
    const ROUTE_CONTRAS_ACTS    = 'contrasacts';
    const ROUTE_CONTRAS_JSON    = 'contraslistjson';
    const ROUTE_PROFILE_ACTS    = 'profileacts';
    const ROUTE_PROFILE_JSON    = 'profilelistjson';
    const ROUTE_CONTRACTS_ACTS  = 'contractacts';
    const ROUTE_CONTRACTS_JSON  = 'contractlistjson';
    const ROUTE_ADDRESS_ACTS    = 'addressacts';
    const ROUTE_ADDRESS_JSON    = 'addresslistjson';
    const ROUTE_PERIOD_ACTS     = 'periodacts';
    const ROUTE_PERIOD_JSON     = 'periodlistjson';
    const ROUTE_FINOPS_ACTS     = 'finopsacts';
    const ROUTE_FINOPS_JSON     = 'finopslistjson';


    const TABLE_EXTCONTRAS      = 'extcontras';
    const TABLE_ECPROFILES      = 'extcontras_profiles';
    const TABLE_ECCONTRACTS     = 'extcontras_contracts';
    const TABLE_ECADDRESS       = 'extcontras_address';
    const TABLE_ECPERIODS       = 'extcontras_periods';
    const TABLE_ECMONEY         = 'extcontras_money';

    const MISC_FILESTORAGE_SCOPE         = 'EXCONTRAS';
    const MISC_CLASS_MWID_CTRL           = '__FormModalWindowID';
    const MISC_CLASS_SUBMITFORM          = '__FormSubmit';
    const MISC_CLASS_SUBMITFORM_MODAL    = '__FormSubmitModal';
    const MISC_CLASS_EMPTYVALCHECK       = '__EmptyCheckControl';
    const MISC_CLASS_EMPTYVALCHECK_MODAL = '__EmptyCheckControlModal';
    const MISC_JS_DEL_FUNC_NAME          = 'deleteRec';
    const MISC_ERRFORM_ID_PARAM          = 'errfrmid';


    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig     = $ubillingConfig;
        $this->messages     = new UbillingMessageHelper();

        $this->loadOptions();
        $this->initDBEntities();
        $this->loadAllData();

        if ($this->fileStorageEnabled) {
            $this->fileStorage = new FileStorage(self::MISC_FILESTORAGE_SCOPE);
        }

        $this->supFrmFldMark = wf_tag('sup') . '*' . wf_tag('sup', true);
    }

    /**
     * Inits DB NyanORM objects
     */
    protected function initDBEntities() {
        $this->dbExtContras  = new NyanORM(self::TABLE_EXTCONTRAS);
        $this->dbEntitiesAll[self::TABLE_EXTCONTRAS]  = $this->dbExtContras;

        $this->dbECProfiles  = new NyanORM(self::TABLE_ECPROFILES);
        $this->dbEntitiesAll[self::TABLE_ECPROFILES]  = $this->dbECProfiles;

        $this->dbECContracts = new NyanORM(self::TABLE_ECCONTRACTS);
        $this->dbEntitiesAll[self::TABLE_ECCONTRACTS] = $this->dbECContracts;

        $this->dbECAddress   = new NyanORM(self::TABLE_ECADDRESS);
        $this->dbEntitiesAll[self::TABLE_ECADDRESS]   = $this->dbECAddress;

        $this->dbECPeriods   = new NyanORM(self::TABLE_ECPERIODS);
        $this->dbEntitiesAll[self::TABLE_ECPERIODS]   = $this->dbECPeriods;

        $this->dbECMoney     = new NyanORM(self::TABLE_ECMONEY);
        $this->dbEntitiesAll[self::TABLE_ECMONEY]     = $this->dbECMoney;
    }

    /**
     * Returns DB entity object by table name
     *
     * @param $dbEntityName
     *
     * @return object|null
     */
    public function getDBEntity($dbEntityName) {
        $result = null;

        if (!empty($this->dbEntitiesAll[$dbEntityName])) {
            $result = $this->dbEntitiesAll[$dbEntityName];
        }

        return ($result);
    }

    /**
     * Loads alter.ini options
     */
    protected function loadOptions() {
        $this->fileStorageEnabled = $this->ubConfig->getAlterParam('FILESTORAGE_ENABLED');
        $this->ecEditablePreiod   = $this->ubConfig->getAlterParam('EXTCONTRAS_EDIT_ALLOWED_DAYS');
        $this->ecEditablePreiod   = empty($this->ecEditablePreiod) ? 60 : $this->ecEditablePreiod;
        $this->ecReadOnlyAccess   = (!cfr('EXTCONTRASRW'));
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
     *  Ash oghum durbatulûk, ash oghum gimbatul,
     *  Ash oghum thrakatulûk, agh burzum-ishi krimpatul.
     *
     * @param $webFormMethod
     * @param $dataArray
     * @param string $crudEntityName
     * @param string $postFrmCtrlValToChk
     * @param string $dbTabName
     * @param string $dbTabFieldName
     *
     * @return mixed|string
     *
     * @throws Exception
     */
    public function processCRUDs($webFormMethod, $dataArray,
                                 $crudEntityName = '', $postFrmCtrlValToChk = '',
                                 $dbTabName = '', $dbTabFieldName = '') {

        $entityExistenceError = '';
        $dbEntity = $this->getDBEntity($dbTabName);

        if (empty($dbEntity)) {
            $entityExistenceError.= wf_nbsp(2) . wf_tag('b') . $dbTabName . wf_tag('b', true);
        }

        if (!method_exists($this, $webFormMethod)) {
            $entityExistenceError.= wf_nbsp(2) . wf_tag('b') . $webFormMethod . wf_tag('b', true);
        }

        if (!empty($entityExistenceError)) {
            return($this->renderWebMsg(__('Error'),
                                       __('CRUDs processing: possible try to call to non-existent method or entity') . ':'
                                       . $entityExistenceError,
                                       'error'));
        }

        if(ubRouting::checkPost(self::ROUTE_EDIT_REC_ID)
           and !(empty($postFrmCtrlValToChk) or empty($dbTabName) or empty($dbTabFieldName))) {

            $recID      = ubRouting::post(self::ROUTE_EDIT_REC_ID);
            $recEdit    = ubRouting::checkPost(self::ROUTE_ACTION_EDIT, false);
            $recClone   = ubRouting::checkPost(self::ROUTE_ACTION_CLONE, false);

            if ($recEdit or $recClone) {
                if (ubRouting::checkPost($postFrmCtrlValToChk)) {
                    $postValToChk = ubRouting::post($postFrmCtrlValToChk);
                    $recExistArrayChk = array($dbTabFieldName => array('operator' => '=',
                                                                       'fieldval' => $postValToChk));
                    if ($recClone) {
                        $foundProfID = $dbEntity->checkRecExists($recExistArrayChk);
                    } else {
                        $foundProfID = $dbEntity->checkRecExists($recExistArrayChk, $recID);
                    }

                    if (empty($foundProfID)) {
                        if ($recEdit) {
                            $this->recordCreateEdit($dbEntity, $dataArray, $recID);
                        } elseif ($recClone) {
                            $this->recordCreateEdit($dbEntity, $dataArray);
                        }
                    } else {
                        $crudEntityName = empty($crudEntityName) ? 'Entity' : $crudEntityName;
                        return($this->renderWebMsg(__('Error'), __($crudEntityName) . ' ' . __('with such name already exists with ID: ') . $foundProfID));
                    }
                } else {
                    return (call_user_func_array(array($this, $webFormMethod), array(true, $recID, $recEdit, $recClone)));
                }
            }
        } elseif (ubRouting::checkPost(self::ROUTE_ACTION_CREATE)) {
            $this->recordCreateEdit($dbEntity, $dataArray);
        } elseif (ubRouting::checkPost(self::ROUTE_ACTION_DELETE)) {
            if(ubRouting::checkPost(self::ROUTE_DELETE_REC_ID)) {
                $this->recordDelete($dbEntity, ubRouting::post(self::ROUTE_DELETE_REC_ID));
            }
        } else {
            return(call_user_func_array(array($this, $webFormMethod), array(false)));
        }

        return ('');
    }

    /**
     * Renders main module controls
     *
     * @return string
     */
    public function renderMainControls() {
        $inputs = '';

        $inputs.= wf_Link(self::URL_ME . '&' . self::URL_FINOPERATIONS, wf_img_sized('skins/ukv/dollar.png') . ' ' . __('External counterparties list'), false, 'ubButton');

        // dictionaries forms
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

    /**
     * Returns modal window with some message and pre-defined DOM ID
     *
     * @param $title
     * @param $message
     * @param string $style
     *
     * @return string
     */
    public function renderWebMsg($title, $message, $style = 'info') {
        $errormes = $this->messages->getStyledMessage($message, $style, 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
        return(wf_modalAutoForm($title, $errormes, ubRouting::post(self::MISC_ERRFORM_ID_PARAM), '', true, 'true'));
    }

    /**
     * Cumulative method for creating and editing some DB records
     *
     * @param $dbEntity
     * @param $dataArray
     *
     * @param int $recordID
     */
    public function recordCreateEdit($dbEntity, $dataArray, $recordID = 0) {
        $dbEntity->dataArr($dataArray);

        if (!empty($recordID)) {
            $dbEntity->where(self::DBFLD_COMMON_ID, '=', $recordID);
            $dbEntity->save(true, true);

            log_register(get_class($this) . ': EDITED record ID: ' . $recordID . ' in table `' . $dbEntity->getTableName() . '`');
        } else {
            $dbEntity->create();

            log_register(get_class($this) . ': ADDED new record to `' . $dbEntity->getTableName() . '`');
        }
    }

    public function recordDelete($dbEntity, $recordID) {
        $dbEntity->where(self::DBFLD_COMMON_ID, '=', $recordID);
        $dbEntity->delete();

        log_register(get_class($this) . ': REMOVED record ID: ' . $recordID . ' from table `' . $dbEntity->getTableName() . '`');
    }

    /**
     * Returns a profile-editor web form
     *
     * @param bool $modal
     * @param int $profileID
     * @param bool $editAction
     * @param bool $cloneAction
     *
     * @return string
     */
    public function profileWebForm($modal = true, $profileID = 0, $editAction = false, $cloneAction = false) {
        $inputs     = '';
        $prfName    = '';
        $prfContact = '';
        $prfEDRPO   = '';
        $prfEmail   = '';
        $modalWinID     = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and !empty($this->allECProfiles[$profileID])) {
            $profile    = $this->allECProfiles[$profileID];
            $prfName    = $profile[self::DBFLD_PROFILE_NAME];
            $prfContact = $profile[self::DBFLD_PROFILE_CONTACT];
            $prfEDRPO   = $profile[self::DBFLD_PROFILE_EDRPO];
            $prfEmail   = $profile[self::DBFLD_PROFILE_MAIL];
        }

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt   = ($editAction) ? __('Edit counterparty profile') :
                      (($cloneAction) ? __('Clone counterparty profile') :
                      __('Create counterparty profile'));

        $ctrlsLblStyle = 'style="line-height: 2.2em"';

        $inputs.= wf_TextInput(self::CTRL_PROFILE_NAME, __('Name') . $this->supFrmFldMark, $prfName, true, '', '',
                               $emptyCheckClass, '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_PROFILE_CONTACT, __('Contact data'), $prfContact, true, '', '',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_PROFILE_EDRPO, __('EDRPO/INN') . $this->supFrmFldMark, $prfEDRPO, true, '', '',
                               $emptyCheckClass, '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_PROFILE_MAIL, __('E-mail'), $prfEmail, true, '', '',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_delimiter(0);
        $inputs.= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', 'style="width: 100%"');
        $inputs.= wf_HiddenInput(self::ROUTE_PROFILE_ACTS, 'true');

        if ($editAction) {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs.= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $profileID);
        } else {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and !empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_DICTPROFILES . '=true','POST',
                          $inputs, 'glamour ' . $formClass);

        if ($modal and !empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for profiles dictionary
     *
     * @return string
     */
    public function profileRenderJQDT() {
        $ajaxURLStr = '' . self::URL_ME . '&' . self::ROUTE_PROFILE_JSON . '=true';
        $jqdtID = 'jqdt_' . md5($ajaxURLStr);
        $errorModalWindowID = wf_InputId();
        $columns = array();
        $opts = '"order": [[ 0, "asc" ]]';

        $columns[] = __('ID');
        $columns[] = __('Profile name');
        $columns[] = __('EDRPO');
        $columns[] = __('Contact');
        $columns[] = __('E-mail');
        $columns[] = __('Actions');

        $result = wf_JqDtLoader($columns, $ajaxURLStr, false, __('results'), 100, $opts);

        $result.= wf_tag('script', false, '', 'type="text/javascript"');
        $result.= wf_JSEmptyFunc();
        $result.= wf_JSElemInsertedCatcherFunc();

        // putting a "form submitting catcher" JS code to process multiple modal and static forms
        // with one piece of code and ajax requests
        $result.= wf_jsAjaxFormSubmit('.' . self::MISC_CLASS_SUBMITFORM . ', .' . self::MISC_CLASS_SUBMITFORM_MODAL,
                                      '.' . self::MISC_CLASS_MWID_CTRL, $jqdtID,
                                      '.' . self::MISC_CLASS_EMPTYVALCHECK . ', .' . self::MISC_CLASS_EMPTYVALCHECK_MODAL,
                                      self::MISC_ERRFORM_ID_PARAM);

        // putting a piece of JS code to perform records delete action
        $result.= wf_jsAjaxCustomFunc(self::MISC_JS_DEL_FUNC_NAME, $jqdtID, self::MISC_ERRFORM_ID_PARAM);
        $result.= wf_tag('script', true);

        return($result);
    }

    /**
     * Renders JSON for profile's dictionary JQDT
     */
    public function profileRenderListJSON() {
        $this->loadECProfiles();
        $json = new wf_JqDtHelper();

        if (!empty($this->allECProfiles)) {
            $data = array();

            foreach ($this->allECProfiles as $eachRecID) {
                foreach ($eachRecID as $fieldName => $fieldVal) {
                    $data[] = $fieldVal;
                }

                // gathering the delete ajax data query
                $tmpDeleteQuery = '\'&' . self::ROUTE_PROFILE_ACTS  . '=true' .
                                  '&' . self::ROUTE_ACTION_DELETE . '=true' .
                                  '&' . self::ROUTE_DELETE_REC_ID . '=' . $eachRecID['id'] . '\'';

                $deleteDialogWID = 'dialog-modal_' . wf_inputid();
                $deleteDialogCloseFunc = ' $(\'#' . $deleteDialogWID .'\').dialog(\'close\') ';

                $actions = wf_ConfirmDialogJS('#', web_delete_icon(), $this->messages->getDeleteAlert(), '', '#',
                                               self::MISC_JS_DEL_FUNC_NAME . '(\'' . self::URL_ME . '\',' . $tmpDeleteQuery . ');' . $deleteDialogCloseFunc,
                                                $deleteDialogCloseFunc, $deleteDialogWID);

                $actions.= wf_nbsp(2);
                $actions.= wf_jsAjaxDynamicWindowButton(self::URL_ME,
                                                         array(self::ROUTE_PROFILE_ACTS => 'true',
                                                               self::ROUTE_ACTION_EDIT => 'true',
                                                               self::ROUTE_EDIT_REC_ID => $eachRecID['id']),
                                                         '', web_edit_icon()
                                                        );
                $actions.= wf_nbsp(2);
                $actions.= wf_jsAjaxDynamicWindowButton(self::URL_ME,
                                                         array(self::ROUTE_PROFILE_ACTS => 'true',
                                                               self::ROUTE_ACTION_CLONE => 'true',
                                                               self::ROUTE_EDIT_REC_ID => $eachRecID['id']),
                                                         '', web_clone_icon()
                                                        );
                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns a profile-editor web form
     *
     * @param bool $modal
     * @param int $contractID
     * @param bool $editAction
     * @param bool $cloneAction
     *
     * @return string
     */
    public function contractWebForm($modal = true, $contractID = 0, $editAction = false, $cloneAction = false) {
        $inputs             = '';
        $ctrctDTStart       = '';
        $ctrctDTEnd         = '';
        $ctrctFileName      = '';
        $ctrctNumber        = '';
        $ctrctSubject       = '';
        $ctrctAutoProlong   = '';
        $ctrctFullSum       = '';
        $ctrctNotes         = '';
        $modalWinID     = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and !empty($this->allECProfiles[$contractID])) {
            $contract           = $this->allECContracts[$contractID];
            $ctrctDTStart       = $contract[self::DBFLD_CTRCT_DTSTART];
            $ctrctDTEnd         = $contract[self::DBFLD_CTRCT_DTEND];
            $ctrctFileName      = $contract[self::DBFLD_CTRCT_FILENAME];
            $ctrctNumber        = $contract[self::DBFLD_CTRCT_NUMBER];
            $ctrctSubject       = $contract[self::DBFLD_CTRCT_SUBJECT];
            $ctrctAutoProlong   = ubRouting::filters($contract[self::DBFLD_CTRCT_AUTOPRLNG], 'fi', FILTER_VALIDATE_BOOLEAN);
            $ctrctFullSum       = $contract[self::DBFLD_CTRCT_FULLSUM];
            $ctrctNotes         = $contract[self::DBFLD_CTRCT_NOTES];
        }

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt   = ($editAction) ? __('Edit counterparty contract') :
            (($cloneAction) ? __('Clone counterparty contract') :
                __('Create counterparty contract'));

        $ctrlsLblStyle = 'style="line-height: 2.2em"';

        $inputs.= wf_DatePickerPreset(self::CTRL_CTRCT_DTSTART, $ctrctDTStart, true, '', $emptyCheckClass);
        $inputs.= wf_tag('span', false, '', $ctrlsLblStyle);
        $inputs.= wf_nbsp(2) . __('Date start');
        $inputs.= wf_tag('span', true) . wf_nbsp(4);

        $inputs.= wf_DatePickerPreset(self::CTRL_CTRCT_DTEND, $ctrctDTEnd, true, '', $emptyCheckClass);
        $inputs.= wf_tag('span', false, '', $ctrlsLblStyle);
        $inputs.= wf_nbsp(2) . __('Date end');
        $inputs.= wf_tag('span', true) . wf_nbsp(4);

        $inputs.= wf_CheckInput(self::CTRL_CTRCT_AUTOPRLNG, __('Autoprolong'), true, $ctrctAutoProlong, '', '');
        $inputs.= wf_TextInput(self::CTRL_CTRCT_NUMBER, __('Contract number') . $this->supFrmFldMark, $ctrctNumber, false, '', '',
                               $emptyCheckClass, '', '', false, $ctrlsLblStyle);
        $inputs.= wf_nbsp(4);
        $inputs.= wf_TextInput(self::CTRL_CTRCT_FULLSUM, __('Contract full sum'), $ctrctFullSum, true, '4', 'finance',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_CTRCT_SUBJECT, __('Contract subject'), $ctrctSubject, true, '70', '',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_CTRCT_NOTES, __('Contract notes'), $ctrctNotes, true, '70', '',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_delimiter(0);

        if ($editAction and $this->fileStorageEnabled) {
            $this->fileStorage->setItemid($contractID);
            $inputs .= $this->fileStorage->renderFilesPreview(true);
        }

        $inputs.= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', 'style="width: 100%"');
        $inputs.= wf_HiddenInput(self::ROUTE_PROFILE_ACTS, 'true');

//CTRL_CTRCT_FILENAME

        if ($editAction) {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs.= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $contractID);
        } else {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and !empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true','POST',
                          $inputs, 'glamour ' . $formClass);

        if ($modal and !empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }


    public function periodWebForm($modal = true, $periodID = 0, $editAction = false) {
        $inputs     = '';
        $prdName    = '';
        $modalWinID     = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if ($editAction and !empty($this->allECPeriods[$periodID])) {
            $period  = $this->allECPeriods[$periodID];
            $prdName = $period[self::DBFLD_PERIOD_NAME];
        }

        $submitCapt = ($editAction) ? __('Edit') : __('Create');
        $formCapt   = ($editAction) ? __('Edit period') : __('Create period');

        $ctrlsLblStyle = 'style="line-height: 2.2em"';

        $inputs.= wf_TextInput(self::CTRL_PERIOD_NAME, __('Name') . $this->supFrmFldMark, $prdName, true, '', '',
                               $emptyCheckClass, '', '', false, $ctrlsLblStyle);

        $inputs.= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', 'style="width: 100%"');
        $inputs.= wf_HiddenInput(self::ROUTE_PERIOD_ACTS, 'true');

        if ($editAction) {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs.= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $periodID);
        } else {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and !empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_DICTPERIODS . '=true','POST', $inputs, 'glamour ' . $formClass);

        if ($modal and !empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for period dictionary
     *
     * @return string
     */
    public function periodRenderJQDT() {
        $ajaxURLStr = '' . self::URL_ME . '&' . self::ROUTE_PERIOD_JSON . '=true';
        $jqdtID = 'jqdt_' . md5($ajaxURLStr);
        $errorModalWindowID = wf_InputId();
        $columns = array();
        $opts = '"order": [[ 0, "asc" ]]';

        $columns[] = __('ID');
        $columns[] = __('Period name');
        $columns[] = __('Actions');

        $result = wf_JqDtLoader($columns, $ajaxURLStr, false, __('results'), 100, $opts);

        $result.= wf_tag('script', false, '', 'type="text/javascript"');
        $result.= wf_JSEmptyFunc();
        $result.= wf_JSElemInsertedCatcherFunc();

        // putting a "form submitting catcher" JS code to process multiple modal and static forms
        // with one piece of code and ajax requests
        $result.= wf_jsAjaxFormSubmit('.' . self::MISC_CLASS_SUBMITFORM . ', .' . self::MISC_CLASS_SUBMITFORM_MODAL,
                                      '.' . self::MISC_CLASS_MWID_CTRL, $jqdtID,
                                      '.' . self::MISC_CLASS_EMPTYVALCHECK . ', .' . self::MISC_CLASS_EMPTYVALCHECK_MODAL,
                                      self::MISC_ERRFORM_ID_PARAM);

        // putting a piece of JS code to perform records delete action
        $result.= wf_jsAjaxCustomFunc(self::MISC_JS_DEL_FUNC_NAME, $jqdtID, self::MISC_ERRFORM_ID_PARAM);
        $result.= wf_tag('script', true);

        return($result);
    }

    /**
     * Renders JSON for period's dictionary JQDT
     */
    public function periodRenderListJSON() {
        $this->loadECPeriods();
        $json = new wf_JqDtHelper();

        if (!empty($this->allECPeriods)) {
            $data = array();

            foreach ($this->allECPeriods as $eachRecID) {
                foreach ($eachRecID as $fieldName => $fieldVal) {
                    $data[] = $fieldVal;
                }

                // gathering the delete ajax data query
                $tmpDeleteQuery = '\'&' . self::ROUTE_PERIOD_ACTS  . '=true' .
                                  '&' . self::ROUTE_ACTION_DELETE . '=true' .
                                  '&' . self::ROUTE_DELETE_REC_ID . '=' . $eachRecID['id'] . '\'';

                $deleteDialogWID = 'dialog-modal_' . wf_inputid();
                $deleteDialogCloseFunc = ' $(\'#' . $deleteDialogWID .'\').dialog(\'close\') ';

                $actions = wf_ConfirmDialogJS('#', web_delete_icon(), $this->messages->getDeleteAlert(), '', '#',
                                              self::MISC_JS_DEL_FUNC_NAME . '(\'' . self::URL_ME . '\',' . $tmpDeleteQuery . ');' . $deleteDialogCloseFunc,
                                              $deleteDialogCloseFunc, $deleteDialogWID);

                $actions = wf_JSAlert('#', web_delete_icon(), $this->messages->getDeleteAlert(),
                          self::MISC_JS_DEL_FUNC_NAME . '(\'' . self::URL_ME . '\',' . $tmpDeleteQuery . ')');
                $actions.= wf_nbsp(2);
                $actions.= wf_jsAjaxDynamicWindowButton(self::URL_ME,
                                                        array(self::ROUTE_PERIOD_ACTS => 'true',
                                                              self::ROUTE_ACTION_EDIT => 'true',
                                                              self::ROUTE_EDIT_REC_ID => $eachRecID['id']),
                                                        '', web_edit_icon()
                                                       );
                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }
}