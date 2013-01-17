<?php
if (cfr('CASHTYPES')) {
    if (isset($_GET['action'])) {
        // delete cash type
        if ($_GET['action']=='delete') {
            $cashtypeid=vf($_GET['id'],3);
            //check for default cash type
            if ($cashtypeid!=1) {
            zb_CashDeleteCashtype($cashtypeid);
            rcms_redirect("?module=cashtypes");
            } else {
                show_window(__('Error'), __('You know, we really would like to let you perform this action, but his conscience does not allow us to do'));
            }
        }
        
        //cash type editing
        if ($_GET['action']=='edit') {
            $cashtypeid=vf($_GET['id'],3);
            
            if (wf_CheckPost(array('editcashtype'))) {
                simple_update_field('cashtype', 'cashtype', $_POST['editcashtype'], "WHERE `id`='".$cashtypeid."'");
                log_register('EDIT CASHTYPE '.$cashtypeid);
                rcms_redirect("?module=cashtypes");
            }
            
            $cashtypename=zb_CashGetTypeName($cashtypeid);
            $editinputs=wf_TextInput('editcashtype', 'Cash type', $cashtypename, true, '10');
            $editinputs.=wf_Submit('Save');
            $editform=wf_Form('', 'POST', $editinputs, 'glamour');
            $editform.=wf_Link('?module=cashtypes', 'Back', true, 'ubButton');
            show_window(__('Edit').' '.__('Cash type'), $editform);
           }
        
      }
      
    if (isset($_POST['newcashtype'])) {
        $newcashtype=mysql_real_escape_string($_POST['newcashtype']);
        zb_CashCreateCashType($newcashtype);
    }
    
    

    
// Edit form construct
$fieldname=__('Cash type');
$fieldkey='cashtype';
$formurl='?module=cashtypes';
$olddata=zb_CashGetAlltypes();

$form=web_EditorTableDataFormOneField($fieldname, $fieldkey, $formurl, $olddata);

show_window(__('Edit payment types'), $form);


} else {
      show_error(__('You cant control this module'));
}

?>
