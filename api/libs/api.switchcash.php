<?php

class SwitchCash {

    /**
     * Contains all available switches financial data as switchId=>data
     *
     * @var array
     */
    protected $allCashData = array();

    /**
     * Contains database abstraction layer for financial data
     *
     * @var object
     */
    protected $swCashDb = '';

    /**
     * Some static defines etc
     */
    const URL_ME = '?module=swcash';
    const URL_SWITCHPROFILE = '?module=switches&edit=';
    const ROUTE_EDIT='switchid';
    const TABLE_FINANCE = 'swcash';

    public function __construct() {
        $this->initDatabase();
        $this->loadAllCashData();
    }

    /**
     * Inits database abstraction layer for further usage
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->swCashDb = new NyanORM(self::TABLE_FINANCE);
    }

    /**
     * Performs loading and preprocessing of available financial data
     * 
     * @return void
     */
    protected function loadAllCashData() {
        $this->allCashData = $this->swCashDb->getAll('switchid');
    }

    /**
     * Renders switch financial data editing form
     * 
     * @param int $switchId
     * 
     * @return string
     */
    public function renderEditForm($switchId) {
        $result = '';
        
        return($result);
    }

}
