<?php

class Districts {

    /**
     * Contains available districts as id=>name
     *
     * @var array
     */
    protected $allDistricts = array();

    /**
     * Creates new districts instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadDistricts();
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

}
?>