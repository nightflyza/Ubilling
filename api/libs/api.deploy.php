<?php

/*
 * Appends new option to config 
 * 
 * @param $path - config file path
 * @param $option - option key
 * @param $value - option raw value
 * 
 * @return void
 */
function zb_DeployConfigOption($path,$option,$value) {
    if (file_exists($path)) {
        $currentData=  rcms_parse_ini_file($path);
        if (!isset($currentData[$option])) {
            file_put_contents($path, "\n".$option.'='.$value."\n", FILE_APPEND | LOCK_EX);
            show_window(__('Added'),__('New option key').': '.$option.' '.__('with value').': '.$value.' to: '.$path);
            log_register("DEPLOY CFG OPT (".$option.") GOOD");
        } else {
           show_window(__('Warning'), __('Option already exist - skipping'));
           log_register("DEPLOY CFG OPT (".$option.") SKIP");
        }
    } else {
        show_window(__('Error'), __('Config not exists'));
        log_register("DEPLOY CFG OPT (".$option.") FAIL");
    }
    
}

/*
 * Appends new option to config with override if old option if exists
 * 
 * @param $path - config file path
 * @param $option - option key
 * @param $value - option raw value
 * 
 * @return void
 */
function zb_DeployConfigOptionOverride($path,$option,$value) {
    if (file_exists($path)) {
        $currentData=  rcms_parse_ini_file($path);
        if (!isset($currentData[$option])) {
            file_put_contents($path, "\n".$option.'='.$value."\n", FILE_APPEND | LOCK_EX);
            show_window(__('Added'),__('New option key').': '.$option.' '.__('with value').': '.$value.' to: '.$path);
             log_register("DEPLOY CFG OPTOVR (".$option.") GOOD");
        } else {
           file_put_contents($path, "\n".$option.'='.$value."\n", FILE_APPEND | LOCK_EX);
           show_window(__('Notice'), __('Option already exist - overriding').': '.$option.' value:'.$value.' in:'.$path);
           log_register("DEPLOY CFG OPTOVR (".$option.") OVR");
        }
    } else {
        show_window(__('Error'), __('Config not exists'));
        log_register("DEPLOY CFG OPTOVR (".$option.") FAIL");
    }
}

/*
 * Create new config file if not exist
 * 
 * @param $path - config file path
 * 
 * @return void
 */
function zb_DeployConfigCreate($path) {
    if (file_exists($path)) {
        show_window(__('Warning'), __('Config already exists - skipping'));
        log_register("DEPLOY CFG CRE (".$path.") SKIP");
    } else {
        file_put_contents($path, ';created by deploy API'.time()."\n");
        show_window(__('Created'),__('New config file').': '.$path);
        log_register("DEPLOY CFG CRE (".$path.") GOOD");
    }
}


function zb_DeployDBQuery($query) {
    nr_query($query);
    log_register("DEPLOY DB QUERY");
}

class Avarice {
    
    private $data=array();
    private $serial='';
    private $raw=array();
    
    public function __construct() {
        $this->getSerial();
        $this->load();
    }


    /*
     * encodes data string by some sey
     * 
     * @param $data data to encode
     * @param $key  encoding key
     * 
     * @return binary
     */
    protected function xoror($data, $key){
	$result='';
	for($i=0;$i<strlen($data);) {
	for($j=0;$j<strlen($key);$j++, $i++) {
		@$result .= $data{$i} ^ $key{$j};
	  }
	 }
	return($result);
	}
        
    /*
     * pack xorored binary data into storable ascii data
     * 
     * @param $data
     * 
     * 
     * @return string
     */    
    protected function pack($data) {
        $data=  base64_encode($data);
        return ($data);
    }
    
    /*
     * unpack packed ascii data into xorored binary
     * 
     * @param $data
     * 
     * 
     * @return string
     */    
    protected function unpack($data) {
        $data= base64_decode($data);
        return ($data);
    }   
    
    /*
     * loads all stored licenses into private data prop
     * 
     * @return void
     */
    protected function load() {
        if (!empty($this->serial)) {
        $query="SELECT * from `ubstorage` WHERE `key` LIKE 'AVLICENSE_%'";
        $keys= simple_queryall($query);
        if (!empty($keys)) {
            foreach ($keys as $io=>$each) {
                if (!empty($each['value'])) {
                    $unpack=$this->unpack($each['value']);
                    $unenc=$this->xoror($unpack, $this->serial);
                    @$unenc=  unserialize($unenc);
                    if (!empty($unenc)) {
                        if (isset($unenc['AVARICE'])) {
                            if (isset($unenc['AVARICE']['SERIAL'])) {
                                if ($this->serial==$unenc['AVARICE']['SERIAL']) {
                                    if (isset($unenc['AVARICE']['MODULE'])) {
                                        if (!empty($unenc['AVARICE']['MODULE'])) {
                                            $this->data[$unenc['AVARICE']['MODULE']]=$unenc[$unenc['AVARICE']['MODULE']];
                                            $this->raw[$unenc['AVARICE']['MODULE']]['LICENSE']=$each['value'];
                                            $this->raw[$unenc['AVARICE']['MODULE']]['VERSION']=$unenc[$unenc['AVARICE']['MODULE']]['VERSION'];
                                            $this->raw[$unenc['AVARICE']['MODULE']]['KEY']=$each['key'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
         }
        }
    }
    
    /*
     * gets ubilling system key into private key prop
     * 
     * @return void
     */
    protected function getSerial() {
        $hostid_q="SELECT * from `ubstats` WHERE `key`='ubid'";
        $hostid=simple_query($hostid_q);
        if (!empty($hostid)) {
            if (isset($hostid['value'])) {
                $this->serial=$hostid['value'];
            }
            
        }
    }
    
    /*
     * checks module license availability
     * 
     * @param $module module name to check
     * 
     * @return bool
     */
    protected function check($module) {
        if (!empty($module)) {
            if (isset($this->data[$module])) {
                return (true);
            } else {
                return(false);
            }
        }
    } 
    
    /*
     * returns module runtime 
     * 
     * @return array
     */
    public function runtime ($module) {
        $result=array();
        if ($this->check($module)) {
            $result=  $this->data[$module];
        }
        return ($result);
    }
    
    /*
     * returns list available license keys
     * 
     * @return array
     */
    public function getLicenseKeys() {
        return ($this->raw);
    }
        
}

?>
