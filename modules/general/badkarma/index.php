<?php

if (cfr('CASH')) {

    class BadKarma {

        /**
         * Contains default online users detection path
         *
         * @var string
         */
        protected $onlineDataPath = '/etc/stargazer/dn/';

        /**
         * Contains all available users data as login=>userdata
         *
         * @var array
         */
        protected $allUsersData = array();

        /**
         * Contains all of online users as login=>login
         *
         * @var array
         */
        protected $allOnlineUsers = array();

        public function __construct() {
            $this->loadUserData();
            $this->loadOnlineUsers();
        }

        /**
         * Loads existing userdata from database
         * 
         * @return void
         */
        protected function loadUserData() {
            $this->allUsersData = zb_UserGetAllData();
        }

        /**
         * Loads list of online users
         * 
         * @return void
         */
        protected function loadOnlineUsers() {
            if (file_exists($this->onlineDataPath)) {
                $all = rcms_scandir($this->onlineDataPath);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $this->allOnlineUsers[$each]=$each;
                    }
                }
            }
        }
        
        

    }
    
    $badKarma=new BadKarma();
    

} else {
    show_error(__('Access denied'));
}