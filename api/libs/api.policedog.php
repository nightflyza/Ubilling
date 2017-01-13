<?php

class PoliceDog {

    /**
     * Contains system alter.ini as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all available MAC data
     *
     * @var array
     */
    protected $macData = array();

    /**
     * Contains all MAC-s to search
     *
     * @var array
     */
    protected $allMacs = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Creates new PoliceDog instance
     */
    public function __construct() {
        $this->loadConfig();
        $this->loadMacData();
        $this->initMessages();
    }

    /**
     * Loads system alter config for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits system message helper object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads current MAC-s data into protected property
     * 
     * @return void
     */
    protected function loadMacData() {
        $query = "SELECT * from `policedog`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->macData[$each['id']] = $each;
                $this->allMacs[$each['mac']] = $each['id'];
            }
        }
    }

    /**
     * Renders module control panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        $result.=wf_modalAuto(web_icon_create() . ' ' . __('Upload new MACs'), __('Upload new MACs'), $this->renderUploadForm(), 'ubButton');

        return ($result);
    }

    /**
     * Renders MAC uploading form
     * 
     * @return string
     */
    protected function renderUploadForm() {
        $result = '';
        $inputs = __('One MAC address per line') . wf_tag('br');
        $inputs.= wf_TextArea('newmacupload', '', '', true, '50x10');
        $inputs.=wf_TextInput('newnotes', __('Notes'), '', true, '40');
        $inputs.=wf_Submit(__('Upload'));
        $result.=wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

}

?>