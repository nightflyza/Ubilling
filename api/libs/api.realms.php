<?php

class Realms {

    const TABLE_NAME = 'realms';
    const MODULE = '?module=vlanmanagement&realms=true';

    /**
     * Placeholder for UbRouing object
     * 
     * @var object
     */
    public $routing;

    /**
     * Contains errors
     * 
     * @var array
     */
    protected $error = array();

    /**
     * Contains exceptions
     * 
     * @var array
     */
    protected $exceptions = array();

    /**
     * Placeholder for nyan_orm object for `realms` table
     * 
     * @var object
     */
    protected $db;

    /**
     * Contains all realms
     * 
     * @var array
     */
    protected $allRealms = array();

    /**
     * Realms object
     * 
     * @return void
     */
    public function __construct() {
        $this->db = new NyanORM(self::TABLE_NAME);
        $this->routing = new ubRouting();
        $this->allRealms = $this->db->getAll('id');
    }

    /**
     * Validator
     * 
     * @return bool
     */
    protected function validate() {
        if (!$this->unique()) {
            $this->error[] = __('Realm exists') . ' :' . $this->routing->get('realm', 'mres');
            return(false);
        }

        return(true);
    }

    /**
     * Check if entry unique
     * 
     * @return bool
     */
    protected function unique() {
        try {
            $allRealms = $this->db->getAll('realm');
            if (isset($allRealms[$this->routing->get('realm', 'mres')])) {
                return(false);
            } else {
                return(true);
            }
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
        }
    }

    /**
     * Adding new entry
     * 
     * @return void
     */
    public function add() {
        try {
            if (!empty($this->routing->get('realm', 'mres')) and $this->validate()) {
                $this->db->data($this->routing->get('realm', 'mres'));
                $this->db->data($this->routing->get('description', 'mres'));
                $this->db->save();
                $this->logAdd();
            }
            $this->goToStartOrError();
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError();
        }
    }

    /**
     * Edit entry
     * 
     * @return void
     */
    public function edit() {
        try {
            if (!empty($this->routing->get('id', 'int')) and $this->validate()) {
                $this->db->where('id', '=', $this->routing->get('id', 'int'));
                $this->db->data($this->routing->get('realm', 'mres'));
                $this->db->data($this->routing->get('description', 'mres'));
                $this->db->save();
                $this->logEdit();
            }
            $this->goToStartOrError();
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError();
        }
    }

    /**
     * Delete entry
     * 
     * @return void
     */
    public function delete() {
        try {
            if (!empty($this->routing->get('id', 'int'))) {
                $this->db->where('id', '=', $this->routing->get('id', 'int'));
                $this->db->delete();
                $this->logDelete();
            }
            $this->goToStartOrError();
        } catch (Exception $ex) {
            $this->exceptions[] = $ex;
            $this->goToStartOrError();
        }
    }

    /**
     * Show all the entries
     * 
     * @return string
     */
    public function showAll() {
        $columns = array('ID', 'Realm', 'Description', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = '';
        $ajaxURL = '' . self::MODULE . '&action=ajax';
        $result .= show_window('', wf_JqDtLoader($columns, $ajaxURL, false, __('Realms'), 100, $opts));
        return ($result);
    }

    /**
     * Form to create new entry
     * 
     * @return void
     */
    protected function addForm() {
        $addControls = wf_HiddenInput('module', 'vlanmanagement');
        $addControls .= wf_HiddenInput('realms', 'true');
        $addControls .= wf_HiddenInput('action', 'add');
        $addControls .= wf_TextInput('realm', __('Realm'), '', true, '', 'alphanumeric');
        $addControls .= wf_TextInput('description', __('Description'), '', true, '', 'alphanumeric');
        $addControls .= wf_Submit('Save');
        $form = wf_Form('', 'GET', $addControls, 'glamour');
        return(wf_modalAuto(web_icon_extended() . ' ' . __('Create new entry'), __('Create new entry'), $form, 'ubButton'));
    }

    /**
     * Forming edit form
     * 
     * @param array $each
     * 
     * @return string
     */
    protected function editForm($each) {
        $addControls = wf_HiddenInput('module', 'vlanmanagement');
        $addControls .= wf_HiddenInput('realms', 'true');
        $addControls .= wf_HiddenInput('action', 'edit');
        $addControls .= wf_HiddenInput('id', $each['id']);
        $addControls .= wf_TextInput('realm', __('Realm'), $each['realm'], true, '', 'alphanumeric');
        $addControls .= wf_TextInput('description', __('Description'), $each['description'], true, '', 'alphanumeric');
        $addControls .= wf_HiddenInput('old_realm', $each['realm']);
        $addControls .= wf_Submit('Save');
        $form = wf_Form('', 'GET', $addControls, 'glamour');
        return($form);
    }

    protected function back() {
        return(wf_link(VlanManagement::MODULE, __('Back'), false, 'ubButton'));
    }

    public function links() {
        show_window('', '' .
                $this->back() .
                $this->addForm()
        );
    }

    /**
     * Form all the entries to ajax array
     * 
     * @return void
     */
    public function ajaxData() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allRealms)) {
            foreach ($this->allRealms as $io => $each) {
                $actLinks = wf_modalAuto(web_edit_icon(), __('Edit'), $this->editForm($each), '');
                $actLinks .= wf_Link(self::MODULE . '&action=delete&id=' . $each['id'], web_delete_icon(), false);
                $data[] = $each['id'];
                $data[] = $each['realm'];
                $data[] = $each['description'];
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Redirects user back and show error if any
     * 
     * @return void
     */
    protected function goToStartOrError() {
        if (empty($this->error)) {
            rcms_redirect(self::MODULE);
        } else {
            $this->showError();
            if (!empty($this->exceptions)) {
                $this->showExceptions();
            }
        }
    }

    /**
     * If we have any errors show all of them
     * 
     * @return void
     */
    protected function showError() {
        foreach ($this->error as $io => $each) {
            show_error($each);
        }
    }

    protected function showExceptions() {
        foreach ($this->exceptions as $io => $each) {
            show_error($each);
        }
    }

    /**
     * Log add action
     * 
     * @return void
     */
    protected function logAdd() {
        log_register('CREATE realm (' . trim($this->routing->get('realm', 'mres')) . ')');
    }

    /**
     * Log delete action
     * 
     * @return void
     */
    protected function logDelete() {
        log_register('DELETE realm (' . trim($this->routing->get('realm', 'mres')) . ')');
    }

    /**
     * Log edit action
     * 
     * @return void
     */
    protected function logEdit() {
        log_register('EDIT realm (' . trim($this->routing->get('old_realm', 'mres')) . ') ' . 'ON (' . trim($this->routing->get('realm', 'mres')) . ')');
    }

}
