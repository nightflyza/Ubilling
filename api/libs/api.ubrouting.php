<?php

class UbillingRouting {

    /**
     * Contains current raw GET variables environment as key=>value
     *
     * @var array
     */
    protected $getVars = array();

    /**
     * Contains current raw POST variables environment as key=>value
     *
     * @var array
     */
    protected $postVars = array();

    /**
     * Creates new Routing object instance
     */
    public function __construct() {
        $this->loadEnvironment();
    }

    /**
     * Preloads raw environment
     * 
     * @return void
     */
    protected function loadEnvironment() {
        $this->getVars = $_GET;
        $this->postVars = $_POST;
    }

    
    public function get($name) {
        $result = false;
        //TODO
        return($result);
    }

    public function post($name) {
        $result = false;
        //TODO
        return($result);
    }

}
