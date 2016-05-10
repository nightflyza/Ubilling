<?php

// Send main headers
header('Last-Modified: ' . date('r'));
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

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

$ContentContainer = '';
$statsconfig = zbs_LoadConfig();


// set default lang
$lang = $statsconfig['lang'];

if ($statsconfig['allowclang']) { // if language change is allowed
    if (isset($_GET['changelang'])) {
        $lang = $_GET['changelang'];
    } else {
        if (isset($_COOKIE['zbs_lang'])) {
            $lang = $_COOKIE['zbs_lang'];
        }
    }
}

setcookie("zbs_lang", $lang, time() + 2592000);
$langglobal = zbs_LoadLang($lang);

//setting auth cookies subroutine
if ($statsconfig['auth'] == 'login') { //if enabled login based auth
    if ((isset($_POST['ulogin'])) AND isset($_POST['upassword'])) {
        $ulogin = trim(vf($_POST['ulogin']));
        $upassword = trim(vf($_POST['upassword']));
        $upassword = md5($upassword);
        setcookie("ulogin", $ulogin, time() + 2592000);
        setcookie("upassword", $upassword, time() + 2592000);
        rcms_redirect("index.php");
    }

    if (isset($_POST['ulogout'])) {
        setcookie("upassword", 'nopassword', time() + 2592000);
        rcms_redirect("index.php");
    }
}

//mark announcements as read/unread
if (isset($_GET['anmarkasread'])) {
    $anReadId = vf($_GET['anmarkasread']);
    setcookie("zbsanread_" . $anReadId, $anReadId, time() + 31104000);
    rcms_redirect('?module=announcements');
}
if (isset($_GET['anmarkasunread'])) {
    $anUnreadId = vf($_GET['anmarkasunread']);
    setcookie("zbsanread_" . $anUnreadId, '', time() - 3600);
    rcms_redirect('?module=announcements');
}

/**
 * Returns userstats config as array
 * 
 * @return array
 */
function zbs_LoadConfig() {
    $config = parse_ini_file('config/userstats.ini');
    return($config);
}

/**
 * Loads required locale lang and returns array of loalized strings
 * 
 * @param type $language
 * @return array
 */
function zbs_LoadLang($language) {
    $language = vf($language);
    $language = preg_replace('/\0/s', '', $language);
    if (file_exists('languages/' . $language . '/lang.php')) {
        include('languages/' . $language . '/lang.php');
        //additional locale
        if (file_exists('languages/' . $language . '/addons.php')) {
            include('languages/' . $language . '/addons.php');
        }
    } else {
        include('languages/english/lang.php');
    }
    return($lang);
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
    if ((isset($langglobal['def'][$str])) AND ( !empty($langglobal['def'][$str]))) {
        return ($langglobal['def'][$str]);
    } else {
        return($str);
    }
}

/**
 * Renders default userstats template
 * 
 * @global string $ContentContainer
 */
function zbs_ShowTemplate() {
    global $ContentContainer;
    include ('template.html');
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
    $modulename = vf($modulename);
    $modulename = preg_replace('/\0/s', '', $modulename);
    $module_path = 'modules/general/';
    if (file_exists($module_path . $modulename . '/index.php')) {
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
    return($currenttime);
}

/**
 * Converts string IP address into integer
 * 
 * @param string $src
 * @return int
 */
function ip2int($src) {
    $t = explode('.', $src);
    return count($t) != 4 ? 0 : 256 * (256 * ((float) $t[0] * 256 + (float) $t[1]) + (float) $t[2]) + (float) $t[3];
}

/**
 * Converts integer into IP address
 * 
 * @param int $src
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
 * Returns random string with some length
 * 
 * @param int $size
 * @return string
 */
function zbs_rand_string($size = 4) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = "";
    for ($p = 0; $p < $size; $p++) {
        $string.= $characters[mt_rand(0, (strlen($characters) - 1))];
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

            if (($contentType == '') OR ( $contentType == 'default')) {
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

?>
