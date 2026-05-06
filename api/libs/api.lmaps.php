<?php

if (!class_exists('MapCore')) {
    include_once 'api.mapcore.php';
}

if (!class_exists('LegacyLmap')) {
    class LegacyLmap {
        protected $mapCore = null;

        public function __construct() {
            $this->mapCore = new MapCore('ubmap');
        }

        public function container($width, $height, $id) {
            $result = '';
            $mapCore = new MapCore($id);
            $result = $mapCore->renderContainer($width, $height);
            return ($result);
        }

        public function iconUrl($icon) {
            $result = MapCore::resolveIconPath($icon);
            return ($result);
        }

        public function addMark($coords, $title, $content, $footer, $icon) {
            $markerId = wf_InputId();
            $iconPath = MapCore::resolveIconPath($icon);
            $iconKey = MapCore::normalizeIconKey($icon);
            $popupHtml = '';
            $tooltip = '';
            $result = '';
            if (!empty($title)) {
                $popupHtml .= '<b>' . $title . '</b><br />';
            }
            if (!empty($content)) {
                $popupHtml .= $content;
                $tooltip = $content;
            }
            if (!empty($footer)) {
                $popupHtml .= '<br>' . $footer;
            }
            $result .= '
                var ubIcon_' . $markerId . ' = ubMapGetCachedIcon(' . $this->quoteJs($iconKey) . ', ' . $this->quoteJs($iconPath) . ');
                var ubMarker_' . $markerId . ' = L.marker([' . $coords . '], {icon: ubIcon_' . $markerId . '}).addTo(ubMarkerLayer);
                ubMarker_' . $markerId . '.bindPopup(' . $this->quoteJs($popupHtml) . ', {maxWidth: 320, minWidth: 50, maxHeight: 600, closeButton: true, closeOnEscapeKey: true});
            ';
            if (!empty($tooltip)) {
                $result .= 'ubMarker_' . $markerId . '.bindTooltip(' . $this->quoteJs($tooltip) . ', {sticky: true});';
            }
            return ($result);
        }

        public function addMarkDynamic($coords, $title, $contentUrl, $icon) {
            $markerId = wf_InputId();
            $iconPath = MapCore::resolveIconPath($icon);
            $iconKey = MapCore::normalizeIconKey($icon);
            $tooltip = $title;
            $result = '';
            $result .= '
                var ubIconDyn_' . $markerId . ' = ubMapGetCachedIcon(' . $this->quoteJs($iconKey) . ', ' . $this->quoteJs($iconPath) . ');
                var ubMarkerDyn_' . $markerId . ' = L.marker([' . $coords . '], {icon: ubIconDyn_' . $markerId . '}).addTo(ubMarkerLayer);
                ubMarkerDyn_' . $markerId . '.bindPopup(' . $this->quoteJs(__('Loading') . '...') . ', {maxWidth: 320, minWidth: 50, maxHeight: 600, closeButton: true, closeOnEscapeKey: true});
                ubMarkerDyn_' . $markerId . '._popupHtml = null;

                ubMarkerDyn_' . $markerId . '.on("click", function (e) {
                    var marker = e.target;

                    if (marker._popupHtml !== null) {
                        marker.setPopupContent(marker._popupHtml);
                        marker.openPopup();
                    } else {
                        marker.setPopupContent(' . $this->quoteJs(__('Loading') . '...') . ');
                        marker.openPopup();
                        fetch(' . $this->quoteJs($contentUrl) . ')
                            .then(function(response) { return response.text(); })
                            .then(function(html) {
                                marker._popupHtml = html;
                                marker.setPopupContent(html);
                                marker.openPopup();
                            })
                            .catch(function() {
                                marker.setPopupContent(' . $this->quoteJs(__('Error') . ' ' . __('Loading')) . ');
                                marker.openPopup();
                            });
                    }
                });
            ';
            if (!empty($tooltip)) {
                $result .= 'ubMarkerDyn_' . $markerId . '.bindTooltip(' . $this->quoteJs($tooltip) . ', {sticky: true});';
            }
            return ($result);
        }

        public function addCircle($coords, $radius, $content, $hint, $color, $opacity, $fillColor, $fillOpacity) {
            $circleId = wf_InputId();
            $result = '
                var ubCircle_' . $circleId . ' = L.circle([' . $coords . '], {
                    color: "#' . $color . '",
                    opacity: ' . $opacity . ',
                    fillColor: "#' . $fillColor . '",
                    fillOpacity: ' . $fillOpacity . ',
                    radius: ' . (int) $radius . '
                }).addTo(map);
            ';
            if (!empty($content)) {
                $result .= 'ubCircle_' . $circleId . '.bindPopup(' . $this->quoteJs($content) . ');';
            }
            if (!empty($hint)) {
                $result .= 'ubCircle_' . $circleId . '.bindTooltip(' . $this->quoteJs($hint) . ', {sticky: true});';
            }
            return ($result);
        }

        public function initMap($center, $zoom, $type, $placemarks, $editor, $container, $searchPrefill) {
            $type = ($type == 'map') ? 'roadmap' : $type;
            $mapCore = new MapCore($container);
            $mapCore->setZoom($zoom);
            $mapCore->setType($type);
            $mapCore->setSearchPrefill($searchPrefill);
            if (!empty($center)) {
                $mapCore->setCenter($center);
            }
            if (!empty($placemarks)) {
                $mapCore->addRawJs($placemarks);
            }
            if (!empty($editor)) {
                $mapCore->addRawJs($editor);
            }
            $result = $mapCore->render();
            return ($result);
        }

        public function editor($name, $title, $data, $precision) {
            $editorId = wf_InputId();
            $name = trim($name);
            $precision = (int) $precision;
            $result = '';
            if (empty($precision)) {
                $precision = 8;
            }
            $safeTitle = str_replace("\n", '', $title);
            $safeTitle = str_replace("\r", '', $safeTitle);
            $safeFormHtml = str_replace("'", '`', $data);
            $safeFormHtml = str_replace("\n", '', $safeFormHtml);
            $safeFormHtml = str_replace("\r", '', $safeFormHtml);
            $popupPrefix = '<b>' . $safeTitle . '</b><br>';
            $popupPrefix .= '<form action="" method="POST">';
            $popupPrefix .= '<input type="hidden" name="' . $name . '" value=\'"+e.latlng.lat.toPrecision(' . $precision . ')+\',\'+e.latlng.lng.toPrecision(' . $precision . ')+"\'>' . $safeFormHtml;
            $popupPrefix .= '</form><br>';
            $result .= '
                var ubEditorPopup_' . $editorId . ' = L.popup();
                function ubEditorOnMapClick_' . $editorId . '(e) {
                    ubEditorPopup_' . $editorId . '
                        .setLatLng(e.latlng)
                        .setContent(' . $this->quoteJs($popupPrefix) . ' + e.latlng.lat.toPrecision(' . $precision . ') + "," + e.latlng.lng.toPrecision(' . $precision . '))
                        .openOn(map);
                }
                map.on("click", ubEditorOnMapClick_' . $editorId . ');
            ';
            return ($result);
        }

        public function addLine($coord1, $coord2, $color, $hint, $width) {
            $lineId = wf_InputId();
            $result = '
                var ubPointA_' . $lineId . ' = new L.LatLng(' . $coord1 . ');
                var ubPointB_' . $lineId . ' = new L.LatLng(' . $coord2 . ');
                var ubLine_' . $lineId . ' = new L.Polyline([ubPointA_' . $lineId . ', ubPointB_' . $lineId . '], {
                    color: "' . $color . '",
                    weight: ' . (int) $width . ',
                    opacity: 0.8,
                    smoothFactor: 1
                });
                ubLine_' . $lineId . '.addTo(map);
            ';
            if (!empty($hint)) {
                $result .= 'ubLine_' . $lineId . '.bindTooltip(' . $this->quoteJs($hint) . ', {sticky: true});';
            }
            return ($result);
        }

        protected function quoteJs($value) {
            $result = json_encode((string) $value);
            if ($result === false) {
                $result = '""';
            }
            return ($result);
        }
    }
}

function lmaps_LegacyCore() {
    global $legacyLmap;
    $result = null;
    if (!is_object($legacyLmap)) {
        $legacyLmap = new LegacyLmap();
    }
    $result = $legacyLmap;
    return ($result);
}

/*
 * Legacy Leaflet maps API implementation
 * 
 * @deprecated since 2026-05-06
 * Use MapCore API instead
 */

/**
 * Returns leaflet maps empty container
 * 
 * @param string $width
 * @param string $height
 * @param string $id
 * 
 * @deprecated Use MapCore::renderContainer()
 *
 * @return string
 */
function generic_MapContainer($width = '', $height = '', $id = '') {
    $id = (!empty($id)) ? $id : 'ubmap';
    $result = lmaps_LegacyCore()->container($width, $height, $id);
    return ($result);
}

/**
 * Translates yandex to google icon code
 * 
 * @param string $icon
 * @return string
 */
function lm_GetIconUrl($icon) {
    $result = lmaps_LegacyCore()->iconUrl($icon);
    return ($result);
}

/**
 * Returns placemark code
 * 
 * @param string $coords
 * @param string $title
 * @param string $content
 * @param string $footer
 * @param string $icon
 * @param string $iconlabel
 * @param bool $canvas
 * 
 * @deprecated Use MapCore::addMarker()
 *
 * @return string
 */
function generic_MapAddMark($coords, $title = '', $content = '', $footer = '', $icon = 'twirl#lightblueIcon', $iconlabel = '', $canvas = false) {
    $result = lmaps_LegacyCore()->addMark($coords, $title, $content, $footer, $icon);
    return ($result);
}

/**
 * Adds a dynamic map marker with AJAX-loaded popup content
 *
 * @param string $coords Coordinates in "lat,lng" format
 * @param string $title Marker tooltip text
 * @param string $contentUrl URL to load popup content from
 * @param string $icon Icon identifier
 * 
 * @deprecated Use MapCore::addDynamicMarker()
 *
 * @return string
 */
function generic_MapAddMarkDynamic($coords, $title = '', $contentUrl = '', $icon = 'twirl#lightblueIcon') {
    $result = lmaps_LegacyCore()->addMarkDynamic($coords, $title, $contentUrl, $icon);
    return ($result);
}



/**
 * Returns circle map placemark
 * 
 * @param string $coords - map coordinates
 * @param int $radius - circle radius in meters
 * @param string $content - popup balloon content
 * @param string $hint - on mouseover hint
 * @param string $color - circle border color, default: 009d25
 * @param float  $opacity - border opacity from 0 to 1, default: 0.8
 * @param string $fillColor - fill color of circle, default: 00a20b55
 * @param float $fillOpacity - fill opacity from 0 to 1, default: 0.5
 * 
 * @deprecated Use MapCore::addCircle()
 *
 * @return string
 */
function generic_MapAddCircle($coords, $radius, $content = '', $hint = '', $color = '009d25', $opacity = 0.8, $fillColor = '00a20b55', $fillOpacity = 0.5) {
    $result = lmaps_LegacyCore()->addCircle($coords, $radius, $content, $hint, $color, $opacity, $fillColor, $fillOpacity);
    return ($result);
}
/**
 * Initalizes leaflet maps API with some params
 * 
 * @param string $center
 * @param int $zoom
 * @param string $type
 * @param string $placemarks
 * @param string $editor
 * @param string $lang
 * @param string $container
 * @param string $searchPrefill
 * 
 * @deprecated Use MapCore instance setup + MapCore::render()
 *
 * @return string
 */
function generic_MapInit($center = '', $zoom = 15, $type = 'roadmap', $placemarks = '', $editor = '', $lang = 'uk-UA', $container = 'ubmap', $searchPrefill = '') {
    $result = lmaps_LegacyCore()->initMap($center, $zoom, $type, $placemarks, $editor, $container, $searchPrefill);
    return ($result);
}


/**
 * Return generic editor code
 * 
 * @param string $name
 * @param string $title
 * @param string $data
 * @param int    $precision
 * 
 * @deprecated Use MapCore::addClickEditor()
 *
 * @return string
 */
function generic_MapEditor($name, $title = '', $data = '', $precision = 8) {
    $result = lmaps_LegacyCore()->editor($name, $title, $data, $precision);
    return ($result);
}

/**
 * Returns JS code to draw line within two points
 * 
 * @param string $coord1
 * @param string $coord2
 * @param string $color
 * @param string $hint
 * @param string $width
 * 
 * @deprecated Use MapCore::addLine()
 *
 * @return string
 */
function generic_MapAddLine($coord1, $coord2, $color = '', $hint = '', $width = '') {
    $color = (!empty($color)) ? $color : '#000000';
    if (!empty($color)) {
        $width = $width + 1;
    } else {
        $width = 1;
    }
    $result = lmaps_LegacyCore()->addLine($coord1, $coord2, $color, $hint, $width);
    return ($result);
}
