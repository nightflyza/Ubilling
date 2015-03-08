<?php
$altcfg=  rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
if(cfr('SWITCHLOGIN')) {
	if ($altcfg['SWITCH_AUTOCONFIG']) {
		if (wf_CheckGet(array('ajax'))) {
			if ($_GET['ajax']=='snmp') {
				$cell = wf_HiddenInput('add', 'true');
				$cell.= wf_HiddenInput('swmethod', 'SNMP');
				$cell.= sw_selector() . ' ' . __('SwitchID') . ' ' . wf_tag('br');
				$cell.= wf_TextInput('rwcommunity', __('SNMP community'));
				$cell.= wf_Tag('br');
				$cell.= wf_Submit(__('Save'));
				$Row = wf_TableRow($cell, 'row1');
				$form=  wf_Form("", 'POST', $Row, 'glamour');
				$result=$form;
				die($result);
			}
			if ($_GET['ajax']=='connect') {
				$conn = array ('SSH'=>__('SSH'), 'TELNET'=>__('TELNET'));
				$enable = array ( 'no'=>__('no'), 'yes'=> __('yes'));
				$cell = wf_HiddenInput('add', 'true');
				$cell.= sw_selector() . ' ' . __('SwitchID') . ' ' . wf_tag('br');
				$cell.= wf_Selector('swmethod',$conn,__('Connection method'),'SSH',true);
				$cell.= wf_Tag('br');
				$cell.= wf_TextInput('swlogin', __('Login'));
				$cell.= wf_TextInput('swpassword', __('Password'));
				$cell.= wf_Tag('br');
				$cell.= wf_Selector('enable', $enable, __('enable propmpt for cisco,bdcom,etc (should be same as password)'), '',true);
				$cell.= wf_Submit(__('Save'));
				$cell.= wf_delimiter();
				$Row = wf_TableRow($cell, 'row1');
				$form=  wf_Form("", 'POST', $cell, 'glamour');
				$result=$form;
				die($result);
			}
		}
		if(!isset($_GET['edit'])) {
			$megaForm=  wf_AjaxLoader();
			$megaForm.= wf_AjaxLink('?module=switchlogin&ajax=snmp', 'SNMP', 'megaContainer1', false, 'ubButton');
			$megaForm.= wf_AjaxLink('?module=switchlogin&ajax=connect', 'Connect', 'megaContainer1', false, 'ubButton');
			$megaForm.= wf_tag('div', false, '', 'id="megaContainer1"').  wf_tag('div',true);
			show_window('Switches',$megaForm);
		}
		show_all_switchlogin();
	
		if(isset($_POST['add'])) {
			if(isset($_POST['swmodel'])) { $model=$_POST['swmodel']; } else { show_error('No switch selected'); }
			if(isset($_POST['swlogin'])) { $login=$_POST['swlogin']; } else { $login=''; }
               		if(isset($_POST['swpassword'])) { $pass=$_POST['swpassword']; } else { $pass=''; }
               		if(isset($_POST['swmethod'])) { $method=$_POST['swmethod']; } else { show_error('No method selected'); }
             		if(isset($_POST['rwcommunity'])) { $community=$_POST['rwcommunity']; } else { $community=''; }
               		if(isset($_POST['enable'])) { $enable=$_POST['enable']; } else { $enable=''; }
			swlogin_add($model,$login,$pass,$method,$community,$enable);
			rcms_redirect("?module=switchlogin");
		}
		if(isset($_GET['delete'])) {
			swlogin_delete($_GET['delete']);
			rcms_redirect("?module=switchlogin");
		}
		if(isset($_GET['edit'])) {
			swlogin_edit_form($_GET['edit']);
		}
		if(isset($_POST['edit'])) {
			$id=$_GET['edit'];
			simple_update_field('switch_login', 'swid', $_POST['swmodel'], "WHERE `id`='".$id."'");
			simple_update_field('switch_login', 'swlogin', $_POST['editswlogin'], "WHERE `id`='".$id."'");
			simple_update_field('switch_login', 'swpass', $_POST['editswpassword'], "WHERE `id`='".$id."'");
			simple_update_field('switch_login', 'method', $_POST['editconn'], "WHERE `id`='".$id."'");
			simple_update_field('switch_login', 'community', $_POST['editrwcommunity'], "WHERE `id`='".$id."'");
			simple_update_field('switch_login', 'enable', $_POST['editenable'], "WHERE `id`='".$id."'");
			log_register('MODIFY Vlan Terminator ['.$id.']');
			rcms_redirect("?module=switchlogin");
		}
	} else {
	show_error('You cant control this module');
	}
} else {
show_error("SWITCH_AUTOCONFIG is disabled");
}
?>
