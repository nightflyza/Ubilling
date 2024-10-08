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
     * Contains default primary key field name for current instance model
     *
     * @var string
     */
    protected $defaultPk = 'id';

    /**
     * Contains selectable fields list
     *
     * @var array
     */
    protected $selectable = array();

    /**
     * Contains key=>value data sets array for INSERT/UPDATE operations
     *
     * @var array
     */
    protected $data = array();

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
     * Contains ORDER BY expressions for some queries
     *
     * @var array
     */
    protected $order = array();

    /**
     * Contains GROUP BY expressions for some queries
     *
     * @var array
     */
    protected $groupby = array();

    /**
     * Contains JOIN expression.
     *
     * @var array
     */
    protected $join = array();

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
     * Yet another debug flag, for full model dumping
     *
     * @var bool
     */
    protected $deepDebug = false;

    /**
     * Default log path
     */
    const LOG_PATH = 'exports/nyanorm.log';

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
     * Setter of fields list which will be optionally used in getAll
     *
     * @param array/string $fieldSet $fieldSet fields names to be selectable from model in array or as comma separated string
     * @param bool $escapeFields determines if there's a need to escape fields with backticks or not
     *
     * @return void
     */
    public function selectable($fieldSet = '', $escapeFields = false) {
        if (!empty($fieldSet)) {
            if (is_array($fieldSet)) {
                $this->selectable = $fieldSet;
            } else {
                if (is_string($fieldSet)) {
                    $this->selectable = explode(',', $fieldSet);
                }
            }

            if ($escapeFields) {
                $tmpArr = array();

                foreach ($this->selectable as $eachField) {
                    $tmpArr[] = $this->escapeField(trim($eachField));
                }

                $this->selectable = empty($tmpArr) ? $this->selectable : $tmpArr;
            }
        } else {
            $this->flushSelectable();
        }
    }

    /**
     * Setter for join (with USING) list which used in getAll.
     *
     * @param string $joinExpression LEFT or RIGHT or whatever you need type of JOIN
     * @param string $tableName table name (for example switches)
     * @param string $using field to use for USING expression
     * @param bool $noTabNameEnclosure do not enclose table name with ``
     *
     * @throws MEOW_JOIN_WRONG_TYPE
     *
     * @return void
     */
    public function join($joinExpression = '', $tableName = '', $using = '', $noTabNameEnclosure = false) {
        if (!empty($joinExpression) and ! empty($tableName) and ! empty($using)) {
            $joinExpression = trim($joinExpression);
            switch ($joinExpression) {
                case 'INNER':
                    break;
                case 'LEFT':
                    break;
                case 'RIGHT':
                    break;
                default:
                    throw new Exception('MEOW_JOIN_WRONG_TYPE');
            }
            if (is_string($joinExpression) and is_string($tableName) and is_string($using)) {
                if ($noTabNameEnclosure) {
                    $this->join[] = $joinExpression . " JOIN " . $tableName . " USING (" . $using . ")";
                } else {
                    $this->join[] = $joinExpression . " JOIN `" . $tableName . "` USING (" . $using . ")";
                }
            }
        } else {
            $this->flushJoin();
        }
    }

    /**
     * Setter for join (with ON) list which used in getAll.
     *
     * @param string $joinExpression
     * @param string $tableName
     * @param string $on
     * @param bool $noTabNameEnclosure
     *
     * @throws MEOW_JOIN_WRONG_TYPE
     *
     * @return void
     */
    public function joinOn($joinExpression = '', $tableName = '', $on = '', $noTabNameEnclosure = false) {
        if (!empty($joinExpression) and ! empty($tableName) and ! empty($on)) {
            $joinExpression = trim($joinExpression);
            switch ($joinExpression) {
                case 'INNER':
                    break;
                case 'LEFT':
                    break;
                case 'RIGHT':
                    break;
                default:
                    throw new Exception('MEOW_JOIN_WRONG_TYPE');
            }
            if (is_string($joinExpression) and is_string($tableName) and is_string($on)) {
                if ($noTabNameEnclosure) {
                    $this->join[] = $joinExpression . " JOIN " . $tableName . " ON (" . $on . ")";
                } else {
                    $this->join[] = $joinExpression . " JOIN `" . $tableName . "` ON (" . $on . ")";
                }
            }
        } else {
            $this->flushJoin();
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
        if (!empty($field) and ! empty($expression)) {
            $value = ($value == 'NULL' or $value == 'null') ? $value : "'" . $value . "'";
            $this->where[] = $this->escapeField($field) . " " . $expression . " " . $value;
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
     * Flushes all available cumulative structures in safety reasons.
     *
     * @return void
     */
    protected function destroyAllStructs() {
        $this->flushData();
        $this->flushWhere();
        $this->flushGroupBy();
        $this->flushOrder();
        $this->flushLimit();
        $this->flushJoin();
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
        if (!empty($field) and ! empty($expression)) {
            $value = ($value == 'NULL' or $value == 'null') ? $value : "'" . $value . "'";
            $this->orWhere[] = $this->escapeField($field) . " " . $expression . " " . $value;
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
     * Can be either a one-field name string, a string of fields separated by coma or an array of field names
     *
     * @param string/array $fieldSet fields for ordering
     * @param string $order SQL order direction like ASC/DESC
     * @param bool $escapeFields determines if there's a need to escape fields with backticks or not
     * @param bool $orderWithinFields allows to put individual sort direction(ASC/DESC) for each field specified.
     *                                $fieldSet must be a RAW COMA-DELIMITED STRING value if using this parameter, as it's not processed in any way
     *                                Keep in mind that this option ignores $order and $escapeFields params and fields should be escaped manually, if needed
     *
     * @return void
     */
    public function orderBy($fieldSet = '', $order = '', $escapeFields = true, $orderWithinFields = false) {
        if (!empty($fieldSet)) {
            $tmpArr = array();
            $tmpStr = '';

            if ($orderWithinFields) {
                $tmpStr = $fieldSet;
            } else {
                if (!empty($order)) {
                    if (is_array($fieldSet)) {
                        $tmpArr = $fieldSet;
                    } else {
                        if (is_string($fieldSet)) {
                            $tmpArr = explode(',', $fieldSet);
                        }
                    }

                    foreach ($tmpArr as $eachField) {
                        if ($escapeFields) {
                            $tmpStr .= $this->escapeField(trim($eachField)) . ", ";
                        } else {
                            $tmpStr .= $eachField . ", ";
                        }
                    }

                    $tmpStr = trim(trim($tmpStr, ", "));
                    $tmpStr .= " " . $order;
                }
            }
            // can't use ternary here 'cause we need to avoid the array element creation
            if (!empty($tmpStr)) {
                $this->order[] = $tmpStr;
            }
        } else {
            $this->flushOrder();
        }
    }

    /**
     * Setter of GROUP BY clause fields list which will be optionally used in getAll
     *
     * @param array/string $fieldSet $fieldSet fields names to be selectable from model in array or as comma separated string
     * @param bool $escapeFields determines if there's a need to escape fields with backticks or not
     *
     * @return void
     */
    public function groupBy($fieldSet = '', $escapeFields = false) {
        if (!empty($fieldSet)) {
            if (is_array($fieldSet)) {
                $this->groupby = $fieldSet;
            } else {
                if (is_string($fieldSet)) {
                    $this->groupby = explode(',', $fieldSet);
                }
            }

            if ($escapeFields) {
                $tmpArr = array();

                foreach ($this->groupby as $eachField) {
                    $tmpArr[] = $this->escapeField(trim($eachField));
                }

                $this->groupby = empty($tmpArr) ? $this->groupby : $tmpArr;
            }
        } else {
            $this->flushGroupBy();
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
     * Flushes groupby cumullative array
     *
     * @return void
     */
    protected function flushGroupBy() {
        $this->groupby = array();
    }

    /**
     * Flushes selectable cumullative struct
     *
     * @return void
     */
    protected function flushSelectable() {
        $this->selectable = array();
    }

    /**
     * Flushed join cumullative struct
     *
     * @return void
     */
    protected function flushJoin() {
        $this->join = array();
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
            show_window(__('NyanORM Debug'), $data);
            $curDate = curdatetime();
            $logData = '';
            if ($this->deepDebug) {
                $logData = $curDate . ' Model name: "' . $this->tableName . '"' . "\n";
                $logData .= $curDate . ' Model state: ' . print_r($this, true) . "\n";
            }
            $logData .= $curDate . ' ' . $data . "\n";
            if ($this->deepDebug) {
                $logData .= str_repeat('=', 40) . "\n";
            }

            file_put_contents(self::LOG_PATH, $logData, FILE_APPEND);
        }
    }

    /**
     * Build join expression from protected join expressions array.
     *
     * @return string
     */
    protected function buildJoinString() {
        $result = '';
        if (!empty($this->join)) {
            if (is_array($this->join)) {
                $result .= implode(' ', $this->join);
            }
        }
        return ($result);
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
        return ($result);
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
                $result = implode(' , ', $this->order);
            }
        }
        // trying to avoid possible empty-string elements arrays(particularly - an array with one empty-string element)
        $result = empty($result) ? '' : " ORDER BY " . $result;
        return ($result);
    }

    /**
     * Retruns groupby expressions as string
     *
     * @return string
     */
    protected function buildGroupByString() {
        $result = '';
        if (!empty($this->groupby)) {
            if (is_array($this->groupby)) {
                $result .= implode(',', $this->groupby);
            }
        }
        // trying to avoid possible empty-string elements arrays(particularly - an array with one empty-string element)
        $result = empty($result) ? '' : " GROUP BY " . $result;
        return ($result);
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
        return ($result);
    }

    /**
     * Constucts field names which will be optionally used for data getting
     *
     * @return string
     */
    protected function buildSelectableString() {
        $result = '';
        if (!empty($this->selectable)) {
            $result .= implode(',', $this->selectable);
        } else {
            $result .= '*';
        }
        return ($result);
    }

    /**
     * Returns all records of current database object instance
     *
     * @param string $assocByField field name to automatically make it as index key in results array
     * @param bool $flushParams flush all query parameters like where, order, limit and other after execution?
     * @param bool $distinctON add "DISTINCT" keyword for a "SELECT" clause
     *
     * @return array
     */
    public function getAll($assocByField = '', $flushParams = true, $distinctON = false) {
        $joinString = $this->buildJoinString();
        $whereString = $this->buildWhereString();
        $groupbyString = $this->buildGroupByString();
        $orderString = $this->buildOrderString();
        $limitString = $this->buildLimitString();
        $selectableString = $this->buildSelectableString();
        $distinct = ($distinctON ? ' DISTINCT ' : '');
        //building some dummy query
        $query = "SELECT " . $distinct . $selectableString . " from `" . $this->tableName . "` "; //base query
        $query .= $joinString . $whereString . $groupbyString . $orderString . $limitString; //optional parameters
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
            $this->destroyAllStructs();
        }
        return ($result);
    }

    /**
     * Deletes record from database. Where must be not empty!
     *
     * @param bool  $flushParams flush all query parameters like where, order, limit and other after execution?
     *
     * @return void
     */
    public function delete($flushParams = true) {
        if (!empty($this->where) or ! empty($this->orWhere)) {
            $whereString = $this->buildWhereString();
            $limitString = $this->buildLimitString();
            if (!empty($whereString)) {
                //double check yeah!
                $query = "DELETE from `" . $this->tableName . "`"; //base deletion query
                $query .= $whereString . $limitString; //optional parameters
                $this->debugLog($query);
                nr_query($query);
            } else {
                throw new Exception('MEOW_WHERE_STRUCT_EMPTY');
            }
        } else {
            throw new Exception('MEOW_WHERE_STRUCT_EMPTY');
        }

        if ($flushParams) {
            //flush instance parameters for further queries
            $this->destroyAllStructs();
        }
    }

    /**
     * Puts some data into protected data property for furrrrther save()/create() operations.
     *
     * @param string $field record field name to push data
     * @param string $value field content to push
     *
     * @return void
     */
    public function data($field = '', $value = '') {
        if (!empty($field)) {
            $this->data[$field] = $value;
        } else {
            $this->flushData();
        }
    }

    /**
     * Same as data() method, but works not with separate $field and $value data but with array of $fields => $values
     * Useful if some "third-party" code returns an already prepared array of $fields => $values
     *
     * @param array $field_value array with $field => $value structure
     *
     * @return void
     */
    public function dataArr($field_value = array()) {
        $dataInsCnt = 0;

        if (!empty($field_value)) {
            foreach ($field_value as $field => $value) {
                if (!empty($field)) {
                    $this->data[$field] = $value;
                    $dataInsCnt++;
                }
            }
        }

        if ($dataInsCnt == 0) {
            $this->flushData();
        }
    }

    /**
     * Flushes current instance data set
     *
     * @return void
     */
    protected function flushData() {
        $this->data = array();
    }

    /**
     * Saves current model data fields changes to database.
     *
     * @param bool $flushParams flush all query parameters like where, order, limit and other after execution?
     * @param bool $fieldsBatch gather all the fields together in a single query from $this->data structure
     *             before actually running the query to reduce the amount of subsequential DB queries for every table field
     *
     * @return void
     */
    public function save($flushParams = true, $fieldsBatch = false) {
        if (!empty($this->data)) {
            if (!empty($this->where)) {
                $whereString = $this->buildWhereString();
                if (!empty($whereString)) {
                    //double check, yeah.
                    if ($fieldsBatch) {
                        $query = "UPDATE `" . $this->tableName . "` SET ";

                        foreach ($this->data as $field => $value) {
                            $query .= $this->escapeField($field) . "='" . $value . "', ";
                        }

                        $query = rtrim($query, ', ');
                        $query .= $whereString;
                        $this->debugLog($query);
                        nr_query($query);
                    } else {
                        foreach ($this->data as $field => $value) {
                            $query = "UPDATE `" . $this->tableName . "` SET " . $this->escapeField($field) . "='" . $value . "'" . $whereString;
                            $this->debugLog($query);
                            nr_query($query);
                        }
                    }
                } else {
                    throw new Exception('MEOW_WHERE_STRUCT_EMPTY');
                }
            } else {
                throw new Exception('MEOW_WHERE_STRUCT_EMPTY');
            }
        } else {
            throw new Exception('MEOW_DATA_STRUCT_EMPTY');
        }

        if ($flushParams) {
            //flush instance parameters for further queries
            $this->destroyAllStructs();
        }
    }

    /**
     * Creates new database record for current model instance.
     *
     * @param bool $autoAiId append default NULL autoincrementing primary key?
     * @param bool $flushParams flush all query parameters like where, order, limit and other after execution?
     *
     * @return void
     */
    public function create($autoAiId = true, $flushParams = true) {
        if (!empty($this->data)) {
            $dataStruct = '';
            $dataValues = '';
            if ($autoAiId) {
                $dataStruct .= '`' . $this->defaultPk . '`,';
                $dataValues .= 'NULL,';
            }
            foreach ($this->data as $field => $value) {
                $dataStruct .= $this->escapeField($field) . ',';
                $dataValues .= "'" . $value . "',";
            }
            $dataStruct = substr($dataStruct, 0, -1);
            $dataValues = substr($dataValues, 0, -1);

            $query = "INSERT INTO `" . $this->tableName . "` (" . $dataStruct . ') VALUES (' . $dataValues . ')';
            $this->debugLog($query);
            nr_query($query); //RUN THAT MEOW!!!!
        } else {
            throw new Exception('MEOW_DATA_STRUCT_EMPTY');
        }

        if ($flushParams) {
            //flush instance parameters for further queries
            $this->destroyAllStructs();
        }
    }

    /**
     * Returns last ID key in table
     *
     * @return int
     */
    public function getLastId() {
        $query = "SELECT " . $this->escapeField($this->defaultPk) . " from `" . $this->tableName . "` ORDER BY " . $this->escapeField($this->defaultPk) . " DESC LIMIT 1";
        $result = simple_query($query);
        return ($result[$this->defaultPk]);
    }

    /**
     * Returns fields count in datatabase instance
     *
     * @param string $fieldsToCount field name to count results
     * @param bool  $flushParams flush all query parameters like where, order, limit and other after execution?
     *
     * @return int
     */
    public function getFieldsCount($fieldsToCount = 'id', $flushParams = true) {
        $whereString = $this->buildWhereString();
        $raw = simple_query("SELECT COUNT(" . $this->escapeField($fieldsToCount) . ") AS `result` from `" . $this->tableName . "`" . $whereString);
        if ($flushParams) {
            //flush instance parameters for further queries
            $this->destroyAllStructs();
        }
        return ($raw['result']);
    }

    /**
     * Returns fields sum in datatabase instance
     *
     * @param string $fieldsToSum field name to retrive its sum
     * @param bool  $flushParams flush all query parameters like where, order, limit and other after execution?
     *
     * @return int
     */
    public function getFieldsSum($fieldsToSum, $flushParams = true) {
        if (!empty($fieldsToSum)) {
            $whereString = $this->buildWhereString();
            $raw = simple_query("SELECT SUM(" . $this->escapeField($fieldsToSum) . ") AS `result` from `" . $this->tableName . "`" . $whereString);
            if ($flushParams) {
                //flush instance parameters for further queries
                $this->destroyAllStructs();
            }
            return ($raw['result']);
        } else {
            throw new Exception('MEOW_NO_FIELD_NAME');
        }
    }

    /**
     * Enables or disables debug flag
     *
     * @param bool $state object instance debug state
     * @param bool $deep deep debugging mode with full model dumps
     *
     * @return void
     */
    public function setDebug($state, $deep = false) {
        $this->debug = $state;
        if ($deep) {
            $this->deepDebug = true;
        }
    }

    /**
     * Sets default primary key for model instance
     *
     * @param string $fieldName
     *
     * @return void
     */
    public function setDefaultPk($fieldName = 'id') {
        $this->defaultPk = $fieldName;
    }

    /**
     * Trying to correctly escape fields when using table_name.field_name.
     *
     * @param string $field
     *
     * @return string
     */
    protected function escapeField($field) {
        $field = str_ireplace('`', '', $field);

        if (strpos($field, '.') !== false) {
            $parts = explode(".", $field);
            $field = "`" . $parts[0] . "`.`" . $parts[1] . "`";
        } else {
            if (trim($field != "*")) {
                $field = "`" . $field . "`";
            }
        }
        return ($field);
    }

    /**
     * Returns true if record with such fields values already exists in current table
     *
     * @param array $dbFilterArr    represents structure, like:
     *        array($fieldname => array('operator' => $opStr,
     *                                  'fieldval' => $fldVal,
     *                                  'cmprlogic' => 'OR'
     *                                 ))
     *        where:
     *              $opStr - is one of the permitted in MYSQL WHERE clause, like:
     *              >, =, <, IS NOT, LIKE, IN, etc
     *              $fldVal - represents the field value to compare against
     *              'cmprlogic' - is optional and can contain 'OR' keyword to point
     *              the need to use NyanORM's orWhere(). No need to use it for 'AND' logical clause
     *              as it is used by DEFAULT and will be ignored
     * @param int $excludeRecID record ID to make exclusion on (e.g. for finding possible duplicates). For record editing purposes generally.
     * @param bool $flushSelectable
     * @param string $primaryKey name of the table's primary key field
     *
     * @return mixed|string returns the primary key value(ID usually) if rec found or empty string
     */
    public function checkRecExists($dbFilterArr, $excludeRecID = 0, $flushSelectable = true, $primaryKey = '') {
        $result = '';
        $primaryKey = (empty($primaryKey)) ? $this->defaultPk : $primaryKey;
        $this->selectable($primaryKey);

        foreach ($dbFilterArr as $dbFieldName => $fieldData) {
            if (!empty($fieldData['operator'])) {
                if (!empty($fieldData['cmprlogic']) and strtoupper($fieldData['cmprlogic']) == 'OR') {
                    $this->orWhere($dbFieldName, $fieldData['operator'], $fieldData['fieldval']);
                } else {
                    $this->where($dbFieldName, $fieldData['operator'], $fieldData['fieldval']);
                }
            }
        }

        if (!empty($excludeRecID)) {
            $this->where($primaryKey, '!=', $excludeRecID);
        }

        $result = $this->getAll();
        $result = empty($result) ? '' : $result[0][$primaryKey];

        if ($flushSelectable) {
            $this->flushSelectable();
        }

        return ($result);
    }

    /**
     * Simple getter for tableName property
     *
     * @param $trimQuotes
     *
     * @return string
     */
    public function getTableName($trimQuotes = false) {
        if ($trimQuotes) {
            return (trim($this->tableName, '"`\''));
        } else {
            return ($this->tableName);
        }
    }

    /**
     * Returns model's base table structure
     *
     * @param bool $fieldNamesOnly
     * @param bool $excludeIDField
     * @param bool $addLeadingTabName
     * @param bool $makeFieldAliases
     * @param string $fieldAliasSeparator
     *
     * @return array
     */
    public function getTableStructure(
        $fieldNamesOnly = false,
        $excludeIDField = false,
        $addLeadingTabName = false,
        $makeFieldAliases = false,
        $fieldAliasSeparator = ''
    ) {
        $result = array();
        $query = 'DESCRIBE ' . $this->tableName;
        $tableStructure = simple_queryall($query);

        if (!empty($tableStructure)) {
            if ($fieldNamesOnly) {
                foreach ($tableStructure as $io => $eachField) {
                    if ($excludeIDField and $eachField['Field'] == $this->defaultPk) {
                        continue;
                    }

                    // create field alias using combination of  $this->tableName + $fieldAliasSeparator + $eachField['Field']?
                    $fieldName = ($makeFieldAliases) ? $eachField['Field'] . ' AS ' . $this->tableName . $fieldAliasSeparator . $eachField['Field']
                        : $eachField['Field'];

                    // append leading table name with dot to the field name to explicitly distinguish fields in a query?
                    $result[] = ($addLeadingTabName) ? $this->tableName . '.' . $fieldName : $fieldName;
                }
            } else {
                $result = $tableStructure;
            }
        }

        return ($result);
    }
}
