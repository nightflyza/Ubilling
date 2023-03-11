<?php

/**
 * Sphinx database abstraction layer
 */
Class SphinxDB {

    /**
     * Placeholder for db link
     * 
     * @var object
     */
    protected $db = '';

    /**
     * Contains name of db driver is correct extension exists
     * 
     * @var string
     */
    protected $dbDriver = 'none';

    /**
     * Contains global configuration
     * 
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains list of indexes to search in
     * 
     * @var string
     */
    protected $searchIndexes = 'ip,mac,realname,login,fulladdress,mobile,phone';

    /**
     * Contains additional sorting parameters
     * 
     * @var string
     */
    protected $queryOptions = ' ';

    /**
     * Limit number of search results
     */
    CONST SEARCHLIMIT = 100;

    public function __construct() {
        $this->LoadAlter();
        $this->dbConnect();
    }

    /**
     * load alter.ini config     
     * 
     * @return void
     */
    protected function LoadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Connect to Sphinx DB if possible and needed extenstions are loaded
     * 
     * @return boolean or object
     */
    protected function dbConnect() {
        if (isset($this->altCfg['SPHINX_SEARCH_HOST'])) {
            $host = $this->altCfg['SPHINX_SEARCH_HOST'];

            if (isset($this->altCfg['SPHINX_SEARCH_SQL_PORT'])) {
                $port = $this->altCfg['SPHINX_SEARCH_SQL_PORT'];

                if (isset($this->altCfg['SPHINX_SEARCH_USER'])) {
                    $user = $this->altCfg['SPHINX_SEARCH_USER'];

                    if (isset($this->altCfg['SPHINX_SEARCH_PASSWORD'])) {
                        $password = $this->altCfg['SPHINX_SEARCH_PASSWORD'];

                        if (isset($this->altCfg['SPHINX_SEARCH_DB'])) {
                            $db = $this->altCfg['SPHINX_SEARCH_DB'];
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
        if (!extension_loaded('mysql')) {
            $this->db = new mysqli($host, $user, $password, $db, $port);
            if ($this->db->connect_error) {
                die('Connection error (' . $this->db->connect_errno . ') ' . $this->db->connect_error);
            }
            $this->dbDriver = 'mysqli';

            return true;
        } elseif (!extension_loaded('mysql')) {
            die('Unable to load module for database server "mysql": PHP mysql extension not available!');
        } else {
            $this->db = mysql_connect($host . ':' . $port, $user, $password);
            if (empty($this->db)) {
                die('Unable to connect to database server!');
            }
            $this->dbDriver = 'legacy';

            return true;
        }
    }

    /**
     * Query search in our fulltextsearch engine
     * 
     * @param string $searchString
     * @return json array
     */
    public function searchQuery($searchString) {
        $search = array();

        if (isset($this->altCfg['SPHINX_SEARCH_INDEXES'])) {
            $this->searchIndexes = $this->altCfg['SPHINX_SEARCH_INDEXES'];
        }

        if (isset($this->altCfg['SPHINX_SEARCH_SORT'])) {
            $this->queryOptions .= $this->altCfg['SPHINX_SEARCH_SORT'];
        }

        if (isset($this->altCfg['SPHINX_SEARCH_LIMIT'])) {
            $this->queryOptions .= ' LIMIT ' . $this->altCfg['SPHINX_SEARCH_LIMIT'];
        } else {
            $this->queryOptions .= ' LIMIT ' . self::SEARCHLIMIT;
        }

        if (!empty($searchString)) {
            if ($this->dbDriver == 'none') {
                return $search;
            }

            $query = "SELECT * FROM " . $this->searchIndexes . " WHERE MATCH ('" . $searchString . "') " . $this->queryOptions;

            if ($this->dbDriver == 'mysqli') {
                if ($result = $this->db->query($query, MYSQLI_USE_RESULT)) {
                    while ($row = $result->fetch_assoc()) {
                        $search[] = $row;
                    }
                }
            }

            if ($this->dbDriver == 'legacy') {
                $queried = mysql_query($query, $this->db);
                while ($row = mysql_fetch_assoc($queried)) {
                    $search[] = $row;
                }
            }


            $search = json_encode($search);
            return $search;
        }
    }

}

/**
 * Sphinx user-search implementation
 */
class SphinxSearch {

    /**
     * Placeholder for Sphinx DB connection
     * 
     * @var object
     */
    protected $db = '';

    public function __construct($searchString = '') {
        $this->db = new SphinxDB;
        if (!empty($searchString)) {
            $this->returnSearchResult($searchString);
        }
    }

    /**
     * Escape unwanted characters
     * 
     * @param string $string
     * @return string
     */
    protected function escapeString($string) {
        $from = array('\\', '(', ')', '|', '-', '!', '@', '~', '"', '&', '/', '^', '$', '=', '<');
        $to = array('\\\\', '\\\(', '\\\)', '\\\|', '\\\-', '\\\!', '\\\@', '\\\~', '\\\"', '\\\&', '\\\/', '\\\^', '\\\$', '\\\=', '\\\<');
        return str_replace($from, $to, $string);
    }

    /**
     * Send our string info search query and return json result.
     * 
     * @param string $searchString
     * @return json array
     */
    protected function returnSearchResult($searchString) {
        $escapedSearchString = $this->escapeString($searchString);
        die($this->db->searchQuery($escapedSearchString));
    }

}
