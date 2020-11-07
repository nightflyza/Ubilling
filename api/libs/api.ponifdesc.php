<?php

/**
 * Allows to attach some description on PON OLT interfaces
 */
class PONIfDesc {

    /**
     * Database mapping abstraction layer
     *
     * @var object
     */
    protected $dataSource = '';

    /**
     * Contains available descriptions for PON interfaces
     *
     * @var array
     */
    protected $allDescriptions = array();

    /**
     * Default datsource table name
     */
    const TABLE_IFDESC = 'ponifdesc';

    /**
     * Creates new descriptor instance
     */
    public function __construct() {
        $this->initDataSource();
        $this->loadDescriptions();
    }

    /**
     * Inits database abstraction layer
     * 
     * @return void
     */
    protected function initDataSource() {
        $this->dataSource = new NyanORM(self::TABLE_IFDESC);
    }

    /**
     * Loads available descriptions from database
     * 
     * @return void
     */
    protected function loadDescriptions() {
        $this->allDescriptions = $this->dataSource->getAll();
    }

    /**
     * Returns description of PON interface if it exists
     * 
     * @param int $oltId
     * @param string $interface
     * 
     * @return string
     */
    public function getDescription($oltId, $interface) {
        $result = '';
        if (!empty($this->allDescriptions)) {
            foreach ($this->allDescriptions as $io => $each) {
                if ($each['oltid'] == $oltId) {
                    if ($each['iface'] == $interface) {
                        $result = $each['desc'];
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Renders interface description form
     * 
     * @param int $oltId
     * @param string $interface
     * 
     * @return string
     */
    public function renderIfForm($oltId, $interface) {
        $oltId = ubRouting::filters($oltId, 'int');
        $result = '';
        $result .= wf_BackLink(PONizer::URL_ME . '&oltstats=true');
        $result .= wf_CleanDiv() . wf_delimiter(0);
        if (!empty($oltId)) {
            $currentDesc = $this->getDescription($oltId, $interface);
            $inputs = wf_HiddenInput('newoltiddesc', $oltId);
            $interface .= wf_HiddenInput('newoltif', $interface);
            $inputs .= wf_TextInput('newoltifdesc', __('Description') . ' ' . $interface, $currentDesc, false, 20) . ' ';
            $inputs .= wf_Submit(__('Save'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Saves changed interface description in database
     * 
     * @return void
     */
    public function save() {
        if (ubRouting::checkPost(array('newoltiddesc', 'newoltif'))) {
            $oltId = ubRouting::post('newoltiddesc', 'int');
            $interface = ubRouting::post('newoltif');
            $interfaceF = ubRouting::post('newoltif', 'mres');
            $newDescF = ubRouting::post('newoltifdesc', 'mres');
            $newDesc = ubRouting::post('newoltifdesc');
            $currentDesc = $this->getDescription($oltId, $interface);
            //something changed
            if ($currentDesc != $newDescF) {
                //clean old description
                $this->dataSource->where('oltid', '=', $oltId);
                $this->dataSource->where('iface', '=', $interfaceF);
                $this->dataSource->delete();
                //create new
                $this->dataSource->data('oltid', $oltId);
                $this->dataSource->data('iface', $interfaceF);
                $this->dataSource->data('desc', $newDescF);
                $this->dataSource->create();

                log_register('PON OLT [' . $oltId . '] IFACE `' . $interface . '` DESC CHANGE ON `' . $newDesc . '`');
            }
        }
    }

}
