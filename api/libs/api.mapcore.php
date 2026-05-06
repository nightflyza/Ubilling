<?php
/**
 * MapCore API implementation
 */
class MapCore {
    /**
     * Canonical icon key => image path
     *
     * @var array
     */
    protected static $icons = array(
        'marker.blue' => 'skins/mapmarks/blue.png',
        'marker.red' => 'skins/mapmarks/red.png',
        'marker.yellow' => 'skins/mapmarks/yellow.png',
        'marker.green' => 'skins/mapmarks/green.png',
        'marker.pink' => 'skins/mapmarks/pink.png',
        'marker.brown' => 'skins/mapmarks/brown.png',
        'marker.darkblue' => 'skins/mapmarks/darkblue.png',
        'marker.orange' => 'skins/mapmarks/orange.png',
        'marker.grey' => 'skins/mapmarks/grey.png',
        'marker.black' => 'skins/mapmarks/black.png',
        'marker.building' => 'skins/mapmarks/build.png',
        'marker.house' => 'skins/mapmarks/house.png',
        'marker.camping' => 'skins/mapmarks/camping.png',
        'vehicle.red' => 'skins/mapmarks/redcar.png',
        'vehicle.green' => 'skins/mapmarks/greencar.png',
        'vehicle.yellow' => 'skins/mapmarks/yellowcar.png',
        'marker.wifi' => 'skins/mapmarks/wifi.png',
        'marker.camera' => 'skins/mapmarks/camera.png'

    );

    /**
     * Legacy Yandex-like icon aliases => canonical key
     *
     * @var array
     */
    protected static $legacyAliases = array(
        'twirl#lightblueIcon' => 'marker.blue',
        'twirl#lightblueStretchyIcon' => 'marker.blue',
        'twirl#redStretchyIcon' => 'marker.red',
        'twirl#yellowIcon' => 'marker.yellow',
        'twirl#greenIcon' => 'marker.green',
        'twirl#pinkDotIcon' => 'marker.pink',
        'twirl#brownIcon' => 'marker.brown',
        'twirl#nightDotIcon' => 'marker.darkblue',
        'twirl#redIcon' => 'marker.red',
        'twirl#orangeIcon' => 'marker.orange',
        'twirl#greyIcon' => 'marker.grey',
        'twirl#buildingsIcon' => 'marker.building',
        'twirl#houseIcon' => 'marker.house',
        'twirl#campingIcon' => 'marker.camping',
        'twirl#blackIcon' => 'marker.black',
        'redCar' => 'vehicle.red',
        'greenCar' => 'vehicle.green',
        'yellowCar' => 'vehicle.yellow'
    );

    /**
     * Map center in "lat,lng" format, empty means geolocation
     *
     * @var string
     */
    protected $center = '';

    /**
     * Map zoom level
     *
     * @var int
     */
    protected $zoom = 15;

    /**
     * Base layer type: roadmap, satellite, hybrid
     *
     * @var string
     */
    protected $type = 'roadmap';

    /**
     * Map container id
     *
     * @var string
     */
    protected $container = 'ubmap';

    /**
     * Search box prefill text
     *
     * @var string
     */
    protected $searchPrefill = '';

    /**
     * JS snippets with map objects
     *
     * @var string
     */
    protected $placemarks = '';

    /**
     * Additional JS snippets, editor etc.
     *
     * @var string
     */
    protected $extraCode = '';

    /**
     * Maps configuration cache
     *
     * @var array
     */
    protected $mapsCfg = array();

    /**
     * Marker icon cache for rendered JS
     *
     * @var array
     */
    protected $usedIcons = array();

    /**
     * Enables marker clustering
     *
     * @var bool
     */
    protected $clusteringEnabled = false;

    /**
     * Marker cluster options
     *
     * @var array
     */
    protected $clusterOptions = array();


    /**
     * Creates map builder instance
     *
     * @param string $container
     */
    public function __construct($container = 'ubmap') {
        global $ubillingConfig;
        $this->container = $container;
        if (is_object($ubillingConfig)) {
            $this->mapsCfg = $ubillingConfig->getYmaps();
        }
    }

    /**
     * Creates map container markup
     *
     * @param string $width
     * @param string $height
     *
     * @return string
     */
    public function renderContainer($width, $height) {
        $result = '';
        $mapWidth = (!empty($width)) ? $width : '100%';
        $mapHeight = (!empty($height)) ? $height : '800px';
        $result .= wf_tag('div', false, '', 'id="' . $this->container . '" style="width:' . $mapWidth . '; height:' . $mapHeight . ';"');
        $result .= wf_tag('div', true);
        return ($result);
    }

    /**
     * Sets map center
     *
     * @param string $center
     *
     * @return object
     */
    public function setCenter($center) {
        $this->center = trim($center);
        return ($this);
    }

    /**
     * Sets map zoom
     *
     * @param int $zoom
     *
     * @return object
     */
    public function setZoom($zoom) {
        $this->zoom = (int) $zoom;
        return ($this);
    }

    /**
     * Sets initial base layer type
     *
     * @param string $type
     *
     * @return object
     */
    public function setType($type) {
        $type = trim($type);
        if ($type == 'map') {
            $type = 'roadmap';
        }
        $this->type = $type;
        return ($this);
    }

    /**
     * Sets geocoder search prefill
     *
     * @param string $searchPrefill
     *
     * @return object
     */
    public function setSearchPrefill($searchPrefill) {
        $this->searchPrefill = $searchPrefill;
        return ($this);
    }

    /**
     * Appends raw JS snippet to map init code
     *
     * @param string $jsCode
     *
     * @return object
     */
    public function addRawJs($jsCode) {
        $this->extraCode .= $jsCode;
        return ($this);
    }

    /**
     * Enables or disables markers clustering
     *
     * @param bool $enabled
     * @param array $options
     *
     * @return object
     */
    public function enableClustering($enabled = true, $options = array()) {
        if ($enabled) {
            $this->clusteringEnabled = true;
        } else {
            $this->clusteringEnabled = false;
        }
        if (!empty($options)) {
            $this->clusterOptions = $options;
        }
        return ($this);
    }

    /**
     * Adds click editor with coordinates picker and custom HTML form
     *
     * @param string $fieldName
     * @param string $title
     * @param string $formHtml
     * @param int $precision
     *
     * @return object
     */
    public function addClickEditor($fieldName, $title, $formHtml, $precision = 8) {
        $editorId = wf_InputId();
        $fieldName = trim($fieldName);
        $precision = (int) $precision;
        if (empty($precision)) {
            $precision = 8;
        }
        $safeTitle = str_replace("\n", '', $title);
        $safeTitle = str_replace("\r", '', $safeTitle);
        $safeFormHtml = str_replace("'", '`', $formHtml);
        $safeFormHtml = str_replace("\n", '', $safeFormHtml);
        $safeFormHtml = str_replace("\r", '', $safeFormHtml);

        $popupPrefix = '<b>' . $safeTitle . '</b><br>';
        $popupPrefix .= '<form action="" method="POST">';
        $popupPrefix .= '<input type="hidden" name="' . $fieldName . '" value=\'"+e.latlng.lat.toPrecision(' . $precision . ')+\',\'+e.latlng.lng.toPrecision(' . $precision . ')+"\'>' . $safeFormHtml;
        $popupPrefix .= '</form><br>';

        $jsPrefix = $this->quoteJs($popupPrefix);
        $this->extraCode .= '
            var ubEditorPopup_' . $editorId . ' = L.popup();
            function ubEditorOnMapClick_' . $editorId . '(e) {
                ubEditorPopup_' . $editorId . '
                    .setLatLng(e.latlng)
                    .setContent(' . $jsPrefix . ' + e.latlng.lat.toPrecision(' . $precision . ') + "," + e.latlng.lng.toPrecision(' . $precision . '))
                    .openOn(map);
            }
            map.on("click", ubEditorOnMapClick_' . $editorId . ');
        ';
        return ($this);
    }

    /**
     * Alias for addClickEditor, semantic helper for location workflows
     *
     * @param string $fieldName
     * @param string $title
     * @param string $formHtml
     * @param int $precision
     *
     * @return object
     */
    public function addLocationEditor($fieldName, $title, $formHtml, $precision = 8) {
        $this->addClickEditor($fieldName, $title, $formHtml, $precision);
        return ($this);
    }

    /**
     * Registers custom icon in global map icon registry
     *
     * @param string $iconKey
     * @param string $iconPath
     *
     * @return object
     */
    public function registerIcon($iconKey, $iconPath) {
        self::registerIconDefinition($iconKey, $iconPath);
        return ($this);
    }

    /**
     * Registers many custom icons in global map icon registry
     *
     * @param array $icons
     *
     * @return object
     */
    public function registerIcons($icons) {
        self::registerIconDefinitions($icons);
        return ($this);
    }

    /**
     * Registers additional icon alias in global map icon registry
     *
     * @param string $alias
     * @param string $iconKey
     *
     * @return object
     */
    public function registerIconAlias($alias, $iconKey) {
        self::registerAlias($alias, $iconKey);
        return ($this);
    }

    /**
     * Adds marker to map
     *
     * Supported options:
     * - icon: canonical or legacy icon key, also you can use custom icon by registering it with registerIcon method
     * - tooltip: marker tooltip text - will be shown on mouseover
     * - popupTitle: popup title - will be shown in popup
     * - popupFooter: popup footer - will be shown in popup
     *
     * @param string $coords "lat,lng" format
     * @param string $popupContent popup content
     * @param array $options  
     *
     * @return object
     */
    public function addMarker($coords, $popupContent, $options = array()) {
        $markerId = wf_InputId();
        $icon = isset($options['icon']) ? $options['icon'] : 'marker.blue';
        $tooltip = isset($options['tooltip']) ? $options['tooltip'] : '';
        $popupTitle = isset($options['popupTitle']) ? $options['popupTitle'] : '';
        $popupFooter = isset($options['popupFooter']) ? $options['popupFooter'] : '';
        $iconPath = self::resolveIconPath($icon);
        $iconKey = self::normalizeIconKey($icon);
        $this->usedIcons[$iconKey] = $iconPath;

        $popupHtml = '';
        if (!empty($popupTitle)) {
            $popupHtml .= '<b>' . $popupTitle . '</b><br />';
        }
        if (!empty($popupContent)) {
            $popupHtml .= $popupContent;
        }
        if (!empty($popupFooter)) {
            $popupHtml .= '<br>' . $popupFooter;
        }

        $this->placemarks .= $this->buildMarkerJs($markerId, $coords, $iconKey, $iconPath, $popupHtml, $tooltip);

        return ($this);
    }

    /**
     * Adds marker with lazy AJAX popup loading
     *
     * @param string $coords
     * @param string $title
     * @param string $contentUrl
     * @param array $options
     *
     * @return object
     */
    public function addDynamicMarker($coords, $title = '', $contentUrl = '', $options = array()) {
        $markerId = wf_InputId();
        $icon = isset($options['icon']) ? $options['icon'] : 'marker.blue';
        $tooltip = isset($options['tooltip']) ? $options['tooltip'] : $title;
        $iconPath = self::resolveIconPath($icon);
        $iconKey = self::normalizeIconKey($icon);
        $this->usedIcons[$iconKey] = $iconPath;

        $jsIconPath = $this->quoteJs($iconPath);
        $jsIconKey = $this->quoteJs($iconKey);
        $jsContentUrl = $this->quoteJs($contentUrl);
        $jsTooltip = $this->quoteJs($tooltip);
        $jsLoading = $this->quoteJs(__('Loading') . '...');
        $jsError = $this->quoteJs(__('Error') . ' ' . __('Loading'));

        $this->placemarks .= '
            var ubIconDyn_' . $markerId . ' = ubMapGetCachedIcon(' . $jsIconKey . ', ' . $jsIconPath . ');
            var ubMarkerDyn_' . $markerId . ' = L.marker([' . $coords . '], {icon: ubIconDyn_' . $markerId . '}).addTo(ubMarkerLayer);
            ubMarkerDyn_' . $markerId . '.bindPopup(' . $jsLoading . ', {maxWidth: 320, minWidth: 50, maxHeight: 600, closeButton: true, closeOnEscapeKey: true});
            ubMarkerDyn_' . $markerId . '._popupHtml = null;

            ubMarkerDyn_' . $markerId . '.on("click", function (e) {
                var marker = e.target;

                if (marker._popupHtml !== null) {
                    marker.setPopupContent(marker._popupHtml);
                    marker.openPopup();
                } else {
                    marker.setPopupContent(' . $jsLoading . ');
                    marker.openPopup();
                    fetch(' . $jsContentUrl . ')
                        .then(function(response) { return response.text(); })
                        .then(function(html) {
                            marker._popupHtml = html;
                            marker.setPopupContent(html);
                            marker.openPopup();
                        })
                        .catch(function() {
                            marker.setPopupContent(' . $jsError . ');
                            marker.openPopup();
                        });
                }
            });
        ';
        if (!empty($tooltip)) {
            $this->placemarks .= 'ubMarkerDyn_' . $markerId . '.bindTooltip(' . $jsTooltip . ', {sticky: true});';
        }
        return ($this);
    }

    /**
     * Adds map circle
     *
     * @param string $coords
     * @param int $radius
     * @param string $popupContent
     * @param array $options
     *
     * @return object
     */
    public function addCircle($coords, $radius, $popupContent, $options = array()) {
        $color = isset($options['color']) ? $options['color'] : '009d25';
        $opacity = isset($options['opacity']) ? $options['opacity'] : 0.8;
        $fillColor = isset($options['fillColor']) ? $options['fillColor'] : '00a20b55';
        $fillOpacity = isset($options['fillOpacity']) ? $options['fillOpacity'] : 0.5;
        $hint = isset($options['hint']) ? $options['hint'] : '';
        $jsHint = $this->quoteJs($hint);
        $jsPopup = $this->quoteJs($popupContent);

        $circleId = wf_InputId();
        $this->placemarks .= '
            var ubCircle_' . $circleId . ' = L.circle([' . $coords . '], {
                color: "#' . $color . '",
                opacity: ' . $opacity . ',
                fillColor: "#' . $fillColor . '",
                fillOpacity: ' . $fillOpacity . ',
                radius: ' . (int) $radius . '
            }).addTo(map);
        ';
        if (!empty($popupContent)) {
            $this->placemarks .= 'ubCircle_' . $circleId . '.bindPopup(' . $jsPopup . ');';
        }
        if (!empty($hint)) {
            $this->placemarks .= 'ubCircle_' . $circleId . '.bindTooltip(' . $jsHint . ', {sticky: true});';
        }
        return ($this);
    }

    /**
     * Adds line between two points
     *
     * @param string $coord1
     * @param string $coord2
     * @param array $options
     *
     * @return object
     */
    public function addLine($coord1, $coord2, $options = array()) {
        $color = isset($options['color']) ? $options['color'] : '#000000';
        $width = isset($options['width']) ? (int) $options['width'] : 1;
        $hint = isset($options['hint']) ? $options['hint'] : '';
        $lineId = wf_InputId();
        $this->placemarks .= '
            var ubPointA_' . $lineId . ' = new L.LatLng(' . $coord1 . ');
            var ubPointB_' . $lineId . ' = new L.LatLng(' . $coord2 . ');
            var ubLine_' . $lineId . ' = new L.Polyline([ubPointA_' . $lineId . ', ubPointB_' . $lineId . '], {
                color: "' . $color . '",
                weight: ' . $width . ',
                opacity: 0.8,
                smoothFactor: 1
            });
            ubLine_' . $lineId . '.addTo(map);
        ';
        if (!empty($hint)) {
            $this->placemarks .= 'ubLine_' . $lineId . '.bindTooltip(' . $this->quoteJs($hint) . ', {sticky: true});';
        }
        return ($this);
    }

    /**
     * Renders full map HTML and JS
     *
     * @return string
     */
    public function render() {
        $tileLayerOSM = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        $tileLayerSatellite = 'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}';
        $tileLayerHybrid = 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}';
        $tileLayerCustoms = '';
        $searchCode = '';

        if (isset($this->mapsCfg['LEAFLET_TILE_LAYER'])) {
            if ($this->mapsCfg['LEAFLET_TILE_LAYER']) {
                $tileLayerOSM = $this->mapsCfg['LEAFLET_TILE_LAYER'];
                if (ispos($tileLayerOSM, 'visicom')) {
                    $tileLayerCustoms = "subdomains: '123', tms: true";
                } else {
                    if (ispos($tileLayerOSM, 'google.com')) {
                        $tileLayerCustoms = "subdomains:['mt0','mt1','mt2','mt3']";
                    }
                }
            }
        }

        if (!empty($this->searchPrefill)) {
            $searchCode = '
                var searchInput = document.querySelector(".leaflet-control-geocoder-form input");
                if (searchInput) {
                    searchInput.value = ' . $this->quoteJs($this->searchPrefill) . ';
                }
            ';
        }

        $canvasRender = 'false';
        if (isset($this->mapsCfg['CANVAS_RENDER'])) {
            if ($this->mapsCfg['CANVAS_RENDER']) {
                $canvasRender = 'true';
            }
        }

        $mapCenter = '';
        if (empty($this->center)) {
            $mapCenter = '
                map.locate({setView: true, maxZoom: ' . $this->zoom . '});
                function onLocationError(e) {
                    alert(e.message);
                }
                map.on("locationerror", onLocationError);
            ';
        } else {
            $mapCenter = 'map.setView([' . $this->center . '], ' . $this->zoom . ');';
        }

        $result = '';
        $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet/leaflet.css"');
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet/leaflet.js"') . wf_tag('script', true);
        $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-geocoder/Control.Geocoder.css"');
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-geocoder/Control.Geocoder.min.js"') . wf_tag('script', true);
        $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-ruler/src/leaflet-ruler.css"');
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-ruler/src/leaflet-ruler.js"') . wf_tag('script', true);
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-easyprint/dist/bundle.js"') . wf_tag('script', true);
        $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-fullscreen/dist/leaflet.fullscreen.css"');
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-fullscreen/dist/Leaflet.fullscreen.min.js"') . wf_tag('script', true);
        if ($this->clusteringEnabled) {
            $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-markercluster/dist/MarkerCluster.css"');
            $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-markercluster/dist/MarkerCluster.Default.css"');
            $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-markercluster/dist/leaflet.markercluster-src.js"') . wf_tag('script', true);
        }
        $clusterOptions = $this->clusterOptions;
        if ($this->clusteringEnabled) {
            if (!isset($clusterOptions['chunkedLoading'])) {
                $clusterOptions['chunkedLoading'] = true;
            }
        }
        $clusterOptionsJs = json_encode($clusterOptions);
        if ($clusterOptionsJs === false) {
            $clusterOptionsJs = '{}';
        }
        $clusterEnabledJs = ($this->clusteringEnabled) ? 'true' : 'false';
        $result .= wf_tag('script', false, '', 'type = "text/javascript"');
        $result .= '
            var map = L.map("' . $this->container . '", {maxZoom: 18});
            ' . $mapCenter . '
            var ubMapIconCache = {};
            function ubMapGetCachedIcon(iconKey, iconUrl) {
                if (!ubMapIconCache[iconKey]) {
                    ubMapIconCache[iconKey] = L.icon({
                        iconUrl: iconUrl,
                        iconSize: [42, 42],
                        iconAnchor: [22, 41],
                        popupAnchor: [-3, -44]
                    });
                }
                return ubMapIconCache[iconKey];
            }
            var ubMarkerLayer = map;
            if (' . $clusterEnabledJs . ') {
                if (typeof L.markerClusterGroup === "function") {
                    ubMarkerLayer = L.markerClusterGroup(' . $clusterOptionsJs . ');
                    map.addLayer(ubMarkerLayer);
                } else {
                    ubMarkerLayer = map;
                    if (window.console && typeof console.warn === "function") {
                        console.warn("MapCore: local markercluster plugin is unavailable, clustering disabled.");
                    }
                }
            }

            var roadmap = L.tileLayer("' . $tileLayerOSM . '", {
                maxZoom: 18,
                attribution: \'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors\',
                ' . $tileLayerCustoms . '
            });
            var satellite = L.tileLayer("' . $tileLayerSatellite . '", {maxZoom: 18, attribution: "© Google"});
            var hybrid = L.tileLayer("' . $tileLayerHybrid . '", {maxZoom: 18, attribution: "© Google"});

            var storageKey = "ubilling_lmaps_base_' . $this->container . '";
            var savedLayerType = null;
            try {
                savedLayerType = window.localStorage ? localStorage.getItem(storageKey) : null;
            } catch (err) {
                savedLayerType = null;
            }
            var requestedLayerType = "' . $this->type . '";
            var layerToUse = roadmap;
            if (savedLayerType === "hybrid") {
                layerToUse = hybrid;
            } else {
                if (savedLayerType === "satellite") {
                    layerToUse = satellite;
                } else {
                    if (savedLayerType === "roadmap") {
                        layerToUse = roadmap;
                    } else {
                        if (requestedLayerType === "hybrid") {
                            layerToUse = hybrid;
                        } else {
                            if (requestedLayerType === "satellite") {
                                layerToUse = satellite;
                            } else {
                                layerToUse = roadmap;
                            }
                        }
                    }
                }
            }
            layerToUse.addTo(map);

            var baseMaps = {
                "' . __('Map') . '": roadmap,
                "' . __('Hybrid') . '": hybrid,
                "' . __('Satellite') . '": satellite
            };
            var geoControl = new L.Control.Geocoder({showResultIcons: true, errorMessage: "' . __('Nothing found') . '", placeholder: "' . __('Search') . '"});
            geoControl.addTo(map);

            L.easyPrint({
                title: "' . __('Export') . '",
                defaultSizeTitles: {Current: "' . __('Current') . '", A4Landscape: "A4 Landscape", A4Portrait: "A4 Portrait"},
                position: "topright",
                filename: "ubillingmap_' . date("Y-m-d_H:i:s") . '",
                exportOnly: true,
                hideControlContainer: true,
                sizeModes: ["Current", "A4Landscape", "A4Portrait"]
            }).addTo(map);

            var options = {
                position: "topright",
                preferCanvas: "' . $canvasRender . '",
                lengthUnit: {
                    display: "' . __('meters') . '",
                    decimal: 2,
                    factor: 1000,
                    label: "' . __('Distance') . ':"
                },
                angleUnit: {
                    display: "&deg;",
                    decimal: 2,
                    factor: null,
                    label: "' . __('Bearing') . ':"
                }
            };
            L.control.ruler(options).addTo(map);
            map.addControl(new L.Control.Fullscreen({
                title: {
                    "false": "' . __('Fullscreen') . '",
                    "true": "' . __('Exit fullscreen') . '"
                }
            }));
            map.addControl(L.control.layers(baseMaps, null, {collapsed: true}));
            map.on("baselayerchange", function(e) {
                var v = "roadmap";
                if (e && e.layer === hybrid) {
                    v = "hybrid";
                } else {
                    if (e && e.layer === satellite) {
                        v = "satellite";
                    }
                }
                try {
                    localStorage.setItem(storageKey, v);
                } catch (err) {
                }
            });

            ' . $this->placemarks . '
            ' . $this->extraCode . '
            ' . $searchCode . '
        ';
        $result .= wf_tag('script', true);

        return ($result);
    }

    /**
     * Quotes string for JS using JSON encoding
     *
     * @param string $value
     *
     * @return string
     */
    protected function quoteJs($value) {
        $result = json_encode((string) $value);
        if ($result === false) {
            $result = '""';
        }
        return ($result);
    }

    /**
     * Builds marker JS with icon cache support
     *
     * @param string $markerId
     * @param string $coords
     * @param string $iconKey
     * @param string $iconPath
     * @param string $popupHtml
     * @param string $tooltip
     *
     * @return string
     */
    protected function buildMarkerJs($markerId, $coords, $iconKey, $iconPath, $popupHtml, $tooltip) {
        $result = '';
        $jsPopup = $this->quoteJs($popupHtml);
        $jsTooltip = $this->quoteJs($tooltip);
        $jsIconPath = $this->quoteJs($iconPath);
        $jsIconKey = $this->quoteJs($iconKey);
        $result .= '
            var ubIcon_' . $markerId . ' = ubMapGetCachedIcon(' . $jsIconKey . ', ' . $jsIconPath . ');
            var ubMarker_' . $markerId . ' = L.marker([' . $coords . '], {icon: ubIcon_' . $markerId . '}).addTo(ubMarkerLayer);
            ubMarker_' . $markerId . '.bindPopup(' . $jsPopup . ', {maxWidth: 320, minWidth: 50, maxHeight: 600, closeButton: true, closeOnEscapeKey: true});
        ';
        if (!empty($tooltip)) {
            $result .= 'ubMarker_' . $markerId . '.bindTooltip(' . $jsTooltip . ', {sticky: true});';
        }
        return ($result);
    }

    /**
     * Resolves icon key (canonical or legacy) to icon image path
     *
     * @param string $iconKey
     *
     * @return string
     */
    public static function resolveIconPath($iconKey) {
        $resolved = self::normalizeIconKey($iconKey);
        $result = self::$icons['marker.blue'];
        if (isset(self::$icons[$resolved])) {
            $result = self::$icons[$resolved];
        } else {
            show_warning('Unknown map icon key received: ' . $iconKey);
        }
        return ($result);
    }

    /**
     * Normalizes canonical or legacy icon key
     *
     * @param string $iconKey
     *
     * @return string
     */
    public static function normalizeIconKey($iconKey) {
        $result = trim($iconKey);
        if (empty($result)) {
            $result = 'marker.blue';
        } else {
            if (isset(self::$legacyAliases[$result])) {
                $result = self::$legacyAliases[$result];
            }
        }
        return ($result);
    }

    /**
     * Registers or overrides icon path by key
     *
     * @param string $iconKey
     * @param string $iconPath
     *
     * @return bool
     */
    public static function registerIconDefinition($iconKey, $iconPath) {
        $result = false;
        $iconKey = trim($iconKey);
        $iconPath = trim($iconPath);
        if (!empty($iconKey) and !empty($iconPath)) {
            self::$icons[$iconKey] = $iconPath;
            $result = true;
        }
        return ($result);
    }

    /**
     * Registers many icon paths at once
     *
     * @param array $icons
     *
     * @return int
     */
    public static function registerIconDefinitions($icons) {
        $result = 0;
        if (!empty($icons)) {
            foreach ($icons as $iconKey => $iconPath) {
                if (self::registerIconDefinition($iconKey, $iconPath)) {
                    $result++;
                }
            }
        }
        return ($result);
    }

    /**
     * Registers legacy alias for custom icon key
     *
     * @param string $alias
     * @param string $iconKey
     *
     * @return bool
     */
    public static function registerAlias($alias, $iconKey) {
        $result = false;
        $alias = trim($alias);
        $iconKey = trim($iconKey);
        if (!empty($alias) and !empty($iconKey)) {
            self::$legacyAliases[$alias] = $iconKey;
            $result = true;
        }
        return ($result);
    }
}

