<?php

class PONBoxes {

    /**
     * Contains all available PON boxes as id=>boxdata
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
    const ROUTE_BOXLIST = 'ajboxes';
    const ROUTE_MAP = 'boxmap';
    const ROUTE_BOXEDIT = 'editboxid';
    const ROUTE_LINKDEL = 'deletelinkid';
    const TABLE_BOXES = 'ponboxes';
    const TABLE_LINKS = 'ponboxeslinks';

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
        //               3  .  ">            .       Y  -MEOW!
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
                $boxActs = ''; //TODO: deletion control here
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
     * Renders existing box editing form
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
                    $placemarks .= generic_mapAddMark($each['geo'], $each['name'], 'TODO CONTENT', 'TODO FOOTER', '', '', true);
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
    public function createLink($boxId, $type, $param) {
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
                log_register('PONBOX LINK BOX [' . $boxId . ']  TO `' . $param . '`');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('box') . ' [' . $boxId . '] ' . __('Not exists');
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
                        $actLinks = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_LINKDEL . '=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
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

}
