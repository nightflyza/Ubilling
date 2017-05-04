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
if(!empty($_POST['ban'])) {
    file_write_contents(CONFIG_PATH . 'bans.ini', @implode("\n", @$_POST['ban']));
}
 
if(!$banlist = @file(CONFIG_PATH . 'bans.ini')) $banlist = array();

$frm = new InputForm ('', 'post', __('Submit'));
$frm->addbreak(__('Manage banned IP addresses'));
foreach ($banlist as $ban){
    $ban = trim($ban);
    if(!empty($ban)) $frm->addrow($frm->text_box('ban[]', $ban, 40), '', 'middle', 'center');
}
$frm->addrow($frm->text_box('ban[]', '', 40), '', 'middle', 'center');
$frm->addmessage(__('If you want to remove ip address leave it\'s string empty. If you want to add new ip address write it in the last field. You can use * that will match only one part of ip address.'));
$frm->show();
?>