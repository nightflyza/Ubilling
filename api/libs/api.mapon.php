<?php

/**
 * MapOn service API wrapper
 */
class MapOn {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains 
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * MaponAPI SDK object placeholder
     *
     * @var object
     */
    protected $api = '';

    /**
     * System messages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Default API URL
     */
    const API_URL = 'https://mapon.com/api/v1/';

    /**
     * Creates new API wrapper
     */
    public function __construct() {
        $this->loadConfig();
        $this->initMessages();
        $this->initMapOn();
    }

    /**
     * Loads all required configs and sets some options
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->apiKey = $this->altCfg['MAPON_APIKEY'];
    }

    /**
     * Inits MaponAPI SDK object into protected proterty for further usage
     * 
     * @return void
     */
    protected function initMapOn() {
        require_once 'api/libs/api.maponapi.php';
        $this->api = new Mapon\MaponAPI($this->apiKey, self::API_URL);
    }

    /**
     * Inits system message helper object instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Get all unit routes between some dates
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * 
     * @return stdObj
     */
    protected function getRoutes($dateFrom, $dateTo) {
        $result = $this->api->get('route/list', array(
            'from' => '' . $dateFrom,
            'till' => '' . $dateTo,
            'include' => array('polyline', 'speed')
        ));
        return ($result);
    }

    /**
     * Returns array of all unit routes by current day
     * 
     * @return array
     */
    public function getTodayRoutes() {
        $result = array();
        $curday = curdate();
        $routes = $this->getRoutes($curday . 'T00:00:00Z', $curday . 'T23:59:59Z');

        if ($routes) {
            if (isset($routes->data)) {
                foreach ($routes->data->units as $io => $each) {
                    $unitId = $each->unit_id;
                    foreach ($each->routes as $route) {
                        if ($route->type == 'route') {
                            if (@$route->speed) {
                                $points = $this->api->decodePolyline($route->polyline, $route->speed, strtotime($route->start->time));
                                $result[$unitId][] = $points;
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Reuturns current units state
     * 
     * @return array
     */
    public function getUnits() {
        $result = array();
        $raw = $this->api->get('unit/list', array('include' => array('drivers')));
        if ($raw) {
            if ($raw->data) {
                foreach ($raw->data as $io => $eachUnit) {
                    if (!empty($eachUnit)) {
                        foreach ($eachUnit as $ia => $each) {
                            $unitId = $each->unit_id;
                            $result[$unitId]['unitid'] = $unitId;
                            $result[$unitId]['label'] = $each->label;
                            $result[$unitId]['number'] = $each->number;
                            $result[$unitId]['mileage'] = $each->mileage;
                            $result[$unitId]['lat'] = $each->lat;
                            $result[$unitId]['lng'] = $each->lng;
                            $result[$unitId]['last_update'] = $each->last_update;
                            $result[$unitId]['state'] = $each->state->name;
                            $result[$unitId]['driver'] = @$each->drivers->driver1->name;
                        }
                    }
                }
            }
        }
        return ($result);
    }

}

?>