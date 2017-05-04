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

$system->config['pagename'] = __('Private message');

if (!LOGGED_IN) {
	show_window(__('Error'), __('Only for registered users'), 'center');
	return false;
};

if (isset($_GET['del']))
{
	pm_post_remove($_GET['del']);
}

if (isset($_GET['mode']))
{
  $adresatz='';
  $allusers=$system->getUserList('*', 'nickname');
  foreach($allusers as $key=>$eachuser) {
  $adresatz.='<a href="?module=pm&for='.$eachuser['username'].'">'.$eachuser['nickname'].'</a><br>';
  }
  show_window(__('Recipients'),$adresatz);
  
	$msgs = pm_get_all_msgs(10, true, false);
	$result= '<table width=99%>';
	foreach ($msgs as $val => $key){
		$bs= '';
		$be= '';
		if ($key['new'] == '1') {$bs="<b>"; $be="</b>";}
		$result.='<tr><td colspan=2 class="row3" align="left">&nbsp;'.$bs.$key['time'].' '.$key['nickname'].' ('.$key['username'].') '.__('wrote').$be.':</td></tr><tr class="row2"><td width = 20 valign = top>'.show_avatar($key['username']).'</td><td align="left">'.$key['text'].'</td></tr>
		<tr><td align="right" colspan=2><a href="?module=pm&for='.$key['username'].'&re='.$val.'">'.__('Reply').'</a>&nbsp;<a href="?module=pm&del='.$val.'&mode=get">'.__('Delete').'</a></tr>&nbsp;<td></td><tr></tr>';
	}
	$result.= '</table>';
	show_window(__('Your messages'), $result , 'center');
	pm_set_all_nonew(10, true, true);
	return false;
}

if (!isset($_POST['to']))
if (isset($_GET['for'])) 
{
$for=$_GET['for'];
$re='';
if (isset($_GET['re'])) {$re = '[quote]'.pm_get_msg_by_id(10, true, true, $_GET['re']).'[/quote]';}
$result = '<form method="post" action="" name="form1">'.rcms_show_bbcode_panel('form1.support_req').'<input type="hidden" name="to" value="'.$for.'" />
' . __('Message text') . ': <br><textarea name="support_req" width="90%" cols="60" rows="7">'.$re.'</textarea><p align="center"><input type="submit" value="' . __('Submit') . '" /></p></form>';
if (getUserData($_GET['for'])) show_window(__('Send private message for ').$for, $result, 'center');
if (!getUserData($_GET['for'])) show_window(__('Error'), __('User not exist'), 'center');
}
	
if (isset($_POST['to'])) 
{$to=$_POST['to'];
if (trim($to) <> '') {
	pm_post_msg($system->user['username'], $system->user['nickname'], $_POST['support_req'], $_POST['to']);
	show_window('', __('Message sent'), 'center');
	return false;
}
}

 
?>