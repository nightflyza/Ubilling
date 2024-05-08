<?php

error_reporting(E_ALL);

/**
 * Page generation time counters begins
 */
$pageGenStartTime = explode(' ', microtime());
$pageGenStartTime = $pageGenStartTime[1] + $pageGenStartTime[0];


// LOAD LIBS:
require_once('modules/engine/api.mysql.php');
require_once('modules/engine/api.compat.php');
require_once('modules/engine/api.lightastral.php');
require_once('modules/engine/api.usconfig.php');
require_once('modules/engine/api.xmlagent.php');
require_once('modules/engine/api.userstats.php');
require_once('modules/engine/api.agents.php');
require_once('modules/engine/api.megogo.php');
require_once('modules/engine/api.polls.php');
require_once('modules/engine/api.extmobiles.php');
require_once('modules/engine/api.ubrouting.php');
require_once('modules/engine/api.nyanorm.php');
require_once('modules/engine/api.omegatv.php');
require_once('modules/engine/api.trinitytv.php');
require_once('modules/engine/api.usreminder.php');


// ACTIONS HANDLING:
$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();
$us_access = zbs_GetUserStatsDeniedAll();

if (!empty($us_access)) {
    if (isset($us_access[$user_login])) {
        $accDeniedBody = file_get_contents('modules/jsc/youshallnotpass.html');
        die($accDeniedBody);
    }
}

if ($user_ip) {
    if (!isset($_GET['module'])) {
        if ($us_config['UBA_ENABLED']) {
            // UBAgent SUPPORT:
            if (ubRouting::checkGet('ubagent')) {
                zbs_UserShowAgentData($user_login);
            }

            // XMLAgent SUPPORT:
            if (ubRouting::checkGet('xmlagent')) {
                //zbs_UserShowXmlAgentData($user_login);
                new XMLAgent($user_login);
            }
        } else {
            //REST API disabled by configuration
            if (ubRouting::checkGet('xmlagent')) {
                $errorOutputFormat = 'xml';
                if (ubRouting::checkGet('json')) {
                    $errorOutputFormat = 'json';
                }
                //zbs_XMLAgentRender(array(array('reason' => 'disabled')), 'error', '', $errorOutputFormat, false);
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
            if (la_CheckPost(array('vote', 'poll_id'))) {
                $poll->createUserVoteOnDB(vf($_POST['vote'], 3), vf($_POST['poll_id'], 3));
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
        zbs_LoadModule($_GET['module']);
    }
    //render logout form if user already signed in
    if (isset($us_config['INLINE_LOGOUT']) AND $us_config['INLINE_LOGOUT']) {
        if ($us_config['auth'] == 'login' OR $us_config['auth'] == 'both') {
            zbs_LogoutForm();
        }
    }
} else {
    if ($us_config['auth'] == 'login' OR $us_config['auth'] == 'both') {
        zbs_LoginForm();
        //bottom auth intro
        if (isset($us_config['INTRO_MODE'])) {
            if ($us_config['INTRO_MODE'] == '4') {
                show_window('', zbs_IntroLoadText());
            }
        }
    }
}

//Page generation timings and query count output
if (isset($us_config['DEBUG_COUNTERS'])) {
    if ($us_config['DEBUG_COUNTERS']) {
        $mtNowTime = explode(' ', microtime());
        $totalPageGenTime = $mtNowTime[0] + $mtNowTime[1] - $pageGenStartTime;
        show_window('', __('GT:') . round($totalPageGenTime, 3) . ' QC: ' . $query_counter);
    }
}

// LOAD TEMPLATE:
zbs_ShowTemplate();
?>
