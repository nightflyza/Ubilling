<?php

$ldapMgrCfg = parse_ini_file(dirname(__FILE__) . '/ldapmgr.ini');
$apiUrl = $ldapMgrCfg['API_URL'];
$apiKey = $ldapMgrCfg['API_KEY'];

class remoteLdapBase {

    protected $apiUrl = '';
    protected $apiKey = '';
    protected $urlInterface = '';

    const USER_CREATE = '/create_user';
    const USER_GROUP = '/add_member';
    const USER_GROUP_DEL = '/remove_member';
    const USER_DEL = '/remove_user';
    const USER_PASSWD = '/change_passwd';

    public function __construct($apiUrl, $apiKey) {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        if ((!empty($this->apiKey)) AND ( !empty($this->apiUrl))) {
            $this->urlInterface = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=ldapmgr&param=';
            $this->getQueueData();
        } else {
            die('Error reading config file' . "\n");
        }
    }

    protected function getQueueData() {
        $result = array();
        $rawData = file_get_contents($this->urlInterface . 'queue');
        if (!empty($rawData)) {
            $tmpArr = json_decode($rawData, true);
            if (!empty($tmpArr)) {
                foreach ($tmpArr as $io => $each) {
                    switch ($each['task']) {
                        case 'userdelete':
                            shell_exec(dirname(__FILE__) . self::USER_DEL . ' ' . $each['param']);
                            break;
                        case 'usercreate':
                            shell_exec(dirname(__FILE__) . self::USER_DEL . ' ' . $each['param'] . ' fakepassword');
                            break;
                        case 'userpassword':
                            $passParam = json_decode($each['param'],true);
                            shell_exec(dirname(__FILE__) . self::USER_PASSWD . ' ' . $passParam['login'] . ' ' . $passParam['password']);
                            break;
                        case 'usergroups':
                            $groupParam = json_decode($each['param'],true);
                            $userLogin = $groupParam['login'];
                            $userGroups = $groupParam['groups'];
                            if (!empty($userGroups)) {
                                foreach ($userGroups as $ia => $eachGroup) {
                                    shell_exec(dirname(__FILE__) . self::USER_GROUP . ' ' . $userLogin . ' ' . $eachGroup);
                                }
                            }
                            break;
                    }
                }
            }
        }
        return ($result);
    }

}

$remoteBase = new remoteLdapBase($apiUrl, $apiKey);
?>