<?php if (!defined('ENVIRONMENT')) exit('No direct script access allowed!\n');

	class Ubilling {

		/* CLASSES LINKS: */
		private $database	=	NULL;
		private $executer	=	NULL;
		private $log		=	NULL;

		/* THIS SECTIONS WILL BE LOADED AUTOMATICALLY FROM `config.ini` FILE: */
		private $config	=	array(
			'database'	=>	NULL,
			'log'		=>	NULL
		);

		/* DATABASE DATA TREE: */
		private $data	=	array(
			'USER'	=>	array(
				'MAC'		=>	NULL,
				'RATE'		=>	array(
					'REASSIGNED'	=>	NULL,
					'TARIFF'		=>	NULL
				),
				'NETID'		=>	NULL
			),
			'NAS'	=>	array(
				'IP'	=>	NULL,
				'TYPE'	=>	NULL
			)
		);

		/* CLASS CONSTRUCTOR: */
		public function __construct() {
			$start = microtime(TRUE);
			$this->get_config($this->config);
			$this->load_logger();
			$this->log->message(__CLASS__, ENVIRONMENT . " started for user `" . LOGIN . "`:");
			$this->load_database();
			$this->load_data_from_database();
			$this->load_executer();
			$this->put_data_to_nas();
			$stop = microtime(TRUE);
			$this->log->message(__CLASS__, "Elapsed time: " . round($stop - $start, 3) . " sec.\n");
		}

		/* LOAD SPECIFIED SECTIONS FROM `config.ini`: */
		private function get_config($sections) {
			if (file_exists(BASEPATH . "config.ini")) {
				$config = parse_ini_file(BASEPATH . "config.ini", TRUE);
				foreach($sections as $section => $content) {
					if (is_null($content)) {
						if (array_key_exists($section, $config)) {
							$this->config[$section] = $config[$section];
						}
					}
				}
			}
		}

		// RETURN $this->config SECTION (BEFORE LOADS IT, IF NECESSARY):
		private function config($section) {
			if (empty($this->config[$section])) {
				$this->get_config(array(
					$section	=>	NULL
				));
			}
			return $this->config[$section];
		}

		// LOAD "LOGGER":
		private function load_logger() {
			if (!is_object($this->log)) {
				if (!empty($this->config['log']['method'])) {
					if (file_exists(LOG_DRVS . $this->config['log']['method'] . EXT)) {
						include_once(LOG_DRVS . $this->config['log']['method'] . EXT);
						$this->log = new Log(array(
							'config' => $this->config('log')
						));
					} else $this->log->message(__CLASS__, "Cannot load Log Class, `" . LOG_DRVS . $this->config['log']['method'] . EXT . "` is absent!", "error");
				} else $this->log->message(__CLASS__, "Cannot load Log Class, `method` was not specified in `config.ini`!", "error");
			} else $this->log->message(__CLASS__, "Log Class is alreay loaded!", "error");
		}

		// LOAD DATABASE DRIVER:
		private function load_database() {
			if (!is_object($this->database)) {
				if(!empty($this->config['database']['dbdriver'])) {
					if(file_exists(DB_DRVS . $this->config['database']['dbdriver'] . EXT)) {
						include_once(DB_DRVS . $this->config['database']['dbdriver'] . EXT);
						$this->database = new Database(array(
							'config'	=>	$this->config('database'),
							'log'		=>	$this->log
						));
					} else $this->log->message(__CLASS__, "Cannot load Database Class, `" . DB_DRVS . $this->config['database']['dbdriver'] . EXT . "` is absent!", "error");
				} else $this->log->message(__CLASS__, "Cannot load Database Class, `dbdriver` was not specified in `config.ini`!", "error");
			} else $this->log->message(__CLASS__, "Database Class is alreay loaded!", "error");
		}
		
		// LOAD NAS COMMAND EXECUTER:
		private function load_executer() {
			if(!is_object($this->executer)) {
				if (!empty($this->data['NAS']['TYPE'])) {
					switch ($this->data['NAS']['TYPE']) {
						case "local":
						case "rscriptd":
							if (file_exists(NAS_DRVS . "rscriptd" . EXT)) {
								include_once(NAS_DRVS . "rscriptd" . EXT);
								$this->executer = new Executer(array(
									'config'	=>	$this->config('rscriptd'),
									'log'		=>	$this->log
								));
							} else $this->log->message(__CLASS__, "Cannot load NAS Command Executer, `" . NAS_DRVS . $this->data['NAS']['TYPE'] . EXT . "` is absent!", "error");
							break;
						case "mikrotikapi":
							if (file_exists(NAS_DRVS . $this->data['NAS']['TYPE'] . EXT)) {
								include_once(NAS_DRVS . $this->data['NAS']['TYPE'] . EXT);
								$this->executer = new Executer(array(
									'config'	=>	$this->config('mikrotik'),
									'log'		=>	$this->log
								));
							} else $this->log->message(__CLASS__, "Cannot load NAS Command Executer, `" . NAS_DRVS . $this->data['NAS']['TYPE'] . EXT . "` is absent!", "error");
							break;
						default:
							$this->log->message(__CLASS__, "Cannot load NAS Command Executer, `" . NAS_DRVS . $this->data['NAS']['TYPE'] . EXT . "` is absent!", "error");
							break;
					}
				} else $this->log->message(__CLASS__, "Cannot load NAS Command Executer, got empty NAS TYPE from database!", "error");
			} else $this->log->message(__CLASS__, "NAS Command Executer is already loaded!", "error");
		}

		/* LOAD NEEDED DATA FROM DATABASE (DEPENDS OF ENVIRONMENT): */
		private function load_data_from_database() {
			switch(ENVIRONMENT) {
				case "OnConnect":
					$this->data['USER']['NETID']	=	$this->database->get_user_network_id();
					$this->data['USER']['RATE']		=	$this->database->get_user_rate();
					$this->data['USER']['MAC']		=	$this->database->get_user_mac();
					$this->data['NAS']				=	$this->database->get_nas_data();
					break;
				case "OnDisconnect":
					$this->data['NAS']				=	$this->database->get_nas_data();
					break;
			}
		}

		/* PUT DATA TO NAS (FUNCT DEPENDS OF ENVIRONMENT): */
		private function put_data_to_nas() {
			if (is_object($this->executer)) {
				$funct = constant('ENVIRONMENT');
				switch($this->data['NAS']['TYPE']) {
					case "local":
					case "rscriptd":
						$this->executer->$funct($this->data['USER']);
						break;
					case "mikrotikapi":
						$this->executer->$funct($this->data['USER'], $this->data['NAS']['IP']);
						break;
					default:
						$this->log->message(__CLASS__, "Cannot put data to NAS, type is unsupported!", "error");
						break;
				}
			}
		}
	}
?>