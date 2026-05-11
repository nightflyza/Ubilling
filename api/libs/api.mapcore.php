<?php
/**
 * MapCore API implementation
 */
class MapCore {
  
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
     * Base layer type: roadmap, satellite, hybrid, terrain
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
     * External JS files to load before the main map inline script (relative URLs)
     *
     * @var array
     */
    protected $extraScriptSrcs = array();

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
     * Forces placemark markers rendering via leaflet-markers-canvas
     *
     * @var bool
     */
    protected $forceCanvasMarkers = false;

    /**
     * Remember zoom level
     *
     * @var bool
     */
    protected $rememberZoom=false;

    /**
     * Remember map center position
     *
     * @var bool
     */
    protected $rememberPosition=false;

    /**
     * Remember last used layer
     *
     * @var bool
     */
    protected $rememberLayer=false;

    /**
     * Enables FPS meter overlay control
     *
     * @var bool
     */
    protected $fpsMeterEnabled = false;

    /**
     * FPS meter refresh interval in milliseconds
     *
     * @var int
     */
    protected $fpsMeterInterval = 1000;

    /**
     * FPS meter control position
     *
     * @var string
     */
    protected $fpsMeterPosition = 'bottomleft';

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
        'marker.camera' => 'skins/mapmarks/camera.png',
        'marker.waterfall' => 'skins/mapmarks/waterfall.png'

    );

    /**
     * Additional attributions for map
     *
     * @var string
     */
    protected $additionalAttributions='';

    /**
     * Custom Leaflet tile layer URL template, empty means default OSM
     *
     * @var string
     */
    protected $tileLayer = '';

    /**
     * Raw JS options appended to base L.tileLayer (subdomains, tms, etc.)
     *
     * @var string
     */
    protected $tileLayerCustoms = '';

    /**
     * Enables canvas rendering for vector layers (Leaflet preferCanvas)
     *
     * @var bool
     */
    protected $canvasRender = false;


    /**
     * Creates map builder instance
     *
     * @param string $container
     */
    public function __construct($container = 'ubmap') {
        global $ubillingConfig;
        $this->setContainerName($container);
        if (is_object($ubillingConfig)) {
            $this->mapsCfg = $ubillingConfig->getYmaps();
        }
        $this->loadConfigOptions();
    }

 
    /**
     * Loads ymaps.ini options into object properties
     *
     * @return void
     */
    protected function loadConfigOptions() {
        $this->preprocessTileLayerOpts();

        if (isset($this->mapsCfg['CANVAS_RENDER'])) {
            if ($this->mapsCfg['CANVAS_RENDER']) {
                $this->canvasRender = true;
            } else {
                $this->canvasRender = false;
            }
        }

        if (isset($this->mapsCfg['METRICS_ENABLED'])) {
            if ($this->mapsCfg['METRICS_ENABLED']) {
                $this->fpsMeterEnabled = true;
            } else {
                $this->fpsMeterEnabled = false;
            }
        }

        if (isset($this->mapsCfg['REMEMBER_LAYER'])) {
            if ($this->mapsCfg['REMEMBER_LAYER']) {
                $this->rememberLayer = true;
            } else {
                $this->rememberLayer = false;
            }
        }
    }

    /**
     * Preprocesses tile layer options from maps config: custom tile URL, Leaflet options, attributions
     *
     * @return void
     */
    protected function preprocessTileLayerOpts() {
        if (isset($this->mapsCfg['LEAFLET_TILE_LAYER'])) {
            $tileLayer = trim((string) $this->mapsCfg['LEAFLET_TILE_LAYER']);
            if (!empty($tileLayer)) {
                $this->tileLayer = $tileLayer;
                if (ispos($tileLayer, 'visicom')) {
                    $this->tileLayerCustoms = "subdomains: '123', tms: true";
                    $this->additionalAttributions = '| <a href="https://www.visicom.ua">Visicom</a>';
                } else {
                    if (ispos($tileLayer, 'google.com')) {
                        $this->tileLayerCustoms = "subdomains:['mt0','mt1','mt2','mt3']";
                        $this->additionalAttributions = '| <a href="https://www.google.com">Google</a>';
                    }
                }
                if (ispos($tileLayer, 'kaminari')) {
                    $this->additionalAttributions = '| ⚡ <a href="https://github.com/nightflyza/kaminaritile">KaminariTile</a>';
                }
                if (ispos($tileLayer, 'mapbox')) {
                    $this->additionalAttributions = '| <a href="https://www.mapbox.com">Mapbox</a>';
                }
            }
        }
    }


    /**
     * Sets container/instance name
     *
     * @param string $containerName - name of container
     *
     * @return void
     */
    public function setContainerName($containerName) {
        $this->container = $containerName;
    }

    /**
     * Creates map container markup
     *
     * @param string $width - width of container in pixels or percentage
     * @param string $height - height of container in pixels or percentage
     * @param string $class - class of container
     * @param string $options - additional raw options for container
     *
     * @return string
     */
    public function renderContainer($width = '100%', $height = '700px', $class = '', $options = '') {
        $result = '';
        $classId='mapcore_style_'.wf_InputId();
        $containerClass = $classId;
        if (!empty($class)) {
            $containerClass .= ' ' . $class;
        }
      
        $containerParams = 'id="' . $this->container . '"';
        if (!empty($options)) {
            $containerParams .= ' ' . trim($options);
        }

        $customStyle = wf_tag('style', false);
        $customStyle .= '.' . $classId . ' {';
        $customStyle .= 'width: ' . $width . '; height: ' . $height . ';}';
        $customStyle .=wf_tag('style', true);
        
        $result .= $customStyle;
        $result .= wf_tag('div', false, $containerClass, $containerParams);
        $result .= wf_tag('div', true);
        return ($result);
    }

    /**
     * Sets map center
     *
     * @param string $center - "lat,lng" coordinates of map center
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
     * @param int $zoom - zoom level
     *
     * @return object
     */
    public function setZoom($zoom) {
        $this->zoom = (int) $zoom;
        return ($this);
    }

    /**
     * Enables or disables saving/restoring map zoom via localStorage
     *
     * @param bool $rememberZoom - true to enable saving/restoring map zoom via localStorage
     *
     * @return object
     */
    public function setRememberZoom($rememberZoom = true) {
        if ($rememberZoom) {
            $this->rememberZoom = true;
        } else {
            $this->rememberZoom = false;
        }
        return ($this);
    }

    /**
     * Enables or disables saving/restoring map center via localStorage
     *
     * @param bool $rememberPosition - true to enable saving/restoring map center via localStorage
     *
     * @return object
     */
    public function setRememberPosition($rememberPosition = true) {
        if ($rememberPosition) {
            $this->rememberPosition = true;
        } else {
            $this->rememberPosition = false;
        }
        return ($this);
    }

    /**
     * Enables or disables saving/restoring last used base layer via localStorage
     *
     * @param bool $rememberLayer - true to enable saving/restoring last used base layer
     *
     * @return object
     */
    public function setRememberLayer($rememberLayer = true) {
        if ($rememberLayer) {
            $this->rememberLayer = true;
        } else {
            $this->rememberLayer = false;
        }
        return ($this);
    }

    /**
     * Sets initial base layer type
     *
     * @param string $type - type of map (map, satellite, hybrid, terrain)
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
     * @param string $searchPrefill - text to prefill search box
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
     * Registers an extra script URL to include before the main map script (deduplicated)
     *
     * @param string $url
     *
     * @return object
     */
    public function addScriptSrc($url) {
        $url = (string) $url;
        if ($url !== '' and !in_array($url, $this->extraScriptSrcs, true)) {
            $this->extraScriptSrcs[] = $url;
        }
        return ($this);
    }

    /**
     * Returns raw map overlays payload (markers, shapes and extra JS)
     *
     * This payload can be injected into another MapCore instance with
     * injectMapObjects() method.
     *
     * @return array
     */
    public function getMapObjects() {
        $result = array(
            'placemarks' => $this->placemarks,
            'extraCode' => $this->extraCode,
            'usedIcons' => $this->usedIcons
        );
        return ($result);
    }

    /**
     * Injects map overlays payload exported by getMapObjects()
     *
     * Supported payload formats:
     * - array from getMapObjects()
     * - MapCore object (payload will be extracted automatically)
     *
     * @param array|object $mapObjects
     * @param bool $replace
     *
     * @return object
     */
    public function injectMapObjects($mapObjects, $replace = false) {
        if (is_object($mapObjects)) {
            if (method_exists($mapObjects, 'getMapObjects')) {
                $mapObjects = $mapObjects->getMapObjects();
            }
        }

        if (is_array($mapObjects)) {
            if ($replace) {
                $this->placemarks = '';
                $this->extraCode = '';
                $this->usedIcons = array();
            }

            if (isset($mapObjects['placemarks'])) {
                $this->placemarks .= $mapObjects['placemarks'];
            }
            if (isset($mapObjects['extraCode'])) {
                $this->extraCode .= $mapObjects['extraCode'];
            }
            if (isset($mapObjects['usedIcons'])) {
                if (is_array($mapObjects['usedIcons'])) {
                    $this->usedIcons = array_merge($this->usedIcons, $mapObjects['usedIcons']);
                }
            }
        }
        return ($this);
    }

    /**
     * Returns only map placemarks JS buffer
     *
     * @return string
     */
    public function getPlacemarks() {
        $result = $this->placemarks;
        return ($result);
    }

    /**
     * Injects raw map placemarks JS into map object
     *
     * @param string $placemarks - raw JS code to inject
     * @param bool $replace - replace existing placemarks with new ones
     *
     * @return object
     */
    public function injectPlacemarks($placemarks, $replace = false) {
        if ($replace) {
            $this->placemarks = '';
        }
        $this->placemarks .= $placemarks;
        return ($this);
    }

    /**
     * Enables or disables markers clustering
     *
     * @param bool $enabled - true to enable clustering, false to disable
     * @param array $options - may contain maxClusterRadius, iconCreateFunction, chunkedLoading, chunkInterval, chunkDelay, chunkProgress
     *
     * @return object
     */
    public function setClustering($enabled = true, $options = array()) {
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
     * Forces placemark markers rendering via canvas markers layer
     *
     * @param bool $enabled
     *
     * @return object
     */
    public function setForceCanvasMarkers($enabled = true) {
        if ($enabled) {
            $this->forceCanvasMarkers = true;
        } else {
            $this->forceCanvasMarkers = false;
        }
        return ($this);
    }

    /**
     * Enables or disables FPS meter control on map
     *
     * @param bool $enabled
     * @param int $intervalMs - meter refresh interval in milliseconds
     * @param string $position - topright|topleft|bottomright|bottomleft
     *
     * @return object
     */
    public function setFpsMeter($enabled = true, $intervalMs = 1000, $position = 'bottomleft') {
        if ($enabled) {
            $this->fpsMeterEnabled = true;
        } else {
            $this->fpsMeterEnabled = false;
        }
        $intervalMs = (int) $intervalMs;
        if ($intervalMs < 100) {
            $intervalMs = 100;
        }
        $this->fpsMeterInterval = $intervalMs;
        $position = trim((string) $position);
        if ($position == 'topright' or $position == 'topleft' or $position == 'bottomright' or $position == 'bottomleft') {
            $this->fpsMeterPosition = $position;
        } else {
            $this->fpsMeterPosition = 'bottomleft';
        }
        return ($this);
    }

    /**
     * Adds location editor with coordinates picker and custom HTML form
     *
     * @param string $fieldName - name of field to store coordinates
     * @param string $title - title of editor
     * @param string $formHtml - HTML form to display in popup
     * @param int $precision - precision of coordinates (number of decimal places)
     *
     * @return object
     */
    public function addLocationEditor($fieldName, $title, $formHtml, $precision = 8) {
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

        $fieldId = 'ubMapEditorField_' . $editorId;
        $coordsLabelId = 'ubMapEditorCoords_' . $editorId;
        $popupPrefix = '<b>' . $safeTitle . '</b><br>';
        $popupPrefix .= '<form action="" method="POST">';
        $popupPrefix .= '<input type="hidden" name="' . $fieldName . '" id="' . $fieldId . '" value="">' . $safeFormHtml;
        $popupPrefix .= '</form><br><span id="' . $coordsLabelId . '"></span>';

        $jsPrefix = $this->quoteJs($popupPrefix);
        $this->extraCode .= '
            var ubEditorPopup_' . $editorId . ' = L.popup();
            function ubEditorOnMapClick_' . $editorId . '(e) {
                var ubEditorCoordsValue = e.latlng.lat.toPrecision(' . $precision . ') + "," + e.latlng.lng.toPrecision(' . $precision . ');
                ubEditorPopup_' . $editorId . '
                    .setLatLng(e.latlng)
                    .setContent(' . $jsPrefix . ')
                    .openOn(map);
                setTimeout(function() {
                    var ubEditorField = document.getElementById(' . $this->quoteJs($fieldId) . ');
                    if (ubEditorField) {
                        ubEditorField.value = ubEditorCoordsValue;
                    }
                    var ubEditorCoords = document.getElementById(' . $this->quoteJs($coordsLabelId) . ');
                    if (ubEditorCoords) {
                        ubEditorCoords.textContent = ubEditorCoordsValue;
                    }
                }, 0);
            }
            map.on("click", ubEditorOnMapClick_' . $editorId . ');
        ';
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
     * Adds marker to map
     *
     * @param string $coords - "lat,lng" format
     * @param string $popupContent - popup content
     * @param array $options - Supported options:
     * - icon: canonical icon key, also you can use custom icon by registering it with registerIcon method
     * - tooltip: marker tooltip text - will be shown on mouseover
     * - popupTitle: popup title - will be shown in popup
     * - popupFooter: popup footer - will be shown in popup
     *
     * @return object
     */
    public function addMarker($coords, $popupContent = '', $options = array()) {
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
     * @param string $coords - "lat,lng" coordinates of marker
     * @param string $title - title of marker
     * @param string $contentUrl - URL of content to load for popup
     * @param array $options - Supported options:
     * - icon: canonical icon key, also you can use custom icon by registering it with registerIcon method
     * - tooltip: marker tooltip text - will be shown on mouseover
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
            var ubMarkerDyn_' . $markerId . ' = L.marker([' . $coords . '], {icon: ubIconDyn_' . $markerId . '});
            ubMapAttachMarker(ubMarkerDyn_' . $markerId . ');
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
     * @param string $coords - "lat,lng" coordinates of center of circle
     * @param int $radius - radius in meters
     * @param string $popupContent - content of popup window
     * @param array $options - Supported options:
     * - color: stroke color hex without # (default: 009d25)
     * - opacity: stroke opacity 0..1 (default: 0.8)
     * - fillColor: fill color hex without # (default: 00a20b)
     * - fillOpacity: fill opacity 0..1 (default: 0.5)
     * - hint: tooltip text shown on mouseover
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
     * @param string $coord1 - first point "lat,lng"
     * @param string $coord2 - second point "lat,lng"
     * @param array $options - Supported options:
     * - color: stroke color hex without # (default: 000000)
     * - width: stroke width in pixels (default: 1)
     * - hint: tooltip text shown on mouseover
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
     * Adds polyline (open multipoint line) to map
     *
     * @param array $points - array of "lat,lng" strings
     * @param string $popupContent - content of popup window
     * @param array $options - Supported options:
     * - color: stroke color hex without # (default: 000000)
     * - weight: stroke width in pixels (default: 2)
     * - opacity: stroke opacity 0..1 (default: 0.8)
     * - smoothFactor: line smoothing factor (default: 1)
     * - dashArray: SVG dash pattern, e.g. "5,5"
     * - hint: tooltip text shown on mouseover
     * - popupTitle: popup title shown above popup content
     *
     * @return object
     */
    public function addPolyline($points, $popupContent = '', $options = array()) {
        $color = isset($options['color']) ? $options['color'] : '#000000';
        $weight = isset($options['weight']) ? (int) $options['weight'] : 2;
        $opacity = isset($options['opacity']) ? $options['opacity'] : 0.8;
        $smoothFactor = isset($options['smoothFactor']) ? $options['smoothFactor'] : 1;
        $dashArray = isset($options['dashArray']) ? $options['dashArray'] : '';
        $hint = isset($options['hint']) ? $options['hint'] : '';
        $popupTitle = isset($options['popupTitle']) ? $options['popupTitle'] : '';
        $lineId = isset($options['lineId']) ? (int) $options['lineId'] : 0;
        $lineMeta = isset($options['meta']) ? $options['meta'] : array();

        $popupHtml = '';
        if (!empty($popupTitle)) {
            $popupHtml .= '<b>' . $popupTitle . '</b><br />';
        }
        if (!empty($popupContent)) {
            $popupHtml .= $popupContent;
        }

        $polylineId = wf_InputId();
        $latLngsJs = $this->buildLatLngArrayJs($points);
        $dashArrayJs = '';
        if (!empty($dashArray)) {
            $dashArrayJs = ', dashArray: ' . $this->quoteJs($dashArray);
        }
        $this->placemarks .= '
            var ubPolyline_' . $polylineId . ' = L.polyline(' . $latLngsJs . ', {
                color: "' . $color . '",
                weight: ' . $weight . ',
                opacity: ' . $opacity . ',
                smoothFactor: ' . $smoothFactor . $dashArrayJs . '
            }).addTo(map);
        ';
        if (!empty($popupHtml)) {
            $this->placemarks .= 'ubPolyline_' . $polylineId . '.bindPopup(' . $this->quoteJs($popupHtml) . ');';
        }
        if (!empty($hint)) {
            $this->placemarks .= 'ubPolyline_' . $polylineId . '.bindTooltip(' . $this->quoteJs($hint) . ', {sticky: true});';
        }
        if ($lineId > 0) {
            $this->placemarks .= 'ubPolyline_' . $polylineId . '._ubLineId = ' . $lineId . ';';
        }
        if (is_array($lineMeta) and !empty($lineMeta)) {
            $lineMetaJs = json_encode($lineMeta);
            if ($lineMetaJs === false) {
                $lineMetaJs = '{}';
            }
            $this->placemarks .= 'ubPolyline_' . $polylineId . '._ubLineMeta = ' . $lineMetaJs . ';';
        }
        return ($this);
    }

    /**
     * Adds polygon (closed multipoint shape) to map
     *
     * @param array $points - array of "lat,lng" strings
     * @param string $popupContent - content of popup window
     * @param array $options - Supported options:
     * - color: stroke color hex without # (default: 009d25)
     * - weight: stroke width in pixels (default: 2)
     * - opacity: stroke opacity 0..1 (default: 0.8)
     * - fillColor: fill color hex without # (default: 00a20b)
     * - fillOpacity: fill opacity 0..1 (default: 0.4)
     * - dashArray: SVG dash pattern, e.g. "5,5"
     * - hint: tooltip text shown on mouseover
     * - popupTitle: popup title shown above popup content
     *
     * @return object
     */
    public function addPolygon($points, $popupContent = '', $options = array()) {
        $color = isset($options['color']) ? $options['color'] : '009d25';
        $weight = isset($options['weight']) ? (int) $options['weight'] : 2;
        $opacity = isset($options['opacity']) ? $options['opacity'] : 0.8;
        $fillColor = isset($options['fillColor']) ? $options['fillColor'] : '00a20b';
        $fillOpacity = isset($options['fillOpacity']) ? $options['fillOpacity'] : 0.4;
        $dashArray = isset($options['dashArray']) ? $options['dashArray'] : '';
        $hint = isset($options['hint']) ? $options['hint'] : '';
        $popupTitle = isset($options['popupTitle']) ? $options['popupTitle'] : '';

        $popupHtml = '';
        if (!empty($popupTitle)) {
            $popupHtml .= '<b>' . $popupTitle . '</b><br />';
        }
        if (!empty($popupContent)) {
            $popupHtml .= $popupContent;
        }

        $polygonId = wf_InputId();
        $latLngsJs = $this->buildLatLngArrayJs($points);
        $dashArrayJs = '';
        if (!empty($dashArray)) {
            $dashArrayJs = ', dashArray: ' . $this->quoteJs($dashArray);
        }
        $this->placemarks .= '
            var ubPolygon_' . $polygonId . ' = L.polygon(' . $latLngsJs . ', {
                color: "#' . $color . '",
                weight: ' . $weight . ',
                opacity: ' . $opacity . ',
                fillColor: "#' . $fillColor . '",
                fillOpacity: ' . $fillOpacity . $dashArrayJs . '
            }).addTo(map);
        ';
        if (!empty($popupHtml)) {
            $this->placemarks .= 'ubPolygon_' . $polygonId . '.bindPopup(' . $this->quoteJs($popupHtml) . ');';
        }
        if (!empty($hint)) {
            $this->placemarks .= 'ubPolygon_' . $polygonId . '.bindTooltip(' . $this->quoteJs($hint) . ', {sticky: true});';
        }
        return ($this);
    }

    /**
     * Adds rectangle to map. Rectangle is defined by two opposite corners (south-west and north-east)
     *
     * @param string $cornerSW - first corner "lat,lng" (south-west)
     * @param string $cornerNE - second corner "lat,lng" (north-east)
     * @param string $popupContent - content of popup window
     * @param array $options - Supported options:
     * - color: stroke color hex without # (default: 009d25)
     * - weight: stroke width in pixels (default: 2)
     * - opacity: stroke opacity 0..1 (default: 0.8)
     * - fillColor: fill color hex without # (default: 00a20b)
     * - fillOpacity: fill opacity 0..1 (default: 0.4)
     * - dashArray: SVG dash pattern, e.g. "5,5"
     * - hint: tooltip text shown on mouseover
     * - popupTitle: popup title shown above popup content
     *
     * @return object
     */
    public function addRectangle($cornerSW, $cornerNE, $popupContent = '', $options = array()) {
        $color = isset($options['color']) ? $options['color'] : '009d25';
        $weight = isset($options['weight']) ? (int) $options['weight'] : 2;
        $opacity = isset($options['opacity']) ? $options['opacity'] : 0.8;
        $fillColor = isset($options['fillColor']) ? $options['fillColor'] : '00a20b';
        $fillOpacity = isset($options['fillOpacity']) ? $options['fillOpacity'] : 0.4;
        $dashArray = isset($options['dashArray']) ? $options['dashArray'] : '';
        $hint = isset($options['hint']) ? $options['hint'] : '';
        $popupTitle = isset($options['popupTitle']) ? $options['popupTitle'] : '';

        $popupHtml = '';
        if (!empty($popupTitle)) {
            $popupHtml .= '<b>' . $popupTitle . '</b><br />';
        }
        if (!empty($popupContent)) {
            $popupHtml .= $popupContent;
        }

        $rectId = wf_InputId();
        $dashArrayJs = '';
        if (!empty($dashArray)) {
            $dashArrayJs = ', dashArray: ' . $this->quoteJs($dashArray);
        }
        $this->placemarks .= '
            var ubRect_' . $rectId . ' = L.rectangle([[' . $cornerSW . '], [' . $cornerNE . ']], {
                color: "#' . $color . '",
                weight: ' . $weight . ',
                opacity: ' . $opacity . ',
                fillColor: "#' . $fillColor . '",
                fillOpacity: ' . $fillOpacity . $dashArrayJs . '
            }).addTo(map);
        ';
        if (!empty($popupHtml)) {
            $this->placemarks .= 'ubRect_' . $rectId . '.bindPopup(' . $this->quoteJs($popupHtml) . ');';
        }
        if (!empty($hint)) {
            $this->placemarks .= 'ubRect_' . $rectId . '.bindTooltip(' . $this->quoteJs($hint) . ', {sticky: true});';
        }
        return ($this);
    }

    /**
     * Adds circle marker (a circle with fixed pixel radius that does not scale with zoom)
     *
     * @param string $coords - "lat,lng" position
     * @param int $radius circle radius in pixels (default: 10)
     * @param string $popupContent - content of popup window
     * @param array $options - Supported options:
     * - color: stroke color hex without # (default: 009d25)
     * - weight: stroke width in pixels (default: 2)
     * - opacity: stroke opacity 0..1 (default: 0.8)
     * - fillColor: fill color hex without # (default: 00a20b)
     * - fillOpacity: fill opacity 0..1 (default: 0.5)
     * - hint: tooltip text shown on mouseover
     * - popupTitle: popup title shown above popup content
     *
     * @return object
     */
    public function addCircleMarker($coords, $radius = 10, $popupContent = '', $options = array()) {
        $color = isset($options['color']) ? $options['color'] : '009d25';
        $weight = isset($options['weight']) ? (int) $options['weight'] : 2;
        $opacity = isset($options['opacity']) ? $options['opacity'] : 0.8;
        $fillColor = isset($options['fillColor']) ? $options['fillColor'] : '00a20b';
        $fillOpacity = isset($options['fillOpacity']) ? $options['fillOpacity'] : 0.5;
        $hint = isset($options['hint']) ? $options['hint'] : '';
        $popupTitle = isset($options['popupTitle']) ? $options['popupTitle'] : '';

        $popupHtml = '';
        if (!empty($popupTitle)) {
            $popupHtml .= '<b>' . $popupTitle . '</b><br />';
        }
        if (!empty($popupContent)) {
            $popupHtml .= $popupContent;
        }

        $circleMarkerId = wf_InputId();
        $this->placemarks .= '
            var ubCircleMarker_' . $circleMarkerId . ' = L.circleMarker([' . $coords . '], {
                radius: ' . (int) $radius . ',
                color: "#' . $color . '",
                weight: ' . $weight . ',
                opacity: ' . $opacity . ',
                fillColor: "#' . $fillColor . '",
                fillOpacity: ' . $fillOpacity . '
            }).addTo(map);
        ';
        if (!empty($popupHtml)) {
            $this->placemarks .= 'ubCircleMarker_' . $circleMarkerId . '.bindPopup(' . $this->quoteJs($popupHtml) . ');';
        }
        if (!empty($hint)) {
            $this->placemarks .= 'ubCircleMarker_' . $circleMarkerId . '.bindTooltip(' . $this->quoteJs($hint) . ', {sticky: true});';
        }
        return ($this);
    }

    /**
     * Adds GeoJSON layer to map
     *
     * GeoJSON spec is supported as defined in RFC 7946 - Feature, FeatureCollection,
     * Point, MultiPoint, LineString, MultiLineString, Polygon, MultiPolygon,
     * GeometryCollection. Coordinates are in [lng, lat] order (Leaflet handles conversion).
     *
     * Supported options:
     * - style: array of leaflet path style options applied to LineString and Polygon features
     *          (color, weight, opacity, fillColor, fillOpacity, dashArray, ...)
     * - rawStyleJs: raw JS string used in place of style. May be a function literal
     *               like "function(feature) { return {color: feature.properties.color}; }"
     * - popupProperty: name of the feature property used as popup content
     * - popupTitleProperty: optional name of the feature property used as popup title (rendered bold)
     * - popupContent: static popup HTML bound to the entire layer (alternative to popupProperty)
     * - tooltipProperty: name of the feature property used as tooltip text
     * - hint: static tooltip text bound to the entire layer (alternative to tooltipProperty)
     * - pointType: 'marker' (default) or 'circleMarker' - how to render Point features
     * - icon: canonical icon key for marker rendering when pointType='marker' (default: 'marker.blue')
     * - iconProperty: name of feature property whose value is used as icon key per-feature.
     *                 Custom icons should be registered via registerIcon() prior to rendering.
     * - circleMarkerOptions: array of options for circleMarker rendering
     *                        (radius, color, weight, opacity, fillColor, fillOpacity)
     * - rawPointToLayerJs: raw JS function literal that overrides default point rendering
     * - rawOnEachFeatureJs: raw JS function literal that overrides default popup/tooltip binding
     *
     * @param array|string $geoJson GeoJSON data as PHP array or JSON-encoded string
     * @param array $options
     *
     * @return object
     */
    public function addGeoJSON($geoJson, $options = array()) {
        $layerId = wf_InputId();

        if (is_string($geoJson)) {
            $jsonString = trim($geoJson);
        } else {
            $jsonString = json_encode($geoJson);
            if ($jsonString === false) {
                $jsonString = '{}';
            }
        }
        if ($jsonString === '') {
            $jsonString = '{}';
        }

        $rawStyleJs = isset($options['rawStyleJs']) ? trim($options['rawStyleJs']) : '';
        if (!empty($rawStyleJs)) {
            $styleJs = $rawStyleJs;
        } else {
            $style = isset($options['style']) ? $options['style'] : array();
            if (!is_array($style)) {
                $style = array();
            }
            if (!isset($style['color'])) {
                $style['color'] = '#009d25';
            }
            if (!isset($style['weight'])) {
                $style['weight'] = 2;
            }
            if (!isset($style['opacity'])) {
                $style['opacity'] = 0.8;
            }
            if (!isset($style['fillColor'])) {
                $style['fillColor'] = '#00a20b';
            }
            if (!isset($style['fillOpacity'])) {
                $style['fillOpacity'] = 0.4;
            }
            $styleJs = json_encode($style);
            if ($styleJs === false) {
                $styleJs = '{}';
            }
        }

        $rawPointToLayerJs = isset($options['rawPointToLayerJs']) ? trim($options['rawPointToLayerJs']) : '';
        if (!empty($rawPointToLayerJs)) {
            $pointToLayerJs = $rawPointToLayerJs;
        } else {
            $pointType = isset($options['pointType']) ? $options['pointType'] : 'marker';
            if ($pointType === 'circleMarker') {
                $circleOptions = isset($options['circleMarkerOptions']) ? $options['circleMarkerOptions'] : array();
                if (!is_array($circleOptions)) {
                    $circleOptions = array();
                }
                if (!isset($circleOptions['radius'])) {
                    $circleOptions['radius'] = 8;
                }
                if (!isset($circleOptions['color'])) {
                    $circleOptions['color'] = '#009d25';
                }
                if (!isset($circleOptions['weight'])) {
                    $circleOptions['weight'] = 2;
                }
                if (!isset($circleOptions['opacity'])) {
                    $circleOptions['opacity'] = 0.8;
                }
                if (!isset($circleOptions['fillColor'])) {
                    $circleOptions['fillColor'] = '#00a20b';
                }
                if (!isset($circleOptions['fillOpacity'])) {
                    $circleOptions['fillOpacity'] = 0.5;
                }
                $circleOptionsJs = json_encode($circleOptions);
                if ($circleOptionsJs === false) {
                    $circleOptionsJs = '{}';
                }
                $pointToLayerJs = 'function(feature, latlng) { return L.circleMarker(latlng, ' . $circleOptionsJs . '); }';
            } else {
                $iconKeyOpt = isset($options['icon']) ? $options['icon'] : 'marker.blue';
                $iconKey = self::normalizeIconKey($iconKeyOpt);
                $iconPath = self::resolveIconPath($iconKeyOpt);
                $this->usedIcons[$iconKey] = $iconPath;
                $jsDefaultIconKey = $this->quoteJs($iconKey);
                $jsDefaultIconPath = $this->quoteJs($iconPath);

                $iconProperty = isset($options['iconProperty']) ? trim($options['iconProperty']) : '';
                if (!empty($iconProperty)) {
                    $iconsMap = array();
                    foreach (self::$icons as $regKey => $regPath) {
                        $iconsMap[$regKey] = $regPath;
                        $this->usedIcons[$regKey] = $regPath;
                    }
                    $iconsMapJs = json_encode($iconsMap);
                    if ($iconsMapJs === false) {
                        $iconsMapJs = '{}';
                    }
                    $jsIconProp = $this->quoteJs($iconProperty);
                    $pointToLayerJs = 'function(feature, latlng) {
                        var ubGeoIcons = ' . $iconsMapJs . ';
                        var ubGeoKey = ' . $jsDefaultIconKey . ';
                        if (feature && feature.properties && feature.properties[' . $jsIconProp . ']) {
                            var ubGeoCustom = String(feature.properties[' . $jsIconProp . ']);
                            if (ubGeoIcons[ubGeoCustom]) { ubGeoKey = ubGeoCustom; }
                        }
                        var ubGeoPath = ubGeoIcons[ubGeoKey] || ' . $jsDefaultIconPath . ';
                        return L.marker(latlng, {icon: ubMapGetCachedIcon(ubGeoKey, ubGeoPath)});
                    }';
                } else {
                    $pointToLayerJs = 'function(feature, latlng) { return L.marker(latlng, {icon: ubMapGetCachedIcon(' . $jsDefaultIconKey . ', ' . $jsDefaultIconPath . ')}); }';
                }
            }
        }

        $rawOnEachFeatureJs = isset($options['rawOnEachFeatureJs']) ? trim($options['rawOnEachFeatureJs']) : '';
        $onEachFeatureJs = '';
        if (!empty($rawOnEachFeatureJs)) {
            $onEachFeatureJs = $rawOnEachFeatureJs;
        } else {
            $popupProperty = isset($options['popupProperty']) ? trim($options['popupProperty']) : '';
            $popupTitleProperty = isset($options['popupTitleProperty']) ? trim($options['popupTitleProperty']) : '';
            $tooltipProperty = isset($options['tooltipProperty']) ? trim($options['tooltipProperty']) : '';

            if (!empty($popupProperty) or !empty($popupTitleProperty) or !empty($tooltipProperty)) {
                $popupBody = '';
                if (!empty($popupTitleProperty)) {
                    $popupBody .= '
                        var ubGeoTitle = feature.properties[' . $this->quoteJs($popupTitleProperty) . '];
                        if (ubGeoTitle) { ubGeoPopupHtml += "<b>" + String(ubGeoTitle) + "</b><br />"; }';
                }
                if (!empty($popupProperty)) {
                    $popupBody .= '
                        var ubGeoBody = feature.properties[' . $this->quoteJs($popupProperty) . '];
                        if (ubGeoBody) { ubGeoPopupHtml += String(ubGeoBody); }';
                }
                $popupBind = '';
                if (!empty($popupBody)) {
                    $popupBind = '
                        var ubGeoPopupHtml = "";' . $popupBody . '
                        if (ubGeoPopupHtml) {
                            layer.bindPopup(ubGeoPopupHtml, {maxWidth: 320, minWidth: 50, maxHeight: 600, closeButton: true, closeOnEscapeKey: true});
                        }';
                }
                $tooltipBind = '';
                if (!empty($tooltipProperty)) {
                    $tooltipBind = '
                        var ubGeoTip = feature.properties[' . $this->quoteJs($tooltipProperty) . '];
                        if (ubGeoTip) { layer.bindTooltip(String(ubGeoTip), {sticky: true}); }';
                }
                $onEachFeatureJs = 'function(feature, layer) {
                    if (feature && feature.properties) {' . $popupBind . $tooltipBind . '
                    }
                }';
            }
        }

        $optsJsParts = array();
        $optsJsParts[] = 'style: ' . $styleJs;
        $optsJsParts[] = 'pointToLayer: ' . $pointToLayerJs;
        if (!empty($onEachFeatureJs)) {
            $optsJsParts[] = 'onEachFeature: ' . $onEachFeatureJs;
        }

        $this->placemarks .= '
            var ubGeoJson_' . $layerId . ' = L.geoJSON(' . $jsonString . ', {
                ' . implode(",\n                ", $optsJsParts) . '
            }).addTo(map);
        ';

        $popupContent = isset($options['popupContent']) ? $options['popupContent'] : '';
        $hint = isset($options['hint']) ? $options['hint'] : '';
        if (!empty($popupContent)) {
            $this->placemarks .= 'ubGeoJson_' . $layerId . '.bindPopup(' . $this->quoteJs($popupContent) . ', {maxWidth: 320, minWidth: 50, maxHeight: 600, closeButton: true, closeOnEscapeKey: true});';
        }
        if (!empty($hint)) {
            $this->placemarks .= 'ubGeoJson_' . $layerId . '.bindTooltip(' . $this->quoteJs($hint) . ', {sticky: true});';
        }
        return ($this);
    }

    /**
     * Renders full map HTML and JS
     *
     * @return string
     */
    public function render() {
        $lang = curlang();
        $localeAppend='';
        $internalLang = '';
        if (function_exists('curlang')) {
            $internalLang = $lang;
            $localeAppend='&hl='.$internalLang;
        }
        
        $tileLayerOSM = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        $tileLayerSatellite = 'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}';
        $tileLayerHybrid = 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}'.$localeAppend;
        $tileLayerTerrain = 'https://mt1.google.com/vt/lyrs=p&x={x}&y={y}&z={z}'.$localeAppend;
        $tileLayerCustoms = '';
        $searchCode = '';
        $rememberZoomJs = ($this->rememberZoom) ? 'true' : 'false';
        $rememberPositionJs = ($this->rememberPosition) ? 'true' : 'false';
        $rememberLayerJs = ($this->rememberLayer) ? 'true' : 'false';

        if (!empty($this->tileLayer)) {
            $tileLayerOSM = $this->tileLayer;
            $tileLayerCustoms = $this->tileLayerCustoms;
        }

        if (!empty($this->searchPrefill)) {
            $searchCode = '
                var searchInput = document.querySelector(".leaflet-control-geocoder-form input");
                if (searchInput) {
                    searchInput.value = ' . $this->quoteJs($this->searchPrefill) . ';
                }
            ';
        }

        $canvasRender = ($this->canvasRender) ? 'true' : 'false';

        $mapCenter = '';
        if (empty($this->center)) {
            $mapCenter = '
                map.locate({setView: true, maxZoom: ubMapInitialZoom});
                function onLocationError(e) {
                    alert(e.message);
                }
                map.on("locationerror", onLocationError);
            ';
        } else {
            $mapCenter = 'map.setView([' . $this->center . '], ubMapInitialZoom);';
        }

        $result = '';
        $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet/leaflet.css"');
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet/leaflet.js"') . wf_tag('script', true);
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-editable/src/Leaflet.Editable.js"') . wf_tag('script', true);
        $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-geocoder/Control.Geocoder.css"');
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-geocoder/Control.Geocoder.min.js"') . wf_tag('script', true);
        $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-ruler/src/leaflet-ruler.css"');
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-ruler/src/leaflet-ruler.js"') . wf_tag('script', true);
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-easyprint/dist/bundle.js"') . wf_tag('script', true);
        $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-fullscreen/dist/leaflet.fullscreen.css"');
        $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-fullscreen/dist/Leaflet.fullscreen.min.js"') . wf_tag('script', true);
        if ($this->clusteringEnabled and !$this->forceCanvasMarkers) {
            $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-markercluster/dist/MarkerCluster.css"');
            $result .= wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/leaflet-markercluster/dist/MarkerCluster.Default.css"');
            $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-markercluster/dist/leaflet.markercluster-src.js"') . wf_tag('script', true);
        }
        if ($this->forceCanvasMarkers) {
            $result .= wf_tag('script', false, '', 'src="modules/jsc/rbush/rbush.min.js"') . wf_tag('script', true);
            $result .= wf_tag('script', false, '', 'src="modules/jsc/leaflet-markers-canvas/dist/leaflet-markers-canvas.min.js"') . wf_tag('script', true);
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
        $forceCanvasMarkersJs = ($this->forceCanvasMarkers) ? 'true' : 'false';
        $fpsMeterEnabledJs = ($this->fpsMeterEnabled) ? 'true' : 'false';
        $fpsMeterIntervalJs = (int) $this->fpsMeterInterval;
        $fpsMeterPositionJs = $this->quoteJs($this->fpsMeterPosition);
        if (!empty($this->extraScriptSrcs)) {
            foreach ($this->extraScriptSrcs as $eachScriptSrc) {
                $result .= wf_tag('script', false, '', 'src="' . $eachScriptSrc . '"') . wf_tag('script', true);
            }
        }
        $result .= wf_tag('script', false, '', 'type = "text/javascript"');
        $result .= '
            var map = L.map("' . $this->container . '", {maxZoom: 18});
            var ubMapZoomStorageKey = "ubMapCore_zoom_' . $this->container . '";
            var ubMapRememberZoom = ' . $rememberZoomJs . ';
            var ubMapRequestedZoom = ' . (int) $this->zoom . ';
            var ubMapInitialZoom = ubMapRequestedZoom;
            var ubMapPositionStorageKey = "ubMapCore_position_' . $this->container . '";
            var ubMapRememberPosition = ' . $rememberPositionJs . ';
            var ubMapRequestedCenter = ' . $this->quoteJs($this->center) . ';
            var ubMapInitialCenter = ubMapRequestedCenter;
            if (ubMapRememberZoom) {
                try {
                    var ubMapSavedZoom = window.localStorage ? localStorage.getItem(ubMapZoomStorageKey) : null;
                    if (ubMapSavedZoom !== null) {
                        var ubMapSavedZoomInt = parseInt(ubMapSavedZoom, 10);
                        if (!isNaN(ubMapSavedZoomInt)) {
                            ubMapInitialZoom = ubMapSavedZoomInt;
                        }
                    }
                } catch (err) {
                }
            }
            if (ubMapRememberPosition) {
                try {
                    var ubMapSavedCenter = window.localStorage ? localStorage.getItem(ubMapPositionStorageKey) : null;
                    if (ubMapSavedCenter !== null) {
                        var ubMapCenterParts = String(ubMapSavedCenter).split(",");
                        if (ubMapCenterParts.length === 2) {
                            var ubMapCenterLat = parseFloat(ubMapCenterParts[0]);
                            var ubMapCenterLng = parseFloat(ubMapCenterParts[1]);
                            if (!isNaN(ubMapCenterLat) && !isNaN(ubMapCenterLng)) {
                                ubMapInitialCenter = ubMapCenterLat + "," + ubMapCenterLng;
                            }
                        }
                    }
                } catch (err) {
                }
            }
            if (ubMapRememberPosition && ubMapInitialCenter) {
                map.setView(ubMapInitialCenter.split(","), ubMapInitialZoom);
            } else {
            ' . $mapCenter . '
            }
            if (!L.Control.Fps) {
                L.Control.Fps = L.Control.extend({
                    options: {
                        position: ' . $fpsMeterPositionJs . ',
                        interval: ' . $fpsMeterIntervalJs . '
                    },
                    onAdd: function(map) {
                        this._map = map;
                        this._container = L.DomUtil.create("div", "leaflet-control-fps");
                        L.DomEvent.disableClickPropagation(this._container);
                        this._container.style.padding = "5px";
                        this._container.style.background = "rgba(255, 255, 255, 0.5)";
                        this._container.style.color = "black";
                        this._container.style.fontFamily = "monospace";
                        this._container.style.fontSize = "11px";
                        this._container.style.borderRadius = "3px";
                        this._container.innerHTML = "FPS: ...";
                        this._running = true;
                        this._lastCalledTime = performance.now();
                        this._frameCount = 0;
                        this._animateBound = this._animate.bind(this);
                        requestAnimationFrame(this._animateBound);
                        return this._container;
                    },
                    onRemove: function() {
                        this._running = false;
                    },
                    _animate: function() {
                        if (!this._running) {
                            return;
                        }
                        this._frameCount++;
                        var now = performance.now();
                        var diff = now - this._lastCalledTime;
                        if (diff >= this.options.interval) {
                            var fps = (this._frameCount * 1000) / diff;
                            this._container.innerHTML = "FPS: " + Math.round(fps);
                            this._lastCalledTime = now;
                            this._frameCount = 0;
                        }
                        requestAnimationFrame(this._animateBound);
                    }
                });
            }
            if (!L.control.fps) {
                L.control.fps = function(options) {
                    return new L.Control.Fps(options);
                };
            }
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
            var ubForceCanvasMarkers = ' . $forceCanvasMarkersJs . ';
            var ubClusterEnabled = ' . $clusterEnabledJs . ';
            if (ubForceCanvasMarkers && ubClusterEnabled) {
                if (window.console && typeof console.warn === "function") {
                    console.warn("MapCore: canvas markers and clustering are both enabled; clustering is ignored because canvas markers mode is active.");
                }
            }
            var ubMarkerLayer = map;
            if (ubForceCanvasMarkers) {
                if (typeof L.MarkersCanvas === "function" && typeof RBush === "function") {
                    // Render canvas markers in markerPane to keep them above vector overlays.
                    ubMarkerLayer = new L.MarkersCanvas({pane: "markerPane"});
                    ubMarkerLayer.addTo(map);
                    if (ubMarkerLayer._canvas && ubMarkerLayer._canvas.style) {
                        // Keep circles/polygons interactive under markerPane canvas layer.
                        ubMarkerLayer._canvas.style.pointerEvents = "none";
                    }
                } else {
                    ubMarkerLayer = map;
                    if (window.console && typeof console.warn === "function") {
                        console.warn("MapCore: leaflet-markers-canvas or RBush is unavailable, canvas markers disabled.");
                    }
                }
            } else {
                if (ubClusterEnabled) {
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
            }
            function ubMapAttachMarker(ubMarker) {
                if (ubForceCanvasMarkers && ubMarkerLayer && typeof ubMarkerLayer.addMarker === "function") {
                    ubMarkerLayer.addMarker(ubMarker);
                } else {
                    ubMarker.addTo(ubMarkerLayer);
                }
                return ubMarker;
            }

            var roadmap = L.tileLayer("' . $tileLayerOSM . '", {
                maxZoom: 18,
                attribution: \'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors\ ' . $this->additionalAttributions . '\',
                ' . $tileLayerCustoms . '
            });
            var satellite = L.tileLayer("' . $tileLayerSatellite . '", {maxZoom: 18, attribution: "© Google"});
            var hybrid = L.tileLayer("' . $tileLayerHybrid . '", {maxZoom: 18, attribution: "© Google"});
            var terrain = L.tileLayer("' . $tileLayerTerrain . '", {maxZoom: 18, attribution: "© Google"});

            var ubMapRememberLayer = ' . $rememberLayerJs . ';
            var ubMapLayerStorageKey = "ubMapCore_layer_' . $this->container . '";
            var savedLayerType = null;
            if (ubMapRememberLayer) {
                try {
                    savedLayerType = window.localStorage ? localStorage.getItem(ubMapLayerStorageKey) : null;
                } catch (err) {
                    savedLayerType = null;
                }
            }
            var ubMapBaseLayers = {roadmap: roadmap, hybrid: hybrid, satellite: satellite, terrain: terrain};
            var requestedLayerType = "' . $this->type . '";
            var layerToUse = ubMapBaseLayers[savedLayerType] || ubMapBaseLayers[requestedLayerType] || roadmap;
            layerToUse.addTo(map);
            if (' . $fpsMeterEnabledJs . ') {
                var ubMapCenter = map.getCenter();
                var ubMapOptionsSnapshot = {
                    canvasRender: ' . $canvasRender . ',
                    forceCanvasMarkers: ubForceCanvasMarkers,
                    clustering: ubClusterEnabled,
                    rememberZoom: ubMapRememberZoom,
                    rememberPosition: ubMapRememberPosition,
                    rememberLayer: ubMapRememberLayer,
                    requestedLayerType: requestedLayerType,
                    savedLayerType: savedLayerType,
                    activeLayerType: savedLayerType || requestedLayerType || "roadmap",
                    zoom: map.getZoom(),
                    center: ubMapCenter
                };
                window["ubMapOptions_' . $this->container . '"] = ubMapOptionsSnapshot;
                if (window.console && typeof console.info === "function") {
                    console.info(
                        "[MapCore #' . $this->container . '] layer=" + ubMapOptionsSnapshot.activeLayerType +
                        ", zoom=" + ubMapOptionsSnapshot.zoom +
                        ", center=" + ubMapCenter.lat.toFixed(6) + "," + ubMapCenter.lng.toFixed(6) +
                        ", canvasRender=" + ubMapOptionsSnapshot.canvasRender +
                        ", forceCanvasMarkers=" + ubMapOptionsSnapshot.forceCanvasMarkers +
                        ", clustering=" + ubMapOptionsSnapshot.clustering +
                        ", rememberZoom=" + ubMapOptionsSnapshot.rememberZoom +
                        ", rememberPosition=" + ubMapOptionsSnapshot.rememberPosition +
                        ", rememberLayer=" + ubMapOptionsSnapshot.rememberLayer
                    );
                }
            }

            var baseMaps = {
                "' . __('Map') . '": roadmap,
                "' . __('Hybrid') . '": hybrid,
                "' . __('Satellite') . '": satellite,
                "' . __('Terrain') . '": terrain
            };
            var geoControl = new L.Control.Geocoder({showResultIcons: true, errorMessage: "' . __('Nothing found') . '", placeholder: "' . __('Search') . '"});
            geoControl.addTo(map);

            L.easyPrint({
                title: "' . __('Export') . '",
                defaultSizeTitles: {Current: "' . __('Current') . '", A4Landscape: "A4 Landscape", A4Portrait: "A4 Portrait"},
                position: "topright",
                filename: "map_' . date("Y-m-d_H:i:s") . '",
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
            if (' . $fpsMeterEnabledJs . ') {
                L.control.fps({position: ' . $fpsMeterPositionJs . ', interval: ' . $fpsMeterIntervalJs . '}).addTo(map);
            }
            map.addControl(new L.Control.Fullscreen({
                title: {
                    "false": "' . __('Fullscreen') . '",
                    "true": "' . __('Exit fullscreen') . '"
                }
            }));
            map.addControl(L.control.layers(baseMaps, null, {collapsed: true}));
            map.on("baselayerchange", function(e) {
                if (ubMapRememberLayer) {
                    var v = "roadmap";
                    for (var ubMapLayerName in ubMapBaseLayers) {
                        if (e && e.layer === ubMapBaseLayers[ubMapLayerName]) {
                            v = ubMapLayerName;
                            break;
                        }
                    }
                    try {
                        localStorage.setItem(ubMapLayerStorageKey, v);
                    } catch (err) {
                    }
                }
            });
            map.on("zoomend", function() {
                if (ubMapRememberZoom) {
                    try {
                        localStorage.setItem(ubMapZoomStorageKey, String(map.getZoom()));
                    } catch (err) {
                    }
                }
            });
            map.on("moveend", function() {
                if (ubMapRememberPosition) {
                    try {
                        var ubMapCenterCurrent = map.getCenter();
                        localStorage.setItem(ubMapPositionStorageKey, String(ubMapCenterCurrent.lat) + "," + String(ubMapCenterCurrent.lng));
                    } catch (err) {
                    }
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
     * Builds JS LatLng array literal from array of "lat,lng" strings
     *
     * Each accepted item may be "lat,lng" string or [lat, lng] array.
     * Returns string in form "[[lat,lng],[lat,lng],...]" suitable for direct
     * usage as L.polygon / L.polyline first argument.
     *
     * @param array $points
     *
     * @return string
     */
    protected function buildLatLngArrayJs($points) {
        $parts = array();
        if (is_array($points)) {
            foreach ($points as $point) {
                if (is_array($point)) {
                    if (count($point) >= 2) {
                        $lat = trim((string) $point[0]);
                        $lng = trim((string) $point[1]);
                        if ($lat !== '' and $lng !== '') {
                            $parts[] = '[' . $lat . ',' . $lng . ']';
                        }
                    }
                } else {
                    $point = trim((string) $point);
                    if (!empty($point)) {
                        $parts[] = '[' . $point . ']';
                    }
                }
            }
        }
        $result = '[' . implode(',', $parts) . ']';
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
            var ubMarker_' . $markerId . ' = L.marker([' . $coords . '], {icon: ubIcon_' . $markerId . '});
            ubMapAttachMarker(ubMarker_' . $markerId . ');
        ';
        if (!empty($popupHtml)) {
            $result .= 'ubMarker_' . $markerId . '.bindPopup(' . $jsPopup . ', {maxWidth: 320, minWidth: 50, maxHeight: 600, closeButton: true, closeOnEscapeKey: true});';
        }
        if (!empty($tooltip)) {
            $result .= 'ubMarker_' . $markerId . '.bindTooltip(' . $jsTooltip . ', {sticky: true});';
        }
        return ($result);
    }

    /**
     * Returns list of canonical icon keys currently registered
     *
     * @return array
     */
    public static function getIconKeys() {
        $result = array_keys(self::$icons);
        return ($result);
    }

    /**
     * Resolves icon key to icon image path
     *
     * @param string $iconKey - canonical icon key
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
     * Normalizes icon key
     *
     * @param string $iconKey
     *
     * @return string
     */
    public static function normalizeIconKey($iconKey) {
        $result = trim($iconKey);
        if (empty($result)) {
            $result = 'marker.blue';
        }
        return ($result);
    }

    /**
     * Registers or overrides icon path by key
     *
     * @param string $iconKey - canonical icon key
     * @param string $iconPath - path to icon image
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
 

}

