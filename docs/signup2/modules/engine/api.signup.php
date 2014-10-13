<?php

class SignupService {
    //settings
    protected $OptionCitySelectable=true;
    protected $OptionStreetSelectable=true;
    protected $OptionStreetAutocomplete=false;
    protected $OptionBuildSelectable=true;
    
    //other properties
    protected $cities=array();


    public function __construct() {
        
    }
    
    /*
     * loads cities from database into private data property
     * 
     * @return void
     */
    protected function loadCities() {
        $query="SELECT * from city";
        $all=simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io=>$each) {
                $this->cities[$each['id']]=$each['cityname'];
            }
        }
    }
    
    
    /*
     * returns city input depends selectable option
     * 
     * @return string
     */
    protected function cityInput() {
        if ($this->OptionCitySelectable) {
            $this->loadCities();
            if (!empty($this->cities)) {
               $cityNames=array();
               foreach ($this->cities as $io=>$each) {
                   $cityNames[$each]=$each;
               }
               $result=  la_Selector('city', $cityNames, __('City'), '', false);
            }
        } else {
                $result=  la_TextInput('city', __('City'), '', false,15);
        }
        return ($result);
    }
    
    /*
     * returns street input depends options
     * 
     * @return string
     */
    protected function streetInput() {
        
    }
    
    /*
     * returns signup service main form
     * 
     * @retun string
     */
    public function renderForm() {
        $inputs='';
        $inputs.=$this->cityInput();
        $result= la_Form("", 'POST', $inputs, '');
        return ($result);
    }
    
    
}


?>
