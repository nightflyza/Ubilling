<?php

/**
 * MapOn cars GPS location service API wrapper
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
        $this->api = new MaponAPI($this->apiKey, self::API_URL);
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
    public function getRoutes($dateFrom, $dateTo) {
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
     * Returns array of all unit routes between selected dates
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * 
     * @return array
     */
    public function getDatesRoutes($dateFrom, $dateTo) {
        $result = array();
        //wrong date format?
        if (!zb_checkDate($dateFrom) or !zb_checkDate($dateTo)) {
            $dateFrom = curdate();
            $dateTo = curdate();
            show_error(__('Wrong date format'));
        }

        //date from is greater than date to?
        if (strtotime($dateFrom) > strtotime($dateTo)) {
            $dateFrom = curdate();
            $dateTo = curdate();
            show_error(__('Start date is greater than end date'));
        }

        //between dates range is too long?
        if (strtotime($dateTo) - strtotime($dateFrom) > 60 * 60 * 24 * 30) {
            $dateFrom = curdate();
            $dateTo = curdate();
            show_error(__('Between dates range is too long'));
        }

        $routes = $this->getRoutes($dateFrom. 'T00:00:00Z', $dateTo. 'T23:59:59Z');

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
        $raw = $this->api->get('unit/list', array('include' => array('drivers', 'supply_voltage')));
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
                            $result[$unitId]['speed'] = $each->speed;
                            $result[$unitId]['lat'] = $each->lat;
                            $result[$unitId]['lng'] = $each->lng;
                            $result[$unitId]['supply_voltage'] = $each->supply_voltage->value;
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