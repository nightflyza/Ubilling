<?php

/**
 * Universal PBX CDR abstraction class
 */
class PBXCdr {

    /**
     * Remote database MySQL host
     *
     * @var string
     */
    protected $host = '';

    /**
     * Remote database MySQL user login
     *
     * @var string
     */
    protected $login = '';

    /**
     * Remote database MySQL user password
     *
     * @var string
     */
    protected $password = '';

    /**
     * Remote database name
     *
     * @var string
     */
    protected $db = '';

    /**
     * Remote database CDR table name
     *
     * @var string
     */
    protected $table = '';

    /**
     * Remote database connection placeholder
     *
     * @var object
     */
    protected $database = '';

    /**
     * Current instance connection state
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * Creates new remote database CDR abstraction instance
     * 
     * @param string $host
     * @param string $login
     * @param string $password
     * @param string $db
     * @param string $table
     * 
     * @return void
     */
    public function __construct($host, $login, $password, $db, $table) {
        $this->host = $host;
        $this->login = $login;
        $this->password = $password;
        $this->db = $db;
        $this->table = $table;

        $this->connectDatabase();
    }

    /**
     * Connects to remote database
     * 
     * @return bool
     */
    protected function connectDatabase() {
        @$this->database = new mysqli($this->host, $this->login, $this->password, $this->db);
        if (!$this->database->connect_error) {
            $this->connected = true;
        } else {
            $this->connected = false;
        }
        return($this->connected);
    }

    /**
     * Another database query execution
     * 
     * @param string $query - query to execute
     * 
     * @return array/bool 
     */
    protected function query($query) {
        $result = array();
        if ($this->connected) {
            $result = array();
            $result_query = $this->database->query($query, MYSQLI_USE_RESULT);
            while ($row = $result_query->fetch_assoc()) {
                $result[] = $row;
            }
            mysqli_free_result($result_query);
        } else {
            $result = $this->connected;
        }
        return ($result);
    }

    /**
     * Returns Call Detail Record for current day or selected range of time
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * 
     * @return array/bool 
     */
    public function getCDR($dateFrom = '', $dateTo = '') {
        $result = array();
        $where = '';
        if ($dateFrom AND $dateTo) {
            $where .= " WHERE `calldate`  BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'";
        } else {
            $where .= " WHERE `calldate` LIKE '" . curdate() . "%'";
        }
        $query = "SELECT * from `" . $this->table . "` " . $where . " ORDER BY `calldate` ASC";
        $result = $this->query($query);
        return($result);
    }

}
