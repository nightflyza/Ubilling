<?php

class RoRoDog {

    /**
     * Contains available printers for agents
     *
     * @var array
     */
    protected $printers = array();

    public function __construct() {
        //load some data here
    }

    protected function readPayments() {
        //reads payments from database
    }

    protected function pushPayments() {
        //pushes payments to printers
    }

    public function processPayments() {
        $this->readPayments();
        $this->pushPayments();
    }

}
