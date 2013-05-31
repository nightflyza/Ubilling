<?php if ( ! defined('ENVIRONMENT') ) exit('Only STG can run script!');

	class Ubilling {

		// LOADED CLASSES:
		private $database	=	NULL;
		private $executer	=	NULL;
		private $log		=	NULL;

		// LOADED CONFIGURATIONS:
		private $config		=	array(
			'database'	=>	NULL,
			'log'		=>	NULL
		);

		// QUERIED DATABASE DATA:
		private $data		=	array();

		// CLASS CONSTRUCTOR:
		public function __construct() {
			$start = microtime(TRUE);
			$this->get_config($this->config);
			$this->load_log();
			$this->log->message(__CLASS__, ENVIRONMENT . " started for user `" . LOGIN . "`:");
			$this->load_database();
			$this->load_data_from_database();
			$this->load_executer();
			$this->put_data_to_nas();
			$this->log->message(__CLASS__, "Elapsed time: " . round(microtime(TRUE) - $start, 3) . " sec.\n");
		}

		// 1. RETURNS CONFIGURATIONS SECTION:
		private function config($section) {
			if ( empty($this->config[$section]) ) {
				$this->get_config(array(
					$section	=>	NULL
				));
			}
			return $this->config[$section];
		}

		// 1.1 LOAD SECTIONS FROM `config.ini`:
		private function get_config($sections) {
			if ( file_exists(BASEPATH . "config.ini") ) {
				$ini = parse_ini_file(BASEPATH . "config.ini", TRUE);
				foreach ( $sections as $section => $content ) {
					if ( empty($content) ) {
						if ( array_key_exists($section, $ini) ) {
							$this->config[$section] = $ini[$section];
						}
					}
				}
			}
		}

		// 2. LOAD LOG CLASS:
		private function load_log() {
			if ( ! is_object($this->log) ) {
				if ( ! empty($this->config['log']['method']) ) {
					if ( file_exists(LOG_DRVS . $this->config['log']['method'] . EXT) ) {
						include_once(LOG_DRVS . $this->config['log']['method'] . EXT);
						$this->log = new Log(array(
							'config' => $this->config('log')
						));
						return TRUE;
					} else $this->log->message(__CLASS__, "Cannot load Log Class, `" . LOG_DRVS . $this->config['log']['method'] . EXT . "` is absent!", "error");
				} else $this->log->message(__CLASS__, "Cannot load Log Class, `method` was not specified in `config.ini`!", "error");
			} else $this->log->message(__CLASS__, "Log Class is alreay loaded!", "error");
		}

		// 3. LOAD DATABASE CLASS:
		private function load_database() {
			if ( ! is_object($this->database) ) {
				if ( ! empty($this->config['database']['dbdriver']) ) {
					if ( file_exists(DB_DRVS . $this->config['database']['dbdriver'] . EXT) ) {
						include_once(DB_DRVS . $this->config['database']['dbdriver'] . EXT);
						$this->database = new Database(array(
							'config'	=>	$this->config('database'),
							'log'		=>	$this->log
						));
						return TRUE;
					} else $this->log->message(__CLASS__, "Cannot load Database Class, `" . DB_DRVS . $this->config['database']['dbdriver'] . EXT . "` is absent!", "error");
				} else $this->log->message(__CLASS__, "Cannot load Database Class, `dbdriver` was not specified in `config.ini`!", "error");
			} else $this->log->message(__CLASS__, "Database Class is alreay loaded!", "error");
		}

		// 4. QUERY DATA FROM DATABASE:
		private function load_data_from_database() {
			switch (ENVIRONMENT) {
				case "OnConnect":			// Load data if OnConnect:
					$this->data['NETWORK']['ID']	=	$this->database->get_network_id();
					$this->data['USER']['PASSWORD']	=	$this->database->get_user_password();
					$this->data['USER']['RATE']		=	$this->database->get_user_rate();
					$this->data['USER']['MAC']		=	$this->database->get_user_mac();
					$this->data['NAS']				=	$this->database->get_nas_data();
					break;
				case "OnDisconnect":		// Load data if OnDisconnect:
					$this->data['NETWORK']['ID']	=	$this->database->get_network_id();
					$this->data['NAS']				=	$this->database->get_nas_data();
					break;
			}
			return TRUE;
		}

		// 5. LOAD EXECUTER CLASS:
		private function load_executer() {
			if ( ! is_object($this->executer) ) {
				if ( ! empty($this->data['NAS']['TYPE']) ) {
					switch ($this->data['NAS']['TYPE']) {
						case "local":			// Local NAS,
						case "rscriptd":		// RScriptD NAS:
							if ( file_exists(NAS_DRVS . 'rscriptd' . EXT) ) {
								include_once(NAS_DRVS . 'rscriptd' . EXT);
								$this->executer = new Executer(array(
									'config'	=>	$this->config('rscriptd'),
									'data'		=>	$this->data,
									'log'		=>	$this->log
								));
								return TRUE;
							} else $this->log->message(__CLASS__, "Cannot load NAS Command Executer, `" . NAS_DRVS . $this->data['NAS']['TYPE'] . EXT . "` is absent!", "error");
							break;
						case "mikrotik":		// MikroTik NAS,
						case "other":			// MikroTIk NAS (DHCP & PPP):
							if ( file_exists(NAS_DRVS . 'mikrotik' . EXT) ) {
								include_once(NAS_DRVS . 'mikrotik' . EXT);
								$this->executer = new Executer(array(
									'config'	=>	$this->config('mikrotik'),
									'data'		=>	$this->data,
									'log'		=>	$this->log
								));
								return TRUE;
							} else $this->log->message(__CLASS__, "Cannot load NAS Command Executer, `" . NAS_DRVS . $this->data['NAS']['TYPE'] . EXT . "` is absent!", "error");
							break;
						default:				// Unsupported NAS:
							$this->log->message(__CLASS__, "Cannot load NAS Command Executer, NAS TYPE is unsupported!", "error");
							return FALSE;
							break;
					}
				} else $this->log->message(__CLASS__, "Cannot load NAS Command Executer, queried empty NAS TYPE from database!", "error");
			} else $this->log->message(__CLASS__, "NAS Command Executer is already loaded!", "error");
		}

		// 6. PUT DATA TO NAS:
		private function put_data_to_nas() {
			if ( is_object($this->executer) ) {
				$script = constant('ENVIRONMENT');
				switch ($this->data['NAS']['TYPE']) {
					case "local":			// Local NAS,
					case "rscriptd":		// RScriptD NAS,
					case "mikrotik":		// MikroTik NAS,
					case "other":			// MikroTik NAS (DHCP & PPP):
						$this->executer->$script();
						return TRUE;
						break;
				}
			}
		}
	}
?>