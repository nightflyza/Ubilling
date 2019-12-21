<?php

class Envy {

    /**
     * Contains all available devices models
     *
     * @var array
     */
    protected $allModels = array();

    /**
     * Contains filtered devices which need to be provisioned as switchid=>data
     *
     * @var array
     */
    protected $envyDevices = array();

    /**
     * Contains available envy-scripts as modelid=>data
     *
     * @var string
     */
    protected $envyScripts = array();

    /**
     * Creates new envy sin instance
     */
    public function __construct() {
        $this->loadDeviceModels();
    }

    /**
     * Loads available device models from database
     * 
     * @return void
     */
    protected function loadDeviceModels() {
        $this->allModels = zb_SwitchModelsGetAll();
    }

}
