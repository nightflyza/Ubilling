<?php
if($system->checkForRight('STGNEWADMIN')) {

if (!isset($_POST['registration_form'])) {
 show_window(__('Administrator registration'), rcms_parse_module_template('user-profile.tpl', array('mode' => 'registration_form','fields' => $system->data['apf'])));
} 
if (isset($_POST['registration_form'])) {
	$system->registerUser($_POST['username'], $_POST['nickname'], @$_POST['password'], @$_POST['confirmation'], $_POST['email'], $_POST['userdata']); 
	$system->updateUser($_POST['username'], $_POST['nickname'], $_POST['password'], $_POST['confirmation'], $_POST['email'], $_POST['userdata']);
	stg_putlogevent('ADMREG '.$_POST['username']);
	show_window(__('Administrator registered'), '<a href="?module=permissions">'.__('His permissions you can setup via corresponding module').'</a>');
}

}
else {
	show_error(__('Access denied'));
}

?>