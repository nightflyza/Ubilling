<?php

/**
 * Electrical generators management and accounting
 */
class Generators {
    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains devices database abstraction layer
     *
     * @var object
     */
    protected $devicesDb = '';

    /**
     * Contains services database abstraction layer
     *
     * @var object
     */
    protected $servicesDb = '';

    /**
     * Contains events database abstraction layer
     *
     * @var object
     */
    protected $eventsDb = '';

    /**
     * Contains refuels database abstraction layer
     *
     * @var object
     */
    protected $refuelsDb = '';

    /**
     * OnePunch object placeholder
     *
     * @var object
     */
    protected $onePunch = '';

    /**
     * Contains available fuel types
     *
     * @var array
     */
    protected $fuelTypes = array();

    /**
     * Contains all generator devices
     *
     * @var array
     */
    protected $allDevices = array();


    /**
     * Contains all generator services
     *
     * @var array
     */
    protected $allServices = array();

    /**
     * Contains all generator events
     *
     * @var array
     */
    protected $allEvents = array();

    /**
     * Contains all generator refuels
     *
     * @var array
     */
    protected $allRefuels = array();

    /**
     * Contains filtered OnePunch punch scripts for generators state monitoring as alias=>name
     *
     * @var array
     */
    protected $availScripts = array();
    

    // some predefined stuff here
    const TABLE_DEVICES = 'gen_devices';
    const TABLE_SERVICE_TYPES = 'gen_service_types';
    const TABLE_SERVICES = 'gen_services';
    const TABLE_EVENTS = 'gen_events';
    const TABLE_REFUELS = 'gen_refuels';

    //get routes here
    const URL_ME = '?module=generators';
    const ROUTE_DEVICES = 'devices';
    const ROUTE_DELETE_DEVICE = 'deletedeviceid';
    const ROUTE_START_DEVICE = 'startdeviceid';
    const ROUTE_STOP_DEVICE = 'stopdeviceid';
    const ROUTE_VIEW_EVENTS = 'viewevents';
    const ROUTE_VIEW_SERVICES_ALL='servicesall';
    const ROUTE_EDIT_SERVICE = 'editserviceid';
    const ROUTE_VIEW_REFUELS_ALL='refuelsall';
    const ROUTE_EDIT_REFUEL = 'editrefuelid';
    const ROUTE_VIEW_MAP='rendermap';

    //some form routes here
    const PROUTE_NEW_DEVICE = 'createdevice';
    const PROUTE_EDIT_DEVICE = 'editdeviceid';
    const PROUTE_DEV_MODEL = 'devmodel';
    const PROUTE_DEV_FUEL_TYPE = 'devfueltype';
    const PROUTE_DEV_TANK_VOLUME = 'devtankvolume';
    const PROUTE_DEV_FUEL_CONSUMPTION = 'devfuelconsumption';
    const PROUTE_DEV_ADDRESS = 'devaddress';
    const PROUTE_DEV_GEO = 'devgeo';
    const PROUTE_DEV_MOTO_HOURS = 'devmotohours';
    const PROUTE_DEV_SERVICE_INTERVAL = 'devserviceinterval';
    const PROUTE_DEV_OP_ALIAS = 'devopalias';
    const PROUTE_REFUEL_DEVICE = 'refueldevice';
    const PROUTE_REFUEL_LITERS = 'refuelliters';
    const PROUTE_REFUEL_PRICE = 'refuelprice';
    const PROUTE_EDIT_REFUEL = 'editrefuel';
    const PROUTE_SERVICE_DEVICE = 'servicedevice';
    const PROUTE_SERVICE_MOTO_HOURS = 'servicemotohours';
    const PROUTE_SERVICE_NOTES = 'servicenotes';
    const PROUTE_SERVICE_DATE = 'servicedate';
    const PROUTE_SERVICE_TIME = 'servicetime';
    const PROUTE_EDIT_SERVICE = 'editservice';

    //other stuff
    const WATCHER_PID='GENERATORS';
    const OP_MON_MARK='$generatorState';


//
//        .----------.
//        |   ~ON~   |
//        |   ____   |
//        |  |.--.|  |
//        |  ||  ||  |
//        |  ||__||  |
//        |  ||\ \|  |
//        |  |\ \_\  |
//        |  |_\[_]  |
//        |          |
//        |  ~OFF~   |
//        '----------'
//

    /**
     * Creates new Generators instance
     *
     * @return void
     */
    public function __construct() {
        $this->loadConfig();
        $this->initMessages();
        $this->setFuelTypes();
        $this->initDb();
        $this->initOnePunch();
        $this->loadOnePunchScripts();
        $this->loadDevices();
        $this->loadServices();
        $this->loadEvents();
        $this->loadRefuels();
    }

    /**
     * Loads system alter.ini config
     *
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Initializes message helper instance
     *
     * @return void
     */
    protected function initMessages() { 
        $this->messages = new UbillingMessageHelper();
    }


    /**
     * Initializes onepunch object
     *
     * @return void
     */
    protected function initOnePunch() {
        $this->onePunch = new OnePunch();
    }


    /**
     * Preloads all one punch scripts and filters them for generators state monitoring
     *
     * @return void
     */
    protected function loadOnePunchScripts() {
        $allScripts= $this->onePunch->getAllScripts();
      
        if (!empty($allScripts)) {
            foreach ($allScripts as $io => $each) {
                if (ispos($each['content'], self::OP_MON_MARK)) {
                    $this->availScripts[$each['alias']] = $each['name'];
                }
                
            }
        }
    }

    /**
     * Initializes database abstraction layers
     *
     * @return void
     */
    protected function initDb() {
        $this->devicesDb = new NyanORM(self::TABLE_DEVICES);
        $this->servicesDb = new NyanORM(self::TABLE_SERVICES);
        $this->eventsDb = new NyanORM(self::TABLE_EVENTS);
        $this->refuelsDb = new NyanORM(self::TABLE_REFUELS);
    }

    /**
     * Sets available fuel types
     *
     * @return void
     */
    protected function setFuelTypes() {
        $this->fuelTypes = array(
            'diesel' => __('Diesel'),
            'petrol' => __('Petrol'),
            'lpg' => __('Liquefied petroleum gas'),
            'wood' => __('On the wood'),
            'other' => __('Other')
        );
    }

    /**
     * Loads all generator devices from database
     *
     * @return void
     */
    protected function loadDevices() {
        $this->allDevices = $this->devicesDb->getAll('id');
    }

    
    /**
     * Loads all services from database
     *
     * @return void
     */
    protected function loadServices() {
        $this->allServices = $this->servicesDb->getAll('id');
    }

    /**
     * Loads all events from database
     *
     * @return void
     */
    protected function loadEvents() {
        $this->allEvents = $this->eventsDb->getAll('id');
    }

    /**
     * Loads all refuels from database
     *
     * @return void
     */
    protected function loadRefuels() {
        $this->allRefuels = $this->refuelsDb->getAll('id');
    }

    /**
     * Gets events count for device
     *
     * @param int $deviceId
     *
     * @return int
     */
    protected function getDeviceEventsCount($deviceId) {
        $result = 0;
        if (!empty($this->allEvents)) {
            foreach ($this->allEvents as $io => $event) {
                if ($event['genid'] == $deviceId) {
                    $result++;
                }
            }
        }
        return ($result);
    }

    /**
     * Gets next maintenance date in motohours remaining
     *
     * @param int $deviceId
     * @param int $runningSeconds
     *
     * @return string
     */
    protected function getNextMaintenanceDate($deviceId, $runningSeconds = 0) {
        $result = '-';
        $deviceId = ubRouting::filters($deviceId, 'int');
        $runningSeconds = ubRouting::filters($runningSeconds, 'int');
        
        if (isset($this->allDevices[$deviceId])) {
            $device = $this->allDevices[$deviceId];
            $serviceInterval = $device['serviceinterval'];
            $currentMotohours = $device['motohours'];
            
            if ($runningSeconds > 0) {
                $currentMotohours += $runningSeconds / 3600;
            }
            
            if ($serviceInterval > 0) {
                $lastServiceMotohours = 0;
                
                if (!empty($this->allServices)) {
                    foreach ($this->allServices as $io => $service) {
                        if ($service['genid'] == $deviceId) {
                            if ($service['motohours'] > $lastServiceMotohours) {
                                $lastServiceMotohours = $service['motohours'];
                            }
                        }
                    }
                }
                
                $nextMaintenanceMotohours = $lastServiceMotohours + $serviceInterval;
                $remainingMotohours = $nextMaintenanceMotohours - $currentMotohours;
                
                if ($remainingMotohours > 0) {
                    $result = round($remainingMotohours, 2) . ' ' . __('hours');
                } else {
                    $result = __('Overdue') . '! (' . round(abs($remainingMotohours), 2) . ' ' . __('hours') . ')';
                }
            }
        }
        
        return ($result);
    }

    /**
     * Creates service record for device
     *
     * @param int $deviceId
     *
     * @return string
     */
    public function createService($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        
        if (isset($this->allDevices[$deviceId])) {
            $requiredFields = array(self::PROUTE_SERVICE_DEVICE, self::PROUTE_SERVICE_MOTO_HOURS);
            if (ubRouting::checkPost($requiredFields)) {
                $motohours = ubRouting::post(self::PROUTE_SERVICE_MOTO_HOURS, 'float');
                $notes = ubRouting::post(self::PROUTE_SERVICE_NOTES, 'safe');
                $notes= ubRouting::filters($notes, 'mres');
                $serviceDate = ubRouting::post(self::PROUTE_SERVICE_DATE, 'mres');
                $serviceTime = ubRouting::post(self::PROUTE_SERVICE_TIME, 'mres');
                
                if ($motohours >= 0) {
                    $serviceDateTime = curdatetime();
                    if (!empty($serviceDate) AND !empty($serviceTime)) {
                        $serviceDateTime = $serviceDate . ' ' . $serviceTime . ':00';
                    } elseif (!empty($serviceDate)) {
                        $serviceDateTime = $serviceDate . ' ' . date('H:i:s');
                    }
                    
                    $this->servicesDb->data('genid', $deviceId);
                    $this->servicesDb->data('date', $serviceDateTime);
                    $this->servicesDb->data('motohours', $motohours);
                    $this->servicesDb->data('notes', $notes);
                    $this->servicesDb->create();
                    $this->loadServices();
                    
                    log_register('GENERATORS DEVICE MAINTENANCE [' . $deviceId . '] MOTOHOURS `' . $motohours . '`');
                } else {
                    $result = __('Invalid motohours value');
                }
            }
        } else {
            $result = __('Something went wrong') . ': [' . $deviceId . '] ' . __('Not exists');
        }
        
        return ($result);
    }

    /**
     * Calculates fuel consumption for device during working time
     *
     * @param int $deviceId
     * @param int $seconds
     *
     * @return float
     */
    public function calculateFuelConsumption($deviceId, $seconds) {
        $result = 0;
        $deviceId = ubRouting::filters($deviceId, 'int');
        $seconds = ubRouting::filters($seconds, 'int');
        
        if (isset($this->allDevices[$deviceId]) AND $seconds > 0) {
            $consumption = $this->allDevices[$deviceId]['consumption'];
            $hours = $seconds / 3600;
            $result = $consumption * $hours;
        }
        
        return ($result);
    }

    /**
     * Gets running time for device since last start event in seconds
     *
     * @param int $deviceId
     *
     * @return int
     */
    protected function getDeviceRunningTime($deviceId) {
        $result = 0;
        $lastStartTime = 0;
        
        if (!empty($this->allEvents)) {
            foreach ($this->allEvents as $io => $event) {
                if ($event['genid'] == $deviceId AND $event['event'] == 'start') {
                    $eventTime = strtotime($event['date']);
                    if ($eventTime > $lastStartTime) {
                        $lastStartTime = $eventTime;
                    }
                }
            }
        }
        
        if ($lastStartTime > 0) {
            $currentTime = strtotime(curdatetime());
            $result = $currentTime - $lastStartTime;
            if ($result < 0) {
                $result = 0;
            }
        }
        
        return ($result);
    }

    /**
     * Renders navigation controls
     *
     * @return string
     */
    public function renderControls() {
        $result = '';
        
        $devicesUrl = self::URL_ME . '&' . self::ROUTE_DEVICES . '=true';
        $result .= wf_Link($devicesUrl, wf_img('skins/icon_generators.png') . ' ' . __('Devices'), false, 'ubButton') . ' ';
        
        $servicesUrl = self::URL_ME . '&' . self::ROUTE_VIEW_SERVICES_ALL . '=true';
        $result .= wf_Link($servicesUrl, wf_img('skins/icon_repair.gif') . ' ' . __('Maintenance'), false, 'ubButton') . ' ';

        $refuelsUrl = self::URL_ME . '&' . self::ROUTE_VIEW_REFUELS_ALL . '=true';
        $result .= wf_Link($refuelsUrl, wf_img('skins/icon_fuel.png') . ' ' . __('Refuels'), false, 'ubButton') . ' ';

        $mapUrl = self::URL_ME . '&' . self::ROUTE_VIEW_MAP . '=true';
        $result .= wf_Link($mapUrl, wf_img('skins/icon_map_small.png') . ' ' . __('Map'), false, 'ubButton') . ' ';
        
        return ($result);
    }

    /**
     * Renders device start stop dialog
     *
     * @param int $deviceId
     *
     * @return string
     */
    protected function renderDeviceStartStopDialog($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
            if (isset($this->allDevices[$deviceId])) {
                $device = $this->allDevices[$deviceId];
                $cancelUrl=self::URL_ME.'&'.self::ROUTE_DEVICES.'=true';

            if ($device['running']) {
                $stopUrl = self::URL_ME . '&' . self::ROUTE_STOP_DEVICE . '=' . $device['id'];
                $shutdownText=__('Stop').' '.__('generator').': '.$device['address'].'?';
                $result.=wf_ConfirmDialog($stopUrl, wf_img('skins/pause.png',__('Stop')), $shutdownText, '', $cancelUrl, __('Stop').'?');
            } else {
                $startUrl = self::URL_ME . '&' . self::ROUTE_START_DEVICE . '=' . $device['id'];
                $startText=__('Start').' '.__('generator').': '.$device['address'].'?';
                $result.=wf_ConfirmDialog($startUrl, wf_img('skins/play.png',__('Start')), $startText, '', $cancelUrl, __('Start').'?');
            }
        }
        return ($result);
    }


    /**
     * Renders generator devices list with actions
     *
     * @return string
     */
    public function renderDevicesList() {
        $result = '';
        
        if (!empty($this->allDevices)) {
            $cells = wf_TableCell(__('Model'));
            $cells .= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('Running'));
            $cells .= wf_TableCell(__('Motohours'));
            $cells .= wf_TableCell(__('In tank'));
            $cells .= wf_TableCell(__('Next maintenance'));
            $cells.= wf_TableCell(__('Events'));

            if (cfr('GENERATORSMGMT')) {
                $cells .= wf_TableCell(__('Actions'));
            }
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allDevices as $io=>$device) {
                $cells = wf_TableCell($device['model']);
                $cells .= wf_TableCell($device['address']);
                $runningDisplay = web_bool_led($device['running']);
                $fuelConsumed=0;
                $deviceMotohours=$device['motohours'];
                $runningSeconds=0;
                if ($device['running']) {
                    $runningSeconds = $this->getDeviceRunningTime($device['id']);
                    if ($runningSeconds > 0) {
                        $fuelConsumption = $this->calculateFuelConsumption($device['id'], $runningSeconds);
                        $fuelConsumed += $fuelConsumption;
                        $runningDisplay .= ' (';
                        $runningDisplay .= zb_formatTime($runningSeconds);
                        $runningDisplay .= ', ' . round($fuelConsumption, 2) . ' ' . __('litre');
                        $runningDisplay .= ')';
                        $deviceMotohours += $runningSeconds / 3600;
                    }
                }
                $cells .= wf_TableCell($runningDisplay);
                $cells .= wf_TableCell(round($deviceMotohours, 2));
                $cells .= wf_TableCell(round($device['intank']-$fuelConsumed, 2) . ' ' . __('litre'));
                $nextMaintenanceDate=$this->getNextMaintenanceDate($device['id'], $runningSeconds);
                $cells .= wf_TableCell($nextMaintenanceDate);
                $eventsCount = $this->getDeviceEventsCount($device['id']);
                $eventsUrl = self::URL_ME . '&' . self::ROUTE_VIEW_EVENTS . '=' . $device['id'];
                $eventsLink = wf_Link($eventsUrl, $eventsCount, false, '');
                $cells .= wf_TableCell($eventsLink);

                $deviceControls='';
         
                if (cfr('GENERATORSMGMT')) {
              
                    $deletionUrl = self::URL_ME . '&' . self::ROUTE_DELETE_DEVICE . '=' . $device['id'];
                    $cancelUrl=self::URL_ME . '&' . self::ROUTE_DEVICES . '=true';
                    $deletionDialog=wf_ConfirmDialog($deletionUrl, web_delete_icon(), $this->messages->getDeleteAlert(), '', $cancelUrl, __('Delete').'?');
                    $deviceControls .= $deletionDialog;
                    $editTitle = __('Edit').' ID:' . $device['id'] . ', '.$device['address'];
                    $editDialog = wf_modalAuto(web_edit_icon(), $editTitle, $this->renderDeviceEditForm($device['id']));
                    $deviceControls .= $editDialog;
                    $deviceControls .= $this->renderDeviceStartStopDialog($device['id']);

                $serviceForm = $this->renderServiceForm($device['id']);
                $deviceControls .= $serviceForm;

                $refuelForm=$this->renderRefuelForm($device['id']);
                $deviceControls .= $refuelForm;
                
                $cells .= wf_TableCell($deviceControls);
            }

                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders service form for device
     *
     * @param int $deviceId
     *
     * @return string
     */
    protected function renderServiceForm($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        if (isset($this->allDevices[$deviceId])) {
            $device = $this->allDevices[$deviceId];
            $deviceMotohours = $device['motohours'];
            if ($device['running']) {
                $runningSeconds = $this->getDeviceRunningTime($deviceId);
                if ($runningSeconds > 0) {
                    $deviceMotohours += $runningSeconds / 3600;
                }
            }
            $currentDate = date('Y-m-d');
            $currentTime = date('H:i');

            $inputs = wf_HiddenInput(self::PROUTE_SERVICE_DEVICE, $deviceId);
            $inputs .= wf_TextInput(self::PROUTE_SERVICE_MOTO_HOURS, __('Motohours'), round($deviceMotohours, 2), false, 8, 'float').' ';
            $inputs .= wf_DatePickerPreset(self::PROUTE_SERVICE_DATE, $currentDate, true);
            $inputs .= wf_tag('label') . __('Date') . wf_tag('label', true).' ';
            $inputs .= wf_TimePickerPreset(self::PROUTE_SERVICE_TIME, $currentTime, __('Time'), true).' ';
            
            $inputs .= wf_TextArea(self::PROUTE_SERVICE_NOTES, '', '', true, '50x5');
            $inputs .= wf_Submit(__('Create'). ' '.__('maintenance'));
            $form = wf_Form('', 'POST', $inputs, 'glamour');
            $result = wf_modalAuto(wf_img('skins/icon_repair.gif',__('Maintenance')), __('Maintenance'), $form, '');
        }
        return ($result);
    }

    /**
     * Renders refuel form for device
     *
     * @param int $deviceId
     *
     * @return string
     */
    protected function renderRefuelForm($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        if (isset($this->allDevices[$deviceId])) {
            $device = $this->allDevices[$deviceId];
            $latestResuelPrice=$this->getLatestRefuelPrice($deviceId);
            $inputs = wf_HiddenInput(self::PROUTE_REFUEL_DEVICE, $deviceId);
            $inputs .= wf_TextInput(self::PROUTE_REFUEL_LITERS, __('Litre'), '', true, 6, 'float');
            $inputs .= wf_TextInput(self::PROUTE_REFUEL_PRICE, __('Price'), $latestResuelPrice, true, 4, 'finance');
            $inputs .= wf_Submit(__('Refuel'));
            $form = wf_Form('', 'POST', $inputs, 'glamour');
            $result = wf_modalAuto(wf_img('skins/icon_fuel.png',__('Refuel')), __('Refuel'), $form, '');
        }
        return ($result);
    }

    /**
     * Gets latest refuel price for device
     *
     * @param int $deviceId
     *
     * @return float
     */
    protected function getLatestRefuelPrice($deviceId) {
        $result = 0;
        $deviceId = ubRouting::filters($deviceId, 'int');
        if (isset($this->allRefuels[$deviceId])) {
            foreach ($this->allRefuels as $io => $refuel) {
                if ($refuel['genid'] == $deviceId) {
                    $result = $refuel['price'];
                }
            }
        }
        return ($result);
    }

    /**
     * Creates refuel record and updates device intank
     *
     * @param int $deviceId
     *
     * @return string
     */
    public function createRefuel($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        
        if (isset($this->allDevices[$deviceId])) {
            $requiredFields = array(self::PROUTE_REFUEL_DEVICE, self::PROUTE_REFUEL_LITERS);
            if (ubRouting::checkPost($requiredFields)) {
                $liters = ubRouting::post(self::PROUTE_REFUEL_LITERS, 'float');
                $price = ubRouting::post(self::PROUTE_REFUEL_PRICE, 'float');
                
                if ($liters > 0) {
                    $currentIntank = $this->allDevices[$deviceId]['intank'];
                    $tankVolume = $this->allDevices[$deviceId]['tankvolume'];
                    $newIntank = $currentIntank + $liters;
                    
                    if ($newIntank <= $tankVolume) {
                        $this->refuelsDb->data('genid', $deviceId);
                        $this->refuelsDb->data('date', curdatetime());
                        $this->refuelsDb->data('liters', $liters);
                        $this->refuelsDb->data('price', $price);
                        $this->refuelsDb->create();
                        
                        $this->setDeviceIntank($deviceId, $newIntank);
                        $this->loadDevices();
                        
                        log_register('GENERATORS DEVICE REFUEL [' . $deviceId . '] LITERS `' . $liters . '` PRICE `' . $price . '`');
                    } else {
                        $result = __('Tank overflow') . ': ' . __('Maximum') . ' ' . $tankVolume . ' ' . __('litre');
                    }
                } else {
                    $result = __('Invalid values');
                }
            }
        } else {
            $result = __('Something went wrong') . ': [' . $deviceId . '] ' . __('Not exists');
        }
        
        return ($result);
    }

    /**
     * Sets device intank value
     *
     * @param int $deviceId
     * @param float $liters
     *
     * @return void
     */
    protected function setDeviceIntank($deviceId, $liters) {
        $deviceId = ubRouting::filters($deviceId, 'int');
        $liters = ubRouting::filters($liters, 'float');
        if (isset($this->allDevices[$deviceId])) {
            $this->devicesDb->where('id', '=', $deviceId);
            $this->devicesDb->data('intank', $liters);
            $this->devicesDb->save();
        }
    }

    /**
     * Renders device creation form
     *
     * @return string
     */
    public function renderDeviceCreateForm() {
        $result = '';
        $sup=wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs = wf_HiddenInput(self::PROUTE_NEW_DEVICE, 'true');
        $inputs .= wf_TextInput(self::PROUTE_DEV_MODEL, __('Model') . $sup, '', true, 20);
        $inputs .= wf_TextInput(self::PROUTE_DEV_ADDRESS, __('Address') . $sup, '', true, 30);
        $inputs .= wf_TextInput(self::PROUTE_DEV_GEO, __('Location'), '', true, 20, 'geo');
        
        $inputs .= wf_Selector(self::PROUTE_DEV_FUEL_TYPE, $this->fuelTypes, __('Fuel type') . $sup, '', true);
        $inputs .= wf_TextInput(self::PROUTE_DEV_TANK_VOLUME, __('Tank volume'), '', true, 4, 'digits');
        $inputs .= wf_TextInput(self::PROUTE_DEV_FUEL_CONSUMPTION, __('Fuel consumption'), '', true, 4, 'float');
        $inputs .= wf_TextInput(self::PROUTE_DEV_MOTO_HOURS, __('Motohours'), '', true, 8, 'float');
        $inputs .= wf_TextInput(self::PROUTE_DEV_SERVICE_INTERVAL, __('Service interval'), '', true, 4, 'digits');
        $availScripts=array(''=>'-');
        $availScripts=array_merge($availScripts, $this->availScripts);
        $inputs .= wf_Selector(self::PROUTE_DEV_OP_ALIAS, $availScripts, __('One-punch').' '.__('script'), '', true);
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new generator device
     *
     * @return void
     */
    public function createDevice() {
        $requiredFields = array(self::PROUTE_NEW_DEVICE, self::PROUTE_DEV_MODEL, self::PROUTE_DEV_ADDRESS, self::PROUTE_DEV_FUEL_TYPE);
        if (ubRouting::checkPost($requiredFields)) {
            $newModel = ubRouting::post(self::PROUTE_DEV_MODEL, 'safe');
            $newAddress = ubRouting::post(self::PROUTE_DEV_ADDRESS, 'safe');
            $newGeo = ubRouting::post(self::PROUTE_DEV_GEO, 'mres');
            $newFuelType = ubRouting::post(self::PROUTE_DEV_FUEL_TYPE, 'gigasafe');
            $newTankVolume = ubRouting::post(self::PROUTE_DEV_TANK_VOLUME, 'int');
            $newFuelConsumption = ubRouting::post(self::PROUTE_DEV_FUEL_CONSUMPTION, 'float');
            $newMotohours = ubRouting::post(self::PROUTE_DEV_MOTO_HOURS, 'float');
            $newServiceInterval = ubRouting::post(self::PROUTE_DEV_SERVICE_INTERVAL, 'int');
            $newOpAlias = ubRouting::post(self::PROUTE_DEV_OP_ALIAS, 'mres');

            if (!empty($newModel) AND !empty($newAddress) AND !empty($newFuelType)) {
                $this->devicesDb->data('model', $newModel);
                $this->devicesDb->data('address', $newAddress);
                $this->devicesDb->data('geo', $newGeo);
                $this->devicesDb->data('fuel', $newFuelType);
                $this->devicesDb->data('tankvolume', $newTankVolume);
                $this->devicesDb->data('consumption', $newFuelConsumption);
                $this->devicesDb->data('motohours', $newMotohours);
                $this->devicesDb->data('serviceinterval', $newServiceInterval);
                $this->devicesDb->data('opalias', $newOpAlias);
                $this->devicesDb->data('running', 0); //default running state is off
                $this->devicesDb->create();
                $newDeviceId = $this->devicesDb->getLastId();
                log_register('GENERATORS DEVICE CREATE [' . $newDeviceId . ']');
            }
        }
    }


    /**
     * Deletes generator device
     *
     * @param int $deviceId
     *
     * @return string
     */
    public function deleteDevice($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        if (isset($this->allDevices[$deviceId])) {
            $this->eventsDb->where('genid', '=', $deviceId);
            $this->eventsDb->delete();
            
            $this->servicesDb->where('genid', '=', $deviceId);
            $this->servicesDb->delete();
            
            $this->refuelsDb->where('genid', '=', $deviceId);
            $this->refuelsDb->delete();
            
            $this->devicesDb->where('id','=',$deviceId);
            $this->devicesDb->delete();
            log_register('GENERATORS DEVICE DELETE [' . $deviceId . ']');
        } else {
            $result = __('Something went wrong') . ': [' . $deviceId . '] ' . __('Not exists');
        }
        return ($result);
    }

    /**
     * Renders device edit form
     *
     * @param int $deviceId
     *
     * @return string
     */
    public function renderDeviceEditForm($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        if (isset($this->allDevices[$deviceId])) {
            $device = $this->allDevices[$deviceId];
            $sup=wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs = wf_HiddenInput(self::PROUTE_EDIT_DEVICE, $deviceId);
            $inputs .= wf_TextInput(self::PROUTE_DEV_MODEL, __('Model') . $sup, $device['model'], true, 20);
            $inputs .= wf_TextInput(self::PROUTE_DEV_ADDRESS, __('Address') . $sup, $device['address'], true, 30);
            $inputs .= wf_TextInput(self::PROUTE_DEV_GEO, __('Location'), $device['geo'], true, 20, 'geo');
            
            $inputs .= wf_Selector(self::PROUTE_DEV_FUEL_TYPE, $this->fuelTypes, __('Fuel type') . $sup, $device['fuel'], true);
            $inputs .= wf_TextInput(self::PROUTE_DEV_TANK_VOLUME, __('Tank volume'), $device['tankvolume'], true, 4, 'digits');
            $inputs .= wf_TextInput(self::PROUTE_DEV_FUEL_CONSUMPTION, __('Fuel consumption'), $device['consumption'], true, 4, 'float');
            $inputs .= wf_TextInput(self::PROUTE_DEV_MOTO_HOURS, __('Motohours'), $device['motohours'], true, 8, 'float');
            $inputs .= wf_TextInput(self::PROUTE_DEV_SERVICE_INTERVAL, __('Service interval'), $device['serviceinterval'], true, 4, 'digits');
            $availScripts=array(''=>'-');
            $availScripts=array_merge($availScripts, $this->availScripts);
            $inputs .= wf_Selector(self::PROUTE_DEV_OP_ALIAS, $availScripts, __('One-punch').' '.__('script'), $device['opalias'], true);
            
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Updates generator device data
     *
     * @param int $deviceId
     *
     * @return string
     */
    public function updateDevice($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        if (isset($this->allDevices[$deviceId])) {
            $requiredFields = array(self::PROUTE_EDIT_DEVICE, self::PROUTE_DEV_MODEL, self::PROUTE_DEV_ADDRESS, self::PROUTE_DEV_FUEL_TYPE);
            if (ubRouting::checkPost($requiredFields)) {
                $newModel = ubRouting::post(self::PROUTE_DEV_MODEL, 'safe');
                $newAddress = ubRouting::post(self::PROUTE_DEV_ADDRESS, 'safe');
                $newGeo = ubRouting::post(self::PROUTE_DEV_GEO, 'mres');
                $newFuelType = ubRouting::post(self::PROUTE_DEV_FUEL_TYPE, 'gigasafe');
                $newTankVolume = ubRouting::post(self::PROUTE_DEV_TANK_VOLUME, 'int');
                $newFuelConsumption = ubRouting::post(self::PROUTE_DEV_FUEL_CONSUMPTION, 'float');
                $newMotohours = ubRouting::post(self::PROUTE_DEV_MOTO_HOURS, 'float');
                $newServiceInterval = ubRouting::post(self::PROUTE_DEV_SERVICE_INTERVAL, 'int');
                $newOpAlias = ubRouting::post(self::PROUTE_DEV_OP_ALIAS, 'mres');
                

                if (!empty($newModel) AND !empty($newAddress) AND !empty($newFuelType)) {
                    $this->devicesDb->where('id', '=', $deviceId);
                    $this->devicesDb->data('model', $newModel);
                    $this->devicesDb->data('address', $newAddress);
                    $this->devicesDb->data('geo', $newGeo);
                    $this->devicesDb->data('fuel', $newFuelType);
                    $this->devicesDb->data('tankvolume', $newTankVolume);
                    $this->devicesDb->data('consumption', $newFuelConsumption);
                    $this->devicesDb->data('motohours', $newMotohours);
                    $this->devicesDb->data('serviceinterval', $newServiceInterval);
                    $this->devicesDb->data('opalias', $newOpAlias);
                    $this->devicesDb->save();
                    $this->loadDevices();
                    log_register('GENERATORS DEVICE EDIT [' . $deviceId . ']');
                }
            }
        } else {
            $result = __('Something went wrong') . ': [' . $deviceId . '] ' . __('Not exists');
        }
        return ($result);
    }

    /**
     * Starts generator device
     *
     * @param int $deviceId
     *
     * @return string
     */
    public function startDevice($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        if (isset($this->allDevices[$deviceId])) {
            if (!$this->allDevices[$deviceId]['running']) {
                $this->devicesDb->where('id', '=', $deviceId);
                $this->devicesDb->data('running', 1);
                $this->devicesDb->save();
                $this->eventsDb->data('genid', $deviceId);
                $this->eventsDb->data('event', 'start');
                $this->eventsDb->data('date', curdatetime());
                $this->eventsDb->create();
                $this->loadDevices();
                log_register('GENERATORS DEVICE START [' . $deviceId . ']');
            } else {
                $result = __('Generator is already running');
            }
        } else {
            $result = __('Something went wrong') . ': [' . $deviceId . '] ' . __('Not exists');
        }
        return ($result);
    }

    /**
     * Updates device motohours based on last start event
     *
     * @param int $deviceId
     *
     * @return int
     */
    protected function updateDeviceMotohours($deviceId) {
        $result = 0;
        $lastStartTime = 0;
        
        if (!empty($this->allEvents)) {
            foreach ($this->allEvents as $io => $event) {
                if ($event['genid'] == $deviceId AND $event['event'] == 'start') {
                    $eventTime = strtotime($event['date']);
                    if ($eventTime > $lastStartTime) {
                        $lastStartTime = $eventTime;
                    }
                }
            }
        }
        
        if ($lastStartTime > 0) {
            $stopTime = strtotime(curdatetime());
            $secondsDiff = $stopTime - $lastStartTime;
            
            if ($secondsDiff > 0) {
                $result = $secondsDiff;
                $hoursDiff = $secondsDiff / 3600;
                $currentMotohours = $this->allDevices[$deviceId]['motohours'];
                $newMotohours = $currentMotohours + $hoursDiff;
                
                $this->devicesDb->where('id', '=', $deviceId);
                $this->devicesDb->data('motohours', $newMotohours);
                $this->devicesDb->save();
            }
        }
        
        return ($result);
    }

    /**
     * Updates device fuel lefts counter
     *
     * @param int $deviceId
     * @param int $timePassed
     *
     * @return int
     */
    protected function updateDeviceIntank($deviceId, $timePassed) {
        $result = 0;
        $deviceId = ubRouting::filters($deviceId, 'int');
        $timePassed = ubRouting::filters($timePassed, 'int');
        if (isset($this->allDevices[$deviceId])) {
            $fuelConsumption = $this->calculateFuelConsumption($deviceId, $timePassed);
            $newIntank = $this->allDevices[$deviceId]['intank'] - $fuelConsumption;
            $this->devicesDb->where('id', '=', $deviceId);
            $this->devicesDb->data('intank', $newIntank);
            $this->devicesDb->save();
            $result = $newIntank;
        }
        return ($result);
    }

    /**
     * Stops generator device
     *
     * @param int $deviceId
     *
     * @return string
     */
    public function stopDevice($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        if (isset($this->allDevices[$deviceId])) {
            if ($this->allDevices[$deviceId]['running']) {
                $this->devicesDb->where('id', '=', $deviceId);
                $this->devicesDb->data('running', 0);
                $this->devicesDb->save();
                $this->eventsDb->data('genid', $deviceId);
                $this->eventsDb->data('event', 'stop');
                $this->eventsDb->data('date', curdatetime());
                $this->eventsDb->create();
                $this->loadDevices();
                //updating device motorhours counter
                $timePassed=$this->updateDeviceMotohours($deviceId);
                //updating device fuel lefts counter
                $this->updateDeviceIntank($deviceId, $timePassed);

                log_register('GENERATORS DEVICE STOP [' . $deviceId . '] AFTER `' . $timePassed . '` SEC');
            } else {
                $result = __('Generator is already stopped');
            }
        } else {
            $result = __('Something went wrong') . ': [' . $deviceId . '] ' . __('Not exists');
        }
        return ($result);
    }

    /**
     * Gets device info by ID
     *
     * @param int $deviceId
     *
     * @return array
     */
    public function getDeviceInfo($deviceId) {
        $result = array();
        $deviceId = ubRouting::filters($deviceId, 'int');
        if (isset($this->allDevices[$deviceId])) {
            $result = $this->allDevices[$deviceId];
        }
        return ($result);
    }

    /**
     * Renders device events table
     *
     * @param int $deviceId
     *
     * @return string
     */
    public function renderDeviceEvents($deviceId) {
        $result = '';
        $deviceId = ubRouting::filters($deviceId, 'int');
        
        if (isset($this->allDevices[$deviceId])) {
            $deviceEvents = array();
            
            if (!empty($this->allEvents)) {
                foreach ($this->allEvents as $io => $event) {
                    if ($event['genid'] == $deviceId) {
                        $deviceEvents[] = $event;
                    }
                }
            }
            
            if (!empty($deviceEvents)) {
                usort($deviceEvents, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                
                $cells = wf_TableCell(__('Date'));
                $cells .= wf_TableCell(__('Event'));
                $cells .= wf_TableCell(__('Duration'));
                $cells .= wf_TableCell(__('Fuel consumption'));
                $rows = wf_TableRow($cells, 'row1');
                
                $deviceRunning = isset($this->allDevices[$deviceId]) AND $this->allDevices[$deviceId]['running'];
                $currentTime = strtotime(curdatetime());
                
                foreach ($deviceEvents as $io => $event) {
                    $timeDisplay = '-';
                    $fuelDisplay = '-';
                    $eventTime = strtotime($event['date']);
                    
                    if ($event['event'] == 'stop') {
                        $startTime = 0;
                        foreach ($deviceEvents as $prevEvent) {
                            $prevTime = strtotime($prevEvent['date']);
                            if ($prevEvent['event'] == 'start' AND $prevTime < $eventTime AND $prevTime > $startTime) {
                                $startTime = $prevTime;
                            }
                        }
                        if ($startTime > 0) {
                            $seconds = $eventTime - $startTime;
                            $timeDisplay = zb_formatTime($seconds);
                            $fuelConsumption = $this->calculateFuelConsumption($deviceId, $seconds);
                            $fuelDisplay = round($fuelConsumption, 2) . ' ' . __('litre');
                        }
                    } elseif ($event['event'] == 'start') {
                        $stopTime = 0;
                        foreach ($deviceEvents as $nextEvent) {
                            $nextTime = strtotime($nextEvent['date']);
                            if ($nextEvent['event'] == 'stop' AND $nextTime > $eventTime AND ($stopTime == 0 OR $nextTime < $stopTime)) {
                                $stopTime = $nextTime;
                            }
                        }
                        if ($stopTime > 0) {
                            $seconds = $stopTime - $eventTime;
                            $timeDisplay = zb_formatTime($seconds);
                        } elseif ($deviceRunning) {
                            $seconds = $currentTime - $eventTime;
                            $timeDisplay = zb_formatTime($seconds);
                        }
                    }
                    
                    $cells = wf_TableCell($event['date']);
                    $cells .= wf_TableCell($event['event']);
                    $cells .= wf_TableCell($timeDisplay);
                    $cells .= wf_TableCell($fuelDisplay);
                    $rows .= wf_TableRow($cells, 'row5');
                }
                
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
            
            $backUrl = self::URL_ME . '&' . self::ROUTE_DEVICES . '=true';
            $result .= wf_delimiter();
            $result .= wf_BackLink($backUrl);
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Not exists'), 'error');
        }
        
        return ($result);
    }

    /**
     * Renders all maintenances report for all devices
     *
     * @return string
     */
    public function renderAllServices() {
        $result = '';
        
        if (!empty($this->allServices)) {
            usort($this->allServices, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            $columns=array(
                __('Date'),
                __('Device'),
                __('Motohours'),
                __('Notes')
            );
            
            if (cfr('GENERATORSMGMT')) {
                $columns[] = __('Actions');
            }

            $data=array();
            
            foreach ($this->allServices as $io => $service) {
                $deviceId = $service['genid'];
                $deviceName = __('Unknown');
                if (isset($this->allDevices[$deviceId])) {
                    $device = $this->allDevices[$deviceId];
                    $deviceName = $device['model'] . ' - ' . $device['address'];
                }
                
                $cells = wf_TableCell($service['date']);
                $cells .= wf_TableCell($deviceName);
                $cells .= wf_TableCell(round($service['motohours'], 2));
                $cells .= wf_TableCell($service['notes']);
                if (cfr('GENERATORSMGMT')) {
                
                }
                

                $dataRow=array($service['date'],
                $deviceName,
                round($service['motohours'], 2),
                $service['notes'],
                );

                if (cfr('GENERATORSMGMT')) {
                    $editDialog = wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderServiceEditForm($service['id']));
                    array_push($dataRow, $editDialog);
                }
                $data[]=$dataRow;
            }
            
            $opts='order: [[ 0, "desc" ]], "dom": \'<"F"lfB>rti<"F"ps>\',  buttons: [\'csv\', \'excel\', \'pdf\', \'print\']';
            $result=wf_JqDtEmbed($columns, $data, false, __('Maintenance'),50, $opts);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        
        $backUrl = self::URL_ME . '&' . self::ROUTE_DEVICES . '=true';
        $result .= wf_delimiter();
        $result .= wf_BackLink($backUrl);
        
        return ($result);
    }

    /**
     * Renders service edit form
     *
     * @param int $serviceId
     *
     * @return string
     */
    protected function renderServiceEditForm($serviceId) {
        $result = '';
        $serviceId = ubRouting::filters($serviceId, 'int');
        
        $service = null;
        if (!empty($this->allServices)) {
            foreach ($this->allServices as $io => $svc) {
                if ($svc['id'] == $serviceId) {
                    $service = $svc;
                    break;
                }
            }
        }
        
        if ($service) {
            $serviceDate = date('Y-m-d', strtotime($service['date']));
            $serviceTime = date('H:i', strtotime($service['date']));
            
            $inputs = wf_HiddenInput(self::PROUTE_EDIT_SERVICE, $serviceId);
            $inputs .= wf_TextInput(self::PROUTE_SERVICE_MOTO_HOURS, __('Motohours'), round($service['motohours'], 2), false, 8, 'float') . ' ';
            $inputs .= wf_DatePickerPreset(self::PROUTE_SERVICE_DATE, $serviceDate, true);
            $inputs .= wf_tag('label') . __('Date') . wf_tag('label', true) . ' ';
            $inputs .= wf_TimePickerPreset(self::PROUTE_SERVICE_TIME, $serviceTime, __('Time'), true) . ' ';
            $inputs .= wf_TextArea(self::PROUTE_SERVICE_NOTES, '', $service['notes'], true, '50x5');
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        }
        
        return ($result);
    }

    /**
     * Updates service record
     *
     * @param int $serviceId
     *
     * @return string
     */
    public function updateService($serviceId) {
        $result = '';
        $serviceId = ubRouting::filters($serviceId, 'int');
        
        $service = null;
        if (!empty($this->allServices)) {
            foreach ($this->allServices as $io => $svc) {
                if ($svc['id'] == $serviceId) {
                    $service = $svc;
                    break;
                }
            }
        }
        
        if ($service) {
            $requiredFields = array(self::PROUTE_EDIT_SERVICE, self::PROUTE_SERVICE_MOTO_HOURS);
            if (ubRouting::checkPost($requiredFields)) {
                $motohours = ubRouting::post(self::PROUTE_SERVICE_MOTO_HOURS, 'float');
                $notes = ubRouting::post(self::PROUTE_SERVICE_NOTES, 'safe');
                $notes = ubRouting::filters($notes, 'mres');
                $serviceDate = ubRouting::post(self::PROUTE_SERVICE_DATE, 'mres');
                $serviceTime = ubRouting::post(self::PROUTE_SERVICE_TIME, 'mres');
                
                if ($motohours >= 0) {
                    $serviceDateTime = $service['date'];
                    if (!empty($serviceDate) AND !empty($serviceTime)) {
                        $serviceDateTime = $serviceDate . ' ' . $serviceTime . ':00';
                    } elseif (!empty($serviceDate)) {
                        $serviceDateTime = $serviceDate . ' ' . date('H:i:s');
                    }
                    
                    $this->servicesDb->where('id', '=', $serviceId);
                    $this->servicesDb->data('date', $serviceDateTime);
                    $this->servicesDb->data('motohours', $motohours);
                    $this->servicesDb->data('notes', $notes);
                    $this->servicesDb->save();
                    $this->loadServices();
                    
                    log_register('GENERATORS DEVICE MAINTENANCE UPDATE [' . $service['genid'] . '] SERVICE [' . $serviceId . '] MOTOHOURS `' . $motohours . '`');
                } else {
                    $result = __('Invalid motohours value');
                }
            }
        } else {
            $result = __('Something went wrong') . ': [' . $serviceId . '] ' . __('Not exists');
        }
        
        return ($result);
    }


    /**
     * Runs generators watcher scripts for all devices
     *
     * @return void
     */
    public function runGeneratorsWatcher() {
        $result = '';
        if (!empty($this->allDevices)) {
            foreach ($this->allDevices as $io=>$device) {
                if (!empty($device['opalias'])) {
                    $scriptContent=$this->onePunch->getScriptContent($device['opalias']);
                    if (!empty($scriptContent)) {
                        $currentGeneratorStatus=$device['running'];
                        eval($scriptContent);
                        if (isset($generatorState)) {
                            if ($generatorState == 1 or $generatorState == 0) {
                            if ($generatorState != $currentGeneratorStatus) {
                                if ($generatorState) {
                                    $this->startDevice($device['id']);
                                } else {
                                    $this->stopDevice($device['id']);
                                }
                                
                            }
                        } else {
                            log_register('GENERATORS WATCHER ['.$device['id'].'] FAIL OPSCRIPT `' . $device['opalias'] . '` GENERATOR STATE WRONG FORMAT');
                        }

                        //cleanup generator state variable
                        unset($generatorState);
                        } else {
                            log_register('GENERATORS WATCHER ['.$device['id'].'] FAIL OPSCRIPT `' . $device['opalias'] . '` NOT SET GENERATOR STATE');
                        }
                    } else {
                        log_register('GENERATORS WATCHER ['.$device['id'].'] FAIL OPSCRIPT `' . $device['opalias'] . '` NOT FOUND');
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders all refuels report for all devices
     *
     * @return string
     */
    public function renderAllRefuels() {
        $result = '';
        
        if (!empty($this->allRefuels)) {
            usort($this->allRefuels, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            $columns=array(
                __('Date'),
                __('Device'),
                __('Liters'),
                __('Price'),
                __('Total')
            );

            if (cfr('GENERATORSMGMT')) {
                $columns[] = __('Actions');
            }

            $data=array();
            foreach ($this->allRefuels as $io => $refuel) {
                $deviceId = $refuel['genid'];
                $deviceName = __('Unknown');
                if (isset($this->allDevices[$deviceId])) {
                    $device = $this->allDevices[$deviceId];
                    $deviceName = $device['model'] . ' - ' . $device['address'];
                }
                
                $editDialog='';

                $dataRow=array($refuel['date'],
                $deviceName,
                round($refuel['liters'], 2) . ' ' . __('litre'),
                round($refuel['price'], 2),
                round($refuel['liters'] * $refuel['price'], 2)
                );

                if (cfr('GENERATORSMGMT')) {
                    $editDialog = wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderRefuelEditForm($refuel['id']));
                    array_push($dataRow, $editDialog);
                }
                $data[]=$dataRow;
            }
            
            $opts='"order": [[ 0, "desc" ]], "dom": \'<"F"lfB>rti<"F"ps>\',  buttons: [\'csv\', \'excel\', \'pdf\', \'print\']';
            $result=wf_JqDtEmbed($columns, $data, false, __('Refuels'),50, $opts);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        
        $backUrl = self::URL_ME . '&' . self::ROUTE_DEVICES . '=true';
        $result .= wf_delimiter();
        $result .= wf_BackLink($backUrl);
        
        return ($result);
    }

    /**
     * Renders refuel edit form
     *
     * @param int $refuelId
     *
     * @return string
     */
    protected function renderRefuelEditForm($refuelId) {
        $result = '';
        $refuelId = ubRouting::filters($refuelId, 'int');
        
        $refuel = null;
        if (!empty($this->allRefuels)) {
            foreach ($this->allRefuels as $io => $rf) {
                if ($rf['id'] == $refuelId) {
                    $refuel = $rf;
                    break;
                }
            }
        }
        
        if ($refuel) {
            $refuelDate = date('Y-m-d', strtotime($refuel['date']));
            $refuelTime = date('H:i', strtotime($refuel['date']));
            
            $inputs = wf_HiddenInput(self::PROUTE_EDIT_REFUEL, $refuelId);
            $inputs .= wf_TextInput(self::PROUTE_REFUEL_LITERS, __('Litre'), round($refuel['liters'], 2), false, 6, 'float') . ' ';
            $inputs .= wf_TextInput(self::PROUTE_REFUEL_PRICE, __('Price'), round($refuel['price'], 2), false, 4, 'finance') . ' ';
            $inputs .= wf_DatePickerPreset('refueldate', $refuelDate, true);
            $inputs .= wf_tag('label') . __('Date') . wf_tag('label', true) . ' ';
            $inputs .= wf_TimePickerPreset('refueltime', $refuelTime, __('Time'), false) . ' ';
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        }
        
        return ($result);
    }

    /**
     * Updates refuel record
     *
     * @param int $refuelId
     *
     * @return string
     */
    public function updateRefuel($refuelId) {
        $result = '';
        $refuelId = ubRouting::filters($refuelId, 'int');
        
        $refuel = null;
        if (!empty($this->allRefuels)) {
            foreach ($this->allRefuels as $io => $rf) {
                if ($rf['id'] == $refuelId) {
                    $refuel = $rf;
                    break;
                }
            }
        }
        
        if ($refuel) {
            $requiredFields = array(self::PROUTE_EDIT_REFUEL, self::PROUTE_REFUEL_LITERS);
            if (ubRouting::checkPost($requiredFields)) {
                $liters = ubRouting::post(self::PROUTE_REFUEL_LITERS, 'float');
                $price = ubRouting::post(self::PROUTE_REFUEL_PRICE, 'float');
                $refuelDate = ubRouting::post('refueldate', 'mres');
                $refuelTime = ubRouting::post('refueltime', 'mres');
                
                if ($liters > 0) {
                    $refuelDateTime = $refuel['date'];
                    if (!empty($refuelDate) AND !empty($refuelTime)) {
                        $refuelDateTime = $refuelDate . ' ' . $refuelTime . ':00';
                    } elseif (!empty($refuelDate)) {
                        $refuelDateTime = $refuelDate . ' ' . date('H:i:s');
                    }
                    
                    $deviceId = $refuel['genid'];
                    $oldLiters = $refuel['liters'];
                    $litersDiff = $liters - $oldLiters;
                    
                    if (isset($this->allDevices[$deviceId])) {
                        $currentIntank = $this->allDevices[$deviceId]['intank'];
                        $tankVolume = $this->allDevices[$deviceId]['tankvolume'];
                        $newIntank = $currentIntank + $litersDiff;
                        
                        if ($newIntank >= 0 AND $newIntank <= $tankVolume) {
                            $this->refuelsDb->where('id', '=', $refuelId);
                            $this->refuelsDb->data('date', $refuelDateTime);
                            $this->refuelsDb->data('liters', $liters);
                            $this->refuelsDb->data('price', $price);
                            $this->refuelsDb->save();
                            
                            $this->setDeviceIntank($deviceId, $newIntank);
                            $this->loadDevices();
                            $this->loadRefuels();
                            
                            log_register('GENERATORS DEVICE REFUEL UPDATE [' . $deviceId . '] REFUEL [' . $refuelId . '] LITERS `' . $liters . '` PRICE `' . $price . '`');
                        } else {
                            $result = __('Invalid tank level') . ': ' . __('Minimum') . ' 0, ' . __('Maximum') . ' ' . $tankVolume . ' ' . __('litre');
                        }
                    } else {
                        $result = __('Device not found');
                    }
                } else {
                    $result = __('Invalid values');
                }
            }
        } else {
            $result = __('Something went wrong') . ': [' . $refuelId . '] ' . __('Not exists');
        }
        
        return ($result);
    }


    /**
     * Renders devices map
     *
     * @return string
     */
    public function renderDevicesMap() {
        $result = '';
        global $ubillingConfig;
        $mapsCfg = $ubillingConfig->getYmaps();
        $mapCenter = $mapsCfg['CENTER'];
        $mapZoom = $mapsCfg['ZOOM'];
        
        $editor = '';
        $result.=generic_MapContainer('100%', '600px', 'ubmap');
        if (!empty($this->allDevices)) {
            $placemarks='';
            foreach ($this->allDevices as $io => $device) {
                if (!empty($device['geo'])) {
                    $deviceLabel=$device['model'] . ' - ' . $device['address'];
                    $deviceIcon=($device['running']) ? sm_MapGoodIcon() : sm_MapBadIcon();
                    $deviceState=($device['running']) ? __('Running') : __('Stopped');
                    $deviceInTankPercent=__('In tank').': '.$this->calculateInTankPercent($device['id']).'%';
                    $placemarks.= sm_MapAddMark($device['geo'], $deviceLabel, $deviceState .', '. $deviceInTankPercent, '', $deviceIcon);
                }
            }
            
        }

        $result .= generic_MapInit($mapCenter, $mapZoom, $mapsCfg['TYPE'], $placemarks, $editor, $mapsCfg['LANG']);
        $result.= wf_delimiter();
        $result.= wf_BackLink(self::URL_ME.'&'.self::ROUTE_DEVICES.'=true');
        return ($result);
    }

    /**
     * Calculates in tank percent for device
     *
     * @param int $deviceId
     *
     * @return float
     */
    public function calculateInTankPercent($deviceId) {
        $result = 0;
        $deviceId = ubRouting::filters($deviceId, 'int');
        if (isset($this->allDevices[$deviceId])) {
            $device = $this->allDevices[$deviceId];
            $runningSeconds=0;
            $fuelConsumed=0;
            if ($device['running']) {
            $runningSeconds = $this->getDeviceRunningTime($device['id']);
                if ($runningSeconds > 0) {
                    $fuelConsumption = $this->calculateFuelConsumption($device['id'], $runningSeconds);
                    $fuelConsumed += $fuelConsumption;
                
                }
            }
            $inTankPercent = (($device['intank']-$fuelConsumed) / $device['tankvolume']) * 100;
            $result = round($inTankPercent, 2);
        }
        return ($result);
    }
}