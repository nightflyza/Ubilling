<?php

Class DbConnect {

    var $host = '';
    var $user = '';
    var $password = '';
    var $database = '';
    var $persistent = false;
    var $conn = NULL;
    var $result = false;
    var $error_reporting = false;

    public function __construct($host, $user, $password, $database, $error_reporting = true, $persistent = false) {

        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->persistent = $persistent;
        $this->error_reporting = $error_reporting;
    }

    function open() {
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
        return true;
    }

    function close() {
        return (@mysql_close($this->conn));
    }

    function error() {
        if ($this->error_reporting) {
            return (mysql_error());
        }
    }

    function query($sql) {
        $this->result = @mysql_query($sql, $this->conn);
        return($this->result != false);
    }

    function affectedrows() {
        return(@mysql_affected_rows($this->conn));
    }

    function numrows() {
        return(@mysql_num_rows($this->result));
    }

    function fetchobject() {
        return(@mysql_fetch_object($this->result, MYSQL_ASSOC));
    }

    function fetcharray() {
        return(mysql_fetch_array($this->result));
    }

    function fetchassoc() {
        return(@mysql_fetch_assoc($this->result));
    }

    function freeresult() {
        return(@mysql_free_result($this->result));
    }

}

?>