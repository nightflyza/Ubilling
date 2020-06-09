<?php

class PONBoxes {

    /**
     * Contains all available PON boxes as id=>boxdata
     *
     * @var array
     */
    protected $allBoxes = array();

    /**
     * Contains all available user/address/onu links to boxes as linkid=>boxid
     *
     * @var array
     */
    protected $allLinks = array();

    /**
     * Database abstraction layer with ponboxes
     *
     * @var object
     */
    protected $boxes = '';

    /**
     * Database abstraction layer with ponboxes links to users/addresses/ONUs etc
     *
     * @var object
     */
    protected $links = '';

    /**
     * Routes, static defines etc
     */
    const URL_ME = '?module=ponboxes';
    const TABLE_BOXES = 'ponboxes';
    const TABLE_LINKS = 'ponboxeslinks';

    /**
     * Creates new PONBoxes instance
     * 
     * @return void
     */
    public function __construct() {
        $this->initDatabase();
    }

    /**
     * Inits all required database abstraction layers for further usage
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->boxes = new NyanORM(self::TABLE_BOXES);
        $this->links = new NyanORM(self::TABLE_LINKS);
    }

}
