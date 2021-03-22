<?php

/**
 * PON Boxes allows to place/render some boxes on map
 */
class PONBoxes {

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
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Routes, static defines etc
     */
    const URL_ME = '?module=ponboxes';
    const PROUTE_NEWBOXNAME = 'newboxname';
    const PROUTE_NEWBOXGEO = 'newboxgeo';
    const PROUTE_NEWLINKBOX = 'newlinkboxid';
    const PROUTE_NEWLINKTYPE = 'newlinktype';
    const PROUTE_NEWLINKONU = 'newlinkonu';
    const ROUTE_BOXLIST = 'ajboxes';
    const ROUTE_BOXNAV = 'boxidnav';
    const ROUTE_MAP = 'boxmap';
    const ROUTE_BOXEDIT = 'editboxid';
    const ROUTE_BOXDEL = 'deleteboxid';
    const ROUTE_LINKDEL = 'deletelinkid';
    const TABLE_BOXES = 'ponboxes';
    const TABLE_LINKS = 'ponboxeslinks';
    const TABLE_PONONU = 'pononu';

    /**
     * Creates new PONBoxes instance
     * 
     * @param bool $loadFullData
     * 
     * @return void
     */
    public function __construct($loadFullData = false) {
        $this->initMessages();
        $this->initDatabase();
        if ($loadFullData) {
            $this->loadBoxes();
            $this->loadLinks();
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
     * Inits all required database abstraction layers for further usage
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->boxes = new NyanORM(self::TABLE_BOXES);
        $this->links = new NyanORM(self::TABLE_LINKS);
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

    /**
     * Renders all available boxes list container
     * 
     * @return string
     */
    public function renderBoxesList() {
        $result = '';
        $columns = array('ID', 'Name', 'Location', 'Actions');
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&' . self::ROUTE_BOXLIST . '=true', false, __('boxes'), 100, $opts);
        return($result);
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
                $data[] = $each['id'];
                $data[] = $each['name'];
                $data[] = $each['geo'];
                $boxActs = '';
                $boxActs .= wf_Link(self::URL_ME . '&' . self::ROUTE_BOXEDIT . '=' . $each['id'], web_edit_icon());
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
        $inputs .= wf_TextInput('newboxgeo', __('Location'), '', true, 20, 'geo');
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders existing box editing form aka "Box profile"
     * 
     * @param int $boxId
     * 
     * @return string
     */
    public function renderBoxEditForm($boxId) {
        $boxid = ubRouting::filters($boxId, 'int');
        $result = '';
        if (isset($this->allBoxes[$boxId])) {
            $boxData = $this->allBoxes[$boxId];
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs = wf_HiddenInput('editboxid', $boxId);
            $inputs .= wf_TextInput('editboxname', __('Name') . $sup, $boxData['name'], true, 20);
            $inputs .= wf_TextInput('editboxgeo', __('Location'), $boxData['geo'], true, 20, 'geo');
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_delimiter(0);
            $result .= wf_BackLink(self::URL_ME);
            if (!$this->isBoxProtected($boxId)) {
                $boxDelControlUrl = self::URL_ME . '&' . self::ROUTE_BOXDEL . '=' . $boxid;
                $result .= ' ' . wf_JSAlert($boxDelControlUrl, web_delete_icon() . ' ' . __('Delete'), $this->messages->getDeleteAlert(), '', 'ubButton');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('box') . ' [' . $boxId . '] ' . __('Not exists'), 'error');
        }
        return($result);
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

                if ($newBoxGeoF != $boxData['geo']) {
                    $this->boxes->data('geo', $newBoxGeoF);
                    $this->boxes->where('id', '=', $boxId);
                    $this->boxes->save();
                    log_register('PONBOX CHANGE BOX [' . $boxId . '] GEO');
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('box') . ' [' . $boxId . '] ' . __('Not exists');
            }
        }
        return($result);
    }

    /**
     * Check is box name alredy user or not?
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
        return($result);
    }

    /**
     * Creates new PON box in database
     * 
     * @param string $name
     * @param string $geo
     * 
     * @return void/string on error
     */
    public function createBox($name, $geo = '') {
        $result = '';
        $nameF = ubRouting::filters($name, 'mres');
        $geoF = ubRouting::filters($geo, 'mres');
        if (!empty($nameF)) {
            if ($this->isBoxNameFree($nameF)) {
                $this->boxes->data('name', $nameF);
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
        return($result);
    }

    /**
     * Renders default controls panel
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create'), __('Create'), $this->renderBoxCreateForm(), 'ubButton') . ' ';
        if (ubRouting::checkGet(self::ROUTE_MAP) OR ubRouting::checkGet(self::ROUTE_BOXEDIT)) {
            $result .= wf_Link(self::URL_ME, wf_img('skins/icon_table.png') . ' ' . __('List'), false, 'ubButton');
        } else {
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_MAP . '=true', wf_img('skins/ponmap_icon.png') . ' ' . __('Map'), false, 'ubButton');
        }
        return($result);
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
        return($result);
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
        return($result);
    }

    /**
     * Delete link from database
     * 
     * @param int $linkId
     * 
     * @return void/string on error
     */
    public function deleteLink($linkId) {
        $linkId = ubRouting::filters($linkId, 'int');
        $result = '';
        if (isset($this->allLinks[$linkId])) {
            $linkData = $this->allLinks[$linkId];
            $this->links->where('id', '=', $linkId);
            $this->links->delete();
            log_register('PONBOX DELETE LINK [' . $linkId . '] BOX [' . $linkData['boxid'] . ']');
        } else {
            $result .= __('Something went wrong') . ': ' . __('Link') . ' [' . $linkId . '] ' . __('Not exists');
        }
        return($result);
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
            $result .= generic_MapContainer('100%', '800px;', $mapContainer);
            $placemarks = '';
            $editor = '';
            foreach ($this->allBoxes as $io => $each) {
                if (!empty($each['geo'])) {
                    $placemarks .= generic_mapAddMark($each['geo'], '', $each['name'], '', '', '', true);
                }
            }
            $result .= generic_MapInit($mapsCfg['CENTER'], $mapsCfg['ZOOM'], $mapsCfg['TYPE'], $placemarks, $editor, $mapsCfg['LANG'], $mapContainer);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Creates box->entity link in database
     * 
     * @param int $boxId existing PON Box ID
     * @param string $type link type: login,address,onuid
     * @param string $param link parameter
     * 
     * @return void/string on error
     */
    protected function createLink($boxId, $type, $param) {
        $result = '';
        $boxId = ubRouting::filters($boxId, 'int');
        $paramF = ubRouting::filters($param, 'mres');
        if (isset($this->allBoxes[$boxId])) {
            $saveLink = true;
            switch ($type) {
                case 'login':
                    $useField = 'login';
                    break;
                case 'address':
                    $useField = 'address';
                    break;
                case 'onuid':
                    $useField = 'onuid';
                    break;
                default:
                    $saveLink = false;
                    $result .= __('Unknown link type') . ' ' . $type;
                    break;
            }

            if ($saveLink) {
                $this->links->data('boxid', $boxId);
                $this->links->data($useField, $paramF);
                $this->links->create();
                $newId = $this->links->getLastId();
                log_register('PONBOX CREATE LINK [' . $newId . '] BOX [' . $boxId . '] TYPE `' . $type . '`  TO `' . $param . '`');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('box') . ' [' . $boxId . '] ' . __('Not exists');
        }
        return($result);
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
                    if ($eachLink['onuid'] == $onuData) {
                        $result[$eachLink['boxid']] = $eachLink['boxid'];
                    }

                    if (!empty($onuUser)) {
                        //fast and dirty address search
                        $allUserAddress = zb_AddressGetFulladdresslistCached();
                        $onuUserAddress = @$allUserAddress[$onuUser];

                        if ($eachLink['address'] == $onuUserAddress) {
                            $result[$eachLink['boxid']] = $eachLink['id'];
                        }

                        //direct login seach
                        if ($eachLink['login'] == $onuUser) {
                            $result[$eachLink['boxid']] = $eachLink['id'];
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Renders linked lined boxes list
     * 
     * @param array $boxesArray
     * 
     * @return string
     */
    public function renderLinkedBoxes($boxesArray) {
        $result = '';
        if (!empty($boxesArray)) {
            foreach ($boxesArray as $boxId => $linkId) {
                $boxName = $this->allBoxes[$boxId]['name'];
                $boxLink = wf_Link(self::URL_ME . '&' . self::ROUTE_BOXEDIT . '=' . $boxId, $boxName);
                $result .= $this->messages->getStyledMessage(__('Box') . ': ' . $boxLink, 'success');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('PON Boxes') . ': ' . __('Nothing to show'), 'info');
        }
        return($result);
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
        return($result);
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
        return($result);
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
                $inputs .= wf_Selector(self::PROUTE_NEWLINKBOX, $boxesTmp, __('box'), '', false, false) . ' ';
                if (!empty($onuUserName)) {
                    $inputs .= wf_RadioInput(self::PROUTE_NEWLINKTYPE, __('ONU'), 'onuid', false, true) . ' ';
                    $inputs .= wf_RadioInput(self::PROUTE_NEWLINKTYPE, __('User'), 'login', false, false) . ' ';
                    $inputs .= wf_RadioInput(self::PROUTE_NEWLINKTYPE, __('Address'), 'address', false, false) . ' ';
                } else {
                    $inputs .= wf_HiddenInput(self::PROUTE_NEWLINKTYPE, 'onuid');
                }


                $inputs .= wf_Submit(__('Create new PON box link'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            }
        }


        return($result);
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
                    $result .= $this->createLink($boxId, $linkType, $onuId);
                }

                if ($linkType == 'login' OR $linkType == 'address') {
                    $userLogin = trim($onuData['login']);
                    if (!empty($userLogin)) {
                        $userData = zb_UserGetAllData($userLogin);
                        if (!empty($userData)) {
                            $userAddress = $userData[$userLogin]['fulladress'];
                            //login linking
                            if ($linkType == 'login') {
                                $result .= $this->createLink($boxId, $linkType, $userLogin);
                            }
                            //address linking
                            if ($linkType == 'address') {
                                if (!empty($userAddress)) {
                                    $result .= $this->createLink($boxId, $linkType, $userAddress);
                                } else {
                                    $result .= __('Something went wrong') . ': ' . __('Address') . ' ' . __('Empty');
                                }
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

        return($result);
    }

}
