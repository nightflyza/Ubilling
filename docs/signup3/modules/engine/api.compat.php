<?php

/**
 * signup3 compatibility helpers
 */

/**
 * Redirect helper
 *
 * @param string $url
 * @param bool $header
 *
 * @return void
 */
function rcms_redirect($url, $header = false) {
    if ($header) {
        @header('Location: ' . $url);
    } else {
        print('<script type="text/javascript">document.location.href="' . $url . '";</script>');
    }
}

/**
 * Loads signup.ini
 *
 * @return array
 */
function sn_LoadConfig() {
    $path = 'config/signup.ini';
    $result = parse_ini_file($path);
    if (empty($result)) {
        $result = array();
    }
    return ($result);
}

/**
 * Filters language name to letters only
 *
 * @param string $data
 *
 * @return string
 */
function sn_FilterLang($data) {
    $result = preg_replace('/\0/s', '', $data);
    $result = preg_replace('#[^a-zA-Z]#Uis', '', $result);
    return ($result);
}

/**
 * Loads language file
 *
 * @param string $language
 *
 * @return array
 */
function sn_LoadLang($language) {
    $language = sn_FilterLang($language);
    $lang = array();
    if ($language == 'ukrainian') {
        if (file_exists('languages/ukrainian/lang.php')) {
            include('languages/ukrainian/lang.php');
        }
    } else {
        if (file_exists('languages/english/lang.php')) {
            include('languages/english/lang.php');
        }
    }
    if (!isset($lang['def'])) {
        $lang['def'] = array();
    }
    return ($lang);
}

$ContentContainer = '';
$snConfig = sn_LoadConfig();
$lang = 'english';
if (isset($snConfig['lang'])) {
    $lang = $snConfig['lang'];
}
$langglobal = sn_LoadLang($lang);
$templateData = array();
$templateData['DEBUG_GT'] = '';
$templateData['HTML_LANG'] = 'en';
if ($lang == 'ukrainian') {
    $templateData['HTML_LANG'] = 'uk';
}

/**
 * Appends a line to cache/debug.log when debug=1
 *
 * @param string $message
 *
 * @return void
 */
function sn_DebugLog($message) {
    global $snConfig;
    $enabled = false;
    if (isset($snConfig['debug'])) {
        if ($snConfig['debug']) {
            $enabled = true;
        }
    }
    if ($enabled) {
        $pid = getmypid();
        $line = date('Y-m-d H:i:s') . ' [' . $pid . '] ' . $message . PHP_EOL;
        file_put_contents('cache/debug.log', $line, FILE_APPEND);
    }
}

/**
 * i18n helper
 *
 * @param string $str
 *
 * @return string
 */
function __($str) {
    global $langglobal;
    $result = $str;
    if (isset($langglobal['def'][$str])) {
        if (!empty($langglobal['def'][$str])) {
            $result = $langglobal['def'][$str];
        }
    }
    return ($result);
}

/**
 * Returns select2 language code
 *
 * @return string
 */
function curlang() {
    global $lang;
    $result = 'en';
    if ($lang == 'ukrainian') {
        $result = 'uk';
    }
    return ($result);
}

/**
 * Renders main template
 *
 * @return void
 */
function sn_ShowTemplate() {
    global $ContentContainer;
    global $templateData;
    include('template/template.html');
}

/**
 * Appends a content window
 *
 * @param string $title
 * @param string $data
 *
 * @return void
 */
function show_window($title, $data, $extraClass = '') {
    global $ContentContainer;
    $winClass = 'sn-window';
    if ($extraClass != '') {
        $winClass .= ' ' . $extraClass;
    }
    $result = wf_tag('section', false, $winClass);
    if (!empty($title)) {
        $result .= wf_tag('h2') . $title . wf_tag('h2', true);
    }
    $result .= wf_tag('div', false, 'sn-window-body') . $data . wf_tag('div', true);
    $result .= wf_tag('section', true);
    $ContentContainer .= $result;
}

/**
 * Required-fields hint with a red asterisk matching form labels
 *
 * @return string
 */
function sn_RequiredHint() {
    $star = '(' . wf_tag('span', false, 'sn-req') . '*' . wf_tag('span', true) . ')';
    $result = __('All fields marked with an asterisk') . ' ' . $star . ' ' . __('are required');
    return ($result);
}
