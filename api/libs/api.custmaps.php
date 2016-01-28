<?php

class CustomMaps {

    protected $allMaps = array();
    protected $allItems = array();
    protected $ymapsCfg = array();
    protected $altCfg = array();
    protected $itemTypes = array();
    protected $center = '';
    protected $zoom = '';

    const UPLOAD_PATH = 'exports/';
    const EX_NO_MAP_ID = 'NOT_EXISTING_MAP_ID';
    const EX_NO_ITM_ID = 'NOT_EXISTING_ITEM_ID';
    const EX_NO_FILE = 'NOT_EXISTING_FILE';
    const EX_WRONG_EXT = 'WRONG_FILE_EXTENSION';
    const EX_WRONG_KML = 'WRONG_KML_FILE_FORMAT';

    public function __construct() {
        $this->loadYmapsConfig();
        $this->loadAlterConfig();
        $this->setDefaults();
        $this->setItemTypes();
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
        $query = "SELECT * from `custmaps` ORDER by `id` ASC";
        $all = simple_queryall($query);
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
            'optrec' => __('Optical reciever')
        );
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
        $query = "SELECT * from `custmapsitems`";
        $all = simple_queryall($query);
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
        $result.=wf_Link('?module=custmaps', __('Back'), false, 'ubButton');
        if (wf_CheckGet(array('showmap'))) {
            $mapId = $_GET['showmap'];
            if (cfr('CUSTMAPEDIT')) {
                $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&mapedit=true', wf_img('skins/ymaps/edit.png') . ' ' . __('Edit'), false, 'ubButton');
            }

            //custom layers
            if (wf_CheckGet(array('cl'))) {
                $custLayers = $_GET['cl'];
            } else {
                $custLayers = '';
            }

            //system layers
            if (wf_CheckGet(array('layers'))) {
                $curLayers = $_GET['layers'];
            } else {
                $curLayers = '';
            }

            $result.=wf_Link('?module=custmaps&showitems=' . $mapId, wf_img('skins/icon_table.png') . ' ' . __('Objects'), false, 'ubButton');
            $result.=wf_delimiter();
            $result.=wf_Link('?module=custmaps&showmap=' . $mapId, wf_img('skins/icon_cleanup.png') . ' ' . $this->mapGetName($mapId), false, 'ubButton');
            foreach ($this->allMaps as $cmapId => $cmapData) {
                if ($cmapId != $mapId) {
                    $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&layers=' . $curLayers . '&cl=' . $cmapId . 'z' . $this->filterLayers($custLayers, $cmapId . 'z'), wf_img('skins/swmapsmall.png') . ' ' . $this->mapGetName($cmapId), false, 'ubButton');
                }
            }
            $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&layers=bs' . $this->filterLayers($curLayers, 'bs') . '&cl=' . $custLayers, wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
            $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&layers=sw' . $this->filterLayers($curLayers, 'sw') . '&cl=' . $custLayers, wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
            $result.=wf_Link('?module=custmaps&showmap=' . $mapId . '&layers=ul' . $this->filterLayers($curLayers, 'ul') . '&cl=' . $custLayers, wf_img('skins/ymaps/uplinks.png') . ' ' . __('Show links'), false, 'ubButton');
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
     * Returns item edit form
     * 
     * @param int $itemid
     * @return string
     */
    public function itemEditForm($itemid) {
        $itemid = vf($itemid, 3);
        $result = '';
        if (isset($this->allItems[$itemid])) {
            $result.= wf_Link('?module=custmaps&showitems=' . $this->allItems[$itemid]['mapid'], __('Back'), false, 'ubButton');
            $result.= wf_delimiter();
            $inputs = wf_HiddenInput('edititemid', $itemid);
            $inputs.= wf_Selector('edititemtype', $this->itemTypes, __('Type'), $this->allItems[$itemid]['type'], true);
            $inputs.= wf_TextInput('edititemgeo', __('Geo location'), $this->allItems[$itemid]['geo'], true, '20');
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
        $itemid = vf($itemid, 3);
        if (isset($this->allItems[$itemid])) {
            simple_update_field('custmapsitems', 'name', $name, "WHERE `id`='" . $itemid . "'");
            simple_update_field('custmapsitems', 'type', $type, "WHERE `id`='" . $itemid . "'");
            simple_update_field('custmapsitems', 'geo', $geo, "WHERE `id`='" . $itemid . "'");
            simple_update_field('custmapsitems', 'location', $location, "WHERE `id`='" . $itemid . "'");
            log_register('CUSTMAPS EDIT ITEM [' . $itemid . ']');
        } else {
            throw new Exception(self::EX_NO_ITM_ID);
        }
    }

    /**
     * Returns items import form
     * 
     * @return string
     */
    protected function itemsImportForm() {
        $inputs = wf_tag('form', false, 'glamour', 'action="" enctype="multipart/form-data" method="POST"');
        $inputs.= wf_tag('input', false, '', 'type="file" name="itemsUploadFile"');
        $inputs.= wf_tag('br');
        $inputs.= wf_Selector('itemsUploadTypes', $this->itemTypes, __('Type'), '', true);
        $inputs.= wf_Submit(__('Upload'));
        $inputs.= wf_tag('form', true);

        $result = $inputs;
        return ($result);
    }

    /**
     * Catches file upload
     * 
     * @return string
     */
    public function catchFileUpload() {
        $result = '';
        $allowedExtensions = array("kml", "txt");
        $fileAccepted = true;
        foreach ($_FILES as $file) {
            if ($file['tmp_name'] > '') {
                if (@!in_array(end(explode(".", strtolower($file['name']))), $allowedExtensions)) {
                    $fileAccepted = false;
                }
            }
        }

        if ($fileAccepted) {
            $newFilename = zb_rand_string(10) . '_custmap.kml';
            $newSavePath = self::UPLOAD_PATH . $newFilename;
            move_uploaded_file($_FILES['itemsUploadFile']['tmp_name'], $newSavePath);
            if (file_exists($newSavePath)) {
                $uploadResult = wf_tag('span', false, 'alert_success') . __('Upload complete') . wf_tag('span', true);
                $result = $newFilename;
            } else {
                $uploadResult = wf_tag('span', false, 'alert_error') . __('Upload failed') . wf_tag('span', true);
            }
        } else {
            $uploadResult = wf_tag('span', false, 'alert_error') . __('Upload failed') . ': ' . self::EX_WRONG_EXT . wf_tag('span', true);
        }

        show_window('', $uploadResult);
        if ($result) {
            $this->itemsImportKml($newFilename, $_GET['showitems'], $_POST['itemsUploadTypes']);
        }
        return ($result);
    }

    /**
     * Extract placemarks to import
     * 
     * @param array $data
     * @return array
     */
    protected function kmlExtractPlacemarks($data) {
        $result = array();
        $i = 0;
        if (!empty($data)) {
            foreach ($data as $io => $each) {
                if (isset($each['Point'])) {
                    @$result[$i]['name'] = trim($each['name']);
                    $coordsRaw = trim($each['Point']['coordinates']);
                    $coordsRaw = explode(',', $coordsRaw);
                    $result[$i]['geo'] = $coordsRaw[1] . ', ' . $coordsRaw[0];
                    $i++;
                }
            }
        }
        return ($result);
    }

    /**
     * Performs import of uploaded KML file
     * 
     * @param string $filename
     */
    protected function itemsImportKml($filename, $mapId, $type) {
        $mapId = vf($mapId, 3);
        $type = vf($type);
        $toImport = array();
        $importCount = 0;

        if (file_exists(self::UPLOAD_PATH . '/' . $filename)) {
            $rawData = file_get_contents(self::UPLOAD_PATH . '/' . $filename);
            if (!empty($rawData)) {
                $rawData = zb_xml2array($rawData);

                if (isset($this->allMaps[$mapId])) {
                    if (!empty($rawData)) {
                        if (isset($rawData['kml'])) {
                            if (isset($rawData['kml']['Document'])) {
                                $importDocument = $rawData['kml']['Document'];
                                if (!empty($importDocument)) {
                                    //turbo GPS 3 broken format
                                    foreach ($importDocument as $io => $each) {
                                        if ($io == 'Placemark') {
                                            $toImport = $each;
                                        } else {
                                            //natural google earth format
                                            if (is_array($each)) {
                                                foreach ($each as $ia => $deeper) {
                                                    if ($ia == 'Placemark') {
                                                        $toImport = $deeper;
                                                    }
                                                }
                                            }
                                        }
                                        //extracting placemarks
                                        if (!empty($toImport)) {
                                            $placemarks = $this->kmlExtractPlacemarks($toImport);
                                            if (!empty($placemarks)) {
                                                foreach ($placemarks as $ix => $importPm) {
                                                    $this->itemCreate($mapId, $type, $importPm['geo'], $importPm['name'], '');
                                                    $importCount++;
                                                }
                                                show_info(__('Objects') . ': ' . $importCount);
                                                show_window('', wf_Link('?module=custmaps&showitems=' . $mapId, wf_img('skins/refresh.gif') . ' ' . __('Renew'), false, 'ubButton'));
                                            }
                                        }
                                    }
                                }
                            } else {
                                show_error(self::EX_WRONG_KML);
                            }
                        } else {
                            show_error(self::EX_WRONG_KML);
                        }
                    } else {
                        show_warning(__('Empty file') . ' ' . self::EX_WRONG_KML);
                    }
                } else {
                    show_error(self::EX_NO_MAP_ID);
                }
            } else {
                show_warning(__('Empty file') . ' (.kml)');
            }
        } else {
            show_error(self::EX_NO_FILE);
        }
    }

    /**
     * Returns existing map items list view
     * 
     * @return string
     */
    public function renderItemsList($mapid) {
        $mapid = vf($mapid, 3);
        $messages = new UbillingMessageHelper();
        $itemsCount = 0;

        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('CUSTMAPITEMS');
            $adc = true;
        } else {
            $adc = false;
        }


        $result = '';
        $result.= wf_Link('?module=custmaps', __('Back'), false, 'ubButton');
        if (cfr('CUSTMAPEDIT')) {
            $result.= wf_modalAuto(wf_img('skins/photostorage_upload.png') . ' ' . __('Upload file from HDD'), __('Upload') . ' KML', $this->itemsImportForm(), 'ubButton');
        }

        $result.=wf_Link('?module=custmaps&showitems=' . $mapid . '&duplicates=true', wf_img('skins/duplicate_icon.gif') . ' ' . __('Show duplicates'), true, 'ubButton');

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

        $result.= wf_TableBody($rows, '100%', '0', 'sortable');
        $result.= __('Total') . ': ' . $itemsCount;
        return ($result);
    }

    /**
     * Returns list of duplicate coords/name items for some existing map
     * 
     * @param int $mapid
     * @return string
     */
    public function renderItemDuplicateList($mapid) {
        $mapid = vf($mapid, 3);
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

        $result.= wf_Link('?module=custmaps&showitems=' . $mapid, __('Back'), false, 'ubButton');

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
        $itemid = vf($itemid, 3);
        $result = '';
        if (isset($this->allItems[$itemid])) {
            $result = $this->allItems[$itemid]['mapid'];
            $query = "DELETE from `custmapsitems` WHERE `id`='" . $itemid . "';";
            nr_query($query);
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
            $query = "DELETE from `custmapsitems` WHERE `id`='" . $id . "';";
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
            case 'box':
                $result = 'twirl#greyIcon';
                break;
            case 'amplifier':
                $result = 'twirl#pinkDotIcon';
                break;
            case 'optrec':
                $result = 'twirl#nightDotIcon';
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
                    $content = $this->itemGetTypeName($each['type']) . ': ' . $each['name'];
                    $controls = wf_Link('?module=custmaps&edititem=' . $each['id'], web_edit_icon(), false);
                    $controls = str_replace("'", '`', $controls);
                    $controls = str_replace("\n", '', $controls);
                    $result.=$this->mapAddMark($each['geo'], $each['location'], $content, $controls, $icon, '');
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
     * Returns map circle
     * 
     * @param $coords - map coordinates
     * @param $radius - circle radius in meters
     * 
     * @return string
     *  
     */
    public function mapAddCircle($coords, $radius, $content = '', $hint = '') {
        $result = '
             myCircle = new ymaps.Circle([
                    [' . $coords . '],
                    ' . $radius . '
                ], {
                    balloonContent: "' . $content . '",
                    hintContent: "' . $hint . '"
                }, {
                    draggable: true,
             
                    fillColor: "#00a20b55",
                    strokeColor: "#006107",
                    strokeOpacity: 0.5,
                    strokeWidth: 1
                });
    
            myMap.geoObjects.add(myCircle);
            ';

        return ($result);
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
        $result.= wf_tag('script', false, '', 'src="https://api-maps.yandex.ru/2.0/?load=package.full&lang=' . $this->ymapsCfg['LANG'] . '"  type="text/javascript"');
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