<?php

/*
 * DOCx profile documents base class
 */

class UsProfileDocuments {

    protected $templates = array();
    protected $userLogin = '';
    protected $userData = array();
    protected $userAgentData = array();
    protected $customFields = array();
    protected $altcfg = array();
    protected $userDocuments = array();
    public $tEMPLATES_PATH = '';
    public $dOCUMENTS_PATH = '';

    public function __construct() {
        $this->loadTemplates();
        $this->altcfg = zbs_LoadConfig();
        $this->tEMPLATES_PATH = $this->altcfg['DOCX_STORAGE'] . 'pl_docx/';
        $this->dOCUMENTS_PATH = $this->altcfg['DOCX_STORAGE'] . 'pl_cache/';
    }

    /**
     * load templates into private prop
     * 
     * @return void
     */
    protected function loadTemplates() {
        $query = "SELECT * from `docxtemplates` WHERE `public`='1';";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->templates[$each['id']] = $each;
            }
        }
    }

    /**
     * Sets user login
     * @param $login existing users login
     * 
     * @return void
     */
    public function setLogin($login) {
        $login = mysql_real_escape_string($login);
        $this->userLogin = $login;
    }

    /**
     * gets current user login
     * 
     * @return string
     */
    public function getLogin() {
        return ($this->userLogin);
    }

    /**
     * gets user data by previously setted login
     * 
     * @return array
     */
    public function getUserData() {
        if (!empty($this->userLogin)) {
            if (isset($this->userData[$this->userLogin])) {
                $currentUserData = $this->userData[$this->userLogin];
                return ($currentUserData);
            } else {
                throw new Exception('NO_USER_DATA_FOUND');
            }
        } else {
            throw new Exception('NO_USER_LOGIN_SET');
        }
    }

    /**
     * Loads current user assigned agent data into private property
     * 
     * @return void
     */
    protected function loadUserAgentData() {
        if (!empty($this->userLogin)) {
            $rawData = zbs_AgentAssignedGetDataFast($this->userLogin, $this->userData[$this->userLogin]['ADDRESS']);
            @$this->userAgentData['AGENTEDRPO'] = $rawData['edrpo'];
            @$this->userAgentData['AGENTNAME'] = $rawData['contrname'];
            @$this->userAgentData['AGENTID'] = $rawData['id'];
            @$this->userAgentData['AGENTBANKACC'] = $rawData['bankacc'];
            @$this->userAgentData['AGENTBANKNAME'] = $rawData['bankname'];
            @$this->userAgentData['AGENTBANKCODE'] = $rawData['bankcode'];
            @$this->userAgentData['AGENTIPN'] = $rawData['ipn'];
            @$this->userAgentData['AGENTLICENSE'] = $rawData['licensenum'];
            @$this->userAgentData['AGENTJURADDR'] = $rawData['juraddr'];
            @$this->userAgentData['AGENTPHISADDR'] = $rawData['phisaddr'];
            @$this->userAgentData['AGENTPHONE'] = $rawData['phone'];
        }
    }

    /**
     * Returns current user assigned agent data
     * 
     * @return array
     */
    public function getUserAgentData() {
        if (!empty($this->userLogin)) {
            $this->loadUserAgentData();
            return ($this->userAgentData);
        } else {
            throw new Exception('NO_USER_LOGIN_SET');
        }
    }

    /**
     * returns last generated ID from documents registry
     * 
     * @return int
     */
    protected function getDocumentLastId() {
        $query = "SELECT `id` from `docxdocuments` ORDER BY `id` DESC LIMIT 1";
        $data = simple_query($query);
        if (!empty($data)) {
            $result = $data['id'];
        } else {
            $result = 0;
        }
        return ($result);
    }

    /**
     * Returns contract dates data
     * 
     * @return array
     */
    protected function getContractDatesAll() {
        $query = "SELECT `login`,`contract` from `contracts`";
        $allcontracts = simple_queryall($query);
        $queryDates = "SELECT `contract`,`date` from `contractdates`";
        $alldates = simple_queryall($queryDates);
        $result = array();
        $dates = array();
        if (!empty($alldates)) {
            foreach ($alldates as $ia => $eachdate) {
                $dates[$eachdate['contract']] = $eachdate['date'];
            }
        }

        if (!empty($allcontracts)) {
            foreach ($allcontracts as $io => $eachcontract) {
                $result[$eachcontract['login']]['contractnum'] = $eachcontract['contract'];
                if (isset($dates[$eachcontract['contract']])) {
                    $result[$eachcontract['login']]['contractdate'] = $dates[$eachcontract['contract']];
                } else {
                    $result[$eachcontract['login']]['contractdate'] = '1970-01-01';
                }
            }
        }

        return($result);
    }

    /**
     * loads user data for template processing 
     * 
     * @return void
     */
    public function loadAllUserData() {
        $userdata = array();

        $alluserdata = zbs_UserGetStargazerData($this->userLogin);
        $tariffspeeds = zbs_TariffGetSpeed($alluserdata['Tariff']);
        $tariffprices = zbs_TariffGetAllPrices();
        $allcontract = zbs_UserGetContract($this->userLogin);
        $contractDates = $this->getContractDatesAll();
        $allrealnames = zbs_UserGetAllRealnames();
        $alladdress = zbs_AddressGetFulladdresslist();
        $allemail = zbs_UserGetEmail($this->userLogin);
        $lastDocId = $this->getDocumentLastId();
        $newDocId = $lastDocId + 1;

        $curdate = date("Y-m-d");


        if ($this->altcfg['OPENPAYZ_REALID']) {
            $allopcustomer = zbs_PaymentIDGet($this->userLogin);
        }

        if (!empty($alluserdata)) {

            $userdata[$alluserdata['login']]['LOGIN'] = $alluserdata['login'];
            $userdata[$alluserdata['login']]['PASSWORD'] = $alluserdata['Password'];
            $userdata[$alluserdata['login']]['TARIFF'] = $alluserdata['Tariff'];
            @$userdata[$alluserdata['login']]['TARIFFPRICE'] = $tariffprices[$alluserdata['Tariff']];
            $userdata[$alluserdata['login']]['CASH'] = $alluserdata['Cash'];
            $userdata[$alluserdata['login']]['CREDIT'] = $alluserdata['Credit'];
            $userdata[$alluserdata['login']]['DOWN'] = $alluserdata['Down'];
            $userdata[$alluserdata['login']]['PASSIVE'] = $alluserdata['Passive'];
            $userdata[$alluserdata['login']]['AO'] = $alluserdata['AlwaysOnline'];
            @$userdata[$alluserdata['login']]['CONTRACT'] = $allcontract;
            @$userdata[$alluserdata['login']]['CONTRACTDATE'] = $contractDates[$this->userLogin]['contractdate'];
            @$userdata[$alluserdata['login']]['REALNAME'] = $allrealnames[$alluserdata['login']];
            @$userdata[$alluserdata['login']]['ADDRESS'] = $alladdress[$alluserdata['login']];
            @$userdata[$alluserdata['login']]['EMAIL'] = $allemail;
            //openpayz payment ID
            if ($this->altcfg['OPENPAYZ_REALID']) {
                @$userdata[$alluserdata['login']]['PAYID'] = $allopcustomer;
            } else {
                @$userdata[$alluserdata['login']]['PAYID'] = ip2int($alluserdata['IP']);
            }
            //traffic params
            $userdata[$alluserdata['login']]['TRAFFIC'] = $alluserdata['D0'] + $alluserdata['U0'];
            $userdata[$alluserdata['login']]['TRAFFICDOWN'] = $alluserdata['D0'];
            $userdata[$alluserdata['login']]['TRAFFICUP'] = $alluserdata['U0'];

            //net params
            $userdata[$alluserdata['login']]['IP'] = $alluserdata['IP'];
            //tariffs speed
            $userdata[$alluserdata['login']]['SPEEDDOWN'] = $tariffspeeds;


            //other document data
            @$userdata[$alluserdata['login']]['CURDATE'] = $curdate;
            @$userdata[$alluserdata['login']]['DOCID'] = $newDocId;
        }

        $this->userData = $userdata;
    }

    /**
     * Returns available document templates prop
     * 
     * @return array
     */
    public function getTemplates() {
        return ($this->templates);
    }

    /**
     * returns available templates list
     * 
     * @return string
     */
    public function renderTemplatesList() {
        $cells = '';
        $cells.= la_TableCell(__('Names'));
        $rows = la_TableRow($cells, 'row1');

        if (!empty($this->templates)) {
            foreach ($this->templates as $io => $each) {
                $cells = '';
                $actlinks = la_Link('?module=zdocs&print=' . $each['id'], $each['name'], false, '');
                $cells.= la_TableCell($actlinks);
                $rows.= la_TableRow($cells, 'row3');
            }
        }
        $result = la_TableBody($rows, '100%', '0', '');
        return ($result);
    }

    /**
     * register generated document in database
     * 
     * @param $login - current user login
     * @param $templateid - existing template ID
     * @param $path path to file in storage
     * 
     * @return void
     */
    public function registerDocument($login, $templateid, $path) {
        $login = mysql_real_escape_string($login);
        $templateid = vf($templateid, 3);
        $path = mysql_real_escape_string($path);
        $date = date("Y-m-d H:i:s");

        $query = "
            INSERT INTO `docxdocuments` (
                `id` ,
                `date` ,
                `login` ,
                `public` ,
                `templateid` ,
                `path`
                )
                VALUES (
                NULL , '" . $date . "', '" . $login . "', '1', '" . $templateid . "', '" . $path . "'
                );
            ";
        nr_query($query);
    }

    /**
     * loads user documents from database
     * 
     * @param $login user login to search public docs
     * 
     * @return void
     */
    public function loadUserDocuments($login) {
        $query = "SELECT * from `docxdocuments` WHERE `login`='" . $this->userLogin . "' AND `public`='1' ORDER BY `id` DESC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->userDocuments[$each['id']] = $each;
            }
        }
    }

    /**
     * Renders previously generated user documents 
     * 
     * @return string
     */
    public function renderUserDocuments() {
        $cells = la_TableCell(__('ID'));
        $cells.= la_TableCell(__('Date'));
        $cells.= la_TableCell(__('Document name'));
        $rows = la_TableRow($cells, 'row1');

        if (!empty($this->userDocuments)) {
            foreach ($this->userDocuments as $io => $each) {
                $cells = la_TableCell($each['id']);
                $cells.= la_TableCell($each['date']);
                @$templateName = $this->templates[$each['templateid']]['name'];
                $downloadLink = la_Link('?module=zdocs&documentdownload=' . $each['id'], $templateName, false, '');
                $cells.= la_TableCell($downloadLink);
                $rows.= la_TableRow($cells, 'row3');
            }
        }

        $result = la_TableBody($rows, '100%', '0', '');
        return ($result);
    }

    /**
     * returns custom documents form fields
     * 
     * @return string
     */
    public function customDocumentFieldsForm() {
        $rawServices = $this->altcfg['DOCX_SERVICES'];
        $availServices = array();

        if (!empty($rawServices)) {
            $rawServices = explode(',', $rawServices);
            if (!empty($rawServices)) {
                foreach ($rawServices as $io => $each) {
                    $availServices[__($each)] = __($each);
                }
            }
        }

        $inputs = la_DatePickerPreset('customdate', date("Y-m-d"));
        $inputs.= la_tag('br');
        $inputs.= la_TextInput('customrealname', __('Real Name'), @$this->userData[$this->userLogin]['REALNAME'], true, '20');
        $inputs.= la_TextInput('customphone', __('Phone'), @$this->userData[$this->userLogin]['PHONE'], true, '10');
        $inputs.= la_Selector('customservice', $availServices, __('Service'), '', 'true');
        $inputs.= la_TextInput('customnotes', __('Notes'), '', true, '20');
        $inputs.= la_TextInput('customsum', __('Sum'), @$this->userData[$this->userLogin]['TARIFFPRICE'], true, '10');
        $inputs.= la_HiddenInput('customfields', 'true');
        $inputs.= la_Submit(__('Create'));
        $result = la_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * sets some custom template fields from post request
     * 
     * @return void
     */
    public function setCustomFields() {
        $pdvPercent = $this->altcfg['DOCX_NDS'];
        if (la_CheckPost(array('customfields'))) {
            $morph = new UBMorph();
            @$this->customFields['CUSTDATE'] = $_POST['customdate'];
            @$this->customFields['CUSTREALNAME'] = $_POST['customrealname'];
            @$this->customFields['CUSTPHONE'] = $_POST['customphone'];
            @$this->customFields['CUSTSERVICE'] = $_POST['customservice'];
            @$this->customFields['CUSTNOTES'] = $_POST['customnotes'];

            @$this->customFields['CUSTSUM'] = $_POST['customsum'];
            @$this->customFields['CUSTPHONE'] = $_POST['customphone'];
            @$pdv = ($this->customFields['CUSTSUM'] / 100) * $pdvPercent;
            @$this->customFields['PDV'] = $pdv;
            @$this->customFields['CUSTSUMPDV'] = $this->customFields['CUSTSUM'] + $pdv;
            @$this->customFields['CUSTSUMLIT'] = $morph->sum2str($this->customFields['CUSTSUM']);
            @$this->customFields['CUSTSUMPDVLIT'] = $morph->sum2str($this->customFields['CUSTSUMPDV']);
        }
    }

    /**
     * receives custom fields from object
     * 
     * @return array
     */
    public function getCustomFields() {
        return ($this->customFields);
    }

    /**
     * downloads previous users document and check it validity
     * 
     * @param $documentid id of existing document
     * 
     * @return void
     */
    public function downloadUserDocument($documentid) {
        $documentid = vf($documentid, 3);
        if (!empty($documentid)) {
            if (isset($this->userDocuments[$documentid])) {
                $documentFileName = $this->userDocuments[$documentid]['path'];
                $fullPath = $this->dOCUMENTS_PATH . $documentFileName;
                zbs_DownloadFile($fullPath, 'docx');
            } else {
                show_window(__('Sorry'), __('No such document'));
            }
        }
    }

}

?>