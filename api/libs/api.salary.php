<?

class Salary {

    protected $allEmployee = array();
    protected $allJobtypes = array();
    protected $allJobPrices = array();
    protected $allJobUnits = array();
    protected $allWages = array();
    protected $unitTypes = array();

    public function __construct() {
        $this->setUnitTypes();
        $this->loadEmployee();
        $this->loadJobtypes();
        $this->loadJobprices();
        $this->loadWages();
    }

    /**
     * Loads active employees from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $this->allEmployee = ts_GetActiveEmployee();
    }

    /**
     * Loads available jobtypes from database
     * 
     * @return void
     */
    protected function loadJobtypes() {
        $this->allJobtypes = ts_GetAllJobtypes();
    }

    protected function setUnitTypes() {
        $this->unitTypes['quantity'] = __('quantity');
        $this->unitTypes['meter'] = __('meter');
        $this->unitTypes['kilometer'] = __('kilometer');
        $this->unitTypes['money'] = __('money');
        $this->unitTypes['time'] = __('time');
        $this->unitTypes['litre'] = __('litre');
    }

    /**
     * Loads existing job prices from database 
     * 
     * @return void
     */
    protected function loadJobprices() {
        $query = "SELECT * from `salary_jobprices`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allJobPrices[$each['jobtypeid']] = $each['price'];
                $this->allJobUnits[$each['jobtypeid']] = $each['unit'];
            }
        }
    }

    /**
     * Loads existing employee wages from database 
     * 
     * @return void
     */
    protected function loadWages() {
        $query = "SELECT * from `salary_wages`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allWages[$each['employeeid']]['wage'] = $each['wage'];
                $this->allWages[$each['employeeid']]['bounty'] = $each['bounty'];
            }
        }
    }

    /**
     * Renders job price creation form
     * 
     * @return string
     */
    public function jobPricesCreateForm() {
        $result = '';
        if (!empty($this->allJobtypes)) {
            $inputs = wf_Selector('newjobtypepriceid', $this->allJobtypes, __('Job type'), '', false).' ';
            $inputs.=wf_Selector('newjobtypepriceunit', $this->unitTypes, __('Units'), '', false).' ';
            $inputs.= wf_TextInput('newjobtypeprice', __('Price'), '', false, 5).' ';
            $inputs.= wf_Submit(__('Create'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }
    
    
    /**
     * Renders job price editing form
     * 
     * @return string
     */
    protected function jobPricesEditForm($jobtypeid) {
        $result = '';
        if (isset($this->allJobPrices[$jobtypeid])) {
            $inputs = wf_Selector('editjobtypepriceid', $this->allJobtypes, __('Job type'), $jobtypeid, true);
            $inputs.= wf_Selector('editjobtypepriceunit', $this->unitTypes, __('Units'), $this->allJobUnits[$jobtypeid], true);
            $inputs.= wf_TextInput('editjobtypeprice', __('Price'), $this->allJobPrices[$jobtypeid], true, 5);
            $inputs.= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Creates job type pricing database record
     * 
     * @param int $jobtypeid
     * @param float $price
     * @param string $unit
     * 
     * @return void
     */
    public function jobPriceCreate($jobtypeid, $price, $unit) {
        $jobtypeid = vf($jobtypeid, 3);
        if (!isset($this->allJobPrices[$jobtypeid])) {
            $priceF = mysql_real_escape_string($price);
            $unit = mysql_real_escape_string($unit);
            $query = "INSERT INTO `salary_jobprices` (`id`, `jobtypeid`, `price`, `unit`) VALUES (NULL, '" . $jobtypeid . "', '" . $priceF . "', '" . $unit . "');";
            nr_query($query);
            log_register('SALARY CREATE JOBPRICE JOBID [' . $jobtypeid . '] PRICE `' . $price . '`');
        } else {
            log_register('SALARY CREATE JOBPRICE FAILED EXIST JOBID [' . $jobtypeid . ']');
        }
    }

    /**
     * Renders job prices list with required controls
     * 
     * @return string
     */
    public function jobPricesRender() {
        $result = '';
        $messages=new UbillingMessageHelper();
        $cells= wf_TableCell(__('Job type'));
        $cells.= wf_TableCell(__('Units'));
        $cells.= wf_TableCell(__('Price'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allJobPrices)) {
            foreach ($this->allJobPrices as $jobtypeid => $eachprice) {
                $cells= wf_TableCell(@$this->allJobtypes[$jobtypeid]);
                $cells.= wf_TableCell(__($this->allJobUnits[$jobtypeid]));
                $cells.= wf_TableCell($eachprice);
                $actLinks=  wf_JSAlert('?module=salary&deletejobprice='.$jobtypeid, web_delete_icon(), $messages->getDeleteAlert());
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->jobPricesEditForm($jobtypeid));
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }
        $result=  wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }
    
    /**
     * Deletes jobprice by jobtype id from database
     * 
     * @param int $jobtypeid
     * 
     * @return void
     */
    public function jobPriceDelete($jobtypeid) {
        $jobtypeid=vf($jobtypeid,3);
        $query="DELETE from `salary_jobprices` WHERE `jobtypeid`='".$jobtypeid."';";
        nr_query($query);
        log_register('SALARY DELETE JOBPRICE JOBID [' . $jobtypeid . ']');
    }

}

?>