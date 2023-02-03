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
     * Contains available corps to normal users bindings as login=>corpId
     *
     * @var array
     */
    protected $users = array();

    /**
     * Contains available corps as id=>corpData
     *
     * @var array
     */
    protected $corps = array();

    /**
     * Contains available corps contact persons as id=>personData
     *
     * @var array
     */
    protected $persons = array();

    /**
     * Contains existing tax types as id=>type
     *
     * @var array
     */
    protected $taxtypes = array();

    /**
     * Use bank/taxes field names for RF flag
     *
     * @var bool
     */
    protected $rfCorpsFlag = false;

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
     * Contains corps data database abstraction layer
     *
     * @var object
     */
    protected $corpsDb = '';

    /**
     * Contains taxtypes database abstraction layer
     *
     * @var object
     */
    protected $taxtypesDb = '';

    /**
     * Contains persons database abstraction layer
     *
     * @var object
     */
    protected $personsDb = '';

    /**
     * Contains users database abstraction layer
     *
     * @var object
     */
    protected $usersDb = '';

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
    //some datasources here
    const TABLE_DATA = 'corp_data';
    const TABLE_TAXTYPES = 'corp_taxtypes';
    const TABLE_PERSONS = 'corp_persons';
    const TABLE_USERS = 'corp_users';

    /**
     * Creates new corps object instance
     */
    public function __construct() {
        $this->loadConfigs();
        $this->initDb();
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

        if (@$this->altCfg['RFCORPS']) {
            $this->rfCorpsFlag = true;
        }

        if (@$this->altCfg['CORPS_ADDT']) {
            $rawTypes = explode(',', $this->altCfg['CORPS_ADDT']);
            if (!empty($rawTypes)) {
                foreach ($rawTypes as $io => $eachDt) {
                    $this->doctypes[] = $eachDt;
                }
            }
        }
    }

    /**
     * Inits all required database abstraction layers
     * 
     * @return void
     */
    protected function initDb() {
        $this->corpsDb = new NyanORM(self::TABLE_DATA);
        $this->taxtypesDb = new NyanORM(self::TABLE_TAXTYPES);
        $this->personsDb = new NyanORM(self::TABLE_PERSONS);
        $this->usersDb = new NyanORM(self::TABLE_USERS);
    }

    /**
     * loads available corps from database into private prop
     * 
     * @return void
     */
    protected function loadCorps() {
        $this->corps = $this->corpsDb->getAll('id');
    }

    /**
     * loads taxtypes from database
     * 
     * @return void
     */
    protected function loadTaxtypes() {
        $all = $this->taxtypesDb->getAll();
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
        $this->persons = $this->personsDb->getAll('id');
    }

    /**
     * loads user bindings from database and store it into private prop users
     * 
     * @return void
     */
    protected function loadUsers() {
        $all = $this->usersDb->getAll();
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
        $id = ubRouting::filters($id, 'int');
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
        $type = ubRouting::filters($type, 'mres');

        $this->taxtypesDb->data('type', $type);
        $this->taxtypesDb->create();

        $newId = $this->taxtypesDb->getLastId();
        log_register('CORPS CREATE TAXTYPE [' . $newId . ']');
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
        $id = ubRouting::filters($id, 'int');
        if (isset($this->taxtypes[$id])) {
            $this->taxtypesDb->where('id', '=', $id);
            $this->taxtypesDb->delete();
            log_register('CORPS DELETE TAXTYPE [' . $id . ']');
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
        $id = ubRouting::filters($id, 'int');
        if (isset($this->taxtypes[$id])) {
            $this->taxtypesDb->data('type', $type);
            $this->taxtypesDb->where('id', '=', $id);
            $this->taxtypesDb->save();
            log_register('CORPS EDIT TAXTYPE [' . $id . ']');
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
        $id = ubRouting::filters($id, 'int');
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

            $mfoLabel = (!$this->rfCorpsFlag) ? __('Bank MFO') : __('Bank BIK');
            $edrpouLabel = (!$this->rfCorpsFlag) ? __('EDRPOU') : __('OGRN');
            $ndsNumLabel = (!$this->rfCorpsFlag) ? __('NDS number') : __('INN Number');
            $innCodeLabel = (!$this->rfCorpsFlag) ? __('INN code') : __('KPP Code');

            $cells = wf_TableCell($mfoLabel, '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['bankmfo']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell($edrpouLabel, '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['edrpou']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell($ndsNumLabel, '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['ndstaxnum']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell($innCodeLabel, '', 'row2');
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

            $cells = wf_TableCell(__('Short name'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['corpnameabbr']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Signatory'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['corpsignatory']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Signatory') . ' 2', '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['corpsignatory2']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Basis'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['corpbasis']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Email'), '', 'row2');
            $cells .= wf_TableCell($this->corps[$id]['corpemail']);
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
        $id = ubRouting::filters($id, 'int');
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
        $id = ubRouting::filters($id, 'int');
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
            $mfoLabel = (!$this->rfCorpsFlag) ? __('Bank MFO') : __('Bank BIK');
            $edrpouLabel = (!$this->rfCorpsFlag) ? __('EDRPOU') : __('OGRN');
            $ndsNumLabel = (!$this->rfCorpsFlag) ? __('NDS number') : __('INN Number');
            $innCodeLabel = (!$this->rfCorpsFlag) ? __('INN code') : __('KPP Code');
            $inputs .= wf_TextInput('editbankmfo', $mfoLabel, $data['bankmfo'], true, '20');
            $inputs .= wf_TextInput('editedrpou', $edrpouLabel, $data['edrpou'], true, '20');
            $inputs .= wf_TextInput('editndstaxnum', $ndsNumLabel, $data['ndstaxnum'], true, '20');
            $inputs .= wf_TextInput('editinncode', $innCodeLabel, $data['inncode'], true, '20');
            $inputs .= wf_Selector('edittaxtype', $this->taxtypes, __('Tax type'), $data['taxtype'], true);
            $inputs .= wf_TextInput('editcorpnameabbr', __('Short name'), $data['corpnameabbr'], true, '20');
            $inputs .= wf_TextInput('editcorpsignatory', __('Signatory'), $data['corpsignatory'], true, '20');
            $inputs .= wf_TextInput('editcorpsignatory2', __('Signatory') . ' 2', $data['corpsignatory2'], true, '20');
            $inputs .= wf_TextInput('editcorpbasis', __('Basis'), $data['corpbasis'], true, '20');
            $inputs .= wf_TextInput('editcorpemail', __('Email'), $data['corpemail'], true, '20');
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
     * Returns corp creation form
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
            $mfoLabel = (!$this->rfCorpsFlag) ? __('Bank MFO') : __('Bank BIK');
            $edrpouLabel = (!$this->rfCorpsFlag) ? __('EDRPOU') : __('OGRN');
            $ndsNumLabel = (!$this->rfCorpsFlag) ? __('NDS number') : __('INN Number');
            $innCodeLabel = (!$this->rfCorpsFlag) ? __('INN code') : __('KPP Code');
            $inputs .= wf_TextInput('addbankmfo', $mfoLabel, '', true, '20');
            $inputs .= wf_TextInput('addedrpou', $edrpouLabel, '', true, '20');
            $inputs .= wf_TextInput('addndstaxnum', $ndsNumLabel, '', true, '20');
            $inputs .= wf_TextInput('addinncode', $innCodeLabel, '', true, '20');
            $inputs .= wf_Selector('addtaxtype', $this->taxtypes, __('Tax type'), '', true);
            $inputs .= wf_TextInput('addcorpnameabbr', __('Short name'), '', true, '20');
            $inputs .= wf_TextInput('addcorpsignatory', __('Signatory'), '', true, '20');
            $inputs .= wf_TextInput('addcorpsignatory2', __('Signatory') . ' 2', '', true, '20');
            $inputs .= wf_TextInput('addcorpbasis', __('Basis'), '', true, '20');
            $inputs .= wf_TextInput('addcorpemail', __('Email'), '', true, '20');

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
        $id = ubRouting::filters($id, 'int');
        if (isset($this->corps[$id])) {
            $this->corpsDb->where('id', '=', $id);
            $this->corpsDb->delete();
            log_register('CORPS DELETE CORP [' . $id . ']');
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
        $id = ubRouting::filters($id, 'int');
        if (isset($this->corps[$id])) {

            $this->corpsDb->data('corpname', ubRouting::post('editcorpname', 'mres'));
            $this->corpsDb->data('address', ubRouting::post('editcoraddress', 'mres'));
            $this->corpsDb->data('doctype', ubRouting::post('editdoctype', 'mres'));
            $this->corpsDb->data('docdate', ubRouting::post('editdocdate', 'mres'));
            $this->corpsDb->data('docnum', ubRouting::post('editdocnum', 'mres'));
            $this->corpsDb->data('bankacc', ubRouting::post('editbankacc', 'mres'));
            $this->corpsDb->data('bankname', ubRouting::post('editbankname', 'mres'));
            $this->corpsDb->data('bankmfo', ubRouting::post('editbankmfo', 'mres'));
            $this->corpsDb->data('edrpou', ubRouting::post('editedrpou', 'mres'));
            $this->corpsDb->data('ndstaxnum', ubRouting::post('editndstaxnum', 'mres'));
            $this->corpsDb->data('inncode', ubRouting::post('editinncode', 'mres'));
            $this->corpsDb->data('taxtype', ubRouting::post('edittaxtype', 'mres'));
            $this->corpsDb->data('notes', ubRouting::post('editnotes', 'mres'));
            $this->corpsDb->data('corpnameabbr', ubRouting::post('editcorpnameabbr', 'mres'));
            $this->corpsDb->data('corpsignatory', ubRouting::post('editcorpsignatory', 'mres'));
            $this->corpsDb->data('corpsignatory2', ubRouting::post('editcorpsignatory2', 'mres'));
            $this->corpsDb->data('corpbasis', ubRouting::post('editcorpbasis', 'mres'));
            $this->corpsDb->data('corpemail', ubRouting::post('editcorpemail', 'mres'));
            $this->corpsDb->where('id', '=', $id);
            $this->corpsDb->save();

            log_register('CORPS EDIT CORP [' . $id . ']');
        }
    }

    /**
     * Creates new corp in database
     * 
     * @return int
     */
    public function corpCreate() {
        $corpname = ubRouting::post('createcorpname', 'mres');
        $address = ubRouting::post('createaddress', 'mres');
        $doctype = ubRouting::post('createdoctype', 'int');
        $docdate = ubRouting::post('createdocdate', 'mres');
        $docnum = ubRouting::post('adddocnum', 'mres');
        $bankacc = ubRouting::post('addbankacc', 'mres');
        $bankname = ubRouting::post('addbankname', 'mres');
        $bankmfo = ubRouting::post('addbankmfo', 'mres');
        $edrpou = ubRouting::post('addedrpou', 'mres');
        $taxnum = ubRouting::post('addndstaxnum', 'mres');
        $inncode = ubRouting::post('addinncode', 'mres');
        $taxtype = ubRouting::post('addtaxtype', 'int');
        $notes = ubRouting::post('addnotes', 'mres');

        $corpnameabbr = ubRouting::post('addcorpnameabbr', 'mres');
        $corpsignatory = ubRouting::post('addcorpsignatory', 'mres');
        $corpsignatory2 = ubRouting::post('addcorpsignatory2', 'mres');
        $corpbasis = ubRouting::post('addcorpbasis', 'mres');
        $corpemail = ubRouting::post('addcorpemail', 'mres');


        $this->corpsDb->data('corpname', $corpname);
        $this->corpsDb->data('address', $address);
        $this->corpsDb->data('doctype', $doctype);
        $this->corpsDb->data('docnum', $docnum);
        $this->corpsDb->data('docdate', $docdate);
        $this->corpsDb->data('bankacc', $bankacc);
        $this->corpsDb->data('bankname', $bankname);
        $this->corpsDb->data('bankmfo', $bankmfo);
        $this->corpsDb->data('edrpou', $edrpou);
        $this->corpsDb->data('ndstaxnum', $taxnum);
        $this->corpsDb->data('inncode', $inncode);
        $this->corpsDb->data('taxtype', $taxtype);
        $this->corpsDb->data('notes', $notes);
        $this->corpsDb->data('corpnameabbr', $corpnameabbr);
        $this->corpsDb->data('corpsignatory', $corpsignatory);
        $this->corpsDb->data('corpsignatory2', $corpsignatory2);
        $this->corpsDb->data('corpbasis', $corpbasis);
        $this->corpsDb->data('corpemail', $corpemail);

        $this->corpsDb->create();

        $newID = $this->corpsDb->getLastId();
        log_register('CORPS CREATE CORP [' . $newID . ']');
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
        $corpid = ubRouting::filters($corpid, 'int');
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
        $corpid = ubRouting::filters($corpid, 'int');
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
        $corpid = ubRouting::filters($corpid, 'int');
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
        $id = ubRouting::filters($id, 'int');
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
        $id = ubRouting::filters($id, 'int');
        if (isset($this->persons[$id])) {

            $this->personsDb->data('realname', ubRouting::post('editpersonrealname', 'mres'));
            $this->personsDb->data('phone', ubRouting::post('editpersonphone', 'mres'));
            $this->personsDb->data('im', ubRouting::post('editpersonim', 'mres'));
            $this->personsDb->data('email', ubRouting::post('editpersonemail', 'mres'));
            $this->personsDb->data('appointment', ubRouting::post('editpersonappointment', 'mres'));
            $this->personsDb->data('notes', ubRouting::post('editpersonnotes', 'mres'));
            $this->personsDb->where('id', '=', $id);
            $this->personsDb->save();

            log_register('CORPS EDIT PERSON [' . $id . ']');
        }
    }

    /**
     * creates new contact person in database
     * 
     * @return void
     */
    public function personCreate() {
        if (ubRouting::checkPost(array('addpersoncorpid', 'addpersonrealname'))) {
            $corpid = ubRouting::post('addpersoncorpid', 'int');

            if (isset($this->corps[$corpid])) {
                $realname = ubRouting::post('addpersonrealname', 'mres');
                $phone = ubRouting::post('addpersonphone', 'mres');
                $im = ubRouting::post('addpersonim', 'mres');
                $email = ubRouting::post('addpersonemail', 'mres');
                $appointment = ubRouting::post('addpersonappointment', 'mres');
                $notes = ubRouting::post('addpersonnotes', 'mres');

                $this->personsDb->data('corpid', $corpid);
                $this->personsDb->data('realname', $realname);
                $this->personsDb->data('phone', $phone);
                $this->personsDb->data('im', $im);
                $this->personsDb->data('email', $email);
                $this->personsDb->data('appointment', $appointment);
                $this->personsDb->data('notes', $notes);
                $this->personsDb->create();

                $newId = $this->personsDb->getLastId();
                log_register('CORPS CREATE PERSON [' . $newId . '] FOR CORP [' . $corpid . ']');
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
        $id = ubRouting::filters($id, 'int');
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
        $id = ubRouting::filters($id, 'int');

        $this->personsDb->where('id', '=', $id);
        $this->personsDb->delete();

        log_register('CORPS DELETE PERSON [' . $id . ']');
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
        $loginF = ubRouting::filters($login, 'mres');
        $corpid = ubRouting::filters($corpid, 'int');

        if (!isset($this->users[$login])) {
            $this->usersDb->data('login', $loginF);
            $this->usersDb->data('corpid', $corpid);
            $this->usersDb->create();

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
        $loginF = ubRouting::filters($login, 'mres');
        if (isset($this->users[$login])) {
            $this->usersDb->where('login', '=', $loginF);
            $this->usersDb->delete();
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
        $login = ubRouting::filters($login, 'mres');
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
            $mfoLabel = (!$this->rfCorpsFlag) ? __('Bank MFO') : __('Bank BIK');
            $edrpouLabel = (!$this->rfCorpsFlag) ? __('EDRPOU') : __('OGRN');
            $ndsNumLabel = (!$this->rfCorpsFlag) ? __('NDS number') : __('INN Number');
            $innCodeLabel = (!$this->rfCorpsFlag) ? __('INN code') : __('KPP Code');
            $inputs .= wf_TextInput('addbankmfo', $mfoLabel, '', true, '20');
            $inputs .= wf_TextInput('addedrpou', $edrpouLabel, '', true, '20');
            $inputs .= wf_TextInput('addndstaxnum', $ndsNumLabel, '', true, '20');
            $inputs .= wf_TextInput('addinncode', $innCodeLabel, '', true, '20');
            $inputs .= wf_Selector('addtaxtype', $this->taxtypes, __('Tax type'), '', true);
            $inputs .= wf_TextInput('addcorpnameabbr', __('Short name'), '', true, '20');
            $inputs .= wf_TextInput('addcorpsignatory', __('Signatory'), '', true, '20');
            $inputs .= wf_TextInput('addcorpsignatory2', __('Signatory') . ' 2', '', true, '20');
            $inputs .= wf_TextInput('addcorpbasis', __('Basis'), '', true, '20');
            $inputs .= wf_TextInput('addcorpemail', __('Email'), '', true, '20');

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
        $id = ubRouting::filters($id, 'int');
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
