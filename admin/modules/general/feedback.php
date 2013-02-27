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

if(!empty($_POST['d'])) {
    foreach ($_POST['d'] as $id => $cond){
        if($cond) guestbook_post_remove($id, DF_PATH . 'support.dat');
    }
}

$messages = guestbook_get_msgs(null, true, false, DF_PATH . 'support.dat');
$frm =new InputForm ('', 'post', __('Submit'));
$frm->addbreak(__('Feedback requests'));
foreach ($messages as $id => $message) {
    $frm->addrow('[' . rcms_format_time('d F Y H:i:s', $message['time'], $system->user['tz']) . '] ' . __('Message by') . ' ' . user_create_link($message['username'], $message['nickname']) . '<hr>' . $message['text'],
        $frm->checkbox('d[' . $id . ']', '1', __('Delete'))
    );
}
$frm->show();
?>