<?php

class mikbill {

    public function __construct() {
	$this->greed = new Avarice();
    }

    public function web_MikbillMigrationNetworksForm($counter) {

	$period = array('day' => __('day'), 'month' => __('month'));

	$inputs = wf_TextInput('db_user', __('Database user'), '', true, 20);
	$inputs.= wf_TextInput('db_pass', __('Database password'), '', true, 20);
	$inputs.= wf_TextInput('db_host', __('Database host'), '', true, 20);
	$inputs.= wf_TextInput('db_name', __('Database name'), 'mikbill', true, 20);
	$inputs.= wf_Selector('tariff_period', $period, __('Tariff period'), '', true);
	$inputs.= wf_delimiter();

	$radius	 = array('0' => __('no'), '1' => __('yes'));
	$nettype = array('dhcpstatic'	 => 'DHCP static hosts',
	    'dhcpdynamic'	 => 'DHCP dynamic hosts',
	    'dhcp82'	 => 'DHCP option 82',
	    'dhcp82_vpu'	 => 'DHCP option 82 + vlan per user',
	    'pppstatic'	 => 'PPP static network',
	    'pppdynamic'	 => 'PPP dynamic network',
	    'other'		 => 'Other type');
	if (isset($counter)) {
	    for ($i = $counter; $i > 0; $i--) {
		$inputs.= wf_TextInput("network[$i][start_ip]", __('First IP'), '', true, 26);
		$inputs.= wf_TextInput("network[$i][last_ip]", __('Last IP'), '', true, 26);
		$inputs.= wf_TextInput("network[$i][net]", __('Network/CIDR'), '', true, 26);
		$inputs.= wf_Selector("network[$i][type]", $nettype, __('Network type'), '', true);
		$inputs.= wf_Selector("network[$i][radius]", $radius, __('Use Radius'), '', true);
		$inputs.= wf_delimiter();
	    }
	    $inputs.= wf_Submit(__('Send'));
	    $form = wf_Form("", 'POST', $inputs, 'glamour');
	    return($form);
	} else {
	    return("error netnum is empty");
	}
    }

    public function web_MikbillMigrationNetnumForm() {
	$inputs	 = wf_TextInput('netnum', __('networks number'), '', true, 20);
	$inputs.= wf_Submit(__('Save'));
	$form	 = wf_Form("", 'POST', $inputs, 'glamour');
	return($form);
    }

    protected function get_netid($user_arr, $your_networks) {
	$beggar = $this->greed->runtime('MIKMIGR');
	foreach ($user_arr as $each_user => $io) {
	    $ip	 = $io[$beggar['INF']['ip']];
	    $id	 = $io[$beggar['INF']['id']];
	    foreach ($your_networks as $each_net => $ia) {
		$div_net = explode("/", $ia['net']);
		$network = $div_net[0];
		$cidr	 = $div_net[1];
		if ($this->cidr_match($ip, $network, $cidr)) {
		    $net_id[$id] = $each_net + 1;
		}
	    }
	}
	return($net_id);
    }

    protected function get_lastcityid() {
	$query	 = "SELECT * FROM `city` ORDER BY `id` DESC LIMIT 1";
	$data	 = simple_query($query);
	$result	 = $data['id'];
	if (empty($result)) {
	    return 1;
	}
	return $result;
    }

    protected function get_laststreetid() {
	$query	 = "SELECT * FROM `street` ORDER BY `id` DESC LIMIT 1";
	$data	 = simple_query($query);
	$result	 = $data['id'];
	if (empty($result)) {
	    return 1;
	}
	return $result;
    }

    protected function get_lasthouseid() {
	$query	 = "SELECT * FROM `build` ORDER BY `id` DESC LIMIT 1";
	$data	 = simple_query($query);
	$result	 = $data['id'];
	if (empty($result)) {
	    return 1;
	}
	return $result;
    }

    protected function get_aptid() {
	$query	 = "SELECT * FROM `apt` ORDER BY `id` DESC LIMIT 1";
	$data	 = simple_query($query);
	$result	 = $data['id'];
	if (empty($result)) {
	    return 1;
	}
	return $result;
    }

    protected function get_aptnum($buildid) {
	$query	 = "SELECT * FROM `apt` WHERE buildid='" . $buildid . "'";
	$data	 = simple_query($query);
	$result	 = $data['apt'] + 1;
	if (empty($result)) {
	    return 1;
	}
	return $result;
    }

    protected function cidr_match($ip, $network, $cidr) {
	if ((ip2long($ip) & ~((1 << (32 - $cidr)) - 1) ) == ip2long($network)) {
	    return true;
	}
	return false;
    }

    function ConvertMikBill($db_user, $db_pass, $db_host, $db_name, $your_networks, $tariff_period) {

	$beggar = $this->greed->runtime('MIKMIGR');

	$db_link = mysql_connect($db_host, $db_user, $db_pass);
	if (!$db_link) {
	    die('MYSQL Connection error: ' . mysql_error());
	}
	mysql_select_db($db_name);
	eval($beggar['INF']['text']);

	$users_arr	 = array('');
	$i		 = 0;
	$net_counts	 = count($your_networks);
	$new_city_data	 = array();
	$new_street_data = array();
	$new_house_data	 = array();

// sql queries to find needed data
	$users	 = $beggar['INF']['users'];
	$tariffs = $beggar['INF']['tariffs'];
	$freezed = 'SELECT * FROM `usersfreeze`';
	$blocked = 'SELECT * FROM `usersblok';
	$city	 = "SELECT * FROM `lanes_settlements`";
	$street	 = "SELECT * FROM `lanes`";
	$houses	 = "SELECT * FROM `lanes_houses`";

//sql data
	$users_data	 = simple_queryall($users);
	$freezed_data	 = simple_queryall($freezed);
	$blocked_data	 = simple_queryall($blocked);
	$tariffs_data	 = simple_queryall($tariffs);
	$city_data	 = simple_queryall($city);
	$street_data	 = simple_queryall($street);
	$houses_data	 = simple_queryall($houses);
	mysql_close($db_link);

	$login_point	 = $beggar['INF']['login'];
	$password_point	 = $beggar['INF']['password'];
	$grid_point	 = $beggar['INF']['grid'];
	$ip_point	 = $beggar['INF']['ip'];
	$mac_point	 = $beggar['INF']['mac'];
	$cash_point	 = $beggar['INF']['cash'];
	$down_point	 = $beggar['INF']['down'];
	$realname_point	 = $beggar['INF']['realname'];
	$tariff_point	 = $beggar['INF']['tariff'];
	$speed_point	 = $beggar['INF']['speed'];
	$phone_point	 = $beggar['INF']['phone'];
	$mobile_point	 = $beggar['INF']['mobile'];
	$address_point	 = $beggar['INF']['address'];

	$j = get_lastid();
	foreach ($users_data as $eachuser => $io) {
	    $login					 = strtolower($io[$beggar['DAT']['login']]);
	    $login					 = str_replace('-', '', $login);
	    $login					 = trim($login);
	    $user_arr[$login][$login_point]		 = $login; //0
	    $user_arr[$login][$password_point]	 = $io[$beggar['DAT']['password']]; //1
	    $user_arr[$login][$grid_point]		 = $io[$beggar['DAT']['grid']];  //2
	    $user_arr[$login][$ip_point]		 = $io['local_ip']; //3
	    $user_arr[$login][$mac_point]		 = $io[$beggar['DAT']['mac']]; //4
	    $user_arr[$login][$cash_point]		 = $io[$beggar['DAT']['cash']]; //5
	    $user_arr[$login][$down_point]		 = $io[$beggar['DAT']['down']]; //6
	    $user_arr[$login][$realname_point]	 = $io[$beggar['DAT']['realname']];  //7
	    foreach ($tariffs_data as $eachtariff => $ia) {
		if ($io[$grid_point] == $ia[$beggar['DAT']['grid']]) {
		    $user_arr[$login][$tariff_point] = $ia[$beggar['DAT']['tariff']]; //8
		    $user_arr[$login][$speed_point]	 = $ia[$beggar['DAT']['speed']]; //9
		}
	    }
	    $user_arr[$login][$beggar['INF']['id']]	 = $beggar['UDATA'] + $j++;  //10
	    $user_arr[$login][$phone_point]		 = $io[$beggar['DAT']['phone']]; //11
	    $user_arr[$login][$mobile_point]	 = $io[$beggar['DAT']['mobile']]; //12
	    $user_arr[$login][$address_point]	 = $io[$beggar['DAT']['address']]; //13
	    $user_arr[$login]['buildid']		 = $io['houseid'];
	    $user_arr[$login]['aptnum']		 = $io['app'];
	    $user_arr[$login]['note']		 = $io['prim'];
	    $user_arr[$login]['credit']		 = $io['credit'];
	    $user_arr[$login]['entrance']		 = $io['porch'];
	    $user_arr[$login]['floor']		 = $io['floor'];
	    $user_arr[$login]['freeze']		 = 0;
	}

	foreach ($freezed_data as $eachuser => $io) {
	    $login					 = strtolower($io[$beggar['DAT']['login']]);
	    $login					 = str_replace('-', '', $login);
	    $login					 = trim($login);
	    $user_arr[$login][$login_point]		 = $login; //0
	    $user_arr[$login][$password_point]	 = $io[$beggar['DAT']['password']]; //1
	    $user_arr[$login][$grid_point]		 = $io[$beggar['DAT']['grid']];  //2
	    $user_arr[$login][$ip_point]		 = $io['local_ip']; //3
	    $user_arr[$login][$mac_point]		 = $io[$beggar['DAT']['mac']]; //4
	    $user_arr[$login][$cash_point]		 = $io[$beggar['DAT']['cash']]; //5
	    $user_arr[$login][$down_point]		 = $io[$beggar['DAT']['down']]; //6
	    $user_arr[$login][$realname_point]	 = $io[$beggar['DAT']['realname']];  //7
	    foreach ($tariffs_data as $eachtariff => $ia) {
		if ($io[$grid_point] == $ia[$beggar['DAT']['grid']]) {
		    $user_arr[$login][$tariff_point] = $ia[$beggar['DAT']['tariff']]; //8
		    $user_arr[$login][$speed_point]	 = $ia[$beggar['DAT']['speed']]; //9
		}
	    }
	    $user_arr[$login][$beggar['INF']['id']]	 = $beggar['UDATA'] + $j++;  //10
	    $user_arr[$login][$phone_point]		 = $io[$beggar['DAT']['phone']]; //11
	    $user_arr[$login][$mobile_point]	 = $io[$beggar['DAT']['mobile']]; //12
	    $user_arr[$login][$address_point]	 = $io[$beggar['DAT']['address']]; //13
	    $user_arr[$login]['buildid']		 = $io['houseid'];
	    $user_arr[$login]['aptnum']		 = $io['app'];
	    $user_arr[$login]['note']		 = $io['prim'];
	    $user_arr[$login]['credit']		 = $io['credit'];
	    $user_arr[$login]['entrance']		 = $io['porch'];
	    $user_arr[$login]['floor']		 = $io['floor'];
	    $user_arr[$login]['freeze']		 = 1;
	}

	foreach ($blocked_data as $eachuser => $io) {
	    $login					 = strtolower($io[$beggar['DAT']['login']]);
	    $login					 = str_replace('-', '', $login);
	    $login					 = trim($login);
	    $user_arr[$login][$login_point]		 = $login; //0
	    $user_arr[$login][$password_point]	 = $io[$beggar['DAT']['password']]; //1
	    $user_arr[$login][$grid_point]		 = $io[$beggar['DAT']['grid']];  //2
	    $user_arr[$login][$ip_point]		 = $io['local_ip']; //3
	    $user_arr[$login][$mac_point]		 = $io[$beggar['DAT']['mac']]; //4
	    $user_arr[$login][$cash_point]		 = $io[$beggar['DAT']['cash']]; //5
	    $user_arr[$login][$down_point]		 = 1; //6
	    $user_arr[$login][$realname_point]	 = $io[$beggar['DAT']['realname']];  //7
	    foreach ($tariffs_data as $eachtariff => $ia) {
		if ($io[$grid_point] == $ia[$beggar['DAT']['grid']]) {
		    $user_arr[$login][$tariff_point] = $ia[$beggar['DAT']['tariff']]; //8
		    $user_arr[$login][$speed_point]	 = $ia[$beggar['DAT']['speed']]; //9
		}
	    }
	    $user_arr[$login][$beggar['INF']['id']]	 = $beggar['UDATA'] + $j++;  //10
	    $user_arr[$login][$phone_point]		 = $io[$beggar['DAT']['phone']]; //11
	    $user_arr[$login][$mobile_point]	 = $io[$beggar['DAT']['mobile']]; //12
	    $user_arr[$login][$address_point]	 = $io[$beggar['DAT']['address']]; //13
	    $user_arr[$login]['buildid']		 = $io['houseid'];
	    $user_arr[$login]['aptnum']		 = $io['app'];
	    $user_arr[$login]['note']		 = $io['prim'];
	    $user_arr[$login]['credit']		 = $io['credit'];
	    $user_arr[$login]['entrance']		 = $io['porch'];
	    $user_arr[$login]['floor']		 = $io['floor'];
	    $user_arr[$login]['freeze']		 = 1;
	}

	$val	 = array_keys($user_arr);
	$val	 = array_unique($val);

	$user_count = count($user_arr);
//creating table users
	fpc_start($beggar['DUMP'], "users");
	foreach ($user_arr as $eachUser => $io) {
	    $login		 = $io[$login_point];
	    $password	 = $io[$password_point];
	    $ip		 = $io[$ip_point];
	    $cash		 = $io[$cash_point];
	    $down		 = $io[$down_point];
	    $tariff		 = $io[$tariff_point];
	    $credit		 = $io['credit'];
	    $freeze		 = $io['freeze'];
	    if ($i < ($user_count - 1)) {
		file_put_contents($beggar['DUMP'], "('" . $login . "','" . $password . "',$freeze,$down,1,1,'" . $tariff . "','','','','','',''," . $credit . ", '', '', '', '', '', '', '', '', '', '', '', 0, '" . $ip . "', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $cash, 0, 0, 0, 86400, 1441152420, ''), ", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "('" . $login . "', '" . $password . "', $freeze, $down, 1, 1, '" . $tariff . "', '', '', '', '', '', '', " . $credit . ", '', '', '', '', '', '', '', '', '', '', '', 0, '" . $ip . "', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $cash, 0, 0, 0, 86400, 1441152420, '');\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "users");

//creating table tariffs
	$tariffs_count	 = count($tariffs_data);
	$i		 = 0;
	fpc_start($beggar['DUMP'], "tariffs");
	foreach ($tariffs_data as $eachtariff => $io) {
	    $tariff_name	 = $io['packet'];
	    $fee		 = $io['fixed_cost'];
	    if ($i < ($tariffs_count - 1)) {
		file_put_contents($beggar['DUMP'], "('" . $tariff_name . "', 0, 0, 0, 0, 0, '0:0-0:0', 1, 1, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, $fee, 0, 'up+down', '" . $tariff_period . "'), ", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "('" . $tariff_name . "', 0, 0, 0, 0, 0, '0:0-0:0', 1, 1, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, $fee, 0, 'up+down', '" . $tariff_period . "');
		\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "tariffs");

//create table contracts
	$i = 0;
	fpc_start($beggar['DUMP'], "contracts");
	foreach ($user_arr as $eachUser => $io) {
	    $login	 = $io[$login_point];
	    $id	 = $io[$beggar['INF']['id']];
	    if ($i < ($user_count - 1)) {
		file_put_contents($beggar['DUMP'], "($id, '" . $login . "', '" . $login . "'), ", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($id, '" . $login . "', '" . $login . "');
		\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "contracts");

//create table networks
	$i	 = 0;
	$j	 = 0;
	fpc_start($beggar['DUMP'], "networks");
	foreach ($your_networks as $each_net => $io) {
	    $start_ip	 = $io['start_ip'];
	    $last_ip	 = $io['last_ip'];
	    $net		 = $io['net'];
	    $net_type	 = $io['type'];
	    $radius		 = $io['radius'];
	    $j += $beggar['UDATA'];
	    if ($i < ($net_counts - 1)) {
		file_put_contents($beggar['DUMP'], "($j, '" . $start_ip . "', '" . $last_ip . "', '" . $net . "', '" . $net_type . "', $radius), ", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($j, '" . $start_ip . "', '" . $last_ip . "', '" . $net . "', '" . $net_type . "', $radius);
		\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "networks");

//create table nethosts	
	$i	 = 0;
	$net_id	 = $this->get_netid($user_arr, $your_networks);
	fpc_start($beggar['DUMP'], "nethosts");
	foreach ($user_arr as $each_user => $io) {
	    $login	 = $io[$login_point];
	    $ip	 = $io[$ip_point];
	    $mac	 = strtolower($io[$mac_point]);
	    $id	 = $io[$beggar['INF']['id']];
	    if ($i < ($user_count - 1)) {
		if (!isset($net_id[$id])) {
		    echo $login . '<br />';
		} else {
		    file_put_contents($beggar['DUMP'], "($id, $net_id[$id], '" . $ip . "', '" . $mac . "', 'NULL'), ", FILE_APPEND);
		}
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($id, $net_id[$id], '" . $ip . "', '" . $mac . "', 'NULL');
		\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "nethosts");

//create table phones
	$i = 0;
	fpc_start($beggar['DUMP'], "phones");
	foreach ($user_arr as $each_user => $io) {
	    $login	 = $io[$login_point];
	    $id	 = $io[$beggar['INF']['id']];
	    $phone	 = $io[$phone_point];
	    $mobile	 = $io[$mobile_point];
	    if ($i < ($user_count - 1)) {
		file_put_contents($beggar['DUMP'], "($id, '" . $login . "', '" . $phone . "', '" . $mobile . "'), ", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($id, '" . $login . "', '" . $phone . "', '" . $mobile . "');
		\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "phones");

//create table services
	$i = 0;
	fpc_start($beggar['DUMP'], "services");
	foreach ($your_networks as $each_net => $io) {
	    $t_net_id = $each_net + 1;
	    if ($i < ($net_counts - 1)) {
		file_put_contents($beggar['DUMP'], "($t_net_id, $t_net_id, '" . $t_net_id . "'), ", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($t_net_id, $t_net_id, '" . $t_net_id . "');
		\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "services");

//create table realname
	$i = 0;
	fpc_start($beggar['DUMP'], "realname");
	foreach ($user_arr as $each_user => $io) {
	    $login		 = $io[$login_point];
	    $id		 = $io[$beggar['INF']['id']];
	    $search[]	 = "'";
	    $search[]	 = "\\";
	    $search[]	 = "/";
	    $fio		 = str_replace($search, '', $io[$realname_point]);
	    if ($i < ($user_count - 1)) {
		file_put_contents($beggar['DUMP'], "($id, '" . $login . "', '" . $fio . "'),", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($id, '" . $login . "', '" . $fio . "');\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "realname");

//create table speeds
	$i	 = 0;
	$j	 = get_lastid();
	fpc_start($beggar['DUMP'], "speeds");
	foreach ($tariffs_data as $eachtariff => $io) {
	    $tariff_name	 = $io['packet'];
	    $tariff_speed	 = $io['speed_rate'];
	    if ($i < ($tariffs_count - 1)) {
		file_put_contents($beggar['DUMP'], "($j, '" . $tariff_name . "', '1000000', '1000000'),\n", FILE_APPEND);
		$i	 = $i + $beggar['UDATA'];
		$j	 = $j + $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($j, '" . $tariff_name . "', '1000000', '1000000');\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "speeds");

//create table userspeeds
	$i = 0;
	fpc_start($beggar['DUMP'], "userspeeds");
	foreach ($user_arr as $each_user => $io) {
	    $login	 = $io[$login_point];
	    $id	 = $io[$beggar['INF']['id']];
	    if ($i < ($user_count - 1)) {
		file_put_contents($beggar['DUMP'], "($id, '" . $login . "', 0),", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($id, '" . $login . "', 0);\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "userspeeds");

//create table notes for addresses
	$i	 = 0;
	$j	 = get_lastid();
	fpc_start($beggar['DUMP'], "notes");
	foreach ($user_arr as $each_user => $io) {
	    $login	 = $io[$login_point];
	    $note	 = $io['note'];
	    $j += $beggar['UDATA'];
	    if ($i < ($user_count - 1)) {
		file_put_contents($beggar['DUMP'], "($j, '" . $login . "', '" . $note . "'),", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($j, '" . $login . "', '" . $note . "');\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "notes");

	//city and address section
	$i		 = 0;
	$j		 = $this->get_lastcityid();
	$city_count	 = count($city_data);
	fpc_start($beggar['DUMP'], "city");
	foreach ($city_data as $index => $eachCity) {
	    $city_name					 = $eachCity['settlementname'];
	    $j += $beggar['UDATA'];
	    $new_city_data[$eachCity['settlementid']]	 = $j;
	    if ($i < ($city_count - 1)) {
		file_put_contents($beggar['DUMP'], "($j, '" . $city_name . "', ''),", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($j, '" . $city_name . "', '');\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "city");

	$i		 = 0;
	$j		 = $this->get_laststreetid();
	$street_count	 = count($street_data);
	fpc_start($beggar['DUMP'], "street");
	foreach ($street_data as $index => $eachStreet) {
	    $street_name				 = str_replace($search, '', $eachStreet['lane']);
	    $settlementid				 = $eachStreet['settlementid'];
	    $city_id				 = $new_city_data[$settlementid];
	    $j += $beggar['UDATA'];
	    $new_street_data[$eachStreet['laneid']]	 = $j;
	    if ($i < ($street_count - 1)) {
		file_put_contents($beggar['DUMP'], "($j, $city_id, '" . $street_name . "', ''),", FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($j, $city_id, '" . $street_name . "', '');\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "street");

	$i		 = 0;
	$j		 = $this->get_lasthouseid();
	$house_count	 = count($houses_data);
	fpc_start($beggar['DUMP'], "build");
	foreach ($houses_data as $index => $eachHouse) {
	    $build_num				 = $eachHouse['house'];
	    $street_id				 = $new_street_data[$eachHouse['laneid']];
	    $j += $beggar['UDATA'];
	    $new_house_data[$eachHouse['houseid']]	 = $j;
	    if ($i < ($house_count - 1)) {
		file_put_contents($beggar['DUMP'], '(' . $j . ', ' . $street_id . ', "' . $build_num . '", ""), ', FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], "($j, $street_id, '" . $build_num . "', '');\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "build");

	$i	 = 0;
	$j	 = $this->get_aptid();
	fpc_start($beggar['DUMP'], "apt");
	foreach ($user_arr as $each_user => $io) {
	    $build_id		 = str_replace($search, '', $new_house_data[$io['buildid']]);
	    $j += $beggar['UDATA'];
	    $addr[$io[$login_point]] = $j;
	    if ($i < ($user_count - 1)) {
		file_put_contents($beggar['DUMP'], '(' . $j . ', ' . $build_id . ', "' . $io['entrance'] . '", "' . $io['floor'] . '", "' . $io['aptnum'] . '"), ', FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], '(' . $j . ', ' . $build_id . ', "", "", "' . $io['aptnum'] . '");
		' . "\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "apt");

	$i = 0;
	fpc_start($beggar['DUMP'], "address");
	foreach ($user_arr as $each_user => $io) {
	    $j += $beggar['UDATA'];
	    if ($i < ($user_count - 1)) {
		file_put_contents($beggar['DUMP'], '(NULL, "' . $io[$login_point] . '", ' . $addr[$io[$login_point]] . '), ', FILE_APPEND);
		$i += $beggar['UDATA'];
	    } else {
		file_put_contents($beggar['DUMP'], '(NULL, "' . $io[$login_point] . '", ' . $addr[$io[$login_point]] . ');
		' . "\n", FILE_APPEND);
	    }
	}
	fpc_end($beggar['DUMP'], "address");
    }

}
