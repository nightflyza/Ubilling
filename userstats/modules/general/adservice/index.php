<?php

$user_ip	 = zbs_UserDetectIp('debug');
$user_login	 = zbs_UserGetLoginByIp($user_ip);
$us_config	 = zbs_LoadConfig();

if ($us_config['ADSERVICE_ENABLED']) {

    $applyDateType	 = $us_config['ADSERVICE_DATE'];
    $serviceNameData = $us_config['ADSERVICE_NAMES'];
    if (strpos($serviceNameData, ',') === false) {
	$availableServices[] = $serviceNameData;
    } else {
	$availableServices = explode(",", $serviceNameData);
    }
    $serviceCostData = $us_config['ADSERVICE_COST'];
    if (strpos($serviceCostData, ',') === false) {
	$serviceCost[] = $serviceCostData;
    } else {
	$serviceCost = explode(",", $serviceCostData);
    }

    if (isset($us_config['ADSERVICE_CUSTOM_ACCEPT'])) {
	$customAcceptances = $us_config['ADSERVICE_CUSTOM_ACCEPT'];
	if (strpos($customAcceptances, ';') === false) {
	    $customAcceptData[] = $customAcceptances;
	} else {
	    $customAcceptData = explode(";", $customAcceptances);
	}
    }

    /**
     * Find in DB matches with login, note, action and param and returns it if some services is sheduled to activate
     * 
     * @param array $availableServices
     * @param string $login
     * @return array
     */
    function GetAllSheduled($availableServices, $login) {
	$query		 = "SELECT * FROM `dealwithit` WHERE login='" . $login . "'";
	$result		 = array();
	$sheduledData	 = simple_queryall($query);
	if (!empty($sheduledData)) {
	    if (!empty($availableServices)) {
		foreach ($availableServices as $eachService) {
		    $eachData	 = explode(":", $eachService);
		    $serviceTagID[]	 = $eachData[1];
		}
		foreach ($serviceTagID as $eachTagID) {
		    foreach ($sheduledData as $eachData => $eachValue) {
			if ($eachTagID == $eachValue['param']) {
			    $result[] = $eachValue;
			}
		    }
		}
	    }
	}
	return($result);
    }

    /**
     * Find in DB matches with login, note, action and param and returns it if some services is activated
     * 
     * @param array $availableServices
     * @param string $login
     * @return array
     */
    function GetAllActivated($availableServices, $login) {
	$query = "SELECT `tagid` FROM `tags` WHERE ";
	if (!empty($availableServices)) {
	    foreach ($availableServices as $eachService) {
		$eachData	 = explode(":", $eachService);
		$serviceTagID[]	 = $eachData[1];
	    }
	    foreach ($serviceTagID as $eachTagID) {
		reset($serviceTagID);
		if (current($serviceTagID) === $eachTagID) {
		    $query.= "(`login`='" . $login . "' AND `tagid`='" . $eachTagID . "')";
		} else {
		    $query.= " OR (`login`='" . $login . "' AND `tagid`='" . $eachTagID . "')";
		}
	    }
	    $query.= ';';
	    $result = simple_queryall($query);
	    return($result);
	} else {
	    return ('');
	}
    }

    if ($applyDateType == 'nextday') {
	$waitDays = '1 ' . __('day');
    } else {
	$allDays	 = date('t');
	$daysLeft	 = $allDays - date('d');
	if ($daysLeft > 1) {
	    $waitDays = $daysLeft . ' ' . __('days') . ".";
	} else {
	    $waitDays = '1 ' . __('day') . ".";
	}
    }

    /**
     * Check what type of apply date set in config and create suitable date string
     * 
     * @param string $applyDateType
     * @return string
     */
    function GetFullApplyDate() {
	$us_config	 = zbs_LoadConfig();
	$applyDateType	 = $us_config['ADSERVICE_DATE'];
	if ($applyDateType == 'nextday') {
	    $applyDate = date('Y-m-d', strtotime("+1 day", time()));
	} else {
	    $alldays	 = date('t');
	    $curday		 = date('d');
	    $leftdays	 = ($alldays - $curday) + 1;
	    $applyDate	 = date('Y-m-d', strtotime("+" . $leftdays . " day", time()));
	}
	return ($applyDate);
    }

    /**
     * Forms and returns selector with available services
     * 
     * @param array $availableServices
     * @param string $login
     * @return string
     */
    function AdServicesSelector($availableServices, $login) {
	$selectData['-'] = '-';
	if (!empty($availableServices)) {
	    $allSheduled	 = GetAllSheduled($availableServices, $login);
	    $allActivated	 = GetAllActivated($availableServices, $login);
	    foreach ($availableServices as $eachService) {
		$eq		 = false;
		$eachData	 = explode(":", $eachService);
		$serviceName	 = $eachData[0];
		$serviceTagID	 = $eachData[1];
		if (!empty($allSheduledl)) {
		    foreach ($allSheduled as $eachShedule) {
			if ($eachShedule['param'] === $serviceTagID) {
			    $eq = true;
			}
		    }
		}
		if (!empty($allActivated)) {
		    foreach ($allActivated as $eachActivated) {
			if ($eachActivated['tagid'] === $serviceTagID) {
			    $eq = true;
			}
		    }
		}
		if (!$eq) {
		    $selectData[$serviceTagID] = $serviceName;
		}
	    }
	}
	$selector	 = la_Selector('tagid', $selectData, '', '', false);
	$selector.= la_delimiter();
	$selector.= la_CheckInput('agree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
	$selector.= la_delimiter();
	$selector.= la_Submit(__('Order'));
	$form		 = la_Form('', 'POST', $selector);
	return($form);
    }

    /**
     * Forms table service name -> service cost + currency
     * 
     * @param array $serviceCost
     * @param string $currency
     * @return string
     */
    function AdServicesList($serviceCost, $currency) {
	$cell	 = la_TableCell(__('Aditional service name'));
	$cell.= la_TableCell(__('Cost'));
	$rows	 = la_TableRow($cell, 'row1');
	if (!empty($serviceCost)) {
	    foreach ($serviceCost as $allCost) {
		$data	 = explode(":", $allCost);
		$name	 = trim($data[0]);
		$cost	 = trim($data[1]);
		$cell	 = la_TableCell($name);
		$cell.= la_TableCell($cost . " " . $currency);
		$rows.= la_TableRow($cell, 'row3');
	    }
	}
	$result = la_TableBody($rows, '100%', 0, '');
	return($result);
    }

    /**
     * Write to DB sheduled task and log that
     * 
     * @param string $date
     * @param string $login
     * @param string $action
     * @param string $param
     * @param string $note
     */
    function createTask($date, $login, $action, $param, $note) {
	$dateF	 = mysql_real_escape_string($date);
	$loginF	 = mysql_real_escape_string($login);
	$actionF = mysql_real_escape_string($action);
	$paramF	 = mysql_real_escape_string($param);
	$noteF	 = mysql_real_escape_string($note);
	$query	 = "INSERT INTO `dealwithit` (`id`,`date`,`login`,`action`,`param`,`note`) VALUES";
	$query.="(NULL,'" . $dateF . "','" . $loginF . "','" . $actionF . "','" . $paramF . "','" . $noteF . "');";
	nr_query($query);
	$newId	 = simple_get_lastid('dealwithit');
	log_register('SCHEDULER CREATE ID [' . $newId . '] (' . $login . ')  DATE `' . $date . ' `ACTION `' . $action . '` NOTE `' . $note . '`');
    }

    function checkTask($login, $action, $param) {
	$query	 = "SELECT `id` FROM `dealwithit` WHERE login='" . $login . "' AND action='" . $action . "' AND param='" . $param . "'";
	$check	 = simple_query($query);
	if (empty($check)) {
	    return true;
	} else {
	    return false;
	}
    }

    /**
     * Deleting task from DB by users will
     * 
     * @param type $login
     * @param type $param
     */
    function deleteTask($login, $param) {
	$query = "DELETE FROM `dealwithit` WHERE login='" . $login . "' and param='" . $param . "' AND action='tagadd'";
	nr_query($query);
	log_register('SCHEDULER deleted (' . $login . ') tagid: ' . $param);
    }

    /**
     * 
     * @param type $availableServices
     * @param type $login
     * @return type
     */
    function ShowAllOrderedServices($availableServices, $login) {
	$allSheduled	 = GetAllSheduled($availableServices, $login);
	$allActivated	 = GetAllActivated($availableServices, $login);
	$cells		 = la_TableCell(__('Service name'));
	$cells.= la_TableCell(__('Status'));
	$rows		 = la_TableRow($cells, 'row1');
	if (!empty($availableServices)) {
	    foreach ($availableServices as $eachService) {
		$each	 = explode(":", $eachService);
		$name	 = $each[0];
		$tagid	 = $each[1];
		if (!empty($allSheduled)) {
		    foreach ($allSheduled as $eachSheduled) {
			if ($eachSheduled['param'] == $tagid) {
			    $cells	 = la_TableCell($name);
			    $action	 = '';
			    if ($eachSheduled['action'] == 'tagadd') {
				$action = __('activated');
			    }
			    if ($eachSheduled['action'] == 'tagdel') {
				$action = __('deactivated');
			    }
			    $cells.= la_TableCell(__('Sheduled') . ' ' . __($action) . ' ' . la_JSAlert('?module=adservice&delete_shedule=' . $eachSheduled['param'], la_img('images/delete.gif'), __('You realy want to abort service activation') . '?'));
			    $rows.= la_TableRow($cells, 'row3');
			}
		    }
		}

		if (!empty($allActivated)) {
		    foreach ($allActivated as $eachActivated) {
			if ($eachActivated['tagid'] == $tagid) {
			    $cells = la_TableCell($name);
			    $cells.= la_TableCell(__('Active') . la_JSAlert('?module=adservice&delete_service=' . $eachActivated['tagid'], la_img('images/delete.gif'), __('You realy want to deactivate service') . '?'));
			    $rows.=la_TableRow($cells, 'row3');
			}
		    }
		}
	    }
	}

	$table = la_TableBody($rows, '100%', 0, '');
	return($table);
    }

    if (isset($_POST['tagid']) AND isset($_POST['agree'])) {
	if ($_POST['tagid'] != '-') {
	    $date	 = GetFullApplyDate();
	    $action	 = 'tagadd';
	    $param	 = vf($_POST['tagid'], 3);
	    $param	 = preg_replace('/\0/s', '', $param);
	    $param	 = strip_tags($param);
	    $param	 = mysql_real_escape_string($param);
	    $note	 = 'Order from userstats';
	    if (isset($us_config['ADSERVICE_CUSTOM_ACCEPT'])) {
		$accept = false;
		foreach ($customAcceptData as $eachCustomData) {
		    $acceptData = explode(",", $eachCustomData);
		    if ($acceptData[0] == $param) {
			$accept = true;
		    }
		}
		if ($accept) {
		    rcms_redirect('?module=adservice&accept=show&service=' . $param);
		} else {
		    if (checkTask($user_login, $action, $param)) {
			createTask($date, $user_login, $action, $param, $note);
		    }
		}
	    } else {
		if (checkTask($user_login, $action, $param)) {
		    createTask($date, $user_login, $action, $param, $note);
		}
	    }
	    rcms_redirect('?module=adservice&action=add&wait=true');
	}
    }

    if (isset($_GET['delete_shedule'])) {
	if (!empty($_GET['delete_shedule'])) {
	    $tag	 = preg_replace('/\0/s', '', $_GET['delete_shedule']);
	    $tag	 = strip_tags($tag);
	    $tag	 = mysql_real_escape_string($tag);
	    $tag	 = vf($tag, 3);
	    deleteTask($user_login, $tag);
	    rcms_redirect('?module=adservice&action=delete&wait=true');
	}
    }

    if (isset($_GET['action'])) {
	if (isset($_GET['wait'])) {
	    show_window(__('Success'), __('Your order was sheduled') . '. ' . __('Please wait for') . ' ' . $waitDays);
	}
    }

    if (isset($_GET['delete_service'])) {
	if (!empty($_GET['delete_service'])) {
	    $date	 = GetFullApplyDate();
	    $action	 = 'tagdel';
	    $param	 = vf($_GET['delete_service'], 3);
	    $param	 = preg_replace('/\0/s', '', $param);
	    $param	 = strip_tags($param);
	    $param	 = mysql_real_escape_string($param);
	    $note	 = 'Deactivate from userstats';
	    if (checkTask($user_login, $action, $param)) {
		createTask($date, $user_login, $action, $param, $note);
	    }
	    rcms_redirect('?module=adservice&action=delete&wait=true');
	}
    }

    if (isset($_GET['accept']) AND isset($_GET['service'])) {
	foreach ($customAcceptData as $eachData) {
	    $customRules = explode(",", $eachData);
	    if ($customRules[0] == $_GET['service']) {
		$url = $customRules[1];
	    }
	}

	if (!empty($url)) {
	    $show_pdf	 = la_tag('iframe src="' . $url . '" width="600px" height="800px"');
	    $show_pdf.= la_tag('iframe', true);
	    $inputs		 = la_CheckInput('custom_agreement', __('I have read text above and agree with terms of use'), FALSE, FALSE);
	    $inputs.= la_delimiter();
	    $inputs.= la_Submit(__('Order'));
	    $show_pdf.= la_Form("", "POST", $inputs);
	    show_window(__("You must accept license agreement"), $show_pdf);
	}
	if (isset($_POST['custom_agreement'])) {
	    $date	 = GetFullApplyDate();
	    $action	 = 'tagadd';
	    $param	 = vf($_GET['service'], 3);
	    $param	 = preg_replace('/\0/s', '', $param);
	    $param	 = strip_tags($param);
	    $param	 = mysql_real_escape_string($param);
	    $note	 = 'Order from userstats';

	    if (checkTask($user_login, $action, $param)) {
		createTask($date, $user_login, $action, $param, $note);
	    }

	    rcms_redirect('?module=adservice&action=add&wait=true');
	}
    }



    if (!isset($_GET['accept'])) {
	show_window(__('Aditional services'), __('You can order aditional services. Available services - listed below.'));
	show_window(__('Aditional services cost'), AdServicesList($serviceCost, $us_config['currency']));
	show_window(__('Order aditional service'), AdServicesSelector($availableServices, $user_login));
	show_window(__('Activated services'), ShowAllOrderedServices($availableServices, $user_login));
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}    