<?php

class SwitchUplinks {

    /**
     * Current instance switch ID
     *
     * @var int
     */
    protected $switchId = 0;

    /**
     * Contains current switch uplink data
     *
     * @var array
     */
    protected $uplinkData = array();

    /**
     * Contains available media types markers and their names
     *
     * @var array
     */
    protected $mediaTypes = array();

    /**
     * Contains available media types icons
     *
     * @var array
     */
    protected $mediaIcons = array();

    /**
     * Contains typical uplink speed rates
     *
     * @var array
     */
    protected $speedRates = array();

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Switches uplink paramereds DB abstraction placeholder
     *
     * @var object
     */
    protected $switchUplinks = '';

    /**
     * Contains all switches uplinks detailed data as switch=>updata
     *
     * @var array
     */
    protected $allUplinksData = array();

    /**
     * Static routes, etc
     */
    const TABLE_UPLINKS = 'switchuplinks';
    const URL_SWPROFILE = '?module=switches&edit=';
    const ROUTE_SWID = 'swuplinkswitchid';
    const ROUTE_MEDIA = 'swuplinksmedia';
    const ROUTE_SPEED = 'swuplinksspeed';
    const ROUTE_PORT = 'swuplinksport';
    const ROUTE_EDITINTERFACE = 'editswuplinkparameters';
    const PATH_ICONS = 'skins/';

    /**
     * Creates new switch uplinks object instance
     * 
     * @param int/void $switchId
     */
    public function __construct($switchId = '') {
        $this->initMessages();
        $this->initDatabase();
        $this->setMediaTypes();
        $this->setSpeedRates();
        if (!empty($switchId)) {
            $this->setSwitchId($switchId);
            $this->loadUplinkData();
        }
    }

    /**
     * Sets available uplink media types
     * 
     * @return void
     */
    protected function setMediaTypes() {
        //may be configurable in future.. or not..
        $this->mediaTypes = array(
            'F' => __('Fiber optics'),
            'C' => __('Copper'),
            'W' => __('Wireless'),
        );

        $this->mediaIcons = array(
            'F' => 'linkfiber.png',
            'C' => 'linkcopper.png',
            'W' => 'linkwireless.png',
        );
    }

    /**
     * Sets typical speed rates for uplink ports
     * 
     * @return void
     */
    protected function setSpeedRates() {
        $this->speedRates = array(
            '1G' => '1 ' . __('Gbit/s'),
            '10G' => '10 ' . __('Gbit/s'),
            '40G' => '40 ' . __('Gbit/s'),
            '100M' => '100 ' . __('Mbit/s'),
            '10M' => '10 ' . __('Mbit/s'),
        );
    }

    /**
     * Inits system message helper instance for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits dabase abstraction
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->switchUplinks = new NyanORM(self::TABLE_UPLINKS);
    }

    /**
     * Current instance switchId setter
     * 
     * @param int/void $switchId
     * 
     * @return void
     */
    protected function setSwitchId($switchId = '') {
        $switchId = ubRouting::filters($switchId, 'int');
        if (!empty($switchId)) {
            $this->switchId = $switchId;
        }
    }

    /**
     * Loads current switch uplink data
     * 
     * @return void
     */
    protected function loadUplinkData() {
        if (!empty($this->switchId)) {
            $this->switchUplinks->where('switchid', '=', $this->switchId);
            $tmpData = $this->switchUplinks->getAll();
            if (!empty($tmpData)) {
                if (isset($tmpData[0])) {
                    $this->uplinkData = $tmpData[0];
                }
            }
        }
    }

    /**
     * Renders uplink parameters editing inputs
     * 
     * @return string
     */
    public function renderEditForm() {
        $result = '';
        if (!empty($this->switchId)) {
            $mediaTmp = array('' => '-');
            $mediaTmp += $this->mediaTypes;
            $speedTmp = array('' => '-');
            $speedTmp += $this->speedRates;

            $inputs = wf_HiddenInput(self::ROUTE_SWID, $this->switchId);
            $inputs .= wf_Selector(self::ROUTE_MEDIA, $mediaTmp, __('Type'), @$this->uplinkData['media'], false) . ' ';
            $inputs .= wf_Selector(self::ROUTE_SPEED, $speedTmp, __('Speed'), @$this->uplinkData['speed'], false) . ' ';
            $inputs .= wf_TextInput(self::ROUTE_PORT, __('Port'), @$this->uplinkData['port'], false, 2, 'digits') . ' ';
            $result .= $inputs; //we need it for main edit form integration
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Switch') . ' ID ' . __('is empty'), 'error');
        }
        return($result);
    }

    /**
     * Saves switch uplink data into database
     * 
     * @return void
     */
    public function save() {
        if (ubRouting::checkPost(array(self::ROUTE_SWID))) {
            $switchId = ubRouting::post(self::ROUTE_SWID, 'int');
            $newMedia = ubRouting::post(self::ROUTE_MEDIA, 'mres');
            $newSpeed = ubRouting::post(self::ROUTE_SPEED, 'mres');
            $newPort = ubRouting::post(self::ROUTE_PORT, 'int');

            $this->switchUplinks->data('media', $newMedia);
            $this->switchUplinks->data('speed', $newSpeed);
            $this->switchUplinks->data('port', $newPort);

            //updating existing record
            if (!empty($this->uplinkData)) {
                $this->switchUplinks->where('switchid', '=', $switchId);
                $this->switchUplinks->save();
            } else {
                //creating new record
                $this->switchUplinks->data('switchid', $switchId);
                $this->switchUplinks->create();
            }
            log_register('SWITCHUPLINK CHANGE [' . $switchId . '] MEDIA `' . $newMedia . '` SPEED `' . $newSpeed . '` PORT `' . $newPort . '`');
        }
    }

    /**
     * Renders current instance uplink data in compact format
     * 
     * @return string
     */
    public function renderSwitchUplinkData() {
        $result = '';
        if (!empty($this->uplinkData)) {
            if (!empty($this->uplinkData['media'])) {
                if (isset($this->mediaIcons[$this->uplinkData['media']])) {
                    $mediaIcon = wf_img_sized(self::PATH_ICONS . $this->mediaIcons[$this->uplinkData['media']], '', '10') . ' ';
                } else {
                    $mediaIcon = '';
                }
                $result .= $mediaIcon . $this->mediaTypes[$this->uplinkData['media']] . ' ';
            }

            if (!empty($this->uplinkData['speed'])) {
                $result .= $this->speedRates[$this->uplinkData['speed']] . ' ';
            }

            if (!empty($this->uplinkData['port'])) {
                $result .= $this->uplinkData['port'] . ' ' . __('Port');
            }

            //empty existing record
            if (!$this->uplinkData['media'] AND ! $this->uplinkData['speed'] AND ! $this->uplinkData['port']) {
                $result .= __('Uplink parameters is not set');
            }
        } else {
            $result .= __('Uplink parameters is not set');
        }
        return($result);
    }

    /**
     * Loads all switches uplinks data
     * 
     * @return void
     */
    public function loadAllUplinksData() {
        $this->allUplinksData = $this->switchUplinks->getAll('switchid');
    }

    /**
     * Returns count of available uplinks data records
     * 
     * @return int
     */
    public function getAllUplinksCount() {
        return(sizeof($this->allUplinksData));
    }

    /**
     * Returns short uplink parameters text description
     * 
     * @param int $swithchId
     * 
     * @return string
     */
    public function getUplinkTinyDesc($swithchId) {
        $result = '';
        if (isset($this->allUplinksData[$swithchId])) {
            $media = $this->allUplinksData[$swithchId]['media'];
            $speed = $this->allUplinksData[$swithchId]['speed'];
            $icon = (isset($this->mediaIcons[$media])) ? wf_img(self::PATH_ICONS . $this->mediaIcons[$media], $this->mediaTypes[$media]) : '';
            $result .= $icon . $media . $speed;
        }
        return($result);
    }

}
