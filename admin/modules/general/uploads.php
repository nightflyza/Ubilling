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
rcms_loadAdminLib('file-uploads');

/******************************************************************************
* Perform uploading                                                           *
******************************************************************************/
if(!empty($_FILES['upload'])) {
    if(fupload_array($_FILES['upload'])){
        rcms_showAdminMessage(__('Files uploaded'));
    } else {
        rcms_showAdminMessage(__('Error occurred'));
    }
}

/******************************************************************************
* Perform deletion                                                            *
******************************************************************************/
if(!empty($_POST['delete'])) {
    $result = '';
    foreach ($_POST['delete'] as $file => $cond){
        $file = basename($file);
        if(!empty($cond)) {
            if(fupload_delete($file)) $result .= __('File removed') . ': ' . $file . '<br>';
            else $result .= __('Error occurred') . ': ' . $file . '<br>';
        }
    }
    if(!empty($result)) rcms_showAdminMessage($result);
}

/******************************************************************************
* Interface                                                                   *
******************************************************************************/
$frm =new InputForm ('', 'post', __('Submit'), '', '', 'multipart/form-data');
$frm->addbreak(__('Upload files'));
$frm->addrow(__('Select files to upload'), $frm->file('upload[]') . $frm->file('upload[]') . $frm->file('upload[]'), 'top');
$frm->show();
$files = fupload_get_list();
$frm =new InputForm ('', 'post', __('Submit'));
$frm->addbreak(__('Uploaded files'));
if(!empty($files)) {
    foreach ($files as $file) {
        $frm->addrow(__('Filename') . ' = ' . $file['name'] . ' [' . __('Size of file') . ' = ' . $file['size'] . '] [' . __('Last modification time') . ' = ' . date("d F Y H:i:s", $file['mtime']) . ']', $frm->checkbox('delete[' . $file['name'] . ']', 'true', __('Delete')), 'top');
    }
}
$frm->show();
?>