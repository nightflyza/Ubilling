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

    if (!zbs_IsLanguageSelectable($lang, $statsconfig)) {
        $lang = $statsconfig['lang'];
    }
}

setcookie("zbs_lang", $lang, time() + 2592000);
$langglobal = zbs_LoadLang($lang);

//if enabled login based auth
if ($statsconfig['auth'] == 'login' or $statsconfig['auth'] == 'both') {
    if (ubRouting::checkPost(array('ulogin', 'upassword'))) {
        $loginRaw = ubRouting::post('ulogin');
        $passwordRaw = ubRouting::post('upassword');
        if (is_string($loginRaw) and is_string($passwordRaw)) {
            $loginFiltered = ubRouting::filters($loginRaw, 'login');
            if ($loginFiltered === $loginRaw and (!empty($loginFiltered))) {
                $passwordHash = md5($passwordRaw);
                setcookie("ulogin", $loginFiltered, time() + 2592000);
                setcookie("upassword", $passwordHash, time() + 2592000);
                rcms_redirect("index.php");
            }
        }
    }

    if (isset($_POST['ulogout'])) {
        setcookie("upassword", 'nopassword', time() + 2592000);
        rcms_redirect("index.php");
    }
}
