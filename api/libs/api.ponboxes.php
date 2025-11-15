<?php

/**
 * PON Boxes allows to place/render some boxes on map
 */
class PONBoxes {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all available PON boxes as boxid=>boxdata
     *
     * @var array
     */
    protected $allBoxes = array();

    /**
     * Contains all available user/address/onu links to boxes as linkid=>boxid
     *
     * @var array
     */
    protected $allLinks = array();

    /**
     * Contains all available ponboxes splitters links as linkid=>boxid
     *
     * @var array
     */
    protected $allSplittersLinks = array();

    /**
     * Database abstraction layer with ponboxes
     *
     * @var object
     */
    protected $boxes = '';

    /**
     * Database abstraction layer with ponboxes links to users/addresses/ONUs etc
     *
     * @var object
     */
    protected $links = '';

    /**
     * Database abstraction layer with ponboxes splitters links
     *
     * @var object
     */
    protected $splittersLinks = '';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * System image storage placeholder
     *
     * @var object
     */
    protected $photoStorage = null;

    /**
     * Pre-defined list pf splitters and couplers
     *
     * @var string[]
     */
    protected $splittersTypesList = array();

    /**
     * Contains preloaded users address array
     *
     * @var array
     */
    protected $allUserAddress = array();

    /**
     * Routes, static defines etc
     */
    const URL_ME = '?module=ponboxes';
    const PROUTE_NEWBOXNAME = 'newboxname';
    const PROUTE_NEWBOEXTENINFO = 'newboxexteninfo';
    const PROUTE_NEWBOXGEO = 'newboxgeo';
    const PROUTE_NEWLINKBOX = 'newlinkboxid';
    const PROUTE_NEWLINKTYPE = 'newlinktype';
    const PROUTE_NEWLINKONU = 'newlinkonu';
    const PROUTE_MAPBOXID = 'setboxidonmap';
    const PROUTE_MAPBOXCOORDS = 'newboxmapcoords';
    const ROUTE_BOXLIST = 'ajboxes';
    const ROUTE_BOXNAV = 'boxidnav';
    const ROUTE_MAP = 'boxmap';
    const ROUTE_ONULINKS='showonulinks';
    const ROUTE_BOXEDIT = 'editboxid';
    const ROUTE_BOXDEL = 'deleteboxid';
    const ROUTE_LINKDEL = 'deletelinkid';
    const ROUTE_SPLITTERADD = 'addboxsplitters';
    const ROUTE_SPLITTERDEL = 'delboxsplitters';
    const ROUTE_PLACEBOX = 'plcmapboxid';
    const ROUTE_PLACEFIND = 'plcmapfind';
    const TABLE_BOXES = 'ponboxes';
    const TABLE_LINKS = 'ponboxeslinks';
    const TABLE_SPLITTERSLINKS = 'ponboxes_splitters';
    const TABLE_PONONU = 'pononu';
    const PHOTOSTORAGE_SCOPE = 'PONBOXES';

    /**
     * Creates new PONBoxes instance
     *
     * @param bool $loadFullData
     *
     * @return void
     */
    public function __construct($loadFullData = false) {
        $this->initMessages();
        $this->loadConfigs();
        $this->setSplitterTypes();
        $this->initDatabase();
        if ($loadFullData) {
            $this->loadAllAddress();
            $this->loadBoxes();
            $this->loadLinks();
            $this->loadSplittersLinks();
        }
    }

    /**
     * Inits system message helper instance
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits system image storage placeholder
     *
     * @return void
     */
    protected function initPhotoStorage() {
        $this->photoStorage = new PhotoStorage('PONBOXES');
    }

    /**
     * Loads some required configs
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Loads all users address data into protected property
     * 
     * @return void
     */
    protected function loadAllAddress() {
        $this->allUserAddress = zb_AddressGetFulladdresslistCached();
    }

    /**
     * Sets predefined splitters ad couplers list
     * 
     * @return void
     */
    protected function setSplitterTypes() {
        $this->splittersTypesList = array(
            'Coupler 5:95' => __('Coupler') . ' 5:95',
            'Coupler 10:90' => __('Coupler') . ' 10:90',
            'Coupler 15:85' => __('Coupler') . ' 15:85',
            'Coupler 20:80' => __('Coupler') . ' 20:80',
            'Coupler 25:75' => __('Coupler') . ' 25:75',
            'Coupler 30:70' => __('Coupler') . ' 30:70',
            'Coupler 35:65' => __('Coupler') . ' 35:65',
            'Coupler 40:60' => __('Coupler') . ' 40:60',
            'Coupler 45:55' => __('Coupler') . ' 45:55',
            'Coupler 50:50' => __('Coupler') . ' 50:50',
            'Splitter 1 x 2' => __('Splitter') . ' 1 x 2',
            'Splitter 1 x 3' => __('Splitter') . ' 1 x 3',
            'Splitter 1 x 4' => __('Splitter') . ' 1 x 4',
            'Splitter 1 x 5' => __('Splitter') . ' 1 x 5',
            'Splitter 1 x 6' => __('Splitter') . ' 1 x 6',
            'Splitter 1 x 8' => __('Splitter') . ' 1 x 8',
            'Splitter 1 x 12' => __('Splitter') . ' 1 x 12',
            'Splitter 1 x 16' => __('Splitter') . ' 1 x 16',
            'Splitter 1 x 24' => __('Splitter') . ' 1 x 24',
            'Splitter 1 x 32' => __('Splitter') . ' 1 x 32',
            'Splitter 1 x 64' => __('Splitter') . ' 1 x 64'
        );
    }

    /**
     * Inits all required database abstraction layers for further usage
     *
     * @return void
     */
    protected function initDatabase() {
        $this->boxes = new NyanORM(self::TABLE_BOXES);
        $this->links = new NyanORM(self::TABLE_LINKS);
        $this->splittersLinks = new NyanORM(self::TABLE_SPLITTERSLINKS);
        //            .               ,.
        //           T."-._..---.._,-"/|
        //           l|"-.  _.v._   (" |
        //           [l /.'_ \; _~"-.`-t
        //           Y " _(o} _{o)._ ^.|
        //           j  T  ,-<v>-.  T  ]
        //           \  l ( /-^-\ ) !  !
        //            \. \.  "~"  ./  /c-..,__
        //              ^r- .._ .- .-"  `- .  ~"--.
        //               > \.                      \
        //               ]   ^.                     \
        //               3  .  ">            .       Y  -MEOW! WHERE IS MY BOX?!
        //  ,.__.--._   _j   \ ~   .         ;       |
        // (    ~"-._~"^._\   ^.    ^._      I     . l
        //  "-._ ___ ~"-,_7    .Z-._   7"   Y      ;  \        _
        //     /"   "~-(r r  _/_--._~-/    /      /,.--^-._   / Y
        //     "-._    '"~~~>-._~]>--^---./____,.^~        ^.^  !
        //         ~--._    '   Y---.                        \./
        //              ~~--._  l_   )                        \
        //                    ~-._~~~---._,____..---           \
        //                        ~----"~       \
        //                                       \
    }

    /**
     * Loads all available boxes from database
     *
     * @return void
     */
    protected function loadBoxes() {
        if (@$this->altCfg['PONBOXES_NAME_ORDER']) {
            $this->boxes->orderBy('name', 'ASC');
        }
        $this->allBoxes = $this->boxes->getAll('id');
    }

    /**
     * Loads all available boxes to something links from database
     *
     * @return void
     */
    protected function loadLinks() {
        $this->allLinks = $this->links->getAll('id');
    }

    protected function loadSplittersLinks() {
        $this->allSplittersLinks = $this->splittersLinks->getAll('id');
    }

    /**
     * Renders all available boxes list container
     *
     * @return string
     */
    public function renderBoxesList() {
        $result = '';
        $columns = array('ID', 'Name', 'Additional info', 'Location', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&' . self::ROUTE_BOXLIST . '=true', false, __('boxes'), 100, $opts);
        return ($result);
    }

    /**
     * Renders JSON data with all available PON boxes list and some controls
     *
     * @return void
     */
    public function ajBoxesList() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allBoxes)) {
            foreach ($this->allBoxes as $io => $each) {
                $geoLink='';
                if (!empty($each['geo'])) {
                    $geoLink = wf_Link(self::URL_ME . '&' . self::ROUTE_MAP . '=true&' . self::ROUTE_PLACEFIND . '=' . $each['geo'], wf_img_sized('skins/icon_search_small.gif', __('Find on map')));
                }
                $data[] = $each['id'];
                $data[] = $each['name'];
                $data[] = $each['exten_info'];
                $data[] = $each['geo'];
                $boxActs = '';
                $boxActs .= wf_Link(self::URL_ME . '&' . self::ROUTE_BOXEDIT . '=' . $each['id'], web_edit_icon());
                $boxActs .= $geoLink;
                $data[] = $boxActs;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Renders new pon box creation form. Its obvious.
     *
     * @return string
     */
    protected function renderBoxCreateForm() {
        $result = '';
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs = wf_TextInput('newboxname', __('Name') . $sup, '', true, 20);
        $inputs .= wf_TextInput('newboxexteninfo', __('Additional info'), '', true, 20);
        $inputs .= wf_TextInput('newboxgeo', __('Location'), '', true, 20, 'geo');
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders existing box editing form aka "Box profile"
     *
     * @param int $boxId
     *
     * @return string
     */
    public function renderBoxEditForm($boxId) {
        $boxId = ubRouting::filters($boxId, 'int');
        $result = '';
        if (isset($this->allBoxes[$boxId])) {
            $boxData = $this->allBoxes[$boxId];
            $mapPlaceUrl = self::URL_ME . '&' . self::ROUTE_MAP . '=true&' . self::ROUTE_PLACEBOX . '=' . $boxId;
            $smallGeoLocControls = '';
            if (!empty($boxData['geo'])) {
                $mapFindUrl = self::URL_ME . '&' . self::ROUTE_MAP . '=true&' . self::ROUTE_PLACEFIND . '=' . $boxData['geo'];
                $smallGeoLocControls .= wf_Link($mapFindUrl, wf_img_sized('skins/icon_search_small.gif', __('Find on map'), '10'));
            } else {
                $smallGeoLocControls .= wf_link($mapPlaceUrl, wf_img_sized('skins/ymaps/target.png', __('Place on map'), '10'));
            }
            
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs = wf_HiddenInput('editboxid', $boxId);
            $inputs .= wf_TextInput('editboxname', __('Name') . $sup, $boxData['name'], true, 20);
            $inputs .= wf_TextInput('editboxexteninfo', __('Additional info'), $boxData['exten_info'], true, 20);
            $inputs .= wf_TextInput('editboxgeo', $smallGeoLocControls . ' ' . __('Location'), $boxData['geo'], true, 20, 'geo');
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_delimiter(0);
            $result .= wf_BackLink(self::URL_ME);
            if (!$this->isBoxProtected($boxId)) {
                $boxDelControlUrl = self::URL_ME . '&' . self::ROUTE_BOXDEL . '=' . $boxId;
                $boxDelCancelUrl = self::URL_ME . '&' . self::ROUTE_BOXEDIT . '=' . $boxId;
                $result .= wf_ConfirmDialogJS($boxDelControlUrl, web_delete_icon() . ' ' . __('Delete'), $this->messages->getDeleteAlert(), 'ubButton', $boxDelCancelUrl) . ' ';
            }
            if (empty($boxData['geo'])) {
                $result .= wf_Link($mapPlaceUrl, wf_img('skins/ymaps/target.png') . ' ' . __('Place on map'), false, 'ubButton');
            } else {
                $result.=wf_delimiter(1);
                $result .= $this->renderBoxesMiniMap($boxData['geo']);
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('box') . ' [' . $boxId . '] ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Saves box data if required
     *
     * @return void/string on error
     */
    public function saveBox() {
        $result = '';
        if (ubRouting::checkPost(array('editboxid', 'editboxname'))) {
            $boxId = ubRouting::post('editboxid', 'int');
            $newBoxName = ubRouting::post('editboxname');
            $newBoxNameF = ubRouting::filters($newBoxName, 'mres');
            $newBoxExtenInfo = ubRouting::post('editboxexteninfo');
            $newBoxExtenInfoF = ubRouting::filters($newBoxExtenInfo, 'mres');
            $newBoxGeoF = ubRouting::post('editboxgeo', 'mres');
            if (isset($this->allBoxes[$boxId])) {
                $boxData = $this->allBoxes[$boxId];
                if ($newBoxNameF != $boxData['name']) {
                    //name changed
                    if ($this->isBoxNameFree($newBoxNameF)) {
                        //and still is unique
                        $this->boxes->data('name', $newBoxNameF);
                        $this->boxes->where('id', '=', $boxId);
                        $this->boxes->save();
                        log_register('PONBOX CHANGE BOX [' . $boxId . '] NAME `' . $newBoxName . '`');
                    } else {
                        $result .= __('This box already exists');
                    }
                }

                if ($newBoxExtenInfoF != $boxData['exten_info']) {
                    $this->boxes->data('exten_info', $newBoxExtenInfoF);
                    $this->boxes->where('id', '=', $boxId);
                    $this->boxes->save();
                    log_register('PONBOX CHANGE BOX [' . $boxId . '] EXTEN INFO');
                }

                if ($newBoxGeoF != $boxData['geo']) {
                    $this->boxes->data('geo', $newBoxGeoF);
                    $this->boxes->where('id', '=', $boxId);
                    $this->boxes->save();
                    log_register('PONBOX CHANGE BOX [' . $boxId . '] GEO `' . $newBoxGeoF . '`');
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('box') . ' [' . $boxId . '] ' . __('Not exists');
            }
        }
        return ($result);
    }

    /**
     * Check is box name already used or not?
     *
     * @param string $boxName
     *
     * @return bool
     */
    protected function isBoxNameFree($boxName) {
        $result = true;
        if (!empty($this->allBoxes)) {
            foreach ($this->allBoxes as $io => $each) {
                if ($each['name'] == $boxName) {
                    $result = false;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Creates new PON box in database
     *
     * @param string $name
     * @param string $geo
     *
     * @return void/string on error
     */
    public function createBox($name, $extenInfo = '', $geo = '') {
        $result = '';
        $nameF = ubRouting::filters($name, 'mres');
        $extenInfoF = ubRouting::filters($extenInfo, 'mres');
        $geoF = ubRouting::filters($geo, 'mres');
        if (!empty($nameF)) {
            if ($this->isBoxNameFree($nameF)) {
                $this->boxes->data('name', $nameF);
                $this->boxes->data('exten_info', $extenInfoF);
                $this->boxes->data('geo', $geoF);
                $this->boxes->create();
                $newId = $this->boxes->getLastId();
                log_register('PONBOX CREATE BOX [' . $newId . '] NAME `' . $name . '`');
            } else {
                $result .= __('This box already exists');
            }
        } else {
            $result .= __('All fields marked with an asterisk are mandatory');
        }
        return ($result);
    }

    /**
     * Renders default controls panel
     *
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create'), __('Create'), $this->renderBoxCreateForm(), 'ubButton') . ' ';
        if (ubRouting::checkGet(self::ROUTE_MAP) or ubRouting::checkGet(self::ROUTE_BOXEDIT)) {
            $result .= wf_Link(self::URL_ME, wf_img('skins/icon_table.png') . ' ' . __('List'), false, 'ubButton');
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_MAP . '=true', wf_img('skins/ponmap_icon.png') . ' ' . __('Just').' '.__('Map'), false, 'ubButton');
            $result .= wf_Link(self::URL_ME.'&'. self::ROUTE_MAP . '=true'. '&' . self::ROUTE_ONULINKS . '=true', wf_img('skins/ymaps/uplinks.png') . ' ' . __('ONU Map').' + '.__('Links'), false, 'ubButton');
            
        } else {
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_MAP . '=true', wf_img('skins/ponmap_icon.png') . ' ' . __('Map'), false, 'ubButton');
        }
        return ($result);
    }

    /**
     * Check is box protected wit some existing links
     *
     * @param int $boxId
     *
     * @return bool
     */
    protected function isBoxProtected($boxId) {
        $boxId = ubRouting::filters($boxId, 'int');
        $result = false;
        if (!empty($this->allLinks)) {
            foreach ($this->allLinks as $io => $each) {
                if ($each['boxid'] == $boxId) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes existing PON box from database
     *
     * @param int $boxId
     *
     * @return void/string on error
     */
    public function deleteBox($boxId) {
        $boxId = ubRouting::filters($boxId, 'int');
        $result = '';
        if (isset($this->allBoxes[$boxId])) {
            if (!$this->isBoxProtected($boxId)) {
                $boxData = $this->allBoxes[$boxId];
                $this->boxes->where('id', '=', $boxId);
                $this->boxes->delete();
                log_register('PONBOX DELETE BOX [' . $boxId . '] NAME `' . $boxData['name'] . '`');
            } else {
                $result .= __('Something went wrong') . ': ' . __('This item is used by something');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('box') . ' [' . $boxId . '] ' . __('Not exists');
        }
        return ($result);
    }

    /**
     * Delete link from database
     *
     * @param int $linkId
     * @param bool $deleteSplitter
     *
     * @return void/string on error
     */
    public function deleteLink($linkId, $deleteSplitter = false) {
        $linkId = ubRouting::filters($linkId, 'int');
        $result = '';

        if ($deleteSplitter) {
            if (isset($this->allSplittersLinks[$linkId])) {
                $linkData = $this->allSplittersLinks[$linkId];
                $this->splittersLinks->where('id', '=', $linkId);
                $this->splittersLinks->delete();
                log_register('PONBOX DELETE SPLITTER [' . $linkId . '] BOX [' . $linkData['boxid'] . ']');
            } else {
                $result .= __('Something went wrong') . ': ' . __('Splitter') . ' [' . $linkId . '] ' . __('Not exists');
            }
        } else {
            if (isset($this->allLinks[$linkId])) {
                $linkData = $this->allLinks[$linkId];
                $this->links->where('id', '=', $linkId);
                $this->links->delete();
                log_register('PONBOX DELETE LINK [' . $linkId . '] BOX [' . $linkData['boxid'] . ']');
            } else {
                $result .= __('Something went wrong') . ': ' . __('Link') . ' [' . $linkId . '] ' . __('Not exists');
            }
        }

        return ($result);
    }

    /**
     * Returns form for setting location of the box on map
     * 
     * @param int $boxId
     * 
     * @return string
     */
    protected function getBoxPlaceForm($boxId) {
        $result = '';
        $boxId = ubRouting::filters($boxId, 'int');
        $boxData = $this->allBoxes[$boxId];
        $inputs = wf_HiddenInput(self::PROUTE_MAPBOXID, $boxId);
        $inputs .= wf_delimiter(1);
        $inputs .= __('Box') . ': ' . $boxData['name'];
        $inputs .= wf_delimiter(1);
        $inputs .= wf_Submit('Save');
        $result .= generic_MapEditor(self::PROUTE_MAPBOXCOORDS, __('Place on map'), $inputs);
        return($result);
    }

    /**
     * Sets some new geo coords for existing box
     * 
     * @param int $boxId
     * @param string $coords
     * 
     * @return void
     */
    public function setBoxGeo($boxId, $coords = '') {
        $boxId = ubRouting::filters($boxId, 'int');
        $coords = ubRouting::filters($coords, 'mres');
        $this->boxes->where('id', '=', $boxId);
        $this->boxes->data('geo', $coords);
        $this->boxes->save();
        log_register('PONBOX CHANGE BOX [' . $boxId . '] GEO `' . $coords . '`');
    }


    /**
     * Renders ONU links placemarks for map
     * 
     * @return string
     */
    protected function renderOnuLinks() {
        $result = '';
        $ponOnu = new NyanORM(self::TABLE_PONONU);
        $allOnuData = $ponOnu->getAll('id');
        $placemarks = '';
        $linkMarks='';

        $pononumap = new PONONUMap();
        $onuPlacemarksRaw = $pononumap->renderOnuMap(true);
        $onuBuilds = $onuPlacemarksRaw['builds'];
        $placemarks = $onuPlacemarksRaw['placemarks'];
       
        if (!empty($onuBuilds)) {
            foreach ($onuBuilds as $eachGeo => $eachBuild) {
                if (!empty($eachBuild)) {
                    foreach ($eachBuild as $io => $eachBuildOnu) {
                        if (!empty($eachBuildOnu['onuid'])) {
                            $onuId = $eachBuildOnu['onuid'];
                            $onuData = @$allOnuData[$onuId];
                            if (!empty($onuData)) {
                            $signalState = $eachBuildOnu['signalstate'];
                            $linkedBoxes = $this->getLinkedBoxes($onuData);
                            if (!empty($linkedBoxes) and $onuData) {
                            
                            if ($signalState) {
                                $linkColor= PONizer::COLOR_OK;
                            } else {
                                $linkColor= PONizer::COLOR_BAD;
                            }

                            foreach ($linkedBoxes as $eachLinkedBoxId => $eachLinkedBoxId) {
                                $linkedBoxData = @$this->allBoxes[$eachLinkedBoxId];
                                $onuGeo = $eachBuildOnu['geo'];
                                if (!empty($linkedBoxData)) {
                                    $boxGeo = $linkedBoxData['geo'];
                                    if ($boxGeo and $onuGeo) {
                                     $linkHint= __('Box') . ': ' . $linkedBoxData['name'].' ➜ '.__('ONU').': '.$eachBuildOnu['buildtitle'];
                                     $linkMarks.=generic_mapAddLine($onuGeo, $boxGeo, $linkColor, $linkHint, 2);
                                    }
                                }
                            }
                            }
                        }
                    }

                }
            }
        }
    }

        $result.=$linkMarks;
        $result.=$placemarks;
        
        return ($result);
    }

    /**
     * Renders available boxes map
     *
     * @global object $ubillingConfig
     * 
     * @return string
     */
    public function renderBoxesMap() {
        global $ubillingConfig;
        $mapsCfg = $ubillingConfig->getYmaps();
        $result = '';
        if (!empty($this->allBoxes)) {
            $mapContainer = 'ponboxmap';
            $mapCenter = $mapsCfg['CENTER'];
            $result .= generic_MapContainer('100%', '800px', $mapContainer);
            $placemarks = '';
            $editor = '';

            if (ubRouting::checkGet(self::ROUTE_ONULINKS)) {
                $placemarks .= $this->renderOnuLinks();
            }

            if (ubRouting::checkGet(self::ROUTE_PLACEBOX)) {
                $placeBoxId = ubRouting::get(self::ROUTE_PLACEBOX, 'int');
                $editor .= $this->getBoxPlaceForm($placeBoxId);
            }

            if (ubRouting::checkGet(self::ROUTE_PLACEFIND)) {
                $findBoxGeo = ubRouting::get(self::ROUTE_PLACEFIND);
                $radius = 30;
                $mapCenter = $findBoxGeo;
                $placemarks .= generic_mapAddCircle($findBoxGeo, $radius, __('Search area radius') . ' ' . $radius . ' ' . __('meters'), __('Search area'));
            }

            foreach ($this->allBoxes as $io => $each) {
                if (!empty($each['geo'])) {
                    $boxLink = trim(wf_Link(self::URL_ME . '&' . self::ROUTE_BOXEDIT . '=' . $each['id'], web_edit_icon()));
                    $placemarks .= generic_mapAddMark($each['geo'], '', $each['name'] . ' ' . $boxLink, '', '', '', true);
                }
            }

            $result .= generic_MapInit($mapCenter, $mapsCfg['ZOOM'], $mapsCfg['TYPE'], $placemarks, $editor, $mapsCfg['LANG'], $mapContainer);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }


    /**
     * Renders mini-map for boxes
     * 
     * @param string $boxGeo
     * 
     * @return string
     */
    public function renderBoxesMiniMap($boxGeo='') {
        $result = '';
        global $ubillingConfig;
        $mapsCfg = $ubillingConfig->getYmaps();
        $result = '';
        if (!empty($this->allBoxes)) {
            $mapContainer = 'ponboxmap';
            $result .= generic_MapContainer('100%', '300px', $mapContainer);
            $placemarks = '';
            $editor = '';

            if (!empty($boxGeo)) {
                $mapCenter = $boxGeo;
                $radius = 30;
                $placemarks.=generic_MapAddCircle($boxGeo, $radius, __('Search area radius') . ' ' . $radius . ' ' . __('meters'), __('Search area'));
            } else {
                $mapCenter = $mapsCfg['CENTER'];
            }

            if (ubRouting::checkGet(self::ROUTE_PLACEBOX)) {
                $placeBoxId = ubRouting::get(self::ROUTE_PLACEBOX, 'int');
                $editor .= $this->getBoxPlaceForm($placeBoxId);
            }
            foreach ($this->allBoxes as $io => $each) {
                if (!empty($each['geo'])) {
                    $boxLink = trim(wf_Link(self::URL_ME . '&' . self::ROUTE_BOXEDIT . '=' . $each['id'], web_edit_icon()));
                    $placemarks .= generic_mapAddMark($each['geo'], '', $each['name'] . ' ' . $boxLink, '', '', '', true);
                }
            }

            $result .= generic_MapInit($mapCenter, $mapsCfg['ZOOM'], $mapsCfg['TYPE'], $placemarks, $editor, $mapsCfg['LANG'], $mapContainer);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Creates box->entity link in database
     *
     * @param int $boxId existing PON Box ID
     * @param string $type link type: login, address, login+address, onuid
     * @param array $param link parameter
     *
     * @return void/string on error
     */
    protected function createLink($boxId, $type, $param) {
        $result = '';
        $useField = array();
        $boxId = ubRouting::filters($boxId, 'int');

        if (isset($this->allBoxes[$boxId])) {
            $saveLink = true;
            switch ($type) {
                case 'login':
                    $useField = array('login');
                    break;
                case 'address':
                    $useField = array('address');
                    break;
                case 'loginaddress':
                    $useField = array('login', 'address');
                    break;
                case 'onuid':
                    $useField = array('onuid');
                    break;
                default:
                    $saveLink = false;
                    $result .= __('Unknown link type') . ' ' . $type;
                    break;
            }

            if ($saveLink) {
                $fieldsCount = count($useField);
                $this->links->data('boxid', $boxId);

                for ($i = 0; $i < $fieldsCount; $i++) {
                    $paramF = ubRouting::filters($param[$i], 'mres');
                    $this->links->data($useField[$i], $paramF);
                }

                $this->links->create();
                $newId = $this->links->getLastId();
                log_register('PONBOX CREATE LINK [' . $newId . '] BOX [' . $boxId . '] TYPE `' . $type . '`  TO `' . implode(', ', $param) . '`');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('box') . ' [' . $boxId . '] ' . __('Not exists');
        }
        return ($result);
    }

    /**
     * Search some linked boxes for this ONU
     *
     * @param array $onuData
     *
     * @return array
     */
    public function getLinkedBoxes($onuData) {
        $result = array();
        if (!empty($onuData)) {
            $onuId = $onuData['id'];
            $onuUser = $onuData['login'];

            if (!empty($this->allLinks)) {
                foreach ($this->allLinks as $io => $eachLink) {
                    //ONU ID link search
                    if ($eachLink['onuid'] == $onuId) {
                        $result[$eachLink['boxid']] = $eachLink['boxid'];
                    }

                    // This is the water.
                    // And this is the well.
                    // Drink full and descend.
                    // The horse is the white of the eyes and dark within.

                    if (!empty($onuUser)) {
                        //address search
                        $onuUserAddress = @$this->allUserAddress[$onuUser];
                        if ($eachLink['address'] == $onuUserAddress) {
                            $result[$eachLink['boxid']] = $eachLink['id'];
                        }

                        //direct login search
                        if ($eachLink['login'] == $onuUser) {
                            $result[$eachLink['boxid']] = $eachLink['id'];
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders linked lined boxes list
     *
     * @param array $boxesArray
     * @param bool $userProfile
     *
     * @return string
     */
    public function renderLinkedBoxes($boxesArray, $userProfile = false) {
        $result = '';
        if (!empty($boxesArray)) {
            foreach ($boxesArray as $boxId => $linkId) {
                $boxName = $this->allBoxes[$boxId]['name'];
                $boxLink = wf_Link(self::URL_ME . '&' . self::ROUTE_BOXEDIT . '=' . $boxId, $boxName);

                if ($userProfile) {
                    $result .= wf_tag('span', false, '', 'style="color: #32510F; font-size: 14px;"') . $boxLink . wf_delimiter(0) . wf_tag('span', true);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Box') . ': ' . $boxLink, 'success');
                }
            }
        } else {
            if ($userProfile) {
                $result .= wf_tag('span', false, '', 'style="color: #32510F"; font-size: 14px;') . __('PON Boxes') . ': ' . __('Nothing to show') . wf_tag('span', true);
            } else {
                $result .= $this->messages->getStyledMessage(__('PON Boxes') . ': ' . __('Nothing to show'), 'info');
            }
        }
        return ($result);
    }

    /**
     * Returns linked entity control link
     *
     * @param array $linkData
     *
     * @return string
     */
    protected function getLinkEntityControl($linkData) {
        $result = '';
        if (!empty($linkData)) {
            if (!empty($linkData['login']) and ! empty($linkData['address'])) {
                $result .= wf_Link('?module=userprofile&username=' . $linkData['login'], web_profile_icon() . ' ' . $linkData['login'])
                        . wf_img('skins/icon_build.gif', __('Address')) . ' ' . $linkData['address'];
            } else {
                if (!empty($linkData['login'])) {
                    $result .= wf_Link('?module=userprofile&username=' . $linkData['login'], web_profile_icon() . ' ' . $linkData['login']);
                }

                if (!empty($linkData['onuid'])) {
                    $result .= wf_Link('?module=ponizer&editonu=' . $linkData['onuid'], wf_img('skins/switch_models.png', __('ONU')) . ' ' . $linkData['onuid']);
                }

                if (!empty($linkData['address'])) {
                    $result .= wf_img('skins/icon_build.gif', __('Address')) . ' ' . $linkData['address'];
                }
            }
        }
        return ($result);
    }

    /**
     * Renders existing POB Box links of any type
     *
     * @param int $boxId
     *
     * @return string
     */
    public function renderBoxLinksList($boxId) {
        $result = '';
        $boxId = ubRouting::filters($boxId, 'int');
        if (isset($this->allBoxes[$boxId])) {
            if (!empty($this->allLinks)) {
                $curBoxLinks = array();
                foreach ($this->allLinks as $io => $each) {
                    if ($each['boxid'] == $boxId) {
                        $curBoxLinks[] = $each;
                    }
                }

                if (!empty($curBoxLinks)) {
                    $cells = wf_TableCell(__('User') . ' / ' . __('ONU') . ' / ' . __('Address'));
                    $cells .= wf_TableCell(__('Actions'));
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($curBoxLinks as $io => $each) {
                        $cells = wf_TableCell($this->getLinkEntityControl($each));
                        $delLinkUrl = self::URL_ME . '&' . self::ROUTE_LINKDEL . '=' . $each['id'] . '&' . self::ROUTE_BOXNAV . '=' . $each['boxid'];
                        $actLinks = wf_JSAlert($delLinkUrl, web_delete_icon(), $this->messages->getDeleteAlert());
                        $cells .= wf_TableCell($actLinks);
                        $rows .= wf_TableRow($cells, 'row5');
                    }

                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('box') . ' [' . $boxId . '] ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Renders box assign form. With some ONU or another ONU params
     *
     * @param array $onuData
     *
     * @return string
     */
    public function renderBoxAssignForm($onuData) {
        $result = '';
        $boxesTmp = array('' => '-');
        if (!empty($onuData)) {
            $onuId = $onuData['id'];
            $onuUserName = $onuData['login'];
            $onuUserName = trim($onuUserName);
            if (!empty($this->allBoxes)) {
                $inputs = '';
                foreach ($this->allBoxes as $eachBoxId => $eachBoxData) {
                    $boxesTmp[$eachBoxData['id']] = $eachBoxData['name'];
                }
                $inputs .= wf_HiddenInput(self::PROUTE_NEWLINKONU, $onuId);
                if ($this->altCfg['PONBOXES_SEARCHBL']) {
                    $inputs .= wf_SelectorSearchable(self::PROUTE_NEWLINKBOX, $boxesTmp, __('box'), '', false, false) . ' ';
                } else {
                    $inputs .= wf_Selector(self::PROUTE_NEWLINKBOX, $boxesTmp, __('box'), '', false, false) . ' ';
                }
                $inputs .= wf_nbsp(2);

                if (!empty($onuUserName)) {
                    $inputs .= wf_RadioInput(self::PROUTE_NEWLINKTYPE, __('ONU'), 'onuid', false, true) . ' ';
                    $inputs .= wf_RadioInput(self::PROUTE_NEWLINKTYPE, __('User'), 'login', false, false) . ' ';
                    $inputs .= wf_RadioInput(self::PROUTE_NEWLINKTYPE, __('Address'), 'address', false, false) . ' ';
                    $inputs .= wf_RadioInput(self::PROUTE_NEWLINKTYPE, __('Login') . ' + ' . __('Address'), 'loginaddress', false, false) . ' ';
                } else {
                    $inputs .= wf_HiddenInput(self::PROUTE_NEWLINKTYPE, 'onuid');
                }

                $inputs .= wf_nbsp(2);
                $inputs .= wf_Submit(__('Create new PON box link'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            }
        }


        return ($result);
    }

    /**
     * Create PON box link with some ONU by some type in database
     *
     * @param int $boxId
     * @param int $onuId
     * @param string $linkType
     *
     * @return void/string on error
     */
    public function createLinkONU($boxId, $onuId, $linkType) {
        $result = '';
        $boxId = ubRouting::filters($boxId, 'int');
        $onuId = ubRouting::filters($onuId, 'int');
        if (isset($this->allBoxes[$boxId])) {
            //PON ONU database abstraction workaround here
            $ponOnu = new NyanORM(self::TABLE_PONONU);
            $ponOnu->where('id', '=', $onuId);
            $onuData = $ponOnu->getAll();
            if (!empty($onuData)) {
                $onuData = $onuData[0];
                //trying to create link
                if ($linkType == 'onuid') {
                    $result .= $this->createLink($boxId, $linkType, array($onuId));
                }

                if ($linkType == 'login' or $linkType == 'address' or $linkType == 'loginaddress') {
                    $userLogin = trim($onuData['login']);
                    if (!empty($userLogin)) {
                        $userData = zb_UserGetAllData($userLogin);
                        if (!empty($userData)) {
                            $userAddress = $userData[$userLogin]['fulladress'];
                            //login linking
                            if ($linkType == 'login') {
                                $result .= $this->createLink($boxId, $linkType, array($userLogin));
                            }
                            //address linking
                            if ($linkType == 'address') {
                                if (!empty($userAddress)) {
                                    $result .= $this->createLink($boxId, $linkType, array($userAddress));
                                } else {
                                    $result .= __('Something went wrong') . ': ' . __('Address') . ' ' . __('Empty');
                                }
                            }
                            //login and address linking
                            if ($linkType == 'loginaddress') {
                                if (empty($userAddress)) {
                                    $userAddress = __('User is a hobo');
                                }

                                $result .= $this->createLink($boxId, $linkType, array($userLogin, $userAddress));
                            }
                        } else {
                            $result .= __('Something went wrong') . ': ' . __('User') . ' (' . $userLogin . ') ' . __('Not exists');
                        }
                    }
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('ONU') . ' [' . $onuId . '] ' . __('Not exists');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('box') . ' [' . $boxId . '] ' . __('Not exists');
        }

        return ($result);
    }

    /**
     * Renders splitters adding controls
     *
     * @param $boxID
     *
     * @return string
     */
    public function renderSplittersControls($boxID) {
        $inputs = __('Place a splitter/coupler in this box') . ':' . wf_nbsp();
        $inputs .= wf_Selector(self::ROUTE_SPLITTERADD, $this->splittersTypesList, '');
        $inputs .= wf_HiddenInput(self::ROUTE_BOXEDIT, $boxID);
        $inputs .= wf_Submit(__('Append'));
        $result = wf_Form('', 'POST', $inputs, 'glamour') . wf_delimiter(0);
        return ($result);
    }

    /**
     * Renders splitters list
     *
     * @param $boxID
     *
     * @return string
     *
     * @throws Exception
     */
    public function renderSplittersList($boxID) {
        $result = '';
        $boxID = ubRouting::filters($boxID, 'int');
        if (isset($this->allBoxes[$boxID])) {
            if (!empty($this->allSplittersLinks)) {
                $curBoxSplitters = array();
                foreach ($this->allSplittersLinks as $io => $each) {
                    if ($each['boxid'] == $boxID) {
                        $curBoxSplitters[] = $each;
                    }
                }

                if (!empty($curBoxSplitters)) {
                    $cells = wf_TableCell(__('Splitter') . ' / ' . __('Coupler'));
                    $cells .= wf_TableCell(__('Actions'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($curBoxSplitters as $io => $each) {
                        $cells = wf_TableCell($this->splittersTypesList[$each['splitter']]);
                        $delLinkUrl = self::URL_ME . '&' . self::ROUTE_SPLITTERDEL . '=' . $each['id'] . '&' . self::ROUTE_BOXNAV . '=' . $each['boxid'];
                        $actLinks = wf_JSAlert($delLinkUrl, web_delete_icon(), $this->messages->getDeleteAlert());
                        $cells .= wf_TableCell($actLinks);
                        $rows .= wf_TableRow($cells, 'row5');
                    }

                    $result .= wf_TableBody($rows, '50%', 0, 'sortable');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('box') . ' [' . $boxID . '] ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Adds a splitter to PON box
     *
     * @return string
     *
     * @throws Exception
     */
    public function addSplitter() {
        $result = '';

        if (ubRouting::checkPost(array(self::ROUTE_BOXEDIT, self::ROUTE_SPLITTERADD))) {
            $boxID = ubRouting::post(self::ROUTE_BOXEDIT, 'int');
            $newSplitterName = ubRouting::post(self::ROUTE_SPLITTERADD);
            $newSplitterNameF = ubRouting::filters($newSplitterName, 'mres');

            if (isset($this->allBoxes[$boxID])) {
                $this->splittersLinks->data('splitter', $newSplitterNameF);
                $this->splittersLinks->data('boxid', $boxID);
                $this->splittersLinks->create();
                log_register('PONBOX ADD SPLITTER TO BOX [' . $boxID . '] TYPE `' . $newSplitterName . '`');
            } else {
                $result .= __('Something went wrong') . ': ' . __('box') . ' [' . $boxID . '] ' . __('Not exists');
            }
        }

        return ($result);
    }

    /**
     * Generates a crosslink warning when a certain ONU is linked to several PON boxes
     *
     * @param false $userProfile
     *
     * @return string
     */
    public function renderCrossLinkWarning($userProfile = false) {
        $result = '';
        $message = __('More then one ponbox link exists for current ONU - does it live in separate multiverses?');

        if ($userProfile) {
            $result = wf_tag('span', false, '', 'style="color: #796616; font-size: 14px;"') . $message . wf_tag('span', true);
        } else {
            $result = $this->messages->getStyledMessage($message, 'warning');
        }

        return ($result);
    }

    /**
     * Rendering images processing form and controls
     *
     * @param $boxID
     *
     * @return string
     */
    public function renderBoxImageControls($boxID) {
        $photoStorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $boxID);
        $inputs = $photoStorage->renderUploadForm(true, base64_encode(self::URL_ME . '&' . self::ROUTE_BOXEDIT . '=' . $boxID));
        $inputs .= $photoStorage->renderImagesList();
        return ($inputs);
    }

}
