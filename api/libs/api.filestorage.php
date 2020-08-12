<?php

class FileStorage {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains array of available files in database as id=>imagedata
     *
     * @var array
     */
    protected $allFiles = array();

    /**
     * Contains current filestorage items scope
     *
     * @var string
     */
    protected $scope = '';

    /**
     * Contains current instance item ID in the current scope
     *
     * @var string
     */
    protected $itemId = '';

    /**
     * Contains current administrator login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Current instance database abstraction layer placeholder
     *
     * @var object
     */
    protected $storageDb = '';

    /**
     * Some predefined paths and URLs
     */
    const TABLE_STORAGE = 'filestorage';
    const STORAGE_PATH = 'content/documents/filestorage/';
    const URL_ME = '?module=filestorage';
    const EX_NOSCOPE = 'NO_OBJECT_SCOPE_SET';
    const EX_WRONG_EXT = 'WRONG_FILE_EXTENSION';

    /**
     * Initializes filestorage engine for some scope/item id
     * 
     * @param string $scope
     * @param string $itemid
     * 
     * @return void
     */
    public function __construct($scope = '', $itemid = '') {
        $this->loadAlter();
        $this->setScope($scope);
        $this->setItemid($itemid);
        $this->setLogin();
        $this->initDatabase();
    }

    /**
     * Loads system alter config into private prop
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Object scope setter
     * 
     * @param string $scope Object actual scope
     * 
     * @return void
     */
    protected function setScope($scope) {
        $this->scope = ubRouting::filters($scope, 'mres');
    }

    /**
     * Object scope item Id setter
     * 
     * @param string $scope Object actual id in current scope
     * 
     * @return void
     */
    protected function setItemid($itemid) {
        $this->itemId = ubRouting::filters($itemid, 'mres');
    }

    /**
     * Administrator login setter
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Inits protected database absctaction layer for current instance
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->storageDb = new NyanORM(self::TABLE_STORAGE);
    }

    /**
     * Loads images list from database into private prop
     * 
     * @return void
     */
    protected function loadAllFiles() {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $this->allFiles = $this->storageDb->getAll('id');
        }
    }

    /**
     * Registers uploaded file in database
     * 
     * @param string $filename
     * 
     * @return void
     */
    protected function registerFile($filename) {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $filename = ubRouting::filters($filename, 'mres');
            $date = curdatetime();

            $this->storageDb->data('scope', $this->scope);
            $this->storageDb->data('item', $this->itemId);
            $this->storageDb->data('date', $date);
            $this->storageDb->data('admin', $this->myLogin);
            $this->storageDb->data('filename', $filename);
            $this->storageDb->create();

            log_register('FILESTORAGE CREATE SCOPE `' . $this->scope . '` ITEM [' . $this->itemId . ']');
        }
    }

    /**
     * Deletes uploaded file from database
     * 
     * @param int $fileId
     * 
     * @return void
     */
    protected function unregisterImage($fileId) {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $fileId = ubRouting::filters($fileId, 'int');
            $date = curdatetime();

            $this->storageDb->where('id', '=', $fileId);
            $this->storageDb->delete();

            log_register('FILESTORAGE DELETE SCOPE `' . $this->scope . '` ITEM [' . $this->itemId . ']');
        }
    }

}
