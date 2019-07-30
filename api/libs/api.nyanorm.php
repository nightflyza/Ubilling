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
     * Cumulative where expressions array
     *
     * @var array
     */
    protected $where = array();

    /**
     * Object wide debug flag
     *
     * @var bool
     */
    protected $debug = true;

    /**
     * Creates new model instance
     * 
     * @param string $name table name
     */
    public function __construct($name = '') {
        $this->setTableName($name);
    }

    /**
     * Table name automatic setter
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
     * Appends some where expression to protected prop for further database queries. Cleans it if all params empty.
     * 
     * @param string $field
     * @param string $expression
     * @param string $value
     * 
     * @return void
     */
    public function where($field = '', $expression = '', $value = '') {
        if (!empty($field) AND ! empty($expression) AND ! empty($value)) {
            $value = ($value == 'NULL' OR $value == 'null') ? $value : "'" . $value . "'";
            $this->where[] = "`" . $field . "` " . $expression . " " . $value;
        } else {
            $this->where = array();
        }
    }

    /**
     * Appends some raw where expression into cumullative where array. Or cleanup all if empty. Yeah.
     * 
     * @param string $expression
     * 
     * @return void
     */
    public function whereRaw($expression = '') {
        if (!empty($expression)) {
            $this->where[] = $expression;
        } else {
            $this->where = array();
        }
    }

    /**
     * Process some debugging data if required
     * 
     * @param string $data
     * 
     * @return void
     */
    protected function debugLog($data) {
        if ($this->debug) {
            show_window(__('NyaORM Debug'), $data);
        }
    }

    /**
     * Returns all records of current database object instance
     * 
     * @param string $assocByField
     * 
     * @return array
     */
    public function getAll($assocByField = '') {
        $whereString = '';
        if (!empty($this->where)) {
            if (is_array($this->where)) {
                $whereString .= " WHERE ";
                $whereString .= implode(' AND ', $this->where);
            }
        }
        $query = "SELECT * from `" . $this->tableName . "` " . $whereString;
        $this->debugLog($query);

        $result = simple_queryall($query);
        if (!empty($assocByField)) {
            $resultTmp = array();
            if (!empty($result)) {
                foreach ($result as $io => $each) {
                    if (isset($each[$assocByField])) {
                        $resultTmp[$each[$assocByField]] = $each;
                    }
                }
            }
            $result = $resultTmp;
        }
        return($result);
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
