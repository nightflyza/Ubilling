<?php

class CallsHistory {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system billing config as key=>value
     *
     * @var array
     */
    protected $billingCfg = array();

    /**
     * Default number position offset
     *
     * @var int
     */
    protected $offsetNumber = 3;

    /**
     * Default call status position offset
     *
     * @var int
     */
    protected $offsetStatus = 5;

    /**
     * Default detected login offset
     *
     * @var int
     */
    protected $offsetLogin = 7;

    /**
     * Default log path to parse
     */
    protected $dataSource = 'content/documents/askozianum.log';

    /**
     * Creates new CallsHistory instance
     */
    public function __construct() {
        $this->loadConfig();
    }

    /**
     * Loads required configs and sets some options
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->billingCfg = $ubillingConfig->getBilling();
    }

    /**
     * Gets and stores calls history into db from datasource
     * 
     * @return void
     */
    public function saveCalls() {

        $reply = array();
        $cachedReply = $this->cache->get(self::CACHE_KEY, $this->cachingTimeout);

  
        if (file_exists($this->dataSource)) {
            $dateFilter= date("Y-m-d H:");
            $command = $this->billingCfg['CAT'] . ' ' . $this->dataSource.' | '.$this->billingCfg['GREP'].' '.$dateFilter;
            $rawData = shell_exec($command);
            if (!empty($rawData)) {
                $rawData = explodeRows($rawData);
              
                if (!empty($rawData)) {
                    foreach ($rawData as $io => $line) {
                        if (!empty($line)) {
                         
                        }
                    }
                }
            }
        }
        $this->cache->set(self::CACHE_KEY, $reply, $this->cachingTimeout);
    }

}
