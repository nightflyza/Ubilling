<?php

error_reporting(E_ALL);

/**
 * Page generation time counters begins
 */
$pageGenStartTime = explode(' ', microtime());
$pageGenStartTime = $pageGenStartTime[1] + $pageGenStartTime[0];

// Send main headers
header('Last-Modified: ' . date('r'));
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");


// LOAD LIBS:
define('USERSTATS_LIBS_PATH', 'modules/engine/');
require_once(USERSTATS_LIBS_PATH . 'api.mysql.php');
require_once(USERSTATS_LIBS_PATH . 'api.compat.php');
require_once(USERSTATS_LIBS_PATH . 'api.userstatsinit.php');

$userstatsEngineLibs = rcms_scandir(USERSTATS_LIBS_PATH, '*.php');
if (!empty($userstatsEngineLibs)) {
    foreach ($userstatsEngineLibs as $io => $eachUserstatLib) {
        require_once(USERSTATS_LIBS_PATH . $eachUserstatLib);
    }
}


// ACTIONS HANDLING:
$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();
$us_access = zbs_GetUserStatsDeniedAll();

if (!empty($us_access) and !empty($user_login)) {
    if (isset($us_access[$user_login])) {
        $accDeniedBody = file_get_contents('modules/jsc/youshallnotpass.html');
        die($accDeniedBody);
    }
}

//web app manifest rendering
zbs_ManifestCatchRequest();


if ($user_ip) {
    if (!ubRouting::checkGet('module')) {
        if ($us_config['UBA_ENABLED']) {
            // UBAgent SUPPORT:
            if (ubRouting::checkGet('ubagent')) {
                zbs_UserShowAgentData($user_login);
            }

            // XMLAgent SUPPORT:
            if (ubRouting::checkGet('xmlagent')) {
                new XMLAgent($user_login);
            }
        } else {
            //REST API disabled by configuration
            if (ubRouting::checkGet('xmlagent')) {
                $errorOutputFormat = 'xml';
                if (ubRouting::checkGet('json')) {
                    $errorOutputFormat = 'json';
                }
                XMLAgent::renderResponse(array(array('reason' => 'disabled')), 'error', '', $errorOutputFormat);
            }
        }

        //announcements notice
        if (isset($us_config['AN_ENABLED'])) {
            if ($us_config['AN_ENABLED']) {
                zbs_AnnouncementsNotice($user_login);
            }
        }

        //top intro
        if (isset($us_config['INTRO_MODE'])) {
            if ($us_config['INTRO_MODE'] == '3') {
                show_window('', zbs_IntroLoadText());
            }
        }

        //Aerial alerts notification
        if (@$us_config['AIR_RAID_ALERT_ENABLED']) {
            zbs_AerialAlertNotification();
        }
        //shows user profile by default
        show_window(__('User profile'), zbs_UserShowProfile($user_login));
        // load poll form
        if ($us_config['POLLS_ENABLED']) {
            $poll = new Polls($user_login);
            if (ubRouting::checkPost(array('vote', 'poll_id'))) {
                $poll->createUserVoteOnDB(ubRouting::post('vote', 'int'), ubRouting::post('poll_id', 'int'));
            }
            show_window('', $poll->renderVotingForm());
        }
        //bottom intro
        if (isset($us_config['INTRO_MODE'])) {
            if ($us_config['INTRO_MODE'] == '1') {
                show_window('', zbs_IntroLoadText());
            }
        }
    } else {
        zbs_LoadModule(ubRouting::get('module'));
    }
    //render logout form if user already signed in
    if (isset($us_config['INLINE_LOGOUT']) and $us_config['INLINE_LOGOUT']) {
        if ($us_config['auth'] == 'login' or $us_config['auth'] == 'both') {
            zbs_LogoutForm();
        }
    }
} else {
    if ($us_config['auth'] == 'login' or $us_config['auth'] == 'both') {
        zbs_LoginForm();
        //bottom auth intro
        if (isset($us_config['INTRO_MODE'])) {
            if ($us_config['INTRO_MODE'] == '4') {
                show_window('', zbs_IntroLoadText());
            }
        }
    }
}


// LOAD TEMPLATE:
zbs_ShowTemplate();
