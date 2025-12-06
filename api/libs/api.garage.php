<?php

/**
 * Vehicles management and accounting
 */
class Garage {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all of available cars as id=>carData
     *
     * @var array
     */
    protected $allCars = array();

    /**
     * Garage cars database abstraction placeholder
     *
     * @var object
     */
    protected $cars = '';

    /**
     * Contains available car drivers as employeeid=>DriverData
     *
     * @var array
     */
    protected $allDrivers = array();

    /**
     * Garage cars drivers database abstraction layer
     *
     * @var object
     */
    protected $drivers = '';

    /**
     * Contains all active employee as id=>name
     *
     * @var array
     */
    protected $allActiveEmployee = array();

    /**
     * Contains all employee as id=>name
     *
     * @var array
     */
    protected $allEmployee = array();

    /**
     * Contains all mileages as carid=>date=>mileage (in meters)
     *
     * @var array
     */
    protected $allMileage = array();

    /**
     * System message helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Database abstraction layer placeholder for cars mileage counters
     *
     * @var object
     */
    protected $mileage = '';

    /**
     * Contains available fuel types
     *
     * @var array
     */
    protected $fuelTypes = array();

    /**
     * Mapon service enabled flag
     *
     * @var bool
     */
    protected $maponEnabled = false;

    /**
     * Some static stuff: routes, tables, etc...
     */
    const TABLE_CARS = 'garage_cars';
    const TABLE_DRIVERS = 'garage_drivers';
    const TABLE_MILEAGE = 'garage_mileage';
    const TABLE_MAPONUNITS = 'garage_mapon';
    const URL_ME = '?module=garage';
    const PROUTE_NEWDRIVER = 'newdriveremployeeid';
    const ROUTE_CARS = 'cars';
    const ROUTE_DRIVERS = 'drivers';
    const ROUTE_MILEAGE = 'mileage';
    const ROUTE_DRIVERDEL = 'deletedriveremployeeid';
    const ROUTE_CARDEL = 'deletethiscarid';
    const PROUTE_DRIVEREDIT = 'editsomedriver';
    const PROUTE_DRIVERCAR = 'driversetcar';
    const PROUTE_MILEAGEKM = 'newmileagekmeterscount';
    const PROUTE_MILEAGECAR = 'newmileagecarid';

    /**
     * Basic car parameters here
     */
    const PROUTE_NEWCAR = 'createnewcarplease';
    const PROUTE_CARVENDOR = 'carvendor';
    const PROUTE_CARMODEL = 'carmodel';
    const PROUTE_CARNUMBER = 'carnumber';
    const PROUTE_CARVIN = 'carvin';
    const PROUTE_CARYEAR = 'caryear';
    const PROUTE_CARPOWER = 'carpower';
    const PROUTE_CARENGINE = 'carengine';
    const PROUTE_CARCONSUMPTION = 'carfuelconsumption';
    const PROUTE_CARFUELTYPE = 'carfueltype';
    const PROUTE_CARGASTANK = 'cargastank';
    const PROUTE_CARWEIGHT = 'carweight';

    /**
     * Preloads some required data and sets some props. 
     * What did you expect here?
     */
    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->setFuelTypes();
        $this->initCars();
        $this->loadCars();
        $this->initDrivers();
        $this->loadDrivers();
        $this->initMileage();
        $this->loadMileage();
        $this->loadEmployee();
    }

    /**
     * Inits system message helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads required configs for further usage
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits cars database abstraction layer
     * 
     * @return void
     */
    protected function initCars() {
        $this->cars = new NyanORM(self::TABLE_CARS);
    }

    /**
     * Loads existing cars from database
     * 
     * @return void
     */
    protected function loadCars() {
        $this->allCars = $this->cars->getAll('id');
    }

    /**
     * Inits drivers database abstraction layer
     * 
     * @return void
     */
    protected function initDrivers() {
        $this->drivers = new NyanORM(self::TABLE_DRIVERS);
    }

    /**
     * Loads available drivers from database
     * 
     * @return void
     */
    protected function loadDrivers() {
        $this->allDrivers = $this->drivers->getAll('employeeid');
    }

    /**
     * Inits mileages database abstraction layer
     * 
     * @return void
     */
    protected function initMileage() {
        $this->mileage = new NyanORM(self::TABLE_MILEAGE);
    }

    /**
     * Loads existing mileage counters from database
     * 
     * @return void
     */
    protected function loadMileage() {
        $mileageTmp = $this->mileage->getAll();
        if (!empty($mileageTmp)) {
            foreach ($mileageTmp as $io => $each) {
                $dateDay = strtotime($each['date']);
                $dateDay = date("Y-m-d", $dateDay); //just a day of month
                $dayMileage = $each['mileage'];
//                 TODO: take some decision on this
//                //on many records by same date
//                if (isset($this->allMileage[$each['carid']][$dateDay])) {
//                    $dayMileage += $this->allMileage[$each['carid']][$dateDay];
//                }
                $this->allMileage[$each['carid']][$dateDay] = $dayMileage;
            }
        }
    }

    /**
     * Renders mileage creation form
     * 
     * @return string
     */
    public function renderMileageCreateForm() {
        $result = '';
        $carsTmp = array();
        if (!empty($this->allCars)) {
            foreach ($this->allCars as $io => $each) {
                $driverId = $this->getCarDriver($each['id']);
                $driverName = (isset($this->allEmployee[$driverId])) ? $this->allEmployee[$driverId] : '';
                $carLabel = $each['vendor'] . ' ' . $each['model'] . ' ' . $each['number'] . ' - ' . $driverName;
                $carsTmp[$each['id']] = $carLabel;
            }
        }

        $inputs = wf_Selector(self::PROUTE_MILEAGECAR, $carsTmp, __('Car'), '', true);
        $inputs .= wf_TextInput(self::PROUTE_MILEAGEKM, __('Mileage') . ' (' . __('km') . ')', '', true, 8, 'digits');
        $inputs .= wf_Submit(__('Save'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Creates new mileage record in database
     * 
     * @param int $carId
     * @param int $mileage
     * @param bool $inKilometers
     * 
     * @return void/string on error
     */
    public function createMileage($carId, $mileage, $inKilometers) {
        $result = '';
        $newDate = curdatetime();
        $mileage = ubRouting::filters($mileage, 'int');
        $carId = ubRouting::filters($carId, 'int');
        if ($inKilometers) {
            $mileage = $mileage * 1000; //omg omg, so much math!
        }
        if (!empty($mileage) AND ! empty($carId)) {
            //TODO: add some new >= old check here
            $this->mileage->data('date', $newDate);
            $this->mileage->data('carid', $carId);
            $this->mileage->data('mileage', $mileage);
            $this->mileage->create();
            log_register('GARAGE MILEAGE CREATE CAR [' . $carId . '] M `' . $mileage . '`');
        } else {
            $result .= __('Something went wrong') . ': ' . __('Car') . ' ' . __('or') . ' ' . __('Mileage') . ' ' . __('is empty');
        }

        return($result);
    }

    /**
     * Sets available fuel types
     * 
     * @return void
     */
    protected function setFuelTypes() {
        $this->fuelTypes['petrol'] = __('Petrol');
        $this->fuelTypes['diesel'] = __('Diesel');
        $this->fuelTypes['lpg'] = __('Liquefied petroleum gas');
        $this->fuelTypes['electric'] = __('Electric');
        $this->fuelTypes['wood'] = __('On the wood');
    }

    /**
     * Loads available active employee from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $employeeRaw = ts_GetAllEmployeeData();
        if (!empty($employeeRaw)) {
            foreach ($employeeRaw as $employeeId => $employeeData) {
                $this->allEmployee[$employeeId] = $employeeData['name'];
                if ($employeeData['active']) {
                    $this->allActiveEmployee[$employeeId] = $employeeData['name'];
                }
            }
        }
    }

    /**
     * Renders new driver creation form
     * 
     * @return string
     */
    protected function renderDriverCreateForm() {
        $result = '';
        $params = array();
        if (!empty($this->allActiveEmployee)) {
            foreach ($this->allActiveEmployee as $employeeId => $employeeName) {
                if (!isset($this->allDrivers[$employeeId])) {
                    $params[$employeeId] = $employeeName;
                }
            }
        }

        if (!empty($params)) {
            $inputs = wf_Selector(self::PROUTE_NEWDRIVER, $params, __('Worker'), '', true);
            $inputs .= wf_Submit(__('Create'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('No employee to be an drivers'), 'info');
        }
        return($result);
    }

    /**
     * Creates new employee in database
     * 
     * @param int $employeeId
     * 
     * @return void
     */
    public function createDriver($employeeId) {
        $newEmployeeId = ubRouting::filters($employeeId, 'int');

        if (!isset($this->allDrivers[$newEmployeeId])) {
            if (isset($this->allActiveEmployee[$newEmployeeId])) {
                $this->drivers->data('employeeid', $newEmployeeId);
                $this->drivers->data('carid', '');
                $this->drivers->create();
                $newId = $this->drivers->getLastId();
                log_register('GARAGE DRIVER CREATE [' . $newEmployeeId . '] AS [' . $newId . ']');
            }
        }
    }

    /**
     * Deletes existing driver from database
     * 
     * @param int $employeeId
     * 
     * @return
     */
    public function deleteDriver($employeeId) {
        $employeeId = ubRouting::filters($employeeId, 'int');
        if (isset($this->allDrivers)) {
            $this->drivers->where('employeeid', '=', $employeeId);
            $this->drivers->delete();
            log_register('GARAGE DRIVER DELETE [' . $employeeId . ']');
        }
    }

    /**
     * Deletes existing car from database
     * 
     * @param int $carId
     * 
     * @return void/string on error
     */
    public function deleteCar($carId) {
        $result = '';
        $carId = ubRouting::filters($carId, 'int');
        if (isset($this->allCars[$carId])) {
            if (!$this->isCarProtected($carId)) {
                $this->cars->where('id', '=', $carId);
                $this->cars->delete();
                log_register('GARAGE CAR DELETE [' . $carId . ']');
            } else {
                $result .= __('You cant delete a car which have a driver');
                log_register('GARAGE CAR DELETE [' . $carId . '] FAIL BUSY');
            }
        } else {
            log_register('GARAGE CAR DELETE [' . $carId . '] FAIL NOT_EXISTS');
        }

        return($result);
    }

    /**
     * Returns array of cars which not used by another drivers
     * 
     * @return array
     */
    protected function getFreeCars() {
        $result = array();
        if (!empty($this->allCars)) {
            $carsTmp = $this->allCars;
            if (!empty($this->allDrivers)) {
                foreach ($this->allDrivers as $io => $eachDriver) {
                    if (!empty($eachDriver['carid'])) {
                        if (isset($carsTmp[$eachDriver['carid']])) {
                            unset($carsTmp[$eachDriver['carid']]);
                        }
                    }
                }
            }

            if (!empty($carsTmp)) {
                foreach ($carsTmp as $carId => $carData) {
                    $result[$carId] = $carData['vendor'] . ' ' . $carData['model'] . ' ' . $carData['number'];
                }
            }
        }
        return($result);
    }

    /**
     * Renders existing driver editing form
     * 
     * @param int $employeeId
     * 
     * @return string
     */
    protected function renderDriverEditFrom($employeeId) {
        $result = '';
        $employeeId = ubRouting::filters($employeeId, 'int');
        if (isset($this->allDrivers[$employeeId])) {
            $freeCars = array('' => '-');
            $freeCars += $this->getFreeCars();
            $inputs = wf_HiddenInput(self::PROUTE_DRIVEREDIT, $employeeId);
            $inputs .= wf_Selector(self::PROUTE_DRIVERCAR, $freeCars, __('Car'), $this->allDrivers[$employeeId]['carid'], false);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_NO_DRIVER_EXIST [' . $employeeId . ']', 'error');
        }
        return($result);
    }

    /**
     * Sets some car as occupied by some driver
     * 
     * @param int $employeeId
     * 
     * @return void
     */
    public function setDriverCar($employeeId, $carId) {
        $employeeId = ubRouting::filters($employeeId, 'int');
        $carId = ubRouting::filters($carId, 'int');
        if (isset($this->allDrivers[$employeeId])) {
            $oldCar = $this->allDrivers[$employeeId]['carid'];
            if (empty($carId)) {
                //drop car from the driver
                $this->drivers->where('employeeid', '=', $employeeId);
                $this->drivers->data('carid', '0');
                $this->drivers->save();
                log_register('GARAGE DRIVER CHANGE CAR [' . $oldCar . '] TO [0]');
            } else {
                //set new car to driver
                $freeCars = $this->getFreeCars();
                if (isset($freeCars[$carId])) {
                    $this->drivers->where('employeeid', '=', $employeeId);
                    $this->drivers->data('carid', $carId);
                    $this->drivers->save();
                    log_register('GARAGE DRIVER CHANGE CAR [' . $oldCar . '] TO [' . $carId . ']');
                } else {
                    log_register('GARAGE DRIVER CHANGE CAR [' . $oldCar . '] TO [' . $carId . '] FAIL BUSY');
                }
            }
        }
    }

    /**
     * Renders existing cars drivers
     * 
     * @return string
     */
    public function renderDriversList() {
        $result = '';
        if (!empty($this->allDrivers)) {

            $cells = wf_TableCell(__('Driver'));
            $cells .= wf_TableCell(__('Car'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allDrivers as $employeeId => $eachDriverData) {
                $cells = wf_TableCell(@$this->allEmployee[$employeeId]);
                $carData = @$this->allCars[$eachDriverData['carid']];
                $cells .= wf_TableCell(@$carData['vendor'] . ' ' . @$carData['model'] . ' ' . @$carData['number']);
                $actControls = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DRIVERDEL . '=' . $employeeId, web_delete_icon(), $this->messages->getDeleteAlert());
                $actControls .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderDriverEditFrom($employeeId));
                $cells .= wf_TableCell($actControls);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        //new driver creation interface here
        $result .= wf_tag('br') . wf_modalAuto(web_icon_create() . ' ' . __('Create'), __('Create'), $this->renderDriverCreateForm(), 'ubButton');
        return($result);
    }

    /**
     * Renders new car creation form
     * 
     * @return string
     */
    protected function renderCarCreateForm() {
        $result = '';

        $inputs = wf_HiddenInput(self::PROUTE_NEWCAR, 'true'); //just creation flag
        $inputs .= wf_TextInput(self::PROUTE_CARVENDOR, __('Vendor'), '', true, 20, '');
        $inputs .= wf_TextInput(self::PROUTE_CARMODEL, __('Model'), '', true, 20, '');
        $inputs .= wf_TextInput(self::PROUTE_CARNUMBER, __('Number'), '', true, 20, 'alphanumeric');
        $inputs .= wf_TextInput(self::PROUTE_CARVIN, __('VIN'), '', true, 20, 'alphanumeric');
        $inputs .= wf_TextInput(self::PROUTE_CARYEAR, __('Year'), '', true, 5, 'digits');
        $inputs .= wf_TextInput(self::PROUTE_CARPOWER, __('Power') . ' (' . __('hp') . ')', '', true, 5, 'digits');
        $inputs .= wf_TextInput(self::PROUTE_CARENGINE, __('Vehicle engine') . ' (' . __('cc') . ')', '', true, 5, 'digits');
        $inputs .= wf_TextInput(self::PROUTE_CARCONSUMPTION, __('Fuel consumption') . ' (' . __('litre') . '/100' . __('km') . ')', '', true, 5);
        $inputs .= wf_Selector(self::PROUTE_CARFUELTYPE, $this->fuelTypes, __('Fuel type'), '', true);
        $inputs .= wf_TextInput(self::PROUTE_CARGASTANK, __('Gas tank') . ' (' . __('litre') . ')', '', true, 4, 'digits');
        $inputs .= wf_TextInput(self::PROUTE_CARWEIGHT, __('Weight') . ' (' . __('kg') . ')', '', true, 4, 'digits');
        $inputs .= wf_Submit(__('Create'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Creates new car in database
     * 
     * @return void
     */
    public function createCar() {
        if (ubRouting::checkPost(array(self::PROUTE_NEWCAR, self::PROUTE_CARVENDOR, self::PROUTE_CARMODEL))) {
            $newCarVendor = ubRouting::post(self::PROUTE_CARVENDOR, 'mres');
            $newCarModel = ubRouting::post(self::PROUTE_CARMODEL, 'mres');
            //required fields
            if (!empty($newCarVendor) AND ! empty($newCarModel)) {
                $this->cars->data('vendor', ubRouting::post(self::PROUTE_CARVENDOR, 'mres'));
                $this->cars->data('model', ubRouting::post(self::PROUTE_CARMODEL, 'mres'));
                $this->cars->data('number', ubRouting::post(self::PROUTE_CARNUMBER, 'mres'));
                $this->cars->data('vin', ubRouting::post(self::PROUTE_CARVIN, 'mres'));
                $this->cars->data('year', ubRouting::post(self::PROUTE_CARYEAR, 'int'));
                $this->cars->data('power', ubRouting::post(self::PROUTE_CARPOWER, 'int'));
                $this->cars->data('engine', ubRouting::post(self::PROUTE_CARENGINE, 'int'));
                $this->cars->data('fuelconsumption', ubRouting::post(self::PROUTE_CARCONSUMPTION, 'float'));
                $this->cars->data('fueltype', ubRouting::post(self::PROUTE_CARFUELTYPE, 'mres'));
                $this->cars->data('gastank', ubRouting::post(self::PROUTE_CARGASTANK, 'int'));
                $this->cars->data('weight', ubRouting::post(self::PROUTE_CARWEIGHT, 'int'));
                $this->cars->create();
                $newCarId = $this->cars->getLastId();
                log_register('GARAGE CAR CREATE [' . $newCarId . ']');
            }
        }
    }

    /**
     * Checks is car used by someone?
     * 
     * @param int $carId
     * 
     * @return bool
     */
    protected function isCarProtected($carId) {
        $result = false;
        $carId = ubRouting::filters($carId, 'int');
        if (isset($this->allCars[$carId])) {
            $freeCars = $this->getFreeCars();
            if (!isset($freeCars[$carId])) {
                $result = true;
            }
        }
        return($result);
    }

    /**
     * Returns car driver employeeId
     * 
     * @param int $carId
     * 
     * @return int
     */
    protected function getCarDriver($carId) {
        $result = 0;
        if (!empty($this->allDrivers)) {
            foreach ($this->allDrivers as $employeeId => $driverData) {
                if ($driverData['carid'] == $carId) {
                    $result = $employeeId;
                }
            }
        }
        return($result);
    }

    /**
     * Renders available cars list
     * 
     * @return string
     */
    public function renderCarsList() {
        $result = '';
        if (!empty($this->allCars)) {
            $cells = wf_TableCell(__('Model'));
            $cells .= wf_TableCell(__('Number'));
            $cells .= wf_TableCell(__('Driver'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allCars as $carId => $carData) {
                $cells = wf_TableCell($carData['vendor'] . ' ' . $carData['model']);
                $cells .= wf_TableCell($carData['number']);
                $carDriverId = $this->getCarDriver($carId);
                $driverName = ($carDriverId) ? @$this->allEmployee[$carDriverId] : '';
                $cells .= wf_TableCell($driverName);
                $carControls = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_CARDEL . '=' . $carId, web_delete_icon(), $this->messages->getDeleteAlert());
                $cells .= wf_TableCell($carControls);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        //creation interface here
        $result .= wf_tag('br') . wf_modalAuto(web_icon_create() . ' ' . __('Create'), __('Create'), $this->renderCarCreateForm(), 'ubButton');
        return($result);
    }

    /**
     * Renders basic controls panel
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_CARS . '=true', wf_img('skins/car_small.png') . ' ' . __('Cars'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_DRIVERS . '=true', wf_img('skins/driver_small.png') . ' ' . __('Drivers'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_MILEAGE . '=true', wf_img('skins/icon_street.gif') . ' ' . __('Mileage'), false, 'ubButton');
        return($result);
    }

}
