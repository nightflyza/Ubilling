<?php

/**
 * Like IPAM for VLAN
 */
class VlanManagement {

    protected $realm_db;
    protected $svlan_db;
    protected $cvlan_db;
    protected $switchesqinq_db;

    public function __construct() {
        $this->dbInit();
    }

    protected function dbInit() {
        $this->realm_db = new NyanORM('realms');
        $this->svlan_db = new NyanORM('qinq_svlan');
        $this->cvlan_db = new NyanORM('qinq_bindings');
        $this->switchesqinq_db = new NyanORM('switches_qinq');
    }

}
