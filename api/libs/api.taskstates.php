<?php

class TaskStates {

    /**
     * Contains available state types as type=>label
     *
     * @var array
     */
    protected $stateTypes = array();

    public function __construct() {
        $this->setTypes();
    }

    /**
     * Sets default task states types for further usage
     * 
     * @return void
     */
    protected function setTypes() {
        $this->stateTypes['STATE_DONE'] = ' ' . __('Done');
        $this->stateTypes['STATE_UNDONE'] = ' ' . __('Undone');
        $this->stateTypes['STATE_MOVED'] = ' ' . __('Moved');
        $this->stateTypes['STATE_CALLFAIL'] = '️ ' . __('Missed a phone call');
        $this->stateTypes['STATE_CANCELLED'] = ' ' . __('Canceled');
        $this->stateTypes['STATE_CONNECTOR'] = ' ' . __('Fixed connector');
    }
    
    

}
