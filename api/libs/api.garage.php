<?php

class Garage {

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
     * Contains all active employee
     *
     * @var array
     */
    protected $allActiveEmployee = array();

    /**
     * Contains all employee
     *
     * @var array
     */
    protected $allEmployee = array();

    /**
     * TODO
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
     * Contains available fuel types
     *
     * @var array
     */
    protected $fuelTypes = array();

    /**
     * Some static stuff: routes, tables, etc...
     */
    const TABLE_CARS = 'garage_cars';
    const TABLE_DRIVERS = 'garage_drivers';
    const URL_ME = '?module=garage';
    const PROUTE_NEWDRIVER = 'newdriveremployeeid';

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
    const PROUTE_CARFUELTYPE = 'carfueltype';
    const PROUTE_CARGASTANK = 'cargastank';
    const PROUTE_CARWEIGHT = 'carweight';

    /**
     * Preloads some required data and sets some props. 
     * What did you expect here?
     */
    public function __construct() {
        $this->initMessages();
        $this->setFuelTypes();
        $this->initCars();
        $this->loadCars();
        $this->initDrivers();
        $this->loadDrivers();
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
    public function renderDriverCreateForm() {
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
     * Renders existing cars drivers
     * 
     * @return string
     */
    public function renderDriversList() {
        $result = '';
        if (!empty($this->allDrivers)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Driver'));
            $cells .= wf_TableCell(__('Car'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allDrivers as $employeeId => $eachDriverData) {
                $cells = wf_TableCell($employeeId);
                $cells .= wf_TableCell(@$this->allEmployee[$employeeId]);
                $carData = @$this->allCars[$eachDriverData['carid']];
                $cells .= wf_TableCell(@$carData['vendor'] . ' ' . @$carData['model'] . ' ' . @$carData['number']);
                $cells .= wf_TableCell('TODO');
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Renders new car creation form
     * 
     * @return string
     */
    public function renderCarCreateForm() {
        $result = '';

        $inputs = wf_HiddenInput(self::PROUTE_NEWCAR, 'true'); //just creation flag
        $inputs .= wf_TextInput(self::PROUTE_CARVENDOR, __('Vendor'), '', true, 20, '');
        $inputs .= wf_TextInput(self::PROUTE_CARMODEL, __('Model'), '', true, 20, '');
        $inputs .= wf_TextInput(self::PROUTE_CARNUMBER, __('Number'), '', true, 20, 'alphanumeric');
        $inputs .= wf_TextInput(self::PROUTE_CARVIN, __('VIN'), '', true, 20, 'alphanumeric');
        $inputs .= wf_TextInput(self::PROUTE_CARYEAR, __('Year'), '', true, 5, 'digits');
        $inputs .= wf_TextInput(self::PROUTE_CARPOWER, __('Power') . ' (' . __('hp') . ')', '', true, 5, 'digits');
        $inputs .= wf_TextInput(self::PROUTE_CARENGINE, __('Engine') . ' (' . __('cc') . ')', '', true, 5, 'digits');
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
     * Renders available cars list
     * 
     * @return string
     */
    public function renderCarsList() {
        $result = '';
        if (!empty($this->allCars)) {

            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Model'));
            $cells .= wf_TableCell(__('Number'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allCars as $carId => $carData) {
                $cells = wf_TableCell($carData['id']);
                $cells .= wf_TableCell($carData['vendor'] . ' ' . $carData['model']);
                $cells .= wf_TableCell($carData['number']);
                $cells .= wf_TableCell('TODO');
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage('Nothing to show', 'warning');
        }
        return($result);
    }

}
