<?php
if (cfr('DSHAPER')) {

function web_DshapeShowTimeRules() {
    $query="SELECT * from `dshape_time`";
    $allrules=simple_queryall($query);
    $result='<table width="100%" border="0" class="sortable">';
    $result.='
                <tr class="row1">
                <td>'.__('ID').'</td>
                <td>'.__('Tariff').'</td>
                <td>'.__('Time from').'</td>
                <td>'.__('Time to').'</td>
                <td>'.__('Speed').'</td>
                <td>'.__('Actions').'</td>
                </tr>
                ';
    if (!empty ($allrules)) {
        foreach ($allrules as $io=>$eachrule) {
            $result.='
                <tr class="row3">
                <td>'.$eachrule['id'].'</td>
                <td>'.$eachrule['tariff'].'</td>
                <td>'.$eachrule['threshold1'].'</td>
                <td>'.$eachrule['threshold2'].'</td>
                <td>'.$eachrule['speed'].'</td>
                <td>
                  '.  wf_JSAlert('?module=dshaper&delete='.$eachrule['id'], web_delete_icon(), 'Removing this may lead to irreparable results').'
                  <a href="?module=dshaper&edit='.$eachrule['id'].'">'.web_edit_icon().'</a>
                </td>
                </tr>
                ';
        }
    }
    $result.='</table>';
    show_window(__('Available dynamic shaper time rules'),$result);
}

function zb_DshapeDeleteTimeRule($ruleid) {
    $ruleid=vf($ruleid);
    $query="DELETE from dshape_time where `id`='".$ruleid."'";
    nr_query($query);
    log_register("DSHAPE DELETE ".$ruleid);
}

function web_DshapeShowTimeRuleAddForm() {
    $form='
        <form action="" method="POST">
        '.  web_tariffselector('newdshapetariff').' '.__('Tariff').'<br>
        <input type="text" name="newthreshold1"> '.__('Time from').'<sup>*</sup> <br>
        <input type="text" name="newthreshold2"> '.__('Time to').'<sup>*</sup> <br>
        <input type="text" name="newspeed"> '.__('Speed').'<sup>*</sup> <br>
        <input type="submit" value="'.__('Create').'">
        </form> 
        <br>
        ';
    show_window(__('Add new time shaper rule'),$form);
}

function web_DshapeShowTimeRuleEditForm($timeruleid) {
    $timeruleid=vf($timeruleid);
    $query="SELECT * from `dshape_time` WHERE `id`='".$timeruleid."'";
    $timerule_data=simple_query($query);
    $form='
        <form action="" method="POST">
        <input type="text" name="editdshapetariff" value="'.$timerule_data['tariff'].'" READONLY> '.__('Tariff').'<br>
        <input type="text" name="editthreshold1" value="'.$timerule_data['threshold1'].'"> '.__('Time from').'<sup>*</sup> <br>
        <input type="text" name="editthreshold2" value="'.$timerule_data['threshold2'].'"> '.__('Time to').'<sup>*</sup> <br>
        <input type="text" name="editspeed" value="'.$timerule_data['speed'].'"> '.__('Speed').'<sup>*</sup> <br>
        <input type="submit" value="'.__('Save').'">
        </form> 
        <br>
        ';
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
    log_register("DSHAPE ADD ".$tariff);
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
    log_register("DSHAPE CHANGE ".$timeruleid);
}

//debug

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
      show_error(__('You cant control this module'));
}

?>
