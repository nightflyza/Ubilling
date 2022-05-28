<?php

class IpACLMgr {

    /**
     * Contais all existing IP ACLs as ip=>notes
     *
     * @var array
     */
    protected $allowedIps = array();

    /**
     * Contais all existing nets ACLs as network network=>notes
     *
     * @var array
     */
    protected $allowedNets = array();

    /**
     * Some predefined URLs, routes, etc...
     */
    const URL_ME = '?module=ipaclmgr';

    /**
     * Creates new IP ACL manager instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadAclIps();
        $this->loadAclNets();
    }

    /**
     * Loads all existing IP ACLs into protected property
     * 
     * @return void
     */
    protected function loadAclIps() {
        $tmp = rcms_scandir(IPACLALLOWIP_PATH);
        if (!empty($tmp)) {
            foreach ($tmp as $io => $eachIp) {
                $this->allowedIps[$eachIp] = file_get_contents(IPACLALLOWIP_PATH . $eachIp);
            }
        }
    }

    /**
     * Loads all existing networks ACLs into protected property
     * 
     * @return void
     */
    protected function loadAclNets() {
        $tmp = rcms_scandir(IPACLALLOWNETS_PATH);
        if (!empty($tmp)) {
            foreach ($tmp as $io => $eachNet) {
                $eachNetCidr = str_replace('_', '/', $eachNet);
                $this->allowedNets[$eachNetCidr] = file_get_contents(IPACLALLOWNETS_PATH . $eachNet);
            }
        }
    }

}
