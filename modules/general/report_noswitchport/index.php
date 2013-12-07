<?php
if (cfr('REPORTNOSWPORT')) {
    
    class ReportNoSwitchPort {
        
        private $data =array();
        private $allusers=array();
        private $diff=array();
        
        public function __construct() {
            //load actual data by switch port assing
            $this->loadData();
            //loads full user list
            $this->loadAllUsers();
        }
        
        /*
         * get all users with switch port assing and push it into data prop
         * 
         * @return void
         */
        private function loadData() {
          
            $query="SELECT * from `switchportassign`;";
            $alldata=  simple_queryall($query);

            if (!empty($alldata)) {
                foreach ($alldata as $io=>$each) {
                    $this->data[$each['login']]=$each['login'];
                }
            }
        }
        
        /*
         * get all users logins and push it into allusers prop
         * 
         * @return void
         */
         private function loadAllUsers() {
          
            $query="SELECT `login` from `users`;";
            $alldata=  simple_queryall($query);

            if (!empty($alldata)) {
                foreach ($alldata as $io=>$each) {
                    $this->allusers[$each['login']]=$each['login'];
                }
            }
        }
        
        /*
         * returns private propert data
         * 
         * @return array
         */
        public function getData () {
            $result=  $this->data;
            return ($result);
        }
        
        /*
         * renders report by existing private data prop
         * 
         * @return string
         */
        public function render() {
            $diff=array();
            if (!empty($this->allusers)) {
                foreach ($this->allusers as $io=>$each) {
                    if (!isset($this->data[$each])) {
                        $this->diff[$each]=$each;
                    }
                }
            }
           $result=  web_UserArrayShower($this->diff);
           return ($result);
        }
        
         
    }
    
   /*
    * controller and view section
    */
    
   $altercfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
   if ($altercfg['SWITCHPORT_IN_PROFILE']) {
   $noSwitchPortReport=new ReportNoSwitchPort();
   show_window(__('Users without port assigned'),$noSwitchPortReport->render());
   } else {
       show_window(__('Error'), __('This module disabled'));
   }
   
} else {
      show_error(__('You cant control this module'));
}
