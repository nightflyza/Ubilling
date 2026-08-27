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
     * Live vehicle refresh interval in seconds, 0 means disabled
     *
     * @var int
     */
    protected $liveRefresh = 0;

    /**
     * Enables smooth marker interpolation between live position updates
     *
     * @var bool
     */
    protected $liveSmoothing = false;

    /**
     * Default API URL
     */
    const API_URL = 'https://mapon.com/api/v1/';

    /**
     * Alter option with live refresh interval in seconds
     */
    const OPTION_LIVEREFRESH = 'MAPON_LIVEREFRESH';

    /**
     * Alter option that enables smooth live marker movement
     */
    const OPTION_SMOOTHING = 'MAPON_SMOOTHING';

    /**
     * Prefix for MapCore marker registry ids
     */
    const MARKER_PREFIX = 'mapon_';

    /**
     * Predefined routes, URLs etc.
     */
    const URL_ME = '?module=mapon';
    const ROUTE_FILTER_UNIT = 'filterunit';
    const ROUTE_AJAX_UNITS = 'ajaxunits';
    const ROUTE_ALLDAY_ROUTES = 'alldayroutes';
    const ROUTE_LAYER_SWITCHES = 'layerswitches';
    const ROUTE_LAYER_BUILDS = 'layerbuilds';
    const ROUTE_LAYER_TASKS = 'layertasks';
    const ROUTE_LAYER_ANYONE_TASKS = 'layeranyonetasks';
    const PROUTE_DATE_FROM = 'datefrom';
    const PROUTE_DATE_TO = 'dateto';
    const PROUTE_POINT_LOCATION = 'maponpointlocation';

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
        $this->liveRefresh = 0;
        $this->liveSmoothing = false;
        if (isset($this->altCfg[self::OPTION_LIVEREFRESH])) {
            $liveRefresh = intval($this->altCfg[self::OPTION_LIVEREFRESH]);
            if ($liveRefresh > 0) {
                $this->liveRefresh = $liveRefresh;
            }
        }
        if (isset($this->altCfg[self::OPTION_SMOOTHING])) {
            if (intval($this->altCfg[self::OPTION_SMOOTHING]) > 0) {
                $this->liveSmoothing = true;
            }
        }
    }

    /**
     * Checks is live vehicle refresh enabled
     *
     * @return bool
     */
    public function isLiveRefreshEnabled() {
        $result = false;
        if ($this->liveRefresh > 0) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Returns live vehicle refresh interval in seconds
     *
     * @return int
     */
    public function getLiveRefreshInterval() {
        $result = $this->liveRefresh;
        return ($result);
    }

    /**
     * Checks is live marker movement smoothing enabled
     *
     * @return bool
     */
    public function isLiveSmoothingEnabled() {
        $result = false;
        if ($this->liveSmoothing) {
            $result = true;
        }
        return ($result);
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

    /**
     * Returns canonical vehicle icon key for unit state
     *
     * @param string $state
     * @param float $speed
     *
     * @return string
     */
    public function getUnitIcon($state, $speed = 0) {
        $result = 'vehicle.yellow';
        $speedVal = floatval($speed);
        if ($state == 'driving' or $state == 'moving' or $speedVal > 1) {
            $result = 'vehicle.green';
        } else {
            if ($state == 'standing') {
                $result = 'vehicle.red';
            }
        }
        return ($result);
    }

    /**
     * Returns MapCore registry id for a unit
     *
     * @param string $unitId
     *
     * @return string
     */
    public function getUnitMarkerId($unitId) {
        $result = self::MARKER_PREFIX . $unitId;
        return ($result);
    }

    /**
     * Builds marker payload used both for the first map paint and live JSON updates
     *
     * @param array $unit
     *
     * @return array
     */
    public function getUnitMarkerData($unit) {
        $result = array();
        $unitId = $unit['unitid'];
        $icon = $this->getUnitIcon($unit['state'], $unit['speed']);
        $carName = $unit['driver'] . ' - ' . $unit['number'];
        $state = $unit['label'] . ' - ' . __($unit['state']);
        $mileage = __('Total mileage') . ': ' . ($unit['mileage'] / 1000) . ' ' . __('kilometer');
        $speed = ($unit['speed']) ? $unit['speed'] : 0;
        $voltage = $unit['supply_voltage'];
        $carParams = __('Speed') . ': ' . $speed . ' ' . __('km/h') . wf_tag('br');
        $carParams .= __('Voltage') . ': ' . $voltage . ' ' . __('Volt');
        $carParams .= wf_delimiter(1) . $unit['lat'] . ',' . $unit['lng'];
        $carLabel = $mileage . wf_tag('br') . $carParams;
        $carLinkLast = $this->getUnitUrl($unitId);
        $carLinkToday = $this->getUnitUrl($unitId, true);
        $carSearchControls = trim(wf_Link($carLinkLast, wf_img('skins/icon_search_small.gif', __('Last trip')))) . ' ';
        $carSearchControls .= trim(wf_Link($carLinkToday, wf_img('skins/icon_time_small.png', __('All trips'))));
        $carLabel .= wf_delimiter(0) . $carSearchControls;

        $popupHtml = '';
        if (!empty($state)) {
            $popupHtml .= '<b>' . $state . '</b><br />';
        }
        $popupHtml .= $carName;
        if (!empty($carLabel)) {
            $popupHtml .= '<br>' . $carLabel;
        }

        $result['id'] = $this->getUnitMarkerId($unitId);
        $result['unitid'] = $unitId;
        $result['lat'] = $unit['lat'];
        $result['lng'] = $unit['lng'];
        $result['icon'] = $icon;
        $result['iconPath'] = MapCore::resolveIconPath($icon);
        $result['popupContent'] = $carName;
        $result['popupTitle'] = $state;
        $result['popupFooter'] = $carLabel;
        $result['popupHtml'] = $popupHtml;
        $result['last_update'] = $unit['last_update'];
        $result['state'] = $unit['state'];
        $result['speed'] = $speed;
        return ($result);
    }

    /**
     * Returns module URL for a unit, optionally with all-day routes
     *
     * @param string $unitId
     * @param bool $allDayRoutes
     *
     * @return string
     */
    public function getUnitUrl($unitId, $allDayRoutes = false) {
        $result = self::URL_ME . '&' . self::ROUTE_FILTER_UNIT . '=' . $unitId;
        if ($allDayRoutes) {
            $result .= '&' . self::ROUTE_ALLDAY_ROUTES . '=true';
        }
        return ($result);
    }

    /**
     * Returns live units JSON endpoint URL
     *
     * @param string $unitIdFilter
     *
     * @return string
     */
    public function getLiveAjaxUrl($unitIdFilter = '') {
        $result = self::URL_ME . '&' . self::ROUTE_AJAX_UNITS . '=true';
        if (!empty($unitIdFilter)) {
            $result .= '&' . self::ROUTE_FILTER_UNIT . '=' . urlencode($unitIdFilter);
        }
        return ($result);
    }

    /**
     * Returns live marker payloads, optionally limited to one unit
     *
     * @param string $unitIdFilter
     *
     * @return array
     */
    public function getLiveUnitsData($unitIdFilter = '') {
        $result = array();
        $units = $this->getUnits();
        if (!empty($units)) {
            foreach ($units as $io => $each) {
                if (empty($unitIdFilter) or $each['unitid'] == $unitIdFilter) {
                    $result[] = $this->getUnitMarkerData($each);
                }
            }
        }
        return ($result);
    }

    /**
     * Returns JS that polls unit positions and moves registered MapCore markers
     *
     * @param string $ajaxUrl
     * @param int $intervalMs
     *
     * @return string
     */
    public function getLiveUpdateJs($ajaxUrl, $intervalMs = 0) {
        $result = '';
        if (empty($intervalMs)) {
            $intervalMs = $this->liveRefresh * 1000;
        }
        $intervalMs = intval($intervalMs);
        if ($intervalMs > 0) {
            $jsUrl = json_encode((string) $ajaxUrl);
            if ($jsUrl === false) {
                $jsUrl = '""';
            }
            $smoothJs = 'false';
            if ($this->liveSmoothing) {
                $smoothJs = 'true';
            }
            $result .= '
            (function() {
                var maponLiveUrl = ' . $jsUrl . ';
                var maponLiveMs = ' . $intervalMs . ';
                var maponLiveSmooth = ' . $smoothJs . ';
                var maponLiveBusy = false;
                var maponAnimFrame = null;
                var maponLivePopupOpts = {maxWidth: 320, minWidth: 50, maxHeight: 600, closeButton: true, closeOnEscapeKey: true};

                function maponNow() {
                    var ts = Date.now();
                    if (typeof performance !== "undefined" && typeof performance.now === "function") {
                        ts = performance.now();
                    }
                    return ts;
                }

                function maponEase(t) {
                    var eased = t;
                    if (t < 0.5) {
                        eased = 2 * t * t;
                    } else {
                        eased = 1 - Math.pow(-2 * t + 2, 2) / 2;
                    }
                    return eased;
                }

                function maponStartAnim() {
                    if (!maponAnimFrame && typeof requestAnimationFrame === "function") {
                        maponAnimFrame = requestAnimationFrame(maponAnimTick);
                    }
                }

                function maponAnimTick(now) {
                    var running = false;
                    var markerId = "";
                    if (typeof now === "undefined") {
                        now = maponNow();
                    }
                    if (typeof ubMapMarkers !== "undefined") {
                        for (markerId in ubMapMarkers) {
                            if (ubMapMarkers.hasOwnProperty(markerId)) {
                                var animMarker = ubMapMarkers[markerId];
                                if (animMarker && animMarker._maponTo) {
                                    var elapsed = now - animMarker._maponAnimStart;
                                    var t = elapsed / animMarker._maponAnimMs;
                                    if (t >= 1) {
                                        animMarker.setLatLng([animMarker._maponTo.lat, animMarker._maponTo.lng]);
                                        animMarker._maponTo = null;
                                        animMarker._maponFrom = null;
                                    } else {
                                        t = maponEase(t);
                                        animMarker.setLatLng([
                                            animMarker._maponFrom.lat + (animMarker._maponTo.lat - animMarker._maponFrom.lat) * t,
                                            animMarker._maponFrom.lng + (animMarker._maponTo.lng - animMarker._maponFrom.lng) * t
                                        ]);
                                        running = true;
                                    }
                                }
                            }
                        }
                    }
                    if (running) {
                        maponAnimFrame = requestAnimationFrame(maponAnimTick);
                    } else {
                        maponAnimFrame = null;
                        if (typeof ubMapRefreshMarkerLayer === "function") {
                            ubMapRefreshMarkerLayer();
                        }
                    }
                }

                function maponIsDriving(unit) {
                    var driving = false;
                    var speed = parseFloat(unit.speed);
                    if (isNaN(speed)) {
                        speed = 0;
                    }
                    if (unit.state === "driving" || unit.state === "moving" || speed > 1) {
                        driving = true;
                    }
                    return driving;
                }

                function maponMoveMarker(marker, lat, lng, unit) {
                    var moved = false;
                    var cur = marker.getLatLng();
                    var dist = 0;
                    var isDriving = false;
                    if (!cur) {
                        marker.setLatLng([lat, lng]);
                        moved = true;
                        return moved;
                    }
                    dist = cur.distanceTo(L.latLng(lat, lng));
                    isDriving = maponIsDriving(unit);
                    if (!isDriving) {
                        marker._maponTo = null;
                        marker._maponFrom = null;
                        if (dist >= 15) {
                            marker.setLatLng([lat, lng]);
                            moved = true;
                        }
                        return moved;
                    }
                    if (dist > 0.5) {
                        moved = true;
                        if (maponLiveSmooth && typeof requestAnimationFrame === "function") {
                            marker._maponFrom = {lat: cur.lat, lng: cur.lng};
                            marker._maponTo = {lat: lat, lng: lng};
                            marker._maponAnimStart = maponNow();
                            marker._maponAnimMs = maponLiveMs;
                            maponStartAnim();
                        } else {
                            marker.setLatLng([lat, lng]);
                            marker._maponTo = null;
                            marker._maponFrom = null;
                        }
                    }
                    return moved;
                }

                function maponApplyUnit(unit) {
                    var changed = false;
                    if (!unit || !unit.id || typeof ubMapMarkers === "undefined") {
                        return changed;
                    }
                    var lat = parseFloat(unit.lat);
                    var lng = parseFloat(unit.lng);
                    if (isNaN(lat) || isNaN(lng)) {
                        return changed;
                    }
                    var marker = ubMapMarkers[unit.id];
                    if (!marker) {
                        var newIcon = ubMapGetCachedIcon(unit.icon, unit.iconPath);
                        marker = L.marker([lat, lng], {icon: newIcon});
                        marker._ubMapIconKey = unit.icon;
                        ubMapAttachMarker(marker);
                        if (unit.popupHtml) {
                            marker.bindPopup(unit.popupHtml, maponLivePopupOpts);
                        }
                        ubMapRegisterMarker(unit.id, marker);
                        marker._maponLastUpdate = unit.last_update;
                        changed = true;
                        return changed;
                    }
                    if (marker._maponLastUpdate && marker._maponLastUpdate === unit.last_update) {
                        return changed;
                    }
                    marker._maponLastUpdate = unit.last_update;
                    if (maponMoveMarker(marker, lat, lng, unit)) {
                        changed = true;
                    }
                    if (unit.icon && marker._ubMapIconKey !== unit.icon) {
                        marker.setIcon(ubMapGetCachedIcon(unit.icon, unit.iconPath));
                        marker._ubMapIconKey = unit.icon;
                        changed = true;
                    }
                    if (unit.popupHtml) {
                        if (marker.getPopup()) {
                            marker.setPopupContent(unit.popupHtml);
                        } else {
                            marker.bindPopup(unit.popupHtml, maponLivePopupOpts);
                        }
                    }
                    return changed;
                }

                function maponLiveTick() {
                    if (maponLiveBusy) {
                        return;
                    }
                    maponLiveBusy = true;
                    fetch(maponLiveUrl)
                        .then(function(response) { return response.json(); })
                        .then(function(units) {
                            var changed = false;
                            if (units && units.length) {
                                var i = 0;
                                for (i = 0; i < units.length; i++) {
                                    if (maponApplyUnit(units[i])) {
                                        changed = true;
                                    }
                                }
                            }
                            if (changed && typeof ubMapRefreshMarkerLayer === "function") {
                                ubMapRefreshMarkerLayer();
                            }
                        })
                        .catch(function() {})
                        .then(function() { maponLiveBusy = false; });
                }
                setInterval(maponLiveTick, maponLiveMs);
            })();
        ';
        }
        return ($result);
    }

}

