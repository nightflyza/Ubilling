<?php

if(cfr('SWITCHES')) {


if (isset($_POST['newswitchmodel'])) {
    $modelid=$_POST['newswitchmodel'];
    $ip=$_POST['newip'];
    $desc=$_POST['newdesc'];
    $location=$_POST['newlocation'];
    $snmp=$_POST['newsnmp'];
    ub_SwitchAdd($modelid, $ip, $desc, $location, $snmp);
    rcms_redirect("?module=switches");
}

if (isset($_GET['switchdelete'])) {
	if (!empty($_GET['switchdelete'])) {
	ub_SwitchDelete($_GET['switchdelete']);
        rcms_redirect("?module=switches");
	}
}


if (!isset($_GET['edit'])) {
show_window('',  wf_Link('?module=switchmodels', 'Available switch models', true, 'ubButton'));
show_window(__('Available switches'), web_SwitchesShow());
show_window(__('Add switch'),  web_SwitchFormAdd());

} else {
    //editing switch form
    $switchid=vf($_GET['edit'],3);
    $allswitchmodels=zb_SwitchModelsGetAllTag();
    $switchdata=zb_SwitchGetData($switchid);
    
    //if someone edit switch 
    if (wf_CheckPost(array('editmodel'))) {
        simple_update_field('switches', 'modelid', $_POST['editmodel'], "WHERE `id`='".$switchid."'");
        simple_update_field('switches', 'ip', $_POST['editip'], "WHERE `id`='".$switchid."'");
        simple_update_field('switches', 'location', $_POST['editlocation'], "WHERE `id`='".$switchid."'");
        simple_update_field('switches', 'desc', $_POST['editdesc'], "WHERE `id`='".$switchid."'");
        simple_update_field('switches', 'snmp', $_POST['editsnmp'], "WHERE `id`='".$switchid."'");
        log_register('SWITCH CHANGE '.$switchid);
        rcms_redirect("?module=switches");
    }
     
    $editinputs=wf_Selector('editmodel', $allswitchmodels, 'Model', $switchdata['modelid'], true);
    $editinputs.=wf_TextInput('editip', 'IP', $switchdata['ip'], true, 20);
    $editinputs.=wf_TextInput('editlocation', 'Location', $switchdata['location'], true, 20);
    $editinputs.=wf_TextInput('editdesc', 'Description', $switchdata['desc'], true, 20);
    $editinputs.=wf_TextInput('editsnmp', 'SNMP community', $switchdata['snmp'], true, 20);
    $editinputs.=wf_Submit('Save');
    $editform=wf_Form('', 'POST', $editinputs, 'glamour');
    show_window(__('Edit switch'),$editform);
    show_window('',  wf_Link('?module=switches', 'Back',true, 'ubButton'));
    }

}
else {
	show_error(__('Access denied'));
}
?>