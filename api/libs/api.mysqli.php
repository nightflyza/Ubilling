<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                                                                            //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////

/**
 * Debug on/off
 *
 */
define("DEBUG",0);
$query_counter=0;

/**
 * MySQLi database working
 *
 */
if (!($db_config = @parse_ini_file('config/' . 'mysql.ini'))) {
    print('Cannot load mysql configuration');
    exit;
}

$loginDB = new mysqli($db_config['server'], $db_config['username'], $db_config['password'], $db_config['db']);
if ($loginDB->connect_error) {
    die('Connection error (' . $loginDB->connect_errno . ') '
            . $loginDB->connect_error);
} else {
    $loginDB->query("set character_set_client='" . $db_config['character'] . "'");
    $loginDB->query("set character_set_results='" . $db_config['character'] . "'");
    $loginDB->query("set collation_connection='" . $db_config['character'] . "_general_ci'");
}

function loginDB_real_escape_string($parametr) {
    global $loginDB;
    $result = $loginDB->real_escape_string($parametr);
    return($result);
}

/**
 * Returns true when we call MySQLDB from api.mysql.php
 *
 */
class MySQLDB {
    public function __construct () {
        return true;
    }
}

if (!function_exists('mysql_real_escape_string')) {
    function mysql_real_escape_string($data) {
        return(loginDB_real_escape_string($data));
    }
}

/**
 * Returns cutted down entry data
 *
 * @param string $data
 * @param int $mode
 * @return string
 */
function vf($data,$mode=0)
{
    switch ($mode)
    {
        case 1:
            return preg_replace("#[^a-z0-9A-Z]#Uis",'',$data); // digits, letters
            break;
        case 2:
            return preg_replace("#[^a-zA-Z]#Uis",'',$data); // letters
            break;
        case 3:
            return preg_replace("#[^0-9]#Uis",'',$data); // digits
            break;
        case 4:
            return preg_replace("#[^a-z0-9A-Z\-_\.]#Uis",'',$data); // digits, letters, "-", "_", "."
            break;
        case 5:
            return preg_replace("#[^ [:punct:]".('a-zA-Z')."0-9]#Uis",'',$data); // current lang alphabet + digits + punctuation
            break;
        default:
            return preg_replace("#[~@\+\?\%\/\;=\*\>\<\"\'\-]#Uis",'',$data); // black list anyway
            break;
    }
}

// function that executing query and returns array
function simple_queryall($query) {
    global $loginDB, $query_counter;
    if (DEBUG) {
        print ($query . "\n");
    }
    $result = '';
    $queried = $loginDB->query($query) or die('wrong data input: ' . $query);
    while ($row = mysqli_fetch_assoc($queried)) {
        $result[] =  $row;
    }
    $query_counter++;
    return($result);
}

// function that executing query and returns array of first result
function simple_query($query) {
    global $loginDB, $query_counter;
    if (DEBUG) {
        print ($query . "\n");
    }
    $queried = $loginDB->query($query) or die('wrong data input: ' . $query);
    $result= mysqli_fetch_assoc($queried);
    $query_counter++;
    return($result);
}

//function update single field in table
function simple_update_field($tablename,$field,$value,$where='') {
    $tablename = loginDB_real_escape_string($tablename);
    $value = loginDB_real_escape_string($value);
    $field = loginDB_real_escape_string($field);
    $query = "UPDATE `" . $tablename . "` SET `" . $field . "` = '" . $value . "' " . $where . "";
    nr_query($query);
}

//function that gets last id from table
function simple_get_lastid($tablename) {
    $tablename = loginDB_real_escape_string($tablename);
    $query = "SELECT `id` from `" . $tablename . "` ORDER BY `id` DESC LIMIT 1";
    $result = simple_query($query);
    return($result['id']);
}

// function that just executing query
function nr_query($query) {
    global $loginDB, $query_counter;
    if (DEBUG) {
        print ($query . "\n");
    }
    $queried = $loginDB->query($query) or die('wrong data input: '.$query);
    $query_counter++;
    return($queried);
}
?>
