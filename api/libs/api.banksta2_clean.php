<?php
class Banksta2 {
    /**
     * UbillingConfig object placeholder
     *
     * @var null
     */
    protected $ubConfig = null;

    /**
     * Billing API object placeholder
     *
     * @var null
     */
    protected $billing = null;

    /**
     * System message helper object placeholder
     *
     * @var null
     */
    protected $messages = null;

    /**
     * List of allowed extensions
     *
     * @var array
     */
    protected $allowedExtensions = array("txt", "csv", "dbf", "xls", "xlsx");

    /**
     * Field mapping presets represented as [preset_id] => array[] of options
     *
     * @var array
     */
    protected $fieldsMappingPresets = array();

    /**
     * Default payment ID to push Internet banksta payments
     *
     * @var int
     */
    protected $inetPaymentId = 1;

    /**
     * Default payment ID to push UKV banksta payments
     *
     * @var int
     */
    protected $ukvPaymentId = 2;

    /**
     * Service types placeholder
     *
     * @var array
     */
    protected $bankstaServiceType = array();


    /**
     * Already preprocessed banksta records from BANKSTA2_TABLE
     *
     * @var array
     */
    protected $bankstaRecordsAll = array();

    /**
     * Contains available Inet users data as login => userdata
     *
     * @var array
     */
    protected $allUsersDataInet = array();

    /**
     * Contains available UKV users data as login => userdata
     *
     * @var array
     */
    protected $allUsersDataUKV = array();

    /**
     * Contains available Inet users contracts mappings as contract => login
     *
     * @var array
     */
    protected $allContractsInet = array();

    /**
     * Contains available UKV users contracts mappings as contract => login
     *
     * @var array
     */
    protected $allContractsUKV = array();

    /**
     * Contains available UKV tariffs mappings as id => data
     *
     * @var array
     */
    protected $ukvTariffs = array();

    /**
     * Contains detected Internet/UKV users as contract => login / contract => id
     * for displaying during statement processing
     *
     * @var array
     */
    protected $bankstaFoundUsers = array();

    /**
     * Placeholder for file data preprocessed during filePreprocessing()
     *
     * @var array
     */
    public $preprocessedFileData = array();

    /**
     * Placeholder mapper for BANKSTA2_PRESETS_TABLE and web_PreprocessingForm
     *
     * @var array
     */
    public $dbPresetsFlds2PreprocForm = array(
                                                'col_realname'          => 'bsrealname_col',
                                                'col_address'           => 'bsaddress_col',
                                                'col_paysum'            => 'bspaysum_col',
                                                'col_paypurpose'        => 'bspaypurpose_col',
                                                'col_paydate'           => 'bspaydate_col',
                                                'col_paytime'           => 'bspaytime_col',
                                                'col_contract'          => 'bscontract_col',
                                                'guess_contract'        => 'bstryguesscontract',
                                                'contract_delim_start'  => 'bscontractdelimstart',
                                                'contract_delim_end'    => 'bscontractdelimend',
                                                'contract_min_len'      => 'bscontractminlen',
                                                'contract_max_len'      => 'bscontractmaxlen',
                                                'service_type'          => 'bssrvtype',
                                                'inet_srv_start_delim'  => 'bsinetdelimstart',
                                                'inet_srv_end_delim'    => 'bsinetdelimend',
                                                'inet_srv_keywords'     => 'bsinetkeywords',
                                                'ukv_srv_start_delim'   => 'bsukvdelimstart',
                                                'ukv_srv_end_delim'     => 'bsukvdelimend',
                                                'ukv_srv_keywords'      => 'bsukvkeywords',
                                                'skip_row'              => 'bsskiprow',
                                                'col_skiprow'           => 'bsskiprow_col',
                                                'skip_row_keywords'     => 'bsskiprowkeywords',
                                                'replace_strs'          => 'bsreplacestrs',
                                                'col_replace_strs'      => 'bscolsreplacestrs',
                                                'strs_to_replace'       => 'bsstrstoreplace'
                                             );


    /**
     * Default storage table name
     */
    const BANKSTA2_TABLE = 'banksta2';
    const BANKSTA2_PRESETS_TABLE = 'banksta2_presets';

    /**
     * Routing URLs
     */
    const URL_ME = '?module=banksta2';
    //const URL_BANKSTA_MGMT = '?module=bankstamd&banksta=true';
    const URL_BANKSTA2_UPLOADFORM = '?module=banksta2&uploadform=true';
    const URL_BANKSTA2_PROCESSING = '?module=banksta2&showhash=';
    const URL_BANKSTA2_DETAILED = '?module=banksta2&showdetailed=';
    const URL_BANKSTA2_FIELD_MAPPING = '?module=banksta2&fieldmapping=true';
    const URL_BANKSTA2_PRESETS = '?module=banksta2&presets=true';
    const URL_BANKSTA2_PROCEED_STMT_IMP = '?module=banksta2&proceedstatementimport=true';
    const URL_BANKSTA2_BANKSTALIST = '?module=banksta2&bankstalist=true';
    const URL_USERS_PROFILE_INET = '?module=userprofile&username=';
    const URL_USERS_PROFILE_UKV = '?module=ukv&users=true&showuser=';

    /**
     * Some banksta options
     */
    const BANKSTA2_PATH = 'content/documents/banksta2/';


    public function __construct() {
        global $ubillingConfig, $billing;
        $this->ubConfig=$ubillingConfig;
        $this->billing=$billing;
        $this->initMessages();
        $this->loadOptions();
        $this->loadUserDataInet();
        $this->loadUserDataUKV();
        $this->loadUKVTariffs();
        $this->loadProcessedBankstaRecs();
        $this->loadMappingPresets();
        $this->bankstaServiceType = array('Internet' => __('Internet'),
                                          'UKV' => __('UKV'),
                                          'Telepathy' => __('Telepathy')
                                         );
    }

    /**
     * Inits message helper object for further usage
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Returns reference to UbillingMessageHelper object
     *
     * @return object
     */
    public function getUbMsgHelperInstance() {
        return $this->messages;
    }

    /**
     * Getting an alter.ini options
     *
     * @return void
     */
    protected function loadOptions() {
        $this->inetPaymentId = $this->ubConfig->getAlterParam('BANKSTA2_PAYMENTID_INET');
        $this->ukvPaymentId = $this->ubConfig->getAlterParam('BANKSTA2_PAYMENTID_UKV');
    }


    /**
     * Loads all available Internet users data from database
     *
     * @return void
     */
    protected function loadUserDataInet() {
        $this->allUsersDataInet = zb_UserGetAllData();

        if (!empty($this->allUsersDataInet)) {
            foreach ($this->allUsersDataInet as $io => $each) {
                if (!empty($each['contract'])) {
                    $this->allContractsInet[$each['contract']] = $each['login'];
                }
            }
        }
    }

    /**
     * Loads all available UKV users data from database
     *
     * @return void
     */
    protected function loadUserDataUKV() {
        $tQuery = "SELECT * from `ukv_users`";
        $allUsers = simple_queryall($tQuery);

        if (!empty($allUsers)) {
            foreach ($allUsers as $io => $each) {
                $this->allUsersDataUKV[$each['id']] = $each;
                $this->allContractsUKV[$each['contract']] = $each['id'];
            }
        }
    }

    /**
     * Loads UKV tariffs into private tariffs prop
     *
     * @return void
     */
    protected function loadUKVTariffs() {
        $tQuery = "SELECT * from `ukv_tariffs` ORDER by `tariffname` ASC;";
        $allTariffs = simple_queryall($tQuery);
        if (!empty($allTariffs)) {
            foreach ($allTariffs as $io => $each) {
                $this->ukvTariffs[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads all of banksta rows to further checks to private property
     *
     * @return void
     */
    protected function loadProcessedBankstaRecs() {
        $tQuery = "SELECT * FROM `" . self::BANKSTA2_TABLE . "`";
        $tQueryResult = simple_queryall($tQuery);

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $io => $eachRec) {
                $this->bankstaRecordsAll[$eachRec['id']] = $eachRec;
            }
        }
    }

    /**
     * Load fields mapping presets (FMPs)
     *
     * @return void
     */
    public function loadMappingPresets() {
        $tQuery = "SELECT * FROM `" . self::BANKSTA2_PRESETS_TABLE . "`";
        $tQueryResult = simple_queryall($tQuery);

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $eachRec) {
                $this->fieldsMappingPresets[$eachRec['id']] = $eachRec;
            }
        }
    }

    /**
     * Fields mapping presets placeholder getter
     *
     * @return array
     */
    public function getMappingPresets() {
        return ($this->fieldsMappingPresets);
    }

    /**
     * Returns fields mapping presets in JSON representation
     *
     * @param $fmpID
     * @param array $arrayToRemap
     *
     * @return array|false|string
     */
    public function getFMPDataJSON($fmpID, $arrayToRemap = array()) {
        $result = array();

        if (isset($this->fieldsMappingPresets[$fmpID]) and !empty($this->fieldsMappingPresets[$fmpID])) {
            $fmpData = $this->fieldsMappingPresets[$fmpID];

            if (empty($arrayToRemap)) {
                $result = json_encode($fmpData);
            } else {
                foreach ($fmpData as $eachField => $eachValue) {
                    $remappedFieldName = (isset($arrayToRemap[$eachField]) and !empty($arrayToRemap[$eachField])) ? $arrayToRemap[$eachField] : $eachField;
                    $result[$remappedFieldName] = $eachValue;
                }

                $result = json_encode($result);
            }
        }

        return ($result);
    }

    /**
     * Returns an HTML-code string containing selector control
     *
     * @param string $selectorID
     * @param string $selectorClass
     * @param bool $inContainer
     * @param string $title
     * @param bool $insBR
     * @param bool $insRefreshButton
     *
     * @return string
     */
    public function getMappingPresetsSelector($selectorID = '', $selectorClass = '', $inContainer = false, $title = '', $insBR = false, $insRefreshButton = false) {
        $labelTitle = (empty($title)) ? __('Choose fields mapping preset') : $title;
        $ctrlID = (empty($selectorID)) ? 'BankstaPresetsSelector' : $selectorID;
        $ctrlClass = (empty($selectorClass)) ? '__BankstaPresetsSelector' : $selectorClass;
        $selectorContent = array('' => '-');
        $result = '';
        $refresh_button = '';

        if ($insRefreshButton) {
            $refresh_button.= wf_tag('span', false, 'ubButton', 'id="refresh_' . $ctrlID . '" title="' . __('Refresh selector data') . '" style="cursor: pointer; vertical-align: sub; padding: 2px 8px !important"');
            $refresh_button.= wf_img('skins/refresh.gif');
            $refresh_button.= wf_tag('span', true);
            $refresh_button.= wf_nbsp(1);
        }

        foreach ($this->fieldsMappingPresets as $eachPreset) {
            $selectorContent[$eachPreset['id']] = $eachPreset['presetname'];
        }

        $result.= wf_Selector('bspresets', $selectorContent, $labelTitle, '', $insBR, true, $ctrlID, $ctrlClass);

        if ($inContainer) {
            $result = wf_tag('span', false, '', 'id="container_' . $ctrlID . '"') . $result;
            $result.= wf_tag('span', true);
        }

        $result = $refresh_button . $result;

        return ($result);
    }

    /**
     * Returns array with certain banksta record content
     *
     * @param $recID
     *
     * @return array|mixed
     */
    public function getBankstaRecDetails($recID) {
        $result = array();

        if (isset($this->bankstaRecordsAll[$recID])) {
            $result = $this->bankstaRecordsAll[$recID];
        }

        return ($result);
    }

    /**
     * Marks banksta record as processed
     *
     * @param $bankstaRecID
     *
     * @return void
     */
    public function setBankstaRecProcessed($bankstaRecID) {
        if (isset($this->bankstaRecordsAll[$bankstaRecID])) {
            simple_update_field(self::BANKSTA2_TABLE, 'processed', 1, "WHERE `id`='" . $bankstaRecID . "';");
            //log_register('BANKSTA2 [' . $bankstaRecID . '] SET AS PROCESSED');
        } else {
            log_register('BANKSTA2 NONEXISTENT [' . $bankstaRecID . '] RECORD SETTING PROCESSED TRY');
        }
    }

    /**
     * Marks banksta record as canceled
     *
     * @param $bankstaRecID
     *
     * @return void
     */
    public function setBankstaRecCanceled($bankstaRecID) {
        if (isset($this->bankstaRecordsAll[$bankstaRecID])) {
            simple_update_field(self::BANKSTA2_TABLE, 'processed', 1, "WHERE `id`='" . $bankstaRecID . "';");
            simple_update_field(self::BANKSTA2_TABLE, 'canceled', 1, "WHERE `id`='" . $bankstaRecID . "';");
            log_register('BANKSTA2 [' . $bankstaRecID . '] SET AS CANCELED');
        } else {
            log_register('BANKSTA2 NONEXISTENT [' . $bankstaRecID . '] RECORD SETTING CANCELED TRY');
        }
    }

    /**
     * Marks banksta record as uncanceled
     *
     * @param $bankstaRecID
     *
     * @return void
     */
    public function setBankstaRecUnCanceled($bankstaRecID) {
        if (isset($this->bankstaRecordsAll[$bankstaRecID])) {
            simple_update_field(self::BANKSTA2_TABLE, 'processed', 0, "WHERE `id`='" . $bankstaRecID . "';");
            simple_update_field(self::BANKSTA2_TABLE, 'canceled', 0, "WHERE `id`='" . $bankstaRecID . "';");
            log_register('BANKSTA2 [' . $bankstaRecID . '] SET AS UNCANCELED');
        } else {
            log_register('BANKSTA2 NONEXISTENT [' . $bankstaRecID . '] RECORD SETTING UNCANCELED TRY');
        }
    }

    /**
     * Changes contract for some banksta record
     *
     * @param $bankstaRecID
     * @param $contract
     *
     * @return void
     */
    public function setBankstaRecContract($bankstaRecID, $contract) {
        $contract = mysql_real_escape_string($contract);
        $contract = trim($contract);

        if (isset($this->bankstaRecordsAll[$bankstaRecID])) {
            $oldContract = $this->bankstaRecordsAll[$bankstaRecID]['contract'];
            simple_update_field(self::BANKSTA2_TABLE, 'contract', $contract, "WHERE `id`='" . $bankstaRecID . "';");
            log_register('BANKSTA2 [' . $bankstaRecID . '] CONTRACT `' . $oldContract . '` CHANGED TO `' . $contract . '`');
        } else {
            log_register('BANKSTA2 NONEXISTENT [' . $bankstaRecID . '] CONTRACT CHANGE TRY');
        }
    }

    /**
     * Changes service type for some banksta record
     *
     * @param $bankstaRecID
     * @param $srvType
     *
     * @return void
     */
    public function setBankstaRecSrvType($bankstaRecID, $srvType) {
        $srvType = mysql_real_escape_string($srvType);
        $srvType = trim($srvType);

        if (isset($this->bankstaRecordsAll[$bankstaRecID])) {
            $oldSrvType = $this->bankstaRecordsAll[$bankstaRecID]['service_type'];
            simple_update_field(self::BANKSTA2_TABLE, 'service_type', $srvType, "WHERE `id`='" . $bankstaRecID . "';");
            log_register('BANKSTA2 [' . $bankstaRecID . '] RECORD SERVICE TYPE `' . $oldSrvType . '` CHANGED TO `' . $srvType . '`');
        } else {
            log_register('BANKSTA2 NONEXISTENT [' . $bankstaRecID . '] RECORD SERVICE TYPE CHANGE TRY');
        }
    }


    /**
     * Upload statement file
     *
     * @return array|bool
     */
    public function uploadFile() {
        $result = false;
        $extCheck = true;

        if ($_FILES['uploadbnksta2']['error'] == 4) {
            log_register('BANKSTA2 NO FILE WAS SELECTED');
            show_error(__('No file was selected'));
            return ($result);
        }

        //check file extension against $allowedExtensions
        foreach ($_FILES as $file) {
            if ($file['tmp_name'] > '') {
                if (@!in_array(end(explode(".", strtolower($file['name']))), $this->allowedExtensions)) {
                    $extCheck = false;
                }
            }
        }

        if ($extCheck) {
            $fileName = vf($_FILES['uploadbnksta2']['name']);
            $uploadFile = self::BANKSTA2_PATH . $fileName;

            if (move_uploaded_file($_FILES['uploadbnksta2']['tmp_name'], $uploadFile)) {
                $fileContent = file_get_contents(self::BANKSTA2_PATH . $fileName);
                $fileHash = md5($fileContent);
                $fileContent = ''; //free some memory

                if (!$this->checkHashExists($fileHash)) {
                    $result = array(
                                    'filename' => $_FILES['uploadbnksta2']['name'],
                                    'savedname' => $fileName,
                                    'hash' => $fileHash
                                   );
                } else {
                    log_register('BANKSTA2 DUPLICATE TRY ' . $fileHash);
                    show_error(__('Same bank statement already exists'));
                }
            } else {
                show_error(__('Cant upload file to') . ' ' . self::BANKSTA2_PATH);
            }
        } else {
            show_error(__('Wrong file type'));
            log_register('BANKSTA2 WRONG FILETYPE');
        }

        return ($result);
    }

    /**
     * checks if banksta hash exists?
     *
     * @param string $hash  bank statement raw content hash
     *
     * @return bool
     */
    protected function checkHashExists($hash) {
        $query = "SELECT `id` FROM `" . self::BANKSTA2_TABLE . "` WHERE `hash`='" . $hash . "'";
        $data = simple_query($query);
        if (empty($data)) {
            return (false);
        } else {
            return (true);
        }
    }

    /**
     * Returns true if field mapping preset with such name already exists
     *
     * @param $fmpName
     * @param int $excludeEditedFMPId
     *
     * @return string
     */
    public function checkFMPNameExists($fmpName, $excludeEditedFMPId = 0) {
        $fmpName = trim($fmpName);

        if (empty($excludeEditedFMPId)) {
            $tQuery = "SELECT `id` FROM `" . self::BANKSTA2_PRESETS_TABLE . "` WHERE `presetname` = '" . $fmpName . "'";
        } else {
            $tQuery = "SELECT `id` FROM `" . self::BANKSTA2_PRESETS_TABLE . "` WHERE `presetname` = '" . $fmpName . "' AND `id` != " . $excludeEditedFMPId;
        }

        $tQueryResult = simple_queryall($tQuery);

        return ( empty($tQueryResult) ) ? '' : $tQueryResult[0]['id'];
    }

    /**
     * Adds new fields mapping preset to DB
     *
     * @param $fmpName
     * @param int $fmpColRealName
     * @param int $fmpColAddr
     * @param int $fmpColPaySum
     * @param int $fmpColPayPurpose
     * @param int $fmpColPayDate
     * @param int $fmpColPayTime
     * @param int $fmpColContract
     * @param int $fmpGuessContract
     * @param string $fmpContractDelimStart
     * @param string $fmpContractDelimEnd
     * @param string $fmpSrvType
     * @param string $fmpInetStartDelim
     * @param string $fmpInetEndDelim
     * @param string $fmpInetKeywords
     * @param string $fmpUKVDelimStart
     * @param string $fmpUKVDelimEnd
     * @param string $fmpUKVKeywords
     */
    public function addFieldsMappingPreset($fmpName, $fmpColRealName = 0, $fmpColAddr = 0, $fmpColPaySum = 0, $fmpColPayPurpose = 0,
                                           $fmpColPayDate = 0, $fmpColPayTime = 0, $fmpColContract = 0, $fmpGuessContract = 0,
                                           $fmpContractDelimStart = '', $fmpContractDelimEnd = '', $fmpContractMinLen = 0, $fmpContractMaxLen = 0,
                                           $fmpSrvType = '', $fmpInetStartDelim = '', $fmpInetEndDelim = '', $fmpInetKeywords = '',
                                           $fmpUKVDelimStart = '', $fmpUKVDelimEnd = '', $fmpUKVKeywords = '',
                                           $fmpSkipRow = 0, $fmpColSkipRow = '', $fmpSkipRowKeywords = '',
                                           $fmpReplaceStrs = 0, $fmpColReplaceStrs = '', $fmpStrsToReplace = ''
    ) {
        $tQuery = "INSERT INTO `" . self::BANKSTA2_PRESETS_TABLE .
                  "` (`presetname`, `col_realname`, `col_address`, `col_paysum`, `col_paypurpose`, `col_paydate`, 
                            `col_paytime`, `col_contract`, `guess_contract`, `contract_delim_start`, `contract_delim_end`, 
                            `contract_min_len`, `contract_max_len`, `service_type`, `inet_srv_start_delim`, `inet_srv_end_delim`, `inet_srv_keywords`, 
                            `ukv_srv_start_delim`, `ukv_srv_end_delim`, `ukv_srv_keywords`, `skip_row`, `col_skiprow`, `skip_row_keywords`,
                            `replace_strs`, `col_replace_strs`, `strs_to_replace`) 
                        VALUES ('" . $fmpName . "', '" . $fmpColRealName . "', '" . $fmpColAddr . "', '" . $fmpColPaySum . "', '" .
                  $fmpColPayPurpose . "', '" . $fmpColPayDate . "', '" . $fmpColPayTime . "', '" . $fmpColContract . "', " .
                  $fmpGuessContract . ", '" . $fmpContractDelimStart . "', '" . $fmpContractDelimEnd . "', " .
                  $fmpContractMinLen . ", " . $fmpContractMaxLen . ", '" . $fmpSrvType . "', '" .
                  $fmpInetStartDelim . "', '" . $fmpInetEndDelim . "', '" . $fmpInetKeywords . "', '" .
                  $fmpUKVDelimStart . "', '" . $fmpUKVDelimEnd . "', '" . $fmpUKVKeywords . "', '" .
                  $fmpSkipRow  . "', '" . $fmpColSkipRow  . "', '" . $fmpSkipRowKeywords . "', '" .
                  $fmpReplaceStrs . "', '" . $fmpColReplaceStrs . "', '" . $fmpStrsToReplace . "')";

        nr_query($tQuery);
        log_register('CREATE banksta2 fields mapping preset [' . $fmpName . ']');
    }


    public function editFieldsMappingPreset($fmpID, $fmpName, $fmpColRealName = 0, $fmpColAddr = 0, $fmpColPaySum = 0, $fmpColPayPurpose = 0,
                                            $fmpColPayDate = 0, $fmpColPayTime = 0, $fmpColContract = 0, $fmpGuessContract = 0,
                                            $fmpContractDelimStart = '', $fmpContractDelimEnd = '', $fmpContractMinLen = 0, $fmpContractMaxLen = 0,
                                            $fmpSrvType = '', $fmpInetStartDelim = '', $fmpInetEndDelim = '', $fmpInetKeywords = '',
                                            $fmpUKVDelimStart = '', $fmpUKVDelimEnd = '', $fmpUKVKeywords = '',
                                            $fmpSkipRow = 0, $fmpColSkipRow = '', $fmpSkipRowKeywords = '',
                                            $fmpReplaceStrs = 0, $fmpColReplaceStrs = '', $fmpStrsToReplace = ''
    ) {
        $tQuery = "UPDATE `" . self::BANKSTA2_PRESETS_TABLE . "` SET 
                            `presetname`            = '" . $fmpName . "', 
                            `col_realname`          = '" . $fmpColRealName . "',  
                            `col_address`           = '" . $fmpColAddr . "', 
                            `col_paysum`            = '" . $fmpColPaySum . "', 
                            `col_paypurpose`        = '" . $fmpColPayPurpose . "', 
                            `col_paydate`           = '" . $fmpColPayDate . "',  
                            `col_paytime`           = '" . $fmpColPayTime . "',  
                            `col_contract`          = '" . $fmpColContract . "',  
                            `guess_contract`        = " . $fmpGuessContract . ",  
                            `contract_delim_start`  = '" . $fmpContractDelimStart . "', 
                            `contract_delim_end`    = '" . $fmpContractDelimEnd . "',
                            `contract_min_len`      = " . $fmpContractMinLen . ",
                            `contract_max_len`      = " . $fmpContractMaxLen . ", 
                            `service_type`          = '" . $fmpSrvType . "',  
                            `inet_srv_start_delim`  = '" . $fmpInetStartDelim . "', 
                            `inet_srv_end_delim`    = '" . $fmpInetEndDelim . "', 
                            `inet_srv_keywords`     = '" . $fmpInetKeywords . "', 
                            `ukv_srv_start_delim`   = '" . $fmpUKVDelimStart . "',  
                            `ukv_srv_end_delim`     = '" . $fmpUKVDelimEnd . "', 
                            `ukv_srv_keywords`      = '" . $fmpUKVKeywords . "',
                            `skip_row`              = '" . $fmpSkipRow . "',
                            `col_skiprow`           = '" . $fmpColSkipRow . "',                            
                            `skip_row_keywords`     = '" . $fmpSkipRowKeywords . "',
                            `replace_strs`         = '" . $fmpReplaceStrs . "',
                            `col_replace_strs`     = '" . $fmpColReplaceStrs . "',
                            `strs_to_replace`       = '" . $fmpStrsToReplace . "'
                        WHERE `id` = " . $fmpID;

        nr_query($tQuery);
        log_register('CHANGE banksta2 fields mapping preset [' . $fmpName . ']');
    }

    /**
     * Deletes fields mapping preset
     *
     * @param $fmpId
     * @param string $fmpName
     *
     * @return void
     */
    public function deleteFieldsMappingPreset($fmpId, $fmpName = '') {
        $tQuery = "DELETE FROM `" . self::BANKSTA2_PRESETS_TABLE . "` WHERE `id` = '" . $fmpId . "'";
        nr_query($tQuery);
        log_register('DELETE banksta2 fields mapping preset [' . $fmpId . '] ` ' . $fmpName);
    }

    /**
     * Deletes uploaded bank statement
     *
     * @param $statementHash
     * @param string $fileName
     *
     * @return void
     */
    public function deleteBankStatement($statementHash, $fileName = '') {
        $tQuery = "DELETE FROM `" . self::BANKSTA2_TABLE . "` WHERE `hash` = '" . $statementHash . "'";
        nr_query($tQuery);
        log_register('DELETE banksta2 statement [' . $statementHash . '] ` ' . $fileName);
    }

    /**
     * Uploaded file preprocessing
     *
     * @param $filename
     * @param $delimiter
     * @param $encoding
     * @param bool $useDBFColNames
     *
     * @return string
     */
    public function preprocessImportFile($filename, $delimiter, $encoding, $useDBFColNames = false, $skipRowsCount = 0) {
        $dataParsed = array();
        $colNamesArr = array();
        $colNamesTypesArr = array();
        $codePage = $encoding;
        $filePath = self::BANKSTA2_PATH . $filename;
        $fileExt = @end(explode(".", strtolower($filename)));
        $errorMessage = '';
        $rowsSkipped = 0;

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
        });

        try {
            // dbf/csv/xlsx differentiation goes here
            switch ($fileExt) {
                case ('dbf'):
                    $dbfTab = new dbf_class($filePath);
                    $dbfTabRecCount = $dbfTab->dbf_num_rec;

                    if ($useDBFColNames) {
                        foreach ($dbfTab->dbf_names as $item => $eachCol) {
                            $colNamesArr[] = $eachCol['name'];
                            $colNamesTypesArr[$eachCol['name']] = $eachCol['type'];
                        }

                        if (!empty($colNamesArr)) {
                            $dataParsed[] = $colNamesArr;
                        }
                    }

                    for ($i = 0; $i < $dbfTabRecCount; $i++) {
                        $eachRow = $dbfTab->getRow($i);

                        if (!empty($eachRow)) {
                            $dataParsed[] = @array_map(function ($row) use ($codePage) {
                                                return (iconv($codePage, 'utf-8', $row));
                                            }, $eachRow);
                        }
                    }
                    break;

                case ('csv'):
                case ('txt'):
                    $dataRaw = file_get_contents($filePath);

                    if ($encoding != 'utf-8') {
                        $dataRaw = iconv($encoding, 'utf-8', $dataRaw);
                    }

                    $dataRaw = explodeRows($dataRaw);

                    if (!empty($dataRaw)) {
                        foreach ($dataRaw as $eachrow) {
                            if ($rowsSkipped != $skipRowsCount) {
                                $rowsSkipped++;
                                continue;
                            }

                            if (!empty($eachrow)) {
                                $tmpArray = explode($delimiter, $eachrow);

                                // shitty hacky tricky check...
                                if (!empty($tmpArray) and count($tmpArray) > 1) {
                                    $dataParsed[] = $tmpArray;
                                }
                            }
                        }
                    }
                    break;

                case ('xls'):
                case ('xlsx'):
                    require_once('api/vendor/excel/excel_reader2.php');
                    require_once('api/vendor/excel/SpreadsheetReader.php');
                    $excelReader = new SpreadsheetReader($filePath);

                    foreach ($excelReader as $eachRow) {
                        if ($rowsSkipped != $skipRowsCount) {
                            $rowsSkipped++;
                            continue;
                        }

                        if (!empty($eachRow) and count($eachRow) > 1) {
                            $dataParsed[] = $eachRow;
                        }
                    }
                    break;
            }
        } catch(Exception $e) {
            $errorMessage = __('File parsing error') . '. ' . __('If you tried to import .DBF file - make sure it\'s version is dBaseIII/IV and you\'ve selected a proper codepage.') .
                            ' ' . wf_delimiter(0) . __('Error message') . ':  ' .
                            $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
        }

        restore_error_handler();
        $this->preprocessedFileData = $dataParsed;
        return($errorMessage);
    }

    /**
     * Bank statement preprocessing and last checks form building
     *
     * @param $statementRawData
     * @param $importOpts
     * @param bool $skipLastChecksForm
     *
     * @return string
     */
    public function preprocessBStatement($statementRawData, $importOpts, $skipLastChecksForm = false) {
        $statementRawData = unserialize(base64_decode($statementRawData));

        $contractGuess   = $importOpts['guess_contract'];
        $contractDelimS  = (empty($importOpts['contract_delim_start'])) ? '' : preg_quote($importOpts['contract_delim_start'], '/');
        $contractDelimE  = (empty($importOpts['contract_delim_end'])) ? '' : preg_quote($importOpts['contract_delim_end'], '/');
        $contactMinLen   = $importOpts['contract_min_len'];
        $contactMaxLen   = $importOpts['contract_max_len'];
        $serviceType     = $importOpts['service_type'];
        $inetSrvDelimS   = (empty($importOpts['inet_srv_start_delim'])) ? '' : preg_quote($importOpts['inet_srv_start_delim'], '/');
        $inetSrvDelimE   = (empty($importOpts['inet_srv_end_delim'])) ? '' : preg_quote($importOpts['inet_srv_end_delim'], '/');
        $inetSrvKeywords = (empty($importOpts['inet_srv_keywords'])) ? '' : preg_quote($importOpts['inet_srv_keywords'], '/');
        $ukvSrvDelimS    = (empty($importOpts['ukv_srv_start_delim'])) ? '' : preg_quote($importOpts['ukv_srv_start_delim'], '/');
        $ukvSrvDelimE    = (empty($importOpts['ukv_srv_end_delim'])) ? '' : preg_quote($importOpts['ukv_srv_end_delim'], '/');
        $ukvSrvKeywords  = (empty($importOpts['ukv_srv_keywords'])) ? '' : preg_quote($importOpts['ukv_srv_keywords'], '/');
        $skipRow         = $importOpts['skip_row'];
        $skipRowCols     = ($importOpts['col_skiprow'] !== 'NONE') ? explode(',', str_replace(' ', '', $importOpts['col_skiprow'])) : array();
        $skipRowKeywords = (empty($importOpts['skip_row_keywords'])) ? '' : preg_quote($importOpts['skip_row_keywords'], '/');
        $strsReplace     = $importOpts['replace_strs'];
        $strsReplaceCols = ($importOpts['col_replace_strs'] !== 'NONE') ? explode(',', str_replace(' ', '', $importOpts['col_replace_strs'])) : array();
        $strsReplaceChars = (empty($importOpts['strs_to_replace'])) ? '' : explode($importOpts['strs_to_replace'], '/');

        $i = 0;
        $rows = '';
        $statementData = array();

        foreach ($statementRawData as $eachRow) {
            if (empty($eachRow)) { continue; }

            $i++;
            $cells = wf_TableCell($i);
            $cancelRow = 0;

            if ($strsReplace and !empty($strsReplaceCols) and !empty($strsReplaceChars)) {
                foreach ($strsReplaceCols as $strsReplaceCol) {
                    if (isset($eachRow[$strsReplaceCol])) {
                        $eachRow[$strsReplaceCol] = str_replace($strsReplaceChars, '', $eachRow[$strsReplaceCol]);
                    }
                }
            }

            $realname = ($importOpts['col_realname'] !== 'NONE' and isset($eachRow[$importOpts['col_realname']])) ? $eachRow[$importOpts['col_realname']] : '';
            $address  = ($importOpts['col_address'] !== 'NONE' and isset($eachRow[$importOpts['col_address']])) ? $eachRow[$importOpts['col_address']] : '';
            $notes    = ($importOpts['col_paypurpose'] !== 'NONE' and isset($eachRow[$importOpts['col_paypurpose']])) ? $eachRow[$importOpts['col_paypurpose']] : '';
            $ptime    = ($importOpts['col_paytime'] !== 'NONE' and isset($eachRow[$importOpts['col_paytime']])) ? $eachRow[$importOpts['col_paytime']] : '';
            $summ     = (isset($eachRow[$importOpts['col_paysum']])) ? $eachRow[$importOpts['col_paysum']] : '';
            $pdate    = (isset($eachRow[$importOpts['col_paydate']])) ? $eachRow[$importOpts['col_paydate']] : '';
            $contract = ($importOpts['col_contract'] !== 'NONE' and isset($eachRow[$importOpts['col_contract']])) ? $eachRow[$importOpts['col_contract']] : '';
            $service_type   = $serviceType;
            $srvTypeMatched = false;

            if (!empty($notes)) {
                if (empty($contract)) {
                    // if contract guessing enabled and at least one of the delimiters is not empty
                    if ($contractGuess) {
                        if (empty($contactMinLen) or empty($contactMaxLen)) {
                            if ($contractDelimS != '' or $contractDelimE != '') {
                                //$contractDelimS = '(' . $contractDelimS . ')';
                                //$contractDelimE = '(' . $contractDelimE . ')';
                                //preg_match('/' . $contractDelimS . '(\D)*?\d{' . $contactMinLen . ',' . $contactMaxLen . '}(\D)*?' . $contractDelimE . '/msu', $notes, $matchResult);
                                //} else {
                                preg_match('/' . $contractDelimS . '(.*?)' . $contractDelimE . '/msu', $notes, $matchResult);
                            }

                            if (isset($matchResult[1])) {
                                $contract = trim($matchResult[1]);
                            } else {
                                $contract = 'unknown_' . $i;
                            }
                        } else {
                            preg_match('/(\D)(\d{' . $contactMinLen . ',' . $contactMaxLen . '})(\D)/msu', $notes, $matchResult);

                            if (isset($matchResult[2])) {
                                $contract = trim($matchResult[2]);
                            } else {
                                $contract = 'unknown_' . $i;
                            }
                        }
                    } else {
                        $contract = 'unknown_' . $i;
                    }
                }

                if (strtolower($serviceType) == 'telepathy') {
                    $keywordsArray = array();
                    $keywordsStr = '';

                    // trying to check for Inet service keywords
                    if (!empty($inetSrvKeywords)) {
                        $keywordsArray = explode(',', $inetSrvKeywords);

                        foreach ($keywordsArray as $keyWord) {
                            $keywordsStr .= trim($keyWord) . '|';
                        }

                        $keywordsStr = rtrim($keywordsStr, '|');

                        if ($inetSrvDelimS == '' and $inetSrvDelimE == '') {
                            $betweenDelimStr = $notes;
                        } else {
                            preg_match('/' . $inetSrvDelimS . '(.*?)' . $inetSrvDelimE . '/msiu', strtolower($notes), $matchResult);

                            if (isset($matchResult[1])) {
                                $betweenDelimStr = trim($matchResult[1]);
                            } else {
                                $betweenDelimStr = $notes;
                            }
                        }

                        if (!empty($keywordsStr)) {
                            preg_match('/(' . $keywordsStr . ')/msiu', $betweenDelimStr, $matchResult);

                            if (isset($matchResult[1])) {
                                $service_type = 'Internet';
                                $srvTypeMatched = true;
                            }
                        }
                    }

                    $keywordsArray = array();
                    $keywordsStr = '';

                    // trying to check for UKV service keywords
                    if (!$srvTypeMatched and !empty($ukvSrvKeywords)) {
                        $keywordsArray = explode(',', $ukvSrvKeywords);

                        foreach ($keywordsArray as $keyWord) {
                            $keywordsStr .= trim($keyWord) . '|';
                        }

                        $keywordsStr = rtrim($keywordsStr, '|');

                        if ($ukvSrvDelimS == '' and $ukvSrvDelimE == '') {
                            $betweenDelimStr = $notes;
                        } else {
                            preg_match('/' . $ukvSrvDelimS . '(.*?)' . $ukvSrvDelimE . '/msiu', $notes, $matchResult);

                            if (isset($matchResult[1])) {
                                $betweenDelimStr = trim($matchResult[1]);
                            } else {
                                $betweenDelimStr = $notes;
                            }
                        }

                        if (!empty($keywordsStr)) {
                            preg_match('/(' . $keywordsStr . ')/msiu', $betweenDelimStr, $matchResult);

                            if (isset($matchResult[1])) {
                                $service_type = 'UKV';
                            }
                        }
                    }
                }
            } else {
                if (empty($contract)) { $contract = 'unknown_' . $i; }
            }

            $keywordsArray = array();
            $keywordsStr = '';

            // skipping rows
            if ($skipRow and !empty($skipRowCols)) {
                foreach ($skipRowCols as $skipRowCol) {
                    if (!empty($eachRow[$skipRowCol])) {
                        //!empty($skipRowContent)
                        $skipRowContent = $eachRow[$skipRowCol];

                        $keywordsArray = explode(',', $skipRowKeywords);

                        foreach ($keywordsArray as $keyWord) {
                            $keywordsStr .= trim($keyWord) . '|';
                        }

                        $keywordsStr = rtrim($keywordsStr, '|');

                        if (!empty($keywordsStr)) {
                            preg_match('/(' . $keywordsStr . ')/msiu', $skipRowContent, $matchResult);

                            if (isset($matchResult[1])) {
                                $cancelRow = 1;
                                break;
                            }
                        }
                    }
                }
            }


            // filling statement array for further processing
            $tArrayIndex = wf_InputId() . wf_InputId();

            $statementData[$tArrayIndex]['contract'] = $contract;
            $statementData[$tArrayIndex]['summ'] = $summ;
            $statementData[$tArrayIndex]['address'] = $address;
            $statementData[$tArrayIndex]['realname'] = $realname;
            $statementData[$tArrayIndex]['notes'] = $notes;
            $statementData[$tArrayIndex]['pdate'] = $pdate;
            $statementData[$tArrayIndex]['ptime'] = $ptime;
            $statementData[$tArrayIndex]['service_type'] = $service_type;
            $statementData[$tArrayIndex]['row_canceled'] = $cancelRow;

            if (!$skipLastChecksForm) {
                $cells.= wf_TableCell($contract);
                $cells.= wf_TableCell($summ);
                $cells.= wf_TableCell($address);
                $cells.= wf_TableCell($realname);
                $cells.= wf_TableCell($notes);
                $cells.= wf_TableCell($pdate);
                $cells.= wf_TableCell($ptime);
                $cells.= wf_TableCell($service_type);
                $cells.= wf_TableCell($cancelRow);

                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        zb_StorageSet('BANKSTA2_STATEMENT_DATA', base64_encode(serialize($statementData)));

        return ($rows);
    }

    /**
     * Bank statement rows processing and adding to DB
     *
     * @param $statementData
     * @param $statementFileData
     *
     * @return void
     */
    public function processBankStatement($statementData, $statementFileData) {
        if (!empty($statementData)) {
            $importCounter = 0;
            $newAdmin = whoami();
            $newHash = $statementFileData['hash'];
            $newFilename = $statementFileData['filename'];

            foreach ($statementData as $eachContract => $eachRow) {
                if (!empty($eachRow)) {
                    $newDate = date("Y-m-d H:i:s");
                    //@$newContract = trim($eachContract);
                    @$newContract = trim($eachRow['contract']);
                    $newContract = mysql_real_escape_string($newContract);
                    @$newSumm = trim($eachRow['summ']);
                    $newSumm = mysql_real_escape_string($newSumm);
                    $newSumm = str_replace(array(' ', ','), array('', '.'), $newSumm);
                    @$newAddress = mysql_real_escape_string($eachRow['address']);
                    @$newRealname = mysql_real_escape_string($eachRow['realname']);
                    $newNotes = mysql_real_escape_string($eachRow['notes']);
                    $newPdate = mysql_real_escape_string($eachRow['pdate']);
                    $newPtime = mysql_real_escape_string($eachRow['ptime']);
                    $newSrvType = mysql_real_escape_string($eachRow['service_type']);
                    $newCancelRow = $eachRow['row_canceled'];

                    //pushing row into database
                    if ((!empty($newPdate)) AND (!empty($newSumm))) {
                        $this->createPaymentRec($newDate, $newHash, $newFilename, $newAdmin, $newContract, $newSumm, $newAddress, $newRealname, $newNotes, $newPdate, $newPtime, $newSrvType, $newCancelRow);
                        $importCounter++;
                    }
                }
            }

            zb_StorageDelete('BANKSTA2_STATEMENT_DATA');
            zb_StorageDelete('BANKSTA2_RAWDATA');
            log_register('BANKSTA2 IMPORTED ' . $importCounter . ' ROWS FROM ' . $statementFileData['savedname']);
        } else {
            show_error(__('Can not process empty bank statement'));
        }
    }

    /**
     * Push bank statement payments for users that have been found
     *
     * @param $paymentsToPush
     *
     * @return void
     */
    public function pushStatementPayments($paymentsToPush, $refiscalize = false) {
        $paymentsToPush = unserialize(base64_decode($paymentsToPush));
        $checkForCorpUsers = $this->ubConfig->getAlterParam('USER_LINKING_ENABLED');
        $dreamkasEnabled = $this->ubConfig->getAlterParam('DREAMKAS_ENABLED');
        $needToFiscalize = false;
        $fiscalDataArray = array();

        if ($dreamkasEnabled and wf_CheckPost(array('bankstapaymentsfiscalize'))) {
            $needToFiscalize = true;
            $fiscalDataArray = json_decode(base64_decode($_POST['bankstapaymentsfiscalize']), true);
            $DreamKas = new DreamKas();

            if ($refiscalize) {
                $paymentsToPush = array();
                $bs2RecIDs = implode(',', array_keys($fiscalDataArray));
                $tQuery = "(SELECT `contracts`.`login` AS `userlogin`, `" . self::BANKSTA2_TABLE . "`.`id`, `summ`, `payid`, `service_type` AS `service` 
                                  FROM `" . self::BANKSTA2_TABLE . "` 
                                    RIGHT JOIN `contracts` ON `" . self::BANKSTA2_TABLE . "`.`contract` = `contracts`.`contract` 
                                                              AND `" . self::BANKSTA2_TABLE . "`.`service_type` = 'Internet'
                                  WHERE `" . self::BANKSTA2_TABLE . "`.`id` IN (" . $bs2RecIDs . "))
                               UNION 
                               (SELECT `ukv_users`.`id` AS `userlogin`, `" . self::BANKSTA2_TABLE . "`.`id`, `summ`, `payid`, `service_type` AS `service` 
                                  FROM `" . self::BANKSTA2_TABLE . "` 
                                    RIGHT JOIN `ukv_users` ON `" . self::BANKSTA2_TABLE . "`.`contract` = `ukv_users`.`contract` 
                                                              AND `" . self::BANKSTA2_TABLE . "`.`service_type` = 'UKV'
                                  WHERE `" . self::BANKSTA2_TABLE . "`.`id` IN (" . $bs2RecIDs . "))";

                $tQueryResult = simple_queryall($tQuery);

                if (!empty($tQueryResult)) {
                    foreach ($tQueryResult as $eachRec) {
                        $paymentsToPush[$eachRec['id']] = $eachRec;
                    }
                }
            }
        }

        if (!empty($paymentsToPush)) {
            $ukv = new UkvSystem();
            $allParentUsers = ($checkForCorpUsers and !$refiscalize) ? cu_GetAllParentUsers() : array();

            foreach ($paymentsToPush as $eachRecID => $eachRec) {
                $paymentSuccessful = false;
                $userLogin = $eachRec['userlogin'];
                $paySumm = $eachRec['summ'];

                if (!$refiscalize) {
                    if ($this->checkBankstaRowIsUnprocessed($eachRecID)) {
                        $cashType = $eachRec['payid'];
                        $operation = 'add';
                        $paymentNote = 'BANKSTA2: [' . $eachRecID . '] ASCONTRACT ' . $eachRec['usercontract'];

                        if (zb_checkMoney($paySumm)) {
                            if (strtolower($eachRec['service']) == 'internet') {
                                // inet service payment processing
                                if (!empty($userLogin)) {
                                    if (isset($this->allUsersDataInet[$userLogin])) {
                                        // check for corporate user possibility
                                        if ($checkForCorpUsers and !empty($allParentUsers[$eachRec['userlogin']])) {
                                            //corporate user
                                            $userLink = $allParentUsers[$eachRec['userlogin']];
                                            $allChildUsers = cu_GetAllChildUsers($userLink);

                                            // adding natural payment to parent user
                                            zb_CashAdd($userLogin, $paySumm, $operation, $cashType, $paymentNote);

                                            if (!empty($allChildUsers)) {
                                                foreach ($allChildUsers as $eachChild) {
                                                    //adding quiet payments for child users
                                                    $this->billing->addcash($eachChild, $paySumm);
                                                    log_register("BANKSTA2 GROUPBALANCE " . $eachChild . " " . $operation . " ON " . $paySumm);
                                                }
                                            }
                                        } else {
                                            // ordinary user processing
                                            zb_CashAdd($userLogin, $paySumm, $operation, $cashType, $paymentNote);
                                        }

                                        $this->setBankstaRecProcessed($eachRecID);
                                        $paymentSuccessful = true;
                                    } else {
                                        log_register('BANKSTA2 [' . $eachRecID . '] FAIL LOGIN (' . $userLogin . ') CONTRACT (' . $eachRec['usercontract'] . ')');
                                    }
                                } else {
                                    log_register('BANKSTA2 [' . $eachRecID . '] FAIL EMPTY LOGIN');
                                }
                            } else {
                                // UKV service payment processing
                                $ukv->userAddCash($userLogin, $paySumm, 1, $cashType, $paymentNote);
                                $this->setBankstaRecProcessed($eachRecID);
                                $paymentSuccessful = true;
                            }
                        } else {
                            log_register('BANKSTA2 FAILED: payment record ID: [' . $eachRecID . '] for service: [' . $eachRec['service'] . '] for login: [' . $userLogin . '] and contract: [' . $eachRec['usercontract'] . '] ' . __('Wrong format of a sum of money to pay'));
                        }
                    } else {
                        $this->setBankstaRecProcessed($eachRecID);
                        log_register('BANKSTA2 DUPLICATE PAYMENT PUSH TRY FOR REC ID: [' . $eachRecID . ']');
                    }
                }

                // dreamkas fiscalization routine
                if ($needToFiscalize and isset($fiscalDataArray[$eachRecID]) and ($paymentSuccessful or $refiscalize)) {
                    $curRecFiscalData = $fiscalDataArray[$eachRecID];

                    $cashMachineID = $curRecFiscalData['drscashmachineid'];
                    $taxType = $curRecFiscalData['drstaxtype'];
                    $paymentType = $curRecFiscalData['drspaymtype'];

                    if (strtolower($eachRec['service']) == 'internet') {
                        $userMobile = zb_UserGetMobile($userLogin);
                        $userEmail = zb_UserGetEmail($userLogin);
                    } else {
                        $userData = $ukv->getUserData($userLogin);
                        $userMobile = (empty($userData)) ? '' : $userData['mobile'];
                        $userEmail = '';
                    }

                    $sellPosIDsPrices = array($curRecFiscalData['drssellingpos'] => array('price' => ($paySumm * 100)));
                    $userContacts = array('email' => $userEmail, 'phone' => $userMobile);

                    $preparedCheckJSON = $DreamKas->prepareCheckFiscalData($cashMachineID, $taxType, $paymentType, $sellPosIDsPrices, $userContacts);
                    $DreamKas->fiscalizeCheck($preparedCheckJSON, $eachRecID);
                    $lastDKError = $DreamKas->getLastErrorMessage();

                    if (!empty($lastDKError)) {
                        log_register('BANKSTA2 FAILED: payment record ID: [' . $eachRecID . '] fiscalization for service: [' . $eachRec['service'] . '] for login: [' . $userLogin . '] and contract: [' . $eachRec['usercontract'] . ']. Error message: ' . $lastDKError);
                    }
                }
            }
        }
    }

    /**
     * Creates a row in BANKSTA2_TABLE with essential payment data
     *
     * @param $newDate
     * @param $newHash
     * @param $newFilename
     * @param $newAdmin
     * @param $newContract
     * @param $newSumm
     * @param $newAddress
     * @param $newRealname
     * @param $newNotes
     * @param $newPdate
     * @param $newPtime
     * @param $newSrvType
     *
     * @return void
     */
    protected function createPaymentRec($newDate, $newHash, $newFilename, $newAdmin, $newContract, $newSumm,
                                        $newAddress, $newRealname, $newNotes, $newPdate, $newPtime, $newSrvType, $newCancelRow) {
        $newPaymentID = (strtolower($newSrvType) == 'internet') ? $this->inetPaymentId : $this->ukvPaymentId;

        $tQuery = "INSERT INTO `" . self::BANKSTA2_TABLE . "` (`date`, `hash`, `filename`, `admin`, `contract`, `summ`, `address`, 
                                                              `realname`, `notes`, `pdate`, `ptime`, `processed`, `canceled`, `service_type`, `payid`) 
                              VALUES (  '" . $newDate . "',
                                        '" . $newHash . "',
                                        '" . $newFilename . "',
                                        '" . $newAdmin . "',
                                        '" . $newContract . "',
                                        '" . $newSumm . "',
                                        '" . $newAddress . "',
                                        '" . $newRealname . "',
                                        '" . $newNotes . "',
                                        '" . $newPdate . "',
                                        '" . $newPtime . "',
                                        '" . $newCancelRow . "',
                                        '" . $newCancelRow . "',
                                        '" . $newSrvType . "',
                                        '" . $newPaymentID . "'
                                     )";
        nr_query($tQuery);

    }

    /**
     * Checks banksta row by it's ID if it is unprocessed
     *
     * @param int $bankstaid   existing banksta row ID
     *
     * @return bool
     */
    protected function checkBankstaRowIsUnprocessed($bankstaid) {
        $result = false;

        if (isset($this->bankstaRecordsAll[$bankstaid])) {
            if ($this->bankstaRecordsAll[$bankstaid]['processed'] == 0) {
                $result = true;
            } else {
                $result = false;
            }
        }

        return ($result);
    }


    public function checkStatementIsUnprocessed($bankstaHash) {
        $tQuery = "SELECT `id` FROM `" . self::BANKSTA2_TABLE . "` WHERE `hash` = '" . $bankstaHash . "' AND `processed` < 1 and `canceled` < 1";
        $tQueryResult = simple_queryall($tQuery);


    }

    /**
     * Returns main buttons controls for banksta2
     *
     * @return string
     */
    public function web_MainButtonsControls() {
        $controls = wf_Link(self::URL_BANKSTA2_BANKSTALIST, wf_img('skins/menuicons/receipt_small_compl.png') . wf_nbsp() . __('Uploaded bank statements'), false, 'ubButton') . wf_nbsp(2);
        $controls.= wf_Link(self::URL_BANKSTA2_UPLOADFORM, wf_img('skins/menuicons/receipt_small.png') . wf_nbsp() . __('Upload bank statement'), false, 'ubButton') . wf_nbsp(2);
        $controls.= wf_Link(self::URL_BANKSTA2_PRESETS, wf_img('skins/icon_note.gif') . wf_nbsp() . __('Fields mapping presets'), false, 'ubButton');

        return ($controls);
    }


    /**
     * Returns file selection and upload form
     *
     * @return string
     */
    public function web_FileUploadForm() {
        $delimiters = array(';'=>';',
            '|'=>'|',
            ','=>','
        );

        $encodings = array('utf-8'=>'utf-8',
            'windows-1251'=>'windows-1251',
            'koi8-u'=>'koi8-u',
            'cp866'=>'cp866'
        );

        $uploadinputs = wf_HiddenInput('bankstatementuploaded','true');
        $uploadinputs.= __('Upload bank statement') . wf_nbsp(2) . ' <input id="fileselector" type="file" name="uploadbnksta2" size="10" /><br>';
        $uploadinputs.= wf_delimiter(0);
        $uploadinputs.= wf_tag('div', false, '', 'style="border: 1px solid #ddd; border-radius: 4px; padding: 4px"');
        $uploadinputs.= wf_Selector('delimiter', $delimiters, __('Delimiter') . ' (' . __('this setting does not have any effect on') . ' .DBF/.XLS/.XLSX)', '', true);
        $uploadinputs.= wf_delimiter(0);
        $uploadinputs.= wf_Selector('encoding', $encodings, __('Encoding') . ' (' . __('this setting does not have any effect on') . ' .XLS/.XLSX)', '', true);
        $uploadinputs.= wf_delimiter(0);
        $uploadinputs.= wf_TextInput('skiprowscount', __('Skip specified numbers of rows from the beginning of .CSV/.TXT/.XLS/.XLSX file (if those rows are empty, or contain fields captions, or whatever)'), 0, true, '4', 'digits');
        $uploadinputs.= wf_delimiter(0);
        $uploadinputs.= wf_CheckInput('usedbfcolnames', __('Use .DBF column names instead of record values in mapping visualizer'), true, false);
        $uploadinputs.= wf_tag('div', true);
        $uploadinputs.= wf_delimiter(0);
        $uploadinputs.= wf_Submit('Upload');
        $uploadform = bs_UploadFormBody(self::URL_BANKSTA2_FIELD_MAPPING, 'POST', $uploadinputs, 'glamour');

        return ($uploadform);
    }

    /**
     * Renders statement preprocessing form with fields mapping
     *
     * @param $dataParsed
     */
    public function web_FieldsMappingForm($dataParsed) {
        $dataParsed = unserialize(base64_decode($dataParsed));

        // actual fileds mapping form assembling
        if (sizeof($dataParsed) > 1) {
            $colCount = sizeof($dataParsed[0]);

            $cells = wf_TableCell(__('Column number'));
            $cells.= wf_TableCell(__('Column content'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($dataParsed[0] as $colNum => $colData) {
                $cells = wf_TableCell($colNum);
                $cells.= wf_TableCell($colData);
                $rows.= wf_TableRow($cells, 'row3');

            }

            $firstRow = wf_TableBody($rows, '100%', '0', '');
            show_window(__('Found count of data columns'),$colCount);
            show_window(__('First of imported data rows'), $firstRow);

            //construct of data processing form
            $rowNumArr = array();
            for ($i = 0; $i < $colCount; $i++) {
                $rowNumArr[$i] = $i;
            }

            $bsrealname_arr     = $rowNumArr + array('NONE' => __('Set to none'));
            $bsaddress_arr      = $rowNumArr + array('NONE' => __('Set to none'));
            $bspaysum_arr       = $rowNumArr;
            $bspaypurpose_arr   = $rowNumArr + array('NONE' => __('Set to none'));
            $bspaydate_arr      = $rowNumArr;
            $bspaytime_arr      = $rowNumArr + array('NONE' => __('Set to none'));
            $bscontract_arr     = $rowNumArr + array('NONE' => __('Set to none'));

            //save preset form
            $errorModalWindowId = wf_InputId();
            $presetsSelectorId = wf_InputId();
            $presetsSelectorClass = '__' . wf_InputId();
            $savePresetInputs = wf_TextInput('fmpname', __('Preset name'), '', true, '', '', '__FMPEmptyCheck');
            $savePresetInputs.= wf_HiddenInput('fmpcreate', 'true');
            $savePresetInputs.= wf_Submit(__('Create'));

            $savePresetForm = wf_Form(self::URL_ME . '&fmpquickadd=true', 'POST', $savePresetInputs, 'glamour __FMPQuickSave');
            $savePresetForm = wf_modalAuto(wf_img_sized('skins/save-as.png', '', '16') . wf_nbsp(1) . __('Save current mapping as preset'), __('Save current mapping as preset'), $savePresetForm, 'ubButton');
            $savePresetForm.= wf_delimiter(1);
            $savePresetForm.= $this->getMappingPresetsSelector($presetsSelectorId, $presetsSelectorClass, true, '', true, true);
            $savePresetForm.= wf_delimiter(0);

            //data column setting form
            $inputs = wf_Selector('bsrealname_col', $bsrealname_arr, __('User realname'), '0', true);
            $inputs.= wf_Selector('bsaddress_col', $bsaddress_arr, __('User address'), '1', true);
            $inputs.= wf_Selector('bspaysum_col', $bspaysum_arr, __('Payment sum'), '2', true);
            $inputs.= wf_Selector('bspaypurpose_col', $bspaypurpose_arr, __('Payment purpose'), '3', true);
            $inputs.= wf_Selector('bspaydate_col', $bspaydate_arr, __('Payment date'), '4', true);
            $inputs.= wf_Selector('bspaytime_col', $bspaytime_arr, __('Payment time'), '5', true);

            $inputs.= wf_delimiter(0);
            $inputs.= wf_Selector('bscontract_col', $bscontract_arr, __('User contract') . ' (' . __('Payment ID') . ')', '6', true);
            $inputs.= wf_CheckInput('bstryguesscontract', __('Try to get contract from payment purpose field'), true, false, 'BankstaTryGuessContract');
            $inputs.= wf_tag('h4', false, '', 'style="font-weight: 400; width: 700px; padding: 2px 0 8px 28px; color: #666; margin-block-end: 0; margin-block-start: 0;"');
            $inputs.= __('ONLY, if mapped contract field for some row will be empty or if contract field will be not specified');
            $inputs.= wf_tag('h4', true);
            $inputs.= wf_tag('div', false, '', 'id="BankstaContractGuessingBlock" style="border: 1px solid #ddd; border-radius: 4px; padding: 4px"');
            $inputs.= __('If it is possible that there are users contracts between start/end delimiters in payment purpose field of the bank statement - they will be extracted');
            $inputs.= wf_delimiter(0);
            $inputs.= wf_TextInput('bscontractdelimstart', __('Contract') . ' (' . __('Payment ID') . '): ' . __('start delimiter string'), '', true, '', '', '', 'BankstaContractDelimStart');
            $inputs.= wf_TextInput('bscontractdelimend', __('Contract') . ' (' . __('Payment ID') . '): ' . __('end delimiter string'), '', true, '', '', '', 'BankstaContractDelimEnd');
            $inputs.= wf_tag('h4', false, '', 'style="font-weight: 400; width: 700px; padding: 11px 0 7px 0; color: #666; margin-block-end: 0; margin-block-start: 0;"');
            $inputs.= __('If your contracts(payment IDs) are 100% DIGITAL - you may specify their minimum and maximum length to extract them properly(delimiters will not be taken into account)');
            $inputs.= wf_tag('h4', true);
            $inputs.= wf_TextInput('bscontractminlen', __('Contract') . ' (' . __('Payment ID') . '): ' . __('min length'), '0', true, '', '', '', 'BankstaContractMinLen');
            $inputs.= wf_TextInput('bscontractmaxlen', __('Contract') . ' (' . __('Payment ID') . '): ' . __('max length'), '0', true, '', '', '', 'BankstaContractMaxLen');
            $inputs.= wf_tag('div', true);

            $inputs.= wf_delimiter(0);
            $inputs.= wf_Selector('bssrvtype', $this->bankstaServiceType, __('Service type'), '21', true, false, 'BankstaSrvType');
            $inputs.= wf_tag('div', false, '', 'id="BankstaServiceGuessingBlock" style="border: 1px solid #ddd; border-radius: 4px; padding: 4px"');
            $inputs.= wf_TextInput('bsinetdelimstart', __('Internet service before keywords delimiter string'), '', true, '', '', '', 'BankstaInetDelimStart');
            $inputs.= wf_TextInput('bsinetkeywords', __('Internet service determination keywords divided with comas'), '', true, '40', '', '', 'BankstaInetKeyWords');
            $inputs.= wf_TextInput('bsinetdelimend', __('Internet service after keywords delimiter string'), '', true, '', '', '', 'BankstaInetDelimEnd');
            $inputs.= wf_delimiter(0);
            $inputs.= wf_TextInput('bsukvdelimstart', __('UKV service before keywords delimiter string'), '', true, '', '', '', 'BankstaUKVDelimStart');
            $inputs.= wf_TextInput('bsukvkeywords', __('UKV service determination keywords divided with comas'), '', true, '40', '', '', 'BankstaUKVKeyWords');
            $inputs.= wf_TextInput('bsukvdelimend', __('UKV service after keywords delimiter string'), '', true, '', '', '', 'BankstaUKVDelimEnd');
            $inputs.= wf_tag('div', true);
            $inputs.= wf_delimiter(0);

            $inputs.= wf_CheckInput('bsskiprow', __('Skip row processing if selected field contains keywords below'), true, false, 'BankstaSkipRow');
            $inputs.= wf_tag('div', false, '', 'id="BankstaSkipRowBlock" style="border: 1px solid #ddd; border-radius: 4px; padding: 4px"');
            $inputs.= wf_Selector('bsskiprow_col', $bsrealname_arr, __('Column to check row skipping'), 'NONE', true);
            $inputs.= wf_TextInput('bsskiprowkeywords', __('Row skipping determination keywords divided with comas'), '', true, '40', '', '', 'BankstaSkipRowKeyWords');
            $inputs.= wf_tag('div', true);
            $inputs.= wf_delimiter(0);

            $inputs.= wf_CheckInput('bsskiplastcheck', __('Skip last check visualization form and import data immediately'), true);

            $inputs.= wf_tag('script', false, '', 'type="text/javascript"');
            $inputs.= wf_JSEmptyFunc();
            $inputs.= wf_JSElemInsertedCatcherFunc();
            $inputs.= ' function chekEmptyVal(ctrlClassName) {
                            $(document).on("focus keydown", ctrlClassName, function(evt) {
                                if ( $(ctrlClassName).css("border-color") == "rgb(255, 0, 0)" ) {
                                    $(ctrlClassName).val("");
                                    $(ctrlClassName).css("border-color", "");
                                    $(ctrlClassName).css("color", "");
                                }
                            });
                        }
                        
                        function dynamicDistributePresetValue(ctrlClassName) {
                            $(document).on("change", ctrlClassName, function(evt) {
                                distributePresetValue($(ctrlClassName).val());
                            });
                        }
                         
                        function distributePresetValue(presetId) {
                            $.ajax({
                                type: "POST",
                                url: "' . self::URL_ME . '",
                                data: { 
                                        getfmpdata: true,
                                        fmpid: presetId 
                                       },
                                success: function(result) {                               
                                                var tFMPData = JSON.parse(result);
                                                var tFields = Object.entries(tFMPData);
                                                
                                                tFields.forEach(function(field) {
                                                    var fieldNam = field[0];
                                                    var fieldVal = field[1];
                                                    
                                                    if ( $(\'[name=\'+fieldNam+\']\').length ) {
                                                        var ctrl = $(\'[name=\'+fieldNam+\']\');
                                                                                                        
                                                        switch(ctrl.prop("type")) { 
                                                            case "radio":
                                                            case "checkbox":   
                                                                ctrl.each(function() {
                                                                    if (fieldVal == true || fieldVal > 0) {
                                                                        $(this).attr("checked", true);
                                                                    } else {
                                                                        $(this).attr("checked", false);
                                                                    }
                                                                });   
                                                                break;  
                                                                
                                                            default:
                                                                ctrl.val(fieldVal); 
                                                        }  
                                                                                                        
                                                        ctrl.change();
                                                    }
                                                });
                                             }
                            });
                        } 
                         
                        function refreshFMPSelector() {                            
                            $.ajax({
                                 type: "GET",
                                 url: "' . self::URL_ME . '",
                                 data: { 
                                         refreshfmpselector: true,
                                         fmpselectorid: "' . $presetsSelectorId . '",
                                         fmpselectorclass: "' . $presetsSelectorClass . '" 
                                       },
                                 success: function(result) {
                                     if ( !empty(result) ) {
                                         $("label[for=\'' . $presetsSelectorId . '\']").remove();
                                         $(\'#' . $presetsSelectorId . '\').replaceWith(result);
                                     }
                                }
                            });
                        }
                                
                        $(document).on("submit", ".__FMPQuickSave", function(evt) {
                            var FrmAction        = $(".__FMPQuickSave").attr("action");
                            var FrmData          = $(".__FMPQuickSave").serialize() + \'&\' + $(".__Banksta2PreprocessingForm").serialize() + \'&errfrmid=' . $errorModalWindowId . '\';
                            evt.preventDefault();
                        
                            var emptyCheckClass = \'.__FMPEmptyCheck\';
                        
                            if ( empty( $(emptyCheckClass).val() ) || $(emptyCheckClass).css("border-color") == "rgb(255, 0, 0)" ) {                            
                                $(emptyCheckClass).css("border-color", "red");
                                $(emptyCheckClass).css("color", "grey");
                                $(emptyCheckClass).val("' . __('Mandatory field') . '");                            
                            } else {
                                $.ajax({
                                type: "POST",
                                url: FrmAction,
                                data: FrmData,                                
                                success: function(result) {
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);                                                
                                                $( \'#' . $errorModalWindowId . '\' ).dialog("open");                                                
                                            } else {                                               
                                                alert(\'' . __('Preset added successfully') . '\');                                                
                                                $(".__FMPQuickSave").parent(\'.ui-dialog-content\').dialog("close");
                                                refreshFMPSelector();
                                            }
                                        }                        
                                });
                            }
                        });
                        
                        onElementInserted(\'body\', \'.' . $presetsSelectorClass . '\', function(element) {
                            dynamicDistributePresetValue(\'.'. $presetsSelectorClass . '\');
                        });
                         
                        onElementInserted(\'body\', \'.__FMPEmptyCheck\', function(element) {
                            chekEmptyVal(\'.__FMPEmptyCheck\');
                        });
                        
                        $(\'#refresh_' . $presetsSelectorId . '\').click(function(evt) {
                            refreshFMPSelector();
                        });
                        
                        $(\'#' . $presetsSelectorId . '\').change(function(evt) {
                            distributePresetValue($(this).val());
                        });
                        
                        $(\'#BankstaTryGuessContract\').change(function () {
                            if ( $(\'#BankstaTryGuessContract\').is(\':checked\') ) {
                                $(\'#BankstaContractGuessingBlock\').show();
                            } else {
                                $(\'#BankstaContractGuessingBlock\').hide();
                            }    
                        });                         
                       
                        $(\'#BankstaSrvType\').change(function () {
                            if ( $(\'#BankstaSrvType\').val() == \'Telepathy\' ) {
                                $(\'#BankstaServiceGuessingBlock\').show();
                            } else {
                                $(\'#BankstaServiceGuessingBlock\').hide();
                            }    
                        });
                       
                       $(\'#BankstaSkipRow\').change(function () {
                            if ( $(\'#BankstaSkipRow\').is(\':checked\') ) {
                                $(\'#BankstaSkipRowBlock\').show();
                            } else {
                                $(\'#BankstaSkipRowBlock\').hide();
                            }    
                        });
                       
                        $(document).ready(function() {
                            $(\'#BankstaContractGuessingBlock\').hide();    
                            $(\'#BankstaServiceGuessingBlock\').hide(); 
                            $(\'#BankstaSkipRowBlock\').hide();
                        });                     
                      ';
            $inputs.= wf_tag('script', true);

            $inputs.=  wf_HiddenInput('import_rawdata', base64_encode(serialize($dataParsed)));
            $inputs.= wf_delimiter(0);
            $inputs.=  wf_Submit('Save this column mappings and continue payments import');

            $colForm =  wf_Form("?module=banksta2", 'POST', $inputs, 'glamour __Banksta2PreprocessingForm');
            show_window(__('Select data columns numbers and their values mapping'), $savePresetForm . $colForm);
        } else {
            show_error(__('File parsing error: data array is empty'));
        }
    }

    /**
     * Renders a "last checks form" with all the actual payments data that will be imported
     *
     * @param $dataRows
     */
    public function web_LastChecksForm($dataRows) {
        $cells = wf_TableCell('#');
        $cells.= wf_TableCell('[contract]');
        $cells.= wf_TableCell('[summ]');
        $cells.= wf_TableCell('[address]');
        $cells.= wf_TableCell('[realname]');
        $cells.= wf_TableCell('[notes]');
        $cells.= wf_TableCell('[pdate]');
        $cells.= wf_TableCell('[ptime]');
        $cells.= wf_TableCell('[service_type]');
        $cells.= wf_TableCell('[row_canceled]');

        $rows = wf_TableRow($cells, 'row1');
        $table = wf_TableBody($rows . $dataRows, '100%', '0', '');

        $inputs = wf_Link(self::URL_BANKSTA2_FIELD_MAPPING, 'No I want to try another import settings', false, 'ubButton');
        $inputs.= wf_nbsp(2);
        $inputs.= wf_Link(self::URL_BANKSTA2_PROCEED_STMT_IMP, 'Yes, proceed payments import', false, 'ubButton');

        show_window(__('All is correct') . '?', $table . wf_delimiter(1) . $inputs);
    }


    /**
     * Shows the fields mapping presets form
     *
     * @return void
     */
    public function web_FMPForm() {
        $lnkId = wf_InputId();
        $addServiceJS = wf_tag('script', false, '', 'type="text/javascript"');
        $addServiceJS.= wf_JSAjaxModalOpener(self::URL_ME, array('fmpcreate' => 'true'), $lnkId, false, 'POST');
        $addServiceJS.= wf_tag('script', true);

        show_window(__('Fields mapping presets'), wf_Link('#', web_add_icon() . ' ' .
                    __('Add fields mapping preset'), false, 'ubButton', 'id="' . $lnkId . '"') .
                    wf_delimiter() . $addServiceJS . $this->renderFMPJQDT()
                   );
    }

    /**
     * Renders a processing form for certain statement determined by hash
     *
     * @param $hash
     *
     * @return string
     */
    public function web_BSProcessingForm($hash) {
        $hash = mysql_real_escape_string($hash);

        if ($this->checkHashExists($hash)) {
            $tQuery = "SELECT * FROM `" . self::BANKSTA2_TABLE . "` WHERE `hash`='" . $hash . "' ORDER BY `id` ASC;";
            $tQueryResult = simple_queryall($tQuery);
            $cashPairs = array();
            $tServices = array('' => __('-'),
                               'Internet' => __('Internet'),
                               'UKV' => __('UKV')
                              );

            $dreamkasEnabled = $this->ubConfig->getAlterParam('DREAMKAS_ENABLED');
            $fiscalRecsIDsList = array();
            $addFiscalizePaymentCtrlsJS = false;
            $refiscalize = false;

            if ($dreamkasEnabled) {
                $DreamKas = new DreamKas();
            }

            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Statement Address'));
            $cells .= wf_TableCell(__('Statement Real Name'));
            $cells .= wf_TableCell(__('Statement Contract'));
            $cells .= wf_TableCell(__('Service type'));
            $cells .= wf_TableCell(__('Edit record'));
            $cells .= wf_TableCell(__('Payment ID'));
            $cells .= wf_TableCell(__('Cash'));
            $cells .= wf_TableCell(__('Processed'));
            $cells .= wf_TableCell(__('Canceled'));
            $cells .= wf_TableCell(__('Contract'));
            $cells .= wf_TableCell(__('Real Name'));
            $cells .= wf_TableCell(__('Address'));
            $cells .= wf_TableCell(__('Tariff'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($tQueryResult)) {
                foreach ($tQueryResult as $io => $eachRec) {
                    $recProcessed = ($eachRec['processed']) ? true : false;
                    $recCanceled = ($eachRec['canceled']) ? true : false;
                    $serviceType = trim($eachRec['service_type']);
                    $detailsWinID = wf_InputId();
                    $lnkID = wf_InputId();

                    $addInfoControl = wf_Link('#', $eachRec['id'], false, '', ' id="' . $lnkID . '" ');
                    $addInfoControl .= wf_tag('script', false, '', 'type="text/javascript"');
                    $addInfoControl .= '$(\'#' . $lnkID . '\').click(function(evt) {
                                        $.ajax({
                                            type: "GET",
                                            url: "' . self::URL_ME . '",
                                            data: { 
                                                    showdetailed: "' . $eachRec['id'] . '",
                                                    detailsWinID: "' . $detailsWinID . '"
                                                   },
                                            success: function(result) {                               
                                                        $(document.body).append(result);                                                
                                                        $( \'#' . $detailsWinID . '\' ).dialog("open"); 
                                                     }
                                        });
                                        
                                        evt.preventDefault();
                                        return false;
                                     });
                                    ';
                    $addInfoControl .= wf_tag('script', true);

                    $cells = wf_TableCell($addInfoControl, '', '', '', '', (($dreamkasEnabled) ? '2' : ''));
                    $cells .= wf_TableCell($eachRec['address']);
                    $cells .= wf_TableCell($eachRec['realname']);

                    if ($recProcessed) {
                        if ($recCanceled) {
                            $editInputs = wf_CheckInput('recallrowprocessing', __('Recall record processing'), false, false);
                            $editInputs .= wf_HiddenInput('bankstaeditrowid', $eachRec['id']);
                            $editInputs .= wf_delimiter(0);
                            $editInputs .= wf_Submit(__('Save'));
                            $editForm = wf_Form('', 'POST', $editInputs);
                        } else {
                            $editForm = __('Record processed');
                        }

                        $cells .= wf_TableCell($eachRec['contract']);
                        $cells .= wf_TableCell($serviceType);
                        $cells .= wf_TableCell($editForm);
                    } else {
                        $formID = wf_InputId();
                        $cells .= wf_TableCell(wf_TextInput('newbankstacontract', '', $eachRec['contract'], false, '6', '', '', '', 'form="' . $formID . '"'));
                        $cells .= wf_TableCell(wf_Selector('newbankstarvtype', $tServices, '', $serviceType, '', '', '', '', 'form="' . $formID . '"'));

                        $editInputs = wf_CheckInput('cancelrowprocessing', __('Cancel record processing'), false, false);
                        $editInputs .= wf_HiddenInput('bankstaeditrowid', $eachRec['id']);
                        $editInputs .= wf_delimiter(0);
                        $editInputs .= wf_Submit(__('Save'));
                        $editForm = wf_Form('', 'POST', $editInputs, '', '', $formID);

                        $cells .= wf_TableCell($editForm);
                    }

                    $cells .= wf_TableCell($eachRec['payid']);
                    $cells .= wf_TableCell($eachRec['summ']);
                    $cells .= wf_TableCell(web_bool_led($recProcessed));
                    $cells .= wf_TableCell(web_bool_led($recCanceled));

//user detection
                    $detectedContract = '';
                    $detectedAddress = '';
                    $detectedRealName = '';
                    $detectedTariff = '';
                    $rowClass = ($recProcessed) ? 'row2' : 'undone';

                    switch (strtolower($serviceType)) {
                        case ('internet'):
                            if (isset($this->allContractsInet[$eachRec['contract']])) {
                                $detectedUser = $this->allUsersDataInet[$this->allContractsInet[$eachRec['contract']]];
                                $detectedContract = wf_Link(self::URL_USERS_PROFILE_INET . $detectedUser['login'], web_profile_icon() . ' ' . $detectedUser['contract'], false, '');
                                $detectedAddress = $detectedUser['fulladress'];
                                $detectedRealName = $detectedUser['realname'];
                                $detectedTariff = $detectedUser['Tariff'];

                                if (!$recProcessed) {
                                    $cashPairs[$eachRec['id']]['bankstaid'] = $eachRec['id'];
                                    $cashPairs[$eachRec['id']]['userlogin'] = $detectedUser['login'];
                                    $cashPairs[$eachRec['id']]['usercontract'] = $detectedUser['contract'];
                                    $cashPairs[$eachRec['id']]['summ'] = $eachRec['summ'];
                                    $cashPairs[$eachRec['id']]['payid'] = $this->inetPaymentId;
                                    $cashPairs[$eachRec['id']]['service'] = $serviceType;

                                    $fiscalRecsIDsList[] = $eachRec['id'];
                                }

                                $rowClass = 'row3';
//try to highlight multiple payments
                                if (!isset($this->bankstaFoundUsers[$eachRec['contract']])) {
                                    $this->bankstaFoundUsers[$eachRec['contract']] = $detectedUser['login'];
                                } else {
                                    $rowClass = 'ukvbankstadup';
                                }
                            }
                            break;

                        case ('ukv'):
                            if (isset($this->allContractsUKV[$eachRec['contract']])) {
                                $detectedUser = $this->allUsersDataUKV[$this->allContractsUKV[$eachRec['contract']]];
                                $detectedContract = wf_Link(self::URL_USERS_PROFILE_UKV . $detectedUser['id'], web_profile_icon() . ' ' . $detectedUser['contract'], false, '');
                                $detectedAddress = $detectedUser['street'] . ' ' . $detectedUser['build'] . '/' . $detectedUser['apt'];
                                $detectedRealName = $detectedUser['realname'];
                                $detectedTariff = $detectedUser['tariffid'];
                                $detectedTariff = $this->ukvTariffs[$detectedTariff]['tariffname'];

                                if (!$recProcessed) {
                                    $cashPairs[$eachRec['id']]['bankstaid'] = $eachRec['id'];
                                    $cashPairs[$eachRec['id']]['userlogin'] = $detectedUser['id'];
                                    $cashPairs[$eachRec['id']]['usercontract'] = $detectedUser['contract'];
                                    $cashPairs[$eachRec['id']]['summ'] = $eachRec['summ'];
                                    $cashPairs[$eachRec['id']]['payid'] = $this->ukvPaymentId;
                                    $cashPairs[$eachRec['id']]['service'] = $serviceType;

                                    $fiscalRecsIDsList[] = $eachRec['id'];
                                }

                                $rowClass = 'row3';
//try to highlight multiple payments
                                if (!isset($this->bankstaFoundUsers[$eachRec['contract']])) {
                                    $this->bankstaFoundUsers[$eachRec['contract']] = $detectedUser['id'];
                                } else {
                                    $rowClass = 'ukvbankstadup';
                                }
                            }
                            break;

                        default:
                            // maybe sometime in future here would be some defaults
                    }

                    $cells.= wf_TableCell($detectedContract);
                    $cells.= wf_TableCell($detectedRealName);
                    $cells.= wf_TableCell($detectedAddress);
                    $cells.= wf_TableCell($detectedTariff);
                    $rows.= wf_TableRow($cells, $rowClass);

                    // dreamkas fiscalizing controls
                    if ($dreamkasEnabled) {
                        $dreamkasCtrls = '';

                        if ($recProcessed) {
                            if ($recCanceled) {
                                $dreamkasCtrls = wf_TableRow('');
                            } else {
                                // geting fiscalized data
                                $dreamkasCtrls = $DreamKas->web_ReceiptDetailsTableRow($eachRec['id']);
                                $refiscalize = empty($dreamkasCtrls);
                            }
                        }

                        if (empty($dreamkasCtrls)) {
                            $addFiscalizePaymentCtrlsJS = true;
                            $dreamkasCtrls = $DreamKas->web_FiscalizePaymentCtrls($serviceType, true, $eachRec['id']);
                        }

                        $rows.= $dreamkasCtrls;
                    }
                }
            }

            $result = wf_TableBody($rows, '100%', '0', '');
            $result.= ($addFiscalizePaymentCtrlsJS) ? $DreamKas->get_BS2FiscalizePaymentCtrlsJS() : '';

            if (!empty($cashPairs) or $refiscalize) {
                $cashInputs = '';

                if ($refiscalize) {
                    $submitCaption = __('Re-fiscalize payments');
                    $cashInputs.= wf_HiddenInput('bankstaneedpaymentspush',base64_encode(serialize('refiscalize')));
                    $cashInputs.= wf_HiddenInput('bankstaneedrefiscalize', 'true');
                } else {
                    $cashPairs = serialize($cashPairs);
                    $cashPairs = base64_encode($cashPairs);
                    $cashInputs.= wf_HiddenInput('bankstaneedpaymentspush', $cashPairs);
                    $cashInputs.= wf_HiddenInput('bankstaneedrefiscalize', 'false');
                    $cashInputs.= ($dreamkasEnabled) ? wf_HiddenInput('bankstafiscalrecsidslist', base64_encode(json_encode($fiscalRecsIDsList))) : '';
                    $submitCaption = __('Process current bank statement');
                }

                $formID = wf_InputId();
                $cashInputs.= ($dreamkasEnabled) ? wf_HiddenInput('bankstapaymentsfiscalize', '') : '';
                $cashInputs.= wf_Submit($submitCaption);
                $result.= wf_Form('', 'POST', $cashInputs, 'glamour', '', $formID);

                if ($dreamkasEnabled) {
                    $result.= wf_tag('script', false, '', 'type="text/javascript"');
                    $result.= '
                                $(\'#' . $formID . '\').submit(function(evt) {
                                    fiscalizationArr = {};
                              ';
                    $result.= ($refiscalize) ? '' : 'fiscalRecsIDsList = JSON.parse(atob($(\'[name="bankstafiscalrecsidslist"]\').val()));';
                    $result.= '         $(\'[name^="fiscalizepayment_"]\').each(function(chkindex, chkelement) {
                     
                                        if ($(chkelement).is(\':checked\')) {
                                            checkCtrlID = $(chkelement).attr("id").substring($(chkelement).attr("id").indexOf(\'_\') + 1);
                                        ';
                    $result.= ($refiscalize) ? '' : 'if ($.inArray(checkCtrlID, fiscalRecsIDsList) != -1) {';
                    $result.= '                 fiscalizationArr[checkCtrlID] = {};
                                                
                                                fiscalizationArr[checkCtrlID][\'drscashmachineid\'] = $(\'[name=drscashmachines_\'+checkCtrlID+\']\').val();
                                                fiscalizationArr[checkCtrlID][\'drstaxtype\'] = $(\'[name=drstaxtypes_\'+checkCtrlID+\']\').val();
                                                fiscalizationArr[checkCtrlID][\'drspaymtype\'] = $(\'[name=drspaymtypes_\'+checkCtrlID+\']\').val();
                                                fiscalizationArr[checkCtrlID][\'drssellingpos\'] = $(\'[name=drssellpos_\'+checkCtrlID+\']\').val();
                                            ';
                    $result.= ($refiscalize) ? '' : '}';
                    $result.= '         }
                                    });
                                    
                                    $(\'[name="bankstapaymentsfiscalize"]\').val(btoa(JSON.stringify(fiscalizationArr)));
                                });
                              ';
                    $result.= wf_tag('script', true);
                }
            }
        } else {
            $result = $this->getUbMsgHelperInstance()->getStyledMessage(__('Specified bank statement hash does not exists'), 'warning');
        }

        return ($result);
    }

    /**
     * Renders uploaded statements ajax list JSON for JQDT
     */
    function renderBStatementsListJSON() {
        $tQuery = "SELECT `filename`, `hash`, `date`, `admin`, 
                          COUNT(`id`) AS `rowcount`, COUNT(if(`processed` > 0, 1, null)) AS processed_cnt, COUNT(if(`canceled` > 0, 1, null)) AS canceled_cnt
                       FROM `" . self::BANKSTA2_TABLE . "` GROUP BY `hash` ORDER BY `date` DESC;";
        $tQueryResult = simple_queryall($tQuery);

        $json = new wf_JqDtHelper();

        if (!empty($tQueryResult)) {
            $data = array();

            foreach ($tQueryResult as $eachRec) {
                $data[] = $eachRec['date'];
                $data[] = $eachRec['filename'];
                $data[] = $eachRec['rowcount'];
                $data[] = $eachRec['processed_cnt'];
                $data[] = $eachRec['canceled_cnt'];
                $data[] = $eachRec['admin'];

                $actions = wf_Link(self::URL_BANKSTA2_PROCESSING . $eachRec['hash'], wf_img('skins/icon_search_small.gif', __('Show')), false, '');

                if ($eachRec['processed_cnt'] == 0) {
                    $actions.= wf_JSAlert(  '#', web_delete_icon(), 'Removing this may lead to irreparable results', 'deleteStatement(\'' . $eachRec['hash'] . '\', \'' . self::URL_ME . '\', \'delStatement\', \'' . wf_InputId() . '\')') . wf_nbsp();
                }

                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns uploaded statements JQDT control
     *
     * @return string
     */
    public function renderBStatementsJQDT() {
        $ajaxUrlStr = '' . self::URL_ME . '&bslistajax=true';
        $jqdtId = 'jqdt_' . md5($ajaxUrlStr);
        $columns = array(__('Date'), __('Filename'), __('Total rows'), __('Processed rows'), __('Canceled rows'), __('Admin'), __('Actions'));
        $opts = '"order": [[ 0, "desc" ]]';
        $result = '';

        $result.= wf_JqDtLoader($columns, $ajaxUrlStr, false, __('Bank statement'), 50, $opts);
        $result.= wf_tag('script', false, '', 'type="text/javascript"');
        $result.= wf_JSEmptyFunc();
        $result.= wf_JSElemInsertedCatcherFunc();
        $result.= '
                    function deleteStatement(bankstaHash, ajaxURL, actionName, errFrmId) {
                        var ajaxData = \'&\'+ actionName +\'=true&hash=\' + bankstaHash + \'&errfrmid=\' + errFrmId                    
                    
                        $.ajax({
                                type: "POST",
                                url: ajaxURL,
                                data: ajaxData,
                                success: function(result) {                                    
                                      if ( !empty(result) ) {                                            
                                          $(document.body).append(result);
                                          $(\'#\'+errFrmId).dialog("open");
                                      }
                                      
                                      $(\'#' . $jqdtId . '\').DataTable().ajax.reload();
                                }
                        });
                    }
                   ';
        $result.= wf_tag('script', true);

        return ($result);
    }

    /**
     * Renders fields mapping presets ajax list JSON for JQDT
     */
    public function renderFMPListJSON() {
        $tQuery = "SELECT `id`, `presetname`, `col_realname`, `col_address`, `col_paysum`, `col_paypurpose`, 
                          `col_paydate`, `col_paytime`, `col_contract`, `guess_contract`, `skip_row`, `service_type`  
                      FROM `" . self::BANKSTA2_PRESETS_TABLE . "`";
        $tQueryResult = simple_queryall($tQuery);

        $json = new wf_JqDtHelper();

        if (!empty($tQueryResult)) {
            $data = array();
            $fmpID = 0;

            foreach ($tQueryResult as $eachRec) {
                foreach ($eachRec as $fieldName => $fieldVal) {
                    switch ($fieldName) {
                        case 'id':
                            $fmpID = $fieldVal;
                            $data[] = $fieldVal;
                            break;

                        case 'guess_contract':
                        case 'skip_row':
                            $data[] = ($fieldVal == 1) ? web_green_led() : web_red_led();
                            break;

                        default:
                            $data[] = $fieldVal;
                    }
                }

                $linkId1 = wf_InputId();
                $actions = wf_JSAlert(  '#', web_delete_icon(), 'Removing this may lead to irreparable results', 'deleteFMP(' . $eachRec['id'] . ', \'' . self::URL_ME . '\', \'delFMP\', \'' . wf_InputId() . '\')') . wf_nbsp();
                $actions.= wf_Link('#', web_edit_icon(), false, '', 'id="' . $linkId1 . '"') . wf_nbsp();
                $actions.= wf_JSAjaxModalOpener(self::URL_ME, array('fmpedit' => 'true', 'fmpid' => $fmpID), $linkId1, true, 'POST');

                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }


    /**
     * Returns fields mapping presets JQDT control and some JS bindings for dynamic forms
     *
     * @return string
     */
    protected function renderFMPJQDT() {
        $ajaxUrlStr = '' . self::URL_ME . '&fmpajax=true';
        $jqdtId = 'jqdt_' . md5($ajaxUrlStr);
        $errorModalWindowId = wf_InputId();
        $columns = array();
        $opts = '"order": [[ 0, "asc" ]],
                "columnDefs": [ {"targets": "_all", "className": "dt-center"} ]';

        $columns[] = __('ID');
        $columns[] = __('Preset name');
        $columns[] = __('Realname column');
        $columns[] = __('Address column');
        $columns[] = __('Paysum column');
        $columns[] = __('Paypurpose column');
        $columns[] = __('Paydate column');
        $columns[] = __('Paytime column');
        $columns[] = __('Contract column');
        $columns[] = __('Contract guessing');
        $columns[] = __('Row skipping');
        $columns[] = __('Service type');
        $columns[] = __('Actions');

        $result = wf_JqDtLoader($columns, $ajaxUrlStr, false, __('results'), 100, $opts);

        $result.= wf_tag('script', false, '', 'type="text/javascript"');
        $result.= wf_JSEmptyFunc();
        $result.= wf_JSElemInsertedCatcherFunc();
        $result.= ' function chekEmptyVal(ctrlClassName) {
                        $(document).on("focus keydown", ctrlClassName, function(evt) {
                            if ( $(ctrlClassName).css("border-color") == "rgb(255, 0, 0)" ) {
                                $(ctrlClassName).val("");
                                $(ctrlClassName).css("border-color", "");
                                $(ctrlClassName).css("color", "");
                            }
                        });
                    }
                     
                    onElementInserted(\'body\', \'.__FMPEmptyCheck\', function(element) {
                        chekEmptyVal(\'.__FMPEmptyCheck\');
                    });
                            
                    $(document).on("submit", ".__FMPForm", function(evt) {
                        var FrmAction        = $(".__FMPForm").attr("action");
                        var FrmData          = $(".__FMPForm").serialize() + \'&errfrmid=' . $errorModalWindowId . '\';
                        //var modalWindowId    = $(".__FMPForm").closest(\'div\').attr(\'id\');
                        evt.preventDefault();
                    
                        var emptyCheckClass = \'.__FMPEmptyCheck\';
                    
                        if ( empty( $(emptyCheckClass).val() ) || $(emptyCheckClass).css("border-color") == "rgb(255, 0, 0)" ) {                            
                            $(emptyCheckClass).css("border-color", "red");
                            $(emptyCheckClass).css("color", "grey");
                            $(emptyCheckClass).val("' . __('Mandatory field') . '");                            
                        } else {
                            $.ajax({
                            type: "POST",
                            url: FrmAction,
                            data: FrmData,
                            success: function(result) {
                                        if ( !empty(result) ) {                                            
                                            $(document.body).append(result);                                                
                                            $( \'#' . $errorModalWindowId . '\' ).dialog("open");                                                
                                        } else {
                                            $(\'#' . $jqdtId . '\').DataTable().ajax.reload();
                                            //$("[name=swgroupname]").val("");
                                            
                                            if ( $(".__CloseFrmOnSubmitChk").is(\':checked\') ) {
                                                $( \'#\'+$(".__FMPFormModalWindowId").val() ).dialog("close");
                                            }
                                        }
                                    }                        
                            });
                        }
                    });
    
                    function deleteFMP(FMPId, ajaxURL, actionName, errFrmId) {
                        var ajaxData = \'&\'+ actionName +\'=true&fmpid=\' + FMPId + \'&errfrmid=\' + errFrmId                    
                    
                        $.ajax({
                                type: "POST",
                                url: ajaxURL,
                                data: ajaxData,
                                success: function(result) {                                    
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);
                                                $(\'#\'+errFrmId).dialog("open");
                                            }
                                            
                                            $(\'#' . $jqdtId . '\').DataTable().ajax.reload();
                                         }
                        });
                    }                  
                  ';
        $result.= wf_tag('script', true);

        return ($result);
    }


    /**
     * Returns field mapping preset addition form
     *
     * @return string
     */
    public function renderFMPAddForm($modalWindowId) {
        $formId = 'Form_' . wf_InputId();
        $closeFormChkId = 'CloseFrmChkID_' . wf_InputId();

        $inputs = wf_TextInput('fmpname', __('Preset name'), '', true, '', '', '__FMPEmptyCheck');

        $inputscells = wf_TableCell(wf_TextInput('fmpcolrealname', __('Real name column number'), '0', false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcoladdr', __('Address column number'), '0', false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaysum', __('Payment sum column number'), '0', false, '4'));
        $inputsrows = wf_TableRow($inputscells);

        $inputscells = wf_TableCell(wf_TextInput('fmpcolpaypurpose', __('Payment purpose column number'), '0', false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaydate', __('Payment date column number'), '0', false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaytime', __('Payment time column number'), '0', true, '4'));
        $inputsrows.= wf_TableRow($inputscells);
        $inputs.= wf_TableBody($inputsrows, '', '0', '', 'cellspacing="4px" style="margin-top: 8px;"');

        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_TextInput('fmpcolcontract', __('User contract column number'), '0', true, '4');
        $inputs.= wf_CheckInput('fmptryguesscontract', __('Try to get contract from payment purpose field'), true, false, 'BankstaTryGuessContract');
        $inputs.= wf_tag('h4', false, '', 'style="font-weight: 400; width: 800px; padding: 2px 0 8px 28px; color: #666; margin-block-end: 0; margin-block-start: 0;"');
        $inputs.= __('ONLY, if mapped contract field for some row will be empty or if contract field will be not specified');
        $inputs.= wf_tag('h4', true);
        $inputs.= wf_TextInput('fmpcontractdelimstart', __('Contract') . ' (' . __('Payment ID') . '): ' . __('start delimiter string'), '', true, '', '', '', 'BankstaContractDelimStart');
        $inputs.= wf_TextInput('fmpcontractdelimend', __('Contract') . ' (' . __('Payment ID') . '): ' . __('end delimiter string'), '', true, '', '', '', 'BankstaContractDelimEnd');
        $inputs.= wf_tag('h4', false, '', 'style="font-weight: 400; width: 800px; padding: 11px 0 7px 0; color: #666; margin-block-end: 0; margin-block-start: 0;"');
        $inputs.= __('If your contracts(payment IDs) are 100% DIGITAL - you may specify their minimum and maximum length to extract them properly(delimiters will not be taken into account)');
        $inputs.= wf_tag('h4', true);
        $inputs.= wf_TextInput('fmpcontractminlen', __('Contract') . ' (' . __('Payment ID') . '): ' . __('min length'), '0', true, '', '', '', 'BankstaContractMinLen');
        $inputs.= wf_TextInput('fmpcontractmaxlen', __('Contract') . ' (' . __('Payment ID') . '): ' . __('max length'), '0', true, '', '', '', 'BankstaContractMaxLen');
        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_Selector('fmpsrvtype', $this->bankstaServiceType, __('Service type'), '', true, false, 'BankstaSrvType');
        $inputs.= wf_TextInput('fmpinetdelimstart', __('Internet service before keywords delimiter string'), '', true, '', '', '', 'BankstaInetDelimStart');
        $inputs.= wf_TextInput('fmpinetkeywords', __('Internet service determination keywords divided with comas'), '', true, '40', '', '', 'BankstaInetKeyWords');
        $inputs.= wf_TextInput('fmpinetdelimend', __('Internet service after keywords delimiter string'), '', true, '', '', '', 'BankstaInetDelimEnd');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_TextInput('fmpukvdelimstart', __('UKV service before keywords delimiter string'), '', true, '', '', '', 'BankstaUKVDelimStart');
        $inputs.= wf_TextInput('fmpukvkeywords', __('UKV service determination keywords divided with comas'), '', true, '40', '', '', 'BankstaUKVKeyWords');
        $inputs.= wf_TextInput('fmpukvdelimend', __('UKV service after keywords delimiter string'), '', true, '', '', '', 'BankstaUKVDelimEnd');
        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_CheckInput('fmpskiprow', __('Skip row processing if selected field contains keywords below'), true, false, 'BankstaSkipRow');
        $inputs.= wf_TextInput('fmpcolskiprow', __('Column to check row skipping'), '', true, '4', '', '', 'BankstaSkipRowKeyWordsCol');
        $inputs.= wf_TextInput('fmpskiprowkeywords', __('Row skipping determination keywords divided with comas'), '', true, '40', '', '', 'BankstaSkipRowKeyWords');
        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_CheckInput('fmpreplacestrs', __('Replace characters specified below in specified fields. Multiple fields must be separated with comas'), true, false, 'BankstaReplaceStrs');
        $inputs.= wf_TextInput('fmpcolsreplacestrs', __('Columns to perform replacing'), '', true, '4', '', '', 'BankstaReplaceStrsCols');
        $inputs.= wf_TextInput('fmpstrstoreplace', __('Replacing characters or strings divided with comas'), '', true, '40', '', '', 'BankstaReplaceStrsChars');
        $inputs.= wf_delimiter(0);

        $inputs.= wf_CheckInput('formclose', __('Close form after operation'), false, true, $closeFormChkId, '__CloseFrmOnSubmitChk');

        $inputs.= wf_HiddenInput('', $modalWindowId, '', '__FMPFormModalWindowId');
        $inputs.= wf_HiddenInput('fmpcreate', 'true');
        $inputs.= wf_delimiter();
        $inputs.= wf_Submit(__('Create'));
        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __FMPForm', '', $formId);

        return ($form);
    }

    /**
     * Returns field mapping preset editing form
     *
     * @return string
     */
    public function renderFMPEditForm($fmpID, $modalWindowId) {
        $formId = 'Form_' . wf_InputId();
        $closeFormChkId = 'CloseFrmChkID_' . wf_InputId();

        $fmpData = $this->fieldsMappingPresets[$fmpID];
        $contractGuessing = (empty($fmpData['guess_contract'])) ? false : true;
        $rowSkipping = (empty($fmpData['skip_row'])) ? false : true;
        $strReplacing = (empty($fmpData['replace_strs'])) ? false : true;

        $inputs = wf_TextInput('fmpname', __('Preset name'), $fmpData['presetname'], true, '', '', '__FMPEmptyCheck');

        $inputscells = wf_TableCell(wf_TextInput('fmpcolrealname', __('Real name column number'), $fmpData['col_realname'], false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcoladdr', __('Address column number'), $fmpData['col_address'], false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaysum', __('Payment sum column number'), $fmpData['col_paysum'], false, '4'));
        $inputsrows = wf_TableRow($inputscells);

        $inputscells = wf_TableCell(wf_TextInput('fmpcolpaypurpose', __('Payment purpose column number'), $fmpData['col_paypurpose'], false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaydate', __('Payment date column number'), $fmpData['col_paydate'], false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaytime', __('Payment time column number'), $fmpData['col_paytime'], true, '4'));
        $inputsrows.= wf_TableRow($inputscells);
        $inputs.= wf_TableBody($inputsrows, '', '0', '', 'cellspacing="4px" style="margin-top: 8px;"');

        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_TextInput('fmpcolcontract', __('User contract column number'), $fmpData['col_contract'], true, '4');
        $inputs.= wf_CheckInput('fmptryguesscontract', __('Try to get contract from payment purpose field'), true, $contractGuessing, 'BankstaTryGuessContract');
        $inputs.= wf_tag('h4', false, '', 'style="font-weight: 400; width: 800px; padding: 2px 0 8px 28px; color: #666; margin-block-end: 0; margin-block-start: 0;"');
        $inputs.= __('ONLY, if mapped contract field for some row will be empty or if contract field will be not specified');
        $inputs.= wf_tag('h4', true);
        $inputs.= wf_TextInput('fmpcontractdelimstart', __('Contract') . ' (' . __('Payment ID') . '): ' . __('start delimiter string'), $fmpData['contract_delim_start'], true, '', '', '', 'BankstaContractDelimStart');
        $inputs.= wf_TextInput('fmpcontractdelimend', __('Contract') . ' (' . __('Payment ID') . '): ' . __('end delimiter string'), $fmpData['contract_delim_end'], true, '', '', '', 'BankstaContractDelimEnd');
        $inputs.= wf_tag('h4', false, '', 'style="font-weight: 400; width: 800px; padding: 11px 0 7px 0; color: #666; margin-block-end: 0; margin-block-start: 0;"');
        $inputs.= __('If your contracts(payment IDs) are 100% DIGITAL - you may specify their minimum and maximum length to extract them properly(delimiters will not be taken into account)');
        $inputs.= wf_tag('h4', true);
        $inputs.= wf_TextInput('fmpcontractminlen', __('Contract') . ' (' . __('Payment ID') . '): ' . __('min length'), $fmpData['contract_min_len'], true, '', '', '', 'BankstaContractMinLen');
        $inputs.= wf_TextInput('fmpcontractmaxlen', __('Contract') . ' (' . __('Payment ID') . '): ' . __('max length'), $fmpData['contract_max_len'], true, '', '', '', 'BankstaContractMaxLen');
        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_Selector('fmpsrvtype', $this->bankstaServiceType, __('Service type'), $fmpData['service_type'], true, false, 'BankstaSrvType');
        $inputs.= wf_TextInput('fmpinetdelimstart', __('Internet service before keywords delimiter string'), $fmpData['inet_srv_start_delim'], true, '', '', '', 'BankstaInetDelimStart');
        $inputs.= wf_TextInput('fmpinetkeywords', __('Internet service determination keywords divided with comas'), $fmpData['inet_srv_keywords'], true, '40', '', '', 'BankstaInetKeyWords');
        $inputs.= wf_TextInput('fmpinetdelimend', __('Internet service after keywords delimiter string'), $fmpData['inet_srv_end_delim'], true, '', '', '', 'BankstaInetDelimEnd');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_TextInput('fmpukvdelimstart', __('UKV service before keywords delimiter string'), $fmpData['ukv_srv_start_delim'], true, '', '', '', 'BankstaUKVDelimStart');
        $inputs.= wf_TextInput('fmpukvkeywords', __('UKV service determination keywords divided with comas'), $fmpData['ukv_srv_keywords'], true, '40', '', '', 'BankstaUKVKeyWords');
        $inputs.= wf_TextInput('fmpukvdelimend', __('UKV service after keywords delimiter string'), $fmpData['ukv_srv_end_delim'], true, '', '', '', 'BankstaUKVDelimEnd');
        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_CheckInput('fmpskiprow', __('Skip row processing if selected field contains keywords below'), true, $rowSkipping, 'BankstaSkipRow');
        $inputs.= wf_TextInput('fmpcolskiprow', __('Column to check row skipping'), $fmpData['col_skiprow'], true, '4', '', '', 'BankstaSkipRowKeyWordsCol');
        $inputs.= wf_TextInput('fmpskiprowkeywords', __('Row skipping determination keywords divided with comas'), $fmpData['skip_row_keywords'], true, '40', '', '', 'BankstaSkipRowKeyWords');
        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_CheckInput('fmpreplacestrs', __('Replace characters specified below in specified fields. Multiple fields must be separated with comas'), true, $strReplacing, 'BankstaReplaceStrs');
        $inputs.= wf_TextInput('fmpcolsreplacestrs', __('Columns to perform replacing'), $fmpData['col_replace_strs'], true, '4', '', '', 'BankstaReplaceStrsCols');
        $inputs.= wf_TextInput('fmpstrstoreplace', __('Replacing characters or strings divided with comas'),  $fmpData['strs_to_replace'], true, '40', '', '', 'BankstaReplaceStrsChars');
        $inputs.= wf_delimiter(0);

        $inputs.= wf_CheckInput('formclose', __('Close form after operation'), false, true, $closeFormChkId, '__CloseFrmOnSubmitChk');

        $inputs.= wf_HiddenInput('', $modalWindowId, '', '__FMPFormModalWindowId');
        $inputs.= wf_HiddenInput('fmpedit', 'true');
        $inputs.= wf_HiddenInput('fmpid', $fmpID);
        $inputs.= wf_delimiter();
        $inputs.= wf_Submit(__('Edit'));
        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __FMPForm', '', $formId);

        return ($form);
    }
}