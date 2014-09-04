<?php
if(cfr('VSERVICES')) {

    if (isset ($_POST['newfee'])) {
        $tagid=$_POST['newtagid'];
        $price=$_POST['newfee'];
        $cashtype=$_POST['newcashtype'];
        $priority=$_POST['newpriority'];
        if (!empty($price)) {
        zb_VserviceCreate($tagid, $price, $cashtype, $priority);
        rcms_redirect("?module=vservices");
        } else {
            show_window(__('Error'), __('Required fields'));
        }
    }
    
    if (isset($_GET['delete'])) {
        $vservid=$_GET['delete'];
        zb_VsericeDelete($vservid);
        rcms_redirect("?module=vservices");
    }
    
    if (wf_CheckGet(array('edit')) ){
        $editId=vf($_GET['edit'],3);
        if (wf_CheckPost(array('edittagid','editcashtype','editpriority','editfee'))) {
            simple_update_field('vservices', 'tagid', $_POST['edittagid'], "WHERE `id`='".$editId."'");
            simple_update_field('vservices', 'cashtype', $_POST['editcashtype'], "WHERE `id`='".$editId."'");
            simple_update_field('vservices', 'priority', $_POST['editpriority'], "WHERE `id`='".$editId."'");
            simple_update_field('vservices', 'price', $_POST['editfee'], "WHERE `id`='".$editId."'");
            log_register("CHANGE VSERVICE [".$editId."] PRICE `".$_POST['editfee']."`");
            rcms_redirect("?module=vservices");
        }
        show_window(__('Edit'), web_VserviceEditForm($_GET['edit']));
    } else {
    //show available services list
        web_VservicesShow();
    }
    
    
}
else {
	show_error(__('Access denied'));
}
?>
