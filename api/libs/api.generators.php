<?php

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
     * Contains service types database abstraction layer
     *
     * @var object
     */
    protected $serviceTypesDb = '';

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
     * Contains all generator service types
     *
     * @var array
     */
    protected $allServiceTypes = array();

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

        $this->loadDevices();
        $this->loadServiceTypes();
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
     * Initializes database abstraction layers
     *
     * @return void
     */
    protected function initDb() {
        $this->devicesDb = new NyanORM(self::TABLE_DEVICES);
        $this->serviceTypesDb = new NyanORM(self::TABLE_SERVICE_TYPES);
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
     * Loads all service types from database
     *
     * @return void
     */
    protected function loadServiceTypes() {
        $this->allServiceTypes = $this->serviceTypesDb->getAll('id');
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
        return $result;
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
        
        return $result;
    }

    /**
     * Renders navigation controls
     *
     * @return string
     */
    public function renderControls() {
        $result = '';
        if (ubRouting::checkGet(self::ROUTE_DEVICES)) {
        if (cfr('GENERATORSMGMT')) {
            $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create new'), __('Create new'), $this->renderDeviceCreateForm(), 'ubButton');
        }
    }
        $devicesUrl = self::URL_ME . '&' . self::ROUTE_DEVICES . '=true';
        $result .= wf_Link($devicesUrl, wf_img('skins/icon_generators.png') . ' ' . __('Devices'), false, 'ubButton') . ' ';
     
        return $result;
    }

    /**
     * Renders generator devices list with actions
     *
     * @return string
     */
    public function renderDeviceList() {
        $result = '';
        
        if (!empty($this->allDevices)) {
            $cells = wf_TableCell(__('Model'));
            $cells .= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('Running'));
            $cells .= wf_TableCell(__('Motohours'));
            $cells.= wf_TableCell(__('Events'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allDevices as $io=>$device) {
                $cells = wf_TableCell($device['model']);
                $cells .= wf_TableCell($device['address']);
                $runningDisplay = web_bool_led($device['running']);
                if ($device['running']) {
                    $runningSeconds = $this->getDeviceRunningTime($device['id']);
                    if ($runningSeconds > 0) {
                        $fuelConsumption = $this->calculateFuelConsumption($device['id'], $runningSeconds);
                        $runningDisplay .= ' (';
                        $runningDisplay .= zb_formatTime($runningSeconds);
                        $runningDisplay .= ', ' . round($fuelConsumption, 2) . ' ' . __('litre');
                        $runningDisplay .= ')';
                    }
                }
                $cells .= wf_TableCell($runningDisplay);
                $cells .= wf_TableCell($device['motohours']);
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
                    $editDialog = wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderDeviceEditForm($device['id']));
                    $deviceControls .= $editDialog;
                }

                if ($device['running']) {
                    $stopUrl = self::URL_ME . '&' . self::ROUTE_STOP_DEVICE . '=' . $device['id'];
                    $deviceControls .= wf_Link($stopUrl, wf_img('skins/pause.png',__('Stop')), false, '');
                } else {
                    $startUrl = self::URL_ME . '&' . self::ROUTE_START_DEVICE . '=' . $device['id'];
                    $deviceControls .= wf_Link($startUrl, wf_img('skins/play.png',__('Start')), false, '');
                    
                }
                
                $cells .= wf_TableCell($deviceControls);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
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
        $inputs .= wf_TextInput(self::PROUTE_DEV_OP_ALIAS, __('One-punch').' '.__('script'), '', true, 10);
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return $result;
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
            $inputs .= wf_TextInput(self::PROUTE_DEV_OP_ALIAS, __('One-punch').' '.__('script'), $device['opalias'], true, 10);
            
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Not exists'), 'error');
        }
        return $result;
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
                $timePassed=$this->updateDeviceMotohours($deviceId);
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
        return $result;
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
                            $fuelConsumption = $this->calculateFuelConsumption($deviceId, $seconds);
                            $fuelDisplay = round($fuelConsumption, 2) . ' ' . __('litre');
                        } elseif ($deviceRunning) {
                            $seconds = $currentTime - $eventTime;
                            $timeDisplay = zb_formatTime($seconds);
                            $fuelConsumption = $this->calculateFuelConsumption($deviceId, $seconds);
                            $fuelDisplay = round($fuelConsumption, 2) . ' ' . __('litre');
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
        
        return $result;
    }
}