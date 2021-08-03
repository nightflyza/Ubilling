<?php

////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) ReloadCMS Development Team                                 //
//   http://reloadcms.sf.net                                                  //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////

class rcms_access {

    var $rights_database = array();
    var $rights = array();
    var $root = false;
    var $level = 0;

    function initialiseAccess($rights, $level) {
        $this->rights = array();
        $this->root = false;
        if ($rights !== '*') {
            preg_match_all('/\|(.*?)\|/', $rights, $rights_r);
            foreach ($rights_r[1] as $right) {
                $this->rights[$right] = (empty($this->rights_database[$right])) ? ' ' : $this->rights_database[$right];
            }
        } else {
            $this->root = true;
        }
        $this->level = $level;
        return true;
    }

    /**
     * @param string $right
     * @return boolean
     * @desc Check if user have specified right
     */
    function checkForRight($right = '-any-', $username = '') {
        if (empty($username)) {
            $rights = &$this->rights;
            $root = &$this->root;
        } else {
            if (!$this->getRightsForUser($username, $rights, $root, $level)) {
                return false;
            }
        }
        return $root || ($right == '-any-' && !empty($rights)) || !empty($rights[$right]);
    }

    function getRightsForUser($username, &$rights, &$root, &$level) {
        if (!($userdata = $this->getUserData($username)))
            return false;
        if (!empty($this->config['registered_accesslevel'])) {
            $level = (int) $this->config['registered_accesslevel'];
            if (!isset($userdata['accesslevel']) || $level > $userdata['accesslevel']) {
                $userdata['accesslevel'] = $level;
            }
        }
        $rights = array();
        $root = false;
        if ($userdata['admin'] !== '*') {
            preg_match_all('/\|(.*?)\|/', $userdata['admin'], $rights_r);
            foreach ($rights_r[1] as $right) {
                $rights[$right] = (empty($this->rights_database[$right])) ? ' ' : $this->rights_database[$right];
            }
        } else {
            $root = true;
        }
        $level = (int) @$userdata['accesslevel'];
        return true;
    }

    function setRightsForUser($username, $rights, $root = false, $level = 0) {
        if (empty($rights))
            $rights = array();
        if (!empty($this->config['registered_accesslevel'])) {
            $reg_level = (int) $this->config['registered_accesslevel'];
            if ($level === '') {
                $userdata['accesslevel'] = $reg_level;
            }
        }
        if ($root) {
            $rights_string = '*';
        } else {
            $rights_string = '';
            if (is_array($rights)) {
                foreach ($rights as $right => $cond) {
                    if ($cond)
                        $rights_string .= '|' . $right . '|';
                }
            }
        }
        user_change_field($username, 'admin', $rights_string);
        user_change_field($username, 'accesslevel', $level);
        return true;
    }

}

class rcms_user_cache {

    var $cache_filename = 'users.cache.dat';
    var $cache = array();

    public function __construct() {
        if (!is_file(DATA_PATH . $this->cache_filename)) {
            $this->cache = array();
        } else {
            if (!($this->cache = @unserialize(@file_get_contents(DATA_PATH . 'users.cache.dat')))) {
                $this->cache = array();
            }
        }
    }

    function save() {
        file_write_contents(DATA_PATH . $this->cache_filename, serialize($this->cache));
    }

    function registerUser($username, $usernick, $email) {
        $this->cache['nicks'][$username] = $usernick;
        $this->cache['mails'][$username] = $email;
        $this->save();
        return true;
    }

    function getUser($field, $value) {
        return array_search($value, $this->cache[$field]);
    }

    function removeUser($username) {
        if (!empty($this->cache['nicks'][$username])) {
            $this->cache['nicks'][$username] = '';
            unset($this->cache['nicks'][$username]);
        }
        if (!empty($this->cache['mails'][$username])) {
            $this->cache['mails'][$username] = '';
            unset($this->cache['mails'][$username]);
        }
        $this->save();
        return true;
    }

    function checkField($field, $value) {
        if (empty($this->cache[$field]))
            return true;
        return !in_array_i($value, $this->cache[$field]);
    }

}

define('USERS_ALLOW_CHANGE', 0);
define('USERS_ALLOW_SET', 1);
define('USERS_DISALLOW_CHANGE', 2);
define('USERS_DISALLOW_CHANGE_ALL', 3);

class rcms_user extends rcms_access {

    var $profile_fields = array();
    var $profile_defaults = array();

    /**
     * This property indicates if user is registered or just a guest
     *
     * @access public
     * @var boolean
     */
    var $logged_in = false;

    /**
     * This array contain data from user's profile
     *
     * @access public
     * @var array
     */
    var $user = array();

    /**
     * Name for user cookie
     *
     * @access private
     * @var string
     */
    var $cookie_user = 'ubilling_user';
    var $users_cache = null;

    /**
     * @return boolean
     * @param string $skipcheck Use this parameter to skip userdata checks
     * @desc This function is an internal private function for class rcms_system
      and must not be used externally. This function initialize user and
      load his profile to object.
     */
    function initializeUser($skipcheck = false) {
        $this->users_cache = new rcms_user_cache();

        $this->data['apf'] = parse_ini_file(CONFIG_PATH . 'users.fields.ini');
        // Enter access levels for fields here
        $this->profile_fields = array(
            'hideemail' => USERS_ALLOW_CHANGE,
            'admin' => USERS_DISALLOW_CHANGE_ALL,
            'tz' => USERS_ALLOW_CHANGE,
            'accesslevel' => USERS_DISALLOW_CHANGE_ALL,
            'last_prr' => USERS_DISALLOW_CHANGE_ALL,
            'blocked' => USERS_DISALLOW_CHANGE
        );
        foreach ($this->data['apf'] as $field => $desc) {
            $this->profile_fields[$field] = USERS_ALLOW_CHANGE;
        }
        $this->profile_defaults = array('hideemail' => 0, 'admin' => ' ', 'tz' => 0, 'accesslevel' => 0, 'blocked' => 0, 'last_prr' => 0);

        // Load default guest userdata
        $this->user = array('nickname' => __('Guest'), 'username' => 'guest', 'admin' => '', 'tz' => (int) @$this->config['default_tz'], 'accesslevel' => 0);
        $this->initialiseAccess($this->user['admin'], (int) @$userdata['accesslevel']);

        // Ability for guests to enter nick
        $_POST['gst_nick'] = substr(trim(@$_POST['gst_nick']), 0, 32);
        if (!empty($_POST['gst_nick']) && !$this->logged_in) {
            $this->user['nickname'] = $_POST['gst_nick'];
            setcookie('reloadcms_nick', $this->user['nickname']);
            $_COOKIE['reloadcms_nick'] = $this->user['nickname'];
        } elseif (!$this->logged_in && !empty($_COOKIE['reloadcms_nick'])) {
            $this->user['nickname'] = substr(trim($_COOKIE['reloadcms_nick']), 0, 32);
        }
        if (!$this->users_cache->checkField('nicks', $this->user['nickname'])) {
            $this->user['nickname'] = __('Guest');
            setcookie('reloadcms_nick', '', time() - 16000);
            unset($_COOKIE['reloadcms_nick']);
        }

        // Secure the nickname
        $this->user['nickname'] = htmlspecialchars($this->user['nickname']);

        // If user cookie is not present we exiting without error
        if (empty($_COOKIE[$this->cookie_user])) {
            $this->logged_in = false;
            return true;
        }

        // So we have a cookie, let's extract data from it
        $cookie_data = explode(':', $_COOKIE[$this->cookie_user], 2);
        if (!$skipcheck) {
            // If this cookie is invalid - we exiting destroying cookie and exiting with error
            if (sizeof($cookie_data) != 2) {
                setcookie($this->cookie_user, null, time() - 3600);
                return false;
            }
            // Now we must validate user's data
            if (!$this->checkUserData($cookie_data[0], $cookie_data[1], 'user_init', true, $this->user)) {
                setcookie($this->cookie_user, null, time() - 3600);
                $this->logged_in = false;
                return false;
            }
        }

        $userdata = $this->getUserData($cookie_data[0]);
        if ($userdata == false) {
            setcookie($this->cookie_user, null, time() - 3600);
            $this->logged_in = false;
            return false;
        }
        $this->user = $userdata;
        $this->logged_in = true;

        if (!empty($this->config['registered_accesslevel'])) {
            $level = (int) $this->config['registered_accesslevel'];
            if (!isset($userdata['accesslevel'])) {
                $this->user['accesslevel'] = $level;
            }
        }

        // Initialise access levels
        $this->initialiseAccess($this->user['admin'], (int) @$this->user['accesslevel']);

        // Secure the nickname
        $this->user['nickname'] = htmlspecialchars($this->user['nickname']);

        return true;
    }

    /**
     * @return boolean
     * @param string $username
     * @param string $password
     * @param string $report_to
     * @param boolean $hash
     * @param link $userdata
     * @desc This function is an internal private function for class rcms_system
      and must not be used externally. This function check user's data and
      validate his data file.
     */
    function checkUserData($username, $password, $report_to, $hash, &$userdata) {
        if (preg_replace("/[\d\w]+/i", "", $username) != "") {
            $this->results[$report_to] = __('Invalid username');
            return false;
        }
        // If login is not exists - we exiting with error
        if (!is_file(USERS_PATH . $username)) {
            $this->results[$report_to] = __('There are no user with this username');
            return false;
        }
        // So all is ok. Let's load userdata
        $result = $this->getUserData($username);
        // If userdata is invalid we must exit with error
        if (empty($result))
            return false;
        // If password is invalid - exit with error
        if ((!$hash && md5($password) !== $result['password']) || ($hash && $password !== $result['password'])) {
            $this->results[$report_to] = __('Invalid password');
            return false;
        }
        // If user is blocked - exit with error
        if (@$result['blocked']) {
            $this->results[$report_to] = __('This account has been blocked by administrator');
            return false;
        }
        $userdata = $result;
        return true;
    }

    /**
     * @return boolean
     * @param string $username
     * @param string $password
     * @param boolean $remember
     * @desc This function check user's data and log in him.
     */
    function logInUser($username, $password, $remember) {
        $username = basename($username);
        if ($username == 'guest')
            return false;
        if (!$this->logged_in && $this->checkUserData($username, $password, 'user_login', false, $userdata)) {
            rcms_log_put('Notification', $this->user['username'], 'Logged in as ' . $username);
            // OK... Let's allow user to log in :)
            setcookie($this->cookie_user, $username . ':' . $userdata['password'], ($remember) ? time() + 3600 * 24 * 365 : null);
            $_COOKIE[$this->cookie_user] = $username . ':' . $userdata['password'];
            $this->initializeUser(true);
            return true;
        } else {
            if (!$this->logged_in) {
                rcms_log_put('Notification', $this->user['username'], 'Attempted to log in as ' . $username);
            }
            return false;
        }
    }

    /**
     * @return boolean
     * @desc This function log out user from system and destroys his cookie.
     */
    function logOutUser() {
        if ($this->logged_in) {
            //normal user logout
            if (!@$_COOKIE['ghost_user']) {
                rcms_log_put('Notification', $this->user['username'], 'Logged out');
                setcookie($this->cookie_user, '', time() - 3600);
                $_COOKIE[$this->cookie_user] = '';
                $this->initializeUser(false);
            } else {
                //ghostmode logout
                $this->deinitGhostMode();
            }
            return true;
        }
    }

    /**
     * Deinits ghost mode for current ghost administrator
     * 
     * @return void
     */
    function deinitGhostMode() {
        global $system;
        if (@$_COOKIE['ghost_user']) {
            $myLogin = $this->user['username'];
            $ghostData = explode(':', $_COOKIE['ghost_user']);
            //cleanup ghostmode data
            setcookie('ghost_user', '', null);
            $_COOKIE['ghost_user'] = '';

            //login of another admin
            rcms_log_put('Notification', $ghostData[0], 'Ghost logged out as ' . $myLogin);
            setcookie('ubilling_user', $ghostData[0] . ':' . $ghostData[1], null);
            $_COOKIE['ubilling_user'] = $ghostData[0] . ':' . $ghostData[1];
        }
    }

    function registerUser($username, $nickname, $password, $confirm, $email, $userdata) {
        $username = basename($username);
        $nickname = empty($nickname) ? $username : substr(trim($nickname), 0, 32);

        if (empty($username) || preg_replace("/[\d\w]+/i", '', $username) != '' || strlen($username) > 32 || $username == 'guest') {
            $this->results['registration'] = __('Invalid username');
            return false;
        }

        if (is_file(USERS_PATH . $username)) {
            $this->results['registration'] = __('User with this username already exists');
            return false;
        }

        if (!user_check_nick_in_cache($username, $nickname, $cache)) {
            $this->results['registration'] = __('User with this nickname already exists');
            return false;
        }

        if (empty($email) || !rcms_is_valid_email($email)) {
            $this->results['registration'] = __('Invalid e-mail address');
            return false;
        }

        if (!user_check_email_in_cache($username, $email, $cache)) {
            $this->results['registration'] = __('This e-mail address already registered');
            return false;
        }

        if (!empty($this->config['regconf']))
            $password = $confirm = rcms_random_string(8);
        if (empty($password) || empty($confirm) || $password != $confirm) {
            $this->results['registration'] = __('Password doesnot match it\'s confirmation');
            return false;
        }

        // If our user is first - we must set him an admin rights
        $_userdata['admin'] = (sizeof(rcms_scandir(USERS_PATH)) == 0) ? '*' : ' ';

        // Also we must set a md5 hash of user's password to userdata
        $_userdata['password'] = md5($password);
        $_userdata['nickname'] = $nickname;
        $_userdata['username'] = $username;
        $_userdata['email'] = $email;

        // Parse some system fields
        $userdata['hideemail'] = empty($userdata['hideemail']) ? '0' : '1';
        $userdata['tz'] = (float) @$userdata['tz'];

        foreach ($this->profile_fields as $field => $acc) {
            if ($acc <= USERS_ALLOW_SET || $acc == USERS_ALLOW_CHANGE) {
                if (!isset($userdata[$field])) {
                    $userdata[$field] = $this->profile_defaults[$field];
                } else {
                    $_userdata[$field] = strip_tags(trim($userdata[$field]));
                }
            }
        }
        foreach ($this->data['apf'] as $field => $desc) {
            $_userdata[$field] = strip_tags(trim($userdata[$field]));
        }

        if (!file_write_contents(USERS_PATH . $username, serialize($_userdata))) {
            $this->results['registration'] = __('Cannot save profile');
            return false;
        }

        user_register_in_cache($username, $nickname, $email, $cache);

        if (!empty($this->config['regconf'])) {
            $site_url = parse_url($this->url);
            rcms_send_mail($email, 'no_reply@' . $site_url['host'], __('Password'), $this->config['encoding'], __('Your password at') . ' ' . $site_url['host'], __('Your username at') . ' ' . $site_url['host'] . ': ' . $username . "\r\n" . __('Your password at') . ' ' . $site_url['host'] . ': ' . $password);
        }

        $this->results['registration'] = __('Registration complete. You can now login with your username and password.');
        rcms_log_put('Notification', $this->user['username'], 'Registered account ' . $username);
        return true;
    }

    function updateUser($username, $nickname, $password, $confirm, $email, $userdata, $admin = false) {
        $username = basename($username);
        $nickname = empty($nickname) ? $username : substr(strip_tags($nickname), 0, 20);

        if (empty($username) || preg_replace("/[\d\w]+/i", '', $username) != '') {
            $this->results['profileupdate'] = __('Invalid username');
            return false;
        }
        if ($username == 'guest')
            return false;

        if (!is_file(USERS_PATH . $username)) {
            $this->results['profileupdate'] = __('There is no user with this name');
            return false;
        }

        user_remove_from_cache($username, $cache);
        if (!($_userdata = $this->getUserData($username))) {
            $this->results['profileupdate'] = __('Cannot open profile');
            return false;
        }

        if (!user_check_nick_in_cache($username, $nickname, $cache)) {
            $this->results['profileupdate'] = __('User with this nickname already exists');
            return false;
        }

        if (empty($email) || !rcms_is_valid_email($email)) {
            $this->results['profileupdate'] = __('Invalid e-mail address');
            return false;
        }

        if (!user_check_email_in_cache($username, $email, $cache)) {
            $this->results['profileupdate'] = __('This e-mail address already registered');
            return false;
        }

        if (!empty($password) && !empty($confirm) && $password != $confirm) {
            $this->results['profileupdate'] = __('Password doesnot match it\'s confirmation');
            return false;
        }

        // Also we must set a md5 hash of user's password to userdata
        $_userdata['password'] = (empty($password)) ? $_userdata['password'] : md5($password);
        $_userdata['nickname'] = $nickname;
        $_userdata['email'] = $email;

        // Parse some system fields
        $userdata['hideemail'] = empty($userdata['hideemail']) ? '0' : '1';
        $userdata['tz'] = (float) $userdata['tz'];
        $userdata['accesslevel'] = (int) @$userdata['accesslevel'];

        foreach ($this->profile_fields as $field => $acc) {
            if (($admin && $acc < USERS_DISALLOW_CHANGE_ALL) || $acc <= USERS_ALLOW_SET || $acc == USERS_ALLOW_CHANGE) {
                if (!isset($userdata[$field])) {
                    $userdata[$field] = $this->profile_defaults[$field];
                } else {
                    $_userdata[$field] = strip_tags(trim($userdata[$field]));
                }
            }
        }
        foreach ($this->data['apf'] as $field => $desc) {
            $_userdata[$field] = strip_tags(trim($userdata[$field]));
        }

        if (!file_write_contents(USERS_PATH . $username, serialize($_userdata))) {
            $this->results['profileupdate'] = __('Cannot save profile');
            return false;
        }

        user_register_in_cache($username, $nickname, $email, $cache);
        $this->results['profileupdate'] = __('Profile updated');
        if ($this->user['username'] == $username) {
            $this->user = $_userdata;
        }
        rcms_log_put('Notification', $this->user['username'], 'Updated userinfo for ' . $username);
        return true;
    }

    function recoverPassword($username, $email) {
        $username = basename($username);
        if (!($data = $this->getUserData($username))) {
            $this->results['passrec'] = __('Cannot open profile');
            return false;
        }
        if ($email != $data['email']) {
            $this->results['passrec'] = __('Your e-mail doesn\'t match e-mail in profile');
            return false;
        }
        $new_password = rcms_random_string(8);
        $site_url = parse_url($this->url);
        $time = time();
        if (!empty($data['last_prr']) && !empty($this->config['pr_flood']) && (int) $time <= ((int) $data['last_prr'] + (int) $this->config['pr_flood'])) {
            $this->results['passrec'] = __('Too many requests in limited period of time. Try later.');
            $data['last_prr'] = time();
            if (!file_write_contents(USERS_PATH . $username, serialize($data))) {
                $this->results['passrec'] .= '<br />' . __('Cannot save profile');
            }
            rcms_log_put('Notification', $this->user['username'], 'Attempted to recover password for ' . $username);
            return false;
        }

        if (rcms_send_mail($email, 'no_reply@' . $site_url['host'], __('Password'), $this->config['encoding'], __('Your new password at') . ' ' . $site_url['host'], __('Your username at') . ' ' . $site_url['host'] . ': ' . $username . "\r\n" . __('Your new password at') . ' ' . $site_url['host'] . ': ' . $new_password)) {
            $data['password'] = md5($new_password);
            $data['last_prr'] = $time;
            if (!file_write_contents(USERS_PATH . $username, serialize($data))) {
                $this->results['passrec'] = __('Cannot save profile');
                return false;
            }
            $this->results['passrec'] = __('New password has been sent to your e-mail');
            rcms_log_put('Notification', $this->user['username'], 'Recovered password for ' . $username);
            return true;
        } else {
            rcms_log_put('Notification', $this->user['username'], 'Recovered password for ' . $username . '" (BUT E-MAIL WAS NOT SENT)');
            $this->results['passrec'] = __('Cannot send e-mail');
            return false;
        }
    }

    function getUserData($username) {
        $result = @unserialize(@file_get_contents(USERS_PATH . basename($username)));
        if (empty($result))
            return false;
        else
            return $result;
    }

    function getUserList($expr = '*', $id_field = '') {
        $return = array();
        $users = rcms_scandir(USERS_PATH, $expr);
        foreach ($users as $user) {
            if ($data = $this->getUserData($user)) {
                if (!empty($id_field) && !empty($data[$id_field])) {
                    $return[$data[$id_field]] = $data;
                } else {
                    $return[] = $data;
                }
            }
        }
        return $return;
    }

    function changeProfileField($username, $field, $value) {
        $username = basename($username);
        if (!($userdata = $this->getUserData($username)))
            return false;
        $userdata[$field] = $value;
        if (!file_write_contents(USERS_PATH . $username, serialize($userdata)))
            return false;
        return true;
    }

    function deleteUser($username) {
        $username = basename($username);
        if (!rcms_delete_files(USERS_PATH . $username))
            return false;
        user_remove_from_cache($username, $cache);
        return true;
    }

    function createLink($user, $nick, $target = '') {
        if (!empty($target))
            $target = ' target="' . $target . '"';
        if ($user != 'guest') {
            return '<a href="' . RCMS_ROOT_PATH . '?module=user.list&amp;user=' . $user . '"' . $target . '>' . strip_tags($nick) . '</a>';
        } elseif (!empty($nick)) {
            return $nick;
        } else {
            return __('Guest');
        }
    }

}

?>