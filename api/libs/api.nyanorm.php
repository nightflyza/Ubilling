<?php

/**
 * Basic Ubilling database abstraction prototype
 */
class NyanORM {
    /**
      ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
      ░░░░░░░░░░▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄░░░░░░░░░
      ░░░░░░░░▄▀░░░░░░░░░░░░▄░░░░░░░▀▄░░░░░░░
      ░░░░░░░░█░░▄░░░░▄░░░░░░░░░░░░░░█░░░░░░░
      ░░░░░░░░█░░░░░░░░░░░░▄█▄▄░░▄░░░█░▄▄▄░░░
      ░▄▄▄▄▄░░█░░░░░░▀░░░░▀█░░▀▄░░░░░█▀▀░██░░
      ░██▄▀██▄█░░░▄░░░░░░░██░░░░▀▀▀▀▀░░░░██░░
      ░░▀██▄▀██░░░░░░░░▀░██▀░░░░░░░░░░░░░▀██░
      ░░░░▀████░▀░░░░▄░░░██░░░▄█░░░░▄░▄█░░██░
      ░░░░░░░▀█░░░░▄░░░░░██░░░░▄░░░▄░░▄░░░██░
      ░░░░░░░▄█▄░░░░░░░░░░░▀▄░░▀▀▀▀▀▀▀▀░░▄▀░░
      ░░░░░░█▀▀█████████▀▀▀▀████████████▀░░░░
      ░░░░░░████▀░░███▀░░░░░░▀███░░▀██▀░░░░░░
      ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
     */

    /**
     * Contains table name for all instance operations
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * Default where expression
     *
     * @var string
     */
    protected $where = '';

    /**
     * Creates new model instance
     * 
     * @param string $name table name
     */
    public function __construct($name = '') {
        $this->setTableName($name);
    }

    /**
     * Table name setter
     * 
     * @param string $name table name to set
     * 
     * @return void
     */
    protected function setTableName($name) {
        if (!empty($name)) {
            $this->tableName = $name;
        } else {
            $this->tableName = strtolower(get_class($this));
        }
    }

    /**
     * Sets some protected where expression for further database queries
     * 
     * @param string $expression
     * 
     * @return void
     */
    public function where($expression = '') {
        $this->where = $expression;
    }

    /**
     * Returns all keys of current database object instance
     * 
     * 
     * @return array
     */
    public function getAll() {
        $where = (!empty($this->where)) ? ' WHERE ' . $this->where : '';
        return(simple_queryall("SELECT * from `" . $this->tableName . "`" . $where));
    }

    /**
     * Returns last ID key in table
     * 
     * @return int
     */
    public function getIdLast() {
        return(simple_get_lastid($this->tableName));
    }

    /**
     * Returns ids count in datatabase instance
     * 
     * @return int
     */
    public function getFieldsCount($fieldsToCount = 'id') {
        $raw = simple_query("SELECT COUNT(`" . $fieldsToCount . "`) from `" . $this->tableName . "`");
        return($raw['COUNT(`' . $fieldsToCount . '`)']);
    }

}
