<?php

/**
 * Corporate aka enterprise users implementation
 */
class Corps {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Display IBAN label instead of bank account in some forms/preview.
     *
     * @var bool
     */
    protected $ibanFlag = false;

    /**
     * Contains available corps to normal users bindings
     *
     * @var array
     */
    protected $users = array();

    /**
     * Contains available corps
     *
     * @var array
     */
    protected $corps = array();

    /**
     * Contains available corps contact persons
     *
     * @var array
     */
    protected $persons = array();

    /**
     * Contains existing tax types
     *
     * @var array
     */
    protected $taxtypes = array();

    /**
     * Contains available document types
     *
     * @var array
     */
    protected $doctypes = array(
        '1' => 'Certificate',
        '2' => 'Regulations',
        '3' => 'Reference'
    );

    /**
     * Some predefined module routing URLs
     */
    const ROUTE_PREFIX = 'show';
    const URL_TAXTYPE = 'taxtypes';
    const URL_TAXTYPE_LIST = '?module=corps&show=taxtypes';
    const URL_TAXTYPE_DEL = '?module=corps&show=taxtypes&deltaxtypeid=';
    const URL_CORPS = 'corps';
    const URL_CORPS_LIST = '?module=corps&show=corps';
    const URL_SEARCH = 'search';
    const URL_CORPS_SEARCH = '?module=corps&show=search';
    const URL_CORPS_EDIT = '?module=corps&show=corps&editid=';
    const URL_CORPS_ADD = '?module=corps&show=corps&add=true';
    const URL_CORPS_DEL = '?module=corps&show=corps&deleteid=';
    const URL_USER = 'user';
    const URL_USER_MANAGE = '?module=corps&show=user&username=';

    /**
     * Creates new corps object instance
     */
    public function __construct() {
        $this->loadConfigs();
        $this->loadUsers();
        $this->loadCorps();
        $this->loadPersons();
        $this->loadTaxtypes();
    }

    /**
     * Loads required configs and sets some object properties
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (@$this->altCfg['IBAN_ENABLED']) {
            $this->ibanFlag = true;
        }
    }

    /**
     * loads available corps from database into private prop
     * 
     * @return void
     */
    protected function loadCorps() {
        $query = "SELECT * from `corp_data`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->corps[$each['id']] = $each;
            }
        }
    }

    /**
     * loads taxtypes from database
     * 
     * @return void
     */
    protected function loadTaxtypes() {
        $query = "SELECT * from `corp_taxtypes`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->taxtypes[$each['id']] = $each['type'];
            }
        }
    }

    /**
     * loads contact persons from database
     * 
     * @return void
     */
    protected function loadPersons() {
        $query = "SELECT * from `corp_persons`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->persons[$each['id']] = $each;
            }
        }
    }

    /**
     * loads user bindings from database and store it into private prop users
     * 
     * @return void
     */
    protected function loadUsers() {
        $query = "SELECT * from `corp_users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->users[$each['login']] = $each['corpid'];
            }
        }
    }

    /**
     * returns existing taxtype edit form
     * 
     * @param $id int existing tax type ID
     * 
     * @return string
     */
    protected function taxtypeEditForm($id) {
        $id = vf($id, 3);
        $result = '';
        if (isset($this->taxtypes[$id])) {
            $taxtypename = $this->taxtypes[$id];
            $taxtypename = htmlspecialchars($taxtypename);
            $inputs = wf_HiddenInput('edittaxtypeid', $id);
            $inputs .= wf_TextInput('edittaxtype', __('Type'), $taxtypename, true, '40');
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, 'glamour');
        } else {
            $result = __('Not existing item');
        }
        return ($result);
    }

    /**
     * returns new taxtype creation form
     * 
     * @return string
     */
    protected function taxtypeCreateForm() {
        $inputs = wf_TextInput('newtaxtype', __('Type'), '', true, '40');
        $inputs .= wf_Submit(__('Create'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * creates new taxtype 
     * 
     * @param $type string new taxtype
     * 
     * @return void
     */
    public function taxtypeCreate($type) {
        $type = mysql_real_escape_string($type);
        $query = "INSERT INTO  `corp_taxtypes` (`id`, `type`) VALUES (NULL, '" . $type . "'); ";
        nr_query($query);
        $newId = simple_get_lastid('corp_taxtypes');
        log_register("CORPS CREATE TAXTYPE [" . $newId . "]");
    }

    /**
     * returns standard localized deletion alert
     * 
     * @return string
     */
    protected function alertDelete() {
        return (__('Removing this may lead to irreparable results'));
    }

    /**
     * return existing taxtypes list with edit controls
     * 
     * @return string
     */
    public function taxtypesList() {
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Type'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        if (!empty($this->taxtypes)) {
            foreach ($this->taxtypes as $id => $type) {
                $cells = wf_TableCell($id);
                $cells .= wf_TableCell($type);
                $actlinks = wf_JSAlert(self::URL_TAXTYPE_DEL . $id, web_delete_icon(), $this->alertDelete());
                $actlinks .= wf_modal(web_edit_icon(), __('Edit'), $this->taxtypeEditForm($id), '', '450', '150');
                $cells .= wf_TableCell($actlinks);
                $rows .= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        $result .= wf_modal(wf_img('skins/icon_add.gif') . ' ' . __('Create'), __('Create'), $this->taxtypeCreateForm(), 'ubButton', '450', '150');
        return ($result);
    }

    /**
     * deletes existing tax type from database
     * 
     * @return void
     */
    public function taxtypeDelete($id) {
        $id = vf($id, 3);
        if (isset($this->taxtypes[$id])) {
            $query = "DELETE from `corp_taxtypes` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register("CORPS DELETE TAXTYPE [" . $id . "]");
        }
    }

    /**
     * edits existing tax type
     * 
     * @param $id int existing taxtype ID
     * @param $type new taxtype description
     * 
     * @return void
     */
    public function taxtypeEdit($id, $type) {
        $id = vf($id, 3);
        if (isset($this->taxtypes[$id])) {
            simple_update_field('corp_taxtypes', 'type', $type, "WHERE `id`='" . $id . "';");
            log_register("CORPS EDIT TAXTYPE [" . $id . "]");
        }
    }

    /**
     * list available corps with some controls
     * 
     * @return string
     */
    public function corpsList() {

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Corp name'));
        $cells .= wf_TableCell(__('Address'));
        $cells .= wf_TableCell(__('Document type'));
        $cells .= wf_TableCell(__('Document date'));
        $cells .= wf_TableCell(__('Tax payer status'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        if (!empty($this->corps)) {
            foreach ($this->corps as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['corpname']);
                $cells .= wf_TableCell($each['address']);
                if (isset($this->doctypes[$each['doctype']])) {
                    $doctype = __($this->doctypes[$each['doctype']]);
                } else {
                    $doctype = $each['doctype'];
                }
                $cells .= wf_TableCell($doctype);
                $cells .= wf_TableCell($each['docdate']);
                if (isset($this->taxtypes[$each['taxtype']])) {
                    $taxtype = $this->taxtypes[$each['taxtype']];
                } else {
                    $taxtype = $each['taxtype'];
                }
                $cells .= wf_TableCell($taxtype);
                $actlinks = wf_JSAlert(self::URL_CORPS_DEL . $each['id'], web_delete_icon(), $this->alertDelete()) . ' ';
                $actlinks .= wf_JSAlert(self::URL_CORPS_EDIT . $each['id'], web_edit_icon(), __('Are you serious')) . ' ';
                $actlinks .= wf_modal(wf_img('skins/icon_search_small.gif', __('Preview')), $each['corpname'], $this->corpPreview($each['id']), '', '800', '600');
                $cells .= wf_TableCell($actlinks);
                $rows .= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * show existing corp preview
     * 
     * @param $id int existing corp ID
     * 
     * @return string
     */
    public function corpPreview($id) {
        $id = vf($id, 3);
        $result = '';
        if (isset($this->corps[$id])) {
            $cells = wf_TableCell(__('Corp name'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['corpname']);
            $rows = wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Address'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['address']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Document type'), '', 'row2');
            if (isset($this->doctypes[$this->corps[$id]['doctype']])) {
                $doctype = __($this->doctypes[$this->corps[$id]['doctype']]);
            } else {
                $doctype = $this->corps[$id]['doctype'];
            }
            $cells .= wf_TableCell($doctype);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Document number'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['docnum']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Document date'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['docdate']);
            $rows .= wf_TableRow($cells, 'row3');

            $bankAccLabel = ($this->ibanFlag) ? __('IBAN') : __('Bank account');
            $cells = wf_TableCell($bankAccLabel, '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['bankacc']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Bank name'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['bankname']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Bank MFO'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['bankmfo']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('EDRPOU'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['edrpou']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('NDS number'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['ndstaxnum']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('INN code'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['inncode']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Tax type'), '', 'row2');
            if (isset($this->taxtypes[$this->corps[$id]['taxtype']])) {
                $taxtype = $this->taxtypes[$this->corps[$id]['taxtype']];
            } else {
                $taxtype = $this->corps[$id]['taxtype'];
            }
            $cells .= wf_TableCell($taxtype);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Notes'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['notes']);
            $rows .= wf_TableRow($cells, 'row3');

            $result = wf_TableBody($rows, '100%', '0');
            $result .= $this->corpListUsers($id);
            $result .= $this->personsList($id);
        } else {
            $result = __('Not existing item');
        }
        return ($result);
    }

    /**
     * returns selector of existing doctypes
     * 
     * @param $name string input name
     * 
     * @return string
     */
    protected function doctypeSelector($name, $selected = '') {
        $doctypes = array();
        if (!empty($this->doctypes)) {
            foreach ($this->doctypes as $id => $type) {
                $doctypes[$id] = __($type);
            }
        }
        $result = wf_Selector($name, $doctypes, __('Document type'), $selected, false);
        return ($result);
    }

    /**
     * returns list of users which linked with this corp
     * 
     * @param $id int Existing corp ID
     * 
     * @return string
     */
    protected function corpListUsers($id) {
        $id = vf($id, 3);
        $result = '';
        $userArr = array();
        if (isset($this->corps[$id])) {
            if (!empty($this->users)) {
                foreach ($this->users as $login => $corpid) {
                    if ($corpid == $id) {
                        $userArr[$login] = $login;
                    }
                }

                if (!empty($userArr)) {
                    $result .= wf_tag('b') . __('Linked users') . ': ' . wf_tag('b', true);
                    foreach ($userArr as $eachlogin) {
                        $result .= wf_Link('?module=userprofile&username=' . $eachlogin, web_profile_icon() . ' ' . $eachlogin, false, '') . ' ';
                    }
                    $result .= wf_delimiter();
                }
            }
        }
        return ($result);
    }

    /**
     * filter array for unacceptable entities
     * 
     * @param $data array Data array for escaping
     * 
     * @return array
     */
    protected function filterArray($data) {
        $result = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $result[$key] = htmlspecialchars($value);
            }
        }
        return ($result);
    }

    /**
     * returns corp edit form
     * 
     * @param $id existing corp ID
     * 
     * @return string
     */
    public function corpEditForm($id) {
        $id = vf($id, 3);
        $result = '';
        if (isset($this->corps[$id])) {
            $data = $this->corps[$id];
            $data = $this->filterArray($data);
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs = wf_HiddenInput('editcorpid', $id);
            $inputs .= wf_TextInput('editcorpname', __('Corp name') . $sup, $data['corpname'], true, '40');
            $inputs .= wf_TextInput('editcoraddress', __('Address'), $data['address'], true, '40');
            $inputs .= $this->doctypeSelector('editdoctype', $data['doctype']);
            $inputs .= wf_DatePickerPreset('editdocdate', $data['docdate'], true) . ' ' . __('Document date') . wf_tag('br');
            $inputs .= wf_TextInput('editdocnum', __('Document number'), $data['docnum'], true, '20');
            $bankAccLabel = ($this->ibanFlag) ? __('IBAN') : __('Bank account');
            $inputs .= wf_TextInput('editbankacc', $bankAccLabel, $data['bankacc'], true, '20');
            $inputs .= wf_TextInput('editbankname', __('Bank name'), $data['bankname'], true, '20');
            $inputs .= wf_TextInput('editbankmfo', __('Bank MFO'), $data['bankmfo'], true, '20');
            $inputs .= wf_TextInput('editedrpou', __('EDRPOU'), $data['edrpou'], true, '20');
            $inputs .= wf_TextInput('editndstaxnum', __('NDS number'), $data['ndstaxnum'], true, '20');
            $inputs .= wf_TextInput('editinncode', __('INN code'), $data['inncode'], true, '20');
            $inputs .= wf_Selector('edittaxtype', $this->taxtypes, __('Tax type'), $data['taxtype'], true);
            $inputs .= wf_TextInput('editnotes', __('Notes'), $data['notes'], true, '40');
            $inputs .= wf_Submit(__('Save'));


            $result = wf_Form('', 'POST', $inputs, 'glamour');
            //contact persons editor
            $result .= $this->personsControl($id);
        } else {
            $result = __('Not existing item');
        }
        return ($result);
    }

    /**
     * returns corp creation form
     * 
     * 
     * @return string
     */
    public function corpCreateForm() {
        if (!empty($this->taxtypes)) {
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

            $inputs = wf_HiddenInput('createcorpid', 'true');
            $inputs .= wf_TextInput('createcorpname', __('Corp name') . $sup, '', true, '40');
            $inputs .= wf_TextInput('createaddress', __('Address'), '', true, '40');
            $inputs .= $this->doctypeSelector('createdoctype', '');
            $inputs .= wf_DatePickerPreset('createdocdate', curdate(), true) . ' ' . __('Document date') . wf_tag('br');
            $inputs .= wf_TextInput('adddocnum', __('Document number'), '', true, '20');
            $bankAccLabel = ($this->ibanFlag) ? __('IBAN') : __('Bank account');
            $inputs .= wf_TextInput('addbankacc', $bankAccLabel, '', true, '20');
            $inputs .= wf_TextInput('addbankname', __('Bank name'), '', true, '20');
            $inputs .= wf_TextInput('addbankmfo', __('Bank MFO'), '', true, '20');
            $inputs .= wf_TextInput('addedrpou', __('EDRPOU'), '', true, '20');
            $inputs .= wf_TextInput('addndstaxnum', __('NDS number'), '', true, '20');
            $inputs .= wf_TextInput('addinncode', __('INN code'), '', true, '20');
            $inputs .= wf_Selector('addtaxtype', $this->taxtypes, __('Tax type'), '', true);
            $inputs .= wf_TextInput('addnotes', __('Notes'), '', true, '40');
            $inputs .= wf_Submit(__('Create'));


            $result = wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result = __('No existing tax types');
        }
        return ($result);
    }

    /**
     * deletes existing corp by ID
     * 
     * @param $id int existing corp ID
     * 
     * @return void
     */
    public function corpDelete($id) {
        $id = vf($id, 3);
        if (isset($this->corps[$id])) {
            $query = "DELETE from `corp_data` WHERE `id`='" . $id . "'; ";
            nr_query($query);
            log_register("CORPS DELETE CORP [" . $id . "]");
        }
    }

    /**
     * edits corp in database
     * 
     * @param $id int existing corp ID
     * 
     * @return void
     */
    public function corpSave($id) {
        $id = vf($id, 3);
        if (isset($this->corps[$id])) {
            simple_update_field('corp_data', 'corpname', $_POST['editcorpname'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'address', $_POST['editcoraddress'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'doctype', $_POST['editdoctype'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'docdate', $_POST['editdocdate'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'docnum', $_POST['editdocnum'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'bankacc', $_POST['editbankacc'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'bankname', $_POST['editbankname'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'bankmfo', $_POST['editbankmfo'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'edrpou', $_POST['editedrpou'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'ndstaxnum', $_POST['editndstaxnum'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'inncode', $_POST['editinncode'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'taxtype', $_POST['edittaxtype'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_data', 'notes', $_POST['editnotes'], "WHERE `id`='" . $id . "'");
            log_register("CORPS EDIT CORP [" . $id . "]");
        }
    }

    /**
     * creates new corp in database
     * 
     * @return int
     */
    public function corpCreate() {
        $corpname = mysql_real_escape_string($_POST['createcorpname']);
        $address = mysql_real_escape_string($_POST['createaddress']);
        $doctype = vf($_POST['createdoctype'], 3);
        $docdate = mysql_real_escape_string($_POST['createdocdate']);
        $docnum = mysql_real_escape_string($_POST['adddocnum']);
        $bankacc = mysql_real_escape_string($_POST['addbankacc']);
        $bankname = mysql_real_escape_string($_POST['addbankname']);
        $bankmfo = mysql_real_escape_string($_POST['addbankmfo']);
        $edrpou = mysql_real_escape_string($_POST['addedrpou']);
        $taxnum = mysql_real_escape_string($_POST['addndstaxnum']);
        $inncode = mysql_real_escape_string($_POST['addinncode']);
        $taxtype = vf($_POST['addtaxtype'], 3);
        $notes = mysql_real_escape_string($_POST['addnotes']);
        $query = "INSERT INTO `corp_data` (`id`, `corpname`, `address`, `doctype`, `docnum`, `docdate`, `bankacc`, `bankname`, `bankmfo`, `edrpou`, `ndstaxnum`, `inncode`, `taxtype`, `notes`) "
                . "VALUES (NULL, '" . $corpname . "', '" . $address . "', '" . $doctype . "', '" . $docnum . "', '" . $docdate . "', '" . $bankacc . "', '" . $bankname . "', '" . $bankmfo . "', '" . $edrpou . "', '" . $taxnum . "', '" . $inncode . "', '" . $taxtype . "', '" . $notes . "');";
        nr_query($query);
        $newID = simple_get_lastid('corp_data');
        log_register("CORPS CREATE CORP [" . $newID . "]");
        return ($newID);
    }

    /**
     * returns corps link panel
     * 
     * @return string
     */
    public function corpsPanel() {
        $result = wf_Link(self::URL_CORPS_ADD, wf_img('skins/icon_add.gif') . ' ' . __('Create'), false, 'ubButton');
        $result .= wf_Link(self::URL_CORPS_LIST, wf_img('skins/icon_search_small.gif') . ' ' . __('Available corps'), false, 'ubButton');
        $result .= wf_Link(self::URL_TAXTYPE_LIST, wf_img('skins/icon_dollar.gif') . ' ' . __('Available tax types'), false, 'ubButton');
        return ($result);
    }

    /**
     * returns contact persons list for some corp
     * 
     * @param $corpid int Existing corp ID
     * 
     * @return string
     */
    protected function personsList($corpid) {
        $corpid = vf($corpid, 3);
        $result = '';
        if (!empty($this->persons)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Real Name'));
            $cells .= wf_TableCell(__('Phone'));
            $cells .= wf_TableCell(__('IM'));
            $cells .= wf_TableCell(__('Email'));
            $cells .= wf_TableCell(__('Appointment'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->persons as $io => $each) {
                if ($each['corpid'] == $corpid) {
                    $cells = wf_TableCell($each['id']);
                    $cells .= wf_TableCell($each['realname']);
                    $cells .= wf_TableCell($each['phone']);
                    $cells .= wf_TableCell($each['im']);
                    $cells .= wf_TableCell($each['email']);
                    $cells .= wf_TableCell($each['appointment']);
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            $result .= wf_tag('b') . __('Contact persons') . wf_tag('b', true);
            $result .= wf_TableBody($rows, '100%', '0', 'sortable');
        }
        return ($result);
    }

    /**
     * returns contact persons edit control for some corp
     * 
     * @param $corpid int Existing corp ID
     * 
     * @return string
     */
    protected function personsControl($corpid) {
        $corpid = vf($corpid, 3);
        $result = '';
        if (!empty($this->persons)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Real Name'));
            $cells .= wf_TableCell(__('Phone'));
            $cells .= wf_TableCell(__('IM'));
            $cells .= wf_TableCell(__('Email'));
            $cells .= wf_TableCell(__('Appointment'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->persons as $io => $each) {
                if ($each['corpid'] == $corpid) {
                    $cells = wf_TableCell($each['id']);
                    $cells .= wf_TableCell($each['realname']);
                    $cells .= wf_TableCell($each['phone']);
                    $cells .= wf_TableCell($each['im']);
                    $cells .= wf_TableCell($each['email']);
                    $cells .= wf_TableCell($each['appointment']);
                    $actLinks = wf_JSAlert(self::URL_CORPS_EDIT . $corpid . '&deletepersonid=' . $each['id'], web_delete_icon(), $this->alertDelete());
                    $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->personEditForm($each['id']), '');
                    $cells .= wf_TableCell($actLinks);
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            $result .= wf_tag('b') . __('Contact persons') . wf_tag('b', true);
            $result .= wf_TableBody($rows, '100%', '0', 'sortable');
        }


        return ($result);
    }

    /**
     * returns conact person creation form
     * 
     * @param $corpid int existing corp ID
     * 
     * @return string
     */
    public function personCreateForm($corpid) {
        $corpid = vf($corpid, 3);
        $result = '';
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        if (isset($this->corps[$corpid])) {
            $inputs = wf_HiddenInput('addpersoncorpid', $corpid);
            $inputs .= wf_TextInput('addpersonrealname', __('Real Name') . $sup, '', true, '20');
            $inputs .= wf_TextInput('addpersonphone', __('Phone'), '', true, '20');
            $inputs .= wf_TextInput('addpersonim', __('Instant messenger (Skype, ICQ, Jabber, etc)'), '', true, '20');
            $inputs .= wf_TextInput('addpersonemail', __('Email'), '', true, '20');
            $inputs .= wf_TextInput('addpersonappointment', __('Appointment'), '', true, '30');
            $inputs .= wf_TextArea('addpersonnotes', __('Notes'), '', true, '30x3');
            $inputs .= wf_Submit(__('Create'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result = __('Not existing item');
        }
        return ($result);
    }

    /**
     * returns conact person creation form
     * 
     * @param $id int existing contact person ID
     * 
     * @return string
     */
    protected function personEditForm($id) {
        $id = vf($id, 3);
        $result = '';
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        if (isset($this->persons[$id])) {
            $data = $this->persons[$id];
            $data = $this->filterArray($data);
            $inputs = wf_HiddenInput('editpersonid', $id);
            $inputs .= wf_TextInput('editpersonrealname', __('Real Name') . $sup, $data['realname'], true, '20');
            $inputs .= wf_TextInput('editpersonphone', __('Phone'), $data['phone'], true, '20');
            $inputs .= wf_TextInput('editpersonim', __('Instant messenger (Skype, ICQ, Jabber, etc)'), $data['im'], true, '20');
            $inputs .= wf_TextInput('editpersonemail', __('Email'), $data['email'], true, '20');
            $inputs .= wf_TextInput('editpersonappointment', __('Appointment'), $data['appointment'], true, '30');
            $inputs .= wf_TextArea('editpersonnotes', __('Notes'), $data['notes'], true, '30x3');
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result = __('Not existing item');
        }
        return ($result);
    }

    /**
     * edits contact person in database
     * 
     * @param $id int existing contact person ID
     * 
     * @return void
     */
    public function personSave($id) {
        $id = vf($id, 3);
        if (isset($this->persons[$id])) {

            simple_update_field('corp_persons', 'realname', $_POST['editpersonrealname'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_persons', 'phone', $_POST['editpersonphone'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_persons', 'im', $_POST['editpersonim'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_persons', 'email', $_POST['editpersonemail'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_persons', 'appointment', $_POST['editpersonappointment'], "WHERE `id`='" . $id . "'");
            simple_update_field('corp_persons', 'notes', $_POST['editpersonnotes'], "WHERE `id`='" . $id . "'");
            log_register("CORPS EDIT PERSON [" . $id . "]");
        }
    }

    /**
     * creates new contact person in database
     * 
     * @return void
     */
    public function personCreate() {
        if (wf_CheckPost(array('addpersoncorpid', 'addpersonrealname'))) {
            $corpid = vf($_POST['addpersoncorpid']);
            $realname = mysql_real_escape_string($_POST['addpersonrealname']);
            $phone = mysql_real_escape_string($_POST['addpersonphone']);
            $im = mysql_real_escape_string($_POST['addpersonim']);
            $email = mysql_real_escape_string($_POST['addpersonemail']);
            $appointment = mysql_real_escape_string($_POST['addpersonappointment']);
            $notes = mysql_real_escape_string($_POST['addpersonnotes']);

            if (isset($this->corps[$corpid])) {
                $query = "INSERT INTO `corp_persons` (`id`, `corpid`, `realname`, `phone`, `im`, `email`, `appointment`, `notes`) VALUES (NULL, '" . $corpid . "', '" . $realname . "', '" . $phone . "', '" . $im . "', '" . $email . "', '" . $appointment . "', '" . $notes . "');";
                nr_query($query);
                $newId = simple_get_lastid('corp_persons');
                log_register("CORPS CREATE PERSON [" . $newId . "] FOR CORP [" . $corpid . "]");
            }
        }
    }

    /**
     * check is taxtype used by someone?
     * 
     * @param $id int existing taxtype ID
     * 
     * @return bool
     */
    public function taxtypeProtected($id) {
        $id = vf($id, 3);
        $result = false;
        if (!empty($this->corps)) {
            foreach ($this->corps as $io => $each) {
                if ($each['taxtype'] == $id) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * deletes an existing contact person
     * 
     * @param $id int existing contact person ID
     * 
     * @return void
     */
    public function personDelete($id) {
        $id = vf($id, 3);
        $query = "DELETE from `corp_persons` WHERE `id`='" . $id . "';";
        nr_query($query);
        log_register("CORPS DELETE PERSON [" . $id . "]");
    }

    /**
     * binds user login to existing corp ID
     * 
     * @param $login string Existing user login
     * @param $corpid int   Existing corp ID
     * 
     * @return void
     */
    public function userBind($login, $corpid) {
        $login = mysql_real_escape_string($login);
        $corpid = vf($corpid, 3);
        if (!isset($this->users[$login])) {
            $query = "INSERT INTO `corp_users` (`id`, `login`, `corpid`) VALUES (NULL, '" . $login . "', '" . $corpid . "'); ";
            nr_query($query);
            log_register('CORPS BIND USER (' . $login . ') TO [' . $corpid . ']');
        }
    }

    /**
     * unbinds user login from any corp and sets him as just private user
     * 
     * @param $login string Existing user login
     * 
     * @return void
     */
    function userUnbind($login) {
        $login = mysql_real_escape_string($login);
        if (isset($this->users[$login])) {
            $query = "DELETE FROM `corp_users` WHERE `login`='" . $login . "';";
            nr_query($query);
            log_register('CORPS UNBIND USER (' . $login . ')');
        }
    }

    /**
     * checks is user associated with some corp or not? If associated - returns corp ID
     * 
     * @param $login string Existing user login
     * 
     * @return int/bool
     */
    function userIsCorporate($login) {
        $result = false;
        if (isset($this->users[$login])) {
            $result = $this->users[$login];
        }
        return ($result);
    }

    /**
     * returns user unbind form
     * 
     * @param $login string Existing user login
     * 
     * @return string
     */
    public function userUnbindForm($login) {
        $login = mysql_real_escape_string($login);
        $result = '';
        if (isset($this->users[$login])) {
            $inputs = wf_HiddenInput('corpsunbindlogin', $login);
            $inputs .= wf_CheckInput('unbindagree', __('I am quite sure that I was going to do'), false, false);
            $inputs .= wf_Submit(__('Destroy user link'));
            $result = wf_Form("", 'POST', $inputs, 'glamour');
            $result .= wf_delimiter();
            $result .= web_UserControls($login);
        } else {
            $result = __('Not existing item');
        }
        return ($result);
    }

    /**
     * returns existing coorps selector
     * 
     * @param $login string Existing user login
     * 
     * @return string
     */
    public function corpsBindForm($login) {
        $result = '';
        $corpsarr = array();
        if (!empty($this->corps)) {
            foreach ($this->corps as $io => $each) {
                $corpsarr[$io] = $each['corpname'];
            }

            $inputs = wf_HiddenInput('bindsomelogin', $login);
            $inputs .= wf_Selector('bindlogintocorpid', $corpsarr, __('Corporate user'), '', false);
            $inputs .= wf_Submit(__('Create user ling with existing corporate user'));
            $result = wf_Form("", 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * returns user binding form
     * 
     * @param $login string Existing user login
     * 
     * @return string
     */
    public function corpCreateAndBindForm($login) {
        if (!empty($this->taxtypes)) {
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs = wf_HiddenInput('createcorpid', 'true');
            $inputs .= wf_HiddenInput('alsobindsomelogin', $login);
            $inputs .= wf_TextInput('createcorpname', __('Corp name') . $sup, '', true, '40');
            $inputs .= wf_TextInput('createaddress', __('Address'), '', true, '40');
            $inputs .= $this->doctypeSelector('createdoctype', '');
            $inputs .= wf_DatePickerPreset('createdocdate', curdate(), true) . ' ' . __('Document date') . wf_tag('br');
            $inputs .= wf_TextInput('adddocnum', __('Document number'), '', true, '20');
            $bankAccLabel = ($this->ibanFlag) ? __('IBAN') : __('Bank account');
            $inputs .= wf_TextInput('addbankacc', $bankAccLabel, '', true, '20');
            $inputs .= wf_TextInput('addbankname', __('Bank name'), '', true, '20');
            $inputs .= wf_TextInput('addbankmfo', __('Bank MFO'), '', true, '20');
            $inputs .= wf_TextInput('addedrpou', __('EDRPOU'), '', true, '20');
            $inputs .= wf_TextInput('addndstaxnum', __('NDS number'), '', true, '20');
            $inputs .= wf_TextInput('addinncode', __('INN code'), '', true, '20');
            $inputs .= wf_Selector('addtaxtype', $this->taxtypes, __('Tax type'), '', true);
            $inputs .= wf_TextInput('addnotes', __('Notes'), '', true, '40');
            $inputs .= wf_Submit(__('Create'));


            $result = wf_Form(self::URL_CORPS_ADD, 'POST', $inputs, 'glamour');
        } else {
            $result = __('No existing tax types');
        }
        return ($result);
    }

    /**
     * checks is corp used by something or not?
     * 
     * @param $id int Existing corp ID
     * 
     * @return bool
     */
    public function corpProtected($id) {
        $id = vf($id, 3);
        $result = false;
        if (!empty($this->users)) {
            foreach ($this->users as $login => $corpid) {
                if ($corpid == $id) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Gets corp data by associated username
     * 
     * @param $login string Existing users login
     * 
     * @return array
     */
    public function corpGetDataByLogin($login) {
        $result = array();
        if (isset($this->users[$login])) {
            $corpId = $this->users[$login];
            if (isset($this->corps[$corpId])) {
                $result = $this->corps[$corpId];
                //map some IDs to normal text
                @$result['doctype'] = __($this->doctypes[$result['doctype']]);
                @$result['taxtype'] = $this->taxtypes[$result['taxtype']];
            }
        }
        return ($result);
    }

    /**
     * Returns array of available corps
     * 
     * @return array
     */
    public function getCorps() {
        return ($this->corps);
    }

    /**
     * Returns array of available corps users
     * 
     * @return array
     */
    public function getUsers() {
        return ($this->users);
    }

    /**
     * Rerurns corp id by name part
     * 
     * @param string $searchterm
     * 
     * @return int
     */
    protected function searchCorpIdbyName($searchterm) {
        $result = '';
        if (!empty($this->corps)) {

            if (!empty($searchterm)) {
                $searchterm = strtolower_utf8($searchterm);
                foreach ($this->corps as $io => $each) {
                    if (ispos(strtolower_utf8($each['corpname']), $searchterm)) {
                        $result = $io;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * returns users array by some corp ID
     * 
     * @return array
     */
    protected function searchUsersByCorpId($corpid) {
        $result = array();
        if (!empty($this->corps)) {
            if (!empty($this->users)) {
                if (!empty($corpid)) {
                    foreach ($this->users as $eachLogin => $eachCorp) {
                        if ($eachCorp == $corpid) {
                            $result[] = $eachLogin;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns standard user list of users assigned for some corp
     * 
     * @param string $corpname
     * @return string
     */
    public function searchUsersByCorpName($corpname) {
        $result = '';
        if (!empty($corpname)) {
            $corpId = $this->searchCorpIdbyName($corpname);
            if (!empty($corpId)) {
                $corpLink = wf_Link('?module=corps&show=corps&editid=' . $corpId, $this->corps[$corpId]['corpname'], false, '');
                show_success($corpLink);
                $corpUsers = $this->searchUsersByCorpId($corpId);
                if (!empty($corpUsers)) {
                    $result = web_UserArrayShower($corpUsers);
                } else {
                    show_warning(__('Nothing found'));
                }
            } else {
                show_warning(__('Nothing found'));
            }
        }
        return ($result);
    }

}

?>