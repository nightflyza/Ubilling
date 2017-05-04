<?php
if($system->checkForRight('STGNEWADMIN')) {

if (!isset($_POST['registration_form'])) {
 show_window(__('Administrator registration'), rcms_parse_module_template('user-profile.tpl', array('mode' => 'registration_form','fields' => $system->data['apf'])));
 show_window('', wf_BackLink('?module=permissions'));
} 
if (isset($_POST['registration_form'])) {
    if (wf_CheckPost(array('username','nickname','password','confirmation','email'))) {
	$system->registerUser($_POST['username'], $_POST['nickname'], @$_POST['password'], @$_POST['confirmation'], $_POST['email'], $_POST['userdata']); 
	$system->updateUser($_POST['username'], $_POST['nickname'], $_POST['password'], $_POST['confirmation'], $_POST['email'], $_POST['userdata']);
	stg_putlogevent('ADMREG {'.$_POST['username'].'}');
	show_window(__('Administrator registered'), wf_link('?module=permissions&edit='.$_POST['username'],__('His permissions you can setup via corresponding module'),true,'ubButton'));
    } else {
        show_error(__('No all of required fields is filled'));
        show_window('', wf_BackLink('?module=adminreg'));
    }
}

}
else {
	show_error(__('Access denied'));
}

?>