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
define('DOWNLOADS_DATAFILE', DF_PATH . 'downloads.dat');

class linksdb{
    var $file = '';
    var $data = array();
    
    
    function __construct($file){
        $this->file = $file;
        if(is_readable($file) && $data = unserialize(file_get_contents($file))) $this->data = $data;
        else $this->data = array();
    }
    
    function createCategory($name, $desc, $level = 0){
        if(empty($name)) return false;
        $this->data[] = array('name' => $name, 'desc' => $desc, 'files' => array(), 'accesslevel' => $level);
        return true;
    }
    
    function updateCategory($id, $name, $desc, $level = 0){
        if(empty($name)) return false;
        if(empty($this->data[$id])) return false;
        $this->data[$id]['name'] = $name;
        $this->data[$id]['desc'] = $desc;
        $this->data[$id]['accesslevel'] = $level;
        return true;
    }
    
    function deleteCategory($id){
        if(!isset($this->data[$id])) return false;
        unset($this->data[$id]);
        return true;
    }
    
    function createFile($cid, $name, $desc, $link, $size, $author){
        if(empty($name)) return false;
        if(empty($this->data[$cid])) return false;
        $this->data[$cid]['files'][] = array('name' => $name, 'desc' => $desc, 'link' => $link, 'size' => $size, 'date' => rcms_get_time(),  'author' => $author, 'count' => 0);
        return true;
    }
    
    function updateFile($cid, $fid, $name, $desc, $link, $size, $author){
        if(empty($name)) return false;
        if(empty($this->data[$cid])) return false;
        if(empty($this->data[$cid]['files'][$fid])) return false;
        $this->data[$cid]['files'][$fid]['name'] = $name;
        $this->data[$cid]['files'][$fid]['desc'] = $desc;
        $this->data[$cid]['files'][$fid]['link'] = $link;
        $this->data[$cid]['files'][$fid]['size'] = $size;
        $this->data[$cid]['files'][$fid]['author'] = $author;
        return true;
    }
    
    function deleteFile($cid, $fid){
        if(empty($this->data[$cid])) return false;
        unset($this->data[$cid]['files'][$fid]);
        return true;
    }
    
    function getLastFiles($cnt){
        global $system;
        $files = array();
        foreach ($this->data as $cid => $cdata){
            if(@$cdata['accesslevel'] <= @$system->user['accesslevel'] || $system->checkForRight('-any-')) {
                foreach ($cdata['files'] as $fid => $fdata) {
                    $files[$cid . '.' . $fid] = $fdata['date'];
                }
            }
        }
        natsort($files);
        $files = array_slice($files, -10);
        $return = array();
        foreach ($files as $id=>$date){
            $return[] = explode('.', $id);
        }
        return $return;
    }
    
    function close(){
        $res = array();
        foreach ($this->data as $key => $value) if(!empty($value)) $res[$key] = $value;
        if(!($data = serialize($res))) return false;
        if(!(file_write_contents($this->file, $data))) return false;
        $this->file = '';
        $this->data = array();
        return true;
    }
}
?>