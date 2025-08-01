<?php


/**
 * Returns userstats config as array
 * 
 * @return array
 */
function zbs_LoadConfig() {
    $config = parse_ini_file('config/userstats.ini');
    return ($config);
}

/**
 * Loads required locale lang and returns array of loalized strings
 * 
 * @param string  $language
 * 
 * @return array
 */
function zbs_LoadLang($language) {
    $language = preg_replace('/\0/s', '', $language);
    $language = preg_replace('/[^a-zA-Z0-9_]/', '', $language);
    $availableLanguages = rcms_scandir('languages/');
    $availableLanguages = array_flip($availableLanguages);
    if (isset($availableLanguages[$language])) {
        if (file_exists('languages/' . $language . '/lang.php')) {
            include('languages/' . $language . '/lang.php');
            //additional locale
            if (file_exists('languages/' . $language . '/addons.php')) {
                include('languages/' . $language . '/addons.php');
            }
        } else {
            include('languages/english/lang.php');
        }
    } else {
        include('languages/english/lang.php');
    }
    return ($lang);
}

/**
 * Returns localized string by current lang
 * 
 * @global string $langglobal
 * @param string $str
 * @return  string
 */
function __($str) {
    global $langglobal;
    if ((isset($langglobal['def'][$str])) and (!empty($langglobal['def'][$str]))) {
        return ($langglobal['def'][$str]);
    } else {
        return ($str);
    }
}

/**
 * Returns current skin path
 * 
 * @param array $usConfig preloaded usrstats config as array
 * 
 * @return string
 */
function zbs_GetCurrentSkinPath($usConfig = array()) {
    if (empty($usConfig)) {
        $usConfig = zbs_LoadConfig();
    }
    $basePath = 'skins/';
    $skinName = 'default';
    if (isset($usConfig['SKIN'])) {
        $skinName = $usConfig['SKIN'];
    }
    $result = $basePath . $skinName . '/';
    return ($result);
}

/**
 * Renders default userstats template
 * 
 * @global string $ContentContainer
 */
function zbs_ShowTemplate() {
    global $ContentContainer;
    $skinPath = zbs_GetCurrentSkinPath();
    if (file_exists($skinPath)) {
        include($skinPath . 'template.html');
    } else {
        print('Skin path not exists: ' . $skinPath);
    }
}

/**
 * Shows data in primary content container
 * 
 * @global string $ContentContainer
 * @param string $title
 * @param string $data
 */
function show_window($title, $data) {
    global $ContentContainer;
    $window_content = '
        <table width="100%" border="0">
        <tr>
        <td><h2>' . @$title . '</h2></td>
        </tr>
        <tr>
        <td valign="top">
        ' . @$data . '
        </td>
        </tr>
        </table>
        ';
    $ContentContainer = $ContentContainer . $window_content;
}

/**
 * Default debug output
 * 
 * @param string $data
 */
function deb($data) {
    show_window('DEBUG', $data);
}

/**
 * Default array debug output
 * 
 * @param array $data
 */
function debarr($data) {
    show_window('DEBUG', '<pre>' . print_r($data, true) . '</pre>');
}

/**
 * Returns array of files in selected directory
 * 
 * @param string $directory
 * @param string $exp
 * @param string $type
 * @param bool $do_not_filter
 * @return array
 */
function rcms_scandir($directory, $exp = '', $type = 'all', $do_not_filter = false) {
    $dir = $ndir = array();
    if (!empty($exp)) {
        $exp = '/^' . str_replace('*', '(.*)', str_replace('.', '\\.', $exp)) . '$/';
    }
    if (!empty($type) && $type !== 'all') {
        $func = 'is_' . $type;
    }
    if (is_dir($directory)) {
        $fh = opendir($directory);
        while (false !== ($filename = readdir($fh))) {
            if (substr($filename, 0, 1) != '.' || $do_not_filter) {
                if ((empty($type) || $type == 'all' || $func($directory . '/' . $filename)) && (empty($exp) || preg_match($exp, $filename))) {
                    $dir[] = $filename;
                }
            }
        }
        closedir($fh);
        natsort($dir);
    }
    return $dir;
}

/**
 * Loads some general module by its name
 * 
 * @param string $modulename
 */
function zbs_LoadModule($modulename) {
    $modCheck = false;
    if (!empty($modulename) and is_string($modulename)) {
        $modulename = preg_replace('/\0/s', '', $modulename);
        $modulename = preg_replace('/[^a-zA-Z0-9_]/', '', $modulename);
        $module_path = 'modules/general/';
        if (!empty($modulename) and is_string($modulename)) {
            $loadableModules = rcms_scandir($module_path);
            $loadableModules = array_flip($loadableModules);
            if (isset($loadableModules[$modulename])) {
                if (file_exists($module_path . $modulename . '/index.php')) {
                    $modCheck = true;
                }
            }
        }
    }

    if ($modCheck) {
        include($module_path . $modulename . '/index.php');
    } else {
        die('Wrong module');
    }
}

/**
 * Returns current date and time in mysql DATETIME format
 * 
 * @return string
 */
function curdatetime() {
    $currenttime = date("Y-m-d H:i:s");
    return ($currenttime);
}

/**
 * Returns random string with some length
 * 
 * @param int $size
 * @return string
 */
function zbs_rand_string($size = 4) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = "";
    for ($p = 0; $p < $size; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters) - 1))];
    }

    return ($string);
}

/**
 * Pushes default file-download subroutine
 * 
 * @param string $filePath
 * @param string $contentType
 * @throws Exception
 */
function zbs_DownloadFile($filePath, $contentType = '') {
    if (!empty($filePath)) {
        if (file_exists($filePath)) {
            $fileContent = file_get_contents($filePath);
            log_register("DOWNLOAD FILE `" . $filePath . "`");

            if (($contentType == '') or ($contentType == 'default')) {
                $contentType = 'application/octet-stream';
            } else {
                //additional content types
                if ($contentType == 'docx') {
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                }
            }

            header('Content-Type: ' . $contentType);
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . basename($filePath) . "\"");
            header("Content-Description: File Transfer");
            header("Content-Length: " . filesize($filePath));

            die($fileContent);
        } else {
            throw new Exception('DOWNLOAD_FILEPATH_NOT_EXISTS');
        }
    } else {
        throw new Exception('DOWNLOAD_FILEPATH_EMPTY');
    }
}

/**
 * Converts IP to integer value
 * 
 * @param string $src
 * 
 * @return int
 */
function ip2int($src) {
    $t = explode('.', $src);
    return count($t) != 4 ? 0 : 256 * (256 * ((float) $t[0] * 256 + (float) $t[1]) + (float) $t[2]) + (float) $t[3];
}

/**
 * Converts integer into IP
 * 
 * @param int $src
 * 
 * @return string
 */
function int2ip($src) {
    $s1 = (int) ($src / 256);
    $i1 = $src - 256 * $s1;
    $src = (int) ($s1 / 256);
    $i2 = $s1 - 256 * $src;
    $s1 = (int) ($src / 256);
    return sprintf('%d.%d.%d.%d', $s1, $src - 256 * $s1, $i2, $i1);
}

/**
 * Checks for substring in string
 *
 * @param string $string
 * @param string $search
 *
 * @return bool
 */
function ispos($string, $search) {
    if (strpos($string, $search) === false) {
        return (false);
    } else {
        return (true);
    }
}

/**
 * Pushes native redirect
 * 
 * @param string $url
 * @param bool $header
 */
function rcms_redirect($url, $header = false) {
    if ($header) {
        @header('Location: ' . $url);
    } else {
        print('<script language="javascript">document.location.href="' . $url . '";</script>');
    }
}
