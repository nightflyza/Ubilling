<?php

class CallMeBack {

    /**
     * Some calls data model placeholder
     */
    protected $calls = '';

    public function __construct() {
        $this->initCalls();
    }

    /**
     * Inits calls model for further usage
     */
    protected function initCalls() {
        $this->calls = new NyanORM('callmeback');
    }

    /**
     * Returns array of all calls which required some reaction as id=>calldata
     * 
     * @return array
     */
    public function getUndoneCalls() {
        $result = array();
        $this->calls->where('state', '=', 'undone');
        return($this->calls->getAll('id'));
    }

    /**
     * Create some callback record in database for further employee reactions.
     * 
     * @param int $number
     * 
     * @return void
     */
    public function createCall($number) {
        $number = ubRouting::filters($number, 'int');
        $this->calls->data('date', curdatetime());
        $this->calls->data('number', $number);
        $this->calls->data('state', 'undone');
        $this->calls->create();
    }

    /**
     * Returns unprocessed calls count
     * 
     * @return int
     */
    public function getUndoneCount() {
        $this->calls->where('state', '=', 'undone');
        $result = $this->calls->getFieldsCount();
        return($result);
    }

}
