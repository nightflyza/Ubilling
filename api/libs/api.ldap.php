<?php

/**
 * LDAP database management class
 */
class UbillingLDAPManager {

    /**
     * Contains available LDAP users as id=>userdata
     *
     * @var string
     */
    protected $allUsers = array();

    /**
     * Contains all of available user groups as id=>name
     *
     * @var array
     */
    protected $allGroups = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    const URL_ME = '?module=ldapmgr';

    /**
     * Even if you can forget, you can't erase the past. Kenzo Tenma.
     */
    public function __construct() {
        $this->initMessages();
        $this->loadUsers();
        $this->loadGroups();
    }

    /**
     * Inits system message helper as local instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads existing users from database into protected property for further usage
     * 
     * @return void 
     */
    protected function loadUsers() {
        $query = "SELECT * from `ldap_users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUsers[$each['id']] = $each;
            }
        }
    }

    /**
     * Sets available groups options
     * 
     * @return
     */
    protected function loadGroups() {
        $query = "SELECT * from `ldap_groups`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allGroups[$each['id']] = $each['name'];
            }
        }
    }

    /**
     * Create new group in database
     * 
     * @param string $name
     * 
     * @return void
     */
    public function createGroup($name) {
        $nameF = mysql_real_escape_string($name);
        $query = "INSERT INTO `ldap_groups` (`id`,`name`) VALUES ";
        $query.="(NULL,'" . $nameF . "');";
        nr_query($query);
        $newId = simple_get_lastid('ldap_groups');
        log_register('LDAPMGR GROUP CREATE `' . $name . '` [' . $newId . ']');
    }

    /**
     * Deletes existing group from database
     * 
     * @param int $groupId
     * 
     * @return void/string on error
     */
    public function deleteGroup($groupId) {
        $result = '';
        $groupId = vf($groupId, 3);
        if (isset($this->allGroups[$groupId])) {
            if (!$this->isGroupProtected($groupId)) {
                $query = "DELETE FROM `ldap_groups` WHERE `id`='" . $groupId . "';";
                nr_query($query);
                log_register('LDAPMGR GROUP DELETE  [' . $groupId . ']');
            } else {
                $result.=__('Something went wrong') . ': EX_GROUPID_USED_BY_SOMEONE';
            }
        } else {
            $result.=__('Something went wrong') . ': EX_GROUPID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Renders group creation interface, Fuck yeah!
     * 
     * @return string
     */
    public function renderGroupCreateFrom() {
        $result = '';
        $inputs = wf_TextInput('newldapgroupname', __('Name'), '', false, 20);
        $inputs.= wf_Submit(__('Create'));
        $result.=wf_Form(self::URL_ME . '&groups=true', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders existing groups list with some controls
     * 
     * @return string
     */
    public function renderGroupsList() {
        $result = '';
        if (!empty($this->allGroups)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Name'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allGroups as $io => $each) {
                $cells = wf_TableCell($io);
                $cells.= wf_TableCell($each);
                $actLinks = wf_JSAlert(self::URL_ME . '&groups=true&deletegroupid=' . $io, web_delete_icon(), $this->messages->getDeleteAlert());
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }
            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return ($result);
    }

    /**
     * Check is user login unique or not?
     * 
     * @param string $login
     * 
     * @return bool
     */
    protected function isUserUnique($login) {
        $login = trim($login);
        $result = true;
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                if ($each['login'] == $login) {
                    $result = false;
                }
            }
        }
        return ($result);
    }

    /**
     * Check is group protected from deletion?
     * 
     * @param int $groupId
     * 
     * @return bool
     */
    protected function isGroupProtected($groupId) {
        $result = false;
        $groupId = vf($groupId, 3);
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $eachUser) {
                $userGroups = json_decode($eachUser['groups'], true);
                if (isset($userGroups[$groupId])) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Creates new user in database
     * 
     * @param string $login
     * @param string $password
     * @param array $groups
     * 
     * @return void
     */
    public function createUser($login, $password, $groups) {
        $loginF = mysql_real_escape_string($login);
        if ($this->isUserUnique($loginF)) {
            $passwordF = mysql_real_escape_string($password);
            $groupsList = json_encode($groups);
            $query = "INSERT INTO `ldap_users` (`id`,`login`,`password`,`groups`,`changed`) VALUES ";
            $query.="(NULL,'" . $loginF . "','" . $passwordF . "','" . $groupsList . "','1');";
            nr_query($query);
            $this->pushQueue('usercreate', $login);
            $passParam = array('login' => $login, 'password' => $password);
            $passParam = json_encode($passParam);
            $this->pushQueue('userpassword', $passParam);
            $taskGroups = array('login' => $login, 'groups' => $groups);
            $taskGroups = json_encode($taskGroups);
            $this->pushQueue('usergroups', $taskGroups);
            $newId = simple_get_lastid('ldap_users');
            log_register('LDAPMGR USER CREATE `' . $login . '` [' . $newId . ']');
        }
    }

    /**
     * Changes user groups
     * 
     * @param int $userId
     * @param array $newGroups
     * 
     * @return void
     */
    public function changeGroups($userId, $newGroups) {
        $userId = vf($userId, 3);
        $pushGroups = array();
        $removeGroups = array();
        if (isset($this->allUsers[$userId])) {
            $userData = $this->allUsers[$userId];
            $userLogin = $userData['login'];
            $oldGroups = json_decode($userData['groups'], true);
            if (!empty($newGroups)) {
                //checking for new groups
                foreach ($newGroups as $newGroupId => $newGroupName) {
                    if (!isset($oldGroups[$newGroupId])) {
                        $pushGroups[$newGroupId] = $newGroupName;
                    }
                }
            }

            //checking for removed groups
            if (!empty($oldGroups)) {
                foreach ($oldGroups as $oldGroupId => $oldGroupName) {
                    if (!isset($newGroups[$oldGroupId])) {
                        $removeGroups[$oldGroupId] = $oldGroupName;
                    }
                }
            }

            //is some changes available?
            if ((!empty($pushGroups)) OR ( !empty($removeGroups))) {
                //saving new groups into user profile
                simple_update_field('ldap_users', 'groups', json_encode($newGroups), "WHERE `id`='" . $userId . "'");
                log_register('LDAPMGR USER GROUPS CHANGED `' . $userLogin . '` [' . $userId . ']');

                //adding some new groups
                if (!empty($pushGroups)) {
                    $taskGroups = array('login' => $userLogin, 'groups' => $pushGroups);
                    $taskGroups = json_encode($taskGroups);
                    $this->pushQueue('usergroups', $taskGroups);
                }

                //deleting removed groups
                if (!empty($removeGroups)) {
                    $taskGroups = array('login' => $userLogin, 'groups' => $removeGroups);
                    $taskGroups = json_encode($taskGroups);
                    $this->pushQueue('usergroupsremove', $taskGroups);
                }

                /**
                 * Jugemu jugemu gokou no surikire
                 * Kaijari suigyo no suigyoumatsu
                 * Unraimatsu fuuraimatsu
                 * Kuuneru tokoro ni sumu tokoro
                 */
            }
        }
    }

    /**
     * Deletes some existing user from database
     * 
     * @param int $userId
     * 
     * @return void/string on error
     */
    public function deleteUser($userId) {
        $result = '';
        $userId = vf($userId, 3);
        if (isset($this->allUsers[$userId])) {
            $userData = $this->allUsers[$userId];
            $query = "DELETE from `ldap_users` WHERE `id`='" . $userId . "';";
            nr_query($query);
            $this->pushQueue('userdelete', $userData['login']);
            log_register('LDAPMGR USER DELETE `' . $userData['login'] . '` [' . $userId . ']');
        } else {
            $result = __('Something went wrong') . ': EX_USERID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Renders password editing form
     * 
     * @return string
     */
    protected function renderUserPasswordForm($userId) {
        $result = '';
        $userId = vf($userId, 3);
        if (isset($this->allUsers[$userId])) {
            $userData = $this->allUsers[$userId];
            $inputs = wf_HiddenInput('passchid', $userId);
            $inputs.= wf_PasswordInput('passchpass', __('Password'), $userData['password'], false, 15);
            $inputs.=wf_Submit(__('Save'));
            $result.=wf_Form(self::URL_ME, 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Renders user groups editing form
     * 
     * @param int $userId
     * 
     * @return string
     */
    protected function renderUserGroupsForm($userId) {
        $result = '';
        $userId = vf($userId, 3);
        if (isset($this->allUsers[$userId])) {
            $groupsInputs = '';
            $userData = $this->allUsers[$userId];
            $currentGroups = json_decode($userData['groups'], true);
            if (!empty($this->allGroups)) {
                foreach ($this->allGroups as $io => $each) {
                    $checkFlag = (isset($currentGroups[$io])) ? true : false;
                    $groupsInputs.=wf_CheckInput('ldapusergroup_' . $io, $each, true, $checkFlag);
                }
            }
            $inputs = wf_HiddenInput('chusergroupsuserid', $userId);
            $inputs.= $groupsInputs;
            $inputs.=wf_delimiter();
            $inputs.=wf_Submit(__('Save'));
            $result = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Pushes some task for queue
     * 
     * @param string $task
     * @param string $param
     * 
     * @return void
     */
    protected function pushQueue($task, $param) {
        $task = mysql_real_escape_string($task);
        $param = mysql_real_escape_string($param);
        $query = "INSERT INTO `ldap_queue` (`id`,`task`,`param`) VALUES ";
        $query.="(NULL,'" . $task . "','" . $param . "');";
        nr_query($query);
    }

    /**
     * Changes user password and stores this into queue
     * 
     * @param int $userId
     * @param string $newPassword
     * 
     * @return void/string on error
     */
    public function changeUserPassword($userId, $newPassword) {
        $result = '';
        $userId = vf($userId, 3);
        if (isset($this->allUsers[$userId])) {
            $userData = $this->allUsers[$userId];
            $login = $userData['login'];
            if ($userData['password'] != $newPassword) {
                simple_update_field('ldap_users', 'password', $newPassword, "WHERE `id`='" . $userId . "'");
                $passParam = array('login' => $login, 'password' => $newPassword);
                $passParam = json_encode($passParam);
                $this->pushQueue('userpassword', $passParam);
                log_register('LDAPMGR USER PASSWORD CHANGED `' . $login . '` [' . $userId . ']');
            }
        } else {
            $result = __('Something went wrong') . ': EX_USERID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Renders user creation form
     * 
     * @return string
     */
    protected function renderUserCreateForm() {
        $result = '';
        $groupsInputs = '';
        if (!empty($this->allGroups)) {
            foreach ($this->allGroups as $io => $each) {
                $groupsInputs.=wf_CheckInput('ldapusergroup_' . $io, $each, true, false);
            }

            $inputs = wf_TextInput('newldapuserlogin', __('Login'), '', true, 20);
            $inputs.= wf_TextInput('newldapuserpassword', __('Password'), '', true, 20);
            $inputs.=$groupsInputs;
            $inputs.=wf_tag('br');
            $inputs.= wf_Submit(__('Create'));
            $result.=wf_Form(self::URL_ME, 'POST', $inputs, 'glamour');
        } else {
            $result.=$this->messages->getStyledMessage(__('Oh no') . ': ' . __('No existing groups available'), 'warning');
        }
        return ($result);
    }

    /**
     * Catches and preprocess user groups
     * 
     * @return array
     */
    public function catchNewUserGroups() {
        $result = array();
        if (!empty($_POST)) {
            foreach ($_POST as $io => $each) {
                if (ispos($io, 'ldapusergroup')) {
                    $groupId = vf($io, 3);
                    $result[$groupId] = $this->allGroups[$groupId];
                }
            }
        }
        return ($result);
    }

    /**
     * Flushes processed queue in database
     * 
     * @return void
     */
    protected function flushQueue() {
        $query = "TRUNCATE TABLE `ldap_queue`;";
        nr_query($query);
    }

    /**
     * Returns current unprocessed tasks queue
     * 
     * @return void
     */
    public function getQueue() {
        $query = "SELECT * from `ldap_queue` ORDER BY `id` ASC";
        $queue = simple_queryall($query);
        $queue = json_encode($queue);
        print($queue);
        $this->flushQueue();
        die();
    }

    /**
     * Renders main control panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        if (!wf_CheckGet(array('groups'))) {
            $result.=wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Users registration'), __('Users registration'), $this->renderUserCreateForm(), 'ubButton') . ' ';
            $result.= wf_Link(self::URL_ME . '&groups=true', web_icon_extended() . ' ' . __('Groups'), false, 'ubButton');
        } else {
            $result.=wf_BackLink(self::URL_ME) . ' ';
            $result.=wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Create'), __('Create'), $this->renderGroupCreateFrom(), 'ubButton');
        }
        return ($result);
    }

    /**
     * Unpacks and 
     * 
     * @param string $groupsData
     * 
     * @return string
     */
    protected function previewGroups($groupsData) {
        $result = '';
        if (!empty($groupsData)) {
            $groupsData = json_decode($groupsData);
            if (!empty($groupsData)) {
                foreach ($groupsData as $groupId => $groupName) {
                    $result.=$groupName . ' ';
                }
            }
        }
        return ($result);
    }

    /**
     * Renders existing users list and some controls
     * 
     * @return string
     */
    public function renderUserList() {
        $result = '';
        if (!empty($this->allUsers)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Login'));
            $cells.= wf_TableCell(__('Password'));
            $cells.= wf_TableCell(__('Groups'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allUsers as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['login']);
                $userPass = __('Hidden');
                $cells.= wf_TableCell($userPass);
                $cells.= wf_TableCell($this->previewGroups($each['groups']));
                $actLinks = wf_JSAlert(self::URL_ME . '&deleteuserid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks.= wf_modalAuto(wf_img('skins/icon_key.gif', __('Password')), __('Password'), $this->renderUserPasswordForm($each['id'])) . ' ';
                $actLinks.=wf_modalAuto(web_icon_extended(__('Groups')), __('Groups'), $this->renderUserGroupsForm($each['id']));
                $cells.= wf_TableCell($actLinks);

                $rows.= wf_TableRow($cells, 'row5');
            }
            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return ($result);
    }

}

?>