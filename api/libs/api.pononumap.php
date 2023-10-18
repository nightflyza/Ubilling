<?php

/**
 * Performs rendering of exiting user-assigned PON ONU devices on coverage map
 */
class PONONUMap {

    /**
     * Contains ymaps config as key=>value
     *
     * @var array
     */
    protected $mapsCfg = array();

    /**
     * Contains all available users data as login=>userdata
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * PONizer object placeholder
     *
     * @var object
     */
    protected $ponizer = '';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains optional OLT filter
     *
     * @var int
     */
    protected $filterOltId = '';

    /**
     * Contains optional offline onu dereg reason filter substring.
     * Only offline ONUs will be rendered if set.
     *
     * @var string
     */
    protected $onuDeregFilter = '';

    /**
     * Concatenate builds with similar geo coordinates?
     * 
     * @var bool
     */
    protected $clusterBuilds = false;

    /**
     * Predefined routes, URLs etc.
     */
    const URL_ME = '?module=ponmap';
    const ROUTE_FILTER_OLT = 'oltidfilter';
    const ROUTE_FILTER_DEREG = 'deregfilter';
    const PROUTE_OLTSELECTOR = 'renderoltidonus';
    const ROUTE_BACKLINK = 'bl';
    const ROUTE_CLUSTER_BUILDS = 'showbuilds';

    /**
     * Creates new ONU MAP instance
     * 
     * @return void
     */
    public function __construct($oltId = '') {
        $this->loadConfigs();
        $this->setBuildsClusterer();
        $this->setOltIdFilter($oltId);
        $this->setOnuDeregFilter();
        $this->initMessages();
        $this->initPonizer();
        $this->loadUsers();
    }

    /**
     * Sets current instance OLT filter
     * 
     * @param int $oltId
     * 
     * @return void
     */
    protected function setOltIdFilter($oltId = '') {
        if (!empty($oltId)) {
            $this->filterOltId = $oltId;
        }
    }

    /**
     * Sets optional ONU dereg reason filter.
     * 
     * @return void
     */
    protected function setOnuDeregFilter() {
        if (ubRouting::checkGet(self::ROUTE_FILTER_DEREG)) {
            $this->onuDeregFilter = ubRouting::get(self::ROUTE_FILTER_DEREG);
        }
    }

    /**
     * Sets optional builds clustering
     * 
     * @return void
     */
    protected function setBuildsClusterer() {
        if (ubRouting::checkGet(self::ROUTE_CLUSTER_BUILDS)) {
            $this->clusterBuilds = true;
        }
    }

    /**
     * Loads required config files into protected properties
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->mapsCfg = $ubillingConfig->getYmaps();
    }

    /**
     * Inits PONizer object instance
     * 
     * @return void
     */
    protected function initPonizer() {
        $this->ponizer = new PONizer($this->filterOltId);
    }

    /**
     * Inits message helper object instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Preloads available users data for further usage
     * 
     * @return void
     */
    protected function loadUsers() {
        $this->allUserData = zb_UserGetAllDataCache();
    }

    /**
     * Returns MAP icon type due signal level
     * 
     * @param string $onuSignal
     * 
     * @return string
     */
    protected function getIcon($onuSignal) {
        $result = 'twirl#greenIcon';
        if ((($onuSignal > -27) AND ( $onuSignal < -25))) {
            $result = 'twirl#orangeIcon';
        }
        if ((($onuSignal > 0) OR ( $onuSignal < -27))) {
            $result = 'twirl#redIcon';
        }
        if ($onuSignal == 'NO' OR $onuSignal == 'Offline' OR $onuSignal == '-9000') {
            $result = 'twirl#greyIcon';
        }
        return($result);
    }

    /**
     * Returns ONU controls
     * 
     * @param int $onuId
     * @param string $login
     * @param string $buildGeo
     * 
     * @return string
     */
    protected function getONUControls($onuId, $login, $buildGeo) {
        $result = '';
        if (!empty($onuId)) {
            $result .= wf_Link(PONizer::URL_ME . '&editonu=' . $onuId, wf_img('skins/switch_models.png', __('Edit') . ' ' . __('ONU')));
            $result = trim($result) . wf_nbsp();
            $result .= wf_Link('?module=userprofile&username=' . $login, wf_img('skins/icons/userprofile.png', __('User profile')));
            $result = trim($result) . wf_nbsp();
            $result .= wf_Link('?module=usersmap&findbuild=' . $buildGeo, wf_img('skins/icon_build.gif', __('Build')));
            $result = trim($result) . wf_nbsp();
        }
        return($result);
    }

    /**
     * Renders module controls
     * 
     * @return string
     */
    protected function renderControls() {
        $result = '';

        if (ubRouting::get(self::ROUTE_BACKLINK) == 'ponizer') {
            $result .= wf_BackLink(PONizer::URL_ONULIST) . ' ';
        } else {
            $result .= wf_BackLink(UbillingTaskbar::URL_ME);
        }
        if ($this->filterOltId) {
            $result .= wf_Link(self::URL_ME, wf_img('skins/ponmap_icon.png') . ' ' . __('All') . ' ' . __('OLT'), false, 'ubButton');
        } else {
            $result .= wf_Link(self::URL_ME, wf_img('skins/ponmap_icon.png') . ' ' . __('All') . ' ' . __('ONU'), false, 'ubButton');
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_FILTER_DEREG . '=Power', wf_img('skins/icon_poweroutage.png') . ' ' . __('Power outages') . '?', false, 'ubButton');
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_FILTER_DEREG . '=Wire', wf_img('skins/icon_cable.png') . ' ' . __('Wire issues') . '?', false, 'ubButton');
            if ($this->clusterBuilds) {
                $result .= wf_Link(self::URL_ME, wf_img('skins/switch_models.png') . ' ' . __('ONU'), false, 'ubButton');
            } else {
                $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_CLUSTER_BUILDS . '=true', web_build_icon() . ' ' . __('Builds'), false, 'ubButton');
            }
        }

        $allOlts = array('' => __('All') . ' ' . __('OLT'));
        $allOlts += $this->ponizer->getAllOltDevices();
        $inputs = wf_SelectorAC(self::PROUTE_OLTSELECTOR, $allOlts, __('OLT'), $this->filterOltId, false);
        $opts = 'style="float:right;"';
        $result .= wf_Form('', 'POST', $inputs, 'glamour', '', '', '', $opts);

        $result .= wf_delimiter(0);
        return($result);
    }

//
//       .,,.                                                                   
//       ,KMKkkkkkkkklll. ..                             ..       .,,,.         
//    .lkKKl,,,,,,,lkkKMKkKkll. ..                 ..,l,lKKkkkkkkkKMMKl.        
//   .lKKl.           .,,lkkKMKkKk,. ,l.  ,l. ,l. .kKKKkkl,,,,,,,,,lkKMKkl.     
// .,kMk.   .,,,,,.         .,,lkKMKkKMKkkKMklKMKkKKl,.       .,,,.  .,kMKl.    
// .kMk.    .,lkKMKkkl.        ...,lKMMk,llkMMklKKl.  .... .lkKKl,.    .kMl     
// .kMl         .,,lKMKkl.  .. lKl. .ll.   .ll. ..  ,lkKKklKMKl.        lmk.    
//.kMk.             .lKMMKllKKkKKKl                .kMKKMMMMMl..        lmk.    
// ,KK,              .KMKKKKklkKl..                 ,l..,lKKKKKx,l,    ,KKl.    
//.lKK,          ..,,lMK,....  ..                         ..lKkKMMl .. lMKl.    
// lMl         ..lKKKKMK,                                   .. lKKKlkl .kMk.    
//.kMl         lKKKKMk,.                                       ...lKMK, lMk.    
// lMk.      ,lkMk.,l.                                             ,lkl,KK,     
// ,KMl     .kkll,                     .,.                ..         ..,Kk,.    
// .kMk.    lMl      .lkkkkkkkl,,.    ,KMl                lk,     .lkkkkKMMK,   
//  ,KMl    ,l.     .lklkMk,,,lKMKkl,lKMMl                lMKl,,lkkkl,,kMMMMk.  
//   lMK.               lMl    .,lkkKMKl,.                .lKMMKkl.   .kMKKMK,  
//   ,KMKl.             lMk.       .kMl                     ,KMk.  .,lKKl.,KMk. 
// .,lKKkl.             ,KMKl,,,,,lKKl.      .lkkkkkkkkkl.   ,KMKkkKKkl.  lMK,  
// ,KMKk,.               .lkkkkkkkkl.        lMMMMMMMMMMMl    .,,,,,.     ,KMk. 
// .lKMMK,                                   .kMMMMMMMMMMl                 lMMl 
//  .lkKMk..l,                .ll,.           .lKMMMMMMkl.       .,ll.     lMK. 
// .,,lKKl.lMKkl.            ,KK,               .,lKMKl.           ,KK,    lMK, 
// .lKMMk,..,,,,.            lMl                  .kMl              lMl   ,KMMK,
//   ,KMMK.   ,Kl            lMl          .,,,,,lkKMMKkl,,.         lMl   lMKkl.
//  .kMkl.   ,KK,            lMl          .kMMMMKKMMMMKKMMKl.      ,KMl   lMKl. 
//  .lkKKkkl.lMKkkl.         ,KKl.         .lkKMkkMKKMkkMk,.     .lKMk.   .kMMl 
//     .lKMMl.,,,,. ..        ,KMKkl.         .lKKl..lKKl.      .kKkl.   ,kKMK, 
//      .kMMKk,    .kl.,,.     .,,lkl.   ..     ..    ..  ..   .ll.   .,,kMMk.  
//     .lKMMMKl,,,.lMKKKl.               lKl.           .lKl          lMKKMK,   
//    .kMMKkkkkkKMl.lkk,                 .kMKl,.     .,lKKl.       .lkKMl.,.    
//   .lKMMk.    lMKkKMMKk.,,              .lkKMKkkkkkKMKl.      ,k,lKKMK,       
//     ,KKl. .. .,,,,kMKl.lK, .,,.           .,,,,,,,,,.   ,;,lkKMKk,.,.        
//   .lKMk. .kl.l,   .,.  lMKkKKKklkk,.,,,,.             .,kMMKKMk,.            
//   .lKMK,.kMKkk,        ,kl,,.lMKlkKKKkKMK,.,,lllllkkkkkKKll,lMl              
//   .lKMk..,,,.                .,. lKl. .ll.lKKMKkl,lKKl...  .kMKl.            
//  .lKMk.                   ,l. .. ..       ...l,    ..     ,KMKl,.            
//  ,KMKl.  ..               lMklKl                          lMMk.              
// .lKMkl.  lk.,l.  .lx,     ,kMKl.                                             
// .lKk.    lMKKMl lKKl     
//
//
//
//
//                    ,d                            ,d     
//                    88                            88     
//        ,adPPYba, MM88MMM ,adPPYba,  ,adPPYYba, MM88MMM  
//        I8[    ""   88   a8"     "8a ""     `Y8   88     
//         `"Y8ba,    88   8b       d8 ,adPPPPP88   88     
//        aa    ]8I   88,  "8a,   ,a8" 88,    ,88   88,    
//        `"YbbdP"'   "Y888 `"YbbdP"'  `"8bbdP"Y8   "Y888  
//    

    /**
     * Returns a list of placemarks to render
     * 
     * @param array $geoArray
     * @param bool $buildsClusterer
     * 
     * @return string
     */
    protected function getPlacemarks($geoArray, $buildsClusterer = false) {
        $result = '';
        if (!empty($geoArray)) {
            foreach ($geoArray as $eachGeo => $geoData) {
                if (!empty($geoData)) {
                    if ($buildsClusterer) {
                        $buildUserCount = sizeof($geoData);
                        if ($buildUserCount > 1) {
                            $concatBuildContent = '';
                            $rows = '';
                            $cells = wf_TableCell(__('apt.'));
                            $cells .= wf_TableCell(__('User'));
                            $cells .= wf_TableCell(__('Signal'));
                            $cells .= wf_TableCell(__('Actions'));
                            $rows = wf_TableRow($cells, 'row1');
                            foreach ($geoData as $io => $eachBuild) {
                                $userLink = wf_Link(UserProfile::URL_PROFILE . $eachBuild['login'], $eachBuild['ip']);
                                $cells = wf_TableCell($eachBuild['apt']);
                                $cells .= wf_TableCell($userLink);
                                $cells .= wf_TableCell($eachBuild['signal']);
                                $cells .= wf_TableCell($eachBuild['controls']);
                                $rows .= wf_TableRow($cells);
                            }
                            $concatBuildContent .= wf_TableBody($rows, '100%', 0, '');
                            $concatBuildContent = str_replace("\n", '', $concatBuildContent);
                            $result .= generic_mapAddMark($eachBuild['geo'], $eachBuild['streetbuild'], $concatBuildContent, '', 'twirl#buildingsIcon', '', true);
                        } else {
                            $eachBuild = $geoData[0]; //just first element as is
                            $result .= generic_mapAddMark($eachBuild['geo'], $eachBuild['buildtitle'], $eachBuild['signal'], $eachBuild['controls'], $eachBuild['icon'], '', true);
                        }
                    } else {
                        foreach ($geoData as $io => $eachBuild) {
                            $result .= generic_mapAddMark($eachBuild['geo'], $eachBuild['buildtitle'], $eachBuild['signal'], $eachBuild['controls'], $eachBuild['icon'], '', true);
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Renders ONU signals Map 
     * 
     * @return string
     */
    public function renderOnuMap() {
        $result = '';
        $allOnu = $this->ponizer->getAllOnu();
        $allOnuSignals = $this->ponizer->getAllONUSignals();
        $allDeregReasons = $this->ponizer->getAllONUDeregReasons();
        $placemarks = '';
        $marksRendered = 0;
        $marksNoGeo = 0;
        $marksNoUser = 0;
        $marksDeadUser = 0;
        $totalOnuCount = 0;
        $result .= $this->renderControls();
        $renderBuilds = array();
        $result .= generic_MapContainer('', '', 'ponmap');
        if (!empty($allOnu)) {

            foreach ($allOnu as $io => $eachOnu) {
                if (!empty($eachOnu['login'])) {
                    if (isset($this->allUserData[$eachOnu['login']])) {
                        $userData = $this->allUserData[$eachOnu['login']];
                        if (!empty($userData['geo'])) {
                            if ($this->onuDeregFilter) {
                                $renderAllowedFlag = false;
                            } else {
                                $renderAllowedFlag = true;
                            }

                            $onuSignal = (isset($allOnuSignals[$eachOnu['login']])) ? $allOnuSignals[$eachOnu['login']] : 'NO';
                            $onuIcon = $this->getIcon($onuSignal);
                            $onuControls = $this->getONUControls($eachOnu['id'], $eachOnu['login'], $userData['geo']);
                            $onuTitle = $userData['fulladress'];
                            $deregState = '';
                            if ($onuSignal == 'NO' OR $onuSignal == 'Offline' OR $onuSignal == '-9000') {
                                $signalLabel = __('No signal');
                                if (isset($allDeregReasons[$eachOnu['login']])) {
                                    $deregLabel = $allDeregReasons[$eachOnu['login']]['styled'];
                                    $deregState = $allDeregReasons[$eachOnu['login']]['raw'];
                                    $signalLabel .= ' - ' . $deregLabel;
                                    if ($this->onuDeregFilter) {
                                        if (ispos($deregState, $this->onuDeregFilter)) {
                                            $renderAllowedFlag = true;
                                        }
                                    }
                                }
                            } else {
                                $signalLabel = $onuSignal;
                            }

                            //48.470554, 24.422853
                            if ($renderAllowedFlag) {
                                $renderBuilds[$userData['geo']][] = array(
                                    'geo' => $userData['geo'],
                                    'streetbuild' => $userData['streetname'] . ' ' . $userData['buildnum'],
                                    'apt' => $userData['apt'],
                                    'buildtitle' => $onuTitle,
                                    'login' => $userData['login'],
                                    'ip' => $userData['ip'],
                                    'signal' => $signalLabel,
                                    'controls' => $onuControls,
                                    'icon' => $onuIcon,
                                );

                                $marksRendered++;
                            }
                        } else {
                            $marksNoGeo++;
                        }
                    } else {
                        if ($eachOnu['login'] != 'dead') {
                            $marksNoUser++;
                        } else {
                            $marksDeadUser++;
                        }
                    }
                } else {
                    $marksNoUser++;
                }
                $totalOnuCount++;
            }
            $placemarks .= $this->getPlacemarks($renderBuilds, $this->clusterBuilds);
        }

        //rendering map

        $result .= generic_MapInit($this->mapsCfg['CENTER'], $this->mapsCfg['ZOOM'], $this->mapsCfg['TYPE'], $placemarks, '', $this->mapsCfg['LANG'], 'ponmap');
        //some stats here
        $result .= $this->messages->getStyledMessage(__('Total') . ' ' . __('ONU') . ': ' . $totalOnuCount, 'info');
        $result .= $this->messages->getStyledMessage(__('ONU rendered on map') . ': ' . $marksRendered, 'success');
        if ($marksNoGeo > 0) {
            $result .= $this->messages->getStyledMessage(__('User builds not placed on map') . ': ' . $marksNoGeo, 'warning');
        }

        if ($marksNoUser > 0) {
            $result .= $this->messages->getStyledMessage(__('ONU without assigned user') . ': ' . $marksNoUser, 'warning');
        }

        return($result);
    }

    /**
     * Returns label if rendering ONUs for only some specified OLT
     * 
     * @return string
     */
    public function getFilteredOLTLabel() {
        $result = '';
        if ($this->filterOltId) {
            $allOltDevices = $this->ponizer->getAllOltDevices();
            if (isset($allOltDevices[$this->filterOltId])) {
                $result .= ': ' . $allOltDevices[$this->filterOltId];
            }
        }

        if ($this->onuDeregFilter) {
            $onuFilterLabel = '';
            switch ($this->onuDeregFilter) {
                case 'Power':
                    $onuFilterLabel .= __('Power outages') . '?';
                    break;
                case 'Wire':
                    $onuFilterLabel .= __('Wire issues') . '?';
                    break;
            }
            $result .= ' : ' . $onuFilterLabel;
        }
        return($result);
    }
}
