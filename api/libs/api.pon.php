<?php

class PONizer {

    protected $allOnu = array();
    protected $allModelsData = array();
    protected $allOltDevices=array();

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
        $query="SELECT `id`,`ip`,`location` from `switches` WHERE `desc` LIKE '%OLT%';";
        $raw=  simple_queryall($query);
        if (!empty($raw)) {
            foreach ($raw as $io=>$each) {
                $this->allOltDevices[$each['id']]=$each['ip'].' - '.$each['location'];
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
     * @param int $oltmodelid
     * @param string $ip
     * @param string $mac
     * @param string $serial
     * @param string $login
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

        $inputs= wf_HiddenInput('createnewonu', 'true');
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
     * Returns default list controls
     * 
     * @return string
     */
    public function controls() {
        $result='';

        $result.=wf_modalAuto(wf_img('skins/add_icon.png').' '.__('Create'), __('Create'), $this->onuCreateForm(), 'ubButton');
        
        return ($result);
    }

}

?>