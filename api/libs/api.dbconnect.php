<?php

error_reporting(E_ALL ^ E_DEPRECATED); //hide some deprecated warnings on 5.6 :(

/**
 * Yet another manual database connection class
 */
class DbConnect {

    var $host = '';
    var $dbport = 3306;
    var $user = '';
    var $password = '';
    var $database = '';
    var $persistent = false;
    var $conn = NULL;
    var $result = false;
    var $error_reporting = false;

    public function __construct($host, $user, $password, $database, $error_reporting = true, $persistent = false, $port = 3306) {
        $this->dbport = (empty($port)) ? 3306 : $port;

        //we can use custom port as :port in old mysql connection method
        if (extension_loaded('mysql')) {
            if (!ispos($host, ':')) {
                $this->host = $host . ':' . $this->dbport;
            } else {
                $this->host = $host;
            }
        } else {
            //mysqli use separate parameter port on connection
            $this->host = $host;
        }

        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->persistent = $persistent;
        $this->error_reporting = $error_reporting;
    }

    public function open() {
        if (extension_loaded('mysql')) {
            if ($this->persistent) {
                $func = 'mysql_pconnect';
            } else {
                $func = 'mysql_connect';
            }

            $this->conn = $func($this->host, $this->user, $this->password);
            if (!$this->conn) {

                return false;
            }

            if (@!mysql_select_db($this->database, $this->conn)) {
                return false;
            }
        } else {
            $this->conn = new mysqli($this->host, $this->user, $this->password, $this->database, $this->dbport);
            if ($this->conn->connect_error) {
                return false;
            }
        }

        return true;
    }

    public function close() {
        if (extension_loaded('mysql')) {
            return (@mysql_close($this->conn));
        } else {
            return (@$this->conn->close());
        }
    }

    public function error() {
        if ($this->error_reporting) {
            if (extension_loaded('mysql')) {
                return (mysql_error());
            } else {
                return ($this->conn->error);
            }
        }
    }

    public function query($sql) {
        if (extension_loaded('mysql')) {
            $this->result = @mysql_query($sql, $this->conn);
        } else {
            mysqli_report(0); //TODO: make here normal fix for PHP 8.2
            $this->result = @$this->conn->query($sql);
        }
        return($this->result != false);
    }

    public function affectedrows() {
        if (extension_loaded('mysql')) {
            return(@mysql_affected_rows($this->conn));
        } else {
            return(@$this->conn->affected_rows);
        }
    }

    public function numrows() {
        if (extension_loaded('mysql')) {
            return(@mysql_num_rows($this->result));
        } else {
            return(@$this->conn->num_rows);
        }
    }

    public function fetchobject() {
        if (extension_loaded('mysql')) {
            return(@mysql_fetch_object($this->result));
        } else {
            return($result = @$this->result->fetch_object());
        }
    }

    public function fetcharray() {
        if (extension_loaded('mysql')) {
            return(mysql_fetch_array($this->result));
        } else {
            return($result = @$this->result->fetch_array(MYSQLI_BOTH));
        }
    }

    public function fetchassoc() {
        if (extension_loaded('mysql')) {
            return(@mysql_fetch_assoc($this->result));
        } else {
            return($result = @$this->result->fetch_assoc());
        }
    }

    public function freeresult() {
        if (extension_loaded('mysql')) {
            return(@mysql_free_result($this->result));
        } else {
            return(@$this->result->free());
        }
    }

}

?>
