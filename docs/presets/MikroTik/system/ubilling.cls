<?php if ( !defined('ENVIRONMENT') ) exit('Only STG can run script!');

    class Ubilling {

        // Ubilling:
        private $config;
        
        // Drivers:
        private $database;
        private $executer;
        private $log;

        // Constructor:
        public function __construct() {
            $start = microtime(TRUE);
            $this->configurate();
            $this->log();
            $this->log->message(__CLASS__, ENVIRONMENT . " started for user `" . LOGIN . "`:");
            $this->database();
            // Check the user's IP presence (for OnUserDel is needed):
            if ( !defined('IP') ) define('IP', $this->database->get_user_ip());
            $this->executer();
            $this->log->message(__CLASS__, "QC: " . $this->database->qc . ", elapsed time: " . round(microtime(TRUE) - $start, 3) . " sec.\n");
            $this->log->write();
        }

        private function configurate() {
            if ( file_exists(BASEPATH . 'config.ini') ) {
                $file = parse_ini_file(BASEPATH . 'config.ini', TRUE);
                if ( !empty($file) ) {
                    $this->config = $file;
                }
            }
        }

        private function log() {
            if ( !empty($this->config['log']['method']) ) {
                if ( file_exists(LOGPATH . $this->config['log']['method'] . EXT) ) {
                    include_once(LOGPATH . $this->config['log']['method'] . EXT);
                    $this->log = new Log(array(
                        'config' => $this->config['log']
                    ));
                } else $this->log->message(__CLASS__, "Can't load log driver, `" . LOGPATH . $this->config['log']['method'] . EXT . "` is absent!", 'error');
            } else $this->log->message(__CLASS__, "Can't load log driver, `method` was not specified in `config.ini`!", 'error');
        }

        private function database() {
            if ( !empty($this->config['database']['dbdriver']) ) {
                if ( file_exists(DBPATH . $this->config['database']['dbdriver'] . EXT) ) {
                    include_once(DBPATH . $this->config['database']['dbdriver'] . EXT);
                    $this->database = new Database(array(
                        'config' => $this->config['database'],
                        'log' => $this->log
                    ));
                } else $this->log->message(__CLASS__, "Can't load database driver, `" . DBPATH . $this->config['database']['dbdriver'] . EXT . "` is absent!", 'error');
            } else $this->log->message(__CLASS__, "Can't load database driver, `dbdriver` was not specified in `config.ini`!", 'error');
        }

        private function executer() {
            $data = $this->database->get_nas_data();
            if ( !empty($data['type']) ) {
                switch ($data['type']) {
                    case 'local':       // Local NAS,
                    case 'rscriptd':    // RScriptD NAS:
                        if ( file_exists(NASPATH . 'rscriptd' . EXT) ) {
                            include_once(NASPATH . 'rscriptd' . EXT);
                            $this->executer = new Executer(array(
                                'log' => $this->log,
                                'config' => $this->config['rscriptd'],
                                'database' => $this->database
                            ));
                        } else $this->log->message(__CLASS__, "Can't run required executer, `" . NAS . $data['type'] . EXT . "` is absent!", 'error');
                        break;
                    case 'mikrotik':    // MikroTik NAS:
                        if ( file_exists(NASPATH . 'mikrotik' . EXT) ) {
                            include_once(NASPATH . 'mikrotik' . EXT);
                            $this->executer = new Executer(array(
                                'log' => $this->log,
                                'config' => $this->config['mikrotik'],
                                'database' => $this->database,
                                'parameters' => $data
                            ));
                        } else $this->log->message(__CLASS__, "Can't run required executer, `" . NASPATH . $data['type'] . EXT . "` is absent!", 'error');
                        break;
                    default:            // Unsupported NAS:
                        $this->log->message(__CLASS__, "Can't run required executer, NAS'es type (" . $data['type'] . ") is unsupported!", 'error');
                        break;
                }
            } else $this->log->message(__CLASS__, "Can't run executer, NAS'es type is needed!", 'error');
        }
    }
?>