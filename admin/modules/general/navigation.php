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
function count_spec_symbols ($text) {
	$i=0;
	$c=0;
	if (strlen($text) == 0) 
		return 0;
	while ($text[$i] == '>') {
		$i++;
		$c++;
	}
	return $c;
}

function normalize($array) {
	$normal = array(0 => array());
	$prev_level = array();
	$prev_level[0] =& $normal[0];
	foreach ($array as $item) {
		$level = count_spec_symbols($item);
		if (isset($prev_level[$level]['sub'])) {
			$index = sizeof($prev_level[$level]['sub']);
		} else {
			$index = 0;
			$prev_level[$level]['sub'] = array();
		}
		$prev_level[$level]['sub'][$index] = array('title' => substr($item, count_spec_symbols($item)));
		$prev_level[$level + 1] =& $prev_level[$level]['sub'][$index];
	}    
	return $normal[0]['sub'];
}

function arr2line($array, $level) {
	$result='';
	foreach($array as $key => $val){
		$result .= $level.$val['title']."\r\n";
		if (isset($val['sub'])) 
			$result .= arr2line($val['sub'],$level.'>');
	}
	return $result;
}

function dat2txt($file){
	$result = arr2line(unserialize(file_get_contents($file)),'');
	return substr($result,0, strlen($result)-2);
}

function txt2dat($text, $file){
	$array = explode("\r\n", $text);
	$normal = normalize($array);
	file_write_contents($file,serialize($normal));
}

if(!empty($_POST['urls']) && !empty($_POST['names']) && is_array($_POST['urls']) && is_array($_POST['names'])){
	if(sizeof($_POST['urls']) !== sizeof($_POST['names'])){
		rcms_showAdminMessage(__('Error occurred'));
	} else {
		$result = array();
		foreach ($_POST['urls'] as $i => $url) {
			if(!empty($url)){
				if(!empty($_POST['ext'][$i])) {
					$ins['url'] = 'external:' . $url;
				} else {
					$ins['url'] = $url;
				}
				$ins['name'] = $_POST['names'][$i];
				$result[] = $ins;
			}
		}
		write_ini_file($result, CONFIG_PATH . 'navigation.ini', true) or rcms_showAdminMessage(__('Error occurred'));
	}
} elseif (!empty($_POST['addlink']) && !empty($_POST['addlink']['url'])) {
	$links = parse_ini_file(CONFIG_PATH . 'navigation.ini', true);
	$links[] = $_POST['addlink'];
	write_ini_file($links, CONFIG_PATH . 'navigation.ini', true) or rcms_showAdminMessage(__('Error occurred'));
}

if(!empty($_POST['dy'])) write_ini_file($_POST['dy'], CONFIG_PATH . 'dynamik.ini');
if(!empty($_POST['c1title'])) file_write_contents(CONFIG_PATH . 'custom_menu_title_1.txt',$_POST['c1title']);
if(!empty($_POST['c2title'])) file_write_contents(CONFIG_PATH . 'custom_menu_title_2.txt',$_POST['c2title']);
if(!empty($_POST['c3title'])) file_write_contents(CONFIG_PATH . 'custom_menu_title_3.txt',$_POST['c3title']);
if(!empty($_POST['c1'])) txt2dat($_POST['c1'], CONFIG_PATH . 'custom_menu_1.dat');
if(!empty($_POST['c2'])) txt2dat($_POST['c2'], CONFIG_PATH . 'custom_menu_2.dat');
if(!empty($_POST['c3'])) txt2dat($_POST['c3'], CONFIG_PATH . 'custom_menu_3.dat');

$links = parse_ini_file(CONFIG_PATH . 'navigation.ini', true);
$dyna = parse_ini_file(CONFIG_PATH . 'dynamik.ini', true);
	
$frm = new InputForm ('', 'post', __('Submit'));
$frm->addbreak(__('Menu options'));
$frm->addrow(__('Show icons'), $frm->checkbox('dy[ico]', '1', '', @$dyna['ico']));
$frm->addbreak(__('Dynamik menu options'));
$frm->addrow(__('Use'), $frm->checkbox('dy[use]', '1', '', @$dyna['use']));
$frm->addrow(__('Min cascading'), $frm->checkbox('dy[min]', '1', '', @$dyna['min']));
$frm->addrow(__('Max subitems'), $frm->text_box('dy[max]',@$dyna['max']));
$frm->addrow(__('Off for ').'"'. __('Articles').'"',$frm->checkbox('dy[use_art]', '1', '', @$dyna['use_art']));
$frm->addrow(__('Off for ').'"'. __('Gallery').'"',$frm->checkbox('dy[use_gal]', '1', '', @$dyna['use_gal']));
$frm->addrow(__('Off for ').'"'. __('Member list').'"',$frm->checkbox('dy[use_mem]', '1', '', @$dyna['use_mem']));
$frm->addrow(__('Off for ').'"'. __('FilesDB').'"',$frm->checkbox('dy[use_fdb]', '1', '', @$dyna['use_fdb']));
$frm->addrow(__('Off for ').'"'. __('Forum').'"',$frm->checkbox('dy[use_for]', '1', '', @$dyna['use_for']));
$frm->addbreak(__('Navigation editor'));
$frm->addrow(__('Link'), __('Title'));
$i = 0;
foreach ($links as $link){
	$tmp = explode(':', $link['url'], 2);
	$checked = $tmp[0] == 'external';
	if($checked){
		$link['url'] = $tmp[1];
	}
	$frm->addrow($frm->text_box('urls[' . $i . ']', $link['url']), $frm->text_box('names[' . $i . ']', $link['name']) . $frm->checkbox('ext[' . $i . ']', '1', __('Open in new window'), $checked));
	$i++;
}
$frm->addrow($frm->text_box('urls[' . $i . ']', ''), $frm->text_box('names[' . $i . ']', '') . $frm->checkbox('ext[' . $i . ']', '1', __('Open in new window')));
$frm->addmessage(__('If you want to remove link leave it\'s URL empty. If you want to add new item fill in the last row.'));
$frm->addmessage(__('You can use modifiers to create link to specified part of your site. Type MODIFIER:OPTIONS in "Link" column. If you want to override default title of modified link you must enter your title to "Title" column, or leave it empty to use default one. Here is a list of modifiers:'));
foreach ($system->navmodifiers as $modifier => $options){
	$frm->addrow($modifier, call_user_func($system->navmodifiers[$modifier]['h']));
}
$frm->addrow(__('CUSTOM1 or CUSTOM2 or CUSTOM3 (tilte column)'), __('Use it for custom menu'));
$frm->addbreak(__('Custom menu editor'));
$frm->addrow(':',__('Separator beetwen title and link (example: MyLink:www.example.com.ua)'));
$frm->addrow('>',__('Use it for define line as subitem (example: >MyLink:www.example.com.ua)'));
$frm->addrow('<b>CUSTOM1:</b>',$frm->text_box('c1title', file_get_contents(CONFIG_PATH.'custom_menu_title_1.txt'),100).'<br>'.$frm->textarea('c1', dat2txt(CONFIG_PATH . 'custom_menu_1.dat'), 100, 20), 'top', 'left'); 
$frm->addrow('<b>CUSTOM2:</b>',$frm->text_box('c2title', file_get_contents(CONFIG_PATH.'custom_menu_title_2.txt'),100).'<br>'.$frm->textarea('c2', dat2txt(CONFIG_PATH.'custom_menu_2.dat'), 100, 20), 'top', 'left'); 
$frm->addrow('<b>CUSTOM3:</b>',$frm->text_box('c3title', file_get_contents(CONFIG_PATH.'custom_menu_title_3.txt'),100).'<br>'.$frm->textarea('c3', dat2txt(CONFIG_PATH.'custom_menu_3.dat'), 100, 20), 'top', 'left'); 
$frm->show();
?>