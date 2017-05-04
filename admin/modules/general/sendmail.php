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

if(!empty($_POST['send'])){
    $_list = explode(',', $_POST['to']);
    $list = array();
    foreach ($_list as $user_mask){
        $user_mask = trim($user_mask);
        $users = user_get_list($user_mask);
        foreach ($users as $userdata){
            $list[] = $userdata['email'];
        }
    }
    if(!empty($list) && !empty($_POST['subj']) && !empty($_POST['body'])){
        $to = implode(';', $list);
        rcms_send_mail($to, $system->user['email'], $system->user['nickname'], $system->config['encoding'], $_POST['subj'] , $_POST['body']);
    }
}

// Interface generation
$frm =new InputForm ('', 'post', __('Send e-mail'));
$frm->addbreak( __('Send e-mail'));
$frm->hidden('send', '1');
$frm->addrow(__('Users') . '<br>' . __('You can use * in names and divide names by comma.'), $frm->text_box('to', '*', 60));
$frm->addrow(__('Subject'), $frm->text_box('subj', '', 60));
$frm->addrow(__('Body'), $frm->textarea('body', '', 60, 10));
$frm->show();
?>
