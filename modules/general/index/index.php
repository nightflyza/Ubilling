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

$intro = file_get_contents(DATA_PATH . 'intro.html');
if(!empty($intro) && !@$system->config['wmh']){
	show_window('', $intro, 'left');
}

if(!empty($menu_points['index-menus'])){
	$old_point = $system->current_point;
	$system->setCurrentPoint('index-menus');
	$c_module = $module;
	foreach ($menu_points['index-menus'] as $menu){
		if(substr($menu, 0, 4) == 'ucm:' && is_readable(DF_PATH . substr($menu, 4) . '.ucm')) {
			$file = file(DF_PATH . substr($menu, 4) . '.ucm');
			$title = preg_replace("/[\n\r]+/", '', $file[0]);
			$align = preg_replace("/[\n\r]+/", '', $file[1]);
			unset($file[0]);
			unset($file[1]);
			show_window($title, implode('', $file), $align);
		} elseif (!empty($system->modules['menu'][$menu])){
			$module = $menu;
			$module_dir = MODULES_PATH . $menu;
			require(MODULES_PATH . $menu . '/index.php');
		} else {
			show_window('', __('Module not found'), 'center');
		}
	}
	$system->setCurrentPoint('index-main');
	$module = $c_module;
}

if(empty($system->config['index_module']) || $system->config['index_module'] == 'news' || $system->config['index_module'] == 'default'){
	$articles = new articles();
	if(!$articles->setWorkContainer('news')){
		show_error($articles->last_error);
	} else {
		$result = '';
		if(($list = $articles->getStat('time')) !== false){
			if(!empty($system->config['perpage'])) {
				$pages = ceil(sizeof($list) / $system->config['perpage']);
				if(!empty($_GET['page']) && ((int) $_GET['page']) > 0) {
					$page = ((int) $_GET['page'])-1;
				} else {
					$page = 0;
				}
				$start = $page * $system->config['perpage'];
				$total = $system->config['perpage'];
				$end = $total + $start;
				if($end > sizeof($list)) $end = sizeof($list);
			} else {
				$pages = 1;
				$page = 0;
				$start = 0;
				$total = sizeof($list);
			}
			$keys = array_keys($list);
			for ($a = $start; $a < $end; $a++){
				$time = &$list[$keys[$a]];
				$id = explode('.', $keys[$a]);
				if(($category = $articles->getCategory($id[0], true)) !== false && ($article = $articles->getArticle($id[0], $id[1], true, true, false, false)) !== false){
					$result .= rcms_parse_module_template('art-article.tpl', $article + array('showtitle' => true,
					'linktext' => (($article['text_nonempty']) ? __('Read more...') : __('Comments')) . ' (' . $article['comcnt'] . '/' . $article['views'] . ')',
					'iconurl' => '?module=articles&amp;c=news&amp;b=' . $id[0],
					'linkurl' => '?module=articles&amp;c=news&amp;b=' . $id[0] . '&amp;a=' . $article['id'],
					'cat_data' => $category));
				}
			}
			$result .= '<div align="right">' . rcms_pagination(sizeof($list), $system->config['perpage'], $page + 1, '?module=' . $module) . '</div>';
		}
		show_window(__('Latest news'), $result);
	}
	$system->config['pagename'] = __('Latest news');
} elseif ($system->config['index_module'] != 'empty' && !empty($system->modules['main'][$module])){
	$my_module = $module;
	$module = $system->config['index_module'];
	include_once(MODULES_PATH . $module . '/index.php');
	$module = $my_module;
}

if(!empty($menu_points['index-menus'])){
	$system->setCurrentPoint($old_point);
	show_window(__('Index'), rcms_parse_module_template('index.tpl'));
}
?>