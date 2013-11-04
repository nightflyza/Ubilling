<?php
if (cfr('DSHAPER')) {

function web_DshapeShowTimeRules() {
    $query="SELECT * from `dshape_time`";
    $allrules=simple_queryall($query);

    $cells=  wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('Tariff'));
    $cells.= wf_TableCell(__('Time from'));
    $cells.= wf_TableCell(__('Time to'));
    $cells.= wf_TableCell(__('Speed'));
    $cells.= wf_TableCell(__('Actions'));
    $rows= wf_TableRow($cells, 'row1');
    
    if (!empty ($allrules)) {
        foreach ($allrules as $io=>$eachrule) {
            $cells=  wf_TableCell($eachrule['id']);
            $cells.= wf_TableCell($eachrule['tariff']);
            $cells.= wf_TableCell($eachrule['threshold1']);
            $cells.= wf_TableCell($eachrule['threshold2']);
            $cells.= wf_TableCell($eachrule['speed']);
            $actions=  wf_JSAlert('?module=dshaper&delete='.$eachrule['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $actions.= wf_JSAlert('?module=dshaper&edit='.$eachrule['id'], web_edit_icon(), __('Are you serious'));
            $cells.= wf_TableCell($actions);
            $rows.= wf_TableRow($cells, 'row3');
            }
    }
    
    $result=  wf_TableBody($rows, '100%', '0', 'sortable');
    show_window(__('Available dynamic shaper time rules'),$result);
}

function zb_DshapeDeleteTimeRule($ruleid) {
    $ruleid=vf($ruleid);
    $query="DELETE from dshape_time where `id`='".$ruleid."'";
    nr_query($query);
    log_register("DSHAPE DELETE [".$ruleid.']');
}

function web_DshapeShowTimeRuleAddForm() {
    $sup=  wf_tag('sup').'*'.wf_tag('sup', true);
    
    $inputs=  web_tariffselector('newdshapetariff').' '.__('Tariff').wf_tag('br');
    $inputs.= wf_TextInput('newthreshold1', __('Time from').$sup, '', true);
    $inputs.= wf_TextInput('newthreshold2', __('Time to').$sup, '', true);
    $inputs.= wf_TextInput('newspeed', __('Speed').$sup, '', true);
    $inputs.= wf_Submit(__('Create'));
    $form=  wf_Form('', 'POST', $inputs, 'glamour');
    
    show_window(__('Add new time shaper rule'),$form);
}

function web_DshapeShowTimeRuleEditForm($timeruleid) {
    $timeruleid=vf($timeruleid);
    $query="SELECT * from `dshape_time` WHERE `id`='".$timeruleid."'";
    $timerule_data=simple_query($query);
    
    $sup=  wf_tag('sup').'*'.wf_tag('sup', true);
    
    $inputs= wf_tag('input', false, '', 'type="text" name="editdshapetariff" value="'.$timerule_data['tariff'].'" READONLY').  wf_tag('br');
    $inputs.= wf_TextInput('editthreshold1', __('Time from').$sup, $timerule_data['threshold1'], true);
    $inputs.= wf_TextInput('editthreshold2', __('Time to').$sup, $timerule_data['threshold2'], true);
    $inputs.= wf_TextInput('editspeed', __('Speed').$sup, $timerule_data['speed'], true);
    $inputs.= wf_Submit(__('Save'));
    $form=  wf_Form('', 'POST', $inputs, 'glamour');
    $form.= wf_Link('?module=dshaper', __('Back'), true, 'ubButton');
    
    
    show_window(__('Edit time shaper rule'),$form);
}


function zb_DshapeAddTimeRule($tariff,$threshold1,$threshold2,$speed) {
    $tariff=mysql_real_escape_string($tariff);
    $threshold1=mysql_real_escape_string($threshold1);
    $threshold2=mysql_real_escape_string($threshold2);
    $speed=vf($speed);
    
    $query="INSERT INTO `dshape_time` (
    `id` ,
    `tariff` ,
    `threshold1` ,
    `threshold2` ,
    `speed`
        )
    VALUES (
    NULL , '".$tariff."', '".$threshold1."', '".$threshold2."', '".$speed."'
    );";
    nr_query($query);
    log_register("DSHAPE ADD `".$tariff.'`');
}

function zb_DshapeEditTimeRule($timeruleid,$threshold1,$threshold2,$speed) {
    $timeruleid=vf($timeruleid);
    $threshold1=mysql_real_escape_string($threshold1);
    $threshold2=mysql_real_escape_string($threshold2);
    $speed=vf($speed);
    $query="UPDATE `dshape_time` SET 
        `threshold1` = '".$threshold1."',
        `threshold2` = '".$threshold2."',
        `speed` = '".$speed."' WHERE `id` ='".$timeruleid."' LIMIT 1;
       ";
    nr_query($query);
    log_register("DSHAPE CHANGE [".$timeruleid.'] ON `'.$speed.'`');
}

$alterconf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
if (isset($alterconf['DSHAPER_ENABLED'])) {
if ($alterconf['DSHAPER_ENABLED']) {
    

//if someone deleting time rule
if (isset($_GET['delete'])) {
    zb_DshapeDeleteTimeRule($_GET['delete']);
    rcms_redirect("?module=dshaper");
}

//if someone adding time rule

if (isset($_POST['newdshapetariff'])) {
    zb_DshapeAddTimeRule($_POST['newdshapetariff'], $_POST['newthreshold1'], $_POST['newthreshold2'], $_POST['newspeed']);
    rcms_redirect("?module=dshaper");
}

//timerule editing subroutine
if (isset ($_GET['edit'])) {
    if (isset($_POST['editdshapetariff'])) {
    zb_DshapeEditTimeRule($_GET['edit'], $_POST['editthreshold1'], $_POST['editthreshold2'], $_POST['editspeed']);
    rcms_redirect("?module=dshaper");
    }
    //show edit form
    web_DshapeShowTimeRuleEditForm($_GET['edit']);
}



//show time dshape list
web_DshapeShowTimeRules();
//show add form
web_DshapeShowTimeRuleAddForm();

} else {
    show_window(__('Error'), __('This module is disabled'));
 }
 
} else {
    show_window(__('Error'), __('This module is disabled'));
 }
//end of option enabled check

} else {
      show_error(__('You cant control this module'));
}

?>
