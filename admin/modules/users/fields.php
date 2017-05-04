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
if(!empty($_POST['field_id']) && !empty($_POST['field_name'])){
    if(sizeof($_POST['field_id']) != sizeof($_POST['field_id'])){
        rcms_showAdminMessage(__('Cannot save configuration'));
    } else {
        $cnt = sizeof($_POST['field_id']);
        for($i = 0; $i < $cnt; $i++){
            if(!empty($_POST['field_id'][$i])) $result[$_POST['field_id'][$i]] = $_POST['field_name'][$i];
        }
        if(write_ini_file($result, CONFIG_PATH . 'users.fields.ini')){
        	rcms_showAdminMessage(__('Configuration updated'));
        	$system->data['apf'] = $result;
        } else rcms_showAdminMessage(__('Cannot save configuration'));
    }
}

// Interface generation
$frm = new InputForm ('', 'post', __('Submit'));
$frm->addbreak(__('Manage additional fields'));
$frm->addrow(__('ID'), __('Title'));
foreach ($system->data['apf'] as $field_id => $field_name){
    $frm->addrow($frm->text_box('field_id[]', $field_id), $frm->text_box('field_name[]', $field_name));
}
$frm->addrow($frm->text_box('field_id[]', ''), $frm->text_box('field_name[]', ''));
$frm->addmessage(__('If you want to remove field leave its id and name empty. If you want to add new item you must write its data must to the last fields.'));
$frm->show();
?>