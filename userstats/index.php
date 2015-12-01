<?php

	// LOAD LIBS:
	include('modules/engine/api.mysql.php');
	include('modules/engine/api.compat.php');
 	include('modules/engine/api.lightastral.php');
	include('modules/engine/api.userstats.php');
        include('modules/engine/api.agents.php');
        include('modules/engine/api.megogo.php');

	$db = new MySQLDB();

	// ACTIONS HANDER:
	$user_ip    = zbs_UserDetectIp('debug');
	$user_login = zbs_UserGetLoginByIp($user_ip);
	$us_config  = zbs_LoadConfig();
	$us_access  = zbs_GetUserStatsDeniedAll();
	
	if ( !empty($us_access) ) {
		if ( isset($us_access[$user_login]) ) {
			die('Access denied');
		}
	}
	
	if ( $user_ip ) {
		if ( $us_config['auth']=='login' ) {
			// IF ALREADY SIGNED:
			zbs_LogoutForm();
		}
		
		if ( !isset($_GET['module']) ) {
			if ( $us_config['UBA_ENABLED'] ) {
				// UBAgent SUPPORT:
				if ( isset($_GET['ubagent']) ) {
					zbs_UserShowAgentData($user_login);
				}
                                
                                // XMLAgent SUPPORT:
				if ( isset($_GET['xmlagent']) ) {
					zbs_UserShowXmlAgentData($user_login);
				}
			}
                        //announcements notice
                        if (isset($us_config['AN_ENABLED'])) {
                            if ($us_config['AN_ENABLED']) {
                                zbs_AnnouncementsNotice();
                            }
                        }
                        
                        //shows user profile by default
			show_window(__('User profile'), zbs_UserShowProfile($user_login));
                        
		} else zbs_LoadModule($_GET['module']);
	} else {
		if ( $us_config['auth']=='login' ) {
			zbs_LoginForm();
		}
	}
	
	// LOAD TEMPLATE:
	zbs_ShowTemplate();

?>
