<?php

/**
 * Returns google maps empty container
 * 
 * @return string
 */
function gm_MapContainer($width = '', $height = '', $id = '') {
    $width = (!empty($width)) ? $width : '100%';
    $height = (!empty($height)) ? $height : '800px;';
    $id = (!empty($id)) ? $id : 'gmap';
    $result = wf_tag('div', false, '', 'id="gmap" style="width: 100%; height:800px;"');
    $result.=wf_tag('div', true);
    return ($result);
}

/**
 * Initalizes google maps API with some params
 * 
 * @param string $center
 * @param int $zoom
 * @param string $type
 * @param string $placemarks
 * @param bool $editor
 * @param string $lang
 * 
 * @return string
 */
function gm_MapInit($center, $zoom, $type, $placemarks = '', $editor = '', $lang = 'ru-RU') {
    global $ubillingConfig;
    $mapsCfg = $ubillingConfig->getYmaps();
    @$apikey = $mapsCfg['GMAPS_APIKEY'];
    $result = '';
    if ((!empty($apikey)) AND ( $apikey != 'YOUR_API_KEY_HERE')) {
        if (!empty($center)) {
            $center = explode(',', $center);
            $centerLat = trim($center[0]);
            $centerLng = trim($center[1]);
            $centerCode = 'center: uluru';
        } else {
            //not working yet, some R&D reqiured about auto detecting position
            $centerLat = '48.5319';
            $centerLng = '25.0350';
            $centerCode = 'center: uluru';
        }
        $result.= wf_tag('script', false, '', 'type="text/javascript"');
        $result.=' function initMap() {
        var uluru = {lat: ' . $centerLat . ', lng: ' . $centerLng . '};
        var map = new google.maps.Map(document.getElementById(\'gmap\'), {
          zoom: ' . $zoom . ',
          ' . $centerCode . '
        });
        ' . $placemarks . '
      }

';
        $result.=wf_tag('script', true);
        $result.=wf_tag('script', false, '', 'async defer type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCZNDUvCXRjZB_lSlYZE91cIYViv_e7JwM&callback=initMap"');
        $result.=wf_tag('script', true);
    } else {
        $messages = new UbillingMessageHelper();
        $result = $messages->getStyledMessage(__('No valid GMAPS_APIKEY set in ymaps.ini'), 'error');
    }
    return ($result);
}

/**
 * Translates yandex to google icon code
 * 
 * @param string $icon
 * @return string
 */
function gm_GetIconUrl($icon) {
    $result = '';
    switch ($icon) {
        case 'twirl#lightblueIcon':
            $result = 'skins/mapmarks/blue.png';
            break;
        case 'twirl#lightblueStretchyIcon':
            $result = 'skins/mapmarks/blue.png';
            break;
        case 'twirl#redStretchyIcon':
            $result = 'skins/mapmarks/red.png';
            break;
        case 'twirl#redIcon':
            $result = 'skins/mapmarks/red.png';
            break;

        default :
            $result = 'skins/mapmarks/blue.png';
            break;
    }
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
 * @return string
 */
function gm_MapAddMark($coords, $title = '', $content = '', $footer = '', $icon = 'twirl#lightblueIcon', $iconlabel = '', $canvas = false) {
    $markerId = wf_InputId();
    if (!empty($coords)) {
        $coords = explode(',', $coords);
        $coordLat = trim($coords[0]);
        $coordLng = trim($coords[1]);
    }

    $iconUrl = gm_GetIconUrl($icon);
    if (!empty($iconUrl)) {
        $iconCode = "var image_" . $markerId . " = '" . $iconUrl . "';";
    }

    if (!empty($title)) {
        $titleCode = '<strong>' . $title . '</strong><br>';
    } else {
        $titleCode = '';
    }
    if (!empty($title)) {
        $labelCode = "title: '" . $iconlabel . "',";
    } else {
        $labelCode = '';
    }
    if ((!empty($content)) OR ( !empty($footer))) {
        if (!empty($footer)) {
            $footerCode = '<div id="footer_' . $markerId . '" class="row3">' . $footer . '</div>';
        } else {
            $footerCode = '';
        }
        $contentWindow = 'var contentString_' . $markerId . ' = \'<div id = "content_' . $markerId . '">' . $titleCode . $content . $footerCode . '</div>\';
            var infowindow_' . $markerId . ' = new google.maps.InfoWindow({
            content: contentString_' . $markerId . '
            });
            google.maps.event.addListener(marker_' . $markerId . ', \'click\', function() {
                infowindow_' . $markerId . '.open(map,marker_' . $markerId . ');
            });
            ';
    } else {
        $contentWindow = '';
    }

    $result = '
          var position_' . $markerId . ' = {lat: ' . $coordLat . ', lng: ' . $coordLng . '};
          ' . $iconCode . '
          var marker_' . $markerId . ' = new google.maps.Marker({
          ' . $labelCode . '
          position: position_' . $markerId . ',
          map: map,
          icon: image_' . $markerId . '
        });
         ' . $contentWindow . '
            ';
    return ($result);
}

///
/// Emulating existing modules functions below
///

/**
 * Shows map container
 *
 * @return void
 *
 */
function sm_ShowMapContainer() {
    $container = gm_MapContainer('', '', 'swmap');
    $controls = wf_Link("?module=usersmap", wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
    $controls.= wf_Link("?module=switchmap", wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
    $controls.= wf_Link("?module=switchmap&locfinder=true", wf_img('skins/ymaps/edit.png') . ' ' . __('Edit map'), false, 'ubButton');
    $controls.= wf_Link("?module=switchmap&showuplinks=true", wf_img('skins/ymaps/uplinks.png') . ' ' . __('Show links'), false, 'ubButton');
    $controls.= wf_Link("?module=switchmap&clusterer=true", wf_img('skins/ymaps/cluster.png') . ' ' . __('Clusterer'), false, 'ubButton');
    $controls.= wf_Link("?module=switchmap&coverage=true", wf_img('skins/ymaps/coverage.png') . ' ' . __('Coverage area'), false, 'ubButton');
    $controls.= wf_Link("?module=switches", wf_img('skins/ymaps/switchdir.png') . ' ' . __('Available switches'), true, 'ubButton');
    $controls.=wf_delimiter(1);
    show_window(__('Active equipment map'), $controls . $container);
}

/**
 * Returns good icon class
 * 
 * @param bool $stretchy - icon resizable by content?
 * 
 * @return string
 */
function sm_MapGoodIcon($stretchy = true) {
    if ($stretchy) {
        return ('twirl#lightblueStretchyIcon');
    } else {
        return ('twirl#lightblueIcon');
    }
}

/**
 * Returns bad icon class
 * 
 * @param bool $stretchy - icon resizable by content?
 * 
 * @return string
 */
function sm_MapBadIcon($stretchy = true) {
    if ($stretchy) {
        return ('twirl#redStretchyIcon');
    } else {
        return ('twirl#redIcon');
    }
}

function sm_MapInit($center, $zoom, $type, $placemarks = '', $editor = '', $lang = 'ru-RU') {
    show_window('', gm_MapInit($center, $zoom, $type, $placemarks, $editor, $lang));
}

//need some code here
function sm_MapLocationFinder() {
    return ('');
}

/**
 * Returns full map marks for switches with filled GEO field
 * 
 * @return string
 */
function sm_MapDrawSwitches() {
    $ym_conf = rcms_parse_ini_file(CONFIG_PATH . "ymaps.ini");
    $query = "SELECT * from `switches` WHERE `geo` != '' ";
    $allswitches = simple_queryall($query);

    $uplinkTraceIcon = wf_img('skins/ymaps/uplinks.png', __('Show links'));
    $switchEditIcon = wf_img('skins/icon_edit.gif', __('Edit'));
    $switchPollerIcon = wf_img('skins/snmp.png', __('SNMP query'));
    $switchLocatorIcon = wf_img('skins/icon_search_small.gif', __('Zoom in'));

    $footerDelimiter = wf_tag('br');
    $result = '';
    //dead switches detection
    $dead_raw = zb_StorageGet('SWDEAD');
    $deadarr = array();
    if ($dead_raw) {
        $deadarr = unserialize($dead_raw);
    }

    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $each) {
            $geo = mysql_real_escape_string($each['geo']);
            $title = mysql_real_escape_string($each['ip']);

            //switch hint content
            $content = mysql_real_escape_string($each['location']);


            $iconlabel = $each['location'];

            if (!isset($deadarr[$each['ip']])) {
                $footer = __('Switch alive');
                $icon = sm_MapGoodIcon();
            } else {
                $footer = __('Switch dead');
                $icon = sm_MapBadIcon();
            }


            //switch footer controls
            $footer.=$footerDelimiter;
            $footer.=wf_tag('a', false, '', 'href="?module=switches&edit=' . $each['id'] . '"') . $switchEditIcon . wf_tag('a', true) . ' ';


            if (!empty($each['snmp'])) {
                $footer.=wf_tag('a', false, '', 'href="?module=switchpoller&switchid=' . $each['id'] . '"') . $switchPollerIcon . wf_tag('a', true) . ' ';
            }

            $footer.=wf_tag('a', false, '', 'href="?module=switchmap&finddevice=' . $each['geo'] . '"') . $switchLocatorIcon . wf_tag('a', true) . ' ';


            if (!empty($each['parentid'])) {
                $uplinkTraceUrl = '?module=switchmap&finddevice=' . $each['geo'] . '&showuplinks=true&traceid=' . $each['id'];
                $uplinkTraceLink = wf_tag('a', false, '', 'href="' . $uplinkTraceUrl . '"') . $uplinkTraceIcon . wf_tag('a', true) . ' ';
                $footer.= $uplinkTraceLink;
            }
            $result.=gm_MapAddMark($geo, $title, $content, $footer, $icon, $iconlabel, true);
        }
    }
    return ($result);
}

?>