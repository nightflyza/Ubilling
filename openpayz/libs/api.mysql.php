<?php

////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) Hakkah ~ CMS Development Team                              //
//   http://hakkahcms.org                                                     //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////

/**
 * Debug on/off
 */
define("DEBUG", 0);
$query_counter = 0;

/**
 * MySQL database working class
 *
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
    function MySQLDB($connection = false) {
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

            $this->connection = @mysql_connect($this->db_config['server'], $this->db_config['username'], $this->db_config['password']);
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
    function query($query) {
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
    function ExecuteReader($query, $assoc = true) {
        $this->lastresult = $this->query($query);
        $this->assoc = $assoc;
    }

    /**
     * Link to query method
     *
     * @param string $query
     * @return MySQL result
     */
    function ExecuteNonQuery($query) {
        $result = $this->query($query);
        return (mysql_affected_rows() == 0 ? false : $result);
    }

    /**
     * Returns array with from the current query result
     *
     * @return array
     */
    function Read() {
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
     * @return string
     */
    function ReadSingleRow($row) {
        return mysql_result($this->lastresult, $row) or false;
    }

    /**
     * Prints MySQL error message; swithing DEBUG, prints MySQL error description or sends it to administrator
     *
     */
    function db_error($show = 0, $query = '') {
        global $system;
        if (!in_array(mysql_errno(), array(1062, 1065, 1191))) { // Errcodes in array are handled at another way :)
            if (DEBUG == 1 || $show == 1) {
                $warning = '<br><b>' . ('MySQL Error') . ':</b><br><i>';
                $warning.=mysql_errno() . ' : ' . mysql_error() . (empty($query) ? '</i>' : '<br>In query: <textarea cols="50" rows="7">' . $query . '</textarea></i>');
                print($warning) or print($warning);
            } else {
                print('An error occured. Please, try again later. Thank You !');
                @$message.=mysql_errno() . ':' . mysql_error() . "\r\n";
                $message.=(empty($query) ? '' : "In query: \r\n" . $query . "\r\n");
                die('MySQL error '.$message);
            }
        }
    }

    /**
     * Escapes string to use in SQL query
     *
     * @param string $string
     * @return string
     */
    function escape($string) {
        if (!get_magic_quotes_gpc())
            return mysql_real_escape_string($string, $this->connection);
        else
            return mysql_real_escape_string(stripslashes($string), $this->connection);
    }

    /**
     * Disconnects from database server
     *
     */
    function disconnect() {
        @mysql_close($this->connection);
    }

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
 * Executing query and returns result as array
 * 
 * @global int $query_counter
 * @param string $query
 * @return array
 */
function simple_queryall($query) {
    global $query_counter;
    if (DEBUG) {
        print ($query . "\n");
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
 * @return array
 */
function simple_query($query) {
    global $query_counter;
    if (DEBUG) {
        print ($query . "\n");
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
 */
function simple_update_field($tablename, $field, $value, $where = '') {
    $tablename = mysql_real_escape_string($tablename);
    $value = mysql_real_escape_string($value);
    $field = mysql_real_escape_string($field);
    $query = "UPDATE `" . $tablename . "` SET `" . $field . "` = '" . $value . "' " . $where . "";
    nr_query($query);
}

/**
 * Returns last used `id` field available in some table
 * 
 * @param string $tablename
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
 * @return mixed
 */
function nr_query($query) {
    global $query_counter;
    if (DEBUG) {
        print ($query . "\n");
    }
    $queried = mysql_query($query) or die('wrong data input: ' . $query);
    $query_counter++;
    return($queried);
}

?>
