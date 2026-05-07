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
     * Enables or disables saving/restoring map zoom via localStorage
     *
     * @param bool $rememberZoom
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
     * @param bool $rememberPosition
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
     * @param string $placemarks
     * @param bool $replace
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
     * Adds location editor with coordinates picker and custom HTML form
     *
     * @param string $fieldName
     * @param string $title
     * @param string $formHtml
     * @param int $precision
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
     * Supported options:
     * - icon: canonical icon key, also you can use custom icon by registering it with registerIcon method
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
     * Adds polyline (open multipoint line) to map
     *
     * Supported options:
     * - color: stroke color (default: #000000)
     * - weight: stroke width in pixels (default: 2)
     * - opacity: stroke opacity 0..1 (default: 0.8)
     * - smoothFactor: line smoothing factor (default: 1)
     * - dashArray: SVG dash pattern, e.g. "5,5"
     * - hint: tooltip text shown on mouseover
     * - popupTitle: popup title shown above popup content
     *
     * @param array $points array of "lat,lng" strings
     * @param string $popupContent
     * @param array $options
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
        return ($this);
    }

    /**
     * Adds polygon (closed multipoint shape) to map
     *
     * Supported options:
     * - color: stroke color hex without # (default: 009d25)
     * - weight: stroke width in pixels (default: 2)
     * - opacity: stroke opacity 0..1 (default: 0.8)
     * - fillColor: fill color hex without # (default: 00a20b)
     * - fillOpacity: fill opacity 0..1 (default: 0.4)
     * - dashArray: SVG dash pattern, e.g. "5,5"
     * - hint: tooltip text shown on mouseover
     * - popupTitle: popup title shown above popup content
     *
     * @param array $points array of "lat,lng" strings
     * @param string $popupContent
     * @param array $options
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
     * Supported options:
     * - color: stroke color hex without # (default: 009d25)
     * - weight: stroke width in pixels (default: 2)
     * - opacity: stroke opacity 0..1 (default: 0.8)
     * - fillColor: fill color hex without # (default: 00a20b)
     * - fillOpacity: fill opacity 0..1 (default: 0.4)
     * - dashArray: SVG dash pattern, e.g. "5,5"
     * - hint: tooltip text shown on mouseover
     * - popupTitle: popup title shown above popup content
     *
     * @param string $cornerSW first corner "lat,lng" (south-west)
     * @param string $cornerNE second corner "lat,lng" (north-east)
     * @param string $popupContent
     * @param array $options
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
     * Supported options:
     * - color: stroke color hex without # (default: 009d25)
     * - weight: stroke width in pixels (default: 2)
     * - opacity: stroke opacity 0..1 (default: 0.8)
     * - fillColor: fill color hex without # (default: 00a20b)
     * - fillOpacity: fill opacity 0..1 (default: 0.5)
     * - hint: tooltip text shown on mouseover
     * - popupTitle: popup title shown above popup content
     *
     * @param string $coords "lat,lng" position
     * @param int $radius circle radius in pixels (default: 10)
     * @param string $popupContent
     * @param array $options
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

        if (isset($this->mapsCfg['LEAFLET_TILE_LAYER'])) {
            if ($this->mapsCfg['LEAFLET_TILE_LAYER']) {
                $tileLayerOSM = $this->mapsCfg['LEAFLET_TILE_LAYER'];
                if (ispos($tileLayerOSM, 'visicom')) {
                    $tileLayerCustoms = "subdomains: '123', tms: true";
                    $this->additionalAttributions = '| <a href="https://www.visicom.ua">Visicom</a>';
                } else {
                    if (ispos($tileLayerOSM, 'google.com')) {
                        $tileLayerCustoms = "subdomains:['mt0','mt1','mt2','mt3']";
                        $this->additionalAttributions = '| <a href="https://www.google.com">Google</a>';
                    }
                }

                if (ispos($tileLayerOSM, 'kaminari')) {
                    $this->additionalAttributions = '| ⚡ <a href="https://github.com/nightflyza/kaminaritile">KaminariTile</a>';
                }

                if (ispos($tileLayerOSM, 'mapbox')) {
                    $this->additionalAttributions = '| <a href="https://www.mapbox.com">Mapbox</a>';
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
        $result .= wf_tag('script', false, '', 'type = "text/javascript"');
        $result .= '
            var map = L.map("' . $this->container . '", {maxZoom: 18});
            var ubMapZoomStorageKey = "ubilling_lmaps_zoom_' . $this->container . '";
            var ubMapRememberZoom = ' . $rememberZoomJs . ';
            var ubMapRequestedZoom = ' . (int) $this->zoom . ';
            var ubMapInitialZoom = ubMapRequestedZoom;
            var ubMapPositionStorageKey = "ubilling_lmaps_position_' . $this->container . '";
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
            var ubMarkerLayer = map;
            if (ubForceCanvasMarkers) {
                if (typeof L.MarkersCanvas === "function" && typeof RBush === "function") {
                    ubMarkerLayer = new L.MarkersCanvas();
                    ubMarkerLayer.addTo(map);
                } else {
                    ubMarkerLayer = map;
                    if (window.console && typeof console.warn === "function") {
                        console.warn("MapCore: leaflet-markers-canvas or RBush is unavailable, canvas markers disabled.");
                    }
                }
            } else {
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
                    if (savedLayerType === "terrain") {
                        layerToUse = terrain;
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
                                    if (requestedLayerType === "terrain") {
                                        layerToUse = terrain;
                                    } else {
                                        layerToUse = roadmap;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            layerToUse.addTo(map);

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
                    } else {
                        if (e && e.layer === terrain) {
                            v = "terrain";
                        }
                    }
                }
                try {
                    localStorage.setItem(storageKey, v);
                } catch (err) {
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
            ubMarker_' . $markerId . '.bindPopup(' . $jsPopup . ', {maxWidth: 320, minWidth: 50, maxHeight: 600, closeButton: true, closeOnEscapeKey: true});
        ';
        if (!empty($tooltip)) {
            $result .= 'ubMarker_' . $markerId . '.bindTooltip(' . $jsTooltip . ', {sticky: true});';
        }
        return ($result);
    }

    /**
     * Resolves icon key to icon image path
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
 

}

