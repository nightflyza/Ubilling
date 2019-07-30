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
     * Cumulative where expressions array. This will used as AND glue.
     *
     * @var array
     */
    protected $where = array();

    /**
     * Cumulative where expressions array. This will used as OR glue.
     *
     * @var array
     */
    protected $orWhere = array();

    /**
     * Contains ORDER by expressions for some queries
     *
     * @var array
     */
    protected $order = array();

    /**
     * Contains default query results limit
     *
     * @var int
     */
    protected $limit = 0;

    /**
     * Contains default query limit offset
     * 
     * @var int
     */
    protected $offset = 0;

    /**
     * Object wide debug flag
     *
     * @var bool
     */
    protected $debug = false;

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
     * @param string $field field name to apply expression
     * @param string $expression SQL expression. For example > = <, IS NOT, LIKE etc...
     * @param string $value expression parameter
     * 
     * @return void
     */
    public function where($field = '', $expression = '', $value = '') {
        if (!empty($field) AND ! empty($expression)) {
            $value = ($value == 'NULL' OR $value == 'null') ? $value : "'" . $value . "'";
            $this->where[] = "`" . $field . "` " . $expression . " " . $value;
        } else {
            $this->flushWhere();
        }
    }

    /**
     * Appends some raw where expression into cumullative where array. Or cleanup all if empty. Yeah.
     * 
     * @param string $expression raw SQL expression
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
     * Appends some OR where expression to protected prop for further database queries. Cleans it if all params empty.
     * 
     * @param string $field field name to apply expression
     * @param string $expression SQL expression. For example > = <, IS NOT, LIKE etc...
     * @param string $value expression parameter
     * 
     * @return void
     */
    public function orWhere($field = '', $expression = '', $value = '') {
        if (!empty($field) AND ! empty($expression)) {
            $value = ($value == 'NULL' OR $value == 'null') ? $value : "'" . $value . "'";
            $this->orWhere[] = "`" . $field . "` " . $expression . " " . $value;
        } else {
            $this->flushWhere();
        }
    }

    /**
     * Appends some raw OR where expression into cumullative where array. Or cleanup all if empty.
     * 
     * @param string $expression raw SQL expression
     * 
     * @return void
     */
    public function orWhereRaw($expression = '') {
        if (!empty($expression)) {
            $this->orWhere[] = $expression;
        } else {
            $this->flushWhere();
        }
    }

    /**
     * Flushes both where cumullative arrays
     * 
     * @return void
     */
    protected function flushWhere() {
        $this->where = array();
        $this->orWhere = array();
    }

    /**
     * Appends some order by expression to protected prop
     * 
     * @param string $field field for ordering
     * @param string $order SQL order direction like ASC/DESC
     * 
     * @return void
     */
    public function orderBy($field = '', $order = '') {
        if (!empty($field) AND ! empty($order)) {
            $this->order[] = "`" . $field . "` " . $order;
        } else {
            $this->flushOrder();
        }
    }

    /**
     * Flushes order cumullative array
     * 
     * @return void
     */
    protected function flushOrder() {
        $this->order = array();
    }

    /**
     * Process some debugging data if required
     * 
     * @param string $data now it just string that will be displayed in debug output
     * 
     * @return void
     */
    protected function debugLog($data) {
        if ($this->debug) {
            show_window(__('NyaORM Debug'), $data);
        }
    }

    /**
     * Builds where string expression from protected where expressions array
     * 
     * @return string
     */
    protected function buildWhereString() {
        $result = '';
        if (!empty($this->where)) {
            if (is_array($this->where)) {
                $result .= " WHERE ";
                $result .= implode(' AND ', $this->where);
            }
        }

        if (!empty($this->orWhere)) {
            if (is_array($this->orWhere)) {
                if (empty($result)) {
                    //maybe only OR statements here
                    $result .= " WHERE ";
                } else {
                    $result .= " OR ";
                }
                $result .= implode(' OR ', $this->orWhere);
            }
        }
        return($result);
    }

    /**
     * Retruns order expressions as string
     * 
     * @return string
     */
    protected function buildOrderString() {
        $result = '';
        if (!empty($this->order)) {
            if (is_array($this->order)) {
                $result .= " ORDER BY ";
                $result .= implode(' , ', $this->order);
            }
        }
        return($result);
    }

    /**
     * Sets query limits with optional offset
     * 
     * @param int $limit results limit count 
     * @param int $offset results limit offset
     * 
     * @return void
     */
    public function limit($limit = '', $offset = '') {
        if (!empty($limit)) {
            $this->limit = $limit;
            if (!empty($offset)) {
                $this->offset = $offset;
            }
        } else {
            $this->flushLimit();
        }
    }

    /**
     * Flushes limits values for further queries. No limits anymore! Meow!
     * 
     * @return void
     */
    protected function flushLimit() {
        $this->limit = 0;
        $this->offset = 0;
    }

    /**
     * Builds SQL formatted limits string
     * 
     * @return string
     */
    protected function buildLimitString() {
        $result = '';
        if (!empty($this->limit)) {
            $result .= ' LIMIT ';
            if (!empty($this->offset)) {
                $result .= ' ' . $this->offset . ',' . $this->limit;
            } else {
                $result .= ' ' . $this->limit;
            }
        }
        return($result);
    }

    /**
     * Returns all records of current database object instance
     * 
     * @param string $assocByField field name to automatically make it as index key in results array
     * @param bool $flushParams flush all query parameters like where, order, limit and other after execution?
     * 
     * @return array
     */
    public function getAll($assocByField = '', $flushParams = true) {
        $whereString = $this->buildWhereString();
        $orderString = $this->buildOrderString();
        $limitString = $this->buildLimitString();
        //building some dummy query
        $query = "SELECT * from `" . $this->tableName . "` "; //base query
        $query .= $whereString . $orderString . $limitString; //optional parameters
        $this->debugLog($query);
        $result = simple_queryall($query);

        //automatic data preprocessing
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
            $resultTmp = array(); //cleanup?
        }

        if ($flushParams) {
            //flush instance parameters for further queries
            $this->flushWhere();
            $this->flushOrder();
            $this->flushLimit();
        }
        return($result);
    }

    /**
     * Deletes record from database. Where must be not empty!
     * 
     * @param bool  $flushParams flush all query parameters like where, order, limit and other after execution?
     * 
     * @return void
     */
    public function delete($flushParams = true) {
        if (!empty($this->where) OR ! empty($this->orWhere)) {
            $whereString = $this->buildWhereString();
            $limitString = $this->buildLimitString();
            if (!empty($whereString)) {
                //double check yeah!
                $query = "DELETE from `" . $this->tableName . "`"; //base deletion query
                $query .= $whereString . $limitString; //optional parameters
                $this->debugLog($query);
                nr_query($query);
            } else {
                //mb some exception here
            }
        }
        if ($flushParams) {
            //flush instance parameters for further queries
            $this->flushWhere();
            $this->flushOrder();
            $this->flushLimit();
        }
    }

    /**
     * Returns last ID key in table
     * 
     * @return int
     */
    public function getLastId() {
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

    /**
     * Enables or disables debug flag
     * 
     * @param bool $state object instance debug state
     * 
     * @return void
     */
    public function setDebug($state) {
        $this->debug = $state;
    }

}
