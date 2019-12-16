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


class rcms_system extends rcms_user {

    var $language = '';
    var $skin = '';
    var $config = array();
    var $results = array();
    var $data = array();
    var $modules = array();
    var $feeds = array();
    var $cookie_lang = 'reloadcms_lang';
    var $cookie_skin = 'reloadcms_skin';
    var $output = array('modules' => array(), 'menus' => array());
    var $current_point = '';
    var $logging = LOGS_PATH;
    var $logging_gz = true;
    var $url = '';

    public function __construct($language_select_form = '', $skin_select_form = '') {
        global $lang;

        // Loading configuration
        $this->config = parse_ini_file(CONFIG_PATH . 'config.ini');
        if (empty($this->config['site_url'])) {
            $this->url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'] . basename($_SERVER['SCRIPT_NAME'])) . '/';
        } else {
            $this->url = $this->config['site_url'];
        }
        if (substr($this->url, -1) != '/') {
            $this->url .= '/';
        }
        $this->language = $this->config['default_lang'];
        $this->skin = $this->config['default_skin'];
        $this->initialiseLanguage(basename($language_select_form));
        $this->logging_gz = extension_loaded('zlib');
        if (!empty($this->config['allowchskin'])) {
            if (!empty($_COOKIE[$this->cookie_skin]) && is_file(SKIN_PATH . basename($_COOKIE[$this->cookie_skin]) . '/skin.general.php'))
                $this->skin = basename($_COOKIE[$this->cookie_skin]);
            if (!empty($skin_select_form) && is_file(SKIN_PATH . basename($skin_select_form) . '/skin.general.php')) {
                $this->skin = $skin_select_form;
                setcookie($this->cookie_skin, basename($skin_select_form), FOREVER_COOKIE);
            }
        }
        define('CUR_SKIN_PATH', SKIN_PATH . $this->skin . '/');
        $this->initialiseModules();
        $this->initializeUser();
    }

    function initialiseLanguage($language = '', $default = false) {
        global $lang;

        // Loading avaible languages lists
        $langs = rcms_scandir(LANG_PATH);
        foreach ($langs as $lng) {
            if (is_dir(LANG_PATH . $lng) && is_file(LANG_PATH . $lng . '/langid.txt')) {
                $lngdata = file(LANG_PATH . $lng . '/langid.txt');
                $this->data['languages'][preg_replace("/[\n\r]+/", '', $lngdata[1])] = preg_replace("/[\n\r]+/", '', $lngdata[0]);
                $this->data['langpath'][preg_replace("/[\n\r]+/", '', $lngdata[1])] = LANG_PATH . $lng . '/';
            }
        }

        if (!empty($this->config['allowchlang']) && !$default) {
            if (!empty($language) && !empty($this->data['languages'][$language])) {
                $this->language = $language;
                setcookie($this->cookie_lang, $language, FOREVER_COOKIE);
                $_COOKIE[$this->cookie_lang] = $language;
            } elseif (!empty($_COOKIE[$this->cookie_lang]) && !empty($this->data['languages'][basename($_COOKIE[$this->cookie_lang])])) {
                $this->language = basename($_COOKIE[$this->cookie_lang]);
            } else {
                if (!empty($this->config['detect_lang']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                    $lang_priority = explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
                    $lang_priority = explode(',', $lang_priority[0]);
                } else
                    $lang_priority = array();

                foreach ($lang_priority as $lng) {
                    if ($this->language == $this->config['default_lang'] && !empty($this->data['languages'][basename($lng)])) {
                        $this->language = basename($lng);
                    }
                }
            }
        }

        if (!is_file($this->data['langpath'][$this->language] . 'langid.txt')) {
            die('Language "' . $this->language . '" not found');
        }

        // Loading language files' list
        $lngdir = rcms_scandir($this->data['langpath'][$this->language]);

        // Loading language definition
        $lngdata = file($this->data['langpath'][$this->language] . 'langid.txt');
        $this->config['language'] = preg_replace("/[\n\r]+/", '', $lngdata[1]);
        $this->config['encoding'] = preg_replace("/[\n\r]+/", '', $lngdata[2]);

        // Loading language bindings
        foreach ($lngdir as $langfile) {
            if (is_file($this->data['langpath'][$this->language] . $langfile) && $langfile !== 'langid.txt') {
                include_once($this->data['langpath'][$this->language] . $langfile);
            }
        }
    }

    function initialiseModules($ignore_disable = false) {
        // Loading modules initializations
        if (!$ignore_disable) {
            if (!$disabled = @parse_ini_file(CONFIG_PATH . 'disable.ini')) {
                $disabled = array();
            }
        } else {
            $disabled = array();
        }
        $modules = rcms_scandir(MODULES_PATH);
        foreach ($modules as $module) {
            if (empty($disabled[$module]) && is_readable(MODULES_PATH . $module . '/module.php')) {
                include_once(MODULES_PATH . $module . '/module.php');
            }
        }
        // Register modules rights in main database
        foreach ($this->modules as $type => $modules) {
            foreach ($modules as $module => $moduledata) {
                foreach ($moduledata['rights'] as $right => $desc) {
                    $this->rights_database[$right] = $desc;
                }
            }
        }
    }

    function addInfoToHead($info) {
        $this->config['meta'] = @$this->config['meta'] . $info;
    }

    function setCurrentPoint($point) {
        $this->current_point = $point;
    }

    function defineWindow($title, $data, $align = 'left') {
        if ($title == __('Error')) {
            $title = '<font color="red">' . $title . '</font>';
        }
        if ($this->current_point == '__MAIN__') {
            $this->output['modules'][] = array($title, $data, $align);
        } elseif (!empty($this->current_point)) {
            $this->output['menus'][$this->current_point][] = array($title, $data, $align);
        } else
            return false;
    }

    function showWindow($title, $content, $align, $template) {
        if ($title == '__NOWINDOW__')
            echo $content;
        elseif (is_readable($template))
            require($template);
        else
            return false;
        return true;
    }

    function registerModule($module, $type, $title, $copyright = '', $rights = array()) {
        $this->modules[$type][$module]['title'] = $title;
        $this->modules[$type][$module]['copyright'] = $copyright;
        $this->modules[$type][$module]['rights'] = $rights;
    }

    function registerFeed($module, $title, $desc, $real = '') {
        $this->feeds[$module] = array($title, $desc, $real);
    }

    function logPut($type, $user, $message) {
        if (!empty($this->config['logging'])) {
            $entry = '---------------------------------' . "\n";
            $entry .= date('H:i:s d-m-Y', time()) . "\n";
            $entry .= $type . ' (' . $user . ' from ' . $_SERVER['REMOTE_ADDR'] . ')' . "\n";
            $entry .= $message . "\n";
            if ($this->logging_gz) {
                gzfile_write_contents($this->logging . date('Y-m-d', time()) . '.log.gz', $entry, 'a');
            } else {
                file_write_contents($this->logging . date('Y-m-d', time()) . '.log', $entry, 'a');
            }
        }
        return true;
    }

    function logMerge($title, $t_d, $t_m, $t_y, $f_d = 1, $f_m = 1, $f_y = 1980) {
        $logs = rcms_scandir($this->logging);
        $f = mktime(0, 0, 0, $f_m, $f_d, $f_y);
        $t = mktime(0, 0, 0, $t_m, $t_d, $t_y);
        $to_merge = array();
        foreach ($logs as $log_entry) {
            if (preg_match("/^(.*?)-(.*?)-(.*?)\.log(|.gz)$/i", $log_entry, $matches)) {
                $c = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
                if ($c >= $f && $c <= $t) {
                    $to_merge[] = $log_entry;
                }
            }
        }
        if (!empty($to_merge)) {
            if ($this->logging_gz)
                $suffix = '.gz';
            else
                $suffix = '';
            $merged_file = $this->logging . $title . '.tar' . $suffix;
            $merged = new tar();
            $merged->isGzipped = $this->logging_gz;
            $merged->filename = $merged_file;
            $path = getcwd();
            chdir($this->logging);
            foreach ($to_merge as $file) {
                $merged->addFile($file, substr($file, -3) == '.gz');
            }
            chdir($path);
            if ($merged->saveTar()) {
                foreach ($to_merge as $file) {
                    rcms_delete_files($this->logging . $file);
                }
            }
        }
        return true;
    }

    function logMergeByMonth() {
        $logs = rcms_scandir($this->logging);
        $d = date('d');
        $m = date('m');
        $Y = date('Y');
        $merged = array();
        foreach ($logs as $log_entry) {
            if (preg_match("/^(.*?)-(.*?)-(.*?)\.log(|.gz)$/i", $log_entry, $matches)) {
                $t = date('t', mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]));
                if (!in_array($matches[1] . '-' . $matches[2], $merged) && ($matches[2] != $m || $matches[1] != $Y)) {
                    $this->logMerge($matches[1] . '-' . $matches[2], $t, $matches[2], $matches[1], 1, $matches[2], $matches[1]);
                    $merged[] = $matches[1] . '-' . $matches[2];
                }
            }
        }
        return true;
    }

    var $navmodifiers = array();

    function registerNavModifier($base, $mod_handler, $help_handler) {
        $this->navmodifiers[$base] = array('m' => $mod_handler, 'h' => $help_handler);
        return true;
    }

}

function __($string) {
    global $lang;
    if (!empty($lang['def'][$string])) {
        return $lang['def'][$string];
    } else {
        return $string;
    }
}

function rcms_log_put($type, $user, $message) {
    global $system;
    return $system->logPut($type, $user, $message);
}

function cut_text($str, $lenght = 25) {
    $str = substr($str, 0, $lenght) . ((strlen($str) > $lenght) ? '...' : '');
    return ($str);
}

?>