<?php

/**
 * Like IPAM for VLAN
 */
class VlanManagement {

    const MODULE = '?module=vlanmanagement';
    const MODULE_SVLAN = '?module=vlanmanagement&svlan=true';
    const MODULE_REALMS = '?module=vlanmanagement&realms=true';

    protected $realmDb;
    protected $svlanDb;
    protected $cvlanDb;
    protected $switchesqinqDb;
    protected $allRealms = array();
    protected $error = array();
    protected $exceptions = array();
    public $routing;

    public function __construct() {
        $this->dbInit();
        $this->loadData();
        $this->routing = new ubRouting();
    }

    protected function dbInit() {
        $this->realmDb = new NyanORM('realms');
        $this->svlanDb = new NyanORM('qinq_svlan');
        $this->cvlanDb = new NyanORM('qinq_bindings');
        $this->switchesqinqDb = new NyanORM('switches_qinq');
    }

    protected function loadData() {
        $this->allRealms = $this->realmDb->getAll('id');
    }

    protected function validateSvlan() {
        if (!$this->checkSvlan()) {
            $this->error[] = __('Wrong value') . ': SVLAN ' . $this->routing->get('svlan', 'int');
        }

        if (!$this->uniqueSvlan()) {
            $this->error[] = __('Wrong value') . ': SVLAN ' . $this->routing->get('svlan', 'int') . ' ' . __('already exists');
        }

        if (!empty($this->error)) {
            return(false);
        }
        return(true);
    }

    protected function checkSvlan() {
        if (($this->routing->get('svlan', 'int') >= 0) and ( $this->routing->get('svlan', 'int') <= 4096)) {
            return(true);
        }
        return (false);
    }

    protected function uniqueSvlan() {
        $this->svlanDb->where('realm_id', '=', $this->routing->get('realm_id', 'int'));
        $allSvlan = $this->svlanDb->getAll('svlan');
        if (isset($allSvlan[$this->routing->get('svlan')])) {
            return(false);
        }
        return(true);
    }

    public function addSvlan() {
        
    }

    public function editSvlan() {
        
    }

    public function deleteSvlan() {
        
    }

    public function listRealms() {
        
    }

    protected function addSvlanForm() {
        
    }

    protected function editSvlanForm() {
        
    }

    protected function backSvlan() {
        return(wf_link(self::MODULE, __('Back'), false, 'ubButton'));
    }

    public function linksSvlan() {
        show_window('', '' .
                $this->backSvlan() .
                $this->addSvlanForm()
        );
    }

    public function linksMain() {
        
    }
    
    public function realmAndSvlanSelectors() {
        
    }
    
    public function cvlanMatrix() {
        
    }

}
