<?php

/**
 * Custom users maps class
 */
class CustMaps {

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
     * Database abstraction layer for items aka markers
     * 
     * @var object
     */
    protected $itemsDb = null;
    /**
     * Database abstraction layer for lines
     * 
     * @var object
     */
    protected $linesDb = null;

    /**
     * ID of the map which is currently being shown
     * 
     * @var int
     */
    protected $showMapId = 0;
    /**
     * Contains all existing lines as id=>lineData
     *
     * @var array
     */
    protected $allLines = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = null;

    /**
     * Default clustering options.
     *
     * @var array
     */
    protected $clustringOptions = array(
        'maxClusterRadius' => 80,
        'iconCreateFunction' => null,
        'spiderfyOnMaxZoom' => true,
        'showCoverageOnHover' => true,
        'zoomToBoundsOnClick' => true,
        'singleMarkerMode' => false,
        'disableClusteringAtZoom' => null,
        'removeOutsideVisibleBounds' => true,
        'animate' => true,
    );

    /** 
     * Some predefined stuff like URLs, routes etc
     */
    const URL_ME = '?module=custmaps';
    const TABLE_MAPS = 'custmaps';
    const TABLE_ITEMS = 'custmapsitems';
    const TABLE_LINES = 'custmaps_lines';
    
    const EX_NO_MAP_ID = 'NOT_EXISTING_MAP_ID';
    const EX_NO_ITM_ID = 'NOT_EXISTING_ITEM_ID';
    const EX_NO_LINE_ID = 'NOT_EXISTING_LINE_ID';
    const LINE_DEFAULT_COLOR = '#f57601';
    const LINE_DEFAULT_FIBERS_AMOUNT = 0;
    const LINE_DEFAULT_WIDTH = 2;
    const LINE_EDITOR_LIB='modules/jsc/custmaps/line-editor.js';

    public function __construct() {
        $this->setShowMapId();
        $this->initMessages();
        $this->loadYmapsConfig();
        $this->initDb();
        $this->loadAlterConfig();
        $this->setDefaults();
        $this->initMapCore();
        $this->setItemTypes();
        $this->loadMaps();
        $this->loadItems();
        $this->loadLines();
    }

    /**
     * Initializes system message helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Initializes database abstraction layers
     * 
     * @return void
     */
    protected function initDb() {
        $this->mapsDb = new NyanORM(self::TABLE_MAPS);
        $this->itemsDb = new NyanORM(self::TABLE_ITEMS);
        $this->linesDb = new NyanORM(self::TABLE_LINES);
    }

    /**
     * Sets ID of the map which is currently being shown
     * 
     * @return void
     */
    protected function setShowMapId() {
        if (ubRouting::checkGet('showmap')) {
            $mapId = ubRouting::get('showmap', 'int');
            if (!empty($mapId)) {
                $this->showMapId = ubRouting::filters($mapId, 'int');
            }
        }
    }

    /**
     * Initializes shared map core instance
     *
     * @return void
     */
    protected function initMapCore() {
        $containerId = 'custmap';
            $rememberZoom = false;
            $rememberPosition = false;
        if ($this->showMapId) {
            $containerId = 'custmap_' . $this->showMapId;
            $rememberZoom = true;
            $rememberPosition = true;
        }

        //creating map core instance
        $this->mapCore = new MapCore($containerId);
        if (@$this->altCfg['CUSTMAP_MCLSTR']) {
            $this->mapCore->setClustering(true, $this->clustringOptions);
        }
        if (@$this->altCfg['CUSTMAP_MCFMRKS']) {
            $this->mapCore->setForceCanvasMarkers(true);
        }
        //saving state of each map
        $this->mapCore->setRememberZoom($rememberZoom);
        $this->mapCore->setRememberPosition($rememberPosition);
        
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
            'waterfall' => __('Waterfall'),
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
            case 'waterfall':
                $result = 'marker.waterfall';
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
     * Loads all existing custom maps lines into private data property
     * 
     * @return void
     */
    protected function loadLines() {
        $all = $this->linesDb->getAll('id');
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allLines[$each['id']] = $each;
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
        $result .= wf_BackLink('?module=custmaps');
        if (ubRouting::checkGet('showmap')) {
            $mapId = ubRouting::get('showmap', 'int');
            if (cfr('CUSTMAPEDIT')) {
                $result .= wf_Link('?module=custmaps&showmap=' . $mapId . '&mapedit=true', wf_img('skins/ymaps/target.png') . ' ' . __('Edit markers'), false, 'ubButton');
                $result .= wf_Link('?module=custmaps&showmap=' . $mapId . '&lineedit=true', wf_img('skins/ymaps/edit.png') . ' ' . __('Edit lines'), false, 'ubButton');
            }

            $result .= wf_Link('?module=custmaps&showitems=' . $mapId, wf_img('skins/icon_mapplacemark16.png') . ' ' . __('Markers'), false, 'ubButton');
            $result .= wf_Link('?module=custmaps&showlines=' . $mapId, wf_img('skins/icon_mapline16.png') . ' ' . __('Lines'), false, 'ubButton');
        }
        $result .= wf_delimiter();
        return ($result);
    }

    /**
     * Returns custom map layers selection controls rendered under the map
     *
     * @return string
     */
    protected function mapLayersControls() {
        $result = '';
        if (ubRouting::checkGet('showmap')) {
            $mapId = ubRouting::get('showmap', 'int');
            if (ubRouting::checkGet('cl')) {
                $custLayers = ubRouting::get('cl');
            } else {
                $custLayers = '';
            }

            $activeLayers = array();
            if (!empty($custLayers)) {
                $activeLayers = array_filter(explode('_', $custLayers), 'strlen');
            }

            if (!empty($this->allMaps) and count($this->allMaps) > 1) {
                $result .= wf_delimiter(0);
                $result .= wf_tag('b') . __('Additional layers') .': '. wf_tag('b', true);
                if (!empty($activeLayers)) {
                    $result .= wf_Link('?module=custmaps&showmap=' . $mapId, wf_img('skins/icon_cleanup.png') . ' ' . $this->mapGetName($mapId), false);
                }
                foreach ($this->allMaps as $cmapId => $cmapData) {
                    if ($cmapId != $mapId and !in_array($cmapId, $activeLayers)) {
                        $result .= ' ' . wf_Link('?module=custmaps&showmap=' . $mapId . '&cl=' . $cmapId . '_' . $this->filterLayers($custLayers, $cmapId . '_'), wf_img('skins/icon_map_small.png') . ' ' . $this->mapGetName($cmapId), false);
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns existing maps list view
     * 
     * @return string
     */
    public function renderMapList() {
        $result = $this->mapListControls();

        
        $cells= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allMaps)) {
            foreach ($this->allMaps as $io => $each) {
                
                $nameLink = wf_Link('?module=custmaps&showmap=' . $each['id'], $each['name'], false);
                $cells= wf_TableCell($nameLink,'90%');
                $actLinks = '';
                if (cfr('CUSTMAPEDIT')) {
                    $deletionUrl = self::URL_ME . '&deletemap=' . $each['id'];
                    $cancelUrl = self::URL_ME;
                    $deleteTitle = __('Delete') . ' ' . $each['name'] . '?';
                    $deletionDialog = wf_ConfirmDialog($deletionUrl, web_delete_icon(), $this->messages->getDeleteAlert(), '', $cancelUrl, $deleteTitle);
                    $actLinks.= $deletionDialog . ' ';
                    $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->mapEditForm($each['id']));
                }
                $actLinks.= wf_Link('?module=custmaps&showmap=' . $each['id'], wf_img('skins/icon_map_small.png', __('Show')), false);

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
            if (cfr('CUSTMAPEDIT')) {
                $inputs.=wf_delimiter(0);
                $inputs.= wf_Submit(__('Save'));
            }
            $result.= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            throw new Exception(self::EX_NO_ITM_ID);
        }
        return ($result);
    }

    /**
     * Renders marker edit UI: form, optional photostorage link, optional additional comments.
     *
     * @param int $itemId
     *
     * @return string
     */
    public function renderMarkerEdit($itemId) {
        $result = '';
        $itemId = ubRouting::filters($itemId, 'int');
        $itemName=$this->allItems[$itemId]['name'];
        show_window(__('Edit') . ': ' . $itemName, $this->itemEditForm($itemId));

        $itemAttachControls='';
        $itemAttachments='';

        if (isset($this->altCfg['PHOTOSTORAGE_ENABLED']) and $this->altCfg['PHOTOSTORAGE_ENABLED']) {
            $photostorage = new PhotoStorage('CUSTMAPMARKERS', $itemId);
            $itemAttachControls .= wf_Link('?module=photostorage&scope=CUSTMAPMARKERS&itemid=' . $itemId . '&mode=list', wf_img('skins/photostorage.png') . ' ' . __('Upload images'), false, 'ubButton');
            $itemAttachments .= $photostorage->renderImagesRaw();
        }

        if (isset($this->altCfg['FILESTORAGE_ENABLED']) and $this->altCfg['FILESTORAGE_ENABLED']) {
            $fileStorage = new FileStorage('CUSTMAPMARKERS', $itemId);
            $callbackUrl= base64_encode(self::URL_ME . '&edititem=' . $itemId);
            $itemAttachControls .= $fileStorage->renderNavigationButton(' '.__('Upload files'), 'ubButton', '&callback=' . $callbackUrl);
            $itemAttachments .= $fileStorage->renderFilesPreview(false, '', '', 64);
        }

        //render attachments UI
        if (!empty($itemAttachControls)) {
            $attachmentsUi='';
            if (cfr('CUSTMAPEDIT')) {
              $attachmentsUi.= $itemAttachControls;
              $attachmentsUi.= wf_delimiter();
            }
            $attachmentsUi.=$itemAttachments;
            show_window('', $attachmentsUi);
        }

        //minimap here if item has geo location
        if (!empty($this->allItems[$itemId]['geo'])) {
            $findingZoom = $this->ymapsCfg['FINDING_ZOOM'];
            $miniMap = new MapCore('custmapmarkerminimap');
            $miniMap->setCenter($this->allItems[$itemId]['geo']);
            $miniMap->setZoom($findingZoom);
            $markerIcon = $this->itemGetIcon($this->allItems[$itemId]['type']);
            $markerContent = $this->itemGetTypeName($this->allItems[$itemId]['type']) . ': ' . $itemName;
            $miniMap->addMarker($this->allItems[$itemId]['geo'], $markerContent, array(
                'icon' => $markerIcon,
                'popupTitle' => $this->allItems[$itemId]['location'],
                'tooltip' => $markerContent
            ));
            show_window('', $miniMap->renderContainer('100%', '350px'));
            show_window('', $miniMap->render());
        }

        //render additional comments
        if (isset($this->altCfg['ADCOMMENTS_ENABLED']) and $this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('CUSTMAPMARKERS');
            show_window(__('Additional comments'), $adcomments->renderComments($itemId));
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
            log_register('CUSTMAPS EDIT MARKER [' . $itemid . ']');
        } else {
            throw new Exception(self::EX_NO_ITM_ID);
        }
    }

    /**
     * Returns existing map markers list as embedded datatable
     *
     * @param int $mapid
     *
     * @return string
     */
    public function renderItemsList($mapid) {
        $mapid = ubRouting::filters($mapid, 'int');
        $opts = '"order": [[ 0, "desc" ]]';
        $columns = array('ID', 'Type', 'Geo location', 'Name', 'Location', 'Actions');
        $dataArr = array();

        $adcomments = new ADcomments('CUSTMAPMARKERS');

        if (!empty($this->allItems)) {
            foreach ($this->allItems as $io => $each) {
                if ($each['mapid'] == $mapid) {
                    $indicator = $adcomments->getCommentsIndicator($each['id']);
                    $actLinks = '';
                    if (cfr('CUSTMAPEDIT')) {
                        $actLinks .= wf_JSAlertStyled('?module=custmaps&deleteitem=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                        $actLinks .= wf_Link('?module=custmaps&edititem=' . $each['id'], web_edit_icon()) . ' ';
                    } else {
                        $actLinks .= wf_Link('?module=custmaps&edititem=' . $each['id'], web_edit_icon('Show'), false) . ' ';
                    }
                    $actLinks .= $indicator;

                    $dataArr[] = array(
                        $each['id'],
                        $this->itemGetTypeName($each['type']),
                        $each['geo'],
                        $each['name'],
                        $each['location'],
                        $actLinks,
                    );
                }
            }
        }

        $result = '';
        $result .= wf_BackLink('?module=custmaps&showmap=' . $mapid);
        $result .= wf_delimiter();
        $result .= wf_JqDtEmbed($columns, $dataArr, false, 'Objects', 100, $opts);
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
            log_register('CUSTMAPS DELETE MARKER ID [' . $itemid . ']');
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
        $result = '';
        if (cfr('CUSTMAPEDIT')) {
            $result = wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Create'), __('Create new map'), $this->mapCreateForm(), 'ubButton');
            $result.= wf_delimiter();
        }
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
            log_register('CUSTMAPS FLUSH MARKERS [' . $id . ']');
            $this->linesDb->where('mapid', '=', $id);
            $this->linesDb->delete();
            log_register('CUSTMAPS FLUSH LINES [' . $id . ']');
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
        $result='';
        if (isset($this->allMaps[$id])) {   
            $result = $this->allMaps[$id]['name'];
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
        $id = ubRouting::filters($id, 'int');
        $result = '';
        if (!empty($this->allItems)) {
            foreach ($this->allItems as $io => $each) {
                if (($each['mapid'] == $id) and (!empty($each['geo']))) {
                    $icon = $this->itemGetIcon($each['type']);
                    $content = $this->itemGetTypeName($each['type']) . ': ' . $each['name'];
                    $controls = '';
                    if (cfr('CUSTMAPEDIT')) {
                        $controls = wf_Link('?module=custmaps&edititem=' . $each['id'], web_edit_icon(), false);
                    } else {
                        $controls = wf_Link('?module=custmaps&edititem=' . $each['id'], web_edit_icon('View'), false);
                    }
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
     * Parses line geometry to MapCore-compatible points array
     *
     * @param string $geo
     * @return array
     */
    protected function lineParsePoints($geo) {
        $result = array();
        $geo = trim((string) $geo);
        if (!empty($geo)) {
            $geoData = json_decode($geo, true);
            if (is_array($geoData)) {
                foreach ($geoData as $point) {
                    if (is_array($point)) {
                        if (count($point) >= 2) {
                            $lat = trim((string) $point[0]);
                            $lng = trim((string) $point[1]);
                            if ($lat !== '' and $lng !== '') {
                                $result[] = $lat . ',' . $lng;
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns random line color
     *
     * @return string
     */
    protected function lineGetRandomColor() {
        $result = self::LINE_DEFAULT_COLOR;
        $pool = array(  '#f57601',
                        '#1e88e5',
                        '#43a047',
                        '#8e24aa',
                        '#fb8c00',
                        '#e53935',
                        '#00897b',
                        '#3949ab',
                        '#6d4c41',
                        '#039be5'
                    );
        $randKey = array_rand($pool);
        if (isset($pool[$randKey])) {
            $result = $pool[$randKey];
        }
        return ($result);
    }

    /**
     * Returns line width selector options
     *
     * @return array
     */
    protected function lineGetWidthOptions() {
        $result = array();
        $index = 1;
        while ($index <= 10) {
            $result[$index] = $index;
            $index++;
        }
        return ($result);
    }

    /**
     * Returns list of map polylines
     * 
     * @param int $id
     * 
     * @return string
     */
    public function mapGetLines($id) {
        $id = ubRouting::filters($id, 'int');
        $result = '';
        if (!empty($this->allLines)) {
            foreach ($this->allLines as $lineId => $lineData) {
                if (($lineData['mapid'] == $id) and (!empty($lineData['geo']))) {
                    $points = $this->lineParsePoints($lineData['geo']);
                    if (count($points) > 1) {
                        $lineColor = !empty($lineData['style_color']) ? $lineData['style_color'] : self::LINE_DEFAULT_COLOR;
                        $lineWeight = !empty($lineData['style_width']) ? $lineData['style_width'] : self::LINE_DEFAULT_WIDTH;
                        $lineFibers = self::LINE_DEFAULT_FIBERS_AMOUNT;
                        if (isset($lineData['fibers_amount']) and $lineData['fibers_amount'] !== '' and $lineData['fibers_amount'] !== null) {
                            $lineFibers = $lineData['fibers_amount'];
                        }
                        $content = __('Fibers amount') . ': ' . $lineFibers . '<br>';
                        $content.= __('Length') . ': ' . $lineData['length_m'] . '<br>';
                        $content.= __('Description') . ': ' . $lineData['description'] . '<br>';
                        if (cfr('CUSTMAPEDIT')) {
                            $content.= wf_Link('?module=custmaps&showmap=' . $id . '&lineedit=true&editline=' . $lineId, web_edit_icon(), false);
                        }
                        $title = $lineData['name'];
                        $this->mapCore->addPolyline($points, $content, array(
                            'color' => $lineColor,
                            'weight' => $lineWeight,
                            'hint' => __('Line') . ': ' . $lineData['name'],
                            'popupTitle' => $title,
                            'lineId' => $lineId,
                            'meta' => array(
                                'name' => $lineData['name'],
                                'fibers_amount' => $lineFibers,
                                'length_m' => $lineData['length_m'],
                                'style_color' => $lineColor,
                                'style_width' => $lineWeight,
                                'description' => $lineData['description']
                            )
                        ));
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns line edit form
     * TODO: make it better later
     * @param int $lineid
     * @return string
     */
    public function lineEditForm($lineid) {
        $lineid = ubRouting::filters($lineid, 'int');
        $result = '';
        if (isset($this->allLines[$lineid])) {
            $lineData = $this->allLines[$lineid];
            $result.= wf_BackLink('?module=custmaps&showlines=' . $lineData['mapid']);
            $result.= wf_delimiter();
            $inputs = wf_HiddenInput('editlineid', $lineid);
            $inputs.= wf_TextInput('editline_name', __('Name'), $lineData['name'], false, 25);
            $inputs.= wf_Selector('editline_fibers_amount', $this->lineGetFibersAmountOptions(), __('Fibers amount'), $lineData['fibers_amount'], true);
            $inputs.= wf_TextInput('editline_length_m', __('Length'), $lineData['length_m'], true, 25);
            $inputs.= wf_ColorInput('editline_style_color', __('Color'), $lineData['style_color'], true);
            $inputs.= wf_Selector('editline_style_width', $this->lineGetWidthOptions(), __('Line width'), $lineData['style_width'], true);
            $inputs.= wf_TextInput('editline_description', __('Description'), $lineData['description'], false, 25);
            $inputs.= wf_TextArea('editline_geo', __('Geometry') . ' [[lat,lng],[lat,lng]]', $lineData['geo'], true, '35x4');
            $inputs.= wf_Submit(__('Save'));
            $result.= wf_Form('', 'POST', $inputs, '');
        } else {
            throw new Exception(self::EX_NO_LINE_ID);
        }
        return ($result);
    }

    /**
     * Renders existing map lines list as embedded datatable
     *
     * @param int $mapid
     * @return string
     */
    public function renderLinesList($mapid) {
        $mapid = ubRouting::filters($mapid, 'int');
        $opts = '"order": [[ 0, "desc" ]]';
        $columns = array('ID', 'Name', 'Fibers', __('Length'). ' (' . __('m') . ')', 'Actions');
        $dataArr = array();

        if (!empty($this->allLines)) {
            foreach ($this->allLines as $lineId => $lineData) {
                if ($lineData['mapid'] == $mapid) {
                    $actLinks = '';
                    if (cfr('CUSTMAPEDIT')) {
                        $actLinks .= wf_JSAlertStyled('?module=custmaps&deleteline=' . $lineId, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                        $actLinks .= wf_JSAlertStyled('?module=custmaps&showmap=' . $mapid . '&lineedit=true&editline=' . $lineId, web_edit_icon(), $this->messages->getEditAlert()) . ' ';
                    }

                    $dataArr[] = array(
                        $lineId,
                        $lineData['name'],
                        $lineData['fibers_amount'],
                        round($lineData['length_m']),
                        $actLinks,
                    );
                }
            }
        }

        $result = '';
        $result .= wf_BackLink('?module=custmaps&showmap=' . $mapid);
        $result .= wf_delimiter();
        $result .= wf_JqDtEmbed($columns, $dataArr, false, 'Lines', 100, $opts);
        return ($result);
    }

    /**
     * Creates new map line in database
     *
     * @param int $mapid
     * @param string $name
     * @param int $fibersAmount
     * @param string $length
     * @param string $color
     * @param int $width
     * @param string $description
     * @param string $geo
     */
    public function lineCreate($mapid, $name, $fibersAmount, $length, $color, $width, $description, $geo) {
        $mapid = ubRouting::filters($mapid, 'int');
        $name = ubRouting::filters($name, 'mres');
        $fibersAmount = ubRouting::filters($fibersAmount, 'int');
        $length = ubRouting::filters($length, 'mres');
        $color = ubRouting::filters($color, 'mres');
        $width = ubRouting::filters($width, 'int');
        $description = ubRouting::filters($description, 'mres');
        $geo = ubRouting::filters($geo, 'mres');
        if (isset($this->allMaps[$mapid])) {
            $this->linesDb->data('mapid', $mapid);
            $this->linesDb->data('name', $name);
            $this->linesDb->data('fibers_amount', $fibersAmount);
            $this->linesDb->data('length_m', $length);
            $this->linesDb->data('style_color', $color);
            $this->linesDb->data('style_width', $width);
            $this->linesDb->data('description', $description);
            $this->linesDb->data('geo', $geo);
            $this->linesDb->data('created_at', curdatetime());
            $this->linesDb->data('updated_at', curdatetime());
            $this->linesDb->create();
            $newId = $this->linesDb->getLastId();
            log_register('CUSTMAPS CREATE LINE `' . $name . '` ID [' . $newId . ']');
        } else {
            throw new Exception(self::EX_NO_MAP_ID);
        }
    }

    /**
     * Changes existing line in database
     *
     * @param int $lineid
     * @param string $name
     * @param int $fibersAmount
     * @param string $length
     * @param string $color
     * @param int $width
     * @param string $description
     * @param string $geo
     */
    public function lineEdit($lineid, $name, $fibersAmount, $length, $color, $width, $description, $geo) {
        $lineid = ubRouting::filters($lineid, 'int');
        $name = ubRouting::filters($name, 'mres');
        $fibersAmount = ubRouting::filters($fibersAmount, 'int');
        $length = ubRouting::filters($length, 'mres');
        $color = ubRouting::filters($color, 'mres');
        $width = ubRouting::filters($width, 'int');
        $description = ubRouting::filters($description, 'mres');
        $geo = ubRouting::filters($geo, 'mres');
        if (isset($this->allLines[$lineid])) {
            $this->linesDb->data('name', $name);
            $this->linesDb->data('fibers_amount', $fibersAmount);
            $this->linesDb->data('length_m', $length);
            $this->linesDb->data('style_color', $color);
            $this->linesDb->data('style_width', $width);
            $this->linesDb->data('description', $description);
            $this->linesDb->data('geo', $geo);
            $this->linesDb->data('updated_at', curdatetime());
            $this->linesDb->where('id', '=', $lineid);
            $this->linesDb->save(true, true);
            log_register('CUSTMAPS EDIT LINE [' . $lineid . ']');
        } else {
            throw new Exception(self::EX_NO_LINE_ID);
        }
    }

    /**
     * Deletes line by id
     *
     * @param int $lineid
     * @return int
     */
    public function lineDelete($lineid) {
        $lineid = ubRouting::filters($lineid, 'int');
        $result = '';
        if (isset($this->allLines[$lineid])) {
            $result = $this->allLines[$lineid]['mapid'];
            $this->linesDb->where('id', '=', $lineid);
            $this->linesDb->delete();
            log_register('CUSTMAPS DELETE LINE ID [' . $lineid . ']');
        } else {
            throw new Exception(self::EX_NO_LINE_ID);
        }
        return ($result);
    }

    /**
     * Returns line by id
     *
     * @param int $lineid
     * @return array
     */
    public function lineGetById($lineid) {
        $lineid = ubRouting::filters($lineid, 'int');
        $result = array();
        if (isset($this->allLines[$lineid])) {
            $result = $this->allLines[$lineid];
        } else {
            throw new Exception(self::EX_NO_LINE_ID);
        }
        return ($result);
    }

    /**
     * Returns all lines for selected map
     *
     * @param int $mapid
     * @return array
     */
    public function lineGetAllByMap($mapid) {
        $mapid = ubRouting::filters($mapid, 'int');
        $result = array();
        if (!empty($this->allLines)) {
            foreach ($this->allLines as $lineId => $lineData) {
                if ($lineData['mapid'] == $mapid) {
                    $result[$lineId] = $lineData;
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
            log_register('CUSTMAPS CREATE MARKER `' . $name . '` ID [' . $newId . ']');
        } else {
            throw new Exception(self::EX_NO_MAP_ID);
        }
    }

    /**
     * Returns initialized JS map
     * 
     * @return string
     */
    public function mapInit() {
        $result = $this->mapControls();
        $result .= $this->mapCore->renderContainer('100%', '650px');
        $this->mapCore->setCenter($this->center);
        $this->mapCore->setZoom($this->zoom);
        $this->mapCore->setType($this->ymapsCfg['TYPE']);
        $result .= $this->mapCore->render();
        $result .= $this->mapLayersControls();
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

    /**
     * Returns fibers amount options for line editor
     * 
     * @return array
     */
    protected function lineGetFibersAmountOptions() {
        $result = array(
            '0'=>'-',
            '1'=>'1',
            '2'=>'2',
            '4'=>'4',
            '8'=>'8',
            '12'=>'12',
            '24'=>'24',
            '48'=>'48',
            '96'=>'96',
        );
        return ($result);
    }

    /**
     * Returns line location form for map editor
     *
     * @param int $mapid
     * @return string
     */
    protected function lineLocationForm($mapid) {
        $mapid = ubRouting::filters($mapid, 'int');
        $lineColor = $this->lineGetRandomColor();
        $inputs = wf_HiddenInput('newline_mapid', $mapid);
        $inputs.= wf_HiddenInput('newline_lineid', '');
        $inputs.= wf_HiddenInput('newline_geo', '');
        $inputs.= wf_TextInput('newline_name', __('Name'), '', true, 20);
        $inputs.= wf_Selector('newline_fibers_amount', $this->lineGetFibersAmountOptions(), __('Fibers amount'), strval(self::LINE_DEFAULT_FIBERS_AMOUNT), true);
        $inputs.= wf_TextInput('newline_length_m', __('Length'), '0', true, 8);
        $inputs.= wf_ColorInput('newline_style_color', __('Color'), $lineColor, true, 'ubLineEditorColor_' . $mapid);
        $inputs.= wf_Selector('newline_style_width', $this->lineGetWidthOptions(), __('Line width'), strval(self::LINE_DEFAULT_WIDTH), true);
        $inputs.= wf_TextInput('newline_description', __('Description'), '', false, 20);
        $inputs.= wf_tag('br');
        $inputs.= wf_Submit(__('Save line'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Adds line editor on map for Leaflet.Editable-based polyline creation and editing
     *
     * @param int $mapid
     * @return string
     */
    public function mapLineEditor($mapid) {
        $mapid = ubRouting::filters($mapid, 'int');
        $title = wf_tag('b') . __('Line editor') . wf_tag('b', true);
        $data = $this->lineLocationForm($mapid);
        $drawBtnId = 'ubLineEditorDrawBtn_' . $mapid;
        $finishBtnId = 'ubLineEditorFinishBtn_' . $mapid;
        $cancelBtnId = 'ubLineEditorCancelBtn_' . $mapid;
        $undoBtnId = 'ubLineEditorUndoBtn_' . $mapid;
        $panelHtml = $title;
        $panelHtml .= '<div style="display:flex; flex-direction:column; gap:4px; margin:8px 0;">';
        $hkLabel = __('Hotkey');
        $panelHtml .= '<button type="button" id="' . $drawBtnId . '" class="ubButton" style="width:100%; text-align:left;" title="' . $hkLabel . ': N">' . wf_img('skins/add_icon.png') . ' ' . __('New line') . '</button>';
        $panelHtml .= '<button type="button" id="' . $finishBtnId . '" class="ubButton" style="width:100%; text-align:left;" title="' . $hkLabel . ': Ctrl+Enter">' . wf_img('skins/done_icon.png') . ' ' . __('Finish line') . '</button>';
        $panelHtml .= '<button type="button" id="' . $cancelBtnId . '" class="ubButton" style="width:100%; text-align:left;" title="' . $hkLabel . ': Esc">' . wf_img('skins/undone_icon.png') . ' ' . __('Cancel drawing') . '</button>';
        $panelHtml .= '<button type="button" id="' . $undoBtnId . '" class="ubButton" style="width:100%; text-align:left;" title="' . $hkLabel . ': Ctrl+Z">' . wf_img('skins/undo_icon.png') . ' ' . __('Undo changes') . '</button>';
        $panelHtml .= '</div>';
        $panelHtml .= $data;
        $lineEditorConfig = array(
            'panelHtml' => $panelHtml,
            'drawBtnId' => $drawBtnId,
            'finishBtnId' => $finishBtnId,
            'cancelBtnId' => $cancelBtnId,
            'undoBtnId' => $undoBtnId,
            'initialEditLineId' => (ubRouting::checkGet('editline') ? ubRouting::get('editline', 'int') : 0),
            'defaultColor' => self::LINE_DEFAULT_COLOR,
            'defaultFibersAmount' => self::LINE_DEFAULT_FIBERS_AMOUNT,
            'defaultWidth' => self::LINE_DEFAULT_WIDTH,
        );
        $lineEditorConfigJs = json_encode($lineEditorConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if ($lineEditorConfigJs === false) {
            $lineEditorConfigJs = '{}';
        }
        $this->mapCore->addScriptSrc(self::LINE_EDITOR_LIB);
        $lineEditorJs = '
            (function() {
                if (typeof ubCustmapsLineEditorInit !== "function") {
                    if (window.console && typeof console.warn === "function") {
                        console.warn("CustMaps: line-editor.js is not loaded");
                    }
                    return;
                }
                ubCustmapsLineEditorInit(map, ' . $lineEditorConfigJs . ');
            })();
        ';
        $this->mapCore->addRawJs($lineEditorJs);
        $result = '';
        return ($result);
    }

}
