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

define('RCMS_GB_DEFAULT_FILE', DF_PATH . 'guestbook.dat');
define('RCMS_MC_DEFAULT_FILE', DF_PATH . 'minichat.dat');
$_CACHE['gbook'] = array();

function guestbook_get_msgs($page = 0, $parse = true, $limited = true, $file = RCMS_GB_DEFAULT_FILE, $config = 'guestbook.ini') {
	global $_CACHE, $system;
	$config = parse_ini_file(CONFIG_PATH . $config);
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
				if(!empty($msg)) $rdata[$id]['text'] = rcms_parse_text($msg['text'], !$limited, false, !$limited);
			}
		}
		return $rdata;
	} else return array();
}

function guestbook_get_last_msgs($num = 10, $parse = true, $limited, $file = RCMS_GB_DEFAULT_FILE, $config = 'guestbook.ini') {
	global $_CACHE, $system;
	$config = parse_ini_file(CONFIG_PATH . $config);
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
				}
			}
		}
		return $rdata;
	} else return array();
}

function guestbook_get_pages_num($file = RCMS_GB_DEFAULT_FILE, $config = 'guestbook.ini') {
	global $_CACHE, $system;
	$config = parse_ini_file(CONFIG_PATH . $config);
	$data = &$_CACHE['gbook'][$file];
	if(!isset($data)) {
		if (!is_readable($file) || !($data = unserialize(file_get_contents($file)))) $data = array();
	}
	if(!empty($system->config['perpage'])) {
		return ceil(sizeof($data)/$system->config['perpage']);
	} else return 1;
}

function guestbook_post_msg($username, $nickname, $text, $file = RCMS_GB_DEFAULT_FILE, $config = 'guestbook.ini') {
	global $_CACHE;
	$text = trim($text);
	if(empty($text)) return false;
	$config = parse_ini_file(CONFIG_PATH . $config);
	$data = &$_CACHE['gbook'][$file];
	if(!isset($data)) {
		if (!is_readable($file) || !($data = unserialize(file_get_contents($file)))) $data = array();
	}
	if(!empty($config['max_db_size'])) $data = array_slice($data, -$config['max_db_size']+1);
	$newmesg['username'] = $username;
	$newmesg['nickname'] = (!empty($config['max_word_len']) && strlen($nickname) > $config['max_word_len']) ? '<abbr title="' . htmlspecialchars($nickname) . '">' . substr($nickname, 0, $config['max_word_len']) . '</abbr>' : htmlspecialchars($nickname);
	$newmesg['time'] = rcms_get_time();
	$newmesg['text'] = (strlen($text) > $config['max_message_len']) ? substr($text, 0, $config['max_message_len']) : $text;
	$data[] = $newmesg;
	return file_write_contents($file, serialize($data));
}

function guestbook_post_remove($id, $file = RCMS_GB_DEFAULT_FILE, $config = 'guestbook.ini') {
	global $_CACHE;
	$config = parse_ini_file(CONFIG_PATH . $config);
	$data = &$_CACHE['gbook'][$file];
	if(!isset($data)) {
		if (!is_readable($file) || !($data = unserialize(file_get_contents($file)))) $data = array();
	}
	rcms_remove_index($id, $data, true);
	return file_write_contents($file, serialize($data));
}
?>