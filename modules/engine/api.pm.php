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

define('RCMS_PM_DEFAULT_FILE', DATA_PATH.'/pm/'.$system->user['username'].'.dat');

function pm_disabled()
{
	$arr = parse_ini_file(CONFIG_PATH . 'disable.ini');
	return isset($arr['pm']);
}

function make_pm_file($file)
{
	if (!file_exists($file)) {
		$f = fopen($file, "w"); 
		fclose($f);
	}
}

function getUserData($username){
		global $system;
		$result = @unserialize(@file_get_contents(USERS_PATH . basename($username)));
		if(empty($result)) return false; else return $result;
}

make_pm_file(RCMS_PM_DEFAULT_FILE);

$_CACHE['gbook'] = array();

function pm_get_msgs($page = 0, $parse = true, $limited = true, $file = RCMS_PM_DEFAULT_FILE) {
	global $_CACHE, $system;
	$all_pm = 0;
	$new_pm = 0;
	$pm_hint = __('No new messages');
	$ret = array($all_pm, $new_pm, $pm_hint);
	$data = &$_CACHE['gbook'][$file];
	if(!isset($data)) {
		if (!is_readable($file) || !($data = unserialize(file_get_contents($file)))) $data = array();
	}
	if(!empty($data)){
		$c    = sizeof($data);
		$ndata = $rdata = array();
		foreach ($data as $key => $value) $ndata[$key . ''] = $value;
		$ndata = array_reverse($ndata, true);
		if($page !== null){
			$i = 0;
			while($i < (($page+1) * $system->config['perpage']) && $el = each($ndata)){
				if($i >= $page * $system->config['perpage']) $rdata[$el['key']] = $el['value'];
				$i++;
			}
		} else {
			$rdata = $ndata;
		}
		if($parse){
			foreach($rdata as $id => $msg){
				if(!empty($msg)) {
					$rdata[$id]['text'] = rcms_parse_text($msg['text'], !$limited, false, !$limited);
					$all_pm++;
					if ($msg['new'] == "1"){
						$new_pm++;
						$pm_hint = rcms_format_time('H:i:s d.m.Y', $msg['time']).' - '.__('last new message from ').$msg['username'].' ('.$msg['nickname'].')';
					}
				}
			}
		}
		return array($all_pm, $new_pm, $pm_hint);
	} else return $ret;
}

function pm_get_msg_by_id($num = 10, $parse = true, $limited, $mid = '0', $file = RCMS_PM_DEFAULT_FILE) {
	global $_CACHE, $system;
	$t='';
	$data = &$_CACHE['gbook'][$file];
	if(!isset($data)) {
		if (!is_readable($file) || !($data = unserialize(file_get_contents($file)))) $data = array();
	}
	if(!empty($data)){
		$ndata = $rdata = array();
		foreach ($data as $key => $value) $ndata[$key . ''] = $value;
		$ndata = array_reverse($ndata, true);
		if($num !== null){
			$i = 0;
			while($i < $num && $el = each($ndata)){
				$rdata[$el['key']] = $el['value'];
				$i++;
			}
		} else {
			$rdata = $ndata;
		}
		if($parse){
			$t= $rdata[$mid]['text'];
		}
	}
	return $t;
}

function pm_get_all_msgs($num = 10, $parse = true, $limited, $file = RCMS_PM_DEFAULT_FILE) {
	global $_CACHE, $system;
	$data = &$_CACHE['gbook'][$file];
	if(!isset($data)) {
		if (!is_readable($file) || !($data = unserialize(file_get_contents($file)))) $data = array();
	}
	if(!empty($data)){
		$ndata = $rdata = array();
		foreach ($data as $key => $value) $ndata[$key . ''] = $value;
		$ndata = array_reverse($ndata, true);
		if($num !== null){
			$i = 0;
			while($i < $num && $el = each($ndata)){
				$rdata[$el['key']] = $el['value'];
				$i++;
			}
		} else {
			$rdata = $ndata;
		}
		if($parse){
			foreach($rdata as $id => $msg){
				if(!empty($msg)) {
					$rdata[$id]['text'] = rcms_parse_text($msg['text'], !$limited, false, !$limited);
					$rdata[$id]['new'] = $msg['new'];
					$rdata[$id]['username'] = $msg['username'];
					$rdata[$id]['nickname'] = $msg['nickname'];
					$rdata[$id]['time'] = rcms_format_time('H:i:s d.m.Y', $msg['time']);
				}
			}
		}
		
		return $rdata;
	} else return array();
}

function pm_set_all_nonew($num = 10, $parse = true, $limited, $file = RCMS_PM_DEFAULT_FILE) {
	global $_CACHE, $system;
	$data = &$_CACHE['gbook'][$file];
	if(!isset($data)) {
		if (!is_readable($file) || !($data = unserialize(file_get_contents($file)))) $data = array();
	}
	if(!empty($data)){
		$ndata = $rdata = array();
		foreach ($data as $key => $value) $ndata[$key . ''] = $value;
		if($num !== null){
			$i = 0;
			while($i < $num && $el = each($ndata)){
				$rdata[$el['key']] = $el['value'];
				$i++;
			}
		} else {
			$rdata = $ndata;
		}
		if($parse){
			foreach($rdata as $id => $msg){
				if(!empty($msg)) {
					$msg['new'] = '0';
					$rdata[$id]=$msg;
				}
			}
		}
		return file_write_contents($file, serialize($rdata));
	}
}

function pm_get_pages_num($file = RCMS_PM_DEFAULT_FILE) {
	global $_CACHE, $system;
	$data = &$_CACHE['gbook'][$file];
	if(!isset($data)) {
		if (!is_readable($file) || !($data = unserialize(file_get_contents($file)))) $data = array();
	}
	if(!empty($system->config['perpage'])) {
		return ceil(sizeof($data)/$system->config['perpage']);
	} else return 1;
}

function pm_post_msg($username, $nickname, $text, $to) {
	global $_CACHE, $system;
	$text = trim($text);
	if(empty($text)) return false;
	if(!getUserData($to)) return false;
	$file = DATA_PATH.'/pm/'.$to.'.dat';
	make_pm_file($file);
	$data = &$_CACHE['gbook'][$file];
	if(!isset($data)) {
		if (!is_readable($file) || !($data = unserialize(file_get_contents($file)))) $data = array();
	}
	$newmesg['username'] = $username;
	$newmesg['nickname'] = htmlspecialchars($nickname);
	$newmesg['time'] = rcms_get_time();
	$newmesg['text'] = $text;
	$newmesg['new'] = '1';
	$data[] = $newmesg;
	return file_write_contents($file, serialize($data));
}

function pm_post_remove($id, $file = RCMS_PM_DEFAULT_FILE) {
	global $_CACHE;
	$data = &$_CACHE['gbook'][$file];
	if(!isset($data)) {
		if (!is_readable($file) || !($data = unserialize(file_get_contents($file)))) $data = array();
	}
	rcms_remove_index($id, $data, true);
	return file_write_contents($file, serialize($data));
}
?>