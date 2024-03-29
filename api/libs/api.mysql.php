<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////

/**
 * Debug mode on/off here
 */
define('SQL_DEBUG_LOG', 'exports/sqldebug.log');
$mysqlDatabaseConfig = @parse_ini_file('config/mysql.ini');
$mysqlDebugBuffer = array();
$query_counter = 0;
$ubillingDatabaseDriver = 'none';
define('SQL_DEBUG_QUERY_EOL', 'UBSQEOL');

if (@$mysqlDatabaseConfig['debug']) {
    switch ($mysqlDatabaseConfig['debug']) {
        case 1:
            define('SQL_DEBUG', 1);
            break;
        case 2:
            define('SQL_DEBUG', 2);
            break;
    }
} else {
    define('SQL_DEBUG', 0);
}

if (!extension_loaded('mysql')) {
    $ubillingDatabaseDriver = 'mysqli';
    /**
     * MySQLi database layer
     */
    if (!($db_config = @parse_ini_file('config/mysql.ini'))) {
        print('Cannot load mysql configuration');
        exit;
    }

    $dbport = (empty($db_config['port'])) ? 3306 : $db_config['port'];

    $loginDB = new mysqli($db_config['server'], $db_config['username'], $db_config['password'], $db_config['db'], $dbport);

    if ($loginDB->connect_error) {
        die('Connection error (' . $loginDB->connect_errno . ') '
                . $loginDB->connect_error);
    } else {
        $loginDB->query("set character_set_client='" . $db_config['character'] . "'");
        $loginDB->query("set character_set_results='" . $db_config['character'] . "'");
        $loginDB->query("set collation_connection='" . $db_config['character'] . "_general_ci'");
    }

    /**
     * Escapes special characters in a string for use in an SQL statement, taking into account the current charset of the connection
     * 
     * @global object $loginDB
     * @param  string $parametr data to filter
     * 
     * @return string
     */
    function loginDB_real_escape_string($parametr) {
        global $loginDB;
        $result = $loginDB->real_escape_string($parametr);
        return($result);
    }

    if (!function_exists('mysql_real_escape_string')) {

        /**
         * Escapes special characters in a string for use in an SQL statement, taking into account the current charset of the connection
         * 
         * @param string $data
         * 
         * @return string
         */
        function mysql_real_escape_string($data) {
            return(loginDB_real_escape_string($data));
        }

    }

    /**
     * Executing query and returns result as array
     * 
     * @global int $query_counter
     * @param string $query
     * 
     * @return array
     */
    function simple_queryall($query) {
        global $loginDB, $query_counter;
        if (SQL_DEBUG) {
            zb_SqlDebugOutput($query);
        }
        $result = array();
        $queried = $loginDB->query($query) or die('wrong data input: ' . $query);
        while ($row = mysqli_fetch_assoc($queried)) {
            $result[] = $row;
        }
        $query_counter++;
        return($result);
    }

    /**
     * Executing query and returns array of first result
     * 
     * @global int $query_counter
     * @param string $query
     * @return array
     */
    function simple_query($query) {
        global $loginDB, $query_counter;
        if (SQL_DEBUG) {
            zb_SqlDebugOutput($query);
        }
        $queried = $loginDB->query($query) or die('wrong data input: ' . $query);
        $result = mysqli_fetch_assoc($queried);
        $query_counter++;
        return($result);
    }

    /**
     * Updates single field in table with where expression
     * 
     * @param string $tablename
     * @param string $field
     * @param string $value
     * @param string $where
     * @param bool $NoQuotesAroundValue
     * 
     * @return void
     */
    function simple_update_field($tablename, $field, $value, $where = '', $NoQuotesAroundValue = false) {
        $tablename = loginDB_real_escape_string($tablename);
        $value = loginDB_real_escape_string($value);
        $field = loginDB_real_escape_string($field);

        if ($NoQuotesAroundValue) {
            $query = "UPDATE `" . $tablename . "` SET `" . $field . "` = " . $value . " " . $where . "";
        } else {
            $query = "UPDATE `" . $tablename . "` SET `" . $field . "` = '" . $value . "' " . $where . "";
        }

        nr_query($query);
    }

    /**
     * Returns last used `id` field available in some table
     * 
     * @param string $tablename
     * 
     * @return int
     */
    function simple_get_lastid($tablename) {
        $tablename = loginDB_real_escape_string($tablename);
        $query = "SELECT `id` from `" . $tablename . "` ORDER BY `id` DESC LIMIT 1";
        $result = simple_query($query);
        return($result['id']);
    }

    /**
     * Just executing single query 
     * 
     * @global int $query_counter
     * @param string $query
     * 
     * @return mixed
     */
    function nr_query($query) {
        global $loginDB, $query_counter;
        if (SQL_DEBUG) {
            zb_SqlDebugOutput($query);
        }
        $queried = $loginDB->query($query) or die('wrong data input: ' . $query);
        $query_counter++;
        return($queried);
    }

} else {
    $ubillingDatabaseDriver = 'mysql';

    /**
     * MySQL database old driver abstraction class. Used for PHP <7 legacy.
     */
    class MySQLDB {

        var $connection;
        var $last_query_num = 0;
        var $db_config = array();

        /**
         * last query result id
         *
         * @var MySQL result
         */
        var $lastresult;

        /**
         * last query assoc value
         *
         * @var bool
         */
        var $assoc = true;

        /**
         * Initialises connection with MySQL database server and selects needed db
         *
         * @param MySQL Connection Id $connection
         * @return MySQLDB
         */
        public function __construct($connection = false) {
            if ($connection)
                $this->connection = $connection;
            else {
                if (!($this->db_config = @parse_ini_file('config/' . 'mysql.ini'))) {
                    print(('Cannot load mysql configuration'));
                    return false;
                }

                if (!extension_loaded('mysql')) {
                    print(('Unable to load module for database server "mysql": PHP mysql extension not available!'));
                    return false;
                }

                $dbport = (empty($this->db_config['port'])) ? 3306 : $this->db_config['port'];

                $this->connection = @mysql_connect($this->db_config['server'] . ':' . $dbport, $this->db_config['username'], $this->db_config['password']);
            }

            if (empty($this->connection)) {
                print(('Unable to connect to database server!'));
                return false;
            } else if (!@mysql_select_db($this->db_config['db'], $this->connection)) {
                $this->db_error();
                return false;
            }

            mysql_query("set character_set_client='" . $this->db_config['character'] . "'");
            mysql_query("set character_set_results='" . $this->db_config['character'] . "'");
            mysql_query("set collation_connection='" . $this->db_config['character'] . "_general_ci'");

            return true;
        }

        /**
         * Executes query and returns result identifier
         *
         * @param string $query
         * @return MySQL result
         */
        public function query($query) {
            // use escape/vf function for input data.
            $result = @mysql_query($query, $this->connection) or $this->db_error(0, $query);
            $this->last_query_num++;
            return $result;
        }

        /**
         * Executes query and makes abstract data read available
         *
         * @param string $query
         * @param bool $assoc
         */
        public function ExecuteReader($query, $assoc = true) {
            $this->lastresult = $this->query($query);
            $this->assoc = $assoc;
        }

        /**
         * Link to query method
         *
         * @param string $query
         * @return MySQL result
         */
        public function ExecuteNonQuery($query) {
            $result = $this->query($query);
            return (mysql_affected_rows() == 0 ? false : $result);
        }

        /**
         * Returns array with from the current query result
         *
         * @return array
         */
        public function Read() {
            if ($this->assoc) {
                $result = @mysql_fetch_assoc($this->lastresult) or false;
            } else {
                $result = @mysql_fetch_row($this->lastresult) or false;
            }
            return $result;
        }

        /**
         * Returns one row from the current query result
         *
         * @param int $row
         * 
         * @return string
         */
        public function ReadSingleRow($row) {
            return mysql_result($this->lastresult, $row) or false;
        }

        /**
         * Prints MySQL error message; switching DEBUG, prints MySQL error description or sends it to administrator
         *
         * @return void
         */
        public function db_error($show = 0, $query = '') {
            global $system;
            if (!in_array(mysql_errno(), array(1062, 1065, 1191))) { // Errcodes in array are handled at another way :)
                if (SQL_DEBUG == 1 || $show == 1) {
                    $warning = '<br><b>' . ('MySQL Error') . ':</b><br><i>';
                    $warning .= mysql_errno() . ' : ' . mysql_error() . (empty($query) ? '</i>' : '<br>In query: <textarea cols="50" rows="7">' . $query . '</textarea></i>');
                    print($warning) or print($warning);
                } else {
                    print('An error occured. Please, try again later. Thank You !');
                    @$message .= mysql_errno() . ':' . mysql_error() . "\r\n";
                    $message .= (empty($query) ? '' : "In query: \r\n" . $query . "\r\n");
                    die('MySQL error ' . $message);
                }
            }
        }

        /**
         * Escapes string to use in SQL query
         *
         * @param string $string
         * 
         * @return string
         */
        public function escape($string) {
            if (!get_magic_quotes_gpc())
                return mysql_real_escape_string($string, $this->connection);
            else
                return mysql_real_escape_string(stripslashes($string), $this->connection);
        }

        /**
         * Disconnects from database server
         *
         * @return void
         */
        public function disconnect() {
            @mysql_close($this->connection);
        }

    }

    /**
     * Executing query and returns result as array
     * 
     * @global int $query_counter
     * @param string $query
     * 
     * @return array
     */
    function simple_queryall($query) {
        global $query_counter;
        if (SQL_DEBUG) {
            zb_SqlDebugOutput($query);
        }
        $result = '';
        $queried = mysql_query($query) or die('wrong data input: ' . $query);
        while ($row = mysql_fetch_assoc($queried)) {
            $result[] = $row;
        }
        $query_counter++;
        return($result);
    }

    /**
     * Executing query and returns array of first result
     * 
     * @global int $query_counter
     * @param string $query
     * 
     * @return array
     */
    function simple_query($query) {
        global $query_counter;
        if (SQL_DEBUG) {
            zb_SqlDebugOutput($query);
        }
        $queried = mysql_query($query) or die('wrong data input: ' . $query);
        $result = mysql_fetch_assoc($queried);
        $query_counter++;
        return($result);
    }

    /**
     * Updates single field in table with where expression
     * 
     * @param string $tablename
     * @param string $field
     * @param string $value
     * @param string $where
     * @param bool $NoQuotesAroundValue
     * 
     * @return void
     */
    function simple_update_field($tablename, $field, $value, $where = '', $NoQuotesAroundValue = false) {
        $tablename = mysql_real_escape_string($tablename);
        $value = mysql_real_escape_string($value);
        $field = mysql_real_escape_string($field);

        if ($NoQuotesAroundValue) {
            $query = "UPDATE `" . $tablename . "` SET `" . $field . "` = " . $value . " " . $where . "";
        } else {
            $query = "UPDATE `" . $tablename . "` SET `" . $field . "` = '" . $value . "' " . $where . "";
        }

        nr_query($query);
    }

    /**
     * Returns last used `id` field available in some table
     * 
     * @param string $tablename
     * 
     * @return int
     */
    function simple_get_lastid($tablename) {
        $tablename = mysql_real_escape_string($tablename);
        $query = "SELECT `id` from `" . $tablename . "` ORDER BY `id` DESC LIMIT 1";
        $result = simple_query($query);
        return ($result['id']);
    }

    /**
     * Just executing single query 
     * 
     * @global int $query_counter
     * @param string $query
     * 
     * @return mixed
     */
    function nr_query($query) {
        global $query_counter;
        if (SQL_DEBUG) {
            zb_SqlDebugOutput($query);
        }
        $queried = mysql_query($query) or die('wrong data input: ' . $query);
        $query_counter++;
        return($queried);
    }

    //creating mysql connection object instance
    $db = new MySQLDB();
}

/**
 * Returns cutted down data entry 
 *  Available modes:
 *  1 - digits, letters
 *  2 - only letters
 *  3 - only digits
 *  4 - digits, letters, "-", "_", "."
 *  5 - current lang alphabet + digits + punctuation
 *  default - filter only blacklist chars
 *
 * @param string $data
 * @param int $mode
 * 
 * @return string
 */
function vf($data, $mode = 0) {
    switch ($mode) {
        case 1:
            return preg_replace("#[^a-z0-9A-Z]#Uis", '', $data); // digits, letters
            break;
        case 2:
            return preg_replace("#[^a-zA-Z]#Uis", '', $data); // letters
            break;
        case 3:
            return preg_replace("#[^0-9]#Uis", '', $data); // digits
            break;
        case 4:
            return preg_replace("#[^a-z0-9A-Z\-_\.]#Uis", '', $data); // digits, letters, "-", "_", "."
            break;
        case 5:
            return preg_replace("#[^ [:punct:]" . ('a-zA-Z') . "0-9]#Uis", '', $data); // current lang alphabet + digits + punctuation
            break;
        default:
            return preg_replace("#[~@\+\?\%\/\;=\*\>\<\"\'\-]#Uis", '', $data); // black list anyway
            break;
    }
}

/**
 * Performs MySQL API debug output if enabled
 * 
 * @param string $data
 * 
 * @return void
 */
function zb_SqlDebugOutput($data) {
    global $mysqlDebugBuffer;
    if (SQL_DEBUG) {
        switch (SQL_DEBUG) {
            case 1:
                $timestamp = curdatetime();
                $cleanData = trim($data);
                $logData = $timestamp . ' ' . $cleanData;
                $mysqlDebugBuffer[] = $logData;
                file_put_contents(SQL_DEBUG_LOG, $logData . SQL_DEBUG_QUERY_EOL . PHP_EOL, FILE_APPEND);
                break;
            case 2:
                print($data . PHP_EOL);
                break;
        }
    }
}
