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

function ucm_create($id, $title, $data, $align = 'left') {
    $id = basename(trim($id));
    if(preg_replace("/[a-z0-9]*/i", '', $id) != '' || empty($id)) return false;
    if(is_file(DF_PATH . $id . '.ucm')) return false;
    if(file_write_contents(DF_PATH . $id . '.ucm', $title . "\n" . $align . "\n" . $data)){
        return true;
    } else return false;
}

function ucm_change($id, $newid, $title, $data, $align = 'left'){
    $id = basename($id);
    $newid = basename($newid);
    if(preg_replace("/[a-z0-9]*/i", '', $id) != '' || empty($id)) return false;
    if(preg_replace("/[a-z0-9]*/i", '', $newid) != '' || empty($newid)) return false;
    if(!is_file(DF_PATH . $id . '.ucm')) return false;
    if($id != $newid && is_file(DF_PATH . $newid . '.ucm')) return false;
    if(!file_write_contents(DF_PATH . $id . '.ucm', $title . "\n" . $align . "\n" . $data)) return false;
    rcms_rename_file(DF_PATH . $id . '.ucm', DF_PATH . $newid . '.ucm');
    if($id != $newid){
        $config = file_get_contents(CONFIG_PATH . 'menus.ini');
        $config = str_replace('"ucm:' . $id . '"', '"ucm:' . $newid . '"', $config);
    }
    return true;
}

function ucm_delete($id) {
    $filename = basename($id) . '.ucm';
    if(!is_file(DF_PATH . $filename)) return false;
    if(rcms_delete_files(DF_PATH . $filename)) {
        $config = file_get_contents(CONFIG_PATH . 'menus.ini');
        $config = preg_replace('/[0-9]* = "ucm:' . $id . '"\s/i', '', $config);
        file_write_contents(CONFIG_PATH . 'menus.ini', $config);
        return true;
    } else return false;
}

function ucm_list(){
    $files = rcms_scandir(DF_PATH, '*.ucm');
    $return = array();
    foreach ($files as $filename){
        $file = file(DF_PATH . $filename);
        $title = preg_replace("/[\n\r]+/", '', $file[0]);
        $align = preg_replace("/[\n\r]+/", '', $file[1]);
        unset($file[0]);
        unset($file[1]);
        $return[substr($filename, 0, -4)] = array($title, implode('', $file), $align);
        $file = '';
    }
    return $return;
}

function ucm_get($id){
    $filename = basename($id) . '.ucm';
    if(!is_file(DF_PATH . $filename)) return false;
    $file = file(DF_PATH . $filename);
    $title = preg_replace("/[\n\r]+/", '', $file[0]);
    $align = preg_replace("/[\n\r]+/", '', $file[1]);
    unset($file[0]);
    unset($file[1]);
    return array($title, implode('', $file), $align);
}
?>