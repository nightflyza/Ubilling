<?php

/**
 * Custom users maps class
 */
class CustomMaps {

    /**
     * Contains all existing maps as id=>mapData
     *
     * @var array
     */
    protected $allMaps = array();

    /**
     * Contains all existing items as id=>itemData
     *
     * @var array
     */
    protected $allItems = array();

    /**
     * Contains Ymaps configuration as key=>value
     *
     * @var array
     */
    protected $ymapsCfg = array();

    /**
     * Contains system alter configuration as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains available item types as type=>name
     *
     * @var array
     */
    protected $itemTypes = array();
    /**
     * Current map center
     *
     * @var string
     */
    protected $center = '';
    /**
     * Current map zoom
     *
     * @var int
     */
    protected $zoom = '';
    /**
     * Map core instance
     *
     * @var object
     */
    protected $mapCore = null;

    /**
     * Database abstraction layer for maps
     *
     * @var object
     */
    protected $mapsDb = null;
    /**
     * Database abstraction layer for items
     * 
     * @var object
     */
    protected $itemsDb = null;

    // some predefined stuff
    const EX_NO_MAP_ID = 'NOT_EXISTING_MAP_ID';
    const EX_NO_ITM_ID = 'NOT_EXISTING_ITEM_ID';
    const TABLE_MAPS = 'custmaps';
    const TABLE_ITEMS = 'custmapsitems';

    public function __construct() {
        $this->loadYmapsConfig();
        $this->initDb();
        $this->loadAlterConfig();
        $this->setDefaults();
        $this->initMapCore();
        $this->setItemTypes();
        $this->loadMaps();
        $this->loadItems();
    }

    protected function initDb() {
        $this->mapsDb = new NyanORM(self::TABLE_MAPS);
        $this->itemsDb = new NyanORM(self::TABLE_ITEMS);
    }

    /**
     * Initializes shared map core instance
     *
     * @return void
     */
    protected function initMapCore() {
        $this->mapCore = new MapCore('custmap');
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
     * Loads system-wide alter config into private config storage
     * 
     * @global object $ubillingConfig
     */
    protected function loadAlterConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Loads existing custom maps into private data property
     * 
     * @return void
     */
    protected function loadMaps() {
        $this->mapsDb->orderBy('id', 'DESC');
        $all = $this->mapsDb->getAll('id');
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allMaps[$each['id']] = $each;
            }
        }
    }

    /**
     * Sets default map center and zoom
     * 
     * @return void
     */
    protected function setDefaults() {
        $this->center = $this->ymapsCfg['CENTER'];
        $this->zoom = $this->ymapsCfg['ZOOM'];
    }

    /**
     * Sets available item types into private data property
     * 
     * @return void
     */
    protected function setItemTypes() {
        $this->itemTypes = array(
            'pillar' => __('Pillar'),
            'sump' => __('Sump'),
            'coupling' => __('Coupling'),
            'node' => __('Node'),
            'box' => __('Box'),
            'amplifier' => __('Amplifier'),
            'optrec' => __('Optical reciever'),
            'camera' => __('Camera'),
            'wifi' => __('WiFi'),
        );
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
                $result = 'marker.green';
                break;
            case 'sump':
                $result = 'marker.brown';
                break;
            case 'coupling':
                $result = 'marker.yellow';
                break;
            case 'node':
                $result = 'marker.orange';
                break;
            case 'box':
                $result = 'marker.grey';
                break;
            case 'amplifier':
                $result = 'marker.pink';
                break;
            case 'optrec':
                $result = 'marker.darkblue';
                break;
            case 'camera':
                $result = 'marker.camera';
                break;
            case 'wifi':
                $result = 'marker.wifi';
                break;
            default :
                $result = 'marker.blue';
                break;
        }
        return ($result);
    }

    /**
     * Returns item type localized name
     * 
     * @param string $type
     * @return string
     */
    protected function itemGetTypeName($type) {
        $result = '';
        if (isset($this->itemTypes[$type])) {
            $result = $this->itemTypes[$type];
        } else {
            $result.=__('Unknown');
        }
        return ($result);
    }

    /**
     * Loads all existing custom maps items into private data property
     * 
     * @return void
     */
    protected function loadItems() {
        $all = $this->itemsDb->getAll('id');
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allItems[$each['id']] = $each;
            }
        }
    }

    /**
     * Sets override map center
     * 
     * @param string $coords
     */
    public function setCenter($coords) {
        $this->center = $coords;
    }

    /**
     * Sets map override zoom
     * 
     * @param int $zoom
     */
    public function setZoom($zoom) {
        $this->zoom = $zoom;
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
        $result.=wf_BackLink('?module=custmaps');
        if (ubRouting::checkGet('showmap')) {
            $mapId = ubRouting::get('showmap', 'int');
            if (cfr('CUSTMAPEDIT')) {
                $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&mapedit=true', wf_img('skins/ymaps/edit.png') . ' ' . __('Edit'), false, 'ubButton');
            }

            //custom layers
            if (ubRouting::checkGet('cl')) {
                $custLayers = ubRouting::get('cl');
            } else {
                $custLayers = '';
            }

            $result.=wf_Link('?module=custmaps&showitems=' . $mapId, wf_img('skins/icon_table.png') . ' ' . __('Objects'), false, 'ubButton');
            $result.=wf_delimiter();
            $result.=wf_Link('?module=custmaps&showmap=' . $mapId, wf_img('skins/icon_cleanup.png') . ' ' . $this->mapGetName($mapId), false, 'ubButton');
            foreach ($this->allMaps as $cmapId => $cmapData) {
                if ($cmapId != $mapId) {
                    $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&cl=' . $cmapId . 'z' . $this->filterLayers($custLayers, $cmapId . 'z'), wf_img('skins/swmapsmall.png') . ' ' . $this->mapGetName($cmapId), false, 'ubButton');
                }
            }
        }
        $result.=wf_delimiter();
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
                $cells.= wf_TableCell($nameLink,'80%');
                $actLinks = '';
                if (cfr('CUSTMAPEDIT')) {
                    $actLinks.= wf_JSAlertStyled('?module=custmaps&deletemap=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert()) . ' ';
                    $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->mapEditForm($each['id']));
                }
                $actLinks.= wf_Link('?module=custmaps&showmap=' . $each['id'], wf_img('skins/icon_search_small.gif', __('Show')), false);
                $actLinks.= wf_Link('?module=custmaps&showitems=' . $each['id'], wf_img('skins/icon_table.png', __('Objects')), false);

                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }
        }

        $result.= wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * Returns item edit form
     * 
     * @param int $itemid
     * @return string
     */
    public function itemEditForm($itemid) {
        $itemid = ubRouting::filters($itemid, 'int');
        $result = '';
        if (isset($this->allItems[$itemid])) {
            $result.= wf_BackLink('?module=custmaps&showitems=' . $this->allItems[$itemid]['mapid']);
            $result.= wf_delimiter();
            $inputs = wf_HiddenInput('edititemid', $itemid);
            $inputs.= wf_Selector('edititemtype', $this->itemTypes, __('Type'), $this->allItems[$itemid]['type'], true);
            $inputs.= wf_TextInput('edititemgeo', __('Geo location'), $this->allItems[$itemid]['geo'], true, '20', 'geo');
            $inputs.= wf_TextInput('edititemname', __('Name'), $this->allItems[$itemid]['name'], true, '20');
            $inputs.= wf_TextInput('edititemlocation', __('Location'), $this->allItems[$itemid]['location'], true, '20');
            $inputs.= wf_Submit(__('Save'));
            $result.= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            throw new Exception(self::EX_NO_ITM_ID);
        }
        return ($result);
    }

    /**
     * Changes existing item properties in database
     * 
     * @param string $itemid
     * @param string $type
     * @param string $geo
     * @param string $name
     * @param string $location
     * @throws Exception
     */
    public function itemEdit($itemid, $type, $geo, $name, $location) {
        $itemid = ubRouting::filters($itemid, 'int');
        $type = ubRouting::filters($type, 'mres');
        $geo = ubRouting::filters($geo, 'mres');
        $name = ubRouting::filters($name, 'mres');
        $location = ubRouting::filters($location, 'mres');
        if (isset($this->allItems[$itemid])) {
            $this->itemsDb->data('name', $name);
            $this->itemsDb->data('type', $type);
            $this->itemsDb->data('geo', $geo);
            $this->itemsDb->data('location', $location);
            $this->itemsDb->where('id', '=', $itemid);
            $this->itemsDb->save(true, true);
            log_register('CUSTMAPS EDIT ITEM [' . $itemid . ']');
        } else {
            throw new Exception(self::EX_NO_ITM_ID);
        }
    }

    /**
     * Returns existing map items list datatables container
     *      
     * @param int $mapid
     * 
     * @return string
     */
    public function renderItemsListFast($mapid) {
        $mapid = ubRouting::filters($mapid, 'int');
        $opts = '"order": [[ 0, "desc" ]]';
        $columns = array('ID', 'Type', 'Geo location', 'Name', 'Location', 'Actions');
        $result = '';
        $result.= wf_BackLink('?module=custmaps');
        $result.=wf_Link('?module=custmaps&showitems=' . $mapid . '&duplicates=true', wf_img('skins/duplicate_icon.gif') . ' ' . __('Show duplicates'), true, 'ubButton');

        $result.= wf_delimiter();
        $result.=wf_JqDtLoader($columns, '?module=custmaps&ajax=true&showitems=' . $mapid, false, 'Objects', '100', $opts);
        return ($result);
    }

    /**
     * Renders custom map items list JSON data
     * 
     * @param int $mapid
     * 
     * @return void
     */
    public function renderItemsListJsonData($mapid) {
        $mapid = ubRouting::filters($mapid, 'int');
        $json = new wf_JqDtHelper();
        $messages = new UbillingMessageHelper();

        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('CUSTMAPITEMS');
            $adc = true;
        } else {
            $adc = false;
        }

        if (!empty($this->allItems)) {
            foreach ($this->allItems as $io => $each) {
                $indicator = ($adc) ? $adcomments->getCommentsIndicator($each['id']) : '';
                if ($each['mapid'] == $mapid) {

                    $actLinks = '';
                    if (cfr('CUSTMAPEDIT')) {
                        $actLinks.= wf_JSAlertStyled('?module=custmaps&deleteitem=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert()) . ' ';
                    }
                    $actLinks.= wf_JSAlertStyled('?module=custmaps&edititem=' . $each['id'], web_edit_icon(), $messages->getEditAlert()) . ' ';
                    $actLinks.= wf_Link('?module=custmaps&showmap=' . $each['mapid'] . '&locateitem=' . $each['geo'] . '&zoom=' . $this->ymapsCfg['FINDING_ZOOM'], wf_img('skins/icon_search_small.gif', __('Find on map')), false) . ' ';

                    $actLinks.=$indicator;


                    $data[] = $each['id'];
                    $data[] = $this->itemGetTypeName($each['type']);
                    $data[] = $each['geo'];
                    $data[] = $each['name'];
                    $data[] = $each['location'];
                    $data[] = $actLinks;


                    $json->addRow($data);
                    unset($data);
                }
            }
        }

        $json->getJson();
    }

    /**
     * Returns list of duplicate coords/name items for some existing map
     * 
     * @param int $mapid
     * @return string
     */
    public function renderItemDuplicateList($mapid) {
        $mapid = ubRouting::filters($mapid, 'int');
        $result = '';
        $messages = new UbillingMessageHelper();
        $itemsCount = 0;
        $filterArray = array();

        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('CUSTMAPITEMS');
            $adc = true;
        } else {
            $adc = false;
        }

        //counting unique geo coords
        if (!empty($this->allItems)) {
            foreach ($this->allItems as $ia => $eachItem) {
                if ($eachItem['mapid'] == $mapid) {
                    if (isset($filterArray[$eachItem['geo']])) {
                        $filterArray[$eachItem['geo']] ++;
                    } else {
                        $filterArray[$eachItem['geo']] = 1;
                    }
                }
            }
        }

        $result.= wf_BackLink('?module=custmaps&showitems=' . $mapid);

        $result.= wf_delimiter();

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Type'));
        $cells.= wf_TableCell(__('Geo location'));
        $cells.= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Location'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allItems)) {
            foreach ($this->allItems as $io => $each) {
                $indicator = ($adc) ? $adcomments->getCommentsIndicator($each['id']) : '';
                if ($each['mapid'] == $mapid) {
                    if (isset($filterArray[$each['geo']])) {
                        if ($filterArray[$each['geo']] > 1) {

                            $cells = wf_TableCell($each['id']);
                            $cells.= wf_TableCell($this->itemGetTypeName($each['type']));
                            $cells.= wf_TableCell($each['geo']);
                            $cells.= wf_TableCell($each['name']);
                            $cells.= wf_TableCell($each['location']);
                            $actLinks = '';
                            if (cfr('CUSTMAPEDIT')) {
                                $actLinks.= wf_JSAlertStyled('?module=custmaps&deleteitem=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert()) . ' ';
                            }
                            $actLinks.= wf_JSAlertStyled('?module=custmaps&edititem=' . $each['id'], web_edit_icon(), $messages->getEditAlert()) . ' ';
                            $actLinks.= wf_Link('?module=custmaps&showmap=' . $each['mapid'] . '&locateitem=' . $each['geo'] . '&zoom=' . $this->ymapsCfg['FINDING_ZOOM'], wf_img('skins/icon_search_small.gif', __('Find on map')), false) . ' ';

                            $actLinks.=$indicator;

                            $cells.= wf_TableCell($actLinks);
                            $rows.= wf_TableRow($cells, 'row3');
                            $itemsCount++;
                        }
                    }
                }
            }
        }

        $result.= wf_TableBody($rows, '100%', '0', 'sortable');
        $result.= __('Total') . ': ' . $itemsCount;
        return ($result);

        return ($result);
    }

    /**
     * Deletes item from database by its ID
     * 
     * @param int $itemid
     * 
     * @return int
     */
    public function itemDelete($itemid) {
        $itemid = ubRouting::filters($itemid, 'int');
        $result = '';
        if (isset($this->allItems[$itemid])) {
            $result = $this->allItems[$itemid]['mapid'];
            $this->itemsDb->where('id', '=', $itemid);
            $this->itemsDb->delete();
            log_register('CUSTMAPS DELETE ITEM  ID [' . $itemid . ']');
        } else {
            throw new Exception(self::EX_NO_ITM_ID);
        }
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
        $nameFiltered = ubRouting::filters($name, 'mres');
        $nameFiltered = ubRouting::filters($nameFiltered, 'safe');
        $this->mapsDb->data('name', $nameFiltered);
        $this->mapsDb->create();
        $newId = $this->mapsDb->getLastId();
        log_register('CUSTMAPS CREATE MAP `' . $name . '` ID [' . $newId . ']');
    }

    /**
     * Deletes existing custom map by its ID
     * 
     * @param int $id
     */
    public function mapDelete($id) {
        $id = ubRouting::filters($id, 'int');
        if (isset($this->allMaps[$id])) {
            $this->mapsDb->where('id', '=', $id);
            $this->mapsDb->delete();
            log_register('CUSTMAPS DELETE MAP [' . $id . ']');
            $this->itemsDb->where('mapid', '=', $id);
            $this->itemsDb->delete();
            log_register('CUSTMAPS FLUSH ITEMS [' . $id . ']');
        } else {
            throw new Exception(self::EX_NO_MAP_ID);
        }
    }

    /**
     * Changes existing custom map name in database
     * 
     * @param int  $id
     * @param string $name
     * @throws Exception
     */
    public function mapEdit($id, $name) {
        $id = ubRouting::filters($id, 'int');
        $name = ubRouting::filters($name, 'mres');
        $name = ubRouting::filters($name, 'safe');
        if (isset($this->allMaps[$id])) {
            $this->mapsDb->data('name', $name);
            $this->mapsDb->where('id', '=', $id);
            $this->mapsDb->save();
            log_register('CUSTMAPS EDIT MAP [' . $id . '] SET `' . $name . '`');
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
        $id = ubRouting::filters($id, 'int');
        return ($this->allMaps[$id]['name']);
    }



    /**
     * Returns list of map placemarks
     * 
     * @param int $id
     * 
     * @return string
     */
    public function mapGetPlacemarks($id) {
        $id = ubRouting::filters($id, 'int');
        $result = '';
        if (!empty($this->allItems)) {
            foreach ($this->allItems as $io => $each) {
                if (($each['mapid'] == $id) and (!empty($each['geo']))) {
                    $icon = $this->itemGetIcon($each['type']);
                    $content = $this->itemGetTypeName($each['type']) . ': ' . $each['name'];
                    $controls = wf_Link('?module=custmaps&edititem=' . $each['id'], web_edit_icon(), false);
                    $this->mapCore->addMarker($each['geo'], $content, array(
                        'icon' => $icon,
                        'popupTitle' => $each['location'],
                        'popupFooter' => $controls,
                        'tooltip' => $content
                    ));
                }
            }
        }
        return ($result);
    }

    /**
     * Returns item location form
     * 
     * @return string
     */
    protected function itemLocationForm() {
        $result = wf_Selector('newitemtype', $this->itemTypes, __('Type'), '', true);
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
        $mapid = ubRouting::filters($mapid, 'int');
        $type = ubRouting::filters($type, 'mres');
        $geo = ubRouting::filters($geo, 'mres');
        $nameFiltered = ubRouting::filters($name, 'mres');
        $location = ubRouting::filters($location, 'mres');

        if (isset($this->allMaps[$mapid])) {
            $this->itemsDb->data('mapid', $mapid);
            $this->itemsDb->data('type', $type);
            $this->itemsDb->data('geo', $geo);
            $this->itemsDb->data('name', $nameFiltered);
            $this->itemsDb->data('location', $location);
            $this->itemsDb->create();
            $newId = $this->itemsDb->getLastId();
            log_register('CUSTMAPS CREATE ITEM `' . $name . '` ID [' . $newId . ']');
        } else {
            throw new Exception(self::EX_NO_MAP_ID);
        }
    }

    /**
     * Returns map circle
     * 
     * @param string $coords - map coordinates
     * @param int $radius - circle radius in meters
     * 
     * @return string
     *  
     */
    public function mapAddCircle($coords, $radius, $content = '', $hint = '') {
        $this->mapCore->addCircle($coords, $radius, $content, array(
            'hint' => $hint
        ));
        $result = '';
        return ($result);
    }

    /**
     * Returns initialized JS map
     * 
     * @return string
     */
    public function mapInit() {
        $result = $this->mapControls();
        $result.= $this->mapCore->renderContainer('100%', '800px');
        $this->mapCore->setCenter($this->center);
        $this->mapCore->setZoom($this->zoom);
        $this->mapCore->setType($this->ymapsCfg['TYPE']);
        $result.= $this->mapCore->render();
        return ($result);
    }

    /**
     * Return geo coordinates locator with embedded form
     * 
     * @return string
     */
    public function mapLocationEditor() {
        $title = wf_tag('b') . __('Place coordinates') . wf_tag('b', true);
        $data = $this->itemLocationForm();
        $this->mapCore->addLocationEditor('newitemgeo', $title, $data);
        $result = '';
        return ($result);
    }

}
