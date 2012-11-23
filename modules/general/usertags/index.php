<?php

if(cfr('TAGS')) {
$alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    

if (!isset($_GET['username'])) {
if (isset ($_POST['addnewtag'])) {
    if (wf_CheckPost(array('newtext'))) {
        stg_add_tagtype();
        rcms_redirect("?module=usertags");
    } else {
        show_window(__('Error'), __('Required fields'));
    }

}

//if someone deleting tagtype
if (isset($_GET['delete'])) {
    stg_delete_tagtype($_GET['delete']);
    rcms_redirect("?module=usertags");
}

//if someone wants to edit tagtype
if (isset ($_GET['edit'])) {
    $tagtypeid=vf($_GET['edit'],3);
    
    if (isset($_POST['edittagcolor'])) {
        simple_update_field('tagtypes', 'tagcolor', $_POST['edittagcolor'], "WHERE `id`='".$tagtypeid."'");
        simple_update_field('tagtypes', 'tagsize', $_POST['edittagsize'], "WHERE `id`='".$tagtypeid."'");
        simple_update_field('tagtypes', 'tagname', $_POST['edittagname'], "WHERE `id`='".$tagtypeid."'");
        log_register("TAGTYPE CHANGE ".$tagtypeid);
        rcms_redirect("?module=usertags");
    }
    
    //form construct
    $tagpriorities=array('1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6');
    $tagtypedata=stg_get_tagtype_data($tagtypeid);
    $editinputs=wf_TextInput('edittagcolor', 'Color', $tagtypedata['tagcolor'], true, 20);
    $editinputs.=wf_TextInput('edittagname', 'Text', $tagtypedata['tagname'], true, 20);
    $editinputs.=wf_Selector('edittagsize', $tagpriorities, 'Priority', $tagtypedata['tagsize'], true);
    $editinputs.=wf_Submit('Save');
    $editform=wf_Form('', 'POST', $editinputs, 'glamour');
    
    show_window(__('Edit'),$editform);
    show_window('', wf_Link("?module=usertags", 'Back', true, 'ubButton'));
}

//show available tagtypes
stg_show_tagtypes();

} else {
$uname=$_GET['username'];
if (isset($_POST['tagselector'])) {
 stg_add_user_tag($uname, $_POST['tagselector']);
 //reset user if needed
 if ($alter_conf['RESETONTAGCHANGE']) {
     $billing->resetuser($uname);
     log_register("RESET User ".$uname);
 }
}

if (isset ($_GET['deletetag'])) {
stg_del_user_tag($_GET['deletetag']);
 //reset user if needed
 if ($alter_conf['RESETONTAGCHANGE']) {
     $billing->resetuser($uname);
     log_register("RESET User ".$uname);
 }
rcms_redirect("?module=usertags&username=".$uname);
}
show_window(__('Tags'),stg_show_user_tags($uname));
stg_tagadd_selector();
stg_tagdel_selector($uname);
show_window('',  web_UserControls($uname));
}



}
else {
	show_error(__('Access denied'));
}
?>