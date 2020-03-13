<?php

//error_reporting(E_ALL);
// LOAD LIBS:
include('modules/engine/api.mysql.php');
include('modules/engine/api.compat.php');
include('modules/engine/api.lightastral.php');
include('modules/engine/api.userstats.php');
include('modules/engine/api.agents.php');
include('modules/engine/api.megogo.php');
include('modules/engine/api.polls.php');
include('modules/engine/api.extmobiles.php');
include('modules/engine/api.omegatv.php');
include('modules/engine/api.trinitytv.php');


// ACTIONS HANDLING:
$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();
$us_access = zbs_GetUserStatsDeniedAll();

if (!empty($us_access)) {
    if (isset($us_access[$user_login])) {
        die('Access denied');
    }
}

if ($user_ip) {


    if (!isset($_GET['module'])) {
        if ($us_config['UBA_ENABLED']) {
            // UBAgent SUPPORT:
            if (isset($_GET['ubagent'])) {
                zbs_UserShowAgentData($user_login);
            }

            // XMLAgent SUPPORT:
            if (isset($_GET['xmlagent'])) {
                zbs_UserShowXmlAgentData($user_login);
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

// LOAD TEMPLATE:
zbs_ShowTemplate();
?>
