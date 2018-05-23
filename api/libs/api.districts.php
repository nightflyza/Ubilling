<?php

class Districts {

    /**
     * Contains available districts as id=>name
     *
     * @var array
     */
    protected $allDistricts = array();

    /**
     * Contains available cities as id=>data
     *
     * @var array
     */
    protected $allCities = array();

    /**
     * Contains available streets as id=>data
     *
     * @var array
     */
    protected $allStreets = array();

    /**
     * Contains available builds as id=>data
     *
     * @var array
     */
    protected $allBuilds = array();

    /**
     * Contains available apts as id=>data
     *
     * @var array
     */
    protected $allApts = array();

    /**
     * Creates new districts instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadDistricts();
        $this->loadCityData();
        $this->loadStreetData();
        $this->loadBuildData();
        $this->loadAptData();
    }

    /**
     * Loads existing districts from database
     * 
     * @return void
     */
    protected function loadDistricts() {
        $query = "SELECT * from `districtnames`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allDistricts[$each['id']] = $each['name'];
            }
        }
    }

    /**
     * Loads available cities from database
     * 
     * @return void
     */
    protected function loadCityData() {
        $tmpArr = zb_AddressGetCityAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allCities[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available streets from database
     * 
     * @return void
     */
    protected function loadStreetData() {
        $tmpArr = zb_AddressGetStreetAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allStreets[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available builds data from database
     * 
     * @return void
     */
    protected function loadBuildData() {
        $tmpArr = zb_AddressGetBuildAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allBuilds[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available apt data from database
     * 
     * @return void
     */
    protected function loadAptData() {
        $tmpArr = zb_AddressGetAptAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allApts[$each['id']] = $each;
            }
        }
    }

}
?>