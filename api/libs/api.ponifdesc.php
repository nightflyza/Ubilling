<?php

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
        $result = '';
        $result .= 'TODO';
        return($result);
    }

}
