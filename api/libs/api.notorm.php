<?php

class NotOrm {

    public $tableName = '';

    public function __construct($name = '') {
        // $this->setTableName($name);
    }

    public function setTableName($name) {
        if (!empty($name)) {
            $this->tableName = $name;
        } else {
            $this->tableName = strtolower(get_class($this));
        }
    }

    public function getAll($options = '') {
        $options = (!empty($options)) ? ' ' . $options : '';
        return(simple_queryall("SELECT * from `" . $this->tableName . "`" . $options));
    }

}
