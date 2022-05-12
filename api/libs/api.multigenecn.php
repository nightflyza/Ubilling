<?php

class MultigenECN {

    /**
     * Database abstraction layer placeholder
     *
     * @var object
     */
    protected $nasDb = '';

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains all available custom-NAS data as id=>data
     *
     * @var array
     */
    protected $allNasData = array();

    /**
     * some predefined URLs, routes, etc...
     */
    const URL_ME = '?module=multigennascustom';
    const DATA_TABLE = 'mlg_nascustom';

    public function __construct() {
        $this->initMessages();
        $this->initDb();
        $this->loadAllNasData();
    }

    /**
     * Inits system message helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits database abstraction layer
     * 
     * @return void
     */
    protected function initDb() {
        $this->nasDb = new NyanORM(self::DATA_TABLE);
    }

    /**
     * Loads all available custom NAS data from database
     * 
     * @return void
     */
    protected function loadAllNasData() {
        $this->allNasData = $this->nasDb->getAll('id');
    }

    /**
     * Renders available custom NAS-es list
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        if (!empty($this->allNasData)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('NAS name'));
            $cells .= wf_TableCell(__('Radius secret'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allNasData as $nasId => $eachNasData) {
                $cells = wf_TableCell($nasId);
                $cells .= wf_TableCell($eachNasData['ip']);
                $cells .= wf_TableCell($eachNasData['name']);
                $cells .= wf_TableCell($eachNasData['secret']);
                $cells .= wf_TableCell('TODO');
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

}
