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
$gd_formats = '';
if(function_exists('imagegif')) $gd_formats .= 'gif ';
if(function_exists('imagejpeg')) $gd_formats .= 'jpg jpeg jpe ';
if(function_exists('imagepng')) $gd_formats .= 'png ';
define('GD_SUPPORTED_FORMATS', $gd_formats);
define('GD_SUPPORTED_FORMATS_PREG', '#.*\.(' . implode('|', explode(' ', $gd_formats)) . ')$#i');
define('GALLERY_UPLOAD_DIR', GALLERY_PATH . 'new/');
define('GALLERY_INDEXES_DIR', GALLERY_PATH . 'indexes/');
define('GALLERY_IMAGES_DIR', GALLERY_PATH . 'images/');
define('GALLERY_THUMBS_DIR', GALLERY_PATH . 'thumbnails/');
define('GALLERY_COMMENTS_DIR', GALLERY_PATH . 'comments/');

class gallery{
	var $img_preg = '/.*\.(jpg|jpe|jpeg|gif|png|bmp)$/i';
	var $gd_preg = GD_SUPPORTED_FORMATS_PREG;
	var $path = GALLERY_PATH;
	var $indexes = array();

	function gallery(){
		// Load Index files
		$this->loadIndexFiles();
	}

	function rebuildIndex(){
		@set_time_limit(0);
		
		$this->scanForRemovedImages();
		$this->cleanUpDirectories();
		$this->scanForNewImages();
		$this->cleanUpIndexes();
		$this->saveIndexFiles();
	}
	
	function scanForRemovedImages(){
		foreach ($this->indexes['filename'] as $filename){
			if(!is_file(GALLERY_IMAGES_DIR . $filename)){
				$this->removeImage($filename);
			}
		}
	}
	
	function cleanUpDirectories(){
		$images = rcms_scandir(GALLERY_IMAGES_DIR);
		foreach ($images as $image){
			if(!in_array($image, $this->indexes['filename'])){
				rcms_delete_files(GALLERY_IMAGES_DIR . $image);
			}
		}
		$images = rcms_scandir(GALLERY_COMMENTS_DIR);
		foreach ($images as $image){
			if(!in_array(substr($image, 0, -4), $this->indexes['filename'])){
				rcms_delete_files(GALLERY_COMMENTS_DIR . $image);
			}
		}
		$images = rcms_scandir(GALLERY_THUMBS_DIR);
		foreach ($images as $image){
			if(!in_array(substr($image, 0, -4), $this->indexes['filename'])){
				rcms_delete_files(GALLERY_THUMBS_DIR . $image);
			}
		}
	}
	
	function scanForNewImages(){
		$return = array();
		$new_images = $this->getImages(GALLERY_UPLOAD_DIR);
		foreach ($new_images as $image){
			$image_newname = $image;
			$temp_i = 0;
			$ext = array_reverse(explode('.', $image));
			$ext = $ext[0];
			while(in_array($image_newname, $this->indexes['filename']) || is_file(GALLERY_IMAGES_DIR . $image_newname)){
				$temp_i++;
				$image_newname = substr($image, 0, -(strlen($ext))-1) . '_' . $temp_i . '.' . $ext;
			}
			if(substr($ext, 0, 2) == 'jp') $type = 'jpeg'; else $type = $ext;
			list($width, $height, $x, $x) = getimagesize(GALLERY_UPLOAD_DIR . $image);
			$size = $width . 'x' . $height;
			rcms_rename_file(GALLERY_UPLOAD_DIR . $image, GALLERY_IMAGES_DIR . $image_newname);
			$this->registerInIndex($image_newname, $image_newname, $size, $type);
			$return[$image] = $image_newname;
		}
		return $return;
	}

	function cleanUpIndexes(){
		foreach ($this->indexes['type'] as $type => $images){
			if(empty($images)){
				rcms_remove_index($type, $this->indexes['type'][$type], true);
			}
		}

		foreach ($this->indexes['size'] as $size => $images){
			if(empty($images)){
				rcms_remove_index($size, $this->indexes['size'][$size], true);
			}
		}

		foreach ($this->indexes['keywords'] as $keyword => $images){
			if(empty($images)){
				rcms_remove_index($size, $this->indexes['keywords'][$keyword], true);
			}
		}
	}
	
	function loadIndexFiles(){
		if(!is_readable(GALLERY_INDEXES_DIR . 'main.dat') || !($this->indexes['main'] = @unserialize(file_get_contents(GALLERY_INDEXES_DIR . 'main.dat')))){
			$this->indexes['main'] = array();
			$this->indexes['filename'] = array();
			$this->indexes['title'] = array();
			$this->indexes['size'] = array();
			$this->indexes['type'] = array();
			$this->indexes['keywords'] = array();
			return true;
		}
		$this->indexes['filename'] = @unserialize(@file_get_contents(GALLERY_INDEXES_DIR . 'filename.dat'));
		$this->indexes['title'] = @unserialize(@file_get_contents(GALLERY_INDEXES_DIR . 'title.dat'));
		$this->indexes['size'] = @unserialize(@file_get_contents(GALLERY_INDEXES_DIR . 'size.dat'));
		$this->indexes['type'] = @unserialize(@file_get_contents(GALLERY_INDEXES_DIR . 'type.dat'));
		$this->indexes['keywords'] = @unserialize(@file_get_contents(GALLERY_INDEXES_DIR . 'keywords.dat'));
		return true;
	}

	function registerInIndex($filename, $title, $size, $type){
		$this->indexes['main'][$filename] = array('title' => $title, 'size' => $size, 'type' => $type);
		$this->indexes['filename'][] = $filename;
		$this->indexes['title'][$filename] = $title;
		$this->indexes['size'][$size][] = $filename;
		$this->indexes['type'][strtolower($type)][] = $filename;
	}

	function unregisterInIndex($filename){
		if(empty($this->indexes['main'][$filename])) return false;
		$k_f = array_search($filename, $this->indexes['filename']);
		$size = $this->indexes['main'][$filename]['size'];
		$type = strtolower($this->indexes['main'][$filename]['type']);
		$k_s = @array_search($filename, $this->indexes['size'][$size]);
		$k_t = @array_search($filename, $this->indexes['type'][$type]);

		$this->indexes['filename'][$k_f] = '';
		$this->indexes['size'][$size][$k_s] = '';
		$this->indexes['type'][$type][$k_t] = '';

		$this->unsetKeywords($filename);

		rcms_remove_index($k_f, $this->indexes['filename'], true);
		rcms_remove_index($filename, $this->indexes['title'], true);
		rcms_remove_index($k_s, $this->indexes['size'][$size], true);
		rcms_remove_index($k_t, $this->indexes['type'][$type], true);

		$this->indexes['main'][$filename] = array();
		rcms_remove_index($filename, $this->indexes['main'], true);

		return true;
	}

	function setKeywords($filename, $keywords){
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		$this->indexes['main'][$filename]['keywords'] = $keywords;
		if(!empty($keywords)){
			$keywords_array = explode(';', $keywords);
			foreach ($keywords_array as $keyword){
				$keyword = trim($keyword);
				$this->indexes['keywords'][$keyword][] = $filename;
			}
		}
		return true;
	}

	function unsetKeywords($filename){
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		$keywords = explode(';', @$this->indexes['main'][$filename]['keywords']);
		if(!empty($keywords)){
			foreach ($keywords as $keyword){
				$keyword = trim($keyword);
				$k = @array_search($filename, $this->indexes['keywords'][$keyword]);
				@rcms_remove_index($k, $this->indexes['keywords'][$keyword], true);
			}
		}
		return true;
	}


	function changeKeywords($filename, $keywords){
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		if(!empty($this->indexes['main'][$filename]['keywords'])){
			$this->unsetKeywords($filename);
		}
		$this->setKeywords($filename, $keywords);
		return true;
	}

	function removeImage($filename){
		$this->unregisterInIndex($filename);
		if(is_file(GALLERY_IMAGES_DIR . $filename)) rcms_delete_files(GALLERY_IMAGES_DIR . $filename);
		if(is_file(GALLERY_COMMENTS_DIR . $filename . '.dat')) rcms_delete_files(GALLERY_COMMENTS_DIR . $filename . '.dat');
		if(is_file(GALLERY_THUMBS_DIR . $filename . '.jpg')) rcms_delete_files(GALLERY_THUMBS_DIR . $filename . '.jpg');
		return true;
	}

	function saveIndexFiles(){
		file_write_contents(GALLERY_INDEXES_DIR . 'main.dat', serialize($this->indexes['main']));
		file_write_contents(GALLERY_INDEXES_DIR . 'filename.dat', serialize($this->indexes['filename']));
		file_write_contents(GALLERY_INDEXES_DIR . 'title.dat', serialize($this->indexes['title']));
		file_write_contents(GALLERY_INDEXES_DIR . 'size.dat', serialize($this->indexes['size']));
		file_write_contents(GALLERY_INDEXES_DIR . 'type.dat', serialize($this->indexes['type']));
		file_write_contents(GALLERY_INDEXES_DIR . 'keywords.dat', serialize($this->indexes['keywords']));
		return true;
	}

	function getImages($directory){
		$directory = rcms_scandir($directory);
		$images = array();
		foreach ($directory as $file){
			if (preg_match($this->img_preg, $file)) {
				$images[] = $file;
			}
		}
		return $images;
	}

	function getFullImagesList(){
		$temp = $this->indexes['filename'];
		$this->indexes['filename'] = array();
		foreach ($temp as $key => $data){
			if(!empty($data)) $this->indexes['filename'][$key] = $data;
		}
		$this->saveIndexFiles();
		natsort($this->indexes['filename']);
		return $this->indexes['filename'];
	}

	function getAvaiableValues($field){
		if(empty($this->indexes[$field])) return false;
		$result = array();
		foreach (array_keys($this->indexes[$field]) as $key){
			if(!empty($this->indexes[$field][$key])){
				$result[] = $key;
			}
		}
		natsort($result);
		return $result;
	}

	function getLimitedImagesList($field, $value){
		if(empty($this->indexes[$field][$value])) return false;
		$result = array();
		foreach ($this->indexes[$field][$value] as $image){
			if(in_array($image, $this->indexes['filename'])) $result[] = $image;
		}
		natsort($result);
		return $result;
	}

	function getImage($filename){
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		return '<div style="overflow: hidden; width: 100%;"><a href="' . GALLERY_IMAGES_DIR . $filename . '" target="_blank"><img src="' . GALLERY_IMAGES_DIR . $filename . '" alt="' . $this->indexes['main'][$filename]['title'] . '" style="max-width: 95%;" /></a></div>';
	}

	function getData($filename){
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		return $this->indexes['main'][$filename];
	}

	function getComments($filename){
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		if(true){
			$comments = guestbook_get_last_msgs(
			null,
			true,
			false,
			GALLERY_COMMENTS_DIR . $filename . '.dat'
			);
			foreach ($comments as $mid => $message) {
				$comments[$mid]['id'] = $mid;
			}
			return $comments;
		}
		return false;
	}


	function countComments($filename){
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		if(true){
			return count(guestbook_get_last_msgs(null, false, false, GALLERY_COMMENTS_DIR . $filename . '.dat'));
		}
		return false;
	}

	function postComment($filename, $text){
		global $system;
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		if(!empty($text)){
			return guestbook_post_msg($system->user['username'],
			$system->user['nickname'],
			$text,
			GALLERY_COMMENTS_DIR . $filename . '.dat'
			);
		}
	}

	function removeComment($filename, $cid){
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		return guestbook_post_remove($cid, GALLERY_COMMENTS_DIR . $filename . '.dat');
	}

	function getThumbnail($filename, $mw = 150, $mh = 150){
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		if($return = $this->generateThumbnail($filename, $mw, $mh)){
			return $return;
		} else {
			$path = GALLERY_IMAGES_DIR . $filename;
			$stat = getimagesize($path);
			$iw = $stat[0];
			$ih = $stat[1];
			if(($iw > $mh) || ($iw < $mw)) {
				$sizefactor = (($ih > $iw) ? ($mh / $ih) : ($mw / $iw));
			} else {
				$sizefactor =1;
			}
			$nw = (int) ($iw * $sizefactor);
			$nh = (int) ($ih * $sizefactor);
			unset($sizefactor);
			return '<img src="' . $path . '" width="' . $nw . '" height="' . $nh . '" border="0" alt="" />';
		}
	}

	function generateThumbnail($filename, $mw, $mh){
		$path = GALLERY_IMAGES_DIR . $filename;
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename) || !preg_match($this->gd_preg, $filename)) return false;
		if(!is_file(GALLERY_THUMBS_DIR . $filename . '.jpg')){
			if(substr(strtolower($filename), -4) == '.jpg') {
				$img = imagecreatefromjpeg($path);
			} elseif(substr(strtolower($filename), -4) == '.gif') {
				$img = imagecreatefromgif($path);
			} elseif(substr(strtolower($filename), -4) == '.png') {
				$img = imagecreatefrompng($path);
			} else return false;
			if(!empty($img)){
				$stat = getimagesize($path);
				$iw = $stat[0];
				$ih = $stat[1];
				if(($iw > $mh) || ($iw < $mw)) {
					$sizefactor = (($ih > $iw) ? ($mh / $ih) : ($mw / $iw));
				} else {
					$sizefactor =1;
				}
				$nw = (int) ($iw * $sizefactor);
				$nh = (int) ($ih * $sizefactor);
				unset($sizefactor);
				$thumb = imagecreatetruecolor($nw, $nh);
				imagecopyresampled($thumb, $img, 0, 0, 0, 0, $nw, $nh, $iw, $ih);
				imagejpeg($thumb, GALLERY_THUMBS_DIR . $filename . '.jpg');
				imagedestroy($thumb);
				imagedestroy($img);
			}
		}
		return '<img src="' . GALLERY_THUMBS_DIR . $filename . '.jpg" alt="' . $filename . '" />';
	}

	function setDataValue($filename, $dataname, $value){
		if(empty($this->indexes['main'][$filename]) || !is_file(GALLERY_IMAGES_DIR . $filename)) return false;
		$this->indexes['main'][$filename][$dataname] = $value;
		$this->indexes[$dataname][$filename] = $value;
		return true;
	}
}
?>