<?php

class UniversalQINQ {

    CONST MODULE = '?module=universalqinq';

    /**
     * Placeholder for nyan orm (`qinq` table)
     * 
     * @var object
     */
    protected $qinqdb;

    /**
     * contains erros if any
     * 
     * @var array
     */
    public $error = array();

    /**
     * Contains all 
     * 
     * @var array
     */
    protected $allData;

    /**
     * Placeholder for ubRouting object
     * 
     * @var object
     */
    public $routing;

    public function __construct() {
        $this->qinqdb = new NyanORM('qinq');
        $this->initRouting();
        $this->loadData();

        //$this->tmp = new nyan_qinq();
    }

    /**
     * Creates new protected routing object instance for further usage
     * 
     * @return void
     */
    protected function initRouting() {
        $this->routing = new ubRouting();
    }

    protected function loadData() {
        try {
            $this->qinqdb->where('login', '=', trim($this->routing->get('login', 'mres')));
        } catch (Exception $ex) {
            $this->error[] = $ex;
        }
        $this->allData = $this->qinqdb->getAll('id');
    }

    public function add() {
        try {
            $this->qinqdb->data('login', trim($this->routing->get('login', 'mres')));
            $this->qinqdb->data('svlan', trim($this->routing->get('svlan', 'int')));
            $this->qinqdb->data('cvlan', trim($this->routing->get('cvlan', 'int')));
            $this->qinqdb->create();
            $this->logAdd();
            rcms_redirect(self::MODULE);
        } catch (Exception $ex) {
            $this->error[] = $ex;
        }
    }

    public function delete() {
        try {
            $this->qinqdb->where('id', '=', trim($this->routing->get('id', 'int')));
            $this->qinqdb->delete();
            $this->logDelete();
            rcms_redirect(self::MODULE);
        } catch (Exception $ex) {
            $this->error[] = $ex;
        }
    }

    public function edit() {
        try {
            $this->qinqdb->where('id', '=', trim($this->routing->get('id', 'int')));
            $this->qinqdb->data('login', trim($this->routing->get('login', 'mres')));
            $this->qinqdb->data('svlan', trim($this->routing->get('svlan', 'int')));
            $this->qinqdb->data('cvlan', trim($this->routing->get('cvlan', 'int')));
            $this->qinqdb->save();
            $this->logEdit();
            rcms_redirect(self::MODULE);
        } catch (Exception $ex) {
            $this->error[] = $ex;
        }
    }

    public function showAll() {
        $columns = array(__('ID'), 'S-VLAN', 'C-VLAN', 'Address', 'Real Name', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = '';
        $ajaxURL = '' . self::MODULE . '&ajax=true';
        $result .= show_window('', wf_JqDtLoader($columns, $ajaxURL, false, 'UniversalQINQ', 100, $opts));
        return ($result);
    }

    public function addForm() {
        $addControls = wf_TextInput('login', __('Login'), '', true, '');
        $addControls .= wf_TextInput('svlan', 'S-VLAN', '', true, '', 'digits');
        $addControls .= wf_TextInput('cvlan', 'C-VLAN', '', true, '', 'digits');
        $addControls .= wf_HiddenInput('action', 'add');
        $addControls .= wf_Submit('Save');
        $form = wf_Form('', 'GET', $addControls, 'glamour');
        show_window('', wf_modalAuto(web_icon_extended() . ' ' . __('Add'), __('Add'), $form, 'ubButton'));
    }

    public function editForm() {
        try {
            $addControls = wf_TextInput('login', __('Login'), $this->routing->get('login'), true, '');
            $addControls .= wf_TextInput('svlan', 'S-VLAN', $this->routing->get('svlan'), true, '', 'digits');
            $addControls .= wf_TextInput('cvlan', 'C-VLAN', $this->routing->get('cvlan'), true, '', 'digits');
            $addControls .= wf_HiddenInput('old_login', $this->routing->get('login'));
            $addControls .= wf_HiddenInput('old_svlan', $this->routing->get('svlan'));
            $addControls .= wf_HiddenInput('old_cvlan', $this->routing->get('cvlan'));
            $addControls .= wf_HiddenInput('action', 'edit');
            $addControls .= wf_Submit('Save');
            $form = wf_Form('', 'GET', $addControls, 'glamour');
            show_window('', wf_modalAuto(web_icon_extended() . ' ' . __('Edit'), __('Edit'), $form, 'ubButton'));
        } catch (Exception $ex) {
            $this->error[] = $ex;
        }
    }

    public function showError() {
        foreach ($this->error as $io => $each) {
            
        }
    }

    public function ajaxData() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allData)) {
            $allRealnames = zb_UserGetAllRealnames();
            $allAddress = zb_AddressGetFulladdresslistCached();
            foreach ($this->allData as $io => $each) {
                $userLogin = trim($each['login']);
                $userLink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . @$allAddress[$userLogin], false);
                $actLinks = wf_Link(self::MODULE . '&action=edit&id=' . $each['id'] . '&login=' . $userLogin . '&svlan=' . $each['svlan'] . '&cvlan=' . $each['cvlan'], web_edit_icon(), false);
                $actLinks .= wf_Link(self::MODULE . '&action=delete&id=' . $each['id'], web_delete_icon(), false);
                $data[] = $each['id'];
                $data[] = $each['svlan'];
                $data[] = $each['cvlan'];
                $data[] = $userLink;
                $data[] = @$allRealnames[$userLogin];
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    protected function logAdd() {
        log_register('CREATE universalqinq ('
                . trim($this->routing->get('login', 'mres'))
                . ') s'
                . trim($this->routing->get('svlan', 'int'))
                . '/c'
                . trim($this->routing->get('cvlan', 'int'))
        );
    }

    protected function logDelete() {
        log_register('DELETE universalqinq: (' .
                trim($this->routing->get('login', 'mres')) .
                ') s' .
                trim($this->routing->get('svlan', 'int')) .
                '/c' .
                trim($this->routing->get('cvlan', 'int'))
        );
    }

    protected function logEdit() {
        log_register('EDIT universalqinq ('
                . trim($this->routing->get('old_login', 'mres'))
                . ') FROM s'
                . trim($this->routing->get('old_svlan', 'int'))
                . '/c'
                . trim($this->routing->get('old_cvlan', 'int'))
                . 'ON ('
                . trim($this->routing->get('login', 'mres'))
                . ') s'
                . trim($this->routing->get('svlan', 'int'))
                . '/c'
                . trim($this->routing->get('cvlan', 'int'))
        );
    }

}
