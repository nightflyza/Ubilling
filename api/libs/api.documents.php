<?php

/*
 * DOCx profile documents base class
 */

class ProfileDocuments {

    protected $templates = array();
    protected $userLogin = '';
    protected $userData = array();
    protected $userAgentData = array();
    protected $customFields = array();
    protected $altcfg = array();
    protected $userDocuments = array();
    protected $allUserDocuments = array();

    const TEMPLATES_PATH = 'content/documents/pl_docx/';
    const DOCUMENTS_PATH = 'content/documents/pl_cache/';

    public function __construct() {
        $this->loadTemplates();
        $this->altcfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    }

    /**
     * load templates into private prop
     * 
     * @return void
     */
    protected function loadTemplates() {
        $query = "SELECT * from `docxtemplates`";
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
            $rawData = zb_AgentAssignedGetDataFast($this->userLogin, $this->userData[$this->userLogin]['ADDRESS']);
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
        $alluserdata = zb_UserGetAllStargazerData();
        $tariffspeeds = zb_TariffGetAllSpeeds();
        $tariffprices = zb_TariffGetPricesAll();
        $multinetdata = zb_MultinetGetAllData();
        $allcontracts = zb_UserGetAllContracts();
        $allcontracts = array_flip($allcontracts);
        $contractDates = $this->getContractDatesAll();
        $allphonedata = zb_UserGetAllPhoneData();
        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        $allemails = zb_UserGetAllEmails();
        $allnasdata = zb_NasGetAllData();
        $allcfdata = cf_FieldsGetAll();
        $allpdata = zb_UserPassportDataGetAll();
        $curdate = curdate();
        $lastDocId = $this->getDocumentLastId();
        $newDocId = $lastDocId + 1;


        if ($this->altcfg['OPENPAYZ_REALID']) {
            $allopcustomers = zb_TemplateGetAllOPCustomers();
        }

        if (!empty($alluserdata)) {
            foreach ($alluserdata as $io => $eachuser) {
                $userdata[$eachuser['login']]['LOGIN'] = $eachuser['login'];
                $userdata[$eachuser['login']]['PASSWORD'] = $eachuser['Password'];
                $userdata[$eachuser['login']]['USERHASH'] = crc16($eachuser['login']);
                $userdata[$eachuser['login']]['TARIFF'] = $eachuser['Tariff'];
                @$userdata[$eachuser['login']]['TARIFFPRICE'] = $tariffprices[$eachuser['Tariff']];
                $userdata[$eachuser['login']]['CASH'] = $eachuser['Cash'];
                $userdata[$eachuser['login']]['CREDIT'] = $eachuser['Credit'];
                $userdata[$eachuser['login']]['DOWN'] = $eachuser['Down'];
                $userdata[$eachuser['login']]['PASSIVE'] = $eachuser['Passive'];
                $userdata[$eachuser['login']]['AO'] = $eachuser['AlwaysOnline'];
                @$userdata[$eachuser['login']]['CONTRACT'] = $allcontracts[$eachuser['login']];
                @$userdata[$eachuser['login']]['CONTRACTDATE'] = $contractDates[$eachuser['login']]['contractdate'];
                @$userdata[$eachuser['login']]['REALNAME'] = $allrealnames[$eachuser['login']];
                @$userdata[$eachuser['login']]['ADDRESS'] = $alladdress[$eachuser['login']];
                @$userdata[$eachuser['login']]['EMAIL'] = $allemails[$eachuser['login']];
                @$userdata[$eachuser['login']]['PHONE'] = $allphonedata[$eachuser['login']]['phone'];
                @$userdata[$eachuser['login']]['MOBILE'] = $allphonedata[$eachuser['login']]['mobile'];

                //openpayz payment ID
                if ($this->altcfg['OPENPAYZ_REALID']) {
                    @$userdata[$eachuser['login']]['PAYID'] = $allopcustomers[$eachuser['login']];
                } else {
                    @$userdata[$eachuser['login']]['PAYID'] = ip2int($eachuser['IP']);
                }
                //traffic params
                $userdata[$eachuser['login']]['TRAFFIC'] = $eachuser['D0'] + $eachuser['U0'];
                $userdata[$eachuser['login']]['TRAFFICDOWN'] = $eachuser['D0'];
                $userdata[$eachuser['login']]['TRAFFICUP'] = $eachuser['U0'];

                //net params
                @$userdata[$eachuser['login']]['IP'] = $eachuser['IP'];
                @$userdata[$eachuser['login']]['MAC'] = $multinetdata[$eachuser['IP']]['mac'];
                @$userdata[$eachuser['login']]['NETID'] = $multinetdata[$eachuser['IP']]['netid'];
                @$userdata[$eachuser['login']]['HOSTID'] = $multinetdata[$eachuser['IP']]['id'];
                //nas data
                @$usernas = zb_NasGetParams($multinetdata[$eachuser['IP']]['netid'], $allnasdata);
                @$userdata[$eachuser['login']]['NASID'] = $usernas['id'];
                @$userdata[$eachuser['login']]['NASIP'] = $usernas['nasip'];
                @$userdata[$eachuser['login']]['NASNAME'] = $usernas['nasname'];
                @$userdata[$eachuser['login']]['NASTYPE'] = $usernas['nastype'];

                if (isset($tariffspeeds[$eachuser['Tariff']])) {
                    $userdata[$eachuser['login']]['SPEEDDOWN'] = $tariffspeeds[$eachuser['Tariff']]['speeddown'];
                    $userdata[$eachuser['login']]['SPEEDUP'] = $tariffspeeds[$eachuser['Tariff']]['speedup'];
                } else {
                    //if no tariff speed defined zero speed by default
                    $userdata[$eachuser['login']]['SPEEDDOWN'] = 0;
                    $userdata[$eachuser['login']]['SPEEDUP'] = 0;
                }


                //passport data
                @$userdata[$eachuser['login']]['PBIRTH'] = $allpdata[$eachuser['login']]['birthdate'];
                @$userdata[$eachuser['login']]['PNUM'] = $allpdata[$eachuser['login']]['passportnum'];
                @$userdata[$eachuser['login']]['PDATE'] = $allpdata[$eachuser['login']]['passportdate'];
                @$userdata[$eachuser['login']]['PWHO'] = $allpdata[$eachuser['login']]['passportwho'];
                @$userdata[$eachuser['login']]['PCITY'] = $allpdata[$eachuser['login']]['pcity'];
                @$userdata[$eachuser['login']]['PSTREET'] = $allpdata[$eachuser['login']]['pstreet'];
                @$userdata[$eachuser['login']]['PBUILD'] = $allpdata[$eachuser['login']]['pbuild'];
                @$userdata[$eachuser['login']]['PAPT'] = $allpdata[$eachuser['login']]['papt'];

                //other document data
                @$userdata[$eachuser['login']]['CURDATE'] = $curdate;
                @$userdata[$eachuser['login']]['DOCID'] = $newDocId;
            }
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
        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Admin'));
        $cells.= wf_TableCell(__('Public'));
        $cells.= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Path'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->templates)) {
            foreach ($this->templates as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['date']);
                $cells.= wf_TableCell($each['admin']);
                $cells.= wf_TableCell(web_bool_led($each['public']));
                $cells.= wf_TableCell($each['name']);
                $cells.= wf_TableCell($each['path']);
                $actlinks = wf_JSAlert('?module=pl_documents&deletetemplate=' . $each['id'] . '&username=' . $this->userLogin, web_delete_icon(), 'Removing this may lead to irreparable results') . ' ';
                $actlinks.= wf_Link('?module=pl_documents&download=' . $each['path'] . '&username=' . $this->userLogin, wf_img('skins/icon_download.png', __('Download'))) . ' ';
                $actlinks.= wf_Link('?module=pl_documents&print=' . $each['id'] . '&custom=true&username=' . $this->userLogin, wf_img('skins/icon_print.png') . ' ' . __('Print'), false, 'ubButton');
                //$actlinks.= wf_Link('?module=pl_documents&print=' . $each['id'] . '&custom=true&username=' . $this->userLogin, wf_img('skins/icon_print_options.png', __('Custom options')));
                //$actlinks.= wf_Link('?module=pl_documents&print=' . $each['id'] . '&username=' . $this->userLogin, wf_img('skins/icon_print.png', __('Print')));
                $cells.= wf_TableCell($actlinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * returns template upload form 
     * 
     * @return string
     */
    public function uploadForm() {
        $uploadinputs = wf_HiddenInput('uploadtemplate', 'true');
        $uploadinputs.= wf_TextInput('templatedisplayname', __('Template display name'), '', true, '15');
        $uploadinputs.= wf_CheckInput('publictemplate', __('Template is public'), true, false);
        $uploadinputs.=__('Upload new document template from HDD') . wf_tag('br');
        $uploadinputs.=wf_tag('input', false, '', 'id="fileselector" type="file" name="uldocxtempplate"') . wf_tag('br');

        $uploadinputs.=wf_Submit('Upload');
        $uploadform = bs_UploadFormBody('', 'POST', $uploadinputs, 'glamour');
        return ($uploadform);
    }

    /**
     * register uploaded template into database
     * 
     * @param $path string            path to template file
     * @param $displayname string     template display name
     * @param $public      int        is template accesible from userstats
     * 
     * @return void
     */
    protected function registerTemplateDB($path, $displayname, $public) {
        $path = mysql_real_escape_string($path);
        $displayname = mysql_real_escape_string($displayname);
        $public = vf($public, 3);
        $admin = whoami();
        $date = curdatetime();
        $query = "INSERT INTO `docxtemplates` (`id`, `date`, `admin`, `public`, `name`, `path`) 
                VALUES (NULL, '" . $date . "', '" . $admin . "', '" . $public . "', '" . $displayname . "', '" . $path . "');";
        nr_query($query);
        log_register("PLDOCS ADD TEMPLATE `" . $displayname . "`");
    }

    /**
     * unregister existing document template
     * 
     * @param $id int   existing template id
     * 
     * @return void
     */
    protected function unregisterTemplateDB($id) {
        $id = vf($id, 3);
        $query = "DELETE from `docxtemplates` WHERE `id`='" . $id . "';";
        nr_query($query);
        log_register("PLDOCS DEL TEMPLATE [" . $id . "]");
    }

    /**
     * deletes existing template
     * 
     * @param $id int   existing template id
     * 
     * @return void
     */
    public function deleteTemplate($id) {
        $id = vf($id, 3);
        $this->unregisterTemplateDB($id);
    }

    /**
     * do the docx template upload subroutine
     * 
     * @return boolean
     */
    public function doUpload() {
        $uploaddir = self::TEMPLATES_PATH;
        $allowedExtensions = array("docx");
        $result = false;
        $extCheck = true;

        //check file type
        foreach ($_FILES as $file) {
            if ($file['tmp_name'] > '') {
                if (@!in_array(end(explode(".", strtolower($file['name']))), $allowedExtensions)) {
                    $extCheck = false;
                }
            }
        }

        if ($extCheck) {
            if (wf_CheckPost(array('templatedisplayname'))) {
                $displayName = $_POST['templatedisplayname'];
                $templatePublic = (isset($_POST['publictemplate'])) ? 1 : 0;

                $filename = zb_rand_string(8) . '.docx';
                $uploadfile = $uploaddir . $filename;

                if (move_uploaded_file($_FILES['uldocxtempplate']['tmp_name'], $uploadfile)) {
                    $result = true;
                    //save template into database
                    $this->registerTemplateDB($filename, $displayName, $templatePublic);
                } else {
                    show_error(__('Error'), __('Cant upload file to') . ' ' . self::TEMPLATES_PATH);
                }
            } else {
                show_error(__('No display name for template'));
            }
        } else {
            show_error(__('Wrong file type'));
        }
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

        $inputs = wf_DatePickerPreset('customdate', curdate());
        $inputs.= wf_tag('br');
        $inputs.= wf_TextInput('customrealname', __('Real Name'), @$this->userData[$this->userLogin]['REALNAME'], true, '20');
        $inputs.= wf_TextInput('customphone', __('Phone'), @$this->userData[$this->userLogin]['PHONE'], true, '10');
        $inputs.= wf_Selector('customservice', $availServices, __('Service'), '', 'true');
        $inputs.= wf_TextInput('customnotes', __('Notes'), '', true, '20');
        $inputs.= wf_TextInput('customsum', __('Sum'), @$this->userData[$this->userLogin]['TARIFFPRICE'], true, '10');
        if ($this->altcfg['CORPS_ENABLED']) {
            $inputs.=wf_tag('br') . wf_tag('span', false, 'row3') . ' ' . __('Corporate users') . ' ' . wf_tag('span', true) . wf_tag('br');
            $greed = new Avarice();
            $corpsRuntime = $greed->runtime('CORPS');
            if (!empty($corpsRuntime)) {
                $corps = new Corps();
                if ($corps->userIsCorporate($this->userLogin)) {
                    //this is realy corp user
                    $corpData = $corps->corpGetDataByLogin($this->userLogin);

                    $inputs.=wf_TextInput('corpname', __('Corp name'), @$corpData['corpname'], true, '30');
                    $inputs.=wf_TextInput('corpaddress', __('Address'), @$corpData['address'], true, '30');
                    $inputs.=wf_TextInput('corpdoctype', __('Document type'), @$corpData['doctype'], true, '30');
                    $inputs.=wf_TextInput('corpdocnum', __('Document number'), @$corpData['docnum'], true, '30');
                    $inputs.=wf_TextInput('corpdocdate', __('Document date'), @$corpData['docdate'], true, '30');
                    $inputs.=wf_TextInput('corpbankacc', __('Bank account'), @$corpData['bankacc'], true, '30');
                    $inputs.=wf_TextInput('corpbankname', __('Bank name'), @$corpData['bankname'], true, '30');
                    $inputs.=wf_TextInput('corpbankmfo', __('Bank MFO'), @$corpData['bankmfo'], true, '30');
                    $inputs.=wf_TextInput('corpedrpou', __('EDRPOU'), @$corpData['edrpou'], true, '30');
                    $inputs.=wf_TextInput('corpndstaxnum', __('NDS number'), @$corpData['ndstaxnum'], true, '30');
                    $inputs.=wf_TextInput('corpinncode', __('INN code'), @$corpData['inncode'], true, '30');
                    $inputs.=wf_TextInput('corptaxtype', __('Tax type'), @$corpData['taxtype'], true, '30');
                    $inputs.=wf_TextInput('corpnotes', __('Notes'), @$corpData['notes'], true, '30');
                } else {
                    $inputs.=__('Private user');
                }
            } else {
                $inputs.=__('No license key available');
            }
        }
        $inputs.= wf_HiddenInput('customfields', 'true');
        $inputs.= wf_tag('br');
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * sets some custom template fields from post request
     * 
     * @return void
     */
    public function setCustomFields() {
        //ugly debug code
        $pdvPercent = $this->altcfg['DOCX_NDS'];
        if (wf_CheckPost(array('customfields'))) {
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
            @$this->customFields['CUSTSUMPDVLIT'] = $morph->sum2str($this->customFields['CUSTSUMPDV']);
            @$this->customFields['CUSTSUMLIT'] = $morph->sum2str($this->customFields['CUSTSUM']);

            if ($this->altcfg['CORPS_ENABLED']) {
                //corporate user fields
                @$this->customFields['CORPNAME'] = $_POST['corpname'];
                @$this->customFields['CORPADDRESS'] = $_POST['corpaddress'];
                @$this->customFields['CORPDOCTYPE'] = $_POST['corpdoctype'];
                @$this->customFields['CORPDOCNUM'] = $_POST['corpdocnum'];
                @$this->customFields['CORPDOCDATE'] = $_POST['corpdocdate'];
                @$this->customFields['CORPBANKACC'] = $_POST['corpbankacc'];
                @$this->customFields['CORPBANKNAME'] = $_POST['corpbankname'];
                @$this->customFields['CORPBANKMFO'] = $_POST['corpbankmfo'];
                @$this->customFields['CORPEDRPOU'] = $_POST['corpedrpou'];
                @$this->customFields['CORPNDSTAXNUM'] = $_POST['corpndstaxnum'];
                @$this->customFields['CORPINNCODE'] = $_POST['corpinncode'];
                @$this->customFields['CORPTAXTYPE'] = $_POST['corptaxtype'];
                @$this->customFields['CORPNOTES'] = $_POST['corpnotes'];
            }

            if ($this->altcfg['NETWORKS_EXT']) {
                //extended network pools management
                $extNets = new ExtNets();
                @$this->customFields['NETWORKS_EXT'] = $extNets->poolTemplateData($this->userLogin);
            }
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
                NULL , '" . $date . "', '" . $login . "', '0', '" . $templateid . "', '" . $path . "'
                );
            ";
        nr_query($query);
    }

    /**
     * kills document in database
     * 
     * @param $documentid - existing document ID
     * 
     * @return void
     */
    public function unregisterDocument($documentid) {
        $documentid = vf($documentid, 3);

        $query = "DELETE FROM `docxdocuments` WHERE `id`='" . $documentid . "'";
        nr_query($query);
        log_register("PLDOCS DEL DOCUMENT [" . $documentid . "]");
    }

    /**
     * loads user documents from database
     * 
     * @param $login user login to search public docs
     * 
     * @return void
     */
    public function loadUserDocuments($login) {
        $query = "SELECT * from `docxdocuments` WHERE `login`='" . $this->userLogin . "' ORDER BY `id` DESC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->userDocuments[$each['id']] = $each;
            }
        }
    }

    /**
     * loads all user generated documents from database
     * 
     * @return void
     */
    public function loadAllUsersDocuments($date = '') {
        $date = trim($date);
        $date = (!empty($date)) ? $date : curdate();
        $where = "WHERE `date` LIKE '" . $date . "%'";
        $query = "SELECT * from `docxdocuments` " . $where . " ORDER BY `id` DESC;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUserDocuments[$each['id']] = $each;
            }
        }
    }

    /**
     * gets all user generated documents from database by this year
     * 
     * @return array
     */
    public function getAllUsersDocumentsThisYear() {
        $result = array();
        $where = "WHERE `date` LIKE '" . date("Y-") . "%'";
        $query = "SELECT * from `docxdocuments` " . $where . " ORDER BY `id` DESC;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['id']] = $each;
            }
        }
        return ($result);
    }

    /**
     * Renders previously generated user documents 
     * 
     * @return string
     */
    public function renderUserDocuments() {
        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Public'));
        $cells.= wf_TableCell(__('Template'));
        $cells.= wf_TableCell(__('Path'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->userDocuments)) {
            foreach ($this->userDocuments as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['date']);
                $cells.= wf_TableCell(web_bool_led($each['public']));
                @$templateName = $this->templates[$each['templateid']]['name'];
                $cells.= wf_TableCell(wf_tag('abbr', false, '', 'title="' . $each['templateid'] . '"') . $templateName . wf_tag('abbr', true));
                $downloadLink = wf_Link('?module=pl_documents&username=' . $this->userLogin . '&documentdownload=' . $each['path'], $each['path'], false, '');
                $cells.= wf_TableCell($downloadLink);
                $actionLinks = wf_JSAlert('?module=pl_documents&username=' . $this->userLogin . '&deletedocument=' . $each['id'], web_delete_icon(), __('Are you serious'));
                $cells.= wf_TableCell($actionLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', '0', '');
        return ($result);
    }

    /**
     * Renders previously generated all users documents 
     * 
     * @return string
     */
    public function renderAllUserDocuments() {
        $allAddress = zb_AddressGetFulladdresslistCached();
        $allRealnames = zb_UserGetAllRealnames();

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Public'));
        $cells.= wf_TableCell(__('Template'));
        $cells.= wf_TableCell(__('Path'));
        $cells.= wf_TableCell(__('Login'));
        $cells.= wf_TableCell(__('Address'));
        $cells.= wf_TableCell(__('Real Name'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allUserDocuments)) {
            foreach ($this->allUserDocuments as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['date']);
                $cells.= wf_TableCell(web_bool_led($each['public']));
                @$templateName = $this->templates[$each['templateid']]['name'];
                $cells.= wf_TableCell(wf_tag('abbr', false, '', 'title="' . $each['templateid'] . '"') . $templateName . wf_tag('abbr', true));
                $downloadLink = wf_Link('?module=report_documents&documentdownload=' . $each['path'], $each['path'], false, '');
                $cells.= wf_TableCell($downloadLink);
                $profileLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login']);
                $cells.= wf_TableCell($profileLink);
                $cells.= wf_TableCell(@$allAddress[$each['login']]);
                $cells.= wf_TableCell(@$allRealnames[$each['login']]);
                $actionLinks = wf_JSAlert('?module=report_documents&deletedocument=' . $each['id'], web_delete_icon(), __('Are you serious'));
                $cells.= wf_TableCell($actionLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', '0', '');
        return ($result);
    }

    /**
     * Renders previously generated all users as fullcalendar widget 
     * 
     * @return string
     */
    public function renderAllUserDocumentsCalendar() {
        $allAddress = zb_AddressGetFulladdresslistCached();

        $calendarData = '';
        $yearDocuments = $this->getAllUsersDocumentsThisYear();
        if (!empty($yearDocuments)) {
            foreach ($yearDocuments as $io => $each) {
                $timestamp = strtotime($each['date']);
                $date = date("Y, n-1, j", $timestamp);
                $rawTime = date("H:i:s", $timestamp);
                $calendarData.="
                      {
                        title: '" . $rawTime . ' ' . @$allAddress[$each['login']] . "',
                        url: '?module=userprofile&username=" . $each['login'] . "',
                        start: new Date(" . $date . "),
                        end: new Date(" . $date . "),
                   },
                    ";
            }
        }

        $result = wf_FullCalendar($calendarData);
        return ($result);
    }

    /**
     * show calendar contol form
     * 
     * @return string
     */
    public function dateControl() {
        if (wf_CheckPost(array('showdate'))) {
            $curdate = $_POST['showdate'];
        } else {
            $curdate = curdate();
        }

        $inputs = wf_DatePickerPreset('showdate', $curdate);
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

}

?>