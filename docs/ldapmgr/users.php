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

    protected function log($event, $data = '') {
        $filename = dirname(__FILE__) . '/ldapmgr.log';
        if (file_exists($filename)) {
            if (is_writable($filename)) {
                $curtime = date("Y-m-d H:i:s");
                file_put_contents($filename, $curtime . ' ' . $event . "\n", FILE_APPEND);
                if (!empty($data)) {
                    file_put_contents($filename, $data . "\n", FILE_APPEND);
                }
            }
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
                            $delResult = shell_exec(dirname(__FILE__) . self::USER_DEL . ' ' . $each['param']);
                            $this->log('USERDELETE: ' . $each['param'], $delResult);
                            break;
                        case 'usercreate':
                            $createResult = shell_exec(dirname(__FILE__) . self::USER_CREATE . ' ' . $each['param'] . ' fakepassword');
                            $this->log('USERCREATE: ' . $each['param'], $createResult);
                            break;
                        case 'userpassword':
                            $passParam = json_decode($each['param'], true);
                            $passResult = shell_exec(dirname(__FILE__) . self::USER_PASSWD . ' ' . $passParam['login'] . ' ' . $passParam['password']);
                            $this->log('USERPASS: ' . $passParam['login'], $passResult);
                            break;
                        case 'usergroups':
                            $groupParam = json_decode($each['param'], true);
                            $userLogin = $groupParam['login'];
                            $userGroups = $groupParam['groups'];
                            if (!empty($userGroups)) {
                                foreach ($userGroups as $ia => $eachGroup) {
                                    $groupResult = shell_exec(dirname(__FILE__) . self::USER_GROUP . ' ' . $userLogin . ' ' . $eachGroup);
                                    $this->log('USERGROUP: ' . $userLogin . '->' . $eachGroup, $groupResult);
                                }
                            }
                            break;
                        case 'usergroupsremove':
                            $groupParam = json_decode($each['param'], true);
                            $userLogin = $groupParam['login'];
                            $userGroups = $groupParam['groups'];
                            if (!empty($userGroups)) {
                                foreach ($userGroups as $ia => $eachGroup) {
                                    $groupDelResult = shell_exec(dirname(__FILE__) . self::USER_GROUP_DEL . ' ' . $userLogin . ' ' . $eachGroup);
                                    $this->log('USERGROUPDEL: ' . $userLogin . '->' . $eachGroup, $groupDelResult);
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