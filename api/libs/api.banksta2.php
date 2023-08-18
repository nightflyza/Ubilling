<?php

/**
 * Bank statements processing class
 */
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
     * UbillingCache instance placeholder
     *
     * @var null
     */
    protected $ubCache = null;

    /**
     * Placeholder for BANKSTA2_CACHE_LIFETIME from alter.ini
     *
     * @var int
     */
    protected $cacheLifeTime = 900;

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
     * Already preprocessed banksta records IDs from BANKSTA2_TABLE
     *
     * @var array
     */
    protected $bankstaRecordsAllIDs = array();

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
     * Placeholder for BANKSTA2_REGEX_KEYWORDS_DELIM option
     *
     * @var string
     */
    protected $regexKeywordsDelimiter = ',';

    /**
     * Placeholder for BANKSTA2_LSTCHK_FNAMES_TRANSLATE option
     *
     * @var bool
     */
    protected $translateLstChkFieldNames = false;

    /**
     * Placeholder for BANKSTA2_OPAYZID_AS_CONTRACT option
     *
     * @var bool
     */
    protected $opayzIDAsContract = false;

    /**
     * Placeholder for BANKSTA2_INETSRV_ALLOTED_IDS option
     *
     * @var array
     */
    protected $inetSrvAllotedIDs = array();

    /**
     * Placeholder for BANKSTA2_CTVSRV_ALLOTED_IDS option
     *
     * @var array
     */
    protected $ctvSrvAllotedIDs = array();

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
                                                'col_srvidents'         => 'bssrvidents_col',
                                                'sum_in_coins'          => 'bspaymincoins',
                                                'guess_contract'        => 'bstryguesscontract',
                                                'srvidents_preffered'   => 'bssrvidentspreff',
                                                'contract_delim_start'  => 'bscontractdelimstart',
                                                'contract_delim_end'    => 'bscontractdelimend',
                                                'contract_min_len'      => 'bscontractminlen',
                                                'contract_max_len'      => 'bscontractmaxlen',
                                                'service_type'          => 'bssrvtype',
                                                'inet_srv_start_delim'  => 'bsinetdelimstart',
                                                'inet_srv_end_delim'    => 'bsinetdelimend',
                                                'inet_srv_keywords'     => 'bsinetkeywords',
                                                'noesc_inet_srv_keywords' => 'bsinetkeywordsnoesc',
                                                'ukv_srv_start_delim'   => 'bsukvdelimstart',
                                                'ukv_srv_end_delim'     => 'bsukvdelimend',
                                                'ukv_srv_keywords'      => 'bsukvkeywords',
                                                'noesc_ukv_srv_keywords' => 'bsukvkeywordsnoesc',
                                                'skip_row'              => 'bsskiprow',
                                                'col_skiprow'           => 'bsskiprow_col',
                                                'skip_row_keywords'     => 'bsskiprowkeywords',
                                                'noesc_skip_row_keywords' => 'bsskiprowkeywordsnoesc',
                                                'replace_strs'          => 'bsreplacestrs',
                                                'col_replace_strs'      => 'bscolsreplacestrs',
                                                'strs_to_replace'       => 'bsstrstoreplace',
                                                'strs_to_replace_with'  => 'bsstrstoreplacewith',
                                                'replacements_cnt'      => 'bsreplacementscnt',
                                                'noesc_replace_keywords' => 'bsreplacekeywordsnoesc',
                                                'remove_strs'           => 'bsremovestrs',
                                                'col_remove_strs'       => 'bscolremovestrs',
                                                'strs_to_remove'        => 'bsstrstoremove',
                                                'noesc_remove_keywords' => 'bsremovekeywordsnoesc',
                                                'payment_type_id'       => 'bspaymtypeid'
                                             );


    /**
     * Default storage table name
     */
    const BANKSTA2_TABLE = 'banksta2';
    const BANKSTA2_PRESETS_TABLE  = 'banksta2_presets';
    const BANKSTA2_USER_CACHE_KEY = "BANKSTA2_USERS_DATA";
    const BANKSTA2_PROCBS_CACHE_KEY = "BANKSTA2_PROCBS_DATA";
    const BANKSTA2_MAPPRESETS_CACHE_KEY = "BANKSTA2_MAPPRESETS_DATA";

    /**
     * Routing URLs
     */
    const URL_ME = '?module=banksta2';
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
        $this->ubConfig = $ubillingConfig;
        $this->billing = $billing;
        $this->ubCache = new UbillingCache();
        $this->initMessages();
        $this->loadOptions();
        $this->loadProcessedBankstaRecsIDs();

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
        $this->regexKeywordsDelimiter = (wf_getBoolFromVar($this->ubConfig->getAlterParam('BANKSTA2_REGEX_KEYWORDS_DELIM'))) ? $this->ubConfig->getAlterParam('BANKSTA2_REGEX_KEYWORDS_DELIM') : ',';
        $this->translateLstChkFieldNames = $this->ubConfig->getAlterParam('BANKSTA2_LSTCHK_FNAMES_TRANSLATE');
        $this->opayzIDAsContract = $this->ubConfig->getAlterParam('BANKSTA2_OPAYZID_AS_CONTRACT');
        $this->inetSrvAllotedIDs = explode(',', trim($this->ubConfig->getAlterParam('BANKSTA2_INETSRV_ALLOTED_IDS'), "\t\n\r\0\x0B,"));
        $this->ctvSrvAllotedIDs = explode(',', trim($this->ubConfig->getAlterParam('BANKSTA2_CTVSRV_ALLOTED_IDS'), "\t\n\r\0\x0B,"));
        $this->cacheLifeTime = ($this->ubConfig->getAlterParam('BANKSTA2_CACHE_LIFETIME')) ? $this->ubConfig->getAlterParam('BANKSTA2_CACHE_LIFETIME') : 900;
    }

    /**
     * Returns essential user data suitable for caching
     *
     * @return array
     */
    public function getUsersDataForCache() {
        $cacheArray     = array();
        $userDataInet   = $this->loadUserDataInet();
        $userDataUKV    = $this->loadUserDataUKV();
        $ukvTariffs     = $this->loadUKVTariffs();

        $cacheArray['usersinet']            = (empty($userDataInet['usersdata']) ? array() : $userDataInet['usersdata']);
        $cacheArray['usersinetcontracts']   = (empty($userDataInet['userscontracts']) ? array() : $userDataInet['userscontracts']);
        $cacheArray['usersukv']             = (empty($userDataUKV['usersdata']) ? array() : $userDataUKV['usersdata']);
        $cacheArray['usersukvcontracts']    = (empty($userDataUKV['userscontracts']) ? array() : $userDataUKV['userscontracts']);
        $cacheArray['tariffsukv']           = (empty($ukvTariffs) ? array() : $ukvTariffs);

        return ($cacheArray);
    }

    /**
     * Returns user data from cache
     *
     * @return array
     */
    public function getUsersDataCached($force = false) {
        $userDataCached = array();

        if ($force) {
            $this->ubCache->set(self::BANKSTA2_USER_CACHE_KEY, $this->getUsersDataForCache(), $this->cacheLifeTime);
            $userDataCached = $this->ubCache->get(self::BANKSTA2_USER_CACHE_KEY, $this->cacheLifeTime);
        } else {
            $thisInstance = $this;
            $userDataCached = $this->ubCache->getCallback(self::BANKSTA2_USER_CACHE_KEY, function () use ($thisInstance) {
                                    return ($thisInstance->getUsersDataForCache());
                                }, $this->cacheLifeTime);
        }

        $this->allUsersDataInet = $userDataCached['usersinet'];
        $this->allContractsInet = $userDataCached['usersinetcontracts'];
        $this->allUsersDataUKV  = $userDataCached['usersukv'];
        $this->allContractsUKV  = $userDataCached['usersukvcontracts'];
        $this->ukvTariffs       = $userDataCached['tariffsukv'];

        return ($userDataCached);
    }

    /**
     * Returns processed bank statements data from cache
     *
     * @return array
     */
    public function getProcessedBSRecsCached($force = false) {
        $processedBSRecsCached = array();

        if ($force) {
            $this->ubCache->set(self::BANKSTA2_PROCBS_CACHE_KEY, $this->loadProcessedBankstaRecs(), $this->cacheLifeTime);
            $processedBSRecsCached = $this->ubCache->get(self::BANKSTA2_PROCBS_CACHE_KEY, $this->cacheLifeTime);
        } else {
            $thisInstance = $this;
            $processedBSRecsCached = $this->ubCache->getCallback(self::BANKSTA2_PROCBS_CACHE_KEY, function () use ($thisInstance) {
                                            return ($thisInstance->loadProcessedBankstaRecs());
                                        }, $this->cacheLifeTime);
        }

        $this->bankstaRecordsAll = $processedBSRecsCached;
        return ($processedBSRecsCached);
    }

    /**
     * Returns processed bank statements data from cache
     *
     * @return array
     */
    public function getMappingPresetsCached($force = false) {
        $mappingPresetsCached = array();

        if ($force) {
            $this->ubCache->set(self::BANKSTA2_MAPPRESETS_CACHE_KEY, $this->loadMappingPresets(), $this->cacheLifeTime);
            $mappingPresetsCached = $this->ubCache->get(self::BANKSTA2_MAPPRESETS_CACHE_KEY, $this->cacheLifeTime);
        } else {
            $thisInstance = $this;
            $mappingPresetsCached = $this->ubCache->getCallback(self::BANKSTA2_MAPPRESETS_CACHE_KEY, function () use ($thisInstance) {
                                            return ($thisInstance->loadMappingPresets());
                                        }, $this->cacheLifeTime);
        }

        $this->fieldsMappingPresets = $mappingPresetsCached;
        return ($mappingPresetsCached);
    }

    /**
     * Loads all available Internet users data from database
     *
     * @return array
     */
    protected function loadUserDataInet() {
        $result             = array();
        $allOpenPayzUsers   = array();
        $allUsersDataInet   = array();
        $allContractsInet   = array();
        $allUsersData       = zb_UserGetAllData();

        if (!empty($allUsersData)) {
            foreach ($allUsersData as $eachLogin => $eachUserData) {
                $allUsersDataInet[$eachUserData['login']] = array('login'       => $eachUserData['login'],
                                                                  'contract'    => $eachUserData['contract'],
                                                                  'fulladress'  => $eachUserData['fulladress'],
                                                                  'realname'    => $eachUserData['realname'],
                                                                  'Tariff'      => $eachUserData['Tariff']
                                                                 );
            }

            if (!empty($allUsersDataInet)) {
                // getting openpayz customers, if any
                if ($this->opayzIDAsContract) {
                    $tQuery       = "SELECT * FROM `op_customers`";
                    $tQueryResult = simple_queryall($tQuery);

                    if (!empty($tQueryResult)) {
                        foreach ($tQueryResult as $eachRec => $eachOpayzUser) {
                            if (!empty($eachOpayzUser['virtualid'])) {
                                $allOpenPayzUsers[$eachOpayzUser['realid']] = $eachOpayzUser['virtualid'];
                            }
                        }
                    }
                }

                foreach ($allUsersDataInet as $io => $eachUser) {
                    $login = $eachUser['login'];

                    if (!empty($eachUser['contract'])) {
                        $allContractsInet[$eachUser['contract']] = $login;
                    } elseif ($this->opayzIDAsContract and !empty($allOpenPayzUsers[$login])) {
                        $allContractsInet[$allOpenPayzUsers[$login]] = $login;
                    }
                }
            }
        }

        $result['usersdata']        = $allUsersDataInet;
        $result['userscontracts']   = $allContractsInet;

        return ($result);
    }

    /**
     * Loads all available UKV users data from database
     *
     * @return array
     */
    protected function loadUserDataUKV() {
        $tQuery = "SELECT * from `ukv_users`";
        $allUsers = simple_queryall($tQuery);
        $allUsersDataUKV = array();
        $allContractsUKV = array();
        $result = array();

        if (!empty($allUsers)) {
            foreach ($allUsers as $io => $eachUser) {
                $allUsersDataUKV[$eachUser['id']] = array('id'       => $eachUser['id'],
                                                          'contract' => $eachUser['contract'],
                                                          'realname' => $eachUser['realname'],
                                                          'tariffid' => $eachUser['tariffid'],
                                                          'street'   => $eachUser['street'],
                                                          'build'    => $eachUser['build'],
                                                          'apt'      => $eachUser['apt']
                                                        );

                $allContractsUKV[$eachUser['contract']] = $eachUser['id'];
            }
        }

        $result['usersdata']        = $allUsersDataUKV;
        $result['userscontracts']   = $allContractsUKV;

        return ($result);
    }

    /**
     * Loads UKV tariffs into private tariffs prop
     *
     * @return array
     */
    protected function loadUKVTariffs() {
        $tQuery = "SELECT * from `ukv_tariffs` ORDER by `tariffname` ASC;";
        $allTariffs = simple_queryall($tQuery);
        $ukvTariffs = array();

        if (!empty($allTariffs)) {
            foreach ($allTariffs as $io => $each) {
                $ukvTariffs[$each['id']] = $each;
            }
        }

        return ($ukvTariffs);
    }

    /**
     * Loads all of banksta rows to private property for further use
     *
     * @return array
     */
    public function loadProcessedBankstaRecs() {
        $tQuery = "SELECT * FROM `" . self::BANKSTA2_TABLE . "`";
        $tQueryResult = simple_queryall($tQuery);
        $bankstaRecordsAll = array();

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $io => $eachRec) {
                $data4cache = array();
                $data4cache['id'] = $eachRec['id'];
                $data4cache['hash'] = $eachRec['hash'];
                $data4cache['contract'] = $eachRec['contract'];
                $data4cache['processed'] = $eachRec['processed'];
                $data4cache['canceled'] = $eachRec['canceled'];
                $data4cache['service_type'] = $eachRec['service_type'];

                $bankstaRecordsAll[$eachRec['id']] = $data4cache;

            }
        }

        return ($bankstaRecordsAll);
    }

    /**
     * Loads all of banksta rows IDs to private property for further use
     *
     * @return array
     */
    public function loadProcessedBankstaRecsIDs() {
        $tQuery = "SELECT `id` FROM `" . self::BANKSTA2_TABLE . "`";
        $tQueryResult = simple_queryall($tQuery);
        $bankstaRecordsAllIDs = array();

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $io => $eachRec) {
                $bankstaRecordsAllIDs[$eachRec['id']] = $eachRec['id'];
            }
        }

        $this->bankstaRecordsAllIDs = $bankstaRecordsAllIDs;
        return ($bankstaRecordsAllIDs);
    }

    /**
     * Load fields mapping presets (FMPs)
     *
     * @return array
     */
    public function loadMappingPresets() {
        $tQuery = "SELECT * FROM `" . self::BANKSTA2_PRESETS_TABLE . "`";
        $tQueryResult = simple_queryall($tQuery);
        $fieldsMappingPresets = array();

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $eachRec) {
                $fieldsMappingPresets[$eachRec['id']] = $eachRec;
            }
        }

        return ($fieldsMappingPresets);
    }

    /**
     * Fields mapping presets placeholder getter
     *
     * @return array
     */
    public function getMappingPresets() {
        $this->getMappingPresetsCached();
        return ($this->fieldsMappingPresets);
    }

    /**
     * Returns file info for a certain filehash
     *
     * @param $hash
     * @return array
     */
    public function getFileInfoByHash($hash) {
        $data = array();
        $tQuery = "SELECT `filename`, `hash`, `date`, `admin`, COUNT(`id`) AS `rowcount`, COUNT(if(`processed` > 0, 1, null)) AS processed_cnt, COUNT(if(`canceled` > 0, 1, null)) AS canceled_cnt 
                        FROM `" . self::BANKSTA2_TABLE . "` WHERE `hash`='" . $hash . "' LIMIT 1";
        $tQueryResult = simple_queryall($tQuery);

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $io => $eachRec) {
                $data['date'] = $eachRec['date'];
                $data['filename'] = $eachRec['filename'];
                $data['rowcount'] = $eachRec['rowcount'];
                $data['processed_cnt'] = $eachRec['processed_cnt'];
                $data['canceled_cnt'] = $eachRec['canceled_cnt'];
                $data['admin'] = $eachRec['admin'];
            }
        }

        return ($data);
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
        $this->getMappingPresetsCached();

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
        $this->getMappingPresetsCached();
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
        $this->getProcessedBSRecsCached();
        $result = array();

        if (isset($this->bankstaRecordsAll[$recID])) {
            //$result = $this->bankstaRecordsAll[$recID];
            $query = "SELECT * FROM `" . self::BANKSTA2_TABLE . "` WHERE `id` = '" . $recID . "'";
            $result = simple_query($query);
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
        if (isset($this->bankstaRecordsAllIDs[$bankstaRecID])) {
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
        if (isset($this->bankstaRecordsAllIDs[$bankstaRecID])) {
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
        if (isset($this->bankstaRecordsAllIDs[$bankstaRecID])) {
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
        $this->getProcessedBSRecsCached();
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
        $this->getProcessedBSRecsCached();
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
     * @param $fmpColRealName
     * @param $fmpColAddr
     * @param $fmpColPaySum
     * @param $fmpColPayPurpose
     * @param $fmpColPayDate
     * @param $fmpColPayTime
     * @param $fmpColContract
     * @param $fmpPaySumInCoins
     * @param $fmpGuessContract
     * @param $fmpContractDelimStart
     * @param $fmpContractDelimEnd
     * @param $fmpContractMinLen
     * @param $fmpContractMaxLen
     * @param $fmpSrvType
     * @param $fmpInetStartDelim
     * @param $fmpInetEndDelim
     * @param $fmpInetKeywords
     * @param $fmpNoEscInetKeywords
     * @param $fmpUKVDelimStart
     * @param $fmpUKVDelimEnd
     * @param $fmpUKVKeywords
     * @param $fmpNoEscUKVKeywords
     * @param $fmpSkipRow
     * @param $fmpColSkipRow
     * @param $fmpSkipRowKeywords
     * @param $fmpNoEscSkipRowKeywords
     * @param $fmpReplaceStrs
     * @param $fmpColReplaceStrs
     * @param $fmpStrsToReplace
     * @param $fmpStrsToReplaceWith
     * @param $fmpReplacementsCount
     * @param $fmpNoEscReplaceKeywords
     * @param $fmpRemoveStrs
     * @param $fmpColRemoveStrs
     * @param $fmpStrsToRemove
     * @param $fmpNoEscRemoveKeywords
     * @param $fmpPaymentTypeID
     * @param $fmpColSrvIdents
     * @param $fmpSrvIdentsPreffered
     *
     * @return void
     */
    public function addFieldsMappingPreset($fmpName, $fmpColRealName = 'NONE', $fmpColAddr = 'NONE', $fmpColPaySum = 'NONE', $fmpColPayPurpose = 'NONE',
                                           $fmpColPayDate = 'NONE', $fmpColPayTime = 'NONE', $fmpColContract = 'NONE', $fmpPaySumInCoins = 0, $fmpGuessContract = 0,
                                           $fmpContractDelimStart = '', $fmpContractDelimEnd = '', $fmpContractMinLen = 0, $fmpContractMaxLen = 0, $fmpSrvType = '',
                                           $fmpInetStartDelim = '', $fmpInetEndDelim = '', $fmpInetKeywords = '', $fmpNoEscInetKeywords = 0,
                                           $fmpUKVDelimStart = '', $fmpUKVDelimEnd = '', $fmpUKVKeywords = '', $fmpNoEscUKVKeywords = 0,
                                           $fmpSkipRow = 0, $fmpColSkipRow = '', $fmpSkipRowKeywords = '', $fmpNoEscSkipRowKeywords = 0,
                                           $fmpReplaceStrs = 0, $fmpColReplaceStrs = '', $fmpStrsToReplace = '',
                                           $fmpStrsToReplaceWith = '', $fmpReplacementsCount = '', $fmpNoEscReplaceKeywords = 0,
                                           $fmpRemoveStrs = 0, $fmpColRemoveStrs = '', $fmpStrsToRemove = '', $fmpNoEscRemoveKeywords = 0,
                                           $fmpPaymentTypeID = 0, $fmpColSrvIdents = 0, $fmpSrvIdentsPreffered = 0
                                          ) {

        $fmpColRealName     = (wf_emptyNonZero($fmpColRealName) ? 'NONE' : $fmpColRealName);
        $fmpColAddr         = (wf_emptyNonZero($fmpColAddr) ? 'NONE' : $fmpColAddr);
        $fmpColPaySum       = (wf_emptyNonZero($fmpColPaySum) ? 'NONE' : $fmpColPaySum);
        $fmpColPayPurpose   = (wf_emptyNonZero($fmpColPayPurpose) ? 'NONE' : $fmpColPayPurpose);
        $fmpColPayDate      = (wf_emptyNonZero($fmpColPayDate) ? 'NONE' : $fmpColPayDate);
        $fmpColPayTime      = (wf_emptyNonZero($fmpColPayTime) ? 'NONE' : $fmpColPayTime);
        $fmpColContract     = (wf_emptyNonZero($fmpColContract) ? 'NONE' : $fmpColContract);
        $fmpColSrvIdents    = (wf_emptyNonZero($fmpColSrvIdents) ? 'NONE' : $fmpColSrvIdents);


        $tQuery = "INSERT INTO `" . self::BANKSTA2_PRESETS_TABLE .
                  "` (`presetname`, `col_realname`, `col_address`, `col_paysum`, `sum_in_coins`, `col_paypurpose`, `col_paydate`, 
                            `col_paytime`, `col_contract`, `col_srvidents`, `guess_contract`, `srvidents_preffered`, 
                            `contract_delim_start`, `contract_delim_end`, `contract_min_len`, `contract_max_len`, 
                            `service_type`, `inet_srv_start_delim`, `inet_srv_end_delim`, `inet_srv_keywords`, `noesc_inet_srv_keywords`,
                            `ukv_srv_start_delim`, `ukv_srv_end_delim`, `ukv_srv_keywords`, `noesc_ukv_srv_keywords`, 
                            `skip_row`, `col_skiprow`, `skip_row_keywords`, `noesc_skip_row_keywords`,
                            `replace_strs`, `col_replace_strs`, `strs_to_replace`, `strs_to_replace_with`, `replacements_cnt`, `noesc_replace_keywords`,
                            `remove_strs`, `col_remove_strs`, `strs_to_remove`, `noesc_remove_keywords`, `payment_type_id`) 
                  VALUES ('" . $fmpName . "', '" . $fmpColRealName . "', '" . $fmpColAddr . "', '" . $fmpColPaySum . "', '" . $fmpPaySumInCoins  . "', '" .
                  $fmpColPayPurpose . "', '" . $fmpColPayDate . "', '" . $fmpColPayTime . "', '" . $fmpColContract . "', '" . $fmpColSrvIdents . "', " .
                  $fmpGuessContract . ", " . $fmpSrvIdentsPreffered . ", '" . $fmpContractDelimStart . "', '" . $fmpContractDelimEnd . "', " .
                  $fmpContractMinLen . ", " . $fmpContractMaxLen . ", '" . $fmpSrvType . "', '" .
                  $fmpInetStartDelim . "', '" . $fmpInetEndDelim . "', '" . $fmpInetKeywords . "', " . $fmpNoEscInetKeywords  . ", '" .
                  $fmpUKVDelimStart . "', '" . $fmpUKVDelimEnd . "', '" . $fmpUKVKeywords . "', " . $fmpNoEscUKVKeywords . ", '" .
                  $fmpSkipRow  . "', '" . $fmpColSkipRow  . "', '" . $fmpSkipRowKeywords . "', " . $fmpNoEscSkipRowKeywords . ", '" .
                  $fmpReplaceStrs . "', '" . $fmpColReplaceStrs . "', '" . $fmpStrsToReplace . "', '" .
                  $fmpStrsToReplaceWith . "', '" . $fmpReplacementsCount . "', '" . $fmpNoEscReplaceKeywords . "', '" .
                  $fmpRemoveStrs . "', '" . $fmpColRemoveStrs . "', '" . $fmpStrsToRemove . "', " . $fmpNoEscRemoveKeywords . ", " . $fmpPaymentTypeID . ")";

        nr_query($tQuery);
        log_register('CREATE banksta2 fields mapping preset [' . $fmpName . ']');

        $this->getMappingPresetsCached(true);
    }

    /**
     * Edits existing fields mapping preset
     *
     * @param $fmpID
     * @param $fmpName
     * @param $fmpColRealName
     * @param $fmpColAddr
     * @param $fmpColPaySum
     * @param $fmpColPayPurpose
     * @param $fmpColPayDate
     * @param $fmpColPayTime
     * @param $fmpColContract
     * @param $fmpPaySumInCoins
     * @param $fmpGuessContract
     * @param $fmpContractDelimStart
     * @param $fmpContractDelimEnd
     * @param $fmpContractMinLen
     * @param $fmpContractMaxLen
     * @param $fmpSrvType
     * @param $fmpInetStartDelim
     * @param $fmpInetEndDelim
     * @param $fmpInetKeywords
     * @param $fmpNoEscInetKeywords
     * @param $fmpUKVDelimStart
     * @param $fmpUKVDelimEnd
     * @param $fmpUKVKeywords
     * @param $fmpNoEscUKVKeywords
     * @param $fmpSkipRow
     * @param $fmpColSkipRow
     * @param $fmpSkipRowKeywords
     * @param $fmpNoEscSkipRowKeywords
     * @param $fmpReplaceStrs
     * @param $fmpColReplaceStrs
     * @param $fmpStrsToReplace
     * @param $fmpStrsToReplaceWith
     * @param $fmpReplacementsCount
     * @param $fmpNoEscReplaceKeywords
     * @param $fmpRemoveStrs
     * @param $fmpColRemoveStrs
     * @param $fmpStrsToRemove
     * @param $fmpNoEscRemoveKeywords
     * @param $fmpPaymentTypeID
     * @param $fmpColSrvIdents
     * @param $fmpSrvIdentsPreffered
     *
     * @return void
     */
    public function editFieldsMappingPreset($fmpID, $fmpName, $fmpColRealName = 'NONE', $fmpColAddr = 'NONE', $fmpColPaySum = 'NONE', $fmpColPayPurpose = 'NONE',
                                            $fmpColPayDate = 'NONE', $fmpColPayTime = 'NONE', $fmpColContract = 'NONE', $fmpPaySumInCoins = 0, $fmpGuessContract = 0,
                                            $fmpContractDelimStart = '', $fmpContractDelimEnd = '', $fmpContractMinLen = 0, $fmpContractMaxLen = 0, $fmpSrvType = '',
                                            $fmpInetStartDelim = '', $fmpInetEndDelim = '', $fmpInetKeywords = '', $fmpNoEscInetKeywords = 0,
                                            $fmpUKVDelimStart = '', $fmpUKVDelimEnd = '', $fmpUKVKeywords = '', $fmpNoEscUKVKeywords = 0,
                                            $fmpSkipRow = 0, $fmpColSkipRow = '', $fmpSkipRowKeywords = '', $fmpNoEscSkipRowKeywords = 0,
                                            $fmpReplaceStrs = 0, $fmpColReplaceStrs = '', $fmpStrsToReplace = '',
                                            $fmpStrsToReplaceWith = '', $fmpReplacementsCount = '', $fmpNoEscReplaceKeywords = 0,
                                            $fmpRemoveStrs = 0, $fmpColRemoveStrs = '', $fmpStrsToRemove = '', $fmpNoEscRemoveKeywords = 0,
                                            $fmpPaymentTypeID = 0, $fmpColSrvIdents = 0, $fmpSrvIdentsPreffered = 0
                                           ) {

        $fmpColRealName     = (wf_emptyNonZero($fmpColRealName) ? 'NONE' : $fmpColRealName);
        $fmpColAddr         = (wf_emptyNonZero($fmpColAddr) ? 'NONE' : $fmpColAddr);
        $fmpColPaySum       = (wf_emptyNonZero($fmpColPaySum) ? 'NONE' : $fmpColPaySum);
        $fmpColPayPurpose   = (wf_emptyNonZero($fmpColPayPurpose) ? 'NONE' : $fmpColPayPurpose);
        $fmpColPayDate      = (wf_emptyNonZero($fmpColPayDate) ? 'NONE' : $fmpColPayDate);
        $fmpColPayTime      = (wf_emptyNonZero($fmpColPayTime) ? 'NONE' : $fmpColPayTime);
        $fmpColContract     = (wf_emptyNonZero($fmpColContract) ? 'NONE' : $fmpColContract);
        $fmpColSrvIdents    = (wf_emptyNonZero($fmpColSrvIdents) ? 'NONE' : $fmpColSrvIdents);

        $tQuery = "UPDATE `" . self::BANKSTA2_PRESETS_TABLE . "` SET 
                            `presetname`                = '" . $fmpName . "', 
                            `col_realname`              = '" . $fmpColRealName . "',  
                            `col_address`               = '" . $fmpColAddr . "', 
                            `col_paysum`                = '" . $fmpColPaySum . "', 
                            `col_paypurpose`            = '" . $fmpColPayPurpose . "', 
                            `col_paydate`               = '" . $fmpColPayDate . "',  
                            `col_paytime`               = '" . $fmpColPayTime . "',  
                            `col_contract`              = '" . $fmpColContract . "',  
                            `col_srvidents`             = '" . $fmpColSrvIdents . "',
                            `sum_in_coins`              = '" . $fmpPaySumInCoins . "',                           
                            `guess_contract`            = " . $fmpGuessContract . ", 
                            `srvidents_preffered`       = " . $fmpSrvIdentsPreffered . ",
                            `contract_delim_start`      = '" . $fmpContractDelimStart . "', 
                            `contract_delim_end`        = '" . $fmpContractDelimEnd . "',
                            `contract_min_len`          = " . $fmpContractMinLen . ",
                            `contract_max_len`          = " . $fmpContractMaxLen . ", 
                            `service_type`              = '" . $fmpSrvType . "',  
                            `inet_srv_start_delim`      = '" . $fmpInetStartDelim . "', 
                            `inet_srv_end_delim`        = '" . $fmpInetEndDelim . "', 
                            `inet_srv_keywords`         = '" . $fmpInetKeywords . "',
                            `noesc_inet_srv_keywords`   = '" . $fmpNoEscInetKeywords . "', 
                            `ukv_srv_start_delim`       = '" . $fmpUKVDelimStart . "',  
                            `ukv_srv_end_delim`         = '" . $fmpUKVDelimEnd . "', 
                            `ukv_srv_keywords`          = '" . $fmpUKVKeywords . "',
                            `noesc_ukv_srv_keywords`    = '" . $fmpNoEscUKVKeywords . "',
                            `skip_row`                  = '" . $fmpSkipRow . "',
                            `col_skiprow`               = '" . $fmpColSkipRow . "',                            
                            `skip_row_keywords`         = '" . $fmpSkipRowKeywords . "',
                            `noesc_skip_row_keywords`   = '" . $fmpNoEscSkipRowKeywords . "',
                            `replace_strs`              = '" . $fmpReplaceStrs . "',
                            `col_replace_strs`          = '" . $fmpColReplaceStrs . "',
                            `strs_to_replace`           = '" . $fmpStrsToReplace . "',
                            `strs_to_replace_with`      = '" . $fmpStrsToReplaceWith . "',
                            `replacements_cnt`          = '" . $fmpReplacementsCount . "',
                            `noesc_replace_keywords`    = '" . $fmpNoEscReplaceKeywords . "',
                            `remove_strs`               = '" . $fmpRemoveStrs . "',
                            `col_remove_strs`           = '" . $fmpColRemoveStrs . "',
                            `strs_to_remove`            = '" . $fmpStrsToRemove . "',
                            `noesc_remove_keywords`     = '" . $fmpNoEscRemoveKeywords . "',
                            `payment_type_id`           = " . $fmpPaymentTypeID . "
                        WHERE `id` = " . $fmpID;

        nr_query($tQuery);
        log_register('CHANGE banksta2 fields mapping preset [' . $fmpName . ']');
        $this->getMappingPresetsCached(true);
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
        $this->getMappingPresetsCached(true);
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
        $this->getProcessedBSRecsCached(true);
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
     * Creates essential regex body-strings for preprocessBStatement() processing
     *
     * @param $keyWordStr
     * @param string $delimiter
     * @return string
     */
    public function prepareRegexStrings($keyWordStr, $delimiter = ',', $noEscaping = false) {
        $keywordsStr = '';
        $keywordsArray = explode($delimiter, $keyWordStr);

        foreach ($keywordsArray as $keyWord) {
            if ($noEscaping) {
                $keywordsStr.= trim($keyWord) . '|';
            } else {
                $keywordsStr.= trim(preg_quote($keyWord, '/')) . '|';
            }
        }

        $keywordsStr = rtrim($keywordsStr, '|');

        return ($keywordsStr);
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
        $statementRawData  = unserialize(base64_decode($statementRawData));

        $noescInetKeywords = wf_getBoolFromVar($importOpts['noesc_inet_srv_keywords']);
        $noescUKVKeywords  = wf_getBoolFromVar($importOpts['noesc_ukv_srv_keywords']);
        $noescSkipKeywords = wf_getBoolFromVar($importOpts['noesc_skip_row_keywords']);
        $noescRplcKeywords = wf_getBoolFromVar($importOpts['noesc_replace_keywords']);
        $noescRmvKeywords  = wf_getBoolFromVar($importOpts['noesc_remove_keywords']);

        $contractGuess   = $importOpts['guess_contract'];
        $contractDelimS  = (empty($importOpts['contract_delim_start'])) ? '' : preg_quote($importOpts['contract_delim_start'], '/');
        $contractDelimE  = (empty($importOpts['contract_delim_end'])) ? '' : preg_quote($importOpts['contract_delim_end'], '/');
        $contractMinLen  = $importOpts['contract_min_len'];
        $contractMaxLen  = $importOpts['contract_max_len'];
        $serviceType     = $importOpts['service_type'];
        $paymentTypeID   = $importOpts['payment_type_id'];

        $inetSrvDelimS   = (empty($importOpts['inet_srv_start_delim'])) ? '' : preg_quote($importOpts['inet_srv_start_delim'], '/');
        $inetSrvDelimE   = (empty($importOpts['inet_srv_end_delim'])) ? '' : preg_quote($importOpts['inet_srv_end_delim'], '/');
        $inetSrvKeywords = (empty($importOpts['inet_srv_keywords']))
                            ? '' : ($noescInetKeywords ? $importOpts['inet_srv_keywords'] : preg_quote($importOpts['inet_srv_keywords'], '/'));

        $ukvSrvDelimS    = (empty($importOpts['ukv_srv_start_delim'])) ? '' : preg_quote($importOpts['ukv_srv_start_delim'], '/');
        $ukvSrvDelimE    = (empty($importOpts['ukv_srv_end_delim'])) ? '' : preg_quote($importOpts['ukv_srv_end_delim'], '/');
        $ukvSrvKeywords  = (empty($importOpts['ukv_srv_keywords']))
                            ? '' : ($noescUKVKeywords ? $importOpts['ukv_srv_keywords'] : preg_quote($importOpts['ukv_srv_keywords'], '/'));

        $skipRow         = $importOpts['skip_row'];
        $skipRowCols     = ($importOpts['col_skiprow'] !== 'NONE') ? explode(',', str_replace(' ', '', $importOpts['col_skiprow'])) : array();
        $skipRowKeywords = (empty($importOpts['skip_row_keywords']))
                            ? '' : ($noescSkipKeywords ? $importOpts['skip_row_keywords'] : preg_quote($importOpts['skip_row_keywords'], '/'));

        $strsReplace     = $importOpts['replace_strs'];
        $strsReplaceCols = ($importOpts['col_replace_strs'] !== 'NONE') ? explode(',', str_replace(' ', '', $importOpts['col_replace_strs'])) : array();
        $strsReplaceChars     = (empty($importOpts['strs_to_replace']))
                                ? '' : ($noescRplcKeywords ? $importOpts['strs_to_replace'] : preg_quote($importOpts['strs_to_replace']));
        $strsReplaceCharsWith = (empty($importOpts['strs_to_replace_with'])) ? '' : $importOpts['strs_to_replace_with'];
        $strsReplacementsCnt  = (empty($importOpts['replacements_cnt'])) ? -1 : $importOpts['replacements_cnt'];

        $strsRemove      = $importOpts['remove_strs'];
        $strsRemoveCols  = ($importOpts['col_remove_strs'] !== 'NONE') ? explode(',', str_replace(' ', '', $importOpts['col_remove_strs'])) : array();
        $strsRemoveChars = (empty($importOpts['strs_to_remove']))
                            ? '' : ($noescRmvKeywords ? $importOpts['strs_to_remove'] : preg_quote($importOpts['strs_to_remove']));

        $srvsIDsIdentsPreff = $importOpts['srvidents_preffered'];

        // creating essential regex bodies
        $keywordsStrInet         = '';
        $keywordsStrUKV          = '';
        $keywordsStrSkipRow      = '';
        $keywordsStrReplaceChars = '';
        $keywordsStrRemoveChars  = '';

        // trying to get Inet service keywords
        if (!empty($inetSrvKeywords)) {
            $keywordsStrInet = $this->prepareRegexStrings($inetSrvKeywords, $this->regexKeywordsDelimiter, $noescInetKeywords);
        }

        // trying to get UKV service keywords
        if (!empty($ukvSrvKeywords)) {
            $keywordsStrUKV = $this->prepareRegexStrings($ukvSrvKeywords, $this->regexKeywordsDelimiter, $noescUKVKeywords);
        }

        // trying to get skipping row keywords
        if ($skipRow and !empty($skipRowCols)) {
            $keywordsStrSkipRow = $this->prepareRegexStrings($skipRowKeywords, $this->regexKeywordsDelimiter, $noescSkipKeywords);
        }

        // trying to get replacement keywords
        if ($strsReplace and !empty($strsReplaceCols)) {
            $keywordsStrReplaceChars = $this->prepareRegexStrings($strsReplaceChars, $this->regexKeywordsDelimiter, $noescRplcKeywords);
        }

        // trying to get removing keywords
        if ($strsRemove and !empty($strsRemoveCols)) {
            $keywordsStrRemoveChars = $this->prepareRegexStrings($strsRemoveChars, $this->regexKeywordsDelimiter, $noescRmvKeywords);
        }

        $i = 0;
        $rows = '';
        $statementData = array();

        foreach ($statementRawData as $eachRow) {
            if (empty($eachRow)) { continue; }

            $i++;
            $cells = wf_TableCell($i);
            $cancelRow = 0;

            // replacing characters/strings in specified fields
            if ($strsReplace and !empty($strsReplaceCols) and !empty($strsReplaceChars)) {
                foreach ($strsReplaceCols as $strsReplaceCol) {
                    if (isset($eachRow[$strsReplaceCol])) {
                        $eachRow[$strsReplaceCol] = preg_replace('/(' . $keywordsStrReplaceChars . ')/msiu', $strsReplaceCharsWith, $eachRow[$strsReplaceCol], $strsReplacementsCnt);
                    }
                }
            }

            // removing characters/strings from specified fields
            if ($strsRemove and !empty($strsRemoveCols) and !empty($keywordsStrRemoveChars)) {
                foreach ($strsRemoveCols as $strsRemoveCol) {
                    if (isset($eachRow[$strsRemoveCol])) {
                        $eachRow[$strsRemoveCol] = preg_replace('/(' . $keywordsStrRemoveChars . ')/msiu', '', $eachRow[$strsRemoveCol]);
                    }
                }
            }

            $realname = ($importOpts['col_realname'] !== 'NONE' and isset($eachRow[$importOpts['col_realname']])) ? $eachRow[$importOpts['col_realname']] : '';
            $address  = ($importOpts['col_address'] !== 'NONE' and isset($eachRow[$importOpts['col_address']])) ? $eachRow[$importOpts['col_address']] : '';
            $notes    = ($importOpts['col_paypurpose'] !== 'NONE' and isset($eachRow[$importOpts['col_paypurpose']])) ? $eachRow[$importOpts['col_paypurpose']] : '';
            $ptime    = ($importOpts['col_paytime'] !== 'NONE' and isset($eachRow[$importOpts['col_paytime']])) ? $eachRow[$importOpts['col_paytime']] : '';
            $summ     = (isset($eachRow[$importOpts['col_paysum']])) ? preg_replace('/[^-0-9\.,]/', '', $eachRow[$importOpts['col_paysum']]) : '';
            $summ     = ((!empty($summ) and $importOpts['sum_in_coins']) ? ($summ / 100) : $summ);
            $pdate    = (isset($eachRow[$importOpts['col_paydate']])) ? $eachRow[$importOpts['col_paydate']] : '';
            $contract = ($importOpts['col_contract'] !== 'NONE' and isset($eachRow[$importOpts['col_contract']])) ? $eachRow[$importOpts['col_contract']] : '';
            $service_type       = $serviceType;
            $payment_type_id    = $paymentTypeID;
            $srvTypeMatched     = false;

            // checking and preparing services idents if an appropriate dedicated field specified
            $serviceIdent = ($importOpts['col_srvidents'] !== 'NONE' and isset($eachRow[$importOpts['col_srvidents']])) ? $eachRow[$importOpts['col_srvidents']] : '';

            if (!empty($serviceIdent) and (!empty($this->inetSrvAllotedIDs[0]) or !empty($this->ctvSrvAllotedIDs[0]))) {
                if (in_array($serviceIdent, $this->inetSrvAllotedIDs)) {
                    $service_type = 'Internet';
                    $srvTypeMatched = true;
                } elseif (in_array($serviceIdent, $this->ctvSrvAllotedIDs)) {
                    $service_type = 'UKV';
                    $srvTypeMatched = true;
                }
            }

            if (!empty($notes)) {
                if (empty($contract)) {
                    // if contract guessing enabled and at least one of the delimiters is not empty
                    if ($contractGuess) {
                        if (empty($contractMinLen) or empty($contractMaxLen)) {
                            if ($contractDelimS != '' or $contractDelimE != '') {
                                //$contractDelimS = '(' . $contractDelimS . ')';
                                //$contractDelimE = '(' . $contractDelimE . ')';
                                //preg_match('/' . $contractDelimS . '(\D)*?\d{' . $contractMinLen . ',' . $contractMaxLen . '}(\D)*?' . $contractDelimE . '/msu', $notes, $matchResult);
                            //} else {
                                preg_match('/' . $contractDelimS . '(.*?)' . $contractDelimE . '/msu', $notes, $matchResult);
                            }

                            if (isset($matchResult[1])) {
                                $contract = trim($matchResult[1]);
                            } else {
                                $contract = 'unknown_' . $i;
                            }
                        } else {
                            preg_match('/(\D)?(\d{' . $contractMinLen . ',' . $contractMaxLen . '})(\D)?/msu', $notes, $matchResult);

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

                if (strtolower($serviceType) == 'telepathy' and !($srvsIDsIdentsPreff and $srvTypeMatched)) {
                    // trying to check for Inet service keywords
                    if (!empty($keywordsStrInet)) {
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

                        preg_match('/(' . $keywordsStrInet . ')/msiu', $betweenDelimStr, $matchResult);

                        if (isset($matchResult[1])) {
                            $service_type = 'Internet';
                            $srvTypeMatched = true;
                        }
                    }

                    // trying to check for UKV service keywords
                    if (!$srvTypeMatched and !empty($keywordsStrUKV)) {
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

                        preg_match('/(' . $keywordsStrUKV . ')/msiu', $betweenDelimStr, $matchResult);

                        if (isset($matchResult[1])) {
                            $service_type = 'UKV';
                        }
                    }
                }
            } else {
                if (empty($contract)) { $contract = 'unknown_' . $i; }
            }

            // skipping rows
            if ($skipRow and !empty($skipRowCols) and !empty($keywordsStrSkipRow)) {
                foreach ($skipRowCols as $skipRowCol) {
                    if (!empty($eachRow[$skipRowCol])) {

                        $skipRowContent = $eachRow[$skipRowCol];
                        preg_match('/(' . $keywordsStrSkipRow . ')/msiu', $skipRowContent, $matchResult);

                        if (isset($matchResult[1])) {
                            $cancelRow = 1;
                            break;
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
            $statementData[$tArrayIndex]['paymtype_id'] = $payment_type_id;

            if (!$skipLastChecksForm) {
                $cancelTitle = ($cancelRow) ? 'Yes' : 'No';
                $cancelTitle = ($this->translateLstChkFieldNames) ? __($cancelTitle) : $cancelTitle;

                $cells.= wf_TableCell($contract);
                $cells.= wf_TableCell($summ);
                $cells.= wf_TableCell($address);
                $cells.= wf_TableCell($realname);
                $cells.= wf_TableCell($notes);
                $cells.= wf_TableCell($pdate);
                $cells.= wf_TableCell($ptime);
                $cells.= wf_TableCell($service_type);
                $cells.= wf_TableCell($cancelTitle);

                $rows.= wf_TableRow($cells, (($cancelRow) ? 'row6' : 'row3'));
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
                    $paymentTypeID = $eachRow['paymtype_id'];

                    //pushing row into database
                    if ((!empty($newPdate)) AND (!empty($newSumm))) {
                        $this->createPaymentRec($newDate, $newHash, $newFilename, $newAdmin, $newContract, $newSumm, $newAddress, $newRealname, $newNotes, $newPdate, $newPtime, $newSrvType, $newCancelRow, $paymentTypeID);
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
     * @param bool $refiscalize
     *
     * @return void
     */
    public function pushStatementPayments($paymentsToPush, $refiscalize = false) {
        $paymentsToPush = unserialize(base64_decode($paymentsToPush));
        $checkForCorpUsers = $this->ubConfig->getAlterParam('USER_LINKING_ENABLED');
        $dreamkasEnabled = $this->ubConfig->getAlterParam('DREAMKAS_ENABLED');
        $needToFiscalize = false;
        $fiscalDataArray = array();
        $insatiability   = false;

        if ($dreamkasEnabled and wf_CheckPost(array('bankstapaymentsfiscalize'))) {
            $DreamKas = null;
            $greed = new Avarice();
            $insatiability = $greed->runtime('DREAMKAS');

            $needToFiscalize = true;
            $fiscalDataArray = json_decode(base64_decode($_POST['bankstapaymentsfiscalize']), true);

            if ($refiscalize) {
                $paymentsToPush = array();
                $bs2RecIDs = implode(',', array_keys($fiscalDataArray));
                $tQuery = "(SELECT `contracts`.`login` AS `userlogin`, `" . self::BANKSTA2_TABLE . "`.`id`, `summ`, `pdate`, `ptime`, `payid`, `service_type` AS `service` 
                                  FROM `" . self::BANKSTA2_TABLE . "` 
                                    RIGHT JOIN `contracts` ON `" . self::BANKSTA2_TABLE . "`.`contract` = `contracts`.`contract` 
                                                              AND `" . self::BANKSTA2_TABLE . "`.`service_type` = 'Internet'
                                  WHERE `" . self::BANKSTA2_TABLE . "`.`id` IN (" . $bs2RecIDs . "))
                               UNION 
                               (SELECT `ukv_users`.`id` AS `userlogin`, `" . self::BANKSTA2_TABLE . "`.`id`, `summ`, `pdate`, `ptime`, `payid`, `service_type` AS `service` 
                                  FROM `" . self::BANKSTA2_TABLE . "` 
                                    RIGHT JOIN `ukv_users` ON `" . self::BANKSTA2_TABLE . "`.`contract` = `ukv_users`.`contract` 
                                                              AND `" . self::BANKSTA2_TABLE . "`.`service_type` = 'UKV'
                                  WHERE `" . self::BANKSTA2_TABLE . "`.`id` IN (" . $bs2RecIDs . ")) ";

                if ($this->opayzIDAsContract) {
                    $tQuery.= " UNION 
                               (SELECT `op_customers`.`realid` AS `userlogin`, `" . self::BANKSTA2_TABLE . "`.`id`, `summ`, `pdate`, `ptime`, `payid`, `service_type` AS `service` 
                                  FROM `" . self::BANKSTA2_TABLE . "` 
                                    RIGHT JOIN `op_customers` ON `" . self::BANKSTA2_TABLE . "`.`contract` = `op_customers`.`virtualid` 
                                                              AND `" . self::BANKSTA2_TABLE . "`.`service_type` = 'Internet'
                                  WHERE `" . self::BANKSTA2_TABLE . "`.`id` IN (" . $bs2RecIDs . ")) ";
                }

                $tQueryResult = simple_queryall($tQuery);

                if (!empty($tQueryResult)) {
                    foreach ($tQueryResult as $eachRec) {
                        $paymentsToPush[$eachRec['id']] = $eachRec;
                    }
                }
            }
        }

        if (!empty($paymentsToPush)) {
            $this->getUsersDataCached();
            $needProcessUKV = $this->checkNeedProcessUKV($paymentsToPush);
            $ukv = $needProcessUKV ? new UkvSystem() : null;
            $allParentUsers = ($checkForCorpUsers and !$refiscalize) ? cu_GetAllParentUsers() : array();

            foreach ($paymentsToPush as $eachRecID => $eachRec) {
                $paymentSuccessful = false;
                $userLogin = $eachRec['userlogin'];
                $paySumm = $eachRec['summ'];

                if (!$refiscalize) {
                    if ($this->checkBankstaRowIsUnprocessed($eachRecID)) {
                        $cashType = $eachRec['payid'];
                        $operation = 'add';
                        $paymentDayTimeNote = (empty($eachRec['pdate'])) ? '' : ' ON ' . $eachRec['pdate'] . ' ' . $eachRec['ptime'];
                        $paymentNote = 'BANKSTA2: [' . $eachRecID . '] ASCONTRACT ' . $eachRec['usercontract'] . $paymentDayTimeNote;

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
                                        log_register('BANKSTA2 [' . $eachRecID . '] FAIL LOGIN (' . $userLogin . ')');
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
                            log_register('BANKSTA2 FAILED: payment record ID: [' . $eachRecID . '] for service: [' . $eachRec['service'] . '] for login: [' . $userLogin . ']. ' . __('Wrong format of a sum of money to pay'));
                        }
                    } else {
                        $this->setBankstaRecProcessed($eachRecID);
                        log_register('BANKSTA2 DUPLICATE PAYMENT PUSH TRY FOR REC ID: [' . $eachRecID . ']');
                    }
                }

                // dreamkas fiscalization routine
                if ($needToFiscalize
                    and !empty($insatiability)
                    and isset($fiscalDataArray[$eachRecID])
                    and ($paymentSuccessful or $refiscalize) ) {

                    $DreamKas = new DreamKas();

                    $rapacity_a = $insatiability['M']['KICKUP'];
                    $rapacity_b = $insatiability['M']['PICKUP'];
                    $rapacity_c = $insatiability['M']['PUSHCASHLO'];
                    $rapacity_d = $insatiability['M']['KANBARU'];
                    $rapacity_e = $insatiability['M']['SURUGA'];
                    $rapacity_z = $insatiability['M']['ONONOKI'];

                    $curRecFiscalData = $fiscalDataArray[$eachRecID];

                    $voracity_a = $curRecFiscalData[$insatiability['B2']['OIKURA']];
                    $voracity_b = $curRecFiscalData[$insatiability['B2']['SODACHI']];
                    $voracity_c = $curRecFiscalData[$insatiability['B2']['IZUKO']];

                    if (strtolower($eachRec[$insatiability['LT']['YOTSUGI']]) == $insatiability['LT']['MEME']) {
                        $voracity_d = $DreamKas->$rapacity_d($userLogin);
                        $voracity_e = $DreamKas->$rapacity_e($userLogin);
                    } else {
                        $voracity_d = $DreamKas->$rapacity_z($userLogin, $ukv);
                        $voracity_d = (empty($voracity_d)) ? '' : $voracity_d[$insatiability['AK']['ARARAGI']];
                        $voracity_e = '';
                    }

                    $voracity_f = array($curRecFiscalData[$insatiability['B2']['GAEN']] => array($insatiability['AK']['TSUKIHI'] => ($paySumm * 100)));
                    $voracity_g = array($insatiability['AK']['MAYOI'] => $voracity_e, $insatiability['AK']['OUGI'] => $voracity_d);

                    $voracity_h = $DreamKas->$rapacity_a($voracity_a, $voracity_b, $voracity_c, $voracity_f, $voracity_g);
                    $DreamKas->$rapacity_c($voracity_h, $eachRecID);
                    $voracity_i = $DreamKas->$rapacity_b();

                    if (!empty($voracity_i)) {
                        log_register('BANKSTA2 FAILED: payment record ID: [' . $eachRecID . '] fiscalization for service: [' . $eachRec['service'] . '] for login: [' . $userLogin . ']. Error message: ' . $voracity_i);
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
                                        $newAddress, $newRealname, $newNotes, $newPdate, $newPtime, $newSrvType,
                                        $newCancelRow, $paymentTypeID = 0) {
        if (empty($paymentTypeID)) {
            $newPaymentID = (strtolower($newSrvType) == 'internet') ? $this->inetPaymentId : $this->ukvPaymentId;
        } else {
            $newPaymentID = $paymentTypeID;
        }

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
        $this->getProcessedBSRecsCached();

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
     * Checks $paymentsToPush array for UKV records presence
     *
     * @param $paymentsToPush
     *
     * @return bool
     */
    public function checkNeedProcessUKV($paymentsToPush) {
        $result = false;

        if (!empty($paymentsToPush)) {
            foreach ($paymentsToPush as $eachRecID => $eachRec) {
                if (strtolower($eachRec['service']) == 'ukv') {
                    $result = true;
                    break;
                }
            }
        }

        return ($result);
    }

    /**
     * Returns main buttons controls for banksta2
     *
     * @return string
     */
    public static function web_MainButtonsControls() {
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

            $cells= wf_TableCell(__('Column number'));
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
            $bssrvidents_arr    = $rowNumArr + array('NONE' => __('Set to none'));

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
            $inputs = wf_TextInput('bspaymtypeid', __('Custom payment type ID for this bank statement'), 0, true, '4', 'digits', '', 'BankstaPaymentTypeID');
            $inputs.= wf_delimiter(0);
            $inputs.= wf_Selector('bsrealname_col', $bsrealname_arr, __('User realname'), '0', true);
            $inputs.= wf_delimiter(0);
            $inputs.= wf_Selector('bsaddress_col', $bsaddress_arr, __('User address'), '1', true);
            $inputs.= wf_delimiter(0);
            $inputs.= wf_Selector('bspaysum_col', $bspaysum_arr, __('Payment sum'), '2', true);
            $inputs.= wf_CheckInput('bspaymincoins', __('Bank statement "SUM" field presented in coins(need to be divided by 100)'), true, false, 'BankstaPaymInCoins');
            $inputs.= wf_delimiter(0);
            $inputs.= wf_Selector('bspaypurpose_col', $bspaypurpose_arr, __('Payment purpose'), '3', true);
            $inputs.= wf_delimiter(0);
            $inputs.= wf_Selector('bspaydate_col', $bspaydate_arr, __('Payment date'), '4', true);
            $inputs.= wf_delimiter(0);
            $inputs.= wf_Selector('bspaytime_col', $bspaytime_arr, __('Payment time'), '5', true);

            //contract defining controls
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

            //service types defining controls
            $inputs.= wf_delimiter(0);
            $inputs.= wf_Selector('bssrvtype', $this->bankstaServiceType, __('Service type'), '21', true, false, 'BankstaSrvType');
            $inputs.= wf_tag('div', false, '', 'id="BankstaServiceGuessingBlock" style="border: 1px solid #ddd; border-radius: 4px; padding: 4px"');
            $inputs.= wf_TextInput('bsinetdelimstart', __('Internet service before keywords delimiter string'), '', true, '', '', '', 'BankstaInetDelimStart');
            $inputs.= wf_TextInput('bsinetkeywords', __('Internet service determination keywords') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', '', true, '40', '', '', 'BankstaInetKeyWords');
            $inputs.= wf_TextInput('bsinetdelimend', __('Internet service after keywords delimiter string'), '', true, '', '', '', 'BankstaInetDelimEnd');
            $inputs.= wf_CheckInput('bsinetkeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, false, 'BankstaInetKeyWordsNoEsc');
            $inputs.= wf_delimiter(0);
            $inputs.= wf_TextInput('bsukvdelimstart', __('UKV service before keywords delimiter string'), '', true, '', '', '', 'BankstaUKVDelimStart');
            $inputs.= wf_TextInput('bsukvkeywords', __('UKV service determination keywords') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', '', true, '40', '', '', 'BankstaUKVKeyWords');
            $inputs.= wf_TextInput('bsukvdelimend', __('UKV service after keywords delimiter string'), '', true, '', '', '', 'BankstaUKVDelimEnd');
            $inputs.= wf_CheckInput('bsukvkeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, false, 'BankstaUKVKeyWordsNoEsc');
            $inputs.= wf_tag('div', true);
            $inputs.= wf_delimiter(0);

            //allotted service IDs controls
            $inputs.= wf_Selector('bssrvidents_col', $bssrvidents_arr, __('Number of the dedicated field which contains services IDs identifiers mapped via BANKSTA2_INETSRV_ALLOTED_IDS and BANKSTA2_CTVSRV_ALLOTED_IDS'), '6', true);
            $inputs.= wf_CheckInput('bssrvidentspreff', __('Services IDs identifiers from the dedicated field take precedence over service type telepathy'), true, false, 'BankstaSrvIdentsPreff');
            $inputs.= wf_tag('h4', false, '', 'style="font-weight: 400; width: 980px; padding: 2px 0 8px 28px; color: #666; margin-block-end: 0; margin-block-start: 0;"');
            $inputs.= __('NOTE: dedicated field\'s services IDs are always take precedence over manually chosen \'Internet\' or \'UKV\' services');
            $inputs.= wf_tag('h4', true);
            $inputs.= wf_delimiter(0);

            //row skipping controls
            $inputs.= wf_CheckInput('bsskiprow', __('Skip row processing if specified fields contain keywords below'), true, false, 'BankstaSkipRow');
            $inputs.= wf_tag('div', false, '', 'id="BankstaSkipRowBlock" style="border: 1px solid #ddd; border-radius: 4px; padding: 4px"');
            //$inputs.= wf_Selector('bsskiprow_col', $bsrealname_arr, __('Fields to check row skipping(multiple fields must be separated with comas)'), 'NONE', true);
            $inputs.= wf_TextInput('bsskiprow_col', __('Fields to check row skipping') . '(' . __('multiple fields must be separated with comas') . ')', '', true, '', '', '', 'BankstaSkipRowKeyWordsCols');
            $inputs.= wf_TextInput('bsskiprowkeywords', __('Row skipping determination keywords') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', '', true, '40', '', '', 'BankstaSkipRowKeyWords');
            $inputs.= wf_CheckInput('bsskiprowkeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, false, 'BankstaSkipRowKeyWordsNoEsc');
            $inputs.= wf_tag('div', true);
            $inputs.= wf_delimiter(0);

            //words/strings replacing controls
            $inputs.= wf_CheckInput('bsreplacestrs', __('Replace characters specified below in specified fields'), true, false, 'BankstaReplaceStrs');
            $inputs.= wf_tag('div', false, '', 'id="BankstaReplaceStrsBlock" style="border: 1px solid #ddd; border-radius: 4px; padding: 4px"');
            //$inputs.= wf_Selector('bscolsreplacestrs', $bsrealname_arr, __('Fields to perform replacing(multiple fields must be separated with comas)'), 'NONE', true);
            $inputs.= wf_TextInput('bscolsreplacestrs', __('Fields to perform replacing') . '(' . __('multiple fields must be separated with comas') . ')', '', true, '', '', '', 'BankstaReplaceStrsCols');
            $inputs.= wf_TextInput('bsstrstoreplace', __('Replaced characters or strings') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', '', true, '40', '', '', 'BankstaReplaceStrsChars');
            $inputs.= wf_TextInput('bsstrstoreplacewith', __('Replacing characters or string'), '', true, '40', '', '', 'BankstaReplaceStrsWith');
            $inputs.= wf_TextInput('bsreplacementscnt', __('Replacements count'), '', true, '40', '', '', 'BankstaReplaceStrsCnt');
            $inputs.= wf_CheckInput('bsreplacekeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, false, 'BankstaReplaceKeyWordsNoEsc');
            $inputs.= wf_tag('div', true);
            $inputs.= wf_delimiter(0);

            //words/strings removing controls
            $inputs.= wf_CheckInput('bsremovestrs', __('Remove characters specified below in specified fields'), true, false, 'BankstaRemoveStrs');
            $inputs.= wf_tag('div', false, '', 'id="BankstaRemoveStrsBlock" style="border: 1px solid #ddd; border-radius: 4px; padding: 4px"');
            //$inputs.= wf_Selector('bscolremovestrs', $bsrealname_arr, __('Fields to perform replacing(multiple fields must be separated with comas)'), 'NONE', true);
            $inputs.= wf_TextInput('bscolremovestrs', __('Fields to perform removing') . '(' . __('multiple fields must be separated with comas') . ')', '', true, '', '', '', 'BankstaRemoveStrsCols');
            $inputs.= wf_TextInput('bsstrstoremove', __('Removed characters or strings') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', '', true, '40', '', '', 'BankstaRemoveStrsChars');
            $inputs.= wf_CheckInput('bsremovekeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, false, 'BankstaRemoveKeyWordsNoEsc');
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
                                                                                    
                                                        console.log(fieldNam);                            
                                                        console.log(fieldVal);
                                                        console.log(ctrl);
                                                                                                        
                                                        switch(ctrl.prop("type")) { 
                                                            case "radio":
                                                            case "checkbox":   
                                                                ctrl.each(function() {
                                                                    if (fieldVal == true || fieldVal > 0) {
                                                                        //$(this).attr("checked", true);
                                                                        $(this).prop("checked", true).change();
                                                                    } else {
                                                                        //$(this).attr("checked", false);
                                                                        $(this).prop("checked", false).change();
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
                        
                        $(\'#BankstaReplaceStrs\').change(function () {
                            if ( $(\'#BankstaReplaceStrs\').is(\':checked\') ) {
                                $(\'#BankstaReplaceStrsBlock\').show();
                            } else {
                                $(\'#BankstaReplaceStrsBlock\').hide();
                            }    
                        });
                        
                        $(\'#BankstaRemoveStrs\').change(function () {
                            if ( $(\'#BankstaRemoveStrs\').is(\':checked\') ) {
                                $(\'#BankstaRemoveStrsBlock\').show();
                            } else {
                                $(\'#BankstaRemoveStrsBlock\').hide();
                            }    
                        });
                                                                      
                        $(document).ready(function() {
                            $(\'#BankstaContractGuessingBlock\').hide();    
                            $(\'#BankstaServiceGuessingBlock\').hide(); 
                            $(\'#BankstaSkipRowBlock\').hide();
                            $(\'#BankstaReplaceStrsBlock\').hide();
                            $(\'#BankstaRemoveStrsBlock\').hide();
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
        if ($this->translateLstChkFieldNames) {
            $captContract   = __('Contract');
            $captSumm       = __('Sum');
            $captAddr       = __('Address');
            $captRealName   = __('Real name');
            $captNotes      = __('Payment notes');
            $captPDate      = __('Payment date');
            $captPTime      = __('Payment time');
            $captSrvType    = __('Service type');
            $captCanceled   = __('Processing canceled');
        } else {
            $captContract   = '[contract]';
            $captSumm       = '[summ]';
            $captAddr       = '[address]';
            $captRealName   = '[realname]';
            $captNotes      = '[notes]';
            $captPDate      = '[pdate]';
            $captPTime      = '[ptime]';
            $captSrvType    = '[service_type]';
            $captCanceled   = '[row_canceled]';
        }

        $cells = wf_TableCell('#');
        $cells.= wf_TableCell($captContract);
        $cells.= wf_TableCell($captSumm);
        $cells.= wf_TableCell($captAddr);
        $cells.= wf_TableCell($captRealName);
        $cells.= wf_TableCell($captNotes);
        $cells.= wf_TableCell($captPDate);
        $cells.= wf_TableCell($captPTime);
        $cells.= wf_TableCell($captSrvType);
        $cells.= wf_TableCell($captCanceled);

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
    public static function web_FMPForm() {
        $lnkId = wf_InputId();
        $addServiceJS = wf_tag('script', false, '', 'type="text/javascript"');
        $addServiceJS.= wf_JSAjaxModalOpener(self::URL_ME, array('fmpcreate' => 'true'), $lnkId, false, 'POST');
        $addServiceJS.= wf_tag('script', true);

        show_window(__('Fields mapping presets'), wf_Link('#', web_add_icon() . ' ' .
                    __('Add fields mapping preset'), false, 'ubButton', 'id="' . $lnkId . '"') .
                    wf_delimiter() . $addServiceJS . self::renderFMPJQDT()
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
            $cells .= wf_TableCell(__('Payment type ID'));
            $cells .= wf_TableCell(__('Cash'));
            $cells .= wf_TableCell(__('Processed'));
            $cells .= wf_TableCell(__('Canceled'));
            $cells .= wf_TableCell(__('Contract'));
            $cells .= wf_TableCell(__('Real Name'));
            $cells .= wf_TableCell(__('Address'));
            $cells .= wf_TableCell(__('Tariff'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($tQueryResult)) {
                $this->getUsersDataCached();

                foreach ($tQueryResult as $io => $eachRec) {
                    $recProcessed = ($eachRec['processed']) ? true : false;
                    $recCanceled = ($eachRec['canceled']) ? true : false;
                    $serviceType = trim($eachRec['service_type']);
                    $addrIsEmpty = empty($eachRec['address']);
                    $nameIsEmpty = empty($eachRec['realname']);
                    $contractUnknown = (empty($eachRec['contract']) or ispos($eachRec['contract'], 'unknown_'));
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

                    if (($addrIsEmpty or $nameIsEmpty or $contractUnknown) and !empty($eachRec['notes'])) {
                        if ($addrIsEmpty or $contractUnknown) {
                            $cells .= wf_TableCell($eachRec['notes']);
                            $cells .= wf_TableCell('');
                        } else {
                            $cells .= wf_TableCell('');
                            $cells .= wf_TableCell($eachRec['notes']);
                        }
                    } else {
                        $cells .= wf_TableCell($eachRec['address']);
                        $cells .= wf_TableCell($eachRec['realname']);
                    }

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
                                    $cashPairs[$eachRec['id']]['pdate'] = $eachRec['pdate'];
                                    $cashPairs[$eachRec['id']]['ptime'] = $eachRec['ptime'];
                                    $cashPairs[$eachRec['id']]['payid'] = (empty($eachRec['payid'])) ? $this->inetPaymentId : $eachRec['payid'];
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
                                    $cashPairs[$eachRec['id']]['pdate'] = $eachRec['pdate'];
                                    $cashPairs[$eachRec['id']]['ptime'] = $eachRec['ptime'];
                                    $cashPairs[$eachRec['id']]['payid'] = (empty($eachRec['payid'])) ? $this->ukvPaymentId : $eachRec['payid'];
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

                                if (empty($dreamkasCtrls)) {
                                    $refiscalize = true;
                                }
                            }
                        }

                        if (empty($dreamkasCtrls)) {
                            $addFiscalizePaymentCtrlsJS = true;
                            $dreamkasCtrls = $DreamKas->web_FiscalizePaymentCtrls($serviceType, true, $eachRec['id'], $recProcessed);
                            $dreamkasCtrls.= $DreamKas->web_FiscalOperationDetailsTableRow($eachRec['id']);
                        }

                        $rows.= $dreamkasCtrls;
                    }
                }
            }

            $result = wf_TableBody($rows, '100%', '0', '');
            $result.= ($addFiscalizePaymentCtrlsJS) ? $DreamKas->get_BS2FiscalizePaymentCtrlsJS() : '';

            if (!empty($cashPairs) or $refiscalize) {
                $cashInputs = '';
                $formID     = wf_InputId();
                $submitID   = wf_InputId();
                $submitID2  = wf_InputId();
                $fiscalizeallChkID      = wf_InputId();
                $submitCaption          = __('Process current bank statement');
                $submitCaption2         = __('Re-fiscalize payments');
                $fiscalizeallChkCapt    = __('Check to fiscalize all');
                $unfiscalizeallChkCapt  = __('Clear check to fiscalize all');

                if ($dreamkasEnabled and (!empty($cashPairs) or $refiscalize)) {
                    //$cashInputs.= wf_delimiter(0);
                    $cashInputs.= wf_CheckInput('fiscalizeall', $fiscalizeallChkCapt, false, false, $fiscalizeallChkID);
                    $cashInputs.= wf_delimiter();
                }

                if (!empty($cashPairs)) {
                    $cashPairs = serialize($cashPairs);
                    $cashPairs = base64_encode($cashPairs);
                    $cashInputs.= wf_HiddenInput('bankstaneedpaymentspush', $cashPairs);
                    $cashInputs.= ($dreamkasEnabled) ? wf_HiddenInput('bankstafiscalrecsidslist', base64_encode(json_encode($fiscalRecsIDsList))) : '';
                    $cashInputs.= wf_Submit($submitCaption, $submitID);
                    $cashInputs.= wf_nbsp(4);
                } else {
                    $cashInputs.= wf_HiddenInput('bankstaneedpaymentspush', base64_encode(serialize('dummy_dump')));
                }

                if ($refiscalize) {
                    $cashInputs.= wf_Submit($submitCaption2, $submitID2);
                    $cashInputs.= wf_nbsp(2);
                }

                $cashInputs.= wf_HiddenInput('bankstaneedrefiscalize', 'false');
                $cashInputs.= ($dreamkasEnabled) ? wf_HiddenInput('bankstapaymentsfiscalize', '') : '';

                $result.= wf_Form('', 'POST', $cashInputs, 'glamour', '', $formID);

                if ($dreamkasEnabled) {
                    $result.= wf_tag('script', false, '', 'type="text/javascript"');
                    $result.= wf_JSEmptyFunc();
                    $result.= '
                                $(\'#' . $fiscalizeallChkID . '\').change(function() {
                                    var checkVal = $(this).is(\':checked\');
                                    $(\'[name^="fiscalizepayment_"]\').prop("checked", checkVal);
                                    
                                    if (checkVal) {
                                        $(\'label[for="' . $fiscalizeallChkID . '"]\').html(\'' . $unfiscalizeallChkCapt . '\');
                                    } else {
                                        $(\'label[for="' . $fiscalizeallChkID . '"]\').html(\'' . $fiscalizeallChkCapt . '\');
                                    } 
                                });
                    
                                $(\'#' . $submitID2 . '\').on("click mouseup keyup", function(evt) {                                    
                                    $(\'[name="bankstaneedrefiscalize"]\').val("true");
                                }); 
                    
                                $(\'#' . $formID . '\').submit(function(evt) {
                                    $(\'#' . $submitID . '\').attr("disabled", "disabled");
                                    $(\'#' . $submitID . '\').val("' . __('Form processing in progress') . '...' . '");
                                    $(\'#' . $submitID2 . '\').attr("disabled", "disabled");
                                    $(\'#' . $submitID2 . '\').val("' . __('Form processing in progress') . '...' . '");
                                    
                                    var fiscalizationArr = {};
                                    var refiscalize = ( $(\'[name="bankstaneedrefiscalize"]\').val() === "true" );
                           
                              ';

                    $result.= '     if (!refiscalize) {
                                        fiscalRecsIDsList = JSON.parse(atob($(\'[name="bankstafiscalrecsidslist"]\').val()));
                                    }
                                                        
                              ';
                    $result.= '     $(\'[name^="fiscalizepayment_"]\').each(function(chkindex, chkelement) {
                     
                                        if ($(chkelement).is(\':checked\')) {
                                            checkCtrlID = $(chkelement).attr("id").substring($(chkelement).attr("id").indexOf(\'_\') + 1);
                                                
                                        ';

                    $result.= '             if ( (refiscalize && $(chkelement).hasClass(\'__BankstaRecProcessed\'))  
                                                || (!refiscalize && $.inArray(checkCtrlID, fiscalRecsIDsList) != -1) ) {
                    
                                                fiscalizationArr[checkCtrlID] = {};
                                                    
                                                fiscalizationArr[checkCtrlID][\'drscashmachineid\'] = $(\'[name=drscashmachines_\'+checkCtrlID+\']\').val();
                                                fiscalizationArr[checkCtrlID][\'drstaxtype\'] = $(\'[name=drstaxtypes_\'+checkCtrlID+\']\').val();
                                                fiscalizationArr[checkCtrlID][\'drspaymtype\'] = $(\'[name=drspaymtypes_\'+checkCtrlID+\']\').val();
                                                fiscalizationArr[checkCtrlID][\'drssellingpos\'] = $(\'[name=drssellpos_\'+checkCtrlID+\']\').val();
                                            }
                                        ';

                    $result.= '         }
                                    });
                   
                                    if (refiscalize && empty(fiscalizationArr)) {
                                        evt.preventDefault();
                                        $(\'#' . $submitID . '\').removeAttr("disabled");
                                        $(\'#' . $submitID . '\').val("' . $submitCaption . '");
                                        $(\'#' . $submitID2 . '\').removeAttr("disabled");
                                        $(\'#' . $submitID2 . '\').val("' . $submitCaption2 . '");
                                        alert(\'' . __('No records to re-fiscalize chosen') . '\');
                                        return false; 
                                    }
                                             
                                    $(\'[name="bankstapaymentsfiscalize"]\').val(btoa(JSON.stringify(fiscalizationArr)));                                    
                                });
                              ';
                    $result.= wf_tag('script', true);
                } else {
                    $result.= wf_tag('script', false, '', 'type="text/javascript"');
                    $result.= '
                                $(\'#' . $formID . '\').submit(function(evt) {
                                    $(\'#' . $submitID . '\').attr("disabled", "disabled");
                                    $(\'#' . $submitID . '\').val("' . __('Form processing in progress') . '...' . '");
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
    public static function renderBStatementsListJSON() {
        $tQuery = "SELECT `filename`, `hash`, `date`, `admin`, 
                          COUNT(`id`) AS `rowcount`, COUNT(if(`processed` > 0 and `canceled` <= 0, 1, null)) AS processed_cnt, COUNT(if(`canceled` > 0, 1, null)) AS canceled_cnt
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
    public static function renderBStatementsJQDT() {
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
    public static function renderFMPListJSON() {
        $tQuery = "SELECT `id`, `presetname`, `payment_type_id`, `col_realname`, `col_address`, `col_paysum`, `col_paypurpose`, 
                          `col_paydate`, `col_paytime`, `col_contract`, `guess_contract`, `skip_row`, 
                          `replace_strs`, `remove_strs`, `service_type`  
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
                        case 'replace_strs':
                        case 'remove_strs':
                            $data[] = ($fieldVal == 1) ? web_green_led() : web_red_led();
                            break;

                        default:
                            $data[] = $fieldVal;
                    }
                }

                $linkId1 = wf_InputId();
                $linkId2 = wf_InputId();
                $actions = wf_JSAlert(  '#', web_delete_icon(), 'Removing this may lead to irreparable results', 'deleteFMP(' . $eachRec['id'] . ', \'' . self::URL_ME . '\', \'delFMP\', \'' . wf_InputId() . '\')') . wf_nbsp();
                $actions.= wf_Link('#', web_edit_icon(), false, '', 'id="' . $linkId1 . '"') . wf_nbsp();
                $actions.= wf_Link('#', web_clone_icon(), false, '', 'id="' . $linkId2 . '"') . wf_nbsp();
                $actions.= wf_JSAjaxModalOpener(self::URL_ME, array('fmpedit' => 'true', 'fmpid' => $fmpID), $linkId1, true, 'POST');
                $actions.= wf_JSAjaxModalOpener(self::URL_ME, array('fmpclone' => 'true', 'fmpid' => $fmpID), $linkId2, true, 'POST');

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
    protected static function renderFMPJQDT() {
        $ajaxUrlStr = '' . self::URL_ME . '&fmpajax=true';
        $jqdtId = 'jqdt_' . md5($ajaxUrlStr);
        $errorModalWindowId = wf_InputId();
        $columns = array();
        $opts = '"order": [[ 0, "asc" ]],
                "columnDefs": [ {"targets": "_all", "className": "dt-center"} ]';

        $columns[] = __('ID');
        $columns[] = __('Preset name');
        $columns[] = __('Payment type ID');
        $columns[] = __('Realname column');
        $columns[] = __('Address column');
        $columns[] = __('Paysum column');
        $columns[] = __('Paypurpose column');
        $columns[] = __('Paydate column');
        $columns[] = __('Paytime column');
        $columns[] = __('Contract column');
        $columns[] = __('Contract guessing');
        $columns[] = __('Row skipping');
        $columns[] = __('Char replacing');
        $columns[] = __('Char removing');
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

        $inputs = wf_TextInput('fmpname', __('Preset name'), '', false, '', '', '__FMPEmptyCheck');
        $inputs.= wf_nbsp(8);
        $inputs.= wf_TextInput('fmppaymtypeid', __('Custom payment type ID for this bank statement'), 0, true, '4', 'digits', '', 'BankstaPaymentTypeID');

        $inputscells = wf_TableCell(wf_TextInput('fmpcolrealname', __('Real name column number'), 'NONE', false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcoladdr', __('Address column number'), 'NONE', false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaysum', __('Payment sum column number'), 'NONE', false, '4'));
        $inputsrows = wf_TableRow($inputscells);

        $inputscells = wf_TableCell(wf_TextInput('fmpcolpaypurpose', __('Payment purpose column number'), 'NONE', false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaydate', __('Payment date column number'), 'NONE', false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaytime', __('Payment time column number'), 'NONE', true, '4'));
        $inputsrows.= wf_TableRow($inputscells);

        $inputscells = wf_TableCell(wf_CheckInput('fmppaymincoins', __('Bank statement "SUM" field presented in coins(need to be divided by 100)'), true, false, 'BankstaPaymInCoins'), '', '', '', '2');
        $inputsrows.= wf_TableRow($inputscells);
        $inputs.= wf_TableBody($inputsrows, '', '0', '', 'cellspacing="4px" style="margin-top: 8px;"');

        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_TextInput('fmpcolcontract', __('User contract column number') . ' (' . __('Payment ID') . ')', 'NONE', true, '4');
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
        $inputs.= wf_Selector('fmpsrvtype', $this->bankstaServiceType, __('Service type') . ' (' . __('select "Telepathy" to try to get service type from payment purpose field') . ')', '', true, false, 'BankstaSrvType');
        $inputs.= wf_TextInput('fmpinetdelimstart', __('Internet service before keywords delimiter string'), '', true, '', '', '', 'BankstaInetDelimStart');
        $inputs.= wf_TextInput('fmpinetkeywords', __('Internet service determination keywords') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', '', true, '40', '', '', 'BankstaInetKeyWords');
        $inputs.= wf_TextInput('fmpinetdelimend', __('Internet service after keywords delimiter string'), '', true, '', '', '', 'BankstaInetDelimEnd');
        $inputs.= wf_CheckInput('fmpinetkeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, false, 'BankstaInetKeyWordsNoEsc');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_TextInput('fmpukvdelimstart', __('UKV service before keywords delimiter string'), '', true, '', '', '', 'BankstaUKVDelimStart');
        $inputs.= wf_TextInput('fmpukvkeywords', __('UKV service determination keywords') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', '', true, '40', '', '', 'BankstaUKVKeyWords');
        $inputs.= wf_TextInput('fmpukvdelimend', __('UKV service after keywords delimiter string'), '', true, '', '', '', 'BankstaUKVDelimEnd');
        $inputs.= wf_CheckInput('fmpukvkeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, false, 'BankstaUKVKeyWordsNoEsc');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_TextInput('fmpcolsrvidents', __('Number of the dedicated field which contains services IDs identifiers mapped via BANKSTA2_INETSRV_ALLOTED_IDS and BANKSTA2_CTVSRV_ALLOTED_IDS'), 'NONE', true, '4');
        $inputs.= wf_CheckInput('fmpsrvidentspreff', __('Services IDs identifiers from the dedicated field take precedence over service type telepathy'), true, false, 'BankstaSrvIdentsPreff');
        $inputs.= wf_tag('h4', false, '', 'style="font-weight: 400; width: 980px; padding: 2px 0 8px 28px; color: #666; margin-block-end: 0; margin-block-start: 0;"');
        $inputs.= __('NOTE: dedicated field\'s services IDs are always take precedence over manually chosen \'Internet\' or \'UKV\' services');
        $inputs.= wf_tag('h4', true);
        $inputs.= wf_delimiter(0);
        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_CheckInput('fmpskiprow', __('Skip row processing if specified fields contain keywords below'), true, false, 'BankstaSkipRow');
        $inputs.= wf_TextInput('fmpcolskiprow', __('Fields to check row skipping') . '(' . __('multiple fields must be separated with comas') . ')', '', true, '', '', '', 'BankstaSkipRowKeyWordsCol');
        $inputs.= wf_TextInput('fmpskiprowkeywords', __('Row skipping determination keywords') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', '', true, '40', '', '', 'BankstaSkipRowKeyWords');
        $inputs.= wf_CheckInput('fmpskiprokeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, false, 'BankstaSkipRowKeyWordsNoEsc');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_CheckInput('fmpreplacestrs', __('Replace characters specified below in specified fields'), true, false, 'BankstaReplaceStrs');
        $inputs.= wf_TextInput('fmpcolsreplacestrs', __('Fields to perform replacing') . '(' . __('multiple fields must be separated with comas') . ')', '', true, '', '', '', 'BankstaReplaceStrsCols');
        $inputs.= wf_TextInput('fmpstrstoreplace', __('Replaced characters or strings') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', '', true, '40', '', '', 'BankstaReplaceStrsChars');
        $inputs.= wf_TextInput('fmpstrstoreplacewith', __('Replacing characters or string'), '', true, '40', '', '', 'BankstaReplaceStrsWith');
        $inputs.= wf_TextInput('fmpstrsreplacecount', __('Replacements count'), '', true, '40', '', '', 'BankstaReplaceStrsCount');
        $inputs.= wf_CheckInput('fmpreplacekeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, false, 'BankstaReplaceKeyWordsNoEsc');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_CheckInput('fmpremovestrs', __('Remove characters specified below in specified fields'), true, false, 'BankstaRemoveStrs');
        $inputs.= wf_TextInput('fmpcolsremovestrs', __('Fields to perform removing') . '(' . __('multiple fields must be separated with comas') . ')', '', true, '', '', '', 'BankstaRemoveStrsCols');
        $inputs.= wf_TextInput('fmpstrstoremove', __('Removed characters or strings') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', '', true, '40', '', '', 'BankstaRemoveStrsChars');
        $inputs.= wf_CheckInput('fmpremovekeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, false, 'BankstaRemoveKeyWordsNoEsc');
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
    public function renderFMPEditForm($fmpID, $modalWindowId, $clone = false) {
        $this->getMappingPresetsCached();
        $formId             = 'Form_' . wf_InputId();
        $closeFormChkId     = 'CloseFrmChkID_' . wf_InputId();

        $fmpData            = $this->fieldsMappingPresets[$fmpID];
        $colRealName        = (wf_emptyNonZero($fmpData['col_realname']) ? 'NONE' : $fmpData['col_realname']);
        $colAddress         = (wf_emptyNonZero($fmpData['col_address']) ? 'NONE' : $fmpData['col_address']);
        $colPaysum          = (wf_emptyNonZero($fmpData['col_paysum']) ? 'NONE' : $fmpData['col_paysum']);
        $colPayPurpose      = (wf_emptyNonZero($fmpData['col_paypurpose']) ? 'NONE' : $fmpData['col_paypurpose']);
        $colPayDate         = (wf_emptyNonZero($fmpData['col_paydate']) ? 'NONE' : $fmpData['col_paydate']);
        $colPayTime         = (wf_emptyNonZero($fmpData['col_paytime']) ? 'NONE' : $fmpData['col_paytime']);
        $colContract        = (wf_emptyNonZero($fmpData['col_contract']) ? 'NONE' : $fmpData['col_contract']);
        $colSrvIdents       = (wf_emptyNonZero($fmpData['col_srvidents']) ? 'NONE' : $fmpData['col_srvidents']);
        $contractGuessing   = (empty($fmpData['guess_contract'])) ? false : true;
        $prefferSrvIdents   = (empty($fmpData['srvidents_preffered'])) ? false : true;
        $rowSkipping        = (empty($fmpData['skip_row'])) ? false : true;
        $strReplacing       = (empty($fmpData['replace_strs'])) ? false : true;
        $strRemoving        = (empty($fmpData['remove_strs'])) ? false : true;
        $sumInCoins         = (empty($fmpData['sum_in_coins'])) ? false : true;
        $noescInetKeyWords  = (empty($fmpData['noesc_inet_srv_keywords'])) ? false : true;
        $noescUKVKeyWords   = (empty($fmpData['noesc_ukv_srv_keywords'])) ? false : true;
        $noescSkipKeyWords  = (empty($fmpData['noesc_skip_row_keywords'])) ? false : true;
        $noescRplcKeyWords  = (empty($fmpData['noesc_replace_keywords'])) ? false : true;
        $noescRmvKeyWords   = (empty($fmpData['noesc_remove_keywords'])) ? false : true;

        $inputs = wf_TextInput('fmpname', __('Preset name'), $fmpData['presetname'], false, '', '', '__FMPEmptyCheck');
        $inputs.= wf_nbsp(8);
        $inputs.= wf_TextInput('fmppaymtypeid', __('Custom payment type ID for this bank statement'), $fmpData['payment_type_id'], true, '4', 'digits', '', 'BankstaPaymentTypeID');

        $inputscells = wf_TableCell(wf_TextInput('fmpcolrealname', __('Real name column number'), $colRealName, false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcoladdr', __('Address column number'), $colAddress, false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaysum', __('Payment sum column number'), $colPaysum, false, '4'));
        $inputsrows = wf_TableRow($inputscells);

        $inputscells = wf_TableCell(wf_TextInput('fmpcolpaypurpose', __('Payment purpose column number'), $colPayPurpose, false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaydate', __('Payment date column number'), $colPayDate, false, '4'));
        $inputscells.= wf_TableCell(wf_TextInput('fmpcolpaytime', __('Payment time column number'), $colPayTime, true, '4'));
        $inputsrows.= wf_TableRow($inputscells);

        $inputscells = wf_TableCell(wf_CheckInput('fmppaymincoins', __('Bank statement "SUM" field presented in coins(need to be divided by 100)'), true, $sumInCoins, 'BankstaPaymInCoins'), '', '', '', '2');
        $inputsrows.= wf_TableRow($inputscells);
        $inputs.= wf_TableBody($inputsrows, '', '0', '', 'cellspacing="4px" style="margin-top: 8px;"');

        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_TextInput('fmpcolcontract', __('User contract column number') . ' (' . __('Payment ID') . ')', $colContract, true, '4');
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
        $inputs.= wf_Selector('fmpsrvtype', $this->bankstaServiceType, __('Service type') . ' (' . __('select "Telepathy" to try to get service type from payment purpose field') . ')', $fmpData['service_type'], true, false, 'BankstaSrvType');
        $inputs.= wf_TextInput('fmpinetdelimstart', __('Internet service before keywords delimiter string'), $fmpData['inet_srv_start_delim'], true, '', '', '', 'BankstaInetDelimStart');
        $inputs.= wf_TextInput('fmpinetkeywords', __('Internet service determination keywords') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', $fmpData['inet_srv_keywords'], true, '40', '', '', 'BankstaInetKeyWords');
        $inputs.= wf_TextInput('fmpinetdelimend', __('Internet service after keywords delimiter string'), $fmpData['inet_srv_end_delim'], true, '', '', '', 'BankstaInetDelimEnd');
        $inputs.= wf_CheckInput('fmpinetkeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, $noescInetKeyWords, 'BankstaInetKeyWordsNoEsc');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_TextInput('fmpukvdelimstart', __('UKV service before keywords delimiter string'), $fmpData['ukv_srv_start_delim'], true, '', '', '', 'BankstaUKVDelimStart');
        $inputs.= wf_TextInput('fmpukvkeywords', __('UKV service determination keywords') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', $fmpData['ukv_srv_keywords'], true, '40', '', '', 'BankstaUKVKeyWords');
        $inputs.= wf_TextInput('fmpukvdelimend', __('UKV service after keywords delimiter string'), $fmpData['ukv_srv_end_delim'], true, '', '', '', 'BankstaUKVDelimEnd');
        $inputs.= wf_CheckInput('fmpukvkeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, $noescUKVKeyWords, 'BankstaUKVKeyWordsNoEsc');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_TextInput('fmpcolsrvidents', __('Number of the dedicated field which contains services IDs identifiers mapped via BANKSTA2_INETSRV_ALLOTED_IDS and BANKSTA2_CTVSRV_ALLOTED_IDS'), $colSrvIdents, true, '4');
        $inputs.= wf_CheckInput('fmpsrvidentspreff', __('Services IDs identifiers from the dedicated field take precedence over service type telepathy'), true, $prefferSrvIdents, 'BankstaSrvIdentsPreff');
        $inputs.= wf_tag('h4', false, '', 'style="font-weight: 400; width: 980px; padding: 2px 0 8px 28px; color: #666; margin-block-end: 0; margin-block-start: 0;"');
        $inputs.= __('NOTE: dedicated field\'s services IDs are always take precedence over manually chosen \'Internet\' or \'UKV\' services');
        $inputs.= wf_tag('h4', true);
        $inputs.= wf_delimiter(0);
        $inputs.= wf_tag('hr', false, '', 'style="margin-bottom: 11px;"');
        $inputs.= wf_CheckInput('fmpskiprow', __('Skip row processing if specified fields contain keywords below'), true, $rowSkipping, 'BankstaSkipRow');
        $inputs.= wf_TextInput('fmpcolskiprow', __('Fields to check row skipping') . '(' . __('multiple fields must be separated with comas') . ')', $fmpData['col_skiprow'], true, '', '', '', 'BankstaSkipRowKeyWordsCol');
        $inputs.= wf_TextInput('fmpskiprowkeywords', __('Row skipping determination keywords') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', $fmpData['skip_row_keywords'], true, '40', '', '', 'BankstaSkipRowKeyWords');
        $inputs.= wf_CheckInput('fmpskiprokeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, $noescSkipKeyWords, 'BankstaSkipRowKeyWordsNoEsc');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_CheckInput('fmpreplacestrs', __('Replace characters specified below in specified fields'), true, $strReplacing, 'BankstaReplaceStrs');
        $inputs.= wf_TextInput('fmpcolsreplacestrs', __('Fields to perform replacing') . '(' . __('multiple fields must be separated with comas') . ')', $fmpData['col_replace_strs'], true, '', '', '', 'BankstaReplaceStrsCols');
        $inputs.= wf_TextInput('fmpstrstoreplace', __('Replaced characters or strings') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', $fmpData['strs_to_replace'], true, '40', '', '', 'BankstaReplaceStrsChars');
        $inputs.= wf_TextInput('fmpstrstoreplacewith', __('Replacing characters or string'), $fmpData['strs_to_replace_with'], true, '40', '', '', 'BankstaReplaceStrsWith');
        $inputs.= wf_TextInput('fmpstrsreplacecount', __('Replacements count'), $fmpData['replacements_cnt'], true, '40', '', '', 'BankstaReplaceStrsCount');
        $inputs.= wf_CheckInput('fmpreplacekeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, $noescRplcKeyWords, 'BankstaReplaceKeyWordsNoEsc');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_CheckInput('fmpremovestrs', __('Remove characters specified below in specified fields'), true, $strRemoving, 'BankstaRemoveStrs');
        $inputs.= wf_TextInput('fmpcolsremovestrs', __('Fields to perform removing') . '(' . __('multiple fields must be separated with comas') . ')', $fmpData['col_remove_strs'], true, '', '', '', 'BankstaRemoveStrsCols');
        $inputs.= wf_TextInput('fmpstrstoremove', __('Removed characters or strings') . ', ' . __('separated with') . ' BANKSTA2_REGEX_KEYWORDS_DELIM', $fmpData['strs_to_remove'], true, '40', '', '', 'BankstaRemoveStrsChars');
        $inputs.= wf_CheckInput('fmpremovekeywordsnoesc', __('Don\'t escape or process in any other way the keywords - just left them "as is"'), true, $noescRmvKeyWords, 'BankstaRemoveKeyWordsNoEsc');
        $inputs.= wf_delimiter(0);

        $inputs.= wf_CheckInput('formclose', __('Close form after operation'), false, true, $closeFormChkId, '__CloseFrmOnSubmitChk');

        $inputs.= wf_HiddenInput('', $modalWindowId, '', '__FMPFormModalWindowId');
        $inputs.= ($clone) ? wf_HiddenInput('fmpclone', 'true') : wf_HiddenInput('fmpedit', 'true');
        $inputs.= wf_HiddenInput('fmpid', $fmpID);
        $inputs.= wf_delimiter();
        $inputs.= ($clone) ? wf_Submit(__('Clone')) : wf_Submit(__('Edit'));
        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __FMPForm', '', $formId);

        return ($form);
    }
}