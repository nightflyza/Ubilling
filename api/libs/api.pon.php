<?php

class PONizer {

    protected $allOnu = array();
    protected $allModelsData = array();
    protected $allOltDevices = array();

    public function __construct() {
        $this->loadOltDevices();
        $this->loadOnu();
        $this->loadModels();
    }

    /**
     * Loads all available devices set as OLT
     * 
     * @return void
     */
    protected function loadOltDevices() {
        $query = "SELECT `id`,`ip`,`location` from `switches` WHERE `desc` LIKE '%OLT%';";
        $raw = simple_queryall($query);
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $this->allOltDevices[$each['id']] = $each['ip'] . ' - ' . $each['location'];
            }
        }
    }

    /**
     * Loads avaliable ONUs from database into private data property
     * 
     * @return void
     */
    protected function loadOnu() {
        $query = "SELECT * from `pononu`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allOnu[$each['id']] = $each;
            }
        }
    }

    /**
     * Getter for loaded ONU devices
     * 
     * @return array
     */
    public function getAllOnu() {
        return ($this->allOnu);
    }

    /**
     * Loads available device models from database
     * 
     * @return void
     */
    protected function loadModels() {
        $tmpModels = zb_SwitchModelsGetAll();
        if (!empty($tmpModels)) {
            foreach ($tmpModels as $io => $each) {
                $this->allModelsData[$each['id']] = $each;
            }
        }
    }

    /**
     * Returns model name by its id
     * 
     * @param int $id
     * @return string
     */
    protected function getModelName($id) {
        $result = '';
        if (isset($this->allModelsData[$id])) {
            $result = $this->allModelsData[$id]['modelname'];
        }
        return ($result);
    }

    /**
     * Creates new ONU in database and returns it Id or 0 if action fails
     * 
     * @param int $onumodelid
     * @param int $oltid
     * @param string $ip
     * @param string $mac
     * @param string $serial
     * @param string $login
     * 
     * @return int
     */
    public function onuCreate($onumodelid, $oltid, $ip, $mac, $serial, $login) {
        $onumodelid = vf($onumodelid, 3);
        $oltid = vf($oltid, 3);
        $ip = mysql_real_escape_string($ip);
        $mac = mysql_real_escape_string($mac);
        $serial = mysql_real_escape_string($serial);
        $login = mysql_real_escape_string($login);
        $result = 0;
        if (!empty($mac)) {
            if (check_mac_format($mac)) {
                $query = "INSERT INTO `pononu` (`id`, `onumodelid`, `oltid`, `ip`, `mac`, `serial`, `login`) "
                        . "VALUES (NULL, '" . $onumodelid . "', '" . $oltid . "', '" . $ip . "', '" . $mac . "', '" . $serial . "', '" . $login . "');";
                nr_query($query);
                $result = simple_get_lastid('pononu');
                log_register('PON CREATE ONU [' . $result . ']');
            } else {
                log_register('PON MACINVALID TRY');
            }
        }
        return ($result);
    }

    /**
     * Saves ONU changes into database
     * 
     * @param int $onuId
     * @param int $onumodelid
     * @param int $oltid
     * @param string $ip
     * @param string $mac
     * @param string $serial
     * @param string $login
     */
    public function onuSave($onuId, $onumodelid, $oltid, $ip, $mac, $serial, $login) {
        $onuId = vf($onuId, 3);
        $onumodelid = vf($onumodelid, 3);
        $oltid = vf($oltid, 3);
        $ip = mysql_real_escape_string($ip);
        $mac = mysql_real_escape_string($mac);
        $serial = mysql_real_escape_string($serial);
        $login = mysql_real_escape_string($login);
        $where = " WHERE `id`='" . $onuId . "';";
        simple_update_field('pononu', 'onumodelid', $onumodelid, $where);
        simple_update_field('pononu', 'oltid', $oltid, $where);
        simple_update_field('pononu', 'ip', $ip, $where);
        simple_update_field('pononu', 'mac', $mac, $where);
        simple_update_field('pononu', 'serial', $serial, $where);
        simple_update_field('pononu', 'login', $login, $where);
        log_register('PON EDIT ONU [' . $onuId . ']');
    }

    /**
     * Deletes onu from database by its ID
     * 
     * @param int $onuId
     */
    public function onuDelete($onuId) {
        $onuId = vf($onuId, 3);
        $query = "DELETE from `pononu` WHERE `id`='" . $onuId . "';";
        nr_query($query);
        log_register('PON DELETE ONU [' . $onuId . ']');
    }

    /**
     * Returns ONU creation form
     * 
     * @return string
     */
    protected function onuCreateForm() {
        $models = array();
        if (!empty($this->allModelsData)) {
            foreach ($this->allModelsData as $io => $each) {
                $models[$each['id']] = $each['modelname'];
            }
        }

        $inputs = wf_HiddenInput('createnewonu', 'true');
        $inputs.= wf_Selector('newoltid', $this->allOltDevices, __('OLT device'), '', true);
        $inputs.= wf_Selector('newonumodelid', $models, __('ONU model'), '', true);
        $inputs.= wf_TextInput('newip', __('IP'), '', true, 20);
        $inputs.= wf_TextInput('newmac', __('MAC'), '', true, 20);
        $inputs.= wf_TextInput('newserial', __('Serial number'), '', true, 20);
        $inputs.= wf_TextInput('newlogin', __('Login'), '', true, 20);
        $inputs.= wf_Submit(__('Create'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns ONU edit form
     * 
     * @param int $onuId
     * 
     * @return string
     */
    public function onuEditForm($onuId) {
        $onuId = vf($onuId, 3);
        $result = '';
        if (isset($this->allOnu[$onuId])) {
            $messages = new UbillingMessageHelper();

            $models = array();
            if (!empty($this->allModelsData)) {
                foreach ($this->allModelsData as $io => $each) {
                    $models[$each['id']] = $each['modelname'];
                }
            }

            $inputs = wf_HiddenInput('editonu', $onuId);
            $inputs.= wf_Selector('editoltid', $this->allOltDevices, __('OLT device'), $this->allOnu[$onuId]['oltid'], true);
            $inputs.= wf_Selector('editonumodelid', $models, __('ONU model'), $this->allOnu[$onuId]['onumodelid'], true);
            $inputs.= wf_TextInput('editip', __('IP'), $this->allOnu[$onuId]['ip'], true, 20);
            $inputs.= wf_TextInput('editmac', __('MAC'), $this->allOnu[$onuId]['mac'], true, 20);
            $inputs.= wf_TextInput('editserial', __('Serial number'), $this->allOnu[$onuId]['serial'], true, 20);
            $inputs.= wf_TextInput('editlogin', __('Login'), $this->allOnu[$onuId]['login'], true, 20);
            $inputs.= wf_Submit(__('Save'));


            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result.= wf_CleanDiv();
            $result.= wf_delimiter();

            $result.= wf_Link('?module=ponizer', __('Back'), false, 'ubButton');
            $result.= wf_JSAlertStyled('?module=ponizer&deleteonu=' . $onuId, web_delete_icon() . ' ' . __('Delete'), $messages->getDeleteAlert(), 'ubButton');
        } else {
            $result = wf_tag('div', false, 'alert_error') . __('Strange exeption') . ': ONUID_NOT_EXISTS' . wf_tag('div', true);
        }
        return ($result);
    }

    /**
     * Returns default list controls
     * 
     * @return string
     */
    public function controls() {
        $result = '';

        $result.=wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Create'), __('Create'), $this->onuCreateForm(), 'ubButton');
        $result.=wf_delimiter();
        return ($result);
    }

    /**
     * Renders available ONU JQDT list container
     * 
     * @return string
     */
    public function renderOnuList() {
        $columns = array('ID', 'Model', 'OLT', 'IP', 'MAC', 'Serial number', 'Login', 'Actions');
        $result = wf_JqDtLoader($columns, '?module=ponizer&ajaxonu=true', false, 'ONU');
        return ($result);
    }

    /**
     * Renders json formatted data for jquery data tables list
     * 
     * @return string
     */
    public function ajaxOnuData() {
        $result = '{ 
                  "aaData": [ ';

        if (!empty($this->allOnu)) {
            foreach ($this->allOnu as $io => $each) {
                if (!empty($each['login'])) {
                    $userLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false);
                    $userLink = str_replace('"', '', $userLink);
                    $userLink = trim($userLink);
                } else {
                    $userLink = '';
                }

                $actLinks = wf_Link('?module=ponizer&editonu=' . $each['id'], web_edit_icon(), false);
                $actLinks = str_replace('"', '', $actLinks);
                $actLinks = trim($actLinks);

                $result.='
                    [
                    "' . $each['id'] . '",
                    "' . $this->getModelName($each['onumodelid']) . '",
                    "' . @$this->allOltDevices[$each['oltid']] . '",
                    "' . $each['ip'] . '",
                    "' . $each['mac'] . '",
                    "' . $each['serial'] . '",
                    "' . $userLink . '",
                    "' . $actLinks . '"
                    ],';
            }
        }

        $result = substr($result, 0, -1);

        $result.='] 
        }';

        return ($result);
    }

}

?>