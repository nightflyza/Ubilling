<?php

/**
 * System-wide phonebook
 */
class PhoneBook {

    /**
     * Stores system alter config, preloaded by constructor
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains available contacts data from DB as id=>contactData
     *
     * @var array
     */
    protected $allContacts = array();

    /**
     * Contains available buildpassport contacts data from DB as id=>contactData
     *
     * @var array
     */
    protected $allBuildContacts = array();

    /**
     * Default module route
     */
    const URL_ME = '?module=phonebook';

    public function __construct() {
        $this->loadAlter();
        $this->loadContacts();
        $this->loadBuildPassports();
    }

    /**
     * Loads system alter.ini into protected data property
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
     * Loads available contacts from database
     * 
     * @return void
     */
    protected function loadContacts() {
        $query = "SELECT * from `contacts`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allContacts[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available builpassport contact data from database
     * and do some preprocessing magic
     * 
     * @return void
     */
    protected function loadBuildPassports() {
        if ($this->altCfg['BUILD_EXTENDED']) {
            $query = "SELECT DISTINCT `ownerphone`,`ownername` FROM `buildpassport` WHERE `ownerphone` !=''";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->allBuildContacts[] = array('phone' => $each['ownerphone'], 'name' => $each['ownername']);
                }
            }
        }
    }

    /**
     * Renders contact creation form
     * 
     * @return string
     */
    public function createForm() {
        $inputs = wf_TextInput('newcontactphone', __('Phone'), '', false, '20');
        $inputs .= wf_TextInput('newcontactname', __('Name'), '', false, '20');
        $inputs .= wf_Submit(__('Create'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders contact editing form
     * 
     * @param int $contactId
     * 
     * @return string
     */
    protected function editForm($contactId) {
        $contactId = vf($contactId, 3);
        if (isset($this->allContacts[$contactId])) {
            $inputs = wf_TextInput('editcontactphone', __('Phone'), $this->allContacts[$contactId]['phone'], true, '20');
            $inputs .= wf_TextInput('editcontactname', __('Name'), $this->allContacts[$contactId]['name'], true, '20');
            $inputs .= wf_HiddenInput('editcontactid', $contactId);
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Creates new DB contact record
     * 
     * @param string $phone
     * @param string $name
     * 
     * @return void
     */
    public function createContact($phone, $name) {
        $phoneF = mysql_real_escape_string($phone);
        $nameF = mysql_real_escape_string($name);
        $query = "INSERT INTO `contacts` (`id`,`phone`,`name`) VALUES (NULL, '" . $phoneF . "','" . $nameF . "');";
        nr_query($query);
        $newId = simple_get_lastid('contacts');
        log_register('PHONEBOOK CREATE [' . $newId . '] NAME `' . $name . '` PHONE `' . $phone . '`');
    }

    /**
     * Deletes contact record from database
     * 
     * @param int $contactId
     * 
     * @return void
     */
    public function deleteContact($contactId) {
        $contactId = vf($contactId, 3);
        if (isset($this->allContacts[$contactId])) {
            $query = "DELETE from `contacts` WHERE `id`='" . $contactId . "';";
            nr_query($query);
            log_register('PHONEBOOK DELETE [' . $contactId . ']');
        }
    }

    /**
     * Tequila in his heartbeat, His veins burned gasoline.
     * It kept his motor running but it never kept him clean.
     */

    /**
     * Saves changes into DB if its needed
     * 
     * @return void
     */
    public function saveContact() {
        if (wf_CheckPost(array('editcontactphone', 'editcontactname', 'editcontactid'))) {
            $contactId = vf($_POST['editcontactid'], 3);
            if (isset($this->allContacts[$contactId])) {
                $newPhone = mysql_real_escape_string($_POST['editcontactphone']);
                $newName = mysql_real_escape_string($_POST['editcontactname']);
                $where = " WHERE `id`='" . $contactId . "';";

                if ($this->allContacts[$contactId]['phone'] != $newPhone) {
                    simple_update_field('contacts', 'phone', $newPhone, $where);
                    log_register('PHONEBOOK UPDATE [' . $contactId . '] PHONE `' . $_POST['editcontactphone'] . '`');
                }
                if ($this->allContacts[$contactId]['name'] != $newName) {
                    simple_update_field('contacts', 'name', $newName, $where);
                    log_register('PHONEBOOK UPDATE [' . $contactId . '] NAME `' . $_POST['editcontactname'] . '`');
                }
            }
        }
    }

    /**
     * Renders phone data container
     * 
     * @return string
     */
    public function renderContactsContainer() {
        $result = '';
        if (cfr('PHONEBOOKEDIT')) {
            $columns = array('Phone', 'Name', 'Actions');
        } else {
            $columns = array('Phone', 'Name');
        }
        $opts = '';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&ajax=true', false, 'Phones', 100, $opts);
        return ($result);
    }

    /**
     * Renders phone data with available controls
     * 
     * @return void
     */
    public function renderAjaxContacts() {
        $result = '';
        $json = new wf_JqDtHelper();
        $messages = new UbillingMessageHelper();

        if ((!empty($this->allContacts)) OR ( !empty($this->allBuildContacts))) {

            //normal contacts processing
            if (!empty($this->allContacts)) {
                foreach ($this->allContacts as $io => $each) {
                    $data[] = $each['phone'];
                    $data[] = $each['name'];

                    if (cfr('PHONEBOOKEDIT')) {
                        $actLinks = wf_JSAlert(self::URL_ME . '&deletecontactid=' . $io, web_delete_icon(), $messages->getDeleteAlert());
                        $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->editForm($io));
                        $data[] = $actLinks;
                    }

                    $json->addRow($data);
                    unset($data);
                }
            }

            //build passport contacts processing
            if (!empty($this->allBuildContacts)) {
                foreach ($this->allBuildContacts as $io => $each) {
                    $data[] = $each['phone'];
                    $data[] = $each['name'];
                    if (cfr('PHONEBOOKEDIT')) {
                        $data[] = '';
                    }
                    $json->addRow($data);
                    unset($data);
                }
            }
        }

        $json->getJson();
    }

    /**
     * Returns all available contacts as number=>contact
     * 
     * @return array
     */
    public function getAllContacts() {
        $result = array();
        if (!empty($this->allContacts)) {
            foreach ($this->allContacts as $io => $each) {
                $result[$each['phone']] = $each['name'];
            }
        }
        return($result);
    }

}
