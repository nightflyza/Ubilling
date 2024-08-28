<?php

$ContentContainer = '';
$statsconfig = zbs_LoadConfig();
// set default lang
$lang = $statsconfig['lang'];

// if language change is allowed
if ($statsconfig['allowclang']) {
    if (isset($_GET['changelang'])) {
        $lang = $_GET['changelang'];
    } else {
        if (isset($_COOKIE['zbs_lang'])) {
            $lang = $_COOKIE['zbs_lang'];
        }
    }

    if (is_string($lang)) {
        $lang = vf($lang);
    } else {
        $lang = $statsconfig['lang'];
    }
}

setcookie("zbs_lang", $lang, time() + 2592000);
$langglobal = zbs_LoadLang($lang);

//if enabled login based auth
if ($statsconfig['auth'] == 'login' or $statsconfig['auth'] == 'both') {
    if ((isset($_POST['ulogin'])) and isset($_POST['upassword'])) {
        if (is_string($_POST['ulogin']) and is_string($_POST['upassword'])) {
            //setting auth cookies subroutine
            $ulogin = trim(vf($_POST['ulogin']));
            $upassword = trim(vf($_POST['upassword']));
            $upassword = md5($upassword);
            setcookie("ulogin", $ulogin, time() + 2592000);
            setcookie("upassword", $upassword, time() + 2592000);
            rcms_redirect("index.php");
        }
    }

    if (isset($_POST['ulogout'])) {
        setcookie("upassword", 'nopassword', time() + 2592000);
        rcms_redirect("index.php");
    }
}
