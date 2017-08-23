<?php

class NasMon {

    /**
     * Contains array of all available grouped by their IP
     *
     * @var array
     */
    protected $allNas = array();

    /**
     * Path to saving NAS checking data
     */
    const CACHE_PATH = 'exports/';

    /**
     * Name of cache file that contains table of NAS states
     */
    const LIST_NAME = 'nasmonlist.dat';

    /**
     * Name of cache file that contains dead NAS-es count
     */
    const DEADCOUNT_NAME = 'nasmondead.dat';

    /**
     * URL to report module
     */
    const URL_ME = '?module=report_nasmon';

    public function __construct() {
        
    }

    /**
     * Loads available NAS array into protected property
     * 
     * @return void
     */
    protected function loadAllNas() {
        $query = "SELECT * from `nas` GROUP BY `nasip`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            $this->allNas = $all;
        }
    }

    /**
     * Performs fast check of all available NASes and returns result as array
     * 
     * @return array
     */
    protected function checkAllNas() {
        $this->loadAllNas();
        $deadCount = 0;
        $result = array();
        $list = '';
        if (!empty($this->allNas)) {
            $cells = wf_TableCell(__('NAS name'));
            $cells.= wf_TableCell(__('IP'));
            $cells.= wf_TableCell(__('Status'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allNas as $io => $each) {
                $icmpState = zb_PingICMP($each['nasip']);
                //second try
                if (!$icmpState) {
                    $icmpState = zb_PingICMP($each['nasip']);
                }

                if ($icmpState) {
                    $aliveFlag = web_bool_led($icmpState) . ' ' . __('Alive');
                } else {
                    $aliveFlag = web_bool_led($icmpState) . ' ' . __('Dead');
                    $deadCount++;
                }
                $cells = wf_TableCell($each['nasname']);
                $cells.= wf_TableCell($each['nasip']);
                $cells.= wf_TableCell($aliveFlag);
                $rows.= wf_TableRow($cells, 'row5');
            }
            $list = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $messages = new UbillingMessageHelper();
            $list = $messages->getStyledMessage(__('No NAS available in database'), 'warning');
        }
        $result['list'] = $list;
        $result['deadcount'] = $deadCount;
        return ($result);
    }

    /**
     * Performs all checks and stores results in cache - required to run periodically
     * 
     * @return void
     */
    public function saveCheckResults() {
        $checkResults = $this->checkAllNas();
        if (!empty($checkResults)) {
            if (isset($checkResults['list'])) {
                file_put_contents(self::CACHE_PATH . self::LIST_NAME, $checkResults['list']);
            }

            if (isset($checkResults['deadcount'])) {
                file_put_contents(self::CACHE_PATH . self::DEADCOUNT_NAME, $checkResults['deadcount']);
            }
        }
    }

    /**
     * Renders cached results of nas checking
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        if (file_exists(self::CACHE_PATH . self::LIST_NAME)) {
            $result = file_get_contents(self::CACHE_PATH . self::LIST_NAME);
        } else {
            $messages = new UbillingMessageHelper();
            $result = $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Returns count of dead NAS servers from cache
     * 
     * @return int
     */
    protected function getDeadCount() {
        $result = 0;
        if (file_exists(self::CACHE_PATH . self::DEADCOUNT_NAME)) {
            $result = file_get_contents(self::CACHE_PATH . self::DEADCOUNT_NAME);
        }
        return ($result);
    }

    /**
     * Returns alert control if required. Used in DarkVoid.
     * 
     * @return string
     */
    public function getNasAlerts() {
        $result = '';
        $deadCount = $this->getDeadCount();
        if ($deadCount > 0) {
            $result = wf_Link(self::URL_ME, wf_img('skins/nasmonalert.png', $deadCount . ' ' . __('NAS servers is dead')), false, '');
        }
        return ($result);
    }

}

?>