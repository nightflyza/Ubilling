<?php

/**
 * - Вы делоете платежов?
 * - Нет, просто показываю.
 * - Кросивое...
 */
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
     * Database abstraction layer with for `money` table + data JOINed from `extcontras` table
     *
     * @var object
     */
    protected $dbECMoneyExten = null;

    /**
     * Database abstraction layer with for `extcontras_invoices` table
     *
     * @var object
     */
    protected $dbECInvoices = null;

    /**
     * Database abstraction layer with for `extcontras_missed_payms` table
     *
     * @var object
     */
    protected $dbECMissedPayms = null;

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
     * Placeholder for $dbECInvoices DB table field structure
     *
     * @var array
     */
    protected $dbECInvoicesStruct = array();

    /**
     * Placeholder for $dbECMissedPayms DB table field structure
     *
     * @var array
     */
    protected $dbECMissedPaymsStruct = array();

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
     * Contains all extcontras money records from DB as ececmoneyid => ecmoneydata + data JOINed from `extcontras` table
     *
     * @var array
     */
    protected $allECMoneyExten = array();

    /**
     * Contains all extcontras invoices records from DB ecinvoiceid => ecinvoicedata
     *
     * @var array
     */
    protected $allECInvoices = array();

    /**
     * Contains all extcontras overdue payments records from DB ecmisspaymid => ecmisspaymdata
     *
     * @var array
     */
    protected $allECMissedPayms = array();

    /**
     * Contains selector control filtering array for a contracts dropdown selector
     *
     * @var array
     */
    protected $selectfiltECContractsAll = array();

    /**
     * Contains selector control filtering array for an address dropdown selector
     *
     * @var array
     */
    protected $selectfiltECAddressAll = array();

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
     *  Placeholder for EXTCONTRAS_INVOICE_ON alter.ini option
     *
     * @var int
     */
    protected $ecInvoicesON = 1;

    /**
     *  Placeholder for EXTCONTRAS_OVERDUE_CONTRACT_NO_ADDR alter.ini option
     *
     * @var int
     */
    protected $ecFullCtrctOverdueNoAddrOnly = 1;

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
     * Contains HTML attribute to disable from's submit buttons on read only access
     *
     * @var string
     */
    protected $submitBtnDisabled = '';

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
     * Placeholder for TEMPLATE_CURRENCY alter.ini option
     *
     * @var string
     */
    public $currencyStr = '';

    /**
     * Routes, static defines, etc
     */
//    const SAY_MY_NAME = 'CONTRAS';

    const URL_ME = '?module=extcontras';
    const URL_EXTCONTRAS = 'extcontraslist';
    const URL_EXTCONTRAS_COLORS = 'extcontrascolors';
    const URL_DICTPROFILES = 'dictprofiles';
    const URL_DICTCONTRACTS = 'dictcontracts';
    const URL_DICTADDRESS = 'dictaddress';
    const URL_DICTPERIODS = 'dictperiods';
    const URL_FINOPERATIONS = 'finoperations';
    const URL_INVOICES = 'invoices';
    const URL_MISSEDPAYMENTS = 'misspayms';
    const TABLE_EXTCONTRAS = 'extcontras';
    const TABLE_EXTCONTRASEXTEN = 'extcontrasexten';
    const TABLE_ECPROFILES = 'extcontras_profiles';
    const TABLE_ECCONTRACTS = 'extcontras_contracts';
    const TABLE_ECADDRESS = 'extcontras_address';
    const TABLE_ECPERIODS = 'extcontras_periods';
    const TABLE_ECMONEY = 'extcontras_money';
    const TABLE_ECMONEYEXTEN = 'extcontras_moneyexten';
    const TABLE_ECINVOICES = 'extcontras_invoices';
    const TABLE_ECMISSPAYMENTS = 'extcontras_missed_payms';
    const DBFLD_COMMON_ID = 'id';
    const CTRL_PROFILE_NAME = 'profname';
    const CTRL_PROFILE_EDRPO = 'profedrpo';
    const CTRL_PROFILE_CONTACT = 'profcontact';
    const CTRL_PROFILE_MAIL = 'profmail';
    const DBFLD_PROFILE_NAME = 'name';
    const DBFLD_PROFILE_EDRPO = 'edrpo';
    const DBFLD_PROFILE_CONTACT = 'contact';
    const DBFLD_PROFILE_MAIL = 'email';
    const CTRL_CTRCT_CONTRACT = 'ctrctcontract';
    const CTRL_CTRCT_DTSTART = 'ctrctdtstart';
    const CTRL_CTRCT_DTEND = 'ctrctdtend';
    const CTRL_CTRCT_SUBJECT = 'ctrctsubject';
    const CTRL_CTRCT_AUTOPRLNG = 'ctrctautoprolong';
    const CTRL_CTRCT_FULLSUM = 'ctrctfullsum';
    const CTRL_CTRCT_NOTES = 'ctrctnotes';
    const DBFLD_CTRCT_CONTRACT = 'contract';
    const DBFLD_CTRCT_DTSTART = 'date_start';
    const DBFLD_CTRCT_DTEND = 'date_end';
    const DBFLD_CTRCT_SUBJECT = 'subject';
    const DBFLD_CTRCT_AUTOPRLNG = 'autoprolong';
    const DBFLD_CTRCT_FULLSUM = 'full_sum';
    const DBFLD_CTRCT_NOTES = 'notes';
    const CTRL_ADDRESS_ADDR = 'addraddress';
    const CTRL_ADDRESS_SUM = 'addrsumm';
    const CTRL_ADDRESS_CTNOTES = 'addrctrctnotes';
    const CTRL_ADDRESS_NOTES = 'addrnotes';
    const DBFLD_ADDRESS_ADDR = 'address';
    const DBFLD_ADDRESS_SUM = 'summ';
    const DBFLD_ADDRESS_CTNOTES = 'contract_notes';
    const DBFLD_ADDRESS_NOTES = 'notes';
    const CTRL_PERIOD_NAME = 'prdname';
    const DBFLD_PERIOD_NAME = 'period_name';
    const CTRL_MONEY_PROFILEID = 'moneyprofileid';
    const CTRL_MONEY_CNTRCTID = 'moneycontractid';
    const CTRL_MONEY_ADDRESSID = 'moneyaddressid';
    const CTRL_MONEY_ACCRUALID = 'moneyaccrualid';
    const CTRL_MONEY_INVOICEID = 'moneyinvoiceid';
    const CTRL_MONEY_PURPOSE = 'moneypurpose';
    const CTRL_MONEY_SUMACCRUAL = 'moneysummaccrual';
    const CTRL_MONEY_SUMPAYMENT = 'moneysummpayment';
    const CTRL_MONEY_INOUT = 'moneyinout';
    const CTRL_MONEY_PAYNOTES = 'moneypaynotes';
    const DBFLD_MONEY_PROFILEID = 'profile_id';
    const DBFLD_MONEY_CNTRCTID = 'contract_id';
    const DBFLD_MONEY_ADDRESSID = 'address_id';
    const DBFLD_MONEY_ACCRUALID = 'accrual_id';
    const DBFLD_MONEY_INVOICEID = 'invoice_id';
    const DBFLD_MONEY_PURPOSE = 'purpose';
    const DBFLD_MONEY_DATE = 'date';
    const DBFLD_MONEY_DATE_EDIT = 'date_edit';
    const DBFLD_MONEY_SMACCRUAL = 'summ_accrual';
    const DBFLD_MONEY_SMPAYMENT = 'summ_payment';
    const DBFLD_MONEY_DATE_PAYMENT = 'date_payment';
    const DBFLD_MONEY_INCOMING = 'incoming';
    const DBFLD_MONEY_OUTGOING = 'outgoing';
    const DBFLD_MONEY_PAYNOTES = 'paynotes';
    const CTRL_INVOICES_CONTRASID = 'invocontrasrecid';
    const CTRL_INVOICES_INTERNAL_NUM = 'invointernalnum';
    const CTRL_INVOICES_INVOICE_NUM = 'invoicenum';
    const CTRL_INVOICES_DATE = 'invodate';
    const CTRL_INVOICES_SUM = 'invosumm';
    const CTRL_INVOICES_SUM_VAT = 'invosummvat';
    const CTRL_INVOICES_NOTES = 'invonotes';
    const CTRL_INVOICES_IN_OUT = 'invoinout';
    const DBFLD_INVOICES_CONTRASID = 'contras_rec_id';
    const DBFLD_INVOICES_INTERNAL_NUM = 'internal_number';
    const DBFLD_INVOICES_INVOICE_NUM = 'invoice_number';
    const DBFLD_INVOICES_DATE = 'date';
    const DBFLD_INVOICES_SUM = 'summ';
    const DBFLD_INVOICES_SUM_VAT = 'summ_vat';
    const DBFLD_INVOICES_NOTES = 'notes';
    const DBFLD_INVOICES_INCOMING = 'incoming';
    const DBFLD_INVOICES_OUTGOING = 'outgoing';
    const CTRL_EXTCONTRAS_PROFILE_ID = 'extcontraprofileid';
    const CTRL_EXTCONTRAS_CONTRACT_ID = 'extcontracontractid';
    const CTRL_EXTCONTRAS_ADDRESS_ID = 'extcontraaddressid';
    const CTRL_EXTCONTRAS_PERIOD_ID = 'extcontraperiodid';
    const CTRL_EXTCONTRAS_PAYDAY = 'extcontrapayday';
    const DBFLD_EXTCONTRAS_PROFILE_ID = 'contras_id';
    const DBFLD_EXTCONTRAS_CONTRACT_ID = 'contract_id';
    const DBFLD_EXTCONTRAS_ADDRESS_ID = 'address_id';
    const DBFLD_EXTCONTRAS_PERIOD_ID = 'period_id';
    const DBFLD_EXTCONTRAS_MISSPAYM_ID = 'missed_paym_id';
    const DBFLD_EXTCONTRAS_PAYDAY = 'payday';
    const DBFLD_EXTCONTRAS_DATECREATE = 'date_create';
    const CTRL_ECCOLOR_PAYEDTHISMONTH_BKGND = 'EC_PAYEDTHISMONTH_BKGND';
    const CTRL_ECCOLOR_PAYEDTHISMONTH_FRGND = 'EC_PAYEDTHISMONTH_FRGND';
    const CTRL_ECCOLOR_FIVEDAYSTILLPAY_BKGND = 'EC_FIVEDAYSTILLPAY_BKGND';
    const CTRL_ECCOLOR_FIVEDAYSTILLPAY_FRGND = 'EC_FIVEDAYSTILLPAY_FRGND';
    const CTRL_ECCOLOR_PAYMENTEXPIRED_BKGND = 'EC_PAYMENTEXPIRED_BKGND';
    const CTRL_ECCOLOR_PAYMENTEXPIRED_FRGND = 'EC_PAYMENTEXPIRED_FRGND';
    const DBFLD_MISSPAYMS_CONTRASID = 'contras_rec_id';
    const DBFLD_MISSPAYMS_PROFILEID = 'profile_id';
    const DBFLD_MISSPAYMS_CONTRACTID = 'contract_id';
    const DBFLD_MISSPAYMS_ADDRESSID = 'address_id';
    const DBFLD_MISSPAYMS_PERIOD_ID = 'period_id';
    const DBFLD_MISSPAYMS_PAYDAY = 'payday';
    const DBFLD_MISSPAYMS_DATE_PAYMENT = 'date_payment';
    const DBFLD_MISSPAYMS_DATE_EXPIRED = 'date_expired';
    const DBFLD_MISSPAYMS_DATE_PAYED = 'date_payed';
    const DBFLD_MISSPAYMS_SUMPAYMENT = 'summ_payment';
    const ROUTE_ACTION_CREATE = 'doCreate';
    const ROUTE_ACTION_PREFILL = 'doPrefill';
    const ROUTE_ACTION_EDIT = 'doEdit';
    const ROUTE_ACTION_CLONE = 'doClone';
    const ROUTE_ACTION_DELETE = 'doRemove';
    const ROUTE_EDIT_REC_ID = 'editRecID';
    const ROUTE_DELETE_REC_ID = 'deleteRecID';
    const ROUTE_CONTRAS_ACTS = 'contrasacts';
    const ROUTE_CONTRAS_JSON = 'contraslistjson';
    const ROUTE_PROFILE_ACTS = 'profileacts';
    const ROUTE_PROFILE_JSON = 'profilelistjson';
    const ROUTE_CONTRACT_ACTS = 'contractacts';
    const ROUTE_CONTRACT_JSON = 'contractlistjson';
    const ROUTE_ADDRESS_ACTS = 'addressacts';
    const ROUTE_ADDRESS_JSON = 'addresslistjson';
    const ROUTE_PERIOD_ACTS = 'periodacts';
    const ROUTE_PERIOD_JSON = 'periodlistjson';
    const ROUTE_FINOPS_ACTS = 'finopsacts';
    const ROUTE_FINOPS_JSON = 'finopslistjson';
    const ROUTE_FINOPS_DETAILS_CNTRCTS = 'finopsdetailscontracts';
    const ROUTE_FINOPS_DETAILS_ADDRESS = 'finopsdetailsaddress';
    const ROUTE_INVOICES_ACTS = 'invoicesacts';
    const ROUTE_INVOICES_JSON = 'invoiceslistjson';
    const ROUTE_MISSPAYMS_ACTS = 'misspaymslistjson';
    const ROUTE_MISSPAYMS_JSON = 'misspaymslistjson';
    const ROUTE_FORCECACHE_UPD = 'extcontrasforcecacheupdate';
    const ROUTE_2LVL_CNTRCTS_DETAIL = 'contras2lvlcntrctsdetails';
    const ROUTE_2LVL_CNTRCTS_JSON = 'contras2lvlcntrctsjson';
    const ROUTE_3LVL_ADDR_JSON = 'contras3lvladdrsjson';
    const MISC_FILESTORAGE_SCOPE = 'EXCONTRAS';
    const MISC_CLASS_MWID_CTRL = '__FormModalWindowID';
    const MISC_CLASS_SUBMITFORM = '__FormSubmit';
    const MISC_CLASS_SUBMITFORM_MODAL = '__FormSubmitModal';
    const MISC_CLASS_EMPTYVALCHECK = '__EmptyCheckControl';
    const MISC_CLASS_EMPTYVALCHECK_MODAL = '__EmptyCheckControlModal';
    const MISC_CLASS_DPICKER_MODAL_INIT = '__DatePickerModalInit';
    const MISC_JS_DEL_FUNC_NAME = 'deleteRec';
    const MISC_ERRFORM_ID_PARAM = 'errfrmid';
    const MISC_MARKROW_URL = 'markrowid';
    const MISC_WEBFILTER_DATE_START = 'datefilterstart';
    const MISC_WEBFILTER_DATE_END = 'datefilterend';
    const MISC_WEBFILTER_PAYDAY = 'paydayfilter';
    const MISC_WEBFILTER_MISSPAYMS = 'misspaysfilter';
    const MISC_PREFILL_DATA = 'prefilldata';
    const MISC_WEBSEL_PROFILES = 'WebSelECProfiles_';
    const MISC_WEBSEL_CONTRACTS = 'WebSelECContracts_';
    const MISC_WEBSEL_ADDRESS = 'WebSelECAddress_';
    const MISC_WEBSEL_FILTDATA_CONTRACTS = 'WebSelContractFilterData_';
    const MISC_WEBSEL_FILTDATA_ADDRESS = 'WebSelAddressFilterData_';
    const MISC_WEBSEL_DBVAL_PROFILE_ID = 'ModalDBValProfile_';
    const MISC_WEBSEL_DBVAL_CONTRACTS_ID = 'ModalDBValContract_';
    const MISC_WEBSEL_DBVAL_ADDRESS_ID = 'ModalDBValAddress_';
    const MISC_MISSED_PAYMENT_PROCESSING = 'misspaymprocessing';
    const MISC_MISSED_PAYMENT_ID = 'missedpaymentid';
    const MISC_FORMS_CAPTS_PROFILE_DICT = 'counterparty profile';
    const MISC_FORMS_CAPTS_CNTRCTS_DICT = 'counterparty contract';
    const MISC_FORMS_CAPTS_ADDRESS_DICT = 'contract address';
    const MISC_FORMS_CAPTS_PERIODS_DICT = 'period';
    const MISC_FORMS_CAPTS_INVOICES_LIST = 'invoice';
    const MISC_FORMS_CAPTS_FINOPS_LIST = 'financial operation';
    const MISC_FORMS_CAPTS_EXTCONTRAS = 'counterparty record';

    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
        $this->ubCache = new UbillingCache();
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
        $this->currencyStr = $this->ubConfig->getAlterParam('TEMPLATE_CURRENCY', 'грн');
        $this->fileStorageEnabled = $this->ubConfig->getAlterParam('FILESTORAGE_ENABLED');
        $this->cacheLifeTime = $this->ubConfig->getAlterParam('EXTCONTRAS_CACHE_LIFETIME', 1800);
        $this->ecInvoicesON = $this->ubConfig->getAlterParam('EXTCONTRAS_INVOICE_ON', 1);
        $this->ecFullCtrctOverdueNoAddrOnly = $this->ubConfig->getAlterParam('EXTCONTRAS_OVERDUE_CONTRACT_NO_ADDR', 1);
        $this->ecEditablePreiod = $this->ubConfig->getAlterParam('EXTCONTRAS_EDIT_ALLOWED_DAYS');
        $this->ecEditablePreiod = empty($this->ecEditablePreiod) ? (60 * 86400) : ($this->ecEditablePreiod * 86400); // Option is in days
        $this->ecReadOnlyAccess = (!cfr('EXTCONTRASRW'));
        $this->submitBtnDisabled = ($this->ecReadOnlyAccess ? 'disabled="true"' : '');
    }

    /**
     * Inits DB NyanORM objects
     */
    protected function initDBEntities() {
        $this->dbExtContras = new NyanORM(self::TABLE_EXTCONTRAS);
        $this->dbEntitiesAll[self::TABLE_EXTCONTRAS] = $this->dbExtContras;
        $this->dataEntitiesAll[self::TABLE_EXTCONTRAS] = 'allExtContras';

        $this->dbExtContrasExten = new NyanORM(self::TABLE_EXTCONTRAS);
        $this->dbEntitiesAll[self::TABLE_EXTCONTRASEXTEN] = $this->dbExtContrasExten;
        $this->dataEntitiesAll[self::TABLE_EXTCONTRASEXTEN] = 'allExtContrasExten';

        $this->dbECProfiles = new NyanORM(self::TABLE_ECPROFILES);
        $this->dbEntitiesAll[self::TABLE_ECPROFILES] = $this->dbECProfiles;
        $this->dataEntitiesAll[self::TABLE_ECPROFILES] = 'allECProfiles';

        $this->dbECContracts = new NyanORM(self::TABLE_ECCONTRACTS);
        $this->dbEntitiesAll[self::TABLE_ECCONTRACTS] = $this->dbECContracts;
        $this->dataEntitiesAll[self::TABLE_ECCONTRACTS] = 'allECContracts';

        $this->dbECAddress = new NyanORM(self::TABLE_ECADDRESS);
        $this->dbEntitiesAll[self::TABLE_ECADDRESS] = $this->dbECAddress;
        $this->dataEntitiesAll[self::TABLE_ECADDRESS] = 'allECAddresses';

        $this->dbECPeriods = new NyanORM(self::TABLE_ECPERIODS);
        $this->dbEntitiesAll[self::TABLE_ECPERIODS] = $this->dbECPeriods;
        $this->dataEntitiesAll[self::TABLE_ECPERIODS] = 'allECPeriods';

        $this->dbECMoney = new NyanORM(self::TABLE_ECMONEY);
        $this->dbEntitiesAll[self::TABLE_ECMONEY] = $this->dbECMoney;
        $this->dataEntitiesAll[self::TABLE_ECMONEY] = 'allECMoney';

        $this->dbECMoneyExten = new NyanORM(self::TABLE_ECMONEY);
        $this->dbEntitiesAll[self::TABLE_ECMONEYEXTEN] = $this->dbECMoneyExten;
        $this->dataEntitiesAll[self::TABLE_ECMONEYEXTEN] = 'allECMoneyExten';

        $this->dbECInvoices = new NyanORM(self::TABLE_ECINVOICES);
        $this->dbEntitiesAll[self::TABLE_ECINVOICES] = $this->dbECInvoices;
        $this->dataEntitiesAll[self::TABLE_ECINVOICES] = 'allECInvoices';

        $this->dbECMissedPayms = new NyanORM(self::TABLE_ECMISSPAYMENTS);
        $this->dbEntitiesAll[self::TABLE_ECMISSPAYMENTS] = $this->dbECMissedPayms;
        $this->dataEntitiesAll[self::TABLE_ECMISSPAYMENTS] = 'allECMissedPayms';
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
        $this->dbExtContrasStruct = $this->dbExtContras->getTableStructure(true, false, true, true);
        $this->dbECProfilesStruct = $this->dbECProfiles->getTableStructure(true, false, true, true);
        $this->dbECContractsStruct = $this->dbECContracts->getTableStructure(true, false, true, true);
        $this->dbECAddressStruct = $this->dbECAddress->getTableStructure(true, false, true, true);
        $this->dbECPeriodsStruct = $this->dbECPeriods->getTableStructure(true, false, true, true);
        $this->dbECMoneyStruct = $this->dbECMoney->getTableStructure(true, false, true, true);
        $this->dbECInvoicesStruct = $this->dbECInvoices->getTableStructure(true, false, true, true);
        $this->dbECMissedPaymsStruct = $this->dbECMissedPayms->getTableStructure(true, false, true, true);
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
     * @param bool $distinctSelectON
     *
     * @return mixed
     */
    public function loadDataFromTableCached($tableName, $cacheKey, $forceDBLoad = false, $flushNyanParams = true, $assocByField = '', $dataEntity = '', $cachingDisabled = false, $distinctSelectON = false) {

        $cacheKey = strtoupper($cacheKey);
        $dbInstance = $this->getDBEntity($tableName);
        $flushParams = $flushNyanParams;
        $assocByField = (empty($assocByField) ? 'id' : $assocByField);
        $dataInstance = (empty($dataEntity) ? $this->getDataEntity($tableName) : $dataEntity);
        $thisInstance = $this;

        if ($forceDBLoad) {
            $this->$dataInstance = $dbInstance->getAll($assocByField, $flushParams, $distinctSelectON);

            if ($cachingDisabled) {
                $this->ubCache->delete($cacheKey);
            } else {
                $this->ubCache->set($cacheKey, $this->$dataInstance, $this->cacheLifeTime);
            }
        } else {
            $this->$dataInstance = $this->ubCache->getCallback($cacheKey, function () use ($thisInstance, $tableName, $cacheKey, $flushParams, $assocByField,
                    $dataInstance, $cachingDisabled, $distinctSelectON) {
                return ($thisInstance->loadDataFromTableCached($tableName, $cacheKey, true, $flushParams, $assocByField, $dataInstance, $cachingDisabled, $distinctSelectON));
            }, $this->cacheLifeTime);
        }

        return ($this->$dataInstance);
    }

    /**
     * Loads extended external counterparties data
     *
     * @param bool   $forceDBLoad
     * @param string $whereRaw
     * @param string $orderBy
     * @param string $orderDir
     * @param bool   $distinctSelectON
     */
    protected function loadExtContrasExtenData($forceDBLoad = false, $whereRaw = '', $orderBy = '', $orderDir = 'ASC', $distinctSelectON = false) {
        $selectable = array_merge($this->dbExtContrasStruct, $this->dbECProfilesStruct, $this->dbECContractsStruct, $this->dbECAddressStruct, $this->dbECPeriodsStruct);

        if (!$forceDBLoad) {
            $forceDBLoad = (empty($whereRaw) and empty($orderBy) and empty($distinctSelectON));
        }

        $this->dbExtContrasExten->selectable($selectable);
        $this->dbExtContrasExten->joinOn();
        $this->dbExtContrasExten->joinOn('LEFT', self::TABLE_ECPROFILES, self::TABLE_EXTCONTRAS . '.' . self::DBFLD_EXTCONTRAS_PROFILE_ID
                . ' = ' . self::TABLE_ECPROFILES . '.' . self::DBFLD_COMMON_ID);
        $this->dbExtContrasExten->joinOn('LEFT', self::TABLE_ECCONTRACTS, self::TABLE_EXTCONTRAS . '.' . self::DBFLD_EXTCONTRAS_CONTRACT_ID
                . ' = ' . self::TABLE_ECCONTRACTS . '.' . self::DBFLD_COMMON_ID);
        $this->dbExtContrasExten->joinOn('LEFT', self::TABLE_ECADDRESS, self::TABLE_EXTCONTRAS . '.' . self::DBFLD_EXTCONTRAS_ADDRESS_ID
                . ' = ' . self::TABLE_ECADDRESS . '.' . self::DBFLD_COMMON_ID);
        $this->dbExtContrasExten->joinOn('LEFT', self::TABLE_ECPERIODS, self::TABLE_EXTCONTRAS . '.' . self::DBFLD_EXTCONTRAS_PERIOD_ID
                . ' = ' . self::TABLE_ECPERIODS . '.' . self::DBFLD_COMMON_ID);

        if (!empty($whereRaw)) {
            $this->dbExtContrasExten->whereRaw($whereRaw);
        }

        if (!empty($orderBy)) {
            $this->dbExtContrasExten->orderBy($orderBy, $orderDir);
        }

//$this->dbExtContrasExten->setDebug(true, true);
        $this->loadDataFromTableCached(self::TABLE_EXTCONTRASEXTEN, self::TABLE_EXTCONTRASEXTEN, $forceDBLoad, false, self::TABLE_EXTCONTRAS . self::DBFLD_COMMON_ID, '', !empty($whereRaw), $distinctSelectON);
    }

    /**
     * Loads extended external finops data
     *
     * @param bool $forceDBLoad
     * @param string $whereRaw
     * @param string $orderBy
     * @param string $orderDir
     * @param bool $distinctSelectON
     *
     */
    public function loadFinopsExtenData($forceDBLoad = false, $whereRaw = '', $orderBy = '', $orderDir = 'ASC', $distinctSelectON = false) {
        $selectable = array_merge($this->dbECMoneyStruct, $this->dbExtContrasStruct);
        if (!$forceDBLoad) {
            $forceDBLoad = (empty($whereRaw) and empty($orderBy) and empty($distinctSelectON));
        }

        $this->dbECMoneyExten->selectable($selectable);
        $this->dbECMoneyExten->joinOn();
        $this->dbECMoneyExten->joinOn('INNER', self::TABLE_EXTCONTRAS, self::TABLE_ECMONEY . '.' . self::DBFLD_MONEY_PROFILEID
                . ' = ' . self::TABLE_EXTCONTRAS . '.' . self::DBFLD_EXTCONTRAS_PROFILE_ID);

        if (!empty($whereRaw)) {
            $this->dbECMoneyExten->whereRaw($whereRaw);
        }

        if (!empty($orderBy)) {
            $this->dbExtContrasExten->orderBy($orderBy, $orderDir);
        }

//$this->dbECMoneyExten->setDebug(true, true);
        $this->loadDataFromTableCached(self::TABLE_ECMONEYEXTEN, self::TABLE_ECMONEYEXTEN, $forceDBLoad, false, self::TABLE_ECMONEY . self::DBFLD_COMMON_ID, '', !empty($whereRaw), $distinctSelectON);
    }

    /**
     * Retrieves data for contracts web selector control filtering
     */
    protected function loadWebSelFilterData() {
        $this->loadExtContrasExtenData(false, '', self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PROFILE_ID);

        foreach ($this->allExtContrasExten as $eachID => $eachRec) {
            $tmpProfileID = $eachRec[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PROFILE_ID];
            $tmpContractID = $eachRec[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_CONTRACT_ID];
            $tmpAddressID = $eachRec[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_ADDRESS_ID];

            $this->selectfiltECContractsAll[$tmpProfileID][] = array($tmpContractID => $eachRec[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_CONTRACT] . ' ' .
                $eachRec[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_SUBJECT] . ' ' .
                $eachRec[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_FULLSUM]);

            $this->selectfiltECAddressAll[$tmpContractID][] = array($tmpAddressID => $eachRec[self::TABLE_ECADDRESS . self::DBFLD_ADDRESS_ADDR] . ' ' .
                $eachRec[self::TABLE_ECADDRESS . self::DBFLD_ADDRESS_SUM]);
        }
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
        $this->loadDataFromTableCached(self::TABLE_ECMISSPAYMENTS, self::TABLE_ECMISSPAYMENTS, $forceDBLoad);
        $this->loadExtContrasExtenData($forceDBLoad);
        $this->loadFinopsExtenData($forceDBLoad);
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

    /**
     * Searches for any occurrences of current month payments for a certain counterparty ID
     *
     * @param $ecRecID
     * @param bool $contractCheck
     * @param bool $addressCheck
     * @param bool $checkSum
     *
     * @return string
     */
    public function checkCurMonthPaymExists($ecRecID, $contractCheck = false, $addressCheck = false, $checkSum = false) {
        $result = '';

        if (!empty($ecRecID) and !empty($this->allExtContras[$ecRecID][self::DBFLD_EXTCONTRAS_PAYDAY])) {
            $tmpECPayDay = $this->allExtContras[$ecRecID][self::DBFLD_EXTCONTRAS_PAYDAY];
            $tmpECProfileID = $this->allExtContras[$ecRecID][self::DBFLD_EXTCONTRAS_PROFILE_ID];
            $tmpECContractID = $this->allExtContras[$ecRecID][self::DBFLD_EXTCONTRAS_CONTRACT_ID];
            $tmpECAddressID = $this->allExtContras[$ecRecID][self::DBFLD_EXTCONTRAS_ADDRESS_ID];
            $curMonthStart = date('Y-m-') . '01';
            $curMonthEnd = date('Y-m-') . date('t');
            $fullPaymentSum = 0;

            // getting full payment sum for a current contract
            if (!empty($this->allECContracts[$tmpECContractID][self::DBFLD_CTRCT_FULLSUM])) {
                $fullPaymentSum = $this->allECContracts[$tmpECContractID][self::DBFLD_CTRCT_FULLSUM];
            }

            // setting initial mandatory filtering by contragent profile ID
            $this->dbECMoney->selectable(self::DBFLD_COMMON_ID);
            $this->dbECMoney->where(self::DBFLD_MONEY_PROFILEID, '=', $tmpECProfileID);

            // adding filtering by contract ID if it's "contract check"
            if ($contractCheck) {
                $this->dbECMoney->where(self::DBFLD_MONEY_CNTRCTID, '=', $tmpECContractID);
            }

            // if it's address check, and there is a payment sum for current address,
            // and we didn't get full payment sum for a current contract on a very first step
            // then we're trying to get a current address payment sum
            if ($addressCheck and empty($fullPaymentSum) and !empty($this->allECAddresses[$tmpECAddressID][self::DBFLD_ADDRESS_SUM])) {
                $fullPaymentSum = $this->allECAddresses[$tmpECAddressID][self::DBFLD_ADDRESS_SUM];
            }

            // adding filtering by payment sum "!= 0" to be sure that the financial operation was ever paid
            $this->dbECMoney->where(self::DBFLD_MONEY_SMPAYMENT, '!=', 0);

            if (!empty($fullPaymentSum)) {
                // if we've got full payment sum for a current contract(or address) ...
                if ($addressCheck) {
                    // ... and it's "address check"
                    // - then we search either for a financial operation with current address ID
                    //   or for a financial operation with current payment sum
                    // - because address check in this case(when we have full payment sum for a current contract) - is just in case
                    $this->dbECMoney->whereRaw(' (`' . self::DBFLD_MONEY_ADDRESSID . '` = ' . $tmpECAddressID
                            . ' OR `' . self::DBFLD_MONEY_SMPAYMENT . '` = ' . $fullPaymentSum . ') ');
                } else {
                    // ... and it's "contract check" - then we search for a financial operation with current or grater payment sum
                    $this->dbECMoney->where(self::DBFLD_MONEY_SMPAYMENT, '>=', $fullPaymentSum);
                }
            } elseif ($addressCheck) {
                // this means that our contract doesn't have payment sum filled
                // - and we search for a financial operation with current address ID
                //
                // this line will never be executed, if all the fields in contract and address dictionaries are filled correctly
                $this->dbECMoney->where(self::DBFLD_MONEY_ADDRESSID, '=', $tmpECAddressID);
            }

            // finally - adding filter by date to range it in current month
            $this->dbECMoney->whereRaw(' `' . self::DBFLD_MONEY_DATE . '` BETWEEN "' . $curMonthStart . '" AND "' . $curMonthEnd . '" + INTERVAL 1 DAY ');
//$this->dbECMoney->setDebug(true, true);
            // getting the ID(s) of the supposed existing payment(s) - or nothing
            $result = $this->dbECMoney->getAll(self::DBFLD_COMMON_ID);
        }

        // if "check sum" - trying to sum up all of the existing payments
        // to get the total paid sum
        // totally used for "address check" to determine if total sum all payments for a certain addresses of the contract
        // are "covering" - i.e. are equal or grater than - the full contract sum
        if ($checkSum and !empty($result)) {
            $tmpPayedSum = 0;

            foreach ($result as $paymID => $paymData) {
                $tmpPayedSum += $paymData[self::DBFLD_MONEY_SMPAYMENT];
            }

            $result = $tmpPayedSum;
        }

        return ($result);
    }


    /**
     * Returns array of addresses assigned to a certain combination of $profileID + $contractID
     *
     * @param $profileID
     * @param $contractID
     *
     * @return mixed
     */
    public function checkContractHasAddresses($profileID, $contractID) {
//$this->dbExtContras->setDebug(true, true);
        $this->dbExtContras->selectable(array(self::DBFLD_COMMON_ID, self::DBFLD_EXTCONTRAS_ADDRESS_ID));
        $this->dbExtContras->where(self::DBFLD_EXTCONTRAS_PROFILE_ID, '=', $profileID);
        $this->dbExtContras->where(self::DBFLD_EXTCONTRAS_CONTRACT_ID, '=', $contractID);
        $result = $this->dbExtContras->getAll(self::DBFLD_COMMON_ID);

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
     * @param bool $addTotalsFooter
     * @param array $totalsColumns
     * @param string $totalsColsLegendStr
     * @param bool $addDetailsProcessingJS
     * @param string $dpAjaxURL
     * @param string $dpColumnIdx
     * @param string $dpJSFuncName
     * @param string $dpAjaxMethod
     *
     * @param string|int $markRowForID
     *
     * @return string
     */
    protected function getStdJQDTWithJSForCRUDs($ajaxURL, $columnsArr, $columnsOpts = '', $stdJSForCRUDs = true,
                                                $customJSCode = '', $markRowForID = '', $truncateURL = '', $truncateParam = '',
                                                $addTotalsFooter = false, $totalsColumns = array(), $totalsColsLegendStr = '',
                                                $addDetailsProcessingJS = false, $dpAjaxURL = '', $dpColumnIdx = '',
                                                $dpJSFuncName = 'showDetailsData', $dpAjaxMethod = 'POST') {
        $result = '';
        $ajaxURLStr = $ajaxURL;
        $jqdtID = 'jqdt_' . md5($ajaxURLStr);
        $columns = $columnsArr;
        $opts = (empty($columnsOpts) ? '"order": [[ 0, "asc" ]]' : $columnsOpts);
        $footerOpts = 'style="line-height: 2.2em; white-space: nowrap;"';
        $footTHOpts = 'style="padding: 0 5px 0 5px; border-top: 2px solid lightgrey;"';

        if (!empty($markRowForID)) {
            $result .= wf_EncloseWithJSTags(wf_JQDTRowShowPluginJS());
        }

        if ($addTotalsFooter and ! empty($totalsColumns)) {
            $result .= wf_EncloseWithJSTags(wf_JQDTColumnTotalSumJS());

            $opts .= '
                ,
                "footerCallback": function(tfoot, data, start, end, display) {
                    var api = this.api();
                    var footerHTML = "";
                    let curPageSum = 0;
                    let curPageTotal = 0;
            ';

            foreach ($totalsColumns as $colNum) {
                $opts .= '
                    curPageSum = api.column( ' . $colNum . ', {page:"current"} ).data().sum().toFixed(2);
                    curPageTotal = api.column( ' . $colNum . ' ).data().sum().toFixed(2);
                    
                    footerHTML = " " + curPageSum + " ' . __($totalsColsLegendStr) . wf_delimiter(0)
                                 . ' ( " + curPageTotal + " ' . __($totalsColsLegendStr) . ' )";
                    
                    $( api.column(' . $colNum . ').footer() ).html( footerHTML );                    
                ';
            }

            $opts .= '
                }

            ';
        }

        $result .= wf_JqDtLoader($columns, $ajaxURLStr, false, __('results'), 100, $opts, $addTotalsFooter, $footerOpts, $footTHOpts);

        if ($stdJSForCRUDs) {
            $result .= wf_tag('script', false, '', 'type="text/javascript"');

            // putting a "form submitting catcher" JS code to process multiple modal and static forms
            // with one piece of code and ajax requests
            $result .= wf_jsAjaxFormSubmit('.' . self::MISC_CLASS_SUBMITFORM . ', .' . self::MISC_CLASS_SUBMITFORM_MODAL, '.' . self::MISC_CLASS_MWID_CTRL, $jqdtID, '.' . self::MISC_CLASS_EMPTYVALCHECK . ', .' . self::MISC_CLASS_EMPTYVALCHECK_MODAL, self::MISC_ERRFORM_ID_PARAM);

            // putting a piece of JS code to perform records delete action
            $result .= wf_jsAjaxCustomFunc(self::MISC_JS_DEL_FUNC_NAME, $jqdtID, '', self::MISC_ERRFORM_ID_PARAM);

            if (!empty($markRowForID)) {
                $result .= wf_JQDTMarkRowJS(0, $markRowForID, $truncateURL, $truncateParam);
            }

            $result .= wf_tag('script', true);
        }

        if ($addDetailsProcessingJS and ! empty($dpAjaxURL) and ! wf_emptyNonZero($dpColumnIdx)) {
            $result .= wf_EncloseWithJSTags(wf_JQDTDetailsClickProcessingJS($dpAjaxURL, $dpColumnIdx, $jqdtID, $dpAjaxMethod, $dpJSFuncName));
        }

        if (!empty($customJSCode)) {
            $result .= wf_EncloseWithJSTags($customJSCode);
        }

        return ($result);
    }

    /**
     * Returns typical JQDT "actions" controls, like "Delete", "Edit", "Clone"
     *
     * @param int    $recID
     * @param string $routeActs
     * @param bool   $cloneButtonON
     * @param string $customControls
     * @param bool   $editButton
     * @param bool   $deleteButton
     *
     * @return string
     */
    protected function getStdJQDTActions($recID, $routeActs, $cloneButtonON = false, $customControls = '', $editButton = true, $deleteButton = true) {
        $curTimeStamp = strtotime(curdate());

        $actions = '';

        if ($deleteButton and ! $this->ecReadOnlyAccess) {
            // gathering the delete ajax data query
            $tmpDeleteQuery = '\'&' . $routeActs . '=true' .
                    '&' . self::ROUTE_ACTION_DELETE . '=true' .
                    '&' . self::ROUTE_DELETE_REC_ID . '=' . $recID . '\'';

            $deleteDialogWID = 'dialog-modal_' . wf_inputid();
            $deleteDialogCloseFunc = ' $(\'#' . $deleteDialogWID . '\').dialog(\'close\') ';

            $actions = wf_ConfirmDialogJS('#', web_delete_icon(), $this->messages->getDeleteAlert(), '', '#', self::MISC_JS_DEL_FUNC_NAME . '(\'' . self::URL_ME . '\',' . $tmpDeleteQuery . ');' . $deleteDialogCloseFunc, $deleteDialogCloseFunc, $deleteDialogWID);
        }

        if ($editButton and ! $this->ecReadOnlyAccess) {
            $actions .= wf_nbsp(2);
            $actions .= wf_jsAjaxDynamicWindowButton(self::URL_ME, array($routeActs => 'true',
                self::ROUTE_ACTION_EDIT => 'true',
                self::ROUTE_EDIT_REC_ID => $recID
                    ), '', web_edit_icon()
            );
        }

        if ($cloneButtonON and ! $this->ecReadOnlyAccess) {
            $actions .= wf_nbsp(2);
            $actions .= wf_jsAjaxDynamicWindowButton(self::URL_ME, array($routeActs => 'true',
                self::ROUTE_ACTION_CLONE => 'true',
                self::ROUTE_EDIT_REC_ID => $recID), '', web_clone_icon()
            );
        }

        $actions .= $customControls;

        return ($actions);
    }

    /**
     * Simply returns JS snippet for datepicker init on dynamic modal forms
     *
     * @return string
     */
    protected function getDatePickerModalInitJS() {
        $result = '                                
            onElementInserted("body", ".' . self::MISC_CLASS_DPICKER_MODAL_INIT . '", function(element) {
                $(".' . self::MISC_CLASS_DPICKER_MODAL_INIT . '").datepicker({
                    showOn: "both",
                    buttonImage: "skins/icon_calendar.gif",
                    buttonImageOnly: true,
                                dateFormat:  "yy-mm-dd",
                                showAnim: "slideDown",
                                changeMonth: true,
                                yearRange: "-100:+100",
                                changeYear: true
                });
            });
                
            ';

        return($result);
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
     * @param array     $prefillFieldsData
     * @param bool      $createFormModality
     * @param bool      $editFormModality
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function processCRUDs($dataArray, $dbTabName, $postFrmCtrlValToChk = '', $webFormMethod = '', $checkUniqueness = true, $checkUniqArray = array(), $crudEntityName = '', $prefillFieldsData = array(), $createFormModality = false, $editFormModality = true) {

        $recID = ubRouting::post(self::ROUTE_EDIT_REC_ID);
        $recEdit = ubRouting::checkPost(self::ROUTE_ACTION_EDIT, false);
        $recClone = ubRouting::checkPost(self::ROUTE_ACTION_CLONE, false);
        $recCreate = ubRouting::checkPost(self::ROUTE_ACTION_CREATE);
        $recDelete = ubRouting::checkPost(self::ROUTE_ACTION_DELETE);
        $dbEntity = $this->getDBEntity($dbTabName);
        $foundRecID = '';
        $crudEntityName = empty($crudEntityName) ? 'Entity' : $crudEntityName;
        $entityExistError = '';
        $wfmExistError = '';

        if (empty($dbEntity)) {
            $entityExistError .= wf_nbsp(2) . wf_tag('b') . $dbTabName . wf_tag('b', true);
        }

        if (!method_exists($this, $webFormMethod)) {
            $wfmExistError .= $this->renderWebMsg(__('Error'), __('CRUDs processing: try to call to non-existent method') . ': '
                    . wf_nbsp(2) . wf_tag('b') . $webFormMethod . wf_tag('b', true), 'error');
        }
// todo: check uniqueness of extrcontras recs by profile_id + contract_id + addr_id
        // checking record uniqueness upon criteria, if needed
        if ($checkUniqueness and ! $recDelete) {
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
                    return ($this->renderWebMsg(__('Error'), __($crudEntityName) . ' ' . __('with such fields criteria') . ' '
                                    . print_r($checkUniqArray, true) . ' '
                                    . __('already exists with ID') . ': ' . $foundRecID, 'error'));
                }
            }
        }

        if (!empty($entityExistError)) {
            return($this->renderWebMsg(__('Error'), __('CRUDs processing: possible try to call to/use of non-existent method, data- or DB-entity') . ': '
                            . $entityExistError, 'error'));
        }

        if (!empty($recID) and ( $recEdit or $recClone)) {
            if (ubRouting::checkPost($postFrmCtrlValToChk)) {
                if ($recEdit) {
                    $this->recordCreateEdit($dbEntity, $dataArray, $recID);
                } elseif ($recClone) {
                    $this->recordCreateEdit($dbEntity, $dataArray);
                }
            } else {
                if (empty($wfmExistError)) {
                    return (call_user_func_array(array($this, $webFormMethod), array($editFormModality, $recID, $recEdit, $recClone)));
                } else {
                    return($wfmExistError);
                }
            }
        } elseif ($recCreate) {
            $this->recordCreateEdit($dbEntity, $dataArray);
        } elseif ($recDelete) {
            if (ubRouting::checkPost(self::ROUTE_DELETE_REC_ID)) {
                $delRecID = ubRouting::post(self::ROUTE_DELETE_REC_ID);
                $tmpUniqArray = array();
                $delRecProtected = false;
                $protectionChkArr = array();
                $protectionChkTab = '';
                $protectionChkFld = '';

                if (ubRouting::checkPost(self::ROUTE_PROFILE_ACTS)) {
                    $protectionChkFld = self::DBFLD_EXTCONTRAS_PROFILE_ID;
                    $protectionChkTab = self::TABLE_EXTCONTRAS;
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

                    $protectionChkFld = self::DBFLD_MONEY_PROFILEID;
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
                    return($this->renderWebMsg(__('Warning'), __('CRUDs processing: can\'t delete record because it\'s ID: [' . $delRecID . '] is used in: `' . $protectionChkTab->getTableName() . '` table'), 'warning'));
                }
            }
        } else {
            if (empty($wfmExistError)) {
                return(call_user_func_array(array($this, $webFormMethod), array($createFormModality, 0, false, false, $prefillFieldsData)));
            } else {
                return($wfmExistError);
            }
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

        $inputs .= wf_Link(self::URL_ME . '&' . self::URL_EXTCONTRAS . '=true', wf_img_sized('skins/extcontrasfin.png', '', '16', '16') . ' ' . __('External counterparties list'), false, 'ubButton');
        $inputs .= wf_Link(self::URL_ME . '&' . self::URL_FINOPERATIONS . '=true', wf_img_sized('skins/ukv/dollar.png') . ' ' . __('Finance operations'), false, 'ubButton');
        $inputs .= wf_Link(self::URL_ME . '&' . self::URL_MISSEDPAYMENTS . '=true', wf_img_sized('skins/dollar_red.png', '', '10', '17') . ' ' . __('Overdue payments'), false, 'ubButton');
        $inputs .= ($this->ecInvoicesON ? wf_Link(self::URL_ME . '&' . self::URL_INVOICES . '=true', wf_img_sized('skins/menuicons/receipt_small.png') . ' ' . __('Invoices list'), false, 'ubButton') : '');

        // dictionaries forms
        $dictControls = wf_Link(self::URL_ME . '&' . self::URL_DICTPROFILES . '=true', wf_img_sized('skins/extcontrasprofiles.png') . ' ' . __('Counterparties profiles dictionary'), false, 'ubButton');
        $dictControls .= wf_Link(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true', wf_img_sized('skins/corporate_small.png') . ' ' . __('Contracts dictionary'), false, 'ubButton');
        $dictControls .= wf_Link(self::URL_ME . '&' . self::URL_DICTADDRESS . '=true', wf_img_sized('skins/extcontrasaddr.png') . ' ' . __('Address dictionary'), false, 'ubButton');
        $dictControls .= wf_Link(self::URL_ME . '&' . self::URL_DICTPERIODS . '=true', wf_img_sized('skins/clock.png') . ' ' . __('Periods dictionary'), false, 'ubButton');
        $inputs .= wf_modalAuto(web_icon_extended() . ' ' . __('Dictionaries'), __('Dictionaries'), $dictControls, 'ubButton');
        $inputs .= wf_jsAjaxDynamicWindowButton(self::URL_ME, array(self::ROUTE_FORCECACHE_UPD => 'true'), wf_img('skins/refresh.gif') . ' ' . __('Refresh cache data'), '', 'ubButton');

        // JS helper functions for forms, modals, etc
        $inputs .= wf_EncloseWithJSTags(wf_JSEmptyFunc() . wf_JSElemInsertedCatcherFunc() . $this->getDatePickerModalInitJS());

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
     * @param bool $blankFirstRow
     * @param bool $br
     * @param bool $sort
     * @param string $ctrlID
     * @param string $ctrlClass
     * @param string $options
     * @param bool $labelLeftSide
     * @param string $labelOpts
     * @param array $filterData
     * @param string $filterDataElemID
     * @param string $blankFirstRowVal
     * @param string $blankFirstRowDispVal
     *
     * @return string
     */
    public function renderWebSelector($selectorData, $dbFiledName, $ctrlName, $ctrlLabel, $selected = '', $blankFirstRow = false, $br = false, $sort = false, $ctrlID = '', $ctrlClass = '', $options = '', $labelLeftSide = false, $labelOpts = '', $filterData = array(), $filterDataElemID = '', $blankFirstRowVal = '0', $blankFirstRowDispVal = '----') {

        $result = '';
        $ctrlID = (empty($ctrlID) ? wf_InputId() : $ctrlID);
        $tmpArray = ($blankFirstRow ? array($blankFirstRowVal => $blankFirstRowDispVal) : array());

        if (!empty($selectorData)) {
            foreach ($selectorData as $eachID => $eachRec) {
                $tmpValue = '';

                if (is_array($dbFiledName)) {
                    foreach ($dbFiledName as $eachdbFieldName) {
                        $tmpValue .= $eachRec[$eachdbFieldName] . ' ';
                    }
                } else {
                    $tmpValue = $eachRec[$dbFiledName];
                }

                $tmpArray[$eachID] = trim($tmpValue);
            }
        }

        $result = wf_Selector($ctrlName, $tmpArray, $ctrlLabel, $selected, $br, $sort, $ctrlID, $ctrlClass, $options, $labelLeftSide, $labelOpts);

        if (!empty($filterData)) {
            $filterDataElemID = (empty($filterDataElemID) ? 'selector_filter_' . wf_InputId() : $filterDataElemID);
            $result .= wf_HiddenInput($filterDataElemID, base64_encode(json_encode($filterData)), $filterDataElemID);
        }

        return ($result);
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
//$dbEntity->setDebug(true, true);

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
     * @param array $prefillFieldsData
     *
     * @return string
     */
    public function profileWebForm($modal = true, $profileID = 0, $editAction = false, $cloneAction = false, $prefillFieldsData = array()) {
        $inputs = '';
        $prfName = '';
        $prfContact = '';
        $prfEDRPO = '';
        $prfEmail = '';
        $modalWinID = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and ! empty($this->allECProfiles[$profileID])) {
            $profile = $this->allECProfiles[$profileID];
            $prfName = $profile[self::DBFLD_PROFILE_NAME];
            $prfContact = $profile[self::DBFLD_PROFILE_CONTACT];
            $prfEDRPO = $profile[self::DBFLD_PROFILE_EDRPO];
            $prfEmail = $profile[self::DBFLD_PROFILE_MAIL];
        }

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt = $submitCapt . ' ' . __(self::MISC_FORMS_CAPTS_PROFILE_DICT);

        $inputs .= wf_TextInput(self::CTRL_PROFILE_NAME, __('Name') . $this->supFrmFldMark, $prfName, false, '', '', $emptyCheckClass, '', '', true);
        $inputs .= wf_TextInput(self::CTRL_PROFILE_CONTACT, __('Contact data'), $prfContact, false, '', '', '', '', '', true);
        $inputs .= wf_TextInput(self::CTRL_PROFILE_EDRPO, __('EDRPO/INN') . $this->supFrmFldMark, $prfEDRPO, false, '', '', $emptyCheckClass, '', '', true);
        $inputs .= wf_TextInput(self::CTRL_PROFILE_MAIL, __('E-mail'), $prfEmail, false, '', '', '', '', '', true);
        $inputs .= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', $this->submitBtnDisabled);
        $inputs .= wf_HiddenInput(self::ROUTE_PROFILE_ACTS, 'true');

        if ($editAction) {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs .= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $profileID);
        } else {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and ! empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_DICTPROFILES . '=true', 'POST', $inputs, 'glamour form-grid-2cols form-grid-2cols-label-right ' . $formClass);

        if ($modal and ! empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for profiles dictionary
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @param string $detailsFilter
     * @param bool $stdJSForCRUDs
     *
     * @return string
     */
    public function profileRenderJQDT($customJSCode = '', $markRowForID = '', $detailsFilter = '', $stdJSForCRUDs = true) {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_PROFILE_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('Profile name');
        $columns[] = __('EDRPO');
        $columns[] = __('Contact');
        $columns[] = __('E-mail');
        $columns[] = __('Actions');

        $opts = '
            "order": [[ 0, "desc" ]],
            "columnDefs": [ {"targets": [0, 2, 5], "className": "dt-center dt-head-center"},
                            {"targets": [5], "orderable": false},
                            {"targets": [5], "width": "85px"}
                          ]
            ';

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, $stdJSForCRUDs, $customJSCode, $markRowForID, self::URL_ME . '&' . self::URL_DICTPROFILES . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID, self::MISC_MARKROW_URL);

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
                $data[] = $actions;

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
     * @param array $prefillFieldsData
     *
     * @return string
     */
    public function contractWebForm($modal = true, $contractID = 0, $editAction = false, $cloneAction = false, $prefillFieldsData = array()) {
        $inputs = '';
        $ctrctDTStart = '';
        $ctrctDTEnd = '';
        $ctrctContract = '';
        $ctrctSubject = '';
        $ctrctAutoProlong = '';
        $ctrctFullSum = '';
        $ctrctNotes = '';
        $modalWinID = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and ! empty($this->allECContracts[$contractID])) {
            $contract = $this->allECContracts[$contractID];
            $ctrctDTStart = $contract[self::DBFLD_CTRCT_DTSTART];
            $ctrctDTEnd = $contract[self::DBFLD_CTRCT_DTEND];
            $ctrctContract = $contract[self::DBFLD_CTRCT_CONTRACT];
            $ctrctSubject = $contract[self::DBFLD_CTRCT_SUBJECT];
            $ctrctAutoProlong = ubRouting::filters($contract[self::DBFLD_CTRCT_AUTOPRLNG], 'fi', FILTER_VALIDATE_BOOLEAN);
            $ctrctFullSum = $contract[self::DBFLD_CTRCT_FULLSUM];
            $ctrctNotes = $contract[self::DBFLD_CTRCT_NOTES];
        }

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt = $submitCapt . ' ' . __(self::MISC_FORMS_CAPTS_CNTRCTS_DICT);
        $datepickerID1 = wf_InputId();
        $datepickerID2 = wf_InputId();

        $ctrlsLblStyle = 'style="line-height: 2.2em"';

        $inputs .= wf_tag('label', false, '', 'for="' . $datepickerID1 . '"');
        $inputs .= __('Date start') . $this->supFrmFldMark;
        $inputs .= wf_tag('label', true);
        $inputs .= wf_tag('span', false);
        $inputs .= wf_DatePickerPreset(self::CTRL_CTRCT_DTSTART, $ctrctDTStart, true, $datepickerID1, $emptyCheckClass . ' ' . self::MISC_CLASS_DPICKER_MODAL_INIT);
        $inputs .= wf_tag('span', true);

        $inputs .= wf_tag('span', false);
        $inputs .= wf_tag('label', false, '', 'for="' . $datepickerID2 . '"');
        $inputs .= __('Date end') . $this->supFrmFldMark;
        $inputs .= wf_tag('label', true);
        $inputs .= wf_DatePickerPreset(self::CTRL_CTRCT_DTEND, $ctrctDTEnd, true, $datepickerID2, $emptyCheckClass . ' ' . self::MISC_CLASS_DPICKER_MODAL_INIT);
        $inputs .= wf_nbsp(14);
        $inputs .= wf_CheckInput(self::CTRL_CTRCT_AUTOPRLNG, __('Autoprolong'), false, $ctrctAutoProlong, '', '');
        $inputs .= wf_tag('span', true);

        $inputs .= wf_TextInput(self::CTRL_CTRCT_CONTRACT, __('Contract number') . $this->supFrmFldMark, $ctrctContract, false, '', '', $emptyCheckClass, '', '', true);

        $inputs .= wf_tag('span', false);
        $inputs .= wf_TextInput(self::CTRL_CTRCT_FULLSUM, __('Contract full sum'), $ctrctFullSum, false, '4', 'finance', '', '', '', true);
        $inputs .= wf_tag('span', true);

        $inputs .= wf_TextInput(self::CTRL_CTRCT_SUBJECT, __('Contract subject'), $ctrctSubject, false, '70', '', 'right-two-thirds-occupy', '', '', true);
        $inputs .= wf_TextInput(self::CTRL_CTRCT_NOTES, __('Contract notes'), $ctrctNotes, false, '70', '', 'right-two-thirds-occupy', '', '', true);

        $inputs .= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', $this->submitBtnDisabled);
        $inputs .= wf_HiddenInput(self::ROUTE_CONTRACT_ACTS, 'true');

        if ($editAction) {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs .= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $contractID);
        } else {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and ! empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true', 'POST', $inputs, 'glamour form-grid-3cols form-grid-3cols-label-right ' . $formClass);

        if ($editAction and $this->fileStorageEnabled) {
            $this->fileStorage->setItemid(self::URL_DICTCONTRACTS . $contractID);

            $inputs .= wf_tag('span', false, '', $ctrlsLblStyle);
            $inputs .= wf_tag('h3');
            $inputs .= __('Uploaded files');
            $inputs .= wf_tag('h3', true);
            $inputs .= $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32', '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true'), true);
            $inputs .= wf_tag('span', true);
        }

        if ($modal and ! empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for contracts dictionary
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @param string $detailsFilter
     * @param bool $stdJSForCRUDs
     *
     * @return string
     */
    public function contractRenderJQDT($customJSCode = '', $markRowForID = '', $detailsFilter = '', $stdJSForCRUDs = true) {
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

        $opts = '
            "order": [[ 1, "desc" ]],
            "columnDefs": [                         
                            {"targets": [4, 7], "className": "dt-left dt-head-center"},
                            {"targets": ["_all"], "className": "dt-center dt-head-center"},
                            {"targets": [8, 9], "orderable": false},
                            {"targets": [8, 9], "width": "85px"}
                          ]              
            ';

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, $stdJSForCRUDs, $customJSCode, $markRowForID, self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID, self::MISC_MARKROW_URL);

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

                if ($this->fileStorage) {
                    $this->fileStorage->setItemid(self::URL_DICTCONTRACTS . $eachRecID['id']);
                    $data[] = $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32', '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true'), true);
                } else {
                    $data[] = __('Filestorage') . ' - ' . __('Disabled');
                }

                $actions = $this->getStdJQDTActions($eachRecID['id'], self::ROUTE_CONTRACT_ACTS, true);
                $data[] = $actions;

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
     * @param array $prefillFieldsData
     *
     * @return string
     */
    public function addressWebForm($modal = true, $addressID = 0, $editAction = false, $cloneAction = false, $prefillFieldsData = array()) {
        $inputs = '';
        $addrAddress = '';
        $addrSum = '';
        $addrCtrctNotes = '';
        $addrNotes = '';
        $modalWinID = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and ! empty($this->allECAddresses[$addressID])) {
            $address = $this->allECAddresses[$addressID];
            $addrAddress = $address[self::DBFLD_ADDRESS_ADDR];
            $addrSum = $address[self::DBFLD_ADDRESS_SUM];
            $addrCtrctNotes = $address[self::DBFLD_ADDRESS_CTNOTES];
            $addrNotes = $address[self::DBFLD_ADDRESS_NOTES];
        }

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt = $submitCapt . ' ' . __(self::MISC_FORMS_CAPTS_ADDRESS_DICT);

        $inputs .= wf_TextInput(self::CTRL_ADDRESS_ADDR, __('Address') . $this->supFrmFldMark, $addrAddress, false, '', '', $emptyCheckClass, '', '', true);
        $inputs .= wf_TextInput(self::CTRL_ADDRESS_SUM, __('Sum'), $addrSum, false, '', '', '', '', '', true);
        $inputs .= wf_TextInput(self::CTRL_ADDRESS_CTNOTES, __('Contract notes'), $addrCtrctNotes, false, '', '', $emptyCheckClass, '', '', true);
        $inputs .= wf_TextInput(self::CTRL_ADDRESS_NOTES, __('Notes'), $addrNotes, false, '', '', '', '', '', true);
        $inputs .= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', $this->submitBtnDisabled);
        $inputs .= wf_HiddenInput(self::ROUTE_ADDRESS_ACTS, 'true');

        if ($editAction) {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs .= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $addressID);
        } else {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and ! empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_DICTADDRESS . '=true', 'POST', $inputs, 'glamour form-grid-2cols form-grid-2cols-label-right ' . $formClass);

        if ($modal and ! empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for address dictionary
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @param string $detailsFilter
     * @param bool $stdJSForCRUDs
     *
     * @return string
     */
    public function addressRenderJQDT($customJSCode = '', $markRowForID = '', $detailsFilter = '', $stdJSForCRUDs = true) {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_ADDRESS_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('Address');
        $columns[] = __('Contract sum');
        $columns[] = __('Contract notes');
        $columns[] = __('Address notes');
        $columns[] = __('Actions');

        $opts = '
            "order": [[ 0, "desc" ]],
            "columnDefs": [ {"targets": [0, 2, 5], "className": "dt-center dt-head-center"},
                            {"targets": [5], "orderable": false},
                            {"targets": [5], "width": "85px"}
                          ]
            ';

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, $stdJSForCRUDs, $customJSCode, $markRowForID, self::URL_ME . '&' . self::URL_DICTADDRESS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID, self::MISC_MARKROW_URL);

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
                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    public function periodWebForm($modal = true, $periodID = 0, $editAction = false) {
        $inputs = '';
        $prdName = '';
        $modalWinID = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if ($editAction and ! empty($this->allECPeriods[$periodID])) {
            $period = $this->allECPeriods[$periodID];
            $prdName = $period[self::DBFLD_PERIOD_NAME];
        }

        $submitCapt = ($editAction) ? __('Edit') : __('Create');
        $formCapt = $submitCapt . ' ' . __(self::MISC_FORMS_CAPTS_PERIODS_DICT);

        $ctrlsLblStyle = 'style="line-height: 3.4em; margin-right: 0.5em;"';

        $inputs .= wf_TextInput(self::CTRL_PERIOD_NAME, __('Name') . $this->supFrmFldMark, $prdName, true, '', '', $emptyCheckClass, '', '', true, $ctrlsLblStyle);

        $inputs .= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', 'style="width: 100%"; ' . $this->submitBtnDisabled);
        $inputs .= wf_HiddenInput(self::ROUTE_PERIOD_ACTS, 'true');

        if ($editAction) {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs .= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $periodID);
        } else {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and ! empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_DICTPERIODS . '=true', 'POST', $inputs, 'glamour ' . $formClass);

        if ($modal and ! empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for period dictionary
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @param string $detailsFilter
     * @param bool $stdJSForCRUDs
     *
     * @return string
     */
    public function periodRenderJQDT($customJSCode = '', $markRowForID = '', $detailsFilter = '', $stdJSForCRUDs = true) {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_PERIOD_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('Period name');
        $columns[] = __('Actions');

        $opts = '
            "order": [[ 0, "desc" ]],
            "columnDefs": [ {"targets": ["_all"], "className": "dt-center dt-head-center"},
                            {"targets": [2], "orderable": false},
                            {"targets": [2], "width": "85px"}
                          ]
            ';

        $result = $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, $stdJSForCRUDs, $customJSCode, $markRowForID, self::URL_ME . '&' . self::URL_DICTPERIODS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID, self::MISC_MARKROW_URL);

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
                $data[] = $actions;

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
        $formID = 'Form_' . wf_InputId();
        $jqdtID = 'jqdt_' . md5($ajaxURLStr);

        $inputs = wf_tag('h3', false);
        $inputs .= __('Filter by:');
        $inputs .= wf_tag('h3', true);
        $rows = wf_DatesTimesRangeFilter(true, true, false, false, true, false, ubRouting::post(self::MISC_WEBFILTER_DATE_START), ubRouting::post(self::MISC_WEBFILTER_DATE_END), self::MISC_WEBFILTER_DATE_START, self::MISC_WEBFILTER_DATE_END
        );

        $inputs .= wf_TableBody($rows, 'auto');
        $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Show'), '', 'style="width: 100%"');

        $inputs = wf_Form($ajaxURLStr, 'POST', $inputs, 'glamour form-grid-3r-1c', '', $formID, '', 'style="margin-top: 110px;"');
        $inputs .= wf_EncloseWithJSTags(wf_jsAjaxFilterFormSubmit($ajaxURLStr, $formID, $jqdtID));

        return ($inputs);
    }

    /**
     * Returns an invoice-editor web form
     *
     * @param bool $modal
     * @param int $invoiceID
     * @param bool $editAction
     * @param bool $cloneAction
     * @param array $prefillFieldsData
     *
     * @return string
     */
    public function invoiceWebForm($modal = true, $invoiceID = 0, $editAction = false, $cloneAction = false, $prefillFieldsData = array()) {
        $inputs = '';
        $invoContrasID = 0;
        $invoInternalNum = '';
        $invoNumber = '';
        $invoDate = '';
        $invoSum = '';
        $invoSumVAT = '';
        $invoNotes = '';
        $invoIncoming = '';
        $invoOutgoing = '';
        $modalWinID = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and ! empty($this->allECInvoices[$invoiceID])) {
            $invoice = $this->allECInvoices[$invoiceID];
            $invoContrasID = $invoice[self::DBFLD_INVOICES_CONTRASID];
            $invoInternalNum = $invoice[self::DBFLD_INVOICES_INTERNAL_NUM];
            $invoNumber = $invoice[self::DBFLD_INVOICES_INVOICE_NUM];
            $invoDate = $invoice[self::DBFLD_INVOICES_DATE];
            $invoSum = $invoice[self::DBFLD_INVOICES_SUM];
            $invoSumVAT = $invoice[self::DBFLD_INVOICES_SUM_VAT];
            $invoNotes = $invoice[self::DBFLD_INVOICES_NOTES];
            $invoIncoming = ubRouting::filters($invoice[self::DBFLD_INVOICES_INCOMING], 'fi', FILTER_VALIDATE_BOOLEAN);
            $invoOutgoing = ubRouting::filters($invoice[self::DBFLD_INVOICES_OUTGOING], 'fi', FILTER_VALIDATE_BOOLEAN);
        }

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt = $submitCapt . ' ' . __(self::MISC_FORMS_CAPTS_INVOICES_LIST);

        $ctrlsLblStyle = 'style="line-height: 2.2em"';
        $datepickerID = wf_InputId();

        $inputs .= wf_TextInput(self::CTRL_INVOICES_INVOICE_NUM, __('Invoice number') . $this->supFrmFldMark, $invoNumber, false, '', '', $emptyCheckClass, '', '', true);

        $inputs .= wf_tag('span', false);
        $inputs .= wf_TextInput(self::CTRL_INVOICES_INTERNAL_NUM, __('Invoice internal number'), $invoInternalNum, false, '', '', '', '', '', true);
        $inputs .= wf_tag('span', true);

        $inputs .= wf_tag('label', false, '', 'for="' . $datepickerID . '"');
        $inputs .= __('Invoice date') . $this->supFrmFldMark;
        $inputs .= wf_tag('label', true);
        $inputs .= wf_tag('span', false);
        $inputs .= wf_DatePickerPreset(self::CTRL_INVOICES_DATE, $invoDate, true, $datepickerID, $emptyCheckClass . ' ' . self::MISC_CLASS_DPICKER_MODAL_INIT);
        $inputs .= wf_tag('span', true);

        $inputs .= wf_tag('span', false);
        $inputs .= wf_TextInput(self::CTRL_INVOICES_SUM, __('Invoice sum') . $this->supFrmFldMark, $invoSum, false, '4', 'finance', $emptyCheckClass, '', '', true);
        $inputs .= wf_nbsp(6);
        $inputs .= wf_TextInput(self::CTRL_INVOICES_SUM_VAT, __('Invoice VAT sum'), $invoSumVAT, false, '4', 'finance', '', '', '', true);
        $inputs .= wf_tag('span', true);

        $inputs .= $this->renderWebSelector($this->allExtContrasExten, array(self::TABLE_ECPROFILES . self::DBFLD_PROFILE_EDRPO,
            self::TABLE_ECPROFILES . self::DBFLD_PROFILE_NAME,
            self::TABLE_ECPROFILES . self::DBFLD_PROFILE_CONTACT
                ), self::CTRL_INVOICES_CONTRASID, __('Counterparty'), $invoContrasID, true, false, true, '', 'right-two-thirds-occupy', '', true);

        $inputs .= wf_TextInput(self::CTRL_INVOICES_NOTES, __('Invoice notes'), $invoNotes, false, '70', '', 'right-two-thirds-occupy', '', '', true);

        $inputs .= wf_tag('span', false, 'glamour full-width-occupy', 'style="text-align: center; width: 97%;"');
        $inputs .= wf_RadioInput(self::CTRL_INVOICES_IN_OUT, __('Incoming invoice'), 'incoming', false, $invoIncoming);
        $inputs .= wf_nbsp(8);
        $inputs .= wf_RadioInput(self::CTRL_INVOICES_IN_OUT, __('Outgoing invoice'), 'outgoing', false, $invoOutgoing);
        $inputs .= wf_tag('span', true);

        $inputs .= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', $this->submitBtnDisabled);
        $inputs .= wf_HiddenInput(self::ROUTE_INVOICES_ACTS, 'true');

        if ($editAction) {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs .= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $invoiceID);
        } else {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and ! empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_INVOICES . '=true', 'POST', $inputs, 'glamour form-grid-3cols form-grid-3cols-label-right ' . $formClass);

        if ($editAction and $this->fileStorageEnabled) {
            $this->fileStorage->setItemid(self::URL_INVOICES . $invoiceID);

            $inputs .= wf_tag('span', false, '', $ctrlsLblStyle);
            $inputs .= wf_tag('h3');
            $inputs .= __('Uploaded files');
            $inputs .= wf_tag('h3', true);
            $inputs .= $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32', '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_INVOICES . '=true'), true);
            $inputs .= wf_tag('span', true);
        }

        if ($modal and ! empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for invoices list
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @param string $detailsFilter
     * @param bool $stdJSForCRUDs
     *
     * @return string
     */
    public function invoiceRenderJQDT($customJSCode = '', $markRowForID = '', $detailsFilter = '', $stdJSForCRUDs = true) {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_INVOICES_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('Counterparty');
        $columns[] = __('Internal number');
        $columns[] = __('Invoice number');
        $columns[] = __('Invoice date');
        $columns[] = __('Sum total');
        $columns[] = __('Sum VAT');
        $columns[] = __('Notes');
        $columns[] = __('Ingoing');
        $columns[] = __('Outgoing');
        $columns[] = __('Uploaded files');
        $columns[] = __('Actions');

        $opts = '
            "order": [[ 0, "desc" ]],
            "columnDefs": [ {"targets": [1, 7], "className": "dt-left dt-head-center"},
                            {"targets": ["_all"], "className": "dt-center dt-head-center"},
                            {"targets": [10, 11], "orderable": false},
                            {"targets": [10, 11], "width": "85px"}
                          ]
            ';

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, $stdJSForCRUDs, $customJSCode, $markRowForID, self::URL_ME . '&' . self::URL_INVOICES . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID, self::MISC_MARKROW_URL);
        return($result);
    }

    /**
     * Renders JSON for invoices JQDT
     *
     * @param string $whereRaw
     */
    public function invoiceRenderListJSON($whereRaw = '') {
        if (!empty($whereRaw)) {
            $this->dbECInvoices->whereRaw($whereRaw);
        }

        $this->loadDataFromTableCached(self::TABLE_ECINVOICES, self::TABLE_ECINVOICES, !empty($whereRaw), true, '', '', !empty($whereRaw));
        $json = new wf_JqDtHelper();

        if (!empty($this->allECInvoices)) {
            $data = array();

            foreach ($this->allECInvoices as $eachRecID) {
                foreach ($eachRecID as $fieldName => $fieldVal) {
                    if ($fieldName == self::DBFLD_INVOICES_CONTRASID) {
                        $data[] = (empty($this->allExtContrasExten[$fieldVal]) ? '' : $this->allExtContrasExten[$fieldVal][self::TABLE_ECPROFILES . self::DBFLD_PROFILE_EDRPO] . ' '
                                . $this->allExtContrasExten[$fieldVal][self::TABLE_ECPROFILES . self::DBFLD_PROFILE_NAME]
                                );
                    } elseif ($fieldName == self::DBFLD_INVOICES_INCOMING or $fieldName == self::DBFLD_INVOICES_OUTGOING) {
                        $data[] = (empty($fieldVal) ? web_red_led() : web_green_led());
                    } else {
                        $data[] = $fieldVal;
                    }
                }

                $this->fileStorage->setItemid(self::URL_INVOICES . $eachRecID['id']);
                $data[] = $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32', '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_INVOICES . '=true'), true);

                $actions = $this->getStdJQDTActions($eachRecID['id'], self::ROUTE_INVOICES_ACTS, true);
                $data[] = $actions;

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
        $ajaxURLStr = self::URL_ME . '&' . self::ROUTE_CONTRAS_JSON . '=true';
        $formID = 'Form_' . wf_InputId();
        $jqdtID = 'jqdt_' . md5($ajaxURLStr);

        $inputs = wf_tag('h3', false);
        $inputs .= __('Filter by') . ':';
        $inputs .= wf_tag('h3', true);
        $cells = wf_DatesTimesRangeFilter(true, true, false, false, true, false, ubRouting::post(self::MISC_WEBFILTER_DATE_START), ubRouting::post(self::MISC_WEBFILTER_DATE_END), self::MISC_WEBFILTER_DATE_START, self::MISC_WEBFILTER_DATE_END
        );

        $cells .= wf_TableCell(wf_nbsp(2));
        $cells .= wf_TableCell(__('Payday') . ':');
        $cells .= wf_TableCell(wf_TextInput(self::MISC_WEBFILTER_PAYDAY, '', ubRouting::post(self::MISC_WEBFILTER_PAYDAY), true, 4, 'digits'));
        $rows = wf_TableRow($cells);
        $inputs .= wf_TableBody($rows, 'auto');

        $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Show'), '', 'style="width: 100%"');

        $inputs = wf_Form($ajaxURLStr, 'POST', $inputs, 'glamour form-grid-3r-1c', '', $formID, '', 'style="margin-top: 105px;"');
        $inputs .= wf_EncloseWithJSTags(wf_jsAjaxFilterFormSubmit($ajaxURLStr, $formID, $jqdtID));

        return ($inputs);
    }

    /**
     * Returns a filter web form for extcontras main form
     *
     * @param string $ajaxURLStr
     * @param string $jqdtID
     * @param bool $includePayDayCtrl
     *
     * @return string
     */
    public function extcontrasFilterWebFormInline($ajaxURLStr, $jqdtID, $includePayDayCtrl = true) {
        $formID     = 'Form_' . wf_InputId();
        $inputs     = '';
        $style      = 'style="font-family: Helvetica, Arial, Verdana, sans-serif; font-size: 12px;"';

        $caption = wf_tag('span') . __('Filter by') . ':' . wf_tag('span', true);
        $inputs.= wf_tag('span', false, '', $style);
        $inputs.= wf_DatesTimesRangeFilter(false, false, false, false, true, false,
                                           ubRouting::post(self::MISC_WEBFILTER_DATE_START), ubRouting::post(self::MISC_WEBFILTER_DATE_END),
                                           self::MISC_WEBFILTER_DATE_START, self::MISC_WEBFILTER_DATE_END
                                          );
        $inputs.= wf_tag('span', true);

        if ($includePayDayCtrl) {
            $inputs .= wf_tag('span', false, '', $style);
            $inputs .= __('Payday') . ':' . wf_nbsp(2);
            $inputs .= wf_TextInput(self::MISC_WEBFILTER_PAYDAY, '', ubRouting::post(self::MISC_WEBFILTER_PAYDAY), false, 4, 'digits');
            $inputs .= wf_tag('span', true);
        }

        $inputs.= wf_SubmitClassed(true, 'ubButtonInline', '', __('Show'), '', '');
        $inputs = wf_Form($ajaxURLStr,'POST', $caption . $inputs, 'glamour form-grid-5cols-inline', '', $formID, '', '');
        $inputs.= wf_EncloseWithJSTags(wf_jsAjaxFilterFormSubmit($ajaxURLStr, $formID, $jqdtID));

        return ($inputs);
    }

    /**
     * Returns a external counterparty editor web form
     *
     * @param bool $modal
     * @param int $extContrasID
     * @param bool $editAction
     * @param bool $cloneAction
     * @param array $prefillFieldsData
     *
     * @return string
     */
    public function extcontrasWebForm($modal = true, $extContrasID = 0, $editAction = false, $cloneAction = false, $prefillFieldsData = array()) {
        $inputs = '';
        $contrasProfileID = 1;
        $contrasContractID = '';
        $contrasAddressID = '';
        $contrasPeriodID = '';
        $contrasPayDay = '';
        $modalWinID = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and ! empty($this->allExtContrasExten[$extContrasID])) {
            $extContra = $this->allExtContrasExten[$extContrasID];
            $contrasProfileID = $extContra[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PROFILE_ID];
            $contrasContractID = $extContra[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_CONTRACT_ID];
            $contrasAddressID = $extContra[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_ADDRESS_ID];
            $contrasPeriodID = $extContra[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PERIOD_ID];
            $contrasPayDay = $extContra[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PAYDAY];
        }

        $ecProfilesWebSelID = ($modal ? 'Modal' : '') . self::MISC_WEBSEL_PROFILES;
        $ecContractsWebSelID = ($modal ? 'Modal' : '') . self::MISC_WEBSEL_CONTRACTS;
        $ecAddressWebSelID = ($modal ? 'Modal' : '') . self::MISC_WEBSEL_ADDRESS;
        $contractsFilterDataID = ($modal ? 'Modal' : '') . self::MISC_WEBSEL_FILTDATA_CONTRACTS;
        $addressFilterDataID = ($modal ? 'Modal' : '') . self::MISC_WEBSEL_FILTDATA_ADDRESS;
        $editDBValProfileID = self::MISC_WEBSEL_DBVAL_PROFILE_ID;
        $editDBValContractID = self::MISC_WEBSEL_DBVAL_CONTRACTS_ID;
        $editDBValAddressID = self::MISC_WEBSEL_DBVAL_ADDRESS_ID;

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt = $submitCapt . ' ' . __(self::MISC_FORMS_CAPTS_EXTCONTRAS);

        $inputs .= $this->renderWebSelector($this->allECProfiles, array(self::DBFLD_PROFILE_NAME,
            self::DBFLD_PROFILE_CONTACT), self::CTRL_EXTCONTRAS_PROFILE_ID, __('Counterparty profile') . $this->supFrmFldMark, $contrasProfileID, false, false, true, '', '', '', true);
        $inputs .= $this->renderWebSelector($this->allECContracts, array(self::DBFLD_CTRCT_CONTRACT,
            self::DBFLD_CTRCT_SUBJECT,
            self::DBFLD_CTRCT_FULLSUM), self::CTRL_EXTCONTRAS_CONTRACT_ID, __('Contract'), $contrasContractID, true, false, true, '', '', '', true);
        $inputs .= $this->renderWebSelector($this->allECAddresses, array(self::DBFLD_ADDRESS_ADDR,
            self::DBFLD_ADDRESS_SUM), self::CTRL_EXTCONTRAS_ADDRESS_ID, __('Address'), $contrasAddressID, true, false, true, '', '', '', true);
        $inputs .= $this->renderWebSelector($this->allECPeriods, array(self::DBFLD_PERIOD_NAME), self::CTRL_EXTCONTRAS_PERIOD_ID, __('Period') . $this->supFrmFldMark, $contrasPeriodID, false, false, true, '', '', '', true);
        $inputs .= wf_TextInput(self::CTRL_EXTCONTRAS_PAYDAY, __('Payday') . $this->supFrmFldMark, $contrasPayDay, false, '4', 'digits', $emptyCheckClass, '', '', true);

        $inputs .= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', $this->submitBtnDisabled);
        $inputs .= wf_HiddenInput(self::ROUTE_CONTRAS_ACTS, 'true');

        if ($editAction) {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs .= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $extContrasID);
        } else {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and ! empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        // for dynamic finops modal from websel filtering
        if (!$modal) {
            $tmpWebSelJS = wf_jsWebSelectorFilter();

            $tmpWebSelJS .= '
        $(function() {
            onElementInserted("body", "#Modal' . $ecProfilesWebSelID . '", function(element) {
                $("#Modal' . $ecProfilesWebSelID . '").on("click change", function(evt) {
                    filterWebDropdown($(this).val(), $(\'#Modal' . $contractsFilterDataID . '\').val(), \'Modal' . $ecContractsWebSelID . '\', true);
                }); 
//console.log($("#Modal' . $ecProfilesWebSelID . '").val());     
//console.log(empty($("#' . $editDBValProfileID . '").val()));
                let tmpDBValue = (empty($("#' . $editDBValProfileID . '").val()) ? "0" : $("#' . $editDBValProfileID . '").val());                 
                $("#Modal' . $ecProfilesWebSelID . '").val(tmpDBValue).change();
                
            });
            
            onElementInserted("body", "#Modal' . $ecContractsWebSelID . '", function(element) {        
                $("#Modal' . $ecContractsWebSelID . '").on("click change", function(evt) {
                    filterWebDropdown($(this).val(), $(\'#Modal' . $addressFilterDataID . '\').val(), \'Modal' . $ecAddressWebSelID . '\', true);
                });
//console.log($("#Modal' . $ecContractsWebSelID . '").val());                
                let tmpDBValue = (empty($("#' . $editDBValContractID . '").val()) ? "0" : $("#' . $editDBValContractID . '").val());
                $("#Modal' . $ecContractsWebSelID . '").val(tmpDBValue).change();
//console.log($("#Modal' . $ecAddressWebSelID . '").val());              
                tmpDBValue = (empty($("#' . $editDBValAddressID . '").val()) ? "0" : $("#' . $editDBValAddressID . '").val()) ;
                $("#Modal' . $ecAddressWebSelID . '").val(tmpDBValue).change();
            });
        });
            
        ';

            $tmpWebSelJS = wf_EncloseWithJSTags($tmpWebSelJS);
        } else {
            $tmpWebSelJS = '';
        }

        $inputs .= $tmpWebSelJS;
        $inputs = wf_Form(self::URL_ME . '&' . self::URL_EXTCONTRAS . '=true', 'POST', $inputs, 'glamour form-grid-2cols form-grid-2cols-label-right ' . $formClass);

        if ($modal and ! empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders main top-level JQDT for external counterparty list
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @param string $detailsFilter
     * @param bool $stdJSForCRUDs
     *
     * @return string
     */
    public function extcontrasRenderMainJQDT($customJSCode = '', $markRowForID = '', $detailsFilter = '', $stdJSForCRUDs = true) {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_CONTRAS_JSON . '=true';
        $ajaxURLDetails = '' . self::URL_ME . '&' . self::ROUTE_2LVL_CNTRCTS_DETAIL . '=true';

        $columns[] = '';
        $columns[] = __('ID');
        $columns[] = __('EDRPO');
        $columns[] = __('Counterparty');
        $columns[] = __('Contact');
        $columns[] = __('E-mail');
        $columns[] = __('Rec fore color');
        $columns[] = __('Rec back color');
        $columns[] = __('Filter for details');

        $this->getTableGridColorOpts();

        $opts = '
            "columnDefs": [ 
                            {"targets": [0], "className": "details-control"},
                            {"targets": [0], "orderable": false},
                            {"targets": [0], "data": null},
                            {"targets": [0], "defaultContent": ""},
                            {"targets": [6, 7, 8], "visible": false},                            
                            {"targets": ["_all"], "className": "dt-center dt-head-center"}                                              
                          ],
            "order": [[ 1, "desc" ]],
            "rowCallback": function(row, data, index) {                               
                if ( data[6] != "" ) {                    
                    $(\'td\', row).css(\'color\', data[6]);
                } 
                
                if ( data[7] != "" ) {
                    $(\'td\', row).css(\'background-color\', data[7]);
                } 
            }
            
            ';

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, $stdJSForCRUDs, $customJSCode, $markRowForID, self::URL_ME . '&' . self::URL_EXTCONTRAS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID, self::MISC_MARKROW_URL, false, '', $this->currencyStr, true, $ajaxURLDetails, 8);

        return($result);
    }

    /**
     * Renders JSON for external counterparty JQDT
     *
     * @param string $whereRaw
     *
     */
    public function extcontrasRenderListJSON($whereRaw = '') {
        $this->loadDataFromTableCached(self::TABLE_ECPROFILES, self::TABLE_ECPROFILES,
                                       !empty($whereRaw), true,'', '', !empty($whereRaw));

        $json = new wf_JqDtHelper();

        if (!empty($this->allECProfiles)) {
//$this->dbExtContras->setDebug(true, true);
  /*          if (!empty($whereRaw)) {
                $this->dbExtContras->whereRaw($whereRaw);
            }*/

            $data = array();
            $tmpExtContrasRecs = $this->loadDataFromTableCached(self::TABLE_EXTCONTRAS, self::TABLE_EXTCONTRAS, !empty($whereRaw));

            foreach ($this->allECProfiles as $eachRecID) {
                $profileRecID = $eachRecID[self::DBFLD_COMMON_ID];
                $recForeColor = '';
                $recBackColor = '';

                if (!empty($tmpExtContrasRecs)) {
                    foreach ($tmpExtContrasRecs as $eachID => $eachData) {
                        if ($eachData[self::DBFLD_EXTCONTRAS_PROFILE_ID] == $profileRecID) {
                            // $eachID === $eachData[self::DBFLD_COMMON_ID]
                            $hasPaymentsCurMonth = $this->checkCurMonthPaymExists($eachData[self::DBFLD_COMMON_ID]);

                            if (!empty($hasPaymentsCurMonth)) {
                                $recForeColor = $this->payedThisMonthFRGND;
                                $recBackColor = $this->payedThisMonthBKGND;
                                break;
                            }

                            if ($eachData[self::DBFLD_EXTCONTRAS_PAYDAY] - date('j') <= 5 and empty($hasPaymentsCurMonth)) {
                                $recForeColor = $this->fiveDaysTillPayFRGND;
                                $recBackColor = $this->fiveDaysTillPayBKGND;
                                break;
                            }

                            if (date('j') > $eachData[self::DBFLD_EXTCONTRAS_PAYDAY] and empty($hasPaymentsCurMonth)) {
                                $recForeColor = $this->paymentExpiredFRGND;
                                $recBackColor = $this->paymentExpiredBKGND;
                                break;
                            }
                        }
                    }
                }

                $data[] = '';
                $data[] = $profileRecID;
                $data[] = wf_Link(self::URL_ME . '&' . self::URL_DICTPROFILES . '=true'
                        . '&' . self::MISC_MARKROW_URL . '=' . $profileRecID, $eachRecID[self::DBFLD_PROFILE_EDRPO]);
                $data[] = $eachRecID[self::DBFLD_PROFILE_NAME];
                $data[] = $eachRecID[self::DBFLD_PROFILE_CONTACT];
                $data[] = $eachRecID[self::DBFLD_PROFILE_MAIL];
                $data[] = $recForeColor;
                $data[] = $recBackColor;
                $data[] = '&' . self::DBFLD_EXTCONTRAS_PROFILE_ID . '=' . $profileRecID;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Renders second-level contract-address JQDT for external counterparty list
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @param string $detailsFilter
     * @param bool $stdJSForCRUDs
     *
     * @return string
     */
    public function ecRender2ndLvlContractsJQDT($customJSCode = '', $markRowForID = '', $detailsFilter = '', $stdJSForCRUDs = true) {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_2LVL_CNTRCTS_JSON . '=true' . $detailsFilter;
        $ajaxURLDetails = '' . self::URL_ME . '&' . self::ROUTE_FINOPS_DETAILS_CNTRCTS . '=true';

        $columns[] = '';
        $columns[] = __('ID');
        $columns[] = __('Contract');
        $columns[] = __('Contract subject');
        $columns[] = __('Contract date start');
        $columns[] = __('Contract sum');  //5
        $columns[] = __('Period');
        $columns[] = __('Payday');
        $columns[] = __('Actions');     //8
        $columns[] = __('Add' . ' ' . self::MISC_FORMS_CAPTS_FINOPS_LIST);
        $columns[] = __('Payed this month');
        $columns[] = __('5 days till payday');
        $columns[] = __('Payment expired');
        $columns[] = __('Filter for details');      //13

        $opts = '
            "columnDefs": [ 
                            {"targets": [0], "className": "details-control"},
                            {"targets": [0], "orderable": false},
                            {"targets": [0], "data": null},
                            {"targets": [0], "defaultContent": ""},                           
                            {"targets": [10, 11, 12, 13], "visible": false},                     
                            {"targets": [3], "className": "dt-left dt-head-center"},
                            {"targets": ["_all"], "className": "dt-center dt-head-center"},
                            {"targets": [8], "width": "85px"},
                            {"targets": [8, 9], "orderable": false}                                                        
                          ],
            "order": [[ 1, "desc" ]],
            "rowCallback": function(row, data, index) {                               
                if ( data[10] == "1" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->payedThisMonthBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->payedThisMonthFRGND . '\');
                } 
                
                if ( data[11] == "1" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->fiveDaysTillPayBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->fiveDaysTillPayFRGND . '\');
                } 
                
                if ( data[12] == "1" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->paymentExpiredBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->paymentExpiredFRGND . '\');
                } 
            }
            
            ';

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, $stdJSForCRUDs, $customJSCode, $markRowForID, self::URL_ME . '&' . self::URL_EXTCONTRAS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID, self::MISC_MARKROW_URL, true, array(5), $this->currencyStr, true, $ajaxURLDetails, 13, 'showDetailsData13');
        return($result);
    }

    /**
     * Renders JSON for external counterparty contract-address JQDT
     *
     * @param string $whereRaw
     *
     */
    public function ecRender2ndLvlContractsListJSON($whereRaw = '') {
        $this->loadExtContrasExtenData(true, $whereRaw);
        $json = new wf_JqDtHelper();
        $contractsIN = array();

        if (!empty($this->allExtContrasExten)) {
            $data = array();

            foreach ($this->allExtContrasExten as $eachRecID) {
                $curRecID = $eachRecID[self::TABLE_EXTCONTRAS . self::DBFLD_COMMON_ID];
                $profileRecID = $eachRecID[self::TABLE_ECPROFILES . self::DBFLD_COMMON_ID];
                $contractRecID = $eachRecID[self::TABLE_ECCONTRACTS . self::DBFLD_COMMON_ID];
                $periodRecID = $eachRecID[self::TABLE_ECPERIODS . self::DBFLD_COMMON_ID];
                $addrRecID = $eachRecID[self::TABLE_ECADDRESS . self::DBFLD_COMMON_ID];
                $contractSum = $eachRecID[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_FULLSUM];
                $payDay = $eachRecID[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PAYDAY];

                if (in_array($contractRecID, $contractsIN)) {
                    continue;
                } else {
                    $contractsIN[] = $contractRecID;
                }

                $data[] = '';
                $data[] = $contractRecID;
                $data[] = wf_Link(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true'
                        . '&' . self::MISC_MARKROW_URL . '=' . $contractRecID, $eachRecID[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_CONTRACT]);
                $data[] = $eachRecID[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_SUBJECT];
                $data[] = $eachRecID[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_DTSTART];
                $data[] = $contractSum;
                $data[] = $eachRecID[self::TABLE_ECPERIODS . self::DBFLD_PERIOD_NAME];
                $data[] = $payDay;

                $actions = $this->getStdJQDTActions($curRecID, self::ROUTE_CONTRAS_ACTS, true);
                $data[] = $actions;

                $hasPaymentsCurMonth = $this->checkCurMonthPaymExists($curRecID, true);
                $hasAddressesAtached = ($this->ecFullCtrctOverdueNoAddrOnly) ? $this->checkContractHasAddresses($profileRecID, $contractRecID) : false;

                if (empty($hasAddressesAtached) and date('j') > $payDay and empty($hasPaymentsCurMonth)) {
                    $payTimeExpired = 1;
                    $this->createMissedPayment($curRecID, $profileRecID, $contractRecID, $addrRecID, $periodRecID, $payDay, $contractSum);
                } else {
                    $payTimeExpired = 0;
                }

                $data[] = wf_jsAjaxDynamicWindowButton(self::URL_ME, array(self::ROUTE_FINOPS_ACTS => 'true',
                    self::ROUTE_ACTION_PREFILL => 'true',
                    self::MISC_PREFILL_DATA => array(self::CTRL_MONEY_PROFILEID => $profileRecID,
                        self::CTRL_MONEY_CNTRCTID => $contractRecID,
                        self::CTRL_MONEY_SUMPAYMENT => $contractSum
                    )
                        ), '', web_add_icon(), '', 'POST', 'click', false, false, true, '$(this).closest("table").parent().children().find(\'[id ^= "jqdt_"][role = "grid"]\').last().attr("id")'
                );

                $data[] = (empty($hasPaymentsCurMonth) ? 0 : 1);
                $data[] = ($payDay - date('j') <= 5 and empty($hasPaymentsCurMonth)) ? 1 : 0;
                $data[] = $payTimeExpired;
                $data[] = '&' . self::DBFLD_COMMON_ID . '=' . $profileRecID
                        . '&' . self::DBFLD_EXTCONTRAS_CONTRACT_ID . '=' . $contractRecID
                        . '&' . self::DBFLD_EXTCONTRAS_ADDRESS_ID . '=' . $addrRecID;
                $json->addRow($data);

                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Renders third-level contract-address JQDT for external counterparty list
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @param string $detailsFilter
     * @param bool $stdJSForCRUDs
     *
     * @return string
     */
    public function ecRender2ndLvlAddressJQDT($customJSCode = '', $markRowForID = '', $detailsFilter = '', $stdJSForCRUDs = true) {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_3LVL_ADDR_JSON . '=true' . $detailsFilter;
        $ajaxURLDetails = '' . self::URL_ME . '&' . self::ROUTE_FINOPS_DETAILS_ADDRESS . '=true';

        $columns[] = '';
        $columns[] = __('ID');
        $columns[] = __('Address');
        $columns[] = __('Address contract notes');
        $columns[] = __('Address sum');     //4
        $columns[] = __('Period');
        $columns[] = __('Payday');
        $columns[] = __('Actions');     // 7
        $columns[] = __('Add' . ' ' . self::MISC_FORMS_CAPTS_FINOPS_LIST);
        $columns[] = __('Payed this month');
        $columns[] = __('5 days till payday');
        $columns[] = __('Payment expired');
        $columns[] = __('Filter for details');  //12
        $opts = '
            "columnDefs": [ 
                            {"targets": [0], "className": "details-control"},
                            {"targets": [0], "orderable": false},
                            {"targets": [0], "data": null},
                            {"targets": [0], "defaultContent": ""},                           
                            {"targets": [9, 10, 11, 12], "visible": false},                     
                            {"targets": [4, 5, 6], "className": "dt-left dt-head-center"},
                            {"targets": ["_all"], "className": "dt-center dt-head-center"},
                            {"targets": [7], "width": "85px"},
                            {"targets": [7, 8], "orderable": false}                                                        
                          ],
            "order": [[ 1, "desc" ]],
            "rowCallback": function(row, data, index) {                               
                if ( data[9] == "1" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->payedThisMonthBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->payedThisMonthFRGND . '\');
                } 
                
                if ( data[10] == "1" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->fiveDaysTillPayBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->fiveDaysTillPayFRGND . '\');
                } 
                
                if ( data[11] == "1" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->paymentExpiredBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->paymentExpiredFRGND . '\');
                } 
            }
            
            ';

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, $stdJSForCRUDs, $customJSCode, $markRowForID, self::URL_ME . '&' . self::URL_EXTCONTRAS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID, self::MISC_MARKROW_URL, true, array(4), $this->currencyStr, true, $ajaxURLDetails, 12, 'showDetailsData12');
        return($result);
    }

    /**
     * Renders JSON for external counterparty contract-address JQDT
     *
     * @param string $whereRaw
     *
     */
    public function ecRender2ndLvlAddressListJSON($whereRaw = '') {
        $this->loadExtContrasExtenData(true, $whereRaw);
        $json = new wf_JqDtHelper();

        if (!empty($this->allExtContrasExten)) {
            $data = array();

            foreach ($this->allExtContrasExten as $eachRecID) {
                $curRecID = $eachRecID[self::TABLE_EXTCONTRAS . self::DBFLD_COMMON_ID];
                $profileRecID = $eachRecID[self::TABLE_ECPROFILES . self::DBFLD_COMMON_ID];
                $contractRecID = $eachRecID[self::TABLE_ECCONTRACTS . self::DBFLD_COMMON_ID];
                $periodRecID = $eachRecID[self::TABLE_ECPERIODS . self::DBFLD_COMMON_ID];
                $addrRecID = $eachRecID[self::TABLE_ECADDRESS . self::DBFLD_COMMON_ID];
                $addressSum = $eachRecID[self::TABLE_ECADDRESS . self::DBFLD_ADDRESS_SUM];
                $payDay = $eachRecID[self::TABLE_EXTCONTRAS . self::DBFLD_EXTCONTRAS_PAYDAY];

                $data[] = '';
                $data[] = $addrRecID;
                $data[] = wf_Link(self::URL_ME . '&' . self::URL_DICTADDRESS . '=true'
                        . '&' . self::MISC_MARKROW_URL . '=' . $addrRecID, $eachRecID[self::TABLE_ECADDRESS . self::DBFLD_ADDRESS_ADDR]);
                $data[] = $eachRecID[self::TABLE_ECADDRESS . self::DBFLD_ADDRESS_NOTES];
                $data[] = $addressSum;
                $data[] = $eachRecID[self::TABLE_ECPERIODS . self::DBFLD_PERIOD_NAME];
                $data[] = $payDay;

                $actions = $this->getStdJQDTActions($curRecID, self::ROUTE_CONTRAS_ACTS, true);
                $data[] = $actions;

                $hasPaymentsCurMonth = $this->checkCurMonthPaymExists($curRecID, true, true);

                if (date('j') > $payDay and empty($hasPaymentsCurMonth)) {
                    $payTimeExpired = 1;
                    $this->createMissedPayment($curRecID, $profileRecID, $contractRecID, $addrRecID, $periodRecID, $payDay, $addressSum);
                } else {
                    $payTimeExpired = 0;
                }

                $data[] = wf_jsAjaxDynamicWindowButton(self::URL_ME, array(self::ROUTE_FINOPS_ACTS => 'true',
                    self::ROUTE_ACTION_PREFILL => 'true',
                    self::MISC_PREFILL_DATA => array(self::CTRL_MONEY_PROFILEID => $profileRecID,
                        self::CTRL_MONEY_CNTRCTID => $contractRecID,
                        self::CTRL_MONEY_ADDRESSID => $addrRecID,
                        self::CTRL_MONEY_SUMPAYMENT => $addressSum
                    )
                        ), '', web_add_icon(), '', 'POST', 'click', false, false, true, '$(this).closest("table").parent().children().find(\'[id ^= "jqdt_"][role = "grid"]\').last().attr("id")'
                );

                $data[] = (empty($hasPaymentsCurMonth) ? 0 : 1);
                $data[] = ($payDay - date('j') <= 5 and empty($hasPaymentsCurMonth)) ? 1 : 0;
                $data[] = $payTimeExpired;
                $data[] = '&' . self::DBFLD_COMMON_ID . '=' . $profileRecID
                        . '&' . self::DBFLD_EXTCONTRAS_CONTRACT_ID . '=' . $contractRecID
                        . '&' . self::DBFLD_EXTCONTRAS_ADDRESS_ID . '=' . $addrRecID;
                $json->addRow($data);

                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Creates a missed payment record from params
     *
     * @param $contrasID
     * @param $profileID
     * @param $contractID
     * @param $addrID
     * @param $periodID
     * @param $payDay
     * @param $paySum
     */
    protected function createMissedPayment($contrasID, $profileID, $contractID, $addrID, $periodID, $payDay, $paySum) {
        $chkUniqArray = array();
        $recordExists = true;
        $paymentDate = date('Y-m-') . $payDay;

        $chkUniqArray += $this->createCheckUniquenessArray(self::DBFLD_MISSPAYMS_CONTRASID, '=', $contrasID);
        $chkUniqArray += $this->createCheckUniquenessArray(self::DBFLD_MISSPAYMS_PROFILEID, '=', $profileID);
        $chkUniqArray += $this->createCheckUniquenessArray(self::DBFLD_MISSPAYMS_CONTRACTID, '=', $contractID);
        $chkUniqArray += $this->createCheckUniquenessArray(self::DBFLD_MISSPAYMS_ADDRESSID, '=', $addrID);
        $chkUniqArray += $this->createCheckUniquenessArray(self::DBFLD_MISSPAYMS_PERIOD_ID, '=', $periodID);
        $chkUniqArray += $this->createCheckUniquenessArray(self::DBFLD_MISSPAYMS_PAYDAY, '=', $payDay);
        $chkUniqArray += $this->createCheckUniquenessArray(self::DBFLD_MISSPAYMS_DATE_PAYMENT, '=', $paymentDate);
        $chkUniqArray += $this->createCheckUniquenessArray(self::DBFLD_MISSPAYMS_SUMPAYMENT, '=', $paySum);

//$this->dbECMissedPayms->setDebug(true, true);
        $recordExists = $this->dbECMissedPayms->checkRecExists($chkUniqArray);

        if (!$recordExists) {
            $this->dbECMissedPayms->data(self::DBFLD_MISSPAYMS_CONTRASID, $contrasID);
            $this->dbECMissedPayms->data(self::DBFLD_MISSPAYMS_PROFILEID, $profileID);
            $this->dbECMissedPayms->data(self::DBFLD_MISSPAYMS_CONTRACTID, $contractID);
            $this->dbECMissedPayms->data(self::DBFLD_MISSPAYMS_ADDRESSID, $addrID);
            $this->dbECMissedPayms->data(self::DBFLD_MISSPAYMS_PERIOD_ID, $periodID);
            $this->dbECMissedPayms->data(self::DBFLD_MISSPAYMS_PAYDAY, $payDay);
            $this->dbECMissedPayms->data(self::DBFLD_MISSPAYMS_DATE_PAYMENT, $paymentDate);
            $this->dbECMissedPayms->data(self::DBFLD_MISSPAYMS_DATE_EXPIRED, curdatetime());
            $this->dbECMissedPayms->data(self::DBFLD_MISSPAYMS_SUMPAYMENT, $paySum);
            $this->dbECMissedPayms->create();
        }
    }

    /**
     * Updates a missed payment record paid date field
     *
     * @param        $missPaymID
     * @param string $datePayed
     */
    public function updateMissedPaymentPayedDate($missPaymID, $datePayed = '') {
        if (!empty($missPaymID)) {
            $datePayed = (empty($datePayed) ? curdatetime() : $datePayed);

            $this->dbECMissedPayms->data(self::DBFLD_MISSPAYMS_DATE_PAYED, $datePayed);
            $this->dbECMissedPayms->where('id', '=', $missPaymID);
            $this->dbECMissedPayms->save();
        }
    }

    /**
     * Returns payday of the contragent by profile ID + contract ID [+ address ID]
     *
     * @param $profileID
     * @param $contractID
     * @param $addressID
     *
     * @return int|string
     */
    public function getContraPayday($profileID, $contractID, $addressID = 0) {
        $this->dbExtContras->where();
        $this->dbExtContras->whereRaw();
        $this->dbExtContras->selectable('payday');
        $this->dbExtContras->where(self::DBFLD_EXTCONTRAS_PROFILE_ID, '=', $profileID);
        $this->dbExtContras->where(self::DBFLD_EXTCONTRAS_CONTRACT_ID, '=', $contractID);

        if (!empty($addressID)) {
            $this->dbExtContras->where(self::DBFLD_EXTCONTRAS_ADDRESS_ID, '=', $addressID);
        }

        $result = $this->dbExtContras->getAll();
        $result = empty($result[0][self::DBFLD_EXTCONTRAS_PAYDAY]) ? '' : $result[0][self::DBFLD_EXTCONTRAS_PAYDAY];

        return ($result);
    }

    /**
     * Returns payday of the missed payment by its ID
     *
     * @param $missPaymID
     *
     * @return mixed|string
     */
    public function getMissedPaymentPayDay($missPaymID) {
        $datePayDue = empty($this->allECMissedPayms[$missPaymID]) ? '' : $this->allECMissedPayms[$missPaymID][self::DBFLD_MISSPAYMS_DATE_PAYMENT];

        return ($datePayDue);
    }

    /**
     * Renders counterparties table coloring settings form
     *
     * @return string
     */
    public function extcontrasColorSettings() {
        $this->getTableGridColorOpts();

        $inputs = '';
        $tmpStyle = 'style="float: right; width: 80px; height: 20px; border: 2px solid rgba(100, 100, 100, .8); border-radius: 4px; ';

        $inputs .= wf_ColPicker(self::CTRL_ECCOLOR_PAYEDTHISMONTH_BKGND, __('Already payed this month background'), $this->payedThisMonthBKGND, false, '7', self::CTRL_ECCOLOR_PAYEDTHISMONTH_BKGND, 'background-color');
        $inputs .= wf_nbsp(4) . wf_tag('span', false, '', 'id="' . self::CTRL_ECCOLOR_PAYEDTHISMONTH_BKGND . '" ' . $tmpStyle . $this->payedThisMonthBKGND . ';"')
                . wf_tag('span', true) . wf_delimiter(1);

        $inputs .= wf_ColPicker(self::CTRL_ECCOLOR_PAYEDTHISMONTH_FRGND, __('Already payed this month foreground'), $this->payedThisMonthFRGND, false, '7', self::CTRL_ECCOLOR_PAYEDTHISMONTH_FRGND, 'background-color');
        $inputs .= wf_nbsp(4) . wf_tag('span', false, '', 'id="' . self::CTRL_ECCOLOR_PAYEDTHISMONTH_FRGND . '" ' . $tmpStyle . $this->payedThisMonthBKGND . ';"')
                . wf_tag('span', true) . wf_delimiter(1);

        $inputs .= wf_ColPicker(self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_BKGND, __('5 days left till payday background'), $this->fiveDaysTillPayBKGND, false, '7', self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_BKGND, 'background-color');
        $inputs .= wf_nbsp(4) . wf_tag('span', false, '', 'id="' . self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_BKGND . '" ' . $tmpStyle . $this->payedThisMonthBKGND . ';"')
                . wf_tag('span', true) . wf_delimiter(1);

        $inputs .= wf_ColPicker(self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_FRGND, __('5 days left till payday background'), $this->fiveDaysTillPayFRGND, false, '7', self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_FRGND, 'background-color');
        $inputs .= wf_nbsp(4) . wf_tag('span', false, '', 'id="' . self::CTRL_ECCOLOR_FIVEDAYSTILLPAY_FRGND . '" ' . $tmpStyle . $this->payedThisMonthBKGND . ';"')
                . wf_tag('span', true) . wf_delimiter(1);

        $inputs .= wf_ColPicker(self::CTRL_ECCOLOR_PAYMENTEXPIRED_BKGND, __('Payment expired background'), $this->paymentExpiredBKGND, false, '7', self::CTRL_ECCOLOR_PAYMENTEXPIRED_BKGND, 'background-color');
        $inputs .= wf_nbsp(4) . wf_tag('span', false, '', 'id="' . self::CTRL_ECCOLOR_PAYMENTEXPIRED_BKGND . '" ' . $tmpStyle . $this->payedThisMonthBKGND . ';"')
                . wf_tag('span', true) . wf_delimiter(1);

        $inputs .= wf_ColPicker(self::CTRL_ECCOLOR_PAYMENTEXPIRED_FRGND, __('Payment expired foreground'), $this->paymentExpiredFRGND, false, '7', self::CTRL_ECCOLOR_PAYMENTEXPIRED_FRGND, 'background-color');
        $inputs .= wf_nbsp(4) . wf_tag('span', false, '', 'id="' . self::CTRL_ECCOLOR_PAYMENTEXPIRED_FRGND . '" ' . $tmpStyle . $this->payedThisMonthBKGND . ';"')
                . wf_tag('span', true) . wf_delimiter(1);

        $inputs .= wf_delimiter(0);
        $inputs .= wf_HiddenInput(self::URL_EXTCONTRAS_COLORS, 'true');
        $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Save'), '', 'style="width: 100%"; ' . $this->submitBtnDisabled);

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_EXTCONTRAS_COLORS . '=true', 'POST', $inputs, 'glamour');

        return ($inputs);
    }

    /**
     * Returns a filter web form for invoices main form
     *
     * @return string
     */
    public function finopsFilterWebForm() {
        $ajaxURLStr = self::URL_ME . '&' . self::ROUTE_FINOPS_JSON . '=true';
        $formID = 'Form_' . wf_InputId();
        $jqdtID = 'jqdt_' . md5($ajaxURLStr);

        $inputs = wf_tag('h3', false);
        $inputs .= __('Filter by') . ':';
        $inputs .= wf_tag('h3', true);
        $rows = wf_DatesTimesRangeFilter(true, true, false, false, true, false, ubRouting::post(self::MISC_WEBFILTER_DATE_START), ubRouting::post(self::MISC_WEBFILTER_DATE_END), self::MISC_WEBFILTER_DATE_START, self::MISC_WEBFILTER_DATE_END
        );

        $inputs .= wf_TableBody($rows, 'auto');
        $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Show'), '', 'style="width: 100%"');

        $inputs = wf_Form($ajaxURLStr, 'POST', $inputs, 'glamour form-grid-3r-1c', '', $formID, '', 'style="margin-top: 10px;"');
        $inputs .= wf_EncloseWithJSTags(wf_jsAjaxFilterFormSubmit($ajaxURLStr, $formID, $jqdtID));

        return ($inputs);
    }

    /**
     * Returns a financial operations editor web form
     *
     * @param bool  $modal
     * @param int   $finopID
     * @param bool  $editAction
     * @param bool  $cloneAction
     * @param array $prefillFieldsData
     *
     * @return string
     */
    public function finopsWebForm($modal = true, $finopID = 0, $editAction = false, $cloneAction = false, $prefillFieldsData = array()) {
        $this->loadWebSelFilterData();

        $inputs = '';
        $finopProfileID = 0;
        $finopContractID = 0;
        $finopAddressID = 0;
        $finopAccrualID = 0;
        $finopInvoiceID = 0;
        $finopPurpose = '';
        $finopSumAccrual = '';
        $finopSumPayment = '';
        $finopNotes = '';
        $finopIncoming = '';
        $finopOutgoing = '';
        $finopAccruals = array();
        $modalWinID = ubRouting::post('modalWindowId');
        $modalWinBodyID = ubRouting::post('modalWindowBodyId');

        if ($modal) {
            $formClass = self::MISC_CLASS_SUBMITFORM_MODAL;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK_MODAL;
        } else {
            $formClass = self::MISC_CLASS_SUBMITFORM;
            $emptyCheckClass = self::MISC_CLASS_EMPTYVALCHECK;
        }

        if (($editAction or $cloneAction) and ! empty($this->allECMoney[$finopID])) {
            $finoperation = $this->allECMoney[$finopID];
            $finopProfileID = $finoperation[self::DBFLD_MONEY_PROFILEID];
            $finopContractID = $finoperation[self::DBFLD_MONEY_CNTRCTID];
            $finopAddressID = $finoperation[self::DBFLD_MONEY_ADDRESSID];
            $finopAccrualID = $finoperation[self::DBFLD_MONEY_ACCRUALID];
            $finopInvoiceID = $finoperation[self::DBFLD_MONEY_INVOICEID];
            $finopPurpose = $finoperation[self::DBFLD_MONEY_PURPOSE];
            $finopSumAccrual = $finoperation[self::DBFLD_MONEY_SMACCRUAL];
            $finopSumPayment = $finoperation[self::DBFLD_MONEY_SMPAYMENT];
            $finopNotes = $finoperation[self::DBFLD_MONEY_PAYNOTES];
            $finopIncoming = ubRouting::filters($finoperation[self::DBFLD_MONEY_INCOMING], 'fi', FILTER_VALIDATE_BOOLEAN);
            $finopOutgoing = ubRouting::filters($finoperation[self::DBFLD_MONEY_OUTGOING], 'fi', FILTER_VALIDATE_BOOLEAN);
        } elseif (!empty($prefillFieldsData)) {
            $finopProfileID = (empty($prefillFieldsData[self::CTRL_MONEY_PROFILEID]) ? 0 : $prefillFieldsData[self::CTRL_MONEY_PROFILEID]);
            $finopContractID = (empty($prefillFieldsData[self::CTRL_MONEY_CNTRCTID]) ? 0 : $prefillFieldsData[self::CTRL_MONEY_CNTRCTID]);
            $finopAddressID = (empty($prefillFieldsData[self::CTRL_MONEY_ADDRESSID]) ? 0 : $prefillFieldsData[self::CTRL_MONEY_ADDRESSID]);
            $finopSumPayment = (empty($prefillFieldsData[self::CTRL_MONEY_SUMPAYMENT]) ? 0 : $prefillFieldsData[self::CTRL_MONEY_SUMPAYMENT]);

            if (!$editAction and ! $cloneAction) {
                $misspaymProcess = (empty($prefillFieldsData[self::MISC_MISSED_PAYMENT_PROCESSING]) ? 0 : $prefillFieldsData[self::MISC_MISSED_PAYMENT_PROCESSING]);
                $misspaymID = (empty($prefillFieldsData[self::MISC_MISSED_PAYMENT_ID]) ? 0 : $prefillFieldsData[self::MISC_MISSED_PAYMENT_ID]);
                $inputs .= wf_HiddenInput(self::MISC_MISSED_PAYMENT_PROCESSING, $misspaymProcess);
                $inputs .= wf_HiddenInput(self::MISC_MISSED_PAYMENT_ID, $misspaymID);
            }
        }

        $this->dbECMoney->whereRaw(" " . self::DBFLD_MONEY_SMACCRUAL . " != 0");
        $finopAccruals = $this->loadDataFromTableCached(self::TABLE_ECMONEY, self::TABLE_ECMONEY, true);
        $this->loadDataFromTableCached(self::TABLE_ECMONEY, self::TABLE_ECMONEY, true);

        $ecProfilesWebSelID = ($modal ? 'Modal' : '') . self::MISC_WEBSEL_PROFILES;
        $ecContractsWebSelID = ($modal ? 'Modal' : '') . self::MISC_WEBSEL_CONTRACTS;
        $ecAddressWebSelID = ($modal ? 'Modal' : '') . self::MISC_WEBSEL_ADDRESS;
        $contractsFilterDataID = ($modal ? 'Modal' : '') . self::MISC_WEBSEL_FILTDATA_CONTRACTS;
        $addressFilterDataID = ($modal ? 'Modal' : '') . self::MISC_WEBSEL_FILTDATA_ADDRESS;
        $editDBValProfileID = self::MISC_WEBSEL_DBVAL_PROFILE_ID;
        $editDBValContractID = self::MISC_WEBSEL_DBVAL_CONTRACTS_ID;
        $editDBValAddressID = self::MISC_WEBSEL_DBVAL_ADDRESS_ID;

        $submitCapt = ($editAction) ? __('Edit') : (($cloneAction) ? __('Clone') : __('Create'));
        $formCapt = $submitCapt . ' ' . __(self::MISC_FORMS_CAPTS_FINOPS_LIST);

        $ctrlsLblStyle = 'style="line-height: 2.2em"';

        if ($editAction or $cloneAction or ! empty($prefillFieldsData)) {
            $inputs .= wf_HiddenInput($editDBValProfileID . 'nm', $finopProfileID, $editDBValProfileID);
            $inputs .= wf_HiddenInput($editDBValContractID . 'nm', $finopContractID, $editDBValContractID);
            $inputs .= wf_HiddenInput($editDBValAddressID . 'nm', $finopAddressID, $editDBValAddressID);
        }

        $inputs .= wf_TextInput(self::CTRL_MONEY_PURPOSE, __('Operation purpose') . $this->supFrmFldMark, $finopPurpose, false, '', '', $emptyCheckClass . ' right-two-thirds-occupy', '', '', true);

        //$inputs.= wf_tag('span', false);
        $inputs .= wf_TextInput(self::CTRL_MONEY_SUMACCRUAL, __('Accrual sum'), $finopSumAccrual, false, '', 'finance', 'col-2-3-occupy', '', '', true);
        //$inputs.= wf_tag('span', true);

        $inputs .= wf_TextInput(self::CTRL_MONEY_SUMPAYMENT, __('Payment sum'), $finopSumPayment, false, '', 'finance', 'col-5-6-occupy', '', '', true);

        $inputs .= $this->renderWebSelector($this->allECProfiles, array(self::DBFLD_PROFILE_EDRPO,
            self::DBFLD_PROFILE_NAME,
            self::DBFLD_PROFILE_CONTACT
                ), self::CTRL_MONEY_PROFILEID, __('Counterparty'), $finopProfileID, true, false, true, $ecProfilesWebSelID, '', '', true, '', $this->selectfiltECContractsAll, $contractsFilterDataID);

        $inputs .= $this->renderWebSelector($this->allECContracts, array(self::DBFLD_CTRCT_CONTRACT,
            self::DBFLD_CTRCT_SUBJECT,
            self::DBFLD_CTRCT_FULLSUM
                ), self::CTRL_MONEY_CNTRCTID, __('Contract'), $finopContractID, true, false, true, $ecContractsWebSelID, '', '', true, '', $this->selectfiltECAddressAll, $addressFilterDataID);

        $inputs .= $this->renderWebSelector($this->allECAddresses, array(self::DBFLD_ADDRESS_ADDR,
            self::DBFLD_ADDRESS_SUM
                ), self::CTRL_MONEY_ADDRESSID, __('Address'), $finopAddressID, true, false, true, $ecAddressWebSelID, '', '', true);

        if (!$modal) {
            $tmpWebSelJS = wf_jsWebSelectorFilter();

            $tmpWebSelJS .= '
        $(function() {
            onElementInserted("body", "#Modal' . $ecProfilesWebSelID . '", function(element) {
                $("#Modal' . $ecProfilesWebSelID . '").on("click change", function(evt) {
                    filterWebDropdown($(this).val(), $(\'#Modal' . $contractsFilterDataID . '\').val(), \'Modal' . $ecContractsWebSelID . '\', true);
                }); 
                
                $("#Modal' . $ecProfilesWebSelID . '").val($("#' . $editDBValProfileID . '").val()).change();
            });
            
            onElementInserted("body", "#Modal' . $ecContractsWebSelID . '", function(element) {        
                $("#Modal' . $ecContractsWebSelID . '").on("click change", function(evt) {
                    filterWebDropdown($(this).val(), $(\'#Modal' . $addressFilterDataID . '\').val(), \'Modal' . $ecAddressWebSelID . '\', true);
                });
                
                $("#Modal' . $ecContractsWebSelID . '").val($("#' . $editDBValContractID . '").val()).change();
                $("#Modal' . $ecAddressWebSelID . '").val($("#' . $editDBValAddressID . '").val()).change();
            });
        
            $(\'#' . $ecProfilesWebSelID . '\').on("change", function(evt) {
                filterWebDropdown($(this).val(), $(\'#' . $contractsFilterDataID . '\').val(), \'' . $ecContractsWebSelID . '\', true);
            });
            
            $(\'#' . $ecContractsWebSelID . '\').on("change", function(evt) {
                filterWebDropdown($(this).val(), $(\'#' . $addressFilterDataID . '\').val(), \'' . $ecAddressWebSelID . '\', true);
            });
            
            $(\'#' . $ecProfilesWebSelID . '\').change();
            $(\'#' . $ecContractsWebSelID . '\').change();
        });
        
            ';

            $tmpWebSelJS = wf_EncloseWithJSTags($tmpWebSelJS . "\n");
        } else {
            $tmpWebSelJS = '';
        }

        $inputs .= $tmpWebSelJS;

        if ($this->ecInvoicesON) {
            $inputs .= $this->renderWebSelector($this->allECInvoices, array(self::DBFLD_INVOICES_INVOICE_NUM,
                self::DBFLD_INVOICES_DATE,
                self::DBFLD_INVOICES_SUM
                    ), self::CTRL_MONEY_INVOICEID, __('Invoice'), $finopInvoiceID, true, false, true, '', 'col-2-3-occupy', '', true);
        }

        $inputs .= $this->renderWebSelector($finopAccruals, array(self::DBFLD_MONEY_PURPOSE,
            self::DBFLD_MONEY_SMACCRUAL,
            self::DBFLD_MONEY_DATE
                ), self::CTRL_MONEY_ACCRUALID, __('Accrual'), $finopAccrualID, true, false, true, '', ($this->ecInvoicesON ? 'col-5-6-occupy' : 'col-2-3-occupy'), '', true);

        $inputs .= wf_TextInput(self::CTRL_MONEY_PAYNOTES, __('Payment notes'), $finopNotes, false, '70', '', ($this->ecInvoicesON ? 'right-two-thirds-occupy' : 'col-5-6-occupy'), '', '', true);

        $inputs .= wf_tag('span', false, 'glamour full-width-occupy', 'style="text-align: center; width: 98%;"');
        $inputs .= wf_RadioInput(self::CTRL_MONEY_INOUT, __('Incoming payment'), 'incoming', false, $finopIncoming);
        $inputs .= wf_nbsp(8);
        $inputs .= wf_RadioInput(self::CTRL_MONEY_INOUT, __('Outgoing payment'), 'outgoing', false, $finopOutgoing);
        $inputs .= wf_tag('span', true);

        $inputs .= wf_SubmitClassed(true, 'ubButton', '', $submitCapt, '', 'style="width: 100%"; ' . $this->submitBtnDisabled);
        $inputs .= wf_HiddenInput(self::ROUTE_FINOPS_ACTS, 'true');

        if ($editAction) {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_EDIT, 'true');
            $inputs .= wf_HiddenInput(self::ROUTE_EDIT_REC_ID, $finopID);
        } else {
            $inputs .= wf_HiddenInput(self::ROUTE_ACTION_CREATE, 'true');
        }

        if ($modal and ! empty($modalWinID)) {
            $inputs .= wf_HiddenInput('', $modalWinID, '', self::MISC_CLASS_MWID_CTRL);
        }

        $inputs = wf_Form(self::URL_ME . '&' . self::URL_FINOPERATIONS . '=true', 'POST', $inputs, 'glamour form-grid-6cols form-grid-6cols-label-right ' . $formClass);

        if ($editAction and $this->fileStorageEnabled) {
            $this->fileStorage->setItemid(self::URL_FINOPERATIONS . $finopID);

            $inputs .= wf_tag('span', false, '', $ctrlsLblStyle);
            $inputs .= wf_tag('h3');
            $inputs .= __('Uploaded files');
            $inputs .= wf_tag('h3', true);
            $inputs .= $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32', '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_FINOPERATIONS . '=true'), true);
            $inputs .= wf_tag('span', true);
        }

        if ($modal and ! empty($modalWinID)) {
            $inputs = wf_modalAutoForm($formCapt, $inputs, $modalWinID, $modalWinBodyID, true);
        }

        return ($inputs);
    }

    /**
     * Renders JQDT for external counterparty finance operations list
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @param string $detailsFilter
     * @param bool $stdJSForCRUDs
     *
     * @return string
     */
    public function finopsRenderJQDT($customJSCode = '', $markRowForID = '', $detailsFilter = '', $stdJSForCRUDs = true) {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_FINOPS_JSON . '=true' . $detailsFilter;

        $colTargets1 = '[1, 2]';
        $colTargets2 = '[14, 15]';
        $totalsCols  = ($this->ecInvoicesON ? array(9, 10) : array(8, 9));

        $columns[] = __('ID');
        $columns[] = __('Counterparty');
        $columns[] = __('Contract');
        $columns[] = __('Address');

        if ($this->ecInvoicesON) {
            $columns[] = __('Invoice');
            $colTargets1 = '[1, 2, 3]';
            $colTargets2 = '[15, 16]';
        }

        $columns[] = __('Leading financial operation');
        $columns[] = __('Operation purpose');
        $columns[] = __('Operation date');
        $columns[] = __('Edit date');
        $columns[] = __('Accrual sum');
        $columns[] = __('Payment sum');     //9
        $columns[] = __('Needed to pay on date');
        $columns[] = __('Ingoing');
        $columns[] = __('Outgoing');
        $columns[] = __('Payment notes');
        $columns[] = __('Uploaded files');
        $columns[] = __('Actions');

        $opts = '
            "order": [[ 0, "desc" ]],
            "columnDefs": [ {"targets": ' . $colTargets1 . ', "className": "dt-left dt-head-center"},
                            {"targets": ["_all"], "className": "dt-center dt-head-center"},
                            {"targets": ' . $colTargets2 . ', "orderable": false},
                            {"targets": ' . $colTargets2 . ', "width": "85px"}
                          ]                                      
            ';

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, $stdJSForCRUDs, $customJSCode, $markRowForID,
                                        self::URL_ME . '&' . self::URL_EXTCONTRAS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID,
                                      self::MISC_MARKROW_URL, true, $totalsCols, $this->currencyStr);
        return($result);
    }

    /**
     * Renders JSON for finance operations JQDT
     *
     * @param string $whereRaw
     */
    public function finopsRenderListJSON($whereRaw = '') {
        if (!empty($whereRaw)) {
            $this->dbECMoney->whereRaw($whereRaw);
        }

//$this->dbECMoney->setDebug(true, true);
        $this->loadDataFromTableCached(self::TABLE_ECMONEY, self::TABLE_ECMONEY, !empty($whereRaw), true, '', '', !empty($whereRaw));
        $json = new wf_JqDtHelper();

        if (!empty($this->allECMoney)) {
            $data = array();

            foreach ($this->allECMoney as $eachRecID) {
                foreach ($eachRecID as $fieldName => $fieldVal) {
                    if ($fieldName == self::DBFLD_MONEY_PROFILEID) {
                        $data[] = (empty($this->allECProfiles[$fieldVal]) ? '' : $this->allECProfiles[$fieldVal][self::DBFLD_PROFILE_EDRPO] . ' '
                                . $this->allECProfiles[$fieldVal][self::DBFLD_PROFILE_NAME]
                                );
                    } elseif ($fieldName == self::DBFLD_MONEY_CNTRCTID) {
                        $data[] = (empty($this->allECContracts[$fieldVal]) ? '' : $this->allECContracts[$fieldVal][self::DBFLD_CTRCT_CONTRACT] . ' '
                                . $this->allECContracts[$fieldVal][self::DBFLD_CTRCT_FULLSUM]
                                );
                    } elseif ($fieldName == self::DBFLD_MONEY_ADDRESSID) {
                        $data[] = (empty($this->allECAddresses[$fieldVal]) ? '' : $this->allECAddresses[$fieldVal][self::DBFLD_ADDRESS_ADDR] . ' '
                                . $this->allECAddresses[$fieldVal][self::DBFLD_ADDRESS_SUM]
                                );
                    } elseif ($fieldName == self::DBFLD_MONEY_INVOICEID) {
                        if ($this->ecInvoicesON) {
                            $data[] = (empty($this->allECInvoices[$fieldVal]) ? '' : $this->allECInvoices[$fieldVal][self::DBFLD_INVOICES_INVOICE_NUM]
                                    . $this->allECInvoices[$fieldVal][self::DBFLD_INVOICES_DATE]
                                    . $this->allECInvoices[$fieldVal][self::DBFLD_INVOICES_SUM]);
                        }
                    } elseif ($fieldName == self::DBFLD_MONEY_ACCRUALID) {
                        $data[] = (empty($this->allECMoney[$fieldVal]) ? '' : $this->allECMoney[$fieldVal][self::DBFLD_MONEY_PURPOSE]
                                . $this->allECMoney[$fieldVal][self::DBFLD_MONEY_SMACCRUAL]
                                . $this->allECMoney[$fieldVal][self::DBFLD_MONEY_DATE]);
                    } elseif ($fieldName == self::DBFLD_MONEY_INCOMING or $fieldName == self::DBFLD_MONEY_OUTGOING) {
                        $data[] = (empty($fieldVal) ? web_red_led() : web_green_led());
                    } else {
                        $data[] = $fieldVal;
                    }
                }

                if ($this->fileStorage) {
                    $this->fileStorage->setItemid(self::URL_FINOPERATIONS . $eachRecID[self::DBFLD_COMMON_ID]);
                    $data[] = $this->fileStorage->renderFilesPreview(true, '', 'ubButton', '32', '&callback=' . base64_encode(self::URL_ME . '&' . self::URL_FINOPERATIONS . '=true'), true);
                } else {
                    $data[] = __('Filestorage') . ' ' . __('Disabled');
                }

                $actions = $this->getStdJQDTActions($eachRecID[self::DBFLD_COMMON_ID], self::ROUTE_FINOPS_ACTS, true);
                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns expired payments filter webform
     *
     * @return string
     */
    public function missedPaymsFilterWebForm() {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_MISSPAYMS_JSON . '=true';

        $result = '';

        $result .= wf_Link('#', __('Unpaid'), false, 'ubButton', 'id="MissPaymsUnpayedFilter"') . wf_nbsp(4);
        $result .= wf_Link('#', __('Paid'), false, 'ubButton', 'id="MissPaymsPayedFilter"') . wf_nbsp(4);
        $result .= wf_Link('#', __('All'), false, 'ubButton', 'id="MissPaymsFilterAll"');
        $result = wf_Plate($result, '', '', 'glamour') . wf_CleanDiv();

        $tmpJS = wf_jsAjaxCustomFunc('doMisspaymsFilter', '', '$(\'body\').find(\'[id ^= "jqdt_"][role = "grid"]\').attr("id")', '', 'POST', true);
        $tmpJS .= '
            $(\'#MissPaymsUnpayedFilter\').click(function(evt) {
                doMisspaymsFilter(\'' . $ajaxURL . '\', \'&' . self::MISC_WEBFILTER_MISSPAYMS . '=ISNULL(`' . self::DBFLD_MISSPAYMS_DATE_PAYED . '`)\');
                evt.preventDefault();
                return false;
            });
            
            $(\'#MissPaymsPayedFilter\').click(function(evt) {
                doMisspaymsFilter(\'' . $ajaxURL . '\', \'&' . self::MISC_WEBFILTER_MISSPAYMS . '=NOT ISNULL(`' . self::DBFLD_MISSPAYMS_DATE_PAYED . '`)\');
                evt.preventDefault();
                return false;
            });
            
            $(\'#MissPaymsFilterAll\').click(function(evt) {
                doMisspaymsFilter(\'' . $ajaxURL . '\', \'&' . self::MISC_WEBFILTER_MISSPAYMS . '=NOT ISNULL(`' . self::DBFLD_MISSPAYMS_CONTRASID . '`)\');
                evt.preventDefault();
                return false;
            });    
            
        ';

        $result .= wf_EncloseWithJSTags($tmpJS);
        return ($result);
    }

    /**
     * Renders JQDT for overdue payments list
     *
     * @param string $customJSCode
     * @param string $markRowForID
     * @param string $detailsFilter
     * @param bool   $stdJSForCRUDs
     *
     * @return string
     */
    public function missedPaymsRenderJQDT($customJSCode = '', $markRowForID = '', $detailsFilter = '', $stdJSForCRUDs = true) {
        $ajaxURL = '' . self::URL_ME . '&' . self::ROUTE_MISSPAYMS_JSON . '=true';

        $columns[] = __('ID');
        $columns[] = __('Counterparty');
        $columns[] = __('Contract');
        $columns[] = __('Contract subject');
        $columns[] = __('Address');     //4
        $columns[] = __('Period');      //5
        $columns[] = __('Payday');
        $columns[] = __('Payment sum');     //7
        $columns[] = __('Needed to pay on date');
        $columns[] = __('Expired date');    //9
        $columns[] = __('Payed date');
        $columns[] = __('Actions');
        $columns[] = __('Add' . ' ' . self::MISC_FORMS_CAPTS_FINOPS_LIST);  //12
        $columns[] = __('Already payed');

        $opts = '
            "columnDefs": [ 
                            {"targets": [13], "visible": false},                     
                            {"targets": [1, 3, 4], "className": "dt-left dt-head-center"},
                            {"targets": ["_all"], "className": "dt-center dt-head-center"},
                            {"targets": [12], "width": "85px"},
                            {"targets": [12], "orderable": false}                                                        
                          ],
            "order": [[ 1, "desc" ]],
            "rowCallback": function(row, data, index) {                               
                if ( data[13] == "1" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->payedThisMonthBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->payedThisMonthFRGND . '\');
                } 
                
                if ( data[13] == "0" ) {
                    $(\'td\', row).css(\'background-color\', \'' . $this->paymentExpiredBKGND . '\');
                    $(\'td\', row).css(\'color\', \'' . $this->paymentExpiredFRGND . '\');
                } 
            }
            
            ';

        $result = $this->getStdJQDTWithJSForCRUDs($ajaxURL, $columns, $opts, $stdJSForCRUDs, $customJSCode, $markRowForID, self::URL_ME . '&' . self::URL_EXTCONTRAS . '=true&' . self::MISC_MARKROW_URL . '=' . $markRowForID, self::MISC_MARKROW_URL, true, array(7), $this->currencyStr);
        return($result);
    }

    /**
     * Renders JSON for overdue payments JQDT
     *
     * @param string $whereRaw
     */
    public function missedPaymsRenderListJSON($whereRaw = '') {
        if (!empty($whereRaw)) {
            $this->dbECMissedPayms->whereRaw($whereRaw);
        }

        $this->loadDataFromTableCached(self::TABLE_ECMISSPAYMENTS, self::TABLE_ECMISSPAYMENTS, !empty($whereRaw), true, '', '', !empty($whereRaw));

        $this->loadExtContrasExtenData();
        $json = new wf_JqDtHelper();

        if (!empty($this->allExtContrasExten) and ! empty($this->allECMissedPayms)) {
            $data = array();

            foreach ($this->allECMissedPayms as $eachRecID) {
                $curRecID = $eachRecID[self::DBFLD_COMMON_ID];
                $contrasRecID = $eachRecID[self::DBFLD_MISSPAYMS_CONTRASID];
                $profileRecID = $eachRecID[self::DBFLD_MISSPAYMS_PROFILEID];
                $contractRecID = $eachRecID[self::DBFLD_MISSPAYMS_CONTRACTID];
                $periodRecID = $eachRecID[self::DBFLD_MISSPAYMS_PERIOD_ID];
                $addrRecID = $eachRecID[self::DBFLD_MISSPAYMS_ADDRESSID];
                $payDay = $eachRecID[self::DBFLD_MISSPAYMS_PAYDAY];
                $datePayment = $eachRecID[self::DBFLD_MISSPAYMS_DATE_PAYMENT];
                $dateExpired = $eachRecID[self::DBFLD_MISSPAYMS_DATE_EXPIRED];
                $datePayed = $eachRecID[self::DBFLD_MISSPAYMS_DATE_PAYED];
                $sumPayment = $eachRecID[self::DBFLD_MISSPAYMS_SUMPAYMENT];
                $alreadyPayed = !empty($datePayed);

                if (!empty($this->allExtContrasExten[$contrasRecID])) {
                    $extenData = $this->allExtContrasExten[$contrasRecID];

                    $counterparty = $extenData[self::TABLE_ECPROFILES . self::DBFLD_PROFILE_NAME];
                    $contractNum = $extenData[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_CONTRACT];
                    $contractSbj = $extenData[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_SUBJECT];
                    $contractSum = $extenData[self::TABLE_ECCONTRACTS . self::DBFLD_CTRCT_FULLSUM];
                    $address = $extenData[self::TABLE_ECADDRESS . self::DBFLD_ADDRESS_ADDR];
                    $addressSum = $extenData[self::TABLE_ECADDRESS . self::DBFLD_ADDRESS_SUM];
                    $periodName = $extenData[self::TABLE_ECPERIODS . self::DBFLD_PERIOD_NAME];
                } else {
                    $counterparty = '';
                    $contractNum = '';
                    $contractSbj = '';
                    $contractSum = '';
                    $address = '';
                    $addressSum = '';
                    $periodName = '';
                }

                $data[] = $curRecID;
                $data[] = wf_Link(self::URL_ME . '&' . self::URL_DICTPROFILES . '=true'
                        . '&' . self::MISC_MARKROW_URL . '=' . $profileRecID, $counterparty);
                $data[] = wf_Link(self::URL_ME . '&' . self::URL_DICTCONTRACTS . '=true'
                        . '&' . self::MISC_MARKROW_URL . '=' . $contractRecID, $contractNum);
                $data[] = $contractSbj;
                $data[] = wf_Link(self::URL_ME . '&' . self::URL_DICTADDRESS . '=true'
                        . '&' . self::MISC_MARKROW_URL . '=' . $addrRecID, $address);
                $data[] = $periodName;
                $data[] = $payDay;
                $data[] = $sumPayment;
                $data[] = $datePayment;
                $data[] = $dateExpired;
                $data[] = $datePayed;

                $actions = $this->getStdJQDTActions($curRecID, self::ROUTE_MISSPAYMS_ACTS, false, '', false);
                $data[] = $actions;

                $data[] = wf_jsAjaxDynamicWindowButton(self::URL_ME, array(self::ROUTE_FINOPS_ACTS => 'true',
                    self::ROUTE_ACTION_PREFILL => 'true',
                    self::MISC_PREFILL_DATA => array(self::CTRL_MONEY_PROFILEID => $profileRecID,
                        self::CTRL_MONEY_CNTRCTID => $contractRecID,
                        self::CTRL_MONEY_ADDRESSID => $addrRecID,
                        self::CTRL_MONEY_SUMPAYMENT => $sumPayment,
                        self::MISC_MISSED_PAYMENT_PROCESSING => 'true',
                        self::MISC_MISSED_PAYMENT_ID => $curRecID
                    )
                        ), '', web_add_icon(), '', 'POST', 'click', false, false, true, '$(this).closest("table").parent().children().find(\'[id ^= "jqdt_"][role = "grid"]\').last().attr("id")'
                );

                $data[] = ($alreadyPayed ? 1 : 0);

                $json->addRow($data);

                unset($data);
            }
        }

        $json->getJson();
    }
}