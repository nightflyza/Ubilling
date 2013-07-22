<?php if (!defined('ENVIRONMENT')) exit('Only STG can run script!');

class Ubilling {
    
    // Ubilling class:
    private $config;
    private $data;

    // Drivers:
    private $database;
    private $executer;
    private $log;

    // Constructor:
    public function __construct() {
        $start = microtime(TRUE);
        $this->configurate();
        $this->load_log();
        $this->log->message(__CLASS__, ENVIRONMENT . " started for user `" . LOGIN . "`:");
        $this->load_database();
        $this->load_executer();
        $this->put_data_to_nas();
        $this->log->message(__CLASS__, "Elapsed time: " . round(microtime(TRUE) - $start, 3) . " sec.\n");
    }

    private function configurate() {
        if ( file_exists(BASEPATH . "config.ini") ) {
            $file = parse_ini_file(BASEPATH . "config.ini", TRUE);
            if ( !empty($file) ) {
                $this->config = $file;
            }
        }
    }

    private function load_log() {
        if ( !is_object($this->log) ) {
            if ( !empty($this->config['log']['method']) ) {
                if ( file_exists(LOG_DRVS . $this->config['log']['method'] . EXT) ) {
                    include_once(LOG_DRVS . $this->config['log']['method'] . EXT);
                    $this->log = new Log(array(
                        'config' => $this->config['log']
                    ));
                    return TRUE;
                } else $this->log->message(__CLASS__, "Cannot load Log Class, `" . LOG_DRVS . $this->config['log']['method'] . EXT . "` is absent!", "error");
            } else $this->log->message(__CLASS__, "Cannot load Log Class, `method` was not specified in `config.ini`!", "error");
        } else $this->log->message(__CLASS__, "Log Class is alreay loaded!", "error");
    }

    private function load_database() {
        if ( !is_object($this->database) ) {
            if ( !empty($this->config['database']['dbdriver']) ) {
                if ( file_exists(DB_DRVS . $this->config['database']['dbdriver'] . EXT) ) {
                    include_once(DB_DRVS . $this->config['database']['dbdriver'] . EXT);
                    $this->database = new Database(array(
                        'config' => $this->config['database'],
                        'log' => $this->log
                    ));
                    return TRUE;
                } else $this->log->message(__CLASS__, "Cannot load Database Class, `" . DB_DRVS . $this->config['database']['dbdriver'] . EXT . "` is absent!", "error");
            } else $this->log->message(__CLASS__, "Cannot load Database Class, `dbdriver` was not specified in `config.ini`!", "error");
        } else $this->log->message(__CLASS__, "Database Class is alreay loaded!", "error");
    }

    private function load_executer() {
        if ( !is_object($this->executer) ) {
            $this->data['NAS'] = $this->database->get_nas_data();
            if ( !empty($this->data['NAS']['TYPE']) ) {
                switch ($this->data['NAS']['TYPE']) {
                    case "local":       // Local NAS,
                    case "rscriptd":    // RScriptD NAS:
                        if ( file_exists(NAS_DRVS . 'rscriptd' . EXT) ) {
                            include_once(NAS_DRVS . 'rscriptd' . EXT);
                            $this->executer = new Executer(array(
                                'log' => $this->log,
                                'config' => $this->config['rscriptd'],
                                'database' => $this->database
                            ));
                            return TRUE;
                        } else $this->log->message(__CLASS__, "Cannot load NAS Command Executer, `" . NAS_DRVS . $this->data['NAS']['TYPE'] . EXT . "` is absent!", "error");
                        break;
                    case "mikrotik":    // MikroTik NAS,
                    case "other":       // MikroTik NAS (DHCP & PPP):
                        if ( file_exists(NAS_DRVS . 'mikrotik' . EXT) ) {
                            include_once(NAS_DRVS . 'mikrotik' . EXT);
                            $this->executer = new Executer(array(
                                'log' => $this->log,
                                'config' => $this->config['mikrotik'],
                                'database' => $this->database,
                                'parameters' => $this->data['NAS']
                            ));
                            return TRUE;
                        } else $this->log->message(__CLASS__, "Cannot load NAS Command Executer, `" . NAS_DRVS . $this->data['NAS']['TYPE'] . EXT . "` is absent!", "error");
                        break;
                    default:            // Unsupported NAS:
                        $this->log->message(__CLASS__, "Cannot load NAS Command Executer, NAS TYPE is unsupported!", "error");
                        return FALSE;
                        break;
                }
            } else $this->log->message(__CLASS__, "Cannot load NAS Command Executer, queried empty NAS TYPE from database!", "error");
        } else $this->log->message(__CLASS__, "NAS Command Executer is already loaded!", "error");
    }

    private function put_data_to_nas() {
        if ( is_object($this->executer) ) {
            $script = constant('ENVIRONMENT');
            switch ($this->data['NAS']['TYPE']) {
                case "local":       // Local NAS,
                case "rscriptd":    // RScriptD NAS,
                case "mikrotik":    // MikroTik NAS,
                case "other":       // MikroTik NAS (DHCP and PPP):
                    $this->executer->$script();
                    return TRUE;
                    break;
            }
        }
    }
}
?>