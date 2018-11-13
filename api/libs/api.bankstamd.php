<?php

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
    protected $allowedExtensions = array('xls');

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
    protected $debug = true;

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
    protected $bankstafoundusers=array();

    /**
     * Creates new BankstaMd instance
     */
    public function __construct() {
        $this->loadAlter();
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
                    if ($this->debug) {
                        debarr($eachRow);
                    }

                    if ($importCounter >= $this->skipRecords) {
                        if (!empty($eachRow)) {
                            $newDate = date("Y-m-d H:i:s");
                            $newContract = trim($eachRow[$this->bsContractOff]);
                            $newContract = mysql_real_escape_string($newContract);
                            $newSumm = trim($eachRow[$this->bsSumOff]);
                            $newSumm = mysql_real_escape_string($newSumm);
                            $newAddress = mysql_real_escape_string($eachRow[$this->bsAddressOff]);
                            $newRealname = mysql_real_escape_string($eachRow[$this->bsRealnameOff]);
                            $newNotes = '';
                            $timeStamp = strtotime($eachRow[$this->bsDateOff]);
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

}
