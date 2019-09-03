<?php

class UniversalQINQ {

    /**
     * Placeholder for qinq table
     * 
     * @var object
     */
    protected $qinqdb;

    /**
     * contains erros if any
     * 
     * @var array
     */
    public $error = array();

    /**
     * Contains all 
     * 
     * @var array
     */
    protected $allData;

    public function __construct() {
        $this->qinqdb = new NyanORM('qinq');
        $this->loadData();
        //$this->tmp = new nyan_qinq();
    }

    protected function loadData() {
        try {
            $this->qinqdb->where('login', '=', ubRouting::get('login', 'mres'));
        } catch (Exception $ex) {
            $this->error[] = $ex;
        }
        $this->allData = $this->qinqdb->getAll('id');
    }

    public function showAll() {
        
    }

    public function add() {
        try {
            $this->qinqdb->data('login', ubRouting::get('login', 'mres'));
            $this->qinqdb->data('cvlan', ubRouting::get('cvlan', 'int'));
            $this->qinqdb->data('svlan', ubRouting::get('svlan', 'int'));
            $this->qinqdb->create();
        } catch (Exception $ex) {
            $this->error[] = $ex;
        }
    }

    public function delete() {
        try {
            $this->qinqdb->where('id', '=', ubRouting::get('id', 'int'));
            $this->qinqdb->delete();
        } catch (Exception $ex) {
            $this->error[] = $ex;
        }
    }

    public function edit() {
        try {
            $this->qinqdb->where('id', '=', ubRouting::get('id', 'int'));
            $this->qinqdb->data('login', ubRouting::get('login', 'mres'));
            $this->qinqdb->data('cvlan', ubRouting::get('cvlan', 'int'));
            $this->qinqdb->data('svlan', ubRouting::get('svlan', 'int'));
            $this->qinqdb->save();
        } catch (Exception $ex) {
            $this->error[] = $ex;
        }
    }

}
