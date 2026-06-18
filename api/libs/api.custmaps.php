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
    const MARKERS_TOGGLE_LIB = 'modules/jsc/custmaps/markers-toggle.js';
    const KML_IMPORT_TMP_PATH = 'exports/';

    const ROUTE_SHOWMAP = 'showmap';
    const ROUTE_DELETEMAP = 'deletemap';
    const ROUTE_SHOWITEMS = 'showitems';
    const ROUTE_SHOWLINES = 'showlines';
    const ROUTE_DELETEITEM = 'deleteitem';
    const ROUTE_EDITITEM = 'edititem';
    const ROUTE_MODIFYLINE = 'modifyline';
    const ROUTE_EDITLINE = 'editline';
    const ROUTE_DELETELINE = 'deleteline';
    const ROUTE_MARKEREDIT = 'markersedit';
    const ROUTE_LINEEDIT = 'lineedit';
    const ROUTE_CL = 'cl';
    const ROUTE_MAPLIST = 'maplist';
    const ROUTE_MAPCONFIG = 'mapconfig';
    const ROUTE_KMLIMPORT = 'kmlimport';
    const ROUTE_KMLIMPORT_OK = 'kmlimport_ok';
    const ROUTE_KMLEXPORT = 'kmlexport';

    const PROUTE_NEWMAPNAME = 'newmapname';
    const PROUTE_EDITMAPID = 'editmapid';
    const PROUTE_EDITMAPNAME = 'editmapname';
    const PROUTE_NEWMAP_CLUSTERING = 'newmap_clustering';
    const PROUTE_NEWMAP_CMARKERS = 'newmap_cmarkers';
    const PROUTE_NEWMAP_METRICS = 'newmap_metrics';
    const PROUTE_EDITMAP_CLUSTERING = 'editmap_clustering';
    const PROUTE_EDITMAP_CMARKERS = 'editmap_cmarkers';
    const PROUTE_EDITMAP_METRICS = 'editmap_metrics';
    const PROUTE_NEWITEMGEO = 'newitemgeo';
    const PROUTE_NEWITEMTYPE = 'newitemtype';
    const PROUTE_NEWITEMNAME = 'newitemname';
    const PROUTE_NEWITEMLOCATION = 'newitemlocation';
    const PROUTE_NEWLINE_MAPID = 'newline_mapid';
    const PROUTE_NEWLINE_LINEID = 'newline_lineid';
    const PROUTE_NEWLINE_STYLE_WIDTH = 'newline_style_width';
    const PROUTE_NEWLINE_STYLE_COLOR = 'newline_style_color';
    const PROUTE_NEWLINE_GEO = 'newline_geo';
    const PROUTE_NEWLINE_NAME = 'newline_name';
    const PROUTE_NEWLINE_FIBERS_AMOUNT = 'newline_fibers_amount';
    const PROUTE_NEWLINE_LENGTH_M = 'newline_length_m';
    const PROUTE_NEWLINE_DESCRIPTION = 'newline_description';
    const PROUTE_EDITITEMID = 'edititemid';
    const PROUTE_EDITITEMTYPE = 'edititemtype';
    const PROUTE_EDITITEMGEO = 'edititemgeo';
    const PROUTE_EDITITEMNAME = 'edititemname';
    const PROUTE_EDITITEMLOCATION = 'edititemlocation';
    const PROUTE_EDITLINEID = 'editlineid';
    const PROUTE_EDITLINE_NAME = 'editline_name';
    const PROUTE_EDITLINE_FIBERS_AMOUNT = 'editline_fibers_amount';
    const PROUTE_EDITLINE_LENGTH_M = 'editline_length_m';
    const PROUTE_EDITLINE_STYLE_COLOR = 'editline_style_color';
    const PROUTE_EDITLINE_STYLE_WIDTH = 'editline_style_width';
    const PROUTE_EDITLINE_DESCRIPTION = 'editline_description';
    const PROUTE_EDITLINE_GEO = 'editline_geo';
    const PROUTE_KMLIMPORT = 'kmlimport';
    const PROUTE_KMLIMPORT_FILE = 'custmaps_kml_upload';
    const PROUTE_KMLIMPORT_ITEMTYPE = 'kmlimport_itemtype';

    public function __construct() {
        $this->setShowMapId();
        $this->initMessages();
        $this->loadYmapsConfig();
        $this->initDb();
        $this->loadAlterConfig();
        $this->setDefaults();
        $this->setItemTypes();
        $this->loadMaps();
        $this->loadItems();
        $this->loadLines();
        $this->initMapCore();
    }

    /**
     * URL of the custom maps list screen (module root)
     *
     * @return string
     */
    public static function urlMapList() {
        $result = self::URL_ME . '&' . self::ROUTE_MAPLIST . '=true';
        return ($result);
    }

    /**
     * URL of the map configuration screen (name, clustering, etc.)
     *
     * @param int $id
     *
     * @return string
     */
    public static function urlMapConfig($id) {
        $id = ubRouting::filters($id, 'int');
        $result = self::URL_ME . '&' . self::ROUTE_MAPCONFIG . '=' . $id;
        return ($result);
    }

    /**
     * URL of the KML import screen
     *
     * @return string
     */
    public static function urlKmlImport() {
        $result = self::URL_ME . '&' . self::ROUTE_KMLIMPORT . '=true';
        return ($result);
    }

    /**
     * URL of the KML export download for map
     *
     * @param int $id
     *
     * @return string
     */
    public static function urlMapKmlExport($id) {
        $id = ubRouting::filters($id, 'int');
        $result = self::URL_ME . '&' . self::ROUTE_KMLEXPORT . '=' . $id;
        return ($result);
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
        if (ubRouting::checkGet(self::ROUTE_SHOWMAP)) {
            $mapId = ubRouting::get(self::ROUTE_SHOWMAP, 'int');
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

        //creating map core instance / global config
        $this->mapCore = new MapCore($containerId);
        $useClustering = false;
        $useCmarkers = false;
        $useMetrics = false;
        if ($this->showMapId) {
            if (isset($this->allMaps[$this->showMapId])) {
                $mapRow = $this->allMaps[$this->showMapId];
                if (isset($mapRow['clustering']) and intval($mapRow['clustering'])) {
                    $useClustering = true;
                }
                if (isset($mapRow['cmarkers']) and intval($mapRow['cmarkers'])) {
                    $useCmarkers = true;
                }
                if (isset($mapRow['metrics']) and intval($mapRow['metrics'])) {
                    $useMetrics = true;
                }
            }
        }

        if ($useClustering) {
            $this->mapCore->setClustering(true, $this->clustringOptions);
        }
        if ($useCmarkers) {
            $this->mapCore->setForceCanvasMarkers(true);
        }
        if ($useMetrics) {
            $this->mapCore->setFpsMeter(true);
        }

        //force saving state of each map
        $this->mapCore->setRememberZoom($rememberZoom);
        $this->mapCore->setRememberPosition($rememberPosition);
        if ($this->showMapId) {
            $this->mapCore->setMarkerToggleShellEnabled(true);
        }
        
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
            'manhole' => __('Manhole'),
            'coupling' => __('Coupling'),
            'node' => __('Node'), 
            'box' => __('Box'), 
            'amplifier' => __('Amplifier'),
            'optrec' => __('Optical reciever'), 
            'camera' => __('Camera'), 
            'wifi' => __('WiFi'),
            'waterfall' => __('Waterfall'), 
            'other' => __('Other'),
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
            case 'manhole':
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
            case 'other':
                $result = 'marker.blue';
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
     * Renders attachments indicators with hidden sort key for datatables
     *
     * @param int $itemId
     * @param ADcomments $adcomments
     * @param PhotoStorage $photostorage
     * @param FileStorage $fileStorage
     *
     * @return string
     */
    protected function renderAttachmentsCell($itemId, $adcomments, $photostorage, $fileStorage) {
        $commentsCount = $adcomments->getCommentsCount($itemId);
        $imagesCount = $photostorage->getImagesCount($itemId);
        $filesCount = $fileStorage->getFilesCount($itemId);
        $sortKey = str_pad($commentsCount, 5, '0', STR_PAD_LEFT)
            . str_pad($imagesCount, 5, '0', STR_PAD_LEFT)
            . str_pad($filesCount, 5, '0', STR_PAD_LEFT);
        $indicators = ' ' . $adcomments->getCommentsIndicator($itemId)
            . ' ' . $photostorage->getImagesIndicator($itemId)
            . ' ' . $fileStorage->getFilesIndicator($itemId);
        $result = wf_tag('span', false, '', 'style="display:none;"') . $sortKey . wf_tag('span', true) . $indicators;
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
        $result .= wf_BackLink(self::urlMapList());
        if (ubRouting::checkGet(self::ROUTE_SHOWMAP)) {
            $mapId = ubRouting::get(self::ROUTE_SHOWMAP, 'int');
            if (cfr('CUSTMAPEDIT')) {
                $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $mapId . '&' . self::ROUTE_MARKEREDIT . '=true', wf_img('skins/ymaps/target.png') . ' ' . __('Edit markers'), false, 'ubButton');
                $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $mapId . '&' . self::ROUTE_LINEEDIT . '=true', wf_img('skins/ymaps/edit.png') . ' ' . __('Edit lines'), false, 'ubButton');
            }

            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SHOWITEMS . '=' . $mapId, wf_img('skins/icon_mapplacemark16.png') . ' ' . __('Markers'), false, 'ubButton');
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SHOWLINES . '=' . $mapId, wf_img('skins/icon_mapline16.png') . ' ' . __('Lines'), false, 'ubButton');
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
        if (ubRouting::checkGet(self::ROUTE_SHOWMAP)) {
            $mapId = ubRouting::get(self::ROUTE_SHOWMAP, 'int');
            if (ubRouting::checkGet(self::ROUTE_CL)) {
                $custLayers = ubRouting::get(self::ROUTE_CL);
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
                    $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $mapId, wf_img('skins/icon_cleanup.png') . ' ' . $this->mapGetName($mapId), false);
                }
                foreach ($this->allMaps as $cmapId => $cmapData) {
                    if ($cmapId != $mapId and !in_array($cmapId, $activeLayers)) {
                        $result .= ' ' . wf_Link(self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $mapId . '&' . self::ROUTE_CL . '=' . $cmapId . '_' . $this->filterLayers($custLayers, $cmapId . '_'), wf_img('skins/icon_map_small.png') . ' ' . $this->mapGetName($cmapId), false);
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

        if (!empty($this->allMaps)) {
        $cells= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allMaps as $io => $each) {
                
                $nameLink = wf_Link(self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $each['id'], $each['name'], false);
                $cells= wf_TableCell($nameLink,'90%');
                $actLinks = '';
                if (cfr('CUSTMAPEDIT')) {
                    $actLinks.= wf_Link(self::urlMapConfig($each['id']), web_icon_extended(__('Configure map')), false) . ' ';
                }
                $actLinks.= wf_Link(self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $each['id'], wf_img('skins/icon_map_small.png', __('Show')), false);

                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }
        

        $result.= wf_TableBody($rows, '100%', '0', 'sortable');
        } else {
            $result.= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
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
            $inputs = wf_HiddenInput(self::PROUTE_EDITITEMID, $itemid);
            $inputs.= wf_Selector(self::PROUTE_EDITITEMTYPE, $this->itemTypes, __('Type'), $this->allItems[$itemid]['type'], true);
            $inputs.= wf_TextInput(self::PROUTE_EDITITEMGEO, __('Geo location'), $this->allItems[$itemid]['geo'], true, '20', 'geo');
            $inputs.= wf_TextInput(self::PROUTE_EDITITEMNAME, __('Name'), $this->allItems[$itemid]['name'], true, '20');
            $inputs.= wf_TextInput(self::PROUTE_EDITITEMLOCATION, __('Location'), $this->allItems[$itemid]['location'], true, '20');
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
     * Renders marker edit UI: edit form and minimap side by side in a flex row (when geo is set),
     *
     * @param int $itemId
     *
     * @return string
     */
    public function renderMarkerEdit($itemId) {
        $result = '';
        $itemId = ubRouting::filters($itemId, 'int');
        $itemName = $this->allItems[$itemId]['name'];
        $editForm = $this->itemEditForm($itemId);
        $miniMapBlock = '';
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
            $miniMapBlock = $miniMap->renderContainer('100%', '300px') . $miniMap->render();
        }

        $mainWindowContent = '';
        if (!empty($this->allItems[$itemId]['geo'])) {
            $splitWrapOpts = 'style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:12px;width:100%;box-sizing:border-box;"';
            $colBorder = 'border:1px solid #d8d8d8;';
            $formColOpts = 'style="flex:0 0 auto;min-width:220px;max-width:100%;box-sizing:border-box;' . $colBorder . 'padding:8px;border-radius:2px;"';
            $mapColOpts = 'style="flex:1 1 0%;min-width:0;max-width:100%;box-sizing:border-box;' . $colBorder . 'padding:8px;border-radius:2px;"';
            $formCol = wf_tag('div', false, '', $formColOpts) . $editForm . wf_tag('div', true);
            $mapCol = wf_tag('div', false, '', $mapColOpts) . $miniMapBlock . wf_tag('div', true);
            $mainWindowContent = wf_tag('div', false, '', $splitWrapOpts) . $formCol . $mapCol . wf_tag('div', true);
        } else {
            $mainWindowContent = $editForm;
        }
        show_window(__('Edit') . ': ' . $itemName, $mainWindowContent);

        $itemControls = '';
        if (isset($this->allItems[$itemId])) {
            $markersUrl = self::URL_ME . '&' . self::ROUTE_SHOWITEMS . '=' . $this->allItems[$itemId]['mapid'];
            $mapUrl=self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $this->allItems[$itemId]['mapid'];
            $itemControls .= wf_Link($mapUrl,wf_img('skins/icon_map_small.png').' '.__('Back').' '.__('to map'), false, 'ubButton');
            $itemControls .= wf_Link($markersUrl,wf_img('skins/icon_mapplacemark16.png').' '.__('Back').' '.__('to markers'), false, 'ubButton');
        }

        $itemUploadControls = '';
        $itemAttachments = '';

        if (isset($this->altCfg['PHOTOSTORAGE_ENABLED']) and $this->altCfg['PHOTOSTORAGE_ENABLED']) {
            $photostorage = new PhotoStorage('CUSTMAPMARKERS', $itemId);
            if (cfr('CUSTMAPEDIT')) {
                $itemUploadControls .= wf_Link('?module=photostorage&scope=CUSTMAPMARKERS&itemid=' . $itemId . '&mode=list', wf_img('skins/photostorage.png') . ' ' . __('Upload images'), false, 'ubButton');
            }
            $itemAttachments .= $photostorage->renderImagesRaw();
        }

        if (isset($this->altCfg['FILESTORAGE_ENABLED']) and $this->altCfg['FILESTORAGE_ENABLED']) {
            $fileStorage = new FileStorage('CUSTMAPMARKERS', $itemId);
            if (cfr('CUSTMAPEDIT')) {
                $callbackUrl = base64_encode(self::URL_ME . '&' . self::ROUTE_EDITITEM . '=' . $itemId);
                $itemUploadControls .= $fileStorage->renderNavigationButton(' ' . __('Upload files'), 'ubButton', '&callback=' . $callbackUrl);
            }
            $itemAttachments .= $fileStorage->renderFilesPreview(false, '', '', 64);
        }

        //render attachments UI
        $attachmentsUi = '';
        $attachmentsUi .= $itemControls;
        if (!empty($itemUploadControls)) {
            $attachmentsUi .= $itemUploadControls;
            $attachmentsUi .= wf_delimiter();
        } else {
            if (!empty($itemAttachments)) {
                $attachmentsUi .= wf_delimiter();
            }
        }
        $attachmentsUi .= $itemAttachments;

        if (!empty($attachmentsUi)) {
            show_window('', $attachmentsUi);
        }

        //render additional comments
        if (isset($this->altCfg['ADCOMMENTS_ENABLED']) and $this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('CUSTMAPMARKERS');
            show_window(__('Additional comments'), $adcomments->renderComments($itemId));
        }

        if (cfr('CUSTMAPEDIT') and isset($this->allItems[$itemId])) {
            $deletionUrl = self::URL_ME . '&' . self::ROUTE_DELETEITEM . '=' . $itemId;
            $cancelUrl = self::URL_ME . '&' . self::ROUTE_EDITITEM . '=' . $itemId;
            $deleteTitle = __('Delete') . ' ' . $itemName . '?';
            $deleteUi = wf_delimiter(0);
            $deleteUi .= wf_ConfirmDialog($deletionUrl, web_delete_icon() . ' ' . __('Delete marker'), $this->messages->getDeleteAlert(), 'ubButton', $cancelUrl, $deleteTitle);
            show_window('', $deleteUi);
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
        $columns = array('ID', 'Type', 'Geo location', 'Name', 'Location', 'Other' ,'Actions');
        $dataArr = array();

        $adcomments = new ADcomments('CUSTMAPMARKERS');
        $photostorage = new PhotoStorage('CUSTMAPMARKERS');
        $fileStorage = new FileStorage('CUSTMAPMARKERS');

        if (!empty($this->allItems)) {
            foreach ($this->allItems as $io => $each) {
                if ($each['mapid'] == $mapid) {
                    $actLinks = '';
                    if (cfr('CUSTMAPEDIT')) {
                        $actLinks .= wf_Link(self::URL_ME . '&' . self::ROUTE_EDITITEM . '=' . $each['id'], web_icon_extended(__('Change'))) . ' ';
                    } else {
                        $actLinks .= wf_Link(self::URL_ME . '&' . self::ROUTE_EDITITEM . '=' . $each['id'], web_icon_extended(__('Show')), false) . ' ';
                    }
                    $attachments = $this->renderAttachmentsCell($each['id'], $adcomments, $photostorage, $fileStorage);

                    $dataArr[] = array(
                        $each['id'],
                        $this->itemGetTypeName($each['type']),
                        $each['geo'],
                        $each['name'],
                        $each['location'],
                        $attachments,
                        $actLinks,
                    );
                }
            }
        }

        $result = '';
        $result .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $mapid);
        $result .= wf_delimiter();
        $result .= wf_JqDtEmbed($columns, $dataArr, false, 'Markers', 100, $opts);
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
        $inputs = wf_TextInput(self::PROUTE_NEWMAPNAME, __('Name'), '', true, '30');
        $inputs .= wf_CheckInput(self::PROUTE_NEWMAP_CLUSTERING, __('Force clustering'), true, false);
        $inputs .= wf_CheckInput(self::PROUTE_NEWMAP_CMARKERS, __('Force canvas markers'), true, false);
        $inputs .= wf_CheckInput(self::PROUTE_NEWMAP_METRICS, __('Force map metrics'), true, false);
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns KML import upload form
     *
     * @return string
     */
    protected function mapKmlImportForm() {
        $postUrl = self::urlKmlImport();
        $inputs = wf_tag('form', false, 'photostorageuploadform', 'action="' . $postUrl . '" enctype="multipart/form-data" method="POST"');
        $inputs .= wf_tag('input', false, '', 'type="file" name="' . self::PROUTE_KMLIMPORT_FILE . '" accept=".kml,.kmz"');
        $inputs .= wf_Selector(self::PROUTE_KMLIMPORT_ITEMTYPE, $this->itemTypes, __('Markers type'), 'box', true);
        $inputs .= wf_HiddenInput(self::PROUTE_KMLIMPORT, '1');
        $inputs .= wf_Submit(__('Import'));
        $inputs .= wf_tag('form', true);
        $result = $inputs;
        return ($result);
    }

    /**
     * Renders KML import page with upload form
     *
     * @return string
     */
    public function renderKmlImportPage() {
        $result = wf_BackLink(self::urlMapList());
        $result .= wf_delimiter();
        $result .= $this->mapKmlImportForm();
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
        $mapRow = $this->allMaps[$id];
        $clustChk = (isset($mapRow['clustering']) and intval($mapRow['clustering'])) ? true : false;
        $cmarkersChk = (isset($mapRow['cmarkers']) and intval($mapRow['cmarkers'])) ? true : false;
        $metricsChk = (isset($mapRow['metrics']) and intval($mapRow['metrics'])) ? true : false;
        $inputs = wf_TextInput(self::PROUTE_EDITMAPNAME, __('Name'), $mapRow['name'], true, '30');
        $inputs .= wf_CheckInput(self::PROUTE_EDITMAP_CLUSTERING, __('Force clustering'), true, $clustChk);
        $inputs .= wf_CheckInput(self::PROUTE_EDITMAP_CMARKERS, __('Force canvas markers'), true, $cmarkersChk);
        $inputs .= wf_CheckInput(self::PROUTE_EDITMAP_METRICS, __('Force map metrics'), true, $metricsChk);
        $inputs.= wf_HiddenInput(self::PROUTE_EDITMAPID, $id);
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Map settings page: back link and edit form  
     *
     * @param int $id
     *
     * @return string
     */
    public function renderMapConfigPage($id) {
        $result = '';
        $id = ubRouting::filters($id, 'int');
        if (isset($this->allMaps[$id])) {
            $result .= wf_BackLink(self::urlMapList());
            if (cfr('CUSTMAPEDIT')) {
                $result .= wf_Link(self::urlMapKmlExport($id), wf_img('skins/icon_download.png') . ' ' . __('Export'), false, 'ubButton');
            }
            $result .= wf_delimiter();
            $result .= $this->mapEditForm($id);
            if (cfr('CUSTMAPEDIT')) {
                $result .= wf_delimiter(0);
                $deletionUrl = self::URL_ME . '&' . self::ROUTE_DELETEMAP . '=' . $id;
                $cancelUrl = self::urlMapConfig($id);
                $mapName = $this->mapGetName($id);
                $deleteTitle = __('Delete') . ' ' . $mapName . '?';
                $result .= wf_ConfirmDialog($deletionUrl, web_delete_icon().' '.__('Delete map'), $this->messages->getDeleteAlert(), 'ubButton', $cancelUrl, $deleteTitle);
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }
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
            $result .= wf_Link(self::urlKmlImport(), wf_img('skins/icon_puzzle.png') . ' ' . __('Import'), false, 'ubButton');

            if (cfr('ROOT')) {
                if (@$this->altCfg['VOLS_ENABLED']) {
                 $result .= wf_Link('?module=volzconvert', wf_img('skins/icon_puzzle.png') .' '.__('Migration'). ' ' . __('VOLS'), false, 'ubButton');
                }
            }
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
        $clustering = ubRouting::checkPost(self::PROUTE_NEWMAP_CLUSTERING) ? 1 : 0;
        $cmarkers = ubRouting::checkPost(self::PROUTE_NEWMAP_CMARKERS) ? 1 : 0;
        $metrics = ubRouting::checkPost(self::PROUTE_NEWMAP_METRICS) ? 1 : 0;
        $this->mapsDb->data('name', $nameFiltered);
        $this->mapsDb->data('clustering', $clustering);
        $this->mapsDb->data('cmarkers', $cmarkers);
        $this->mapsDb->data('metrics', $metrics);
        $this->mapsDb->create();
        $newId = $this->mapsDb->getLastId();
        log_register('CUSTMAPS CREATE MAP `' . $name . '` ID [' . $newId . ']');
    }

    /**
     * Imports KML/KMZ file into a new custom map
     *
     * @param string $tmpPath readable path to uploaded file in exports/
     * @param string $originalFilename original client filename
     * @param string $markerType CustMaps marker type key for imported points
     *
     * @return array keys: success, error, map_id, marks, lines
     */
    public function mapImportFromKml($tmpPath, $originalFilename, $markerType) {
        $result = array(
            'success' => false,
            'error' => '',
            'map_id' => 0,
            'marks' => 0,
            'lines' => 0,
        );
        $mapId = 0;
        $tmpPath = trim((string) $tmpPath);
        $originalFilename = trim((string) $originalFilename);
        if ($tmpPath === '' or !is_readable($tmpPath) or !is_file($tmpPath)) {
            $result['error'] = __('File upload failed');
        } else {
            $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            if ($ext !== 'kml' and $ext !== 'kmz') {
                $result['error'] = __('Wrong file type');
            } else {
                $markerType = ubRouting::filters($markerType, 'mres');
                if (!isset($this->itemTypes[$markerType])) {
                    $markerType = 'other';
                }
                $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);
                if ($baseName === '') {
                    $baseName = 'imported';
                }
                $mapNameRaw = $baseName . ' ' . curdatetime();
                $mapName = $this->truncateImportField($this->sanitizeImportText($mapNameRaw), 255);

                $this->mapCreate($mapName);
                $mapId = (int) $this->mapsDb->getLastId();
                if ($mapId <= 0) {
                    $result['error'] = __('Something went wrong');
                } else {
                    $this->loadMaps();
                    $kml = new TinyKML();
                    $loaded = $kml->loadFromFile($tmpPath);
                    if ($loaded === false) {
                        $kmlError = $kml->getLastError();
                        $this->mapDelete($mapId);
                        if ($kmlError !== '') {
                            $result['error'] = $kmlError;
                        } else {
                            $result['error'] = __('Something went wrong');
                        }
                    } else {
                        $marksCount = 0;
                        $linesCount = 0;
                        $importFailed = false;
                        $importError = '';

                        $points = $kml->getPoints();
                        if (!empty($points)) {
                            foreach ($points as $io => $eachPoint) {
                                $itemName = isset($eachPoint['name']) ? $eachPoint['name'] : '';
                                $itemLocation = isset($eachPoint['description']) ? $eachPoint['description'] : '';
                                $itemName = $this->truncateImportField($this->sanitizeImportText($itemName), 255);
                                $itemLocation = $this->truncateImportField($this->sanitizeImportText($itemLocation), 255);
                                $lat = isset($eachPoint['lat']) ? $eachPoint['lat'] : '';
                                $lng = isset($eachPoint['lng']) ? $eachPoint['lng'] : '';
                                if ($lat !== '' and $lng !== '' and is_numeric($lat) and is_numeric($lng)) {
                                    $geo = (float) $lat . ',' . (float) $lng;
                                    try {
                                        $this->itemCreate($mapId, $markerType, $geo, $itemName, $itemLocation);
                                        $marksCount = $marksCount + 1;
                                    } catch (Exception $e) {
                                        $importFailed = true;
                                        $importError = $e->getMessage();
                                        break;
                                    }
                                }
                            }
                        }

                        if ($importFailed === false) {
                            $kmlLines = $kml->getLines();
                            if (!empty($kmlLines)) {
                                foreach ($kmlLines as $lio => $eachLine) {
                                    $lineName = isset($eachLine['name']) ? $eachLine['name'] : '';
                                    $lineDescr = isset($eachLine['description']) ? $eachLine['description'] : '';
                                    $lineName = $this->truncateImportField($this->sanitizeImportText($lineName), 255);
                                    $lineDescr = $this->sanitizeImportText($lineDescr);
                                    $lineColor = self::LINE_DEFAULT_COLOR;
                                    if (isset($eachLine['color']) and trim((string) $eachLine['color']) !== '') {
                                        $lineColor = trim((string) $eachLine['color']);
                                    }
                                    $lineWidth = self::LINE_DEFAULT_WIDTH;
                                    if (isset($eachLine['width']) and (int) $eachLine['width'] > 0) {
                                        $lineWidth = (int) $eachLine['width'];
                                    }
                                    $geoPoints = array();
                                    if (isset($eachLine['points']) and is_array($eachLine['points'])) {
                                        foreach ($eachLine['points'] as $pio => $eachPt) {
                                            if (is_array($eachPt) and isset($eachPt['lat']) and isset($eachPt['lng'])) {
                                                if (is_numeric($eachPt['lat']) and is_numeric($eachPt['lng'])) {
                                                    $geoPoints[] = array((float) $eachPt['lat'], (float) $eachPt['lng']);
                                                }
                                            }
                                        }
                                    }
                                    if (count($geoPoints) > 1) {
                                        $lineGeo = json_encode($geoPoints);
                                        $lineLengthM = sprintf('%.2f', $this->lineCalcLengthMeters($geoPoints));
                                        try {
                                            $this->lineCreate(
                                                $mapId,
                                                $lineName,
                                                self::LINE_DEFAULT_FIBERS_AMOUNT,
                                                $lineLengthM,
                                                $lineColor,
                                                $lineWidth,
                                                $lineDescr,
                                                $lineGeo
                                            );
                                            $linesCount = $linesCount + 1;
                                        } catch (Exception $e) {
                                            $importFailed = true;
                                            $importError = $e->getMessage();
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        if ($importFailed) {
                            $this->mapDelete($mapId);
                            if ($importError !== '') {
                                $result['error'] = $importError;
                            } else {
                                $result['error'] = __('Something went wrong');
                            }
                        } else {
                            $result['success'] = true;
                            $result['map_id'] = $mapId;
                            $result['marks'] = $marksCount;
                            $result['lines'] = $linesCount;
                            log_register('CUSTMAPS KML IMPORT MAP [' . $mapId . '] MARKS [' . $marksCount . '] LINES [' . $linesCount . ']');
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders KML import result summary
     *
     * @param int $mapId
     * @param int $marks
     * @param int $lines
     *
     * @return string
     */
    public function renderKmlImportResult($mapId, $marks, $lines) {
        $result = '';
        $mapId = ubRouting::filters($mapId, 'int');
        $marks = (int) $marks;
        $lines = (int) $lines;
        if ($mapId > 0) {
            $msg = __('Done') . ': ' . __('Markers') . ' ' . $marks . ', ' . __('Lines') . ' ' . $lines;
            $result .= $this->messages->getStyledMessage($msg, 'success');
            $result .= wf_delimiter();
            $mapUrl = self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $mapId;
            $result .= wf_Link($mapUrl, wf_img('skins/icon_map_small.png').' '.__('Show map'), false, 'ubButton');
            $result .= wf_delimiter();
            $result .= wf_BackLink(self::urlMapList());
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }
        return ($result);
    }

    /**
     * Builds KML document string for map objects
     *
     * @param int $mapId
     *
     * @return string
     */
    public function mapExportToKml($mapId) {
        $result = '';
        $mapId = ubRouting::filters($mapId, 'int');
        if (isset($this->allMaps[$mapId])) {
            $kml = new TinyKML();
            $documentName = $this->mapGetName($mapId);
            if (!empty($this->allItems)) {
                foreach ($this->allItems as $io => $each) {
                    if (($each['mapid'] == $mapId) and (!empty($each['geo']))) {
                        $parts = explode(',', $each['geo']);
                        if (count($parts) >= 2) {
                            $lat = trim($parts[0]);
                            $lng = trim($parts[1]);
                            if ($lat !== '' and $lng !== '' and is_numeric($lat) and is_numeric($lng)) {
                                $itemName = isset($each['name']) ? $each['name'] : '';
                                $itemLocation = isset($each['location']) ? $each['location'] : '';
                                $itemType = isset($each['type']) ? $each['type'] : 'other';
                                $itemDescription = $this->itemGetTypeName($itemType) . ':';
                                if ($itemLocation !== '') {
                                    $itemDescription .= ' ' . $itemLocation;
                                }
                                $kml->addPoint($itemName, $lat, $lng, $itemDescription);
                            }
                        }
                    }
                }
            }
            if (!empty($this->allLines)) {
                foreach ($this->allLines as $lineId => $lineData) {
                    if (($lineData['mapid'] == $mapId) and (!empty($lineData['geo']))) {
                        $geoPoints = json_decode($lineData['geo'], true);
                        if (is_array($geoPoints)) {
                            $lineName = isset($lineData['name']) ? $lineData['name'] : '';
                            $lineDescr = isset($lineData['description']) ? $lineData['description'] : '';
                            $lineColor = isset($lineData['style_color']) ? $lineData['style_color'] : '';
                            $lineWidth = 0;
                            if (isset($lineData['style_width'])) {
                                $lineWidth = (int) $lineData['style_width'];
                            }
                            $kml->addLine($lineName, $geoPoints, $lineDescr, $lineColor, $lineWidth);
                        }
                    }
                }
            }
            $result = $kml->toString($documentName);
        }
        return ($result);
    }

    /**
     * Sends map KML export as file download
     *
     * @param int $mapId
     *
     * @return void
     */
    public function mapExportKmlSend($mapId) {
        $mapId = ubRouting::filters($mapId, 'int');
        if (!isset($this->allMaps[$mapId])) {
            show_error(__('Something went wrong'));
        } else {
            $kmlBody = $this->mapExportToKml($mapId);
            $fileName = $this->mapExportKmlFileName($mapId);
            header('Content-Type: application/vnd.google-earth.kml+xml');
            header('Content-Transfer-Encoding: Binary');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Description: File Transfer');
            echo $kmlBody;
            log_register('CUSTMAPS KML EXPORT MAP [' . $mapId . ']');
            die();
        }
    }

    /**
     * Returns safe download filename for map KML export
     *
     * @param int $mapId
     *
     * @return string
     */
    protected function mapExportKmlFileName($mapId) {
        $timePart = curdatetime();
        $timePart = str_replace(' ', '_', $timePart);
        $timePart = str_replace(':', '-', $timePart);
        $result = 'custmap_' . (int) $mapId . '_' . $timePart . '.kml';
        $mapName = $this->mapGetName($mapId);
        if ($mapName !== '') {
            $safe = zb_TranslitString($mapName);
            $safe = ubRouting::filters($safe, 'safe');
            $safe = str_replace(' ', '_', $safe);
            $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $safe);
            if ($safe !== '') {
                $result = $safe . '_' . $timePart . '.kml';
            }
        }
        return ($result);
    }

    /**
     * Calculates polyline length in meters using haversine formula
     *
     * Points as array of array(lat, lng)
     *
     * @param array $points
     *
     * @return float
     */
    protected function lineCalcLengthMeters($points) {
        $result = 0.0;
        if (is_array($points)) {
            $pointCount = count($points);
            if ($pointCount > 1) {
                $earthRadius = 6371000;
                $idx = 0;
                while ($idx < ($pointCount - 1)) {
                    $lat1 = deg2rad((float) $points[$idx][0]);
                    $lng1 = deg2rad((float) $points[$idx][1]);
                    $lat2 = deg2rad((float) $points[$idx + 1][0]);
                    $lng2 = deg2rad((float) $points[$idx + 1][1]);
                    $dLat = $lat2 - $lat1;
                    $dLng = $lng2 - $lng1;
                    $a = sin($dLat / 2) * sin($dLat / 2) + cos($lat1) * cos($lat2) * sin($dLng / 2) * sin($dLng / 2);
                    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                    $result = $result + ($earthRadius * $c);
                    $idx++;
                }
            }
        }
        return ($result);
    }

    /**
     * Sanitizes imported text: strip_tags then ubRouting safe filter
     *
     * @param string $str
     *
     * @return string
     */
    protected function sanitizeImportText($str) {
        $result = strip_tags((string) $str);
        $result = ubRouting::filters($result, 'safe');
        return ($result);
    }

    /**
     * Truncates import field value to max length
     *
     * @param string $str
     * @param int $maxLen
     *
     * @return string
     */
    protected function truncateImportField($str, $maxLen) {
        $result = (string) $str;
        $maxLen = (int) $maxLen;
        if ($maxLen > 0) {
            if (function_exists('mb_substr')) {
                if (mb_strlen($result, 'UTF-8') > $maxLen) {
                    $result = mb_substr($result, 0, $maxLen, 'UTF-8');
                }
            } else {
                if (strlen($result) > $maxLen) {
                    $result = substr($result, 0, $maxLen);
                }
            }
        }
        return ($result);
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
            $clustering = ubRouting::checkPost(self::PROUTE_EDITMAP_CLUSTERING) ? 1 : 0;
            $cmarkers = ubRouting::checkPost(self::PROUTE_EDITMAP_CMARKERS) ? 1 : 0;
            $metrics = ubRouting::checkPost(self::PROUTE_EDITMAP_METRICS) ? 1 : 0;
            $this->mapsDb->data('name', $name);
            $this->mapsDb->data('clustering', $clustering);
            $this->mapsDb->data('cmarkers', $cmarkers);
            $this->mapsDb->data('metrics', $metrics);
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
                        $controls = wf_Link(self::URL_ME . '&' . self::ROUTE_EDITITEM . '=' . $each['id'], web_icon_extended(__('Change')), false);
                    } else {
                        $controls = wf_Link(self::URL_ME . '&' . self::ROUTE_EDITITEM . '=' . $each['id'], web_icon_extended('View'), false);
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
     * Returns map center for a polyline minimap: one existing vertex between ends
     * (middle index along the polyline), or empty if there are no valid points.
     *
     * @param array $points MapCore-style points as "lat,lng" strings
     *
     * @return string center as "lat,lng" or empty string
     */
    protected function lineGetMiniMapCenterFromPoints($points) {
        $result = '';
        if (is_array($points)) {
            $validPoints = array();
            foreach ($points as $eachPoint) {
                $eachPoint = trim((string) $eachPoint);
                if (!empty($eachPoint)) {
                    $parts = explode(',', $eachPoint);
                    if (count($parts) >= 2) {
                        $lat = trim($parts[0]);
                        $lng = trim($parts[1]);
                        if ($lat !== '' and $lng !== '') {
                            $validPoints[] = $lat . ',' . $lng;
                        }
                    }
                }
            }
            $n = count($validPoints);
            if ($n > 0) {
                $idx = (int) floor(($n - 1) / 2);
                $result = $validPoints[$idx];
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
                            $content.= wf_Link(self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $id . '&' . self::ROUTE_LINEEDIT . '=true&' . self::ROUTE_MODIFYLINE . '=' . $lineId, web_edit_icon(__('Edit on map')), false);
                            $content.= wf_Link(self::URL_ME . '&' . self::ROUTE_EDITLINE . '=' . $lineId, web_icon_extended(__('Change')), false);
                        } else {
                            $content.= wf_Link(self::URL_ME . '&' . self::ROUTE_EDITLINE . '=' . $lineId, web_icon_extended(__('View')), false);
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
     * Quick line edit form (name, style, fibers, description) without geometry editor.
     * Geometry and length are preserved via hidden fields.
     *
     * @param int $lineid
     *
     * @return string
     */
    protected function lineQuickEditForm($lineid) {
        $lineid = ubRouting::filters($lineid, 'int');
        $result = '';
        if (isset($this->allLines[$lineid])) {
            $lineData = $this->allLines[$lineid];
            $inputs = wf_HiddenInput(self::PROUTE_EDITLINEID, $lineid);
            $inputs .= wf_HiddenInput(self::PROUTE_EDITLINE_GEO, $lineData['geo']);
            $inputs .= wf_HiddenInput(self::PROUTE_EDITLINE_LENGTH_M, $lineData['length_m']);
            $inputs .= wf_TextInput(self::PROUTE_EDITLINE_NAME, __('Name'), $lineData['name'], true, '25');
            $inputs .= wf_ColorInput(self::PROUTE_EDITLINE_STYLE_COLOR, __('Color'), $lineData['style_color'], true);
            $inputs .= wf_Selector(self::PROUTE_EDITLINE_STYLE_WIDTH, $this->lineGetWidthOptions(), __('Line width'), $lineData['style_width'], true);
            $inputs .= wf_Selector(self::PROUTE_EDITLINE_FIBERS_AMOUNT, $this->lineGetFibersAmountOptions(), __('Fibers amount'), $lineData['fibers_amount'], true);
            $inputs .= wf_TextInput(self::PROUTE_EDITLINE_DESCRIPTION, __('Description'), $lineData['description'], false, '25');
            if (cfr('CUSTMAPEDIT')) {
                $inputs .= wf_delimiter(0);
                $inputs .= wf_Submit(__('Save'));
            }
            $result .= wf_Form('', 'POST', $inputs, 'glamour');

            $parsedPoints = $this->lineParsePoints($lineData['geo']);
            $pointsCount = count($parsedPoints);
            $lengthShown = round((float) $lineData['length_m']);
            $createdStr = '—';
            if (isset($lineData['created_at']) and strlen(trim((string) $lineData['created_at'])) > 0) {
                $createdStr = trim((string) $lineData['created_at']);
            }
            $updatedStr = '—';
            if (isset($lineData['updated_at']) and strlen(trim((string) $lineData['updated_at'])) > 0) {
                $updatedStr = trim((string) $lineData['updated_at']);
            }
            
            $metaBlock = wf_tag('div', false, '', 'style="border: 1px solid #d8d8d8;padding:8px;border-radius:2px;"');
            $metaBlock .= wf_tag('div', false) .  __('Length') .': '.  $lengthShown . ' ' . __('m') . wf_tag('div', true);
            $metaBlock .= wf_tag('div', false) . __('Points count').': ' . $pointsCount . wf_tag('div', true);
            $metaBlock .= wf_tag('div', false) . __('Created at') .': ' . $createdStr . wf_tag('div', true);
            $metaBlock .= wf_tag('div', false) . __('Updated at') . ': '. $updatedStr . wf_tag('div', true);
            $metaBlock .= wf_tag('div', true);
            $result .= wf_delimiter(0);
            $result .= $metaBlock;
        } else {
            throw new Exception(self::EX_NO_LINE_ID);
        }
        return ($result);
    }

    /**
     * Renders line profile/quick editing form
     *
     * @param int $lineId
     *
     * @return string
     */
    public function renderLineEdit($lineId) {
        $result = '';
        $lineId = ubRouting::filters($lineId, 'int');
        if (isset($this->allLines[$lineId])) {
            $lineData = $this->allLines[$lineId];
            $lineName = $lineData['name'];
            $editForm = $this->lineQuickEditForm($lineId);
            $miniMapBlock = '';
            $points = $this->lineParsePoints($lineData['geo']);
            if (count($points) > 1) {
                $centerGeo = $this->lineGetMiniMapCenterFromPoints($points);
                if (!empty($centerGeo)) {
                    $findingZoom = $this->ymapsCfg['FINDING_ZOOM'];
                    $miniMap = new MapCore('custmaplineminimap');
                    $miniMap->setCenter($centerGeo);
                    $miniMap->setZoom($findingZoom);
                    $lineColor = !empty($lineData['style_color']) ? $lineData['style_color'] : self::LINE_DEFAULT_COLOR;
                    $lineWeight = !empty($lineData['style_width']) ? $lineData['style_width'] : self::LINE_DEFAULT_WIDTH;
                    $lineFibers = self::LINE_DEFAULT_FIBERS_AMOUNT;
                    if (isset($lineData['fibers_amount']) and $lineData['fibers_amount'] !== '' and $lineData['fibers_amount'] !== null) {
                        $lineFibers = $lineData['fibers_amount'];
                    }
                    $popupBody = __('Fibers amount') . ': ' . $lineFibers . '<br>';
                    $popupBody .= __('Length') . ': ' . $lineData['length_m'] . '<br>';
                    $popupBody .= __('Description') . ': ' . $lineData['description'];
                    $miniMap->addPolyline($points, $popupBody, array(
                        'color' => $lineColor,
                        'weight' => $lineWeight,
                        'hint' => __('Line') . ': ' . $lineName,
                        'popupTitle' => $lineName,
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
                    $miniMapBlock = $miniMap->renderContainer('100%', '300px') . $miniMap->render();
                }
            }

            $mainWindowContent = '';
            if (!empty($miniMapBlock)) {
                $splitWrapOpts = 'style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:12px;width:100%;box-sizing:border-box;"';
                $colBorder = 'border:1px solid #d8d8d8;';
                $formColOpts = 'style="flex:0 0 auto;min-width:220px;max-width:100%;box-sizing:border-box;' . $colBorder . 'padding:8px;border-radius:2px;"';
                $mapColOpts = 'style="flex:1 1 0%;min-width:0;max-width:100%;box-sizing:border-box;' . $colBorder . 'padding:8px;border-radius:2px;"';
                $formCol = wf_tag('div', false, '', $formColOpts) . $editForm . wf_tag('div', true);
                $mapCol = wf_tag('div', false, '', $mapColOpts) . $miniMapBlock . wf_tag('div', true);
                $mainWindowContent = wf_tag('div', false, '', $splitWrapOpts) . $formCol . $mapCol . wf_tag('div', true);
            } else {
                $mainWindowContent = $editForm;
            }
            show_window(__('Edit') . ': ' . $lineName, $mainWindowContent);

            $lineControls = '';
            $linesUrl = self::URL_ME . '&' . self::ROUTE_SHOWLINES . '=' . $lineData['mapid'];
            $mapUrl=self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $lineData['mapid'];
            $lineControls .= wf_Link($mapUrl,wf_img('skins/icon_map_small.png').' '.__('Back').' '.__('to map'), false, 'ubButton');
            $lineControls .= wf_Link($linesUrl,wf_img('skins/icon_mapline16.png').' '.__('Back').' '.__('to lines'), false, 'ubButton');

            $lineUploadControls = '';
            $lineAttachments = '';

            if (isset($this->altCfg['PHOTOSTORAGE_ENABLED']) and $this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photostorage = new PhotoStorage('CUSTMAPLINES', $lineId);
                if (cfr('CUSTMAPEDIT')) {
                    $lineUploadControls .= wf_Link('?module=photostorage&scope=CUSTMAPLINES&itemid=' . $lineId . '&mode=list', wf_img('skins/photostorage.png') . ' ' . __('Upload images'), false, 'ubButton');
                }
                $lineAttachments .= $photostorage->renderImagesRaw();
            }

            if (isset($this->altCfg['FILESTORAGE_ENABLED']) and $this->altCfg['FILESTORAGE_ENABLED']) {
                $fileStorage = new FileStorage('CUSTMAPLINES', $lineId);
                if (cfr('CUSTMAPEDIT')) {
                    $callbackUrl = base64_encode(self::URL_ME . '&' . self::ROUTE_EDITLINE . '=' . $lineId);
                    $lineUploadControls .= $fileStorage->renderNavigationButton(' ' . __('Upload files'), 'ubButton', '&callback=' . $callbackUrl);
                }
                $lineAttachments .= $fileStorage->renderFilesPreview(false, '', '', 64);
            }

            $attachmentsUi = '';
            $attachmentsUi .= $lineControls;
            if (!empty($lineUploadControls)) {
                $attachmentsUi .= $lineUploadControls;
                $attachmentsUi .= wf_delimiter();
            } else {
                if (!empty($lineAttachments)) {
                    $attachmentsUi .= wf_delimiter();
                }
            }
            $attachmentsUi .= $lineAttachments;

            if (!empty($attachmentsUi)) {
                show_window('', $attachmentsUi);
            }

            if (isset($this->altCfg['ADCOMMENTS_ENABLED']) and $this->altCfg['ADCOMMENTS_ENABLED']) {
                $adcomments = new ADcomments('CUSTMAPLINES');
                show_window(__('Additional comments'), $adcomments->renderComments($lineId));
            }

            if (cfr('CUSTMAPEDIT')) {
                $deletionUrl = self::URL_ME . '&' . self::ROUTE_DELETELINE . '=' . $lineId;
                $cancelUrl = self::URL_ME . '&' . self::ROUTE_EDITLINE . '=' . $lineId;
                $deleteTitle = __('Delete') . ' ' . $lineName . '?';
                $deleteUi = wf_delimiter(0);
                $deleteUi .= wf_ConfirmDialog($deletionUrl, web_delete_icon() . ' ' . __('Delete line'), $this->messages->getDeleteAlert(), 'ubButton', $cancelUrl, $deleteTitle);
                show_window('', $deleteUi);
            }
        } else {
            show_error(__('Line') . ': ' . __('Not found'));
        }
        return ($result);
    }

    /**
     * Returns line edit form
     * 
     * @param int $lineid
     * 
     * @return string
     */
    public function lineEditForm($lineid) {
        $lineid = ubRouting::filters($lineid, 'int');
        $result = '';
        if (isset($this->allLines[$lineid])) {
            $lineData = $this->allLines[$lineid];
            $result.= wf_BackLink(self::URL_ME . '&' . self::ROUTE_SHOWLINES . '=' . $lineData['mapid']);
            $result.= wf_delimiter();
            $inputs = wf_HiddenInput(self::PROUTE_EDITLINEID, $lineid);
            $inputs.= wf_TextInput(self::PROUTE_EDITLINE_NAME, __('Name'), $lineData['name'], false, 25);
            $inputs.= wf_Selector(self::PROUTE_EDITLINE_FIBERS_AMOUNT, $this->lineGetFibersAmountOptions(), __('Fibers amount'), $lineData['fibers_amount'], true);
            $inputs.= wf_TextInput(self::PROUTE_EDITLINE_LENGTH_M, __('Length'), $lineData['length_m'], true, 25);
            $inputs.= wf_ColorInput(self::PROUTE_EDITLINE_STYLE_COLOR, __('Color'), $lineData['style_color'], true);
            $inputs.= wf_Selector(self::PROUTE_EDITLINE_STYLE_WIDTH, $this->lineGetWidthOptions(), __('Line width'), $lineData['style_width'], true);
            $inputs.= wf_TextInput(self::PROUTE_EDITLINE_DESCRIPTION, __('Description'), $lineData['description'], false, 25);
            $inputs.= wf_TextArea(self::PROUTE_EDITLINE_GEO, __('Geometry') . ' [[lat,lng],[lat,lng]]', $lineData['geo'], true, '35x4');
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
        $columns = array('ID', 'Name', 'Fibers', __('Length'). ' (' . __('m') . ')', 'Other' ,'Actions');
        $dataArr = array();

        $adcomments = new ADcomments('CUSTMAPLINES');
        $photostorage = new PhotoStorage('CUSTMAPLINES');
        $fileStorage = new FileStorage('CUSTMAPLINES');

        if (!empty($this->allLines)) {
            foreach ($this->allLines as $lineId => $lineData) {
                if ($lineData['mapid'] == $mapid) {
                    $actLinks = '';
                    if (cfr('CUSTMAPEDIT')) {
                        $actLinks .= wf_JSAlertStyled(self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $mapid . '&' . self::ROUTE_LINEEDIT . '=true&' . self::ROUTE_MODIFYLINE . '=' . $lineId, web_edit_icon(__('Edit on map')), $this->messages->getEditAlert()) . ' ';
                        $actLinks .= wf_Link(self::URL_ME . '&' . self::ROUTE_EDITLINE . '=' . $lineId, web_icon_extended(__('Change')), false) . ' ';
                    } else {
                        $actLinks .= wf_Link(self::URL_ME . '&' . self::ROUTE_EDITLINE . '=' . $lineId, web_icon_extended(__('View')), false) . ' ';
                    }
                    $attachments = $this->renderAttachmentsCell($lineId, $adcomments, $photostorage, $fileStorage);

                    $dataArr[] = array(
                        $lineId,
                        $lineData['name'],
                        $lineData['fibers_amount'],
                        round($lineData['length_m']),
                        $attachments,
                        $actLinks,
                    );
                }
            }
        }

        $result = '';
        $result .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_SHOWMAP . '=' . $mapid);
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
        $result = wf_Selector(self::PROUTE_NEWITEMTYPE, $this->itemTypes, __('Type'), '', true);
        $result.= wf_TextInput(self::PROUTE_NEWITEMNAME, __('Name'), '', true, 20);
        $result.= wf_TextInput(self::PROUTE_NEWITEMLOCATION, __('Location'), '', true, 20);
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
        $this->registerCustmapMarkersToggleControl();
        $result .= $this->mapCore->render();
        $result .= $this->mapLayersControls();
        return ($result);
    }

    /**
     * Adds a map control to show/hide marker layers and persists choice in localStorage.
     *
     * @return void
     */
    protected function registerCustmapMarkersToggleControl() {
        if (empty($this->showMapId)) {
            return;
        }
        $toggleConfig = array(
            'storageKey' => 'ubCustmap_markersVisible_' . $this->mapCore->getContainerId(),
            'iconMarkersOn' => 'skins/icon_fullmap16.png',
            'iconMarkersOff' => 'skins/icon_briefmap16.png',
            'titleShowMarkers' => __('Show markers'),
            'titleHideMarkers' => __('Hide markers'),
        );
        $toggleConfigJs = json_encode($toggleConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if ($toggleConfigJs === false) {
            $toggleConfigJs = '{}';
        }
        $this->mapCore->addScriptSrc(self::MARKERS_TOGGLE_LIB . '?nc=' . time());
        $markersToggleJs = '
            (function() {
                if (typeof ubCustmapsMarkersToggleInit !== "function") {
                    if (window.console && typeof console.warn === "function") {
                        console.warn("CustMaps: markers-toggle.js is not loaded");
                    }
                    return;
                }
                ubCustmapsMarkersToggleInit(map, ' . $toggleConfigJs . ');
            })();
        ';
        $this->mapCore->addRawJs($markersToggleJs);
    }

    /**
     * Return geo coordinates locator with embedded form
     * 
     * @return string
     */
    public function mapLocationEditor() {
        $title = wf_tag('b') . __('Place coordinates') . wf_tag('b', true);
        $data = $this->itemLocationForm();
        $this->mapCore->addLocationEditor(self::PROUTE_NEWITEMGEO, $title, $data);
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
     * @param string $lengthReadoutId
     * 
     * @return string
     */
    protected function lineLocationForm($mapid, $lengthReadoutId) {
        $mapid = ubRouting::filters($mapid, 'int');
        $lineColor = $this->lineGetRandomColor();
        $inputs = wf_HiddenInput(self::PROUTE_NEWLINE_MAPID, $mapid);
        $inputs.= wf_HiddenInput(self::PROUTE_NEWLINE_LINEID, '');
        $inputs.= wf_HiddenInput(self::PROUTE_NEWLINE_GEO, '');
        $inputs.= wf_TextInput(self::PROUTE_NEWLINE_NAME, __('Name'), '', true, 20);
        $inputs.= wf_Selector(self::PROUTE_NEWLINE_FIBERS_AMOUNT, $this->lineGetFibersAmountOptions(), __('Fibers amount'), strval(self::LINE_DEFAULT_FIBERS_AMOUNT), true);
        $inputs.= wf_ColorInput(self::PROUTE_NEWLINE_STYLE_COLOR, __('Color'), $lineColor, true, 'ubLineEditorColor_' . $mapid);
        $inputs.= wf_Selector(self::PROUTE_NEWLINE_STYLE_WIDTH, $this->lineGetWidthOptions(), __('Line width'), strval(self::LINE_DEFAULT_WIDTH), true);
        $inputs.= wf_TextInput(self::PROUTE_NEWLINE_DESCRIPTION, __('Description'), '', false, 20);
        $inputs.= wf_HiddenInput(self::PROUTE_NEWLINE_LENGTH_M, '0');
        $inputs.= wf_tag('div', false, 'ubLineEditorLengthReadout');
        $inputs.= __('Length') . ': ';
        $inputs.= wf_tag('span', false, '', 'id="' . $lengthReadoutId . '"') . '0.00' . wf_tag('span', true);
        $inputs.= ' ' . __('m') . wf_tag('div', true);
        
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
        $lengthReadoutId = 'ubLineEditorLengthReadout_' . $mapid;
        $title = wf_tag('b') . __('Line editor') . wf_tag('b', true);
        $data = $this->lineLocationForm($mapid, $lengthReadoutId);
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
        $animLineEdit = true;
        if (isset($this->altCfg['CUSTMAP_ANIM_LINEEDIT']) and !$this->altCfg['CUSTMAP_ANIM_LINEEDIT']) {
            $animLineEdit = false;
        }
        $lineEditorConfig = array(
            'panelHtml' => $panelHtml,
            'drawBtnId' => $drawBtnId,
            'finishBtnId' => $finishBtnId,
            'cancelBtnId' => $cancelBtnId,
            'undoBtnId' => $undoBtnId,
            'lengthReadoutId' => $lengthReadoutId,
            'initialEditLineId' => (ubRouting::checkGet(self::ROUTE_MODIFYLINE) ? ubRouting::get(self::ROUTE_MODIFYLINE, 'int') : 0),
            'defaultColor' => self::LINE_DEFAULT_COLOR,
            'defaultFibersAmount' => self::LINE_DEFAULT_FIBERS_AMOUNT,
            'defaultWidth' => self::LINE_DEFAULT_WIDTH,
            'animLineEdit' => $animLineEdit,
        );
        $lineEditorConfigJs = json_encode($lineEditorConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if ($lineEditorConfigJs === false) {
            $lineEditorConfigJs = '{}';
        }
        $this->mapCore->addScriptSrc(self::LINE_EDITOR_LIB.'?nc='.time()); // prevent lib caching
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
