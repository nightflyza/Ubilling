<?php


class ExtContras {
    /**
     * Database abstraction layer with for `extcontras` table
     *
     * @var object
     */
    protected $dbExtContras = null;

    /**
     * Database abstraction layer with for `extcontras` table + data JOINed from related tables
     *
     * @var object
     */
    protected $dbExtContrasExten = null;

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
     * Database abstraction layer with for `extcontras_invoices` table
     *
     * @var object
     */
    protected $dbECInvoices = null;

    /**
     * Contains all DB entities objects in array($tableName => $dbEntity)
     *
     * @var array
     */
    protected $dbEntitiesAll = array();

    /**
     * Contains all data entities objects in array($tableName => $dataEntity)
     *
     * @var array
     */
    protected $dataEntitiesAll = array();

    /**
     * Placeholder for $dbExtContras DB table field structure
     *
     * @var array
     */
    protected $dbExtContrasStruct = array();

    /**
     * Placeholder for $dbECProfiles DB table field structure
     *
     * @var array
     */
    protected $dbECProfilesStruct = array();

    /**
     * Placeholder for $dbECContracts DB table field structure
     *
     * @var array
     */
    protected $dbECContractsStruct = array();

    /**
     * Placeholder for $dbECAddress DB table field structure
     *
     * @var array
     */
    protected $dbECAddressStruct = array();

    /**
     * Placeholder for $dbECPeriods DB table field structure
     *
     * @var array
     */
    protected $dbECPeriodsStruct = array();

    /**
     * Placeholder for $dbECExtMoney DB table field structure
     *
     * @var array
     */
    protected $dbECMoneyStruct = array();

    /**
     * Placeholder for $dbECExtInvoices DB table field structure
     *
     * @var array
     */
    protected $dbECInvoicesStruct = array();

    /**
     * Contains all extcontras records from DB as ecid => ecdata
     *
     * @var array
     */
    protected $allExtContras = array();

    /**
     * Contains all extcontras records from DB as ecid => ecdata + data JOINed from related tables
     *
     * @var array
     */
    protected $allExtContrasExten = array();

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
     * Contains all extcontras invoices records from DB ecinvoiceid => ecinvoicedata
     *
     * @var array
     */
    protected $allECInvoices = array();

    /**
     * System config object placeholder
     *
     * @var null
     */
    protected $ubConfig = null;

    /**
     * UbillingCache instance placeholder
     *
     * @var null
     */
    protected $ubCache = null;

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
     * Placeholder for EXTCONTRAS_CACHE_LIFETIME from alter.ini
     *
     * @var int
     */
    protected $cacheLifeTime = 1800;

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
     * Background color for records which have payments in current month
     *
     * @var string
     */
    protected $payedThisMonthBKGND = '';

    /**
     * Foreground color for records which have payments in current month
     *
     * @var string
     */
    protected $payedThisMonthFRGND = '';

    /**
     * Background color for records which need to be payed during next 5 days
     *
     * @var string
     */
    protected $fiveDaysTillPayBKGND = '';

    /**
     * Foreground color for records which need to be payed during next 5 days
     *
     * @var string
     */
    protected $fiveDaysTillPayFRGND = '';

    /**
     * Background color for records which payday has passed already
     *
     * @var string
     */
    protected $paymentExpiredBKGND = '';

    /**
     * Foreground color for records which payday has passed already
     *
     * @var string
     */
    protected $paymentExpiredFRGND = '';

    /**
     * Routes, static defines, etc
     */
    const SAY_MY_NAME = 'CONTRAS';

    const URL_ME = '?module=extcontras';
    const URL_EXTCONTRAS        = 'extcontraslist';
    const URL_EXTCONTRAS_COLORS = 'extcontrascolors';
    const URL_DICTPROFILES      = 'dictprofiles';
    const URL_DICTCONTRACTS     = 'dictcontracts';
    const URL_DICTADDRESS       = 'dictaddress';
    const URL_DICTPERIODS       = 'dictperiods';
    const URL_FINOPERATIONS     = 'finoperations';
    const URL_INVOICES          = 'invoices';

    const DBFLD_COMMON_ID       = 'id';

    const CTRL_PROFILE_NAME     = 'profname';
    const CTRL_PROFILE_EDRPO    = 'profedrpo';
    const CTRL_PROFILE_CONTACT  = 'profcontact';
    const CTRL_PROFILE_MAIL     = 'profmail';
    const CTRL_PROFILE_SELECTOR = 'profdropdown';

    const DBFLD_PROFILE_NAME    = 'name';
    const DBFLD_PROFILE_EDRPO   = 'edrpo';
    const DBFLD_PROFILE_CONTACT = 'contact';
    const DBFLD_PROFILE_MAIL    = 'email';

    const CTRL_CTRCT_CONTRACT   = 'ctrctcontract';
    const CTRL_CTRCT_DTSTART    = 'ctrctdtstart';
    const CTRL_CTRCT_DTEND      = 'ctrctdtend';
    const CTRL_CTRCT_SUBJECT    = 'ctrctsubject';
    const CTRL_CTRCT_AUTOPRLNG  = 'ctrctautoprolong';
    const CTRL_CTRCT_FULLSUM    = 'ctrctfullsum';
    const CTRL_CTRCT_NOTES      = 'ctrctnotes';
    const CTRL_CTRCT_SELECTOR   = 'ctrctdropdown';

    const DBFLD_CTRCT_CONTRACT  = 'contract';
    const DBFLD_CTRCT_DTSTART   = 'date_start';
    const DBFLD_CTRCT_DTEND     = 'date_end';
    const DBFLD_CTRCT_SUBJECT   = 'subject';
    const DBFLD_CTRCT_AUTOPRLNG = 'autoprolong';
    const DBFLD_CTRCT_FULLSUM   = 'full_sum';
    const DBFLD_CTRCT_NOTES     = 'notes';

    const CTRL_ADDRESS_ADDR     = 'addraddress';
    const CTRL_ADDRESS_SUM      = 'addrsumm';
    const CTRL_ADDRESS_CTNOTES  = 'addrctrctnotes';
    const CTRL_ADDRESS_NOTES    = 'addrnotes';
    const CTRL_ADDRESS_SELECTOR = 'addrdropdown';

    const DBFLD_ADDRESS_ADDR    = 'address';
    const DBFLD_ADDRESS_SUM     = 'summ';
    const DBFLD_ADDRESS_CTNOTES = 'contract_notes';
    const DBFLD_ADDRESS_NOTES   = 'notes';

    const CTRL_PERIOD_NAME      = 'prdname';
    const CTRL_PERIOD_SELECTOR  = 'prddropdown';
    const DBFLD_PERIOD_NAME     = 'period_name';

    const CTRL_MONEY_CONTRASID  = 'moneycontrasrecid';
    const CTRL_MONEY_ACCRUALID  = 'moneyaccrualid';
    const CTRL_MONEY_DATE       = 'moneydate';
    const CTRL_MONEY_SUMACCRUAL = 'moneysummaccrual';
    const CTRL_MONEY_SUNPAYMENT = 'moneysummpayment';
    const CTRL_MONEY_INOUT      = 'moneyinout';

    const DBFLD_MONEY_CONTRASID = 'contras_rec_id';
    const DBFLD_MONEY_ACCRUALID = 'accrual_id';
    const DBFLD_MONEY_DATE      = 'date';
    const DBFLD_MONEY_SMACCRUAL = 'summ_accrual';
    const DBFLD_MONEY_SMPAYMENT = 'summ_payment';
    const DBFLD_MONEY_INCOMING  = 'incoming';
    const DBFLD_MONEY_OUTGOING  = 'outgoing';

    const CTRL_INVOICES_CONTRASID       = 'invocontrasrecid';
    const CTRL_INVOICES_INTERNAL_NUM    = 'invointernalnum';
    const CTRL_INVOICES_INVOICE_NUM     = 'invoicenum';
    const CTRL_INVOICES_DATE            = 'invodate';
    const CTRL_INVOICES_SUM             = 'invosumm';
    const CTRL_INVOICES_SUM_VAT         = 'invosummvat';
    const CTRL_INVOICES_NOTES           = 'invonotes';
    const CTRL_INVOICES_IN_OUT          = 'invoinout';

    const DBFLD_INVOICES_CONTRASID      = 'contras_rec_id';
    const DBFLD_INVOICES_INTERNAL_NUM   = 'internal_number';
    const DBFLD_INVOICES_INVOICE_NUM    = 'invoice_number';
    const DBFLD_INVOICES_DATE           = 'date';
    const DBFLD_INVOICES_SUM            = 'summ';
    const DBFLD_INVOICES_SUM_VAT        = 'summ_vat';
    const DBFLD_INVOICES_NOTES          = 'notes';
    const DBFLD_INVOICES_INCOMING       = 'incoming';
    const DBFLD_INVOICES_OUTGOING       = 'outgoing';

    const CTRL_EXTCONTRAS_PROFILE_ID    = 'extcontraprofileid';
    const CTRL_EXTCONTRAS_CONTRACT_ID   = 'extcontracontractid';
    const CTRL_EXTCONTRAS_ADDRESS_ID    = 'extcontraaddressid';
    const CTRL_EXTCONTRAS_PERIOD_ID     = 'extcontraperiodid';
    const CTRL_EXTCONTRAS_PAYDAY        = 'extcontrapayday';

    const DBFLD_EXTCONTRAS_PROFILE_ID   = 'contras_id';
    const DBFLD_EXTCONTRAS_CONTRACT_ID  = 'contract_id';
    const DBFLD_EXTCONTRAS_ADDRESS_ID   = 'address_id';
    const DBFLD_EXTCONTRAS_PERIOD_ID    = 'period_id';
    const DBFLD_EXTCONTRAS_PAYDAY       = 'payday';

    const CTRL_ECCOLOR_PAYEDTHISMONTH_BKGND  = 'EC_PAYEDTHISMONTH_BKGND';
    const CTRL_ECCOLOR_PAYEDTHISMONTH_FRGND  = 'EC_PAYEDTHISMONTH_FRGND';
    const CTRL_ECCOLOR_FIVEDAYSTILLPAY_BKGND = 'EC_FIVEDAYSTILLPAY_BKGND';
    const CTRL_ECCOLOR_FIVEDAYSTILLPAY_FRGND = 'EC_FIVEDAYSTILLPAY_FRGND';
    const CTRL_ECCOLOR_PAYMENTEXPIRED_BKGND  = 'EC_PAYMENTEXPIRED_BKGND';
    const CTRL_ECCOLOR_PAYMENTEXPIRED_FRGND  = 'EC_PAYMENTEXPIRED_FRGND';


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
    const ROUTE_CONTRACT_ACTS   = 'contractacts';
    const ROUTE_CONTRACT_JSON   = 'contractlistjson';
    const ROUTE_ADDRESS_ACTS    = 'addressacts';
    const ROUTE_ADDRESS_JSON    = 'addresslistjson';
    const ROUTE_PERIOD_ACTS     = 'periodacts';
    const ROUTE_PERIOD_JSON     = 'periodlistjson';
    const ROUTE_FINOPS_ACTS     = 'finopsacts';
    const ROUTE_FINOPS_JSON     = 'finopslistjson';
    const ROUTE_INVOICES_ACTS   = 'invoicesacts';
    const ROUTE_INVOICES_JSON   = 'invoiceslistjson';
    const ROUTE_FORCECACHE_UPD  = 'extcontrasforcecacheupdate';


    const TABLE_EXTCONTRAS      = 'extcontras';
    const TABLE_EXTCONTRASEXTEN = 'extcontrasexten';
    const TABLE_ECPROFILES      = 'extcontras_profiles';
    const TABLE_ECCONTRACTS     = 'extcontras_contracts';
    const TABLE_ECADDRESS       = 'extcontras_address';
    const TABLE_ECPERIODS       = 'extcontras_periods';
    const TABLE_ECMONEY         = 'extcontras_money';
    const TABLE_ECINVOICES      = 'extcontras_invoices';

    const MISC_FILESTORAGE_SCOPE         = 'EXCONTRAS';
    const MISC_CLASS_MWID_CTRL           = '__FormModalWindowID';
    const MISC_CLASS_SUBMITFORM          = '__FormSubmit';
    const MISC_CLASS_SUBMITFORM_MODAL    = '__FormSubmitModal';
    const MISC_CLASS_EMPTYVALCHECK       = '__EmptyCheckControl';
    const MISC_CLASS_EMPTYVALCHECK_MODAL = '__EmptyCheckControlModal';
    const MISC_JS_DEL_FUNC_NAME          = 'deleteRec';
    const MISC_ERRFORM_ID_PARAM          = 'errfrmid';
    const MISC_MARKROW_URL               = 'markrowid';
    const MISC_WEBFILTER_DATE_START      = 'datefilterstart';
    const MISC_WEBFILTER_DATE_END        = 'datefilterend';
    const MISC_WEBFILTER_PAYDAY          = 'paydayfilter';


    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
        $this->ubCache  = new UbillingCache();
        $this->messages = new UbillingMessageHelper();

        $this->loadOptions();
        $this->initDBEntities();
        $this->loadDBTableStructs();
        $this->loadAllData();
        $this->getTableGridColorOpts();

        if ($this->fileStorageEnabled) {
            $this->fileStorage = new FileStorage(self::MISC_FILESTORAGE_SCOPE);
        }

        $this->supFrmFldMark = wf_tag('sup') . '*' . wf_tag('sup', true);
    }

    /**
     * Loads alter.ini options
     */
    protected function loadOptions() {
        $this->fileStorageEnabled = $this->ubConfig->getAlterParam('FILESTORAGE_ENABLED');
        $this->ecEditablePreiod   = $this->ubConfig->getAlterParam('EXTCONTRAS_EDIT_ALLOWED_DAYS');
        $this->ecEditablePreiod   = empty($this->ecEditablePreiod) ? 60 : $this->ecEditablePreiod;
        $this->cacheLifeTime      = $this->ubConfig->getAlterParam('EXTCONTRAS_CACHE_LIFETIME', 1800);
        $this->ecReadOnlyAccess   = (!cfr('EXTCONTRASRW'));
    }

    /**
     * Inits DB NyanORM objects
     */
    protected function initDBEntities() {
        $this->dbExtContras  = new NyanORM(self::TABLE_EXTCONTRAS);
        $this->dbEntitiesAll[self::TABLE_EXTCONTRAS]    = $this->dbExtContras;
        $this->dataEntitiesAll[self::TABLE_EXTCONTRAS]  = 'allExtContras';

        $this->dbExtContrasExten = new NyanORM(self::TABLE_EXTCONTRAS);
        $this->dbEntitiesAll[self::TABLE_EXTCONTRASEXTEN]    = $this->dbExtContrasExten;
        $this->dataEntitiesAll[self::TABLE_EXTCONTRASEXTEN]  = 'allExtContrasExten';

        $this->dbECProfiles  = new NyanORM(self::TABLE_ECPROFILES);
        $this->dbEntitiesAll[self::TABLE_ECPROFILES]    = $this->dbECProfiles;
        $this->dataEntitiesAll[self::TABLE_ECPROFILES]  = 'allECProfiles';

        $this->dbECContracts = new NyanORM(self::TABLE_ECCONTRACTS);
        $this->dbEntitiesAll[self::TABLE_ECCONTRACTS]   = $this->dbECContracts;
        $this->dataEntitiesAll[self::TABLE_ECCONTRACTS] = 'allECContracts';

        $this->dbECAddress   = new NyanORM(self::TABLE_ECADDRESS);
        $this->dbEntitiesAll[self::TABLE_ECADDRESS]     = $this->dbECAddress;
        $this->dataEntitiesAll[self::TABLE_ECADDRESS]   = 'allECAddresses';

        $this->dbECPeriods   = new NyanORM(self::TABLE_ECPERIODS);
        $this->dbEntitiesAll[self::TABLE_ECPERIODS]     = $this->dbECPeriods;
        $this->dataEntitiesAll[self::TABLE_ECPERIODS]   = 'allECPeriods';

        $this->dbECMoney     = new NyanORM(self::TABLE_ECMONEY);
        $this->dbEntitiesAll[self::TABLE_ECMONEY]       = $this->dbECMoney;
        $this->dataEntitiesAll[self::TABLE_ECMONEY]     = 'allECMoney';

        $this->dbECInvoices  = new NyanORM(self::TABLE_ECINVOICES);
        $this->dbEntitiesAll[self::TABLE_ECINVOICES]    = $this->dbECInvoices;
        $this->dataEntitiesAll[self::TABLE_ECINVOICES]  = 'allECInvoices';
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
     * Returns data entity object by table name
     *
     * @param $dataEntityName
     *
     * @return mixed|null
     */
    public function getDataEntity($dataEntityName) {
        $result = null;

        if (!empty($this->dataEntitiesAll[$dataEntityName])) {
            $result = $this->dataEntitiesAll[$dataEntityName];
        }

        return ($result);
    }

    /**
     * Loads DB tables fields structures to a class properties
     */
    protected function loadDBTableStructs() {
        $this->dbExtContrasStruct   = $this->dbExtContras->getTableStructure(true, false, true, true);
        $this->dbECProfilesStruct   = $this->dbECProfiles->getTableStructure(true, false, true, true);
        $this->dbECContractsStruct  = $this->dbECContracts->getTableStructure(true, false, true, true);
        $this->dbECAddressStruct    = $this->dbECAddress->getTableStructure(true, false, true, true);
        $this->dbECPeriodsStruct    = $this->dbECPeriods->getTableStructure(true, false, true, true);
        $this->dbECMoneyStruct      = $this->dbECMoney->getTableStructure(true, false, true, true);
        $this->dbECInvoicesStruct   = $this->dbECInvoices->getTableStructure(true, false, true, true);
    }

     /**
     * Loads data from a DB table or UB cache
     *
     * @param string $tableName
     * @param string $cacheKey
     * @param bool $forceDBLoad
     * @param bool $flushNyanParams
     * @param string $assocByField
     * @param string $dataEntity
     * @param bool $cachingDisabled
     *
     * @return mixed
     */
    public function loadDataFromTableCached($tableName, $cacheKey, $forceDBLoad = false, $flushNyanParams = true, $assocByField = '', $dataEntity = '', $cachingDisabled = false) {

        $cacheKey       = strtoupper($cacheKey);
        $dbInstance     = $this->getDBEntity($tableName);
        $flushParams    = $flushNyanParams;
        $assocByField   = (empty($assocByField) ? 'id' : $assocByField);
        $dataInstance   = (empty($dataEntity) ? $this->getDataEntity($tableName) : $dataEntity);
        $thisInstance   = $this;

        if ($forceDBLoad) {
            $this->$dataInstance = $dbInstance->getAll($assocByField, $flushParams);

            if ($cachingDisabled) {
                $this->ubCache->delete($cacheKey);
            } else {
                $this->ubCache->set($cacheKey, $this->$dataInstance, $this->cacheLifeTime);
            }
        } else {
            $this->$dataInstance = $this->ubCache->getCallback($cacheKey, function () use ($thisInstance, $tableName, $cacheKey, $flushParams, $assocByField) {
                                                                    return ($thisInstance->loadDataFromTableCached($tableName, $cacheKey, true,
                                                                                                                   $flushParams, $assocByField));
                                                                }, $this->cacheLifeTime);
        }

        return ($this->$dataInstance);
    }

    /**
     * Loads extended external counterparties data
     *
     * @param bool $forceDBLoad
     * @param string $whereRaw
     */
    protected function loadExtContrasExtenData($forceDBLoad = false, $whereRaw = '') {
        $selectable = array_merge($this->dbExtContrasStruct, $this->dbECProfilesStruct, $this->dbECContractsStruct, $this->dbECAddressStruct, $this->dbECPeriodsStruct);

        $this->dbExtContrasExten->selectable($selectable);
        $this->dbExtContrasExten->joinOn();
        $this->dbExtContrasExten->joinOn('LEFT', self::TABLE_ECPROFILES,
                                        self::TABLE_EXTCONTRAS . '.' . self::DBFLD_EXTCONTRAS_PROFILE_ID
                                        . ' = ' . self::TABLE_ECPROFILES . '.' . self::DBFLD_COMMON_ID);
        $this->dbExtContrasExten->joinOn('LEFT', self::TABLE_ECCONTRACTS,
                                        self::TABLE_EXTCONTRAS . '.' . self::DBFLD_EXTCONTRAS_CONTRACT_ID
                                        . ' = ' . self::TABLE_ECCONTRACTS . '.' . self::DBFLD_COMMON_ID);
        $this->dbExtContrasExten->joinOn('LEFT', self::TABLE_ECADDRESS,
                                        self::TABLE_EXTCONTRAS . '.' . self::DBFLD_EXTCONTRAS_ADDRESS_ID
                                        . ' = ' . self::TABLE_ECADDRESS . '.' . self::DBFLD_COMMON_ID);
        $this->dbExtContrasExten->joinOn('LEFT', self::TABLE_ECPERIODS,
                                        self::TABLE_EXTCONTRAS . '.' . self::DBFLD_EXTCONTRAS_PERIOD_ID
                                        . ' = ' . self::TABLE_ECPERIODS . '.' . self::DBFLD_COMMON_ID);

        if (!empty($whereRaw)) {
            $this->dbExtContrasExten->whereRaw($whereRaw);
        }

        $this->loadDataFromTableCached(self::TABLE_EXTCONTRASEXTEN, self::TABLE_EXTCONTRASEXTEN, $forceDBLoad,
                                       false, self::TABLE_EXTCONTRAS . self::DBFLD_COMMON_ID);
    }

    /**
     * Unified data loader
     */
    protected function loadAllData($forceDBLoad = false) {
        $this->loadDataFromTableCached(self::TABLE_EXTCONTRAS, self::TABLE_EXTCONTRAS, $forceDBLoad);
        $this->loadDataFromTableCached(self::TABLE_ECPROFILES, self::TABLE_ECPROFILES, $forceDBLoad);
        $this->loadDataFromTableCached(self::TABLE_ECCONTRACTS, self::TABLE_ECCONTRACTS, $forceDBLoad);
        $this->loadDataFromTableCached(self::TABLE_ECADDRESS, self::TABLE_ECADDRESS, $forceDBLoad);
        $this->loadDataFromTableCached(self::TABLE_ECPERIODS, self::TABLE_ECPERIODS, $forceDBLoad);
        $this->loadDataFromTableCached(self::TABLE_ECMONEY, self::TABLE_ECMONEY, $forceDBLoad);
        $this->loadDataFromTableCached(self::TABLE_ECINVOICES, self::TABLE_ECINVOICES, $forceDBLoad);
        $this->loadExtContrasExtenData($forceDBLoad);
    }

    /**
     * Forcibly updates cached data
     */
    public function refreshCacheForced() {
        $this->loadAllData(true);
    }

    /**
     * Returns prepared filtering array for NyanORM checkRecExists() method
     *
     * @param $dbTabField
     * @param $operator
     * @param $dbFieldValue
     *
     * @return array
     */
    public function createCheckUniquenessArray($dbTabField, $operator, $dbFieldValue) {
        $tmpArray = array($dbTabField => array('operator' => $operator,
                                               'fieldval' => $dbFieldValue)
                         );

        return ($tmpArray);
    }

    /**
     * Saves counterparties list coloring to ubStorage
     */
    public function setTableGridColorOpts() {
        zb_StorageSet(self::CTRL_ECCOLOR_PAYEDTHISMONTH_BKGND, ubRouting::post(self::CTRL_ECCOLOR_PAYEDTHISMONTH_BKGND));
        zb_StorageSet(self::CTRL_ECCOLOR_PAYEDTHISMONTH_FRGND, ubRouting::post(self::CTRL_ECCOLOR_PAYEDTHISMONTH_FRGND));
        zb_StorageSet(self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_BKGND, ubRouting::post(self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_BKGND));
        zb_StorageSet(self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_FRGND, ubRouting::post(self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_FRGND));
        zb_StorageSet(self::CTRL_ECCOLOR_PAYMENTEXPIRED_BKGND, ubRouting::post(self::CTRL_ECCOLOR_PAYMENTEXPIRED_BKGND));
        zb_StorageSet(self::CTRL_ECCOLOR_PAYMENTEXPIRED_FRGND, ubRouting::post(self::CTRL_ECCOLOR_PAYMENTEXPIRED_FRGND));
    }

    /**
     * Loads counterparties list coloring to class properties
     */
    public function getTableGridColorOpts() {
        $this->payedThisMonthBKGND = zb_StorageGet(self::CTRL_ECCOLOR_PAYEDTHISMONTH_BKGND);
        $this->payedThisMonthBKGND = (empty($this->payedThisMonthBKGND) ? '#4f7318' : $this->payedThisMonthBKGND);

        $this->payedThisMonthFRGND = zb_StorageGet(self::CTRL_ECCOLOR_PAYEDTHISMONTH_FRGND);
        $this->payedThisMonthFRGND = (empty($this->payedThisMonthFRGND) ? '#ffffff' : $this->payedThisMonthFRGND);

        $this->fiveDaysTillPayBKGND = zb_StorageGet(self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_BKGND);
        $this->fiveDaysTillPayBKGND = (empty($this->fiveDaysTillPayBKGND) ? '#ffff00' : $this->fiveDaysTillPayBKGND);

        $this->fiveDaysTillPayFRGND = zb_StorageGet(self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_FRGND);
        $this->fiveDaysTillPayFRGND = (empty($this->fiveDaysTillPayFRGND) ? '#4800ff' : $this->fiveDaysTillPayFRGND);

        $this->paymentExpiredBKGND = zb_StorageGet(self::CTRL_ECCOLOR_PAYMENTEXPIRED_BKGND);
        $this->paymentExpiredBKGND = (empty($this->paymentExpiredBKGND) ? '#9e1313' : $this->paymentExpiredBKGND);

        $this->paymentExpiredFRGND = zb_StorageGet(self::CTRL_ECCOLOR_PAYMENTEXPIRED_FRGND);
        $this->paymentExpiredFRGND = (empty($this->paymentExpiredFRGND) ? '#ffff44' : $this->paymentExpiredFRGND);
    }


    /*public function getDateRangeFilterForm($dateStart = '', $dateEnd = '', $inTable = true, $vertical = false) {
        $inputs = '';
        $rows   = '';
        $datepeakerStart     = wf_DatePickerPreset(self::MISC_DATE_FILTER_START, $dateStart, true);
        $datepeakerEnd       = wf_DatePickerPreset(self::MISC_DATE_FILTER_END, $dateEnd, true);
        $datepeakerStartCapt = __('Send date from:');
        $datepeakerEndCapt   = __('Send date to:');

        if ($inTable) {
            $cells = wf_TableCell($datepeakerStartCapt);
            $cells.= wf_TableCell($datepeakerStart);

            if ($vertical) {
                $rows = wf_TableRow($cells);
                $cells = '';
            }

            $cells.= wf_TableCell($datepeakerEndCapt);
            $cells.= wf_TableCell($datepeakerEnd);

            $rows .= wf_TableRow($cells);
            $inputs = wf_TableBody($rows, 'auto', '0', '', '');
        } else {
            $inputs.= $datepeakerStartCapt . wf_nbsp(2) . $datepeakerStart;

            if ($vertical) {
                $inputs.= wf_delimiter();
            }

            $inputs.= $datepeakerEndCapt . wf_nbsp(2) . $datepeakerEnd;
        }

        return($inputs);
    }*/

    /**
     * Searches for any occurrences of current month payments for a certain counterparty ID
     *
     * @param $ecRecID
     *
     * @return string
     */
    protected function checkCurMonthPaymExists($ecRecID) {
        $result = '';

        if (!empty($ecRecID) and !empty($this->allExtContras[$ecRecID][self::DBFLD_EXTCONTRAS_PAYDAY])) {
            $tmpECPayDay    = $this->allExtContras[$ecRecID][self::DBFLD_EXTCONTRAS_PAYDAY];
            $curMonthStart  = date('Y-m-') . '01';
            $curMonthEnd    = date('Y-m-') . $tmpECPayDay;

            $this->dbECMoney->selectable('id');
            $this->dbECMoney->where('contras_rec_id', '=', $ecRecID);
            $this->dbECMoney->where('summ_payment', '!=', 0);
            $this->dbECMoney->whereRaw(' `date` BETWEEN ' . $curMonthStart . ' AND ' . $curMonthEnd . ' + INTERVAL 1 DAY ');
            $result = $this->dbECMoney->getAll('id');
        }

        return ($result);
    }

    /**
     * Returns typical JQDT with or without JS code for interacting with modals and dynamic modals
     *
     * @param $ajaxURL
     * @param $columnsArr
     * @param string $columnsOpts
     * @param bool $stdJSForCRUDs
     * @param string $customJSCode
     * @param string $truncateURL
     * @param string $truncateParam
     *
     * @param string|int $markRowForID
     * @return string
     */
    protected function getStdJQDTWithJSForCRUDs($ajaxURL, $columnsArr, $columnsOpts = '', $stdJSForCRUDs = true,
                                                $customJSCode = '', $markRowForID = '', $truncateURL = '', $truncateParam = '') {
        $result     = '';
        $ajaxURLStr = $ajaxURL;
        $jqdtID     = 'jqdt_' . md5($ajaxURLStr);
        $columns    = $columnsArr;
        $opts       = (empty($columnsOpts) ? '"order": [[ 0, "asc" ]]' : $columnsOpts);

        if (!empty($markRowForID)) {
            $result.= wf_EncloseWithJSTags(wf_JQDTRowShowPluginJS());
        }

        $result.= wf_JqDtLoader($columns, $ajaxURLStr, false, __('results'), 100, $opts);

        if ($stdJSForCRUDs) {
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

            if (!empty($markRowForID)) {
                $result.= wf_JQDTMarkRow(0, $markRowForID, $truncateURL, $truncateParam);
            }

            $result.= wf_tag('script', true);
        }

        if (!empty($customJSCode)) {
            $result.= wf_EncloseWithJSTags($customJSCode);
        }

        return ($result);
    }

    /**
     * Returns typical JQDT "actions" controls, like "Delete", "Edit", "Clone"
     *
     * @param int $recID
     * @param string $routeActs
     * @param bool $cloneButtonON
     * @param string $customControls
     *
     * @return string
     */
    protected function getStdJQDTActions($recID, $routeActs, $cloneButtonON = false, $customControls = '') {
        $actions = '';

        // gathering the delete ajax data query
        $tmpDeleteQuery = '\'&' . $routeActs  . '=true' .
                          '&' . self::ROUTE_ACTION_DELETE . '=true' .
                          '&' . self::ROUTE_DELETE_REC_ID . '=' . $recID . '\'';

        $deleteDialogWID = 'dialog-modal_' . wf_inputid();
        $deleteDialogCloseFunc = ' $(\'#' . $deleteDialogWID .'\').dialog(\'close\') ';

        $actions = wf_ConfirmDialogJS('#', web_delete_icon(), $this->messages->getDeleteAlert(), '', '#',
                                      self::MISC_JS_DEL_FUNC_NAME . '(\'' . self::URL_ME . '\',' . $tmpDeleteQuery . ');' . $deleteDialogCloseFunc,
                                      $deleteDialogCloseFunc, $deleteDialogWID);

        $actions .= wf_nbsp(2);
        $actions .= wf_jsAjaxDynamicWindowButton(self::URL_ME,
                                                 array($routeActs => 'true',
                                                       self::ROUTE_ACTION_EDIT => 'true',
                                                       self::ROUTE_EDIT_REC_ID => $recID),
                                                 '', web_edit_icon()
                                                );

        if ($cloneButtonON) {
            $actions .= wf_nbsp(2);
            $actions .= wf_jsAjaxDynamicWindowButton(self::URL_ME,
                                                     array($routeActs => 'true',
                                                           self::ROUTE_ACTION_CLONE => 'true',
                                                           self::ROUTE_EDIT_REC_ID => $recID),
                                                     '', web_clone_icon()
                                                    );
        }

        $actions.= $customControls;

        return ($actions);
    }

    /**
     *  Ash oghum durbatulûk, ash oghum gimbatul,
     *  Ash oghum thrakatulûk, agh burzum-ishi krimpatul.
     *
     * @param array     $dataArray
     * @param string    $dbTabName
     * @param string    $postFrmCtrlValToChk
     * @param string    $webFormMethod
     * @param bool      $checkUniqueness
     * @param array     $checkUniqArray
     * @param string    $crudEntityName
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function processCRUDs($dataArray, $dbTabName, $postFrmCtrlValToChk, $webFormMethod,
                                 $checkUniqueness = true, $checkUniqArray = array(),
                                 $crudEntityName = '') {

        $recID              = ubRouting::post(self::ROUTE_EDIT_REC_ID);
        $recEdit            = ubRouting::checkPost(self::ROUTE_ACTION_EDIT, false);
        $recClone           = ubRouting::checkPost(self::ROUTE_ACTION_CLONE, false);
        $recCreate          = ubRouting::checkPost(self::ROUTE_ACTION_CREATE);
        $recDelete          = ubRouting::checkPost(self::ROUTE_ACTION_DELETE);
        $dbEntity           = $this->getDBEntity($dbTabName);
        $foundRecID         = '';
        $crudEntityName     = empty($crudEntityName) ? 'Entity' : $crudEntityName;
        $entityExistError   = '';

        if (empty($dbEntity)) {
            $entityExistError.= wf_nbsp(2) . wf_tag('b') . $dbTabName . wf_tag('b', true);
        }

        if (!method_exists($this, $webFormMethod)) {
            $entityExistError.= wf_nbsp(2) . wf_tag('b') . $webFormMethod . wf_tag('b', true);
        }

        // checking record uniqueness upon criteria, if needed
        if ($checkUniqueness and !$recDelete) {
            if (empty($checkUniqArray)) {
                $entityExistError .= wf_nbsp(2) . wf_tag('b') . '$checkUniqArray' . wf_tag('b', true);
            } else {
                if ($recEdit) {
                    if (!empty($recID)) {
                        $foundRecID = $dbEntity->checkRecExists($checkUniqArray, $recID);
                    } else {
                        $entityExistError .= wf_nbsp(2) . wf_tag('b') . '$recID' . wf_tag('b', true);
                    }
                } else {
                    $foundRecID = $dbEntity->checkRecExists($checkUniqArray);
                }

                if (!empty($foundRecID)) {
                    return ($this->renderWebMsg(__('Error'),
                            __($crudEntityName) . ' ' . __('with such fields criteria') . ' '
                            . print_r($checkUniqArray, true) . ' '
                            . __('already exists with ID') . ': ' . $foundRecID,
                            'error'));
                }
            }
        }

        if (!empty($entityExistError)) {
            return($this->renderWebMsg(__('Error'),
                   __('CRUDs processing: possible try to call to/use of non-existent method, data or DB entity') . ':'
                   . $entityExistError,
                   'error'));
        }

        if(!empty($recID) and ($recEdit or $recClone)) {
            if (ubRouting::checkPost($postFrmCtrlValToChk)) {
                if ($recEdit) {
                    $this->recordCreateEdit($dbEntity, $dataArray, $recID);
                } elseif ($recClone) {
                    $this->recordCreateEdit($dbEntity, $dataArray);
                }
            }  else {
                return (call_user_func_array(array($this, $webFormMethod), array(true, $recID, $recEdit, $recClone)));
            }
        } elseif ($recCreate) {
            $this->recordCreateEdit($dbEntity, $dataArray);
        } elseif ($recDelete) {
            if(ubRouting::checkPost(self::ROUTE_DELETE_REC_ID)) {
                $delRecID         = ubRouting::post(self::ROUTE_DELETE_REC_ID);
                $tmpUniqArray     = array();
                $delRecProtected  = false;
                $protectionChkArr = array();
                $protectionChkTab = '';
                $protectionChkFld = '';

                if (ubRouting::checkPost(self::ROUTE_PROFILE_ACTS)) {
                    $protectionChkFld   = self::DBFLD_EXTCONTRAS_PROFILE_ID;
                    $protectionChkTab   = self::TABLE_EXTCONTRAS;
                    $protectionChkArr[] = array($protectionChkTab => $protectionChkFld);
                }

                if (ubRouting::checkPost(self::ROUTE_CONTRACT_ACTS)) {
                    $protectionChkFld = self::DBFLD_EXTCONTRAS_CONTRACT_ID;
                    $protectionChkTab = self::TABLE_EXTCONTRAS;
                    $protectionChkArr[] = array($protectionChkTab => $protectionChkFld);
                }

                if (ubRouting::checkPost(self::ROUTE_ADDRESS_ACTS)) {
                    $protectionChkFld = self::DBFLD_EXTCONTRAS_ADDRESS_ID;
                    $protectionChkTab = self::TABLE_EXTCONTRAS;
                    $protectionChkArr[] = array($protectionChkTab => $protectionChkFld);
                }

                if (ubRouting::checkPost(self::ROUTE_PERIOD_ACTS)) {
                    $protectionChkFld = self::DBFLD_EXTCONTRAS_PERIOD_ID;
                    $protectionChkTab = self::TABLE_EXTCONTRAS;
                    $protectionChkArr[] = array($protectionChkTab => $protectionChkFld);
                }

                if (ubRouting::checkPost(self::ROUTE_CONTRAS_ACTS)) {
                    $protectionChkFld = self::DBFLD_INVOICES_CONTRASID;
                    $protectionChkTab = self::TABLE_ECINVOICES;
                    $protectionChkArr[] = array($protectionChkTab => $protectionChkFld);

                    $protectionChkFld = self::DBFLD_MONEY_CONTRASID;
                    $protectionChkTab = self::TABLE_ECMONEY;
                    $protectionChkArr[] = array($protectionChkTab => $protectionChkFld);
                }

                foreach ($protectionChkArr as $tmpArray) {
                    foreach ($tmpArray as $table => $field) {
                        $tmpUniqArray = $this->createCheckUniquenessArray($field, '=', $delRecID);
                        $protectionChkTab = $this->getDBEntity($table);
                        $delRecProtected = $protectionChkTab->checkRecExists($tmpUniqArray);
                    }
                }

                if (empty($delRecProtected)) {
                    $this->recordDelete($dbEntity, $delRecID);
                } else {
                    return($this->renderWebMsg(__('Warning'),
                           __('CRUDs processing: can\'t delete record because it\'s ID: [' . $delRecID . '] is used in: `' . $protectionChkTab->getTableName() . '` table'),
                           'warning'));
                }
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

        $inputs.= wf_Link(self::URL_ME . '&' . self::URL_EXTCONTRAS . '=true', wf_img_sized('skins/ukv/dollar.png') . ' ' . __('External counterparties list'), false, 'ubButton');
        $inputs.= wf_Link(self::URL_ME . '&' . self::URL_INVOICES . '=true', wf_img_sized('skins/menuicons/receipt_small.png') . ' ' . __('Invoices list'), false, 'ubButton');

        // dictionaries forms
        $dictControls = wf_Link(self::URL_ME . '&' . self::URL_DICTPROFILES . '=true', wf_img_sized('skins/extcontrasprofiles.png') . ' ' . __('Counterparties profiles dictionary'), false, 'ubButton');
        $dictControls.= wf_Link(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true', wf_img_sized('skins/corporate_small.png') . ' ' . __('Contracts dictionary'), false, 'ubButton');
        $dictControls.= wf_Link(self::URL_ME . '&' . self::URL_DICTADDRESS . '=true', wf_img_sized('skins/extcontrasaddr.png') . ' ' . __('Address dictionary'), false, 'ubButton');
        $dictControls.= wf_Link(self::URL_ME . '&' . self::URL_DICTPERIODS . '=true', wf_img_sized('skins/clock.png') . ' ' . __('Periods dictionary'), false, 'ubButton');
        $inputs.= wf_modalAuto(web_icon_extended() . ' ' . __('Dictionaries'), __('Dictionaries'), $dictControls, 'ubButton');
        $inputs.= wf_jsAjaxDynamicWindowButton(self::URL_ME, array(self::ROUTE_FORCECACHE_UPD => 'true'), wf_img('skins/refresh.gif') . ' ' . __('Refresh cache data'), '', 'ubButton');

        return ($inputs);
    }

    /**
     * Returns dropdown selector control
     *
     * @param $selectorData
     * @param $dbFiledName
     * @param $ctrlName
     * @param $ctrlLabel
     * @param string $selected
     * @param bool $br
     * @param bool $sort
     * @param string $ctrlID
     * @param string $ctrlClass
     * @param string $options
     * @param bool   $labelLeftSide
     * @param string $labelOpts
     *
     * @return string
     */
    public function renderWebSelector($selectorData, $dbFiledName, $ctrlName, $ctrlLabel, $selected = '',
                                      $br = false, $sort = false, $ctrlID = '', $ctrlClass = '',
                                      $options = '', $labelLeftSide = false, $labelOpts = '') {
        $tmpArray = array();

        if (!empty($selectorData)) {
            foreach ($selectorData as $eachID => $eachRec) {
                $tmpValue = '';

                if (is_array($dbFiledName)) {
                    foreach ($dbFiledName as $eachdbFieldName) {
                        $tmpValue.= $eachRec[$eachdbFieldName] . ' ';
                    }
                } else {
                    $tmpValue = $eachRec[$dbFiledName];
                }

                $tmpArray[$eachID] = trim($tmpValue);
            }
        }

        return (wf_Selector($ctrlName, $tmpArray, $ctrlLabel, $selected, $br, $sort, $ctrlID, $ctrlClass, $options, $labelLeftSide, $labelOpts));
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
    protected function recordCreateEdit($dbEntity, $dataArray, $recordID = 0) {
        $dbEntity->dataArr($dataArray);

        if (!empty($recordID)) {
            $dbEntity->where(self::DBFLD_COMMON_ID, '=', $recordID);
            $dbEntity->save(true, true);

            log_register(get_class($this) . ': EDITED record ID: ' . $recordID . ' in table `' . $dbEntity->getTableName() . '`');
        } else {
            $dbEntity->create();

            log_register(get_class($this) . ': ADDED new record to `' . $dbEntity->getTableName() . '`');
        }

        $this->loadDataFromTableCached($dbEntity->getTableName(true), $dbEntity->getTableName(true), true);
    }

    protected function recordDelete($dbEntity, $recordID) {
        $dbEntity->where(self::DBFLD_COMMON_ID, '=', $recordID);
        $dbEntity->delete();
        $this->loadDataFromTableCached($dbEntity->getTableName(true), $dbEntity->getTableName(true), true);

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
     * @param string $customJSCode
     * @param string $markRowForID
     *
     * @return string
     */
    public function profileRenderJQDT($customJSCode = '', $markRowForID = '') {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_PROFILE_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('Profile name');
        $columns[] = __('EDRPO');
        $columns[] = __('Contact');
        $columns[] = __('E-mail');
        $columns[] = __('Actions');

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, '', true, $customJSCode, $markRowForID,
                                      self::URL_ME . '&' . self::URL_DICTPROFILES . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID,
                                    self::MISC_MARKROW_URL);

        return($result);
    }

    /**
     * Renders JSON for profile's dictionary JQDT
     */
    public function profileRenderListJSON() {
        $this->loadDataFromTableCached(self::TABLE_ECPROFILES, self::TABLE_ECPROFILES);
        $json = new wf_JqDtHelper();

        if (!empty($this->allECProfiles)) {
            $data = array();

            foreach ($this->allECProfiles as $eachRecID) {
                foreach ($eachRecID as $fieldName => $fieldVal) {
                    $data[] = $fieldVal;
                }

                $actions = $this->getStdJQDTActions($eachRecID['id'], self::ROUTE_PROFILE_ACTS, true);
                $data[]  = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }


    /**
     * Returns a contract-editor web form
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
        $ctrctContract        = '';
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

        if (($editAction or $cloneAction) and !empty($this->allECContracts[$contractID])) {
            $contract           = $this->allECContracts[$contractID];
            $ctrctDTStart       = $contract[self::DBFLD_CTRCT_DTSTART];
            $ctrctDTEnd         = $contract[self::DBFLD_CTRCT_DTEND];
            $ctrctContract      = $contract[self::DBFLD_CTRCT_CONTRACT];
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
        $inputs.= wf_nbsp(2) . __('Date start') . $this->supFrmFldMark;
        $inputs.= wf_tag('span', true) . wf_nbsp(4);

        $inputs.= wf_DatePickerPreset(self::CTRL_CTRCT_DTEND, $ctrctDTEnd, true, '', $emptyCheckClass);
        $inputs.= wf_tag('span', false, '', $ctrlsLblStyle);
        $inputs.= wf_nbsp(2) . __('Date end') . $this->supFrmFldMark;
        $inputs.= wf_tag('span', true) . wf_nbsp(4);

        $inputs.= wf_CheckInput(self::CTRL_CTRCT_AUTOPRLNG, __('Autoprolong'), true, $ctrctAutoProlong, '', '');
        $inputs.= wf_TextInput(self::CTRL_CTRCT_CONTRACT, __('Contract number') . $this->supFrmFldMark, $ctrctContract, false, '', '',
                               $emptyCheckClass, '', '', false, $ctrlsLblStyle);
        $inputs.= wf_nbsp(4);
        $inputs.= wf_TextInput(self::CTRL_CTRCT_FULLSUM, __('Contract full sum'), $ctrctFullSum, true, '4', 'finance',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_CTRCT_SUBJECT, __('Contract subject'), $ctrctSubject, true, '70', '',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_CTRCT_NOTES, __('Contract notes'), $ctrctNotes, true, '70', '',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_delimiter(0);

        $inputs.= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', 'style="width: 100%"');
        $inputs.= wf_HiddenInput(self::ROUTE_CONTRACT_ACTS, 'true');

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

        if ($editAction and $this->fileStorageEnabled) {
            $this->fileStorage->setItemid(self::URL_DICTCONTRACTS . $contractID);

            $inputs.= wf_tag('span', false, '', $ctrlsLblStyle);
            $inputs.= wf_tag('h3');
            $inputs.= __('Uploaded files');
            $inputs.= wf_tag('h3', true);
            $inputs.= $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32',
                                                             '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true'));
            $inputs.= wf_tag('span', true);
        }

        if ($modal and !empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for contracts dictionary
     *
     * @param string $customJSCode
     * @param string $markRowForID
     *
     * @return string
     */
    public function contractRenderJQDT($customJSCode = '', $markRowForID = '') {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_CONTRACT_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('Contract');
        $columns[] = __('Date start');
        $columns[] = __('Date end');
        $columns[] = __('Contract subject');
        $columns[] = __('Full sum');
        $columns[] = __('Autoprolong');
        $columns[] = __('Notes');
        $columns[] = __('Uploaded files');
        $columns[] = __('Actions');

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, '', true, $customJSCode,
                                    self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID,
                                      self::MISC_MARKROW_URL);

        return($result);
    }

    /**
     * Renders JSON for contract's dictionary JQDT
     */
    public function contractRenderListJSON() {
        $this->loadDataFromTableCached(self::TABLE_ECCONTRACTS, self::TABLE_ECCONTRACTS);
        $json = new wf_JqDtHelper();

        if (!empty($this->allECContracts)) {
            $data = array();

            foreach ($this->allECContracts as $eachRecID) {
                foreach ($eachRecID as $fieldName => $fieldVal) {
                    if ($fieldName == self::DBFLD_CTRCT_AUTOPRLNG) {
                        $data[] = (empty($fieldVal) ? web_red_led() : web_green_led());
                    } else {
                        $data[] = $fieldVal;
                    }
                }

                $this->fileStorage->setItemid(self::URL_DICTCONTRACTS . $eachRecID['id']);
                $data[] = $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32',
                                                                '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true'));

                $actions = $this->getStdJQDTActions($eachRecID['id'], self::ROUTE_CONTRACT_ACTS, true);
                $data[]  = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns a address-editor web form
     *
     * @param bool $modal
     * @param int $addressID
     * @param bool $editAction
     * @param bool $cloneAction
     *
     * @return string
     */
    public function addressWebForm($modal = true, $addressID = 0, $editAction = false, $cloneAction = false) {
        $inputs     = '';
        $addrAddress    = '';
        $addrSum = '';
        $addrCtrctNotes   = '';
        $addrNotes   = '';
        $modalWinID     = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and !empty($this->allECAddresses[$addressID])) {
            $address        = $this->allECAddresses[$addressID];
            $addrAddress    = $address[self::DBFLD_ADDRESS_ADDR];
            $addrSum        = $address[self::DBFLD_ADDRESS_SUM];
            $addrCtrctNotes = $address[self::DBFLD_ADDRESS_CTNOTES];
            $addrNotes      = $address[self::DBFLD_ADDRESS_NOTES];
        }

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt   = ($editAction) ? __('Edit counterparty address') :
                      (($cloneAction) ? __('Clone counterparty address') :
                      __('Create counterparty address'));

        $ctrlsLblStyle = 'style="line-height: 2.2em"';

        $inputs.= wf_TextInput(self::CTRL_ADDRESS_ADDR, __('Address') . $this->supFrmFldMark, $addrAddress, true, '', '',
                               $emptyCheckClass, '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_ADDRESS_SUM, __('Sum'), $addrSum, true, '', '',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_ADDRESS_CTNOTES, __('Contract notes'), $addrCtrctNotes, true, '', '',
                               $emptyCheckClass, '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_ADDRESS_NOTES, __('Notes'), $addrNotes, true, '', '',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_delimiter(0);
        $inputs.= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', 'style="width: 100%"');
        $inputs.= wf_HiddenInput(self::ROUTE_ADDRESS_ACTS, 'true');

        if ($editAction) {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs.= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $addressID);
        } else {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and !empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_DICTADDRESS . '=true','POST',
                          $inputs, 'glamour ' . $formClass);

        if ($modal and !empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for address dictionary
     *
     * @param string $customJSCode
     * @param string $markRowForID
     *
     * @return string
     */
    public function addressRenderJQDT($customJSCode = '', $markRowForID = '') {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_ADDRESS_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('Address');
        $columns[] = __('Contract sum');
        $columns[] = __('Contract notes');
        $columns[] = __('Address notes');
        $columns[] = __('Actions');

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, '', true, $customJSCode,
                                    self::URL_ME . '&' . self::URL_DICTADDRESS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID,
                                      self::MISC_MARKROW_URL);

        return($result);
    }

    /**
     * Renders JSON for address's dictionary JQDT
     */
    public function addressRenderListJSON() {
        $this->loadDataFromTableCached(self::TABLE_ECADDRESS, self::TABLE_ECADDRESS);
        $json = new wf_JqDtHelper();

        if (!empty($this->allECAddresses)) {
            $data = array();

            foreach ($this->allECAddresses as $eachRecID) {
                foreach ($eachRecID as $fieldName => $fieldVal) {
                    $data[] = $fieldVal;
                }

                $actions = $this->getStdJQDTActions($eachRecID['id'], self::ROUTE_ADDRESS_ACTS, true);
                $data[]  = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
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

        $ctrlsLblStyle = 'style="line-height: 3.4em"';

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
     * @param string $customJSCode
     * @param string $markRowForID
     *
     * @return string
     */
    public function periodRenderJQDT($customJSCode = '', $markRowForID = '') {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_PERIOD_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('Period name');
        $columns[] = __('Actions');

        $result = $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, '', true, $customJSCode,
                                              self::URL_ME . '&' . self::URL_DICTPERIODS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID,
                                                self::MISC_MARKROW_URL);

        return($result);
    }

    /**
     * Renders JSON for period's dictionary JQDT
     */
    public function periodRenderListJSON() {
        $this->loadDataFromTableCached(self::TABLE_ECPERIODS, self::TABLE_ECPERIODS);
        $json = new wf_JqDtHelper();

        if (!empty($this->allECPeriods)) {
            $data = array();

            foreach ($this->allECPeriods as $eachRecID) {
                foreach ($eachRecID as $fieldName => $fieldVal) {
                    $data[] = $fieldVal;
                }

                $actions = $this->getStdJQDTActions($eachRecID['id'], self::ROUTE_PERIOD_ACTS);
                $data[]  = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns a filter web form for invoices main form
     *
     * @return string
     */
    public function invoiceFilterWebForm() {
        $ajaxURLStr = self::URL_ME . '&' . self::ROUTE_INVOICES_JSON . '=true';
        $formID     = 'Form_' . wf_InputId();
        $jqdtID     = 'jqdt_' . md5($ajaxURLStr);

        $inputs = wf_tag('h3', false);
        $inputs.= __('Filter by:');
        $inputs.= wf_tag('h3', true);
        $rows   = wf_DatesTimesRangeFilter(true, true, true, true, false,
                                           ubRouting::post(self::MISC_WEBFILTER_DATE_START), ubRouting::post(self::MISC_WEBFILTER_DATE_END),
                            self::MISC_WEBFILTER_DATE_START, self::MISC_WEBFILTER_DATE_END
                                          );

        $inputs.= wf_TableBody($rows, 'auto');
        $inputs.= wf_delimiter(0);

        $inputs.= wf_SubmitClassed(true, 'ubButton', '', __('Show'), '', 'style="width: 100%"');

        $inputs = wf_Form($ajaxURLStr,'POST', $inputs, 'glamour', '', $formID);
        $inputs = wf_Plate($inputs, '', '', 'glamour');

        $inputs.= wf_EncloseWithJSTags(wf_jsAjaxFilterFormSubmit($ajaxURLStr, $formID, $jqdtID));

        return ($inputs);
    }


    /**
     * Returns a contract-editor web form
     *
     * @param bool $modal
     * @param int $invoiceID
     * @param bool $editAction
     * @param bool $cloneAction
     *
     * @return string
     */
    public function invoiceWebForm($modal = true, $invoiceID = 0, $editAction = false, $cloneAction = false) {
        $inputs             = '';
        $invoContrasID      = 1;
        $invoInternalNum    = '';
        $invoNumber         = '';
        $invoDate           = '';
        $invoSum            = '';
        $invoSumVAT         = '';
        $invoNotes          = '';
        $invoIncoming       = '';
        $invoOutgoing       = '';
        $modalWinID     = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and !empty($this->allECInvoices[$invoiceID])) {
            $invoice            = $this->allECInvoices[$invoiceID];
            $invoContrasID      = $invoice[self::DBFLD_INVOICES_CONTRASID];
            $invoInternalNum    = $invoice[self::DBFLD_INVOICES_INTERNAL_NUM];
            $invoNumber         = $invoice[self::DBFLD_INVOICES_INVOICE_NUM];
            $invoDate           = $invoice[self::DBFLD_INVOICES_DATE];
            $invoSum            = $invoice[self::DBFLD_INVOICES_SUM];
            $invoSumVAT         = $invoice[self::DBFLD_INVOICES_SUM_VAT];
            $invoNotes          = $invoice[self::DBFLD_INVOICES_NOTES];
            $invoIncoming       = ubRouting::filters($invoice[self::DBFLD_INVOICES_INCOMING], 'fi', FILTER_VALIDATE_BOOLEAN);
            $invoOutgoing       = ubRouting::filters($invoice[self::DBFLD_INVOICES_OUTGOING], 'fi', FILTER_VALIDATE_BOOLEAN);
        }

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt   = ($editAction) ? __('Edit counterparty contract') :
            (($cloneAction) ? __('Clone counterparty contract') :
                __('Create counterparty contract'));

        $ctrlsLblStyle = 'style="line-height: 2.2em"';

        $inputs.= wf_TextInput(self::CTRL_INVOICES_INVOICE_NUM, __('Invoice number') . $this->supFrmFldMark, $invoNumber, false, '', '',
                               $emptyCheckClass, '', '', false, $ctrlsLblStyle);
        $inputs.= wf_nbsp(4);
        $inputs.= wf_TextInput(self::CTRL_INVOICES_INTERNAL_NUM, __('Invoice internal number'), $invoInternalNum, false, '', '',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_delimiter(0);

        $inputs.= wf_DatePickerPreset(self::CTRL_INVOICES_DATE, $invoDate, true, '', $emptyCheckClass);
        $inputs.= wf_tag('span', false, '', $ctrlsLblStyle);
        $inputs.= wf_nbsp(2) . __('Invoice date') . $this->supFrmFldMark;
        $inputs.= wf_tag('span', true);

        $inputs.= wf_nbsp(8);
        $inputs.= wf_TextInput(self::CTRL_INVOICES_SUM, __('Invoice sum') . $this->supFrmFldMark, $invoSum, false, '4', 'finance',
                               $emptyCheckClass, '', '', false, $ctrlsLblStyle);
        $inputs.= wf_nbsp(8);
        $inputs.= wf_TextInput(self::CTRL_INVOICES_SUM_VAT, __('Invoice VAT sum'), $invoSumVAT, true, '4', 'finance',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_delimiter(0);

        //$inputs.= $this->renderWebSelector($this->allExtContras, self::DBFLD_CON)
        $inputs.= $this->renderWebSelector($this->allExtContrasExten, array(self::TABLE_ECPROFILES . self::DBFLD_PROFILE_EDRPO,
                                                                            self::TABLE_ECPROFILES . self::DBFLD_PROFILE_NAME,
                                                                            self::TABLE_ECPROFILES . self::DBFLD_PROFILE_CONTACT
                                                                           ),
                                           self::CTRL_INVOICES_CONTRASID, __('Counterparty'), $invoContrasID,true, true);

        $inputs.= wf_delimiter(0);
        $inputs.= wf_TextInput(self::CTRL_INVOICES_NOTES, __('Invoice notes'), $invoNotes, true, '70', '',
                               '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_delimiter(0);

        $inputs.= wf_tag('span', false, 'glamour', 'style="text-align: center; width: 95%;"');
        $inputs.= wf_RadioInput(self::CTRL_INVOICES_IN_OUT, __('Incoming invoice'), 'incoming', false, $invoIncoming);
        $inputs.= wf_nbsp(8);
        $inputs.= wf_RadioInput(self::CTRL_INVOICES_IN_OUT, __('Outgoing invoice'), 'outgoing', true, $invoOutgoing);
        $inputs.= wf_tag('span', true);
        $inputs.= wf_delimiter(3);

        $inputs.= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', 'style="width: 100%"');
        $inputs.= wf_HiddenInput(self::ROUTE_INVOICES_ACTS, 'true');

        if ($editAction) {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs.= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $invoiceID);
        } else {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and !empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_INVOICES . '=true','POST',
                          $inputs, 'glamour ' . $formClass);

        if ($editAction and $this->fileStorageEnabled) {
            $this->fileStorage->setItemid(self::URL_INVOICES . $invoiceID);

            $inputs.= wf_tag('span', false, '', $ctrlsLblStyle);
            $inputs.= wf_tag('h3');
            $inputs.= __('Uploaded files');
            $inputs.= wf_tag('h3', true);
            $inputs.= $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32',
                                                             '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_INVOICES . '=true'));
            $inputs.= wf_tag('span', true);
        }

        if ($modal and !empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for invoices list
     *
     * @param string $customJSCode
     * @param string $markRowForID
     *
     * @return string
     */
    public function invoiceRenderJQDT($customJSCode = '', $markRowForID = '') {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_INVOICES_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('Counterparty');
        $columns[] = __('Internal number');
        $columns[] = __('Invoice number');
        $columns[] = __('Invoice date');
        $columns[] = __('Sum total');
        $columns[] = __('Sum VAT');
        $columns[] = __('Notes');
        $columns[] = __('Incoming');
        $columns[] = __('Outgoing');
        $columns[] = __('Uploaded files');
        $columns[] = __('Actions');

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, '', true, $customJSCode,
                                    self::URL_ME . '&' . self::URL_INVOICES . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID,
                                      self::MISC_MARKROW_URL);

        return($result);
    }

    /**
     * Renders JSON for invoices JQDT
     */
    public function invoiceRenderListJSON($whereRaw = '') {
        $this->loadDataFromTableCached(self::TABLE_ECPROFILES, self::TABLE_ECPROFILES);

        if (!empty($whereRaw)) {
            $this->dbECInvoices->whereRaw($whereRaw);
        }

        $this->dbECInvoices->setDebug(true, true);
        $this->loadDataFromTableCached(self::TABLE_ECINVOICES, self::TABLE_ECINVOICES,
                                       !empty($whereRaw), true,'', '', !empty($whereRaw));
        $json = new wf_JqDtHelper();

        if (!empty($this->allECInvoices)) {
            $data = array();

            foreach ($this->allECInvoices as $eachRecID) {
                foreach ($eachRecID as $fieldName => $fieldVal) {
                    if ($fieldName == self::DBFLD_INVOICES_CONTRASID) {
                        $data[] = (empty($this->allExtContrasExten[$fieldVal]) ? ''
                                    : $this->allExtContrasExten[$fieldVal][self::TABLE_ECPROFILES . self::DBFLD_PROFILE_EDRPO] . ' '
                                    . $this->allExtContrasExten[$fieldVal][self::TABLE_ECPROFILES . self::DBFLD_PROFILE_NAME]
                                  );
                    } elseif ($fieldName == self::DBFLD_INVOICES_INCOMING or $fieldName == self::DBFLD_INVOICES_OUTGOING) {
                        $data[] = (empty($fieldVal) ? web_red_led() : web_green_led());
                    } else {
                        $data[] = $fieldVal;
                    }
                }

                $this->fileStorage->setItemid(self::URL_INVOICES . $eachRecID['id']);
                $data[] = $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32',
                                                                 '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_INVOICES . '=true'));

                $actions = $this->getStdJQDTActions($eachRecID['id'], self::ROUTE_INVOICES_ACTS, true);
                $data[]  = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns a filter web form for extcontras main form
     *
     * @return string
     */
    public function extcontrasFilterWebForm() {
        $inputs = wf_tag('h3', false);
        $inputs.= __('Filter by:');
        $inputs.= wf_tag('h3', true);
        $rows   = wf_DatesTimesRangeFilter(true, true, true, true, false,
                                           ubRouting::post(self::MISC_WEBFILTER_DATE_START), ubRouting::post(self::MISC_WEBFILTER_DATE_END),
                            self::MISC_WEBFILTER_DATE_START, self::MISC_WEBFILTER_DATE_END
                                          );

        $cells  = wf_TableCell(__('Payday:'));
        $cells .= wf_TableCell(wf_TextInput(self::MISC_WEBFILTER_PAYDAY, '', ubRouting::post(self::MISC_WEBFILTER_PAYDAY), true, 4, 'digits'));
        $rows  .= wf_TableRow($cells);
        $inputs.= wf_TableBody($rows, 'auto');
        $inputs.= wf_delimiter(0);

        $inputs.= wf_SubmitClassed(true, 'ubButton', '', __('Show'), '', 'style="width: 100%"');
        $inputs = wf_Plate($inputs, '', '', 'glamour');

        return ($inputs);
    }

    /**
     * Returns a external counterparty editor web form
     *
     * @param bool $modal
     * @param int $extContrasID
     * @param bool $editAction
     * @param bool $cloneAction
     *
     * @return string
     */
    public function extcontrasWebForm($modal = true, $extContrasID = 0, $editAction = false, $cloneAction = false) {
        $inputs             = '';
        $contrasProfileID   = 1;
        $contrasContractID  = '';
        $contrasAddressID   = '';
        $contrasPeriodID    = '';
        $contrasPayDay      = '';
        $modalWinID     = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and !empty($this->allExtContrasExten[$extContrasID])) {
            $extContra          = $this->allExtContrasExten[$extContrasID];
            $contrasProfileID   = $extContra[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PROFILE_ID];
            $contrasContractID  = $extContra[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_CONTRACT_ID];
            $contrasAddressID   = $extContra[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_ADDRESS_ID];
            $contrasPeriodID    = $extContra[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PERIOD_ID];
            $contrasPayDay      = $extContra[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PAYDAY];
        }

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt   = ($editAction) ? __('Edit counterparty contract') :
                        (($cloneAction) ? __('Clone counterparty contract') :
                        __('Create counterparty contract'));

        $ctrlsLblStyle = 'style="line-height: 2.2em"';

        $inputs.= $this->renderWebSelector($this->allECProfiles, array(self::DBFLD_PROFILE_NAME, self::DBFLD_PROFILE_CONTACT),
                                           self::CTRL_EXTCONTRAS_PROFILE_ID, __('Counterparty profile') . $this->supFrmFldMark, $contrasProfileID,
                                           true, true, '', '', '', false, $ctrlsLblStyle);
        $inputs.= $this->renderWebSelector($this->allECContracts, array(self::DBFLD_CTRCT_CONTRACT, self::DBFLD_CTRCT_SUBJECT, self::DBFLD_CTRCT_FULLSUM),
                                           self::CTRL_EXTCONTRAS_CONTRACT_ID, __('Contract') . $this->supFrmFldMark, $contrasContractID,
                                           true, true, '', '', '', false, $ctrlsLblStyle);
        $inputs.= $this->renderWebSelector($this->allECAddresses, array(self::DBFLD_ADDRESS_ADDR, self::DBFLD_ADDRESS_SUM),
                                           self::CTRL_EXTCONTRAS_ADDRESS_ID, __('Address') . $this->supFrmFldMark, $contrasAddressID,
                                           true, true, '', '', '', false, $ctrlsLblStyle);
        $inputs.= $this->renderWebSelector($this->allECPeriods, array(self::DBFLD_PERIOD_NAME),
                                           self::CTRL_EXTCONTRAS_PERIOD_ID, __('Period') . $this->supFrmFldMark, $contrasPeriodID,
                                           true, true, '', '', '', false, $ctrlsLblStyle);
        $inputs.= wf_TextInput(self::CTRL_EXTCONTRAS_PAYDAY, __('Payday') . $this->supFrmFldMark, $contrasPayDay, true, '4', 'digits',
                               $emptyCheckClass, '', '', false, $ctrlsLblStyle);
        $inputs.= wf_delimiter(0);

        $inputs.= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', 'style="width: 100%"');
        $inputs.= wf_HiddenInput(self::ROUTE_CONTRAS_ACTS, 'true');

        if ($editAction) {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs.= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $extContrasID);
        } else {
            $inputs.= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and !empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_EXTCONTRAS . '=true','POST',
                          $inputs, 'glamour ' . $formClass);

        if ($modal and !empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for external counterparty list
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @return string
     */
    public function extcontrasRenderJQDT($customJSCode = '', $markRowForID = '') {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_CONTRAS_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('EDRPO');
        $columns[] = __('Counterparty');
        $columns[] = __('Contract');
        $columns[] = __('Contract subject');
        $columns[] = __('Contract sum');
        $columns[] = __('Address');
        $columns[] = __('Address contract notes');
        $columns[] = __('Address sum');
        $columns[] = __('Period');
        $columns[] = __('Payday');
        $columns[] = __('Actions');
        $columns[] = __('Payed this month');
        $columns[] = __('5 days till payday');
        $columns[] = __('Payment expired');

        $this->getTableGridColorOpts();

        $opts = '
            "order": [[ 0, "desc" ]],
            "columnDefs": [ {"targets": [12, 13, 14], "visible": false} ],
            
            "rowCallback": function(row, data, index) {                               
                if ( data[12] == "1" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->payedThisMonthBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->payedThisMonthFRGND . '\');
                } 
                
                if ( data[13] == "1" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->fiveDaysTillPayBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->fiveDaysTillPayFRGND . '\');
                } 
                
                if ( data[14] == "1" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->paymentExpiredBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->paymentExpiredFRGND . '\');
                } 
            }
            
            ';

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, true, $customJSCode,
                                    self::URL_ME . '&' . self::URL_EXTCONTRAS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID,
                                      self::MISC_MARKROW_URL);

        return($result);
    }

    /**
     * Renders JSON for external counterparty JQDT
     */
    public function extcontrasRenderListJSON() {
        $this->loadExtContrasExtenData(true);
        $json = new wf_JqDtHelper();

        if (!empty($this->allExtContrasExten)) {
            $data = array();

            foreach ($this->allExtContrasExten as $eachRecID) {
/*                $fldCntr = 0;

                foreach ($eachRecID as $fieldName => $fieldVal) {
                    switch ($fldCntr) {
                        case 0:

                            $fldCntr++;
                    }
                    if ($fieldName == self::DBFLD_INVOICES_CONTRASID) {
                        (empty($this->allECProfiles[$fieldVal]) ? '' : $this->allECProfiles[$fieldVal][self::DBFLD_PROFILE_NAME]);
                    } elseif ($fieldName == self::DBFLD_INVOICES_INCOMING or $fieldName == self::DBFLD_INVOICES_OUTGOING) {
                        $data[] = (empty($fieldVal) ? web_red_led() : web_green_led());
                    } else {
                        $data[] = $fieldVal;
                    }
                }
*/
                $data[] = $eachRecID[self::TABLE_EXTCONTRAS . self::DBFLD_COMMON_ID];
                $data[] = wf_Link(self::URL_ME . '&' . self::URL_DICTPROFILES . '=true'
                                  . '&' . self::MISC_MARKROW_URL . '=' . $eachRecID[self::TABLE_ECPROFILES . self::DBFLD_COMMON_ID],
                                  $eachRecID[self::TABLE_ECPROFILES . self::DBFLD_PROFILE_EDRPO]);
                $data[] = $eachRecID[self::TABLE_ECPROFILES . self::DBFLD_PROFILE_NAME];
                $data[] = $eachRecID[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_CONTRACT];
                $data[] = $eachRecID[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_DTSTART];
                $data[] = $eachRecID[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_FULLSUM];
                $data[] = $eachRecID[self::TABLE_ECADDRESS . self::DBFLD_ADDRESS_ADDR];
                $data[] = $eachRecID[self::TABLE_ECADDRESS . self::DBFLD_ADDRESS_CTNOTES];
                $data[] = $eachRecID[self::TABLE_ECADDRESS . self::DBFLD_ADDRESS_SUM];
                $data[] = $eachRecID[self::TABLE_ECPERIODS . self::DBFLD_PERIOD_NAME];
                $data[] = $eachRecID[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PAYDAY];

/*                $this->fileStorage->setItemid(self::URL_INVOICES . $eachRecID['id']);
                $data[] = $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32',
                    '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_INVOICES . '=true'));
*/
                $actions = $this->getStdJQDTActions($eachRecID[self::TABLE_EXTCONTRAS . self::DBFLD_COMMON_ID], self::ROUTE_CONTRAS_ACTS, true);
                $data[]  = $actions;

                $hasPaymentsCurMonth = $this->checkCurMonthPaymExists($eachRecID[self::TABLE_EXTCONTRAS . self::DBFLD_COMMON_ID]);

                $data[] = (empty($hasPaymentsCurMonth) ? 0 : 1);
                $data[] = ($eachRecID[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PAYDAY] - date('j') <= 5 and empty($hasPaymentsCurMonth)) ? 1 : 0;
                $data[] = (date('j') > $eachRecID[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PAYDAY] and empty($hasPaymentsCurMonth)) ? 1 : 0;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Renders counterparties table coloring settings form
     *
     * @return string
     */
    public function extcontrasColorSettings() {
        $this->getTableGridColorOpts();

        $inputs = '';

        $inputs.= wf_ColPicker(self::CTRL_ECCOLOR_PAYEDTHISMONTH_BKGND, __('Already payed this month background'), $this->payedThisMonthBKGND, true, '7');
        $inputs.= wf_ColPicker(self::CTRL_ECCOLOR_PAYEDTHISMONTH_FRGND, __('Already payed this month foreground'), $this->payedThisMonthFRGND, true, '7');
        $inputs.= wf_ColPicker(self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_BKGND, __('5 days left till payday background'), $this->fiveDaysTillPayBKGND, true, '7');
        $inputs.= wf_ColPicker(self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_FRGND, __('5 days left till payday background'), $this->fiveDaysTillPayFRGND, true, '7');
        $inputs.= wf_ColPicker(self::CTRL_ECCOLOR_PAYMENTEXPIRED_BKGND, __('Payment expired background'), $this->paymentExpiredBKGND, true, '7');
        $inputs.= wf_ColPicker(self::CTRL_ECCOLOR_PAYMENTEXPIRED_FRGND, __('Payment expired foreground'), $this->paymentExpiredFRGND, true, '7');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_HiddenInput(self::URL_EXTCONTRAS_COLORS, 'true');
        $inputs.= wf_SubmitClassed(true, 'ubButton', '', __('Save'), '', 'style="width: 100%"');

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_EXTCONTRAS_COLORS . '=true','POST',
                            $inputs, 'glamour');

        return ($inputs);
    }
}