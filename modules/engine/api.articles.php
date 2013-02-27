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
define('ARTICLES_PATH', DATA_PATH . 'a/');

class articles{
	var $containers = array();
	var $categories = array();
	var $articles = array();
	var $container = '';
	var $index = array();

	var $last_error = '';

	//---------------------------------------------------------------------------------//
	// Containers

	function getContainers($all = 1){
		$res = rcms_scandir(ARTICLES_PATH, '', 'dir');
		foreach ($res as $dir){
			if($all == 2 || ($all == 1 && $dir != '#hidden') || ($all == 0 && $dir != '#hidden' && $dir != '#root')){
				$this->containers[$dir] = __(file_get_contents(ARTICLES_PATH . $dir . '/title'));
			}
		}
		return $this->containers;
	}

	function setWorkContainer($id){
		if(empty($id) || preg_replace("/#{0,1}[\d\w]+/i", '', $id) != '') {
			$this->last_error = __('Invalid ID');
			return false;
		}
		if(!is_dir(ARTICLES_PATH . $id)) {
			$this->last_error = __('Section with this ID doesn\'t exists');
			return false;
		}
		$this->container = $id;
		return $this->getIndex();
	}

	function createContainer($id, $title){
		if(empty($id) || preg_replace("/[\d\w]+/i", '', $id) != '') {
			$this->last_error = __('Invalid ID');
			return false;
		}
		if(is_dir(ARTICLES_PATH . $id) || is_file(ARTICLES_PATH . $id)) {
			$this->last_error = __('Section with this ID already exists');
			return false;
		}
		if(!rcms_mkdir(ARTICLES_PATH . $id)){
			$this->last_error = __('Cannot create directory');
			return false;
		}
		if(!file_write_contents(ARTICLES_PATH . $id . '/title', $title) || !file_write_contents(ARTICLES_PATH . $id . '/lst', 0)){
			$this->last_error = __('Cannot write to file');
			return false;
		}
		$this->containers[$id] = $title;
		return true;
	}

	function editContainer($id, $newid, $newtitle){
		if($id == '#root' || $id == '#hidden') {
			$this->last_error = __('Cannot rename system section');
			return false;
		}
		if(empty($id) || preg_replace("/[\d\w]+/i", '', $id) != '') {
			$this->last_error = __('Invalid ID');
			return false;
		}
		if(empty($newid) || preg_replace("/[\d\w]+/i", '', $newid) != '') {
			$this->last_error = __('Invalid ID');
			return false;
		}
		if(!is_dir(ARTICLES_PATH . $id)) {
			$this->last_error = __('Section with this ID doesn\'t exists');
			return false;
		}
		if($id != $newid && is_dir(ARTICLES_PATH . $newid)) {
			$this->last_error = __('Section with this ID already exists');
			return false;
		}
		if($id !== $newid && !rcms_rename_file(ARTICLES_PATH . $id, ARTICLES_PATH . $newid)){
			$this->last_error = __('Cannot change id');
			return false;
		}
		if(!file_write_contents(ARTICLES_PATH . $newid . '/title', $newtitle)){
			$this->last_error = __('Cannot write to file');
			return false;
		}
		unset($this->containers[$id]);
		$this->containers[$newid] = $newtitle;
		return true;
	}

	function removeContainer($id){
		if(empty($id) || preg_replace("/[\d\w]+/i", '', $id) != '') {
			$this->last_error = __('Invalid ID');
			return false;
		}
		if($id == '#root' || $id == '#hidden') {
			$this->last_error = __('Cannot remove system section');
			return false;
		}
		if(!is_dir(ARTICLES_PATH . $id)) {
			$this->last_error = __('Section with this ID doesn\'t exists');
			return false;
		}
		if(!rcms_delete_files(ARTICLES_PATH . $id, true)){
			$this->last_error = __('Cannot remove section');
			return false;
		}
		unset($this->containers[$id]);
		return true;
	}

	//---------------------------------------------------------------------------------//
	// Categories

	function getCategories($short = false, $parse = true, $ret_last_article = false){
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		if($this->container == '#root' || $this->container == '#hidden'){
			$this->last_error = __('This system section doesn\'t have categories');
			return false;
		}
		$return = array();
		$path = ARTICLES_PATH . $this->container . '/';
		if($categories = rcms_scandir($path, '', 'dir')) {
			foreach ($categories as $id => $category){
				if($data = $this->getCategory($category, $parse, $ret_last_article)){
					if(!$short) {
						$return[] = $data;
					} else {
						$return[$data['id']] = $data['title'];
					}
				}
			}
		}
		return $return;
	}

	function getCategory($cat_id = 0, $parse = true, $ret_last_article = false){
		$cat_id = (int) $cat_id;
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		if($this->container == '#root' || $this->container == '#hidden'){
			$this->last_error = __('This system section doesn\'t have categories');
			return false;
		}
		global $system;
		$cat_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/';
		if(empty($cat_id)){
			$this->last_error = __('Invalid category ID');
			return false;
		}
		$cat_data = &$this->categories[$this->container][$cat_id];

		// Checking access level
		$cat_data['accesslevel'] = (!is_file($cat_prefix . 'access')) ? 0 : (int) file_get_contents($cat_prefix . 'access');
		if($cat_data['accesslevel'] > @$system->user['accesslevel'] && !$system->checkForRight('-any-')) {
			$this->last_error = __('Access denied');
			return false;
		}
		// If category exists
		if(is_dir($cat_prefix)){
			$cat_data['id'] = $cat_id;
			$cat_data['title'] = file_get_contents($cat_prefix . 'title');
			$cat_data['description'] = @file_get_contents($cat_prefix . 'description');
			if($parse) $cat_data['description'] = rcms_parse_text($cat_data['description'], true, false, true, false, true);
			$cat_data['articles_clv'] = sizeof(rcms_scandir($cat_prefix, '', 'dir'));
			if($ret_last_article && $cat_data['articles_clv'] > 0){
				$stat = $this->getStat('time', $cat_id, true);
				if(!empty($stat)) {
					$id = explode('.', $stat['key']);
					$cat_data['last_article'] = $this->getArticle($cat_id, $id[1], $parse, false, false, false);
				}
			}
			// Search for icon
			if(is_file($cat_prefix . 'icon.gif')) {
				$cat_data['icon'] = 'icon.gif';
				$cat_data['iconfull'] = $cat_prefix . 'icon.gif';
			} elseif(is_file($cat_prefix . 'icon.png')) {
				$cat_data['icon'] = 'icon.png';
				$cat_data['iconfull'] = $cat_prefix . 'icon.png';
			} elseif(is_file($cat_prefix . 'icon.jpg')) {
				$cat_data['icon'] = 'icon.jpg';
				$cat_data['iconfull'] = $cat_prefix . 'icon.jpg';
			}elseif(is_file($cat_prefix . 'icon.jpeg'))  {
				$cat_data['icon'] = 'icon.jpeg';
				$cat_data['iconfull'] = $cat_prefix . 'icon.jpeg';
			} else $cat_data['icon'] = false;
			// Finish!
			return $cat_data;
		} else {
			$this->last_error = __('There are no category with this ID');
			return false;
		}
	}

	function createCategory($title, $desc = '', $icon = array(), $access = 0) {
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		if($this->container == '#root' || $this->container == '#hidden'){
			$this->last_error = __('This system section doesn\'t have categories');
			return false;
		}
		// If title is empty we cannot create category
		if(empty($title)) {
			$this->last_error = __('Title is empty');
			return false;
		}
		if(is_readable(ARTICLES_PATH . $this->container . '/lst')) {
			$cat_id = (int) @file_get_contents(ARTICLES_PATH . $this->container . '/lst') + 1;
		} else {
			$cat_id = 1;
		}
		$cat_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/';
		$cat_data = &$this->categories[$this->container][$cat_id];
		rcms_mkdir($cat_prefix);
		// Now we can safely create category files
		file_write_contents($cat_prefix . 'title', $title);
		file_write_contents($cat_prefix . 'description', $desc);
		file_write_contents($cat_prefix . 'access', $access);
		file_write_contents($cat_prefix . 'lst', '0');
		// If there is an icon uploaded let's parse it
		if(!empty($icon) && empty($icon['error'])){
			$icon['name'] = basename($icon['name']);
			$icon['tmp']  = explode('.', $icon['name']);
			if($icon['type'] == 'image/gif' || $icon['type'] == 'image/jpeg' || $icon['type'] == 'image/png'){
				move_uploaded_file($icon['tmp_name'], $cat_prefix . 'icon.' . $icon['tmp'][sizeof($icon['tmp'])-1]);
			} else {
				$this->last_error = __('Category created without icon');
				return false;
			}
		}
		file_write_contents(ARTICLES_PATH . $this->container . '/lst', $cat_id);
		return true;
	}

	function editCategory($cat_id, $title, $desc, $icon = array(), $access = 0, $killicon = false) {
		$cat_id = (int) $cat_id;
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		if($this->container == '#root' || $this->container == '#hidden'){
			$this->last_error = __('This system section doesn\'t have categories');
			return false;
		}
		// If title is empty we cannot create category
		if(empty($title)) {
			$this->last_error = __('Title is empty');
			return false;
		}
		$cat_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/';
		$cat_data = &$this->categories[$this->container][$cat_id];
		if(!$cat_data = $this->getCategory($cat_id, false)) {
			$this->last_error = __('There are no category with this ID');
			return false;
		}
		file_write_contents($cat_prefix . 'title', $title);
		file_write_contents($cat_prefix . 'description', $desc);
		file_write_contents($cat_prefix . 'access', $access);
		if(!empty($killicon)) rcms_delete_files($cat_data['iconfull']);
		if(!empty($icon) && empty($icon['error'])){
			$icon['name'] = basename($icon['name']);
			$icon['tmp']  = explode('.', $icon['name']);
			if($icon['type'] == 'image/gif' || $icon['type'] == 'image/jpeg' || $icon['type'] == 'image/png'){
				move_uploaded_file($icon['tmp_name'], $cat_prefix . 'icon.' . $icon['tmp'][sizeof($icon['tmp'])-1]);
			} else {
				$this->last_error = __('Category saved without icon');
				return false;
			}
		}
		return true;
	}

	function deleteCategory($cat_id) {
		$cat_id = (int) $cat_id;
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		if($this->container == '#root' || $this->container == '#hidden'){
			$this->last_error = __('This system section doesn\'t have categories');
			return false;
		}
		if(!$cat_data = $this->getCategory($cat_id, false)) {
			$this->last_error = __('There are no category with this ID');
			return false;
		}

		$cat_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/';
		rcms_remove_index($cat_id, $this->index, true);
		$this->saveIndex();
		rcms_delete_files($cat_prefix, true);
		return true;
	}

	//---------------------------------------------------------------------------------//
	// Articles

	function getArticles($cat_id, $parse = true, $desc = false, $text = false) {
		$cat_id = (int) $cat_id;
		global $system;
		if($this->container !== '#root' && $this->container !== '#hidden'){
			if(!($category = $this->getCategory($cat_id))){
				return false;
			}
			if(!$system->checkForRight('-any-') && $category['accesslevel'] > (int)@$system->user['accesslevel']) {
				$this->last_error = __('Access denied');
				return false;
			}
			$dir = ARTICLES_PATH . $this->container . '/' . $cat_id;
		} else {
			$dir = ARTICLES_PATH . $this->container;
		}
		$return = array();

		if($articles = rcms_scandir($dir, '', 'dir')) {
			foreach ($articles as $art_id) {
				$return[] = $this->getArticle($cat_id, $art_id, $parse, $desc, $text);
			}
		}
		return $return;
	}

	function getArticle($cat_id, $art_id, $parse = true, $desc = false, $text = false, $cnt = false) {
		$cat_id = (int) $cat_id;
		$art_id = (int) $art_id;
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		global $system;
		if($this->container !== '#root' && $this->container !== '#hidden'){
			if(!($category = $this->getCategory($cat_id))){
				$this->last_error = __('There are no category with this ID');
				return false;
			}
			if($category['accesslevel'] > (int)@$system->user['accesslevel'] && !$system->checkForRight('-any-')) {
				$this->last_error = __('Access denied');
				return false;
			}
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/' . $art_id . '/';
			$art_data = &$this->articles[$this->container][$cat_id][$art_id];
		} else {
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $art_id . '/';
			$art_data = &$this->articles[$this->container][$art_id];
		}
		if(is_file($art_prefix . 'define')){
			$art_data = rcms_parse_ini_file($art_prefix . 'define');
			if($cnt){
				$art_data['views']++;
				if(!write_ini_file($art_data, $art_prefix . 'define')){
					$this->last_error = __('Cannot write to file');
				} else {
					$this->index[$cat_id][$art_id]['view'] = $art_data['views'];
					$this->saveIndex();
				}
			}
			$art_data['text_nonempty'] = (filesize($art_prefix . 'full') < 1) ? false : true;
			if($desc) {
				$art_data['desc'] = file_get_contents($art_prefix . 'short');
				if ($parse) $art_data['desc'] = rcms_parse_text_by_mode($art_data['desc'], $art_data['mode']);
			}
			if($text) {
				$art_data['text'] = file_get_contents($art_prefix . 'full');
				if ($parse) $art_data['text'] = rcms_parse_text_by_mode($art_data['text'], $art_data['mode']);
			}
			$art_data['id'] = $art_id;
			$art_data['catid'] = $cat_id;
			$art_data['comcnt'] = $art_data['comcount'];
			$art_data['title'] = str_replace('&quot;', '"', $art_data['title']);
			return $art_data;
		} else {
			$this->last_error = __('There are no article with this ID');
			return false;
		}
	}

	function saveArticle($cat_id, $art_id, $title, $src, $keywords, $sef_desc, $desc, $text, $mode = 'text', $comments = 'yes') {
		$cat_id = (int) $cat_id;
		$art_id = (int) $art_id;
		global $system;
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		$new_flag = ($art_id == 0);
		if($this->container !== '#root' && $this->container !== '#hidden'){
			if(!($category = $this->getCategory($cat_id))){
				return false;
			}
			if($category['accesslevel'] > (int)@$system->user['accesslevel'] && !$system->checkForRight('-any-')) {
				$this->last_error = __('Access denied');
				return false;
			}
			$cat_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/';
			if($new_flag) $art_id = @file_get_contents($cat_prefix . 'lst') + 1;
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/' . $art_id . '/';
			$art_data = &$this->articles[$this->container][$cat_id][$art_id];
		} else {
			$cat_prefix = ARTICLES_PATH . $this->container . '/';
			if($new_flag) $art_id = @file_get_contents($cat_prefix . 'lst') + 1;
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $art_id . '/';
			$art_data = &$this->articles[$this->container][$art_id];
		}
		// For security reasons all html will be striped off
		$title = str_replace('"', '&quot;', trim(strip_tags($title)));
		$src   = trim(strip_tags($src));
		$text  = trim($text);
		$desc  = trim($desc);
		// Now check for empty fields
		if(empty($title)) {
			$this->last_error = __('Title is empty');
			return false;
		}
		if(empty($src)) $src = "-";
		if(empty($text) && empty($desc)) {
			$this->last_error = __('Text is empty');
			return false;
		}
		if(empty($desc)) {
			$desc = substr($text, 0, 250) . ((strlen($text) > 250) ? ' ...' : '');
		}
		if(!$new_flag && ($old = $this->getArticle($cat_id, $art_id, false, false, false, false)) === false){
			$this->last_error = __('There are no article with this ID');
			return false;
		}
		if(!is_dir($art_prefix)) rcms_mkdir($art_prefix);
		// Writing files
		if($new_flag){
			$add_data = array(
			'author_nick' => $system->user['nickname'],
			'author_name' => $system->user['username'],
			'time' => rcms_get_time(),
			);
		} else {
			$add_data = array(
			'author_nick' => $old['author_nick'],
			'author_name' => $old['author_name'],
			'time' => $old['time'],
			);
		}

		if(!write_ini_file(array_merge(array('title' => $title, 'src'  => $src, 'keywords' => strip_tags($keywords), 'sef_desc' => strip_tags($sef_desc), 'comments' => $comments, 'views' => (!$new_flag) ? $old['views'] : 0, 'mode' => $mode, 'comcount' => (!$new_flag) ? $old['comcount'] : 0), $add_data), $art_prefix . 'define') || !file_write_contents($art_prefix . 'short', $desc) || !file_write_contents($art_prefix . 'full', $text)){
			$this->last_error = __('Error while saving article');
			return false;
		}
		if($new_flag && !file_write_contents($cat_prefix . 'lst', $art_id)){
			$this->last_error = __('Cannot update last article flag');
			return false;
		}
		if($this->container !== '#root' && $this->container !== '#hidden') {
			$this->index[$cat_id][$art_id]['time'] = $add_data['time'];
			$this->index[$cat_id][$art_id]['ccnt'] = (!$new_flag) ? $old['comcount'] : 0;
			$this->index[$cat_id][$art_id]['view'] = (!$new_flag) ? $old['views'] : 0;
			if($new_flag) $this->index[$cat_id][$art_id]['lcnt'] = 0;
		} else {
			$this->index[$art_id]['time'] = $add_data['time'];
			$this->index[$art_id]['ccnt'] = (!$new_flag) ? $old['comcount'] : 0;
			$this->index[$art_id]['view'] = (!$new_flag) ? $old['views'] : 0;
			if($new_flag) $this->index[$art_id]['lcnt'] = 0;
		}
		$_SESSION['art_id'] = $art_id;
		return $this->saveIndex();
	}

	function moveArticle($cat_id, $art_id, $new_cat_id) {
		$cat_id = (int) $cat_id;
		$art_id = (int) $art_id;
		$new_cat_id = (int) $new_cat_id;
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		if($this->container == '#root' || $this->container == '#hidden'){
			$this->last_error = __('This system section doesn\'t have categories');
			return false;
		}
		$cat_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/';
		$art_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/' . $art_id . '/';
		if(!is_dir($art_prefix)){
			$this->last_error = __('Invalid article');
			return false;
		}
		$ncat_prefix = ARTICLES_PATH . $this->container . '/' . $new_cat_id . '/';
		if(!is_dir($ncat_prefix)){
			$this->last_error = __('Invalid target category');
			return false;
		}
		$new_art_id = @file_get_contents($ncat_prefix . 'lst') + 1;
		$nart_prefix = ARTICLES_PATH . $this->container . '/' . $new_cat_id . '/' . $new_art_id . '/';
		rcms_rename_file($art_prefix, $nart_prefix);
		file_write_contents($ncat_prefix . 'lst', file_get_contents($ncat_prefix . 'lst') + 1);
		$this->index[$new_cat_id][$new_art_id] = $this->index[$cat_id][$art_id];

		rcms_remove_index($art_id, $this->index[$cat_id], true);
		return $this->saveIndex();
	}

	function moveArticleToContainer($src_container, $cat_id, $art_id, $new_container, $new_cat_id = 0) {
		$cat_id = (int) $cat_id;
		$art_id = (int) $art_id;
		if(!$this->setWorkContainer($src_container)){
			return false;
		}
		if(!$this->setWorkContainer($new_container)){
			return false;
		}
		if(!$this->setWorkContainer($src_container)){
			return false;
		}
		if($src_container == '#root' || $src_container == '#hidden'){
			$cat_prefix = ARTICLES_PATH . $src_container . '/';
			$art_prefix = ARTICLES_PATH . $src_container . '/' . $art_id . '/';
		} else {
			$cat_prefix = ARTICLES_PATH . $src_container . '/' . $cat_id . '/';
			$art_prefix = ARTICLES_PATH . $src_container . '/' . $cat_id . '/' . $art_id . '/';
		}
		if($new_container == '#root' || $new_container == '#hidden'){
			$ncat_prefix = ARTICLES_PATH . $new_container . '/';
		} else {
			$ncat_prefix = ARTICLES_PATH . $new_container . '/' . $new_cat_id . '/';
		}
		if(!is_dir($art_prefix)){
			$this->last_error = __('Invalid article');
			return false;
		}
		if(!is_dir($ncat_prefix)){
			$this->last_error = __('Invalid target category');
			return false;
		}
		if($src_container == '#root' || $src_container == '#hidden'){
			$data = $this->index[$art_id];
			rcms_remove_index($art_id, $this->index, true);
		} else {
			$data = $this->index[$cat_id][$art_id];
			rcms_remove_index($art_id, $this->index[$cat_id], true);
		}
		$this->saveIndex();
		$new_art_id = @file_get_contents($ncat_prefix . 'lst') + 1;
		$nart_prefix = $ncat_prefix . $new_art_id . '/';
		rcms_rename_file($art_prefix, $nart_prefix);
		file_write_contents($ncat_prefix . 'lst', file_get_contents($ncat_prefix . 'lst') + 1);
		if(!$this->setWorkContainer($new_container)){
			return false;
		}
		if($new_container == '#root' || $new_container == '#hidden'){
			$this->index[$new_art_id] = $data;
		} else {
			$this->index[$new_cat_id][$new_art_id] = $data;
		}
		return $this->saveIndex();
	}

	function deleteArticle($cat_id, $art_id) {
		$cat_id = (int) $cat_id;
		$art_id = (int) $art_id;
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		global $system;
		if($this->container !== '#root' && $this->container !== '#hidden'){
			if(!($category = $this->getCategory($cat_id))){
				$this->last_error = __('There are no category with this ID');
				return false;
			}
			if($category['accesslevel'] > (int)@$system->user['accesslevel'] && !$system->checkForRight('-any-')) {
				$this->last_error = __('Access denied');
				return false;
			}
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/' . $art_id . '/';
			$art_data = &$this->articles[$this->container][$cat_id][$art_id];
		} else {
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $art_id . '/';
			$art_data = &$this->articles[$this->container][$art_id];
		}
		rcms_delete_files($art_prefix, true);

		if($this->container !== '#root' && $this->container !== '#hidden') {
			rcms_remove_index($art_id, $this->index[$cat_id], true);
			unset($this->index[$cat_id][$art_id]);
		} else {
			rcms_remove_index($art_id, $this->index, true);
		}
		$this->saveIndex();
		return true;
	}

	//---------------------------------------------------------------------------------//
	// Comments

	function getComments($cat_id, $art_id) {
		$cat_id = (int) $cat_id;
		$art_id = (int) $art_id;
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		if($this->container !== '#root' && $this->container !== '#hidden'){
			if(!($category = $this->getCategory($cat_id))){
				return false;
			}
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/' . $art_id . '/';
		} else {
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $art_id . '/';
		}
		if($data = @file_get_contents($art_prefix . 'comments')){
			$data = unserialize($data);
			foreach($data as $i => $msg){
				if(!empty($data[$i])) $data[$i]['text'] = rcms_parse_text($data[$i]['text'], true, false, true, 50);
			}
			return $data;
		} else return array();
	}

	function addComment($cat_id, $art_id, $text) {
		$cat_id = (int) $cat_id;
		$art_id = (int) $art_id;
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		global $system;
		if($this->container !== '#root' && $this->container !== '#hidden'){
			if(!($category = $this->getCategory($cat_id))){
				return false;
			}
			if($category['accesslevel'] > (int)@$system->user['accesslevel'] && !$system->checkForRight('-any-')) {
				$this->last_error = __('Access denied');
				return false;
			}
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/' . $art_id . '/';
		} else {
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $art_id . '/';
		}
		if(is_file($art_prefix . 'define')){
			if($data = @file_get_contents($art_prefix . 'comments')) $data = unserialize($data); else $data = array();
			$article_data = rcms_parse_ini_file($art_prefix . 'define');
			// Forming new message
			$newmesg['author_user'] = $system->user['username'];
			$newmesg['author_nick'] = $system->user['nickname'];
			$newmesg['time'] = rcms_get_time();
			$newmesg['text'] = substr($text, 0, 2048);
			$newmesg['author_ip'] = $_SERVER['REMOTE_ADDR'];  // Matrix haz you neo ;)
			// Including new message
			$data[] = $newmesg;
			$save = serialize($data);
			iF(!file_write_contents($art_prefix . 'comments', serialize($data))){
				$this->last_error = __('Cannot write to file');
				return false;
			}
			$article_data['comcount']++;
			if(!write_ini_file($article_data, $art_prefix . 'define')){
				$this->last_error = __('Cannot write to file');
				return false;
			}
			if($this->container !== '#root' && $this->container !== '#hidden') {
				$this->index[$cat_id][$art_id]['ccnt']++;
				$this->index[$cat_id][$art_id]['lcnt'] = $newmesg['time'];
			} else {
				$this->index[$art_id]['ccnt']++;
				$this->index[$art_id]['lcnt'] = $newmesg['time'];
			}
			$res = $this->saveIndex();
			return $res;
		} else {
			$this->last_error = __('There are no article with this ID');
			return false;
		}
	}

	function deleteComment($cat_id, $art_id, $comment) {
		$cat_id = (int) $cat_id;
		$art_id = (int) $art_id;
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		if($this->container !== '#root' && $this->container !== '#hidden'){
			if(!($category = $this->getCategory($cat_id))){
				return false;
			}
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $cat_id . '/' . $art_id . '/';
		} else {
			$art_prefix = ARTICLES_PATH . $this->container . '/' . $art_id . '/';
		}
		if($data = @unserialize(@file_get_contents($art_prefix . 'comments'))){
			if(isset($data[$comment])) {
				rcms_remove_index($comment, $data, true);
				@file_write_contents($art_prefix . 'comments', serialize($data));
				$article_data = rcms_parse_ini_file($art_prefix . 'define');
				$article_data['comcount']--;
				@write_ini_file($article_data, $art_prefix . 'define');
			}
			if($this->container !== '#root' && $this->container !== '#hidden') {
				$this->index[$cat_id][$art_id]['ccnt']--;
			} else {
				$this->index[$art_id]['ccnt']--;
			}
			$res = $this->saveIndex();
			return $res;
		} else return false;
	}

	//---------------------------------------------------------------------------------//
	// Index file

	function getIndex(){
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		$data = @file_get_contents(ARTICLES_PATH . $this->container . '/index');
		if(($this->index = @unserialize($data)) === false){
			$this->index = array();
		}
		return true;
	}

	function getStat($param, $cat_id = 0, $recent_only = false){
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		$result = array();
		if(!$cat_id && $this->container !== '#root' && $this->container !== '#hidden'){
			foreach ($this->index as $cat_id => $arts_data){
				foreach ($arts_data as $art_id => $art_data){
					$result[$cat_id . '.' . $art_id] = $art_data[$param];
				}
			}
		} else {
			if($this->container !== '#root' && $this->container !== '#hidden') $arts_data = &$this->index[$cat_id];
			else $arts_data = &$this->index;
			if(!empty($arts_data)){
				foreach ($arts_data as $art_id => $art_data){
					$result[$cat_id . '.' . $art_id] = $art_data[$param];
				}
			}
		}
		natsort($result);
		$result = array_reverse($result);
		if($recent_only) {
			if(!empty($result)){
				reset($result);
				return each($result);
			} else {
				return false;
			}
		} else {
			return $result;
		}
	}

	function getLimitedStat($param, $limit, $reverse = false){
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		$result = array();
		$return = array();

		if($this->container !== '#root' && $this->container !== '#hidden'){
			foreach ($this->index as $cat_id => $arts_data){
				foreach ($arts_data as $art_id => $art_data){
					$result[$cat_id . '.' . $art_id] = $art_data[$param];
				}
			}
		} else {
			$arts_data = &$this->index;
			if(!empty($arts_data)){
				foreach ($arts_data as $art_id => $art_data){
					$result[$cat_id . '.' . $art_id] = $art_data[$param];
				}
			}
		}
		natsort($result);
		if($reverse) {
			$result = array_reverse($result);
		}
		$i = 1;
		$limits = explode(',', $limit);
		if(sizeof($limits) == 1){
			foreach ($result as $k => $v){
				if($i <= $limits[0]) $return[$k] = $v;
				$i++;
			}
		} else {
			foreach ($result as $k => $v){
				if($i <= $limit[1] && $i >= $limits[0]) $return[$k] = $v;
				$i++;
			}
		}
		return $return;
	}

	function saveIndex(){
		if(empty($this->container)){
			$this->last_error = __('No section selected!');
			return false;
		}
		if(($data = serialize($this->index)) === false){
			$this->last_error = __('Error while converting index');
			return false;
		}
		if(!file_write_contents(ARTICLES_PATH . $this->container . '/index', $data)){
			$this->last_error = __('Error while saving index');
			return false;
		}
		return true;
	}
}
?>