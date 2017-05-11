<?php

class widget_traffic extends TaskbarWidget {

    /**
     * Caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Caching timeout in seconds
     *
     * @var int
     */
    protected $timeout = 3600;

    public function __construct() {
        
    }

    /**
     * Initalizes system cache object for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Render traffic data
     * 
     * @return string
     */
    public function getTraffic() {
        $queryDown = "SELECT SUM(D0+D1+D2+D3+D4+D5+D6+D7+D8+D9) as `downloaded` from `users`";
        $dataDown = simple_query($queryDown);
        $queryUp = "SELECT SUM(U0+U1+U2+U3+U4+U5+U6+U7+U8+U9) as `uploaded` from `users`";
        $dataUp = simple_query($queryUp);
        $result = __('Traffic') . ': ' . __('Downloaded') . ' - ' . stg_convert_size($dataDown['downloaded']) . ', ' . __('Uploaded') . ' - ' . stg_convert_size($dataUp['uploaded']);
        $result = $this->widgetContainer($result);
        return ($result);
    }

    /**
     * Widget data with caching
     * 
     * @return string
     */
    public function render() {
        $result = '';
        $this->initCache();
        $obj = $this;
        $result = $this->cache->getCallback('WIDGET_TRAFFIC', function() use ($obj) {
            return ($obj->getTraffic());
        }, $this->timeout);

        return ($result);
    }

}

?>