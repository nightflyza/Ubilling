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

function user_tz_select($default = 0, $select_name = 'timezone') {
	global $lang;

	$tz_select = '<select name="' . $select_name . '">';
	while(list($offset, $zone) = @each($lang['tz'])) {
		$selected = ( $offset == $default ) ? ' selected="selected"' : '';
		$tz_select .= '<option value="' . $offset . '"' . $selected . '>' . $zone . '</option>';
	}
	$tz_select .= '</select>';

	return $tz_select;
}

function user_skin_select($dir, $select_name, $default = '', $style = '', $script = '') {
    $skins = rcms_scandir($dir);
    $frm = '<select name="' . $select_name . '" style="' . $style . '" ' . $script . '>';
    foreach ($skins as $skin){
        if(is_dir($dir . $skin) && is_file($dir . $skin . '/skin_name.txt')){
            $name = file_get_contents($dir . $skin . '/skin_name.txt');
            $frm .= '<option value="' . $skin . '"' . (($default == $skin) ? ' selected="selected">' : '>') . $name . '</option>';
        }
    }
    $frm .= '</select>';
    return $frm;
}

function user_lang_select($select_name, $default = '', $style = '', $script = '') {
    global $system;
    $frm = '<select name="' . $select_name . '" style="' . $style . '" ' . $script . '>';
    foreach ($system->data['languages'] as $lang_id => $lang_name){
        $frm .= '<option value="' . $lang_id . '"' . (($default == $lang_id) ? ' selected="selected">' : '>') . $lang_name . '</option>';
    }
    $frm .= '</select>';
	return $frm;
}

function rcms_pagination($total, $perpage, $current, $link){
    $return = '';
    $link = preg_replace("/((&amp;|&)page=(\d*))/", '', $link);
    if(!empty($perpage)) {
        $pages = ceil($total/$perpage);
        if($pages != 1){
            $c = 1;
            while($c <= $pages){
                if($c != $current) $return .= ' [' . '<a href="' . $link . '&amp;page=' . $c . '">' . $c . '</a>] ';
                else $return .= ' [' . $c . '] ';
                $c++;
            }
        }
    }
    return $return;
}

function rcms_parse_menu($format) {
	global $system;
	$navigation = parse_ini_file(CONFIG_PATH . 'navigation.ini', true);
	$dyna = parse_ini_file(CONFIG_PATH . 'dynamik.ini', true);
	$result = array();
	foreach ($navigation as $link) {
		if(substr($link['url'], 0, 9) == 'external:') {
			$target = '_blank';
			$link['url'] = substr($link['url'], 9);
		} else {
			$target = '';
		}
		$tdata = explode(':', $link['url'], 2);
		if(count($tdata) == 2){
			list($modifier, $value) = $tdata;
		} else {
			$modifier = $tdata[0];
		}
		if(!empty($value) && !empty($system->navmodifiers[$modifier])){
			if($clink = call_user_func($system->navmodifiers[$modifier]['m'], $value)){
				$result[] = array($clink[0], (empty($link['name'])) ? $clink[1] : __($link['name']), $target);
			}
		} else {
			$result[] = array($link['url'], __($link['name']));
		}
	}
	$menu = '';
	foreach ($result as $item){
		if(empty($item[2])) {
			$item[2] = '_top';
		}
		// Begin of Icons support by Migel
		if ($item[0] == '?module=index') {
			$item[3] = 'home.png';
		} elseif ($item[0] == '?module=articles') {
			$item[3] = 'articles.png';
		} elseif ($item[0] == '?module=guestbook') {
			$item[3] = 'guestbook.png';
		} elseif ($item[0] == '?module=gallery') {
			$item[3] = 'gallery.png';
		} elseif ($item[0] == '?module=user.list') {
			$item[3] = 'userlist.png';
		} elseif ($item[0] == '?module=filesdb') {
			$item[3] = 'files.png';
		} elseif ($item[0] == '?module=feedback') {
			$item[3] = 'email.png';
		} elseif ($item[0] == '?module=forum') {
			$item[3] = 'forum.png';
		} else {
			$item[3] = 'default.png';
		}
		if (isset($dyna['ico']))
			$item[3] = '<img src="skins/icons/'.$item[3].'">';
		else 
			$item[3]='';
		$menu .= str_replace('{link}', $item[0], str_replace('{title}', $item[1], str_replace('{target}', @$item[2], str_replace('{icon}', $item[3], $format))));
		// End of Icons support by Migel
	}
	$result = $menu;
	return $result;
}

function arr2ddm($array) {
	$ra = array();
	foreach($array as $key => $val){
		$ta = explode(":", $val['title'],2);
		if (!isset($ta[1])) $ta[1]='';
		if (isset($val['sub'])) {
			$ra['&nbsp;&nbsp;⇨ &nbsp;&nbsp; '.__($ta[0]).'&nbsp;&nbsp; '] = array_merge(array("-" => $ta[1]),arr2ddm($val['sub']));
		} else {
			$ra['&nbsp;&nbsp; '.__($ta[0]).'&nbsp;&nbsp; '] = $ta[1];
		}	
	}
	return $ra;
}

function rcms_parse_dynamik_menu($format) {
	global $system;
	
	function convertArray($ar){
		$var = '{ ';
  	foreach ($ar as $key=>$val ){
    	$var .= '"'.$key.'" : ';
      if ( is_array( $val ) ){
        $var .= convertArray($val).', ';
      } else {
      	$var .= '"'.$val.'", ';
      }
    }
    if ($var[strlen($var)-2] == ',') $var[strlen($var)-2] = ' ';
		return $var.'} ';
	}

	$pic_right = '&nbsp;&nbsp;<b>�</b> ';
	//Commented becouse fucking IE, Microsoft, Gates and his mother...
	//$pic_right = '&nbsp;<img src = \''.SKIN_PATH.'arrow_right.gif\'>';
	//$pic_down = '<img src = \''.SKIN_PATH.'arrow_down.gif\'>';
	$pic_down='';
	$navigation = parse_ini_file(CONFIG_PATH . 'navigation.ini', true);
  	$dyna = parse_ini_file(CONFIG_PATH . 'dynamik.ini', true);
	$result = array();
	foreach ($navigation as $link) {
		if(substr($link['url'], 0, 9) == 'external:') {
			$target = '_blank';
			$link['url'] = substr($link['url'], 9);
		} else {
			$target = '';
		}
		$tdata = explode(':', $link['url'], 2);
		if(count($tdata) == 2){
			list($modifier, $value) = $tdata;
		} else {
			$modifier = $tdata[0];
		}
		if(!empty($value) && !empty($system->navmodifiers[$modifier])){
			if($clink = call_user_func($system->navmodifiers[$modifier]['m'], $value)){
				$result[] = array($clink[0], (empty($link['name'])) ? $clink[1] : __($link['name']), $target);
			}
		} else {
			$result[] = array($link['url'], __($link['name']));
		}
	}
	$menu = ' <script type="text/javascript" src="modules/jsc/navigation.js"></script> <div class="dhtml_menu"> <div class="horz_menu"> ';
	foreach ($result as $item){
		if(empty($item[2])) {
			$item[2] = '_top';
		}
		if(empty($item[4])) {
			$item[4] = '';
		}
		// Begin of Icons support by Migel
		//$arr = array();
		if ($item[0] == '?module=articles') {
		if (!isset($dyna['use_art'])){
			$articles = new articles();
			$containers = $articles -> getContainers();
			$count = 0;
			if (is_array($containers)){
				$item[1] .= '&nbsp;'.$pic_down;
				$containers = array_reverse($containers);
				foreach ($containers as $conkey => $conval) {
      		$count++;
        	if ($count != $dyna['max']) {
        		$arr['ddm_article']['&nbsp;&nbsp; '.cut_text($conval).'&nbsp;&nbsp; '] = '?module=articles&c='.$conkey;
        		if (!isset($dyna['min'])){
        		$articles -> setWorkContainer($conkey);
         		$art = $articles -> getCategories();
         		$count2 = 0;
         		if (is_array($art)){
         			unset($arr['ddm_article']['&nbsp;&nbsp; '.cut_text($conval).'&nbsp;&nbsp; ']);
         			$arr['ddm_article'][$pic_right.'&nbsp;&nbsp; '.cut_text($conval).'&nbsp;&nbsp; '] = array('-' => '?module=articles&c='.$conkey);
         			$art = array_reverse($art);
         			foreach ($art as $artkey => $artval){
         				$count2++;
         				if ($count2 != $dyna['max']) {
         					$arr['ddm_article'][$pic_right.'&nbsp;&nbsp; '.cut_text($conval).'&nbsp;&nbsp; ']['&nbsp;&nbsp; '.cut_text($artval['title']).'&nbsp;&nbsp;'] = '?module=articles&c='.$conkey.'&b='.$artval['id'];
         					$art2 = $articles -> getArticles($artval['id']);
         					$count3 = 0;
         					if (count($art2) > 0){
         						unset($arr['ddm_article'][$pic_right.'&nbsp;&nbsp; '.cut_text($conval).'&nbsp;&nbsp; ']['&nbsp;&nbsp; '.cut_text($artval['title']).'&nbsp;&nbsp;']);
         						$arr['ddm_article'][$pic_right.'&nbsp;&nbsp; '.cut_text($conval).'&nbsp;&nbsp; '][$pic_right.'&nbsp;&nbsp; '.cut_text($artval['title']).'&nbsp;&nbsp;'] = array('-' => '?module=articles&c='.$conkey.'&b='.$artval['id']);
         						$art2 = array_reverse($art2);
         						foreach ($art2 as $art2key => $art2val){
         							$count3++;
         							if ($count3 != $dyna['max'])
         								$arr['ddm_article'][$pic_right.'&nbsp;&nbsp; '.cut_text($conval).'&nbsp;&nbsp; '][$pic_right.'&nbsp;&nbsp; '.cut_text($artval['title']).'&nbsp;&nbsp;']['&nbsp;&nbsp;'.cut_text($art2val['title']).'&nbsp;&nbsp;'] = '?module=articles&c='.$conkey.'&b='.$artval['id'].'&a='.$art2val['id'];
         					}
         				}
         			}
         		}
         	}
         }
      }
		}
		}
		$item[4] = 'ddm_article';
		}
			$item[3] = 'articles.png';
		} elseif ($item[0] == '?module=gallery') {
			if (!isset($dyna['use_gal'])){
			$gallery = new gallery();
			$kw = $gallery -> getAvaiableValues('keywords');
			$count = 0;
			if (is_array($kw)){
				$kw = array_reverse($kw);
				$count++;;
				if (!isset($dyna['min']))
				foreach($kw as $key => $val){
					if ($count != $dyna['max']) {
					$arr['ddm_gallery'][$pic_right.'&nbsp;&nbsp;'.__('By keywords').'&nbsp;&nbsp;'][$pic_right.'&nbsp;&nbsp;'.cut_text($val).'&nbsp;&nbsp;'] = array('-' => '?module=gallery&keyword='.$val);
					$kw2 = $gallery->getLimitedImagesList('keywords', $val);
					$kw2 = array_reverse($kw2);
					$count2 = 0;
					foreach ($kw2 as $key2 => $val2){
						$count2++;
						if ($count2 != $dyna['max']) {
						$arr['ddm_gallery'][$pic_right.'&nbsp;&nbsp;'.__('By keywords').'&nbsp;&nbsp;'][$pic_right.'&nbsp;&nbsp;'.cut_text($val).'&nbsp;&nbsp;']['&nbsp;&nbsp;'.cut_text($val2).'&nbsp;&nbsp;'] = '?module=gallery&id='.$val2;
						}
					}
					}
				}
			}
			$kw = $gallery -> getAvaiableValues('size');
			$count = 0;
			if (is_array($kw)){
				$kw = array_reverse($kw);
				$count++;
				$item[1] .= '&nbsp;'.$pic_down;
				if (!isset($dyna['min']))
				foreach($kw as $key => $val){
					if ($count != $dyna['max']) {
					$arr['ddm_gallery'][$pic_right.'&nbsp;&nbsp;'.__('By size').'&nbsp;&nbsp;'][$pic_right.'&nbsp;&nbsp;'.cut_text($val).'&nbsp;&nbsp;'] = array('-' => '?module=gallery&size='.$val);
					$kw2 = $gallery->getLimitedImagesList('size', $val);
					$kw2 = array_reverse($kw2);
					$count2 = 0;
					foreach ($kw2 as $key2 => $val2){
						$count2++;
						if ($count2 != $dyna['max']) {
						$arr['ddm_gallery'][$pic_right.'&nbsp;&nbsp;'.__('By size').'&nbsp;&nbsp;'][$pic_right.'&nbsp;&nbsp;'.cut_text($val).'&nbsp;&nbsp;']['&nbsp;&nbsp;'.cut_text($val2).'&nbsp;&nbsp;'] = '?module=gallery&id='.$val2;
						}
					}
					}
				}
			}
			$kw = $gallery -> getAvaiableValues('type');
			$count = 0;
			if (is_array($kw)){
				$kw = array_reverse($kw);
				$count++;
				if (!isset($dyna['min']))
				foreach($kw as $key => $val){
					if ($count != $dyna['max']) {
					$arr['ddm_gallery'][$pic_right.'&nbsp;&nbsp;'.__('By type').'&nbsp;&nbsp;'][$pic_right.'&nbsp;&nbsp;'.cut_text($val).'&nbsp;&nbsp;'] = array('-' => '?module=gallery&type='.$val);
					$kw2 = $gallery->getLimitedImagesList('type', $val);
					$kw2 = array_reverse($kw2);
					$count2 = 0;
					foreach ($kw2 as $key2 => $val2){
						$count2++;
						if ($count2 != $dyna['max']) {
						$arr['ddm_gallery'][$pic_right.'&nbsp;&nbsp;'.__('By type').'&nbsp;&nbsp;'][$pic_right.'&nbsp;&nbsp;'.cut_text($val).'&nbsp;&nbsp;']['&nbsp;&nbsp;'.cut_text($val2).'&nbsp;&nbsp;'] = '?module=gallery&id='.$val2;
						}
					}
					}
				}
			}
			$kw = $gallery -> getFullImagesList();
			$count = 0;
			if (count($kw) > 0){
				$kw = array_reverse($kw);
				$count++;
				foreach($kw as $key => $val){
					if ($count != $dyna['max']) {
					$arr['ddm_gallery']['&nbsp;&nbsp;'.cut_text($val).'&nbsp;&nbsp;'] = '?module=gallery&id='.$val;
					}
				}
			}
			$item[4] = 'ddm_gallery';
		}
			$item[3] = 'gallery.png';
		} elseif ($item[0] == '?module=user.list') {
			if (!isset($dyna['use_mem'])){
				$userlist = $system->getUserList('*', 'nickname');
				$count = 0;
				if (count($userlist) > 0) {
					$item[1] .= '&nbsp;'.$pic_down;
					$userlist = array_reverse($userlist);
					foreach ($userlist as $conkey => $conval) {
        		$count++;
        		if ($count != $dyna['max'])
         			$arr['ddm_users']['&nbsp;&nbsp;'.cut_text($conval['nickname']).'&nbsp;&nbsp;'] = '?module=user.list&user='.$conval['username'];
					}
				}
			$item[4] = 'ddm_users';
			}
			$item[3] = 'userlist.png';
		} elseif ($item[0] == '?module=filesdb') {
			if (!isset($dyna['use_fdb'])){
			$filesdb = new linksdb(DOWNLOADS_DATAFILE);
			$count = 0;
			if (!empty($filesdb -> data)) {
				$item[1] .= '&nbsp;'.$pic_down;
				$fdb = array_reverse($filesdb -> data);
				foreach ($fdb as $conkey => $conval) {
        	$count++;
        	if ($count != $dyna['max']) {
         		$arr['ddm_filesdb']['&nbsp;&nbsp;'.cut_text($conval['name']).'&nbsp;&nbsp;'] = '?module=filesdb&id='.(sizeof($fdb) - ($count - 1));
         		if (count($conval['files']) > 0)
         			if (!isset($dyna['min'])){
         				unset($arr['ddm_filesdb']['&nbsp;&nbsp;'.cut_text($conval['name']).'&nbsp;&nbsp;']);
         				$arr['ddm_filesdb'][$pic_right.'&nbsp;&nbsp;'.cut_text($conval['name']).'&nbsp;&nbsp;'] = array('-' => '?module=filesdb&id='.(sizeof($fdb) - ($count - 1)));
         				$count2 = 0;
         				$conval['files'] = array_reverse($conval['files']);
         				foreach ($conval['files'] as $artkey => $artval){
         					$count2++;
         					if ($count2 != $dyna['max'])
         						$arr['ddm_filesdb'][$pic_right.'&nbsp;&nbsp;'.cut_text($conval['name']).'&nbsp;&nbsp;']['&nbsp;&nbsp;'.cut_text($artval['name']).'&nbsp;&nbsp;'] = '?module=filesdb&id='.(sizeof($fdb) - ($count - 1)).'&fid='.(sizeof($conval['files']) - ($count2 - 1));
         				}
         			}
						}
					}
				}
			$item[4] = 'ddm_filesdb';
			}
			$item[3] = 'files.png';
		} elseif ($item[0] == '?module=forum') {
			if (!isset($dyna['use_for'])){
			$topics = @unserialize(@file_get_contents(FORUM_PATH . 'topic_index.dat'));
			$count = 0;
			if (count($topics) > 0) {
			$item[1] .= '&nbsp;'.$pic_down;
			if (is_array($topics)){
				$topics = array_reverse($topics);
				foreach ($topics as $conkey => $conval) {
        	$count++;
        	if ($count != $dyna['max'])
         		$arr['ddm_forum']['&nbsp;&nbsp;'.cut_text($conval['title']).'&nbsp;&nbsp;'] = '?module=forum&id='.(sizeof($topics) - ($count)).'&action=topic';
				}
			}
			}
			$item[4] = 'ddm_forum';
		}
			$item[3] = 'forum.png';
		} elseif ($item[1] == 'CUSTOM1') {
			$item[1] = __(file_get_contents(CONFIG_PATH.'custom_menu_title_1.txt')).'&nbsp;'.$pic_down;
    	   	$arr['ddm_custom1'] = arr2ddm(unserialize(file_get_contents( CONFIG_PATH . 'custom_menu_1.dat')));
             		$item[4] = 'ddm_custom1';
			$item[3] = 'custom1.png';
		} elseif ($item[1] == 'CUSTOM2') {
			$item[1] = __(file_get_contents(CONFIG_PATH.'custom_menu_title_2.txt')).'&nbsp;'.$pic_down;
    	   	$arr['ddm_custom2'] = arr2ddm(unserialize(file_get_contents( CONFIG_PATH . 'custom_menu_2.dat')));
               
			$item[4] = 'ddm_custom2';
			$item[3] = 'custom2.png';
		} elseif ($item[1] == 'CUSTOM3') {
			$item[1] = __(file_get_contents(CONFIG_PATH.'custom_menu_title_3.txt')).'&nbsp;'.$pic_down;
    	   	$arr['ddm_custom3'] = arr2ddm(unserialize(file_get_contents( CONFIG_PATH . 'custom_menu_3.dat')));
			$item[4] = 'ddm_custom3';
			$item[3] = 'custom3.png';
		} else {
			$item[3] = 'default.png';
		}
		if (isset($dyna['ico']))
			$item[3] = '<img src="skins/icons/'.$item[3].'">';
		else 
			$item[3]='';
		$menu .= str_replace('{link}', $item[0], str_replace('{title}', $item[1], str_replace('{target}', @$item[2], str_replace('{icon}', $item[3], str_replace('{id}', $item[4], $format)))));
		// End of Icons support by Migel
	}
	$menu .= ' <br clear="both" /> </div>';
	$result = $menu.' <script type="text/javascript"> dhtmlmenu_build('.convertArray($arr,'arr').');</script></div>';
	return $result;
}


function rcms_parse_module_template($module, $tpldata = array()) {
    global $system;
    ob_start();
    if(is_file(CUR_SKIN_PATH . $module . '.php')) {
        include(CUR_SKIN_PATH . $module . '.php');
    } elseif(is_file(MODULES_TPL_PATH . $module . '.php')) {
        include(MODULES_TPL_PATH . $module . '.php');
    }
    $return = ob_get_contents();
    ob_end_clean();
    return $return;
}

function rcms_open_browser_window($id, $link, $attributes = '', $return = false){
	global $system;
	$code = '<script language="javascript">window.open(\'' . addslashes($link) . '\', \'' . $id . '\',\'' . $attributes . '\');</script>';
	if($return){
		return $code;
	} else {
		@$system->config['meta'] .= $code;
	}
}

function rcms_parse_module_template_path($module) {
    if(is_file(CUR_SKIN_PATH . $module . '.php')) {
        return (CUR_SKIN_PATH . $module . '.php');
    } elseif(is_file(MODULES_TPL_PATH . $module . '.php')) {
        return (MODULES_TPL_PATH . $module . '.php');
    } else {
        return false;
    }
}

function rcms_show_element($element, $parameters = ''){
    global $system;
    switch($element){
        case 'title':
            if(empty($system->config['hide_title'])) {
                echo $system->config['title'];
                if(!empty($system->config['pagename'])) echo ' - ';
            }
            echo (!empty($system->config['pagename'])) ? $system->config['pagename'] : '';
            break;
        case 'menu_point':
            list($point, $template) = explode('@', $parameters);
            
    		if(is_file(CUR_SKIN_PATH . 'skin.' . $template . '.php')) {
        		$tpl_path = CUR_SKIN_PATH . 'skin.' . $template . '.php';
    		} elseif(is_file(MODULES_TPL_PATH . $template . '.php')) {
        		$tpl_path = MODULES_TPL_PATH . $template . '.php';
    		}
    		
            if(!empty($tpl_path) && !empty($system->output['menus'][$point])){
                foreach($system->output['menus'][$point] as $module){
                    $system->showWindow($module[0], $module[1], $module[2], $tpl_path);
                }
            }
            break;
        case 'main_point':
            foreach ($system->output['modules'] as $module) {
                $system->showWindow($module[0], $module[1], $module[2], CUR_SKIN_PATH . 'skin.' . substr(strstr($parameters, '@'), 1) . '.php');
            }
            break;
        case 'navigation':
        		$dyna = parse_ini_file(CONFIG_PATH . 'dynamik.ini', true);
        		if (isset($dyna['use'])) {
        				echo rcms_parse_dynamik_menu($parameters);
        				break;
        				}
            echo rcms_parse_menu($parameters);
            break;
        case 'meta':
            readfile(DATA_PATH . 'meta_tags.html');
            echo '<meta http-equiv="Content-Type" content="text/html; charset=' . $system->config['encoding'] . '" />' . "\r\n";
            if(!empty($system->config['enable_rss'])){
                foreach ($system->feeds as $module => $d) {
                    echo '<link rel="alternate" type="application/xml" title="RSS ' . $d[0] . '" href="./rss.php?m=' . $module . '" />' . "\r\n";
                }
            }
            if(!empty($system->config['meta'])) echo $system->config['meta'];
            break;
        case 'copyright':
            if(!defined('RCMS_COPYRIGHT_SHOWED') || !RCMS_COPYRIGHT_SHOWED){
                echo RCMS_POWERED . ' ' . RCMS_VERSION_A . '.'  . RCMS_VERSION_B . '.' . RCMS_VERSION_C . RCMS_VERSION_SUFFIX . ' ' . RCMS_COPYRIGHT.'<br /> ';
            }
            break;
    }
}
?>
