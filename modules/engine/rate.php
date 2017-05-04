<?php
////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) ReloadCMS Development Team                                 //
//   http://reloadcms.sf.net                                                  //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////
$writed = false;

function show_rate($for) {
	$arr=array();
	if (file_exists(RATE_PATH.$for)) {
		$arr = unserialize(file_get_contents(RATE_PATH.$for));
	}
	$cou=count($arr);
	$sum=0;
	foreach ($arr as $ip => $rate) {
		$sum.=$rate;
	}
	$res=0;
	$ret='<table width=100% cellspacing="0" cellpadding="0"><tr width=100%>';
	if ($cou != 0) $res = bcdiv($sum,$cou);
	$desc = __('Poor');
	if ($res == '1') $desc = __('Poor');
	if ($res == '2') $desc = __('Fair');
	if ($res == '3') $desc = __('Average');
	if ($res == '4') $desc = __('Very Good');
	if ($res == '5') $desc = __('Excelent');
	$txt='&nbsp;'.__('Rate').'('.$cou.'): '.$desc;
	for ($i=0;$i<5;$i++) {
		if ($i < $res) {
			$ret.= '<td class="rate-fill">'.$txt.'</td>';
		} else {
			$ret.= '<td class="rate-empty">'.$txt.'</td>';
		}
		$txt='&nbsp;';
	}
	$ret.='</tr></table>';
	return $ret;
}

function show_rate_rbox($for) {
	return '<center><form method="POST" name="'.$for.'"><input type="hidden" name="for" value="'.$for.'">'.__('Rate').':&nbsp;&nbsp;&nbsp;'.
	'<input type="radio" class = "rate-radio" name="val" value="1">'.__('Poor').'&nbsp;&nbsp;&nbsp;'.
	'<input type="radio" class = "rate-radio" name="val" value="2">'.__('Fair').'&nbsp;&nbsp;&nbsp;'.
	'<input type="radio" class = "rate-radio" name="val" value="3">'.__('Average').'&nbsp;&nbsp;&nbsp;'.
	'<input type="radio" class = "rate-radio" name="val" value="4">'.__('Very Good').'&nbsp;&nbsp;&nbsp;'.
	'<input type="radio" class = "rate-radio" name="val" value="5" checked>'.__('Excelent').'&nbsp;&nbsp;&nbsp;'.
	'<input type="submit" value="'.__('Submit').'"></form></center>';
}

function write_rate($for,$val,$ip) {
	global $write;
	if (!$write) {
	$arr=array();
	if (file_exists(RATE_PATH.$for)) {
		$arr = unserialize(file_get_contents(RATE_PATH.$for));
	}
	$arr[$ip] = $val;
	file_write_contents(RATE_PATH.$for, serialize($arr));
}
}

function check_ip ($for,$ip) {
	if (file_exists(RATE_PATH.$for)) {
		$arr = unserialize(file_get_contents(RATE_PATH.$for));
	}
	return isset($arr[$ip]);
}

function get_rate($for){
	$valid_post=false;
	$b_start='<div class="rate"> ';
	$b_end=' </div>';
	if (trim(rcms_parse_text($for)) != '') {
		$valid_post = true;
		$for = md5(trim(rcms_parse_text($for)));
	}
	
	$post_for = 0;
	if (isset($_POST['for'])) {
		$post_for = $_POST['for'];
	}
	
	$valid_val = 0;
	if (isset($_POST['val'])) {
		if (($_POST['val'] == '5') or ($_POST['val'] == '4') or ($_POST['val'] == '3') or ($_POST['val'] == '2') or ($_POST['val'] == '1')) {
			$valid_val = $_POST['val'];
		}
	}

	if (!$valid_post) {
		return $b_start.__('Rate').': '.__('Only for registered users').$b_end;
	}
	
	if (!LOGGED_IN) {
		return $b_start.show_rate($for).$b_end;;
	}

	if ($post_for == $for and $valid_val == 0 and $valid_post and !check_ip($for,$_SERVER['REMOTE_ADDR'])) {
		return $b_start.show_rate_rbox($for).$b_end;
	} 

	if ($post_for == $for and $valid_val == 0 and check_ip($for,$_SERVER['REMOTE_ADDR'])) {
		return $b_start.show_rate($for).$b_end;
	}
	
	if ($post_for == $for and $valid_val !== 0 and $valid_post and !check_ip($for,$_SERVER['REMOTE_ADDR'])) { 
		write_rate($for,$valid_val,$_SERVER['REMOTE_ADDR']);
		return $b_start.show_rate($for).$b_end;
	} 
	
	if ($post_for !== $for and $valid_post and !check_ip($for,$_SERVER['REMOTE_ADDR'])) {
		return $b_start.show_rate_rbox($for).$b_end;
	} 

	if ($post_for !== $for and $valid_post and check_ip($for,$_SERVER['REMOTE_ADDR'])) {
		return $b_start.show_rate($for).$b_end;
	}
	
	return $b_start.__('Rate').': '.__('Data not valid').$b_end;
}

?>