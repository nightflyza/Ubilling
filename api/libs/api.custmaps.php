<?php

class CustomMaps {

    protected $allMaps = array();
    protected $allItems = array();
    protected $ymapsCfg = array();
    protected $center = '';
    protected $zoom = '';

    const EX_NO_MAP_ID = 'NOT_EXISTING_MAP_ID';

    public function __construct() {
        $this->loadYmapsConfig();
        $this->setDefaults();
        $this->loadMaps();
        $this->loadItems();
    }

    /**
     * Loads system-wide ymaps config into private config storage
     * 
     * @global object $ubillingConfig
     */
    protected function loadYmapsConfig() {
        global $ubillingConfig;
        $this->ymapsCfg = $ubillingConfig->getYmaps();
    }

    /**
     * Loads existing custom maps into private data property
     * 
     * @return void
     */
    protected function loadMaps() {
        $query = "SELECT * from `custmaps` ORDER by `id` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allMaps[$each['id']] = $each;
            }
        }
    }

    protected function setDefaults() {
        $this->center = $this->ymapsCfg['CENTER'];
        $this->zoom = $this->ymapsCfg['ZOOM'];
    }

    /**
     * Loads all existing custom maps items into private data property
     * 
     * @return void
     */
    protected function loadItems() {
        $query = "SELECT * from `custmapsitems`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allItems[$each['id']] = $each;
            }
        }
    }

    /**
     * Filters some layer from current
     * 
     * @param string $layers
     * @param string $filter
     * @return string
     */
    protected function filterLayers($layers, $filter) {
        $result = str_replace($filter, '', $layers);
        return ($result);
    }

    /**
     * Returns map controls
     * 
     * @return string
     */
    protected function mapControls() {
        $result = '';
        $result.=wf_Link('?module=custmaps', __('Back'), false, 'ubButton');
        if (wf_CheckGet(array('showmap'))) {
            $mapId = $_GET['showmap'];
            if (cfr('CUSTMAPEDIT')) {
                $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&mapedit=true', wf_img('skins/ymaps/edit.png') . ' ' . __('Edit'), false, 'ubButton');
            }

            if (wf_CheckGet(array('layers'))) {
                $curLayers = $_GET['layers'];
            } else {
                $curLayers = '';
            }

            $result.=wf_Link('?module=custmaps&showmap=' . $mapId, wf_img('skins/icon_cleanup.png') . ' ' . $this->mapGetName($mapId), false, 'ubButton');
            $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&layers=bs' . $this->filterLayers($curLayers, 'bs'), wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
            $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&layers=sw' . $this->filterLayers($curLayers, 'sw'), wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
            $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&layers=ul' . $this->filterLayers($curLayers, 'ul'), wf_img('skins/ymaps/uplinks.png') . ' ' . __('Show links'), false, 'ubButton');
        }
        $result.=wf_delimiter();
        return ($result);
    }

    /**
     * Returns empty map container
     * 
     * @return string
     */
    protected function mapContainer() {
        $container = wf_tag('div', false, '', 'id="custmap" style="width: 1000; height:800px;"');
        $container.=wf_tag('div', true);
        return ($container);
    }

    /**
     * Returns initialized JS map
     * 
     * @param string $placemarks
     * @param string $editor
     * @return string
     */
    public function mapInit($placemarks, $editor = '') {
        if (empty($this->center)) {
            $center = 'ymaps.geolocation.latitude, ymaps.geolocation.longitude';
        } else {
            $center = $this->center;
        }

        $result = $this->mapControls();
        $result.= $this->mapContainer();
        $result.= wf_tag('script', false, '', 'src="http://api-maps.yandex.ru/2.0/?load=package.full&lang=' . $this->ymapsCfg['LANG'] . '"  type="text/javascript"');
        $result.=wf_tag('script', true);
        $result.=wf_tag('script', false, '', 'type="text/javascript"');
        $result.= '
        ymaps.ready(init);
        function init () {
            var myMap = new ymaps.Map(\'custmap\', {
                    center: [' . $center . '], 
                    zoom: ' . $this->zoom . ',
                    type: \'yandex#' . $this->ymapsCfg['TYPE'] . '\',
                    behaviors: [\'default\',\'scrollZoom\']
                })
               
                 myMap.controls
                .add(\'zoomControl\')
                .add(\'typeSelector\')
                .add(\'mapTools\')
                .add(\'searchControl\');
               
         ' . $placemarks . '    
         ' . $editor . '
    }';
        $result.=wf_tag('script', true);
        return ($result);
    }

    /**
     * Returns existing maps list view
     * 
     * @return string
     */
    public function renderMapList() {
        $messages = new UbillingMessageHelper();

        $result = $this->mapListControls();

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allMaps)) {
            foreach ($this->allMaps as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $nameLink = wf_Link('?module=custmaps&showmap=' . $each['id'], $each['name'], false);
                $cells.= wf_TableCell($nameLink);
                $actLinks = '';
                if (cfr('CUSTMAPEDIT')) {
                    $actLinks.= wf_JSAlertStyled('?module=custmaps&deletemap=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert()) . ' ';
                    $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->mapEditForm($each['id']));
                }
                $actLinks.= wf_Link('?module=custmaps&showmap=' . $each['id'], wf_img('skins/icon_search_small.gif', __('Show')), false);
                $actLinks.= wf_Link('?module=custmaps&showitems=' . $each['id'], wf_img('skins/icon_table.png', __('Objects')), false);
                
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result.= wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }
    
     /**
     * Returns existing map items list view
     * 
     * @return string
     */
    public function renderItemsList($mapid) {
        $mapid=vf($mapid,3);
        $messages = new UbillingMessageHelper();

        $result = '';

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allItems)) {
            foreach ($this->allItems as $io => $each) {
                
                if ($each['mapid']==$mapid) {
                $cells = wf_TableCell($each['id']);
                
                $cells.= wf_TableCell($each['name']);
                $actLinks = '';
                if (cfr('CUSTMAPEDIT')) {
                    $actLinks.= wf_JSAlertStyled('?module=custmaps&deleteitem=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert()) . ' ';
                    //$actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->mapEditForm($each['id']));
                }
                //$actLinks.= wf_Link('?module=custmaps&showmap=' . $each['id'], wf_img('skins/icon_search_small.gif', __('Show')), false);
                //$actLinks.= wf_Link('?module=custmaps&showitems=' . $each['id'], wf_img('skins/icon_table.png', __('Objects')), false);
                
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
                }
            }
        }

        $result.= wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * Returns map creation form
     * 
     * @return string
     */
    protected function mapCreateForm() {
        $inputs = wf_TextInput('newmapname', __('Name'), '', true, '30');
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns custom map editing form
     * 
     * @param int $id
     * 
     * @return string
     */
    protected function mapEditForm($id) {
        $inputs = wf_TextInput('editmapname', __('Name'), $this->allMaps[$id]['name'], true, '30');
        $inputs.= wf_HiddenInput('editmapid', $id);
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns map list controls panel
     * 
     * @return string
     */
    protected function mapListControls() {
        $result = wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Create'), __('Create new map'), $this->mapCreateForm(), 'ubButton');
        $result.= wf_delimiter();
        return ($result);
    }

    /**
     * Creates new custom map in database
     * 
     * @param string $name
     */
    public function mapCreate($name) {
        $nameFiltered = mysql_real_escape_string($name);
        $query = "INSERT INTO `custmaps` (`id`, `name`) VALUES (NULL, '" . $nameFiltered . "'); ";
        nr_query($query);
        $newId = simple_get_lastid('custmaps');
        log_register('CUSTMAPS CREATE MAP `' . $name . '` ID [' . $newId . ']');
    }
    

    /**
     * Deletes existing custom map by its ID
     * 
     * @param int $id
     */
    public function mapDelete($id) {
        $id = vf($id, 3);
        if (isset($this->allMaps[$id])) {
            $query = "DELETE from `custmaps` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register('CUSTMAPS DELETE MAP [' . $id . ']');
            $query="DELETE from `custmapsitems` WHERE `id`='".$id."';";
            nr_query($query);
            log_register('CUSTMAPS FLUSH ITEMS [' . $id . ']');
        } else {
            throw new Exception(self::EX_NO_MAP_ID);
        }
    }

    /**
     * Changes existing custom map name in database
     * 
     * @paramint     $id
     * @param string $name
     * @throws Exception
     */
    public function mapEdit($id, $name) {
        $id = vf($id, 3);
        if (isset($this->allMaps[$id])) {
            simple_update_field('custmaps', 'name', $name, "WHERE `id`='" . $id . "'");
            log_register('CUSTMAPS EDIT [' . $id . '] SET `' . $name . '`');
        } else {
            throw new Exception(self::EX_NO_MAP_ID);
        }
    }

    /**
     * Returns existing custom map name by its Id
     * 
     * @param int $id
     * @return string
     */
    public function mapGetName($id) {
        $id = vf($id, 3);
        return ($this->allMaps[$id]['name']);
    }

    /**
     * Returns icon for some item type
     * 
     * @param string $type
     * 
     * @return string
     */
    protected function itemGetIcon($type) {

        switch ($type) {
            case 'pillar':
                $result = 'twirl#greenIcon';
                break;
            case 'sump':
                $result = 'twirl#brownIcon';
                break;
            case 'coupling':
                $result = 'twirl#yellowIcon';
                break;
            case 'node':
                $result = 'twirl#orangeIcon';
                break;
            default :
                $result = 'twirl#lightblueIcon';
                break;
        }
        return ($result);
    }

    /**
     * Returns list of map placemarks
     * 
     * @param int $id
     * 
     * @return string
     */
    public function mapGetPlacemarks($id) {
        $id = vf($id, 3);
        $result = '';
        if (!empty($this->allItems)) {
            foreach ($this->allItems as $io => $each) {
                if (($each['mapid'] == $id) AND ( !empty($each['geo']))) {
                    $icon = $this->itemGetIcon($each['type']);
                    $result.=$this->mapAddMark($each['geo'], $each['location'], $each['name'], 'FOOTER_CONTROLS', $icon, '');
                }
            }
        }
        return ($result);
    }

    /**
     * Returns map mark
     * 
     * @param string $coords - map coordinates
     * @param string $title - ballon title
     * @param string $content - ballon content
     * @param string $footer - ballon footer content
     * @param string $icon - YM icon class
     * @param string $iconlabel - icon label string
     * 
     * @return string
     */
    protected function mapAddMark($coords, $title = '', $content = '', $footer = '', $icon = 'twirl#lightblueIcon', $iconlabel = '') {
        if ($this->ymapsCfg['CANVAS_RENDER']) {
            if ($iconlabel == '') {
                $overlay = 'overlayFactory: "default#interactiveGraphics"';
            } else {
                $overlay = '';
            }
        } else {
            $overlay = '';
        }

        if (!wf_CheckGet(array('clusterer'))) {
            $markType = 'myMap.geoObjects';
        } else {
            $markType = 'clusterer';
        }

        $result = '
            myPlacemark = new ymaps.Placemark([' . $coords . '], {
                 iconContent: \'' . $iconlabel . '\',
                 balloonContentHeader: \'' . $title . '\',
                 balloonContentBody: \'' . $content . '\',
                 balloonContentFooter: \'' . $footer . '\',
                 hintContent: "' . $content . '",
                } , {
                    draggable: false,
                    preset: \'' . $icon . '\',
                    ' . $overlay . '
                        
                }),
 
            
           ' . $markType . '.add(myPlacemark);
        
            
            ';
        return ($result);
    }

    /**
     * Returns item location form
     * 
     * @return string
     */
    protected function itemLocationForm() {
        $itemtypes = array(
            'pillar' => __('Pillar'),
            'sump' => __('Sump'),
            'coupling' => __('Coupling'),
            'node' => __('Node')
        );
        $result = wf_Selector('newitemtype', $itemtypes, __('Type'), '', true);
        $result.= wf_TextInput('newitemname', __('Name'), '', true, 20);
        $result.= wf_TextInput('newitemlocation', __('Location'), '', true, 20);
        $result.= wf_Submit(__('Create'));
        return ($result);
    }

    /**
     * Creates new map item in database
     * 
     * @param int $mapid
     * @param string $type
     * @param string $geo
     * @param string $name
     * @param string $location
     */
    public function itemCreate($mapid, $type, $geo, $name, $location) {
        $mapid = vf($mapid, 3);
        $type = mysql_real_escape_string($type);
        $geo = mysql_real_escape_string($geo);
        $nameFiltered = mysql_real_escape_string($name);
        $location = mysql_real_escape_string($location);

        if (isset($this->allMaps[$mapid])) {
            $query = "INSERT INTO `custmapsitems` (`id`, `mapid`, `type`, `geo`, `name`, `location`) "
                    . "VALUES (NULL, '" . $mapid . "', '" . $type . "', '" . $geo . "', '" . $nameFiltered . "', '" . $location . "');";
            nr_query($query);
            $newId = simple_get_lastid('custmapsitems');
            log_register('CUSTMAPS CREATE ITEM `' . $name . '` ID [' . $newId . ']');
        } else {
            throw new Exception(self::EX_NO_MAP_ID);
        }
    }

    /**
     * Return geo coordinates locator with embedded form
     * 
     * @return string
     */
    public function mapLocationEditor() {
        $form = str_replace("'", '`', $this->itemLocationForm());
        $form = str_replace("\n", '', $form);

        $result = '
            myMap.events.add(\'click\', function (e) {
                if (!myMap.balloon.isOpen()) {
                    var coords = e.get(\'coordPosition\');
                    myMap.balloon.open(coords, {
                        contentHeader: \'' . __('Place coordinates') . '\',
                        contentBody: \' \' +
                            \'<p>\' + [
                            coords[0].toPrecision(6),
                            coords[1].toPrecision(6)
                            ].join(\', \') + \'</p> <form action="" method="POST"><input type="hidden" name="newitemgeo" value="\'+coords[0].toPrecision(6)+\', \'+coords[1].toPrecision(6)+\'">' . $form . '</form> \'
                 
                    });
                } else {
                    myMap.balloon.close();
                }
            });
            ';
        return ($result);
    }

}

?>