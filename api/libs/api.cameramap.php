<?php

/**
 * Visor cameras map service based on MapCore API.
 */
class CameraMap {

    /**
     * Ubilling configuration provider.
     *
     * @var object
     */
    protected $ubillingConfig = null;

    /**
     * Cameras database abstraction layer.
     *
     * @var object
     */
    protected $camsDb = null;

    /**
     * Visor users database abstraction layer.
     *
     * @var object
     */
    protected $usersDb = null;

    /**
     * MapCore instance.
     *
     * @var object
     */
    protected $mapCore = null;

    /**
     * All stargazer users data as login=>data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * All visor users as id=>data
     *
     * @var array
     */
    protected $allVisorUsers = array();

    /**
     * All visor cameras as id=>data
     *
     * @var array
     */
    protected $allCams = array();

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

    const ICON_CAMERA = 'marker.camera';

    /**
     * Creates CameraMap service instance.
     *
     * @global object $ubillingConfig
     */
    public function __construct() {
        global $ubillingConfig;
        $this->ubillingConfig = $ubillingConfig;
        $this->initDb();
        $this->initMapCore();
        $this->loadData();
    }

    /**
     * Inits cameras and visor users database abstraction layers.
     *
     * @return void
     */
    protected function initDb() {
        $this->camsDb = new NyanORM(UbillingVisor::TABLE_CAMS);
        $this->usersDb = new NyanORM(UbillingVisor::TABLE_USERS);
    }

    /**
     * Inits MapCore instance.
     *
     * @return void
     */
    protected function initMapCore() {
        $this->mapCore = new MapCore('cameramap');
        if ($this->ubillingConfig->getAlterParam('SWYMAP_MCLSTR')) {
            $this->mapCore->setClustering(true, $this->clustringOptions);
        }
        if ($this->ubillingConfig->getAlterParam('SWYMAP_MCFMRKS')) {
            $this->mapCore->setForceCanvasMarkers(true);
        }
    }

    /**
     * Loads cameras, visor users and stargazer user data.
     *
     * @return void
     */
    protected function loadData() {
        $this->allCams = $this->camsDb->getAll('id');
        $this->allVisorUsers = $this->usersDb->getAll('id');
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Draws camera markers with geo from camera account build coordinates.
     *
     * @return void
     */
    public function drawCameras() {
        $footerDelimiter = wf_tag('br');
        $visorIcon = wf_img('skins/icon_camera_small.png', __('Video surveillance user profile'));
        $profileIcon = wf_img('skins/icon_user_16.gif', __('Profile'));

        if (!empty($this->allCams)) {
            foreach ($this->allCams as $io => $each) {
                $login = $each['login'];
                $cameraUserData = @$this->allUserData[$login];
                $geo = @$cameraUserData['geo'];
                if (!empty($geo)) {
                    $geo = ubRouting::filters($geo, 'mres');
                    $fullAddress = @$cameraUserData['fulladress'];
                    $visorRealName = @$this->allVisorUsers[$each['visorid']]['realname'];
                    

                    $title = __('Camera').' '.$each['id'];
                    
                    $content = '';
                    if (!empty($fullAddress)) {
                        $content .= ubRouting::filters($fullAddress, 'mres');
                    }
                    if (!empty($visorRealName)) {
                        if (!empty($content)) {
                            $content .= $footerDelimiter;
                        }
                      
                    }
                    if (empty($content)) {
                        $content = __('Camera') . ' ' . $each['id'];
                    }

                    $tooltip = $fullAddress;
                    if (empty($tooltip)) {
                        $tooltip = $login;
                    }

                    $footer = '';
                    $footer .= wf_Link(UbillingVisor::URL_ME . UbillingVisor::URL_USERVIEW . $each['visorid'], $visorIcon . ' ' . $visorRealName);
                    $footer .= $footerDelimiter;
                    $footer .= wf_Link(UbillingVisor::URL_CAMPROFILE . $login, $profileIcon . ' ' . $fullAddress);

                    $markerOptions = array(
                        'icon' => self::ICON_CAMERA,
                        'tooltip' => $tooltip,
                        'popupTitle' => $title,
                        'popupFooter' => $footer
                    );
                    $this->mapCore->addMarker($geo, $content, $markerOptions);
                }
            }
        }
    }

    /**
     * Renders map container block.
     *
     * @return void
     */
    public function renderMapContainer() {
        $container = $this->mapCore->renderContainer('100%', '700px');
        $controls = '';
        $controls .= wf_Link(UbillingVisor::URL_ME . UbillingVisor::URL_USERS, wf_img('skins/icon_camera_small.png') . ' ' . __('Surveillance users'), false, 'ubButton') . ' ';
        $controls .= wf_Link(UbillingVisor::URL_ME . UbillingVisor::URL_CAMS, wf_img('skins/photostorage.png') . ' ' . __('Cams'), false, 'ubButton');
        $controls .= wf_delimiter(1);
        show_window(__('Cameras map'), $controls . $container);
    }

    /**
     * Creates configured MapCore instance with camera markers.
     *
     * @return object
     */
    public function getMapCore() {
        $ymconf = $this->ubillingConfig->getYmaps();
        $ymCenter = $ymconf['CENTER'];
        $ymZoom = $ymconf['ZOOM'];
        $ymType = $ymconf['TYPE'];

        $this->mapCore->setZoom($ymZoom);
        $this->mapCore->setType($ymType);
        if (!empty($ymCenter)) {
            $this->mapCore->setCenter($ymCenter);
        }

        $this->drawCameras();

        $result = $this->mapCore;
        return ($result);
    }

    /**
     * Renders full cameras map page output.
     *
     * @return void
     */
    public function render() {
        $this->renderMapContainer();
        $mapCore = $this->getMapCore();
        show_window('', $mapCore->render());
    }
}
