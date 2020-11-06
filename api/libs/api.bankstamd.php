<?php

/**
 * XLS Bank statements processing class
 */
class BankstaMd {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains available users data as login=>userdata
     *
     * @var array
     */
    protected $allUsersData = array();

    /**
     * Temp array for previous bank statements
     *
     * @var array
     */
    protected $bankstarecords = array();

    /**
     * Contains available contracts mappings as contract=>login
     *
     * @var array
     */
    protected $contracts = array();

    /**
     * List of allowed extensions
     *
     * @var array
     */
    protected $allowedExtensions = array('xls', 'xlsx');

    /**
     * Excel reader object placeholder
     *
     * @var object
     */
    protected $excelReader = '';

    /**
     * Just debug flag
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Default storage table name
     */
    const BANKSTA_TABLE = 'bankstamd';

    /**
     * Routing URLs
     */
    const URL_ME = '?module=bankstamd';
    const URL_BANKSTA_MGMT = '?module=bankstamd&banksta=true';
    const URL_BANKSTA_PROCESSING = '?module=bankstamd&banksta=true&showhash=';
    const URL_BANKSTA_DETAILED = '?module=bankstamd&banksta=true&showdetailed=';
    const URL_USERS_PROFILE = '?module=userprofile&username=';

    /**
     * Some banksta options
     */
    const BANKSTA_PATH = 'content/documents/bankstamd/';

    protected $skipRecords = 2;
    protected $bsContractOff = 0;
    protected $bsRealnameOff = 1;
    protected $bsAddressOff = 2;
    protected $bsSumOff = 3;
    protected $bsDateOff = 4;
    protected $contractNumeric = 0;

    /**
     * Default payment ID to push banksta payments
     *
     * @var int
     */
    protected $bsPaymentId = 1;

    /**
     * Contains detected users as contract=>login
     *
     * @var array
     */
    protected $bankstafoundusers = array();

    /**
     * Creates new BankstaMd instance
     */
    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
        $this->loadUserData();
    }

    /**
     * Loads system alter config into protected prop
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
     * Sets some config based options
     * 
     * @return void
     */
    protected function setOptions() {
        if (@$this->altCfg['BANKSTAMD_PAYID']) {
            $this->bsPaymentId = $this->altCfg['BANKSTAMD_PAYID'];
        }
        if (@$this->altCfg['BANKSTAMD_DEBUG']) {
            $this->debug = true;
        }

        //some custom options if required 
        if (@$this->altCfg['BANKSTAMD_OPTIONS']) {
            $rawOpts = explode('|', $this->altCfg['BANKSTAMD_OPTIONS']);
            if (sizeof($rawOpts) >= 7) {
                if ($this->debug) {
                    show_window(__('Custom options'), wf_tag('pre') . print_r($rawOpts, true) . wf_tag('pre', true));
                }
                $this->skipRecords = trim($rawOpts[0]); //skip offset 0 
                $this->bsContractOff = trim($rawOpts[1]); //contract offset 1
                $this->bsRealnameOff = trim($rawOpts[2]); //realname offset 2
                $this->bsAddressOff = trim($rawOpts[3]); //address offset 3
                $this->bsSumOff = trim($rawOpts[4]); //summ offset 4
                $this->bsDateOff = trim($rawOpts[5]); //date offset 5
                $this->contractNumeric = trim($rawOpts[6]); //numeric contract filter offset 6
            } else {
                show_error(__('Something went wrong') . ': BANKSTAMD_OPTIONS ' . __('wrong format'));
            }
        }
    }

    /**
     * Loads all available users data from database
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUsersData = zb_UserGetAllData();
        if (!empty($this->allUsersData)) {
            foreach ($this->allUsersData as $io => $each) {
                if (!empty($each['contract'])) {
                    $this->contracts[$each['contract']] = $each['login'];
                }
            }
        }
    }

    /**
     * Inits reader object and performs file parsing
     * 
     * @param string $filePath
     * 
     * @return void
     */
    protected function initExcelReader($filePath) {
        require('api/vendor/excel/excel_reader2.php');
        require('api/vendor/excel/SpreadsheetReader.php');
        $this->excelReader = new SpreadsheetReader($filePath);
    }

    /**
     * Returns bank statement upload form
     * 
     * @return string
     */
    public function renderBankstaLoadForm() {
        $uploadinputs = wf_HiddenInput('uploadbankstamd', 'true');
        $uploadinputs .= __('Bank statement') . wf_tag('br');
        $uploadinputs .= wf_tag('input', false, '', 'id="fileselector" type="file" name="bankstamd"') . wf_tag('br');
        $uploadinputs .= wf_Submit('Upload');
        $uploadform = bs_UploadFormBody('', 'POST', $uploadinputs, 'glamour');
        return ($uploadform);
    }

    /**
     * Process of uploading of bank statement
     * 
     * @return array
     */
    public function bankstaDoUpload() {
        $result = array();
        $extCheck = true;
        //check file type
        foreach ($_FILES as $file) {
            if ($file['tmp_name'] > '') {
                if (@!in_array(end(explode(".", strtolower($file['name']))), $this->allowedExtensions)) {
                    $extCheck = false;
                }
            }
        }

        if ($extCheck) {
            $filename = $_FILES['bankstamd']['name'];
            $uploadfile = self::BANKSTA_PATH . $filename;

            if (move_uploaded_file($_FILES['bankstamd']['tmp_name'], $uploadfile)) {
                $fileContent = file_get_contents(self::BANKSTA_PATH . $filename);
                $fileHash = md5($fileContent);
                $fileContent = ''; //free some memory
                if ($this->bankstaCheckHash($fileHash)) {
                    $result = array(
                        'filename' => $_FILES['bankstamd']['name'],
                        'savedname' => $filename,
                        'hash' => $fileHash
                    );
                } else {
                    log_register('BANKSTAMD DUPLICATE TRY ' . $fileHash);
                    show_error(__('Same bank statement already exists'));
                }
            } else {
                show_error(__('Cant upload file to') . ' ' . self::BANKSTA_PATH);
            }
        } else {
            show_error(__('Wrong file type'));
            log_register('BANKSTAMD WRONG FILETYPE');
        }
        return ($result);
    }

    /**
     * checks is banksta hash unique?
     * 
     * @param string $hash  bank statement raw content hash
     * 
     * @return bool
     */
    protected function bankstaCheckHash($hash) {
        $query = "SELECT `id` from `" . self::BANKSTA_TABLE . "` WHERE `hash`='" . $hash . "';";
        $data = simple_query($query);
        if (empty($data)) {
            return (true);
        } else {
            return (false);
        }
    }

    /**
     * Creates new banksta row in Database
     * 
     * @param string $newDate
     * @param string $newHash
     * @param string $newFilename
     * @param string $newAdmin
     * @param string $newContract
     * @param string $newSumm
     * @param string $newAddress
     * @param string $newRealname
     * @param string $newNotes
     * @param string $newPate
     * @param string $newPtime
     * 
     * @return void
     */
    protected function bankstaCreateRow($newDate, $newHash, $newFilename, $newAdmin, $newContract, $newSumm, $newAddress, $newRealname, $newNotes, $newPdate, $newPtime) {
        $query = "INSERT INTO `" . self::BANKSTA_TABLE . "` (`id`, `date`, `hash`, `filename`, `admin`, `contract`, `summ`, `address`, `realname`, `notes`, `pdate`, `ptime`, `processed`)
                                VALUES (
                                NULL ,
                                '" . $newDate . "',
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
                                '0'
                                );
                            ";
        nr_query($query);
    }

    /**
     * new banksta store in database bankstaDoUpload() method and returns preprocessed
     * bank statement hash for further usage
     * 
     * @param $bankstadata   array returned from doUpload
     * 
     * @return string
     */
    public function bankstaPreprocessing($bankstadata) {
        $result = '';
        if (!empty($bankstadata)) {
            if (file_exists(self::BANKSTA_PATH . $bankstadata['savedname'])) {
//processing raw data
                $newHash = $bankstadata['hash'];
                $result = $newHash;
                $newFilename = $bankstadata['filename'];
                $newAdmin = whoami();

                $this->initExcelReader(self::BANKSTA_PATH . $bankstadata['savedname']);
                $importCounter = 0;
                foreach ($this->excelReader as $eachRow) {
                    if ($importCounter >= $this->skipRecords) {
                        if (!empty($eachRow)) {
                            if ($this->debug) {
                                debarr($eachRow);
                            }
                            $newDate = date("Y-m-d H:i:s");
                            @$newContract = trim($eachRow[$this->bsContractOff]);
                            $newContract = mysql_real_escape_string($newContract);
                            //filter only numeric values from contract
                            if ($this->contractNumeric) {
                                $newContract = vf($newContract, 3);
                            }
                            @$newSumm = trim($eachRow[$this->bsSumOff]);
                            $newSumm = mysql_real_escape_string($newSumm);
                            $newSumm = str_replace(' ', '', $newSumm);
                            @$newAddress = mysql_real_escape_string($eachRow[$this->bsAddressOff]);
                            @$newRealname = mysql_real_escape_string($eachRow[$this->bsRealnameOff]);
                            $newNotes = '';
                            @$timeStamp = strtotime($eachRow[$this->bsDateOff]);
                            if (!empty($timeStamp)) {
                                $newPdate = date("Y-m-d", $timeStamp);
                                $newPtime = date("H:i:s", $timeStamp);
                            } else {
                                $newPdate = '';
                                $newPtime = '';
                            }
                            //pushing row into database
                            if ((!empty($newContract)) AND ( !empty($newSumm))) {
                                $this->bankstaCreateRow($newDate, $newHash, $newFilename, $newAdmin, $newContract, $newSumm, $newAddress, $newRealname, $newNotes, $newPdate, $newPtime);
                            }
                        }
                    }
                    $importCounter++;
                }

                log_register('BANKSTAMD IMPORTED ' . ($importCounter - $this->skipRecords) . ' ROWS');
            } else {
                show_error(__('Strange exeption'));
            }
        } else {
            throw new Exception(self::EX_BANKSTA_PREPROCESS_EMPTY);
        }
        return ($result);
    }

    /**
     * Catches file upload form and performs basic banksta preprocessing
     * 
     * @return void
     */
    public function catchUploadRequest() {
        if (wf_CheckPost(array('uploadbankstamd'))) {
            $uploadResult = $this->bankstaDoUpload();
            if ($this->debug) {
                debarr($uploadResult);
            }
            if (!empty($uploadResult)) {
                $this->bankstaPreprocessing($uploadResult);
            }
        }
    }

    /**
     * Renders bank statements list container
     * 
     * @return type
     */
    public function renderBankstaList() {
        $result = '';
        $columns = array(__('Date'), __('Filename'), __('Rows'), __('Admin'), __('Actions'));
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_BANKSTA_MGMT . '&ajbslist=true', false, __('Bank statement'), 50, $opts);
        return ($result);
    }

    /**
     * Renders bank statements list datatables json datasource
     * 
     * @return void
     */
    public function bankstaRenderAjaxList() {
        $query = "SELECT `filename`,`hash`,`date`,`admin`,COUNT(`id`) AS `rowcount` FROM `" . self::BANKSTA_TABLE . "` GROUP BY `hash` ORDER BY `date` DESC;";
        $all = simple_queryall($query);

        $json = new wf_JqDtHelper();
        if (!empty($all)) {
            foreach ($all as $io => $each) {

                $data[] = $each['date'];
                $data[] = $each['filename'];
                $data[] = $each['rowcount'];
                $data[] = $each['admin'];
                $actLinks = wf_Link(self::URL_BANKSTA_PROCESSING . $each['hash'], wf_img('skins/icon_search_small.gif', __('Show')), false, '');
                $data[] = $actLinks;

                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * returns banksta processing form for some hash
     * 
     * @param string $hash  existing preprocessing bank statement hash
     * 
     * @return string
     */
    public function bankstaProcessingForm($hash) {
        $hash = mysql_real_escape_string($hash);
        $query = "SELECT * from `" . self::BANKSTA_TABLE . "` WHERE `hash`='" . $hash . "' ORDER BY `id` ASC;";
        $all = simple_queryall($query);
        $cashPairs = array();

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Address'));
        $cells .= wf_TableCell(__('Real Name'));
        $cells .= wf_TableCell(__('Contract'));
        $cells .= wf_TableCell(__('Cash'));
        $cells .= wf_TableCell(__('Processed'));
        $cells .= wf_TableCell(__('Contract'));
        $cells .= wf_TableCell(__('Real Name'));
        $cells .= wf_TableCell(__('Address'));
        $cells .= wf_TableCell(__('Tariff'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $addInfoControl = wf_Link(self::URL_BANKSTA_DETAILED . $each['id'], $each['id'], false, '');
                $processed = ($each['processed']) ? true : false;

                $cells = wf_TableCell($addInfoControl);
                $cells .= wf_TableCell($each['address']);
                $cells .= wf_TableCell($each['realname']);

                if (!$processed) {
                    $editInputs = wf_TextInput('newbankcontr', '', $each['contract'], false, '6');
                    $editInputs .= wf_CheckInput('lockbankstarow', __('Lock'), false, false);
                    $editInputs .= wf_HiddenInput('bankstacontractedit', $each['id']);
                    $editInputs .= wf_Submit(__('Save'));
                    $editForm = wf_Form('', 'POST', $editInputs);
                } else {
                    $editForm = $each['contract'];
                }
                $cells .= wf_TableCell($editForm);
                $cells .= wf_TableCell($each['summ']);
                $cells .= wf_TableCell(web_bool_led($processed));
//user detection 
                if (isset($this->contracts[$each['contract']])) {
                    $detectedUser = $this->allUsersData[$this->contracts[$each['contract']]];
                    $detectedContract = wf_Link(self::URL_USERS_PROFILE . $detectedUser['login'], web_profile_icon() . ' ' . $detectedUser['contract'], false, '');
                    $detectedAddress = $detectedUser['fulladress'];
                    $detectedRealName = $detectedUser['realname'];
                    $detectedTariff = $detectedUser['Tariff'];

                    if (!$processed) {
                        $cashPairs[$each['id']]['bankstaid'] = $each['id'];
                        $cashPairs[$each['id']]['userlogin'] = $detectedUser['login'];
                        $cashPairs[$each['id']]['usercontract'] = $detectedUser['contract'];
                        $cashPairs[$each['id']]['summ'] = $each['summ'];
                        $cashPairs[$each['id']]['payid'] = $this->bsPaymentId;
                    }

                    $rowClass = 'row3';
//try to highlight multiple payments
                    if (!isset($this->bankstafoundusers[$each['contract']])) {
                        $this->bankstafoundusers[$each['contract']] = $detectedUser['login'];
                    } else {
                        $rowClass = 'ukvbankstadup';
                    }
                } else {
                    $detectedContract = '';
                    $detectedAddress = '';
                    $detectedRealName = '';
                    $detectedTariff = '';
                    if ($each['processed'] == 1) {
                        $rowClass = 'row2';
                    } else {
                        $rowClass = 'undone';
                    }
                }

                $cells .= wf_TableCell($detectedContract);
                $cells .= wf_TableCell($detectedRealName);
                $cells .= wf_TableCell($detectedAddress);
                $cells .= wf_TableCell($detectedTariff);
                $rows .= wf_TableRow($cells, $rowClass);
            }
        }

        $result = wf_TableBody($rows, '100%', '0', '');

        if (!empty($cashPairs)) {
            $cashPairs = serialize($cashPairs);
            $cashPairs = base64_encode($cashPairs);
            $cashInputs = wf_HiddenInput('bankstaneedpaymentspush', $cashPairs);
            $cashInputs .= wf_Submit(__('Bank statement processing'));
            $result .= wf_Form('', 'POST', $cashInputs, 'glamour');
        }


        return ($result);
    }

    /**
     * returns detailed banksta row info
     * 
     * @param int $id   existing banksta ID
     * 
     * @return string
     */
    public function bankstaGetDetailedRowInfo($id) {
        $id = vf($id, 3);
        $query = "SELECT * from `" . self::BANKSTA_TABLE . "` WHERE `id`='" . $id . "'";
        $dataRaw = simple_query($query);
        $result = '';
        $result.= wf_BackLink(self::URL_BANKSTA_PROCESSING . $dataRaw['hash']);
        $result.= wf_delimiter();

        if (!empty($dataRaw)) {
            $result.= wf_tag('pre', false, 'floatpanelswide', '') . print_r($dataRaw, true) . wf_tag('pre', true);
            $result.= wf_CleanDiv();
        }
        return ($result);
    }

    /**
     * loads all of banksta rows to further checks to private prop
     * 
     * @return void
     */
    protected function loadBankstaAll() {
        $query = "SELECT * from `" . self::BANKSTA_TABLE . "`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->bankstarecords[$each['id']] = $each;
            }
        }
    }

    /**
     * cnahges banksta contract number for some existing row
     * 
     * @param int $bankstaid    existing bank statement transaction ID
     * @param string $contract     new contract number for this row
     * 
     * @return void
     */
    public function bankstaSetContract($bankstaid, $contract) {
        $bankstaid = vf($bankstaid, 3);
        $contract = mysql_real_escape_string($contract);
        $contract = trim($contract);
        if (empty($this->bankstarecords)) {
            $this->loadBankstaAll();
        }

        if (isset($this->bankstarecords[$bankstaid])) {
            $oldContract = $this->bankstarecords[$bankstaid]['contract'];
            simple_update_field(self::BANKSTA_TABLE, 'contract', $contract, "WHERE `id`='" . $bankstaid . "';");
            log_register('BANKSTAMD [' . $bankstaid . '] CONTRACT `' . $oldContract . '` CHANGED ON `' . $contract . '`');
        } else {
            log_register('BANKSTAMD NONEXIST [' . $bankstaid . '] CONTRACT CHANGE TRY');
        }
    }

    /**
     * checks is banksta row ID unprocessed?
     * 
     * @param int $bankstaid   existing banksta row ID
     * 
     * @return bool
     */
    protected function bankstaIsUnprocessed($bankstaid) {
        $result = false;
        if (isset($this->bankstarecords[$bankstaid])) {
            if ($this->bankstarecords[$bankstaid]['processed'] == 0) {
                $result = true;
            } else {
                $result = false;
            }
        }
        return ($result);
    }

    /**
     * sets banksta row as processed
     * 
     * @param int $bankstaid  existing bank statement ID
     * 
     * @return void
     */
    public function bankstaSetProcessed($bankstaid, $logging = true) {
        $bankstaid = vf($bankstaid, 3);
        $this->bankstarecords[$bankstaid]['processed'] = 1;
        simple_update_field(self::BANKSTA_TABLE, 'processed', 1, "WHERE `id`='" . $bankstaid . "'");
        if ($logging) {
            log_register('BANKSTAMD [' . $bankstaid . '] LOCKED');
        }
    }

    /**
     * push payments to some user accounts via bank statements
     * 
     * @return void
     */
    public function bankstaPushPayments() {
        if (wf_CheckPost(array('bankstaneedpaymentspush'))) {
            $rawData = base64_decode($_POST['bankstaneedpaymentspush']);
            $rawData = unserialize($rawData);
            if (!empty($rawData)) {
                if (empty($this->bankstarecords)) {
                    $this->loadBankstaAll();
                }

                foreach ($rawData as $io => $eachstatement) {
                    if ($this->bankstaIsUnprocessed($eachstatement['bankstaid'])) {
                        //all good is with this row
                        // push payment and mark banksta as processed
                        $paymentNote = 'BANKSTA: [' . $eachstatement['bankstaid'] . '] ASCONTRACT ' . $eachstatement['usercontract'];
                        $userLogin = $eachstatement['userlogin'];
                        $summ = $eachstatement['summ'];
                        $summ = abs($summ);
                        if (!empty($userLogin)) {
                            if (isset($this->allUsersData[$userLogin])) {
                                if (zb_checkMoney($summ)) {
                                    $this->bankstaSetProcessed($eachstatement['bankstaid'], false);
                                    zb_CashAdd($eachstatement['userlogin'], $summ, 'add', $this->bsPaymentId, $paymentNote);
                                }
                            } else {
                                log_register('BANKSTAMD [' . $eachstatement['bankstaid'] . '] FAIL LOGIN (' . $userLogin . ')');
                            }
                        } else {
                            log_register('BANKSTAMD [' . $eachstatement['bankstaid'] . '] FAIL EMPTY LOGIN');
                        }
                    } else {
                        //duplicate payment try
                        log_register('BANKSTAMD TRY DUPLICATE [' . $eachstatement['bankstaid'] . '] PAYMENT PUSH');
                    }
                }
            }
        }
    }

}
